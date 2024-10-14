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
 * Define the general structure of how to restore a response activity.
 *
 * @package     mod_response
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one response activity.
 *
 * @package     mod_response
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_response_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the overall structure of the response data for the restore system.
     *
     * @return array An array of restore_path_elements to restore
     */
    protected function define_structure() {
        $paths = [];

        // Setting out the structure for responses in general.
        $userinfo = $this->get_setting_value('userinfo');

        $response = new restore_path_element('response', '/activity/response');

        $this->add_subplugin_structure('responsetype', $response);
        $paths[] = $response;

        if ($userinfo) {
            // This table contains data on completions of activity regardless of response type.
            $user = new restore_path_element('response_user', '/activity/response/response_user');
            $paths[] = $user;
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Store the data from the /activity/response path in the XML into the DB.
     *
     * @param array $data The items from /activity/response in the backup XML.
     */
    protected function process_response($data) {
        global $DB;

        // This inserts the principle data for a response activity instance.
        $new = new stdClass();
        $new->course = $this->get_courseid();
        $new->timemodified = $this->apply_date_offset($data['timemodified']);

        $new->name = $data['name'];
        $new->intro = $data['intro'];
        $new->introformat = $data['introformat'];
        $new->content = $data['content'];
        $new->contentformat = $data['contentformat'];
        $new->responsetype = $data['responsetype'];
        $new->responsedisplay = $data['responsedisplay'];
        $new->viewownpagedescription = $data['viewownpagedescription'];
        $new->caption = $data['caption'];
        $new->question = $data['question'];

        $new->displaycompletion = $data['displaycompletion'];
        $new->displaypeerresults = $data['displaypeerresults'];
        $new->requiresubmission = $data['requiresubmission'];

        $newitemid = $DB->insert_record('response', $new);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Store the data from /activity/response/response_user into the DB.
     *
     * @param array $data The generic items for users' responses.
     */
    protected function process_response_user($data) {

        global $DB;

        // If a user has started/finished an activity, add the record for it.
        $new = new stdClass();
        $new->userid = $this->get_mappingid('user', $data['userid']);
        $new->response = $this->get_new_parentid('response');
        $new->response_identifier = 0;
        $new->timecreated = $this->apply_date_offset($data['timecreated']);
        $new->timemodified = $this->apply_date_offset($data['timemodified']);
        $new->timecompleted = $this->apply_date_offset($data['timecompleted']);

        $DB->insert_record('response_user', $new);
    }

    /**
     * Make sure to process uploaded images/etc after a restore.
     */
    protected function after_execute() {
        // Add response related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_response', 'intro', null);
        $this->add_related_files('mod_response', 'content', null);
    }
}
