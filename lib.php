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
 * Core activity definition.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

/**
 * Outlines features supported by the response activity.
 *
 * @param int $feature A number which corresponds to a FEATURE_ constant
 * @return bool Whether the listed feature is supported by this activity
 */
function response_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_IDNUMBER:
        case FEATURE_GROUPS:
        case FEATURE_COMPLETION:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return false;
    }
}

/**
 * Handles insertion of new instances of this activity into the
 * database. Note that it will receive all the data but delegate
 * out handling to subplugins for some of the items being passed
 * in here.
 *
 * @param stdClass $moduleinstance Details of the new activity
 * @param mod_response_mod_form $mform The form being validated
 * @return int ID of the newly inserted response
 */
function response_add_instance($moduleinstance, $mform = null) {

    global $DB;
    // Use a transaction to ensure that the main activity record and subplugin data are created together.
    $transaction = $DB->start_delegated_transaction();

    // Let our friendly repackager build most of the object we need.
    $newinstance = helper::package_modform_data($moduleinstance);

    $newinstance->id = $DB->insert_record('response', $newinstance);

    // Add any files added to WYSIWYG editor.
    // Done after insert_record as we need an id for the context.
    if ($mform && !empty($moduleinstance->page['itemid'])) {
        $cmid = $moduleinstance->coursemodule;
        $DB->set_field('course_modules', 'instance', $newinstance->id, ['id' => $cmid]);
        $context = context_module::instance($cmid);
        $draftitemid = $moduleinstance->responsecontent['itemid'];
        $newinstance->content = file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'mod_response',
            'content',
            0,
            helper::get_editor_options($context),
            $newinstance->content
        );
        $DB->update_record('response', $newinstance);
    }

    // We've handled instances of the parent record, now we need to save subplugin data.
    $subplugin = helper::instance_factory($moduleinstance->responsetype, 'configuration');
    // But we don't want to taint the module instance object with new data.
    $dummyinstance = clone $moduleinstance;
    $dummyinstance->response = $newinstance->id;
    $subplugin->add_instance($dummyinstance, $mform);

    // Add completion event to calendar.
    if (!empty($moduleinstance->completionexpected)) {
        \core_completion\api::update_completion_date_event(
            $moduleinstance->coursemodule,
            'response',
            $newinstance->id,
            $moduleinstance->completionexpected
        );
    }

    $transaction->allow_commit();

    return $newinstance->id;
}

/**
 * Handles updating instances of this activity in the database. Note
 * that it will receive all the data but delegate out handling to
 * subplugins for some of the items being passed in here.
 *
 * @param stdClass $moduleinstance Details of the updated activity
 * @param mod_response_mod_form $mform The form being validated
 * @return bool True if successful
 */
function response_update_instance($moduleinstance, $mform = null) {
    global $DB;
    // Use a transaction to ensure that the main activity record and subplugin data are updated together.
    $transaction = $DB->start_delegated_transaction();

    // Let our friendly repackager build most of the object we need.
    $newinstance = helper::package_modform_data($moduleinstance);

    $newinstance->id = $moduleinstance->instance;

    $DB->update_record('response', $newinstance);

    // Update files added to the WYSIWYG editor.
    $draftitemid = $moduleinstance->responsecontent['itemid'];
    if ($draftitemid) {
        $cmid = $moduleinstance->coursemodule;
        $context = context_module::instance($cmid);
        $newinstance->content = file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'mod_response',
            'content',
            0,
            helper::get_editor_options($context),
            $newinstance->content
        );
        $DB->update_record('response', $newinstance);
    }

    // We've handled instances of the parent record, now we need to save subplugin data.
    $subplugin = helper::instance_factory($moduleinstance->responsetype, 'configuration');
    $subplugin->update_instance($moduleinstance, $mform);

    // Update completion event in calendar.
    $completionexpected = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event(
        $moduleinstance->coursemodule,
        'response',
        $newinstance->id,
        $completionexpected,
    );

    $transaction->allow_commit();

    return true;
}

/**
 * Handles deleting instances of this activity in the database. Note
 * that it will delegate out to subplugins to ensure that subplugin
 * specific tables are correctly handled.
 *
 * @param int $id The activity id in the response table
 * @return bool True if successful
 */
