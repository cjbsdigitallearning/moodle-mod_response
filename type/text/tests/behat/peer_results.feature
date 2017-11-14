@mod @mod_response @responsetype @responsetype_text
Feature: Students should see others' responses, filtered by group or not
  In order to further my learning and have a broader mindset
  As a student
  I need to see answers from my study group and compare them to everyone else

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
    And the following "groupings" exist:
      | name | course | idnumber |
      | Grouping 1 | C1 | GG1 |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1 | G1 |
      | GG1 | G2 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
      | student2 | G1 |
      | student3 | G2 |

  @javascript
  Scenario: An activity has a group restriction and only members of that group can see it.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Group" "button"
    And I set the field with xpath "//span[@class='availability-group']/select" to "Group 1"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then I should see "What is the weather like outside?"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I should not see "What is the weather like outside?"

  @javascript
  Scenario: An activity has a group restriction and should be visible outside the group - but only completable by group members.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Group" "button"
    And I set the field with xpath "//span[@class='availability-group']/select" to "Group 1"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then I should see "What is the weather like outside?"
    And the "Submit" "button" should be enabled
    And I should not see "Not available unless"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I should see "What is the weather like outside?"
    And the "Submit" "button" should be disabled
    And I should see "Not available unless: You belong to"
    And I should see "Group 1" in the "#section-1 .availabilityinfo" "css_element"

  @javascript
  Scenario: Making sure an activity's responses are visible inside a group but not outside.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Study group | 1 |
      | All | 0 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 1."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 2."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 3."
    And I press "Submit"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    Then "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion" "css_element" should be visible
    And "img[title='Student 3']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And "All" "button" should not be visible
    And "Group" "button" should be visible
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And "img[title='Student 1']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And "img[title='Student 3']" "css_element" in the "div.display-completion" "css_element" should be visible

  @javascript
  Scenario: Making sure the group toggle functions correctly between group and all.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Study group | 1 |
      | All | 1 |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 1."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 2."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 3."
    And I press "Submit"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I click on "Group" "button"
    Then ".group" "css_element" in the "div.display-completion" "css_element" should be visible
    And ".all" "css_element" in the "div.display-completion" "css_element" should not be visible
    And "img[title='Student 1']" "css_element" in the "div.display-completion .group" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion .group" "css_element" should be visible
    And "img[title='Student 3']" "css_element" in the "div.display-completion" "css_element" should not be visible
    And I click on "All" "button"
    And ".group" "css_element" in the "div.display-completion" "css_element" should not be visible
    And ".all" "css_element" in the "div.display-completion" "css_element" should be visible
    And "img[title='Student 1']" "css_element" in the "div.display-completion .all" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion .all" "css_element" should be visible
    And "img[title='Student 3']" "css_element" in the "div.display-completion" "css_element" should be visible
    And I click on "Group" "button"
    And ".group" "css_element" in the "div.display-completion" "css_element" should be visible
    And ".all" "css_element" in the "div.display-completion" "css_element" should not be visible
    And "img[title='Student 1']" "css_element" in the "div.display-completion .group" "css_element" should be visible
    And "img[title='Student 2']" "css_element" in the "div.display-completion .group" "css_element" should be visible
    And "img[title='Student 3']" "css_element" in the "div.display-completion" "css_element" should not be visible

  @javascript
  Scenario: Making sure the group toggle functions correctly in the teacher summary.
    When I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Learning Response" to section "1"
    And I set the following fields to these values:
      | Activity title | The weather |
      | Activity question | What is the weather like outside? |
      | Response type | Free text |
      | Study group | 1 |
      | All | 1 |
      | Group mode | Visible groups |
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 1."
    And I press "Submit"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 2."
    And I press "Submit"
    And I log out
    And I log in as "student3"
    And I follow "Course 1"
    And I set the field "Your answer" to "Answer 3."
    And I press "Submit"
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I follow "View all responses"
    And I select "Group 1" from the "Visible groups" singleselect
    And I should see "Student 1"
    And I should see "Answer 1."
    And I should see "Student 2"
    And I should see "Answer 2."
    And I should not see "Student 3"
    And I should not see "Answer 3."
    And I select "Group 2" from the "Visible groups" singleselect
    And I should not see "Student 1"
    And I should not see "Answer 1."
    And I should not see "Student 2"
    And I should not see "Answer 2."
    And I should see "Student 3"
    And I should see "Answer 3."
    And I select "All participants" from the "Visible groups" singleselect
    Then I should see "Student 1"
    And I should see "Answer 1."
    And I should see "Student 2"
    And I should see "Answer 2."
    And I should see "Student 3"
    And I should see "Answer 3."
