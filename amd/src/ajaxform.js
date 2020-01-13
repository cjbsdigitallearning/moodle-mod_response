define(
[
    'jquery',
    'core/notification',
    'core/fragment',
    'core/templates',
    'mod_response/precompletion',
    'mod_response/postcompletion'
],
function(
    $,
    notification,
    fragment,
    template,
    precompletion,
    postcompletion
) {
    return {
        init: function(selector) {
            var responseselector = selector || '.modtype_response .response .user-response.has-form';
            var that = this;

            that.bindForm(responseselector);
        },

        bindForm: function(selector) {
            var btnselector = 'input[type=submit], input[type=button], button:not(.plus-x-other)';

            // Make a hidden element for our form so we know which button was pressed.
            $(selector).find('form').each(function () {
                if ($(this).find('input[name=_button_pressed]').length === 0) {
                    $(this).append('<input type="hidden" name="_button_pressed">');
                }

                // Move the buttons from the form to the footer.
                $(selector + ' [id^=fgroup_id_buttonar]').each(function() {
                    var $response = $(this).closest('.card.response');
                    var content = $(this);
                    var destination = $response.find('.card-footer');
                    destination.empty().html(content);

                    // On hitting a button, push that to our hidden element.
                    $(destination).find(btnselector).on('click', function() {
                        var form = $(this).closest('.card.response').find('form');
                        form.find('input[name=_button_pressed]').val($(this).attr('name'));
                        form.trigger('submit');
                    });
                });
            });

            $(selector).each(function () {
                var $response = $(this).closest('.card.response');
                var hasform = $response.find('.user-response').hasClass('has-form');

                // Make sure we handle pre- and post-completion.
                precompletion();
                if (!hasform) {
                    var responseid = $response.find('.user-response[data-response]').data('response');
                    $response.find('.card-footer').find(btnselector).unbind();
                    postcompletion.init('#mod_response_form_' + responseid);
                }
            });

            // Rehandle the form submission.
            $(selector).find('form').unbind().on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Gather all the form data.
                var formdata = $(this).serializeArray();
                var params = {};
                // Find the button press in the serialised array and replace it with the real one.
                for (var i = 0; i < formdata.length; i++) {
                    if (formdata[i].name == '_button_pressed') {
                        var btnname = formdata[i].value;
                        params[btnname] = $(this).closest('.card.response').find('input[name="' + btnname + '"]').val();
                    } else {
                        params[encodeURIComponent(formdata[i].name)] = encodeURIComponent(formdata[i].value);
                    }
                }

                // Send it to the server and handle the response - retriggering all this for next time.
                var contextid = $(this).closest('.response[data-context]').data('context');
                var jsonformdata = JSON.stringify(params);
                fragment.loadFragment('mod_response', 'form', contextid, { jsonformdata: jsonformdata }).done(function(html, js) {
                    $('div.response[data-context=' + contextid + ']').closest('.card').find('.card-footer').empty();
                    template.replaceNode($('div.response[data-context=' + contextid + ']'), html, js);
                }.bind(this)).fail(notification.exception);
            });
        }
    };
});