function response_delete_instance($id) {
    global $DB;

    // Use a transaction to ensure that the main activity record and subplugin data are deleted together.
    $transaction = $DB->start_delegated_transaction();

    // Before we delete it, we need to know what kind of response it was.
    $response = $DB->get_record('response', ['id' => $id]);

    $subplugin = helper::instance_factory($response->responsetype, 'configuration');
    $subplugin->delete_instance($id);

    $DB->delete_records('response', ['id' => $id]);

    // Delete completion event from calendar.
    $cm = get_coursemodule_from_instance('response', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'response', $id, null);

    $transaction->allow_commit();

    return true;
}

/**
 * Handles completion criteria for a given response activity.
 *
 * @param object $course The course in question
 * @param object $cm Course module being examined
 * @param int $userid User ID to check for
 * @param bool $type Type of comparison (and/or)
 * @return bool True if completed, false if not, $type if conditions not set
 */
function response_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $response = $DB->get_record('response', ['id' => $cm->instance], '*', MUST_EXIST);

    if (!$response->requiresubmission) {
        // The student does not need to submit anything.
        return $type;
    }

    $resourceuser = $DB->get_record('response_user', ['response' => $cm->instance, 'userid' => $userid]);
    if (!empty($resourceuser) && !empty($resourceuser->timecompleted)) {
        // If we did get a record, and it has a non-empty completion time, we must have completed this activity.
        return true;
    }

    // Either we didn't have a record, or the student isn't yet finished (because it's possibly multi-step).
    return false;
}

/**
 * We want to delete a response to an activity given by a specific user.
 *
 * @param object $course The course in question
 * @param object $cm Course module being examined
 * @param int $userid User ID to filter on
 * @return bool True if completed.
 */
function response_delete_response($course, $cm, $userid) {
    global $DB, $CFG;

    // Delete the record of it in the response table... after loading a copy for reference.
    $response = $DB->get_record('response', ['id' => $cm->instance]);
    $DB->delete_records('response_user', ['response' => $response->id, 'userid' => $userid]);

    // Pass it out to subplugins.
    $subplugin = helper::instance_factory($response->responsetype, 'information');
    $subplugin->delete_user_response($course, $cm, $userid);

    // Notify the completion system that this isn't completed.
    require_once($CFG->libdir . '/completionlib.php');
    $completion = new completion_info($course);

    if ($completion->is_enabled($cm) && $response->requiresubmission) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $userid);
    }

    return true;
}

/**
 * Serves the response files.
 *
 * @package  mod_response
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function response_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if ($filearea !== 'content') {
        // Intro is handled automatically in pluginfile.php.
        return false;
    }
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_response/$filearea/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
        send_file_not_found();
    }
    // Send the file.
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * We want to expose some additional data to the course layout etc. pages.
 *
 * @param object $cm Course module instance for this activity.
 * @return cached_cm_info Data passed back about the current course activity.
 */
function response_get_coursemodule_info($cm) {
    global $DB;

    $response = $DB->get_record('response', ['id' => $cm->instance], '*', MUST_EXIST);

    $info = new cached_cm_info();
    $info->name = $response->name;
    if (!empty($cm->showdescription)) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('response', $response, $cm->id, false);
    }

    $subplugins = helper::get_type_subplugins();
    if (!empty($subplugins[$response->responsetype])) {
        $info->icon = 'icon';
        $info->iconcomponent = $subplugins[$response->responsetype]->type;

        $instance = helper::instance_factory($response->responsetype, 'information');
        $instance->load_activity($response);
        $info->customdata = $response;
    }

    // If the response display is inline, the activity icon should not be displayed.
    if (!empty($info->customdata->responsedisplay)) {
        $info->iconurl = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        // Add extra css for inline.
        $info->extraclasses = 'displayresponseinline';

        // This to display the activity in the course view instead own page.
        $course = $DB->get_record('course', ['id' => $cm->course]);
        $info->onclick = "location.href='" . helper::get_context_url($cm, $course, 1)->out(true) . "'; return false;";
    } else {
        // Add extra css for own page.
        $info->extraclasses = 'displayresponseownpage';
    }

    if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata->customcompletionrules['requiresubmission'] = $response->requiresubmission;
    }

    return $info;
}

