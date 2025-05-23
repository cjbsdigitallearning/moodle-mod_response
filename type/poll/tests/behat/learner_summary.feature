@mod @mod_response @responsetype @responsetype_poll
Feature: Users should be able to reflect on their answers
  In order for me to reflect on multiple activities that might be interlinked
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
    # Make a simple poll.
    And I add a "response" activity to course "Course 1" section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "No"
    And I press "Save and return to course"
    # Make a second simple poll.
    And I add a "response" activity to course "Course 1" section "2"
    And I set the field "Activity title" to "Fried breakfasts"
    And I set the field "Activity question" to "What is your favourite part of a fried breakfast?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sausages."
    And I set the field "Choice 2" to "Bacon."
    And I set the field "Choice 3" to "Eggs."
    And I set the field "Choice 4" to "Toast."
    And I set the field "Add a reflection step" to "No"
    And I press "Save and return to course"
    # Make a third poll, this time with a reflection step.
    And I add a "response" activity to course "Course 1" section "3"
    And I set the field "Activity title" to "Working beverages"
    And I set the field "Activity question" to "What do you drink most of while working?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "tea"
    And I set the field "Choice 2" to "coffee"
    And I set the field "Choice 3" to "water"
    And I set the field "Choice 4" to "Coca-Cola"
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "How much {choice} do you drink?"
    And I press "Save and return to course"
    # Rename sections.
    And I set the field "Edit section name" in the "li#section-0" "css_element" to "Topic 1"
    And I set the field "Edit section name" in the "li#section-1" "css_element" to "Topic 2"
    And I set the field "Edit section name" in the "li#section-2" "css_element" to "Topic 3"
    And I log out

  @javascript
  Scenario: A user completes two activities and views them on one page.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the first activity.
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Submit"
    # Completing the second activity.
    And I should see "Fried breakfasts"
    And I click on "Sausages." "radio"
    And I press "Submit"
    # Viewing the summary.
    And I should see "View course summary"
    And I follow "View course summary"
    Then I should see "Responses"
    And I should see "Topic 1"
    And I should see "The weather"
    And I should see "Fried breakfasts"
    And I should see "You answered"
    And I should see "Sunny."
    And I should see "Sausages."

  @javascript
  Scenario: A user completes three activities, one with a reflection step, and views them all on one page.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the first activity.
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Submit"
    # Completing the second activity because it's easier than selecting the third out of sequence.
    And I should see "Fried breakfasts"
    And I click on "Sausages." "radio"
    And I press "Submit"
    # Completing the third activity.
    And I should see "Working beverages"
    And I click on "tea" "radio"
    And I press "Next"
    And I should see "How much"
    And I should see "tea"
    And I set the field "Your answer" to "A good brew can help deal with any situation that comes up."
    And I press "Submit"
    # Viewing the summary.
    And I should see "View course summary"
    And I follow "View course summary"
    Then I should see "Responses"
    And I should see "Topic 1"
    And I should see "Topic 2"
    And I should see "Topic 3"
    And I should see "The weather"
    And I should see "Fried breakfasts"
    And I should see "Working beverages"
    And I should see "You answered"
    And I should see "Sunny."
    And I should see "Sausages."
    And I should see "tea"
    And I should see "A good brew can help"
