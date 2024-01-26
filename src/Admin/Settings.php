<?php
namespace Zettle\Admin;

defined("ABSPATH") or exit;

use WC_Settings_Page;
use WC_Admin_Settings;
use Zettle\Plugin;

class Settings extends WC_Settings_Page
{
    public function __construct()
    {
        $this->id    = "zettle";
        $this->label = "Zettle";

        parent::__construct();
    }

    /**
     * Get the settings configuration.
     */
    public function get_settings(): array
    {
        return apply_filters("woocommerce_{$this->id}_settings", [
            // -------------------------------------------------------------
            [
                "title" => __("Zettle Settings", "wc_zettle"),
                "type"  => "title",
                "id"    => "wc_zettle_auth",
            ],
            [
                "title" => "Client ID",
                "type"  => "text",
                "id"    => "wc_zettle_client_id",
            ],
            [
                "title" => "API-key",
                "type"  => "text",
                "id"    => "wc_zettle_client_secret",
            ],
            [
                "title" => "Access Token",
                "type"  => "textarea",
                "value" => get_option("zettle_token"),
                "custom_attributes" => [
                    "readonly" => "",
                    "rows"     => "5",
                ],
            ],
            //[
            //    "title" => __("Client ID", "wc_zettle"),
            //    "type"  => "text",
            //    "id"    => "wc_zettle_client_id",
            //],
            //[
            //    "title" => __("Client Secret", "wc_zettle"),
            //    "type"  => "text",
            //    "id"    => "wc_zettle_client_secret",
            //],
            [
                "type"  => "sectionend",
                "id"    => "wc_zettle_auth_end",
            ],
            // -------------------------------------------------------------
        ]);
    }

    /**
     * Save settings.
     */
    public function save()
    {
        $connected = Plugin::instance()->is_connected();

        parent::save();

        // After the settings has been saved, check which fields have been updated
        // and determine if Zettle has recently been connected or disconnected.

        $client_id = get_option("wc_zettle_client_id");
        $client_sc = get_option("wc_zettle_client_secret");

        if ($connected) {
            if (empty($client_id) || empty($client_sc))
                do_action("zettle_disconnected");
        }
        else {
            if (!empty($client_id) && !empty($client_sc))
                do_action("zettle_connected");
        }
    }

    /**
     *	Output the settings.
     */
    public function output()
    {
        WC_Admin_Settings::output_fields($this->get_settings());
    }
}