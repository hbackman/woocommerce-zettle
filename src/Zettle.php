<?php
namespace Zettle;

defined("ABSPATH") or exit;

use Ramsey\Uuid\Uuid;
use WP_Error;
use Zettle\Support\Arr;
use Zettle\Support\JsonResponse;
use Exception;
use RuntimeException;

class Zettle
{
    /**
     * The plugin instance.
     */
    private Plugin $plugin;

    const ENDPOINT_PUSHER    = "https://pusher.izettle.com/organizations/self";
    const ENDPOINT_PRODUCTS  = "https://products.izettle.com/organizations/self";
    const ENDPOINT_INVENTORY = "https://inventory.izettle.com/v3";

    /**
     * Zettle constructor.
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Retrieve products from the library.
     */
    public function get_library_products(): array
    {
        $products = [];

        // Zettle uses the "link" header for pagination. This is handled by looping
        // the requests until the "next" link is empty.
        do {
            $endpoint = $next ?? self::ENDPOINT_PRODUCTS."/library";
            $response = $this->json_request("GET", $endpoint);

            if (! $response->is_successful())
                throw new RuntimeException("Failed to retrieve Zettle library.");

            $next = $response->get_link("next");
            $data = $response->json("products");

            if (is_array($data))
                array_push($products, ...($data ?? []));
        } while ($next);

        return $products;
    }

    /**
     * Set the stock quantity for a given variant uuid.
     */
    public function set_variant_stock(
        string $product_uuid,
        string $variant_uuid,
        ?int   $variant_stock
    ): bool
    {
        // If the variant is not tracking stock, then we do not need to update
        // the Zettle stock.
        if (is_null($variant_stock))
            return true;

        $stock = $this->get_product_stock(
            $product_uuid,
            $variant_uuid
        );

        // Calculate the difference between the local and remote inventory. When the
        // diff is < 0, this means that we need to move from "store" to "sold". When
        // it is > 0, we need to move from "sold" to "store".
        $diff = $variant_stock - $stock;

        return $this->apply_stock_diff(
            $product_uuid,
            $variant_uuid, $diff
        );
    }

    /**
     * Set the stock quantity for a given product uuid.
     */
    public function set_product_stock(
        string $product_uuid,
        ?int  $product_stock
    ): bool
    {
        // If the product is not tracking stock, then we do not need to update
        // the Zettle stock.
        if (is_null($product_stock))
            return true;

        $stock = $this->get_product_stock(
            $product_uuid,
            $variant_uuid
        );

        // Calculate the difference between the local and remote inventory. When the
        // diff is < 0, this means that we need to move from "store" to "sold". When
        // it is > 0, we need to move from "sold" to "store".
        $diff = $product_stock - $stock;

        return $this->apply_stock_diff(
            $product_uuid,
            $variant_uuid, $diff
        );
    }

    /**
     * Applies a stock diff to the store inventory.
     */
    private function apply_stock_diff(
        string $product_uuid,
        string $variant_uuid,
        int    $diff
    ): bool
    {
        // If the difference is zero, then nothing has been moved.
        if ($diff == 0)
            return true;

        $inventory_store = get_option("wc_zettle_inventory_store");
        $inventory_sold  = get_option("wc_zettle_inventory_sold");

        $payload = ["movements" => [[
            "productUuid" => $product_uuid,
            "variantUuid" => $variant_uuid,
            "change"      => abs($diff),
            "from"        => $diff > 0 ? $inventory_sold : $inventory_store,
            "to"          => $diff > 0 ? $inventory_store : $inventory_sold,
        ]]];

        $endpoint = self::ENDPOINT_INVENTORY."/movements";
        $response = $this->json_request("POST", $endpoint, $payload);

        if (false == $response->is_successful()) {
            $this->plugin->logger()->error(
                "Could not update product stock.",
                $product_uuid,
                $variant_uuid,
            );
            return false;
        }

        return true;
    }

    /**
     * Retrieve stock for a product or variant.
     *
     * This uses a reference for $variant_uuid to fill that when not provided.
     */
    public function get_product_stock(
        string  $product_uuid,
        ?string &$variant_uuid = null
    ): ?int
    {
        $inv_uuid = get_option("wc_zettle_inventory_store");

        $endpoint = self::ENDPOINT_INVENTORY."/stock/$inv_uuid/products/$product_uuid";
        $response = $this->json_request("GET", $endpoint);

        if (false == $response->is_successful()) {
            $this->plugin->logger()->error(
                "Could not retrieve product stock.",
                $product_uuid,
                $variant_uuid
            );
        }

        $list = $response->json();
        $item = $variant_uuid === null
            ? Arr::first($list, "productUuid", "=", $product_uuid)
            : Arr::first($list, "variantUuid", "=", $variant_uuid);

        if (($stock = Arr::get($item, "balance")) !== null)
            $stock = (int)$stock;
        else {
            // I am not sure that this could actually happen. The event should not be
            // emitted, the request should 404, etc. But let's check this anyway just
            // in case.
            return null;
        }

        $variant_uuid = Arr::get($item, "variantUuid");

        return $stock;
    }

    /**
     * Retrieve a product by its uuid.
     */
    public function get_product(string $uuid): ?array
    {
        $response = $this->json_request("GET", self::ENDPOINT_PRODUCTS."/products/$uuid");

        if (false == $response->is_successful())
            return null;

        return $response->json();
    }

    /**
     * Retrieve the zettle signing key.
     */
    public function get_signing_key(): string
    {
        return (string) get_option("zettle_signing_key");
    }

    /**
     * Update the zettle signing key.
     */
    public function set_signing_key(string $key): void
    {
        update_option("zettle_signing_key", $key);
    }

    /**
     * Register a subscription in zettle.
     */
    public function create_pusher_subscription(array $events): void
    {
        $this->delete_pusher_subscription();

        $payload = [
            "uuid"          => Uuid::uuid1()->toString(),
            "transportName" => "WEBHOOK",
            "destination"   => Plugin::instance()->get_webhook_url(),
            "contactEmail"  => "webhook@hbackman.com",
            "eventNames"    => $events,
        ];

        $response = $this->json_request("POST", self::ENDPOINT_PUSHER."/subscriptions", $payload);

        if (false == $response->is_successful()) {
            Plugin::instance()->panic();

            return;

            // TODO: Print notice.
        }

        $this->set_signing_key($response->json("signingKey"));
    }

    /**
     * Unregister a subscription in zettle.
     */
    public function delete_pusher_subscription(): void
    {
        $response = $this->json_request("GET", self::ENDPOINT_PUSHER."/subscriptions");

        if (false == $response->is_successful())
            return;

        $webhooks = $response->json();
        $webhooks = Arr::pluck($webhooks, "uuid");

        foreach ($webhooks as $uuid) {
            $this->json_request("DELETE", self::ENDPOINT_PUSHER."/subscriptions/$uuid");
        }
    }

    /**
     * Send a request.
     */
    private function json_request(
        string $method,
        string $url,
        ?array $payload = null
    ): JsonResponse
    {
        if ($payload) {
            $payload = json_encode($payload);
        }

        $response = wp_remote_request($url, [
            "body"    => $payload,
            "method"  => $method,
            "headers" => [
                "Authorization"  => "Bearer ".Plugin::instance()->get_zettle_access_token(),
                "Content-Type"   => "application/json",
                "Content-Length" => strlen($payload),
            ],
        ]);

        if ($response instanceof WP_Error)
            throw new Exception("Response returned error.");

        return JsonResponse::create($response["http_response"]);
    }
}