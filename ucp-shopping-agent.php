<?php
/**
 * Plugin Name: UCP Shopping Agent
 * Plugin URI:  https://wordpress.org/plugins/ucp-shopping-agent
 * Description: Google Universal Commerce Protocol (UCP) implementation for WooCommerce. Enables AI agents to discover, browse, and transact with your store.
 * Version:     1.0.3
 * Author:      Roger Deng
 * Author URI:  https://sites.google.com/view/ucp-shopping-agent
 * License:     GPL2
 * Text Domain: ucp-shopping-agent
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Constants
 */
define('WC_UCP_VERSION', '1.0.3');
define('WC_UCP_PLUGIN_FILE', __FILE__);
define('WC_UCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_UCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_UCP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for UCP classes
 */
spl_autoload_register(function ($class) {
    // Only handle our classes
    if (strpos($class, 'WC_UCP_') !== 0) {
        return;
    }

    // Convert class name to file name
    $class_file = str_replace('WC_UCP_', '', $class);
    $class_file = strtolower(str_replace('_', '-', $class_file));
    $class_file = 'class-ucp-' . $class_file . '.php';

    // Define possible paths
    $paths = array(
        WC_UCP_PLUGIN_DIR . 'includes/' . $class_file,
        WC_UCP_PLUGIN_DIR . 'includes/api/' . $class_file,
        WC_UCP_PLUGIN_DIR . 'includes/models/' . $class_file,
        WC_UCP_PLUGIN_DIR . 'includes/webhooks/' . $class_file,
        WC_UCP_PLUGIN_DIR . 'admin/' . $class_file,
    );

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

/**
 * Main Plugin Class
 */
final class WC_UCP_Agent
{

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Loader instance
     */
    private $loader;

    /**
     * Get single instance
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->check_requirements();
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_api_hooks();
    }

    /**
     * Check plugin requirements
     */
    private function check_requirements()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e('WooCommerce UCP Agent requires WooCommerce to be installed and activated.', 'ucp-shopping-agent'); ?>
                    </p>
                </div>
                <?php
            });
            return;
        }
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies()
    {
        require_once WC_UCP_PLUGIN_DIR . 'includes/class-ucp-loader.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/class-ucp-i18n.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/class-ucp-activator.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/class-ucp-deactivator.php';

        // API Classes
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-rest-controller.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-auth.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-discovery.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-products.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-categories.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-cart.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-checkout.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-orders.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-customers.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-shipping.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-reviews.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/api/class-ucp-coupons.php';

        // Models
        require_once WC_UCP_PLUGIN_DIR . 'includes/models/class-ucp-api-key.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/models/class-ucp-cart-session.php';

        // Webhooks
        require_once WC_UCP_PLUGIN_DIR . 'includes/webhooks/class-ucp-webhook-manager.php';
        require_once WC_UCP_PLUGIN_DIR . 'includes/webhooks/class-ucp-webhook-sender.php';

        // Admin
        require_once WC_UCP_PLUGIN_DIR . 'admin/class-ucp-admin.php';
        require_once WC_UCP_PLUGIN_DIR . 'admin/class-ucp-settings.php';

        $this->loader = new WC_UCP_Loader();
    }

    /**
     * Set plugin locale
     */
    private function set_locale()
    {
        $i18n = new WC_UCP_I18n();
        $this->loader->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');
    }

    /**
     * Register admin hooks
     */
    private function define_admin_hooks()
    {
        $admin = new WC_UCP_Admin();

        // Initialize order list enhancements
        $admin->init();

        $this->loader->add_action('admin_menu', $admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
    }

    /**
     * Register API hooks
     */
    private function define_api_hooks()
    {
        // Rewrite rule for /.well-known/ucp
        $this->loader->add_action('init', $this, 'add_rewrite_rules');

        // Register REST API endpoints
        $this->loader->add_action('rest_api_init', $this, 'register_rest_routes');

        // Order webhooks
        $webhook_manager = new WC_UCP_Webhook_Manager();
        $this->loader->add_action('woocommerce_order_status_changed', $webhook_manager, 'on_order_status_changed', 10, 4);
        $this->loader->add_action('woocommerce_payment_complete', $webhook_manager, 'on_payment_complete');
        $this->loader->add_action('woocommerce_order_refunded', $webhook_manager, 'on_order_refunded', 10, 2);
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules()
    {
        add_rewrite_rule(
            '^\.well-known/ucp/?$',
            'index.php?rest_route=/ucp/v1/discovery',
            'top'
        );
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes()
    {
        $controllers = array(
            new WC_UCP_Discovery(),
            new WC_UCP_Products(),
            new WC_UCP_Categories(),
            new WC_UCP_Cart(),
            new WC_UCP_Checkout(),
            new WC_UCP_Orders(),
            new WC_UCP_Customers(),
            new WC_UCP_Shipping(),
            new WC_UCP_Reviews(),
            new WC_UCP_Coupons(),
            new WC_UCP_Auth(),
        );

        foreach ($controllers as $controller) {
            $controller->register_routes();
        }
    }

    /**
     * Run the loader
     */
    public function run()
    {
        $this->loader->run();
    }
}

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, array('WC_UCP_Activator', 'activate'));

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, array('WC_UCP_Activator', 'deactivate'));

/**
 * Add custom cron schedules
 */
add_filter('cron_schedules', array('WC_UCP_Activator', 'add_cron_schedules'));

/**
 * Register webhook retry cron handler
 */
add_action('wc_ucp_retry_failed_webhooks', array('WC_UCP_Activator', 'run_webhook_retry'));

/**
 * Initialize plugin
 */
function wc_ucp_agent()
{
    return WC_UCP_Agent::instance();
}

// Start the plugin after plugins are loaded
add_action('plugins_loaded', function () {
    if (class_exists('WooCommerce')) {
        wc_ucp_agent()->run();
    }
}, 20);