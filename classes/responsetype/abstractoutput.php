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
 * Generic configuration API for response activity subplugins.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_response\responsetype;
use stdClass;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the response information API that subplugins are expected to follow.
 *
 * Any subplugin that defines a type of response for the response activity
 * will need to load data for displaying it to users, as well as loading users'
 * responses. All such subplugins should define a configuration class that extends
 *  this one.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstractoutput implements renderable, templatable {
    /** @var object Stores state about the current activity. */
    protected $data;

    /**
     * Creates an instance of the renderer, and accepts the overall
     * object containing the response state.
     *
     * @param object $data The general data of the response itself
     * @param object $subplugin Instance of the subplugin object to render
     */
    public function __construct($data, $subplugin) {
        $this->data = $data;
        $this->data->subplugin = $subplugin;
    }

    /**
     * Provides the data for the template.
     *
     * This object will have already received all the data for the
     * current activity, this identifies which is the relevant template
     * for the current state of the activity, and renders it.
     *
     * @param renderer_base $output The output renderer object
     * @return object $data An object containing all the template data
     */
    abstract public function export_for_template(renderer_base $output);

    /**
     * Provides the default chart colours as Moodle 3.2 uses (in
     * case we're on 3.1), ready made into an JSON-encoded string
     * for exporting to templates. Will use $CFG->chart_colorset
     * if defined (as per 3.2)
     *
     * @return string JSON-encoded string of an array of colours.
     */
    public function stringify_chart_colorset() {
        global $CFG;
        if (!empty($CFG->chart_colorset) && is_array($CFG->chart_colorset)) {
            return json_encode($CFG->chart_colorset);
        } else {
            return '["#f3c300","#875692","#f38400","#a1caf1","#be0032","#c2b280","#7f180d","#008856","#e68fac","#0067a5"]';
        }
    }
}
