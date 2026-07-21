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

namespace mod_response\type\text;
use mod_response\responsetype\abstractinfo;
use stdClass;
use moodle_url;
use mod_response\helper;
use context_module;

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

        $response->activity = $DB->get_record('responsetype_text', ['response' => $response->id]);
        if (!$response->activity) {
            // If the activity record is missing, we should return early to avoid a fatal error
            // This can happen if the transaction was interrupted during creation or if data is inconsistent.
            return false;
        }

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
            return [];
        }

        [$sql, $params] = $DB->get_in_or_equal($users, SQL_PARAMS_NAMED);
        $params['response'] = $instance->id;

        $query = "SELECT ru.userid, rtu.id AS response_user_id, ru.timecreated, ru.timemodified, ru.timecompleted, rtu.response_text
                    FROM {response_user} ru
                    JOIN {responsetype_text_user} rtu ON (ru.response_identifier = rtu.id)
                   WHERE ru.userid $sql
                     AND ru.response = :response";

        if ($completedonly) {
            $query .= ' AND ru.timecompleted > :timecompleted';
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

        $responses = [];

        // First load any responses we actually have.
        $query = "SELECT ru.userid, rtu.id AS response_user_id, ru.timecreated, ru.timemodified, ru.timecompleted, rtu.response_text
                    FROM {response_user} ru
                    JOIN {responsetype_text_user} rtu ON (ru.response_identifier = rtu.id)
                   WHERE ru.response = :response
                     AND ru.timecompleted > :timecompleted
                ORDER BY ru.timecreated";
        $params = [
            'response' => $response->id,
            'timecompleted' => 0,
        ];
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
        $params = [
            new moodle_url('/mod/response/submit.php', ['r' => $response->id]), // The action item.
            clone $response, // Custom data, which we do need.
            'post', // Form method.
            '', // Target of the form.
            null, // Generic attributes.
            true, // Whether the form is editable.
            $ajaxformdata, // Passing through AJAX data.
        ];
        $mform = helper::instance_factory('text', 'text_form', $params);

        if (!empty($response->user_responses[$userid]) && !empty($response->is_editing)) {
            $data = [
                'responsetype_text_' . $response->id => ['text' => $response->user_responses[$userid]->response_text],
            ];
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
        $newinstance->id = $responseidentifier;

        if (!empty($data->{'responsetype_text_' . $response->id}['itemid'])) {
            $cmid = $response->cm->id;
            $context = context_module::instance($cmid);
            $newinstance->response_text = file_save_draft_area_files(
                $data->{'responsetype_text_' . $response->id}['itemid'],
                $context->id,
                'responsetype_text_user',
                'response_text',
                $responseidentifier,
                helper::get_editor_options($context),
                $newinstance->response_text
            );

            // If we have an old response ID, update any existing file itemids.
            if (array_key_exists($userid, $response->user_responses) && $response->user_responses[$userid]->response_user_id) {
                // Get existing old files.
                $fs = get_file_storage();
                $oldfiles = $fs->get_area_files(
                    $context->id,
                    'responsetype_text_user',
                    'response_text',
                    $response->user_responses[$userid]->response_user_id,
                );

                if ($oldfiles) {
                    foreach ($oldfiles as $file) {
                        // Skip directories.
                        if ($file->is_directory()) {
                            continue;
                        }
                        // Check files already exist, if not create them.
                        if (
                            !$fs->file_exists(
                                $context->id,
                                'responsetype_text_user',
                                'response_text',
                                $responseidentifier,
                                $file->get_filepath(),
                                $file->get_filename()
                            )
                        ) {
                            // Create new copies of the files with the new itemid.
                            $fileupdate['itemid'] = $responseidentifier;
                            $fs->create_file_from_storedfile($fileupdate, $file->get_id());
                        }
                    }

                    // Delete files with the old itemid.
                    $fs->delete_area_files(
                        $context->id,
                        'responsetype_text_user',
                        'response_text',
                        $response->user_responses[$userid]->response_user_id,
                    );
                }
            }

            $DB->update_record('responsetype_text_user', $newinstance);
        }

        // We now need to update the response_user table.
        // Was this activity already completed by this user? Or being edited?
        $response->has_just_completed = false;
        if (empty($response->user_responses[$userid])) {
            $response->has_just_completed = true;
        }
        $this->progress_activity($response->id, $userid, $responseidentifier, $response->has_just_completed);

        // Make sure we don't try to do anything funky with back/forwards when editing.
        // When we save, there's no additional steps we can be going back/forward to.
        $response->going_forward = false;
        $response->going_back = false;
        $response->is_editing = false;

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

        $userresponses = $DB->get_records('responsetype_text_user', ['response' => $cm->instance, 'userid' => $userid]);
        $DB->delete_records('responsetype_text_user', ['response' => $cm->instance, 'userid' => $userid]);

        // Delete any attached files.
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        if (!empty($userresponses)) {
            foreach ($userresponses as $response) {
                $fs->delete_area_files($context->id, 'responsetype_text_user', 'response_text', $response->id);
            }
        }

        return true;
    }

    /**
     * User response fields.
     * @param object $response A response.
     * @return array The response fields.
     */
    public function get_response_fields(object $response): array {
         return [
             $this->get_usertext_fieldname() => 'response_text',
         ];
    }

    /**
     * User response values.
     * @param object $response A response including the user response.
     * @return array
     */
    public function get_response_values(object $response): array {
        return [
            $this->get_usertext_fieldname() => $response->response->response_text,
        ];
    }

    /**
     * The file component.
     *
     * @return string
     */
    public function get_filecomponent(): string {
        return 'responsetype_text_user';
    }

    /**
     * The file area.
     *
     * @return string
     */
    public function get_filearea(): string {
        return 'response_text';
    }

    /**
     * Does the plugin have any user text fields.
     *
     * @param object $response A response.
     * @return bool
     */
    public function has_user_text(object $response): bool {
        return true;
    }

    /**
     * Get the field name containing user text.
     *
     * @return string The field name.
     */
    public function get_usertext_fieldname(): string {
        return 'response';
    }
}
