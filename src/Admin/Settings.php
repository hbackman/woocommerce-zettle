<?php
namespace Zettle\Admin;

defined("ABSPATH") or exit;

use WC_Settings_Page;
use Zettle\Plugin;

class Settings extends WC_Settings_Page
{
    public function __construct()
    {
        $this->id    = "zettle";
        $this->label = "Zettle";

        parent::__construct();

        add_action("woocommerce_admin_field_button", [$this, "button"]);

        $this->check_for_rebuild_webhooks();
    }

    private function check_for_rebuild_webhooks()
    {
        if (! array_key_exists("rebuild_webhooks", $_GET))
            return;

        // The easiest way to build webhooks is to let the plugin trigger the
        // activation again..
        do_action("zettle_connected");

        // Redirect back to the regular url to avoid the user from refreshing
        // hooks by coming back to the page.
        wp_redirect($this->get_url());
    }

    /**
     * Retrieve the settings page url.
     */
    private function get_url(): string
    {
        return  admin_url("admin.php?page=wc-settings&tab=".esc_attr($this->id));
    }

    /**
     * Settings form button.
     */
    public function button(array $options): void
    {
        ?><tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $options["title"] ?? ""; ?>
            </th>
            <td class="forminp">
                <a href="<?php echo $options["href"] ?? ""; ?>" class="button-primary woocommerce-save-button">
                    <?php echo $options["text"] ?? ""; ?>
                </a>
            </td>
        </tr><?php
    }

    /**
     * Get the settings configuration.
     */
    public function get_settings(): array
    {
        //wp_redirect($redirect);
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
                "title" => "Refresh Webhooks",
                "type"  => "button",
                "text"  => "Refresh",
                "href"  => $this->get_url()."&rebuild_webhooks=1",
            ],
            [
                "title" => "Access Token",
                "type"  => "textarea",
                "value" => get_option("wc_zettle_token"),
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
}