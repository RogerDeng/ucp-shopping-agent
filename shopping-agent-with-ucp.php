<?php
/**
 * Plugin Name: Shopping Agent with UCP
 * Plugin URI:  https://wordpress.org/plugins/shopping-agent-with-ucp
 * Description: Shopping Agent with UCP(Universal Commerce Protocol) implementation for WooCommerce. Enables AI agents to discover, browse, and transact with your store.
 * Version:     1.0.3
 * Author:      Roger Deng
 * Author URI:  https://sites.google.com/view/shopping-agent-ucp-agent
 * License:     GPL2
 * Text Domain: shopping-agent-with-ucp
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 10.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Declare High-Performance Order Storage (HPOS) compatibility
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Plugin Constants
 */
define('SHOPPING_AGENT_UCP_VERSION', '1.0.3');
define('SHOPPING_AGENT_UCP_PLUGIN_FILE', __FILE__);
define('SHOPPING_AGENT_UCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHOPPING_AGENT_UCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHOPPING_AGENT_UCP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for UCP classes
 */
spl_autoload_register(function ($class) {
    // Only handle our classes
    if (strpos($class, 'Shopping_Agent_UCP_') !== 0) {
        return;
    }

    // Convert class name to file name
    $class_file = str_replace('Shopping_Agent_UCP_', '', $class);
    $class_file = strtolower(str_replace('_', '-', $class_file));
    $class_file = 'class-ucp-' . $class_file . '.php';

    // Define possible paths
    $paths = array(
        SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/' . $class_file,
        SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/' . $class_file,
        SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/models/' . $class_file,
        SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/webhooks/' . $class_file,
        SHOPPING_AGENT_UCP_PLUGIN_DIR . 'admin/' . $class_file,
    );

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

final class Shopping_Agent_UCP
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
                    <p><?php esc_html_e('Shopping Agent with UCP requires WooCommerce to be installed and activated.', 'shopping-agent-with-ucp'); ?>
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
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/class-shopping-agent-ucp-loader.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/class-shopping-agent-ucp-i18n.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/class-shopping-agent-ucp-activator.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/class-shopping-agent-ucp-deactivator.php';

        // API Classes
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-rest-controller.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-auth.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-discovery.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-products.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-categories.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-cart.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-checkout.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-orders.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-customers.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-shipping.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-reviews.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/api/class-shopping-agent-ucp-coupons.php';

        // Models
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/models/class-shopping-agent-ucp-api-key.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/models/class-shopping-agent-ucp-cart-session.php';

        // Webhooks
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/webhooks/class-shopping-agent-ucp-webhook-manager.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/webhooks/class-shopping-agent-ucp-webhook-sender.php';

        // Admin
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'admin/class-shopping-agent-ucp-admin.php';
        require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'admin/class-shopping-agent-ucp-settings.php';

        $this->loader = new Shopping_Agent_UCP_Loader();
    }

    /**
     * Set plugin locale
     */
    private function set_locale()
    {
        $i18n = new Shopping_Agent_UCP_I18n();
        $this->loader->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');
    }

    /**
     * Register admin hooks
     */
    private function define_admin_hooks()
    {
        $admin = new Shopping_Agent_UCP_Admin();

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
        $webhook_manager = new Shopping_Agent_UCP_Webhook_Manager();
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
            new Shopping_Agent_UCP_Discovery(),
            new Shopping_Agent_UCP_Products(),
            new Shopping_Agent_UCP_Categories(),
            new Shopping_Agent_UCP_Cart(),
            new Shopping_Agent_UCP_Checkout(),
            new Shopping_Agent_UCP_Orders(),
            new Shopping_Agent_UCP_Customers(),
            new Shopping_Agent_UCP_Shipping(),
            new Shopping_Agent_UCP_Reviews(),
            new Shopping_Agent_UCP_Coupons(),
            new Shopping_Agent_UCP_Auth(),
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
require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/class-shopping-agent-ucp-activator.php';
register_activation_hook(__FILE__, array('Shopping_Agent_UCP_Activator', 'activate'));

/**
 * Plugin deactivation hook
 */
require_once SHOPPING_AGENT_UCP_PLUGIN_DIR . 'includes/class-shopping-agent-ucp-deactivator.php';
register_deactivation_hook(__FILE__, array('Shopping_Agent_UCP_Activator', 'deactivate'));

/**
 * Add custom cron schedules
 */
add_filter('cron_schedules', array('Shopping_Agent_UCP_Activator', 'add_cron_schedules'));

/**
 * Register webhook retry cron handler
 */
add_action('shopping_agent_ucp_retry_failed_webhooks', array('Shopping_Agent_UCP_Activator', 'run_webhook_retry'));

/**
 * Initialize plugin
 */
function shopping_agent_ucp()
{
    return Shopping_Agent_UCP::instance();
}

// Start the plugin after plugins are loaded
add_action('plugins_loaded', function () {
    if (class_exists('WooCommerce')) {
        shopping_agent_ucp()->run();
    }
}, 20);