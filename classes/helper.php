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
 * This file contains a helper class for response activities.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response;
use core_component;
use coding_exception;
use stdClass;
use ReflectionClass;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Helpers for mod_response; essentially an autoloadable version of locallib.php.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Cleans the response text given by users. In general we only want
     * to preserve the p tags in a post, not the full formatting. (We pass
     * it through Moodle's sanitiser in case of bad stuff on p tags.)
     *
     * @param string $text Original content
     * @return string String cleaned to remove all but p tags.
     */
    public static function clean_text($text) {
        return strip_tags(clean_text($text, FORMAT_HTML), '<p>');
    }

    /**
     * Checks that a given user response is not empty. For this purpose
     * we're considering responses from the editor which may contain p
     * tags and we want to ensure that there is still content except them.
     *
     * @param string $text Content to test
     * @return bool True if content exists that isn't whitespace or p tags
     */
    public static function contains_content($text) {
        // First, replace all space entities to be actual whitespace, and for good measure bidi characters.
        $entities = array('&nbsp;', '&ensp;', '$emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&lrm;', '&rlm;');
        $text = str_replace($entities, ' ', $text);

        // Now pass it through Moodle's sanitiser and then strip_tags.
        // This way the first step deals with script tags without being naive about it.
        $text = clean_text($text, FORMAT_HTML);
        $text = strip_tags($text);

        // Now remove all spaces.
        $text = preg_replace('/\s+/i', '', $text);

        // If there's anything left, it's content.
        return !empty($text);
    }

    /**
     * Identifies all of the response type subplugins for this activity,
     * and returns an array of details.
     *
     * @return array Details of known subplugins
     */
    public static function get_type_subplugins() {
        $subplugins = array();
        $subpluginlist = core_component::get_plugin_list('responsetype');
        foreach ($subpluginlist as $name => $path) {

            // Set up some details for them.
            $subplugin = new stdClass();
            $subplugin->type = 'responsetype_' . $name;
            $subplugin->display_name = get_string('pluginname', $subplugin->type);

            $subplugin->classpath = 'mod_response\\type\\' . $name;

            $subplugin->path = $path;

            $subplugins[$name] = $subplugin;
        }

        return $subplugins;
    }

    /**
     * Returns an instance of the requested plugin type.
     *
     * @param string $plugin Subplugin type to load, e.g. 'poll'
     * @param string $class Which subplugin class to load, e.g. 'configuration'
     * @param array $args Arguments to pass to the constructor.
     * @return mixed Plugin class instance, or false if not present.
     */
    public static function instance_factory($plugin, $class, $args = null) {
        $subplugins = self::get_type_subplugins();
        // This is an unknown plugin.
        if (!isset($subplugins[$plugin])) {
            throw new coding_exception('Unknown learning response subplugin ' . $plugin);
        }

        $classname = $subplugins[$plugin]->classpath . '\\' . $class;
        if (!file_exists($subplugins[$plugin]->path . '/classes/' . $class . '.php')) {
            throw new coding_exception('Learning response subplugin ' . $plugin . ' is missing its ' . $class . ' class');
        }
        require_once($subplugins[$plugin]->path . '/classes/' . $class . '.php');

        if (empty($args)) {
            // No arguments, very easy to instantiate.
            return new $classname;
        }

        // Instantiation is a little trickier, and our baseline must be 5.4.x so no splat operator.
        // Because then it would simply be retur new $class(...$args).
        $reflection = new ReflectionClass($classname);
        return $reflection->newInstanceArgs($args);
    }

    /**
     * Takes the form input for configuring this kind of activity
     * and repackages the data to suit how the database actually wants it.
     *
     * This is largely about streamlining mod_response's lib.php to not have
     * a ton of stuff in it that's practically boilerplate.
     *
     * @param object $moduleinstance Module instance to pass to _add_instance etc.
     * @return object An object mostly ready to insert/update.
     */
    public static function package_modform_data($moduleinstance) {
        $newinstance = new stdClass();
        foreach (array('course', 'name', 'intro', 'introformat', 'responsetype', 'question', 'displaycompletion') as $field) {
            $newinstance->$field = $moduleinstance->$field;
        }
        $newinstance->timemodified = time();

        // Toggle peer results is two things in the UI and needs to be one here.
        $newinstance->displaypeerresults = 0;
        if ($moduleinstance->togglepeerresults['studygroup']) {
            $newinstance->displaypeerresults |= RESPONSE_PEER_RESULTS_GROUP;
        }
        if ($moduleinstance->togglepeerresults['all']) {
            $newinstance->displaypeerresults |= RESPONSE_PEER_RESULTS_ALL;
        }

        if (!empty($moduleinstance->completionunlocked)) {
            $newinstance->requiresubmission = 0;
            if ($moduleinstance->completion == COMPLETION_TRACKING_AUTOMATIC) {
                $newinstance->requiresubmission = !empty($moduleinstance->requiresubmission) ? 1 : 0;
            }
        }

        return $newinstance;
    }

    /**
     * Provides the list of possible options for showing completion
     * within the activity. Kept as a separate list to declutter the
     * main form definition which is already complicated enough.
     *
     * @return array List of completion visibility options.
     */
    public static function display_completion_options() {
        return array(
            'full' => get_string('displaycompletionfull', 'response'),
            'number' => get_string('displaycompletionnumber', 'response'),
            'none' => get_string('displaycompletionnone', 'response'),
        );
    }

    /**
     * Provides a list of possible checkboxes for toggling
     * peer results. Kept as a separate list to declutter the group
     * definition.
     *
     * @return array List of peer toggle options.
     */
    public static function peer_result_options() {
        return array(
            'studygroup' => get_string('togglepeerresultsstudygroup', 'response'),
            'all' => get_string('togglepeerresultsall', 'response'),
        );
    }

    /**
     * Given a response and a user who can access that response activity,
     * identify which other users are in the same groups.
     *
     * @param int $id Response activity as per resposne table
     * @param int $userid User ID to look up
     * @return array A list of member ids in the same group
     */
    public static function get_users_in_same_group($id, $userid = null) {
        if (empty($id) || empty($userid)) {
            return array();
        }

        // Now, figure out which groups the user is in.
        $cm = get_coursemodule_from_instance('response', $id, 0, false, MUST_EXIST);
        $groups = groups_get_user_groups($cm->course, $userid);
        $groupmembers = array();
        if (!empty($groups)) {
            foreach ($groups as $grouping) {
                foreach ($grouping as $group) {
                    $groupmembers = array_merge($groupmembers, groups_get_members($group, 'u.id', 'u.id ASC'));
                }
            }
        }
        // Having worked out which people are in the same groups the user is, flatten it down.
        $memberlist = array();
        foreach ($groupmembers as $memberid) {
            $memberlist[] = $memberid->id;
        }

        return $memberlist;
    }

    /**
     * Checks if the user can delete their own response.
     *
     * Assumes $response contains the current state of user
     * responses loaded into it via a subplugin's information
     * class and its load_response_for_users() method.
     *
     * @param object $response The response object, modified in place
     * @param object $context The course module context for the user
     * @param object $cm The course module object
     */
    public static function check_user_delete_own_response(&$response, $context, $cm) {
        global $USER;
        $response->can_delete = false;
        if (!empty($response->user_responses[$USER->id]->timecompleted)) {
            $response->can_delete = has_capability('mod/response:deleteown', $context);
        }
        if ($response->can_delete) {
            $response->delete_url = new moodle_url('/mod/response/deleteanswer.php', array('id' => $cm->id, 'u' => $USER->id));
        }
    }

    /**
     * Checks if the user can see all responses, and sets up
     * suitable links for templates to show all responses.
     *
     * @param object $response The response object, modified in place
     * @param object $context The course module context for the user
     * @param object $cm The course module object
     */
    public static function check_can_see_all_responses(&$response, $context, $cm) {
        $response->can_see_all = false;
        if (has_capability('mod/response:viewall', $context)) {
            $response->can_see_all = true;
            $response->viewall_url = new moodle_url('/mod/response/viewall.php', array('id' => $cm->id));
        }
    }
}
