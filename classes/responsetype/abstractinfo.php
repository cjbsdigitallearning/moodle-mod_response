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
 * Generic configuration API for response activity subplugins.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\responsetype;

use stdClass;
use core_user\fields;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the response information API that subplugins are expected to follow.
 *
 * Any subplugin that defines a type of response for the response activity
 * will need to load data for displaying it to users, as well as loading users'
 * responses. All such subplugins should define a configuration class that extends
 *  this one.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstractinfo {
    /** @var object Stores state about the current activity. */
    protected $data;

    /**
     * Retrieves the data for the given instance of activity, e.g.
     * poll choices, or whatever the activity-type-specific data
     * might be.
     *
     * @param object $response The response object.
     * @return bool True on success.
     */
    public function load_activity(&$response) {
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
        return array();
    }

    /**
     * Takes a set of responses and loads the user data into those
     * responses specifically.
     *
     * @param array $responses An array of responses from users, keyed by user id, modified in place
     */
    public function merge_user_data(&$responses) {
        if (!empty($responses)) {
            $users = $this->load_user_information(array_keys($responses));

            // Go through the responses, match up against userdata, and prune ones without.
            foreach (array_keys($responses) as $userid) {
                // It shouldn't happen but that means it might sometime...
                if (!isset($users[$userid])) {
                    unset ($responses[$userid]);
                    continue;
                }

                // Match 'em up.
                $responses[$userid]->profile_picture = $users[$userid]['picture'];
                $responses[$userid]->first_name = $users[$userid]['first_name'];
                $responses[$userid]->last_name = $users[$userid]['last_name'];
            }
        }
    }

    /**
     * Load all completed responses for this activity.
     *
     * @param object $response The response object
     * @return array An array of all responses, key by user id, ordered completion date ascending.
     */
    public function load_all_responses($response) {
        return array();
    }

    /**
     * All response activities that still require user input will
     * require instantiating a moodleform object.
     *
     * A plugin will know if the current activity state shows
     * a form is required and if so, should return it from this
     * method.
     *
     * @param object $response The response object that contains all the instance data.
     * @param int $userid User ID to check for.
     * @param array $ajaxformdata Array of form data, or null
     */
    public function load_form(&$response, $userid = null, $ajaxformdata = null) {
        $response->form = false;
    }

    /**
     * Some types of this activity show some aggregate data
     * upon completion, e.g. polls show peoples' answers.
     * This is where we let plugins load whatever data
     * they're going to need to deal with this.
     *
     * @param object $response The response object
     * @param int $userid The current user id (to match groups)
     * @param int $override Override the activity's toggle-peer-results setting, as a bitmask using RESPONSE_PEER_RESULTS_*
     */
    public function load_aggregate_data(&$response, $userid, $override = null) {
        return;
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
        return false;
    }

    /**
     * Given a response object previously loaded, save this
     * part of the submission (whether this is everything or not)
     *
     * @param object $response The response object that contains all the instance data.
     * @param int $userid The user ID to save for.
     * @param object $data The form data.
     * @return bool True on save.
     */
    public function save_submission(&$response, $userid, $data) {
        return false;
    }

    /**
     * Updates the entry for the user for this response so that we
     * can track the user has completed it, or is part way through.
     *
     * @param int $response Response id.
     * @param int $userid User id.
     * @param int $responseidentifier Pointer to the subplugin table to indicate which is the most recent response.
     * @param bool $becomescomplete Whether this update would complete the activity.
     * @return int The id from response_user that has been updated/created
     */
    public function progress_activity($response, $userid, $responseidentifier, $becomescomplete) {
        global $DB;

        // Does this already exist?
        $existing = $DB->get_record('response_user', array('response' => $response, 'userid' => $userid));
        if (!empty($existing)) {
            $existing->response_identifier = $responseidentifier;
            $existing->timemodified = time();
            if ($becomescomplete) {
                $existing->timecompleted = time();
            }
            $DB->update_record('response_user', $existing);
            return $existing->id;
        }

        // It didn't exist, so let's make a new record.
        $new = new stdClass();
        $new->response = $response;
        $new->userid = $userid;
        $new->response_identifier = $responseidentifier;
        $new->timecreated = time();
        $new->timemodified = time();
        $new->timecompleted = 0;
        if ($becomescomplete) {
            $new->timecompleted = time();
        }

        $newid = $DB->insert_record('response_user', $new);
        return $newid;
    }

    /**
     * Sanitise a group of numbers (ids) into a comma
     * separated string, or merely to sanitise the array.
     *
     * IDs that are 0 or negative are removed, non-numeric
     * items are converted to ints and if 0, removed.
     * The list is also deduplicated.
     *
     * @param array $ids An array of ids, e.g. [1,2,3,4]
     * @param bool $implode True to implode result to comma separated string
     * @return mixed If $implode, ids converted to CSV, e.g. "1,2,3,4", otherwise array
     */
    protected function sanitise_int_array($ids, $implode = true) {
        // Make sure everything is an integer that is greater than 1, and deduplicated.
        // And then converted to '1,2,3,4' for inclusion into the query.
        $filter = function ($v) {
            return $v >= 1;
        };
        $ids = (array) $ids;
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, $filter);
        $ids = array_unique($ids);
        if ($implode) {
            $ids = implode(',', $ids);
        }

        return $ids;
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
        return false;
    }

    /**
     * Loads the user data associated with the ids passed in.
     *
     * @param array $userids A list of user ids to load data for.
     * @return array User data, keyed by user id, to return user name and picture
     */
    public function load_user_information($userids) {
        global $DB, $OUTPUT;

        $userids = $this->sanitise_int_array($userids, false);
        if (empty($userids)) {
            return array();
        }

        $users = array();
        $fields = fields::get_picture_fields();
        list ($sql, $params) = $DB->get_in_or_equal($userids);
        $records = $DB->get_records_select('user', 'id ' . $sql, $params, '', implode(',', $fields));

        $users = array();
        foreach ($records as $id => $record) {
            $users[$id] = array(
                'picture' => $OUTPUT->user_picture($record, array('size' => 50, 'class' => 'profilepicture')),
                'first_name' => $record->firstname,
                'last_name' => $record->lastname,
            );
        }
        return $users;
    }
}
