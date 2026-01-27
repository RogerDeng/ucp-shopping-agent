<?php
/**
 * Plugin Activator
 *
 * Creates database tables and sets up initial configuration.
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Activator
{

    /**
     * Cron hook name for webhook retry
     *
     * @var string
     */
    const CRON_HOOK = 'shopping_agent_ucp_retry_failed_webhooks';

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
        $api_keys_table = $wpdb->prefix . 'shopping_agent_ucp_api_keys';
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
        $cart_sessions_table = $wpdb->prefix . 'shopping_agent_ucp_cart_sessions';
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
        $checkout_sessions_table = $wpdb->prefix . 'shopping_agent_ucp_checkout_sessions';
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
        $webhooks_table = $wpdb->prefix . 'shopping_agent_ucp_webhooks';
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
        update_option('shopping_agent_ucp_db_version', SHOPPING_AGENT_UCP_VERSION);
    }

    /**
     * Create default options
     */
    private static function create_options()
    {
        $default_options = array(
            'shopping_agent_ucp_enabled' => 'yes',
            'shopping_agent_ucp_cart_expiry_hours' => 24,
            'shopping_agent_ucp_checkout_expiry' => 30, // minutes
            'shopping_agent_ucp_rate_limit' => 100, // requests per minute
            'shopping_agent_ucp_log_enabled' => 'no',
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Generate and store Ed25519 signing keypair for webhooks
     * Uses libsodium for cryptographic operations
     */
    private static function generate_signing_key()
    {
        // Only generate if not already set
        if (get_option('shopping_agent_ucp_signing_public_key') !== false) {
            return;
        }

        // Check for libsodium support
        if (!function_exists('sodium_crypto_sign_keypair')) {
            // Fallback: store a flag indicating keys need manual generation
            add_option('shopping_agent_ucp_signing_key_error', 'libsodium_not_available');
            return;
        }

        // Generate Ed25519 keypair
        $keypair = sodium_crypto_sign_keypair();
        $secret_key = sodium_crypto_sign_secretkey($keypair);
        $public_key = sodium_crypto_sign_publickey($keypair);

        // Generate a unique Key ID with timestamp
        $kid = 'ed25519-' . date('Y-m') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

        // Store keys securely
        add_option('shopping_agent_ucp_signing_private_key', base64_encode($secret_key));
        add_option('shopping_agent_ucp_signing_public_key', base64_encode($public_key));
        add_option('shopping_agent_ucp_signing_key_kid', $kid);
        add_option('shopping_agent_ucp_signing_key_created_at', current_time('c'));

        // Clean up memory
        sodium_memzero($keypair);
        sodium_memzero($secret_key);
    }

    /**
     * Get the Ed25519 private key for signing
     *
     * @return string|null The private key (base64 encoded) or null if not set.
     */
    public static function get_signing_private_key()
    {
        $key = get_option('shopping_agent_ucp_signing_private_key', null);
        return $key ? base64_decode($key) : null;
    }

    /**
     * Get the Ed25519 public key
     *
     * @return string|null The public key (raw bytes) or null if not set.
     */
    public static function get_signing_public_key()
    {
        $key = get_option('shopping_agent_ucp_signing_public_key', null);
        return $key ? base64_decode($key) : null;
    }

    /**
     * Get the Key ID (kid)
     *
     * @return string|null The key ID or null if not set.
     */
    public static function get_signing_key_kid()
    {
        return get_option('shopping_agent_ucp_signing_key_kid', null);
    }

    /**
     * Base64URL encode (for JWK format)
     *
     * @param string $data Data to encode.
     * @return string Base64URL encoded string.
     */
    private static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get signing key info for discovery endpoint (JWK format)
     * Compliant with UCP/Beckn Ed25519 specification
     *
     * @return array Signing keys in JWK format.
     */
    public static function get_signing_key_info()
    {
        $public_key = self::get_signing_public_key();
        $kid = self::get_signing_key_kid();

        if (!$public_key || !$kid) {
            // Check for error
            $error = get_option('shopping_agent_ucp_signing_key_error');
            if ($error === 'libsodium_not_available') {
                return array(
                    array(
                        'error' => 'libsodium_not_available',
                        'message' => 'PHP libsodium extension is required for Ed25519 signing',
                    ),
                );
            }
            return array();
        }

        // Return JWK format per RFC 8037 (CFRG Elliptic Curve)
        return array(
            array(
                'kid' => $kid,
                'kty' => 'OKP',              // Octet Key Pair
                'crv' => 'Ed25519',          // Edwards-curve signature algorithm
                'x' => self::base64url_encode($public_key), // Public key (base64url)
                'use' => 'sig',              // Signature use
                'alg' => 'EdDSA',            // Edwards-curve Digital Signature Algorithm
                'status' => 'active',
                'created_at' => get_option('shopping_agent_ucp_signing_key_created_at', current_time('c')),
            ),
        );
    }

    /**
     * Rotate signing key (generate new keypair, keep old for transition)
     *
     * @return bool True if rotation successful.
     */
    public static function rotate_signing_key()
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            return false;
        }

        // Backup current key
        $old_public = get_option('shopping_agent_ucp_signing_public_key');
        $old_kid = get_option('shopping_agent_ucp_signing_key_kid');
        $old_created = get_option('shopping_agent_ucp_signing_key_created_at');

        if ($old_public && $old_kid) {
            update_option('shopping_agent_ucp_signing_public_key_previous', $old_public);
            update_option('shopping_agent_ucp_signing_key_kid_previous', $old_kid);
            update_option('shopping_agent_ucp_signing_key_created_at_previous', $old_created);
        }

        // Generate new keypair
        $keypair = sodium_crypto_sign_keypair();
        $secret_key = sodium_crypto_sign_secretkey($keypair);
        $public_key = sodium_crypto_sign_publickey($keypair);
        $kid = 'ed25519-' . date('Y-m') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

        update_option('shopping_agent_ucp_signing_private_key', base64_encode($secret_key));
        update_option('shopping_agent_ucp_signing_public_key', base64_encode($public_key));
        update_option('shopping_agent_ucp_signing_key_kid', $kid);
        update_option('shopping_agent_ucp_signing_key_created_at', current_time('c'));

        // Clean up memory
        sodium_memzero($keypair);
        sodium_memzero($secret_key);

        return true;
    }

    /**
     * Legacy: Get the old signing key (for backward compatibility)
     *
     * @return string|null The signing key or null if not set.
     */
    public static function get_signing_key()
    {
        // Return the kid for identification purposes
        return self::get_signing_key_kid();
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
            'display' => __('Every 15 Minutes', 'shopping-agent-with-ucp'),
        );
        return $schedules;
    }

    /**
     * Retry failed webhooks (called by WP-Cron)
     */
    public static function run_webhook_retry()
    {
        $sender = new Shopping_Agent_UCP_Webhook_Sender();
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

