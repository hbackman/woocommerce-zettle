<?php
namespace Zettle\Webhook;

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

        $uuid  = $payload["uuid"];

        $product_id = wc_get_product_id_by_zettle_uuid($uuid);
        $product    = wc_get_product($product_id);

        if (null == $product_id) {
            $this->plugin->logger()->error("zettle_product_id_not_found", $uuid);
            return;
        }

        // If the product was found, then delete it. I assume that this will also
        // delete any variations.
        $product->delete();
    }
}