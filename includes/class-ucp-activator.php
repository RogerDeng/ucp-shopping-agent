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
     * Cron hook name for webhook retry
     *
     * @var string
     */
    const CRON_HOOK = 'wc_ucp_retry_failed_webhooks';

    /**
     * Activate the plugin
     */
    public static function activate()
    {
        self::create_tables();
        self::create_options();
        self::generate_signing_key();
        self::schedule_cron_jobs();
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Deactivate the plugin - clear scheduled events
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
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
            status ENUM('pending','ready','confirmed','complete','failed','expired') DEFAULT 'pending',
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
     * Generate and store signing key for webhooks
     */
    private static function generate_signing_key()
    {
        // Only generate if not already set
        if (get_option('wc_ucp_signing_key') !== false) {
            return;
        }

        // Generate a secure random key
        $signing_key = bin2hex(random_bytes(32));
        add_option('wc_ucp_signing_key', $signing_key);
        add_option('wc_ucp_signing_key_created_at', current_time('c'));
    }

    /**
     * Get the signing key
     *
     * @return string|null The signing key or null if not set.
     */
    public static function get_signing_key()
    {
        return get_option('wc_ucp_signing_key', null);
    }

    /**
     * Get signing key info for discovery
     *
     * @return array Signing key metadata for JWK-style response.
     */
    public static function get_signing_key_info()
    {
        $signing_key = self::get_signing_key();

        if (!$signing_key) {
            return array();
        }

        // Generate a key ID from the key (partial hash)
        $key_id = substr(hash('sha256', $signing_key), 0, 16);

        return array(
            array(
                'key_id' => 'ucp_' . $key_id,
                'algorithm' => 'HS256',
                'use' => 'sig',
                'status' => 'active',
                'created_at' => get_option('wc_ucp_signing_key_created_at', current_time('c')),
            ),
        );
    }

    /**
     * Schedule cron jobs
     */
    private static function schedule_cron_jobs()
    {
        // Schedule webhook retry every 15 minutes
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'fifteen_minutes', self::CRON_HOOK);
        }
    }

    /**
     * Add custom cron schedule
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public static function add_cron_schedules($schedules)
    {
        $schedules['fifteen_minutes'] = array(
            'interval' => 900, // 15 minutes in seconds
            'display' => __('Every 15 Minutes', 'ucp-shopping-agent'),
        );
        return $schedules;
    }

    /**
     * Retry failed webhooks (called by WP-Cron)
     */
    public static function run_webhook_retry()
    {
        $sender = new WC_UCP_Webhook_Sender();
        $sender->retry_failed_webhooks();
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

