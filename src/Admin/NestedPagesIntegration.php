<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class NestedPagesIntegration
{
    public function register(): void
    {
        add_filter('nestedpages_post_title', [$this, 'appendQuickLinkIndicator'], 10, 2);
        add_action('admin_head', [$this, 'inlineStyles']);
    }

    public function inlineStyles(): void
    {
        echo '<style>.easy-quicklinks-np-indicator { margin-left: 6px; color: #888; font-weight: normal; }</style>';
    }

    public function appendQuickLinkIndicator(string $title, object $post): string
    {
        $slug = (string) get_post_meta($post->id, QuickEditColumn::META_KEY, true);

        if ($slug === '') {
            return $title;
        }

        $title .= '<span class="easy-quicklinks-np-indicator" title="'
            . esc_attr__('Quick Link', 'easy-quicklinks') . '">('
            . _x('quick link', 'Indicates a post has a quick link slug set in the quick edit', 'easy-quicklinks')
            . ': '
            . esc_html('/' . $slug)
            . ')</span>';

        return $title;
    }
}
