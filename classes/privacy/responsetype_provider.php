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

namespace mod_response\privacy;

use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\subplugin_provider;
use core_privacy\local\request\shared_userlist_provider;
use core_privacy\local\request\approved_contextlist;
use context;

/**
 * Response Sub plugins should implement this if they store personal information.
 *
 * @package mod_response
 * @copyright 2018 Peter Spicer <peter.spicer@catalyst-eu.net>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface responsetype_provider extends subplugin_provider, shared_userlist_provider {

    /**
     * Export the user data from a subplugin specific context.
     *
     * @param approved_contextlist $contextlist The approved context list to export
     * @param array $responseidstocmids An array mapping response IDs to their course modules to be processed
     * @param int $userid The user whose data to export
     */
    public static function export_user_data(approved_contextlist $contextlist, array $responseidstocmids, int $userid);

    /**
     * Delete all user data which matches the specified context.
     *
     * @param context $context The module context.
     * @param int $responseid A response ID to clean up
     */
    public static function delete_data_for_all_users_in_context(\context $context, int $responseid);

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @param array $responseidstocmids An array of response/course module mappings (to avoid requerying)
     * @param int $userid
     */
    public static function delete_data_for_user(approved_contextlist $contextlist, array $responseidstocmids, int $userid);

    /**
     * Delete data for specified users in a specified context.
     *
     * @param context $context The context to limit deletions to
     * @param int $responseid The repsonse ID (should match context, but kept for performance)
     * @param array $userids An array of user IDs to delete data for within the limited context
     */
    public static function delete_data_for_users(context $context, int $responseid, array $userids);
}
