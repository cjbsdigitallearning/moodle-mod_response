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
 * Upgrade instructions for historical versions of mod_response to current functionality.
 *
 * @param string|int $oldversion The currently installed version of the plugin
 * @return bool True on success
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod_response
 * @author 2019 Matt Whelan <matt.whelan@catalyst-eu.net>
 * @copyright 2019 Catalyst IT Europe Ltd.
 */
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

    if ($oldversion < 2019120200) {
        // This is to fix instances that were broken during restoration with an invalid course id.
        $sql = "
            SELECT r.id, cm.course
              FROM {response} r
              JOIN {course_modules} cm ON (r.id = cm.instance)
              JOIN {modules} m ON (cm.module = m.id)
             WHERE m.name = ?
               AND r.course = ?";
        $params = ['response', 0];

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            // Conveniently this is already in the right format to fix it.
            $DB->update_record('response', $record);
        }

        upgrade_plugin_savepoint(true, 2019120200, 'mod', 'response');
    }

    if ($oldversion < 20200020400) {

        $table = new xmldb_table('response');
        $content = new xmldb_field('content', XMLDB_TYPE_TEXT);
        $contentformat = new xmldb_field('contentformat', XMLDB_TYPE_INTEGER, 4, null, true, null, 0);

        // Add new 'content' field to 'response' table.
        if (!$dbmanager->field_exists($table, $content)) {
            $dbmanager->add_field($table, $content);
        }

        // Add new 'contentformat' field to 'response' table.
        if (!$dbmanager->field_exists($table, $contentformat)) {
            $dbmanager->add_field($table, $contentformat);
        }

        upgrade_plugin_savepoint(true, 20200020400, 'mod', 'response');
    }

    if ($oldversion < 20200020401) {

        $table = new xmldb_table('response');
        $contentformat = new xmldb_field('contentformat', XMLDB_TYPE_INTEGER, 4, null, true, null, 1);

        // Change the default value to 1 to ensure we always get WYSIWYG.
        if ($dbmanager->field_exists($table, $contentformat)) {
            $dbmanager->change_field_default($table, $contentformat);
        }

        upgrade_plugin_savepoint(true, 20200020401, 'mod', 'response');
    }

    if ($oldversion < 20250131000) {

        $sql = "SELECT cm.*
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name  = 'response'
                 WHERE cm.completionexpected IS NOT NULL";

        if ($coursemodules = $DB->get_records_sql($sql)) {
            foreach ($coursemodules as $cm) {
                $completionexpected = !empty($cm->completionexpected) ? $cm->completionexpected : null;
                \core_completion\api::update_completion_date_event(
                    $cm->id,
                    'response',
                    $cm->instance,
                    $completionexpected,
                );
            }
        }

        upgrade_plugin_savepoint(true, 20250131000, 'mod', 'response');
    }

    if ($oldversion < 20250522000) {
        // Add 'displaycompletionbefore' and 'displaycompletionafter' fields.
        $table = new xmldb_table('response');
        $displaycompletionbeforefield = new xmldb_field(
            'displaycompletionbefore',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'timemodified'
        );
        $displaycompletionafterfield = new xmldb_field(
            'displaycompletionafter',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            0,
            'displaycompletionbefore'
        );

        if (!$dbmanager->field_exists($table, $displaycompletionbeforefield)) {
            $dbmanager->add_field($table, $displaycompletionbeforefield);
        }
        if (!$dbmanager->field_exists($table, $displaycompletionafterfield)) {
            $dbmanager->add_field($table, $displaycompletionafterfield);
        }

        // Populate new 'displaycompletionbefore' fields with converted values.
        $oldnewvalues = [
            [RESPONSE_DISPLAY_COMPLETIONS_NONE, ['none']],
            [RESPONSE_DISPLAY_COMPLETIONS_NUMBER, ['number', 'numbergrp']],
            [RESPONSE_DISPLAY_COMPLETIONS_NAME, ['full', 'fullgrp']],
        ];
        foreach ($oldnewvalues as $oldnewvalue) {
            [$newvalue, $oldvalues] = $oldnewvalue;
            [$insql, $inparams] = $DB->get_in_or_equal($oldvalues);
            $sql = "UPDATE {response}
                       SET displaycompletionbefore = ?
                     WHERE displaycompletion $insql";
            $DB->execute($sql, [$newvalue, $newvalue, ...$inparams]);
        }

        // Populate new 'displaycompletionafter' fields with converted values.
        $sql = "UPDATE {response}
                   SET displaycompletionafter = displaypeerresults";
        $DB->execute($sql);

        $sql = "UPDATE {response}
                   SET displaycompletionafter = 1
                 WHERE displaypeerresults = 2";
        $DB->execute($sql);

        // Delete 'displaycompletion' and 'displaypeerresults' column.
        $fields = ['displaycompletion', 'displaypeerresults'];
        foreach ($fields as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbmanager->field_exists($table, $field)) {
                $dbmanager->drop_field($table, $field);
            }
        }

        // Convert and set new configs, unset old configs.
        $config = get_config('mod_response');

        foreach ($oldnewvalues as $oldnewvalue) {
            [$newvalue, $oldvalues] = $oldnewvalue;
            if (in_array($config->displaycompletion, $oldvalues)) {
                set_config('displaycompletionbefore', $newvalue, 'mod_response');
                break;
            }
        }

        set_config('displaycompletionafter', (int) $config->displaypeerresults == 0 ? 0 : 1, 'mod_response');

        unset_config('displaycompletion', 'mod_response');
        unset_config('displaypeerresults', 'mod_response');

        upgrade_plugin_savepoint(true, 20250522000, 'mod', 'response');
    }

    return true;
}
