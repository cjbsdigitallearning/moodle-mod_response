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

/**
 * Creates a renderer for viewing a user's responses inline.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inlineoutput extends abstractoutput implements renderable, templatable {
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

        $data->responsetype = $this->data->responsetype;
        $data->profile_picture = $this->data->user_wrote['picture'];
        $data->profile_name = $this->data->user_wrote['first_name'];
        $data->viewing_own = $this->data->viewing_own;

        $userchoice = $this->data->response->choice;
        $data->user_choice = $this->data->activity->poll_choices[$userchoice]->choice;

        $data->user_response = helper::clean_text($this->data->response->reflection_text);

        $dateformat = get_string('strftimedatefullshort', 'langconfig');
        $datetimeformat = get_string('strftimedatetimeshort', 'langconfig');
        $data->user_response_date = userdate($this->data->response->timecompleted, $dateformat, 99, false, false);
        $data->user_response_time = userdate($this->data->response->timecompleted, $datetimeformat, 99, false, false);

        return $data;
    }
}
