<?php

if (! function_exists("dd")) {
    /**
     * Die and dump.
     */
    function dd(...$args): void
    {
        foreach ($args as $arg)
            echo "<pre>".print_r($arg, true)."</pre>";
        die();
    }
}

if (! function_exists("z_is_connected")) {
    /**
     * Check if Zettle is connected/
     */
    function z_is_connected(): bool
    {
        return get_option("zettle_token") && get_option("vendor_token");
    }
}

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

if (! function_exists("z_header_map")) {
    /**
     * Transforms a key value map into curl format.
     */
    function z_prepare_headers(array $headers): array
    {
        $output = [];

        foreach ($headers as $key => $value)
            $output[] = "$key: $value";

        return $output;
    }
}