@mod @mod_response @responsetype @responsetype_poll
Feature: Students should see others' poll responses
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
  Scenario: Not showing students answers of other students as configured at activity level (No reflection step).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
      | Display completions after response? | 0 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Raining" "radio"
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Raining"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Showing students answers of other students as configured at activity level (No reflection step).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
      | Display completions after response? | 1 |
      | Group mode | Visible groups |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Submit"
    Then "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Raining" "radio"
    And I press "Submit"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I should see "You answered"
    And I should see "Raining"
    And I click on "img[title='Student 1']" "css_element"
    And I should see "Student answered"
    And I should see "Cloudy"
    And I click on "img[title='Student 2']" "css_element"
    And I should see "You answered"
    And I should see "Raining"

  @javascript
  Scenario: Not showing students answers of other students because of a lack of permission (No reflection step).
    When the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:viewother | Prevent | student | Course | C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | No |
      | Display completions after response? | 1 |
      | Group mode | Visible groups |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Submit"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Raining" "radio"
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Raining"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Not showing students answers of other students as configured at activity level (With reflection step).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | Please elaborate on your choice {choice} |
      | Display completions after response? | 0 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "I see clouds in the sky."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Raining" "radio"
    And I press "Next"
    And I set the field "Your answer" to "It is precipitating rather profusely outside."
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Raining"
    And I should see "precipitating rather profusely"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Showing students answers of other students as configured at activity level (With reflection step).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | Please elaborate on your choice {choice} |
      | Display completions after response? | 1 |
      | Group mode | Visible groups |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "I see clouds in the sky."
    And I press "Submit"
    Then "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Raining" "radio"
    And I press "Next"
    And I set the field "Your answer" to "It is precipitating rather profusely outside."
    And I press "Submit"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I should see "You answered"
    And I should see "Raining"
    And I should see "precipitating rather profusely"
    And I click on "img[title='Student 1']" "css_element"
    And I should see "Student answered"
    And I should see "Cloudy"
    And I should see "clouds in the sky"
    And I click on "img[title='Student 2']" "css_element"
    And I should see "You answered"
    And I should see "Raining"
    And I should see "precipitating rather profusely"

  @javascript
  Scenario: Not showing students answers of other students because of a lack of permission (With reflection step).
    When the following "permission overrides" exist:
      | capability | permission | role | contextlevel | reference |
      | mod/response:viewother | Prevent | student | Course | C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "response" activity to course "Course 1" section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | Please elaborate on your choice {choice} |
      | Display completions after response? | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "I see clouds in the sky."
    And I press "Submit"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Raining" "radio"
    And I press "Next"
    And I set the field "Your answer" to "It is precipitating rather profusely outside."
    And I press "Submit"
    Then I should see "You answered"
    And I should see "Raining"
    And I should see "precipitating rather profusely"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Checking that interacting with one set of answers doesn't interfere with another.
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
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sunny |
      | Choice 2 | Cloudy |
      | Choice 3 | Raining |
      | Add a reflection step | Yes |
      | Reflection text | Please elaborate on {choice} |
      | All | 1 |
    And I press "Save and return to course"
    # Testing with just a simple poll could be ambiguous.
    # And would yield no differences if the other tests pass.
    And I add a "response" activity to course "Course 1" section "2"
    And I set the following fields to these values:
      | Activity title | Fried breakfasts |
      | Activity question | What is your favourite part of a fried breakfast? |
      | Response type | Poll |
      | Response display | Inline - within the module section |
      | Choice 1 | Sausages |
      | Choice 2 | Bacon |
      | Choice 3 | Toast |
      | Choice 4 | Mushrooms |
      | Choice 5 | Other |
      | Add a reflection step | Yes |
      | Reflection text | Please elaborate on {choice} |
      | All | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Cloudy but slowly clearing."
    And I press "Submit"
    And I click on "Sausages" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Love some well-seasoned sausages."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Miserable and overcast."
    And I press "Submit"
    And I click on "Bacon" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Some properly cooked bacon."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Breezy and overcast."
    And I press "Submit"
    And I click on "Toast" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Some well-cooked toast with jam."
    And I press "Submit"
    And I log out
    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Overcast with occasional breaks in the cloud."
    And I press "Submit"
    And I click on "Other" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Fried eggs, plain and simple."
    And I press "Submit"
    And I log out
    And I log in as "student5"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Patchy but the clouds are moving quickly."
    And I press "Submit"
    And I click on "Other" "radio"
    And I press "Next"
    And I set the field "Your answer" to "I hate fried food. A nice croissant for me!"
    And I press "Submit"
    And I log out
    And I log in as "student6"
    And I am on "Course 1" course homepage
    And I click on "Cloudy" "radio"
    And I press "Next"
    And I set the field "Your answer" to "Clearing up, hope to get some autumn sunshine."
    And I press "Submit"
    And I click on "Mushrooms" "radio"
    And I press "Next"
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
    And I click on "+1 other" "button"
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
    And I click on "Show less..." "button"
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
