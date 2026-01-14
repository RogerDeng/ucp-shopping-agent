<?php
/**
 * WordPress & WooCommerce Function Stubs for IDE Support
 *
 * This file provides function/class declarations for WordPress and WooCommerce
 * to help IDEs with code completion and to prevent "unknown function" errors.
 *
 * DO NOT INCLUDE THIS FILE IN PRODUCTION - it is for IDE static analysis only.
 *
 * To use: Add this file path to your IDE's PHP include paths, or use the
 * php-stubs/wordpress-stubs and php-stubs/woocommerce-stubs packages.
 *
 * @package WC_UCP_Agent
 * @phpcs:disable
 */

// Prevent direct execution
if (defined('ABSPATH')) {
    return;
}

// ========================================
// WordPress Core Functions
// ========================================

/**
 * @param string $tag
 * @param callable $callback
 * @param int $priority
 * @param int $accepted_args
 */
function add_action($tag, $callback, $priority = 10, $accepted_args = 1)
{
}

/**
 * @param string $tag
 * @param callable $callback
 * @param int $priority
 * @param int $accepted_args
 */
function add_filter($tag, $callback, $priority = 10, $accepted_args = 1)
{
}

/**
 * @param string $tag
 * @param mixed ...$args
 */
function do_action($tag, ...$args)
{
}

/**
 * @param string $tag
 * @param mixed $value
 * @param mixed ...$args
 * @return mixed
 */
function apply_filters($tag, $value, ...$args)
{
    return $value;
}

/**
 * @param string $text
 * @param string $domain
 * @return string
 */
function __($text, $domain = 'default')
{
    return $text;
}

/**
 * @param string $text
 * @param string $domain
 */
function _e($text, $domain = 'default')
{
}

/**
 * @param string $text
 * @param string $domain
 * @return string
 */
function esc_html($text)
{
    return $text;
}

/**
 * @param string $text
 * @param string $domain
 */
function esc_html_e($text, $domain = 'default')
{
}

/**
 * @param string $text
 * @return string
 */
function esc_attr($text)
{
    return $text;
}

/**
 * @param string $url
 * @return string
 */
function esc_url($url)
{
    return $url;
}

/**
 * @param mixed $data
 * @return bool
 */
function is_wp_error($data)
{
    return false;
}

/**
 * @param string $option
 * @param mixed $default
 * @return mixed
 */
function get_option($option, $default = false)
{
    return $default;
}

/**
 * @param string $option
 * @param mixed $value
 * @return bool
 */
function update_option($option, $value)
{
    return true;
}

/**
 * @param string $option
 * @param mixed $value
 * @return bool
 */
function add_option($option, $value = '')
{
    return true;
}

/**
 * @param string $option
 * @return bool
 */
function delete_option($option)
{
    return true;
}

/**
 * @param string $show
 * @param string $filter
 * @return string
 */
function get_bloginfo($show = '', $filter = 'raw')
{
    return '';
}

/**
 * @param string $path
 * @param string $scheme
 * @return string
 */
function home_url($path = '', $scheme = null)
{
    return '';
}

/**
 * @return string
 */
function get_home_url()
{
    return '';
}

/**
 * @param int|null $blog_id
 * @param string $path
 * @param string $scheme
 * @return string
 */
function get_rest_url($blog_id = null, $path = '/', $scheme = 'rest')
{
    return '';
}

/**
 * @param string|null $file
 * @return string
 */
function plugin_dir_path($file)
{
    return '';
}

/**
 * @param string|null $file
 * @return string
 */
function plugin_dir_url($file)
{
    return '';
}

/**
 * @param string|null $file
 * @return string
 */
function plugin_basename($file)
{
    return '';
}

/**
 * @return string
 */
function admin_url($path = '', $scheme = 'admin')
{
    return '';
}

/**
 * @param string $file
 * @param callable $callback
 */
function register_activation_hook($file, $callback)
{
}

/**
 * @param string $file
 * @param callable $callback
 */
function register_deactivation_hook($file, $callback)
{
}

/**
 * @param string $regex
 * @param string $query
 * @param string $after
 */
function add_rewrite_rule($regex, $query, $after = 'bottom')
{
}

/**
 * @param bool $hard
 */
function flush_rewrite_rules($hard = true)
{
}

/**
 * @param int $length
 * @param bool $special_chars
 * @param bool $extra_special_chars
 * @return string
 */
