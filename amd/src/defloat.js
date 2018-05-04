define(['jquery'], function($) {
    return function (selector, minwidth) {
        minwidth = minwidth || 320;
        var containers = $(selector).closest('.col-md-4');
        $.each(containers, function(i, container) {
            if ($(container).outerWidth() < minwidth) {
                var newhome = $(container).closest('.row');
                $(newhome).find('.col-md-8').toggleClass('col-md-8 col-md-12');
                $(container).detach();
                $(container).removeClass('.col-md-4').addClass('col-md-8 offset-md-1');
                $(container).insertAfter(newhome).wrap('<div class="row"></div>');
            }
        });
    };
});
