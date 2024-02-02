<?php
namespace Zettle\Webhook;

use Zettle\Support\Arr;

class InventoryBalanceChanged extends Webhook
{
    /**
     * The webhook event name.
     */
    public string $action = "InventoryBalanceChanged";

    /**
     * Handle the request.
     */
    public function handle(Request $request)
    {
        [$payload] = $this->unpack($request);

        // Zettle will send a list of inventory "events", each containing the stock
        // change data (balanceAfter). They will also send "balanceBefore". This is
        // not something that WooCommerce supports, so it can be skipped.

        foreach (Arr::get($payload, "balanceAfter", []) as $balance) {
            $product_uuid  = Arr::get($balance, "productUuid");
            $variant_uuid  = Arr::get($balance, "variantUuid");
            $variant_stock = Arr::get($balance, "balance");

            // Zettle will send both a product id and a variant id even if it is a
            // simple product. Load the WooCommerce product and check it there.

            $product = wc_get_product_by_zettle_uuid($product_uuid);
            $variant = wc_get_product_by_zettle_uuid($variant_uuid);

            if (! $product) {
                $this->error("zettle_product_id_not_found", $product_uuid);
                continue;
            }

            if ($product->get_type() == "simple") {
                // If the product does not have stock management enabled, then
                // it needs to be enabled before the stock is set.
                if (false == $product->managing_stock()) {
                    $product->set_manage_stock(true);
                    $product->save();
                }

                wc_update_product_stock($product, $variant_stock);
            }

            if ($product->get_type() == "variable") {
                // If the variant does not have stock management enabled, then
                // it needs to be enabled before the stock is set.
                if (false == $variant->managing_stock()) {
                    $variant->set_manage_stock(true);
                    $variant->save();
                }

                wc_update_product_stock($variant, $variant_stock);
            }

            // No other types than the ones above are supported by this plugin
            // yet. I don't think Zettle supports external or grouped products.
        }
    }
}