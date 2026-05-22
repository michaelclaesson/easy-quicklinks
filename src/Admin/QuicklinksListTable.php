<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

if (! class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class QuicklinksListTable extends \WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Quick Link', 'easy-quicklinks'),
            'plural'   => __('Quick Links', 'easy-quicklinks'),
            'ajax'     => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Column definitions
    // -------------------------------------------------------------------------

    public function get_columns(): array
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'page_title' => __('Page', 'easy-quicklinks'),
            'quick_link' => __('Quick Link', 'easy-quicklinks'),
        ];
    }

    protected function get_sortable_columns(): array
    {
        return [
            'page_title' => ['title', false],
        ];
    }

    protected function get_bulk_actions(): array
    {
        return [
            'delete' => __('Delete', 'easy-quicklinks'),
        ];
    }

    protected function get_default_primary_column_name(): string
    {
        return 'page_title';
    }

    public function no_items(): void
    {
        esc_html_e('No quick links have been set yet.', 'easy-quicklinks');
    }

    // -------------------------------------------------------------------------
    // Data loading
    // -------------------------------------------------------------------------

    public function prepare_items(): void
    {
        $this->_column_headers = [
            $this->get_columns(),
            get_hidden_columns($this->screen),
            $this->get_sortable_columns(),
            $this->get_default_primary_column_name(),
        ];

        $perPage = $this->get_items_per_page('easy_quicklinks_per_page', 20);
        $paged   = $this->get_pagenum();
        $search  = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $orderby = isset($_GET['orderby']) && $_GET['orderby'] === 'title' ? 'title' : 'title';
        $order   = isset($_GET['order']) && strtolower((string) $_GET['order']) === 'desc' ? 'DESC' : 'ASC';

        $args = [
            'post_type'      => 'page',
            'post_status'    => ['publish', 'draft', 'pending', 'private', 'future'],
            'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key'     => QuickEditColumn::META_KEY,
                    'value'   => '',
                    'compare' => '!=',
                ],
            ],
            'posts_per_page' => $perPage,
            'paged'          => $paged,
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        if ($search !== '') {
            $args['s'] = $search;
        }

        $query = new \WP_Query($args);

        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page'    => $perPage,
        ]);

        $this->items = $query->posts;
    }

    // -------------------------------------------------------------------------
    // Column renderers
    // -------------------------------------------------------------------------

    public function column_default($item, $column_name): string
    {
        return '';
    }

    /**
     * @param \WP_Post $item
     */
    public function column_cb($item): string
    {
        return sprintf(
            '<input type="checkbox" name="post_ids[]" value="%d" />',
            $item->ID
        );
    }

    /**
     * @param \WP_Post $item
     */
    public function column_page_title($item): string
    {
        $editUrl = get_edit_post_link($item->ID) ?? '#';
        $pageUrl = (string) get_permalink($item->ID);
        $slug    = (string) get_post_meta($item->ID, QuickEditColumn::META_KEY, true);
        $title   = get_the_title($item->ID);

        if ($title === '') {
            $title = __('(no title)', 'easy-quicklinks');
        }

        $statusLabel = '';
        if ($item->post_status !== 'publish') {
            $statusObj   = get_post_status_object($item->post_status);
            $statusLabel = $statusObj
                ? ' &mdash; <span class="post-state">' . esc_html($statusObj->label) . '</span>'
                : '';
        }

        $deleteUrl = wp_nonce_url(
            add_query_arg(
                [
                    'page'    => 'easy-quicklinks',
                    'action'  => 'easy_quicklinks_delete',
                    'post_id' => $item->ID,
                ],
                admin_url('tools.php')
            ),
            'easy_quicklinks_delete_' . $item->ID
        );

        $rowActions = [
            'delete' => sprintf(
                '<a href="%s" class="submitdelete easy-quicklinks-confirm-delete" aria-label="%s">%s</a>',
                esc_url($deleteUrl),
                esc_attr(
                    /* translators: %s: page title */
                    sprintf(__('Delete quick link for ā€œ%sā€', 'easy-quicklinks'), $title)
                ),
                __('Delete quick link', 'easy-quicklinks')
            ),
        ];

        return sprintf(
            '<strong><a class="row-title" href="%s">%s</a>%s</strong>'
            . '<span class="eql-page-url">%s</span>'
            . '<span class="eql-primary-quick-link"><code>/%s</code></span>'
            . '%s',
            esc_url($editUrl),
            esc_html($title),
            $statusLabel,
            esc_html($pageUrl),
            esc_html($slug),
            $this->row_actions($rowActions)
        );
    }

    /**
     * @param \WP_Post $item
     */
    public function column_quick_link($item): string
    {
        $slug = (string) get_post_meta($item->ID, QuickEditColumn::META_KEY, true);
        $url  = trailingslashit(home_url()) . $slug;

        return sprintf(
            '<code class="eql-slug">/%s</code>'
            . '<a href="%s" class="eql-full-url" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_html($slug),
            esc_url($url),
            esc_html($url)
        );
    }

    protected function handle_row_actions($item, $column_name, $primary): string
    {
        return '';
    }
}
