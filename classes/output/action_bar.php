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

namespace mod_response\output;

use core\output\comboboxsearch;
use moodle_url;
use templatable;
use renderable;

/**
 * Renderable class for the action bar elements for mod_response response items.
 * 
 * Copied and altered from grade/classes/output/action_bar.php
 *
 * @package    mod_response
 * @copyright  2024 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_bar implements templatable, renderable {

    /** @var \context $context The context object. */
    protected $context;

    /** @var string $usersearch The content that the current user is looking for. */
    protected string $usersearch = '';

    /**
     * The class constructor.
     *
     * @param \context_module $context The context object.
     */
    public function __construct(\context_module $context) {
        $this->context = $context;
        $this->usersearch = optional_param('search', '', PARAM_NOTAGS);
    }

    /**
     * Returns the template for the action bar.
     *
     * @return string
     */
    public function get_template(): string {
        return 'mod_response/action_bar';
    }

    /**
     * Export the data for the mustache template.
     *
     * @param \renderer_base $output renderer to be used to render the action bar elements.
     * @return array
     * @throws \moodle_exception
     */
    public function export_for_template(\renderer_base $output): array {
        global $OUTPUT, $SESSION, $USER, $PAGE;
        $cmid = $this->context->instanceid;

        // If the user has the capability to view all responses, display the group selector (if applicable), the user selector
        // and the view mode selector (if applicable).
        if (has_capability('mod/response:viewall', $this->context)) {
            $cm = get_coursemodule_from_id('response', $cmid);
            $responsesrenderer = $PAGE->get_renderer('mod_response');

            $initialscontent = $responsesrenderer->initials_selector(
                $cm,
                $this->context,
                '/mod/response/viewall.php'
            );
            $initialselector = new comboboxsearch(
                false,
                $initialscontent->buttoncontent,
                $initialscontent->dropdowncontent,
                'initials-selector',
                'initialswidget',
                'initialsdropdown',
                $initialscontent->buttonheader,
            );
            $data['initialselector'] = $initialselector->export_for_template($output);

            $resetlink = new moodle_url('/mod/response/viewall.php', ['id' => $cmid]);
            $searchinput = $OUTPUT->render_from_template('core_user/comboboxsearch/user_selector', [
                'currentvalue' => $this->usersearch,
                'courseid' => $cm->course,
                'resetlink' => $resetlink->out(false),
                'group' => 0,
            ]);
            $searchdropdown = new comboboxsearch(
                true,
                $searchinput,
                null,
                'user-search dropdown d-flex',
                null,
                'usersearchdropdown overflow-auto',
                null,
                false,
            );
            $data['searchdropdown'] = $searchdropdown->export_for_template($output);

            if (!empty($SESSION->modresponse["filterfirstname-{$this->context->id}"]) ||
                    !empty($SESSION->modresponse["filterlastname-{$this->context->id}"]) ||
                    $this->usersearch) {
                $reset = new moodle_url('/mod/response/viewall.php', [
                    'id' => $cmid,
                    'search' => '',
                    'ifirst' => '',
                    'ilast' => '',
                    'userid' => '',
                ]);
                $data['pagereset'] = $reset->out(false);
            }
        }

        return $data;
    }
}
