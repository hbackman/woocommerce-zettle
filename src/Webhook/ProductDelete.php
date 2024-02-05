<?php
namespace Zettle\Webhook;

use WC_Product;

class ProductDelete extends Webhook
{
    /**
     * The webhook event name.
     */
    public string $action = "ProductDeleted";

    /**
     * Handle the request.
     */
    public function handle(Request $request)
    {
        [$payload] = $this->unpack($request);

        // For now, we shouldn't really be deleting products. Instead, we will
        // just update the stock to zero.

        $uuid  = $payload["uuid"];

        $product_id = wc_get_product_id_by_zettle_uuid($uuid);
        $product    = wc_get_product($product_id);

        if (null == $product_id) {
            $this->plugin->logger()->error("zettle_product_id_not_found", $uuid);
            return;
        }

        // For simple products, just set the stock to zero.
        if ($product->get_type() == "simple") {
            // The product must be tracking stock.
            if (false == $product->managing_stock())
                return;

            wc_update_product_stock($product->get_id(), 0);
        }

        // For variable products, loop through each variation and set the stock
        // on each one (accounting for stock management).
        if ($product->get_type() == "variable") {
            /** @var \WC_Product_Variable $product */
            foreach ($product->get_available_variations("objects") as $variation) {
                // The variant must be tracking stock.
                if (false == $variation->managing_stock())
                    continue;

                wc_update_product_stock($variation->get_id(), 0);
            }
        }
    }
}