/**
 * Adds any behaviour for the course module when rendering the
 * dynamic view of the course.
 *
 * @param cm_info $cm Course module instance
 */
function response_cm_info_dynamic(cm_info $cm) {
    global $PAGE;

    $customdata = $cm->get_custom_data();
    $customdata->fullpage = strpos($PAGE->url->get_path(), '/mod/response/view.php') === 0;

    // If the default (separate page) view is set, render this view instead.
    if ($customdata->responsedisplay == 0 && !$customdata->fullpage) {
        return;
    }

    // If users don't have the relevant capability, they can't even see it.
    $context = context_module::instance($cm->id);
    if (!has_capability('mod/response:view', $context)) {
        $cm->set_user_visible(false);
    }
}

/**
 * Applies rendering of the learning activity inline to the course view.
 *
 * @param cm_info $cm Course module instance
 */
function response_cm_info_view(cm_info $cm) {
    global $PAGE, $USER, $CFG;

    $customdata = $cm->customdata;

    $customdata->fullpage = strpos($PAGE->url->get_path(), '/mod/response/view.php') === 0;

    // If the default (separate page) view is set, render this view instead.
    if ($customdata->responsedisplay == 0 && !$customdata->fullpage) {
        return;
    }

    // Before we go any further, we need to work out if the user has completed this instance.
    $instance = helper::instance_factory($customdata->responsetype, 'information');
    $usercompletion = $instance->load_response_for_users($customdata, [$USER->id]);
    $customdata->user_responses = $usercompletion;

    $renderer = $PAGE->get_renderer('mod_response');

    $data = new stdClass();
    $data->course_module = $cm;
    $data->course = $data->course_module->course;
    $data->user_completion = !empty($usercompletion[$USER->id]) ? $usercompletion[$USER->id] : false;
    $data->fullpage = $customdata->fullpage;

    // Can they see all the responses?
    $context = context_module::instance($cm->id);
    helper::check_can_see_all_responses($customdata, $context, $cm);

    $instance->load_form($customdata, $USER->id);
    if (!empty($customdata->form)) {
        $data->form = $customdata->form;

        $data->contextid = $context->id;
    } else if ($data->user_completion && $data->user_completion->timecompleted) {
        $instance->load_aggregate_data($customdata, $USER->id);

        // Can they delete their own answer?
        helper::check_user_delete_own_response($customdata, $context, $cm);

        // Can they edit their response?
        helper::check_can_edit_own_response($customdata, $context, $cm);

        if (!empty($customdata->displaycompletionafter)) {
            require_once($CFG->libdir . '/formslib.php');
            $responseclone = clone $customdata;
            $responseclone->context = $context;
            $customdata->postcompletion = new mod_response\postcompletion($PAGE->url, $responseclone);
        }

        $renderable = helper::instance_factory($customdata->responsetype, 'output', [$customdata, $instance]);
        $data->user_answer = $renderer->render($renderable);
    }

    if (!empty($data->form)) {
        $canparticipate = $cm->uservisible;
        if (!has_capability('mod/response:participate', $context)) {
            $canparticipate = false;
        }
        if (!$canparticipate) {
            $data->form->disable_form(get_string('cannotparticipate', 'response'));
        }
    }

    if (!empty($customdata->postcompletion)) {
        $data->postcompletion = $customdata->postcompletion->render();
    }

    $data->showdescription = !empty($cm->showdescription) || !empty($customdata->showdescription);
    $cm->set_content($renderer->render_courseinline($data), true);
}

/**
 * Handles returning view or form components back to the course layout
 * when supplied via AJAX.
 *
 * @param array $args The arguments as provided by core/fragment
 * @return string Rendered HTML for the browser (JS is handled by mutated global state)
 */
