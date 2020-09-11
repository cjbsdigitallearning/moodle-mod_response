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
 * Privacy class for requesting user data.
 *
 * @package   responsetype_text
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace responsetype_text\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\tests\provider_testcase;
use \core_privacy\local\request\contextlist;
use \context_module;
use \core_privacy\local\request\approved_contextlist;
use \mod_response\privacy\provider as parentprovider;
use \responsetype_text\privacy\provider as subpluginprovider;
use \mod_response\helper;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;
use \stdClass;
use \moodle_url;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

/**
 * Privacy class for requesting user data.
 *
 * @package   responsetype_text
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group responsetype_text
 * @group mod_response
 */
class responsetype_text_privacy_testcase extends provider_testcase {

    /**
     * Do initial setup to support this test case.
     *
     * @return void Not declared for inheritance.
     */
    public function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/response/lib.php');
    }

    /**
     * Verify that the contexts fetched for the user are correct.
     */
    public function test_get_contexts_for_userid() : void {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $gen->create_module('response', [
            'course' => $c1,
            'question' => 'Example Question?',
            'responsetype' => 'text',
        ]);
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id);

        // Create an activity in a second course.
        $text2 = $gen->create_module('response', [
            'course' => $c2,
            'question' => 'Question 2?',
            'responsetype' => 'text',
        ]);
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 'Second Answer');

        // Now verify the contexts we get.
        $usercontextids = [
            $text1ctx->id,
            $text2ctx->id,
        ];
        $usercontextids = array_unique($usercontextids);

        $contextlist = parentprovider::get_contexts_for_userid($u1->id);
        $contextlistids = $contextlist->get_contextids();
        // If we compare the lists, we should find the intersection matches completely.
        $this->assertEquals(count($usercontextids), count(array_intersect($usercontextids, $contextlistids)));
    }

    /**
     * Verify that all user data for a single context is removed upon call.
     */
    public function test_delete_data_for_all_users_in_context() : void {
        global $DB;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $gen->create_module('response', [
            'course' => $c1,
            'question' => 'Question 1?',
            'responsetype' => 'text',
        ]);
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id, 'User 1 answer to Response 1');
        $this->respond_to_activity($text1->id, $u2->id, 'User 2 answer to Response 2');

        // Create an activity in a second course.
        $text2 = $gen->create_module('response', [
            'course' => $c2,
            'question' => 'Question 2?',
            'responsetype' => 'text',
        ]);
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 'User 1 answer to Response 2');
        $this->respond_to_activity($text2->id, $u2->id, 'User 2 answer to Response 2');

        // Now, delete things. We call the parent because the API will too, and verify the results.
        parentprovider::delete_data_for_all_users_in_context($text1ctx);

        // Let's query what we have. There should be two Response entries total (i.e. this shouldn't be touched)
        $records = $DB->get_records('response');
        $this->assertEquals(2, count($records));

        $originalquestions = ['Question 1?', 'Question 2?'];
        foreach ($records as $record) {
            $this->assertContains($record->question, $originalquestions);
        }

        // Now let's get the user answers.
        $recordsuser = $DB->get_records('response_user');
        // There should only be two answers.
        $this->assertEquals(2, count($recordsuser));

        // And the records that are there should match the activity we know we're dealing with.
        foreach ($recordsuser as $record) {
            $this->assertEquals($record->response, $text2->id);
        }

        // Now the specific textual responses.
        $recordsusertext = $DB->get_records('responsetype_text_user');
        $this->assertEquals(2, count($recordsusertext));
        // And assert they are connected to the correct instance.
        foreach ($recordsusertext as $record) {
            $this->assertEquals($record->response, $text2->id);
        }
    }

    /**
     * Verify that a single user's data is removed from multiple contexts.
     */
    public function test_delete_data_for_user() : void {
        global $DB;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $gen->create_module('response', [
            'course' => $c1,
            'question' => 'Question 1?',
            'responsetype' => 'text',
        ]);
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id, 'User 1 answer to Response 1');
        $this->respond_to_activity($text1->id, $u2->id, 'User 2 answer to Response 2');

        // Create an activity in a second course.
        $text2 = $gen->create_module('response', [
            'course' => $c2,
            'question' => 'Question 2?',
            'responsetype' => 'text',
        ]);
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 'User 1 answer to Response 2');
        $this->respond_to_activity($text2->id, $u2->id, 'User 2 answer to Response 2');

        // Now, delete things. We call the parent because the API will too, and verify the results.
        $contextlist = new approved_contextlist($u1, 'mod_response', [$text1ctx->id, $text2ctx->id]);
        parentprovider::delete_data_for_user($contextlist);

        // Now let's get the user answers.
        $recordsuser = $DB->get_records('response_user');
        // There should only be two answers.
        $this->assertEquals(2, count($recordsuser));

        // And the records that are there should match the activity we know we're dealing with.
        foreach ($recordsuser as $record) {
            $this->assertEquals($record->userid, $u2->id);
        }

        // Now the specific textual responses.
        $recordsusertext = $DB->get_records('responsetype_text_user');
        $this->assertEquals(2, count($recordsusertext));
        // And assert they are both for User 2.
        foreach ($recordsusertext as $record) {
            $this->assertEquals($record->userid, $u2->id);
        }
    }

    /**
     * Verify that multiple users' data is removed from multiple contexts.
     */
    public function test_delete_data_for_users() : void {
        global $DB;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();
        $u3 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $gen->create_module('response', [
            'course' => $c1,
            'question' => 'Question 1?',
            'responsetype' => 'text',
        ]);
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id, 'User 1 answer to Response 1');
        $this->respond_to_activity($text1->id, $u2->id, 'User 2 answer to Response 1');
        $this->respond_to_activity($text1->id, $u3->id, 'User 3 answer to Response 1');

        // Create an activity in a second course.
        $text2 = $gen->create_module('response', [
            'course' => $c2,
            'question' => 'Question 2?',
            'responsetype' => 'text',
        ]);
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 'User 1 answer to Response 2');
        $this->respond_to_activity($text2->id, $u2->id, 'User 2 answer to Response 2');
        $this->respond_to_activity($text2->id, $u3->id, 'User 3 answer to Response 2');

        // Now, delete things. We call the parent because the API will too, and verify the results.
        $userlist = new approved_userlist($text1ctx, 'mod_response', [$u1->id, $u2->id]);
        parentprovider::delete_data_for_users($userlist);

        // Now let's get the user answers.
        $recordsuser = $DB->get_records('response_user');
        // There should be 4 answers: user 3 in response 1, users 1, 2, 3 in response 2.
        $this->assertEquals(4, count($recordsuser));

        // And the records that are there should match the activity we know we're dealing with.
        $matches = [
            $text1->id . '-' . $u3->id,
            $text2->id . '-' . $u1->id,
            $text2->id . '-' . $u2->id,
            $text2->id . '-' . $u3->id,
        ];

        // Now the specific responses.
        $recordsusertext = $DB->get_records('responsetype_text_user');
        $this->assertEquals(4, count($recordsusertext));
        $results = [];
        foreach ($recordsusertext as $record) {
            $recordcode = $record->response . '-' . $record->userid;
            $results[] = $recordcode;
        }

        // Verify everything we expect to find did come out of the list.
        foreach ($matches as $match) {
            $this->assertContains($match, $results);
        }
        // Verify everything we got matches our original list too.
        foreach ($results as $match) {
            $this->assertContains($match, $matches);
        }
    }

    /**
     * Verify that all appropriate information is exported upon request.
     */
    public function test_export_data_for_user() : void {
        global $PAGE;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $gen->create_module('response', [
            'course' => $c1,
            'question' => 'Question 1?',
            'responsetype' => 'text',
        ]);
        $text1ctx = context_module::instance($text1->cmid);

        $u1r1 = $this->respond_to_activity($text1->id, $u1->id, 'User 1 answer to Response 1');
        $u2r1 = $this->respond_to_activity($text1->id, $u2->id, 'User 2 answer to Response 2');

        // Create an activity in a second course.
        $text2 = $gen->create_module('response', [
            'course' => $c2,
            'question' => 'Question 2?',
            'responsetype' => 'text',
        ]);
        $text2ctx = context_module::instance($text2->cmid);

        $u1r2 = $this->respond_to_activity($text2->id, $u1->id, 'User 1 answer to Response 2');
        $u2r2 = $this->respond_to_activity($text2->id, $u2->id, 'User 2 answer to Response 2');

        $contextlist = new approved_contextlist($u1, 'mod_response', [$text1ctx->id, $text2ctx->id]);

        // Set some global handling for the format_text calls we're about to use.
        $PAGE->set_context($text2ctx);
        $PAGE->set_url(new moodle_url('/mod/response/view.php', ['id' => $text2->cmid]));

        parentprovider::export_user_data($contextlist);

        $data = writer::with_context($text1ctx)->get_data([]);
        $this->assertNotEmpty($data);
        $this->assertEquals($data->question, 'Question 1?');

        $relateddata = writer::with_context($text1ctx)->get_related_data([], 'answer_text');
        $this->assertNotEmpty($relateddata);
        $this->assertEquals(count($relateddata), 1);
        $answer1 = $relateddata[0];
        $this->assertEquals($answer1->timesubmitted, transform::datetime($u1r1->timemodified));
        $this->assertEquals($answer1->response_text, "User 1 answer to Response 1");

        $data = writer::with_context($text2ctx)->get_data([]);
        $this->assertNotEmpty($data);
        $this->assertEquals($data->question, 'Question 2?');

        $relateddata = writer::with_context($text2ctx)->get_related_data([], 'answer_text');
        $this->assertNotEmpty($relateddata);
        $this->assertEquals(count($relateddata), 1);
        $answer2 = $relateddata[0];
        $this->assertEquals($answer2->timesubmitted, transform::datetime($u1r2->timemodified));
        $this->assertEquals($answer2->response_text, "User 1 answer to Response 2");
    }

    /**
     * Complete an activity using the information given.
     *
     * @param int $response The activity ID
     * @param stdClass $user The user completing the activity
     * @param string $responsetext The textual response going into the activity, a default will be used in absence of actual text
     * @return stdClass The activity object from the database
     */
    protected function respond_to_activity($response, $user, $responsetext = '') : stdClass {
        global $DB;

        if (empty($responsetext)) {
            $responsetext = 'Generic answer.';
        }

        $response = $DB->get_record('response', ['id' => $response], '*', MUST_EXIST);

        $instance = helper::instance_factory($response->responsetype, 'information');
        $instance->load_activity($response);
        $response->user_responses = $instance->load_response_for_users($response, array($user));
        $instance->load_form($response, $user);

        $data = new stdClass;
        $data->{'responsetype_text_' . $response->id}['text'] = $responsetext;
        $response->in_course = false;
        // It doesn't matter what the URL is, we're not going to visit it directly.
        $response->standalone_url = new moodle_url('/');
        $instance->save_submission($response, $user, $data);

        // Make the information available to the caller.
        return $DB->get_record('response_user', ['response' => $response->id, 'userid' => $user]);
    }
}
