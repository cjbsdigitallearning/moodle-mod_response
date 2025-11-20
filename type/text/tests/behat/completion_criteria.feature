@mod @mod_response @responsetype @responsetype_text
Feature: Users should be able to complete text response to complete courses
  In order for me to complete a course
  As a student
  I need to have my answers mark its activities as completed

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode | enablecompletion |
      | Course 1 | C1        | 0        | 1         | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Add requirements | 1 |
      | Student must submit an answer to complete this activity | 1 |
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: Student completes activity inline, marked completed.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I set the field "Your answer" to "It is overcast and bleak outside."
    And I press "Submit"
    # We fetch the completion status with an AJAX callback after returning the form.
    # So we need to wait for that to happen.
    And I wait "2" seconds
    Then I should see "You wrote"
    And I should see "overcast and bleak"
    And "Done" "button" should exist in the "The weather" "activity"

  @javascript
  Scenario: Student completes not inline, marked completed.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I set the field "Your answer" to "It is overcast and bleak outside."
    And I press "Submit"
    Then I should see "You wrote"
    And I should see "overcast and bleak"
    And I should see "Done: Submit an answer"
