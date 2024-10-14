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
 * Viewing all response activities in a course.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

require('../../config.php');
require_once($CFG->dirroot.'/mod/response/lib.php');
require_once($CFG->libdir.'/completionlib.php');

$id = required_param('id', PARAM_INT); // Course ID.

$PAGE->set_url('/mod/response/index.php', ['id' => $id]);

if (!$course = $DB->get_record("course", ["id" => $id])) {
    throw new moodle_exception('invalidcourseid', 'error');
}

require_login($course);
$PAGE->set_pagelayout('incourse');

$responsesplural = get_string('modulenameplural', 'response');
$responsessingular = get_string('modulename', 'response');

$PAGE->navbar->add($responsesplural);
$PAGE->set_title($course->shortname . ': ' . $responsesplural);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($responsesplural, 2);

$responses = get_all_instances_in_course('response', $course);
if (empty($responses)) {
    // There... are responses in this course, right?
    notice(get_string('thereareno', 'moodle', $responsesplural), "/course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    die;
}

$responselist = [];

// Create an object to store the items that don't have a response yet.
$noresponseobj = (object) [
    'section_title' => get_string('yettorespond', 'mod_response'),
    'responses' => [],
];

if (course_format_uses_sections($course->format)) {
    // This course format uses sections, so we need to arrange for this.
    foreach ($responses as $response) {
        if (!isset($responselist[$response->section])) {
            $responselist[$response->section] = new stdClass();
            $responselist[$response->section]->section_title = get_section_name($course, $response->section);
            $responselist[$response->section]->responses = [];
        }
        $activity = get_response_data($course, $response, $USER->id);
        if (isset($activity->response)) {
            $responselist[$response->section]->responses[] = $activity;
        } else {
            $noresponseobj->responses[] = $activity;
        }
    }
} else {
    // No sections here, so present it flat.
    $responselist[] = new stdClass();
    $responselist[0]->section_title = '';
    $responselist[0]->responses = [];
    foreach ($responses as $response) {
        $activity = get_response_data($course, $response, $USER->id);
        if (isset($activity->response)) {
            $responselist[0]->responses[] = $activity;
        } else {
            $noresponseobj->responses[] = $activity;
        }
    }
}
$responselist[] = $noresponseobj;

// We already filtered activities that haven't been completed. This might result in empty sections.
foreach ($responselist as $sectionid => $section) {
    if (empty($section->responses)) {
        unset ($responselist[$sectionid]);
    }
}

if (empty($responselist)) {
    // There's nothing to show.
    notice(get_string('thereareno', 'moodle', $responsesplural), "/course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    die;
}

$output = $PAGE->get_renderer('mod_response');
echo $output->render_summarycourse($responselist);

echo $OUTPUT->footer();

/**
 * Builds a simplified response object to pass out to templates for rendering
 * a given activity in a course summary.
 *
 * @param object $course The course in question
 * @param object $response The response object for a given response activity
 * @param int $userid The user ID whose response is being examined
 * @param object $renderer The renderer to apply, typically the mod_response one
 * @return object A simple object to pass to the summary template, with an individual activity having already been templated.
 */
function get_response_data($course, $response, $userid, $renderer = null) {
    global $PAGE;
    if (empty($renderer)) {
        $renderer = $PAGE->get_renderer('mod_response');
    }

    $cm = get_coursemodule_from_instance('response', $response->id);

    $return = new stdClass();
    $return->activity_title = $cm->name;
    $return->question = $response->question;
    $instance = helper::instance_factory($response->responsetype, 'information');

    // Determine if the user has responded.
    // We actually can't rely on completion status if it wasn't tracked by the completion system, so use ours.
    $response->user_responses = $instance->load_response_for_users($response, [$userid]);
    if (!empty($response->user_responses[$userid]) || !empty($response->user_responses[$userid]->timecompleted)) {
        $return->response = $response->user_responses[$userid];
    }
    $instance->load_activity($response);

    $instance->load_aggregate_data($response, $userid);

    $return->cm_id = $cm->id;
    $return->course_id = $course->id;
    $return->section_id = null;

    $sections = course_get_format($course->id)->get_sections();
    foreach ($sections as $sectionobj) {
        if ($sectionobj->id == $cm->section) {
            $return->section_id = $sectionobj->section;
        }
    }

    // When showing in context, the link varies depending on course format.
    $return->view_in_course = !empty($course->format) && $course->format != 'singleactivity';
    $return->responsetype = $response->responsetype;
    $return->aggregate = !empty($response->aggregate) ? $response->aggregate : new stdClass();
    $return->activity = $response->activity;
    $return->displaypeerresults = $response->displaypeerresults;
    $return->icon = new pix_icon('icon', '', 'responsetype_' . $response->responsetype);

    $renderable = helper::instance_factory($response->responsetype, 'summaryoutput', [$return, $instance]);
    $return->render = $renderer->render($renderable);

    return $return;
}
