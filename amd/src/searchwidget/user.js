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
 * Allow the user to search for user responses.
 *
 * Copied and altered from grade/report/user/amd/src/user.js
 *
 * @module    mod_response/searchwidget/user
 * @copyright 2024 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import UserSearch from 'core_user/comboboxsearch/user';
import Url from 'core/url';
import * as Repository from 'mod_response/searchwidget/repository';

// Define our standard lookups.
const selectors = {
    component: '.user-search',
    courseid: '[data-region="courseid"]',
    formDropdown: '.initialsdropdownform',
};
const component = document.querySelector(selectors.component);
const courseID = component.querySelector(selectors.courseid).dataset.courseid;
const cmID = document.querySelector(selectors.formDropdown).dataset.cmid;

export default class User extends UserSearch {

    constructor() {
        super();
    }

    static init() {
        return new User();
    }

    /**
     * Get the data we will be searching against in this component.
     *
     * @returns {Promise<*>}
     */
    fetchDataset() {
        return Repository.userFetch(courseID, cmID).then((r) => r.users);
    }

    /**
     * Build up the view all link.
     *
     * @returns {string|*}
     */
    selectAllResultsLink() {
        return Url.relativeUrl('/mod/response/viewall.php', {
            id: cmID,
            search: this.getSearchTerm()
        }, false);
    }

    /**
     * Build up the view all link that is dedicated to a particular result.
     *
     * @param {Number} userID The ID of the user selected.
     * @returns {string|*}
     */
    selectOneLink(userID) {
        return Url.relativeUrl('/mod/response/viewall.php', {
            id: cmID,
            userid: userID,
        }, false);
    }
}
