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
 * Supporting functions for the responsetype_text plugin.
 *
 * @package   responsetype_text
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Serves the response-text files.
 *
 * @package  responsetype_text
 * @category files
 * @param stdClass $course Standard course object
 * @param stdClass $cm Standard course module object
 * @param stdClass $context Standard context object
 * @param string $filearea The file area to serve from
 * @param array $args Any additional arguments the filesystem might have
 * @param bool $forcedownload True to force download (not guaranteed to be actual boolean by sender)
 * @param array $options Additional options affecting the file serving (see the filesystem subsystem)
 * @return bool False if file not found, does not return if found - just send the file
 */
function responsetype_text_pluginfile(stdClass $course,
                                      stdClass $cm,
                                      stdClass $context,
                                      string $filearea,
                                      array $args,
                                      $forcedownload,
                                      array $options=[]) {
    global $DB, $USER, $CFG;

    // Load the underlying Response library.
    require_once($CFG->dirroot . '/mod/response/lib.php');

    // If the requested file isn't a module context, we have a problem.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // So the next situation, do we have the ability to see this activity at all?
    if (!has_capability('mod/response:view', $context)) {
        // We can't see the activity at all.
        return false;
    }

    // Args should contain the response-from-user ID, plus the filename, e.g. [1, 'myfile.png'].
    // Let's load that to verify we can see it.
    if (empty($args[0]) || !is_numeric($args[0])) {
        return false;
    }
    $requestedresponse = $DB->get_record('responsetype_text_user', ['id' => $args[0]]);
    if (empty($requestedresponse)) {
        return false;
    }

    // Work out if we can see the activity response.
    $userid = $requestedresponse->userid;
    $response = $DB->get_record('response', array('id' => $cm->instance), '*', MUST_EXIST);

    // Now we need to verify the user could conceivably could see these answers.
    $instance = helper::instance_factory($response->responsetype, 'information');
    $instance->load_activity($response);
    $response->user_responses = $instance->load_response_for_users($response, array($userid, $USER->id));

    // First, did the viewing user complete the activity?
    if (empty($response->user_responses[$USER->id])) {
        return false;
    }
    // Did the user whose completion is requested complete the activity?
    if (empty($response->user_responses[$userid])) {
        return false;
    }

    // Now, can the user actually see it? This involves verifying peer results etc.
    $cansee = false;
    if ($userid == $USER->id) {
        $cansee = true;
    }
    $displaypeerresults = (int) $response->displaypeerresults;
    if ($displaypeerresults & RESPONSE_PEER_RESULTS_ALL) {
        $cansee = true;
    }
    if ($displaypeerresults & RESPONSE_PEER_RESULTS_GROUP) {
        // Is the user in the same group?
        $membersingroup = helper::get_users_in_same_group($response->id, $USER->id);
        if (in_array($userid, $membersingroup)) {
            $cansee = true;
        }
    }
    if (!$cansee) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/responsetype_text/$filearea/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
        send_file_not_found();
    }
    // Send the file.
    send_stored_file($file, null, 0, $forcedownload, $options);
}
