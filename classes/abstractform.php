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

namespace mod_response;

use mod_response\completions;
use mod_response\display_completion;
use mod_response\simpleeditor;
use moodleform;
use stdClass;

/**
 * This class handles some of the behaviours we want for response activities.
 *
 * Making sure our forms get sensible ids and making a minimal editor available
 * are the two key items. (We want Atto instances for autosaving, without the
 * formatting options)
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstractform extends moodleform {

    /**
     * Pass through to the moodleform constructor to ensure
     * our form object has a specific, targetable ID.
     *
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param mixed $customdata if your form defintion method needs access to data such as $course
     *              $cm, etc. to construct the form definition then pass it in this array. You can
     *              use globals for somethings.
     * @param string $method if you set this to anything other than 'post' then _GET and _POST will
     *               be merged and used as incoming data to the form.
     * @param string $target target frame for form submission. You will rarely use this. Don't use
     *               it if you don't need to as the target attribute is deprecated in xhtml strict.
     * @param mixed $attributes you can pass a string of html attributes here or an array.
     * @param bool $editable
     * @param array $ajaxformdata Forms submitted via ajax, must pass their data here, instead of relying on _GET and _POST.
     */
    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null,
                                $editable=true, $ajaxformdata=null) {
        // Contrary to the documentation, the default ID for a form is simply mform#, e.g. mform1.
        // This makes sure whatever form we make has a more specific ID.
        if (!is_array($attributes)) {
            $attributes = [];
        }
        $attributes['id'] = 'mod_response_form_' . $customdata->id;
        $attributes['class'] = str_replace('\\', '_', get_class($this));

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Add an editor to the form for the user's response.
     *
     * @param string $name The name for this instance of the editor.
     * @return void
     */
    public function add_response_editor(string $name): void {
        $mform = $this->_form;
        [$course, $cm] = get_course_and_cm_from_instance($this->_customdata->id, 'response');

        $editoroptions = [
            'maxfiles' => -1,
            'maxbytes' => $course->maxbytes,
            'context' => \core\context\module::instance($cm->id),
            'enable_filemanagement' => true,
        ];
        $mform->addElement(
            'editor',
            $name,
            get_string('youranswer', 'response'),
            ['class' => 'fullwidtheditor'],
            $editoroptions,
        );
        $mform->setType($name, PARAM_RAW);
    }

    /**
     * Adds the pre-completion incentivisation logic to the form.
     *
     * This is for the pre-completion "[] [] [] [] (+5 others) have
     * completed this activity" behaviour, or similar.
     *
     * $this->_customdata should contain the activity data when
     * initialised.
     *
     * @param int $userid The user viewing the form
     * @param array $submitarea The submission area group from the form
     */
    public function add_precomplete_completion(&$submitarea) {
        $displaybefore = $this->_customdata->displaycompletionbefore;

        if ($displaybefore == display_completion::NONE->value) {
            return;
        }

        global $PAGE;

        $mform = $this->_form;
        $cm = get_coursemodule_from_instance('response', $this->_customdata->activity->response);
        $group = groups_get_activity_group($cm, true);
        $completions = completions::get_completions_by_groupmode($cm, $group);
        $number = count($completions);

        if ($number > 0) {
            if ($displaybefore == display_completion::NAME->value) {
                $renderer = $PAGE->get_renderer('mod_response');
                $displaystring = $renderer->render_precompletion($completions);
                $submitarea[] = &$mform->createElement('static', 'displaycompletion', '', $displaystring);
            } else if ($displaybefore == display_completion::NUMBER->value) {
                $display = $number == 1 ? 'completed1' : 'completedn';
                $displaystring = get_string($display, 'response', $number);
                $displaystring = '<div class="display-completion number">' . $displaystring . '</div>';
                $submitarea[] = &$mform->createElement('static', 'displaycompletion', '', $displaystring);
            }
        }
    }

    /**
     * Adds the post-completion stats display.
     *
     * This is for displaying the completion status once a given
     * activity is complete. We use the form system for consistent
     * styling purposes.
     *
     * @param array $submitarea The submission area group from the form
     * @param object $response The response object
     * @return array $completions The data about the completions being displayed
     */
    public function add_postcompletion_completion(array &$submitarea): array {
        global $PAGE;
        $mform = $this->_form;
        $cm = get_coursemodule_from_instance('response', $this->_customdata->activity->response);
        $group = groups_get_activity_group($cm, true);
        $completions = completions::get_completions_by_groupmode($cm, $group);

        if (count($completions) > 0) {
            $renderer = $PAGE->get_renderer('mod_response');
            $displaystring = $renderer->render_postcompletion($completions);
            $submitarea[] = &$mform->createElement('static', 'displaycompletion', '', $displaystring);
        }

        return $completions;
    }

    /**
     * Adds a mod_response word count widget to a given activity.
     *
     * This assumes it functions the way plugins like text makes use of it.
     * This adds form items to display the word count (so call this where
     * you want it in your form) and will also load/prepare the JavaScript.
     *
     * @param int $responseid The activity id (as per mdl_response)
     * @param int $wordcount The expected wordcount that the activity has.
     */
    public function add_word_count($responseid, $wordcount) {
        global $PAGE;

        $mform = $this->_form;

        // Get the string and pass in the activity configuration.
        $prompt = get_string('maximumwordsprompt', 'response', $wordcount);

        // Pass the string to the client and provide a container for the string after.
        $prompt = str_replace('"', '&quot;', $prompt);
        $mform->addElement('html', '<div class="maximumwordsprompt" data-message="' . $prompt . '"></div>');

        // Load our JavaScript for counting words.
        $PAGE->requires->js_call_amd('mod_response/formwordcount', 'init', ['#mod_response_form_' . $responseid]);
    }

    /**
     * Disables all the form elements.
     *
     * @param string $disablemessage If provided, inserts as the first item on the form with suitable styling.
     */
    public function disable_form($disablemessage = null) {
        // First, disable everything. Needs recursion but don't need to expose that to a wider scope.
        $disable = function ($form, $disable) {
            if (!empty($form->_elements)) {
                foreach ($form->_elements as $idx => $element) {
                    if (in_array($element->_type, ['hidden', 'html', 'static'])) {
                        continue;
                    }
                    if (!empty($element->_elements)) {
                        $element = $disable($element, $disable);
                    } else {
                        if (!empty($element->_attributes)) {
                            $element->_attributes['disabled'] = 'disabled';
                        } else {
                            $element->_attributes = [
                                'disabled' => 'disabled',
                            ];
                        }
                    }
                    $form->_elements[$idx] = $element;
                }
            }
            return $form;
        };
        $this->_form = $disable($this->_form, $disable);

        // Now we need to tackle editors. They shouldn't be in any groups.
        $editors = [];
        foreach ($this->_form->_elements as $element) {
            if ($element->_type != 'editor') {
                continue; // Not an editor.
            }
            if (!empty($element->_attributes) && !empty($element->_attributes['name'])) {
                $editors[] = $element->_attributes['name'];
            }
        }
        // We do this here because mutating an array while iterating over it isn't good.
        $editorattrs = [
            'disabled' => 'disabled',
            'style' => 'width:100%;height:200px;resize:none',
        ];
        foreach ($editors as $editorname) {
            $editor = $this->_form->getElement($editorname);
            $editorlabel = $editor->_label;
            $textarea = $this->_form->createElement('textarea', $editorname . '_disabled', $editorlabel, $editorattrs);
            $this->_form->insertElementBefore($textarea, $editorname);
            $this->_form->removeElement($editorname);
        }

        if (!empty($disablemessage)) {
            $firstitem = $this->_form->_elements[0];
            $insertbefore = '';
            if (!empty($firstitem->_attributes) && !empty($firstitem->_attributes['name'])) {
                $insertbefore = $firstitem->_attributes['name'];
            } else if (!empty($firstitem->_name)) {
                $insertbefore = $firstitem->_name;
            }
            if ($insertbefore) {
                $label = $this->_form->createElement('html', '<div class="alert alert-warning">' . $disablemessage . '</div>');
                $this->_form->insertElementBefore($label, $insertbefore);
            }
        }
    }
}
