<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class Ajax
{
    public function register(): void
    {
        add_action('wp_ajax_easy_quicklinks_validate_slug', [$this, 'validateSlug']);
    }

    public function validateSlug(): void
    {
        check_ajax_referer('easy_quicklinks_validate');

        if (! current_user_can('edit_pages')) {
            wp_send_json_error(
                ['message' => __('Unauthorized.', 'easy-quicklinks')],
                403
            );
        }

        $slug   = isset($_POST['slug']) ? sanitize_text_field(wp_unslash($_POST['slug'])) : '';
        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if ($slug === '') {
            wp_send_json_success(['available' => true]);
        }

        if (! preg_match('/^[a-z][a-z-]*$/', $slug)) {
            wp_send_json_error([
                'message' => __('Only lowercase letters (a-z) and dashes (-) are allowed.', 'easy-quicklinks'),
            ]);
        }

        // Check for top-level page conflict.
        $page = get_page_by_path($slug, OBJECT, 'page');

        if ($page instanceof \WP_Post && $page->post_parent === 0 && $page->ID !== $postId) {
            wp_send_json_error([
                'message' => __('A top-level page already uses this slug.', 'easy-quicklinks'),
            ]);
        }

        // Check for duplicate quicklink on another page.
        $query = new \WP_Query([
            'post_type'      => 'page',
            'meta_key'       => QuickEditColumn::META_KEY,
            'meta_value'     => $slug,
            'post__not_in'   => $postId ? [$postId] : [],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        if ($query->have_posts()) {
            wp_send_json_error([
                'message' => __('This quick link is already in use by another page.', 'easy-quicklinks'),
            ]);
        }

        wp_send_json_success([
            'available' => true,
            'message'   => __('Quick link is available.', 'easy-quicklinks'),
        ]);
    }
}
