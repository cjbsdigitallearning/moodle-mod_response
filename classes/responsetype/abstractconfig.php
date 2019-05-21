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
 * Generic configuration API for response activity subplugins.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\responsetype;
use MoodleQuickForm;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the response configuration API that subplugins are expected to follow.
 *
 * Any subplugin that defines a type of response for the response activity
 * will need to load and save its own configuration. This class essentially is
 * about connecting to the master form (mod_response_mod_form), adding its own
 * form items and requirements into that form, as well as loading/saving activity
 * configuration from the admin area. All such subplugins should define a
 * configuration class that extends this one.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstractconfig {

    /**
     * Attach the form elements required for configuring
     * this response type to an existing moodleform object.
     *
     * Validation rules should not generally be applied here.
     *
     * @param MoodleQuickForm $mform Parent form object to add to.
     */
    abstract public function add_elements(MoodleQuickForm &$mform);

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
        return array();
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
    abstract public function apply_data_preprocessing(&$defaultvalues);

    /**
     * Delegation handler for subplugins to offer them definition_after_data
     * functionality.
     *
     * @param object $form The current form object in whatever state it is in.
     * @return bool Whether any changes were successfully applied.
     */
    public function apply_definition_after_data(&$form) {
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
    abstract public function add_instance($moduleinstance, $mform = null);

    /**
     * Receives the instance data from response_update_instance and allows
     * the subplugin to update data for the current activity.
     *
     * @param object $moduleinstance The module instance to be updated
     *                               which should include a response id.
     * @param object $mform          Form object if required.
     */
    abstract public function update_instance($moduleinstance, $mform = null);

    /**
     * Receives the id of an activity instance from response_delete_instance
     * and a subplugin can then delete any data that might be held.
     *
     * @param int $id Instance id to be deleted
     */
    abstract public function delete_instance($id);

    /**
     * Returns what settings might have default values, to be made available
     * to the response activity default settings page.
     *
     * @return array object An array of admin_setting* objects
     */
    public function get_default_settings() {
        return array();
    }
}
