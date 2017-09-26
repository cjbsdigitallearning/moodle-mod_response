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
 * Rendering for viewing all response activities - text subplugin.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\type\text;
use mod_response\responsetype\abstractoutput;
use stdClass;
use renderable;
use renderer_base;
use templatable;
use mod_response\helper;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates a renderer for showing all responses to an activity.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewall extends abstractoutput implements renderable, templatable {
    /** @var object Contains all the data for a response so a user can complete it. */
    protected $data = null;

    /**
     * Provides the data for the template.
     *
     * Essentially hands everything to the subplugin because the subplugin
     * knows what it needs for its own templates.
     *
     * @param renderer_base $output The output renderer object
     * @return object $data An object containing all the template data
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        // Whatever we're exporting, we want the title and question. (And support multilang by default).
        $data->heading = format_string($this->data->name);
        $data->question = format_string($this->data->question);
        $data->all_responses = !empty($this->data->all_responses) ? $this->data->all_responses : array();

        $data->responsetype = $this->data->responsetype;

        // Mustache requires we provide it a real array.
        $data->all_responses = array_values($data->all_responses);

        // Fix up date formatting.
        $dateformat = get_string('strftimedatefullshort', 'langconfig');
        $datetimeformat = get_string('strftimedatetimeshort', 'langconfig');
        foreach ($data->all_responses as $id => $response) {
            $timestamp = $response->timecompleted;
            $data->all_responses[$id]->timecompleted_date = userdate($timestamp, $dateformat, 99, false, false);
            $data->all_responses[$id]->timecompleted_datetime = userdate($timestamp, $datetimeformat, 99, false, false);
        }

        // Link back to the activity itself.
        if ($this->data->course->format !== 'singleactivity') {
            $course = $this->data->course;
            $cm = $this->data->cm;
            $data->context_link = new moodle_url('/course/view.php', array('id' => $course->id), 'module-' . $cm->id);
        } else {
            $data->context_link = new moodle_url('/view.php', array('id' => $cm->id));
        }

        return $data;
    }
}
