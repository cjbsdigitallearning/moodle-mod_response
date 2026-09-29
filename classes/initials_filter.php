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

namespace mod_response;

use cache;

/**
 * Remembers the "initials" name filter applied on the viewall/viewallresponses pages, per module context, for the
 * duration of a user's session.
 *
 * Backed by the Cache API (MUC) using MODE_SESSION, rather than storing data directly in $_SESSION.
 *
 * @package   mod_response
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class initials_filter {
    /**
     * Remember the initials name filter a user has applied for a given module context.
     *
     * @param int $contextid The module context ID the filter applies to.
     * @param ?string $firstinitial The first name initial to remember, or null to leave the current value unchanged.
     * @param ?string $lastinitial The last name initial to remember, or null to leave the current value unchanged.
     * @return void
     */
    public static function set(int $contextid, ?string $firstinitial, ?string $lastinitial): void {
        $cache = cache::make('mod_response', 'initialsfilter');

        if ($firstinitial !== null) {
            $cache->set("firstname_{$contextid}", $firstinitial);
        }
        if ($lastinitial !== null) {
            $cache->set("lastname_{$contextid}", $lastinitial);
        }
    }

    /**
     * Get the initials name filter previously remembered for a given module context.
     *
     * @param int $contextid The module context ID the filter applies to.
     * @return array Array with 'firstinitial' and 'lastinitial' string keys, empty string when nothing is remembered.
     */
    public static function get(int $contextid): array {
        $cache = cache::make('mod_response', 'initialsfilter');

        return [
            'firstinitial' => $cache->get("firstname_{$contextid}") ?: '',
            'lastinitial' => $cache->get("lastname_{$contextid}") ?: '',
        ];
    }
}
