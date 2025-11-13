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

use core_component;
use coding_exception;
use stdClass;
use ReflectionClass;
use moodle_url;
use renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Options for how users view the response completions.
 *
 * @package    mod_response
 * @copyright  2025 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum display_completion: int {
    case NONE   = 0;
    case NUMBER = 1;
    case NAME   = 2;
}

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
        // First, convert lists into something useful if somehow we got one.
        $text = str_replace('</li>', '<br />', $text);
        $text = str_replace('<li>', '', $text);
        $text = str_replace(['<ul>', '<ol>'], '<p>', $text);
        $text = str_replace(['</ul>', '</ol>'], '</p>', $text);
        // Strip the rest of the formatting.
        $text = strip_tags(clean_text($text, FORMAT_HTML), '<p><br>');
        // Clean house on weird circumstances; Atto can oddly nest P tags, for example.
        $text = str_replace('<p><p>', '<p>', $text);
        $text = str_replace('</p></p>', '</p>', $text);
        $text = str_replace('<p></p>', '', $text);
        return $text;
    }

    /**
     * Checks that a given user response is not empty. For this purpose
     * we're considering responses from the editor which may contain p
     * tags and we want to ensure that there is still content except them.
     *
     * @param string $text Content to test
     * @return bool True if content exists that isn't whitespace or p tags
     */
    public static function contains_content(string $text): bool {
        // First, replace all space entities to be actual whitespace, and for good measure bidi characters.
        $entities = ['&nbsp;', '&ensp;', '$emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&lrm;', '&rlm;'];
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
     * Checks that a given user response is not empty. For this purpose
     * we're considering responses from the editor which may contain p
     * tags and we want to ensure that there is still content except them.
     *
     * This version allows supporting media objects such as <img> or similar.
     *
     * @param string $text Content to test
     * @return bool True if content exists that isn't whitespace or p tags
     */
    public static function contains_content_or_media(string $text): bool {
        // First, replace all space entities to be actual whitespace, and for good measure bidi characters.
        $entities = ['&nbsp;', '&ensp;', '$emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&lrm;', '&rlm;'];
        $text = str_replace($entities, ' ', $text);

        // Now pass it through Moodle's sanitiser and then strip_tags.
        // This way the first step deals with script tags without being naive about it.
        $text = clean_text($text, FORMAT_HTML);
        $text = strip_tags($text, '<img><audio><video>');

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
        $subplugins = [];
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
            throw new coding_exception('Unknown response subplugin ' . $plugin);
        }

        $classname = $subplugins[$plugin]->classpath . '\\' . $class;
        if (!file_exists($subplugins[$plugin]->path . '/classes/' . $class . '.php')) {
            throw new coding_exception('Response subplugin ' . $plugin . ' is missing its ' . $class . ' class');
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

        $fields = ['course',
                        'name',
                        'intro',
                        'introformat',
                        'responsetype',
                        'responsedisplay',
                        'viewownpagedescription',
                        'question',
                        'displaycompletionbefore',
                        'displaycompletionafter',
                        'caption',
                    ];

        foreach ($fields as $field) {
            $newinstance->$field = $moduleinstance->$field;
        }
        $newinstance->timemodified = time();

        $newinstance->content = $moduleinstance->responsecontent['text'];
        $newinstance->contentformat = $moduleinstance->responsecontent['format'];

        if (!empty($moduleinstance->completionunlocked)) {
            $newinstance->requiresubmission = 0;
            if ($moduleinstance->completion == COMPLETION_TRACKING_AUTOMATIC) {
                $newinstance->requiresubmission = !empty($moduleinstance->requiresubmission) ? 1 : 0;
            }
        }

        return $newinstance;
    }

    /**
     * Determine if user can see peer response before/after completion.
     *
     * @param integer $viewerid ID of user viewing the response
     * @param integer $vieweeid
     * @param stdClass $cm
     * @param stdClass $response
     * @param int $groupid In the case of groupmode = VISIBLEGROUPS, we use this value to determine which groups to view - 0 = all.
     * @param boolean $before Determine if we are retrieving $response->displaycompletionbefore or $response->displaycompletionafter.
     * @return boolean
     */
    public static function can_see(int $viewerid, int $vieweeid, stdClass $cm, stdClass $response, int $groupid = 0, bool $before = true): bool {
        // User can see their own response.
        if ($viewerid == $vieweeid) {
            return true;
        }

        // Return false if relative display setting is NONE.
        $when = $before ? 'before' : 'after';
        $property = 'displaycompletion' . $when;
        $display = $response->$property;
        if ($display == display_completion::NONE->value) {
            return false;
        }

        // Determine based on groupmode.
        switch ($cm->groupmode) {
            case VISIBLEGROUPS:
                if (groups_group_visible($groupid, $cm->course, $cm, $vieweeid)) {
                    return true;
                }
                return has_capability('moodle/course:viewhiddengroups', \context_course::instance($cm->course));
            case SEPARATEGROUPS:
                return in_array($viewerid, helper::get_users_in_same_group($response->id, $vieweeid));
            case NOGROUPS:
            default:
                return true;
        }

    }

    /**
     * Returns an array of options for the Atto WYSIWYG editor.
     *
     * @param context_module $context Context the editor is being added to.
     *
     * @return array An array of options.
     */
    public static function get_editor_options($context) {
        global $CFG;

        $editoroptions = [
            'subdirs' => true,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'changeformat' => true,
            'context' => $context,
            'noclean' => true,
            'trusttext' => false,
        ];

        return $editoroptions;
    }

    /**
     * Provides the list of possible options for showing completion
     * within the activity. Kept as a separate list to declutter the
     * main form definition which is already complicated enough.
     *
     * @return array List of completion visibility options.
     */
    public static function display_completion_options() {
        return [
            display_completion::NONE->value => get_string('displaycompletionnone', 'response'),
            display_completion::NAME->value => get_string('displaycompletionfull', 'response'),
            display_completion::NUMBER->value => get_string('displaycompletionnumber', 'response'),
        ];
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
        if (is_null($userid)) {
            global $USER;
            $userid = $USER->id;
        }
        
        // Now, figure out which groups the user is in.
        $cm = get_coursemodule_from_instance('response', $id, 0, false, MUST_EXIST);
        $groups = groups_get_user_groups($cm->course, $userid);
        $groupmembers = [];
        if (!empty($groups)) {
            foreach ($groups as $grouping) {
                foreach ($grouping as $group) {
                    $groupmembers = array_merge($groupmembers, groups_get_members($group, 'u.id', 'u.id ASC'));
                }
            }
        }
        // Having worked out which people are in the same groups the user is, flatten it down.
        $memberlist = [];
        foreach ($groupmembers as $memberid) {
            $memberlist[] = $memberid->id;
        }

        return $memberlist;
    }

    /**
     * Given a response and a user who can access that response activity,
     * identify which other users are in the same groups.
     *
     * @param int $id Response activity as per resposne table
     * @param int $userid User ID to look up
     * @return array A list of member ids in the same group
     */
    public static function get_users_in_group($cm, $groupid) {
        if (groups_group_visible($groupid, $cm->course)) {
            return;
        }

        // Now, figure out which groups the user is in.
        $groupmembers = groups_get_members($groupid, $cm->course, 'u.id', 'u.id ASC');

        // Having worked out which people are in the same groups the user is, flatten it down.
        $memberlist = [];
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
            $response->delete_url = new moodle_url('/mod/response/deleteanswer.php', ['id' => $cm->id, 'u' => $USER->id]);
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
            $response->viewall_url = new moodle_url('/mod/response/viewall.php', ['id' => $cm->id]);
        }
    }

    /**
     * Checks if the user can edit their own response
     * and sets up appropriate links for the templates.
     *
     * @param object $response The response object, modified in place
     * @param object $context The course module context for the user
     * @param object $cm The course module object
     */
    public static function check_can_edit_own_response(&$response, $context, $cm) {
        global $USER;
        $response->can_edit = false;
        if (!empty($response->user_responses[$USER->id]->timecompleted)) {
            $response->can_edit = has_capability('mod/response:editown', $context);
        }
        if ($response->can_edit) {
            $response->edit_url = new moodle_url('/mod/response/view.php', ['id' => $cm->id, 'editing' => 1]);
        }
    }

    /**
     * Get list of users with responses in the course.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_all_userids_with_responses_in_course(int $courseid) {
        global $DB;
        $sql = "SELECT u.id FROM {user} u
                 JOIN {response_user} ru ON ru.userid = u.id
                 JOIN {response} r ON r.id = ru.response AND r.course = ?
                 JOIN {user_enrolments} ue ON u.id = ue.userid
           INNER JOIN {enrol} e ON ue.enrolid = e.id AND e.courseid = ?";
        $params = [$courseid, $courseid];
        return $DB->get_fieldset_sql($sql, $params) ?: [];
    }

    /**
     * Filters list of responses with filter settings.
     *
     * @param array $responses The list of responses to filter
     * @param array $filters The filters to apply 'userid', 'ifirst', 'ilast', and 'search'
     * @param integer $courseid ID of the course, used to retrieve users that belong to the course the response is in.
     * @return array
     */
    public static function filter_responses(array $responses, array $filters, int $courseid) {
        // Return responses from respective user if userid is set.
        if (isset($filters['userid']) && $userid = $filters['userid']) {
            return array_filter($responses, static fn($response) => $response->userid == $userid);
        }

        // Filter by first name initial.
        if (isset($filters['ifirst']) && $ifirst = $filters['ifirst']) {
            $responses = array_filter($responses, static fn($response) => stripos($response->first_name, $ifirst) === 0);
        }

        // Filter by last name initial.
        if (isset($filters['ilast']) && $ilast = $filters['ilast']) {
            $responses = array_filter($responses, static fn($response) => stripos($response->last_name, $ilast) === 0);
        }

        // Filter by search.
        if (isset($filters['search']) && $search = $filters['search']) {
            $searchusers = search_users($courseid, 0, $search);
            $userids = array_column($searchusers, 'id');
            $responses = array_filter($responses, static fn($response) => in_array($response->userid, $userids));
        }

        return $responses;
    }

    /**
     * Builds a simplified response object to pass out to templates for rendering
     * a given activity in a course summary.
     *
     * @param object $course The course in question
     * @param object $response The response object for a given response activity
     * @param int $userid The user ID whose response is being examined
     * @param ?renderer_base $renderer Option to use existing renderer.
     * @return object A simple object to pass to the summary template, with an individual activity having already been templated.
     */
    public static function get_response_data(object $course, object $response, int $userid,
            ?renderer_base $renderer = null): object {
        global $PAGE;
        if (empty($renderer)) {
            $renderer = $PAGE->get_renderer('mod_response');
        }

        $cm = get_coursemodule_from_instance('response', $response->id);

        $return = new stdClass();
        $return->activity_title = $cm->name;
        $return->question = $response->question;
        $instance = helper::instance_factory($response->responsetype, 'information');

        // Determine if the user has responded.
        // We actually can't rely on completion status if it wasn't tracked by the completion system, so use ours.
        $response->user_responses = $instance->load_response_for_users($response, [$userid]);
        if (!empty($response->user_responses[$userid]) || !empty($response->user_responses[$userid]->timecompleted)) {
            $return->response = $response->user_responses[$userid];
        }
        $instance->load_activity($response);

        $instance->load_aggregate_data($response, $userid);

        $return->cm_id = $cm->id;
        $return->course_id = $course->id;
        $return->section_id = null;

        $sections = course_get_format($course->id)->get_sections();
        foreach ($sections as $sectionobj) {
            if ($sectionobj->id == $cm->section) {
                $return->section_id = $sectionobj->section;
            }
        }

        // When showing in context, the link varies depending on course format.
        $return->view_in_course = !empty($course->format) && $course->format !== 'singleactivity';
        $return->responsetype = $response->responsetype;
        $return->aggregate = !empty($response->aggregate) ? $response->aggregate : new stdClass();
        $return->activity = $response->activity;
        $return->displaypeerresults = $response->displaypeerresults;
        $return->icon = new \pix_icon('icon', '', 'responsetype_' . $response->responsetype);

        $renderable = helper::instance_factory($response->responsetype, 'summaryoutput', [$return, $instance]);
        $return->render = $renderer->render($renderable);

        return $return;
    }

    /**
     * Ger all responses in course with filters.
     *
     * @param object $course Standard course object.
     * @param array $filters Filter values from filter form.
     * @return array
     */
    public static function get_course_responses(stdClass $course, array $filters): array {
        $responses = get_all_instances_in_course('response', $course);

        $sectionresponses = [];
        $responselist = [];

        // Create an object to store the items that don't have a response yet.
        $noresponseobj = (object) [
            'section_title' => get_string('yettorespond', 'mod_response'),
            'responses' => [],
        ];
        
        if (course_format_uses_sections($course->format)) {
            // This course format uses sections, so we need to arrange for this.

            foreach ($responses as &$response) {
                // Get and filter user responses to response module.
                $instance = self::instance_factory($response->responsetype, 'information');
                $instance->load_activity($response);
                $loadedresponses = $instance->load_all_responses($response);
                $loadedresponses = self::filter_responses($loadedresponses, $filters, $course->id);
                $response->user_responses = $loadedresponses;

                $responseid = $response->id;
                $responsesection = $response->section;

                // If they can delete responses, we need to build suitable links.
                $context = \context_module::instance($response->coursemodule);
                if (has_capability('mod/response:manage', $context)) {
                    foreach ($response->user_responses as $userid => $user_response) {
                        $deletelink = new moodle_url('/mod/response/deleteanswer.php', ['id' => $response->coursemodule, 'u' => $userid]);
                        $response->user_responses[$userid]->delete_link = $deletelink;
                    }
                }

                // Add response to list, categorised by section.
                if (isset($sectionresponses[$responsesection])) {
                    $sectionresponses[$responsesection]->responses[$responseid] = $response;
                } else {
                    $responsesectionobj = (object) [
                        'section_title' => get_section_name($course, $responsesection),
                        'responses' => [$responseid => $response],
                    ];
                    $sectionresponses[$responsesection] = $responsesectionobj;
                }

            }
        } else {
            // No sections here, so present it flat.
            $responselist[] = new stdClass();
            $responselist[0]->section_title = '';
            $responselist[0]->responses = [];
            foreach ($responses as &$response) {
                $instance = self::instance_factory($response->responsetype, 'information');
                $loadedresponses = $instance->load_all_responses($response);
                $loadedresponses = self::filter_responses($loadedresponses, $filters, $course->id);

                $response->user_responses = [];
                foreach ($loadedresponses as $loadresponse) {
                    $activity = helper::get_response_data($course, $response, $loadresponse->userid);
                    if (isset($activity->response)) {
                        $response->user_responses[] = $activity;
                    } else {
                        $noresponseobj->responses[] = $activity;
                    }
                }
            }
            $sectionresponses[] = $responselist;
        }

        return $sectionresponses;
    }


}
