<?php
/**
 * Settings Page Template
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = WC_UCP_Settings::get_all();
$api_key_model = new WC_UCP_API_Key();
$api_keys = $api_key_model->get_all();
?>

<div class="wrap wc-ucp-settings">
    <h1>
        <?php esc_html_e('WooCommerce UCP Settings', 'ucp-shopping-agent'); ?>
    </h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=wc-ucp-settings&tab=general"
            class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General', 'ucp-shopping-agent'); ?>
        </a>
        <a href="?page=wc-ucp-settings&tab=api-keys"
            class="nav-tab <?php echo $active_tab === 'api-keys' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('API Keys', 'ucp-shopping-agent'); ?>
        </a>
        <a href="?page=wc-ucp-settings&tab=discovery"
            class="nav-tab <?php echo $active_tab === 'discovery' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Discovery', 'ucp-shopping-agent'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ($active_tab === 'general'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('wc_ucp_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Enable UCP', 'ucp-shopping-agent'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_ucp_enabled" value="yes" <?php checked($settings['wc_ucp_enabled'], 'yes'); ?>>
                                <?php esc_html_e('Enable the UCP API endpoints', 'ucp-shopping-agent'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Rate Limit', 'ucp-shopping-agent'); ?>
                        </th>
                        <td>
                            <input type="number" name="wc_ucp_rate_limit"
                                value="<?php echo esc_attr($settings['wc_ucp_rate_limit']); ?>" min="10" max="1000">
                            <p class="description">
                                <?php esc_html_e('Maximum requests per minute per API key.', 'ucp-shopping-agent'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Cart Expiry', 'ucp-shopping-agent'); ?>
                        </th>
                        <td>
                            <input type="number" name="wc_ucp_cart_expiry_hours"
                                value="<?php echo esc_attr($settings['wc_ucp_cart_expiry_hours']); ?>" min="1" max="168">
                            <?php esc_html_e('hours', 'ucp-shopping-agent'); ?>
                            <p class="description">
                                <?php esc_html_e('Hours until an inactive cart expires.', 'ucp-shopping-agent'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Checkout Expiry', 'ucp-shopping-agent'); ?>
                        </th>
                        <td>
                            <input type="number" name="wc_ucp_checkout_expiry"
                                value="<?php echo esc_attr($settings['wc_ucp_checkout_expiry']); ?>" min="5" max="120">
                            <?php esc_html_e('minutes', 'ucp-shopping-agent'); ?>
                            <p class="description">
                                <?php esc_html_e('Minutes until a checkout session expires.', 'ucp-shopping-agent'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Enable Logging', 'ucp-shopping-agent'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_ucp_log_enabled" value="yes" <?php checked($settings['wc_ucp_log_enabled'], 'yes'); ?>>
                                <?php esc_html_e('Log API requests and webhook deliveries for debugging', 'ucp-shopping-agent'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

        <?php elseif ($active_tab === 'api-keys'): ?>
            <div class="api-keys-section">
                <h2>
                    <?php esc_html_e('API Keys', 'ucp-shopping-agent'); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('API keys allow AI agents to authenticate with your store. Create keys with appropriate permissions.', 'ucp-shopping-agent'); ?>
                </p>

                <div class="create-api-key-form">
                    <h3>
                        <?php esc_html_e('Create New API Key', 'ucp-shopping-agent'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Description', 'ucp-shopping-agent'); ?>
                            </th>
                            <td>
                                <input type="text" id="api-key-description"
                                    placeholder="<?php esc_attr_e('e.g., My AI Assistant', 'ucp-shopping-agent'); ?>"
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Permissions', 'ucp-shopping-agent'); ?>
                            </th>
                            <td>
                                <select id="api-key-permissions">
                                    <option value="read">
                                        <?php esc_html_e('Read - Browse products, categories, reviews', 'ucp-shopping-agent'); ?>
                                    </option>
                                    <option value="write">
                                        <?php esc_html_e('Write - Create carts, checkout sessions, orders', 'ucp-shopping-agent'); ?>
                                    </option>
                                    <option value="admin">
                                        <?php esc_html_e('Admin - Full access including API key management', 'ucp-shopping-agent'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="button" id="create-api-key" class="button button-primary">
                        <?php esc_html_e('Generate API Key', 'ucp-shopping-agent'); ?>
                    </button>
                </div>

                <div id="new-api-key-display" style="display: none;">
                    <div class="notice notice-success">
                        <h4>
                            <?php esc_html_e('API Key Generated Successfully!', 'ucp-shopping-agent'); ?>
                        </h4>
                        <p>
                            <?php esc_html_e('Copy this key now. The secret will not be shown again.', 'ucp-shopping-agent'); ?>
                        </p>
                        <code id="new-api-key-value"></code>
                        <button type="button" class="button copy-to-clipboard" data-target="#new-api-key-value">
                            <?php esc_html_e('Copy', 'ucp-shopping-agent'); ?>
                        </button>
                    </div>
                </div>

                <h3>
                    <?php esc_html_e('Existing API Keys', 'ucp-shopping-agent'); ?>
                </h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php esc_html_e('Key ID', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Description', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Permissions', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Last Used', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Created', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Actions', 'ucp-shopping-agent'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="api-keys-list">
                        <?php if (empty($api_keys)): ?>
                            <tr>
                                <td colspan="6">
                                    <?php esc_html_e('No API keys found. Create one above.', 'ucp-shopping-agent'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($api_keys as $key): ?>
                                <tr data-key-id="<?php echo esc_attr($key->id); ?>">
                                    <td><code><?php echo esc_html($key->key_id); ?></code></td>
                                    <td>
                                        <?php echo esc_html($key->description ?: '—'); ?>
                                    </td>
                                    <td>
                                        <span class="permission-badge permission-<?php echo esc_attr($key->permissions); ?>">
                                            <?php echo esc_html(ucfirst($key->permissions)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $key->last_access ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($key->last_access))) : '—'; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($key->created_at))); ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small delete-api-key"
                                            data-key-id="<?php echo esc_attr($key->id); ?>">
                                            <?php esc_html_e('Delete', 'ucp-shopping-agent'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($active_tab === 'discovery'): ?>
            <div class="discovery-section">
                <h2>
                    <?php esc_html_e('Discovery Endpoint', 'ucp-shopping-agent'); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('AI agents can discover your store capabilities at the following URL:', 'ucp-shopping-agent'); ?>
                </p>

                <div class="discovery-url-box">
                    <code id="discovery-url"><?php echo esc_url(home_url('/.well-known/ucp')); ?></code>
                    <button type="button" class="button copy-to-clipboard" data-target="#discovery-url">
                        <?php esc_html_e('Copy', 'ucp-shopping-agent'); ?>
                    </button>
                    <a href="<?php echo esc_url(home_url('/.well-known/ucp')); ?>" target="_blank" class="button">
                        <?php esc_html_e('Test', 'ucp-shopping-agent'); ?>
                    </a>
                </div>

                <h3>
                    <?php esc_html_e('Quick Start Guide', 'ucp-shopping-agent'); ?>
                </h3>
                <ol class="quick-start">
                    <li>
                        <strong>
                            <?php esc_html_e('Generate an API Key', 'ucp-shopping-agent'); ?>
                        </strong>
                        <p>
                            <?php esc_html_e('Go to the API Keys tab and create a new key with appropriate permissions.', 'ucp-shopping-agent'); ?>
                        </p>
                    </li>
                    <li>
                        <strong>
                            <?php esc_html_e('Verify Discovery', 'ucp-shopping-agent'); ?>
                        </strong>
                        <pre>curl <?php echo esc_url(home_url('/.well-known/ucp')); ?></pre>
                    </li>
                    <li>
                        <strong>
                            <?php esc_html_e('Authenticate Requests', 'ucp-shopping-agent'); ?>
                        </strong>
                        <pre>curl -H "X-UCP-API-Key: YOUR_KEY_ID:YOUR_SECRET" \
      <?php echo esc_url(get_rest_url(null, 'ucp/v1/products')); ?></pre>
                    </li>
                </ol>

                <h3>
                    <?php esc_html_e('Available Endpoints', 'ucp-shopping-agent'); ?>
                </h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php esc_html_e('Endpoint', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Description', 'ucp-shopping-agent'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Auth Required', 'ucp-shopping-agent'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>/ucp/v1/discovery</code></td>
                            <td>
                                <?php esc_html_e('Store capabilities and info', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/products</code></td>
                            <td>
                                <?php esc_html_e('Browse and search products', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/categories</code></td>
                            <td>
                                <?php esc_html_e('Product categories', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/carts</code></td>
                            <td>
                                <?php esc_html_e('Cart management', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/checkout/sessions</code></td>
                            <td>
                                <?php esc_html_e('Checkout sessions', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/orders</code></td>
                            <td>
                                <?php esc_html_e('Order details', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/customers</code></td>
                            <td>
                                <?php esc_html_e('Customer management', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/shipping/rates</code></td>
                            <td>
                                <?php esc_html_e('Shipping calculation', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/reviews</code></td>
                            <td>
                                <?php esc_html_e('Product reviews', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No/Write', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/coupons</code></td>
                            <td>
                                <?php esc_html_e('Coupon validation', 'ucp-shopping-agent'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'ucp-shopping-agent'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>