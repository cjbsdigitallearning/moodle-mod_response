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
 * Delete a response to a learning response activity.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

require('../../config.php');
require_once($CFG->dirroot.'/mod/response/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$userid = required_param('u', PARAM_INT); // User ID.
$confirm = optional_param('confirm', 0, PARAM_INT); // Confirmation flag.

if (!$cm = get_coursemodule_from_id('response', $id)) {
    print_error('invalidcoursemodule');
}
$response = $DB->get_record('response', array('id' => $cm->instance), '*', MUST_EXIST);

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

// We need to fetch some data about this instance before we decide whether we can delete it. There may be nothing to delete...

$instance = helper::instance_factory($response->responsetype, 'information');
$instance->load_activity($response);
$response->user_responses = $instance->load_response_for_users($response, array($userid), true, true);

if (empty($response->user_responses[$userid])) {
    // There's no response from this user.
    print_error('deleteresponsenotcomplete', 'response');
}
if (empty($response->user_responses[$userid]->timecompleted)) {
    // The user hasn't finished it yet.
    print_error('deleteresponsenotcomplete', 'response');
}

$response->viewing_other = $USER->id != $userid;
$response->viewing_id = $userid;

$response->response = $response->user_responses[$userid];
$response->user_wrote = array(
    'picture' => $response->response->profile_picture,
    'first_name' => $response->response->first_name,
);

// Now, if we're deleting our own response, we check that we have that capability.
if (!$response->viewing_other) {
    // We're looking to delete our own.
    require_capability('mod/response:deleteown', $context);
} else {
    // We need the super-power version, along with the right to view all (to even see it).
    require_capability('mod/response:viewall', $context);
    require_capability('mod/response:manage', $context);
}

if ($confirm && confirm_sesskey()) {
    // Do the actual delete.
    response_delete_response($course, $cm, $userid);
    // Send them back to the course the activity was from.
    redirect(new moodle_url('/course/view.php', array('id' => $cm->course), 'module-' . $cm->id));
}

// Set up and show the form plus the original response.
$PAGE->set_url('/mod/response/deleteanswer.php', array('id' => $cm->id, 'u' => $userid));
$PAGE->set_title($course->shortname . ': ' . $response->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_response');

echo $output->header();

$renderable = helper::instance_factory($response->responsetype, 'output', array($response, $instance));

$deleteurl = new moodle_url('/mod/response/deleteanswer.php', array('id' => $id, 'u' => $userid, 'confirm' => 1));
$cancelurl = new moodle_url('/mod/response/view.php', array('id' => $id));

$response->fullpage = true;
echo $OUTPUT->confirm(get_string('deleteresponseconfirm', 'response'), $deleteurl, $cancelurl);

echo $output->render($renderable);

echo $output->footer();