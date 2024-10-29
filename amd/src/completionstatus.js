define(['core/notification', 'core/fragment', 'core/templates'], function(notification, fragment, template) {
    return {
        init: function (contextid) {
            // Start with the elements we have control over.
            const response = document.querySelector(`div.response[data-context="${contextid}"]`);
            // Trail up to the highest point we know is still this module.
            const module = response.closest('.modtype_response');
            let completion;
            let mode = 'module';
            if (module) {
                // Then dive back down to the completion.
                completion = module.querySelector('div.activity-completion');
            } else {
                mode = 'page';
                // Not inline on the course page, check we are on the activity page.
                const page = response.closest(`body.context-${contextid}.cm-type-response`);
                // Find the activity header with the completion criteria.
                completion = page.querySelector('.activity-header');
            }

            if (completion) {
                fragment.loadFragment('mod_response', 'completion', contextid, {mode})
                    .done(function(html, js) {
                        template.replaceNodeContents(completion, html, js);
                    })
                    .fail(notification.exception);
            }
        }
    };
});