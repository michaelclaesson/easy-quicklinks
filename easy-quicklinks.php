<?php
/**
 * Plugin Name:       Easy Quicklinks
 * Plugin URI:
 * Description:       Adds support for top level quick link redirect in the post quick edit.
 * Version:           1.0.0
 * Author:
 * Author URI:
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easy-quicklinks
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Requires PHP:      8.3
 */

declare(strict_types=1);

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

define('EASY_QUICKLINKS_VERSION', '1.0.0');
define('EASY_QUICKLINKS_FILE', __FILE__);
define('EASY_QUICKLINKS_DIR', plugin_dir_path(__FILE__));
define('EASY_QUICKLINKS_URL', plugin_dir_url(__FILE__));

if (file_exists(EASY_QUICKLINKS_DIR . 'vendor/autoload.php')) {
    require_once EASY_QUICKLINKS_DIR . 'vendor/autoload.php';
}

add_action('plugins_loaded', function (): void {
    EasyQuicklinks\Plugin::getInstance()->boot();
});