function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
{
    return '';
}

/**
 * @param string $password
 * @return string
 */
function wp_hash_password($password)
{
    return '';
}

/**
 * @param string $password
 * @param string $hash
 * @return bool
 */
function wp_check_password($password, $hash)
{
    return false;
}

/**
 * @param string $data
 * @param string $scheme
 * @return string
 */
function wp_hash($data, $scheme = 'auth')
{
    return '';
}

/**
 * @param mixed $data
 * @param int $options
 * @return string|false
 */
function wp_json_encode($data, $options = 0)
{
    return json_encode($data, $options);
}

/**
 * @param string $type
 * @return string
 */
function current_time($type, $gmt = 0)
{
    return '';
}

/**
 * @return DateTimeZone
 */
function wp_timezone()
{
    return new DateTimeZone('UTC');
}

/**
 * @return string
 */
function wp_timezone_string()
{
    return 'UTC';
}

/**
 * @return string
 */
function get_locale()
{
    return 'en_US';
}

/**
 * @param string $url
 * @param array $args
 * @return array|WP_Error
 */
function wp_safe_remote_post($url, $args = array())
{
    return array();
}

/**
 * @param array|WP_Error $response
 * @return int
 */
function wp_remote_retrieve_response_code($response)
{
    return 200;
}

/**
 * @param array|WP_Error $response
 * @return string
 */
function wp_remote_retrieve_body($response)
{
    return '';
}

/**
 * @param int $user_id
 * @param string $meta_key
 * @param mixed $meta_value
 * @param mixed $prev_value
 * @return int|bool
 */
function update_user_meta($user_id, $meta_key, $meta_value, $prev_value = '')
{
    return true;
}

/**
 * @param int $user_id
 * @param string $meta_key
 * @param bool $single
 * @return mixed
 */
function get_user_meta($user_id, $meta_key = '', $single = false)
{
    return '';
}

/**
 * @param string $field
 * @param string|int $value
 * @return WP_User|false
 */
function get_user_by($field, $value)
{
    return false;
}

/**
 * @param string $capability
 * @return bool
 */
function current_user_can($capability)
{
    return false;
}

/**
 * @param int $action
 * @param string $query_arg
 * @return string
 */
function wp_create_nonce($action = -1)
{
    return '';
}

/**
 * @param string $action
 * @param string $query_arg
 */
function check_ajax_referer($action = -1, $query_arg = false)
{
}

/**
 * @param mixed $response
 * @param int $status_code
 * @param int $options
 */
function wp_send_json_success($response = null, $status_code = null, $options = 0)
{
}

/**
 * @param mixed $data
 * @param int $status_code
 * @param int $options
 */
function wp_send_json_error($data = null, $status_code = null, $options = 0)
{
}

/**
 * @param string $email
 * @return string
 */
function sanitize_email($email)
{
    return $email;
}

/**
 * @param string $str
 * @return string
 */
function sanitize_text_field($str)
{
    return $str;
}

/**
 * @param string $str
 * @return string
 */
function sanitize_textarea_field($str)
{
    return $str;
}

/**
 * @param string $username
 * @param bool $strict
 * @return string
 */
function sanitize_user($username, $strict = false)
{
    return $username;
}

/**
 * @param string $name
 * @return mixed
 */
function get_theme_mod($name, $default = false)
{
    return $default;
}

/**
 * @param int $attachment_id
 * @return string|false
 */
function wp_get_attachment_url($attachment_id)
{
    return false;
}

/**
 * @param array $args
 * @return WC_Product[]|stdClass[]
 */
function get_comments($args = array())
{
    return array();
}

/**
 * @param int $comment_id
 * @return object|null
 */
function get_comment($comment_id)
{
    return null;
}

/**
 * @param array $commentdata
 * @return int|false
 */
function wp_insert_comment($commentdata)
{
    return 0;
}

/**
 * @param int $comment_id
 * @param string $key
 * @param mixed $value
 * @return int|bool
 */
function update_comment_meta($comment_id, $key, $value)
{
    return true;
}

/**
 * @param int $comment_id
 * @param string $key
 * @param bool $single
 * @return mixed
 */
function get_comment_meta($comment_id, $key = '', $single = false)
{
    return '';
}

/**
 * @param string $format
 * @param int|null $comment_id
 * @return string
 */