function mod_response_output_fragment_form($args) {
    global $USER, $PAGE, $DB, $CFG;

    $context = $args['context'];
    $contextid = $context->id;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    // Because we have to accept things like editors, we JSONify the data in transit - and unpack it now we have it.
    $json = @json_decode($args['jsonformdata'], true);
    unset($args['jsonformdata']);

    // We receive arbitrary arrays here, except... they're flattened in transit by JSON.stringify.
    // So we need to convert "var[x]" into a real array item into $args... regardless of depth or nesting.
    foreach ($json as $k => $v) {
        $var = [];
        parse_str($k . '=' . $v, $var);
        $args = array_replace_recursive($args, $var);
    }

    // Sanitise incoming.
    $id = isset($args['id']) ? (int) $args['id'] : 0; // Course module ID.
    $r = isset($args['r']) ? (int) $args['r'] : 0; // Response instance ID.
    $back = !empty($args['back']); // Whether to go back a step.
    $forward = isset($args['forward']) ? (int) $args['forward'] : 0; // Whether to re-go forward a step.
    $editing = isset($args['editing']) ? (int) $args['editing'] : 0; // Whether editing or not.

    if ($r) {
        if (!$response = $DB->get_record('response', ['id' => $r])) {
            throw new moodle_exception('invalidaccessparameter', 'error');
        }
        $cm = get_coursemodule_from_instance('response', $response->id, $response->course, false, MUST_EXIST);
    } else {
        if (!$cm = get_coursemodule_from_id('response', $id)) {
            throw new moodle_exception('invalidcoursemodule', 'error');
        }
        $response = $DB->get_record('response', ['id' => $cm->instance], '*', MUST_EXIST);
    }

    $response->cm = $cm;
    $response->going_back = !empty($back);
    $response->going_forward = !empty($forward);
    $response->in_course = false;

    $response->standalone_url = new moodle_url('/mod/response/view.php', ['id' => $cm->id]);

    $context = context_module::instance($cm->id);
    require_capability('mod/response:participate', $context);

    $response->is_editing = $editing && has_capability('mod/response:editown', $context) ? $editing : 0;

    $instance = helper::instance_factory($response->responsetype, 'information');
    $instance->load_activity($response);
    $response->user_responses = $instance->load_response_for_users($response, [$USER->id]);

    // Before we pass everything to the form, clean out the context because that breaks the form system otherwise.
    $argsclone = $args;
    unset($argsclone['context']);
    unset($argsclone['editing']);
    $PAGE->set_url(new moodle_url('/course/view.php', ['id' => $cm->course]));
    $instance->load_form($response, $USER->id, $argsclone);

    $output = $PAGE->get_renderer('mod_response');

    $response->postcompletion = '';

    if (empty($response->form)) {
        // We have no form to process (presumably completed), but load the aggregate data if appropriate.
        $instance->load_aggregate_data($response, $USER->id);
        require_once($CFG->libdir . '/formslib.php');

        $responseclone = clone $response;
        $responseclone->context = $context;
        $response->postcompletion = new mod_response\postcompletion($PAGE->url, $responseclone);
    } else if ($data = $response->form->get_data()) {
        // We have a form submission, so save it and then work out what happens next.
        $instance->save_submission($response, $USER->id, $data);

        if ($instance->has_now_completed($response, $USER->id)) {
            // Notify the completion system.
            require_once($CFG->libdir . '/completionlib.php');

            $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $response->requiresubmission) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
                $PAGE->requires->js_call_amd('mod_response/completionstatus', 'init', [$contextid]);
            }
        }

        // Force a reload of whatever form state.
        $response->form = false;
        $response->user_responses = $instance->load_response_for_users($response, [$USER->id]);
        $instance->load_form($response, $USER->id);
        if (!empty($response->user_responses[$USER->id]->timecompleted)) {
            $instance->load_aggregate_data($response, $USER->id);
            require_once($CFG->libdir . '/formslib.php');
            $responseclone = clone $response;
            $responseclone->context = $context;
            $response->postcompletion = new mod_response\postcompletion($PAGE->url, $responseclone);
        }
    }

    // Can they delete their own answer?
    helper::check_user_delete_own_response($response, $context, $cm);

    // Can they see all the responses?
    helper::check_can_see_all_responses($response, $context, $cm);

    // Can they edit their response?
    helper::check_can_edit_own_response($response, $context, $cm);

    $response->contextid = $context->id;

    // Return whatever view we have on the data.
    $renderable = helper::instance_factory($response->responsetype, 'output', [$response, $instance]);
    return $output->render($renderable);
}

