@mod @mod_response @responsetype @responsetype_text
Feature: Teachers should be able to remove answers
  In order to remove answers I deem inappropriate
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
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I set the field "Your answer" to "Cloudy outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I set the field "Your answer" to "Sunny outside."
    And I press "Submit"
    And I log out

  @javascript
  Scenario: A user (here a student) that can see all responses cannot see the delete button.
    When I log in as "student1"
    And I follow "Course 1"
    And I should see "View all responses"
    And I follow "View all responses"
    Then I should see "Student wrote"
    And I should see "Cloudy outside."
    And I should see "Student 2"
    And I should see "Sunny outside."
    And I should not see "Delete response"

  @javascript
  Scenario: A user can delete an answer that is not theirs.
    When I log in as "student1"
    And I follow "Course 1"
    And I set the field "Your answer" to "Cloudy outside."
    And I press "Submit"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I should see "View all responses"
    And I follow "View all responses"
    And I should see "Student wrote"
    And I should see "Delete response"
    And I follow "Delete response"
    And I press "Continue"
    And I follow "View all responses"
    # And there is now nothing to see as no-one has completed it yet.
    Then I should see "No-one has completed this activity yet."
