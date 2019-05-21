@mod @mod_response @responsetype @responsetype_text
Feature: Users should be able to reflect on their answers
  In order to for me to reflect on multiple activities that might be interlinked
  As a student
  I need to see all my answers together at once

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
    # First response.
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    # Second response.
    And I add a "Response" to section "2"
    And I set the field "Activity title" to "Fried breakfasts"
    And I set the field "Activity question" to "What is your favourite part of a fried breakfast?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: A user completes two activities and views them on one page.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the first activity.
    And I should see "The weather"
    And I set the field "Your answer" to "It is overcast outside right now."
    And I press "Submit"
    # Completing the second activity.
    And I should see "Fried breakfasts"
    And I set the field "Your answer" to "I really like sausages out of the pork products in a fried breakfast."
    And I press "Submit"
    # Viewing the summary
    And I should see "View course summary"
    And I follow "View course summary"
    Then I should see "Responses"
    And I should see "Topic 1"
    And I should see "Topic 2"
    And I should see "The weather"
    And I should see "Fried breakfasts"
    And I should see "You wrote"
    And I should see "overcast outside"
    And I should see "I really like sausages"
    And I should see "View in context"
