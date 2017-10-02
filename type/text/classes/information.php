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
 * Settings for the response activity - text subplugin.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\type\text;
use mod_response\responsetype\abstractinfo;
use stdClass;
use moodle_url;
use mod_response\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines everything for showing a specific instance of a text activity.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class information extends abstractinfo {

    /**
     * Retrieves the data for the given instance of activity, e.g.
     * poll choices, or whatever the activity-type-specific data
     * might be.
     *
     * @param object $response The response object including its id.
     * @return bool True on success.
     */
    public function load_activity(&$response) {
        global $DB;

        $response->activity = $DB->get_record('responsetype_text', array('response' => $response->id));

        return true;
    }

    /**
     * Retrieves all answers given to this activity, for specific
     * user ids (to handle getting all responses or merely some
     * specific users' responses, e.g. a study group)
     *
     * @param int $instance Instance id for this activity.
     * @param array $users List of user ids to load data for.
     * @param bool $completedonly True to only load completed responses.
     * @param bool $getuserinfo True to load the user profile picture/name as well.
     * @return array Array of responses, user id -> that users' most recent response.
     */
    public function load_response_for_users($instance, $users, $completedonly = false, $getuserinfo = false) {
        global $DB;

        $users = $this->sanitise_int_array($users, false);
        if (empty($users)) {
            // Nothing to do, don't even bother querying.
            return array();
        }

        list ($sql, $params) = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
        $params['response'] = $instance->id;

        $query = "SELECT ru.userid, ru.timecreated, ru.timemodified, ru.timecompleted, rtu.response_text
                    FROM {response_user} ru
                    JOIN {responsetype_text_user} rtu ON (ru.response_identifier = rtu.id)
                   WHERE ru.userid $sql
                     AND ru.response = :response";

        if ($completedonly) {
            $query .= '
                     AND ru.timecompleted > :timecompleted';
            $params['timecompleted'] = 0;
        }

        $responses = $DB->get_records_sql($query, $params);

        // Now get user data.
        if ($getuserinfo) {
            $this->merge_user_data($responses);
        }

        return $responses;
    }

    /**
     * Load all completed responses for this activity.
     *
     * @param object $response The response object
     * @return array An array of all responses, key by user id, ordered completion date ascending.
     */
    public function load_all_responses($response) {
        global $DB;

        $responses = array();

        // First load any responses we actually have.
        $query = "SELECT ru.userid, ru.timecreated, ru.timemodified, ru.timecompleted, rtu.response_text
                    FROM {response_user} ru
                    JOIN {responsetype_text_user} rtu ON (ru.response_identifier = rtu.id)
                   WHERE ru.response = :response
                     AND ru.timecompleted > :timecompleted
                ORDER BY ru.timecreated";
        $params = array(
            'response' => $response->id,
            'timecompleted' => 0,
        );
        $responses = $DB->get_records_sql($query, $params);

        // Now get user data.
        $this->merge_user_data($responses);

        return $responses;
    }

    /**
     * Identifies if the current activity requires a form
     * or not, and if so instantiates it.
     *
     * @param object $response Current response state.
     * @param int $userid User ID to check for.
     * @param array $ajaxformdata Array of form data, or null
     */
    public function load_form(&$response, $userid = null, $ajaxformdata = null) {
        global $CFG;

        if (!$userid) {
            // We don't have a specific user; this suggests a read-only context for the answer.
            $response->form = false;
            return;
        }
        if (!empty($response->user_responses[$userid]) && empty($response->is_editing)) {
            // The current user has completed this (and not editing), so no form to render.
            $response->form = false;
            return;
        }

        require_once($CFG->libdir . '/formslib.php');
        $params = array(
            new moodle_url('/mod/response/submit.php', array('r' => $response->id)), // The action item.
            clone $response, // Custom data, which we do need.
            'post', // Form method.
            '', // Target of the form.
            null, // Generic attributes.
            true, // Whether the form is editable.
            $ajaxformdata, // Passing through AJAX data.
        );
        $mform = helper::instance_factory('text', 'text_form', $params);

        if (!empty($response->user_responses[$userid]) && !empty($response->is_editing)) {
            $data = array(
                'responsetype_text_' . $response->id => array('text' => $response->user_responses[$userid]->response_text),
            );
            $mform->set_data($data);
        }

        $response->form = $mform;
    }

    /**
     * Given a response object previously loaded, identify
     * if the current state of that response causes it to
     * now be considered complete. Note that 'previously
     * complete but subsequently edited' should not return true.
     *
     * A plugin will know if the current activity state shows
     * completion has occurred.
     *
     * @param object $response The response object that contains all the instance data.
     * @param int $userid User ID to check for.
     * @return bool True if the current state has 'just' changed to complete.
     */
    public function has_now_completed(&$response, $userid = null) {
        return !empty($response->has_just_completed);
    }

    /**
     * Given a response object previously loaded, save this
     * part of the submission (whether this is everything or not)
     *
     * @param object $response The response object that contains all the instance data.
     * @param int $userid The user ID to save for.
     * @param object $data The form data.
     * @return moodle_url Where to redirect to on the back of this submission.
     */
    public function save_submission(&$response, $userid, $data) {
        global $DB;

        // Before anything else, save this response.
        $newinstance = new stdClass();
        $newinstance->response = $response->id;
        $newinstance->userid = $userid;
        $newinstance->timesubmitted = time();
        $newinstance->response_text = $data->{'responsetype_text_' . $response->id}['text'];
        // We don't really care which format it was, it's going through format_text all the same.

        $responseidentifier = $DB->insert_record('responsetype_text_user', $newinstance);

        // We now need to update the response_user table.
        // Was this activity already completed by this user? Or being edited?
        $response->has_just_completed = false;
        if (empty($response->user_responses[$userid])) {
            $response->has_just_completed = true;
        }
        $this->progress_activity($response->id, $userid, $responseidentifier, $response->has_just_completed);

        // Redirect back to whence we came.
        if ($response->in_course) {
            return $response->in_course_url;
        } else {
            return $response->standalone_url;
        }
    }

    /**
     * Deletes the data attached to a user completing an activity using this subplugin.
     *
     * Used when a user deletes their response (or an admin does it).
     *
     * @param object $course The course in question
     * @param object $cm Course module being examined
     * @param int $userid User ID to filter on
     * @return bool True if completed.
     */
    public function delete_user_response($course, $cm, $userid) {
        global $DB;
        $DB->delete_records('responsetype_text_user', array('response' => $cm->instance, 'userid' => $userid));

        return true;
    }
}
