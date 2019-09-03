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
 * Plugin version and other meta-data are defined here.
 *
 * @package   mod_response
 * @copyright 2019 Matt Whelan <matt.whelan@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_response_upgrade($oldversion) {
    global $CFG, $DB;

    $dbmanager = $DB->get_manager();

    if ($oldversion < 2019090301) {

        $table = new xmldb_table('response');
        $field = new xmldb_field('responsedisplay');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'responsetype');

        // Add new 'responsedisplay' field to 'response' table.
        if (!$dbmanager->field_exists($table, $field)) {
            $dbmanager->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019090301, 'mod', 'response');
    }

    return true;
}
