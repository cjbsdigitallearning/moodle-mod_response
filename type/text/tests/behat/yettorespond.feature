@mod @mod_response @responsetype @responsetype_text
Feature: Users should be able to view which text activities they are yet to respond to
  In order to for me to ensure I complete all response activities
  As a student
  I need to see in the summary which activities I have yet to respond to

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
    And I add a "response" activity to course "Course 1" section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I press "Save and return to course"
    # Second response.
    And I add a "response" activity to course "Course 1" section "2"
    And I set the field "Activity title" to "Fried breakfasts"
    And I set the field "Activity question" to "What is your favourite part of a fried breakfast?"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Own page - on a separate page"
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: A user views summary where one activity is not completed.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the first activity.
    And I set the field "Your answer" to "It is overcast outside right now."
    And I press "Submit"
    # Viewing the summary
    And I follow "View course summary"
    Then I should see "Yet to respond to"
    And I should see "Fried breakfasts" in the "//div[@class='activity-section-container'][2]" "xpath_element"
    And I should see "You have not responded yet." in the "//div[@class='activity-section-container'][2]" "xpath_element"
    And I should not see "The weather" in the "//div[@class='activity-section-container'][2]" "xpath_element"

      @javascript
  Scenario: A user views summary where all activities are completed.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the first activity.
    And I set the field "Your answer" to "It is overcast outside right now."
    And I press "Submit"
    # Completing the second activity.
    And I follow "Fried breakfasts"
    And I set the field "Your answer" to "I really like sausages out of the pork products in a fried breakfast."
    And I press "Submit"
    # Viewing the summary
    And I follow "View course summary"
    Then I should not see "Yet to respond to"
    And I should not see "You have not responded yet."

  @javascript
  Scenario: A user completes an in-line activity after navigating to it from the summary.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the other activity.
    And I follow "Fried breakfasts"
    And I set the field "Your answer" to "I really like sausages out of the pork products in a fried breakfast."
    And I press "Submit"
    # Viewing the summary
    And I follow "View course summary"
    And I should see "The weather"
    And I follow "The weather"
    And I set the field "Your answer" to "It is overcast outside right now."
    And I press "Submit"
    # Viewing the summary
    And I follow "View course summary"
    Then I should not see "Yet to respond to"
    And I should see "It is overcast outside right now."
    And I should not see "You have not responded yet."

  @javascript
  Scenario: A user completes a non in-line activity after navigating to it from the summary.
    When I log in as "student1"
    And I am on "Course 1" course homepage
    # Completing the other activity.
    And I set the field "Your answer" to "It is overcast outside right now."
    And I press "Submit"
    # Viewing the summary
    And I follow "View course summary"
    And I follow "Fried breakfasts"
    And I set the field "Your answer" to "I really like sausages out of the pork products in a fried breakfast."
    And I press "Submit"
    # Viewing the summary
    And I follow "View course summary"
    Then I should not see "Yet to respond to"
    And I should see "I really like sausages out of the pork products in a fried breakfast."
    And I should not see "You have not responded yet."