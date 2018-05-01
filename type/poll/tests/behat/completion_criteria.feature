@mod @mod_response @responsetype @responsetype_poll
Feature: Users should be able to complete courses
  In order for me to complete a course
  As a student
  I need to have my response mark the activity as completed

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
    And I log out

  @javascript
  Scenario: Student completes activity inline (no reflection step), marked completed.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
      | Completion tracking | Show activity as complete when conditions are met |
      | Student must submit an answer to complete this activity | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Cloudy" "radio"
    And I press "Submit"
    # We fetch the completion status with an AJAX callback after returning the form.
    # So we need to wait for that to happen.
    And I wait "2" seconds
    Then I should see "You answered"
    And I should see "Cloudy"
    And the "The weather" "response" activity with "auto" completion should be marked as complete

  @javascript
  Scenario: Student completes activity not inline (no reflection step), marked completed.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
      | Completion tracking | Show activity as complete when conditions are met |
      | Student must submit an answer to complete this activity | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I click on "Cloudy" "radio"
    And I press "Submit"
    And I am on "Course 1" course homepage
    Then I should see "You answered"
    And I should see "Cloudy"
    And the "The weather" "response" activity with "auto" completion should be marked as complete

  @javascript
  Scenario: Student completes activity inline (reflection step), marked completed.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | {choice} is an interesting choice, please tell me more. |
      | Completion tracking | Show activity as complete when conditions are met |
      | Student must submit an answer to complete this activity | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Cloudy outside, but not raining."
    And I press "Submit"
    # We fetch the completion status with an AJAX callback after returning the form.
    # So we need to wait for that to happen.
    And I wait "2" seconds
    Then I should see "You answered"
    And I should see "Cloudy outside"
    And the "The weather" "response" activity with "auto" completion should be marked as complete

  @javascript
  Scenario: Student completes activity not inline (reflection step), marked completed.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | {choice} is an interesting choice, please tell me more. |
      | Completion tracking | Show activity as complete when conditions are met |
      | Student must submit an answer to complete this activity | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Cloudy outside, but not raining."
    And I press "Submit"
    And I am on "Course 1" course homepage
    Then I should see "You answered"
    And I should see "Cloudy outside"
    And the "The weather" "response" activity with "auto" completion should be marked as complete
