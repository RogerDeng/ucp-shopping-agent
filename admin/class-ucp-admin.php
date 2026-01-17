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
     * Initialize admin hooks
     */
    public function init()
    {
        // Order list enhancements
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'render_order_column'), 10, 2);
        add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_order_column'));
        add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'render_order_column_hpos'), 10, 2);
        add_action('restrict_manage_posts', array($this, 'add_order_filter_dropdown'));
        add_filter('request', array($this, 'filter_orders_by_ucp'));
        add_action('woocommerce_order_list_table_restrict_manage_orders', array($this, 'add_order_filter_dropdown_hpos'));

        // Order meta box
        add_action('add_meta_boxes', array($this, 'add_order_meta_box'));
    }

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
        // Always load UCP badge styles on order pages
        $screen = get_current_screen();
        if ($screen && (strpos($screen->id, 'shop_order') !== false || strpos($screen->id, 'wc-orders') !== false)) {
            $this->enqueue_order_styles();
        }

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
     * Enqueue order list styles
     */
    private function enqueue_order_styles()
    {
        $css = '
            .ucp-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                white-space: nowrap;
            }
            .ucp-badge img {
                width: 14px;
                height: 14px;
                filter: brightness(0) invert(1);
            }
            .ucp-source-column {
                width: 100px;
            }
            .ucp-meta-box-content {
                padding: 10px 0;
            }
            .ucp-meta-box-content p {
                margin: 8px 0;
            }
            .ucp-meta-box-content strong {
                color: #1e1e1e;
            }
            .ucp-session-id {
                font-family: monospace;
                background: #f0f0f1;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
                word-break: break-all;
            }
        ';
        wp_add_inline_style('woocommerce_admin_styles', $css);
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
     * Add UCP source column to orders list
     */
    public function add_order_column($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            // Add after order status column
            if ($key === 'order_status') {
                $new_columns['ucp_source'] = __('Source', 'ucp-shopping-agent');
            }
        }

        return $new_columns;
    }

    /**
     * Render UCP source column (Legacy post-based orders)
     */
    public function render_order_column($column, $post_id)
    {
        if ($column !== 'ucp_source') {
            return;
        }

        $is_ucp = get_post_meta($post_id, '_ucp_created', true);

        if ($is_ucp) {
            echo $this->get_ucp_badge();
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Render UCP source column (HPOS - High Performance Order Storage)
     */
    public function render_order_column_hpos($column, $order)
    {
        if ($column !== 'ucp_source') {
            return;
        }

        $is_ucp = $order->get_meta('_ucp_created');

        if ($is_ucp) {
            echo $this->get_ucp_badge();
        } else {
            echo '<span style="color:#999;">—</span>';
        }
    }

    /**
     * Get UCP badge HTML
     */
    private function get_ucp_badge()
    {
        $icon_url = WC_UCP_PLUGIN_URL . 'assets/ucp-icon.svg';
        $badge_style = 'display:inline-flex;align-items:center;gap:4px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:4px 10px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;';
        $img_style = 'width:14px;height:14px;filter:brightness(0) invert(1);';
        return '<span style="' . esc_attr($badge_style) . '"><img src="' . esc_url($icon_url) . '" alt="UCP" style="' . esc_attr($img_style) . '"> UCP</span>';
    }

    /**
     * Add filter dropdown to orders list (Legacy)
     */
    public function add_order_filter_dropdown()
    {
        global $typenow;

        if ($typenow !== 'shop_order') {
            return;
        }

        $current = isset($_GET['ucp_filter']) ? sanitize_text_field($_GET['ucp_filter']) : '';

        ?>
        <select name="ucp_filter">
            <option value=""><?php esc_html_e('All sources', 'ucp-shopping-agent'); ?></option>
            <option value="ucp" <?php selected($current, 'ucp'); ?>><?php esc_html_e('UCP Orders', 'ucp-shopping-agent'); ?>
            </option>
            <option value="non_ucp" <?php selected($current, 'non_ucp'); ?>>
                <?php esc_html_e('Non-UCP Orders', 'ucp-shopping-agent'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Add filter dropdown to orders list (HPOS)
     */
    public function add_order_filter_dropdown_hpos()
    {
        $current = isset($_GET['ucp_filter']) ? sanitize_text_field($_GET['ucp_filter']) : '';

        ?>
        <select name="ucp_filter">
            <option value=""><?php esc_html_e('All sources', 'ucp-shopping-agent'); ?></option>
            <option value="ucp" <?php selected($current, 'ucp'); ?>><?php esc_html_e('UCP Orders', 'ucp-shopping-agent'); ?>
            </option>
            <option value="non_ucp" <?php selected($current, 'non_ucp'); ?>>
                <?php esc_html_e('Non-UCP Orders', 'ucp-shopping-agent'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Filter orders by UCP meta
     */
    public function filter_orders_by_ucp($vars)
    {
        global $typenow;

        if ($typenow !== 'shop_order' || !isset($_GET['ucp_filter']) || empty($_GET['ucp_filter'])) {
            return $vars;
        }

        $filter = sanitize_text_field($_GET['ucp_filter']);

        if ($filter === 'ucp') {
            $vars['meta_query'][] = array(
                'key' => '_ucp_created',
                'value' => '1',
                'compare' => '=',
            );
        } elseif ($filter === 'non_ucp') {
            $vars['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key' => '_ucp_created',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_ucp_created',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }

        return $vars;
    }

    /**
     * Add UCP meta box to order details page
     */
    public function add_order_meta_box()
    {
        $screen = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order')
            : 'shop_order';

        add_meta_box(
            'wc_ucp_order_info',
            __('UCP Order Info', 'ucp-shopping-agent'),
            array($this, 'render_order_meta_box'),
            $screen,
            'side',
            'default'
        );
    }

    /**
     * Render UCP order meta box
     */
    public function render_order_meta_box($post_or_order)
    {
        $order = ($post_or_order instanceof WP_Post) ? wc_get_order($post_or_order->ID) : $post_or_order;

        if (!$order) {
            return;
        }

        $is_ucp = $order->get_meta('_ucp_created');

        if (!$is_ucp) {
            echo '<p style="color:#999;">' . esc_html__('This order was not created via UCP.', 'ucp-shopping-agent') . '</p>';
            return;
        }

        $session_id = $order->get_meta('_ucp_checkout_session_id');
        $payment_handler = $order->get_meta('_ucp_payment_handler_id');

        ?>
        <div class="ucp-meta-box-content">
            <p>
                <?php echo $this->get_ucp_badge(); ?>
            </p>
            <?php if ($session_id): ?>
                <p>
                    <strong><?php esc_html_e('Session ID:', 'ucp-shopping-agent'); ?></strong><br>
                    <code class="ucp-session-id"><?php echo esc_html($session_id); ?></code>
                </p>
            <?php endif; ?>
            <?php if ($payment_handler): ?>
                <p>
                    <strong><?php esc_html_e('Payment Handler:', 'ucp-shopping-agent'); ?></strong><br>
                    <?php echo esc_html($payment_handler); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
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

