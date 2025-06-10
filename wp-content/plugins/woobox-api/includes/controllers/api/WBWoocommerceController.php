<?php

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Post;
use Includes\baseClasses\WBBase;
use WC_Shipping_Zones;
use WC_Shipping_Zone;
use WC_Order;
use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class WBWoocommerceController extends WBBase {

    public $module = 'woocommerce';

	public $nameSpace;

	function __construct() {

        $this->nameSpace = WOOBOX_API_NAMESPACE;
        
        add_action( 'rest_api_init', function () {

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-product', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_product' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-product-details', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_product_details' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-sub-category', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_sub_category' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-product-attribute', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_product_attribute' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-single-product', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_single_product' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-featured-product', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_featured_product' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-offer-product', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_offer_product' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-search-product', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_search_product' ],
                'permission_callback' => '__return_true'
            ));


            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-dashboard', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_dashboard' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-checkout-url', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_checkout_url' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-product-attributes', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_product_attributes_with_terms' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-customer-orders', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_customer_orders' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-stripe-client-secret', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'getStripeClientSecret' ],
                'permission_callback' => '__return_true'
            ));
            if(isDokanActive() == true){
                register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-vendors', array(
                    'methods'             => WP_REST_Server::ALLMETHODS,
                    'callback'            => [ $this, 'woobox_get_vendors' ],
                    'permission_callback' => '__return_true'
                ));

                register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-vendor-products', array(
                    'methods'             => WP_REST_Server::ALLMETHODS,
                    'callback'            => [ $this, 'woobox_get_vendor_products' ],
                    'permission_callback' => '__return_true'
                ));
            }
            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-shipping-methods', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_method' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-admin-dashboard', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_admin_dashboard' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-vendor-dashboard', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_vendor_dashboard' ],
                'permission_callback' => '__return_true'
            ));

            // 3D Model Endpoints
            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-product-3d-model', array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_product_3d_model' ],
                'permission_callback' => '__return_true'
            ));

        });
    }

    public function woobox_get_product( $request ) {
        global $product;
    
        $parameters = $request->get_params();
    
        $array       = array();
        $masterarray = array();
    
        $meta      = array();
        $dummymeta = array();
        $taxargs   = array();
        $tax_query = array();
        $args      = array();
        $page      = 1;
        $product_per_page      = 15;
    
        $data = wbValidationToken($request);
		$userid = null;
		if ($data['status']) {
			$userid = $data['user_id'];
		}
    
        if ( ! empty( $parameters ) ) {
            foreach ( $parameters as $key => $data ) {
                $taxargs['relation'] = 'AND';
    
                if ( $key == "price" ) {
                    $meta['key']     = '_price';
                    $meta['value']   = $parameters['price'];
                    $meta['compare'] = 'BETWEEN';
                    $meta['type']    = 'NUMERIC';

                    array_push( $dummymeta, $meta );
                }
    
                if ( $key == "category" ) {
                    $tax_query['taxonomy'] = 'product_cat';
                    $tax_query['field']    = 'term_id';
                    $tax_query['terms']    = $parameters[ $key ];
                    $tax_query['operator'] = 'IN';
                    array_push( $taxargs, $tax_query );
                }
    
                if ( $key == "page" ) {
                    $page = $parameters[ $key ];
                }
    
            }
    
            if(isset($parameters['attribute']) && !empty($parameters['attribute']))
            {
                foreach($parameters['attribute'] as $key=>$val)
                {
                    foreach($val as $k => $v)
                    {
                        $tax_query['taxonomy'] = $k;
                        $tax_query['field']    = 'term_id';
                        $tax_query['terms']    = $v;
                        $tax_query['operator'] = 'IN';
                        array_push( $taxargs, $tax_query );
                    }
                }
            }
    
            if(isset($parameters['text']) && !empty($parameters['text']))
            {
                $args['woobox_title_filter'] = $parameters['text'];
            }
    
            if(isset($parameters['product_per_page']) && !empty($parameters['product_per_page']))
            {
                $product_per_page = $parameters['product_per_page'];
            }
    
            if(isset($parameters['best_selling']) && !empty($parameters['best_selling']))
            {
                $args['meta_key'] = $parameters['best_selling'];
                $args['orderby'] = $parameters['meta_value_num'];
            }
    
            if(isset($parameters['on_sale']) && !empty($parameters['on_sale']))
            {
                $args['meta_query']     = array(
                    'relation' => 'OR',
                    array( // Simple products type
                        'key'           => $parameters['on_sale'],
                        'value'         => 0,
                        'compare'       => '>',
                        'type'          => 'numeric'
                    ),
                    array( // Variable products type
                        'key'           => '_min_variation_sale_price',
                        'value'         => 0,
                        'compare'       => '>',
                        'type'          => 'numeric'
                    )
                );
    
            }
            if(isset($parameters['featured']) && !empty($parameters['featured']))
            {
                $tax_query['taxonomy'] = $parameters['featured'];
                $tax_query['field']    = 'name';
                $tax_query['terms']    = 'featured';
                $tax_query['operator'] = 'IN';
                array_push( $taxargs, $tax_query );
            }
    
            if(isset($parameters['newest']) && !empty($parameters['newest']))
            {
                $args['orderby'] = 'ID';
                $args['order'] = 'DESC';
    
            }
    
            if(isset($parameters['special_product']) && !empty($parameters['special_product']))
            {
                $dummymeta =
                    array(
                        array(
                            'key' => 'woobox_'.$parameters['special_product'],
                            'value' => array('yes'),
                            'compare' => 'IN',
                        )
                    );
                array_push( $meta, $dummymeta );
            }    
        }

        $args['post_type']      = 'product';
        $args['post_status']    = 'publish';
        $args['posts_per_page'] = $product_per_page;
        $args['paged']          = $page;
    
        if ( ! empty( $meta ) ) {
            $args['meta_query'] = $dummymeta;
        }
        if ( ! empty( $taxargs ) ) {
            $args['tax_query'] = $taxargs;
        }

        $wp_query = new WP_Query( $args );
    
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $i       = 1;
        while ( $wp_query->have_posts() ) {
            $num_pages = $wp_query->max_num_pages;
            $wp_query->the_post();
            $masterarray [] = iqonic_get_product_details_helper( get_the_ID() ,$userid );
            $i ++;
        }
        $response = array(
                "num_of_pages" => $num_pages,
                "data" => $masterarray
            );
        return comman_custom_response($response);
    }

    public function woobox_get_product_details( $request ) {

        global $product;
    
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);
		$userid = null;
		if ($data['status']) {
			$userid = $data['user_id'];
		}
    
        $json_response = [];
    
        $product_details = iqonic_get_product_details_helper( $parameters['product_id'] ,$userid );
    
        if ( $product_details != [] ) {
            $json_response[] = $product_details;
            if ( isset( $product_details['variations'] ) && count( $product_details['variations'] ) ) {
                foreach ( $product_details['variations'] as $variation ) {
                    $product = iqonic_get_product_details_helper( $variation ,$userid );
    
                    if ( $product != [] ) {
                        $json_response[] = $product;
                    }
                }
            }
        }
    
        $response = new WP_REST_Response( $json_response );
    
        $response->set_status( 200 );
    
        return $response;
    
    }

    public function woobox_get_sub_category( $request ) {

        $parameters = $request->get_params();
    
        $taxonomy     = 'product_cat';
        $orderby      = 'name';
        $show_count   = 0; // 1 for yes, 0 for no
        $pad_counts   = 0; // 1 for yes, 0 for no
        $hierarchical = 1; // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;
    
    
        $args = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'child_of'     => $parameters['cat_id'],
            'hide_empty'   => $empty,
            'parent'       => $parameters['cat_id']
        );
    
        $all_categories = get_categories( $args );
    
        //$temp = array_map('get_enable_category',$all_categories);
        $a   = array_map( 'get_category_child', $all_categories );
        $arr = array_map( 'woobox_attach_category_image', $a );
    
    
        return comman_custom_response ( woobox_filter_array( $arr ) );
    
    }

    public function woobox_get_product_attribute( $request ) {

        $masterarray = array();
        $parameters  = $request->get_params();
    
        $taxonomy     = 'product_cat';
        $orderby      = 'name';
        $show_count   = 0; // 1 for yes, 0 for no
        $pad_counts   = 0; // 1 for yes, 0 for no
        $hierarchical = 1; // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;
    
        $args           = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty,
            'parent'       => 0
        );
        $all_categories = get_categories( $args );
    
        $a   = array_map( 'get_category_child', $all_categories );
        $arr = array_map( 'woobox_attach_category_image', $a );
    
    
        $masterarray['categories'] = woobox_filter_array( $arr );
    
        $size = array();
        if ( taxonomy_exists( 'pa_size' ) ) {
            $size = get_terms( array(
                'taxonomy'   => 'pa_size',
                'hide_empty' => false,
            ) );
    
        }
    
        $masterarray['sizes'] = $size;
    
        $brand = array();
    
        if ( taxonomy_exists( 'pa_brand' ) ) {
            $brand = get_terms( array(
                'taxonomy'   => 'pa_brand',
                'hide_empty' => false,
            ) );
        }
    
        $masterarray['brands'] = $brand;
    
        $color = array();
    
        if ( taxonomy_exists( 'pa_color' ) ) {
            $color = get_terms( array(
                'taxonomy'   => 'pa_color',
                'hide_empty' => false,
            ) );
    
        }
    
        $masterarray['colors'] = $color;
    
        if ( taxonomy_exists( 'pa_weight' ) ) {
            $size = get_terms( array(
                'taxonomy'   => 'pa_weight',
                'hide_empty' => false,
            ) );
    
        }
    
        $masterarray['pa_weight'] = $size;
    
        return comman_custom_response ( $masterarray );
    
    }

    public function woobox_get_single_product( $request ) {

        if(!empty($request['product_id']))
        {
            $exclude_attributes = array(
                'dimensions',
                'assign_product_attributes',
                'attribute_public',
                'attribute_variation',
                'attribute_position',
                'attribute_visible'
            );

            $productid = $request['product_id'];
        
            $product = wc_get_product( $productid );
            $data = [];
            if($product != null)
            {
                
                if(is_object($product))
                {
                    
                    $is_purchasable = $product->is_purchasable() ? 1 : 0;
                    $model_file = get_post_meta($productid, 'woobox_3d_model_file', true);
                    $model_poster = get_post_meta($productid, 'woobox_3d_model_poster', true);
                    $model_autorotate = get_post_meta($productid, 'woobox_3d_model_autorotate', true);
                    $model_config = get_post_meta($productid, 'woobox_3d_model_config', true);
                    
                    // Parse additional configuration if available
                    $config = array();
                    if (!empty($model_config)) {
                        $config = json_decode($model_config, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $config = array();
                        }
                    }
                    
                    $has_3d_model = !empty($model_file);

                    $data = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'slug' => $product->get_slug(),
                        'type' => $product->get_type(),
                        'status' => $product->get_status(),
                        'featured' => $product->is_featured(),
                        'description' => $product->get_description(),
                        'short_description' => $product->get_short_description(),
                        'permalink' => get_permalink( $product->get_id() ),
                        'sku' => $product->get_sku(),
                        'price' => $product->get_price(),
                        'regular_price' => $product->get_regular_price(),
                        'sale_price' => $product->get_sale_price(),
                        'total_sales' => $product->get_total_sales(),
                        'tax_status' => $product->get_tax_status(),
                        'tax_class' => $product->get_tax_class(),
                        'manage_stock' => $product->managing_stock(),
                        'stock_quantity' => $product->get_stock_quantity(),
                        'stock_status' => $product->get_stock_status(),
                        'backorders' => $product->get_backorders(),
                        'weight' => $product->get_weight(),
			'length' => $product->get_length(),
                        'width' => $product->get_width(),
			'height' => $product->get_height(),
                        'reviews_allowed' => $product->get_reviews_allowed(),
                        'average_rating' => $product->get_average_rating(),
                        'rating_count' => $product->get_rating_count(),
                        'related_ids' => $product->get_related(),
                        'upsell_ids' => $product->get_upsell_ids(),
                        'cross_sell_ids' => $product->get_cross_sell_ids(),
                        'parent_id' => $product->get_parent_id(),
                        'menu_order' => $product->get_menu_order(),
                        'attributes' => $product->get_attributes(),                        
                        'is_purchasable' => $is_purchasable,
                        'has_3d_model' => $has_3d_model,
                        '3d_model' => $has_3d_model ? array(
                            'file' => $model_file,
                            'poster' => $model_poster,
                            'autorotate' => $model_autorotate === 'yes',
                            'config' => $config
                        ) : null
                    );

                    if(!empty($product->get_price_html()))
                    $data['price_html'] = $product->get_price_html();
                    if($product->is_on_sale())
                    $data['on_sale'] = $product->is_on_sale();
                    if($product->has_child())
                    $data['has_child'] = $product->has_child();

                    $data['add_to_cart'] =  add_query_arg( 'add-to-cart', $product->get_id(), get_permalink( $product->get_id() ) );

                    $attributes = array();
                    $attributes_tmp = array();
                    if(!empty($data['attributes']))
                    {
                        foreach ( $data['attributes'] as $key => $attr ) {

                            $attributes_tmp['name'] = wc_attribute_label( $key );
                            if ( $attr->is_taxonomy() ) 
                            {
                                
                                $values = wc_get_product_terms( $product->get_id(), $key, array( 'fields' => 'names' ) );
                                $attributes_tmp['options'] = implode( ', ', $values );
                                
                            }
                            else
                            {
                                $attributes_tmp['options'] = implode( ', ', $attr->get_options() );
                            }
                            if(!in_array(strtolower($key), $exclude_attributes))
                                $attributes[] = $attributes_tmp;
                            $attributes_tmp = array();
                        }
                    }

                    $data['categories'] = $product->get_category_ids();
                    $data['name'] = $product->get_name();
                    $data['type'] = $product->get_type();
                    $data['slug'] = $product->get_slug();
                    $data['date_created'] = $product->get_date_created();
                    $data['date_modified'] = $product->get_date_modified();
                    $data['featured'] = $product->get_featured();
                    $data['catalog_visibility'] = $product->get_catalog_visibility();
                    $data['virtual'] = $product->get_virtual();
                    $data['regular_price'] = $product->get_regular_price();
                    $data['brand'] = $product->get_attribute( 'brand' );
                    $data['size'] = $product->get_attribute( 'size' );
                    $data['color'] = $product->get_attribute( 'color' );
                    $data['tax_status'] = $product->get_tax_status();
                    $data['tax_class'] = $product->get_tax_class();
                    $data['manage_stock'] = $product->get_manage_stock();
                    $data['stock_quantity'] = $product->get_stock_quantity();
                    $data['stock_status'] = $product->get_stock_status();
                    $data['backorders'] = $product->get_backorders();
                    $data['sold_individually'] = $product->get_sold_individually();
                    $data['get_purchase_note'] = $product->get_purchase_note();
                    $data['shipping_class_id'] = $product->get_shipping_class_id();
                    $data['weight'] = $product->get_weight();
                    $data['length'] = $product->get_length();
                    $data['width'] = $product->get_width();
                    $data['height'] = $product->get_height();
                    $data['dimensions'] = array(
                        'length' => $product->get_length(),
                        'width'  => $product->get_width(),
                        'height' => $product->get_height(),
                    );
                    $data['reviews_allowed'] = $product->get_reviews_allowed();
                    $data['rating_counts'] = $product->get_rating_counts();
                    $data['review_count'] = $product->get_review_count();
                    $data['is_added_cart'] = false;
                    $data['is_added_wishlist'] = false;
        
        if ($userid != null) {
                        $cart_item = $wpdb->get_row(" SELECT * FROM {$wpdb->prefix}store_add_to_cart WHERE user_id='{$userid}' AND pro_id='{$data['id']}'", OBJECT );
            
            if ($cart_item != null ) {
                            $data['is_added_cart'] = true;
            }
            
                        $wishlist_item = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}store_wishlist_product WHERE user_id='{$userid}' AND pro_id='{$data['id']}'", OBJECT );
            if ($wishlist_item != null ) {
                            $data['is_added_wishlist'] = true;
            }
        }
    
        $thumb              = wp_get_attachment_image_src( $product->get_image_id(), "thumbnail" );
        $full               = wp_get_attachment_image_src( $product->get_image_id(), "full" );
                    $data['thumbnail'] = $thumb[0];
                    $data['full']      = $full[0];
        $gallery            = array();
        foreach ( $product->get_gallery_image_ids() as $img_id ) {
            $g         = wp_get_attachment_image_src( $img_id, "full" );
            $gallery[] = $g[0];
        }
                    $data['gallery'] = $gallery;
        $gallery          = array();
    
                    $data['woofv_video_embed'] = woobox_woo_featured_video($product->get_id());
                }
            }
        }

        $response = new WP_REST_Response( $data );
    
        $response->set_status( 200 );
    
        return $response;
    }

    public function woobox_get_featured_product( $request ) {
        global $product;
    
        $parameters = $request->get_params();
    
        $array       = array();
        $masterarray = array();
    
        $meta                  = array();
        $dummymeta             = array();
        $taxargs               = array();
        $tax_query             = array();
        $args                  = array();
        $page                  = 1;
        $tax_query['taxonomy'] = 'product_visibility';
        $tax_query['field']    = 'name';
        $tax_query['terms']    = 'featured';
        array_push( $taxargs, $tax_query );
    
        if ( ! empty( $parameters ) ) {
            foreach ( $parameters as $key => $data ) {
    
    
                if ( $key == "price" ) {
                    $meta['key']     = '_price';
                    $meta['value']   = $parameters['price'];
                    $meta['compare'] = 'BETWEEN';
                    $meta['type']    = 'NUMERIC';
                    array_push( $dummymeta, $meta );
    
                }
                if ( $key == "category" ) {
                    $tax_query['taxonomy'] = 'product_cat';
                    $tax_query['field']    = 'term_id';
                    $tax_query['terms']    = $parameters[ $key ];
                    $tax_query['operator'] = 'IN';
                    array_push( $taxargs, $tax_query );
                }
                if ( $key == "brand" ) {
                    $tax_query['taxonomy'] = 'pa_brand';
                    $tax_query['field']    = 'slug';
                    $tax_query['terms']    = $parameters[ $key ];
                    $tax_query['operator'] = 'IN';
                    array_push( $taxargs, $tax_query );
    
                }
    
                if ( $key == "size" ) {
                    $tax_query['taxonomy'] = 'pa_size';
                    $tax_query['field']    = 'slug';
                    $tax_query['terms']    = $parameters[ $key ];
                    $tax_query['operator'] = 'IN';
                    array_push( $taxargs, $tax_query );
    
                }
    
                if ( $key == "color" ) {
                    $tax_query['taxonomy'] = 'pa_color';
                    $tax_query['field']    = 'slug';
                    $tax_query['terms']    = $parameters[ $key ];
                    $tax_query['operator'] = 'IN';
                    array_push( $taxargs, $tax_query );
    
                }
    
    
                if ( $key == "page" ) {
                    $page = $parameters[ $key ];
    
                }
    
            }
        }
    
        $args['post_type']      = 'product';
        $args['post_status']    = 'publish';
        $args['posts_per_page'] = 10;
        $args['paged']          = $page;
    
        if ( ! empty( $meta ) ) {
            $args['meta_query'] = $dummymeta;
        }
        if ( ! empty( $taxargs ) ) {
            $args['tax_query'] = $taxargs;
        }
    
    
        $wp_query = new WP_Query( $args );
    
    
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
    
        if ( $total == 0 ) {
            return comman_message_response ( __('Sorry! No Product Available'), 400 );
        }
    
        $product = null;
        $i       = 1;
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
            $product             = wc_get_product( get_the_ID() );
            $array['num_pages']  = $num_pages;
            $array['srno']       = $i;
            $array['pro_id']     = $product->get_id();
            $array['categories'] = $product->get_category_ids();
    
            $array['name'] = $product->get_name();
    
            $array['type']               = $product->get_type();
            $array['slug']               = $product->get_slug();
            $array['date_created']       = $product->get_date_created();
            $array['date_modified']      = $product->get_date_modified();
            $array['status']             = $product->get_status();
            $array['featured']           = $product->get_featured();
            $array['catalog_visibility'] = $product->get_catalog_visibility();
            $array['description']        = $product->get_description();
            $array['short_description']  = $product->get_short_description();
            $array['sku']                = $product->get_sku();
    
            $array['virtual']       = $product->get_virtual();
            $array['permalink']     = get_permalink( $product->get_id() );
            $array['price']         = $product->get_price();
            $array['regular_price'] = $product->get_regular_price();
            $array['sale_price']    = $product->get_sale_price();
            $array['brand']         = $product->get_attribute( 'brand' );
            $array['size']          = $product->get_attribute( 'size' );
            $array['color']         = $product->get_attribute( 'color' );
    
            $array['stock_quantity'] = $product->get_stock_quantity();
            $array['tax_status']     = $product->get_tax_status();
            $array['tax_class']      = $product->get_tax_class();
            $array['manage_stock']   = $product->get_manage_stock();
    
            $array['stock_status']      = $product->get_stock_status();
            $array['backorders']        = $product->get_backorders();
            $array['sold_individually'] = $product->get_sold_individually();
            $array['get_purchase_note'] = $product->get_purchase_note();
            $array['shipping_class_id'] = $product->get_shipping_class_id();
    
            $array['weight']     = $product->get_weight();
            $array['length']     = $product->get_length();
            $array['width']      = $product->get_width();
            $array['height']     = $product->get_height();
            // $array['dimensions'] = html_entity_decode( wc_format_dimensions( $product->get_dimensions(false) ) );
            $array['dimensions'] = array(
                'length' => $product->get_length(),
                'width'  => $product->get_width(),
                'height' => $product->get_height(),
            );
    
            // Get Linked Products
            $array['upsell_ids']     = $product->get_upsell_ids();
            $array['cross_sell_ids'] = $product->get_cross_sell_ids();
            $array['parent_id']      = $product->get_parent_id();
    
            $array['reviews_allowed'] = $product->get_reviews_allowed();
            $array['rating_counts']   = $product->get_rating_counts();
            $array['average_rating']  = $product->get_average_rating();
            $array['review_count']    = $product->get_review_count();
    
            $thumb              = wp_get_attachment_image_src( $product->get_image_id(), "thumbnail" );
            $full               = wp_get_attachment_image_src( $product->get_image_id(), "full" );
            $array['thumbnail'] = $thumb[0];
            $array['full']      = $full[0];
            $gallery            = array();
            foreach ( $product->get_gallery_image_ids() as $img_id ) {
                $g         = wp_get_attachment_image_src( $img_id, "full" );
                $gallery[] = $g[0];
            }
            $array['gallery'] = $gallery;
            $gallery          = array();
    
            array_push( $masterarray, $array );
            $i ++;
        }
    
        return comman_custom_response ( $masterarray );
    }

    public function woobox_get_offer_product( $request ) {
        global $product;
        global $wpdb;
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);
		$userid = null;
		if ($data['status']) {
			$userid = $data['user_id'];
        }
    
        $array       = array();
        $masterarray = array();
    
        $meta      = array();
        $dummymeta = array();
        $taxargs   = array();
        $tax_query = array();
        $args      = array();
        $page      = 1;
    
        $category = get_term_by( 'slug', 'offers', 'product_cat' );
        
        if ( ! empty( $category ) ) {
            $cat_id   = $category->term_id;
            $tax_query['taxonomy'] = 'product_cat';
            $tax_query['field']    = 'term_id';
            $tax_query['terms']    = $cat_id;
            $tax_query['operator'] = 'IN';
            array_push( $taxargs, $tax_query );
        }
    
        if ( ! empty( $parameters ) ) {
            foreach ( $parameters as $key => $data ) {
    
                if ( $key == "page" ) {
                    $page = $parameters[ $key ];
    
                }
    
            }
        }
    
        $args['post_type']      = 'product';
        $args['post_status']    = 'publish';
        $args['posts_per_page'] = 10;
        $args['paged']          = $page;
    
        if ( ! empty( $taxargs ) ) {
            $args['tax_query'] = $taxargs;
        }
    
        $wp_query = new WP_Query( $args );
    
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
    
    
        if ( $total == 0 ) {
            return comman_message_response ( __('Sorry! No Product Available') , 400 );
        }
    
        $product = null;
        $i       = 1;
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
            $product             = wc_get_product( get_the_ID() );
            $array['num_pages']  = $num_pages;
            $array['srno']       = $i;
            $array['pro_id']     = $product->get_id();
            $array['categories'] = $product->get_category_ids();
    
            $array['name'] = $product->get_name();
    
            $array['type']               = $product->get_type();
            $array['slug']               = $product->get_slug();
            $array['date_created']       = $product->get_date_created();
            $array['date_modified']      = $product->get_date_modified();
            $array['status']             = $product->get_status();
            $array['featured']           = $product->get_featured();
            $array['catalog_visibility'] = $product->get_catalog_visibility();
            $array['description']        = $product->get_description();
            $array['short_description']  = $product->get_short_description();
            $array['sku']                = $product->get_sku();
    
            $array['virtual']       = $product->get_virtual();
            $array['permalink']     = get_permalink( $product->get_id() );
            $array['price']         = $product->get_price();
            $array['regular_price'] = $product->get_regular_price();
            $array['sale_price']    = $product->get_sale_price();
            $array['brand']         = $product->get_attribute( 'brand' );
            $array['size']          = $product->get_attribute( 'size' );
            $array['color']         = $product->get_attribute( 'color' );
    
            $array['tax_status']        = $product->get_tax_status();
            $array['tax_class']         = $product->get_tax_class();
            $array['manage_stock']      = $product->get_manage_stock();
            $array['stock_quantity']    = $product->get_stock_quantity();
            $array['stock_status']      = $product->get_stock_status();
            $array['backorders']        = $product->get_backorders();
            $array['sold_individually'] = $product->get_sold_individually();
            $array['get_purchase_note'] = $product->get_purchase_note();
            $array['shipping_class_id'] = $product->get_shipping_class_id();
    
            $array['weight']     = $product->get_weight();
            $array['length']     = $product->get_length();
            $array['width']      = $product->get_width();
            $array['height']     = $product->get_height();
            // $array['dimensions'] = html_entity_decode( wc_format_dimensions( $product->get_dimensions(false) ) );
            $array['dimensions'] = array(
                'length' => $product->get_length(),
                'width'  => $product->get_width(),
                'height' => $product->get_height(),
            );
    
            // Get Linked Products
            $array['upsell_ids']     = $product->get_upsell_ids();
            $array['cross_sell_ids'] = $product->get_cross_sell_ids();
            $array['parent_id']      = $product->get_parent_id();
    
            $array['reviews_allowed'] = $product->get_reviews_allowed();
            $array['rating_counts']   = $product->get_rating_counts();
            $array['average_rating']  = $product->get_average_rating();
            $array['review_count']    = $product->get_review_count();
    
            $array['is_added_cart'] = false;
            $array['is_added_wishlist'] = false;
            
            if ($userid != null) {
                $cart_item = $wpdb->get_row(" SELECT * FROM {$wpdb->prefix}store_add_to_cart WHERE user_id='{$userid}' AND pro_id='{$array['pro_id']}'", OBJECT );
                
                if ($cart_item != null ) {
                    $array['is_added_cart'] = true;
                }
                
                $wishlist_item = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}store_wishlist_product WHERE user_id='{$userid}' AND pro_id='{$array['pro_id']}'", OBJECT );
                if ($wishlist_item != null ) {
                    $array['is_added_wishlist'] = true;
                }
            }
            $thumb              = wp_get_attachment_image_src( $product->get_image_id(), "thumbnail" );
            $full               = wp_get_attachment_image_src( $product->get_image_id(), "full" );
            $array['thumbnail'] = $thumb[0];
            $array['full']      = $full[0];
            $gallery            = array();
            foreach ( $product->get_gallery_image_ids() as $img_id ) {
                $g         = wp_get_attachment_image_src( $img_id, "full" );
                $gallery[] = $g[0];
            }
            $array['gallery'] = $gallery;
            $gallery          = array();
    
            $array['woofv_video_embed'] = woobox_woo_featured_video($product->get_id());

            array_push( $masterarray, $array );
            $i ++;
        }
    
        return comman_custom_response ( $masterarray );
    }
    
    public function woobox_get_search_product( $request ) {
        global $product;
    
        $parameters = $request->get_params();
    
        $masterarray = array();
    
        $args        = array();
        $page        = 1;
        $search_term = '';
    
        if ( ! empty( $parameters ) ) {
            foreach ( $parameters as $key => $data ) {
    
                if ( $key == "page" ) {
                    $page = $parameters[ $key ];
    
                }
    
                if ( $key == "text" ) {
                    $search_term = $parameters[ $key ];
                    if ( empty( $search_term ) ) {
                        return comman_message_response ( __('Please! Enter Product Name') , 400 );
                    }
                }
    
            }
        }
    
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 50,
            'paged'          => $page,
            's'              => $search_term,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        );
    
        $wp_query = new WP_Query( $args );
    
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
    
    
        if ( $total == 0 ) {
            return comman_message_response ( __('Sorry! No Product Available') , 400 );
        }
    
        $product = null;
        $i       = 1;
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
            $masterarray[] = woobox_get_product_helper( get_the_ID(), $num_pages, $i );
        }
    
        return comman_custom_response ( $masterarray );
    }


    public function woobox_get_dashboard( $request ) {

        global $app_opt_name;
        global $post;
        global $wpdb;
        $parameters    = $request->get_params();
        $woobox_option = get_option( 'woobox_app_options' );
        
        $masterarray   = array();
        $array         = array();
        $dashboard     = array();
        $social        = array();
        $testimonial   = array();

        $product_per_page = isset($parameters['product_per_page']) && !empty($parameters['product_per_page']) ? $parameters['product_per_page'] : 10;
        $data = wbValidationToken($request);
		$userid = null;
		if ($data['status']) {
			$userid = $data['user_id'];
		}
        if ( isset( $woobox_option['whatsapp'] ) ) {
            $social['whatsapp'] = $woobox_option['whatsapp'];
        } else {
            $social['whatsapp'] = '';
        }
    
        if ( isset( $woobox_option['facebook'] ) ) {
            $social['facebook'] = $woobox_option['facebook'];
        } else {
            $social['facebook'] = '';
        }
    
        if ( isset( $woobox_option['twitter'] ) ) {
            $social['twitter'] = $woobox_option['twitter'];
        } else {
            $social['twitter'] = '';
        }
    
        if ( isset( $woobox_option['instagram'] ) ) {
            $social['instagram'] = $woobox_option['instagram'];
        } else {
            $social['instagram'] = '';
        }
    
    
        if ( isset( $woobox_option['contact'] ) ) {
            $social['contact'] = $woobox_option['contact'];
        } else {
            $social['contact'] = '';
        }
    
        if ( isset( $woobox_option['privacy_policy'] ) ) {
            $social['privacy_policy'] = $woobox_option['privacy_policy'];
        } else {
            $social['privacy_policy'] = '';
        }
    
        if ( isset( $woobox_option['copyright_text'] ) ) {
            $social['copyright_text'] = esc_html( $woobox_option['copyright_text'] );
        } else {
            $social['copyright_text'] = '';
        }
    
        if ( isset( $woobox_option['term_condition'] ) ) {
            $social['term_condition'] = esc_html( $woobox_option['term_condition'] );
        } else {
            $social['term_condition'] = '';
        }
    
        $dashboard['social_link'] = $social;
    
        $dashboard['banner'] = [];
        $dashboard['slider'] = [];
        $dashboard['is_dokan_active'] = isDokanActive();
    
        if (isset($woobox_option['banner_slider']) && !empty($woobox_option['banner_slider']))
        {
            foreach ($woobox_option['banner_slider'] as $slide)
            {
                $array['image'] = $slide['image'];
                $array['thumb'] = $slide['thumb'];            
                $array['url'] = $slide['url'];
                $array['desc'] = $slide['title'];

                if ( ! empty( $slide['image'] ) ) {
                    $dashboard['banner'][] = $array;
                }
                $array = array();
            }
        }
        
        if (isset($woobox_option['opt-slides']) && !empty($woobox_option['opt-slides']))
        {
            foreach ($woobox_option['opt-slides'] as $slide)
            {
                $array['image'] = $slide['image'];
                $array['thumb'] = $slide['thumb'];
                $array['url'] = $slide['url'];
    
                if (!empty($slide['image']))
                {
                    $dashboard['slider'][] = $array;
                }
                $array = array();
            }
        }

        $woobox_payment_method = 'webview';
        if ( isset( $woobox_option['payment_method'] ) ) {
            if ( ! empty( $woobox_option['payment_method'] ) ) {
    
                $woobox_payment_method = $woobox_option['payment_method'];
            }
        }

        $dashboard['payment_method'] = $woobox_payment_method;
        
        $theme_color = '';
        if(isset($woobox_option['woobox_app_theme_color']))
        {
            if(!empty($woobox_option['woobox_app_theme_color']))
            {
                $theme_color = $woobox_option['woobox_app_theme_color'];
            }
        }
        $dashboard['theme_color'] = $theme_color;

        $dashboard['enable_coupons'] = false;//wc_coupons_enabled();
    
        $dashboard['currency_symbol'] = array(
                "currency_symbol" => get_woocommerce_currency_symbol(),
                "currency"        => get_woocommerce_currency()
            );
    
        $dashboard['deal_of_the_day']   = iqonic_get_special_product_details_helper( 'woobox_deal_of_the_day', $userid );
        $dashboard['suggested_for_you'] = iqonic_get_special_product_details_helper( 'woobox_suggested_for_you', $userid );
        $dashboard['offer']             = iqonic_get_special_product_details_helper( 'woobox_offer', $userid );
        $dashboard['you_may_like']      = iqonic_get_special_product_details_helper( 'woobox_you_may_like', $userid );
    
    
        $masterarray = array();
    
        if ( $userid != null ) {
            $customer_orders = wc_get_orders( array(
                'meta_key'    => '_customer_user',
                'meta_value'  => $userid,
                'numberposts' => - 1
            ) );
            $count           = 0;
    
            $dashboard['total_order'] = count( $customer_orders );
        }
    
    
        // Best Selling Product
        $masterarray = array();
        $args        = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $product_per_page,
            'paged'          => 1,
            'meta_key'       => 'total_sales',
            'orderby'        => 'meta_value_num'
        );
    
        $wp_query  = new WP_Query( $args );
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
        $i         = 1;
        if ( $total == 0 ) {
            $masterarray = array();
        }
    
    
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();

            $masterarray[] = iqonic_get_product_details_helper( get_the_ID(), $userid );
    
        }
    
        $dashboard['best_selling_product'] = $masterarray;
    
        // Best Selling Product
    
        // Sale product Start
        $masterarray = array();
        $args        = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $product_per_page,
            'paged'          => 1,
            'post__in'       => wc_get_product_ids_on_sale(),
        );
    
        $wp_query  = new WP_Query( $args );
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
        $i         = 1;
        if ( $total == 0 ) {
            $masterarray = array();
        }
    
    
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
    
            $masterarray[] = iqonic_get_product_details_helper( get_the_ID(), $userid );    
    
        }
    
        $dashboard['sale_product'] = $masterarray;
    
        // Sale product end
        // featured Product start
        $masterarray = array();
    
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $product_per_page,
            'paged'          => 1,
            'tax_query'      => array(
                array
                (
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'featured'
                )
    
            )
    
        );
    
        $wp_query  = new WP_Query( $args );
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
        $i         = 1;
        if ( $total == 0 ) {
            $masterarray = array();
        }
    
    
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
    
            $masterarray[] = iqonic_get_product_details_helper( get_the_ID(), $userid );
    
        }
    
        $dashboard['featured'] = $masterarray;
        $masterarray           = array();
    
        // featured Product End
        // newest Product start
    
        $masterarray = array();
    
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $product_per_page,
            'paged'          => 1,
            'orderby'        => 'ID',
            'order'          => 'DESC',
    
    
        );
    
        $wp_query  = new WP_Query( $args );
        $total     = $wp_query->found_posts;
        $num_pages = 1;
        $num_pages = $wp_query->max_num_pages;
        $i         = 1;
        if ( $total == 0 ) {
            $masterarray = array();
        }
    
    
        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();
    
            $masterarray[] = iqonic_get_product_details_helper( get_the_ID(), $userid );
    
        }
    
    
        $taxonomy     = 'product_cat';
        $orderby      = 'name';
        $show_count   = 0; // 1 for yes, 0 for no
        $pad_counts   = 0; // 1 for yes, 0 for no
        $hierarchical = 1; // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;
    
        $args = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty,
            'parent'       => 0,
            'number'       => 10
        );
        $all_categories = get_categories( $args );
    
        $a = array_map( 'get_category_child', $all_categories );
    
        $dashboard['category'] = array_map( 'woobox_attach_category_image', $a );
    
    
        $dashboard['vendors'] = [];
        if(isDokanActive() == true){
            $stores = dokan()->vendor->get_vendors( [
                'number' => 5
            ] );
        
            if (count($stores)) {
                foreach ($stores as $k => $store) {
                    $store_array = $store->to_array();
        
                    $dashboard['vendors'][] = $store_array;
                    if(empty($store_array['social'])){
                        $dashboard['vendors'][$k]['social'] = (object) $store_array['social'];
                    }
                    if(empty($store_array['address'])){
                        $dashboard['vendors'][$k]['address'] = (object) $store_array['address'];
                    }
                    if(empty($store_array['store_open_close']['time'])){
                        $dashboard['vendors'][$k]['store_open_close']['time'] = (object) $store_array['store_open_close']['time'];
                    }
        
                }
            }
        }
    
    
        $dashboard['newest'] = $masterarray;
        $masterarray         = array();
    
        return comman_custom_response ( $dashboard );    
    
    }

    public function woobox_get_checkout_url( $request ) {
        global $wpdb;
        $masterarray = array();
    
    
        $parameters = $request->get_params();
    
        if ( empty( $parameters['order_id'] ) ) {
            return comman_message_response ( __('Order Id Is Missing') , 400 );
        }
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
        $table = $wpdb->prefix . 'iqonic_add_to_cart';
    
        $cart_items = $wpdb->delete( $table , array ('user_id' => $userid ) );
    
        $order = new WC_Order( $parameters['order_id'] );
    
    
        $payment_page = $order->get_checkout_payment_url();
    
        $masterarray['checkout_url'] = $payment_page;
    
        return comman_custom_response ( $masterarray );
    }
    
    public function woobox_get_product_attributes_with_terms( $request ) {
        $masterarray = array();
        $attributes = wc_get_attribute_taxonomies();
        $attribute_data = array();
    
        if (count($attributes)) {
            foreach ($attributes as $attribute) {
    
                $temp = array(
                    'id' => $attribute->attribute_id,
                    'name' => $attribute->attribute_label,
                    'slug' => $attribute->attribute_name,
                    'type' => $attribute->attribute_type,
                    'order_by' => $attribute->attribute_orderby,
                    'has_archives' => $attribute->attribute_public,
                    'terms' => get_terms(wc_attribute_taxonomy_name($attribute->attribute_name), 'hide_empty=0'),
                );
    
                $attribute_data[] = $temp;
            }
        }
    
        $masterarray['attribute'] = $attribute_data;
    
        // Get all product categories...
        $taxonomy     = 'product_cat';
        $orderby      = 'name';
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;
    
        $args = array(
            'taxonomy' => $taxonomy,
            'orderby' => $orderby,
            'show_count' => $show_count,
            'pad_counts' => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li' => $title,
            'hide_empty' => $empty,
            'category_parent' => 0
        );
    
        $all_categories = collect(get_categories($args))->map(function ($category) {
            $category->sub_categories = collect(get_categories(array(
                'category_parent' => $category->category_parent,
                'taxonomy' => 'product_cat',
                'child_of' => 0,
                'parent' => $category->term_id,
                'orderby' => 'name',
                'show_count' => 0,
                'pad_counts' => 0,
                'hierarchical' => 1,
                'title_li' => '',
                'hide_empty' => 0
            )));
            return $category;
        });
    
    
        $masterarray['categories'] = $all_categories;
    
        $response = new WP_REST_Response( $masterarray );
        $response->set_status( 200 );
    
        return $response;
    }
    
    public function woobox_get_customer_orders($request) {
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
    
        global $wpdb;
        $masterarray = array();
    
        $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key' => '_customer_user',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_value' => $userid,
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
            //'post_status' => array('wc-processing', 'wc-pending'),
        ));
    
        if (count($customer_orders)) {
            foreach ($customer_orders as $order) {
                $details = new WC_Order( $order->ID );
                $temp = $details->data;
    
                $line_items = [];
                if (count($temp['line_items'])) {
                    foreach ($temp['line_items'] as $line) {
                        $line_data = $line->get_data();
    
                        if (isset($line_data['product_id'])) {
                            $product_id = $line_data['product_id'];
                            $product = wc_get_product( $product_id );
                            $line_data['product_images'] = iqonic_get_product_images_helper($product);
                        }
    
                        $line_items[] = $line_data;
                    }
                    $temp['line_items'] = $line_items;
                }
    
                $masterarray[] = $temp;
            }
        }
    
        return comman_custom_response ( $masterarray );
    
    }

    public function getStripeClientSecret ($request) {

        $master = [];
    
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        $status_code = 200;
        try {
    
            \Stripe\Stripe::setApiKey($parameters['apiKey']);
    
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $parameters['amount'],
                'currency' => $parameters['currency'],
                /*'metadata' => [
                    'name' => isset($parameters['name']) ? $parameters['name'] : "",
                    'address' => implode($parameters['address'], " ")
                ]*/
    
                'description' => isset($parameters['description']) ? $parameters['description'] : "",
                'shipping' => [
                    'name' => 'Jenny Rosen',
                    'address' => [
                        'line1' => '510 Townsend St',
                        'postal_code' => '98140',
                        'city' => 'San Francisco',
                        'state' => 'CA',
                        'country' => 'US',
                    ],
                ]
    
            ]);
    
            $master['client_secret'] = $intent->client_secret;
            $master['message'] = "Token generated";
    
        } catch (Exception $e) {
            $master['message'] = $e->getMessage();
            $master['client_secret'] = "";

            $status_code = 400;
        }
    
        return comman_custom_response( $master ,$status_code );
    }

    public function woobox_get_vendors ($request) {
        $masterarray = [];
        $parameters = $request->get_params();
        if(isDokanActive() == true){
            $stores = dokan()->vendor->get_vendors([
                'number' => (isset($parameters['vendor_per_page']) && $parameters['vendor_per_page'] != '' ) ? $parameters['vendor_per_page'] : 10,
                'paged' => (isset($parameters['page']) && $parameters['page'] != '' ) ? $parameters['page'] : 1
            ]);
        
            if (count($stores)) {
                foreach ($stores as $k => $store) {
                    $store_array = $store->to_array();
                    $masterarray[] = $store_array;
                    
                    if(empty($store_array['social'])){
                        $masterarray[$k]['social'] = (object) $store_array['social'];
                    }
                    if(empty($store_array['address'])){
                        $masterarray[$k]['address'] = (object) $store_array['address'];
                    }
                    if(empty($store_array['store_open_close']['time'])){
                        $masterarray[$k]['store_open_close']['time'] = (object) $store_array['store_open_close']['time'];
                    }
                }
            }
        }
        return comman_custom_response ( $masterarray );
    
    }

    public function woobox_get_vendor_products ($request) {

        $masterarray = array();
    
        $parameters = $request->get_params();
    
        if ( empty( $parameters['vendor_id'] ) ) {

            return comman_message_response ( __('Vendor id is missing'), 400 );
        }
    
        $data = wbValidationToken($request);
		$userid = null;
		if ($data['status']) {
			$userid = $data['user_id'];
        }
        if(isDokanActive() == true){
            $products = dokan()->product->all( [
                'author' => $parameters['vendor_id']
            ] )->posts;
        
            if (count($products)) {
                foreach ($products as $product) {
                    $masterarray[] =  iqonic_get_product_details_helper($product->ID , $userid);
                }
            }
        }
    
        return comman_custom_response ( $masterarray );
    
    }

    public function woobox_get_method($request) {

        $parameters = $request->get_params();
    
        if (!isset($parameters['country_code']) && empty($parameters['country_code']))
        {
            return comman_message_response ( __('Country code is Required') , 400 );
        }
    
        if (!isset($parameters['state_code']) || $parameters['state_code'] === "") {
            $code = strtoupper($parameters['country_code']);
        } else {
            $code = strtoupper($parameters['country_code']) . ':' . strtoupper($parameters['state_code']);
        }
    
        $postcode = '';
        if (!empty($parameters['postcode'])) {
            $postcode = substr($parameters['postcode'], 0, 4) . '*';
        }
    
        $delivery_zones = collect(WC_Shipping_Zones::get_zones());
    
        $new_shipping_methods = collect([]);
    
        $default_zone = new WC_Shipping_Zone(0);
    
        $default_zone_shipping_methods = collect($default_zone->get_shipping_methods());
    
        $default_shiping_method = $default_zone_shipping_methods->where('enabled', 'yes');
        $default_shiping_methods = $default_shiping_method->unique('id')->map(function ($ship_method) {
            unset($ship_method->instance_form_fields);
            return $ship_method;
        });
    
        if (count($delivery_zones)) {
    
            foreach ($delivery_zones as $delivery_zone) {
    
                $zone_locations = collect($delivery_zone['zone_locations']);
                $all_shipping_methods = collect($delivery_zone['shipping_methods']);
                $shipping_methods = $all_shipping_methods->where('enabled', 'yes');
    
                $free_shipping = get_default_shipping_method($default_shiping_methods);
    
                $zone_type = get_zone_type($zone_locations);
    
                $exit = false;
    
                if ($zone_type !== "") {
    
                    switch ($zone_type) {
                        case "state_postcode":
                            $code_count = $zone_locations->where('code', $code)->count();
    
                            if ($code_count > 0) {
    
                                $codes = woobox_check_postcode($zone_locations,$parameters,$postcode);
    
                                if ($codes > 0 ) {
                                    foreach ($shipping_methods as $method) {
                                        $new_shipping_methods->push($method);
                                    }
                                    $exit = true;
                                }
                            }
                            break;
                        case "country_postcode":
                            $code_count = $zone_locations->where('code', strtoupper($parameters['country_code']))->count();
    
                            if ($code_count > 0) {
                                $codes = woobox_check_postcode($zone_locations,$parameters,$postcode);
    
                                if ($codes > 0) {
                                    foreach ($shipping_methods as $method) {
                                        $new_shipping_methods->push($method);
                                    }
                                    $exit = true;
                                }
                            }
                            break;
                        case "country_state":
                            $code_count = $zone_locations->where('code', $code)->count();
                            if ($code_count > 0) {
                                foreach ($shipping_methods as $method) {
                                    $new_shipping_methods->push($method);
                                }
                                $exit = true;
                            }
                            break;
                        case "country":
                            $code_count = $zone_locations->where('code', strtoupper($parameters['country_code']))->count();
                            if ($code_count > 0) {
                                foreach ($shipping_methods as $method) {
                                    $new_shipping_methods->push($method);
                                }
                                $exit = true;
                            }
                            break;
                        default:
                            $new_shipping_methods->push($free_shipping);
                    }
    
                    if ($exit)
                        break;
    
                }
            }
        }
    
        if(count($new_shipping_methods) == 0 ){
            $new_shipping_methods = $new_shipping_methods->merge($free_shipping);
        }else{

        }
        $new_shipping_methods = $new_shipping_methods->unique('id')->map(function ($ship_method) {
            unset($ship_method->instance_form_fields);
            return $ship_method;
        })->filter()->values()->toArray();
    
        $response = new WP_REST_Response([
            'message' => 'Methods list.',
            'methods' => $new_shipping_methods
        ]);

        return comman_custom_response ( $response );
    
    }


    public function woobox_get_admin_dashboard($request)
    {
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $role = $data['role'];

        if ( $role != 'administrator'){
            return comman_message_response('Sorry, you are not allowed to access this.',401);
        }

        global $woocommerce;
        $parameters = $request->get_params();

        $masterarray = [];
        $dashboard = [];
        $commmet_args = [
            'paged' => 1,
            'number' => 5,
            'comment_status' => 'approve'
        ];

        $comment_data = get_comments($commmet_args);
        $dashboard['new_comment'] = $comment_data;       

        $woocommerce = new Client(
            get_home_url(),
            $parameters['ck'],
            $parameters['cs'],
            [
                'wp_api' => true,
                'version' => 'wc/v3',
                'query_string_auth' => true
            ]
        );
        
        try {
            $results = $woocommerce->get('');
        } catch (HttpClientException $e) {
            return comman_message_response($e->getMessage(), $e->getCode()); // Error message.
        }

        $new_order = $woocommerce->get('orders');
        $dashboard['new_order'] = $new_order;

        // sales report.
        $sale_query = [
            'date_min' => $parameters['date_min'], 
            'date_max' => $parameters['date_max']
        ];
        
        $sale_report = $woocommerce->get('reports/sales', $sale_query);
        $dashboard['sale_report'] = $sale_report;
        
        // list of top sellers report.
        $top_sale_query = [
            'period' => isset($parameters['period']) ? $parameters['period'] : 'week'
        ];

        $top_date_min = isset($parameters['top_date_min']) && $parameters['top_date_min'] != null ? isset($parameters['top_date_min']) : null;
        $top_date_max = isset($parameters['top_date_max']) && $parameters['top_date_max'] != null ? isset($parameters['top_date_max']) : null;

        if( $top_date_max != null && $top_date_min != null )
        {
            $top_sale_query['date_min'] = $top_date_min;
            $top_sale_query['date_max'] = $top_date_max;
        }

        $top_sale_report = $woocommerce->get('reports/sales', $top_sale_query);
        $dashboard['top_sale_report'] = $top_sale_report;

        // customers totals report.
        $customer_total = $woocommerce->get('reports/customers/totals');
        $dashboard['customer_total'] = $customer_total;

        // orders totals report.
        $order_total = $woocommerce->get('reports/orders/totals');
        $dashboard['order_total'] = $order_total;

        // products totals report.
        $products_total = $woocommerce->get('reports/products/totals');
        $dashboard['products_total'] = $products_total;

        // reviews totals report.
        $reviews_total = $woocommerce->get('reports/reviews/totals');
        $dashboard['reviews_total'] = $reviews_total;
        
        return comman_custom_response($dashboard);
    }

    public function woobox_get_vendor_dashboard($request)
    {
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $role = $data['role'];

        if ( $role != 'seller'){
            return comman_message_response('Sorry, you are not allowed to access this.',401);
        }

        $parameters = $request->get_params();

        $dashboard = [];

        $authorization = $request->get_header('Authorization');
        
        $order_list = wp_remote_get( get_home_url() . "/wp-json/dokan/v1/orders" , array(
            'headers' => array(
                'Authorization' => $authorization,
            )
        ));

		// $dashboard['order'] = json_decode($order_list['body']);
        $dashboard['order'] = json_decode(wp_remote_retrieve_body($order_list));
        $product_summary = wp_remote_get( get_home_url() . "/wp-json/dokan/v1/products/summary" , array(
            'headers' => array(
                'Authorization' => $authorization,
            )
        ));

		// $dashboard['product_summary'] = json_decode($product_summary['body']);
        $dashboard['product_summary'] = json_decode(wp_remote_retrieve_body($product_summary));

        $order_summary = wp_remote_get( get_home_url() . "/wp-json/dokan/v1/orders/summary" , array(
            'headers' => array(
                'Authorization' => $authorization,
            )
        ));
        
        // $dashboard['order_summary'] = json_decode($order_summary['body']);
        $dashboard['order_summary'] = json_decode(wp_remote_retrieve_body($order_summary));

        return comman_custom_response($dashboard);
    }
    
    /**
     * Get 3D model for a product
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_product_3d_model($request)
    {
        $parameters = $request->get_params();
        
        if (!isset($parameters['product_id'])) {
            return new \WP_Error('missing_product_id', __('Product ID is required', 'woobox'), array('status' => 400));
        }
        
        $product_id = absint($parameters['product_id']);
        $model_data = woobox_get_product_3d_model_data($product_id);
        
        if ($model_data === null) {
            return new \WP_Error('no_model', __('No 3D model available for this product', 'woobox'), array('status' => 404));
        }
        
        $response = new \WP_REST_Response($model_data);
        $response->set_status(200);
        
        return $response;
    }

}