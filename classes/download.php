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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;
use ZipArchive;


/**
 * Class for downloading responses.
 *
 * @package   mod_response
 * @copyright 20125 Sarah Cotton <sarah.cotton@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class download {

    /**
     * The class constructor.
     *
     * @param \context_module $context Context object.
     * @param \stdClass $response Response object.
     * @param object $subplugin Subplugin object.
     */
    public function __construct(\context_module $context, \stdClass $response, object $subplugin) {
        $this->context = $context;
        $this->response = $response;
        $this->subplugin = $subplugin;
        // Add activity info to $response.
        $subplugin->load_activity($response);
        // File area info.
        $this->filetype = $subplugin->get_filetype();
        $this->filearea = $subplugin->get_filearea();
        $this->filecomponent = $subplugin->get_filecomponent();
        // Export dir/filename.
        $this->responsename = 'mod_response_' . $response->name . '_' . time();
        $this->tempcoursedir = make_temp_directory("mod_response_{$response->course}");
        $this->tempresponsedir = make_temp_directory("mod_response_{$response->course}/{$response->name}");
        // CSV headings.
        $this->csvheadings = array_merge(
            $this->get_identity_fields(),
            $this->get_standard_response_fields(),
            $this->get_user_response_fields()
        );
        // Response fields.
        $this->usertextfield = $subplugin->get_usertext_fieldname();

        return $this;
    }

    /**
     * Get the user identity field headings for the csv.
     *
     * @return array An array of user fields.
     */
    public function get_identity_fields(): array {
        // CSV headers.
        $userdetailfields = [
            'firstname',
            'lastname',
        ];
        // Additional user fields according to user policies showuseridentity.
        $identityfields = \core_user\fields::get_identity_fields($this->context, true);
        foreach ($identityfields as $field) {
            $userdetailfields[] = $field;
        }

        return $userdetailfields;
    }

    /**
     * Get the user identity data for the csv.
     *
     * @param stdClass $user The user object.
     * @return array An array of user fields.
     */
    public function get_identity_values(stdClass $user): array {
        // Standard users details.
        $userdetails = [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
        ];
        // Additional user details according to user policies showuseridentity.
        foreach ($this->get_identity_fields() as $field) {
            $userdetails[$field] = $user->$field;
        }

        return $userdetails;
    }

    /**
     * Get the standard response field headings for the csv.
     *
     * @return array An array of standard response fields.
     */
    public function get_standard_response_fields(): array {
        return [
            'response_name',
            'response_type',
            'question',
            'timemodified',
        ];
    }

    /**
     * Get the values for the standard response fields for the csv.
     *
     * @param stdClass $responsedata The response data
     * @return array An array of standard response fields.
     */
    public function get_standard_response_values(stdClass $responsedata): array {
        // Data for standard csv fields.
        $timemodified = userdate($responsedata->response->timemodified, get_string('strftimedaydatetime', 'core_langconfig'));

        return [
            'response_name' => $responsedata->activity_title,
            'response_type' => $responsedata->responsetype,
            'question' => $responsedata->question,
            'timemodified' => $timemodified,
        ];
    }

    /**
     * Get the user response field headings for the csv.
     *
     * @return array An array of user response fields.
     */
    public function get_user_response_fields(): array {
        $subfields = $this->subplugin->get_response_fields($this->response);
        foreach ($subfields as $k => $v) {
            $responsefields[$k] = $k;
        }
        return $responsefields;
    }

    /**
     * Format the user response text.
     *
     * @param array $userresponse The user response
     * @param int $userid The user id
     * @return array An array of text formatted in different ways.
     */
    public function format_user_text(array $userresponse, int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/repository/url/locallib.php');

        $formattedtext = [];
        // Convert file paths to URLs.
        $rewritelinks = file_rewrite_pluginfile_urls(
            $userresponse[$this->usertextfield],
            $this->filetype,
            $this->context->id,
            $this->filecomponent,
            $this->filearea,
            $userid
        );
        // For the csv export we just want plain text.
        $formattedtext['plain'] = strip_tags($rewritelinks);
        // Convert html to text with links for the file export.
        $formattedtext['withlinks'] = html_to_text($rewritelinks, 0, true);

        // Extract all the internal URLs.
        $urls = extract_html_urls($rewritelinks);
        if (isset($urls['a']['href'])) {
            foreach ($urls['a']['href'] as $url) {
                // If this is an internal URL the file will be included in the zip,
                // so we just want the filename.
                if (str_contains($url, $CFG->wwwroot)) {
                    // Remove the url, leaving just the filename.
                    $filename = basename($url);
                    $formattedtext['withlinks'] = str_replace($url, $filename, $formattedtext['withlinks']);
                }
            }
        }

        return $formattedtext;
    }

    /**
     * Create a file containing the formatted user response ready for export.
     *
     * @param stdClass $user The user response
     * @param string $text The user id
     * @return bool
     */
    public function create_response_file(stdClass $user, string $text): bool {
        // Create a text file with formatted response.
        mkdir("{$this->tempresponsedir}/{$user->username}");
        $path = "{$this->tempresponsedir}/{$user->username}/{$user->username}-{$this->usertextfield}.txt";
        $myfile = fopen($path, "w");
        fwrite($myfile, $text);
        if (fclose($myfile)) {
            return true;
        }
        return false;
    }

    /**
     * Get the files attached to the text editor for export.
     *
     * @param array $text The formatted response text array
     * @param string $username Username of the user to export
     */
    public function create_file_attachments(array $text, string $username): void {
        // Get the file attachments to be exported.
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id,
            $this->subplugin->get_filecomponent(),
            $this->subplugin->get_filearea(),
            false,
            '',
            false
        );
        // This will possibly have problems until WR473938 is addressed.
        foreach ($files as $file) {
            // Remove files from updated/previous responses. We only want the current ones.
            if (!str_contains($text['withlinks'], $file->get_filename())) {
                unset($files[$file->get_pathnamehash()]);
            } else {
                $file->copy_content_to("{$this->tempresponsedir}/{$username}/{$file->get_filename()}");
            }
        }
    }

    /**
     * Move the csv from the csvexport dir to the dir we're actually going to zip up.
     *
     * @param string $from Path to move the file from
     * @param string $todir Path to move the file to
     * @return bool
     */
    public function move_file(string $from): bool {
        // We will also rename the csv.
        $to = "{$this->tempresponsedir}/{$this->responsename}.csv";
        if (copy($from, $to)) {
            return unlink($from);
        }
        return false;
    }

    /**
     * Zip everything up ready for export.
     *
     * @param array $csvs The csvs to be zipped
     */
    public function do_the_zip($allresponses, $courseshortname): void {

        if ($allresponses) {
            $filename = "mod_responses_{$courseshortname}.zip";
            $tempdir = $this->tempcoursedir;
        } else {
            $filename = $this->responsename . '.zip';
            $tempdir = $this->tempresponsedir;
        }

        // Remove any trailing slashes from the path.
        $rootpath = rtrim($tempdir, '\\/');

        // Initialize archive object.
        $zip = new ZipArchive();
        $zip->open("{$tempdir}/{$filename}", ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator.
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootpath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // Skip directories (they would be added automatically).
            if (!$file->isDir()) {
                // Get real and relative path for current file.
                $realpath = $file->getRealPath();
                $relativepath = substr($realpath, strlen($rootpath) + 1);

                // Add current file to archive.
                $zip->addFile($realpath, $relativepath);
            }
        }

        // Zip archive is created after closing the object.
        if ($zip->close()) {
            $filepath = "{$tempdir}/{$filename}";
            send_file($filepath, $filename, null, 0, false, true, '', true);
            $this->cleanup();
        }
    }

    /**
     * Recursively delete the tempdir and all its contents.
     *
     * @param string $path Path to the deleted
     */
    private function cleanup(): void {
        $it = new RecursiveDirectoryIterator($this->tempcoursedir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($this->tempcoursedir);
    }
}

