<?php
/**
 * Plugin Activator
 *
 * Creates database tables and sets up initial configuration.
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Activator
{

    /**
     * Activate the plugin
     */
    public static function activate()
    {
        self::create_tables();
        self::create_options();
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // API Keys table
        $api_keys_table = $wpdb->prefix . 'ucp_api_keys';
        $sql_api_keys = "CREATE TABLE IF NOT EXISTS $api_keys_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            key_id VARCHAR(32) NOT NULL,
            secret_hash VARCHAR(255) NOT NULL,
            description VARCHAR(200) DEFAULT '',
            permissions VARCHAR(20) DEFAULT 'read',
            user_id BIGINT UNSIGNED DEFAULT NULL,
            last_access DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_key_id (key_id),
            KEY idx_user_id (user_id)
        ) $charset_collate;";

        // Cart Sessions table
        $cart_sessions_table = $wpdb->prefix . 'ucp_cart_sessions';
        $sql_cart_sessions = "CREATE TABLE IF NOT EXISTS $cart_sessions_table (
            id VARCHAR(36) PRIMARY KEY,
            api_key_id BIGINT UNSIGNED DEFAULT NULL,
            items LONGTEXT,
            shipping_address LONGTEXT DEFAULT NULL,
            billing_address LONGTEXT DEFAULT NULL,
            coupon_codes LONGTEXT DEFAULT NULL,
            status ENUM('active','checkout','converted','expired') DEFAULT 'active',
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_expires (expires_at),
            KEY idx_api_key (api_key_id)
        ) $charset_collate;";

        // Checkout Sessions table
        $checkout_sessions_table = $wpdb->prefix . 'ucp_checkout_sessions';
        $sql_checkout_sessions = "CREATE TABLE IF NOT EXISTS $checkout_sessions_table (
            id VARCHAR(36) PRIMARY KEY,
            cart_id VARCHAR(36) DEFAULT NULL,
            api_key_id BIGINT UNSIGNED DEFAULT NULL,
            customer_id BIGINT UNSIGNED DEFAULT NULL,
            items LONGTEXT,
            shipping_address LONGTEXT DEFAULT NULL,
            billing_address LONGTEXT DEFAULT NULL,
            shipping_method VARCHAR(100) DEFAULT NULL,
            payment_method VARCHAR(100) DEFAULT NULL,
            coupon_codes LONGTEXT DEFAULT NULL,
            totals LONGTEXT DEFAULT NULL,
            status ENUM('pending','ready','confirmed','failed','expired') DEFAULT 'pending',
            order_id BIGINT UNSIGNED DEFAULT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_cart_id (cart_id),
            KEY idx_order_id (order_id)
        ) $charset_collate;";

        // Webhooks table
        $webhooks_table = $wpdb->prefix . 'ucp_webhooks';
        $sql_webhooks = "CREATE TABLE IF NOT EXISTS $webhooks_table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            api_key_id BIGINT UNSIGNED NOT NULL,
            url VARCHAR(500) NOT NULL,
            events LONGTEXT NOT NULL,
            secret VARCHAR(64) NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_api_key (api_key_id),
            KEY idx_status (status)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_api_keys);
        dbDelta($sql_cart_sessions);
        dbDelta($sql_checkout_sessions);
        dbDelta($sql_webhooks);

        // Store database version
        update_option('wc_ucp_db_version', WC_UCP_VERSION);
    }

    /**
     * Create default options
     */
    private static function create_options()
    {
        $default_options = array(
            'wc_ucp_enabled' => 'yes',
            'wc_ucp_cart_expiry_hours' => 24,
            'wc_ucp_checkout_expiry' => 30, // minutes
            'wc_ucp_rate_limit' => 100, // requests per minute
            'wc_ucp_log_enabled' => 'no',
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Add rewrite rules
     */
    private static function add_rewrite_rules()
    {
        add_rewrite_rule(
            '^\.well-known/ucp/?$',
            'index.php?rest_route=/ucp/v1/discovery',
            'top'
        );
    }
}
