<?php

declare(strict_types=1);

namespace EasyQuicklinks\Admin;

class QuicklinksPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_easy_quicklinks_list', [$this, 'ajaxList']);

        // Allow the per-page screen option to be saved.
        add_filter(
            'set_screen_option_easy_quicklinks_per_page',
            static fn ($status, $option, $value) => absint($value),
            10,
            3
        );
    }

    // -------------------------------------------------------------------------
    // Menu & screen options
    // -------------------------------------------------------------------------

    public function addMenuPage(): void
    {
        $hook = add_management_page(
            __('Quick Links', 'easy-quicklinks'),
            __('Quick Links', 'easy-quicklinks'),
            'edit_pages',
            'easy-quicklinks',
            [$this, 'render']
        );

        // Actions and screen options are only handled when the page actually loads.
        add_action("load-{$hook}", [$this, 'handleActions']);
        add_action("load-{$hook}", [$this, 'addScreenOptions']);
    }

    public function addScreenOptions(): void
    {
        add_screen_option('per_page', [
            'label'   => __('Quick links per page', 'easy-quicklinks'),
            'default' => 20,
            'option'  => 'easy_quicklinks_per_page',
        ]);
    }

    // -------------------------------------------------------------------------
    // Page render
    // -------------------------------------------------------------------------

    public function render(): void
    {
        if (! current_user_can('edit_pages')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'easy-quicklinks'));
        }

        $table = new QuicklinksListTable();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Quick Links', 'easy-quicklinks'); ?></h1>
            <hr class="wp-header-end">

            <?php $this->renderNotices(); ?>

            <form id="easy-quicklinks-form" method="get">
                <input type="hidden" name="page" value="easy-quicklinks" />
                <?php wp_nonce_field('easy_quicklinks_bulk', 'easy_quicklinks_bulk_nonce'); ?>

                <?php $table->search_box(__('Search pages', 'easy-quicklinks'), 'easy-quicklinks'); ?>

                <div id="easy-quicklinks-table-wrap">
                    <?php $table->display(); ?>
                </div>
            </form>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Action handling (single delete & bulk delete)
    // -------------------------------------------------------------------------

    public function handleActions(): void
    {
        if (! current_user_can('edit_pages')) {
            return;
        }

        // --- Single row delete (nonce URL, GET) ---
        if (
            isset($_GET['action'], $_GET['post_id']) &&
            $_GET['action'] === 'easy_quicklinks_delete'
        ) {
            $postId = absint($_GET['post_id']);

            if (
                $postId > 0 &&
                isset($_GET['_wpnonce']) &&
                wp_verify_nonce(
                    sanitize_text_field(wp_unslash($_GET['_wpnonce'])),
                    'easy_quicklinks_delete_' . $postId
                ) &&
                current_user_can('edit_page', $postId)
            ) {
                delete_post_meta($postId, QuickEditColumn::META_KEY);

                wp_safe_redirect(
                    add_query_arg(
                        ['page' => 'easy-quicklinks', 'deleted' => 1],
                        admin_url('tools.php')
                    )
                );
                exit;
            }

            // Nonce failed or no capability – redirect without deleting.
            wp_safe_redirect(add_query_arg(['page' => 'easy-quicklinks'], admin_url('tools.php')));
            exit;
        }

        // --- Bulk delete (GET form submission) ---
        $bulkAction = '';
        if (isset($_GET['action']) && $_GET['action'] !== '-1') {
            $bulkAction = sanitize_text_field(wp_unslash($_GET['action']));
        } elseif (isset($_GET['action2']) && $_GET['action2'] !== '-1') {
            $bulkAction = sanitize_text_field(wp_unslash($_GET['action2']));
        }

        if (
            $bulkAction === 'delete' &&
            isset($_GET['easy_quicklinks_bulk_nonce']) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_GET['easy_quicklinks_bulk_nonce'])),
                'easy_quicklinks_bulk'
            )
        ) {
            $postIds = isset($_GET['post_ids']) ? array_map('absint', (array) $_GET['post_ids']) : [];
            $count   = 0;

            foreach ($postIds as $id) {
                if ($id > 0 && current_user_can('edit_page', $id)) {
                    delete_post_meta($id, QuickEditColumn::META_KEY);
                    $count++;
                }
            }

            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'easy-quicklinks', 'deleted' => $count],
                    admin_url('tools.php')
                )
            );
            exit;
        }
    }

    // -------------------------------------------------------------------------
    // Admin notices
    // -------------------------------------------------------------------------

    private function renderNotices(): void
    {
        if (! isset($_GET['deleted'])) {
            return;
        }

        $deleted = absint($_GET['deleted']);

        if ($deleted > 0) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html(
                    sprintf(
                        /* translators: %d: number of deleted quick links */
                        _n('%d quick link deleted.', '%d quick links deleted.', $deleted, 'easy-quicklinks'),
                        $deleted
                    )
                )
            );
        }
    }

    // -------------------------------------------------------------------------
    // Asset enqueue
    // -------------------------------------------------------------------------

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'tools_page_easy-quicklinks') {
            return;
        }

        $jsFile  = EASY_QUICKLINKS_DIR . 'assets/js/quicklinks-list.js';
        $version = file_exists($jsFile)
            ? hash('crc32b', (string) filemtime($jsFile))
            : EASY_QUICKLINKS_VERSION;

        wp_enqueue_script(
            'easy-quicklinks-list',
            EASY_QUICKLINKS_URL . 'assets/js/quicklinks-list.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('easy-quicklinks-list', 'easyQuicklinksList', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('easy_quicklinks_list'),
            'i18n'    => [
                'confirmDelete' => __('Are you sure you want to remove this quick link? The page itself is not affected.', 'easy-quicklinks'),
                'loading'       => __('Loadingā€¦', 'easy-quicklinks'),
                'error'         => __('An error occurred. Please refresh the page and try again.', 'easy-quicklinks'),
            ],
        ]);

        wp_add_inline_style('wp-admin', '
            #easy-quicklinks-table-wrap { transition: opacity 0.15s; }
            #easy-quicklinks-table-wrap.eql-loading { opacity: 0.5; pointer-events: none; }
            .eql-page-url { display: block; color: #646970; font-size: 12px; margin: 2px 0 0; }
            .eql-primary-quick-link { display: none; font-size: 12px; margin: 4px 0 0; }
            .eql-slug { font-size: 12px; display: block; margin-bottom: 2px; }
            .eql-full-url { font-size: 12px; color: #2271b1; }
            @media screen and (max-width: 782px) {
                .eql-primary-quick-link { display: block; }
            }
        ');
    }

    // -------------------------------------------------------------------------
    // AJAX: paginated table refresh
    // -------------------------------------------------------------------------

    public function ajaxList(): void
    {
        check_ajax_referer('easy_quicklinks_list');

        if (! current_user_can('edit_pages')) {
            wp_die('', '', ['response' => 403]);
        }

        // $_GET is populated by jQuery $.get() – paged, s, orderby, order are
        // passed directly as query-string params by the front-end JS.
        $table = new QuicklinksListTable();
        $table->prepare_items();

        ob_start();
        $table->display();
        $html = (string) ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
}
