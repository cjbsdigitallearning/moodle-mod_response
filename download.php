<?php

use mod_response\helper;

define('NO_OUTPUT_BUFFERING', true);
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
global $DB;

admin_externalpage_setup('userbulk');

$id = optional_param('id', '', PARAM_RAW);
$r = optional_param('r', '', PARAM_RAW);
$course = optional_param('course', '', PARAM_RAW);
$course = $DB->get_record('course', ['id' => $course], '*', MUST_EXIST);
//$response = $DB->get_record('response', ['id' => 3], '*', MUST_EXIST);
$response = $DB->get_record('response', ['id' => 1], '*', MUST_EXIST);

$users = helper::get_all_userids_with_responses_in_course(2);
$users = array_unique($users);
$cm = get_coursemodule_from_instance('response', $response->id);
$context = context_module::instance($cm->id);

// CSV headers.
$userdetailfields = [
    'firstname',
    'lastname',
];
//Additional user fields according to user policies showuseridentity.
$identiyfields = \core_user\fields::get_identity_fields($context, true);
foreach ($identiyfields as $field) {
    $userdetailfields[] = $field;
}
$responsefields = [
    'name',
    'question',
    'type',
    'timemodified',
];
$csvfields = array_merge($userdetailfields, $responsefields);

foreach ($users as $user) {
    // Get the user details.
    $user = $DB->get_record('user', ['id' => $user], '*', MUST_EXIST);
    // Get user response.
    $response = helper::get_response_data($course, $response, $user->id);
    // Prepare for csv export.
    $responsevalues = [
        'name' => $response->activity_title,
        'question' => $response->question,
        'type' => $response->responsetype,
        'timemodified' => $response->response->timemodified,
    ];

    // Get user response text.
    $subplugin = helper::instance_factory($response->responsetype, 'information');
    $subfields = $subplugin->get_response_fields();
    $subvalues = $subplugin->get_response_values($response);
    foreach ($subfields as $k => $v) {

        $responsefields[] = $k;
        $responsevalues[$k] = $subvalues[$k];
    }
    // Convert file paths to URLs.
    $text = file_rewrite_pluginfile_urls($responsevalues['response'], 'pluginfile.php', $context->id, 'responsetype_text_user', 'response_text', $response->response->response_user_id);
    // Convert html to text with links.
    $text = html_to_text($text, 0, true);
    $responsevalues['response'] = $text;
    // Export response text to a file.
//    $myfile = fopen("{$user->email}.txt", "w") or die("Unable to open file!");
//    fwrite($myfile, $text);
//    fclose($myfile);
    // Get the files to be exported.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'responsetype_text_user', 'response_text', false, '', false);
    // This will possibly have problems until WR473938 is addressed.
    foreach ($files as $file) {
        $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
            $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
    }

    // Standard users details.
    $userdetails = [
      'firstname' => $user->firstname,
      'lastname' => $user->lastname,
    ];
    // Additional user details according to user policies showuseridentity.
    foreach ($identiyfields as $field) {
        $userdetails[$field] = $user->$field;
    }

    // Should have everything we need to start the export.
    var_export($csvfields);
    echo "<br>";
    var_export($userdetails);
    echo "<br>";
    echo "<br>";
    var_export($responsefields);
    echo "<br>";
    var_export($responsevalues);
}

// Testing things.
//$instance = helper::instance_factory($response->responsetype, 'information');
//$activity = $instance->load_activity($response);
//$responses = $instance->load_all_responses($response);
//$fields = [];
//foreach ($responses as $userresponse) {
//    // At some point we can use the array keys as the csv column headers.
//    $fields = [
//        'name' => $response->name,
//        'type' => $response->responsetype,
//        'question' => $response->question,
//    ];
//}
//
//foreach ($responses as $userresponse) {
//    foreach ($userresponse as $k => $v) {
//        if (!array_key_exists($k, $fields)) {
//            $fields[$k] = $v;
//        }
//        // Remove any fields we don't want.
//        unset($fields['id'], $fields['profile_picture'], $fields['response_user_id'], $fields['userid']);
//    }
//}

