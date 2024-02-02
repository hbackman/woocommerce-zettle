<?php
namespace Zettle\Webhook;

use Webmozart\Assert\Assert;
use Zettle\Support\Arr;
use WC_Product_Variation;

class ProductUpdate extends Webhook
{
    /**
     * The webhook event name.
     */
    public string $action = "ProductUpdated";

    /**
     * Handle the request.
     */
    public function handle(Request $request)
    {
        [$payload] = $this->unpack($request);

        $data = Arr::get($payload, "newEntity");

        // Update the product.

        $product_uuid = Arr::get($data, "uuid");
        $product_data = $this->zettle->get_product($product_uuid);

        $this->update_product($product_data);
    }

    private function update_product(array $data): void
    {
        Assert::notEmpty($uuid = Arr::get($data, "uuid"));
        Assert::notEmpty($name = Arr::get($data, "name"));

        // Update product.

        $product = wc_get_product_by_zettle_uuid($uuid);

        if (! $product) {
            $this->error("zettle_product_id_not_found", $uuid);
            return;
        }

        // Zettle will always provide the variant option definitions if the product
        // has multiple variations. This is important to check as Zettle will still
        // return a single variation for simple products.
        $is_variable =
            Arr::has($data, "variantOptionDefinitions") &&
            Arr::len($data, "variantOptionDefinitions.definitions") > 0;

        if ($is_variable) {
            // Update variations.
            foreach (Arr::get($data, "variants", []) as $variant) {
                // If the update encounters any errors then it may return null. Handle
                // this by wrapping the save call in an if statement.
                if ($variation = $this->update_product_variant($variant))
                    $variation->save();
            }
        }
        else {
            // Zettle will return a single variant, representing the simple product. So
            // pull these from said variant and put it on the product.
            $sku   = Arr::get($data, "variants.0.sku");
            $price = Arr::get($data, "variants.0.price.amount");

            $product->set_sku($sku);
            $product->set_regular_price($price);
        }

        // Save product.
        $product->set_name($name);
        $product->save();
    }

    private function update_product_variant(array $data): ?WC_Product_Variation
    {
        Assert::notEmpty($uuid = Arr::get($data, "uuid"));
        Assert::notEmpty($name = Arr::get($data, "name"));

        $variation = wc_get_product_by_zettle_uuid($uuid);

        if (! $variation) {
            $this->error("zettle_product_id_not_found", $uuid);
            return null;
        }

        $price = Arr::get($data, "price.amount");
        $sku   = Arr::get($data, "sku");

        $variation->set_name($name);
        $variation->set_regular_price($price);
        $variation->set_sku($sku);

        // Enable stock management if the sku was provided.
        if ($sku) {
            $variation->set_manage_stock(true);
        }

        $options = [];

        foreach (Arr::get($data, "options", []) as $option) {
            $name  = Arr::get($option, "name");
            $value = Arr::get($option, "value");

            $options[wc_attribute_taxonomy_slug($name)] = $value;
        }

        $variation->set_attributes($options);

        return $variation;
    }
}