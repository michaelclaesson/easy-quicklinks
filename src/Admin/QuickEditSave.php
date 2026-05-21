<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class QuickEditSave
{
    private SlugValidator $validator;

    public function __construct(?SlugValidator $validator = null)
    {
        $this->validator = $validator ?? new SlugValidator();
    }

    public function register(): void
    {
        add_action('save_post_page', [$this, 'save'], 10, 2);
    }

    public function save(int $postId, \WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($postId)) {
            return;
        }

        if (! isset($_POST['easy_quicklinks_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['easy_quicklinks_nonce'])),
            'easy_quicklinks_quick_edit'
        )) {
            return;
        }

        if (! current_user_can('edit_page', $postId)) {
            return;
        }

        $slug = isset($_POST['easy_quicklinks_slug'])
            ? sanitize_text_field(wp_unslash($_POST['easy_quicklinks_slug']))
            : '';

        if ($slug === '') {
            delete_post_meta($postId, QuickEditColumn::META_KEY);

            return;
        }

        if ($this->validator->validate($slug, $postId) !== null) {
            return;
        }

        update_post_meta($postId, QuickEditColumn::META_KEY, $slug);
    }
}
