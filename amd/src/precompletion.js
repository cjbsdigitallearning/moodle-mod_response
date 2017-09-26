define(['jquery'], function($) {
    return function () {
        $('.display-completion button.plus-x-other').unbind().on('click', function() {
            $('.display-completion .others, .display-completion button.plus-x-other').toggleClass('hide');
            $(this).closest('fieldset').get(0).scrollIntoView();
        });
    };
});