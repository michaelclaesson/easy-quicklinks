<?php

declare(strict_types=1);

namespace EasyQuicklinks\Frontend;

class Redirect
{
    private const META_KEY     = '_easy_quicklinks_slug';
    private const SLUG_PATTERN = '/^[a-z][a-z-]*$/';

    public function register(): void
    {
        add_action('template_redirect', [$this, 'handleRedirect']);
    }

    public function handleRedirect(): void
    {
        if (! is_404()) {
            return;
        }

        global $wp;

        $requestPath = trim($wp->request, '/');

        // Only act on single-segment paths (no slashes).
        if (empty($requestPath) || str_contains($requestPath, '/')) {
            return;
        }

        if (! preg_match(self::SLUG_PATTERN, $requestPath)) {
            return;
        }

        $query = new \WP_Query([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'meta_key'       => self::META_KEY,
            'meta_value'     => $requestPath,
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ]);

        if (! $query->have_posts()) {
            return;
        }

        $url = get_permalink($query->posts[0]->ID);

        if (! $url) {
            return;
        }

        wp_redirect($url, 301);
        exit;
    }
}