function get_comment_date($format = '', $comment_id = null)
{
    return '';
}

/**
 * @param string $handle
 * @param string $src
 * @param array $deps
 * @param string|bool|null $ver
 */
function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
{
}

/**
 * @param string $handle
 * @param string $src
 * @param array $deps
 * @param string|bool|null $ver
 * @param bool $in_footer
 */
function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false)
{
}

/**
 * @param string $handle
 * @param string $object_name
 * @param array $l10n
 * @return bool
 */
function wp_localize_script($handle, $object_name, $l10n)
{
    return true;
}

/**
 * @param string $page_title
 * @param string $menu_title
 * @param string $capability
 * @param string $menu_slug
 * @param callable $callback
 * @param string $icon_url
 * @param int|float $position
 */
function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null)
{
}

/**
 * @param string $parent_slug
 * @param string $page_title
 * @param string $menu_title
 * @param string $capability
 * @param string $menu_slug
 * @param callable $callback
 * @param int|float $position
 */
function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = '', $position = null)
{
}

/**
 * @param string $option_group
 * @param string $option_name
 * @param array $args
 */
function register_setting($option_group, $option_name, $args = array())
{
}

/**
 * @param string $option_group
 */
function settings_fields($option_group)
{
}

/**
 * @param string $text
 * @param string $type
 * @param string $name
 * @param bool $wrap
 * @param array $other_attributes
 */
function submit_button($text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null)
{
}

/**
 * @param string $format
 * @param int|bool $timestamp_or_locale
 * @param bool $gmt
 * @return string
 */
function date_i18n($format, $timestamp_or_locale = false, $gmt = false)
{
    return '';
}

/**
 * @param mixed $response
 * @return WP_REST_Response
 */
function rest_ensure_response($response)
{
    return new WP_REST_Response($response);
}

/**
 * @param string $namespace
 * @param string $route
 * @param array $args
 * @param bool $override
 * @return bool
 */
function register_rest_route($namespace, $route, $args = array(), $override = false)
{
    return true;
}

/**
 * @param int $term_id
 * @param string $taxonomy
 * @param string|array $output
 * @param string $filter
 * @return WP_Term|array|WP_Error|null
 */
function get_term($term_id, $taxonomy = '', $output = OBJECT, $filter = 'raw')
{
    return null;
}

/**
 * @param array $args
 * @return WP_Term[]|int[]|string[]|string|WP_Error
 */
function get_terms($args = array())
{
    return array();
}

/**
 * @param string $domain
 * @param string|false $deprecated
 * @param string|false $plugin_rel_path
 * @return bool
 */
function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
{
    return true;
}

/**
 * @param string $timestamp
 * @param string $event
 */
function wp_clear_scheduled_hook($timestamp, $event = '')
{
}

// ========================================
// WordPress Classes
// ========================================

/**
 * WordPress Error Class
 */
class WP_Error
{
    public function __construct($code = '', $message = '', $data = '')
    {
    }
    public function get_error_code()
    {
        return '';
    }
    public function get_error_message($code = '')
    {
        return '';
    }
    public function get_error_data($code = '')
    {
        return null;
    }
    public function get_error_codes()
    {
        return array();
    }
    public function add($code, $message, $data = '')
    {
    }
}

/**
 * WordPress User Class
 */
class WP_User
{
    public $ID = 0;
    public $user_login = '';
    public $user_email = '';
}

/**
 * WordPress REST Response Class
 */
class WP_REST_Response
{
    public function __construct($data = null, $status = 200, $headers = array())
    {
    }
    public function set_status($code)
    {
    }
    public function header($key, $value, $replace = true)
    {
    }
}

/**
 * WordPress REST Request Class
 */
class WP_REST_Request
{
    public function get_param($key)
    {
        return null;
    }
    public function get_params()
    {
        return array();
    }
    public function get_header($key)
    {
        return null;
    }
    public function get_query_params()
    {
        return array();
    }
}

/**
 * WordPress REST Server Class
 */
class WP_REST_Server
{
    const READABLE = 'GET';
    const CREATABLE = 'POST';
    const EDITABLE = 'POST, PUT, PATCH';
    const DELETABLE = 'DELETE';
    const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
}

/**
 * WordPress Query Class
 */
class WP_Query
{
    public $posts = array();
    public $found_posts = 0;
    public function __construct($args = '')
    {
    }
}

/**
 * WordPress Term Class
 */
