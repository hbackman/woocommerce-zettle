<?php

if (! function_exists("z_plugin_enabled")) {
    /**
     * Check if a plugin is enabled.
     */
    function z_plugin_enabled(string $plugin): bool
    {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

        $is_enabled_locally = in_array($plugin, $active_plugins);
        $is_enabled_network =
            function_exists('is_plugin_active_for_network') &&
            is_plugin_active_for_network($plugin);

        return $is_enabled_locally || $is_enabled_network;
    }
}

if (! function_exists("z_plugin_disable")) {
    /**
     * Disable a plugin.
     */
    function z_plugin_disable(string $plugin): void
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins($plugin);
    }
}

if (! function_exists("wc_get_product_id_by_zettle_uuid")) {
    /**
     * Get product ID by Zettle UUID.
     */
    function wc_get_product_id_by_zettle_uuid(string $uuid): ?int
    {
        global $wpdb;

        $sql = <<<SQL
SELECT
    posts.ID
FROM $wpdb->posts as posts
JOIN $wpdb->postmeta as postmeta on
    postmeta.post_id = posts.ID
AND postmeta.meta_key = 'zettle_uuid'
AND postmeta.meta_value = %s
SQL;

        $id = $wpdb->get_var($wpdb->prepare($sql, $uuid));

        return $id;
    }
}

if (! function_exists("wc_get_product_by_zettle_uuid")) {
    /**
     * Get a product by Zettle UUID.
     *
     * @return WC_Product|WC_Product_Variation|WC_Product_Simple|false
     */
    function wc_get_product_by_zettle_uuid(string $uuid)
    {
        return wc_get_product(wc_get_product_id_by_zettle_uuid($uuid));
    }
}