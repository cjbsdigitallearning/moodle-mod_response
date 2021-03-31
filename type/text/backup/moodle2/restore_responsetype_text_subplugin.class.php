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
     * @var array $responsemappings Defines a mapping of old responses to new responses and back.
     */
    protected $responsemappings = [];

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

        $newid = $DB->insert_record('responsetype_text_user', $new);

        $this->responsemappings['old'][$data['id']] = $newid;
        $this->responsemappings['new'][$newid] = $data['id'];

        $this->set_mapping('responsetype_text_user_files', $newid, $data['id'], true);

        $this->add_related_files('responsetype_text_user', 'response_text', 'response', null, $data['id']);
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

        $oldresponseid = $this->get_old_parentid('response');
        $newresponseid = $this->get_new_parentid('response');

        $result = $DB->get_records('responsetype_text_user', array('response' => $newresponseid));
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

            $oldid = $this->get_mappingid('responsetype_text_user_files', $responseid);

            // Because we're working around some limitations inside Moodle's backup system...
            // Specifically, when you have a file attached to a sub-entity of the subplugin
            // e.g. in this case response -> responsetype_text -> responsetype_text_user
            // to represent one instance of an answer a user has to a response, it can't
            // properly handle this. So we have to work around Moodle - when we store the
            // id, we tag it as responsetext_type_user so backups annotate the ids correctly
            // and then we have to work around the wrong response id (the parent id itself)
            // being used for mappings by force-replacing the response mapping and putting it
            // back afterwards. Sub-plugin backups have a variety of strange behaviours,
            // this is one of them.
            $this->set_mapping('response', $oldid, $this->responsemappings['old'][$oldid]);
            $this->add_related_files('responsetype_text_user', 'response_text', 'response', null, $oldid);
            $this->set_mapping('response', $oldresponseid, $newresponseid);
        }

        foreach ($values as $userid => $response) {
            $params = array($response['id'], $newresponseid, $userid);
            $DB->execute('UPDATE {response_user}
                             SET response_identifier = ?
                           WHERE response = ? AND userid = ?', $params);
        }
    }
}
