<?php
/**
 * Coupons Endpoint
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Coupons extends WC_UCP_REST_Controller
{

    protected $rest_base = 'coupons';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // List public coupons
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_coupons'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                ),
            ),
        ));

        // Validate coupon
        register_rest_route($this->namespace, '/' . $this->rest_base . '/validate', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'validate_coupon'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'code' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'cart_total' => array(
                    'type' => 'number',
                    'description' => __('Cart total for validation', 'ucp-shopping-agent'),
                ),
                'product_ids' => array(
                    'type' => 'array',
                    'description' => __('Product IDs in cart for validation', 'ucp-shopping-agent'),
                ),
            ),
        ));

        // Get coupon by code
        register_rest_route($this->namespace, '/' . $this->rest_base . '/code/(?P<code>[^/]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_coupon_by_code'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'code' => array(
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
    }

    /**
     * Get public coupons
     */
    public function get_coupons($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $pagination = $this->get_pagination_params($request);

        // Query for active, non-private coupons
        $args = array(
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'posts_per_page' => $pagination['per_page'],
            'paged' => $pagination['page'],
            'meta_query' => array(
                'relation' => 'AND',
                // Only public coupons (not individual use marked as private)
                array(
                    'relation' => 'OR',
                    array(
                        'key' => '_ucp_public',
                        'value' => 'yes',
                        'compare' => '=',
                    ),
                    // Fallback: include coupons without the meta (for backward compatibility)
                    array(
                        'key' => '_ucp_public',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ),
        );

        $query = new WP_Query($args);
        $coupons = array();

        foreach ($query->posts as $coupon_post) {
            $coupon = new WC_Coupon($coupon_post->ID);

            // Skip expired coupons
            if ($this->is_coupon_expired($coupon)) {
                continue;
            }

            // Skip fully used coupons
            if ($this->is_coupon_usage_exhausted($coupon)) {
                continue;
            }

            $coupons[] = $this->format_coupon($coupon);
        }

        return $this->success_response(
            $coupons,
            $this->format_pagination_meta($pagination['page'], $pagination['per_page'], $query->found_posts)
        );
    }

    /**
     * Validate coupon
     */
    public function validate_coupon($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $code = sanitize_text_field($request->get_param('code'));
        $cart_total = $request->get_param('cart_total');
        $product_ids = $request->get_param('product_ids') ?: array();

        $coupon = new WC_Coupon($code);

        if (!$coupon->get_id()) {
            return $this->error_response(
                'coupon_not_found',
                __('Coupon not found.', 'ucp-shopping-agent'),
                404
            );
        }

        $validation_result = $this->validate_coupon_rules($coupon, $cart_total, $product_ids);

        if (!$validation_result['valid']) {
            return $this->success_response(array(
                'valid' => false,
                'code' => $code,
                'reason' => $validation_result['reason'],
                'message' => $validation_result['message'],
            ));
        }

        // Calculate discount
        $discount_info = $this->calculate_discount($coupon, $cart_total, $product_ids);

        return $this->success_response(array(
            'valid' => true,
            'code' => $code,
            'coupon' => $this->format_coupon($coupon),
            'discount' => $discount_info,
        ));
    }

    /**
     * Get coupon by code
     */
    public function get_coupon_by_code($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $code = sanitize_text_field(urldecode($request->get_param('code')));
        $coupon = new WC_Coupon($code);

        if (!$coupon->get_id()) {
            return $this->error_response(
                'coupon_not_found',
                __('Coupon not found.', 'ucp-shopping-agent'),
                404
            );
        }

        return $this->success_response($this->format_coupon($coupon, true));
    }

    /**
     * Validate coupon rules
     */
    private function validate_coupon_rules($coupon, $cart_total = null, $product_ids = array())
    {
        // Check if expired
        if ($this->is_coupon_expired($coupon)) {
            return array(
                'valid' => false,
                'reason' => 'expired',
                'message' => __('This coupon has expired.', 'ucp-shopping-agent'),
            );
        }

        // Check usage limit
        if ($this->is_coupon_usage_exhausted($coupon)) {
            return array(
                'valid' => false,
                'reason' => 'usage_limit_reached',
                'message' => __('This coupon has reached its usage limit.', 'ucp-shopping-agent'),
            );
        }

        // Check minimum amount
        if ($cart_total !== null) {
            $min_amount = $coupon->get_minimum_amount();
            if ($min_amount && $cart_total < $min_amount) {
                return array(
                    'valid' => false,
                    'reason' => 'minimum_not_met',
                    'message' => sprintf(
                        /* translators: %s: minimum amount */
                        __('Minimum order amount of %s is required.', 'ucp-shopping-agent'),
                        wc_price($min_amount)
                    ),
                );
            }

            // Check maximum amount
            $max_amount = $coupon->get_maximum_amount();
            if ($max_amount && $cart_total > $max_amount) {
                return array(
                    'valid' => false,
                    'reason' => 'maximum_exceeded',
                    'message' => sprintf(
                        /* translators: %s: maximum amount */
                        __('Maximum order amount of %s is exceeded.', 'ucp-shopping-agent'),
                        wc_price($max_amount)
                    ),
                );
            }
        }

        // Check product restrictions
        if (!empty($product_ids)) {
            $allowed_products = $coupon->get_product_ids();
            $excluded_products = $coupon->get_excluded_product_ids();

            if (!empty($allowed_products)) {
                $matches = array_intersect($product_ids, $allowed_products);
                if (empty($matches)) {
                    return array(
                        'valid' => false,
                        'reason' => 'product_not_eligible',
                        'message' => __('This coupon is not valid for the products in cart.', 'ucp-shopping-agent'),
                    );
                }
            }

            if (!empty($excluded_products)) {
                $excluded = array_intersect($product_ids, $excluded_products);
                if (!empty($excluded)) {
                    return array(
                        'valid' => false,
                        'reason' => 'product_excluded',
                        'message' => __('This coupon cannot be used with some products in cart.', 'ucp-shopping-agent'),
                    );
                }
            }
        }

        return array('valid' => true);
    }

    /**
     * Check if coupon is expired
     */
    private function is_coupon_expired($coupon)
    {
        $expiry = $coupon->get_date_expires();
        return $expiry && time() > $expiry->getTimestamp();
    }

    /**
     * Check if coupon usage is exhausted
     */
    private function is_coupon_usage_exhausted($coupon)
    {
        $limit = $coupon->get_usage_limit();
        $count = $coupon->get_usage_count();
        return $limit && $count >= $limit;
    }

    /**
     * Calculate discount amount
     */
    private function calculate_discount($coupon, $cart_total, $product_ids = array())
    {
        $discount_type = $coupon->get_discount_type();
        $amount = $coupon->get_amount();
        $discount_amount = 0;

        switch ($discount_type) {
            case 'percent':
                if ($cart_total) {
                    $discount_amount = $cart_total * ($amount / 100);
                    // Apply maximum discount if set
                    $max_discount = $coupon->get_maximum_amount();
                    if ($max_discount && $discount_amount > $max_discount) {
                        $discount_amount = $max_discount;
                    }
                }
                break;

            case 'fixed_cart':
                $discount_amount = $amount;
                break;

            case 'fixed_product':
                $discount_amount = $amount * count($product_ids);
                break;
        }

        return array(
            'type' => $discount_type,
            'amount' => $this->format_price($discount_amount),
            'currency' => get_woocommerce_currency(),
        );
    }

    /**
     * Format coupon for response
     */
    private function format_coupon($coupon, $detailed = false)
    {
        $data = array(
            'id' => $coupon->get_id(),
            'code' => $coupon->get_code(),
            'description' => $coupon->get_description(),
            'discount_type' => $coupon->get_discount_type(),
            'amount' => floatval($coupon->get_amount()),
            'free_shipping' => $coupon->get_free_shipping(),
            'date_expires' => $coupon->get_date_expires() ? $coupon->get_date_expires()->format('c') : null,
        );

        if ($detailed) {
            $data['minimum_amount'] = floatval($coupon->get_minimum_amount());
            $data['maximum_amount'] = floatval($coupon->get_maximum_amount());
            $data['usage_limit'] = $coupon->get_usage_limit();
            $data['usage_count'] = $coupon->get_usage_count();
            $data['individual_use'] = $coupon->get_individual_use();
            $data['product_ids'] = $coupon->get_product_ids();
            $data['excluded_product_ids'] = $coupon->get_excluded_product_ids();
            $data['product_categories'] = $coupon->get_product_categories();
            $data['excluded_product_categories'] = $coupon->get_excluded_product_categories();
        }

        return $data;
    }
}
