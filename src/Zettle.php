<?php
namespace Zettle;

defined("ABSPATH") or exit;

use Ramsey\Uuid\Uuid;
use WP_HTTP_Requests_Response;
use WP_Error;
use Zettle\Support\Arr;
use Exception;
use Zettle\Support\JsonResponse;

class Zettle
{
    /**
     * Prepare the zettle api endpoint.
     */
    private function get_endpoint(string $domain): string
    {
        return "https://$domain.izettle.com/organizations/self";
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

        $response = $this->json_request("POST", $this->get_endpoint("pusher")."/subscriptions", $payload);

        if (false == $this->is_successful($response)) {
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
        $response = $this->json_request("GET", $this->get_endpoint("pusher")."/subscriptions");

        if (false == $this->is_successful($response))
            return;

        $webhooks = $response->json();
        $webhooks = Arr::pluck($webhooks, "uuid");

        foreach ($webhooks as $uuid) {
            $this->json_request("DELETE", $this->get_endpoint("pusher")."/subscriptions/$uuid");
        }
    }

    /**
     * Retrieve a product by its uuid.
     */
    public function get_product(string $uuid): ?array
    {
        $response = $this->json_request("GET", $this->get_endpoint("products")."/products/$uuid");

        if (false == $this->is_successful($response))
            return null;

        return $response->json();
    }

    /**
     * Return whether the request was successful.
     */
    private function is_successful(WP_HTTP_Requests_Response $response): bool
    {
        return $response->get_status() >= 200 &&
               $response->get_status() <= 299;
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