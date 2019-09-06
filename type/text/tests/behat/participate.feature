@mod @mod_response @responsetype @responsetype_text
Feature: Not all students can participate in a course - but it should still be visible
  In order for me to decide to enrol in a course
  As a student
  I need to see the activities in it but not be able to complete them

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | teacher2 | Teacher | 2 | teacher2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher2 | C1 | teacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: A user without suitable permissions cannot participate in the activity inline.
    When I log in as "teacher2"
    And I am on "Course 1" course homepage
    Then I should see "The weather"
    And the "Submit" "button" should be disabled

  @javascript
  Scenario: A user without suitable permissions cannot participate in the activity non-inline.
    When I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    Then I should see "The weather"
    And the "Submit" "button" should be disabled
