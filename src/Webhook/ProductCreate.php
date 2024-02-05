<?php
namespace Zettle\Webhook;

use Webmozart\Assert\Assert;
use Zettle\Support\Arr;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Attribute;
use WC_Product_Variation;

class ProductCreate extends Webhook
{
    /**
     * The webhook event name.
     */
    public string $action = "ProductCreated";

    /**
     * Handle the request.
     */
    public function handle(Request $request)
    {
        [$payload] = $this->unpack($request);

        // Create the product.

        $product_uuid = Arr::get($payload, "uuid");
        $product_data = $this->plugin
            ->zettle()
            ->get_product($product_uuid);

        $this->create_product($product_data);
    }

    /**
     * Create the product.
     */
    private function create_product(array $data)
    {
        $is_variable =
            Arr::has($data, "variantOptionDefinitions") &&
            Arr::len($data, "variantOptionDefinitions.definitions") > 0;

        $is_variable
            ? $this->create_product_variable($data)
            : $this->create_product_simple($data);
    }

    private function create_product_simple(array $data): void
    {
        Assert::notEmpty($uuid  = Arr::get($data, "uuid"));
        Assert::notEmpty($title = Arr::get($data, "name"));

        $sku   = Arr::get($data, "variants.0.sku");
        $price = Arr::get($data, "variants.0.price.amount");

        // Insert

        if (wc_get_product_id_by_zettle_uuid($uuid)) {
            $this->plugin->logger()->error("zettle_product_id_already_in_use", $uuid);
            return;
        }

        if (wc_get_product_id_by_sku($sku)) {
            $this->plugin->logger()->error("zettle_product_sku_already_in_use", $sku);
            return;
        }

        $product = new WC_Product_Simple();
        $product->set_status("publish");

        $price /= 100;

        $product->set_name($title);
        $product->set_sku($sku);
        $product->set_regular_price($price);

        $product->save();

        // After the product is created, store the zettle uuid in the post meta
        // so that the plugin can look it up later.
        update_post_meta($product->get_id(), "zettle_uuid", $uuid);
    }

    private function create_product_variable(array $data): void
    {
        Assert::notEmpty($uuid = Arr::get($data, "uuid"));
        Assert::notEmpty($name = Arr::get($data, "name"));

        if (wc_get_product_id_by_zettle_uuid($uuid)) {
            $this->plugin->logger()->error("zettle_product_id_already_in_use", $uuid);
            return;
        }

        // Product
        $product = new WC_Product_Variable();
        $product->set_status("publish");
        $product->set_name($name);

        // Attributes
        $attributes = $this->make_product_attributes($data);

        // Before we create variants, we need to ensure that the product exists.
        $product->set_attributes($attributes);
        $product->save();

        // After the product is created, store the zettle uuid in the post meta
        // so that the plugin can look it up later.
        update_post_meta($product->get_id(), "zettle_uuid", $uuid);

        // Variations
        foreach (Arr::get($data, "variants", []) as $variant) {
            $uuid = Arr::get($variant, "uuid");
            $sku  = Arr::get($variant, "sku");

            // Validate
            if (wc_get_product_id_by_zettle_uuid($uuid)) {
                $this->plugin->logger()->error("zettle_variant_id_already_in_use", $uuid);
                continue;
            }

            if ($sku) {
                if (wc_get_product_id_by_sku($sku)) {
                    $this->plugin->logger()->error("zettle_variant_sku_already_in_use", $sku);
                    continue;
                }
            }

            // Insert
            $variation = $this->create_product_variant($variant);

            $variation->set_parent_id($product->get_id());
            $variation->save();

            // After the variation is created, store the zettle uuid in the post meta
            // so that the plugin can look it up later.
            update_post_meta($variation->get_id(), "zettle_uuid", $uuid);
        }
    }

    private function make_product_attributes(array $data): array
    {
        $attributes = Arr::get($data, "variantOptionDefinitions.definitions", []);
        $attributes = Arr::map($attributes, function ($attr) {
            $attribute = new WC_Product_Attribute();

            $name = Arr::get($attr, "name");
            $opts = Arr::get($attr, "properties.*.value", []);

            $attribute->set_id(wc_attribute_taxonomy_id_by_name($name));
            $attribute->set_name($name);
            $attribute->set_options($opts);

            $attribute->set_variation(true);
            $attribute->set_visible(true);

            return $attribute;
        });

        return $attributes;
    }

    private function create_product_variant(array $data): WC_Product_Variation
    {
        $title = Arr::get($data, "name");
        $sku   = Arr::get($data, "sku");
        $price = Arr::get($data, "price.amount");

        $variant = new WC_Product_Variation();

        $price /= 100;

        $variant->set_name($title);
        $variant->set_sku($sku);
        $variant->set_regular_price($price);

        $options = [];

        foreach (Arr::get($data, "options", []) as $option) {
            $name  = Arr::get($option, "name");
            $value = Arr::get($option, "value");

            $options[wc_attribute_taxonomy_slug($name)] = $value;
        }

        $variant->set_attributes($options);

        return $variant;
    }
}