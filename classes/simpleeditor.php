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
 * Workaround class to fix issues in Moodle, see MDL-59904.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response;
use MoodleQuickForm_editor;
use context_course;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Dummy class to provide an option not defined by default in the editor, required for the plugin.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simpleeditor extends MoodleQuickForm_editor {

    /**
     * Constructor
     *
     * @param string $elementname (optional) name of the editor
     * @param string $elementlabel (optional) editor label
     * @param array $attributes (optional) Either a typical HTML attribute string
     *              or an associative array
     * @param array $options set of options to initalize filepicker
     */
    public function __construct($elementname = null, $elementlabel = null, $attributes = null, $options = null) {
        global $PAGE;

        $this->_options['atto:toolbar'] = '';
        $this->_options['context'] = context_course::instance($PAGE->course->id);
        $options = $this->_options;
        parent::__construct($elementname, $elementlabel, $attributes, $options);
    }

    // @codingStandardsIgnoreStart
    /**
     * Gets the editor HTML for rendering to forms.
     *
     * This version wraps around Moodle's standard and essentially forces TinyMCE
     * to be treated as disabled while setting up the editor instance.
     *
     * It also reinstates the default state afterwards.
     *
     * Unfortunately the name of the function can't conform to Moodle standards
     * as it extends a core Moodle function and as such can't be renamed.
     *
     * @return string Editor HTML.
     */
    public function toHtml() {
        global $CFG;

        // This is such a dirty, dirty hack. Save the present configuration...
        $systemconfig = $CFG->texteditors;
        // Force it to be what we want - and cover for people who might need plain for a11y.
        $CFG->texteditors = 'atto,textarea';
        // Render the widget.
        $html = parent::toHtml();
        // Clean up and return.
        $CFG->texteditors = $systemconfig;
        return $html;
    }
    // @codingStandardsIgnoreLine
}