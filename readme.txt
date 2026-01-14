=== UCP Shopping Agent ===
Contributors: rogerdeng
Donate link: https://sites.google.com/view/ucp-shopping-agent
Tags: shopping, ecommerce, ucp, agent, woocommerce
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable AI agents to discover, browse, and transact with your online store through the Google Universal Commerce Protocol (UCP) REST API.

== Description ==

**UCP Shopping Agent** implements the Google Universal Commerce Protocol (UCP), providing a standardized REST API that enables AI agents and automated systems to interact with your online store.

This plugin requires WooCommerce to be installed and activated.

= Key Features =

* **Store Discovery** - Standard `/.well-known/ucp` endpoint for AI agents to discover your store's capabilities
* **Product Catalog** - Browse, search, and filter products with full support for variations
* **Category Navigation** - Full category hierarchy with nested subcategories
* **Persistent Cart** - Create and manage shopping carts with automatic stock validation
* **Checkout Sessions** - Complete checkout flow with address management and coupon support
* **Order Management** - Retrieve order details, status, and event timeline
* **Customer Profiles** - Create and manage customer accounts
* **Shipping Rates** - Real-time shipping rate calculation
* **Product Reviews** - Access and create product reviews
* **Coupon Validation** - Discover and validate promotional codes
* **Webhooks** - Real-time order event notifications with HMAC-SHA256 signature verification
* **Secure Authentication** - API key authentication with granular permission levels

= Why Use UCP Shopping Agent? =

The Universal Commerce Protocol (UCP) is designed to enable AI assistants and automated agents to help users discover products, compare options, and complete purchases. By implementing UCP, your store becomes accessible to the next generation of AI-powered shopping experiences.

= API Endpoints =

The plugin provides the following API endpoints:

* `/wp-json/ucp/v1/discovery` - Store discovery and capabilities
* `/wp-json/ucp/v1/products` - Product listing, search, and details
* `/wp-json/ucp/v1/categories` - Category navigation
* `/wp-json/ucp/v1/carts` - Cart management
* `/wp-json/ucp/v1/checkout/sessions` - Checkout flow
* `/wp-json/ucp/v1/orders` - Order information
* `/wp-json/ucp/v1/customers` - Customer management
* `/wp-json/ucp/v1/shipping/rates` - Shipping calculation
* `/wp-json/ucp/v1/reviews` - Product reviews
* `/wp-json/ucp/v1/coupons` - Coupon validation

= Authentication =

API keys can be created with three permission levels:

* **Read** - Browse products, categories, reviews
* **Write** - Create carts, checkout sessions, orders
* **Admin** - Full access including API key management

= Requirements =

* WordPress 5.8 or higher
* WooCommerce 5.0 or higher
* PHP 7.4 or higher

= Documentation =

For full documentation, please visit our [GitHub repository](https://github.com/rogerdeng/ucp-shopping-agent).

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "UCP Shopping Agent"
4. Click "Install Now" and then "Activate"
5. Ensure WooCommerce is installed and activated
6. Go to WooCommerce > UCP to configure settings

= Manual Installation =

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Upload the zip file and click "Install Now"
5. Activate the plugin
6. Ensure WooCommerce is installed and activated
7. Go to WooCommerce > UCP to configure settings

== Frequently Asked Questions ==

= What is the Universal Commerce Protocol (UCP)? =

UCP is a standardized protocol designed by Google to enable AI agents and automated systems to interact with e-commerce stores. It provides a consistent API structure for discovery, browsing, and transactions.

= Does this plugin require WooCommerce? =

Yes, this plugin is an extension for WooCommerce and requires WooCommerce to be installed and activated.

= Do I need special hosting for this plugin? =

No, the plugin works on any hosting that supports WordPress and WooCommerce. The REST API endpoints are served through WordPress's built-in REST API infrastructure.

= How do AI agents find my store? =

AI agents can discover your store by accessing the `/.well-known/ucp` endpoint at your site's root URL. This returns a manifest of your store's capabilities and available API endpoints.

= Is this plugin secure? =

Yes, the plugin implements secure API key authentication with hashed secrets stored in the database. All inputs are sanitized and outputs are escaped following WordPress security best practices. Webhook payloads are signed with HMAC-SHA256 for verification.

= Can I limit API access? =

Yes, you can create API keys with different permission levels (read, write, admin) and set rate limits to control API usage. You can also revoke API keys at any time.

= Does this work with product variations? =

Yes, the plugin fully supports variable products, including listing all variations with their attributes, prices, and stock status.

= How do I test if the API is working? =

You can test the discovery endpoint directly in your browser by visiting `https://your-site.com/.well-known/ucp`. For other endpoints, use a tool like cURL or Postman with your API key.

= What happens to carts and checkout sessions? =

Carts and checkout sessions have configurable expiration times (default: 24 hours for carts, 30 minutes for checkout). Expired sessions are automatically cleaned up.

== Screenshots ==

1. UCP Settings - General configuration options
2. API Keys Management - Create and manage API keys
3. Discovery Endpoint - Quick start guide and endpoint reference
4. Sample API Response - JSON response from products endpoint

== Changelog ==

= 1.0.0 =
* Initial release
* Store discovery endpoint (/.well-known/ucp)
* Products API with search and filtering
* Categories API with hierarchy support
* Persistent cart management
* Checkout session flow
* Order retrieval and timeline
* Customer profile management
* Shipping rate calculation
* Product reviews API
* Coupon validation
* Webhook support for order events
* API key authentication with permissions
* Admin settings interface
* Rate limiting support
* Internationalization ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of UCP Shopping Agent. Install to enable AI agents to interact with your online store.

== Privacy Policy ==

UCP Shopping Agent stores the following data:

* **API Keys** - Stored in a custom database table with hashed secrets
* **Cart Sessions** - Temporary cart data stored until expiration or checkout
* **Checkout Sessions** - Temporary checkout data stored until completion or expiration
* **Webhook Configurations** - Webhook URLs and secrets for event notifications

No data is sent to external servers except:
* Webhook payloads are sent to URLs you configure for order event notifications

All data is stored locally in your WordPress database and can be deleted by deactivating and removing the plugin.

== Third Party Services ==

This plugin does not connect to any third-party services by default. If you configure webhooks, the plugin will send HTTP POST requests to the URLs you specify when order events occur.

This plugin requires WooCommerce (https://woocommerce.com) to function, but does not send any data to WooCommerce servers.
