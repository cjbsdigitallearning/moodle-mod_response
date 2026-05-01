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
use mod_response\responsetype\abstractoutput;
use stdClass;
use renderable;
use renderer_base;
use templatable;
use mod_response\helper;
use moodle_url;
use pix_icon;

/**
 * Creates a renderer for showing all responses to an activity.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewallresponses extends abstractoutput implements renderable, templatable {
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
    public function export_for_template(renderer_base $output): object {
        global $OUTPUT;

        $data = new stdClass();

        // Whatever we're exporting, we want the title and question. (And support multilang by default).
        $data->heading = format_string($this->data->name);
        $data->question = format_string($this->data->question);
        $data->user_responses = !empty($this->data->user_responses) ? $this->data->user_responses : [];
        $data->group_selector = !empty($this->data->group_selector) ? $this->data->group_selector : '';

        $data->icon = $OUTPUT->render(new pix_icon('icon', '', 'responsetype_poll'));

        $data->responsetype = $this->data->responsetype;
        // Combine the answer possibilities into the answers from users.
        foreach ($data->user_responses as $id => $response) {
            if (isset($this->data->activity->poll_choices[$response->choice])) {
                $data->user_responses[$id]->choicetext = $this->data->activity->poll_choices[$response->choice]->choice;
                $data->user_responses[$id]->reflection_text = helper::clean_text($response->reflection_text);
            } else {
                unset($data->user_responses[$id]);
            }
        }
        // Mustache requires we provide it a real array.
        $data->user_responses = array_values($data->user_responses);

        // Fix up date formatting.
        $dateformat = get_string('strftimedatefullshort', 'langconfig');
        $datetimeformat = get_string('strftimedatetimeshort', 'langconfig');
        foreach ($data->user_responses as $id => $response) {
            $timestamp = $response->timecompleted;
            $data->user_responses[$id]->timecompleted_date = userdate($timestamp, $dateformat, 99, false, false);
            $data->user_responses[$id]->timecompleted_datetime = userdate($timestamp, $datetimeformat, 99, false, false);
        }

        $data->aggregate = [];
        foreach ($this->data->activity->poll_choices as $choice) {
            $data->aggregate[$choice->responsenum] = [
                'choice' => $choice->choice,
                'count' => 0,
            ];
        }
        foreach ($data->user_responses as $response) {
            $data->aggregate[$response->choice]['count']++;
        }
        $data->aggregate = json_encode($data->aggregate);
        $data->chart_colours = $this->stringify_chart_colorset();

        // Generate the URL for the context where the activity is displayed.
        $cm = get_coursemodule_from_id('response', $this->data->coursemodule);
        $course = get_course($cm->course);
        $data->context_link = \mod_response\helper::get_context_url(
            $cm,
            $course,
            $this->data->responsedisplay
        )->out(false);

        return $data;
    }
}
