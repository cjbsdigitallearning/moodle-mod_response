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
 * Viewing all responses in a course.
 *
 * @package   mod_response
 * @copyright 2025 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Check user capability.
$cmid = required_param('id', PARAM_INT); // Course module ID.
$context = context_module::instance($cmid);
$cm = get_coursemodule_from_id('response', $cmid);
$PAGE->set_context($context);
$PAGE->set_cm($cm);

require_capability('mod/response:viewall', $context);

// Prepare filters.
$userid = optional_param('userid', null, PARAM_INT); // User id if selected in user search popup.
$search = optional_param('search', null, PARAM_NOTAGS); // User search.
$ifirst = optional_param('ifirst', null, PARAM_NOTAGS); // First initial.
$ilast = optional_param('ilast', null, PARAM_NOTAGS); // Last initial.

$filters = [];
foreach (['userid', 'search', 'ifirst', 'ilast'] as $param) {
  if (!empty(${$param})) {
    $filters[$param] = ${$param};
  }
}

// Set initials.
if (!is_null($ifirst)) {
  $SESSION->modresponse["filterfirstname-{$context->id}"] = $ifirst;
}
if (!is_null($ilast)) {
  $SESSION->modresponse["filtersurname-{$context->id}"] = $ilast;
}

$PAGE->set_url('/mod/response/viewallresponses.php', ['id' => $cmid, ...$filters]);

// Set page title and heading using course name.
$course = get_course($cm->course);
$title = $course->shortname . ': ' . get_string('response:viewallresponses', 'response');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Get all responses in course coursemodule is in.
$output = $PAGE->get_renderer('mod_response');

// Render page.
echo $output->header();

$viewalllink = new \action_link(
  new moodle_url('/mod/response/viewall.php', ['id' => $cm->id]),
  get_string('response:viewall', 'response'),
);
echo $output->render($viewalllink);

$PAGE->requires->js_call_amd('mod_response/searchwidget/user', 'init', ['/mod/response/viewallresponses.php']);
$actionbar = new \mod_response\output\action_bar($context, '/mod/response/viewallresponses.php');
echo $output->render_action_bar($actionbar);

$responses = helper::get_course_responses($course, $filters);
echo $output->render_view_all_users($responses, $course->format);

// Need to keep hold of these values.
$urlparams['course'] = $course->id;
global $OUTPUT;
$url = new moodle_url('/mod/response/download.php', $urlparams);
echo $OUTPUT->single_button($url, 'Export responses');

echo $output->footer();
