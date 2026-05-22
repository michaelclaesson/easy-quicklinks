<?php

declare(strict_types=1);

namespace EasyQuicklinks;

class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    private function __construct() {}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        $this->loadTextDomain();
        $this->registerHooks();
    }

    private function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'easy-quicklinks',
            false,
            dirname(plugin_basename(EASY_QUICKLINKS_FILE)) . '/languages'
        );
    }

    private function registerHooks(): void
    {
        // Runs on every request (admin, frontend, REST) to prevent pages from
        // claiming a slug that is already allocated as a quick link.
        (new Admin\SlugGuard())->register();

        if (is_admin()) {
            (new Admin\QuickEditColumn())->register();
            (new Admin\QuickEditSave())->register();
            (new Admin\Ajax())->register();
            (new Admin\NestedPagesIntegration())->register();
            (new Admin\QuicklinksPage())->register();
        } else {
            (new Frontend\Redirect())->register();
        }
    }
}
