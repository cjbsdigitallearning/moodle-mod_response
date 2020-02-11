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
  Scenario: Prepare creation of an activity and check the selection system works.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Response type" to "Free text"
    Then I should see "Response - Free text"
    And I should not see "Response - Poll"

  @javascript
  Scenario: Create an activity with default values (separate page view).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view.
    Then I should see "The weather"
    And I should not see "What is the weather like outside?"
    And I follow "The weather"
    # Seeing it on separate page view.
    And I should see "What is the weather like outside?"

    And I log out

  @javascript
  Scenario: Create an activity with a wordcount (separate page view).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I click on "#id_text_maximumwords_enabled" "css_element"
    And I set the field "text_maximumwords" to "42"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view.
    Then I should see "The weather"
    And I should not see "What is the weather like outside?"
    And I follow "The weather"
    # Seeing it on separate page view.
    And I should see "What is the weather like outside?"
    And I wait "2" seconds
    Then I should see "[0/42]"

    And I log out

  @javascript
  Scenario: Create an activity with a description and a wordcount (separate page view).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity description" to "Describe the weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Caption for Response" to "Thoughts on the Weather"
    And I set the field "Response type" to "Free text"
    And I click on "#id_viewownpagedescription" "css_element"
    And I click on "#id_text_maximumwords_enabled" "css_element"
    And I set the field "text_maximumwords" to "42"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view.
    Then I should see "The weather"
    And I should not see "What is the weather like outside?"
    And I should not see "Describe the weather"
    And I follow "The weather"
    # Seeing it on separate page view.
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Thoughts on the Weather"
    And I should not see "Share your thoughts"
    And I should see "Describe the weather"
    And I wait "2" seconds
    Then I should see "[0/42]"

    And I log out

  @javascript
  Scenario: Create an activity with text page content (inline view).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity Content" to "Describe the weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Caption for Response" to "Thoughts on the Weather"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view (inline).
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Describe the weather"
    And I should see "Thoughts on the Weather"
    And I should not see "Share your thoughts"

    And I log out

  @javascript @_file_upload
  Scenario: Create an activity with image page content (inline view).
    When I log in as "teacher1"
    # Upload the image
    And I follow "Manage private files..."
    And I upload "lib/editor/atto/tests/fixtures/moodle-logo.png" file to "Files" filemanager
    And I click on "Save changes" "button"
    When I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I click on "Insert or edit image" "button" in the "Content" "fieldset"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "moodle-logo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "It's the Moodle"
    # Wait for the page to "settle".
    And I wait until the page is ready
    And I click on "Save image" "button"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Caption for Response" to "Thoughts on the Weather"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view (inline).
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And "//img[contains(@src, 'moodle-logo.png')]" "xpath_element" should exist
    And I should see "Thoughts on the Weather"
    And I should not see "Share your thoughts"

    And I log out

  @javascript
  Scenario: Create an activity (inline view).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Caption for Response" to "Thoughts on the Weather"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view (inline).
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I should see "Thoughts on the Weather"
    And I should not see "Share your thoughts"
    And I log out

  @javascript
  Scenario: Create an activity with a wordcount (inline view).
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response type" to "Free text"
    And I set the field "Response display" to "Inline - within the module section"
    And I click on "#id_text_maximumwords_enabled" "css_element"
    And I set the field "text_maximumwords" to "42"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    # Seeing it on course view (inline).
    Then I should see "The weather"
    And I should see "What is the weather like outside?"
    And I wait "2" seconds
    Then I should see "[0/42]"

    And I log out
