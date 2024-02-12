# Zettle for WooCommerce

## Wordpress Options

**wc_zettle_client_id**  
The Zettle integration customer id.

**wc_zettle_client_secret**  
The Zettle integration customer secret.

**wc_zettle_token**  
The Zettle API access token. This is valid for 2 hours and will be replaced when expired.

### Webhooks

**wc_zettle_webhook_url**  
The webhook url to use. This is used for development where https isn't accessible.

### Inventory

**wc_zettle_inventory_store**  
The "store" inventory. This will be automatic later on.

**wc_zettle_inventory_sold**  
The "sold" inventory. This will be automatic later on.

### TODO
- Validate token scopes.
- Show zettle sync status on products list.