class WP_Term
{
    public $term_id = 0;
    public $name = '';
    public $slug = '';
    public $term_group = 0;
    public $term_taxonomy_id = 0;
    public $taxonomy = '';
    public $description = '';
    public $parent = 0;
    public $count = 0;
}

// ========================================
// WooCommerce Core Functions
// ========================================

/**
 * @param array $args
 * @return WC_Product[]
 */
function wc_get_products($args = array())
{
    return array();
}

/**
 * @param mixed $product_id
 * @return WC_Product|false
 */
function wc_get_product($product_id = false)
{
    return false;
}

/**
 * @param string $sku
 * @return int
 */
function wc_get_product_id_by_sku($sku)
{
    return 0;
}

/**
 * @param array $args
 * @return WC_Order[]
 */
function wc_get_orders($args = array())
{
    return array();
}

/**
 * @param mixed $order_id
 * @return WC_Order|false
 */
function wc_get_order($order_id = false)
{
    return false;
}

/**
 * @param array $args
 * @return WC_Order|WP_Error
 */
function wc_create_order($args = array())
{
    return new WC_Order();
}

/**
 * @return int
 */
function wc_get_price_decimals()
{
    return 2;
}

/**
 * @return string
 */
function get_woocommerce_currency()
{
    return 'USD';
}

/**
 * @param string $currency
 * @return string
 */
function get_woocommerce_currency_symbol($currency = '')
{
    return '$';
}

/**
 * @param float $price
 * @param array $args
 * @return string
 */
function wc_price($price, $args = array())
{
    return '';
}

/**
 * @param array $args
 * @return stdClass[]
 */
function wc_get_order_notes($args = array())
{
    return array();
}

/**
 * @param string $email
 * @param array $args
 * @return string
 */
function wc_create_new_customer_username($email, $args = array())
{
    return '';
}

/**
 * @param int $comment_id
 * @return bool
 */
function wc_review_is_from_verified_owner($comment_id)
{
    return false;
}

/**
 * @param string $name
 * @param WC_Product|null $product
 * @return string
 */
function wc_attribute_label($name, $product = null)
{
    return $name;
}

/**
 * @param int $product_id
 * @param string $taxonomy
 * @param array $args
 * @return array
 */
function wc_get_product_terms($product_id, $taxonomy, $args = array())
{
    return array();
}

/**
 * @return WooCommerce
 */
function WC()
{
    return new WooCommerce();
}

// ========================================
// WooCommerce Classes
// ========================================

/**
 * Main WooCommerce Class
 */
class WooCommerce
{
    /** @var WC_Session_Handler */
    public $session;
    /** @var WC_Customer */
    public $customer;

    public function shipping()
    {
        return new WC_Shipping();
    }
}

/**
 * WooCommerce Shipping Class
 */
class WC_Shipping
{
    public function get_shipping_methods()
    {
        return array();
    }
}

/**
 * WooCommerce Product Base Class
 */
class WC_Product
{
    public function get_id()
    {
        return 0;
    }
    public function get_name()
    {
        return '';
    }
    public function get_slug()
    {
        return '';
    }
    public function get_type()
    {
        return 'simple';
    }
    public function get_status()
    {
        return 'publish';
    }
    public function get_description()
    {
        return '';
    }
    public function get_short_description()
    {
        return '';
    }
    public function get_sku()
    {
        return '';
    }
    public function get_price()
    {
        return 0;
    }
    public function get_regular_price()
    {
        return 0;
    }
    public function get_sale_price()
    {
        return 0;
    }
    public function is_on_sale()
    {
        return false;
    }
    public function is_in_stock()
    {
        return true;
    }
    public function get_stock_quantity()
    {
        return null;
    }
    public function get_stock_status()
    {
        return 'instock';
    }
    public function is_featured()
    {
        return false;
    }
    public function get_image_id()
    {
        return 0;
    }
    public function get_gallery_image_ids()
    {
        return array();
    }
    public function get_category_ids()
    {
        return array();
    }
    public function get_tag_ids()
    {
        return array();
    }
    public function get_attributes()
    {
        return array();
    }
    public function get_average_rating()
    {
        return '0';
    }
    public function get_rating_count()
    {
        return 0;
    }
    public function get_review_count()
    {
        return 0;
    }
    public function get_weight()
    {
        return '';
    }
    public function get_length()
    {
        return '';
    }
    public function get_width()
    {
        return '';
    }
    public function get_height()
    {
        return '';
    }
    public function get_children()
    {
        return array();
    }
    public function get_available_variations()
    {
        return array();
    }
}

