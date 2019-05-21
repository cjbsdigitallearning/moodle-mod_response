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
 * Viewing all responses to a response activity.
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

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/response:viewall', $context);

$response->cm = $cm;
$response->course = $course;

// Set up and show the form.
$PAGE->set_url('/mod/response/viewall.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname . ': ' . $response->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_response');

echo $output->header();

$response->group_selector = groups_print_activity_menu($cm, $PAGE->url, true);
$group = groups_get_activity_group($cm);

$instance = helper::instance_factory($response->responsetype, 'information');
$instance->load_activity($response);

if ($group == 0) {
    $response->all_responses = $instance->load_all_responses($response);
} else {
    // Get who is in the group and then get their responses.
    $members = groups_get_members($group, 'u.id');
    $response->all_responses = $instance->load_response_for_users($response, array_keys($members), true, true);
}

// If they can delete responses, we need to build suitable links.
if (has_capability('mod/response:manage', $context)) {
    foreach (array_keys($response->all_responses) as $userid) {
        $deletelink = new moodle_url('/mod/response/deleteanswer.php', array('id' => $cm->id, 'u' => $userid));
        $response->all_responses[$userid]->delete_link = $deletelink;
    }
}

// The renderer is very much up to the plugin to identify what it is rendering.

$renderable = helper::instance_factory($response->responsetype, 'viewall', array($response, $instance));

echo $output->render($renderable);

echo $output->footer();