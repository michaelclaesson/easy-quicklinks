<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class SlugValidator
{
    public function validate(string $slug, int $excludePostId = 0): ?string
    {
        if ($slug === '') {
            return null;
        }

        if (! preg_match('/^[a-z][a-z-]*$/', $slug)) {
            return __('Only lowercase letters (a-z) and dashes (-) are allowed.', 'easy-quicklinks');
        }

        if ($this->topLevelPageExists($slug, $excludePostId)) {
            return __('A top-level page already uses this slug.', 'easy-quicklinks');
        }

        if ($this->quickLinkExists($slug, $excludePostId)) {
            return __('This quick link is already in use by another page.', 'easy-quicklinks');
        }

        return null;
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
            'post_status'    => 'any',
            'meta_key'       => QuickEditColumn::META_KEY,
            'meta_value'     => $slug,
            'post__not_in'   => $excludePostId > 0 ? [$excludePostId] : [],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);

        return $query->have_posts();
    }
}
