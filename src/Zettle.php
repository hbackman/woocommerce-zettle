<?php
namespace Zettle;

use InvalidArgumentException;

class Zettle
{
    const OAUTH_URL  = "https://oauth.zettle.com";
    const PUSHER_URL = "https://pusher.izettle.com";

    /**
     * Retrieve a Zettle access token.
     */
    public function get_access_token()
    {
        $code = "";

        // $this->handle_authorization_code_grant($code);
        // $this->handle_refresh_token_grant();
    }

    /**
     * Request a new access token using an authorization code.
     */
    private function handle_authorization_code_grant(string $code): void
    {
        // Retrieve Auth Settings
        $client_id     = get_option("wc_zettle_client_id");
        $client_secret = get_option("wc_zettle_client_secret");

        $payload = [
            "grant_type"    => "authorization_code",
            "code"          => $code,
            "client_id"     => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri"  => "https://httpbin.org/get",
        ];

        $content = self::send_request("POST", self::OAUTH_URL."/token", $payload);

        $has_tokens =
            array_key_exists("access_token", $content) &&
            array_key_exists("refresh_token", $content);

        if (! $has_tokens)
            throw new InvalidArgumentException("Unexpected response");

        update_option("wc_zettle_access_token", $content["access_token"]);
        update_option("wc_zettle_refresh_token", $content["refresh_token"]);
    }

    /**
     * Request a new access token using the refresh token.
     */
    private function handle_refresh_token_grant(): void
    {
        $client_id     = get_option("wc_zettle_client_id");
        $client_secret = get_option("wc_zettle_client_secret");

        $payload = [
            "grant_type"    => "refresh_token",
            "refresh_token" => get_option("wc_zettle_refresh_token"),
            "client_id"     => $client_id,
            "client_secret" => $client_secret,
        ];

        $content = self::send_request("POST", self::OAUTH_URL."/token", $payload);

        $has_tokens =
            array_key_exists("access_token", $content) &&
            array_key_exists("refresh_token", $content);

        if (! $has_tokens)
            throw new InvalidArgumentException("Unexpected response");

        update_option("wc_zettle_access_token", $content["access_token"]);
        update_option("wc_zettle_refresh_token", $content["refresh_token"]);
    }

    /**
     * Send a request.
     */
    public static function send_request(
        string $method,
        string $url,
        array  $payload = []
    ): array
    {
        // Ensure valid method.
        if (! in_array($method, ["GET", "POST"]))
            throw new InvalidArgumentException("Unsupported request method.");

        $ch = curl_init();

        // Set url, method and data.
        if ($method == "GET") {
            curl_setopt($ch, CURLOPT_URL, "$url?".http_build_query($payload));
        }

        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        // Return data rather than printing it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return json_decode($body, true);
    }
}