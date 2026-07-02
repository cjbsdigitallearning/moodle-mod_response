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
 * The values defined here are configured for all instances.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_response\helper;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_heading(
            'response_heading',
            get_string('generalconfig', 'response'),
            get_string('explaingeneralconfig', 'response')
        )
    );
    $settings->add(new admin_setting_configtext(
        'response/maxprofileimages',
        get_string('maxprofileimages', 'response'),
        get_string('configmaxprofileimages', 'response'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'pagemodeditdefaults',
        get_string('modeditdefaults', 'admin'),
        get_string('condifmodeditdefaults', 'admin')
    ));

    // Show completions before response.
    $options = helper::display_completion_options();
    $settings->add(new admin_setting_configselect(
        'response/displaycompletionbefore',
        get_string('displaycompletion', 'response'),
        get_string('displaycompletion_desc', 'response'),
        'full',
        $options
    ));

    // Show completions after response.
    $options = [get_string('no'), get_string('yes')];
    $settings->add(new admin_setting_configselect(
        'response/displaycompletionafter',
        get_string('displaycompletionafter', 'response'),
        get_string('displaycompletionafter_desc', 'response'),
        'full',
        $options
    ));

    $subplugins = helper::get_type_subplugins();
    $sorter = function ($a, $b) {
        return strcmp($a->display_name, $b->display_name);
    };
    // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnCallbackFunctions
    uasort($subplugins, $sorter);
    foreach ($subplugins as $name => $subplugin) {
        $instance = helper::instance_factory($name, 'configuration');

        // We don't need to pass validation or anything crazy around, we don't need to pass $settings.
        // Instead we receive an array of objects from each subplugin and connect them ourselves.
        $subpluginsettings = $instance->get_default_settings();
        if (!empty($subpluginsettings)) {
            // First add a title.
            $settings->add(new admin_setting_heading(
                'responsetype_' . $name,
                get_string('responsetype_defaultsettings', 'response', $subplugin),
                ''
            ));

            // Then add the subplugin settings.
            foreach ($subpluginsettings as $subpluginsetting) {
                $settings->add($subpluginsetting);
            }
        } else {
            $settings->add(new admin_setting_heading(
                'responsetype_' . $name,
                get_string('responsetype_defaultsettings', 'response', $subplugin),
                get_string('responsetype_nodefault', 'response')
            ));
        }
    }
}
