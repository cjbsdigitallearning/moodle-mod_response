@mod @mod_response @responsetype @responsetype_poll
Feature: In a course, students can see and respond to a question
  In order to submit to a response activity
  As a student
  I need to answer the prompt question and possibly reflect on my answer

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
  Scenario: Student can complete no-reflection activity inline.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
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
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Sunny."

  @javascript
  Scenario: Student can complete no-reflection activity non-inline.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
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
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Sunny."

  @javascript
  Scenario: Student can complete reflection activity inline.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, how does that make you feel?"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Next"
    And I should see "You chose"
    And I should see "Sunny."
    And I set the field "Your answer" to "I like it being sunny outside."
    Then I press "Submit"
    And I should see "You answered"
    And I should see "Sunny."
    And I should see "I like it being sunny outside."
    And I should not see "Submit"

  @javascript
  Scenario: Student can complete reflection activity non-inline.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, how does that make you feel?"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Next"
    And I should see "You chose"
    And I should see "Sunny."
    And I set the field "Your answer" to "I like it being sunny outside."
    Then I press "Submit"
    And I should see "You answered"
    And I should see "Sunny."
    And I should see "I like it being sunny outside."
    And I should not see "Submit"

  @javascript
  Scenario: Student can complete reflection activity inline after changing their mind mid-way.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, how does that make you feel?"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Next"
    And I should see "You chose"
    And I should see "Sunny."
    And I press "Back"
    And I click on "Cloudy." "radio"
    And I press "Next"
    And I set the field "Your answer" to "I would like it being sunny outside but it is cloudy."
    Then I press "Submit"
    And I should see "You answered"
    And I should see "Cloudy."
    And I should see "I would like it being sunny outside but it is cloudy."
    And I should not see "Submit"

  @javascript
  Scenario: Student can complete reflection activity non-inline after changing their mind mid-way.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Poll"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Choice 1" to "Sunny."
    And I set the field "Choice 2" to "Cloudy."
    And I set the field "Choice 3" to "Raining."
    And I set the field "Add a reflection step" to "Yes"
    And I set the field "Reflection text" to "You chose {choice}, how does that make you feel?"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "The weather"
    And I should see "The weather"
    And I click on "Sunny." "radio"
    And I press "Next"
    And I should see "You chose"
    And I should see "Sunny."
    And I press "Back"
    And I click on "Cloudy." "radio"
    And I press "Next"
    And I set the field "Your answer" to "I would like it being sunny outside but it is cloudy."
    Then I press "Submit"
    And I should see "You answered"
    And I should see "Cloudy."
    And I should see "I would like it being sunny outside but it is cloudy."
    And I should not see "Submit"
