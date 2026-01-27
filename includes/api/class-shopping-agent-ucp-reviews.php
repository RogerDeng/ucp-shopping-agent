<?php
/**
 * Reviews Endpoint
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Reviews extends Shopping_Agent_UCP_REST_Controller
{

    protected $rest_base = 'reviews';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // List reviews
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_reviews'),
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
                'product_id' => array(
                    'type' => 'integer',
                    'description' => __('Filter by product ID', 'shopping-agent-with-ucp'),
                ),
                'rating' => array(
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5,
                    'description' => __('Filter by rating', 'shopping-agent-with-ucp'),
                ),
            ),
        ));

        // Get single review
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_review'),
            'permission_callback' => array($this, 'public_permissions_check'),
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

        // Create review
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_review'),
            'permission_callback' => array($this, 'write_permissions_check'),
            'args' => array(
                'product_id' => array(
                    'type' => 'integer',
                    'required' => true,
                ),
                'rating' => array(
                    'type' => 'integer',
                    'required' => true,
                    'minimum' => 1,
                    'maximum' => 5,
                ),
                'review' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'reviewer' => array(
                    'type' => 'string',
                    'required' => true,
                ),
                'reviewer_email' => array(
                    'type' => 'string',
                    'required' => true,
                    'format' => 'email',
                ),
            ),
        ));

        // Get product reviews summary
        register_rest_route($this->namespace, '/' . $this->rest_base . '/product/(?P<id>\d+)/summary', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_product_review_summary'),
            'permission_callback' => array($this, 'public_permissions_check'),
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
    }

    /**
     * Get reviews list
     */
    public function get_reviews($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $pagination = $this->get_pagination_params($request);

        $args = array(
            'type' => 'review',
            'status' => 'approve',
            'number' => $pagination['per_page'],
            'offset' => ($pagination['page'] - 1) * $pagination['per_page'],
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
        );

        // Filter by product
        if ($product_id = $request->get_param('product_id')) {
            $args['post_id'] = (int) $product_id;
        }

        // Filter by rating
        if ($rating = $request->get_param('rating')) {
            $args['meta_query'] = array(
                array(
                    'key' => 'rating',
                    'value' => (int) $rating,
                    'type' => 'NUMERIC',
                ),
            );
        }

        $reviews = get_comments($args);

        // Get total count
        $count_args = $args;
        $count_args['count'] = true;
        unset($count_args['number'], $count_args['offset']);
        $total = get_comments($count_args);

        $formatted_reviews = array();
        foreach ($reviews as $review) {
            $formatted_reviews[] = $this->format_review($review);
        }

        return $this->success_response(
            $formatted_reviews,
            $this->format_pagination_meta($pagination['page'], $pagination['per_page'], $total)
        );
    }

    /**
     * Get single review
     */
    public function get_review($request)
    {
        $review_id = (int) $request->get_param('id');
        $review = get_comment($review_id);

        if (!$review || $review->comment_type !== 'review') {
            return $this->error_response(
                'review_not_found',
                __('Review not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        return $this->success_response($this->format_review($review, true));
    }

    /**
     * Create review
     */
    public function create_review($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $product_id = (int) $request->get_param('product_id');
        $product = wc_get_product($product_id);

        if (!$product || $product->get_status() !== 'publish') {
            return $this->error_response(
                'product_not_found',
                __('Product not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        $rating = (int) $request->get_param('rating');
        $review_content = sanitize_textarea_field($request->get_param('review'));
        $reviewer = sanitize_text_field($request->get_param('reviewer'));
        $reviewer_email = sanitize_email($request->get_param('reviewer_email'));

        // Check if review moderation is required
        $approved = 1;
        if (get_option('comment_moderation') === '1') {
            $approved = 0;
        }

        $comment_data = array(
            'comment_post_ID' => $product_id,
            'comment_author' => $reviewer,
            'comment_author_email' => $reviewer_email,
            'comment_content' => $review_content,
            'comment_type' => 'review',
            'comment_approved' => $approved,
        );

        $comment_id = wp_insert_comment($comment_data);

        if (!$comment_id) {
            return $this->error_response(
                'review_creation_failed',
                __('Failed to create review.', 'shopping-agent-with-ucp'),
                500
            );
        }

        // Add rating meta
        update_comment_meta($comment_id, 'rating', $rating);

        // Update product ratings
        WC_Comments::clear_transients($product_id);

        $review = get_comment($comment_id);

        return $this->success_response($this->format_review($review));
    }

    /**
     * Get product review summary
     */
    public function get_product_review_summary($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $product_id = (int) $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product) {
            return $this->error_response(
                'product_not_found',
                __('Product not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        // Get rating distribution
        $distribution = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);

        $reviews = get_comments(array(
            'post_id' => $product_id,
            'type' => 'review',
            'status' => 'approve',
        ));

        foreach ($reviews as $review) {
            $rating = (int) get_comment_meta($review->comment_ID, 'rating', true);
            if ($rating >= 1 && $rating <= 5) {
                $distribution[$rating]++;
            }
        }

        return $this->success_response(array(
            'product_id' => $product_id,
            'average_rating' => floatval($product->get_average_rating()),
            'review_count' => (int) $product->get_review_count(),
            'rating_count' => (int) $product->get_rating_count(),
            'distribution' => $distribution,
        ));
    }

    /**
     * Format review for response
     */
    private function format_review($review, $detailed = false)
    {
        $rating = (int) get_comment_meta($review->comment_ID, 'rating', true);

        $data = array(
            'id' => (int) $review->comment_ID,
            'product_id' => (int) $review->comment_post_ID,
            'reviewer' => $review->comment_author,
            'rating' => $rating,
            'review' => $review->comment_content,
            'verified' => wc_review_is_from_verified_owner($review->comment_ID),
            'created_at' => get_comment_date('c', $review->comment_ID),
        );

        if ($detailed) {
            $product = wc_get_product($review->comment_post_ID);
            if ($product) {
                $data['product'] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'slug' => $product->get_slug(),
                );
            }
        }

        return $data;
    }
}
