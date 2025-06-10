<?php

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Post;
use Includes\baseClasses\WBBase;

class WBCartController extends WBBase {

    public $module = 'cart';

    public $nameSpace;

    function __construct() {

        $this->nameSpace = WOOBOX_API_NAMESPACE;

        add_action( 'rest_api_init', function () {

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/add-cart', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_add_cart' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-cart', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_cart' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/update-cart', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_update_cart' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/delete-cart', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_delete_cart' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/clear-cart', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_clear_cart' ],
                'permission_callback' => '__return_true'
            ));

         });

    }

    public function woobox_add_cart($request)
    {
        global $wpdb;
        
        // Log request để debug
        error_log('Add Cart Request: ' . json_encode($request->get_params()));
        
        $parameters = $request->get_params();
        $data = wbValidationToken($request);
        
        // Log token validation result
        error_log('Token Validation Result: ' . json_encode($data));
        
        if (!$data['status']) {
            return comman_custom_response([
                'status' => false,
                'message' => $data['message']
            ], 401);
        }
        
        $userid = $data['user_id'];
        
        // Thêm kiểm tra user_id
        if (empty($userid)) {
            return comman_custom_response([
                'status' => false,
                'message' => __('User ID is required')
            ], 400);
        }
        
        if (!isset($parameters['pro_id'])) {
            return comman_custom_response([
                'status' => false,
                'message' => __('Product id is required')
            ], 400);
        }
        
        // Log database query
        error_log('Checking existing cart items for user: ' . $userid . ', product: ' . $parameters['pro_id']);
        
        $cart_items = $wpdb->get_results("SELECT * FROM 
                    {$wpdb->prefix}iqonic_add_to_cart 
                        where 
                        user_id=" . $userid . " AND pro_id =" . $parameters['pro_id'] . "", OBJECT);
        
        if (!empty($cart_items))
        {
            return comman_custom_response([
                'status' => false,
                'message' => __('Product Already in Cart')
            ], 400);
        }
        
        $insdata = array();
        
        if(isset($parameters['color']))
        {
            $insdata['color'] = $parameters['color'];
        }
        
        if(isset($parameters['size']))
        {
            $insdata['size'] = $parameters['size'];
        }

        if(isset($parameters['quantity']))
        {
            $insdata['quantity'] = $parameters['quantity'];
        }
        
        $insdata['user_id'] = $userid;
        $insdata['created_at'] = current_time('mysql');
        $insdata['pro_id'] = $parameters['pro_id'];

        $table = $wpdb->prefix . 'iqonic_add_to_cart';
        
        // Log insert data
        error_log('Inserting cart data: ' . json_encode($insdata));
        
        $res = $wpdb->insert($table, $insdata);
        
        if($res > 0)
        {
            return comman_custom_response([
                'status' => true,
                'message' => __('Product Successfully Added To Cart')
            ]);
        }
        else
        {
            // Log database error
            error_log('Database Error: ' . $wpdb->last_error);
            
            return comman_custom_response([
                'status' => false,
                'message' => __('Product Not Added To Cart')
            ], 400);
        }
    }

    public function woobox_get_cart($request)
    {
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];

        global $wpdb;
        global $product;
        $masterarray = array();
        $datarray = array();
    
        $cart_items = $wpdb->get_results("SELECT * FROM 
                                                    {$wpdb->prefix}iqonic_add_to_cart 
                                                        where 
                                                            user_id=" . $userid . "", OBJECT);
    
        if( empty ($cart_items)) {
            return comman_message_response( __('Cart List Empty') );
        }

        $product_ids = collect($cart_items)->toArray();

        $response = [
            'total_quantity'    => 0,
            'data'              => []
        ];

        if(!empty($product_ids) && count($product_ids) > 0) {

            $masterarray = collect($product_ids)->map( function ($product_ids) {
            $products = wc_get_product($product_ids->pro_id);
            $exit = false;
            
            if (!empty($products) && $products->status == 'publish')
            {
                $exit = true;
            }
            if ( $exit ){
                $datarray = [
                    'cart_id' => $product_ids->ID,
                    'pro_id' => $products->get_id(),
                    'name' => $products->get_name(),
                    'sku' => $products->get_sku(),
                    'price' => $products->get_price(),
                    'on_sale' => $products->is_on_sale(),
                    'regular_price' => $products->get_regular_price(),
                    'sale_price' => $products->get_sale_price(),
                    'stock_quantity' => $products->get_stock_quantity(),
                    'stock_status' => $products->get_stock_status(),
                    'shipping_class' => $products->get_shipping_class(),
                    'shipping_class_id' => $products->get_shipping_class_id(),
                ];

                $thumb = wp_get_attachment_image_src($products->get_image_id() , "thumbnail");
                $full = wp_get_attachment_image_src($products->get_image_id() , "full");
                
                $datarray['thumbnail'] = !empty($thumb) ? $thumb[0] : null;
                $datarray['full'] = !empty($full) ? $full[0] : null;
    
                $gallery = array();
                foreach ($products->get_gallery_image_ids() as $img_id) {
                    $g = wp_get_attachment_image_src($img_id, "full");
                    $gallery[] = $g[0];
                }
                $datarray['gallery'] = $gallery;
                $gallery = array();
    
                $datarray['created_at'] = $product_ids->created_at;
                $datarray['quantity'] = $product_ids->quantity;
                
                return $datarray;
            }
            });
            $response['total_quantity'] = $masterarray->sum('quantity');
            $response['data'] = woobox_filter_array($masterarray);
        }
        
        return comman_custom_response($response);
    
    }

    public function woobox_update_cart($request)
    {
        global $wpdb;
    
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
        $table = $wpdb->prefix . 'iqonic_add_to_cart';

        
            $insdata = array();
    
            if(isset($parameters['color']))
            {
                $insdata['color'] = $parameters['color'];
            }
            if(isset($parameters['size']))
            {
                $insdata['size'] = $parameters['size'];
            }
            if(isset($parameters['quantity']))    
            {
                $insdata['quantity'] = isset($parameters['quantity']) ? $parameters['quantity'] : 1;
            }  
        
            $insdata['user_id']     = $userid;
            $insdata['created_at']  = current_time('mysql');
            $insdata['pro_id']      = $parameters['pro_id'];
            
            $cond = array(
                "ID" => $parameters['cart_id']
            );
    
            $res = $wpdb->update($table, $insdata, $cond);
    
        if($res > 0)
        {
           $message = __("Cart Updated Successfully");
           $status_code = 200;
        } else {
            $message = __("Cart Not Updated");
            $status_code = 400;
        }
       
        return comman_message_response ( $message, $status_code );
    }

    public function woobox_delete_cart($request)
    {
        global $wpdb;
    
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
        $table = $wpdb->prefix . 'iqonic_add_to_cart';
    
        $cart_items = $wpdb->delete( $table , array ('user_id' => $userid , 'pro_id' => $parameters['pro_id']) );

        return comman_message_response ( __('Product Deleted From Cart'), 200 );
    
    }

    public function woobox_clear_cart($request)
    {
        global $wpdb;
    
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
        $table = $wpdb->prefix . 'iqonic_add_to_cart';

        $cart_items = $wpdb->delete( $table , array ('user_id' => $userid ));

        return comman_message_response ( __('All Product Deleted From Cart'), 200 );
    
    }

}