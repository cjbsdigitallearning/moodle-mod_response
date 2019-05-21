@mod @mod_response @responsetype @responsetype_poll
Feature: Users should be able to edit their answers if permitted
  In order for me to fix typos I notice in my answer
  As a user with the relevant permission
  I need to edit my answer and save the corrected version

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
  Scenario: Student cannot edit their answer.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Cloudy" "radio"
    And I press "Submit"
    Then I should not see "Edit response"

  @javascript
  Scenario: Student can edit their answer (no reflection step).
    When the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:editown | Allow | student | Course | C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Cloudy" "radio"
    And I press "Submit"
    And I follow "Edit response"
    And I click on "Sunny" "radio"
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Sunny"

  @javascript
  Scenario: Student can edit their answer (with reflection step).
    When the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:editown | Allow | student | Course | C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | You chose {choice}, please elaborate. |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Always cloudy in Britain."
    And I press "Submit"
    And I follow "Edit response"
    And I click on "Sunny" "radio"
    And I press "Next"
    And I set the field "Your answer" to "The weather has improved."
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Sunny"
    And I should see "has improved"
