@mod @mod_response @responsetype @responsetype_text
Feature: Users might choose to withdraw and submit a new text answer
  In order for me to submit a newer, better answer
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
    And I am on the "C1" "course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "response" activity to course "Course 1" section "1" and I fill the form with:
      | Activity title        | The weather                        |
      | Activity question     | What is the weather like outside?  |
      | Response type         | Free text                          |
      | Response display      | Inline - within the module section |
    And I log out

  @javascript
  Scenario: A user with suitable capabilities can delete their response.
    Given I am on the "The weather" "mod_response > view" page logged in as "teacher1"
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
  Scenario: A user without suitable capabilities cannot delete their response.
    Given I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I should see "The weather"
    And I set the field "Your answer" to "This is my response."
    And I press "Submit"
    Then I should see "You wrote"
    And I should not see "Delete response"
