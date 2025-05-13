@mod @mod_response @responsetype @responsetype_poll
Feature: Teachers should be able to remove inappropriate answers
  In order for me to remove hate speech or profanity in answers
  As a teacher
  I need to see all answers and remove inappropriate answers

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:viewall | Allow | student | Course | C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sunny" "radio"
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Submit"
    And I log out

    # This does not materially change between with and without a reflection step.

  @javascript
  Scenario: A user with a relevant permission allowed can see all responses, but cannot see the delete button.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "View all responses"
    And I click on "View all responses" "link"
    Then I should see "Student answered"
    And I should see "Sunny"
    And I should see "Student 2"
    And I should see "Cloudy"
    And I should not see "Delete response"

  @javascript
  Scenario: A teacher can delete an answer that is not theirs.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "View all responses"
    And I follow "View all responses"
    And I should see "Student answered"
    And I should see "Delete response"
    And I follow "Delete response"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I follow "View all responses"
    And I should see "Student answered"
    And I should see "Delete response"
    And I follow "Delete response"
    And I press "Continue"
    And I follow "View all responses"
    # And there is now nothing to see as no-one has completed it yet.
    Then I should see "No-one has completed this activity yet."
