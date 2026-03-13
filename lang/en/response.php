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
 * Strings for component 'response', language 'en'
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activitydescription'] = 'Activity description';
$string['activityquestion'] = 'Activity question';
$string['activityquestion_help'] = 'The question text will be displayed within the card as the card title, this is the question you wish the student to respond to.';
$string['activitytitle'] = 'Activity title';
$string['allresponses'] = 'All responses';
$string['cannotparticipate'] = 'This is a preview of the activity, it cannot be completed at this time.';
$string['caption'] = 'Caption for Response (Card header)<br><small>This will default to \'Share your thoughts\' is left blank</small>';
$string['caption_help'] = 'The caption allows you to add text to card header at the top of the response activity. If you leave it blank it will display the default text \'Share Your thoughts\'.';
$string['completed1'] = '1 person has completed this activity.';
$string['completedn'] = '{$a} people have completed this activity.';
$string['completiondetail:submission'] = 'Submit an answer';
$string['configmaxprofileimages'] = 'While completing a response activity, a student can be shown a number of profile images of other students that have completed that activity already. If a lot of students complete an activity, it might be better to show users a few profile pictures and the wording "+5 others" or however many there were. This setting lets you set the maximum number of profile images that will be shown.';
$string['content'] = 'Activity Content';
$string['contentheader'] = 'Content';
$string['deleteresponse'] = 'Delete response';
$string['deleteresponseconfirm'] = 'Are you sure you want to delete this response?';
$string['deleteresponsenotcomplete'] = 'This activity has not yet been completed, it cannot yet be deleted.';
$string['displaycompletion'] = 'Shared before response';
$string['displaycompletion_desc'] = 'How to show students how many other students have completed the same activity.';
$string['displaycompletion_help'] = "The 'Shared before response' options determine whether the student should see information about the responses of other students before they submit there own response. The options are:\n
• Nothing<br>
• Names of respondents<br>
• Number of responses\n
Where group mode is enabled within the Common module settings for this tool the count responses and visibility of respondents will be reflective of the group the student is a member of.";
$string['displaycompletionafter'] = 'Shared after response<br><small>Names of respondents</small>';
$string['displaycompletionafter_desc'] = 'Show other students have completed the same activity after completion.';
$string['displaycompletionafter_help'] = "The 'Shared after response' option determines whether the Names of respondents will be displayed after the student submits their response.\n
For example by setting 'Shared before response' option to 'Number of responses' and 'Shared after response' to 'Yes' students may be encouraged to respond in the knowledge the their peers have already responded and wishing to be able to view their responses. A useful motivational tool.\n
Where group mode is enabled within the Common module settings for this tool the count responses and visibility of respondents will be reflective of the group the student is a member of.";
$string['displaycompletionfull'] = 'Names of respondents';
$string['displaycompletionnone'] = 'Nothing';
$string['displaycompletionnumber'] = 'Number of responses';
$string['displayresponseinline'] = 'Inline - within the module section';
$string['displayresponseownpage'] = 'Own page - on a separate page';
$string['downloadresponses'] = 'Download responses';
$string['downloadresponsesall'] = 'Download all responses';
$string['editresponse'] = 'Edit response';
$string['error:cannotviewresponse'] = 'You do not have permission to view this user\'s response';
$string['error:viewerhasnotcompleted'] = 'You cannot view this response as you have not completed the activity';
$string['error:subjecthasnotcompleted'] = 'You cannot view this user\'s response as they have not completed the activity';
$string['explaingeneralconfig'] = 'These settings apply to all Response activities.';
$string['filterbothactive'] = 'First ({$a->first}) Last ({$a->last})';
$string['filterbyname'] = 'Filter by name';
$string['filterfirstactive'] = 'First ({$a->first})';
$string['filterlastactive'] = 'Last ({$a->last})';
$string['generalconfig'] = 'General configuration';
$string['hascompletedthisactivity'] = 'has completed this activity';
$string['havecompletedthisactivity'] = 'have completed this activity';
$string['maximumwords'] = 'Advised word count';
$string['maximumwords_help'] = 'This shows students the expectation of answer as part of an activity. Students can enter more words than this, however.';
$string['maximumwordsprompt'] = 'You have entered [{words}/{$a}] words.';
$string['maxprofileimages'] = 'Maximum number of profile images';
$string['modulename'] = 'Response';
$string['modulename_help'] = 'The response activity module enables a teacher to pose a question, prompt for further information in a reflective fashion, and generally encourage learners to engage with content in a socially orientated manner.';
$string['modulenameplural'] = 'Responses';
$string['nocompletions'] = 'No-one has completed this activity yet.';
$string['other1completed'] = '+1 other';
$string['otherncompleted'] = '+{$a} others';
$string['pluginadministration'] = 'Response administration';
$string['pluginname'] = 'Response';
$string['privacy:metadata:responsetypepluginsummary'] = 'Data for specific types of response.';
$string['privacy:metadata:responseuser'] = 'A record of a user answering a response activity';
$string['privacy:metadata:timecompleted'] = 'The time that the user completed the response';
$string['privacy:metadata:timecreated'] = 'The time when the user first added something to the response.';
$string['privacy:metadata:timemodified'] = 'The time that the user last updated the response';
$string['privacy:metadata:userid'] = 'The ID of the user who completed the response activity.';
$string['requiresubmission'] = 'Require submission';
$string['requiresubmission_desc'] = 'Student must submit an answer to complete this activity';
$string['requiresubmission_help'] = 'If enabled, this activity is marked complete when a student has responded to the stimulus question given and submitted their answer. (This includes completing any reflection steps that may be configured.)';
$string['response'] = 'Response';
$string['response:addinstance'] = 'Add a new response';
$string['response:deleteown'] = 'Delete own response';
$string['response:editown'] = 'Edit own response';
$string['response:manage'] = 'Manage responses';
$string['response:participate'] = 'Participate in response activity';
$string['response:view'] = 'View response activity';
$string['response:viewall'] = 'View all completed responses';
$string['response:viewallresponses'] = 'View all response activity responses';
$string['response:viewother'] = 'View responses of others';
$string['responsedisplay'] = 'Response display';
$string['responsedisplay_help'] = "The 'Response display' options determine how the activity is displayed within a Moodle course, either on it's own page as a standard Moodle 'Page' resource would be displayed or inline as a 'Text and media area' would be.\n
• Own page - on a separate page<br>
• Inline - within the module section";
$string['responsetype'] = 'Response type';
$string['responsetype_defaultsettings'] = 'Default settings for response type: {$a->display_name}';
$string['responsetype_nodefault'] = 'This response type has no default settings.';
$string['shareyourthoughts'] = 'Share your thoughts';
$string['subplugintype_responsetype'] = 'Response type';
$string['subplugintype_responsetype_plural'] = 'Response types';
$string['viewallresponses'] = 'View all responses';
$string['viewcoursesummary'] = 'View course summary';
$string['viewownpagedescription'] = 'Display description on own page?';
$string['yettorespond'] = 'Yet to respond to';
$string['youranswer'] = 'Your answer';
