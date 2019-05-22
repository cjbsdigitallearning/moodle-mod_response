<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy class for requesting user data.
 *
 * @package   mod_response
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\plugin\provider as pluginprovider;
use \core_privacy\local\request\core_userlist_provider as userlist_provider;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \context_module;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;
use \core_privacy\manager;

/**
 * Privacy class for requesting user data.
 *
 * @package   mod_response
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider, userlist_provider {

    /** Interface for all response type sub-plugins. */
    const RESPONSETYPE_INTERFACE = 'mod_response\privacy\responsetype_provider';

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        // The core plugin only has one table with user data.
        $responseuser = [
            'userid' => 'privacy:metadata:userid',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
            'timecompleted' => 'privacy:metadata:timecreated',
        ];
        $collection->add_database_table('response_user', $responseuser, 'privacy:metadata:responseuser');

        // We also need to connect to our subplugins.
        $collection->add_plugintype_link('responsetype', [], 'privacy:metadata:responsetypepluginsummary');
        return $collection;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $sql = "
            SELECT DISTINCT ctx.id
              FROM {response} r
              JOIN {modules} m
                ON m.name = :response
              JOIN {course_modules} cm
                ON cm.instance = r.id
               AND cm.module = m.id
              JOIN {context} ctx
                ON ctx.instanceid = cm.id
               AND ctx.contextlevel = :modulelevel
         LEFT JOIN {response_user} ru
                ON ru.response = r.id
               AND ru.userid = :userid";

        $params = [
            'response' => 'response',
            'modulelevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     *
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $sql = "
                SELECT DISTINCT ru.userid
                  FROM {response} r
                  JOIN {modules} m
                    ON m.name = :response
                  JOIN {course_modules} cm
                    ON cm.instance = r.id
                   AND cm.module = m.id
                  JOIN {context} ctx
                    ON ctx.instanceid = cm.id
                   AND ctx.contextlevel = :modulelevel
                  JOIN {response_user} ru
                    ON ru.response = r.id
                 WHERE ctx.id = :contextid";

        $params = [
            'response' => 'response',
            'modulelevel' => CONTEXT_MODULE,
            'contextid' => $context->id,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Write out the user data filtered by contexts.
     *
     * @param approved_contextlist $contextlist contexts that we are writing data out from.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        // Get the response IDs.
        $responseidstocmids = static::get_response_ids_to_cmids_from_cmids($cmids);

        // Prepare the common SQL fragments.
        list($inresponsesql, $inresponseparams) = $DB->get_in_or_equal(array_keys($responseidstocmids), SQL_PARAMS_NAMED);
        $sql = "userid = :userid AND response $inresponsesql";
        $params = array_merge($inresponseparams, ['userid' => $userid]);

        $recordset = $DB->get_recordset_select('response_user', $sql, $params);
        static::recordset_loop_and_export($recordset, 'response', null, function($carry, $record) {
            // There will only be one row per response activity, so no need to use $carry.
            return (object) [
                'firstaction' => $record->timecreated !== null ? transform::datetime($record->timecreated) : null,
                'lastchanged' => $record->timemodified !== null ? transform::datetime($record->timemodified) : null,
                'timecompleted' => $record->timecompleted !== null ? transform::datetime($record->timecompleted) : null,
            ];
        }, function($responseid, $data) use ($user, $responseidstocmids) {
            // This combines the overall completion data we need with the activity data.
            $context = context_module::instance($responseidstocmids[$responseid]->cmid);
            $contextdata = helper::get_context_data($context, $user);
            $finaldata = [
                'question' => $responseidstocmids[$responseid]->question,
                'completion' => $data,
            ];
            $finaldata = (object) array_merge((array) $contextdata, $finaldata);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $finaldata);
        });

        // Now call out to the subplugins. Also might as well reuse stuff we already queried that they will need.
        manager::plugintype_class_callback('responsetype', self::RESPONSETYPE_INTERFACE,
                'export_user_data', [$contextlist, $responseidstocmids, $userid]);

        return $contextlist;
    }

    /**
     * Delete all user data which matches the specified context.
     *
     * @param context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        // So we have a context, let's get the response id itself.
        $cm = get_coursemodule_from_id('response', $context->instanceid);
        if (!$cm) {
            return;
        }
        $responseid = $cm->instance;

        // First pass it to the subplugins in case they've declared foreign keys.
        manager::plugintype_class_callback('responsetype', self::RESPONSETYPE_INTERFACE,
                'delete_data_for_all_users_in_context', [$context, $responseid]);

        // Then delete what's left.
        $DB->delete_records('response_user', ['response' => $responseid]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $responseid = static::get_response_id_from_context($context);
        $userids = $userlist->get_userids();

        if (empty($responseid)) {
            return;
        }

        // First pass it to the subplugins in case they've declared foreign keys.
        manager::plugintype_class_callback('responsetype', self::RESPONSETYPE_INTERFACE,
                'delete_data_for_users', [$context, $responseid, $userids]);

        // Delete the response for the users.
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['responseid'] = $responseid;
        $sql = "response = :responseid AND userid {$insql}";

        $DB->delete_records_select('response_user', $sql, $inparams);
    }

    /**
     * Get a response ID from its context.
     *
     * @param context_module $context The module context.
     * @return int The instance id
     */
    protected static function get_response_id_from_context(context_module $context) {
        $cm = get_coursemodule_from_id('response', $context->instanceid);
        return $cm ? (int) $cm->instance : 0;
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        $userid = $user->id;
        $cmids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_MODULE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($cmids)) {
            return;
        }

        // Get the response IDs.
        $responseidstocmids = static::get_response_ids_to_cmids_from_cmids($cmids);

        // First pass it to the subplugins in case they've declared foreign keys.
        manager::plugintype_class_callback('responsetype', self::RESPONSETYPE_INTERFACE,
                'delete_data_for_user', [$contextlist, $responseidstocmids, $userid]);

        // Then delete what's left.
        list($inresponsesql, $inresponseparams) = $DB->get_in_or_equal(array_keys($responseidstocmids), SQL_PARAMS_NAMED);
        $params = array_merge($inresponseparams, ['userid' => $userid]);
        $sql = "userid = :userid AND response $inresponsesql";
        $DB->delete_records_select("response_user", $sql, $params);
    }

    /**
     * Return a dict of response IDs mapped to their course module ID.
     *
     * @param array $cmids The course module IDs.
     * @return array In the form of [$responseid => {cmid, question}].
     */
    protected static function get_response_ids_to_cmids_from_cmids(array $cmids) {
        global $DB;
        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "
            SELECT r.id, cm.id AS cmid, r.question
              FROM {response} r
              JOIN {modules} m
                ON m.name = :response
              JOIN {course_modules} cm
                ON cm.instance = r.id
               AND cm.module = m.id
             WHERE cm.id $insql";
        $params = array_merge($inparams, ['response' => 'response']);
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Loop and export from a recordset.
     *
     * @param moodle_recordset $recordset The recordset.
     * @param string $splitkey The record key to determine when to export.
     * @param mixed $initial The initial data to reduce from.
     * @param callable $reducer The function to return the dataset, receives current dataset, and the current record.
     * @param callable $export The function to export the dataset, receives the last value from $splitkey and the dataset.
     * @return void
     */
    public static function recordset_loop_and_export(\moodle_recordset $recordset, $splitkey, $initial,
            callable $reducer, callable $export) {
        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }
}
