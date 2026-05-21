<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class QuickEditSave
{
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

        if (! preg_match('/^[a-z][a-z-]*$/', $slug)) {
            return;
        }

        if ($this->topLevelPageExists($slug, $postId)) {
            return;
        }

        if ($this->quickLinkExists($slug, $postId)) {
            return;
        }

        update_post_meta($postId, QuickEditColumn::META_KEY, $slug);
    }

    private function topLevelPageExists(string $slug, int $excludePostId): bool
    {
        $page = get_page_by_path($slug, OBJECT, 'page');

        return $page instanceof \WP_Post
            && $page->post_parent === 0
            && $page->ID !== $excludePostId;
    }

    private function quickLinkExists(string $slug, int $excludePostId): bool
    {
        $query = new \WP_Query([
            'post_type'      => 'page',
            'meta_key'       => QuickEditColumn::META_KEY,
            'meta_value'     => $slug,
            'post__not_in'   => [$excludePostId],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        return $query->have_posts();
    }
}
