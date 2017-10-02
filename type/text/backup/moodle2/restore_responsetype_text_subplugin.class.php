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
 * Defines the steps necessary to restore a text response.
 *
 * @package     responsetype_text
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore a text response.
 *
 * @package     responsetype_text
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_responsetype_text_subplugin extends restore_subplugin {

    /**
     * Defines the overall structure of the response data for text responses.
     *
     * @return array An array of restore_path_elements to restore
     */
    protected function define_response_subplugin_structure() {
        $paths = array();

        $userinfo = $this->get_setting_value('userinfo');

        $element = $this->get_namefor('settings');
        $elementpath = $this->get_pathfor('/responsetype_text_settings');

        $paths[] = new restore_path_element($element, $elementpath);

        if ($userinfo) {
            $element = $this->get_namefor('answers');
            $elementpath = $this->get_pathfor('/responsetype_text_answers');
            $paths[] = new restore_path_element($element, $elementpath);
        }
        return $paths;
    }

    /**
     * Store the general settings from the XML into the DB.
     *
     * @param array $data The items from /activity/response in the backup XML.
     */
    public function process_responsetype_text_settings($data) {
        global $DB;

        $data = (object) $data;
        $data->response = $this->get_new_parentid('response');

        $DB->insert_record('responsetype_text', $data);
    }

    /**
     * Store the data user's answers from the XML into the DB.
     *
     * @param array $data The items from /activity/response in the backup XML.
     */
    public function process_responsetype_text_answers($data) {

        global $DB;

        $new = new stdClass();
        $new->response = $this->get_new_parentid('response');
        $new->userid = $this->get_mappingid('user', $data['userid']);
        $new->timesubmitted = $this->apply_date_offset($data['timesubmitted']);
        $new->response_text = $data['response_text'];

        $DB->insert_record('responsetype_text_user', $new);
    }

    /**
     * Once the user's answers are inserted into the database, they need
     * to be reconciled against the general table which points to the most
     * recent answer given by a student. This reconciles all of them for a
     * single activity instance, at the end of the response being restored.
     */
    public function after_execute_response() {
        global $DB;

        // We need to update all the response_identifier columns.
        // Since we don't know what order we get the data in, let's recalculate them all now.
        $values = array();

        $parentid = $this->get_new_parentid('response');
        $result = $DB->get_records('responsetype_text_user', array('response' => $parentid));
        foreach ($result as $responseid => $response) {
            if (!isset($values[$response->userid])) {
                // We don't have a record for this user already.
                $values[$response->userid] = array(
                    'id' => $responseid,
                    'time' => (int) $response->timesubmitted,
                );
            } else {
                if ((int) $response->timesubmitted > $values[$response->userid]['time']) {
                    // It's a newer answer.
                    $values[$response->userid] = array(
                        'id' => $responseid,
                        'time' => (int) $response->timesubmitted,
                    );
                }
            }
        }

        foreach ($values as $userid => $response) {
            $params = array($response['id'], $parentid, $userid);
            $DB->execute('UPDATE {response_user}
                             SET response_identifier = ?
                           WHERE response = ? AND userid = ?', $params);
        }
    }
}
