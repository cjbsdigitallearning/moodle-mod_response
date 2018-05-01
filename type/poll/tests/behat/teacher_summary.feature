@mod @mod_response @responsetype @responsetype_poll
Feature: Teachers need to be able to review all answers
  In order for me to guide my class
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

  @javascript
  Scenario: Two students complete an activity without reflection step, the teacher can see both.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "No"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sunny." "radio"
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Cloudy." "radio"
    And I press "Submit"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "View all responses"
    And I follow "View all responses"
    Then I should see "What is the weather like outside?"
    And I should see "StudentA answered"
    And I should see "Sunny."
    And I should see "StudentB answered"
    And I should see "Cloudy."

  @javascript
  Scenario: Two students complete an activity with reflection step, the teacher can see both.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, please elaborate."
    And I press "Save and return to course"
    And I log out
    # First student completing the activity.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sunny." "radio"
    And I press "Next"
    And I set the field "Your answer" to "The sun is shining right now."
    And I press "Submit"
    And I log out
    # Second student completing the activity.
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Cloudy." "radio"
    And I press "Next"
    And I set the field "Your answer" to "All bleak and overcast right now."
    And I press "Submit"
    And I log out
    # Viewing the summary.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I should see "View all responses"
    And I follow "View all responses"
    Then I should see "What is the weather like outside?"
    And I should see "StudentA answered"
    And I should see "Sunny."
    And I should see "sun is shining"
    And I should see "StudentB answered"
    And I should see "Cloudy."
    And I should see "bleak and overcast"
