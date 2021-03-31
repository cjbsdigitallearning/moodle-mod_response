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
 * Rendering for the summary of response activity - text subplugin.
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
use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates a renderer for summary of a course's activitities.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summaryoutput extends abstractoutput implements renderable, templatable {
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
        global $OUTPUT;

        $data = new stdClass();

        $data->response_id = $this->data->activity->response;
        $data->activity_title = $this->data->activity_title;
        $data->activity_question = $this->data->question;
        $data->icon = $OUTPUT->render($this->data->icon);

        // If there is no userresponse then these fields won't exist.
        if (isset($this->data->response)) {
            // Export date+time and date to the template in case people want to change it.
            $data->timemodified = $this->data->response->timemodified;
            $dateformat = get_string('strftimedatefullshort', 'langconfig');
            $datetimeformat = get_string('strftimedatetimeshort', 'langconfig');
            $data->timemodified_date = userdate($data->timemodified, $dateformat, 99, false, false);
            $data->timemodified_datetime = userdate($data->timemodified, $datetimeformat, 99, false, false);

            $data->user_response = file_rewrite_pluginfile_urls(
                $this->data->response->response_text,
                'pluginfile.php',
                context_module::instance($this->data->cm_id)->id,
                'responsetype_text',
                'response_text',
                $this->data->response->response_user_id
            );

            $data->user_response = format_text($data->user_response);
        }

        $data->responsetype = $this->data->responsetype;
        $data->template = 'summaryoutput';
        if ($this->data->view_in_course) {
            $courseid = $this->data->course_id;
            $data->context_link = course_get_url($courseid, $this->data->section_id);
            if ($data->context_link) {
                $data->context_link->set_anchor('module-' . $this->data->cm_id);
            }
        } else {
            $data->context_link = new moodle_url('/mod/response/view.php', array('id' => $this->data->cm_id));
        }

        return $data;
    }
}
