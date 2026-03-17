@mod @mod_response @responsetype @responsetype_text
Feature: Students should see others' text responses
  In order to further my learning and have a broader mindset
  As a student
  I need to see the other students' answers and compare them to mine

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |

  @javascript
  Scenario: Not showing students answers of other students as configured at activity level.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions after response? | 0 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Overcast outside."
    And I press "Submit"
    Then I should see "You wrote"
    And I should see "Overcast outside."
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Showing students answers of other students as configured at activity level.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Shared after response | 1 |
      | Group mode | Visible groups |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    Then "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Overcast outside."
    And I press "Submit"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I should see "You wrote"
    And I should see "Overcast outside."
    And I click on "img[title='Student 1']" "css_element"
    And I should see "Student wrote"
    And I should see "Miserable outside."
    And I click on "img[title='Student 2']" "css_element"
    And I should see "You wrote"
    And I should see "Overcast outside."

  @javascript
  Scenario: Not showing students answers of other students because of a lack of permission.
    When the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:viewother | Prevent | student | Course | C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | Display completions after response? | 0 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable outside."
    And I press "Submit"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Overcast outside."
    And I press "Submit"
    Then I should see "You wrote"
    And I should see "Overcast outside."
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Checking that interacting with one set of answers doesn't interfere with another. (WR280798)
    When the following "users" exist:
      | username | firstname | lastname | email |
      | student3 | Student | 3 | student3@example.com |
      | student4 | Student | 4 | student4@example.com |
      | student5 | Student | 5 | student5@example.com |
      | student6 | Student | 6 | student6@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | student3 | C1 | student |
      | student4 | C1 | student |
      | student5 | C1 | student |
      | student6 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | All | 1 |
    And I press "Save and return to course"
    And I add a "response" activity to course "Course 1" section "2"
    And I set the following fields to these values:
      | Activity title | Fried breakfasts |
      | Activity question | What is your favourite part of a fried breakfast? |
      | Response type | Free text |
      | Response display | Inline - within the module section |
      | All | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Cloudy but slowly clearing."
    And I press "Submit"
    And I set the field "Your answer" to "Love some well-seasoned sausages."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Miserable and overcast."
    And I press "Submit"
    And I set the field "Your answer" to "Some properly cooked bacon."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Breezy and overcast."
    And I press "Submit"
    And I set the field "Your answer" to "Some well-cooked toast with jam."
    And I press "Submit"
    And I log out
    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Overcast with occasional breaks in the cloud."
    And I press "Submit"
    And I set the field "Your answer" to "Fried eggs, plain and simple."
    And I press "Submit"
    And I log out
    And I log in as "student5"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Patchy but the clouds are moving quickly."
    And I press "Submit"
    And I set the field "Your answer" to "I hate fried food. A nice croissant for me!"
    And I press "Submit"
    And I log out
    And I log in as "student6"
    And I am on "Course 1" course homepage
    And I set the field "Your answer" to "Clearing up, hope to get some autumn sunshine."
    And I press "Submit"
    And I set the field "Your answer" to "I like some mushrooms as part of my breakfast."
    And I press "Submit"
    And I log out
    Then I log in as "student1"
    And I am on "Course 1" course homepage
    And "+1 other" "button" in the "#section-1" "css_element" should be visible
    And "Show less..." "button" in the "#section-1" "css_element" should not be visible
    And "+1 other" "button" in the "#section-2" "css_element" should be visible
    And "Show less..." "button" in the "#section-2" "css_element" should not be visible
    # Press the first activity's button.
    And I press "+1 other"
    And "+1 other" "button" in the "#section-1" "css_element" should not be visible
    And "Show less..." "button" in the "#section-1" "css_element" should be visible
    And "+1 other" "button" in the "#section-2" "css_element" should be visible
    And "Show less..." "button" in the "#section-2" "css_element" should not be visible
    # Then the second.
    And I click on "#section-2 div.plus-x-others button.plus-x-other" "css_element"
    And "+1 other" "button" in the "#section-1" "css_element" should not be visible
    And "Show less..." "button" in the "#section-1" "css_element" should be visible
    And "+1 other" "button" in the "#section-2" "css_element" should not be visible
    And "Show less..." "button" in the "#section-2" "css_element" should be visible
    # And the first again.
    And I press "Show less..."
    And "+1 other" "button" in the "#section-1" "css_element" should be visible
    And "Show less..." "button" in the "#section-1" "css_element" should not be visible
    And "+1 other" "button" in the "#section-2" "css_element" should not be visible
    And "Show less..." "button" in the "#section-2" "css_element" should be visible
    # Now adjust who we're looking at. Right now, it's student 1's answers.
    And I should see "Cloudy but slowly clearing."
    And I should see "Love some well-seasoned sausages."
    # Now let's pick student 2 from the first section.
    And I click on "#section-1 img[title='Student 2']" "css_element"
    And I should see "Miserable and overcast."
    And I should see "Love some well-seasoned sausages."
    # And student 6 from the second as it is currently 'showing more'.
    And I click on "#section-2 img[title='Student 6']" "css_element"
    And I should see "Miserable and overcast."
    And I should see "I like some mushrooms as part of my breakfast."
    # And student 4 from the first section
    And I click on "#section-1 img[title='Student 4']" "css_element"
    And I should see "Overcast with occasional breaks in the cloud."
    And I should see "I like some mushrooms as part of my breakfast."