/**
 * WooCommerce Order Class
 */
class WC_Order
{
    public function get_id()
    {
        return 0;
    }
    public function get_order_number()
    {
        return '';
    }
    public function get_status()
    {
        return '';
    }
    public function get_currency()
    {
        return 'USD';
    }
    public function get_total()
    {
        return 0;
    }
    public function get_subtotal()
    {
        return 0;
    }
    public function get_total_tax()
    {
        return 0;
    }
    public function get_shipping_total()
    {
        return 0;
    }
    public function get_discount_total()
    {
        return 0;
    }
    public function get_customer_id()
    {
        return 0;
    }
    public function get_billing_email()
    {
        return '';
    }
    public function get_billing_first_name()
    {
        return '';
    }
    public function get_billing_last_name()
    {
        return '';
    }
    public function get_billing_company()
    {
        return '';
    }
    public function get_billing_address_1()
    {
        return '';
    }
    public function get_billing_address_2()
    {
        return '';
    }
    public function get_billing_city()
    {
        return '';
    }
    public function get_billing_state()
    {
        return '';
    }
    public function get_billing_postcode()
    {
        return '';
    }
    public function get_billing_country()
    {
        return '';
    }
    public function get_billing_phone()
    {
        return '';
    }
    public function get_shipping_first_name()
    {
        return '';
    }
    public function get_shipping_last_name()
    {
        return '';
    }
    public function get_shipping_company()
    {
        return '';
    }
    public function get_shipping_address_1()
    {
        return '';
    }
    public function get_shipping_address_2()
    {
        return '';
    }
    public function get_shipping_city()
    {
        return '';
    }
    public function get_shipping_state()
    {
        return '';
    }
    public function get_shipping_postcode()
    {
        return '';
    }
    public function get_shipping_country()
    {
        return '';
    }
    public function get_date_created()
    {
        return null;
    }
    public function get_date_modified()
    {
        return null;
    }
    public function get_date_completed()
    {
        return null;
    }
    public function get_date_paid()
    {
        return null;
    }
    public function get_payment_method()
    {
        return '';
    }
    public function get_payment_method_title()
    {
        return '';
    }
    public function get_shipping_method()
    {
        return '';
    }
    public function get_items()
    {
        return array();
    }
    public function get_meta($key, $single = true)
    {
        return '';
    }
    public function add_product($product, $qty = 1, $args = array())
    {
        return 0;
    }
    public function set_address($address, $type = 'billing')
    {
    }
    public function set_payment_method($payment_method)
    {
    }
    public function set_customer_id($customer_id)
    {
    }
    public function calculate_totals($and_taxes = true)
    {
        return 0;
    }
    public function set_status($new_status, $note = '', $manual_update = false)
    {
    }
    public function save()
    {
        return 0;
    }
    public function add_order_note($note, $is_customer_note = 0, $added_by_user = false)
    {
        return 0;
    }
    public function update_meta_data($key, $value, $meta_id = 0)
    {
    }
    public function get_amount()
    {
        return 0;
    }
    public function get_reason()
    {
        return '';
    }
}

/**
 * WooCommerce Customer Class
 */
