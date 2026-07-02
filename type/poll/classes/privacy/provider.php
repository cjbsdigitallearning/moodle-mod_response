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

namespace responsetype_poll\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadataprovider;
use mod_response\privacy\responsetype_provider as subplugin_provider;
use core_privacy\local\request\contextlist;
use context_module;
use core_privacy\local\request\approved_contextlist;
use mod_response\privacy\provider as responseprovider;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;
use context;

/**
 * Privacy class for requesting user data.
 *
 * @package   responsetype_poll
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, subplugin_provider {
    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        // The core plugin only has one table with user data.
        $responsepolluser = [
            'response' => 'privacy:metadata:response',
            'userid' => 'privacy:metadata:userid',
            'timesubmitted' => 'privacy:metadata:timesubmitted',
            'choice' => 'privacy:metadata:choice',
            'reflection_text' => 'privacy:metadata:reflectiontext',
        ];
        $collection->add_database_table('responsetype_poll_user', $responsepolluser, 'privacy:metadata:responsetype_poll_user');

        return $collection;
    }

    /**
     * Export the user data from a subplugin specific context.
     *
     * @param approved_contextlist $contextlist The approved context list to export
     * @param array $responseidstocmids An array mapping response IDs to their course modules to be processed
     * @param int $userid The user whose data to export
     */
    public static function export_user_data(approved_contextlist $contextlist, array $responseidstocmids, int $userid) {
        global $DB;

        // Prepare the common SQL fragments.
        [$inresponsesql, $inresponseparams] = $DB->get_in_or_equal(array_keys($responseidstocmids), SQL_PARAMS_NAMED);
        $params = array_merge($inresponseparams, ['userid' => $userid]);

        $recordset = $DB->get_recordset_sql("
            SELECT rpu.response, rpc.choice, rpu.timesubmitted, rpu.reflection_text
              FROM {responsetype_poll_user} rpu
              JOIN {responsetype_poll_choice} rpc
                ON rpc.response = rpu.response
               AND rpc.responsenum = rpu.choice
             WHERE rpu.userid = :userid
               AND rpu.response $inresponsesql
          ORDER BY rpu.response, rpu.timesubmitted", $params);
        responseprovider::recordset_loop_and_export($recordset, 'response', null, function ($carry, $record) {
            $carry[] = (object) [
                'choice' => $record->choice,
                'timesubmitted' => $record->timesubmitted !== null ? transform::datetime($record->timesubmitted) : null,
                'reflection_text' => $record->reflection_text,
            ];
            return $carry;
        }, function ($responseid, $data) use ($responseidstocmids) {
            $context = context_module::instance($responseidstocmids[$responseid]->cmid);
            writer::with_context($context)->export_related_data([], 'answer_poll', $data);
        });
    }

    /**
     * Delete all user data which matches the specified context.
     *
     * @param context $context The module context.
     * @param int $responseid A response ID to clean up
     */
    public static function delete_data_for_all_users_in_context(\context $context, int $responseid) {
        global $DB;

        $DB->delete_records('responsetype_poll_user', ['response' => $responseid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @param array $responseidstocmids An array of response/course module mappings (to avoid requerying)
     * @param int $userid
     */
    public static function delete_data_for_user(approved_contextlist $contextlist, array $responseidstocmids, int $userid) {
        global $DB;

        [$inresponsesql, $inresponseparams] = $DB->get_in_or_equal(array_keys($responseidstocmids), SQL_PARAMS_NAMED);
        $params = array_merge($inresponseparams, ['userid' => $userid]);
        $sql = "userid = :userid AND response $inresponsesql";
        $DB->delete_records_select("responsetype_poll_user", $sql, $params);
    }

    /**
     * Delete data for specified users in a specified context.
     *
     * @param context $context The context to limit deletions to
     * @param int $responseid The repsonse ID (should match context, but kept for performance)
     * @param array $userids An array of user IDs to delete data for within the limited context
     */
    public static function delete_data_for_users(context $context, int $responseid, array $userids) {
        global $DB;

        [$inuserssql, $inusersparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge($inusersparams, ['response' => $responseid]);
        $sql = "userid $inuserssql AND response = :response";
        $DB->delete_records_select("responsetype_poll_user", $sql, $params);
    }
}
