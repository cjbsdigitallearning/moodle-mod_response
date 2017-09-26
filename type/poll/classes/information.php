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
 * Settings for the response activity - poll subplugin.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\type\poll;
use mod_response\responsetype\abstractinfo;
use stdClass;
use mod_response\helper;
use moodle_url;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines everything for showing a specific instance of a poll activity.
 *
 * @package   responsetype_poll
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

        if (empty($response->id)) {
            return true; // Nothing to do here.
        }

        // First the generic record.
        $response->activity = $DB->get_record('responsetype_poll', array('response' => $response->id));

        // Then the poll choices.
        $response->activity->poll_choices = array();

        $responsechoices = $DB->get_records('responsetype_poll_choice', array('response' => $response->id));
        foreach ($responsechoices as $responsechoice) {
            $response->activity->poll_choices[$responsechoice->responsenum] = $responsechoice;
        }
        // Make sure the choices are explicitly in the right order.
        ksort($response->activity->poll_choices);

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
     * @return array Array of responses, user id -> that users' most recent response.
     */
    public function load_response_for_users($instance, $users, $completedonly = false) {
        global $DB;

        $users = $this->sanitise_int_array($users);
        if (empty($users)) {
            // Nothing to do, don't even bother querying.
            return array();
        }
        $query = "SELECT ru.userid, rpu.id, ru.timecreated, ru.timemodified, ru.timecompleted, rpu.choice, rpu.reflection_text
                    FROM {response_user} ru
                    JOIN {responsetype_poll_user} rpu ON (ru.response_identifier = rpu.id)
                   WHERE ru.userid IN (:users)
                     AND ru.response = :response";
        $params = array(
            'response' => $instance->id,
            'users' => $users,
        );
        if ($completedonly) {
            $query .= '
                     AND ru.timecompleted > :timecompleted';
            $params['timecompleted'] = 0;
        }
        $responses = $DB->get_records_sql($query, $params);
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
        $query = "SELECT ru.userid, rpu.id, ru.timecreated, ru.timemodified, ru.timecompleted, rpu.choice, rpu.reflection_text
                    FROM {response_user} ru
                    JOIN {responsetype_poll_user} rpu ON (ru.response_identifier = rpu.id)
                   WHERE ru.response = :response
                     AND ru.timecompleted > :timecompleted
                ORDER BY ru.timecreated";
        $params = array(
            'response' => $response->id,
            'timecompleted' => 0,
        );
        $responses = $DB->get_records_sql($query, $params);

        // Now get user data.
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

        return $responses;
    }

    /**
     * Identifies if the current activity requires a form
     * or not, and if so instantiates it.
     *
     * @param object $response Current response state.
     * @param int $userid User ID to check for.
     * @param bool $incourse True if coming from the in-course view.
     * @param array $ajaxformdata Array of form data, or null
     */
    public function load_form(&$response, $userid = null, $incourse = false, $ajaxformdata = null) {
        global $CFG;
        require_once($CFG->libdir . '/formslib.php');

        $responseclone = clone $response;
        $params = array(
            new moodle_url('/mod/response/submit.php', array('r' => $response->id)), // The action item.
            $responseclone, // Custom data, which we do need.
            'post', // Form method.
            '', // Target of the form.
            null, // Generic attributes.
            true, // Whether the form is editable.
            $ajaxformdata, // Passing through AJAX data.
        );

        // Now we can begin our checking.
        if (!$userid) {
            // We don't have a specific user; this suggests a read-only context for the answer.
            $response->form = false;
            return;
        }
        $userresponse = !empty($response->user_responses[$userid]) ? $response->user_responses[$userid] : false;
        // It's also possible we come back to step 1 after having been to step 2...
        if ($userresponse && empty($response->going_back) && empty($response->going_forward)) {
            // The current user has done something, but there might still be a form.)
            $response->form = false;
            if ($response->activity->reflection_step && !$userresponse->reflection_text) {
                // There's a reflection step and the user hasn't completed it, so we do need a form.
                $response->form = helper::instance_factory('poll', 'poll_form_reflection', $params);
            }
            return;
        }

        // So we're definitely doing something. The only question is whether there's a reflection step on this activity.
        if ($response->activity->reflection_step) {
            $mform = helper::instance_factory('poll', 'poll_form_choicesbeforereflection', $params);
        } else {
            $mform = helper::instance_factory('poll', 'poll_form_noreflection', $params);
        }
        $response->form = $mform;
    }

    /**
     * Polls show the aggregate choices made by all users as a bar chart.
     * We need to load that data, for groups and/or all users, depending
     * on the configuration of the activity.
     *
     * @param object $response The response object
     * @param int $userid The current user id (to match groups)
     * @param int $override Override the activity's toggle-peer-results setting, as a bitmask using RESPONSE_PEER_RESULTS_*
     */
    public function load_aggregate_data(&$response, $userid, $override = null) {
        global $DB;

        $response->aggregate = new stdClass();

        // Get the 'all' raw data first, even if we don't actually want it.
        // We'll already have it for when we do the groups stuff.
        $query = "SELECT ru.userid, rpu.choice
                    FROM {response_user} ru
                    JOIN {responsetype_poll_user} rpu ON (ru.response_identifier = rpu.id)
                   WHERE ru.response = :response
                     AND ru.timecompleted > :timecompleted";
        $params = array(
            'response' => $response->id,
            'timecompleted' => 0
        );
        $rawallusers = $DB->get_records_sql_menu($query, $params);
        if (empty($rawallusers)) {
            // This shouldn't happen, but just in case it does...
            return;
        }

        // Let's sift that data into the aggregate for all users.
        $displaypeerresults = isset($override) ? $override : $response->displaypeerresults;
        if ($displaypeerresults & RESPONSE_PEER_RESULTS_ALL) {
            $response->aggregate->all = array();
            // First, make some defaults using all valid choices.
            foreach ($response->activity->poll_choices as $choice) {
                $response->aggregate->all[$choice->responsenum] = 0;
            }
            // And aggregate.
            foreach ($rawallusers as $choice) {
                $response->aggregate->all[$choice]++;
            }
        }

        // Now let's work out about groups.
        if ($displaypeerresults & RESPONSE_PEER_RESULTS_GROUP) {
            $response->aggregate->group = array();
            // First, make some defaults using all valid choices.
            foreach ($response->activity->poll_choices as $choice) {
                $response->aggregate->group[$choice->responsenum] = 0;
            }
            // Then fetch users in the groups and sift out the data.
            $groupusers = helper::get_users_in_same_group($response->id, $userid);
            foreach ($groupusers as $groupuser) {
                if (isset($rawallusers[$groupuser])) {
                    $response->aggregate->group[$rawallusers[$groupuser]]++;
                }
            }
        }
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

        $response->has_just_completed = false;

        // We probably want to follow back to the activity itself.
        if (!empty($response->in_course)) {
            $redirect = $response->in_course_url;
        } else {
            $redirect = $response->standalone_url;
        }

        $pollid = 'poll_choice' . $response->id;
        $textid = 'responsetype_poll_' . $response->id;

        // There are multiple possibilities here.
        // 1. We could be saving a choice without a reflection step.
        // 2. We could be saving a choice with a reflection step.
        // 2a. We could be saving the reflection step.
        // 3. We could have saved a choice, were at reflection step and user requested to go back to change choice.

        // Is there a reflection step in this activity?
        if ($response->activity->reflection_step) {
            // Are we handling an inline course?
            if (!empty($response->in_course)) {
                $reflectiontext = !empty($data->{$textid}['text']) ? $data->{$textid}['text'] : '';
                $responseidentifier = $this->save_new_answer($response->id, $userid, $data->{$pollid}, $reflectiontext);

                $response->has_just_completed = !empty($reflectiontext);

                $this->progress_activity($response->id, $userid, $responseidentifier, $response->has_just_completed);
                return $redirect;
            }

            // So it's just a case of working out if we had a user-choice made already or not.
            if (empty($response->user_responses[$userid])) {
                // We haven't had a user choice already.
                // Create a record saving the user's choice, then pass it back to go to step 2.
                $responseidentifier = $this->save_new_answer($response->id, $userid, $data->{$pollid});

                $this->progress_activity($response->id, $userid, $responseidentifier, false);
                return $redirect;
            }

            // Are we heading back to the first step? This is where we don't progress to step 2.
            if (!empty($data->back)) {
                return new moodle_url('/mod/response/view.php', array('r' => $response->id, 'back' => 1));
            }

            // So we already had some kind of answer (and we're progressing). Is it the reflection step for our existing answer?
            $reflectiontext = !empty($data->{$textid}['text']) ? $data->{$textid}['text'] : '';
            $responseidentifier = $this->save_new_answer($response->id, $userid, $data->{$pollid}, $reflectiontext);

            $response->has_just_completed = !empty($reflectiontext);

            $this->progress_activity($response->id, $userid, $responseidentifier, $response->has_just_completed);
        } else {
            // So there's no step, we're just saving the user's poll choice.
            $responseidentifier = $this->save_new_answer($response->id, $userid, $data->{$pollid});

            // We now need to update the response_user table.
            // Was this activity already completed by this user? Or being edited?
            if (empty($response->user_responses[$userid])) {
                $response->has_just_completed = true;
            }

            $this->progress_activity($response->id, $userid, $responseidentifier, $response->has_just_completed);
        }

        return $redirect;
    }

    /**
     * Creates a new entry in the responsetype_poll_user table
     * representing a new answer being given.
     *
     * @param int $responseid ID of the activity (in response table)
     * @param int $userid User ID to save for
     * @param int $choice The poll choice ID
     * @param string $reflectiontext The reflection text, if any
     * @return int Identifier for the new response
     */
    protected function save_new_answer($responseid, $userid, $choice, $reflectiontext = null) {
        global $DB;

        $newinstance = new stdClass();
        $newinstance->response = $responseid;
        $newinstance->userid = $userid;
        $newinstance->timesubmitted = time();
        $newinstance->choice = $choice;
        $newinstance->reflection_text = !empty($reflectiontext) ? $reflectiontext : '';

        return $DB->insert_record('responsetype_poll_user', $newinstance);
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
        $DB->delete_records('responsetype_poll_user', array('response' => $cm->instance, 'userid' => $userid));

        return true;
    }
}
