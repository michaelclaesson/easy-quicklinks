<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class SlugGuard
{
    public function register(): void
    {
        add_filter('wp_unique_post_slug', [$this, 'preventQuicklinkSlugConflict'], 10, 6);
    }

    /**
     * If the resolved slug conflicts with a quick link owned by another page,
     * append a numeric suffix until it no longer conflicts.
     */
    public function preventQuicklinkSlugConflict(
        string $slug,
        int $postId,
        string $postStatus,
        string $postType,
        int $postParent,
        string $originalSlug
    ): string {
        // Only top-level pages can conflict with quick links.
        if ($postType !== 'page' || $postParent !== 0) {
            return $slug;
        }

        if (! $this->isQuicklinkSlug($slug, $postId)) {
            return $slug;
        }

        $suffix  = 2;
        $newSlug = $originalSlug . '-' . $suffix;

        while ($this->isQuicklinkSlug($newSlug, $postId)) {
            $suffix++;
            $newSlug = $originalSlug . '-' . $suffix;
        }

        return $newSlug;
    }

    private function isQuicklinkSlug(string $slug, int $excludePostId): bool
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
