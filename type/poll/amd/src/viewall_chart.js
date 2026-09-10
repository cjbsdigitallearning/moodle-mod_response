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
 * Render the aggregate poll results chart on the "view all responses" page.
 *
 * @module    responsetype_poll/viewall_chart
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'mod_response/Chart', 'mod_response/percentify'], function($, Chart, percentify) {
    return function (aggregate, colours, pclabel) {
        var data = [];
        var labels = [];
        for (var i in aggregate) {
            data.push(aggregate[i].count);
            labels.push(aggregate[i].choice);
        }
        data = percentify.convert(data.slice(0));

        var element = $('#viewall_chart');
        element.height(percentify.getpixelheight(data.length));
        var ctx = element.append('<canvas></canvas>').find('canvas')[0].getContext('2d');
        return new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colours
                }]
            },
            options: {
                legend: {
                    display: false
                },
                maintainAspectRatio: false,
                scales: {
                    xAxes: percentify.percentaxis(),
                    yAxes: percentify.percentlabels()
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return pclabel.replace('{pc}', tooltipItem.xLabel + '%');
                        }
                    }
                }
            }
        });
    };
});
