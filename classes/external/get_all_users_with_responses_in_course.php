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

namespace mod_response\external;

use core_user_external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use mod_response\helper;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot .'/user/externallib.php');

/**
 * Get users with responses in the course.
 *
 * @package    mod_response
 * @copyright  2024 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_all_users_with_responses_in_course extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters (
            [
                'courseid' => new external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
                'cmid' => new external_value(PARAM_INT, 'Course Module Id', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * Given a course ID find the enrolled users within and map some fields to the returned array of user objects.
     *
     * @param int $courseid
     * @return array Users to pass back to the calling widget.
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function execute(int $courseid, int $cmid): array {
        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/response:viewall', $context);

        $userids = helper::get_all_userids_with_responses_in_course($courseid);
        return [
            'users' => core_user_external::get_users_by_field('id', $userids),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(core_user_external::user_description()),
        ]);
    }
}
