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

namespace mod_response;

use advanced_testcase;
use moodle_exception;
use required_capability_exception;
use stdClass;

/**
 * Tests for the download.php entry point's access checks.
 *
 * The script itself sends CSV/zip output and calls exit() on the success path, so these tests only exercise
 * the guard clauses (require_login/require_sesskey/require_capability) which throw before any output is sent.
 *
 * @package   mod_response
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_response
 * @covers ::download
 */
final class download_test extends advanced_testcase {
    /**
     * Creates a course with a text response activity, a teacher, and a student who has responded.
     *
     * @return array [$course, $response, $teacher, $student]
     */
    protected function create_fixture(): array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/response/lib.php');

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_user();
        $student = $gen->create_user();

        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
        $gen->enrol_user($student->id, $course->id, 'student');

        $response = $gen->create_module('response', [
            'course' => $course,
            'question' => 'Question?',
            'responsetype' => 'text',
            'displaycompletionbefore' => 0,
            'displaycompletionafter' => 0,
        ]);

        $this->respond_to_activity($response->id, $student->id, 'Student answer');

        return [$course, $response, $teacher, $student];
    }

    /**
     * Records a text response for the given user directly in the database, bypassing the form/page layer
     * entirely so that $PAGE is left untouched (the download.php script under test needs to set up its own
     * page/course context via require_login()).
     *
     * @param int $responseid The response activity ID.
     * @param int $userid The user submitting the response.
     * @param string $responsetext The text answer.
     * @return void
     */
    protected function respond_to_activity(int $responseid, int $userid, string $responsetext): void {
        global $DB;

        $now = time();

        $textrecordid = $DB->insert_record('responsetype_text_user', (object) [
            'response' => $responseid,
            'userid' => $userid,
            'timesubmitted' => $now,
            'response_text' => $responsetext,
        ]);

        $DB->insert_record('response_user', (object) [
            'response' => $responseid,
            'userid' => $userid,
            'response_identifier' => $textrecordid,
            'timecreated' => $now,
            'timemodified' => $now,
            'timecompleted' => $now,
        ]);
    }

    /**
     * Requires download.php, temporarily changing into its directory so its relative config.php include resolves.
     *
     * @return void
     */
    protected function require_download_script(): void {
        global $CFG;

        $previouscwd = getcwd();
        chdir($CFG->dirroot . '/mod/response');
        try {
            require($CFG->dirroot . '/mod/response/download.php');
        } finally {
            chdir($previouscwd);
        }
    }

    /**
     * Verify that requesting the download without a valid session key is rejected before any data is read.
     */
    public function test_download_without_sesskey_is_rejected(): void {
        $this->resetAfterTest();

        [, $response, $teacher] = $this->create_fixture();
        $this->setUser($teacher);

        $_GET['id'] = $response->id;
        $_GET['sesskey'] = 'notarealsesskey';

        try {
            $this->expectException(moodle_exception::class);
            $this->require_download_script();
        } finally {
            unset($_GET['id'], $_GET['sesskey']);
        }
    }

    /**
     * Verify that a user without the viewall capability is rejected before any data is read, even with a
     * valid session key.
     */
    public function test_download_without_capability_is_rejected(): void {
        global $PAGE;

        $this->resetAfterTest();

        [, $response, , $student] = $this->create_fixture();
        $this->setUser($student);
        $PAGE->set_url('/mod/response/download.php');

        $_GET['id'] = $response->id;
        $_GET['sesskey'] = sesskey();

        try {
            $this->expectException(required_capability_exception::class);
            $this->require_download_script();
        } finally {
            unset($_GET['id'], $_GET['sesskey']);
        }
    }
}
