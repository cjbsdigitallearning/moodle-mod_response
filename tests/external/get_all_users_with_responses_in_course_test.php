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

namespace mod_response\external;

use advanced_testcase;
use required_capability_exception;
use stdClass;
use moodle_url;
use mod_response\helper;

/**
 * Tests for the get_all_users_with_responses_in_course external service.
 *
 * @package   mod_response
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_response
 * @covers \mod_response\external\get_all_users_with_responses_in_course
 */
final class get_all_users_with_responses_in_course_test extends advanced_testcase {
    /**
     * Creates a course with a text response activity, a teacher, and two students who have both responded.
     *
     * @return array [$course, $response, $teacher, $student1, $student2]
     */
    protected function create_fixture(): array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/response/lib.php');

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_user();
        $student1 = $gen->create_user();
        $student2 = $gen->create_user();

        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
        $gen->enrol_user($student1->id, $course->id, 'student');
        $gen->enrol_user($student2->id, $course->id, 'student');

        $response = $gen->create_module('response', [
            'course' => $course,
            'question' => 'Question?',
            'responsetype' => 'text',
        ]);

        $this->respond_to_activity($response->id, $student1->id, 'Student 1 answer');
        $this->respond_to_activity($response->id, $student2->id, 'Student 2 answer');

        return [$course, $response, $teacher, $student1, $student2];
    }

    /**
     * Submit a text response for the given user, bypassing the form/UI layer.
     *
     * @param int $responseid The response activity ID.
     * @param int $userid The user submitting the response.
     * @param string $responsetext The text answer.
     * @return void
     */
    protected function respond_to_activity(int $responseid, int $userid, string $responsetext): void {
        global $DB;

        $response = $DB->get_record('response', ['id' => $responseid], '*', MUST_EXIST);

        $instance = helper::instance_factory($response->responsetype, 'information');
        $instance->load_activity($response);
        $response->user_responses = $instance->load_response_for_users($response, [$userid]);
        $instance->load_form($response, $userid);

        $data = new stdClass();
        $data->{'responsetype_text_' . $response->id}['text'] = $responsetext;
        $response->in_course = false;
        // It doesn't matter what the URL is, we're not going to visit it directly.
        $response->standalone_url = new moodle_url('/');
        $instance->save_submission($response, $userid, $data);
    }

    /**
     * Verify that a user with the viewall capability receives all users who have responded.
     */
    public function test_execute_returns_users_with_responses(): void {
        global $PAGE;

        $this->resetAfterTest();

        [$course, $response, $teacher] = $this->create_fixture();

        $this->setUser($teacher);
        $PAGE->set_url(new moodle_url('/mod/response/view.php', ['id' => $response->cmid]));

        $result = get_all_users_with_responses_in_course::execute($course->id, $response->cmid);

        $this->assertCount(2, $result['users']);
    }

    /**
     * Verify that a user without the viewall capability is prevented from calling the service.
     */
    public function test_execute_requires_capability(): void {
        global $PAGE;

        $this->resetAfterTest();

        [$course, $response, , $student1] = $this->create_fixture();

        $this->setUser($student1);
        $PAGE->set_url(new moodle_url('/mod/response/view.php', ['id' => $response->cmid]));

        $this->expectException(required_capability_exception::class);
        get_all_users_with_responses_in_course::execute($course->id, $response->cmid);
    }

    /**
     * Verify that an invalid course module ID is rejected by context validation before any data is fetched.
     */
    public function test_execute_with_invalid_cmid(): void {
        $this->resetAfterTest();

        [$course, , $teacher] = $this->create_fixture();

        $this->setUser($teacher);

        $this->expectException(\dml_exception::class);
        get_all_users_with_responses_in_course::execute($course->id, -1);
    }
}
