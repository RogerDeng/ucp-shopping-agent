<?php
/**
 * Settings Class
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Settings
{

    /**
     * Get all settings with defaults
     */
    public static function get_all()
    {
        return array(
            'shopping_agent_ucp_enabled' => get_option('shopping_agent_ucp_enabled', 'yes'),
            'shopping_agent_ucp_cart_expiry_hours' => get_option('shopping_agent_ucp_cart_expiry_hours', 24),
            'shopping_agent_ucp_checkout_expiry' => get_option('shopping_agent_ucp_checkout_expiry', 30),
            'shopping_agent_ucp_rate_limit' => get_option('shopping_agent_ucp_rate_limit', 100),
            'shopping_agent_ucp_log_enabled' => get_option('shopping_agent_ucp_log_enabled', 'no'),
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
                'title' => __('General Settings', 'shopping-agent-with-ucp'),
                'fields' => array(
                    array(
                        'id' => 'shopping_agent_ucp_enabled',
                        'title' => __('Enable UCP', 'shopping-agent-with-ucp'),
                        'type' => 'checkbox',
                        'description' => __('Enable the UCP API endpoints.', 'shopping-agent-with-ucp'),
                        'default' => 'yes',
                    ),
                    array(
                        'id' => 'shopping_agent_ucp_rate_limit',
                        'title' => __('Rate Limit', 'shopping-agent-with-ucp'),
                        'type' => 'number',
                        'description' => __('Maximum requests per minute per API key.', 'shopping-agent-with-ucp'),
                        'default' => 100,
                        'min' => 10,
                        'max' => 1000,
                    ),
                ),
            ),
            'cart' => array(
                'title' => __('Cart & Checkout', 'shopping-agent-with-ucp'),
                'fields' => array(
                    array(
                        'id' => 'shopping_agent_ucp_cart_expiry_hours',
                        'title' => __('Cart Expiry', 'shopping-agent-with-ucp'),
                        'type' => 'number',
                        'description' => __('Hours until an inactive cart expires.', 'shopping-agent-with-ucp'),
                        'default' => 24,
                        'min' => 1,
                        'max' => 168,
                        'suffix' => __('hours', 'shopping-agent-with-ucp'),
                    ),
                    array(
                        'id' => 'shopping_agent_ucp_checkout_expiry',
                        'title' => __('Checkout Expiry', 'shopping-agent-with-ucp'),
                        'type' => 'number',
                        'description' => __('Minutes until a checkout session expires.', 'shopping-agent-with-ucp'),
                        'default' => 30,
                        'min' => 5,
                        'max' => 120,
                        'suffix' => __('minutes', 'shopping-agent-with-ucp'),
                    ),
                ),
            ),
            'advanced' => array(
                'title' => __('Advanced', 'shopping-agent-with-ucp'),
                'fields' => array(
                    array(
                        'id' => 'shopping_agent_ucp_log_enabled',
                        'title' => __('Enable Logging', 'shopping-agent-with-ucp'),
                        'type' => 'checkbox',
                        'description' => __('Log webhook delivery attempts and errors.', 'shopping-agent-with-ucp'),
                        'default' => 'no',
                    ),
                ),
            ),
        );
    }
}
