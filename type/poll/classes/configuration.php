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
use mod_response\responsetype\abstractconfig;
use mod_response\helper;
use mod_response\completions;
use MoodleQuickForm;
use stdClass;
use admin_setting_configtext;
use admin_setting_configselect;

/**
 * Defines everything for showing, loading and saving config for polls in response activities.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configuration extends abstractconfig {
    /** @var int Maximum number of choices a poll can have. */
    const RESPONSETYPE_POLL_MAXCHOICES = 5;

    /** @var bool If the activity has previously been started */
    protected $studentsbegun = false;

    /**
     * Attach the form elements required for configuring
     * this response type to an existing moodleform object.
     *
     * Validation rules should not generally be applied here.
     *
     * @param MoodleQuickForm $mform Parent form object to add to.
     */
    public function add_elements(MoodleQuickForm &$mform) {
        $responseconfig = get_config('responsetype_poll');

        // Add some choices.
        for ($i = 1; $i <= 5; $i++) {
            $string = get_string('poll_choice', 'responsetype_poll', $i);
            $mform->addElement('text', 'poll_choice' . $i, $string, ['size' => '64']);
            $mform->setType('poll_choice' . $i, PARAM_TEXT);
        }
        // Add the reflection step configuration.
        $mform->addElement('selectyesno', 'poll_reflectionstep', get_string('poll_reflectionstep', 'responsetype_poll'));
        $mform->setDefault('poll_reflectionstep', $responseconfig->poll_reflectionstep);
        $mform->addElement('text', 'poll_reflectiontext',
                get_string('poll_reflectiontext', 'responsetype_poll'), ['size' => '64']);
        $mform->setType('poll_reflectiontext', PARAM_TEXT);
        $mform->addHelpButton('poll_reflectiontext', 'poll_reflectiontext', 'responsetype_poll');
        // Disable the reflection text box if we've turned off that step.
        $mform->disabledIf('poll_reflectiontext', 'poll_reflectionstep', 'neq', 1);

        // Add the maximum words, again disabled if no reflection step.
        $maxwords = [];
        $maxwords[] = $mform->createElement('text', 'poll_maximumwords', '', ['size' => '6', 'maxlength' => '6']);
        $maxwords[] = $mform->createElement('checkbox', 'poll_maximumwords_enabled', '', get_string('enable'));
        $mform->addGroup($maxwords, 'poll_maximumwords_group', get_string('maximumwords', 'response'), ' ', false);
        $mform->setType('poll_maximumwords', PARAM_INT);
        // Maximum words gets disabled if the enable tickbox isn't ticked, or if no reflection step.
        $mform->disabledIf('poll_maximumwords', 'poll_maximumwords_enabled');
        $mform->disabledIf('poll_maximumwords', 'poll_reflectionstep', 'neq', 1);
        $mform->disabledIf('poll_maximumwords_enabled', 'poll_reflectionstep', 'neq', 1);
        $mform->setDefault('poll_maximumwords', !empty($responseconfig->defaultwords) ? $responseconfig->defaultwords : 0);
        $mform->setDefault('poll_maximumwords_enabled', !empty($responseconfig->defaultwords) ? 1 : 0);
    }

    /**
     * Similar to the main form validation method, review
     * the submitted $data and $files to verify if any
     * validation errors have occurred with this subplugin
     * form and return an array of errors if so.
     *
     * @param array $data Form elements
     * @param array $files Files if uploaded with form
     * @return array Array of field name -> error message validation messages
     */
    public function apply_validation($data, $files) {
        global $DB;

        $errors = [];

        if (!empty($data['poll_reflectionstep'])) {
            // If there is a reflection step, there is some validation to do.
            if (empty($data['poll_reflectiontext'])) {
                $errors['poll_reflectiontext'] = get_string('err_required', 'form');
            }
        }

        // We apply this here rather than in definition, so we only actually perform this step if poll was chosen.
        // But which items we need depends on whether we are creating or updating.
        if (!empty($data['instance']) && completions::total_students_have_begun($data['instance'])) {
            // We're updating, we need to add whichever were used before.
            $requiredchoices = [];
            $responsechoices = $DB->get_records('responsetype_poll_choice', ['response' => $data['instance']]);
            foreach ($responsechoices as $responsechoice) {
                $requiredchoices[] = (int) $responsechoice->responsenum;
            }
            $warning = get_string('poll_choice_required_previous', 'responsetype_poll');
        } else if (!empty($data['responsetype']) && $data['responsetype'] == 'poll') {
            // We're creating a new one, so only the first two are required... but only if we're actually doing a poll.
            $requiredchoices = [1, 2];
            $warning = get_string('poll_choice_required', 'responsetype_poll');
        }
        // Now we know which items are required, apply validation.
        foreach ($requiredchoices as $choice) {
            $field = 'poll_choice' . $choice;
            if (empty($data[$field]) || trim($data[$field]) == '') {
                $errors[$field] = $warning;
            }
        }
        return $errors;
    }

    /**
     * Delegation handler for subplugins to offer them data_preprocessing
     * as a set of options during activity creation/editing.
     *
     * More likely, though, this will be used to load data from existing
     * instances of activity subplugins for the purposes of editing.
     *
     * @param mixed $defaultvalues The default values for the form
     */
    public function apply_data_preprocessing(&$defaultvalues) {
        global $DB;

        // We only have work to do if it's this response type.
        if (empty($defaultvalues['responsetype']) || $defaultvalues['responsetype'] != 'poll') {
            return true;
        }
        // And we only have work to do if we're editing an existing item.
        if (empty($defaultvalues['instance'])) {
            return true;
        }

        if ($defaultvalues['instance']) {
            $response = new stdClass();
            $response->id = $defaultvalues['instance'];

            $information = helper::instance_factory('poll', 'information');
            $information->load_activity($response);

            // First, the poll information.
            if ($response->activity->reflection_step) {
                $defaultvalues['poll_maximumwords_enabled'] = !empty($response->activity->maxwords);
                $defaultvalues['poll_maximumwords'] = $response->activity->maxwords;
            }
            $defaultvalues['poll_reflectionstep'] = $response->activity->reflection_step;
            $defaultvalues['poll_reflectiontext'] = $response->activity->reflection_text;

            // Then the poll choices.
            foreach ($response->activity->poll_choices as $choicenum => $choice) {
                $defaultvalues['poll_choice' . $choicenum] = $choice->choice;
            }
        }

        return true;
    }

    /**
     * Delegation handler for subplugins to offer them definition_after_data
     * functionality.
     *
     * @param object $form The current form object in whatever state it is in.
     * @return bool Whether any changes were successfully applied.
     */
    public function apply_definition_after_data(&$form) {
        global $DB;

        // First, we need to get the instance we're dealing with.
        $instance = (int) $form->getElementValue('instance');

        // This is mostly about preventing users deleting choices that might already have been used somewhere.
        if (!empty($instance) && completions::total_students_have_begun($instance)) {
            // Someone has at least attempted this.
            $responsechoices = $DB->get_records('responsetype_poll_choice', ['response' => $instance]);
            $warning = get_string('poll_choice_required_previous', 'responsetype_poll');
            foreach ($responsechoices as $responsechoice) {
                $form->addRule('poll_choice' . $responsechoice->responsenum, $warning, 'required', null, 'client');
            }

            $warning = $form->createElement('static', 'poll_warning', '', get_string('poll_warning_post', 'responsetype_poll'));
        } else {
            // No-one has attempted this, only make 1-2 required.
            // But don't actually try to enforce it here, it breaks on non-poll cases...
            $warning = $form->createElement('static', 'poll_warning', '', get_string('poll_warning_pre', 'responsetype_poll'));
        }

        $form->insertElementBefore($warning, 'poll_choice1');

        return true;
    }

    /**
     * Receives the instance data from response_add_instance and allows the
     * subplugin to add data for the current activity.
     *
     * @param object $moduleinstance The module instance to be created
     *                               which should include a response id.
     * @param object $mform          Form object if required.
     */
    public function add_instance($moduleinstance, $mform = null) {
        global $DB;

        // First we need to care about the poll parent record.
        $newinstance = new stdClass();
        $newinstance->response = $moduleinstance->response;
        $newinstance->maxwords = 0;
        if (!empty($moduleinstance->poll_maximumwords_enabled)) {
            $newinstance->maxwords = (int) $moduleinstance->poll_maximumwords;
        }
        $newinstance->reflection_step = $moduleinstance->poll_reflectionstep;
        $newinstance->reflection_text = '';
        if (!empty($moduleinstance->poll_reflectiontext)) {
            $newinstance->reflection_text = (string) $moduleinstance->poll_reflectiontext;
        }

        $DB->insert_record('responsetype_poll', $newinstance);

        // Then we need to insert the poll choices.
        $rowstoinsert = [];
        $position = 1;
        for ($i = 1; $i <= self::RESPONSETYPE_POLL_MAXCHOICES; $i++) {
            if ($moduleinstance->{'poll_choice' . $i}) {
                $newrow = new stdClass();
                $newrow->response = $moduleinstance->response;
                $newrow->responsenum = $position;
                $newrow->choice = $moduleinstance->{'poll_choice' . $i};

                $rowstoinsert[] = $newrow;
                $position++;
            }
        }

        if (!empty($rowstoinsert)) {
            $DB->insert_records('responsetype_poll_choice', $rowstoinsert);
        }
    }

    /**
     * Receives the instance data from response_update_instance and allows
     * the subplugin to update data for the current activity.
     *
     * @param object $moduleinstance The module instance to be updated
     *                               which should include a response id.
     * @param object $mform          Form object if required.
     */
    public function update_instance($moduleinstance, $mform = null) {
        global $DB;

        // Before we can update, we need to get the row ID first.
        $instance = $moduleinstance->instance;
        $response = $DB->get_record('responsetype_poll', ['response' => $instance]);

        $updatedinstance = new stdClass();
        $updatedinstance->id = $response->id;
        $updatedinstance->response = $instance;
        $updatedinstance->maxwords = 0;
        if (!empty($moduleinstance->poll_maximumwords_enabled)) {
            $updatedinstance->maxwords = (int) $moduleinstance->poll_maximumwords;
        }
        $updatedinstance->reflection_step = $moduleinstance->poll_reflectionstep;
        $updatedinstance->reflection_text = $moduleinstance->poll_reflectiontext;

        $DB->update_record('responsetype_poll', $updatedinstance);

        // Now we need to update the poll choices.
        // Start by fetching the ones we already have.
        $existingchoices = [];
        $responsechoices = $DB->get_records('responsetype_poll_choice', ['response' => $instance]);
        foreach ($responsechoices as $responsechoice) {
            $existingchoices[$responsechoice->responsenum] = $responsechoice;
        }

        // Now let's step through the values and see what we are adding/updating.
        for ($i = 1; $i <= self::RESPONSETYPE_POLL_MAXCHOICES; $i++) {
            // For the current option, is there something to do?
            if (empty($moduleinstance->{'poll_choice' . $i})) {
                // Was there an option previously?
                if (!empty($existingchoices[$i])) {
                    $DB->delete_records('responsetype_poll_choice', ['response' => $instance, 'responsenum' => $i]);
                }
                continue;
            }

            // If it doesn't already exist in our DB, let's add it.
            if (empty($existingchoices[$i])) {
                $insert = new stdClass();
                $insert->response = $instance;
                $insert->responsenum = $i;
                $insert->choice = $moduleinstance->{'poll_choice' . $i};
                $DB->insert_record('responsetype_poll_choice', $insert);
                continue;
            }

            // Is it different to what we already have? Update it.
            if ($existingchoices[$i]->choice != $moduleinstance->{'poll_choice' . $i}) {
                $update = $existingchoices[$i];
                $update->choice = $moduleinstance->{'poll_choice' . $i};
                $DB->update_record('responsetype_poll_choice', $update);
                continue;
            }
        }
    }

    /**
     * Receives the id of an activity instance from response_delete_instance
     * and a subplugin can then delete any data that might be held.
     *
     * @param int $id Instance id to be deleted
     */
    public function delete_instance($id) {
        global $DB;

        $DB->delete_records('responsetype_poll', ['response' => $id]);

        $userresponses = $DB->get_records('responsetype_poll_user', ['response' => $id]);
        $DB->delete_records('responsetype_poll_choice', ['response' => $id]);

        // Having gotten all the user details, delete all the attached files.
        $cm = get_coursemodule_from_instance('response', $id);
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        if (!empty($userresponses)) {
            foreach ($userresponses as $response) {
                $fs->delete_area_files($context->id, 'responsetype_poll_user', 'response_poll', $response->id);
            }
        }
    }

    /**
     * Returns what settings might have default values, to be made available
     * to the response activity default settings page.
     *
     * @return array object An array of admin_setting* objects
     */
    public function get_default_settings() {
        return [
            new admin_setting_configtext('responsetype_poll/defaultwords', get_string('maximumwords', 'response'),
                                         get_string('maximumwords_default', 'responsetype_poll'), 0, PARAM_INT),
            new admin_setting_configselect('responsetype_poll/poll_reflectionstep',
                                         get_string('poll_reflectionstep', 'responsetype_poll'),
                                         get_string('poll_reflectionstep_explain', 'responsetype_poll'),
                                         1, [1 => get_string('yes'), 0 => get_string('no')]),
        ];
    }
}
