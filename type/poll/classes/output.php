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
 * Creates a renderer for creation of an activity (i.e. user completion of response).
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output extends abstractoutput implements renderable, templatable {
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
        global $USER, $OUTPUT;

        $data = new stdClass();

        // Whatever we're exporting, we want the title and question. (And support multilang by default).
        $data->heading = format_string($this->data->name);
        $data->question = format_string($this->data->question);
        $data->fullpage = !empty($this->data->fullpage);
        $data->response_id = $this->data->activity->response;
        $data->contextid = !empty($this->data->contextid) ? $this->data->contextid : false;
        $data->viewownpagedescription = !empty($this->data->viewownpagedescription);

        // If we are rendering for an individual course module, we also want the course module object, with intro (description).
        if (isset($this->data->cm)) {
            $data->cm = $this->data->cm;
            $data->cm->intro = $this->data->intro;
            $data->cm->introformat = $this->data->introformat;

            // Export the description only if settings are for 'full page' and 'view description'.
            if ($data->fullpage && $data->viewownpagedescription) {
                $data->description = format_module_intro('response', $data->cm, $data->cm->id, false);
            } else {
                $data->description = '';
            }
        }

        if (empty($this->data->viewing_id)) {
            $this->data->viewing_id = $USER->id;
            $this->data->viewing_own = true;
        }
        $data->viewing_id = $this->data->viewing_id;
        $data->viewing_own = $this->data->viewing_own;

        $data->can_see_all = !empty($this->data->can_see_all);
        $data->viewall_url = !empty($this->data->viewall_url) ? $this->data->viewall_url : '';

        // And we need to inform the renderer which response type and template to load.
        $data->responsetype = $this->data->responsetype;
        if (!empty($this->data->form)) {
            // Showing the form, so render the form and select that template.
            $data->form = $this->data->form->render();
            $data->template = 'responselayout';
        } else {
            // Showing what the user selected.
            $data->template = 'showsubmission';
            $data->fullpage = !empty($this->data->fullpage);
            $data->summary_url = new moodle_url('/mod/response/index.php', ['id' => $this->data->course]);

            if (!empty($this->data->response->profile_picture)) {
                $data->profile_picture = $this->data->response->profile_picture;
                $data->profile_name = $this->data->response->first_name;
            } else {
                $data->profile_picture = $OUTPUT->user_picture($USER, ['size' => '50', 'class' => 'profilepicture']);
                $data->profile_name = ''; // Not needed.
            }

            $userchoice = $this->data->user_responses[$this->data->viewing_id]->choice;
            $data->user_choice = format_string($this->data->activity->poll_choices[$userchoice]->choice);
            $data->user_response = helper::clean_text($this->data->user_responses[$this->data->viewing_id]->reflection_text);

            // This wasn't a template helper until Moodle 3.2...
            $dateformat = get_string('strftimedatetimeshort', 'langconfig');
            $timemodified = $this->data->user_responses[$this->data->viewing_id]->timemodified;
            // We want the date in dd/mm/yy hh:mm format without stripping leading 0s.
            $data->user_response_time = userdate($timemodified, $dateformat, 99, false, false);

            $data->can_delete = !empty($this->data->can_delete);
            $data->delete_url = !empty($this->data->delete_url) ? $this->data->delete_url : '';

            $data->can_edit = !empty($this->data->can_edit);
            $data->edit_url = !empty($this->data->edit_url) ? $this->data->edit_url : '';

            // We also want to handle the completion stuff.
            $data->postcompletion = '';
            if (!empty($this->data->displaypeerresults) && !empty($this->data->postcompletion)) {
                $data->postcompletion = $this->data->postcompletion->render();
            }

            // There may be some stuff to display aggregate-wise.
            $aggregate = false;
            if (!empty($this->data->aggregate)) {
                $aggregate = new stdClass();
                foreach (['group', 'all'] as $set) {
                    if (empty($this->data->aggregate->$set)) {
                        continue;
                    }
                    $aggregate->$set = new stdClass();
                    $aggregate->$set->title = get_string('aggregate_title_' . $set, 'responsetype_poll');
                    $aggregate->$set->labels = [];
                    $aggregate->$set->data = [];
                    foreach ($this->data->activity->poll_choices as $choicenum => $choice) {
                        $aggregate->$set->labels[] = $choice->choice;
                        $amount = 0;
                        if (!empty($this->data->aggregate->{$set}[$choicenum])) {
                            $amount = $this->data->aggregate->{$set}[$choicenum];
                        }
                        $aggregate->$set->data[] = $amount;
                    }

                    if (array_sum($aggregate->$set->data) == 0) {
                        unset($aggregate->$set);
                    }
                }
            }
            $data->aggregate = json_encode($aggregate);
            $data->colours = $this->stringify_chart_colorset();
        }
        return $data;
    }
}