/**
 * Handles returning completed views back to the course layout
 * when supplied via AJAX.
 *
 * @param array $args The arguments as provided by core/fragment
 * @return string Rendered HTML for the browser (JS is handled by mutated global state)
 */
function mod_response_output_fragment_answer($args) {
    global $PAGE, $DB, $USER;

    $context = $args['context'];

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    if (!$cm = get_coursemodule_from_id('response', $context->instanceid)) {
        throw new moodle_exception('invalidcoursemodule', 'error');
    }
    require_capability('mod/response:participate', $context);
    require_capability('mod/response:viewother', $context);

    $response = $DB->get_record('response', ['id' => $cm->instance], '*', MUST_EXIST);

    $userid = !empty($args['userid']) ? (int) $args['userid'] : 0;
    if (empty($userid)) {
        throw new moodle_exception('invaliduserid', 'error', '', $cm->id);
    }

    // Now we need to verify the user could conceivably could see these answers.
    $instance = helper::instance_factory($response->responsetype, 'information');
    $instance->load_activity($response);
    $response->user_responses = $instance->load_response_for_users($response, [$userid, $USER->id]);

    // First, did the viewing user complete the activity?
    if (empty($response->user_responses[$USER->id])) {
        throw new moodle_exception('error:viewerhasnotcompleted', 'mod_response', '', $cm->id);
    }
    // Did the user whose completion is requested complete the activity?
    if (empty($response->user_responses[$userid])) {
        throw new moodle_exception('error:subjecthasnotcompleted', 'mod_response', '', $cm->id);
    }

    $cansee = helper::can_see($USER->id, $userid, $cm, $response, before: false);

    if (!$cansee) {
        throw new moodle_exception('error:cannotviewresponse', 'mod_response', '', $cm->id);
    }

    // If we're here, we can see the response.
    $data = new stdClass();
    $data = $response;
    $data->response = $response->user_responses[$userid];
    unset($data->user_responses);

    $userwrote = $instance->load_user_information($userid);
    $data->user_wrote = !empty($userwrote[$userid]) ? $userwrote[$userid] : [];
    $data->viewing_own = $userid == $USER->id; // Viewing our own item?

    $data->meta = new stdClass();
    $data->meta->cm = $cm;
    $data->meta->context = $context;

    $renderer = $PAGE->get_renderer('mod_response');

    $renderable = helper::instance_factory($response->responsetype, 'inlineoutput', [$data, $instance]);
    return $renderer->render($renderable);
}

/**
 * Handles refreshing completion status via AJAX if it's possible it has changed.
 *
 * @param array $args The arguments as provided by core/fragment
 * @return string Rendered HTML for the browser (JS is handled by mutated global state)
 */
function mod_response_output_fragment_completion($args) {
    global $PAGE, $DB, $USER;

    $context = $args['context'];
    $mode = $args['mode'];

    // Work out where we are and get some details going for this.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }
    if (!$cm = get_coursemodule_from_id('response', $context->instanceid)) {
        throw new moodle_exception('invalidcoursemodule', 'error');
    }
    $response = $DB->get_record('response', ['id' => $cm->instance], '*', MUST_EXIST);

    // This module has no completion criteria set.
    if (!$response->requiresubmission) {
        return '';
    }

    // For the renderer we need a real cm_info instance, not a stdClass.
    $modinfo = get_fast_modinfo($cm->course, $USER->id);
    $cminfo = $modinfo->get_cm($cm->id);
    $renderer = $PAGE->get_renderer('core', 'course');

    if ($mode === 'page') {
        $PAGE->set_cm($cminfo);
        $PAGE->set_activity_record($response);
        $header = new \core\output\activity_header($PAGE, $USER);
        $output = $renderer->render($header);
    } else {
        $cmoutput = new core_courseformat\output\local\content\cm(
            course_get_format($cminfo->course),
            $modinfo->get_section_info($cminfo->sectionnum),
            $cminfo,
        );

        $context = $cmoutput->export_for_template($renderer);

        $output = $renderer->render_from_template('core_courseformat/local/content/cm/activity_info', $context->completion);
    }

    return $output;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_response_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    $userid = 0
) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['response'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/response/view.php', ['id' => $cm->id]),
        1,
        true
    );
}
