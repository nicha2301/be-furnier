<?php

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Post;
use Includes\baseClasses\WBBase;

class WBWishlistController extends WBBase {

    public $module = 'wishlist';

    public $nameSpace;

	function __construct() {

        $this->nameSpace = WOOBOX_API_NAMESPACE;

        add_action( 'rest_api_init', function () {

			register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/add-wishlist', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_add_wishlist' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-wishlist', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_wishlist' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/delete-wishlist', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_delete_wishlist' ],
                'permission_callback' => '__return_true'
            ));

        });

    }

    public function woobox_add_wishlist($request)
    {
        global $wpdb;
    
        $parameters = $request->get_params();
    
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $table = $wpdb->prefix . 'iqonic_wishlist_product';
        $userid = $data['user_id'];
        $wishlist_items = $wpdb->get_results("SELECT * FROM 
                    {$table} 
                        where 
                            user_id=" . $userid . " AND pro_id =" . $parameters['pro_id'] . "", OBJECT);

        if (!empty($wishlist_items))
        {
            return comman_message_response ( __('Product Already in Wishlist') , 400 );
        }
    
         
        $insdata['user_id'] = $userid;
        $insdata['created_at'] = current_time('mysql');
        $insdata['pro_id'] = $parameters['pro_id'];
            
            
        
    
        $wpdb->insert($table, $insdata);
    
        return comman_message_response ( __('Product Succesfully Added To Wishlist') , 200 );
    
    }

    public function woobox_get_wishlist($request)
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
    
        $wishlist_items = $wpdb->get_results("SELECT * FROM 
                                                    {$wpdb->prefix}iqonic_wishlist_product 
                                                        where 
                                                            user_id=" . $userid . "", OBJECT);
        if (empty($wishlist_items))
        {
            return comman_custom_response ( $masterarray );
        }
        else
        {
            $product_ids = collect($wishlist_items)->toArray();

            if(!empty($product_ids) && count($product_ids) > 0) {

                $masterarray = collect($product_ids)->map( function ($product_ids) {
                $products = wc_get_product($product_ids->pro_id);

                $exit = false;
                if (!empty($products) && $products->status == 'publish'){
                    $exit = true;
                }
                
                if ( $exit ){
                    $datarray = [
                        'pro_id' => $products->get_id(),
                        'name' => $products->get_name(),
                        'sku' => $products->get_sku(),
                        'price' => $products->get_price(),
                        'regular_price' => $products->get_regular_price(),
                        'sale_price' => $products->get_sale_price(),
                        'stock_quantity' => $products->get_stock_quantity(),
                        'in_stock' => $products->is_in_stock(),
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
                    
                    return $datarray;
                }
                });
                $masterarray = woobox_filter_array($masterarray);
            }

        }
    
        return comman_custom_response ( $masterarray );
    
    }

    public function woobox_delete_wishlist($request)
    {
        global $wpdb;
    
        $parameters = $request->get_params();
        $table = $wpdb->prefix . 'iqonic_wishlist_product';

        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
    
        $wishlist_items = $wpdb->delete( $table , array ('user_id' => $userid , 'pro_id' => $parameters['pro_id']) );

        if( $wishlist_items != 0 ) {
            return comman_message_response ( __('Product Deleted From Wishlist') , 200 );
        } else {
            return comman_message_response ( __('Product Not Available in Wishlist') , 400 );
        }
    
    }

  

}
