/* global easyQuicklinksList, jQuery */
(function ($) {
    'use strict';

    var WRAP = '#easy-quicklinks-table-wrap';

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    function getUrlParam(url, param) {
        var match = url.match(new RegExp('[?&]' + param + '=([^&#]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    /** Collect shared params that must survive pagination / sort changes. */
    function currentParams() {
        var params = {};

        // Search term (stays in the DOM even after AJAX table refresh).
        var s = $('#easy-quicklinks-form input[name="s"]').val();
        if (s) {
            params.s = s;
        }

        return params;
    }

    // ------------------------------------------------------------------
    // AJAX table fetch
    // ------------------------------------------------------------------

    function fetchPage(extra) {
        var $wrap = $(WRAP);
        $wrap.addClass('eql-loading');

        var data = $.extend(
            {
                action: 'easy_quicklinks_list',
                nonce:  easyQuicklinksList.nonce,
            },
            currentParams(),
            extra
        );

        $.get(easyQuicklinksList.ajaxUrl, data)
            .done(function (response) {
                if (response && response.success) {
                    $wrap.removeClass('eql-loading').html(response.data.html);
                } else {
                    $wrap.removeClass('eql-loading');
                    // eslint-disable-next-line no-alert
                    window.alert(easyQuicklinksList.i18n.error);
                }
            })
            .fail(function () {
                $wrap.removeClass('eql-loading');
                // eslint-disable-next-line no-alert
                window.alert(easyQuicklinksList.i18n.error);
            });
    }

    // ------------------------------------------------------------------
    // Event delegation (works after table HTML is replaced by AJAX)
    // ------------------------------------------------------------------

    // Pagination links (first / prev / next / last / numbered pages).
    $(document).on('click', WRAP + ' .tablenav-pages a', function (e) {
        e.preventDefault();
        var href  = $(this).attr('href') || '';
        var paged = parseInt(getUrlParam(href, 'paged') || '1', 10);
        fetchPage({ paged: paged });
    });

    // "Go" button on the current-page input.
    $(document).on('click', WRAP + ' .tablenav-pages input[type="submit"]', function (e) {
        e.preventDefault();
        var paged = parseInt($(this).closest('.tablenav-pages').find('.current-page').val(), 10) || 1;
        fetchPage({ paged: paged });
    });

    // Enter on the page-number text box.
    $(document).on('keydown', WRAP + ' .tablenav-pages .current-page', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            var paged = parseInt($(this).val(), 10) || 1;
            fetchPage({ paged: paged });
        }
    });

    // Sortable column headers.
    $(document).on('click', WRAP + ' th.sortable a, ' + WRAP + ' th.sorted a', function (e) {
        e.preventDefault();
        var href    = $(this).attr('href') || '';
        var orderby = getUrlParam(href, 'orderby') || 'title';
        var order   = getUrlParam(href, 'order')   || 'asc';
        fetchPage({ paged: 1, orderby: orderby, order: order });
    });

    // ------------------------------------------------------------------
    // Delete confirmation (row action)
    // ------------------------------------------------------------------

    $(document).on('click', '.easy-quicklinks-confirm-delete', function (e) {
        // eslint-disable-next-line no-alert
        if (!window.confirm(easyQuicklinksList.i18n.confirmDelete)) {
            e.preventDefault();
        }
    });

}(jQuery));
