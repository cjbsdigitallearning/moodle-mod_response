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

namespace mod_response\type\poll;
use mod_response\abstractform;

/**
 * Defines the form required for handling a poll response when there is a reflection step.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll_form_choicesbeforereflection extends abstractform {

    /**
     * Defines the items belonging to this form for validation purposes.
     */
    public function definition() {
        global $PAGE, $USER;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $id = $this->_customdata->id;
        $choices = [];
        foreach ($this->_customdata->activity->poll_choices as $choice) {
            $choice->choice = format_string($choice->choice);
            $choices[] = $mform->createElement('radio', 'poll_choice' . $id, '', $choice->choice, $choice->responsenum);
        }
        $mform->addGroup($choices, 'poll_choices' . $id, '', ['<br />'], false);
        $mform->addRule('poll_choices' . $id, get_string('poll_choice_required', 'responsetype_poll'), 'required');
        if (!empty($this->_customdata->user_responses[$USER->id])) {
            $userchoice = $this->_customdata->user_responses[$USER->id]->choice;
            $mform->setDefault('poll_choice' . $id, $userchoice);

            // We need to preserve the original reflection step.
            $userresponse = $this->_customdata->user_responses[$USER->id];

            $fieldprefix = 'responsetype_poll_' . $id;

            if (!empty($this->_customdata->is_editing)) {
                $mform->addElement('hidden', $fieldprefix . '[text]', $userresponse->reflection_text);
                $mform->setType($fieldprefix . '[text]', PARAM_RAW);
                $mform->addElement('hidden', $fieldprefix . '[format]', FORMAT_HTML);
                $mform->setType($fieldprefix . '[format]', PARAM_RAW);
            }
        }

        if (!empty($this->_customdata->going_back)) {
            $mform->addElement('hidden', 'forward', 1);
            $mform->setType('forward', PARAM_INT);
        }

        // Are we editing?
        if (!empty($this->_customdata->is_editing)) {
            $mform->addElement('hidden', 'editing', 1);
            $mform->setType('editing', PARAM_INT);
        }

        $submitarea = [];
        $this->add_precomplete_completion($USER->id, $submitarea);
        $submitarea[] = &$mform->createElement('submit', 'submitbutton', get_string('next'));
        $mform->addGroup($submitarea, 'buttonar' . $this->_customdata->id, '', [' '], false);
    }
}
