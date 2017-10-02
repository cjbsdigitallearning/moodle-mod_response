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

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form required for handling a poll response when there is no reflection step.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class poll_form_noreflection extends abstractform {

    /**
     * Defines the items belonging to this form for validation purposes.
     */
    public function definition() {
        global $PAGE, $USER;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $id = $this->_customdata->id;

        $choices = array();
        foreach ($this->_customdata->activity->poll_choices as $choice) {
            $choice->choice = format_string($choice->choice);
            $choices[] = $mform->createElement('radio', 'poll_choice' . $id, '', $choice->choice, $choice->responsenum);
        }
        $mform->addGroup($choices, 'poll_choices' . $id, '', array('<br />'), false);
        $mform->addRule('poll_choices' . $id, get_string('poll_answer_required', 'responsetype_poll'), 'required');

        // We came from the course view via non AJAX, so make sure we go back there.
        if (!empty($this->_customdata->in_course)) {
            $mform->addElement('hidden', 'incourse', 1);
            $mform->setType('incourse', PARAM_INT);
        }

        // Are we editing?
        if (!empty($this->_customdata->is_editing)) {
            $mform->addElement('hidden', 'editing', 1);
            $mform->setType('editing', PARAM_INT);
        }

        $submitarea = array();
        $this->add_precomplete_completion($USER->id, $submitarea);
        $submitarea[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
        $mform->addGroup($submitarea, 'buttonar' . $this->_customdata->id, '', array(' '), false);
    }
}
