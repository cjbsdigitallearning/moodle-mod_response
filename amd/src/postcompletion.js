define(['jquery', 'core/notification', 'core/fragment', 'core/templates'], function($, notification, fragment, template) {
    return {
        init: function (selector) {
            var $form = $(selector).parent().find('.mod_response_postcompletion');
            $form.show();
            $form.find('input[type=submit], input[type=button]').on('click', function(e) {
                e.preventDefault();
                $form.find('input[type=submit], input[type=button]').removeClass('form-submit btn-primary')
                                                                    .addClass('btn btn-default');
                $(this).addClass('form-submit btn-primary');
                $(this).blur();
                $form.find('.profiles').hide();
                var peer = $(this).attr('name');
                $form.find('.profiles.' + peer).show();
                $form.closest('.user-response[data-response]').data('response_peer', peer)
                     .trigger('peer_change.response', { display: peer});
            });
            $form.find('input:last-of-type').trigger('click');

            $form.find('.profiles img').on('click', function() {
                var contextid = $(this).closest('form').find('input[name=context]').val();
                var userid = $(this).data('id');

                if (userid) {
                    fragment.loadFragment('mod_response', 'answer', contextid, {userid: userid}).done(function(html, js) {
                        template.replaceNodeContents($form.closest('.user-response').find('.user-container'), html, js);
                    }.bind(this)).fail(notification.exception);
                }
            });
        }
    };
});