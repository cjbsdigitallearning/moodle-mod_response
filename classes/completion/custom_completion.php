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

declare(strict_types=1);

namespace mod_response\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the response activity.
 *
 * @package mod_response
 * @copyright Catalyst IT Europe Ltd.
 * @author Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $responseid = $this->cm->instance;

        $response = $DB->get_record('response', ['id' => $responseid], strictness: MUST_EXIST);

        $status = false;
        if ($rule === 'requiresubmission') {
            $responseuser = $DB->get_record('response_user', array('response' => $response->id, 'userid' => $userid));
            if (!empty($responseuser) && !empty($responseuser->timecompleted)) {
                // If we did get a record, and it has a non-empty completion time, we must have completed this activity.
                $status = true;
            }

        }
        return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'requiresubmission',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $requiresubmission = $this->cm->customdata->customcompletionrules['requiresubmission'] ?? 0;

        return [
            'requiresubmission' => get_string('completiondetail:submission', 'response', $requiresubmission),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'requiresubmission',
        ];
    }
}
