define(['jquery'], function($) {
    return function (selector, minwidth) {
        minwidth = minwidth || 320;
        var containers = $(selector).closest('.span4');
        $.each(containers, function(i, container) {
            if ($(container).outerWidth() < minwidth) {
                var newhome = $(container).closest('.row-fluid');
                $(newhome).find('.span8').toggleClass('span8 span12');
                $(container).detach();
                $(container).removeClass('span4').addClass('span8 offset1');
                $(container).insertAfter(newhome).wrap('<div class="row"></div>');
            }
        });
    };
});
