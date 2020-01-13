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
 * Container form for completion status.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response;
use mod_response\abstractform;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form required for handling showing completions in an activity.
 *
 * Even though this isn't classically a form, keeping it using a form enables
 * us to keep visual consistency with everything else that does use a form.
 * On some level this shouldn't be a form, but it enables us to preserve
 * the look and feel through core Moodle styling changes compared to things
 * that are actually forms.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class postcompletion extends abstractform {

    /**
     * Defines the items belonging to this form for validation purposes.
     */
    public function definition() {
        global $USER, $PAGE;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        $submitarea = array();

        $displaypeerresults = (int) $this->_customdata->displaypeerresults;

        if (!has_capability('mod/response:viewother', $this->_customdata->context)) {
            return;
        }

        $completions = $this->add_postcompletion_completion($USER->id, $submitarea, $this->_customdata);

        if (!empty($submitarea)) {
            $mform->addElement('hidden', 'context', $this->_customdata->context->id);
            $mform->setType('context', PARAM_INT);

            if ($displaypeerresults & RESPONSE_PEER_RESULTS_ALL) {
                $submitarea[] = &$mform->createElement('submit', 'response' . $this->_customdata->context->id . '_all', get_string('togglepeerresultsall', 'response'), ['data-completion' => 'all']);
            }
            if ($displaypeerresults & RESPONSE_PEER_RESULTS_GROUP && $completions->people_group) {
                $submitarea[] = &$mform->createElement('submit', 'response' . $this->_customdata->context->id . 'group', get_string('togglepeerresultsgroup', 'response'), ['data-completion' => 'group']);
            }

            $mform->addGroup($submitarea, 'buttonar' . $this->_customdata->id, '', array(' '), false);
        }
    }
}
