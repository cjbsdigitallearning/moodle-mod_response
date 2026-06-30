@mod @mod_response @responsetype @responsetype_poll
Feature: View in context links in poll response listings
  In order to navigate to poll responses in context
  As a teacher or student
  I need "View in context" links to point to the correct destination

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |

  @javascript
  Scenario: View in context links point to the activity page when response display is own page
    Given the following "courses" exist:
      | fullname | shortname | category | format | coursedisplay |
      | Course 1 | C1 | 0 | topics | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "No"
    And I press "Save and return to course"
    And I log out
    And I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I click on "Sunny." "radio"
    And I press "Submit"
    And I log out
    And I am on the "The weather" "mod_response > view" page logged in as "teacher1"
    And I follow "View all responses"
    Then I should see "View in context"
    When I follow "View all response activity responses"
    Then I should see "View in context"
    When I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I follow "View course summary"
    Then I should see "View in context"

  @javascript
  Scenario: View in context links point to the course page anchor for inline display with all sections on one page
    Given the following "courses" exist:
      | fullname | shortname | category | format | coursedisplay |
      | Course 2 | C2 | 0 | topics | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C2 | editingteacher |
      | student1 | C2 | student |
    When I log in as "teacher1"
    And I am on "Course 2" course homepage with editing mode on
    And I add a "response" activity to course "Course 2" section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "No"
    And I press "Save and return to course"
    And I log out
    And I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I click on "Sunny." "radio"
    And I press "Submit"
    And I log out
    And I am on the "The weather" "mod_response > view" page logged in as "teacher1"
    And I follow "View all responses"
    Then I should see "View in context"
    When I follow "View all response activity responses"
    Then I should see "View in context"
    When I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I follow "View course summary"
    Then I should see "View in context"

  @javascript
  Scenario: View in context links point to the section page anchor for inline display with one section per page
    Given the following "courses" exist:
      | fullname | shortname | category | format | coursedisplay |
      | Course 3 | C3 | 0 | topics | 1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C3 | editingteacher |
      | student1 | C3 | student |
    When I log in as "teacher1"
    And I am on "Course 3" course homepage with editing mode on
    And I add a "response" activity to course "Course 3" section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "No"
    And I press "Save and return to course"
    And I log out
    And I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I click on "Sunny." "radio"
    And I press "Submit"
    And I log out
    And I am on the "The weather" "mod_response > view" page logged in as "teacher1"
    And I follow "View all responses"
    Then I should see "View in context"
    When I follow "View all response activity responses"
    Then I should see "View in context"
    When I am on the "The weather" "mod_response > view" page logged in as "student1"
    And I follow "View course summary"
    Then I should see "View in context"
