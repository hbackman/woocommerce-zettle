<?php
namespace Zettle;

defined("ABSPATH") or exit;

use Automattic\Jetpack\Constants;
use Zettle\Admin\Settings;
use Zettle\Support\Jwt;
use Zettle\Webhook\ProductCreate;
use Zettle\Webhook\Request;
use Zettle\Webhook\TestMessage;
use Zettle\Webhook\Webhook;
use InvalidArgumentException;

class Plugin
{
    /**
     * The plugin instance.
     */
    private static $instance;

    /**
     * The plugin webhooks.
     *
     * @var class-string<Webhook>[]
     */
    private array $webhooks = [
        TestMessage::class,
        ProductCreate::class,
    ];

    /**
     * The zettle instance.
     */
    private Zettle $zettle;

    /**
     * Initialize the plugin.
     */
    public function init(): void
    {
        $this->zettle = new Zettle();

        $this->init_webhooks();
        $this->init_settings();
    }

    /**
     * Disable the plugin if something goes very wrong.
     */
    public function panic(): void
    {
        if (Constants::get_constant("WP_DEBUG")) {
            dd("panic", debug_backtrace());
            dd(debug_backtrace());
        }

        z_plugin_disable(ZETTLE_PLUGIN);

        update_option("zettle_token", null);
        update_option("vendor_token", null);
    }

    /**
     * Retrieve the zettle token.
     */
    public function get_zettle_token(): ?string
    {
        if (! get_option("vendor_token"))
            return null;

        try {
            $token = get_option("zettle_token");
            $token = Jwt::parse($token);
        }
        catch (InvalidArgumentException $e) {
            // If the token fails to parse, remove it from storage. This requires the
            // plugin to ask for a new token, which should fix the problem.
            update_option("zettle_token", $token = null);
        }

        try {
            $isExpired = $token && $token->isExpired();

            if ($isExpired) {
                $token = $this->refresh_token();
                $token = Jwt::parse($token);
            }
        }
        catch (InvalidArgumentException $e) {
            // If the refresh fails, then reset both the zettle and vendor token
            // and show a notice for the user to reconnect.

            $this->panic();

            // TODO: Notice

            return null;
        }

        return $token->getToken();
    }

    /**
     * Request a new token from the token service.
     */
    private function refresh_token(): ?string
    {
        $payload = [
            "customer_id"    => $this->get_customer_id(),
            "customer_token" => $this->get_customer_token(),
        ];

        $response = wp_remote_post("http://host.docker.internal:5010/refresh", ["body" => $payload]);
        $response = $response["http_response"];

        if ($response->get_status() != 200)
            return null;

        $payload = $response->get_data();
        $payload = json_decode($payload, true);

        return (string) $payload["zettle_token"];
    }

    /**
     * Retrieve the customer id.
     */
    public function get_customer_id(): string
    {
        return get_bloginfo('admin_email');
    }

    /**
     * Retrieve the customer token.
     */
    public function get_customer_token(): ?string
    {
        return get_option("vendor_token");
    }

    /**
     * Retrieve the url used for incoming webhooks.
     */
    public function get_webhook_url(): string
    {
        return "https://7571-73-152-112-64.ngrok.io/wp-admin/admin-ajax.php?action=zettle_webhook";
    }

    /**
     * Init webhooks.
     */
    private function init_webhooks(): void
    {
        // Webhook handler.
        add_action("wp_ajax_nopriv_zettle_webhook",  function () {
            // Retrieve the request data.
            $request = Request::make();
            $content = $request->json();

            // Retrieve the event name.
            $webhook = $content["eventName"] ?? null;

            // Invoke the hook if found.
            foreach ($this->webhooks as $hook) {
                $hook = new $hook($this->zettle);

                if ($hook->action == $webhook)
                    $hook->handle($request);
            }
        });

        add_action("zettle_connected", function () {
            $this->zettle->create_pusher_subscription([
                "ProductCreated",
                "ProductUpdated",
                "ProductDeleted",
                "InventoryBalanceChanged",
            ]);
        });

        add_action("zettle_disconnected", function () {
            $this->zettle->delete_pusher_subscription();
        });
    }

    /**
     * Init admin settings page.
     */
    private function init_settings(): void
    {
        add_filter("woocommerce_get_settings_pages", function ($a) {
            new Settings();

            return $a;
        });
    }

    /**
     * Retrieve the plugin instance.
     */
    public static function instance(): static
    {
        if (self::$instance === null)
            self::$instance = new self();

        return self::$instance;
    }
}