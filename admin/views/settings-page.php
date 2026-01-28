<?php
/**
 * Settings Page Template
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}


// Variables passed from Shopping_Agent_UCP_Admin::render_settings_page():
// $settings, $api_key_model, $api_keys, $active_tab
?>

<div class="wrap shopping-agent-ucp-settings">
    <h1>
        <?php esc_html_e('Shopping Agent UCP Setting', 'shopping-agent-with-ucp'); ?>
    </h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=shopping-agent-ucp-settings&tab=general"
            class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('General', 'shopping-agent-with-ucp'); ?>
        </a>
        <a href="?page=shopping-agent-ucp-settings&tab=api-keys"
            class="nav-tab <?php echo $active_tab === 'api-keys' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('API Keys', 'shopping-agent-with-ucp'); ?>
        </a>
        <a href="?page=shopping-agent-ucp-settings&tab=discovery"
            class="nav-tab <?php echo $active_tab === 'discovery' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Discovery', 'shopping-agent-with-ucp'); ?>
        </a>
    </nav>

    <div class="tab-content">
        <?php if ($active_tab === 'general'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('shopping_agent_ucp_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Enable UCP', 'shopping-agent-with-ucp'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="shopping_agent_ucp_enabled" value="yes" <?php checked($settings['shopping_agent_ucp_enabled'], 'yes'); ?>>
                                <?php esc_html_e('Enable the UCP API endpoints', 'shopping-agent-with-ucp'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Rate Limit', 'shopping-agent-with-ucp'); ?>
                        </th>
                        <td>
                            <input type="number" name="shopping_agent_ucp_rate_limit"
                                value="<?php echo esc_attr($settings['shopping_agent_ucp_rate_limit']); ?>"
                                min="10" max="1000">
                            <p class="description">
                                <?php esc_html_e('Maximum requests per minute per API key.', 'shopping-agent-with-ucp'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Cart Expiry', 'shopping-agent-with-ucp'); ?>
                        </th>
                        <td>
                            <input type="number" name="shopping_agent_ucp_cart_expiry_hours"
                                value="<?php echo esc_attr($settings['shopping_agent_ucp_cart_expiry_hours']); ?>"
                                min="1" max="168">
                            <?php esc_html_e('hours', 'shopping-agent-with-ucp'); ?>
                            <p class="description">
                                <?php esc_html_e('Hours until an inactive cart expires.', 'shopping-agent-with-ucp'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Checkout Expiry', 'shopping-agent-with-ucp'); ?>
                        </th>
                        <td>
                            <input type="number" name="shopping_agent_ucp_checkout_expiry"
                                value="<?php echo esc_attr($settings['shopping_agent_ucp_checkout_expiry']); ?>"
                                min="5" max="120">
                            <?php esc_html_e('minutes', 'shopping-agent-with-ucp'); ?>
                            <p class="description">
                                <?php esc_html_e('Minutes until a checkout session expires.', 'shopping-agent-with-ucp'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Enable Logging', 'shopping-agent-with-ucp'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="shopping_agent_ucp_log_enabled" value="yes"
                                    <?php checked($settings['shopping_agent_ucp_log_enabled'], 'yes'); ?>>
                                <?php esc_html_e('Log API requests and webhook deliveries for debugging', 'shopping-agent-with-ucp'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

        <?php elseif ($active_tab === 'api-keys'): ?>
            <div class="api-keys-section">
                <h2>
                    <?php esc_html_e('API Keys', 'shopping-agent-with-ucp'); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('API keys allow AI agents to authenticate with your store. Create keys with appropriate permissions.', 'shopping-agent-with-ucp'); ?>
                </p>

                <div class="create-api-key-form">
                    <h3>
                        <?php esc_html_e('Create New API Key', 'shopping-agent-with-ucp'); ?>
                    </h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Description', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <td>
                                <input type="text" id="shopping-agent-ucp-api-key-description"
                                    placeholder="<?php esc_attr_e('e.g., My AI Assistant', 'shopping-agent-with-ucp'); ?>"
                                    class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php esc_html_e('Permissions', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <td>
                                <select id="shopping-agent-ucp-api-key-permissions">
                                    <option value="read">
                                        <?php esc_html_e('Read - Browse products, categories, reviews', 'shopping-agent-with-ucp'); ?>
                                    </option>
                                    <option value="write">
                                        <?php esc_html_e('Write - Create carts, checkout sessions, orders', 'shopping-agent-with-ucp'); ?>
                                    </option>
                                    <option value="admin">
                                        <?php esc_html_e('Admin - Full access including API key management', 'shopping-agent-with-ucp'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="button" id="shopping-agent-ucp-create-api-key" class="button button-primary">
                        <?php esc_html_e('Generate API Key', 'shopping-agent-with-ucp'); ?>
                    </button>
                </div>

                <div id="shopping-agent-ucp-new-api-key-display" style="display: none;">
                    <div class="notice notice-success">
                        <h4>
                            <?php esc_html_e('API Key Generated Successfully!', 'shopping-agent-with-ucp'); ?>
                        </h4>
                        <p>
                            <?php esc_html_e('Copy this key now. The secret will not be shown again.', 'shopping-agent-with-ucp'); ?>
                        </p>
                        <div class="api-key-display-wrapper"
                            style="display: flex; align-items: center; gap: 10px; margin: 10px 0;">
                            <code id="shopping-agent-ucp-new-api-key-value"
                                style="flex: 1; padding: 10px 15px; background: #f0f0f1; border: 1px solid #c3c4c7; font-size: 14px; word-break: break-all;"></code>
                            <button type="button" class="button copy-to-clipboard" data-target="#shopping-agent-ucp-new-api-key-value">
                                <?php esc_html_e('Copy', 'shopping-agent-with-ucp'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <h3>
                    <?php esc_html_e('Existing API Keys', 'shopping-agent-with-ucp'); ?>
                </h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php esc_html_e('Key ID', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Description', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Permissions', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Last Used', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Created', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Actions', 'shopping-agent-with-ucp'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="api-keys-list">
                        <?php if (empty($api_keys)): ?>
                            <tr>
                                <td colspan="6">
                                    <?php esc_html_e('No API keys found. Create one above.', 'shopping-agent-with-ucp'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($api_keys as $shopping_agent_ucp_api_key_item): // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
                                <tr data-key-id="<?php echo esc_attr($shopping_agent_ucp_api_key_item->id); ?>">
                                    <td><code><?php echo esc_html($shopping_agent_ucp_api_key_item->key_id); ?></code></td>
                                    <td>
                                        <?php echo esc_html($shopping_agent_ucp_api_key_item->description ?: '—'); ?>
                                    </td>
                                    <td>
                                        <span class="permission-badge permission-<?php echo esc_attr($shopping_agent_ucp_api_key_item->permissions); ?>">
                                            <?php echo esc_html(ucfirst($shopping_agent_ucp_api_key_item->permissions)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $shopping_agent_ucp_api_key_item->last_access ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($shopping_agent_ucp_api_key_item->last_access))) : '—'; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($shopping_agent_ucp_api_key_item->created_at))); ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small delete-api-key"
                                            data-key-id="<?php echo esc_attr($shopping_agent_ucp_api_key_item->id); ?>">
                                            <?php esc_html_e('Delete', 'shopping-agent-with-ucp'); ?>
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
                    <?php esc_html_e('Discovery Endpoint', 'shopping-agent-with-ucp'); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('AI agents can discover your store capabilities at the following URL:', 'shopping-agent-with-ucp'); ?>
                </p>

                <div class="discovery-url-box">
                    <code id="shopping-agent-ucp-discovery-url"><?php echo esc_url(home_url('/.well-known/ucp')); ?></code>
                    <button type="button" class="button copy-to-clipboard" data-target="#shopping-agent-ucp-discovery-url">
                        <?php esc_html_e('Copy', 'shopping-agent-with-ucp'); ?>
                    </button>
                    <a href="<?php echo esc_url(home_url('/.well-known/ucp')); ?>" target="_blank" class="button">
                        <?php esc_html_e('Test', 'shopping-agent-with-ucp'); ?>
                    </a>
                </div>

                <h3>
                    <?php esc_html_e('Quick Start Guide', 'shopping-agent-with-ucp'); ?>
                </h3>
                <ol class="quick-start">
                    <li>
                        <strong>
                            <?php esc_html_e('Generate an API Key', 'shopping-agent-with-ucp'); ?>
                        </strong>
                        <p>
                            <?php esc_html_e('Go to the API Keys tab and create a new key with appropriate permissions.', 'shopping-agent-with-ucp'); ?>
                        </p>
                    </li>
                    <li>
                        <strong>
                            <?php esc_html_e('Verify Discovery', 'shopping-agent-with-ucp'); ?>
                        </strong>
                        <pre>curl <?php echo esc_url(home_url('/.well-known/ucp')); ?></pre>
                    </li>
                    <li>
                        <strong>
                            <?php esc_html_e('Authenticate Requests', 'shopping-agent-with-ucp'); ?>
                        </strong>
                        <pre>curl -H "X-UCP-API-Key: YOUR_KEY_ID:YOUR_SECRET" \
                      <?php echo esc_url(get_rest_url(null, 'ucp/v1/products')); ?></pre>
                    </li>
                </ol>

                <h3>
                    <?php esc_html_e('Available Endpoints', 'shopping-agent-with-ucp'); ?>
                </h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php esc_html_e('Endpoint', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Description', 'shopping-agent-with-ucp'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Auth Required', 'shopping-agent-with-ucp'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>/ucp/v1/discovery</code></td>
                            <td>
                                <?php esc_html_e('Store capabilities and info', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/products</code></td>
                            <td>
                                <?php esc_html_e('Browse and search products', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/categories</code></td>
                            <td>
                                <?php esc_html_e('Product categories', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/carts</code></td>
                            <td>
                                <?php esc_html_e('Cart management', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/checkout/sessions</code></td>
                            <td>
                                <?php esc_html_e('Checkout sessions', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/orders</code></td>
                            <td>
                                <?php esc_html_e('Order details', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/customers</code></td>
                            <td>
                                <?php esc_html_e('Customer management', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('Write', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/shipping/rates</code></td>
                            <td>
                                <?php esc_html_e('Shipping calculation', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/reviews</code></td>
                            <td>
                                <?php esc_html_e('Product reviews', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No/Write', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>/ucp/v1/coupons</code></td>
                            <td>
                                <?php esc_html_e('Coupon validation', 'shopping-agent-with-ucp'); ?>
                            </td>
                            <td>
                                <?php esc_html_e('No', 'shopping-agent-with-ucp'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>