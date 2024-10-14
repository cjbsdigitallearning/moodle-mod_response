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

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps for mod_response
 *
 * @package   mod_response
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_response extends behat_base {
    #[\Override]
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'view':
                return new moodle_url('/mod/response/view.php',
                    ['id' => $this->get_cm_by_response_name($identifier)->id]);
        }
    }

    /**
     * Get a response by name.
     *
     * @param string $name response name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_response_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('response', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * Get a response cmid from the response name.
     *
     * @param string $name quiz name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_response_name(string $name): stdClass {
        $response = $this->get_response_by_name($name);
        return get_coursemodule_from_instance('response', $response->id, $response->course);
    }
}
