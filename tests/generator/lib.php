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
 * Privacy class for requesting user data.
 *
 * @package   mod_response
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_response_generator extends testing_module_generator {

    /**
     * Create an instance of the response activity in the databae for PHPUnit.
     *
     * Types not hinted due to inheritance.
     *
     * @param array|stdClass $record The data for creating the activity, as if submitted from the module creation form
     * @param array $options An array of options passed in that may affect creation of the module.
     * @return stdClass The entry from the course module's own table (mdl_response) of the created record.
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object) (array) $record;

        if (!isset($record->responsedisplay)) {
            $record->responsedisplay = 0;
        }
        if (!isset($record->viewownpagedescription)) {
            $record->viewownpagedescription = 0;
        }
        if (!isset($record->caption)) {
            $record->caption = get_string('shareyourthoughts', 'response');
        }
        if (!isset($record->responsecontent)) {
            $record->responsecontent = ['text' => '', 'format' => FORMAT_HTML];
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->displaycompletion)) {
            $record->displaycompletion = 'full';
        }

        return parent::create_instance($record, (array) $options);
    }
}
