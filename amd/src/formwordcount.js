define(['jquery', 'mod_response/countwords'], function($, count_words) {
    return {
        init: function(selector) {
            $(selector + ' textarea').on('change', function() {
                var text = $(this).val();
                var msgel = $(this).closest('form').find('[data-message]');
                var msg = $(msgel).data('message');
                if (msg) {
                    $(msgel).html(msg.replace('{words}', count_words(text)));
                }
            }).trigger('change');
        }
    };
});
