@mod @mod_response @responsetype @responsetype_text
Feature: Teachers need to be able to review all answers
  In order for me to guide my class and discuss things with them
  As a teacher
  I need to see all their answers together at once

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | StudentA | 1 | student1@example.com |
      | student2 | StudentB | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: Two students complete an activity, and the teacher can see both.
    # First student completing the activity.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "It is sunny here at the beach."
    And I press "Submit"
    And I log out
    # Second student completing the activity.
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "All overcast and dreary outside."
    And I press "Submit"
    And I log out
    # Viewing the summary.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "View all responses"
    And I follow "View all responses"
    Then I should see "What is the weather like outside?"
    And I should see "StudentA wrote"
    And I should see "sunny here"
    And I should see "StudentB wrote"
    And I should see "overcast and dreary"
