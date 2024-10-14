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
 * Upgrade code for responsetype_text
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   responsetype_text
 * @copyright 2020 Catalyst IT Europe Ltd.
 * @author 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @param int $oldversion
 * @return bool
 * */
function xmldb_responsetype_text_upgrade($oldversion) {
    global $CFG, $DB;

    $dbmanager = $DB->get_manager();

    if ($oldversion < 2020090302) {

        $table = new xmldb_table('responsetype_text');
        $editorconfig = new xmldb_field('editorconfig', XMLDB_TYPE_TEXT);

        // Add new 'editorconfig' field to 'responsetype_text' table.
        if (!$dbmanager->field_exists($table, $editorconfig)) {
            $dbmanager->add_field($table, $editorconfig);
        }

        $overrideeditorconfig = new xmldb_field('overrideeditorconfig', XMLDB_TYPE_INTEGER, 4, null, true, null, 0);

        // Add new 'overrideeditorconfig' field to 'responsetype_text' table.
        if (!$dbmanager->field_exists($table, $overrideeditorconfig)) {
            $dbmanager->add_field($table, $overrideeditorconfig);
        }

        upgrade_plugin_savepoint(true, 2020090302, 'responsetype', 'text');
    }

    if ($oldversion < 2024091200) {

        // Define field editorconfig to be dropped from responsetype_text.
        $table = new xmldb_table('responsetype_text');
        $field = new xmldb_field('editorconfig');

        // Conditionally launch drop field editorconfig.
        if ($dbmanager->field_exists($table, $field)) {
            $dbmanager->drop_field($table, $field);
        }

        $field = new xmldb_field('overrideeditorconfig');

        // Conditionally launch drop field overrideeditorconfig.
        if ($dbmanager->field_exists($table, $field)) {
            $dbmanager->drop_field($table, $field);
        }

        // Text savepoint reached.
        upgrade_plugin_savepoint(true, 2024091200, 'responsetype', 'text');
    }

    return true;
}
