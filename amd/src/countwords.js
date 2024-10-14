define(['jquery'], function($) {
    // Courtesy of https://github.com/kvz/locutus/blob/master/src/php/strings/strip_tags.js (PHPJS).
    /**
     * Strip HTML tags except allowed.
     *
     * @param {string} input
     * @param {string} allowed
     * @return {*}
     */
    function strip_tags (input, allowed) {
        allowed = (((allowed || '') + '').toLowerCase().match(/<[a-z][a-z0-9]*>/g) || []).join('');

        var tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi;
        var commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi;

        return input.replace(commentsAndPhpTags, '').replace(tags, function ($0, $1) {
            return allowed.indexOf('<' + $1.toLowerCase() + '>') > -1 ? $0 : '';
        });
    }

    /**
     * Convert HTML entities to text.
     *
     * @param {string} text
     * @return {*|jQuery}
     */
    function html_entity_decode(text) {
        return $('<textarea />').html(text).text();
    }

    return function(text) {
        // A port of Moodle's count_words function.
        // Make sure adjacent tags don't break the counting, like closing paragraphs.
        text = text.replace(/></g, '> <');
        // Strip tags.
        text = strip_tags(text);
        // Decode entities.
        text = html_entity_decode(text);
        // Replace underscores that are treated as word characters.
        text = text.replace(/_/g, ' ');
        // Replace shouldn't-be-word boundary characters.
        text = text.replace(/[\'"’-]/g, '');
        // Remove dots and commas from within numbers only.
        text = text.replace(/(?![0-9])[.,](?=[0-9])/g, "");
        // And count.
        var split = text.split(/\w\b/);
        return split.length - 1;
    };
});
