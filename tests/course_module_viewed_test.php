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
use completion_info;
use context_module;
use mod_response\event\course_module_viewed;

/**
 * Tests for the response_view() helper and the course_module_viewed event it triggers.
 *
 * @package   mod_response
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group mod_response
 * @covers ::response_view
 * @covers \mod_response\event\course_module_viewed
 */
final class course_module_viewed_test extends advanced_testcase {
    /**
     * Creates a course with a text response activity and a student enrolled on it.
     *
     * @return array [$course, $response, $cm, $context, $student]
     */
    protected function create_fixture(): array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/response/lib.php');

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user();

        $gen->enrol_user($student->id, $course->id, 'student');

        $response = $gen->create_module('response', [
            'course' => $course,
            'question' => 'Question?',
            'responsetype' => 'text',
            'displaycompletionbefore' => 0,
            'displaycompletionafter' => 0,
        ]);

        $cm = get_coursemodule_from_instance('response', $response->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        return [$course, $response, $cm, $context, $student];
    }

    /**
     * Verify that response_view() triggers a single course_module_viewed event with the expected data.
     */
    public function test_response_view_triggers_course_module_viewed_event(): void {
        $this->resetAfterTest();

        [$course, $response, $cm, $context, $student] = $this->create_fixture();
        $this->setUser($student);

        $sink = $this->redirectEvents();
        response_view($response, $course, $cm, $context);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);

        $event = reset($events);
        $this->assertInstanceOf(course_module_viewed::class, $event);
        $this->assertEquals($response->id, $event->objectid);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals('response', $event->objecttable);
        $this->assertEquals('r', $event->crud);
        $this->assertEquals($student->id, $event->userid);
        $this->assertEquals(
            "The user with id '{$student->id}' viewed the 'response' activity with course module id '{$cm->id}'.",
            $event->get_description()
        );
    }

    /**
     * Verify that response_view() does not raise an error when completion tracking is enabled, even though
     * this activity does not currently support view-based completion rules.
     */
    public function test_response_view_updates_completion_data_without_error(): void {
        global $DB, $PAGE;

        $this->resetAfterTest();

        set_config('enablecompletion', 1);

        [$course, $response, , , $student] = $this->create_fixture();

        $DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);

        $cm = get_coursemodule_from_instance('response', $response->id, $course->id, false, MUST_EXIST);
        $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, ['id' => $cm->id]);
        $cm = get_coursemodule_from_instance('response', $response->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $this->setUser($student);
        $PAGE->set_url('/mod/response/view.php', ['id' => $cm->id]);

        response_view($response, $course, $cm, $context);

        $completion = new completion_info($course);
        $data = $completion->get_data($cm, false, $student->id);
        $this->assertNotNull($data);
    }
}
