<?php
namespace Zettle\Admin;

class Products
{
    /**
     * Products Admin constructor.
     */
    public function __construct()
    {
        if (get_option("wc_zettle_display_sync_status") == "yes") {
            add_filter("manage_product_posts_columns",       [$this, "add_product_list_columns"]);
            add_action("manage_product_posts_custom_column", [$this, "add_product_list_columns_content"]);
        }
    }

    /**
     * Adds Zettle-related columns to the products edit screen.
     */
    public function add_product_list_columns(array $columns): array
    {
        return array_merge($columns, [
            "zettle_status" => __("Zettle Sync", "wc_zettle"),
        ]);
    }

    /**
     * Outputs sync information for products in the edit screen.
     */
    public function add_product_list_columns_content(string $column): void
    {
        global $post;

        if ("zettle_status" != $column)
            return;

        $is_connected = (bool) get_post_meta($post->ID, "zettle_uuid");

        printf(
            '<div
                style="background-color: %s;"
                class="zettle-status-icon tips"
                data-tip="%s">
            </div>',
            $is_connected ? "#7ad03a" : "#dc3232",
            $is_connected ? "Connected" : "Disconnected"
        );
    }
}