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
 * Settings for the response activity - text subplugin.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\type\text;
use mod_response\abstractform;
use stdClass;
use mod_response\helper;
use mod_response\completions;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form required for handling a text response.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_form extends abstractform {

    /**
     * Defines the items belonging to this form for validation purposes.
     */
    public function definition() {
        global $USER;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $this->add_simple_editor('responsetype_text_' . $this->_customdata->id);

        // Is there a word count prompt on this activity?
        // If so we need to pass the language string to the client and load our counting JS.
        if ($this->_customdata->activity->maxwords) {
            $this->add_word_count($this->_customdata->id, $this->_customdata->activity->maxwords);
        }

        $mform->addElement('hidden', 'response', $this->_customdata->id);
        $mform->setType('response', PARAM_INT);

        // We came from the course view via non AJAX, so make sure we go back there.
        if (!empty($this->_customdata->in_course)) {
            $mform->addElement('hidden', 'incourse', 1);
            $mform->setType('incourse', PARAM_INT);
        }

        $submitarea = array();

        $this->add_precomplete_completion($USER->id, $submitarea);

        $submitarea[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
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

        if (empty($data['response']) || empty($data['responsetype_text_' . $data['response']])) {
            $errors['responsetype_text'] = get_string('nothingwritten', 'responsetype_text');
        } else if (!helper::contains_content($data['responsetype_text_' . $data['response']]['text'])) {
            $errors['responsetype_text'] = get_string('nothingwritten', 'responsetype_text');
        }

        return $errors;
    }
}
