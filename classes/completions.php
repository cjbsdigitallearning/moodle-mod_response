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
use stdClass;
use user_picture;
use core_user\fields;

/**
 * Methods for loading information on completed activities for the user incentivisation aspect of responses.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completions {

    /**
     * Returns whether any students have at least started this activity.
     *
     * Some activities are multi-page activities and state is tracked
     * between the pages in the database even if the activity is not
     * yet officially complete. Note that this count will necessarily
     * include students that have completed the activity.
     *
     * @param int $id Response instance ID (as in response table)
     * @return int Number of students that have at least started the activity.
     */
    public static function total_students_have_begun($id) {
        global $DB;

        return $DB->count_records_select('response_user', 'response = ?', [$id]);
    }

    /**
     * Returns whether any students have completed a given instance
     * of this activity.
     *
     * @param int $id Response instance ID (as in response table)
     * @return int Number of students that have completed this activity.
     */
    public static function total_students_have_completed($id) {
        global $DB;

        return $DB->count_records_select('response_user', 'response = ? AND timecompleted > ?', [$id, 0]);
    }

    /**
     * Load the raw data of users that have completed a given instance
     * of a response activity.
     *
     * The data will be loaded and sorted into an array ordered by
     * completion time. If a user id is given, and that user has
     * completed the activity, they will be listed first in all cases.
     *
     * @param int $id Instance of activity as in response table
     * @param int $userid The current user id
     * @return array An array of objects representing completions, keyed by user id
     */
    protected static function fetch_completions($id, $userid = null) {
        global $DB;

        $completions = [];

        // First, get everyone who has completed this activity.
        $completed = $DB->get_records_select('response_user', 'response = ? AND timecompleted > ?', [$id, 0]);
        foreach ($completed as $completion) {
            $completions[$completion->userid] = $completion;
        }

        // If the user in question has completed, move them to the start of the list.
        if (!empty($userid) && isset($completions[$userid])) {
            $usercompletion = $completions[$userid];
            unset ($completions[$userid]);
            $completions = [$userid => $usercompletion] + $completions;
        }

        return $completions;
    }

    /**
     * Load the user data for a list of already-fetched completions.
     *
     * Accepts an array of already-fetched users (see fetch_completions)
     * and attaches user name and profile picture to those completions.
     *
     * Data moved by reference because it could grow quite large...
     *
     * @param array $completions An array of completions as per fetch_completions
     */
    protected static function load_user_data_for_completion(&$completions) {
        global $DB, $PAGE;

        $useridlist = array_keys($completions);
        if (empty($useridlist)) {
            return;
        }

        foreach ($useridlist as $userid) {
            $completions[$userid]->name = '';
            $completions[$userid]->picture = '';
        }

        $fields = implode(',', fields::get_picture_fields());
        list ($sql, $params) = $DB->get_in_or_equal($useridlist);
        $records = $DB->get_records_select('user', 'id ' . $sql, $params, '', $fields);

        foreach ($records as $user) {
            $completions[$user->id]->name = $user->firstname . ' ' . $user->lastname;
            $userpicture = new user_picture($user);
            $completions[$user->id]->picture = (string) $userpicture->get_url($PAGE);
        }
    }

    /**
     * Copies the data across from the 'all users' to the
     * 'grouped users' part of the object.
     *
     * @param object $completions Completions data
     * @param array $membersingroup Array of members in group
     */
    protected static function sift_groups_out(&$completions, $membersingroup) {
        // Now sift out groups.
        $completions->people_group = [];
        $members = array_flip($membersingroup); // As isset runs faster than in_array, flip the array and do it as a hash lookup.
        foreach (array_keys($completions->people_all) as $user) {
            if (isset($members[$user])) {
                $completions->people_group[$user] = $completions->people_all[$user];
            }
        }
        $completions->number_group = count($completions->people_group);
    }

    /**
     * Having fetched all the member data, we might need to split
     * it into 'the first x' and 'everyone else' as per config
     * settings. This function manages that on the already-fetched
     * data.
     *
     * @param object $completions Completions data
     */
    protected static function slice_people_lists(&$completions) {
        $responseconfig = get_config('response');

        if (empty($responseconfig->maxprofileimages)) {
            return; // Nothing else to do here.
        }

        foreach (['group', 'all'] as $sel) {
            $source = 'people_' . $sel;
            $dest = 'people_' . $sel . '_other';
            // Get full list.
            $people = $completions->$source;
            // Put the first x back.
            $completions->$source = array_slice($people, 0, $responseconfig->maxprofileimages);
            // And put the rest into the destination.
            $completions->$dest = array_slice($people, $responseconfig->maxprofileimages);
        }
    }

    /**
     * Returns list of completions based on groupmode of activity.
     *
     * @param stdClass $cm
     * @param int $groupid
     * @return array
     */
    public static function get_completions_by_groupmode(stdClass $cm, int $groupid = 0): array {
        $completions = self::fetch_completions($cm->instance);
        $intersectuserids = [];

        switch ($cm->groupmode) {
            case SEPARATEGROUPS:
                $intersectuserids = helper::get_users_in_same_group($cm->instance);
                $completions = array_intersect_key($completions, array_flip($intersectuserids));
                break;
            case VISIBLEGROUPS:
                if (!($groupid <= 0) && groups_group_visible($groupid, $cm->course)) {
                    $intersectuserids = groups_get_members($groupid, 'u.id');
                    $completions = array_intersect_key($completions, $intersectuserids);
                }
                break;
            case NOGROUPS:
            default:
                break;
        }

        self::load_user_data_for_completion($completions);
        return $completions;
    }
}
