define(['jquery'], function($) {
    return function () {
        $('.display-completion button.plus-x-other').unbind().on('click', function() {
            var $response = $(this).closest('.user-response');
            $response.find('.display-completion .others, .display-completion button.plus-x-other').toggleClass('hide');
        });
    };
});