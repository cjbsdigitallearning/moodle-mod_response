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
$userid = optional_param('userid', '', PARAM_TEXT); // User id if selected in user search popup.
$search = optional_param('search', '', PARAM_TEXT); // User search.
$firstinitial = optional_param('ifirst', '', PARAM_ALPHA); // First initial.
$lastinitial = optional_param('ilast', '', PARAM_ALPHA); // Last initial.

$PAGE->requires->js_call_amd('mod_response/searchwidget/user', 'init');

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

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

$PAGE->set_url('/mod/response/viewall.php', ['id' => $cm->id]);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/response:viewall', $context);

$response->cm = $cm;
$response->course = $course;

// Set initials.
if (isset($firstinitial)) {
    $SESSION->modresponse["filterfirstname-{$context->id}"] = $firstinitial;
}
if (isset($lastinitial)) {
    $SESSION->modresponse["filtersurname-{$context->id}"] = $lastinitial;
}

// Set up and show the form.
$PAGE->set_title($course->shortname . ': ' . $response->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_response');

echo $output->header();

$actionbar = new \mod_response\output\action_bar($context);
echo $output->render_action_bar($actionbar);

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

// Filter responses according to query paramaters.
$filters = [
    'userid' => $userid,
    'search' => $search,
    'ifirst' => $firstinitial,
    'ilast' => $lastinitial,
];
$response->all_responses = helper::filter_responses($response->all_responses, $filters, $course->id);

// If they can delete responses, we need to build suitable links.
if (has_capability('mod/response:manage', $context)) {
    foreach (array_keys($response->all_responses) as $userid) {
        $deletelink = new moodle_url('/mod/response/deleteanswer.php', ['id' => $cm->id, 'u' => $userid]);
        $response->all_responses[$userid]->delete_link = $deletelink;
    }
}

// The renderer is very much up to the plugin to identify what it is rendering.

$renderable = helper::instance_factory($response->responsetype, 'viewall', [$response, $instance]);

echo $output->render($renderable);

echo $output->footer();
