@mod @mod_response @responsetype @responsetype_text
Feature: Users should be able to edit their answers if permitted
  In order to for me to fix typos I notice in my answer
  As a student
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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Edit settings" in current page administration
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: Student cannot edit their answer.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I set the field "Your answer" to "It is overcast and bleak outside."
    And I press "Submit"
    Then I should not see "Edit response"

  @javascript
  Scenario: Student can edit their answer.
    When the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:editown | Allow | student | Course | C1 |
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I set the field "Your answer" to "It is overcast and bleak outside."
    And I press "Submit"
    Then I should see "Edit response"
    And I follow "Edit response"
    And I should see "It is overcast and bleak outside."
    And I set the field "Your answer" to "It was bleak earlier but is brighter now."
    And I press "Submit"
    And I should see "You wrote"
    And I should see "bleak earlier"
    And I should not see "Submit"
