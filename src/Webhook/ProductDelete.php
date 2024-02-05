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

        $uuid = $payload["uuid"];
        $prid = wc_get_product_id_by_zettle_uuid($uuid);

        if (null == $prid) {
            $this->plugin->logger()->error("zettle_product_id_not_found", $uuid);
            return;
        }

        wc_update_product_stock($prid, 0);
    }
}