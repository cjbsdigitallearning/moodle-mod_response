require(['jquery'], function($) {
    $('#id_responsetype').on('change', function() {
        $('fieldset[id^=id_responsetype]').hide();
        $('fieldset[id=id_responsetype_' + $(this).val() + ']').show();
    }).trigger('change');
});