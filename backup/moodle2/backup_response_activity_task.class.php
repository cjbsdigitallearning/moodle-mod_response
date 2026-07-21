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
 * Defines {@see backup_response_activity_task} class
 *
 * @package     mod_response
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/backup_response_stepslib.php');

/**
 * Provides all the settings and steps to perform one complete backup of response activity
 *
 * @package     mod_response
 * @category    backup
 * @copyright   2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_response_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the workshop.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_response_activity_structure_step('response_structure', 'response.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        // Link to the list of responses.
        $search = '/(' . preg_quote($CFG->wwwroot, '/') . '\/mod\/response\/index.php\?id=)([0-9]+)/';
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceDyn
        $content = preg_replace($search, '$@RESPONSEINDEX*$2@$', $content);

        // Link to response view by module ID.
        $search = '/(' . preg_quote($CFG->wwwroot, '/') . '\/mod\/response\/view.php\?id=)([0-9]+)/';
        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.PregReplace.PregReplaceDyn
        $content = preg_replace($search, '$@RESPONSEVIEWBYID*$2@$', $content);

        return $content;
    }
}
