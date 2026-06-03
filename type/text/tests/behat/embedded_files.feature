@editor @editor_atto @mod @mod_response @responsetype @responsetype_text
Feature: Images and embedded files in text responses
  In order to provide rich responses
  As a student
  I need to be able to include images in my responses and have them preserved during edits

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "permission overrides" exist:
      | capability           | permission | role    | contextlevel | reference |
      | mod/response:editown | Allow      | student | Course       | C1        |
    And the following "activities" exist:
      | activity | name        | intro | course | idnumber  | question                          | responsetype | responsedisplay | displaycompletionbefore | displaycompletionafter |
      | response | The weather | Intro | C1     | response1 | What is the weather like outside? | text         | 0               | 0                       | 0                      |

  @javascript
  Scenario: Student adds an image to their response and edits it
    Given the following "user private file" exists:
      | user     | student1                                       |
      | filepath | lib/editor/atto/tests/fixtures/moodle-logo.png |
    And I am on the "C1" "course" page logged in as "student1"
    And I should see "The weather"
    When I follow "The weather"
    And I set the field "Your answer" to "<p>Weather report</p>"
    And I select the text in the "Your answer" Atto editor
    And I click on "Insert or edit image" "button"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "moodle-logo.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "Moodle logo"
    And I press "Save image"
    And I press "Submit"
    Then "img[alt='Moodle logo']" "css_element" should exist
    When I follow "Edit response"
    Then "img[alt='Moodle logo']" "css_element" should exist in the ".editor_atto_content_wrap" "css_element"
    And the image at ".editor_atto_content_wrap img[alt='Moodle logo']" "css_element" should be identical to "lib/editor/atto/tests/fixtures/moodle-logo.png"
    And I press "Submit"
    Then the image at "img[alt='Moodle logo']" "css_element" should be identical to "lib/editor/atto/tests/fixtures/moodle-logo.png"
