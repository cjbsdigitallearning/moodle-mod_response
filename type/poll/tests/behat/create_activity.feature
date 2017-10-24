@mod @mod_response @responsetype @responsetype_poll
Feature: In a course, teacher can pose a poll question
  In order for me to pose a question
  As a teacher
  I need to create an activity

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
  Scenario: Prepare creation of an activity and check the selection system works.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Response type" to "Poll"
    Then I should see "Response - Poll"
    And I should see "Choice 1"
    And I should not see "Response - Free text"

  @javascript
  Scenario: Create an activity without a reflection step.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
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
    And I follow "Course 1"
    # Seeing it on course view.
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Sunny"
    And I follow "The weather"
    # Seeing it in its own view.
    And I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Sunny"
    And I log out

  @javascript
  Scenario: Create an activity with a reflection step.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, how does that make you feel?"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    # Seeing it on course view.
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Sunny."
    And "Next" "button" should be visible
    # Seeing it in its own view.
    And I follow "The weather"
    And I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Sunny."
    And "Next" "button" should be visible
    And I log out

  @javascript
  Scenario: Create an activity with a reflection step with wordcount.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, how does that make you feel?"
    And I click on "#id_poll_maximumwords_enabled" "css_element"
    And I set the field "poll_maximumwords" to "42"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    # The easiest way is to follow the form through on its own page.
    # We're not really testing submission here, but proving it saved correctly.
    And I follow "The weather"
    And I should see "The weather"
    And I should see "What is the weather like outside?"
    And I click on "Sunny." "radio"
    And I press "Next"
    And I wait "2" seconds
    Then I should see "[0/42]"
