# WooCommerce UCP Agent

[![WordPress](https://img.shields.io/badge/WordPress-5.8+-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0+-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Google Universal Commerce Protocol (UCP) implementation for WooCommerce** — Enable AI agents to discover, browse, and transact with your WooCommerce store through a standardized REST API.

---

## 🌟 Features

### 🔍 Store Discovery
- Standard `/.well-known/ucp` discovery endpoint
- Complete store capability manifest
- Merchant information, currency, locale, and timezone

### 🛍️ Product Catalog
- Browse products with pagination and filtering
- Search by keyword, category, price range
- Get product details by ID or SKU
- Variable products with all variations
- Product images, attributes, and ratings

### 📁 Categories
- Full category hierarchy navigation
- Nested subcategory support
- Products by category with pagination

### 🛒 Persistent Cart
- Create and manage shopping carts
- Add, update, remove cart items
- Support for product variations
- Automatic stock validation
- Cart expiration management

### 💳 Checkout
- Create checkout sessions from carts
- Direct checkout with items
- Shipping and billing address management
- Coupon application
- Order confirmation and creation

### 📦 Orders
- Order listing with filters
- Detailed order information
- Order event timeline tracking
- Payment and shipping status

### 👤 Customer Management
- Create customer profiles
- Update billing/shipping addresses
- Lookup by email

### 🚚 Shipping
- Real-time shipping rate calculation
- Multiple shipping zones support
- Available shipping methods

### ⭐ Reviews
- Product reviews listing
- Review creation
- Rating distribution summary

### 🎟️ Coupons
- Discover available coupons
- Validate coupon codes
- Calculate discounts

### 🔔 Webhooks
- Real-time order event notifications
- HMAC-SHA256 signature verification
- Events: `order.created`, `order.status_changed`, `order.paid`, `order.refunded`

### 🔐 Authentication
- Secure API key authentication
- Three permission levels: `read`, `write`, `admin`
- Key management via admin interface
- Rate limiting support

---

## 📋 Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

---

## 🚀 Installation

1. Download the plugin zip file
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Click **Activate Plugin**
5. Go to **WooCommerce → UCP** to configure settings

---

## ⚙️ Configuration

### Admin Settings

Navigate to **WooCommerce → UCP** in your WordPress admin panel.

#### General Tab
| Setting | Description | Default |
|---------|-------------|---------|
| Enable UCP | Enable/disable UCP API endpoints | Yes |
| Rate Limit | Max requests per minute per API key | 100 |
| Cart Expiry | Hours until inactive cart expires | 24 |
| Checkout Expiry | Minutes until checkout session expires | 30 |
| Enable Logging | Log API requests for debugging | No |

#### API Keys Tab
- Create new API keys with descriptions
- Set permission levels (read/write/admin)
- View existing keys and last access time
- Delete unused keys

#### Discovery Tab
- View your Discovery URL
- Quick start guide
- Available endpoints reference

---

## 🔑 Authentication

### API Key Format
```
key_id:secret
```
Example: `ucp_abc123:ucp_secret_xyz789`

### Authentication Methods

**Header (Recommended)**
```bash
curl -H "X-UCP-API-Key: ucp_abc123:ucp_secret_xyz789" \
  https://your-store.com/wp-json/ucp/v1/products
```

**Query Parameter**
```bash
curl "https://your-store.com/wp-json/ucp/v1/products?ucp_api_key=ucp_abc123:ucp_secret_xyz789"
```

### Permission Levels

| Level | Access |
|-------|--------|
| `read` | Browse products, categories, reviews |
| `write` | Create carts, checkout, orders, customers |
| `admin` | Full access including API key management |

---

## 📡 API Endpoints

### Discovery
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/.well-known/ucp` | No | Store discovery manifest |
| GET | `/wp-json/ucp/v1/discovery` | No | Same as above |

### Products
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wp-json/ucp/v1/products` | No | List products |
| GET | `/wp-json/ucp/v1/products/{id}` | No | Get product by ID |
| GET | `/wp-json/ucp/v1/products/search` | No | Search products |
| GET | `/wp-json/ucp/v1/products/sku/{sku}` | No | Get product by SKU |

### Categories
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wp-json/ucp/v1/categories` | No | List categories |
| GET | `/wp-json/ucp/v1/categories/{id}` | No | Get category |
| GET | `/wp-json/ucp/v1/categories/{id}/products` | No | Category products |

### Cart
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/wp-json/ucp/v1/carts` | Write | Create cart |
| GET | `/wp-json/ucp/v1/carts/{id}` | Write | Get cart |
| DELETE | `/wp-json/ucp/v1/carts/{id}` | Write | Delete cart |
| POST | `/wp-json/ucp/v1/carts/{id}/items` | Write | Add item |
| PATCH | `/wp-json/ucp/v1/carts/{id}/items/{key}` | Write | Update item |
| DELETE | `/wp-json/ucp/v1/carts/{id}/items/{key}` | Write | Remove item |
| POST | `/wp-json/ucp/v1/carts/{id}/checkout` | Write | Convert to checkout |

### Checkout
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/wp-json/ucp/v1/checkout/sessions` | Write | Create session |
| GET | `/wp-json/ucp/v1/checkout/sessions/{id}` | Write | Get session |
| PATCH | `/wp-json/ucp/v1/checkout/sessions/{id}` | Write | Update session |
| POST | `/wp-json/ucp/v1/checkout/sessions/{id}/confirm` | Write | Confirm checkout |

### Orders
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wp-json/ucp/v1/orders` | Write | List orders |
| GET | `/wp-json/ucp/v1/orders/{id}` | Write | Get order |
| GET | `/wp-json/ucp/v1/orders/{id}/events` | Write | Order timeline |

### Customers
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/wp-json/ucp/v1/customers` | Write | Create customer |
| GET | `/wp-json/ucp/v1/customers/{id}` | Write | Get customer |
| PATCH | `/wp-json/ucp/v1/customers/{id}` | Write | Update customer |
| GET | `/wp-json/ucp/v1/customers/email/{email}` | Write | Find by email |

### Shipping
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/wp-json/ucp/v1/shipping/rates` | No | Calculate rates |
| GET | `/wp-json/ucp/v1/shipping/methods` | No | List methods |
| GET | `/wp-json/ucp/v1/shipping/zones` | No | List zones |

### Reviews
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wp-json/ucp/v1/reviews` | No | List reviews |
| GET | `/wp-json/ucp/v1/reviews/{id}` | No | Get review |
| POST | `/wp-json/ucp/v1/reviews` | Write | Create review |
| GET | `/wp-json/ucp/v1/reviews/product/{id}/summary` | No | Rating summary |

### Coupons
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wp-json/ucp/v1/coupons` | No | List coupons |
| POST | `/wp-json/ucp/v1/coupons/validate` | No | Validate coupon |
| GET | `/wp-json/ucp/v1/coupons/code/{code}` | No | Get by code |

### API Keys
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/wp-json/ucp/v1/auth/keys` | WP Admin | Create key |
| GET | `/wp-json/ucp/v1/auth/keys` | WP Admin | List keys |
| DELETE | `/wp-json/ucp/v1/auth/keys/{id}` | WP Admin | Delete key |
| GET | `/wp-json/ucp/v1/auth/verify` | Read | Verify key |

---

## 📝 Usage Examples

### 1. Discover Store
```bash
curl https://your-store.com/.well-known/ucp
```

### 2. Browse Products
```bash
curl "https://your-store.com/wp-json/ucp/v1/products?per_page=10&category=15"
```

### 3. Search Products
```bash
curl "https://your-store.com/wp-json/ucp/v1/products/search?q=shirt&min_price=20&max_price=100"
```

### 4. Create Cart & Add Items
```bash
# Create cart
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  https://your-store.com/wp-json/ucp/v1/carts

# Add item to cart
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"product_id": 123, "quantity": 2}' \
  https://your-store.com/wp-json/ucp/v1/carts/{cart_id}/items
```

### 5. Checkout Flow
```bash
# Convert cart to checkout
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "shipping_address": {
      "first_name": "John",
      "last_name": "Doe",
      "address_1": "123 Main St",
      "city": "Taipei",
      "country": "TW"
    },
    "billing_address": {...}
  }' \
  https://your-store.com/wp-json/ucp/v1/carts/{cart_id}/checkout

