<?php
namespace Zettle\Admin;

defined("ABSPATH") or exit;

use WC_Settings_Page;
use Zettle\Plugin;

class Settings extends WC_Settings_Page
{
    /**
     * The plugin instance.
     */
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;

        $this->id    = "zettle";
        $this->label = "Zettle";

        parent::__construct();

        add_action("woocommerce_admin_field_zettle_button", [$this, "zettle_button"]);

        add_action("update_option_wc_zettle_webhook_url", function () {
            // When the webhook url updates, refresh the plugin connection.
            do_action("zettle_disconnected");
            do_action("zettle_connected");
        });
    }

    /**
     * Get own sections.
     */
    protected function get_own_sections(): array
    {
        return [
            ""            => __("General", "wc_zettle"),
            "inventories" => __("Inventories", "wc_zettle"),
            "advanced"    => __("Advanced", "wc_zettle"),
        ];
    }

    /**
     * Get the settings for the default section.
     */
    protected function get_settings_for_default_section(): array
    {
        $fields = [];
        $fields[] = [
            "title" => __("Zettle Integration Settings", "wc_zettle"),
            "type"  => "title",
        ];
        $fields[] = [
            "title" => "Integration Client ID",
            "type"  => "text",
            "id"    => "wc_zettle_client_id",
        ];
        $fields[] = [
            "title" => "Integration API-key",
            "type"  => "text",
            "id"    => "wc_zettle_client_secret",
        ];
        $fields[] = [
            "type" => "sectionend",
        ];
        return $fields;
    }

    /**
     * Get the settings for the inventories section.
     */
    protected function get_settings_for_inventories_section(): array
    {
        $fields = [];
        $fields[] = [
            "title" => __("Inventory", "wc_zettle"),
            "type"  => "title",
        ];
        $fields[] = [
            "title" => "Inventory UUID - Store",
            "type"  => "text",
            "id"    => "wc_zettle_inventory_store",
        ];
        $fields[] = [
            "title" => "Inventory UUID - Sold",
            "type"  => "text",
            "id"    => "wc_zettle_inventory_sold",
        ];
        $fields[] = [
            "type"  => "sectionend",
        ];
        return $fields;
    }

    /**
     * Get the settings for the advanced section.
     */
    protected function get_settings_for_advanced_section(): array
    {
        $fields = [];
        $fields[] = [
            "title" => __("Advanced", "wc_zettle"),
            "type"  => "title",
        ];
        $fields[] = [
            "title" => "Webhook URL",
            "type"  => "text",
            "id"    => "wc_zettle_webhook_url",
        ];
        $fields[] = [
            "type"  => "sectionend",
        ];
        return $fields;
    }

    /**
     * Save settings.
     */
    public function save()
    {
        $connected = $this->plugin->is_connected();

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
     * Settings form button.
     */
    public function zettle_button(array $options): void
    {
        ?><tr valign="top">
            <th scope="row" class="titledesc">
                <?php echo $options["title"] ?? ""; ?>
            </th>
            <td class="forminp forminp-button">
                <button
                    id="<?php esc_attr_e($options["id"] ?? ""); ?>"
                    name="<?php esc_attr_e($options["name"] ?? ""); ?>"
                    class="button">
                    <?php esc_html_e($options["text"] ?? ""); ?>
                </button>
            </td>
        </tr><?php
    }
}