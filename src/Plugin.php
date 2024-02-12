<?php
namespace Zettle;

defined("ABSPATH") or exit;

use Automattic\Jetpack\Constants;
use Zettle\Admin\Settings;
use Zettle\Commands\FakeWebhook;
use Zettle\Commands\GetProductLibrary;
use Zettle\Commands\MatchProducts;
use Zettle\Commands\StockSync;
use Zettle\Support\Arr;
use Zettle\Support\Jwt;
use Zettle\Webhook\InventoryBalanceChanged;
use Zettle\Webhook\ProductCreate;
use Zettle\Webhook\ProductDelete;
use Zettle\Webhook\ProductUpdate;
use Zettle\Webhook\Request;
use Zettle\Webhook\TestMessage;
use Zettle\Webhook\Webhook;
use InvalidArgumentException;
use WP_CLI;
use Closure;

class Plugin
{
    /**
     * The plugin instance.
     */
    private static $instance;

    /**
     * Track if the plugin should be preventing the stock update events.
     */
    private bool $prevent_stock_update = false;

    /**
     * The plugin webhooks.
     *
     * @var class-string<Webhook>[]
     */
    public array $webhooks = [
        InventoryBalanceChanged::class,
        TestMessage::class,
        ProductCreate::class,
        ProductUpdate::class,
        ProductDelete::class,
    ];

    /**
     * The zettle instance.
     */
    private Zettle $zettle;

    /**
     * The logger instance.
     */
    private Logger $logger;

    /**
     * Initialize the plugin.
     */
    public function init(): void
    {
        $this->zettle = new Zettle($this);
        $this->logger = new Logger();

        new StockEvents($this);

        $this->init_webhooks();
        $this->init_settings();
        $this->init_commands();
    }

    /**
     * Check the plugin connection status.
     */
    public function is_connected(): bool
    {
        return $this->get_zettle_client_id() &&
               $this->get_zettle_client_secret();
    }

    /**
     * Check if the plugin is currently serving a webhook.
     */
    public function prevent_stock_update(): bool
    {
        return $this->prevent_stock_update;
    }

    /**
     * Execute the callback without stock update events.
     */
    public function without_stock_update(Closure $callback): void
    {
        $this->prevent_stock_update = true;

        $callback();

        $this->prevent_stock_update = false;
    }

    /**
     * Retrieve the logger instance.
     */
    public function logger(): Logger
    {
        return $this->logger;
    }

    /**
     * Retrieve the zettle instance.
     */
    public function zettle(): Zettle
    {
        return $this->zettle;
    }

    /**
     * Disable the plugin if something goes very wrong.
     */
    public function panic(): void
    {
        if (Constants::get_constant("WP_DEBUG")) {
            dd("panic", debug_backtrace());
        }

        wcz_plugin_disable(ZETTLE_PLUGIN);
    }

    /**
     * Retrieve the Zettle access token.
     */
    public function get_zettle_access_token(): ?string
    {
        // If the plugin is not connected, then the token will not exist. There is no
        // need for a notice here as the caller should handle failures.
        if (false == $this->is_connected())
            return null;

        try {
            $token = get_option("wc_zettle_token");
            $token = Jwt::parse($token);
        }
        catch (InvalidArgumentException $e) {
            // If the token fails to parse, remove it from storage. This requires the
            // plugin to ask for a new token, which should fix the problem.
            update_option("wc_zettle_token", $token = null);
        }

        try {
            $is_missing = $token == null;
            $is_expired = $token && $token->isExpired();

            if ($is_expired || $is_missing)
                $token = Jwt::parse($this->refresh_access_token());
        }
        catch (InvalidArgumentException $e) {
            // If the refresh fails, then reset both the zettle and vendor token
            // and show a notice for the user to reconnect.
            $this->panic();

            // TODO: Notice
            return null;
        }

        return $token ? $token->getToken() : null;
    }

    /**
     * Retrieve the Zettle integration client id.
     */
    public function get_zettle_client_id(): ?string
    {
        return get_option("wc_zettle_client_id");
    }

    /**
     * Retrieve the Zettle integration client secret.
     */
    public function get_zettle_client_secret(): ?string
    {
        return get_option("wc_zettle_client_secret");
    }

    /**
     * Request a new Zettle access token.
     */
    private function refresh_access_token(): ?string
    {
        $payload = ["body" => [
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "client_id"  => $this->get_zettle_client_id(),
            "assertion"  => $this->get_zettle_client_secret(),
        ]];

        $response = wp_remote_post("https://oauth.zettle.com/token", $payload);
        $response = $response["http_response"];

        if ($response->get_status() != 200)
            return null;

        $payload = $response->get_data();
        $payload = json_decode($payload, true);

        $token = (string) Arr::get($payload, "access_token");

        update_option("wc_zettle_token", $token);

        return $token;
    }

    /**
     * Retrieve the url used for incoming webhooks.
     */
    public function get_webhook_url(): ?string
    {
        $domain = get_option("wc_zettle_webhook_url");
        $endpoint = "/wp-admin/admin-ajax.php?action=zettle_webhook";

        if ($domain == null) {
            $domain = get_site_url();
        }

        return "$domain$endpoint";
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

            $this->prevent_stock_update = true;

            // Invoke the hook if found.
            foreach ($this->webhooks as $hook) {
                $hook = new $hook($this);

                if ($hook->action == $webhook)
                    $hook->handle($request);
            }

            $this->prevent_stock_update = false;
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
            new Settings($this);

            return $a;
        });

        add_filter("plugin_action_links", function ($actions, $file) {
            // Check that the filter is applied for this plugin only.
            if (str_contains(ZETTLE_PLUGIN, $file)) {
                $actions = array_merge([
                    "settings" => sprintf(
                        '<a href="%s" aria-label="%s">%s</a>',
                        admin_url("admin.php?page=wc-settings&tab=zettle"),
                        esc_attr__("View Zettle settings", "wc_zettle"),
                        esc_html__("Settings", "wc_zettle")
                    ),
                ], $actions);
            }
            return $actions;
        }, 10, 2);
    }

    /**
     * Init WP_CLI commands.
     */
    private function init_commands(): void
    {
        if (! defined("WP_CLI") || ! WP_CLI)
            return;

        // Initialize woocommerce.

        $woocommerce = "woocommerce/woocommerce.php";
        $woocommerce = dirname(ZETTLE_PLUGIN, 2) .DIRECTORY_SEPARATOR.$woocommerce;
        include_once $woocommerce;

        // Register commands.

        WP_CLI::add_command("zettle match-products", MatchProducts::class);
        WP_CLI::add_command("zettle stock-sync", StockSync::class);
        WP_CLI::add_command("zettle fake-webhook", FakeWebhook::class);
        WP_CLI::add_command("zettle get-product-library", GetProductLibrary::class);
    }

    /**
     * Retrieve the plugin instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null)
            self::$instance = new self();

        return self::$instance;
    }
}