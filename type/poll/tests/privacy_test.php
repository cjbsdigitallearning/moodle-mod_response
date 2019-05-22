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
 * @package   responsetype_poll
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace responsetype_poll\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\tests\provider_testcase;
use \core_privacy\local\request\contextlist;
use \context_module;
use \core_privacy\local\request\approved_contextlist;
use \mod_response\privacy\provider as parentprovider;
use \responsetype_poll\privacy\provider as subpluginprovider;
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
 * @package   responsetype_poll
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @group responsetype_poll
 * @group mod_response
 */
class responsetype_poll_privacy_testcase extends provider_testcase {

    public function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/response/lib.php');
    }

    /**
     * Create a poll instance.
     *
     * @param testing_data_generator $gen Data generator object
     * @param stdClass $course A course object
     * @param string $question The question for the response
     * @param int $choices Number of choices in the poll
     * @param string $reflectionprompt The reflection prompt; leave empty for no reflection step
     * @return stdClass The created module object
     */
    protected function create_poll($gen, $course, $question, $choices, $reflectionprompt = '') {

        $options = [
            'course' => $course,
            'question' => $question,
            'responsetype' => 'poll',
            'poll_reflectionstep' => 0,
            'poll_reflectiontext' => '',
        ];
        for ($i = 1; $i <= 5; $i++) {
            $options['poll_choice' . $i] = $i <= $choices ? 'Choice ' . $i : '';
        }

        if (!empty($reflectionprompt)) {
            $options['poll_reflectionstep'] = 1;
            $options['poll_reflectiontext'] = $reflectionprompt;
        }

        return $gen->create_module('response', $options);
    }

    /**
     * Verify that the contexts fetched for the user are correct.
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $this->create_poll($gen, $c1, 'Question 1?', 2, '');
        $text1ctx = context_module::instance($text1->cmid);
        $this->respond_to_activity($text1->id, $u1->id, 1);

        // Create an activity in a second course.
        $text2 = $this->create_poll($gen, $c2, 'Question 2?', 2, '');
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 2);

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
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $this->create_poll($gen, $c1, 'Question 1?', 2, 'User answer?');
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id, 1, 'User 1 answer to Response 1');
        $this->respond_to_activity($text1->id, $u2->id, 1, 'User 2 answer to Response 1');

        // Create an activity in a second course.
        $text2 = $this->create_poll($gen, $c1, 'Question 2?', 2, 'User answer?');
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 2, 'User 1 answer to Response 2');
        $this->respond_to_activity($text2->id, $u2->id, 2, 'User 2 answer to Response 2');

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
        $recordsusertext = $DB->get_records('responsetype_poll_user');
        $this->assertEquals(4, count($recordsusertext));
        // And assert they are connected to the correct instance.
        foreach ($recordsusertext as $record) {
            $this->assertEquals($record->response, $text2->id);
        }
    }

    /**
     * Verify that a single user's data is removed from multiple contexts.
     */
    public function test_delete_data_for_user() {
        global $DB;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $this->create_poll($gen, $c1, 'Question 1?', 2, 'User answer?');
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id, 1, 'User 1 answer to Response 1');
        $this->respond_to_activity($text1->id, $u2->id, 1, 'User 2 answer to Response 1');

        // Create an activity in a second course.
        $text2 = $this->create_poll($gen, $c1, 'Question 2?', 2, 'User answer?');
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 2, 'User 1 answer to Response 2');
        $this->respond_to_activity($text2->id, $u2->id, 2, 'User 2 answer to Response 2');

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
        $recordsusertext = $DB->get_records('responsetype_poll_user');
        $this->assertEquals(4, count($recordsusertext));
        // And assert they are both for User 2.
        foreach ($recordsusertext as $record) {
            $this->assertEquals($record->userid, $u2->id);
        }
    }

    /**
     * Verify that multiple users' data is removed from multiple contexts.
     */
    public function test_delete_data_for_users() {
        global $DB;

        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();
        $u3 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $this->create_poll($gen, $c1, 'Question 1?', 2, 'User answer?');
        $text1ctx = context_module::instance($text1->cmid);

        $this->respond_to_activity($text1->id, $u1->id, 1, 'User 1 answer to Response 1');
        $this->respond_to_activity($text1->id, $u2->id, 1, 'User 2 answer to Response 1');
        $this->respond_to_activity($text1->id, $u3->id, 1, 'User 3 answer to Response 1');

        // Create an activity in a second course.
        $text2 = $this->create_poll($gen, $c1, 'Question 2?', 2, 'User answer?');
        $text2ctx = context_module::instance($text2->cmid);

        $this->respond_to_activity($text2->id, $u1->id, 2, 'User 1 answer to Response 2');
        $this->respond_to_activity($text2->id, $u2->id, 2, 'User 2 answer to Response 2');
        $this->respond_to_activity($text2->id, $u3->id, 2, 'User 3 answer to Response 2');

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
        $recordsusertext = $DB->get_records('responsetype_poll_user');
        // There are two records per poll response when there's a reflection step.
        $this->assertEquals(8, count($recordsusertext));
        $results = [];
        foreach ($recordsusertext as $record) {
            $recordcode = $record->response . '-' . $record->userid;
            $results[] = $recordcode;
        }
        $results = array_unique($results);

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
    public function test_export_data_for_user() {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();
        $c1 = $gen->create_course();
        $c2 = $gen->create_course();

        $u1 = $gen->create_user();
        $u2 = $gen->create_user();

        // Create an activity in one course.
        $text1 = $this->create_poll($gen, $c1, 'Question 1?', 2);
        $text1ctx = context_module::instance($text1->cmid);

        $u1r1 = $this->respond_to_activity($text1->id, $u1->id, 1);
        $u2r1 = $this->respond_to_activity($text1->id, $u2->id, 1);

        // Create an activity in a second course.
        $text2 = $this->create_poll($gen, $c1, 'Question 2?', 2, 'User answer?');
        $text2ctx = context_module::instance($text2->cmid);

        $u1r2 = $this->respond_to_activity($text2->id, $u1->id, 2, 'User 1 answer to Response 2');
        $u2r2 = $this->respond_to_activity($text2->id, $u2->id, 2, 'User 2 answer to Response 2');

        $contextlist = new approved_contextlist($u1, 'mod_response', [$text1ctx->id, $text2ctx->id]);
        parentprovider::export_user_data($contextlist);

        // Response 1 doesn't have a reflection step and the export should agree with that.
        $data = writer::with_context($text1ctx)->get_data([]);
        $this->assertNotEmpty($data);
        $this->assertEquals($data->question, 'Question 1?');

        $relateddata = writer::with_context($text1ctx)->get_related_data([], 'answer_poll');
        $this->assertNotEmpty($relateddata);
        $this->assertEquals(1, count($relateddata));
        $answer1 = $relateddata[0];
        $this->assertEquals(transform::datetime($u1r1->timemodified), $answer1->timesubmitted);
        $this->assertEquals('Choice 1', $answer1->choice);
        $this->assertEquals('', $answer1->reflection_text);

        // Response 2 does have a reflection step, let's check.
        $data = writer::with_context($text2ctx)->get_data([]);
        $this->assertNotEmpty($data);
        $this->assertEquals('Question 2?', $data->question);

        $relateddata = writer::with_context($text2ctx)->get_related_data([], 'answer_poll');
        $this->assertNotEmpty($relateddata);
        // This should have two rows, one for the initial submission, and one for the final submission.
        $this->assertEquals(2, count($relateddata));
        $answer2a = $relateddata[0];
        $this->assertEquals('Choice 2', $answer2a->choice);
        $this->assertEquals('', $answer2a->reflection_text);

        $answer2b = $relateddata[1];
        $this->assertEquals(transform::datetime($u1r2->timemodified), $answer2b->timesubmitted);
        $this->assertEquals('Choice 2', $answer2b->choice);
        $this->assertEquals('User 1 answer to Response 2', $answer2b->reflection_text);
    }

    /**
     * Complete an activity using the information given. Does not account for back/forward steps.
     *
     * @param int $response The activity ID
     * @param stdClass $user The user completing the activity
     * @param int $choice The choice (1-5) being selected
     * @param string $reflectiontext The textual response going into the activity
     */
    protected function respond_to_activity($response, $user, $choice, $reflectiontext = '') {
        global $DB;

        $this->setUser($user);

        $response = $DB->get_record('response', ['id' => $response], '*', MUST_EXIST);
        $originalresponse = clone $response;

        $instance = helper::instance_factory($response->responsetype, 'information');
        $instance->load_activity($response);
        $response->user_responses = $instance->load_response_for_users($response, array($user));
        $instance->load_form($response, $user);

        $data = new stdClass;
        $data->{'poll_choice' . $response->id} = $choice;
        $response->in_course = false;
        // It doesn't matter what the URL is, we're not going to visit it directly.
        $response->standalone_url = new moodle_url('/');
        $instance->save_submission($response, $user, $data);

        // Did this have a reflection step?
        if (!$instance->has_now_completed($response)) {
            unset ($instance, $response);
            $response = clone $originalresponse;
            $instance = helper::instance_factory($response->responsetype, 'information');
            $instance->load_activity($response);
            $response->user_responses = $instance->load_response_for_users($response, array($user));
            $instance->load_form($response, $user);

            $data = new stdClass;
            $data->{'poll_choice' . $response->id} = $choice;
            $data->{'responsetype_poll_' . $response->id}['text'] = $reflectiontext;
            $response->in_course = false;
            // It doesn't matter what the URL is, we're not going to visit it directly.
            $response->standalone_url = new moodle_url('/');
            $instance->save_submission($response, $user, $data);
        }

        // Make the information available to the caller.
        return $DB->get_record('response_user', ['response' => $response->id, 'userid' => $user]);
    }
}