class WC_Customer
{
    public function __construct($customer_id = 0)
    {
    }
    public function get_id()
    {
        return 0;
    }
    public function get_email()
    {
        return '';
    }
    public function set_email($email)
    {
    }
    public function get_first_name()
    {
        return '';
    }
    public function set_first_name($name)
    {
    }
    public function get_last_name()
    {
        return '';
    }
    public function set_last_name($name)
    {
    }
    public function get_username()
    {
        return '';
    }
    public function set_username($username)
    {
    }
    public function set_password($password)
    {
    }
    public function save()
    {
        return 0;
    }
    public function get_billing_first_name()
    {
        return '';
    }
    public function get_billing_last_name()
    {
        return '';
    }
    public function get_billing_company()
    {
        return '';
    }
    public function get_billing_address_1()
    {
        return '';
    }
    public function get_billing_address_2()
    {
        return '';
    }
    public function get_billing_city()
    {
        return '';
    }
    public function get_billing_state()
    {
        return '';
    }
    public function get_billing_postcode()
    {
        return '';
    }
    public function get_billing_country()
    {
        return '';
    }
    public function get_billing_email()
    {
        return '';
    }
    public function get_billing_phone()
    {
        return '';
    }
    public function get_shipping_first_name()
    {
        return '';
    }
    public function get_shipping_last_name()
    {
        return '';
    }
    public function get_shipping_company()
    {
        return '';
    }
    public function get_shipping_address_1()
    {
        return '';
    }
    public function get_shipping_address_2()
    {
        return '';
    }
    public function get_shipping_city()
    {
        return '';
    }
    public function get_shipping_state()
    {
        return '';
    }
    public function get_shipping_postcode()
    {
        return '';
    }
    public function get_shipping_country()
    {
        return '';
    }
    public function get_is_paying_customer()
    {
        return false;
    }
    public function get_order_count()
    {
        return 0;
    }
    public function get_total_spent()
    {
        return 0;
    }
    public function get_date_created()
    {
        return null;
    }
    public function set_billing_first_name($v)
    {
    }
    public function set_billing_last_name($v)
    {
    }
    public function set_billing_company($v)
    {
    }
    public function set_billing_address_1($v)
    {
    }
    public function set_billing_address_2($v)
    {
    }
    public function set_billing_city($v)
    {
    }
    public function set_billing_state($v)
    {
    }
    public function set_billing_postcode($v)
    {
    }
    public function set_billing_country($v)
    {
    }
    public function set_billing_email($v)
    {
    }
    public function set_billing_phone($v)
    {
    }
    public function set_shipping_first_name($v)
    {
    }
    public function set_shipping_last_name($v)
    {
    }
    public function set_shipping_company($v)
    {
    }
    public function set_shipping_address_1($v)
    {
    }
    public function set_shipping_address_2($v)
    {
    }
    public function set_shipping_city($v)
    {
    }
    public function set_shipping_state($v)
    {
    }
    public function set_shipping_postcode($v)
    {
    }
    public function set_shipping_country($v)
    {
    }
}

/**
 * WooCommerce Coupon Class
 */
class WC_Coupon
{
    public function __construct($coupon = '')
    {
    }
    public function get_id()
    {
        return 0;
    }
    public function get_code()
    {
        return '';
    }
    public function get_description()
    {
        return '';
    }
    public function get_discount_type()
    {
        return 'fixed_cart';
    }
    public function get_amount()
    {
        return 0;
    }
    public function get_free_shipping()
    {
        return false;
    }
    public function get_date_expires()
    {
        return null;
    }
    public function get_minimum_amount()
    {
        return 0;
    }
    public function get_maximum_amount()
    {
        return 0;
    }
    public function get_usage_limit()
    {
        return 0;
    }
    public function get_usage_count()
    {
        return 0;
    }
    public function get_individual_use()
    {
        return false;
    }
    public function get_product_ids()
    {
        return array();
    }
    public function get_excluded_product_ids()
    {
        return array();
    }
    public function get_product_categories()
    {
        return array();
    }
    public function get_excluded_product_categories()
    {
        return array();
    }
}

/**
 * WooCommerce Shipping Zones Class
 */
class WC_Shipping_Zones
{
    public static function get_zones()
    {
        return array();
    }
    public static function get_zone_matching_package($package)
    {
        return new WC_Shipping_Zone();
    }
}

/**
 * WooCommerce Shipping Zone Class
 */
class WC_Shipping_Zone
{
    public function __construct($zone_id = 0)
    {
    }
    public function get_id()
    {
        return 0;
    }
    public function get_zone_name()
    {
        return '';
    }
    public function get_zone_order()
    {
        return 0;
    }
    public function get_zone_locations()
    {
        return array();
    }
    public function get_shipping_methods($enabled_only = false)
    {
        return array();
    }
}

/**
 * WooCommerce Session Handler Class
 */
class WC_Session_Handler
{
    public function init()
    {
    }
}

/**
 * WooCommerce Comments Class
 */
class WC_Comments
{
    public static function clear_transients($post_id)
    {
    }
}

/**
 * WooCommerce Product Attribute Class
 */
class WC_Product_Attribute
{
    public function get_name()
    {
        return '';
    }
    public function get_options()
    {
        return array();
    }
    public function get_visible()
    {
        return true;
    }
    public function get_variation()
    {
        return false;
    }
}

// phpcs:enable
