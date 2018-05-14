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
 * @package   responsetype_text
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace responsetype_text\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \mod_response\privacy\responsetype_provider as subplugin_provider;
use \core_privacy\local\request\contextlist;
use \context_module;
use \core_privacy\local\request\approved_contextlist;
use \mod_response\privacy\provider as responseprovider;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;

/**
 * Privacy class for requesting user data.
 *
 * @package   responsetype_text
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
    public static function get_metadata(collection $collection) : collection {
        // The core plugin only has one table with user data.
        $responsetextuser = [
            'response' => 'privacy:metadata:response',
            'userid' => 'privacy:metadata:userid',
            'timesubmitted' => 'privacy:metadata:timesubmitted',
            'response_text' => 'privacy:metadata:responsetext',
        ];
        $collection->add_database_table('responsetype_text_user', $responsetextuser, 'privacy:metadata:responsetype_text_user');

        return $collection;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        return responseprovider::get_contexts_for_userid($userid);
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
        list($inresponsesql, $inresponseparams) = $DB->get_in_or_equal(array_keys($responseidstocmids), SQL_PARAMS_NAMED);
        $sql = "userid = :userid AND response $inresponsesql";
        $params = array_merge($inresponseparams, ['userid' => $userid]);

        $recordset = $DB->get_recordset_select('responsetype_text_user', $sql, $params);
        responseprovider::recordset_loop_and_export($recordset, 'response', null, function($carry, $record) {
            $carry[] = (object) [
                'timesubmitted' => $record->timesubmitted !== null ? transform::datetime($record->timesubmitted) : null,
                'response_text' => $record->response_text,
            ];
            return $carry;
        }, function($responseid, $data) use ($responseidstocmids) {
            $context = context_module::instance($responseidstocmids[$responseid]->cmid);
            writer::with_context($context)->export_related_data([], 'answer_text', $data);
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

        $DB->delete_records('responsetype_text_user', ['response' => $responseid]);
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

        list($inresponsesql, $inresponseparams) = $DB->get_in_or_equal(array_keys($responseidstocmids), SQL_PARAMS_NAMED);
        $params = array_merge($inresponseparams, ['userid' => $userid]);
        $sql = "userid = :userid AND response $inresponsesql";
        $DB->delete_records_select("responsetype_text_user", $sql, $params);
    }
}
