# WooCommerce Zettle

A WordPress plugin that syncs products and inventory between [WooCommerce](https://woocommerce.com/) and [Zettle](https://www.zettle.com/) (PayPal's point-of-sale platform).

When a product changes in Zettle — or stock moves through a Zettle sale — the change is mirrored back to WooCommerce, and vice versa, so the two stay in sync.

## Requirements

- PHP 7.4+ (with `ext-json`, `ext-curl`)
- WordPress with the WooCommerce plugin active
- Composer
- A Zettle integration (client ID + secret) — created in your Zettle developer account

## Installation

Drop `woocommerce-zettle.zip` (from a release, or built locally — see [Development](#development)) into `wp-content/plugins/` and unzip it. Dependencies are bundled in the zip, so no Composer step is required on the WordPress host.

Then activate **WooCommerce Zettle** from the WordPress plugins page. If WooCommerce isn't active, the plugin will refuse to load and display a notice.

## Configuration

Settings live under **WooCommerce → Settings → Zettle**. The plugin stores its configuration in standard WordPress options:

| Option | Description |
|---|---|
| `wc_zettle_client_id` | Zettle integration client ID |
| `wc_zettle_client_secret` | Zettle integration client secret |
| `wc_zettle_token` | API access token (auto-refreshed; valid 2h) |
| `wc_zettle_webhook_url` | Webhook URL override — useful in local dev where HTTPS isn't reachable |
| `wc_zettle_inventory_store` | UUID of the "store" inventory location |
| `wc_zettle_inventory_sold` | UUID of the "sold" inventory location |

## Webhooks

The plugin registers handlers for these Zettle events:

- `InventoryBalanceChanged` — pulls stock changes from Zettle into WooCommerce
- `ProductCreate` / `ProductUpdate` / `ProductDelete` — mirrors Zettle product changes
- `TestMessage` — used by Zettle to verify the endpoint

## WP-CLI commands

Run via `wp zettle <command>` (or `composer wp zettle <command>` in the docker setup):

| Command | What it does |
|---|---|
| `get-inventories` | List inventory locations from Zettle |
| `get-library` | Fetch the full Zettle product library |
| `match-products` | Match existing WooCommerce products to Zettle products by SKU |
| `run-stock-sync` | Push current WooCommerce stock levels to Zettle |
| `run-webhook` | Replay a webhook payload locally (useful in dev) |

## Development

```sh
git clone https://github.com/hbackman/woocommerce-zettle.git
cd woocommerce-zettle
composer install
docker compose up -d
```

This brings up WordPress at `http://localhost:5000` with MySQL and a `wp-cli` container. The plugin directory is mounted into the WordPress container, so edits take effect immediately.

Run the test suite:

```sh
composer test
```

Build a distribution zip:

```sh
composer build
```

This produces `woocommerce-zettle.zip` with `vendor/` bundled, ready to drop into a WordPress install.

## License

GPL-2.0 — see [LICENSE.md](LICENSE.md).

## Authors

- Maja Backman &lt;maja@hbackman.com&gt;
- ydgcam
