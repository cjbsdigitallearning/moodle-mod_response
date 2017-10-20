@mod @mod_response @responsetype @responsetype_text
Feature: In a course, students can see and respond to a question and see others' responses
  In order to pose a question
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
  Scenario: Prepare creation of an activity and check the selection system works
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Response type" to "Free text"
    Then I should see "Response - Free text"
    And I should not see "Response - Poll"

  @javascript
  Scenario: Create an activity without a wordcount
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    # Seeing it on course view.
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I follow "The weather"
    # Seeing it in its own view.
    And I should see "The weather"
    And I should see "What is the weather like outside?"
    And I log out

  @javascript
  Scenario: Create an activity with a wordcount
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I click on "#id_text_maximumwords_enabled" "css_element"
    And I set the field "text_maximumwords" to "42"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    # Seeing it in course view.
    And I should see "The weather"
    And I should see "What is the weather like outside?"
    And I wait "2" seconds
    Then I should see "[0/42]"
    And I follow "The weather"
    # Seeing it in its own view.
    And I should see "The weather"
    And I should see "What is the weather like outside?"
    And I wait "2" seconds
    And I should see "[0/42]"
