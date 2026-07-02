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
 * @package     responsetype_poll
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_responsetype_poll_subplugin extends backup_subplugin {
    /**
     * Returns the subplugin information to attach to response element.
     *
     * @return backup_nested_element
     */
    protected function define_response_subplugin_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());

        // First push the general settings for the poll to the backup.
        $instancesettings = ['reflection_step', 'reflection_text', 'maxwords'];
        $settings = new backup_nested_element('responsetype_poll_settings', null, $instancesettings);

        $settings->set_source_table('responsetype_poll', ['response' => backup::VAR_ACTIVITYID]);

        $subplugin->add_child($wrapper);
        $wrapper->add_child($settings);

        // Then push the different choices that the poll has.
        $choices = new backup_nested_element('responsetype_poll_choices', ['responsenum'], ['choice']);
        $choices->set_source_table('responsetype_poll_choice', ['response' => backup::VAR_ACTIVITYID]);
        $wrapper->add_child($choices);

        if ($userinfo) {
            // Lastly, push the user's answer if they answered already.
            $userfields = ['userid', 'timesubmitted', 'choice', 'reflection_text'];
            $answers = new backup_nested_element('responsetype_poll_answers', ['id'], $userfields);
            $answers->set_source_table('responsetype_poll_user', ['response' => backup::VAR_ACTIVITYID]);
            $answers->annotate_ids('user', 'userid');
            $wrapper->add_child($answers);
        }

        return $subplugin;
    }
}
