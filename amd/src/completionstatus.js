define(['jquery', 'core/notification', 'core/fragment', 'core/templates'], function($, notification, fragment, template) {
    return {
        init: function (contextid) {
            // Start with the elements we have control over.
            var $response = $('div.response[data-context=' + contextid + ']');
            // Trail up to the highest point we know is still this module.
            var $module = $response.closest('.modtype_response');
            // Then dive back down to the completion.
            var $span = $module.find('span.autocompletion');

            if ($span && $span.length) {
                fragment.loadFragment('mod_response', 'completion', contextid).done(function(html, js) {
                    template.replaceNodeContents($span, html, js);
                }.bind(this)).fail(notification.exception);
            }
        }
    };
});