<?php
namespace Zettle;

use WC_Product;

class StockEvents
{
    protected Plugin $plugin;

    /**
     * StockEvents constructor.
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;

        // Register hooks.
        add_action('woocommerce_variation_set_stock', [$this, 'on_variant_stock_change']);
        add_action('woocommerce_product_set_stock',   [$this, 'on_product_stock_change']);
    }

    /**
     * Handle WooCommerce variant stock changes.
     */
    public function on_variant_stock_change(WC_Product $variant): void
    {
        // The Zettle webhooks will trigger the stock change. The stock is already in
        // sync when this happens.
        if ($this->plugin->prevent_stock_update())
            return;

        $product = wc_get_product($variant->get_parent_id());

        $product_uuid = get_post_meta($product->get_id(), "zettle_uuid", true);
        $variant_uuid = get_post_meta($variant->get_id(), "zettle_uuid", true);

        // Ensure valid product uuid.
        if (! $product_uuid) {
            $this->plugin
                ->logger()
                ->notice("zettle_product_not_connected", $product->get_id());
            return;
        }

        // Ensure valid variant uuid.
        if (! $variant_uuid) {
            $this->plugin
                ->logger()
                ->notice("zettle_variant_not_connected", $variant->get_id());
            return;
        }

        // Attempt to update the zettle variant using both the uuid and the
        // new product stock.
        $this->plugin->zettle()->set_variant_stock(
            $product_uuid,
            $variant_uuid,
            $variant->get_stock_quantity()
        );
    }

    /**
     * Handle WooCommerce product stock changes.
     */
    public function on_product_stock_change(WC_Product $product): void
    {
        // The Zettle webhooks will trigger the stock change. The stock is already in
        // sync when this happens.
        if ($this->plugin->prevent_stock_update())
            return;

        $product_uuid = get_post_meta($product->get_id(), "zettle_uuid", true);

        // Ensure valid product id.
        if (! $product_uuid) {
            $this->plugin
                ->logger()
                ->notice("zettle_product_not_connected", $product->get_id());
            return;
        }

        // Attempt to update the zettle product. We do not have the variant
        // uuid here, but we can get that from the inventory endpoint.
        $this->plugin->zettle()->set_product_stock(
            $product_uuid,
            $product->get_stock_quantity()
        );
    }
}