<?php
/**
 * Admin Class
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Admin
{

    /**
     * Add menu pages
     */
    public function add_menu_pages()
    {
        add_submenu_page(
            'woocommerce',
            __('UCP Settings', 'ucp-shopping-agent'),
            __('UCP', 'ucp-shopping-agent'),
            'manage_woocommerce',
            'wc-ucp-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook)
    {
        if (strpos($hook, 'wc-ucp-settings') === false) {
            return;
        }

        wp_enqueue_style(
            'wc-ucp-admin',
            WC_UCP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WC_UCP_VERSION
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook)
    {
        if (strpos($hook, 'wc-ucp-settings') === false) {
            return;
        }

        wp_enqueue_script(
            'wc-ucp-admin',
            WC_UCP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_UCP_VERSION,
            true
        );

        wp_localize_script('wc-ucp-admin', 'wcUcpAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_ucp_admin'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this API key?', 'ucp-shopping-agent'),
                'copied' => __('Copied to clipboard!', 'ucp-shopping-agent'),
                'error' => __('An error occurred. Please try again.', 'ucp-shopping-agent'),
            ),
        ));
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('wc_ucp_settings', 'wc_ucp_enabled', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'yes',
        ));
        register_setting('wc_ucp_settings', 'wc_ucp_cart_expiry_hours', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 24,
        ));
        register_setting('wc_ucp_settings', 'wc_ucp_checkout_expiry', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 30,
        ));
        register_setting('wc_ucp_settings', 'wc_ucp_rate_limit', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 100,
        ));
        register_setting('wc_ucp_settings', 'wc_ucp_log_enabled', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'no',
        ));

        // Handle AJAX actions
        add_action('wp_ajax_wc_ucp_create_api_key', array($this, 'ajax_create_api_key'));
        add_action('wp_ajax_wc_ucp_delete_api_key', array($this, 'ajax_delete_api_key'));
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        include WC_UCP_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * AJAX: Create API key
     */
    public function ajax_create_api_key()
    {
        check_ajax_referer('wc_ucp_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'ucp-shopping-agent'));
        }

        $description = sanitize_text_field($_POST['description'] ?? '');
        $permissions = sanitize_text_field($_POST['permissions'] ?? 'read');

        $api_key_model = new WC_UCP_API_Key();
        $result = $api_key_model->create($description, $permissions, get_current_user_id());

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'id' => $result['id'],
            'key_id' => $result['key_id'],
            'secret' => $result['secret'],
            'api_key' => $result['key_id'] . ':' . $result['secret'],
        ));
    }

    /**
     * AJAX: Delete API key
     */
    public function ajax_delete_api_key()
    {
        check_ajax_referer('wc_ucp_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied.', 'ucp-shopping-agent'));
        }

        $key_id = intval($_POST['key_id'] ?? 0);

        if (!$key_id) {
            wp_send_json_error(__('Invalid key ID.', 'ucp-shopping-agent'));
        }

        $api_key_model = new WC_UCP_API_Key();
        $result = $api_key_model->delete($key_id);

        if (!$result) {
            wp_send_json_error(__('Failed to delete API key.', 'ucp-shopping-agent'));
        }

        wp_send_json_success();
    }
}
