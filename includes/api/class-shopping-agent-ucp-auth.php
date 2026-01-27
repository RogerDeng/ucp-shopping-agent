<?php
/**
 * Authentication Handler
 *
 * Handles API key authentication for UCP requests.
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Auth extends Shopping_Agent_UCP_REST_Controller
{

    protected $rest_base = 'auth';

    /**
     * Current authenticated API key
     */
    private static $current_api_key = null;

    /**
     * Register routes
     */
    public function register_routes()
    {
        // Generate API key (admin only via WordPress session)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/keys', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_api_key'),
                'permission_callback' => array($this, 'wp_admin_permissions_check'),
                'args' => array(
                    'description' => array(
                        'type' => 'string',
                        'required' => false,
                        'default' => '',
                        'description' => __('Description for this API key', 'shopping-agent-with-ucp'),
                    ),
                    'permissions' => array(
                        'type' => 'string',
                        'required' => false,
                        'default' => 'read',
                        'enum' => array('read', 'write', 'admin'),
                        'description' => __('Permission level', 'shopping-agent-with-ucp'),
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'list_api_keys'),
                'permission_callback' => array($this, 'wp_admin_permissions_check'),
            ),
        ));

        // Delete API key
        register_rest_route($this->namespace, '/' . $this->rest_base . '/keys/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'delete_api_key'),
            'permission_callback' => array($this, 'wp_admin_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
            ),
        ));

        // Verify API key (for testing)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/verify', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'verify_api_key'),
            'permission_callback' => array($this, 'read_permissions_check'),
        ));
    }

    /**
     * WordPress admin permission check
     */
    public function wp_admin_permissions_check($request)
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Validate API key from request (static method for use by other controllers)
     *
     * @param WP_REST_Request $request The REST request.
     * @param string $required_permission Required permission level (read, write, admin).
     * @return true|WP_Error True if authenticated, WP_Error otherwise.
     */
    public static function validate_api_key_request($request, $required_permission = 'read')
    {
        // Try to get API key from header
        $api_key = $request->get_header('X-UCP-API-Key');

        // Fallback to query parameter
        if (empty($api_key)) {
            $api_key = $request->get_param('shopping_agent_shopping_agent_ucp_api_key');
        }

        if (empty($api_key)) {
            // For read-only access, allow unauthenticated requests to public endpoints
            if ($required_permission === 'read') {
                return true;
            }
            return new WP_Error(
                'shopping_agent_shopping_agent_ucp_auth_required',
                __('API key is required for this request.', 'shopping-agent-with-ucp'),
                array('status' => 401)
            );
        }

        // Parse key format: key_id:secret
        $parts = explode(':', $api_key, 2);
        if (count($parts) !== 2) {
            return new WP_Error(
                'shopping_agent_shopping_agent_ucp_invalid_key_format',
                __('Invalid API key format. Expected format: key_id:secret', 'shopping-agent-with-ucp'),
                array('status' => 401)
            );
        }

        list($key_id, $secret) = $parts;

        // Look up the key
        $api_key_model = new Shopping_Agent_UCP_API_Key();
        $key_data = $api_key_model->get_by_key_id($key_id);

        if (!$key_data) {
            return new WP_Error(
                'shopping_agent_shopping_agent_ucp_invalid_api_key',
                __('Invalid API key.', 'shopping-agent-with-ucp'),
                array('status' => 401)
            );
        }

        // Verify secret
        if (!wp_check_password($secret, $key_data->secret_hash)) {
            return new WP_Error(
                'shopping_agent_shopping_agent_ucp_invalid_api_key',
                __('Invalid API key.', 'shopping-agent-with-ucp'),
                array('status' => 401)
            );
        }

        // Check permissions
        $permission_levels = array('read' => 1, 'write' => 2, 'admin' => 3);
        $key_level = $permission_levels[$key_data->permissions] ?? 0;
        $required_level = $permission_levels[$required_permission] ?? 0;

        if ($key_level < $required_level) {
            return new WP_Error(
                'shopping_agent_shopping_agent_ucp_insufficient_permissions',
                sprintf(
                    /* translators: %s: required permission level */
                    __('This API key does not have %s permissions.', 'shopping-agent-with-ucp'),
                    $required_permission
                ),
                array('status' => 403)
            );
        }

        // Update last access time
        $api_key_model->update_last_access($key_data->id);

        // Store current key for later use
        self::$current_api_key = $key_data;

        return true;
    }

    /**
     * Get current authenticated API key
     */
    public static function get_current_api_key()
    {
        return self::$current_api_key;
    }

    /**
     * Create a new API key
     */
    public function create_api_key($request)
    {
        $description = sanitize_text_field($request->get_param('description'));
        $permissions = $request->get_param('permissions');
        $user_id = get_current_user_id();

        $api_key_model = new Shopping_Agent_UCP_API_Key();
        $result = $api_key_model->create($description, $permissions, $user_id);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success_response(array(
            'id' => $result['id'],
            'key_id' => $result['key_id'],
            'secret' => $result['secret'],
            'api_key' => $result['key_id'] . ':' . $result['secret'],
            'description' => $description,
            'permissions' => $permissions,
            'created_at' => current_time('c'),
            'message' => __('Save this API key securely. The secret will not be shown again.', 'shopping-agent-with-ucp'),
        ));
    }

    /**
     * List API keys
     */
    public function list_api_keys($request)
    {
        $api_key_model = new Shopping_Agent_UCP_API_Key();
        $keys = $api_key_model->get_all();

        $formatted_keys = array();
        foreach ($keys as $key) {
            $formatted_keys[] = array(
                'id' => (int) $key->id,
                'key_id' => $key->key_id,
                'description' => $key->description,
                'permissions' => $key->permissions,
                'user_id' => (int) $key->user_id,
                'last_access' => $key->last_access,
                'created_at' => $key->created_at,
            );
        }

        return $this->success_response($formatted_keys);
    }

    /**
     * Delete an API key
     */
    public function delete_api_key($request)
    {
        $id = (int) $request->get_param('id');

        $api_key_model = new Shopping_Agent_UCP_API_Key();
        $result = $api_key_model->delete($id);

        if (!$result) {
            return $this->error_response(
                'shopping_agent_shopping_agent_ucp_key_not_found',
                __('API key not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        return $this->success_response(array(
            'deleted' => true,
            'id' => $id,
        ));
    }

    /**
     * Verify API key
     */
    public function verify_api_key($request)
    {
        $key = self::get_current_api_key();

        return $this->success_response(array(
            'valid' => true,
            'key_id' => $key ? $key->key_id : null,
            'permissions' => $key ? $key->permissions : 'read',
        ));
    }
}
