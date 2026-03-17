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

namespace mod_response\output;

use core\output\comboboxsearch;
use context_module;
use plugin_renderer_base;
use stdClass;
use mod_response\helper;

/**
 * Rendering a response activity.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Rendering the empty response form so users can complete it.
     *
     * @param object $page The data for the activity.
     * @return The rendered HTML for this activity step.
     */
    public function render_output($page) {
        $data = $page->export_for_template($this);
        $component = 'responsetype_' . $data->responsetype;

        return parent::render_from_template($component . '/' . $data->template, $data);
    }

    /**
     * Rendering the summary view of an activity.
     *
     * @param object $page The data for the activity.
     * @return The rendered HTML for this activity step.
     */
    public function render_summaryoutput($page) {
        $data = $page->export_for_template($this);
        $component = 'responsetype_' . $data->responsetype;

        return parent::render_from_template($component . '/' . $data->template, $data);
    }

    /**
     * Rendering the summary view of all in a course.
     *
     * @param object $page The data for the course.
     * @return string The rendered HTML for this course's activities.
     */
    public function render_summarycourse($page) {
        $data = new stdClass();
        $data->sections = array_values($page);
        return parent::render_from_template('response/summary', $data);
    }

    /**
     * Rendering the 'all learner responses' for an activity.
     *
     * @param object $rawdata The data for the activity.
     * @return string The rendered HTML for this activity's responses.
     */
    public function render_viewall($rawdata) {
        $data = $rawdata->export_for_template($this);
        $component = 'responsetype_' . $data->responsetype;
        return parent::render_from_template($component . '/viewall', $data);
    }

    /**
     * Rendering the pre-completion view for other people who have
     * completed an activity, e.g. "[] [] (+ 2 others) have completed..."
     *
     * @param array $completions The completions
     * @return string The rendered HTML
     */
    public function render_precompletion($completions) {
        $data = new stdClass();
        $number = count($completions);

        // Get first 5 people (unless there are under 5 completions).
        if ($number <= 5) {
            $data->people = array_values($completions);
            $data->people_other = [];
            $data->number = $number;
            $data->number_other = 0;
        } else {
            $data->people = array_slice($completions, 0, 5);
            $data->people_other = array_slice($completions, 5, $number);
            $data->number = 5;
            $data->number_other = $number - 5;
        }

        // We need to do a bit of language juggling that's really not nice for the template.
        $data->plus_x_other = $data->number_other == 1 ? 'other1completed' : 'otherncompleted';
        $data->plus_x_other_string = get_string($data->plus_x_other, 'response', $data->number_other);

        $data->others_completed = $number == 1 ? 'hascompletedthisactivity' : 'havecompletedthisactivity';
        $data->others_completed_string = get_string($data->others_completed, 'response');

        return parent::render_from_template('response/precompletion', $data);
    }

    /**
     * Rendering the post-completion view for a given activity
     * to show who completed it and potentially review their
     * answers.
     *
     * @param array $completions The completion data
     * @return string The rendered HTML
     */
    public function render_postcompletion($completions) {
        $data = new stdClass();

        // Get first 5 people (unless there are under 5 completions).
        if (($number = count($completions)) <= 5) {
            $data->people = array_values($completions);
            $data->people_other = [];
            $data->number = $number;
            $data->number_other = 0;
        } else {
            $data->people = array_slice($completions, 0, 5);
            $data->people_other = array_slice($completions, 5, $number);
            $data->number = 5;
            $data->number_other = $number - 5;
        }

        $data->plus_x_other_all = $data->number == 1 ? 'other1completed' : 'otherncompleted';
        $data->plus_x_other_string_all = get_string($data->plus_x_other_all, 'response', $data->number_other);

        $data->plus_x_other_group = $data->number_other == 1 ? 'other1completed' : 'otherncompleted';
        $data->plus_x_other_string_group = get_string($data->plus_x_other_group, 'response', $data->number_other);

        return parent::render_from_template('response/postcompletion', $data);
    }

    /**
     * Render the course layout for an activity.
     *
     * @param object $rawdata The activity data, including the course module
     * @return string The rendered HTML
     */
    public function render_courseinline($rawdata) {
        $cm = $rawdata->course_module;
        $data = new stdClass();
        if (groups_get_activity_groupmode($cm)) {
            groups_get_activity_group($cm, true);
            $data->groupselector = groups_print_activity_menu($cm, $this->page->url, true);
        }
        $data->fullpage = !empty($rawdata->fullpage);
        $data->contextid = !empty($rawdata->contextid) ? $rawdata->contextid : '';
        $data->icon_url = $cm->get_icon_url();
        $data->activity_title = $cm->get_formatted_name();
        if ($rawdata->showdescription) {
            $data->description = format_module_intro('response', $cm->customdata, $cm->id, false);
        } else {
            $data->description = '';
        }
        // Render page content.
        $context = context_module::instance($cm->id);
        $content = file_rewrite_pluginfile_urls(
            $cm->customdata->content,
            'pluginfile.php',
            $context->id,
            'mod_response',
            'content',
            0
        );
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $data->contenttext = format_text($content, $cm->customdata->contentformat, $formatoptions);

        $data->question = format_string($cm->customdata->question);
        if (!empty($cm->customdata->caption)) {
            $data->caption = format_string($cm->customdata->caption);
        } else {
            $data->caption = get_string('shareyourthoughts', 'response');
        }
        $data->responsetype = $cm->customdata->responsetype;
        if (!empty($rawdata->form)) {
            $data->form = $rawdata->form->render();
        }
        if (!empty($rawdata->user_answer)) {
            $data->user_answer = $rawdata->user_answer;
        }
        $data->postcompletion = !empty($rawdata->postcompletion) ? $rawdata->postcompletion : '';

        $data->can_see_all = !empty($cm->customdata->can_see_all);
        $data->viewall_url = !empty($cm->customdata->viewall_url) ? $cm->customdata->viewall_url : '';

        $data->summary_url = new \moodle_url('mod/response/index.php', ['id' => $rawdata->course]);

        return parent::render_from_template('response/courseinline', $data);
    }

    /**
     * Render the layout for an inline view of a student's answer for an activity.
     *
     * @param object $rawdata The activity data, including the course module
     * @return string The rendered HTML
     */
    public function render_inlineoutput($rawdata) {
        $data = $rawdata->export_for_template($this);
        $component = 'responsetype_' . $data->responsetype;
        return parent::render_from_template($component . '/inlinesubmission', $data);
    }

    /**
     * Render the tertiary nav for the manage categories page.
     *
     * @param \mod_response\output\action_bar $actionbar
     * @return string The renderered template
     */
    public function render_action_bar(\mod_response\output\action_bar $actionbar): string {
        return $this->render_from_template($actionbar->get_template(), $actionbar->export_for_template($this));
    }


    /**
     * Using this initials selector means you'll have to retain the use of the templates & JS to handle form submission.
     * If a simple redirect on each selection is desired the standard user_search() within the user renderer is what you are after.
     *
     * @param object $course The course object.
     * @param context $context Our current context.
     * @param string $slug The slug for the report that called this function.
     * @return stdClass The data to output.
     */
    public function initials_selector(
        object $course,
        \context $context,
        string $slug
    ): stdClass {
        global $SESSION, $COURSE;
        // User search.
        $searchvalue = optional_param('search', null, PARAM_NOTAGS);
        $url = new \moodle_url($slug, ['id' => $context->instanceid]);
        $firstinitial = $SESSION->modresponse["filterfirstname-{$context->id}"] ?? '';
        $lastinitial  = $SESSION->modresponse["filtersurname-{$context->id}"] ?? '';

        $renderer = $this->page->get_renderer('core_user');
        $initialsbar = $renderer->partial_user_search($url, $firstinitial, $lastinitial, true);

        $currentfilter = '';
        if ($firstinitial !== '' && $lastinitial !== '') {
            $currentfilter = get_string('filterbothactive', 'mod_response', ['first' => $firstinitial, 'last' => $lastinitial]);
        } else if ($firstinitial !== '') {
            $currentfilter = get_string('filterfirstactive', 'mod_response', ['first' => $firstinitial]);
        } else if ($lastinitial !== '') {
            $currentfilter = get_string('filterlastactive', 'mod_response', ['last' => $lastinitial]);
        }

        $this->page->requires->js_call_amd('mod_response/searchwidget/initials', 'init', [$slug, $searchvalue]);

        $formdata = (object) [
            'courseid' => $COURSE->id,
            'cmid' => $context->instanceid,
            'initialsbars' => $initialsbar,
        ];
        $dropdowncontent = $this->render_from_template('mod_response/initials_dropdown_form', $formdata);

        return (object) [
             'buttoncontent' => $currentfilter !== '' ? $currentfilter : get_string('filterbyname', 'mod_response'),
             'buttonheader' => $currentfilter !== '' ? get_string('name') : null,
             'dropdowncontent' => $dropdowncontent,
        ];
    }

    /**
     * Renderer for viewallresponses view.
     *
     * @param array $userresponses List of response objects.
     * @param string $format Course format ID e.g. 'weeks' $course->format
     * @return string
     */
    public function render_view_all_users(array $userresponses, string $format): string {
        $output = '';

        if (course_format_uses_sections($format)) {
            foreach ($userresponses as $section) {
                $output .= $this->heading($section->section_title, 3, 'activity-section');
                foreach ($section->responses as $response) {
                    $output .= $this->render_viewallresponses_response_module($response);
                }
            }
        } else {
            foreach ($userresponses as $response) {
                $output .= $this->render_viewallresponses_response_module($response);
            }
        }

        return $output;
    }

    /**
     * Renderer for single response module.
     *
     * @param object $response
     * @return string
     */
    protected function render_viewallresponses_response_module(object $response): string {
        $instance = helper::instance_factory($response->responsetype, 'information');
        $instance->load_activity($response);
        $renderable = helper::instance_factory($response->responsetype, 'viewallresponses', [$response, $instance]);
        $data = $renderable->export_for_template($this);
        $component = 'responsetype_' . $data->responsetype;
        return parent::render_from_template($component . '/viewallresponses', $data);
    }
}
