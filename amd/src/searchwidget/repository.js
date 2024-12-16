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
 * A repo for the search widget.
 *
 * Copied and altered from grade/amd/src/searchwidget/repository.js
 *
 * @module    mod_response/searchwidget/repository
 * @copyright 2024 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ajax from 'core/ajax';

/**
 * Given a course ID, we want to fetch the enrolled learners, so we may fetch their reports.
 *
 * @method userFetch
 * @param {int} courseid ID of the course to fetch the users of.
 * @param {int} cmid ID of the course to fetch the users of.
 * @return {object} jQuery promise
 */
export const userFetch = (courseid, cmid) => {
    const request = {
        methodname: 'mod_response_get_all_users_with_responses_in_course',
        args: {
            courseid: courseid,
            cmid: cmid,
        },
    };
    return ajax.call([request])[0];
};
