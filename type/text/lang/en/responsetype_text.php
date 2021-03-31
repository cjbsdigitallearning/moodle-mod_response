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
 * Strings for component 'responsetype_text', language 'en'
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['editorconfig'] = 'Default toolbar for free-text responses';
$string['editorconfig_desc'] = '<b>Available options:</b><br>
{$a->available}<br>
<br>
<em>Options can be organised into groups if required, as follows:</em><br>
style1 = title, bold, italic<br>
list = unorderedlist, orderedlist<br>
links = link, noautolink<br>
files = image, media, recordrtc';
$string['editorconfig_instance'] = 'Toolbar configuration';
$string['maximumwords_default'] = 'This sets the default advised word count. If given as 0, the field will be disabled by default.';
$string['noresponse'] = 'You have not responded yet.';
$string['nothingwritten'] = 'Please provide an answer.';
$string['overrideeditorconfig'] = 'Override default toolbar';
$string['overrideeditorconfig_help'] = 'There is a default configuration for the toolbar for free-text responses - this option, if available, allows you to override it and provide your own specific configuration for this instance.';
$string['pluginname'] = 'Free text';
$string['privacy:metadata:response'] = 'The ID of the response activity the user is providing answer for';
$string['privacy:metadata:responsetext'] = 'The textual response from the user (edit history is kept if not displayed)';
$string['privacy:metadata:responsetype_text_user'] = 'Storage for "Free Text" responses from users';
$string['privacy:metadata:timesubmitted'] = 'The time the response was submitted';
$string['privacy:metadata:userid'] = 'The ID of the user who completed the response activity';
$string['text:editor_atto__toolbar_config'] = 'Override Atto editor configuration in free-text responses';
$string['theywrote'] = '{$a} wrote:';
$string['youwrote'] = 'You wrote:';
