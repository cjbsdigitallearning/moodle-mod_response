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
 * Download responses.
 *
 * @package   mod_response
 * @copyright 20125 Sarah Cotton <sarah.cotton@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\download;
use mod_response\helper;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');

global $DB;
require_login();

$id = optional_param('id', '', PARAM_INT);
$ids = optional_param('ids', '', PARAM_TEXT);
$courseid = optional_param('course', '', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$allresponses = false;
$csvs = [];

if (!empty($id)) {
    // One response.
    $responses = $DB->get_records('response', ['id' => $id]);
} else {
    // All responses.
    $allresponses = true;
    [$insql, $inparams] = $DB->get_in_or_equal(explode(',', $ids));
    $sql = "SELECT *
              FROM {response}
                   WHERE id " . $insql;
    $responses = $DB->get_records_sql($sql, $inparams);
}

foreach ($responses as $response) {
    $cm = get_coursemodule_from_instance('response', $response->id);
    $context = context_module::instance($cm->id);
    $users = get_enrolled_users($context, 'mod/response:participate');

    $subplugin = helper::instance_factory($response->responsetype, 'information');
    $download = new download($context, $response, $subplugin);

    // Set up the csv.
    $csv = new csv_export_writer();
    $csv->set_filename($download->responsename);
    // Add the csv field headings.
    $csv->add_data($download->csvheadings);

    foreach ($users as $user) {
        // User details.
        $userdetails = $download->get_identity_values($user);
        // Get the full user response.
        $responsedata = helper::get_response_data($course, $response, $user->id);
        // Data for the standard response fields.
        $standardresponsedata = $download->get_standard_response_values($responsedata);
        // Data for the user response fields.
        $userresponse = $subplugin->get_response_values($responsedata);

        // If the response includes user added text.
        if ($subplugin->has_user_text($response)) {
            $text = $download->format_user_text($userresponse, $user->id);
            $filecreated = $download->create_response_file($user, $text['withlinks']);
            $userresponse[$download->usertextfield] = $text['plain'];
            if ($filecreated) {
                $download->create_file_attachments($text, $user->username);
            }
        }

        // Add a row to the csv.
        $row = array_merge($userdetails, $standardresponsedata, $userresponse);
        $csv->add_data($row);
    }

    // If there is no text field then we only have one csv, so download it now.
    if (!$subplugin->has_user_text($response) && !$allresponses) {
        $csv->download_file();
    } else {
        $download->move_file($csv->path);
    }
}
// Otherwise, zip everything up and send to the user.
$download->do_the_zip($allresponses, $course->shortname);
