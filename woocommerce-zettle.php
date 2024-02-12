<?php
/**
 * Plugin Name: WooCommerce Zettle
 * Plugin URI:
 * Description: A Zettle integration for WooCommerce.
 * Version: 0.0.0
 * Author: Maja Backman
 * Author URI: https://hbackman.com
 */
defined("ABSPATH") or exit;

use Zettle\Plugin;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ZETTLE_PLUGIN', __FILE__);

/*
 * Composer Autoload.
 */
require __DIR__ . "/vendor/autoload.php";

if (wcz_plugin_enabled("woocommerce/woocommerce.php") == false) {
    wcz_plugin_disable(ZETTLE_PLUGIN);

    add_action('admin_notices', function () {
        global $current_screen;

        if ($current_screen->parent_base != 'plugins')
            return;

        $error_notice = __("Zettle WooCommerce requires %s to be installed and enabled to function properly.", "wc_zettle");
        $error_notice = sprintf($error_notice, '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>');

        ?>
        <div class="error">
            <p><?php echo $error_notice; ?>
        </div>
        <?php
    });

    return;
}

Plugin::instance()->init();