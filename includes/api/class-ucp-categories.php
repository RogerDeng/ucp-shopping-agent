<?php
/**
 * Categories Endpoint
 *
 * @package WC_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_UCP_Categories extends WC_UCP_REST_Controller
{

    protected $rest_base = 'categories';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // List categories
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_categories'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'parent' => array(
                    'type' => 'integer',
                    'default' => 0,
                    'description' => __('Filter by parent category ID (0 for top-level)', 'ucp-shopping-agent'),
                ),
                'hide_empty' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'include_children' => array(
                    'type' => 'boolean',
                    'default' => false,
                    'description' => __('Include child categories in response', 'ucp-shopping-agent'),
                ),
            ),
        ));

        // Get category by ID
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_category'),
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

        // Get products in category
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/products', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_category_products'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
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
                'include_subcategories' => array(
                    'type' => 'boolean',
                    'default' => true,
                    'description' => __('Include products from subcategories', 'ucp-shopping-agent'),
                ),
            ),
        ));
    }

    /**
     * Get categories list
     */
    public function get_categories($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $parent = (int) $request->get_param('parent');
        $hide_empty = $request->get_param('hide_empty');
        $include_children = $request->get_param('include_children');

        $args = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => $hide_empty,
            'parent' => $parent,
        );

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return $this->error_response(
                'categories_error',
                $terms->get_error_message(),
                500
            );
        }

        $categories = array();
        foreach ($terms as $term) {
            $category = $this->format_category($term);

            if ($include_children) {
                $category['children'] = $this->get_child_categories($term->term_id, $hide_empty);
            }

            $categories[] = $category;
        }

        return $this->success_response($categories, array(
            'total' => count($categories),
        ));
    }

    /**
     * Get child categories recursively
     */
    private function get_child_categories($parent_id, $hide_empty = true)
    {
        $children = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => $hide_empty,
            'parent' => $parent_id,
        ));

        if (is_wp_error($children) || empty($children)) {
            return array();
        }

        $result = array();
        foreach ($children as $child) {
            $category = $this->format_category($child);
            $category['children'] = $this->get_child_categories($child->term_id, $hide_empty);
            $result[] = $category;
        }

        return $result;
    }

    /**
     * Get single category
     */
    public function get_category($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $category_id = (int) $request->get_param('id');
        $term = get_term($category_id, 'product_cat');

        if (!$term || is_wp_error($term)) {
            return $this->error_response(
                'category_not_found',
                __('Category not found.', 'ucp-shopping-agent'),
                404
            );
        }

        $category = $this->format_category($term, true);
        $category['children'] = $this->get_child_categories($term->term_id);

        return $this->success_response($category);
    }

    /**
     * Get products in category
     */
    public function get_category_products($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $category_id = (int) $request->get_param('id');
        $term = get_term($category_id, 'product_cat');

        if (!$term || is_wp_error($term)) {
            return $this->error_response(
                'category_not_found',
                __('Category not found.', 'ucp-shopping-agent'),
                404
            );
        }

        $pagination = $this->get_pagination_params($request);
        $include_subcategories = $request->get_param('include_subcategories');

        // Get category IDs to include
        $category_ids = array($category_id);
        if ($include_subcategories) {
            $category_ids = array_merge($category_ids, $this->get_subcategory_ids($category_id));
        }

        // Convert term IDs to slugs (wc_get_products expects slugs for 'category' param)
        $category_slugs = array();
        foreach ($category_ids as $cat_id) {
            $cat_term = get_term($cat_id, 'product_cat');
            if ($cat_term && !is_wp_error($cat_term)) {
                $category_slugs[] = $cat_term->slug;
            }
        }

        $args = array(
            'status' => 'publish',
            'limit' => $pagination['per_page'],
            'page' => $pagination['page'],
            'category' => $category_slugs,
        );

        $products = wc_get_products($args);

        // Get total count
        $count_args = $args;
        $count_args['limit'] = -1;
        $count_args['return'] = 'ids';
        $total = count(wc_get_products($count_args));

        $formatted_products = array();
        $products_controller = new WC_UCP_Products();

        foreach ($products as $product) {
            $formatted_products[] = $this->format_product_summary($product);
        }

        return $this->success_response(
            array(
                'category' => $this->format_category($term),
                'products' => $formatted_products,
            ),
            $this->format_pagination_meta($pagination['page'], $pagination['per_page'], $total)
        );
    }

    /**
     * Get all subcategory IDs
     */
    private function get_subcategory_ids($parent_id)
    {
        $ids = array();
        $children = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => $parent_id,
            'fields' => 'ids',
        ));

        if (!is_wp_error($children)) {
            foreach ($children as $child_id) {
                $ids[] = $child_id;
                $ids = array_merge($ids, $this->get_subcategory_ids($child_id));
            }
        }

        return $ids;
    }

    /**
     * Format category for response
     */
    private function format_category($term, $detailed = false)
    {
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null;

        $data = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'parent_id' => $term->parent,
            'description' => $term->description,
            'count' => $term->count,
            'url' => get_term_link($term),
            'image' => $image_url,
        );

        if ($detailed) {
            // Get breadcrumb path
            $data['breadcrumb'] = $this->get_category_breadcrumb($term);
        }

        return $data;
    }

    /**
     * Get category breadcrumb
     */
    private function get_category_breadcrumb($term)
    {
        $breadcrumb = array();
        $current = $term;

        while ($current && $current->parent != 0) {
            $parent = get_term($current->parent, 'product_cat');
            if ($parent && !is_wp_error($parent)) {
                array_unshift($breadcrumb, array(
                    'id' => $parent->term_id,
                    'name' => $parent->name,
                    'slug' => $parent->slug,
                ));
                $current = $parent;
            } else {
                break;
            }
        }

        // Add current category at the end
        $breadcrumb[] = array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        );

        return $breadcrumb;
    }

    /**
     * Format product summary for category listing
     */
    private function format_product_summary($product)
    {
        $image_id = $product->get_image_id();

        return array(
            'id' => (string) $product->get_id(),
            'sku' => $product->get_sku() ?: null,
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'url' => $product->get_permalink(),
            'price' => array(
                'amount' => $this->format_price($product->get_price()),
                'currency' => get_woocommerce_currency(),
                'on_sale' => $product->is_on_sale(),
            ),
            'availability' => $product->is_in_stock() ? 'IN_STOCK' : 'OUT_OF_STOCK',
            'image' => $image_id ? wp_get_attachment_url($image_id) : null,
            'rating' => floatval($product->get_average_rating()),
        );
    }
}
