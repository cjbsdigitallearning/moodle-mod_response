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
 * Settings for the response activity - text subplugin.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\type\text;

use admin_setting_configtext;
use editor_atto_toolbar_setting;
use context_course;
use context_module;
use core_plugin_manager;
use InvalidArgumentException;
use mod_response\helper;
use mod_response\responsetype\abstractconfig;
use MoodleQuickForm;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines everything for showing, loading and saving config for text in response activities.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configuration extends abstractconfig {

    /**
     * Attach the form elements required for configuring
     * this response type to an existing moodleform object.
     *
     * Validation rules should not generally be applied here.
     *
     * @param MoodleQuickForm $mform Parent form object to add to.
     */
    public function add_elements(MoodleQuickForm &$mform) {
        $responseconfig = get_config('responsetype_text');

        $maxwords = array();
        $maxwords[] = $mform->createElement('text', 'text_maximumwords', '', array('size' => '6', 'maxlength' => '6'));
        $maxwords[] = $mform->createElement('checkbox', 'text_maximumwords_enabled', '', get_string('enable'));
        $mform->addGroup($maxwords, 'text_maximumwords_group', get_string('maximumwords', 'response'), ' ', false);
        $mform->setType('text_maximumwords', PARAM_INT);
        $mform->disabledIf('text_maximumwords', 'text_maximumwords_enabled');
        $mform->setDefault('text_maximumwords', !empty($responseconfig->defaultwords) ? $responseconfig->defaultwords : 0);
        $mform->setDefault('text_maximumwords_enabled', !empty($responseconfig->defaultwords) ? 1 : 0);
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
        if (empty($defaultvalues['responsetype']) || $defaultvalues['responsetype'] != 'text') {
            return true;
        }
        // And we only have work to do if we're editing an existing item.
        if (empty($defaultvalues['instance'])) {
            return true;
        }

        // There's only one item we need for this type of response.
        if ($defaultvalues['instance']) {
            $response = new stdClass();
            $response->id = $defaultvalues['instance'];

            $information = helper::instance_factory('text', 'information');
            $information->load_activity($response);

            // First, the text information.
            if ($response->activity->maxwords) {
                $defaultvalues['text_maximumwords_enabled'] = 1;
                $defaultvalues['text_maximumwords'] = $response->activity->maxwords;
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
        // We need to change the form if the user doesn't have permissions.
        $cmid = $form->getElementValue('coursemodule');
        if (!empty($cm)) {
            $context = context_module::instance($cmid);
        } else {
            $context = context_course::instance($form->getElementValue('course'));
        }
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
        $errors = [];

        return $errors;
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

        // There are only two settings we care about for this.
        $newinstance = new stdClass();
        $newinstance->response = $moduleinstance->response;
        $newinstance->maxwords = 0;
        if (!empty($moduleinstance->text_maximumwords_enabled)) {
            $newinstance->maxwords = (int) $moduleinstance->text_maximumwords;
        }

        $DB->insert_record('responsetype_text', $newinstance);
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
        $response = $DB->get_record('responsetype_text', array('response' => $moduleinstance->instance));

        $updatedinstance = new stdClass();
        $updatedinstance->id = $response->id;
        $updatedinstance->response = $moduleinstance->instance;
        $updatedinstance->maxwords = 0;
        if (!empty($moduleinstance->text_maximumwords_enabled)) {
            $updatedinstance->maxwords = (int) $moduleinstance->text_maximumwords;
        }

        $DB->update_record('responsetype_text', $updatedinstance);
    }

    /**
     * Receives the id of an activity instance from response_delete_instance
     * and a subplugin can then delete any data that might be held.
     *
     * @param int $id Instance id to be deleted
     */
    public function delete_instance($id) {
        global $DB;

        $DB->delete_records('responsetype_text', array('response' => $id));

        $userresponses = $DB->get_records('responsetype_text_user', ['response' => $id]);
        $DB->delete_records('responsetype_text_user', ['response' => $id]);

        // Having gotten all the user details, delete all the attached files.
        $cm = get_coursemodule_from_instance('response', $id);
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        if (!empty($userresponses)) {
            foreach ($userresponses as $response) {
                $fs->delete_area_files($context->id, 'responsetype_text_user', 'response_text', $response->id);
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
        return array(
            new admin_setting_configtext('responsetype_text/defaultwords', get_string('maximumwords', 'response'),
                                         get_string('maximumwords_default', 'responsetype_text'), 0, PARAM_INT),
        );
    }

    /**
     * Validates the editor configuration against Atto's own configuration.
     *
     * Adapted from lib/editor/atto/adminlib.php::validate.
     *
     * @param string $config The configuration as supplied by the user.
     * @return bool True on success
     * @throws InvalidArgumentException on failure; language string for error is the exception message.
     */
    protected function validate_atto_config(string $config) : bool {

        $lines = explode("\n", $config);
        $groups = array();
        $plugins = array();

        foreach ($lines as $line) {
            if (!trim($line)) {
                continue;
            }

            $matches = array();
            if (!preg_match('/^\s*([a-z0-9]+)\s*=\s*([a-z0-9]+(\s*,\s*[a-z0-9]+)*)+\s*$/', $line, $matches)) {
                throw new InvalidArgumentException(get_string('errorcannotparseline', 'editor_atto', $line));
            }

            $group = $matches[1];
            if (isset($groups[$group])) {
                throw new InvalidArgumentException(get_string('errorgroupisusedtwice', 'editor_atto', $group));
            }
            $groups[$group] = true;

            $lineplugins = array_map('trim', explode(',', $matches[2]));
            foreach ($lineplugins as $plugin) {
                if (isset($plugins[$plugin])) {
                    throw new InvalidArgumentException(get_string('errorpluginisusedtwice', 'editor_atto', $plugin));
                } else if (!core_component::get_component_directory('atto_' . $plugin)) {
                    throw new InvalidArgumentException(get_string('errorpluginnotfound', 'editor_atto', $plugin));
                    break 2;
                }
                $plugins[$plugin] = true;
            }
        }

        // We did not find any groups or plugins.
        if (empty($groups) || empty($plugins)) {
            throw new InvalidArgumentException(get_string('errornopluginsorgroupsfound', 'editor_atto'));
        }

        return true;
    }
}
