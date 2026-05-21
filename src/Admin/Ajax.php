<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class Ajax
{
    private SlugValidator $validator;

    public function __construct(?SlugValidator $validator = null)
    {
        $this->validator = $validator ?? new SlugValidator();
    }

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

        $message = $this->validator->validate($slug, $postId);

        if ($message !== null) {
            wp_send_json_error([
                'message' => $message,
            ]);
        }

        wp_send_json_success([
            'available' => true,
            'message'   => __('Quick link is available.', 'easy-quicklinks'),
        ]);
    }
}
