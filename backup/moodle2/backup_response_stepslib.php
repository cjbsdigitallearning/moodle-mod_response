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
 * Defines all the backup steps that will be used by {@link backup_response_activity_task}
 *
 * @package     mod_response
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete response structure for backup, with file and id annotations
 *
 * @package     mod_response
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_response_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the 'response' element inside the response.xml file
     *
     * @return backup_nested_element The structure of the response activity XML.
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // The most core table.
        $response = new backup_nested_element('response', array('id'), array(
            'name', 'intro', 'introformat', 'responsetype', 'responsedisplay',
            'viewownpagedescription', 'question', 'timemodified', 'displaypeerresults',
            'displaycompletion', 'requiresubmission'
        ));
        $response->set_source_table('response', array('id' => backup::VAR_ACTIVITYID));

        // Hook up the subplugins.
        $this->add_subplugin_structure('responsetype', $response, true);

        if ($userinfo) {
            // Data exists for users that have started an activity regardless of type.
            $userfields = array('timecreated', 'timemodified', 'timecompleted');
            $userresponse = new backup_nested_element('response_user', array('userid'), $userfields);
            $userresponse->set_source_table('response_user', array('response' => backup::VAR_ACTIVITYID));
            $response->add_child($userresponse);
            $userresponse->annotate_ids('user', 'userid');
        }

        $response->annotate_files('mod_response', 'intro', null);

        return $this->prepare_activity_structure($response);
    }
}
