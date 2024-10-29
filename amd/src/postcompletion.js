define(
    [
        'jquery',
        'core/notification',
        'core/fragment',
        'core/templates',
        'core/str'
    ], function($, notification, fragment, template, str) {
    return {
        init: function (selector) {
            $(selector + ' [id^=fgroup_id_buttonar').unbind().each(function() {
                var content = $(this);
                var destination = $(this).closest('.card.response').find('.card-footer');
                destination.html(content);
            });

            var $form = $(selector).closest('.card.response');
            $form.find('.card-footer input[type=submit], .card-footer input[type=button]').on('click', function(e) {
                e.preventDefault();
                $form.find('input[type=submit], input[type=button]').removeClass('btn-primary')
                                                                    .addClass('btn-default');
                $(this).removeClass('btn-default').addClass('btn-primary');
                $(this).blur();
                $form.find('.profiles').hide();
                var peer = $(this).data('completion');
                $form.find('.profiles.' + peer).show();
                $form.find('.user-response[data-response]').data('response_peer', peer)
                     .trigger('peer_change.response', { display: peer });
                if (peer === 'group') {
                    $(this).data('completion', 'all');
                    str.get_string('togglepeerresultsall', 'mod_response').then((label) => {
                        return $(this).val(label);
                    });
                } else {
                    $(this).data('completion', 'group');
                    str.get_string('togglepeerresultsgroup', 'mod_response').then((label) => {
                        return $(this).val(label);
                    });
                }
            });
            $form.find('.card-footer input:last-of-type').trigger('click');

            $form.find('.profiles .userresponse').unbind().on('click', function() {
                var contextid = $(this).closest('.card.response').find('form input[name=context]').val();
                var userid = $(this).data('id');

                if (userid) {
                    fragment.loadFragment('mod_response', 'answer', contextid, {userid: userid}).done(function(html, js) {
                        template.replaceNodeContents($form.find('.user-container'), html, js);
                    }.bind(this)).fail(notification.exception);
                }
            });
        }
    };
});