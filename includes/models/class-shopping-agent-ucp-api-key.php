<?php
/**
 * API Key Model
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_API_Key
{

    /**
     * Table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'shopping_agent_ucp_api_keys';
    }

    /**
     * Create a new API key
     */
    public function create($description = '', $permissions = 'read', $user_id = null)
    {
        global $wpdb;

        // Generate unique key ID
        // Prefix: 7 chars, Random: 24 chars = 31 chars (Max 32)
        $key_id = 'sa_ucp_' . $this->generate_random_string(24);

        // Generate secret
        $secret = 'sa_ucp_secret_' . $this->generate_random_string(32);

        // Hash the secret
        $secret_hash = wp_hash_password($secret);

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'key_id' => $key_id,
                'secret_hash' => $secret_hash,
                'description' => $description,
                'permissions' => $permissions,
                'user_id' => $user_id,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error(
                'shopping_agent_ucp_key_creation_failed',
                __('Failed to create API key.', 'shopping-agent-with-ucp'),
                array('status' => 500)
            );
        }

        return array(
            'id' => $wpdb->insert_id,
            'key_id' => $key_id,
            'secret' => $secret,
        );
    }

    /**
     * Get API key by key_id with caching
     *
     * @param string $key_id The key ID to look up.
     * @return object|null The API key data or null if not found.
     */
    public function get_by_key_id($key_id)
    {
        global $wpdb;

        // Check cache first
        $cache_key = 'shopping_agent_ucp_api_key_' . md5($key_id);
        $key_data = wp_cache_get($cache_key, 'shopping_agent_ucp_api_keys');

        if ($key_data !== false) {
            return $key_data === 'not_found' ? null : $key_data;
        }

        $key_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE key_id = %s",
                $key_id
            )
        );

        // Cache for 5 minutes (300 seconds)
        wp_cache_set($cache_key, $key_data ? $key_data : 'not_found', 'shopping_agent_ucp_api_keys', 300);

        return $key_data;
    }

    /**
     * Get API key by ID
     */
    public function get_by_id($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Get all API keys
     */
    public function get_all()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
        );
    }

    /**
     * Update last access time
     */
    public function update_last_access($id)
    {
        global $wpdb;

        return $wpdb->update(
            $this->table_name,
            array('last_access' => current_time('mysql')),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Delete an API key
     */
    public function delete($id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        return $result !== false && $result > 0;
    }

    /**
     * Generate random string
     */
    private function generate_random_string($length)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }
}
