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
 * Viewing a response activity.
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

$PAGE->set_url('/mod/response/view.php', array('id' => $cm->id));

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
$PAGE->set_title($course->shortname . ': ' . $response->name);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');

$output = $PAGE->get_renderer('mod_response');
echo $output->header();

// Display any activity information (eg completion requirements / dates).
$cminfo = cm_info::create($cm);
$completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
$activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);

$modinfo = get_fast_modinfo($cm->course, $USER->id);
$customdata =& $cminfo->customdata;
$customdata->showdescription = !empty($response->viewownpagedescription);
$customdata->going_back = $response->going_back;
$customdata->is_editing = $response->is_editing;
response_cm_info_dynamic($cminfo);

echo $cminfo->content;

echo $output->footer();