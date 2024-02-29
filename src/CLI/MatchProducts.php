<?php
namespace Zettle\CLI;

use WP_CLI_Command;
use Zettle\Plugin;
use Zettle\Support\Arr;

class MatchProducts extends WP_CLI_Command
{
    /**
     * Run the command.
     */
    public function __invoke(): void
    {
        $zettle_products = Plugin::instance()
            ->zettle()
            ->get_library_products();

        // Map each product variant to include the product uuid. This allows us to
        // easier look up the product uuid when plucking variants.
        $zettle_products = Arr::map($zettle_products, function ($product) {
            $variants = $product["variants"];
            $variants = Arr::map($variants, function ($variant) use ($product) {
                $variant["variant_uuid"] = $variant["uuid"];
                $variant["product_uuid"] = $product["uuid"];
                return $variant;
            });
            return array_merge($product, [
                "variants" => $variants,
            ]);
        });

        // Build sku => product map.
        $product_sku_map = Arr::keyBy($zettle_products, "variants.0.sku");

        // Build sku => variant map.
        $variant_sku_map = Arr::pluck($zettle_products, "variants");
        $variant_sku_map = Arr::flatten($variant_sku_map, 1);
        $variant_sku_map = Arr::keyBy($variant_sku_map, "sku");

        // Loop through each WooCommerce product and check its sku, or variants sku
        // if get_type() == "variable". Then attach the zettle info.
        foreach ($this->get_woocommerce_products() as $product) {
            // Handle simple.
            if ($product->get_type() == "simple") {
                echo "Skipping simple product.";
                echo PHP_EOL;
            }
            // Handle variable.
            if ($product->get_type() == "variable") {
                /** @var \WC_Product_Variable $product */
                foreach ($product->get_available_variations("objects") as $variant) {
                    // SKU must exist to match.
                    if (! $sku = $variant->get_sku())
                        continue;

                    $zettle_variant = Arr::get($variant_sku_map, $sku);

                    // Match
                    // First

                    // Zettle variant must exist.
                    if (! $zettle_variant)
                        continue;

                    // Connect
                    update_post_meta($variant->get_id(), "zettle_uuid", $zettle_variant["variant_uuid"]);
                    update_post_meta($product->get_id(), "zettle_uuid", $zettle_variant["product_uuid"]);

                    echo "Connected: ".$sku;
                    echo PHP_EOL;
                }
            }
        }
    }

    /**
     * Retrieve WooCommerce products.
     */
    private function get_woocommerce_products(): array
    {
        $args = [
            "status"   => ["draft", "pending", "private", "publish"],
            "type"     => array_keys(wc_get_product_types()),
            "limit"    => -1,
            "offset"   => null,
            "page"     => 1,
            "paginate" => false,
        ];

        return wc_get_products($args);
    }
}