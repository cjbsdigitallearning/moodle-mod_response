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
 * Rendering a response activity.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\output;

use context_module;
use plugin_renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

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
     * @param object $completion The completion data
     * @param boolean $all All people or only group
     * @return string The rendered HTML
     */
    public function render_precompletion($completion, $all = true) {
        $sel = $all ? "all" : "group";
        $data = new stdClass();
        $data->people = !empty($completion->{'people_' . $sel}) ? $completion->{'people_' . $sel} : [];
        $data->number = count($data->people);

        $data->people_other = !empty($completion->{'people_' . $sel . '_other'}) ? $completion->{'people_' . $sel . '_other'} : [];
        $data->number_other = count($data->people_other);

        // We need to do a bit of language juggling that's really not nice for the template.
        $data->plus_x_other = $data->number_other == 1 ? 'other1completed' : 'otherncompleted';
        $data->plus_x_other_string = get_string($data->plus_x_other, 'response', $data->number_other);

        $totalcompletions = ($data->number + $data->number_other);
        $data->others_completed = $totalcompletions == 1 ? 'hascompletedthisactivity' : 'havecompletedthisactivity';
        $data->others_completed_string = get_string($data->others_completed, 'response');

        return parent::render_from_template('response/precompletion', $data);
    }

    /**
     * Rendering the post-completion view for a given activity
     * to show who completed it and potentially review their
     * answers.
     *
     * @param object $completion The completion data
     * @return string The rendered HTML
     */
    public function render_postcompletion($completion) {
        $data = new stdClass();
        $data->people_all = !empty($completion->people_all) ? array_values($completion->people_all) : array();
        $data->number_all = count($data->people_all);
        $data->people_group = !empty($completion->people_group) ? array_values($completion->people_group) : array();
        $data->number_group = count($data->people_group);
        $data->response_id = $completion->response_id;

        $data->number_all_other = 0;
        if ($data->number_all) {
            $data->people_all_other = !empty($completion->people_all_other) ? $completion->people_all_other : array();
            $data->number_all_other = count($data->people_all_other);
        }

        $data->number_group_other = 0;
        if ($data->number_group) {
            $data->people_group_other = !empty($completion->people_group_other) ? $completion->people_group_other : array();
            $data->number_group_other = count($data->people_group_other);
        }

        $data->plus_x_other_all = $data->number_all_other == 1 ? 'other1completed' : 'otherncompleted';
        $data->plus_x_other_string_all = get_string($data->plus_x_other_all, 'response', $data->number_all_other);

        $data->plus_x_other_group = $data->number_group_other == 1 ? 'other1completed' : 'otherncompleted';
        $data->plus_x_other_string_group = get_string($data->plus_x_other_group, 'response', $data->number_group_other);

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

        $data->summary_url = new \moodle_url('mod/response/index.php', array('id' => $rawdata->course));

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
}
