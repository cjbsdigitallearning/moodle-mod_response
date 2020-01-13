define(
    ['jquery', 'mod_response/Chart', 'mod_response/percentify'],
    function($, Chart, percentify) {
        return function (response, aggregate, colours, pclabel) {

            var set = '';
            if (aggregate.hasOwnProperty('group')) {
                set = 'group';
            } else if (aggregate.hasOwnProperty('all')) {
                set = 'all';
            } else {
                return;
            }

            var data = aggregate[set].data.slice(0);
            data = percentify.convert(data);

            var element = $('[data-response=' + response + '] .aggregate');
            element.height(percentify.getpixelheight(data.length));
            var ctx = element.append('<canvas></canvas>').find('canvas')[0].getContext('2d');
            return new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: aggregate[set].labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colours,
                    }]
                },
                options: {
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
                                return pclabel.replace('{pc}', tooltipItem.xLabel + '%');
                            }
                        }
                    },
                    maintainAspectRatio: false
                }
            });
        };
    }
);
