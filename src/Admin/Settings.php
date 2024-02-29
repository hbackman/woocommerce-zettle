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
        add_action("woocommerce_admin_field_zettle_status", [$this, "zettle_status"]);

        $this->check_for_rebuild_webhooks();

        if (false == $this->plugin->is_connected()) {
            $this->plugin->push_notice(
                "warning",
                "Zettle for WooCommerce is not enabled. To enable, <a href=\"{$this->get_url()}\">go to the plugin settings.</a>",
                true
            );
        }
    }

    /**
     * Get own sections.
     */
    protected function get_own_sections(): array
    {
        return [
            ""            => __("General", "wc_zettle"),
            "inventories" => __("Inventories", "wc_zettle"),
            "webhooks"    => __("Webhooks", "wc_zettle"),
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
     * Get the settings for the webhooks section.
     */
    protected function get_settings_for_webhooks_section(): array
    {
        $fields = [];
        $fields[] = [
            "title" => __("Webhooks", "wc_zettle"),
            "type"  => "title",
        ];
        $fields[] = [
            "title" => __("Refresh", "wc_zettle"),
            "type"  => "zettle_button",
            "text"  => "Refresh",
            "href"  => $this->get_url()."&rebuild_webhooks=1",
        ];
        $fields[] = [
            "title" => __("Webhooks", "wc_zettle"),
            "type"  => "webhooks",
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
 * Output the settings.
 */
    public function output()
    {
        global $current_section;
        switch ($current_section) {
            case "webhooks":
                parent::output();
                $this->webhooks_screen();
                return;
            default:
                parent::output();
        }
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
        return admin_url("admin.php?page=wc-settings&tab=".esc_attr($this->id));
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
            <td class="forminp">
                <a href="<?php echo $options["href"] ?? ""; ?>" class="button-primary woocommerce-save-button">
                    <?php echo $options["text"] ?? ""; ?>
                </a>
            </td>
        </tr><?php
    }

    /**
     * Display the webhooks table.
     */
    public function webhooks_screen(): void
    {
        $webhooks = $this->plugin->zettle()
            ->get_webhooks()
            ->json();

        ?><table class="wp-list-table widefat">
            <thead>
            <tr>
                <th scope="col">Destination</th>
                <th scope="col">Status</th>
                <th scope="col">Event Names</th>
                <th scope="col">Updated</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($webhooks as $webhook): ?>
                <tr>
                    <td><?php esc_html_e($webhook["destination"]); ?></td>
                    <td><?php esc_html_e($webhook["status"]); ?></td>
                    <td><?php esc_html_e($webhook["updated"]); ?></td>
                    <td><?php esc_html_e(implode(", ", $webhook["eventNames"])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table><?php
    }
}