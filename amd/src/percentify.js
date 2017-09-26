define([], function() {
    var percentify = {};

    percentify.convert = function (data) {
        // Make sure we take a copy of the data rather than altering the real data.
        data = data.slice(0);

        // Work out the total for which each is a percentage.
        var total = 0;
        for (var i = 0; i < data.length; i++) {
            total += data[i];
        }
        // Convert everything.
        if (total) {
            for (i = 0; i < data.length; i++) {
                data[i] = Math.round(data[i] / total * 100);
            }
        }
        return data;
    };

    percentify.percentaxis = function() {
        return [{
            ticks: {
                min: 0,
                max: 100,
                callback: function(value) {
                    return value + '%';
                }
            }
        }];
    };

    return percentify;
});
