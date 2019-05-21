@mod @mod_response @responsetype @responsetype_text
Feature: In a course, students can see and respond to a question and see others' responses
  In order to for my teacher to assess me
  As a student
  I need to answer the prompt question

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Student can complete activity inline.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I set the field "Your answer" to "This is my response."
    And I press "Submit"
    Then I should see "You wrote"
    And I should see "This is my response."

  @javascript
  Scenario: Student can complete activity not-inline.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I should see "The weather"
    And I set the field "Your answer" to "This is my response."
    And I press "Submit"
    Then I should see "You wrote"
    And I should see "This is my response."
