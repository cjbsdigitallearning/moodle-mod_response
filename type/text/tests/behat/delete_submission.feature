@mod @mod_response @responsetype @responsetype_text
Feature: Users might choose to withdraw and submit a new answer
  In order to for me to submit a newer, better answer
  As a student
  I need to remove my existing answer to an activity

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
    And the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:participate | Allow | editingteacher | Course | C1 |
      | mod/response:deleteown | Allow | editingteacher | Course | C1 |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: A user with suitable capabilities can delete their response
    When I log in as "teacher1"
    And I follow "Course 1"
    And I follow "The weather"
    And I should see "The weather"
    And I set the field "Your answer" to "This is my response."
    And I press "Submit"
    And I should see "Delete response"
    And I follow "Delete response"
    And I should see "Are you sure you want to delete this response?"
    And I press "Continue"
    Then I should see "What is the weather like outside?"
    And I should not see "You wrote"

  @javascript
  Scenario: A user without suitable capabilities cannot delete their response
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "The weather"
    And I should see "The weather"
    And I set the field "Your answer" to "This is my response."
    And I press "Submit"
    Then I should see "You wrote"
    And I should not see "Delete response"
