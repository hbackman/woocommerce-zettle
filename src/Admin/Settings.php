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

        add_filter("woocommerce_settings_tabs_array",      [$this, "add_settings_page"], 20);
        add_action("woocommerce_settings_{$this->id}",     [$this, "output"]);
        add_action("woocommerce_admin_field_zettle_login", [$this, "zettle_login"]);

        parent::__construct();

        $this->check_for_zettle_return();
        $this->check_for_disconnect();
    }

    /**
     * Check for if the user was returned from the auth service.
     */
    private function check_for_zettle_return(): void
    {
        $was_returned =
            array_key_exists("zettle_token", $_GET) &&
            array_key_exists("vendor_token", $_GET);

        if (! $was_returned)
            return;

        update_option("zettle_token", $_GET["zettle_token"] ?? null);
        update_option("vendor_token", $_GET["vendor_token"] ?? null);

        // Invoke events.

        do_action("zettle_connected");

        // Redirect a final time.

        $redirect = admin_url("admin.php?page=wc-settings&tab=".esc_attr($this->id));

        wp_redirect($redirect);
    }

    /**
     * Check if the user clicked the disconnect button.
     */
    private function check_for_disconnect(): void
    {
        $has_param = array_key_exists("disconnect", $_GET);

        if (! $has_param)
            return;

        update_option("zettle_token", null);
        update_option("vendor_token", null);

        // Invoke events.

        do_action("zettle_disconnected");

        // Redirect

        $redirect = admin_url("admin.php?page=wc-settings&tab=".esc_attr($this->id));

        wp_redirect($redirect);
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
                "title" => "Token",
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
     *	Output the settings
     */
    public function output()
    {
        global $hide_save_button;

        $hide_save_button = true;

        WC_Admin_Settings::output_fields($this->get_settings());
    }

    /**
     * Print a zettle login button.
     */
    public function zettle_login(array $options)
    {
        $redirect_uri = site_url("/wp-admin/admin.php?page=wc-settings&tab=zettle");
        $customer_uid = Plugin::instance()->get_customer_id();

        $link = sprintf(
            "http://localhost:5010/connect?customer_id=%s&redirect_uri=%s",
            urlencode($customer_uid),
            urlencode($redirect_uri)
        );

        $disconnect = $redirect_uri."&disconnect=1";

        ?><tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $options["title"] ?? ""; ?>
            </th>
            <td class="forminp">
                <?php if (z_is_connected()): ?>
                    Connected (<a href="<?php echo $disconnect; ?>">Disconnect</a>)
                <?php else: ?>
                    <a href="<?php echo $link; ?>" class="button-primary woocommerce-save-button">
                        Connect
                    </a>
                <?php endif; ?>
            </td>
        </tr><?php
    }
}