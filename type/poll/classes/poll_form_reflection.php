<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for the response activity - poll subplugin.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\type\poll;
use mod_response\abstractform;
use stdClass;
use mod_response\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form required for handling a poll response's reflection step.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll_form_reflection extends abstractform {

    /**
     * Defines the items belonging to this form for validation purposes.
     */
    public function definition() {
        global $PAGE, $USER;

        // We need to do some preparation of the reflection prompt to insert the chosen answer - and support multilang.
        $reflectionprompt = format_string($this->_customdata->activity->reflection_text);
        // Get the user's actual choice as an id.
        $useranswer = $this->_customdata->user_responses[$USER->id]->choice;
        // Get the user's answer as a piece of text.
        $userchoice = $this->_customdata->activity->poll_choices[$useranswer]->choice;
        // Apply multilang.
        $userchoice = format_string($userchoice);
        // And insert into the reflection prompt.
        $reflectionprompt = str_replace('{choice}', '<span class="user-choice">' . $userchoice . '</span>', $reflectionprompt);

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $id = $this->_customdata->id;

        $mform->addElement('hidden', 'poll_choice' . $id, $useranswer);
        $mform->setType('poll_choice' . $id, PARAM_INT);

        $mform->addElement('html', '<div class="reflection-prompt">' . $reflectionprompt . '</div>');
        $this->add_simple_editor('responsetype_poll_' . $this->_customdata->id);

        // Is there a word count prompt on this activity?
        // If so we need to pass the language string to the client and load our counting JS.
        if ($this->_customdata->activity->maxwords) {
            $this->add_word_count($this->_customdata->id, $this->_customdata->activity->maxwords);
        }

        // Are we editing?
        if (!empty($this->_customdata->is_editing)) {
            $mform->addElement('hidden', 'editing', 2);
            $mform->setType('editing', PARAM_INT);
        }

        $mform->addElement('hidden', 'response', $this->_customdata->id);
        $mform->setType('response', PARAM_INT);

        $submitarea = array();
        $this->add_precomplete_completion($USER->id, $submitarea);
        // Put them in less obvious order, so they get floated appropriately in the form.
        $submitarea[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
        $submitarea[] = &$mform->createElement('submit', 'back', get_string('back'));
        $mform->addGroup($submitarea, 'buttonar' . $this->_customdata->id, '', array(' '), false);
    }

    /**
     * Handles validation on this step of the form. Mostly because the rule
     * for validating content is more than just 'the field is not empty'.
     *
     * @param array $data The form data
     * @param array $files Uploaded files to the form
     * @return array $errors Errors encountered in the form
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!isset($data['back'])) {
            if (empty($data['response']) || empty($data['responsetype_poll_' . $data['response']])) {
                $errors['responsetype_poll'] = get_string('nothingwritten', 'responsetype_poll');
            }
            if (!helper::contains_content($data['responsetype_poll_' . $data['response']]['text'])) {
                $errors['responsetype_poll_' . $data['response']] = get_string('nothingwritten', 'responsetype_poll');
            }
        }

        return $errors;
    }
}
