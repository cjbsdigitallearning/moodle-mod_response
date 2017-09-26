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
 * Submitting a learning response activity.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

require('../../config.php');
require_once($CFG->dirroot.'/mod/response/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$r = optional_param('r', 0, PARAM_INT); // Response instance ID.
$back = optional_param('back', '', PARAM_TEXT); // Whether to go back a step.
$forward = optional_param('forward', 0, PARAM_INT); // Whether to re-go forward a step.
$incourse = optional_param('incourse', 0, PARAM_INT); // Whether to return to course view.

if ($r) {
    if (!$response = $DB->get_record('response', array('id' => $r))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('response', $response->id, $response->course, false, MUST_EXIST);
} else {
    if (!$cm = get_coursemodule_from_id('response', $id)) {
        print_error('invalidcoursemodule');
    }
    $response = $DB->get_record('response', array('id' => $cm->instance), '*', MUST_EXIST);
}

$response->going_back = !empty($back);
$response->going_forward = !empty($forward);
$response->in_course = !empty($incourse);

$response->in_course_url = new moodle_url('/course/view.php', array('id' => $cm->course), 'module-' . $cm->id);
$response->standalone_url = new moodle_url('/mod/response/view.php', array('id' => $cm->id));

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/response:participate', $context);

$PAGE->set_url('/mod/response/submit.php', array('id' => $cm->id));

// Work out what stage they're in and what needs to happen.
$instance = helper::instance_factory($response->responsetype, 'information');
$instance->load_activity($response);
$response->user_responses = $instance->load_response_for_users($response, array($USER->id));
$instance->load_form($response, $USER->id, $response->in_course);

// At this point, $response->form might contain false, which is 'nothing to do' - send them off to the completed activity.
if (empty($response->form)) {
    if ($incourse) {
        redirect($response->in_course_url);
    } else {
        redirect($response->standalone_url);
    }
}

// Whatever form we currently have, is the form we need to validate against.
if ($data = $response->form->get_data()) {
    // Pass off to the subplugin to save whatever data we have here.
    $redirect = $instance->save_submission($response, $USER->id, $data);

    // Has this activity become complete now?
    if ($instance->has_now_completed($response, $USER->id)) {
        // Notify the completion system.
        require_once($CFG->libdir . '/completionlib.php');

        $completion = new completion_info($course);
        if ($completion->is_enabled($cm) && $response->requiresubmission) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
        }
    }

    // And we're done.
    redirect($redirect);
}

// So the form wasn't valid... better re-render it.
$PAGE->set_url('/mod/response/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname . ': ' . $response->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_response');

echo $output->header();

$renderable = helper::instance_factory($response->responsetype, 'output', array($response, $instance));

echo $output->render($renderable);
echo $output->footer();
