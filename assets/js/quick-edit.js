/* global inlineEditPost, jQuery */
(function ($) {
    'use strict';

    if (typeof inlineEditPost === 'undefined' || typeof window.easyQuicklinks === 'undefined') {
        return;
    }

    const config       = window.easyQuicklinks;
    const SLUG_PATTERN  = /^[a-z][a-z-]*$/;
    let debounceTimer   = null;
    let validationState = 'idle'; // 'idle' | 'pending' | 'valid' | 'invalid'

    // Override the inline edit open handler to pre-populate our field.
    const originalEdit = inlineEditPost.edit;

    inlineEditPost.edit = function (id) {
        originalEdit.apply(this, arguments);

        // Reset state for the newly opened row.
        validationState = 'idle';
        clearTimeout(debounceTimer);

        const postId  = typeof id === 'object' ? parseInt(this.getId(id), 10) : parseInt(id, 10);
        const editRow = document.getElementById('edit-' + postId);

        if (! editRow) {
            return;
        }

        const columnCell  = document.querySelector('#post-' + postId + ' .column-' + config.columnKey);
        const anchor      = columnCell ? columnCell.querySelector('a[data-slug]') : null;
        const currentSlug = anchor ? anchor.dataset.slug : '';
        const input       = editRow.querySelector('.easy-quicklinks-slug');

        if (input) {
            input.value                 = currentSlug;
            input.dataset.postId        = postId;
            input.dataset.originalSlug  = currentSlug;

            // Clear any message left from a previous open.
            const msg = input.closest('fieldset') && input.closest('fieldset').querySelector('.easy-quicklinks-message');

            if (msg) {
                msg.textContent = '';
                msg.className   = 'easy-quicklinks-message';
            }
        }
    };

    // Override save to block submission when the slug is invalid or still being checked.
    const originalSave = inlineEditPost.save;

    inlineEditPost.save = function (id) {
        if (validationState === 'invalid' || validationState === 'pending') {
            const postId  = typeof id === 'object' ? parseInt(this.getId(id), 10) : parseInt(id, 10);
            const editRow = document.getElementById('edit-' + postId);

            if (editRow) {
                const input = editRow.querySelector('.easy-quicklinks-slug');

                if (input) {
                    const $msg = $(input).closest('fieldset').find('.easy-quicklinks-message');

                    if (validationState === 'pending') {
                        $msg.text(config.i18n.validating)
                            .removeClass('easy-quicklinks-ok easy-quicklinks-error');
                    }
                    // For 'invalid' the message is already shown; just keep focus.
                    input.focus();
                }
            }

            return;
        }

        originalSave.apply(this, arguments);
    };

    // Live validation with debounce.
    $(document).on('input', '.easy-quicklinks-slug', function () {
        const $input = $(this);
        const slug   = $input.val().trim();
        const $msg   = $input.closest('fieldset').find('.easy-quicklinks-message');

        clearTimeout(debounceTimer);

        if (slug === '') {
            validationState = 'idle';
            $msg.text('').removeClass('easy-quicklinks-error easy-quicklinks-ok');

            return;
        }

        if (! SLUG_PATTERN.test(slug)) {
            validationState = 'invalid';
            $msg.text(config.i18n.invalidFormat)
                .removeClass('easy-quicklinks-ok')
                .addClass('easy-quicklinks-error');

            return;
        }

        const postId       = this.dataset.postId || 0;
        const originalSlug = this.dataset.originalSlug || '';

        // No need to check if the value hasn't changed.
        if (slug === originalSlug) {
            validationState = 'idle';
            $msg.text('').removeClass('easy-quicklinks-error easy-quicklinks-ok');

            return;
        }

        validationState = 'pending';
        $msg.text(config.i18n.validating).removeClass('easy-quicklinks-ok easy-quicklinks-error');

        debounceTimer = setTimeout(function () {
            $.post(config.ajaxUrl, {
                action:      'easy_quicklinks_validate_slug',
                _ajax_nonce: config.nonce,
                slug:        slug,
                post_id:     postId,
            }).done(function (response) {
                if (response.success) {
                    validationState = 'valid';
                    $msg.text(config.i18n.slugAvailable)
                        .removeClass('easy-quicklinks-error')
                        .addClass('easy-quicklinks-ok');
                } else {
                    validationState = 'invalid';
                    $msg.text(response.data.message || config.i18n.slugTaken)
                        .removeClass('easy-quicklinks-ok')
                        .addClass('easy-quicklinks-error');
                }
            });
        }, 400);
    });

})(jQuery);
