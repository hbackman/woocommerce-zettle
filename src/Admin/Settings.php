<?php
namespace Zettle\Admin;

defined("ABSPATH") or exit;

use WC_Settings_Page;
use WC_Admin_Settings;

class Settings extends WC_Settings_Page
{
    public function __construct()
    {
        $this->id    = "zettle";
        $this->label = "Zettle";

        add_filter("woocommerce_settings_tabs_array",      [$this, "add_settings_page"], 20);
        add_action("woocommerce_settings_{$this->id}",     [$this, "output"]);
        add_action("woocommerce_admin_field_zettle_login", [$this, "zettle_login"]);

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
                "title" => __("Connection", "wc_zettle"),
                "type"  => "zettle_login",
            ],
            [
                "title" => __("Client ID", "wc_zettle"),
                "type"  => "text",
                "id"    => "wc_zettle_client_id",
            ],
            [
                "title" => __("Client Secret", "wc_zettle"),
                "type"  => "text",
                "id"    => "wc_zettle_client_secret",
            ],
            [
                "type"  => "sectionend",
                "id"    => "wc_zettle_auth_end",
            ],
            // -------------------------------------------------------------
        ]);
    }


    /**
     *	Output the settings
     */
    public function output()
    {
        // global $hide_save_button;
        //
        // $hide_save_button = true;

        WC_Admin_Settings::output_fields($this->get_settings());
    }

    /**
     * Print a zettle login button.
     */
    public function zettle_login(array $options)
    {
        $link = sprintf(
            "https://oauth.zettle.com/authorize?response_type=code&scope=%s&client_id=%s&redirect_uri=%s&state=%s",
            "READ:PRODUCT",
            "96aeb3b6-e8bc-4adf-8468-76a1ab787601",
            "https://httpbin.org/get",
            rand()
        );

        ?><tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $options["title"] ?? ""; ?>
            </th>
            <td class="forminp">
                <a href="<?php echo $link; ?>" class="button-primary woocommerce-save-button">
                    Connect
                </a>
            </td>
        </tr><?php
    }
}