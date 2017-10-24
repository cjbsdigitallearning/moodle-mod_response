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
 * Viewing a learning response activity.
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
$back = optional_param('back', 0, PARAM_INT); // Back a step or not.
$edit = optional_param('editing', 0, PARAM_INT); // Whether editing or not, and which step through the activity.

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

// We can't know if this is relevant or not, but we need to pass it onward.
$response->going_back = !empty($back);

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/response:view', $context);

$response->course = $cm->course;
$response->cm = $cm;

// Editing requires privileges.
$canedit = has_capability('mod/response:editown', $context);
$response->is_editing = false;
// The user is currently trying to edit...
if ($edit && $canedit) {
    $response->is_editing = $edit;
}

// Set up and show the form.
$PAGE->set_url('/mod/response/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname . ': ' . $response->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_response');

echo $output->header();

$instance = helper::instance_factory($response->responsetype, 'information');
$instance->load_activity($response);
$response->user_responses = $instance->load_response_for_users($response, array($USER->id));
$instance->load_form($response, $USER->id);

if (!empty($response->form)) {
    // We need to set the page specifically to the course here so autosave works consistently between course/individual views.
    $PAGE->set_url(new moodle_url('/course/view.php', array('id' => $PAGE->course->id)));

    if (!has_capability('mod/response:participate', $context)) {
        $response->form->disable_form(get_string('cannotparticipate', 'response'));
    }
}

// There might be some aggregate data to load, e.g. group stuff.
if (!empty($response->user_responses[$USER->id]->timecompleted)) {
    $instance->load_aggregate_data($response, $USER->id);

    require_once($CFG->libdir . '/formslib.php');
    $responseclone = clone $response;
    $responseclone->context = $context;
    $response->postcompletion = new mod_response\postcompletion($PAGE->url, $responseclone);
}

// Can they delete their own answer?
helper::check_user_delete_own_response($response, $context, $cm);

// Can they see all the responses?
helper::check_can_see_all_responses($response, $context, $cm);

// Can they edit their response?
helper::check_can_edit_own_response($response, $context, $cm);

// The renderer is very much up to the plugin to identify what it is rendering.

$response->fullpage = true;
$renderable = helper::instance_factory($response->responsetype, 'output', array($response, $instance));

echo $output->render($renderable);

echo $output->footer();