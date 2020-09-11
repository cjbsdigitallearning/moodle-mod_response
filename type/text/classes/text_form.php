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
use context_module;

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

        if (!empty($this->_customdata->activity->overrideeditorconfig)) {
            $toolbar = $this->_customdata->activity->editorconfig;
        } else {
            $toolbar = get_config('responsetype_text', 'editorconfig');
        }

        $this->add_simple_editor('responsetype_text_' . $this->_customdata->id, get_string('youranswer', 'response'), $toolbar);

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

        // Are we editing?
        if (!empty($this->_customdata->is_editing)) {
            $mform->addElement('hidden', 'editing', $this->_customdata->is_editing);
            $mform->setType('editing', PARAM_INT);
        }

        $submitarea = array();

        $this->add_precomplete_completion($USER->id, $submitarea);

        $submitarea[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
        $mform->addGroup($submitarea, 'buttonar' . $this->_customdata->id, '', array(' '), false);
    }

    /**
     * Process the form's data to include plugin files.
     *
     * If editing a response, we need to process the HTML for Atto to include the proper references
     * to files.
     *
     * Not typehinted due to inheritance.
     *
     * @param array $defaultvalues The values being submitted for the form.
     * @return void
     */
    public function set_data($defaultvalues) {

        // If this is an edit form, we have to edit the existing stuff.
        if (!empty($this->_customdata->user_responses)) {
            $existinganswer = reset($this->_customdata->user_responses);

            $draftitemid = file_get_submitted_draft_itemid('responsetype_text_' . $this->_customdata->id);
            $cm = get_coursemodule_from_instance('response', $this->_customdata->id);
            $context = context_module::instance($cm->id);

            $element = 'responsetype_text_' . $this->_customdata->id;
            $defaultvalues[$element]['format'] = FORMAT_HTML;
            $defaultvalues[$element]['text'] = file_prepare_draft_area($draftitemid, $context->id, 'responsetype_text',
                'response_text', $existinganswer->response_user_id, helper::get_editor_options($context),
                $defaultvalues[$element]['text']);
            $defaultvalues[$element]['itemid'] = $draftitemid;
        }

        parent::set_data($defaultvalues);
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
