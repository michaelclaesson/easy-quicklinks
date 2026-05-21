<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class QuickEditColumn
{
    public const COLUMN_KEY = 'easy_quicklinks_slug';
    public const META_KEY   = '_easy_quicklinks_slug';

    public function register(): void
    {
        add_filter('manage_pages_columns', [$this, 'addColumn']);
        add_action('manage_pages_custom_column', [$this, 'renderColumn'], 10, 2);
        add_action('quick_edit_custom_box', [$this, 'renderQuickEditField'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addColumn(array $columns): array
    {
        $columns[self::COLUMN_KEY] = __('Quick Link', 'easy-quicklinks');

        return $columns;
    }

    public function renderColumn(string $column, int $postId): void
    {
        if ($column !== self::COLUMN_KEY) {
            return;
        }

        $slug = (string) get_post_meta($postId, self::META_KEY, true);

        if ($slug === '') {
            return;
        }

        $url = trailingslashit(home_url()) . $slug;

        printf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" data-slug="%s">%s</a>',
            esc_url($url),
            esc_attr($slug),
            esc_html($url)
        );
    }

    public function renderQuickEditField(string $column, string $postType): void
    {
        if ($column !== self::COLUMN_KEY || $postType !== 'page') {
            return;
        }

        wp_nonce_field('easy_quicklinks_quick_edit', 'easy_quicklinks_nonce');
        ?>
        <fieldset class="inline-edit-col-right easy-quicklinks-fieldset">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php esc_html_e('Quick Link', 'easy-quicklinks'); ?></span>
                    <span class="input-text-wrap">
                        <input
                            type="text"
                            name="easy_quicklinks_slug"
                            class="easy-quicklinks-slug"
                            value=""
                            placeholder="<?php esc_attr_e('e.g. testskolan', 'easy-quicklinks'); ?>"
                        />
                    </span>
                </label>
                <p class="description">
                    <?php esc_html_e('Lowercase letters and dashes only. Creates a top-level redirect to this page.', 'easy-quicklinks'); ?>
                </p>
                <p class="easy-quicklinks-message" aria-live="polite"></p>
            </div>
        </fieldset>
        <?php
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'edit.php') {
            return;
        }

        $screen = get_current_screen();

        if ($screen === null || $screen->post_type !== 'page') {
            return;
        }

        $jsFile = EASY_QUICKLINKS_DIR . 'assets/js/quick-edit.js';
        $version = hash('crc32b', (string) filemtime($jsFile));

        wp_enqueue_script(
            'easy-quicklinks-admin',
            EASY_QUICKLINKS_URL . 'assets/js/quick-edit.js',
            ['jquery', 'inline-edit-post'],
            $version,
            true
        );

        wp_localize_script('easy-quicklinks-admin', 'easyQuicklinks', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('easy_quicklinks_validate'),
            'columnKey' => self::COLUMN_KEY,
            'i18n'      => [
                'invalidFormat' => __('Only lowercase letters (a-z) and dashes (-) are allowed.', 'easy-quicklinks'),
                'slugTaken'     => __('This quick link is already in use.', 'easy-quicklinks'),
                'slugAvailable' => __('Quick link is available.', 'easy-quicklinks'),
                'validating'    => __('Checking availability', 'easy-quicklinks'),
            ],
        ]);

        wp_add_inline_style('wp-admin', '
            .easy-quicklinks-message { margin: 4px 0 0; font-size: 12px; min-height: 1.4em; }
            .easy-quicklinks-error { color: #d63638; }
            .easy-quicklinks-ok { color: #00a32a; }
        ');
    }
}
