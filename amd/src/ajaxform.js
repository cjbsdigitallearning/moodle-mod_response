define(['jquery', 'core/notification', 'core/fragment', 'core/templates'], function($, notification, fragment, template) {
    return {
        init: function(selector) {
            var responseselector = selector || '.modtype_response .response .user-response.has-form';
            var btnselector = 'input[type=submit], input[type=button], button:not(.plus-x-other)';

            // Add a hidden element to each of our forms (without duplication).
            $(responseselector).find('form').each(function() {
                if ($(this).find('input[name=_button_pressed]').length === 0) {
                    $(this).append('<input type="hidden" name="_button_pressed">');
                }
            });

            // On hitting a button, push that to our hidden element.
            $(responseselector).find(btnselector).on('click', function() {
                $(this).closest('form').find('input[name=_button_pressed]').val($(this).attr('name'));
            });

            // Then grab this as part of the serialise process.
            $(responseselector).find('form').on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var formdata = $(this).serializeArray();
                var params = {};
                // Find the button press in the serialised array and replace it with the real one.
                for (var i = 0; i < formdata.length; i++) {
                    if (formdata[i].name == '_button_pressed') {
                        var btnname = formdata[i].value;
                        params[btnname] = $(this).find('input[name="' + btnname + '"]').val();
                    } else {
                        params[formdata[i].name] = formdata[i].value;
                    }
                }

                var contextid = $(this).closest('.response[data-context]').data('context');
                var jsonformdata = JSON.stringify(params);
                fragment.loadFragment('mod_response', 'form', contextid, { jsonformdata: jsonformdata }).done(function(html, js) {
                    template.replaceNode($('div.response[data-context=' + contextid + ']'), html, js);
                }.bind(this)).fail(notification.exception);
            });
        }
    };
});
