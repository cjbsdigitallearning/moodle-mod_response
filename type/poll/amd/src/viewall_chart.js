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
                    xAxes: percentify.percentaxis()
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
