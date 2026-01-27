<?php
/**
 * Plugin Deactivator
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Deactivator
{

    /**
     * Deactivate the plugin
     */
    public static function deactivate()
    {
        // Flush rewrite rules to remove our custom rules
        flush_rewrite_rules();

        // Clear any scheduled events
        wp_clear_scheduled_hook('shopping_agent_ucp_cleanup_expired_sessions');
    }
}
