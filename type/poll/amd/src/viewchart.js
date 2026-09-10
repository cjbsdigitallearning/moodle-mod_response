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
 * Build and update the per-instance poll results chart.
 *
 * @module    responsetype_poll/viewchart
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'mod_response/Chart', 'mod_response/percentify'],
    function($, Chart, percentify) {
        return {
            make_window: function(cm) {
                if (!window.hasOwnProperty('responsetype_poll_chart')) {
                    // Preserve it at window level because of weird multi-workflows.
                    window.responsetype_poll_chart = {};
                }
                if (!window.responsetype_poll_chart.hasOwnProperty(cm)) {
                    window.responsetype_poll_chart[cm] = {
                        data: {},
                        colours: {},
                        tooltip: '{pc} selected',
                        chart: false
                    };
                }
            },
            set_data: function(cm, data) {
                this.make_window(cm);
                window.responsetype_poll_chart[cm].data = data;
            },
            set_colours: function(cm, colours) {
                this.make_window(cm);
                window.responsetype_poll_chart[cm].colours = colours;
            },
            set_tooltip: function(cm, tooltip) {
                this.make_window(cm);
                window.responsetype_poll_chart[cm].tooltip = tooltip;
            },
            init: function(cm, opts) {
                this.set_data(cm, opts.data);
                this.set_colours(cm, opts.colours);
                this.set_tooltip(cm, opts.tooltip);

                var set = window.responsetype_poll_chart[cm].data;
                var that = this;
                $('div.user-response[data-response=' + cm + ']').on('peer_change.response', function(e, data) {
                    that.draw(cm, data.display);
                });
                if (set.hasOwnProperty('all')) {
                    this.draw(cm, 'all');
                }
                if (!set.hasOwnProperty('all')) {
                    $('div.user-response[data-response=' + cm + '] .aggregate').hide();
                }
            },
            draw: function(cm, set) {
                var aggregate = window.responsetype_poll_chart[cm].data;
                var data = aggregate[set].data.slice(0);
                data = percentify.convert(data);

                var options = {
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: percentify.percentaxis(),
                        yAxes: percentify.percentlabels()
                    },
                    title: {
                        display: true,
                        text: aggregate[set].title
                    },
                    tooltips: {
                        callbacks: {
                            title: function(tooltipItems, data) {
                                var idx = tooltipItems[0].index;
                                return data.labels[idx];
                            },
                            label: function(tooltipItem) {
                                return window.responsetype_poll_chart[cm].tooltip.replace('{pc}', tooltipItem.xLabel + '%');
                            }
                        }
                    },
                    maintainAspectRatio: false
                };

                if (!window.responsetype_poll_chart[cm].chart) {
                    // Rendering fresh.
                    var pixelheight = percentify.getpixelheight(data.length);
                    $('div.user-response[data-response=' + cm + '] canvas:first-of-type').parent().height(pixelheight);
                    var ctx = $('div.user-response[data-response=' + cm + ']').find('canvas')[0].getContext('2d');
                    window.responsetype_poll_chart[cm].chart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: {
                            labels: aggregate[set].labels,
                            datasets: [{
                                data: data,
                                backgroundColor: window.responsetype_poll_chart[cm].colours
                            }]
                        },
                        options: options
                    });
                } else {
                    // We're just updating.
                    window.responsetype_poll_chart[cm].chart.options.title.text = aggregate[set].title;
                    for (var i in data) {
                        window.responsetype_poll_chart[cm].chart.data.datasets[0].data[i] = data[i];
                        window.responsetype_poll_chart[cm].chart.update();
                    }
                }
            }
        };
    }
);
