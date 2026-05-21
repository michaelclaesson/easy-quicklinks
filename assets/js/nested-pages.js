/* global easyQuicklinksNestedPages, jQuery */
(function ($) {
    'use strict';

    if (typeof window.easyQuicklinksNestedPages === 'undefined') {
        return;
    }

    const config = window.easyQuicklinksNestedPages;

    // Build the same quick-link badge PHP adds on the initial Nested Pages render.
    function indicator(slug) {
        return $('<span />', {
            class: 'easy-quicklinks-np-indicator',
            title: config.indicatorLabel,
            text: '(' + config.indicatorText + ': /' + slug + ')',
        });
    }

    // Nested Pages rewrites the row title after quick edit saves, which removes our
    // server-rendered badge. Re-apply it from the saved custom field value.
    function updateRow(data) {
        if (! data || ! data.post_id || typeof data.post_title === 'undefined') {
            return;
        }

        const $row = $('#menuItem_' + data.post_id).children('.row').find('.row-inner').first();

        if (! $row.length) {
            return;
        }

        const fieldName = 'np_custom_' + config.metaKey;
        const slug = data[fieldName] ? $.trim(data[fieldName]) : '';
        const $title = $row.find('.page-title .title').first();

        $title.find('.easy-quicklinks-np-indicator').remove();

        if (slug !== '') {
            $title.append(' ').append(indicator(slug));
        }
    }

    // Listen globally because Nested Pages owns the quick edit request and does not
    // expose a more specific completion event for custom field integrations.
    $(document).ajaxSuccess(function (event, xhr, settings) {
        if (! settings || typeof settings.data !== 'string' || settings.data.indexOf('action=npquickEdit') === -1) {
            return;
        }

        const response = xhr.responseJSON;

        if (! response || response.status !== 'success') {
            return;
        }

        // Let Nested Pages finish its own row update first, then repair our badge.
        window.setTimeout(function () {
            updateRow(response.post_data);
        }, 0);
    });

})(jQuery);
