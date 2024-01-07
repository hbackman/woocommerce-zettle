<?php
namespace Zettle;

defined("ABSPATH") or exit;

use Zettle\Admin\Settings;

class Plugin
{
    /**
     * The plugin instance.
     */
    private static $instance;

    /**
     * Initialize the plugin.
     */
    public function init(): void
    {
        $zettle = new Zettle();
        $zettle->get_access_token();

        add_filter("woocommerce_get_settings_pages", function ($a) {
            new Settings();

            return $a;
        });
    }

    /**
     * Retrieve the plugin instance.
     */
    public static function instance(): static
    {
        if (self::$instance === null)
            self::$instance = new self();

        return self::$instance;
    }
}