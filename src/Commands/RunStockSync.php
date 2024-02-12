<?php
namespace Zettle\Commands;

use WP_CLI_Command;
use WP_CLI;
use WC_Product;
use Zettle\Plugin;
use Zettle\Zettle;

class RunStockSync extends WP_CLI_Command
{
    private Plugin $plugin;
    private Zettle $zettle;

    /**
     * Run the command.
     */
    public function __invoke(): void
    {
        $this->plugin = Plugin::instance();
        $this->zettle = Plugin::instance()->zettle();

        foreach ($this->get_connected_products() as $product) {
            // We are setting the stock here, and even though the zettle class will not
            // update if the stock matches, it makes more sense just disabling this.
            $this->plugin->without_stock_update(function () use ($product) {
                $this->handle_product($product);
            });
        }
    }

    private function handle_product(WC_Product $product): void
    {
        if ($product->get_type() == "simple") {
            //
        }

        if ($product->get_type() == "variable") {
            /** @var \WC_Product_Variable $product */
            foreach ($product->get_available_variations("objects") as $variant) {

                $product_uuid = get_post_meta($product->get_id(), "zettle_uuid", true);
                $variant_uuid = get_post_meta($variant->get_id(), "zettle_uuid", true);

                $stock = $this->zettle->get_product_stock(
                    $product_uuid,
                    $variant_uuid
                );

                if (! $stock || $stock == $variant->get_stock_quantity()) {
                    echo "Skipped";
                    echo PHP_EOL;
                    continue;
                }

                $variant->set_manage_stock(true);
                $variant->set_stock_quantity($stock);
                $variant->save();

                echo "Updated";
                echo PHP_EOL;
            }
        }
    }

    /**
     * Retrieve connected products.
     */
    private function get_connected_products(): array
    {
        $args = [
            "status"   => ["draft", "pending", "private", "publish"],
            "type"     => array_keys(wc_get_product_types()),
            "limit"    => -1,
            "offset"   => null,
            "page"     => 1,
            "paginate" => false,
        ];

        $args = array_merge($args, [
            "meta_key"     => "zettle_uuid",
            "meta_compare" => "EXISTS",
        ]);

        return wc_get_products($args);
    }
}