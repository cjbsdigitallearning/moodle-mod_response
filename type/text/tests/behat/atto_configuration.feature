@mod @mod_response @responsetype @responsetype_text @javascript
Feature: In a free-text activity, admins can configure which features are available in the embedded Atto instance
  In order to get relevant responses
  As a teacher
  I need to configure a response activity with useful options for students

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
    And I log in as "admin"
    And I navigate to "Plugins > Activity modules > Response" in site administration
    And I set the following fields to these values:
      | Default toolbar for free-text responses | style1 = title, bold, italic, underline, strike |
    And I press "Save changes"
    And I log out

  Scenario: An activity uses the system-wide configuration by default
    When the following "permission overrides" exist:
      | capability                                    | permission | role           | contextlevel | reference |
      | responsetype/text:editor_atto__toolbar_config | Prevent    | editingteacher | Course       | C1        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Response type" to "Free text"
    And the "Override default toolbar" "checkbox" should be disabled
    And the "Toolbar configuration" "field" should be disabled
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then ".atto_bold_button_bold" "css_element" should exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_italic_button_italic" "css_element" should exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_underline_button_underline" "css_element" should exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_strike_button_strikeThrough" "css_element" should exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_link_button" "css_element" should not exist in the ".fullwidtheditor.hastoolbar" "css_element"

  Scenario: An activity uses its own configuration
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Response" to section "1"
    And I set the field "Activity title" to "The weather"
    And I set the field "Activity question" to "What is the weather like outside?"
    And I set the field "Response display" to "Inline - within the module section"
    And I set the field "Response type" to "Free text"
    And I click on "#id_text_overrideeditorconfig" "css_element"
    And I set the field "Toolbar configuration" to "style1 = title, bold, italic"
    And I click on "#id_text_maximumwords_enabled" "css_element"
    And I set the field "text_maximumwords" to "42"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then ".atto_bold_button_bold" "css_element" should exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_italic_button_italic" "css_element" should exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_underline_button_underline" "css_element" should not exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_strike_button_strikeThrough" "css_element" should not exist in the ".fullwidtheditor.hastoolbar" "css_element"
    And ".atto_link_button" "css_element" should not exist in the ".fullwidtheditor.hastoolbar" "css_element"