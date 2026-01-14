<?php
/**
 * Plugin Deactivator
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Deactivator
{

    /**
     * Deactivate the plugin
     */
    public static function deactivate()
    {
        // Flush rewrite rules to remove our custom rules
        flush_rewrite_rules();

        // Clear any scheduled events
        wp_clear_scheduled_hook('wc_ucp_cleanup_expired_sessions');
    }
}
