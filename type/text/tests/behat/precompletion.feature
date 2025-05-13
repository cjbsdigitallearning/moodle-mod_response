@mod @mod_response @responsetype @responsetype_text
Feature: Students should see some incentive to reply
  In order to encourage me to complete an activity
  As a student
  I need to see who else has already completed an activity before I have

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
      | student4 | Student | 4 | student4@example.com |
      | student5 | Student | 5 | student5@example.com |
      | student6 | Student | 6 | student6@example.com |
      | student7 | Student | 7 | student6@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
      | student5 | C1 | student |
      | student6 | C1 | student |
      | student7 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G2 |
      | student3 | G1 |

  @javascript
  Scenario: Showing students nothing about completion status.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions before response? | Show nothing |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should not see "completed this activity"
    # This phrasing covers both alternate forms of presentation.

  @javascript
  Scenario: Showing students the number of other completions.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions before response? | Show the number of completions - all responders |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "1 person has completed this activity"

  @javascript
  Scenario: Showing students other completions (but not a number).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions before response? | Show full completions - all responders |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "has completed this activity"
    And I should not see "1 person has completed this activity"

  @javascript
  Scenario: Showing students the number of other completions when there is more than the minimum number of people.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions before response? | Show full completions - all responders |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Answer 1."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Answer 2."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Answer 3."
    And I press "Submit"
    And I log out
    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Answer 4."
    And I press "Submit"
    And I log out
    And I log in as "student5"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Answer 5."
    And I press "Submit"
    And I log out
    And I log in as "student6"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Answer 6."
    And I press "Submit"
    And I log out
    And I log in as "student7"
    And I am on "Course 1" course homepage
    Then I should see "have completed this activity"
    And I should see "+1 other"
    # Each of the user portraits has the student's name as a title.
    # This is unfortunately messy.
    And "img[title='Student 6']" "css_element" in the "div.display-completion div.others" "css_element" should not be visible
    And I click on "+1 other" "button"
    And "img[title='Student 6']" "css_element" in the "div.display-completion div.others" "css_element" should be visible
    And I click on "Show less..." "button"
    And "img[title='Student 6']" "css_element" in the "div.display-completion div.others" "css_element" should not be visible

  @javascript
  Scenario: Showing students other completions in their group only.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions before response? | Show the number of completions - group members |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "It's lovely."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    # student3 is in the same group as student1.
    And I am on "Course 1" course homepage
    Then I should see "1 person has completed this activity"
    And I should not see "have completed this activity"

  @javascript
  Scenario: Showing students other completions in their group only (but not a number).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions before response? | Show full completions - group members |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "It's lovely."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    # student3 is in the same group as student1.
    And I am on "Course 1" course homepage
    Then I should see "has completed this activity"
    And I should not see "have completed this activity"
    And I should not see "1 person has completed this activity"
