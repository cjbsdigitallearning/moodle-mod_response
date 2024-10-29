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
 * Defines the complete workshop structure for backup, with file and id annotations.
 *
 * @package     responsetype_text
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_responsetype_text_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to response element.
     *
     * @return backup_nested_element
     */
    protected function define_response_subplugin_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Set up the values we actually want to save for a given text response.
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());

        $settings = new backup_nested_element('responsetype_text_settings', null,
                                              ['maxwords']);

        $settings->set_source_table('responsetype_text', ['response' => backup::VAR_ACTIVITYID]);

        $subplugin->add_child($wrapper);
        $wrapper->add_child($settings);

        if ($userinfo) {
            // Set up how to save the answers given by students.
            $userfields = ['userid', 'timesubmitted', 'response_text'];
            $answers = new backup_nested_element('responsetype_text_answers', ['id'], $userfields);
            $answers->set_source_table('responsetype_text_user', ['response' => backup::VAR_ACTIVITYID]);
            $answers->annotate_ids('user', 'userid');
            $wrapper->add_child($answers);

            $answers->annotate_files('responsetype_text_user',
                                     'response_text',
                                     'id');
        }

        return $subplugin;
    }
}
