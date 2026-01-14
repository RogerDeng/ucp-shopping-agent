<?php
/**
 * Settings Class
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Settings
{

    /**
     * Get all settings with defaults
     */
    public static function get_all()
    {
        return array(
            'wc_ucp_enabled' => get_option('wc_ucp_enabled', 'yes'),
            'wc_ucp_cart_expiry_hours' => get_option('wc_ucp_cart_expiry_hours', 24),
            'wc_ucp_checkout_expiry' => get_option('wc_ucp_checkout_expiry', 30),
            'wc_ucp_rate_limit' => get_option('wc_ucp_rate_limit', 100),
            'wc_ucp_log_enabled' => get_option('wc_ucp_log_enabled', 'no'),
        );
    }

    /**
     * Get single setting
     */
    public static function get($key, $default = null)
    {
        $settings = self::get_all();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Update setting
     */
    public static function update($key, $value)
    {
        return update_option($key, $value);
    }

    /**
     * Get settings fields
     */
    public static function get_fields()
    {
        return array(
            'general' => array(
                'title' => __('General Settings', 'ucp-shopping-agent'),
                'fields' => array(
                    array(
                        'id' => 'wc_ucp_enabled',
                        'title' => __('Enable UCP', 'ucp-shopping-agent'),
                        'type' => 'checkbox',
                        'description' => __('Enable the UCP API endpoints.', 'ucp-shopping-agent'),
                        'default' => 'yes',
                    ),
                    array(
                        'id' => 'wc_ucp_rate_limit',
                        'title' => __('Rate Limit', 'ucp-shopping-agent'),
                        'type' => 'number',
                        'description' => __('Maximum requests per minute per API key.', 'ucp-shopping-agent'),
                        'default' => 100,
                        'min' => 10,
                        'max' => 1000,
                    ),
                ),
            ),
            'cart' => array(
                'title' => __('Cart & Checkout', 'ucp-shopping-agent'),
                'fields' => array(
                    array(
                        'id' => 'wc_ucp_cart_expiry_hours',
                        'title' => __('Cart Expiry', 'ucp-shopping-agent'),
                        'type' => 'number',
                        'description' => __('Hours until an inactive cart expires.', 'ucp-shopping-agent'),
                        'default' => 24,
                        'min' => 1,
                        'max' => 168,
                        'suffix' => __('hours', 'ucp-shopping-agent'),
                    ),
                    array(
                        'id' => 'wc_ucp_checkout_expiry',
                        'title' => __('Checkout Expiry', 'ucp-shopping-agent'),
                        'type' => 'number',
                        'description' => __('Minutes until a checkout session expires.', 'ucp-shopping-agent'),
                        'default' => 30,
                        'min' => 5,
                        'max' => 120,
                        'suffix' => __('minutes', 'ucp-shopping-agent'),
                    ),
                ),
            ),
            'advanced' => array(
                'title' => __('Advanced', 'ucp-shopping-agent'),
                'fields' => array(
                    array(
                        'id' => 'wc_ucp_log_enabled',
                        'title' => __('Enable Logging', 'ucp-shopping-agent'),
                        'type' => 'checkbox',
                        'description' => __('Log API requests and webhook deliveries for debugging.', 'ucp-shopping-agent'),
                        'default' => 'no',
                    ),
                ),
            ),
        );
    }
}
