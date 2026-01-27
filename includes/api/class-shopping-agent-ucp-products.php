<?php
/**
 * Products Endpoint
 *
 * @package Shopping_Agent_UCP_Agent
 */

if (!defined('ABSPATH')) {
    exit;
}

class Shopping_Agent_UCP_Products extends Shopping_Agent_UCP_REST_Controller
{

    protected $rest_base = 'products';

    /**
     * Register routes
     */
    public function register_routes()
    {
        // List products
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_products'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => $this->get_collection_params(),
        ));

        // Get product by ID
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_product'),
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

        // Search products
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'search_products'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => $this->get_search_params(),
        ));

        // Get product by SKU
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sku/(?P<sku>[^/]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_product_by_sku'),
            'permission_callback' => array($this, 'public_permissions_check'),
            'args' => array(
                'sku' => array(
                    'type' => 'string',
                    'required' => true,
                ),
            ),
        ));
    }

    /**
     * Get collection parameters
     */
    private function get_collection_params()
    {
        return array(
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
            'category' => array(
                'type' => 'integer',
                'description' => __('Filter by category ID', 'shopping-agent-with-ucp'),
            ),
            'orderby' => array(
                'type' => 'string',
                'default' => 'date',
                'enum' => array('date', 'title', 'price', 'popularity', 'rating'),
            ),
            'order' => array(
                'type' => 'string',
                'default' => 'desc',
                'enum' => array('asc', 'desc'),
            ),
            'in_stock' => array(
                'type' => 'boolean',
                'description' => __('Filter to only in-stock products', 'shopping-agent-with-ucp'),
            ),
            'featured' => array(
                'type' => 'boolean',
                'description' => __('Filter to only featured products', 'shopping-agent-with-ucp'),
            ),
            'on_sale' => array(
                'type' => 'boolean',
                'description' => __('Filter to only products on sale', 'shopping-agent-with-ucp'),
            ),
        );
    }

    /**
     * Get search parameters
     */
    private function get_search_params()
    {
        $params = $this->get_collection_params();
        $params['q'] = array(
            'type' => 'string',
            'required' => true,
            'description' => __('Search query', 'shopping-agent-with-ucp'),
        );
        $params['min_price'] = array(
            'type' => 'number',
            'description' => __('Minimum price filter', 'shopping-agent-with-ucp'),
        );
        $params['max_price'] = array(
            'type' => 'number',
            'description' => __('Maximum price filter', 'shopping-agent-with-ucp'),
        );
        return $params;
    }

    /**
     * Get products list
     */
    public function get_products($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $pagination = $this->get_pagination_params($request);

        $args = array(
            'status' => 'publish',
            'limit' => $pagination['per_page'],
            'page' => $pagination['page'],
            'orderby' => $request->get_param('orderby') ?: 'date',
            'order' => strtoupper($request->get_param('order') ?: 'DESC'),
        );

        // Category filter
        if ($category = $request->get_param('category')) {
            $args['category'] = array(intval($category));
        }

        // Stock filter
        if ($request->get_param('in_stock') === true) {
            $args['stock_status'] = 'instock';
        }

        // Featured filter
        if ($request->get_param('featured') === true) {
            $args['featured'] = true;
        }

        // On sale filter
        if ($request->get_param('on_sale') === true) {
            $args['on_sale'] = true;
        }

        $products = wc_get_products($args);

        // Get total count
        $count_args = $args;
        $count_args['limit'] = -1;
        $count_args['return'] = 'ids';
        $total = count(wc_get_products($count_args));

        $formatted_products = array();
        foreach ($products as $product) {
            $formatted_products[] = $this->format_product($product);
        }

        return $this->success_response(
            array('products' => $formatted_products),
            $this->format_pagination_meta($pagination['page'], $pagination['per_page'], $total)
        );
    }

    /**
     * Get single product
     */
    public function get_product($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $product_id = (int) $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product || $product->get_status() !== 'publish') {
            return $this->error_response(
                'product_not_found',
                __('Product not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        return $this->success_response(array('product' => $this->format_product($product, true)));
    }

    /**
     * Search products
     */
    public function search_products($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $query = sanitize_text_field($request->get_param('q'));
        $pagination = $this->get_pagination_params($request);

        $args = array(
            'status' => 'publish',
            'limit' => $pagination['per_page'],
            'page' => $pagination['page'],
            's' => $query,
            'orderby' => $request->get_param('orderby') ?: 'relevance',
            'order' => strtoupper($request->get_param('order') ?: 'DESC'),
        );

        // Category filter
        if ($category = $request->get_param('category')) {
            $args['category'] = array(intval($category));
        }

        // Stock filter
        if ($request->get_param('in_stock') === true) {
            $args['stock_status'] = 'instock';
        }

        // Price filters
        $min_price = $request->get_param('min_price');
        $max_price = $request->get_param('max_price');

        $products = wc_get_products($args);

        // Apply price filters manually if needed
        if ($min_price !== null || $max_price !== null) {
            $products = array_filter($products, function ($product) use ($min_price, $max_price) {
                $price = floatval($product->get_price());
                if ($min_price !== null && $price < floatval($min_price)) {
                    return false;
                }
                if ($max_price !== null && $price > floatval($max_price)) {
                    return false;
                }
                return true;
            });
        }

        // Get total count
        $count_args = $args;
        $count_args['limit'] = -1;
        $count_args['return'] = 'ids';
        $total = count(wc_get_products($count_args));

        $formatted_products = array();
        foreach ($products as $product) {
            $formatted_products[] = $this->format_product($product);
        }

        return $this->success_response(
            array('products' => $formatted_products),
            array_merge(
                $this->format_pagination_meta($pagination['page'], $pagination['per_page'], $total),
                array('query' => $query)
            )
        );
    }

    /**
     * Get product by SKU
     */
    public function get_product_by_sku($request)
    {
        $wc_check = $this->check_woocommerce();
        if (is_wp_error($wc_check)) {
            return $wc_check;
        }

        $sku = sanitize_text_field($request->get_param('sku'));
        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            return $this->error_response(
                'product_not_found',
                __('Product with this SKU not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        $product = wc_get_product($product_id);

        if (!$product || $product->get_status() !== 'publish') {
            return $this->error_response(
                'product_not_found',
                __('Product not found.', 'shopping-agent-with-ucp'),
                404
            );
        }

        return $this->success_response(array('product' => $this->format_product($product, true)));
    }

    /**
     * Format product for UCP response
     */
    private function format_product($product, $detailed = false)
    {
        $image_id = $product->get_image_id();
        $main_image = $image_id ? wp_get_attachment_url($image_id) : null;

        $data = array(
            'id' => (string) $product->get_id(),
            'sku' => $product->get_sku() ?: null,
            'name' => $product->get_name(),
            'type' => $product->get_type(),
            'slug' => $product->get_slug(),
            'url' => $product->get_permalink(),
            'description' => array(
                'short' => strip_tags($product->get_short_description()),
                'full' => $detailed ? strip_tags($product->get_description()) : null,
            ),
            'price' => array(
                'amount' => $this->format_price($product->get_price()),
                'regular' => $this->format_price($product->get_regular_price()),
                'sale' => $product->get_sale_price() ? $this->format_price($product->get_sale_price()) : null,
                'currency' => get_woocommerce_currency(),
                'formatted' => $product->get_price_html(),
                'on_sale' => $product->is_on_sale(),
            ),
            'availability' => array(
                'status' => $product->is_in_stock() ? 'IN_STOCK' : 'OUT_OF_STOCK',
                'stock_qty' => $product->get_stock_quantity(),
                'backorders' => $product->backorders_allowed(),
                'manage_stock' => $product->managing_stock(),
            ),
            'images' => array(
                'main' => $main_image,
                'gallery' => $this->get_gallery_images($product),
            ),
            'categories' => $this->get_product_categories($product),
            'attributes' => $this->get_product_attributes($product),
            'rating' => array(
                'average' => floatval($product->get_average_rating()),
                'count' => (int) $product->get_rating_count(),
            ),
            'featured' => $product->is_featured(),
            'created_at' => $product->get_date_created() ? $product->get_date_created()->format('c') : null,
            'updated_at' => $product->get_date_modified() ? $product->get_date_modified()->format('c') : null,
        );

        // Add variations for variable products
        if ($detailed && $product->is_type('variable')) {
            $data['variations'] = $this->get_product_variations($product);
        }

        // Remove null short description if not detailed
        if (!$detailed) {
            $data['description'] = $data['description']['short'];
        }

        return $data;
    }

    /**
     * Get product gallery images
     */
    private function get_gallery_images($product)
    {
        $gallery_ids = $product->get_gallery_image_ids();
        $images = array();

        foreach ($gallery_ids as $id) {
            $url = wp_get_attachment_url($id);
            if ($url) {
                $images[] = $url;
            }
        }

        return $images;
    }

    /**
     * Get product categories
     */
    private function get_product_categories($product)
    {
        $categories = array();
        $term_ids = $product->get_category_ids();

        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $categories[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        return $categories;
    }

    /**
     * Get product attributes
     */
    private function get_product_attributes($product)
    {
        $attributes = array();

        foreach ($product->get_attributes() as $attribute) {
            $attr_data = array(
                'name' => wc_attribute_label($attribute->get_name()),
            );

            if ($attribute->is_taxonomy()) {
                $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), array('fields' => 'names'));
                $attr_data['options'] = $terms;
            } else {
                $attr_data['options'] = $attribute->get_options();
            }

            $attributes[] = $attr_data;
        }

        return $attributes;
    }

    /**
     * Get product variations
     */
    private function get_product_variations($product)
    {
        $variations = array();
        $variation_ids = $product->get_children();

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }

            $variations[] = array(
                'id' => (string) $variation->get_id(),
                'sku' => $variation->get_sku() ?: null,
                'price' => array(
                    'amount' => $this->format_price($variation->get_price()),
                    'regular' => $this->format_price($variation->get_regular_price()),
                    'sale' => $variation->get_sale_price() ? $this->format_price($variation->get_sale_price()) : null,
                    'currency' => get_woocommerce_currency(),
                ),
                'availability' => array(
                    'status' => $variation->is_in_stock() ? 'IN_STOCK' : 'OUT_OF_STOCK',
                    'stock_qty' => $variation->get_stock_quantity(),
                ),
                'attributes' => $variation->get_variation_attributes(),
                'image' => $variation->get_image_id() ? wp_get_attachment_url($variation->get_image_id()) : null,
            );
        }

        return $variations;
    }
}
