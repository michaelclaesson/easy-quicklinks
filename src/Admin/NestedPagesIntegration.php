<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class NestedPagesIntegration
{
    private SlugValidator $validator;

    public function __construct(?SlugValidator $validator = null)
    {
        $this->validator = $validator ?? new SlugValidator();
    }

    public function register(): void
    {
        add_filter('nestedpages_post_title', [$this, 'appendQuickLinkIndicator'], 10, 2);
        add_action('admin_head', [$this, 'inlineStyles']);
        add_filter('nestedpages_quickedit_custom_fields', [$this, 'addQuickLinkField'], 10, 3);
        add_action('wp_ajax_npquickEdit', [$this, 'validateQuickEditRequest'], 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter('update_post_metadata', [$this, 'interceptNpSlugSave'], 10, 5);
    }

    /**
     * Add the Quick Link field to the Nested Pages quick edit form.
     *
     * @param array        $fields    Existing custom fields.
     * @param \WP_Post_Type $postType  Current post type object.
     * @param string       $column    'left' or 'right'.
     */
    public function addQuickLinkField(array $fields, \WP_Post_Type $postType, string $column): array
    {
        if ($postType->name !== 'page' || $column !== 'left') {
            return $fields;
        }

        $fields[] = [
            'key'      => QuickEditColumn::META_KEY,
            'label'    => __('Quick Link', 'easy-quicklinks'),
            'type'     => 'text',
            'required' => false,
        ];

        return $fields;
    }

    public function validateQuickEditRequest(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $postType = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';

        if ($postType !== 'page') {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if ($postId < 1 || ! current_user_can('edit_page', $postId)) {
            return;
        }

        $fieldName = 'np_custom_' . QuickEditColumn::META_KEY;
        $slug = isset($_POST[$fieldName])
            ? sanitize_text_field(wp_unslash($_POST[$fieldName]))
            : '';

        $message = $this->validator->validate($slug, $postId);

        if ($message !== null) {
            wp_send_json([
                'status'  => 'error',
                'message' => $message,
            ]);
        }
    }

    /**
     * Intercept Nested Pages' automatic meta save for our slug key.
     *
     * Nested Pages calls update_post_meta() for every np_custom_* field without
     * format or conflict validation. The AJAX request is validated before Nested
     * Pages runs; this remains as a defensive guard for direct meta writes inside
     * that request.
     *
     * @param bool|null $check     Pre-flight value; non-null short-circuits the save.
     * @param int       $postId    Post being updated.
     * @param string    $metaKey   Meta key.
     * @param mixed     $metaValue Incoming value.
     * @param mixed     $prevValue Previous value passed to update_post_meta().
     */
    public function interceptNpSlugSave(
        ?bool $check,
        int $postId,
        string $metaKey,
        mixed $metaValue,
        mixed $prevValue
    ): ?bool {
        if ($metaKey !== QuickEditColumn::META_KEY) {
            return $check;
        }

        if (! defined('DOING_AJAX') || ! DOING_AJAX) {
            return $check;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';

        if ($action !== 'npquickedit') {
            return $check;
        }

        $slug = (string) $metaValue;

        // Empty value: remove any existing quick link and abort the meta write.
        if ($slug === '') {
            delete_post_meta($postId, QuickEditColumn::META_KEY);

            return false;
        }

        if ($this->validator->validate($slug, $postId) !== null) {
            return false;
        }

        return $check; // Allow Nested Pages to proceed with the save.
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        $screen = get_current_screen();

        if ($screen === null || strpos($screen->id, 'nestedpages') === false) {
            return;
        }

        $jsFile = EASY_QUICKLINKS_DIR . 'assets/js/nested-pages.js';

        wp_enqueue_script(
            'easy-quicklinks-nested-pages',
            EASY_QUICKLINKS_URL . 'assets/js/nested-pages.js',
            ['jquery', 'nestedpages'],
            hash('crc32b', (string) filemtime($jsFile)),
            true
        );

        wp_localize_script('easy-quicklinks-nested-pages', 'easyQuicklinksNestedPages', [
            'metaKey'        => QuickEditColumn::META_KEY,
            'indicatorLabel' => __('Quick Link', 'easy-quicklinks'),
            'indicatorText'  => _x('quick link', 'Indicates a post has a quick link slug set in the quick edit', 'easy-quicklinks'),
        ]);
    }

    public function inlineStyles(): void
    {
        echo '<style>.easy-quicklinks-np-indicator { margin-left: 6px; color: #888; font-weight: normal; }</style>';
    }

    public function appendQuickLinkIndicator(string $title, object $post): string
    {
        $slug = (string) get_post_meta($post->ID, QuickEditColumn::META_KEY, true);

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
