<?php
namespace Zettle;

defined("ABSPATH") or exit;

use Ramsey\Uuid\Uuid;
use WP_HTTP_Requests_Response;
use WP_Error;
use Zettle\Support\Arr;
use Exception;

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
            "destination"   => "https://6cfc-73-152-112-64.ngrok.io/wp-admin/admin-ajax.php?action=zettle_webhook",
            "contactEmail"  => "webhook@hbackman.com",
            "eventNames"    => $events,
        ];

        $response = $this->json_request("POST", $this->get_endpoint("pusher")."/subscriptions", $payload);

        if (false == $this->is_successful($response)) {
            // TODO: Print notice.

            Plugin::instance()->panic();
        }

        $this->set_signing_key(
            Arr::get($response->get_data(), "signingKey", "")
        );
    }

    /**
     * Unregister a subscription in zettle.
     */
    public function delete_pusher_subscription(): void
    {
        $response = $this->json_request("GET", $this->get_endpoint("pusher")."/subscriptions");

        if (false == $this->is_successful($response)) {
            return;
        }

        $webhooks = $response->get_data();
        $webhooks = Arr::pluck($webhooks, "uuid");

        foreach ($webhooks as $uuid) {
            $this->json_request("DELETE", $this->get_endpoint("pusher")."/subscriptions/$uuid");
        }
    }

    /**
     * Return whether the request was successful.
     */
    private function is_successful(WP_HTTP_Requests_Response $response): bool
    {
        return
            $response->get_status() >= 200 &&
            $response->get_status() <= 299;
    }

    /**
     * Send a request.
     */
    private function json_request(
        string $method,
        string $url,
        ?array $payload = null
    ): WP_HTTP_Requests_Response
    {
        if ($payload) {
            $payload = json_encode($payload);
        }

        $response = wp_remote_request($url, [
            "body"    => $payload,
            "method"  => $method,
            "headers" => [
                "Authorization"  => "Bearer ".Plugin::instance()->get_zettle_token(),
                "Content-Type"   => "application/json",
                "Content-Length" => strlen($payload),
            ],
        ]);

        if ($response instanceof WP_Error)
            throw new Exception("Response returned error.");

        $response = $response["http_response"];

        /** @var WP_HTTP_Requests_Response $response */

        $response->set_data(json_decode($response->get_data(), true));

        return $response;
    }
}