# Confirm checkout
curl -X POST \
  -H "X-UCP-API-Key: YOUR_API_KEY" \
  https://your-store.com/wp-json/ucp/v1/checkout/sessions/{session_id}/confirm
```

---

## 🔔 Webhooks

### Webhook Signature Verification

All webhook requests include a signature header for verification:

```
X-UCP-Signature: sha256=<hmac_signature>
X-UCP-Event: order.created
X-UCP-Timestamp: 1705234567
```

### Verify Signature (PHP Example)
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_UCP_SIGNATURE'];
$secret = 'your_webhook_secret';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected, $signature)) {
    // Valid webhook
    $data = json_decode($payload, true);
}
```

---

## 🗄️ Database Tables

The plugin creates the following custom tables:

| Table | Purpose |
|-------|---------|
| `wp_ucp_api_keys` | API key storage |
| `wp_ucp_cart_sessions` | Persistent cart data |
| `wp_ucp_checkout_sessions` | Checkout session data |
| `wp_ucp_webhooks` | Webhook configurations |

---

## 🌐 Internationalization

The plugin supports translations. Translation files are located in the `/languages` directory.

- Text Domain: `wc-ucp-agent`
- POT file: `languages/wc-ucp-agent.pot`

---

## 📁 File Structure

```
woocommerce-ucp-agent/
├── woocommerce-ucp-agent.php    # Main plugin file
├── admin/
│   ├── class-ucp-admin.php      # Admin functionality
│   ├── class-ucp-settings.php   # Settings management
│   └── views/
│       └── settings-page.php    # Admin UI template
├── includes/
│   ├── api/                     # REST API controllers
│   │   ├── class-ucp-rest-controller.php
│   │   ├── class-ucp-auth.php
│   │   ├── class-ucp-discovery.php
│   │   ├── class-ucp-products.php
│   │   ├── class-ucp-categories.php
│   │   ├── class-ucp-cart.php
│   │   ├── class-ucp-checkout.php
│   │   ├── class-ucp-orders.php
│   │   ├── class-ucp-customers.php
│   │   ├── class-ucp-shipping.php
│   │   ├── class-ucp-reviews.php
│   │   └── class-ucp-coupons.php
│   ├── models/                  # Data models
│   │   ├── class-ucp-api-key.php
│   │   └── class-ucp-cart-session.php
│   ├── webhooks/                # Webhook handling
│   │   ├── class-ucp-webhook-manager.php
│   │   └── class-ucp-webhook-sender.php
│   ├── class-ucp-activator.php
│   ├── class-ucp-deactivator.php
│   ├── class-ucp-loader.php
│   └── class-ucp-i18n.php
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
└── languages/
    └── wc-ucp-agent.pot
```

---

## 🔧 Hooks & Filters

### Actions
```php
// Webhook delivery failed
do_action('wc_ucp_webhook_delivery_failed', $webhook, $error);
```

### Filters
```php
// Modify webhook SSL verification
apply_filters('wc_ucp_webhook_ssl_verify', true);
```

---

## 🛠️ Troubleshooting

### API Returns 404
- Ensure you're using the correct URL: `/wp-json/ucp/v1/...`
- Flush permalinks: **Settings → Permalinks → Save Changes**

### Authentication Fails
- Verify API key format: `key_id:secret`
- Check key permissions match required endpoint access
- Ensure key hasn't been deleted

### Cart/Checkout Expires
- Adjust expiry times in **WooCommerce → UCP → General**
- Default: Cart = 24 hours, Checkout = 30 minutes

---

## 📄 License

This plugin is licensed under the GPL2 license. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

## 👨‍💻 Author

**Roger Deng**

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📞 Support

For support, please create an issue in the GitHub repository.
