<?php
namespace Zettle\Webhook;

use Webmozart\Assert\Assert;
use Zettle\Support\Arr;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Data_Exception;
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
        [$payload, $message] = $this->unpack($request);

        // Log the event.

        $log = sprintf("%s - %s",
            $message["name"],
            $payload["uuid"]
        );

        wc_get_logger()->info($log, [
            "source" => "woocommerce-zettle",
        ]);

        // Create the product.

        $product = $this->zettle->get_product(Arr::get($payload, "uuid"));

        $this->create_product($product);
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
        // Validate

        Assert::notEmpty($title = Arr::get($data, "name"));
        Assert::notEmpty($sku   = Arr::get($data, "variants.0.sku"));
        Assert::notEmpty($price = Arr::get($data, "variants.0.price.amount"));

        // Insert

        if (wc_get_product_id_by_sku($sku)) {
            wc_get_logger()->error("Zettle: product_invalid_sku ($sku)", [
                "source" => "woocommerce-zettle",
            ]);
            return;
        }

        $product = new WC_Product_Simple();
        $product->set_status("publish");

        $price /= 100;

        $product->set_name($title);
        $product->set_sku($sku);
        $product->set_regular_price($price);

        $product->save();
    }

    private function create_product_variable(array $data): void
    {
        Assert::notEmpty($name = Arr::get($data, "name"));

        // Product
        $product = new WC_Product_Variable();
        $product->set_status("publish");

        $product->set_name($name);

        // Attributes
        $attr = Arr::get($data, "variantOptionDefinitions.definitions", []);
        $attr = Arr::map($attr, function ($attr) {
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

        $product->set_attributes($attr);
        $product->save();

        // Variations
        foreach (Arr::get($data, "variants", []) as $variant) {
            $title = Arr::get($variant, "uuid");
            $sku   = Arr::get($variant, "sku");
            $price = Arr::get($variant, "price");

            if (wc_get_product_id_by_sku($sku)) {
                wc_get_logger()->error("Zettle: product_invalid_sku ($sku)", [
                    "source" => "woocommerce-zettle",
                ]);
                continue;
            }

            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product->get_id());

            $price /= 100;

            $variation->set_name($title);
            $variation->set_sku($sku);
            $variation->set_regular_price($price);

            $options = [];

            foreach (Arr::get($variant, "options") as $option) {
                $name  = Arr::get($option, "name");
                $value = Arr::get($option, "value");

                $options[wc_attribute_taxonomy_slug($name)] = $value;
            }

            $variation->set_attributes($options);
            $variation->save();
        }
    }
}