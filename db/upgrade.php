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

    if ($oldversion < 2019091100) {

        $table = new xmldb_table('response');
        $responsedisplay = new xmldb_field('responsedisplay');
        $viewownpagedescription = new xmldb_field('viewownpagedescription');
        $responsedisplay->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'responsetype');
        $viewownpagedescription->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'responsedisplay');

        // Add new 'responsedisplay' field to 'response' table.
        if (!$dbmanager->field_exists($table, $responsedisplay)) {
            $dbmanager->add_field($table, $responsedisplay);
        }

        // Add new 'viewownpagedescription' field to 'response' table.
        if (!$dbmanager->field_exists($table, $viewownpagedescription)) {
            $dbmanager->add_field($table, $viewownpagedescription);
        }

        upgrade_plugin_savepoint(true, 2019091100, 'mod', 'response');
    }

    if ($oldversion < 2019091800) {

        $table = new xmldb_table('response');
        $caption = new xmldb_field('caption', XMLDB_TYPE_CHAR, '255');

        // Add new 'caption' field to 'response' table.
        if (!$dbmanager->field_exists($table, $caption)) {
            $dbmanager->add_field($table, $caption);
        }

        upgrade_plugin_savepoint(true, 2019091800, 'mod', 'response');
    }

    return true;
}
