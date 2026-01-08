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
 * Settings for the response activity.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/response/lib.php');

/**
 * Provides handling for settings when configuring an instance of a response activity.
 *
 * Also handles delegation out to subplugins to get their settings and to let
 * them having loading/saving settings as appropriate.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_response_mod_form extends moodleform_mod {

    /** @var array Contains details on all the subplugins, including instances of their objects. */
    protected $subplugins;

    /**
     * Provide the definition for the form for the activity as a whole.
     *
     * Modifies the _form definition in place. Also handles delegating
     * out to subplugins to get definitions for their respective sections.
     */
    public function definition() {
        global $COURSE, $CFG, $DB, $PAGE;

        $mform = $this->_form;
        $responseconfig = get_config('response');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Add the activity name and description.
        $mform->addElement('text', 'name', get_string('activitytitle', 'response'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements(get_string('activitydescription', 'response'));

        // Add the activity content.
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'response'));
        $mform->setExpanded('contentsection');
        $mform->addElement(
            'editor',
            'responsecontent',
            get_string('content', 'response'),
            null,
            helper::get_editor_options($this->context),
        );

        // Now split the items up so the response-specific stuff is in a response section.
        $mform->addElement('header', 'general', get_string('response', 'response'));

        // Add the caption.
        $mform->addElement(
            'text',
            'caption',
            get_string('caption', 'response'),
            ['size' => '64', 'placeholder' => get_string('shareyourthoughts', 'response')],
        );
        $mform->setType('caption', PARAM_TEXT);
        $mform->addHelpButton('caption', 'caption', 'response');

        // And add the question for the activity.
        $mform->addElement('text', 'question', get_string('activityquestion', 'response'), ['size' => '64']);
        $mform->addRule('question', null, 'required', null, 'client');
        $mform->setType('question', PARAM_TEXT);
        $mform->addHelpButton('question', 'activityquestion', 'response');

        // Completions before response settings.
        $options = helper::display_completion_options();
        $mform->addElement('select', 'displaycompletionbefore', get_string('displaycompletion', 'response'), $options);
        if (isset($responseconfig->displaycompletionbefore)) {
            $mform->setDefault('displaycompletionbefore', $responseconfig->displaycompletionbefore);
        }
        $mform->addHelpButton('displaycompletionbefore', 'displaycompletion', 'response');

        // Completions after response settings.
        $mform->addElement('selectyesno', 'displaycompletionafter', get_string('displaycompletionafter', 'response'));
        if (isset($responseconfig->displaycompletionafter)) {
            $mform->setDefault('displaycompletionafter', $responseconfig->displaycompletionafter);
        }
        $mform->addHelpButton('displaycompletionafter', 'displaycompletionafter', 'response');

        // Now the response type selector.
        $this->load_subplugins();
        $subplugins = [];
        foreach ($this->subplugins as $name => $subplugin) {
            $subplugins[$name] = $subplugin->display_name;
        }
        asort($subplugins);

        $mform->addElement('select', 'responsetype', get_string('responsetype', 'response'), $subplugins);

        // Now the display options.
        $displayoptions = ['0' => get_string('displayresponseownpage', 'response'),
                                '1' => get_string('displayresponseinline', 'response')];

        $mform->addElement('select', 'responsedisplay', get_string('responsedisplay', 'response'), $displayoptions);
        $mform->addHelpButton('responsedisplay', 'responsedisplay', 'response');

        $mform->addElement(
            'advcheckbox',
            'viewownpagedescription',
            '',
            get_string('viewownpagedescription', 'response'),
            null,
            ['0', '1'],
        );

        // Hide the viewownpagedescription checkbox if displayresponseinline selected.
        $mform->disabledIf('viewownpagedescription', 'responsedisplay', 'eq', 1);

        // Now actually add the subplugins' items.
        foreach ($this->subplugins as $name => $subplugin) {
            // First, add a group for this plugin and make it expanded.
            $mform->addElement('header', $subplugin->type, get_string('response', 'response') . ' - ' . $subplugin->display_name);
            $mform->setExpanded($subplugin->type);

            // Then add this plugin's items.
            $subplugin->instance->add_elements($mform);
        }

        // And don't forget our JS to show/hide options.
        $PAGE->requires->js('/mod/response/js/form_toggle.js');

        // Add the boilerplate options.
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Delegate out to subplugins to offer them data_preprocessing
     * as a set of options.
     *
     * The base provided plugins may or may not make use of data_preprocessing
     * but future subplugins might need the functionality in the future.
     *
     * @param mixed $defaultvalues The default values for the form
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('responsecontent');
            $defaultvalues['responsecontent']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['responsecontent']['text'] = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_response',
                'content',
                0,
                helper::get_editor_options($this->context),
                $defaultvalues['content'],
            );
            $defaultvalues['responsecontent']['itemid'] = $draftitemid;
        }

        foreach ($this->subplugins as $subplugin) {
            $subplugin->instance->apply_data_preprocessing($defaultvalues);
        }
    }

    /**
     * Delegate out to subplugins to offer them definition_after_data
     * functionality.
     *
     * The base provided plugins may or may not make use of it but future
     * subplugins might. Unlike the typical definition_after_data setup,
     * which implicitly already has access to this form object, we need to
     * explicitly pass it on to them to modify if they want.
     */
    public function definition_after_data() {
        parent::definition_after_data();

        $mform =& $this->_form;
        $responsetype =& $mform->getElement('responsetype');
        $responsetypevalue = $mform->getElementValue('responsetype');
        if (isset($responsetypevalue[0])) {
            // So we have a value of some kind.
            $responsetype->freeze();
            $responsetype->setPersistantFreeze(true);
        }

        foreach ($this->subplugins as $subplugin) {
            $subplugin->instance->apply_definition_after_data($mform);
        }
    }

    /**
     * Handles validation for the form. The core form that applies to
     * all LR activities already has validation rules, but we need to
     * delegate out to subplugins to let them validate themselves.
     *
     * @param array $data The form data that Moodle knows about
     * @param array $files Uploaded files to the form
     * @return array $errors A list of errors encountered during processing
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['responsetype']) && isset($this->subplugins[$data['responsetype']])) {
            $subplugin = $this->subplugins[$data['responsetype']];

            $pluginerrors = $subplugin->instance->apply_validation($data, $files);
            if (!empty($pluginerrors) && is_array($pluginerrors)) {
                $errors += $pluginerrors;
            }
        }

        return $errors;
    }

    /**
     * Provides the list of possible options for showing completion
     * within the activity. Kept as a separate list to declutter the
     * main form definition which is already complicated enough.
     *
     * @return array List of completion visibility options.
     */
    public function display_completion_options() {
        return [
            'full' => get_string('displaycompletionfull', 'response'),
            'number' => get_string('displaycompletionnumber', 'response'),
            'none' => get_string('displaycompletionnone', 'response'),
        ];
    }

    /**
     * Retrieves a list of subplugins and creates instances of each.
     */
    public function load_subplugins() {
        // Get the subplugins and then proceed to instantiate them.
        $this->subplugins = helper::get_type_subplugins();
        foreach ($this->subplugins as $name => $subplugin) {
            $this->subplugins[$name]->instance = helper::instance_factory($name, 'configuration');
        }
    }

    /**
     * Add the custom completion rules to the configuration page.
     *
     * @return array Group name attached to this activity's completion rules so it can be disabled appropriately.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $group = [];
        $group[] = $mform->createElement(
            'advcheckbox',
            'requiresubmission' . $suffix,
            null,
            get_string('requiresubmission_desc', 'response'),
            ['group' => 'requiresubmissiongroup'],
        );

        $mform->addGroup($group, 'requiresubmissiongroup' . $suffix, get_string('requiresubmission', 'response'), ' &nbsp; ', false);
        $mform->addHelpButton('requiresubmissiongroup' . $suffix, 'requiresubmission', 'response');

        return ['requiresubmissiongroup' . $suffix];
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        return !empty($data['requiresubmission' . $suffix]);
    }
}
