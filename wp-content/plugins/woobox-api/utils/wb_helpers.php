<?php


function wbValidationToken ($request) {
	$data = [
		'message' => 'Valid token',
        'status' => true,
	];
	
	// Log request headers
	error_log('Request Headers: ' . json_encode($request->get_headers()));
	
	$response = collect((new Jwt_Auth_Public('jwt-auth', '1.1.0'))->validate_token($request));
   
	// Log token validation response
	error_log('Token Validation Response: ' . json_encode($response));
	
	if ($response->has('errors')) {
		$data['status'] = false;
		$data['message'] = isset(array_values($response['errors'])[0][0]) ? array_values($response['errors'])[0][0] : __("Authorization failed");
	} else {
        // Kiểm tra response format mới
        if (isset($response['code']) && $response['code'] === 'jwt_auth_valid_token') {
            // Lấy user_id từ token
            $token = $request->get_header('Authorization');
            if ($token) {
                $token = str_replace('Bearer ', '', $token);
                $token_parts = explode('.', $token);
                if (count($token_parts) === 3) {
                    $payload = json_decode(base64_decode($token_parts[1]), true);
                    if (isset($payload['data']['user']['id'])) {
                        $data['user_id'] = $payload['data']['user']['id'];
                        $user_meta = get_userdata($data['user_id']);
                        
                        if ($user_meta) {
                            $user_roles = $user_meta->roles;
                            $data['role'] = $user_roles[0];
                        } else {
                            $data['status'] = false;
                            $data['message'] = __("User not found");
                        }
                    } else {
                        $data['status'] = false;
                        $data['message'] = __("Invalid token payload");
                    }
                } else {
                    $data['status'] = false;
                    $data['message'] = __("Invalid token format");
                }
            } else {
                $data['status'] = false;
                $data['message'] = __("Authorization header not found");
            }
        } else {
            $data['status'] = false;
            $data['message'] = __("Invalid token");
        }
    }
	
	// Log final validation result
	error_log('Final Validation Result: ' . json_encode($data));
	
	return $data;
}

function wbGenerateToken( $data ) {
	return wp_remote_post( get_home_url() . "/wp-json/jwt-auth/v1/token" , array(
		'body' => $data
	));
}
function wbValidateRequest($rules, $request, $message = [])
{
	$error_messages = [];
	$required_message = ' field is required';
	$email_message =  ' has invalid email address';

	if (count($rules)) {
		foreach ($rules as $key => $rule) {
			if (strpos($rule, '|') !== false) {
				$ruleArray = explode('|', $rule);
				foreach ($ruleArray as $r) {
					if ($r === 'required') {
						if (!isset($request[$key]) || $request[$key] === "" || $request[$key] === null) {
							$error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $required_message;
						}
					} elseif ($r === 'email') {
						if (isset($request[$key])) {
							if (!filter_var($request[$key], FILTER_VALIDATE_EMAIL) || !is_email($request[$key])) {
								$error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $email_message;
							}
						}
					}
				}
			} else {
				if ($rule === 'required') {
					if (!isset($request[$key]) || $request[$key] === "" || $request[$key] === null) {
						$error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $required_message;
					}
				} elseif ($rule === 'email') {
					if (isset($request[$key])) {
						if (!filter_var($request[$key], FILTER_VALIDATE_EMAIL) || !is_email($request[$key]) ) {
							$error_messages[] = isset($message[$key]) ? $message[$key] : str_replace('_', ' ', $key) . $email_message;
						}
					}
				}
			}

		}
	}

	return $error_messages;
}


function wbRecursiveSanitizeTextField($array)
{
	$filterParameters = [];
	foreach ($array as $key => $value) {

		if ($value === '') {
			$filterParameters[$key] = null;
		} else {
			if (is_array($value)) {
				$filterParameters[$key] = wbRecursiveSanitizeTextField($value);
			} else {
				if (preg_match("/<[^<]+>/", $value, $m) !== 0) {
					$filterParameters[$key] = $value;
				} else {
					$filterParameters[$key] = sanitize_text_field($value);
				}
			}
		}

	}

	return $filterParameters;
}

function wbGetErrorMessage ($response) {
	return isset(array_values($response->errors)[0][0]) ? array_values($response->errors)[0][0] : __("Internal server error");
}

function wbGenerateString($length_of_string = 10)
{
	// String of all alphanumeric character
	$str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	return substr(str_shuffle($str_result),0, $length_of_string);
}

if(!function_exists('comman_message_response')){
	function comman_message_response($message, $status_code = 200)
	{
		$response = new WP_REST_Response([
			'status' => $status_code >= 200 && $status_code < 300,
			'message' => $message
		]);
		$response->set_status($status_code);
		return $response;
	}
}

if(!function_exists('comman_custom_response')){
	function comman_custom_response( $res, $status_code = 200 )
	{	
		$response = new WP_REST_Response($res);
		$response->set_status($status_code);
		return $response;
	}
}

if(!function_exists('comman_list_response')){
    function comman_list_response( $data )
    {
        $response = new WP_REST_Response(array(
            "data" => $data
        ));

        $response->set_status(200);
        return $response;
    }
}
if(!function_exists('woobox_title_filter')){
function woobox_title_filter( $where, $wp_query ){
    global $wpdb;
    if( $search_term = $wp_query->get( 'woobox_title_filter' ) ) :
        $search_term = $wpdb->esc_like( $search_term );
        $search_term = ' \'%' . $search_term . '%\'';
        $title_filter_relation = ( strtoupper( $wp_query->get( 'title_filter_relation' ) ) == 'OR' ? 'OR' : 'AND' );
        $where .= ' '.$title_filter_relation.' ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
    endif;
    return $where;
}
}

add_filter( 'posts_where', 'woobox_title_filter', 10, 2 );

function woobox_check_zone_location($zone, $code, $postcode = '') {
    $zone_locations = $zone['zone_locations'];

    if (count($zone_locations)) {
        $zone_check = false;
        foreach ($zone_locations as $zone) {
            if ($zone->type == 'state' && $zone->code == $code) {
                $zone_check = true;
            }
        }


        if ($zone_check) {
            foreach ($zone_locations as $location) {
                if ($postcode != '') {
                    if ($location->type == 'postcode' || $location->type == 'country') {
                        if (strpos($location->code, '*') !== false) {
                            $new_postcode = substr($postcode, 0, 4). '*';
                            if ($new_postcode == $location->code) {
                                return true;
                            }
                        } else {
                            if ($location->code == $postcode) {
                                return true;
                            }
                        }
                    } else {
                        if ($location->code == $code) {
                            return true;
                        }
                    }

                } else {
                    if ($location->code == $code) {
                        return true;
                    }
                }
            }
        }
    }
    return false;
}

function iqonic_get_special_product_details_helper ($type, $userid = null)
{
	$product = [];
	global $wpdb;

	$product_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = '{$type}' AND  meta_value = 'yes' ORDER BY `post_id` DESC LIMIT 10", object);

	if (count($product_meta)) {

		foreach ($product_meta as $meta) {
			$data = iqonic_get_product_details_helper($meta->post_id, $userid);
			if ($data != []) {
				$product[] = $data;
			}
		}
	}

	return $product;
}

function iqonic_get_product_details_helper ($product_id , $userid = null)
{
	global $product;
	global $wpdb;
	$product = wc_get_product($product_id);

	if ($product === false) {
		return [];
	}

	$temp = array(
		'id'                    => $product->get_id(),
		'name'                  => $product->get_name(),
		'slug'                  => $product->get_slug(),
		'permalink'             => $product->get_permalink(),
		'date_created'          => wc_rest_prepare_date_response( $product->get_date_created() ),
		'date_modified'         => wc_rest_prepare_date_response( $product->get_date_modified() ),
		'type'                  => $product->get_type(),
		'status'                => $product->get_status(),
		'featured'              => $product->is_featured(),
		'catalog_visibility'    => $product->get_catalog_visibility(),
		'description'           => wpautop( do_shortcode( $product->get_description() ) ),
		'short_description'     => apply_filters( 'woocommerce_short_description', $product->get_short_description() ),
		'sku'                   => $product->get_sku(),
		'price'                 => $product->get_price(),
		'regular_price'         => $product->get_regular_price(),
		'sale_price'            => $product->get_sale_price() ? $product->get_sale_price() : '',
		'date_on_sale_from'     => $product->get_date_on_sale_from() ? date_i18n( 'Y-m-d', $product->get_date_on_sale_from()->getOffsetTimestamp() ) : '',
		'date_on_sale_to'       => $product->get_date_on_sale_to() ? date_i18n( 'Y-m-d', $product->get_date_on_sale_to()->getOffsetTimestamp() ) : '',
		'price_html'            => $product->get_price_html(),
		'on_sale'               => $product->is_on_sale(),
		'purchasable'           => $product->is_purchasable(),
		'total_sales'           => $product->get_total_sales(),
		'virtual'               => $product->is_virtual(),
		'downloadable'          => $product->is_downloadable(),
		'downloads'             => iqonic_get_product_downloads( $product ),
		'download_limit'        => $product->get_download_limit(),
		'download_expiry'       => $product->get_download_expiry(),
		'download_type'         => 'standard',
		'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url() : '',
		'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text() : '',
		'tax_status'            => $product->get_tax_status(),
		'model_url'             => get_post_meta($product_id, 'woobox_3d_model_file', true),
		'3d_model'              => woobox_get_product_3d_model_data($product_id),
		'tax_class'             => $product->get_tax_class(),
		'manage_stock'          => $product->managing_stock(),
		'stock_quantity'        => $product->get_stock_quantity(),
		'in_stock'              => $product->is_in_stock(),
		'backorders'            => $product->get_backorders(),
		'backorders_allowed'    => $product->backorders_allowed(),
		'backordered'           => $product->is_on_backorder(),
		'sold_individually'     => $product->is_sold_individually(),
		'weight'                => $product->get_weight(),
		'dimensions'            => array(
			'length' => $product->get_length(),
			'width'  => $product->get_width(),
			'height' => $product->get_height(),
		),
		'shipping_required'     => $product->needs_shipping(),
		'shipping_taxable'      => $product->is_shipping_taxable(),
		'shipping_class'        => $product->get_shipping_class(),
		'shipping_class_id'     => $product->get_shipping_class_id(),
		'reviews_allowed'       => $product->get_reviews_allowed(),
		'average_rating'        => wc_format_decimal( $product->get_average_rating(), 2 ),
		'rating_count'          => $product->get_rating_count(),
		'related_ids'           => array_map( 'absint', array_values( wc_get_related_products( $product->get_id() ) ) ),
		'upsell_ids'            => array_map( 'absint', $product->get_upsell_ids() ),
		'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sell_ids() ),
		'parent_id'             => $product->get_parent_id(),
		'purchase_note'         => wpautop( do_shortcode( wp_kses_post( $product->get_purchase_note() ) ) ),
		'categories'            => iqonic_get_taxonomy_terms_helper( $product ),
		'tags'                  => iqonic_get_taxonomy_terms_helper( $product, 'tag' ),
		'images'                => iqonic_get_product_images_helper( $product ),
		'attributes'            => iqonic_get_product_attributes( $product ),
		'default_attributes'    => iqonic_get_product_default_attributes( $product ),
		'variations'            => $product->get_children(),
		'grouped_products'      => array(),
		'upsell_id'      => array(),
		'menu_order'            => $product->get_menu_order(),

	);

	$temp['is_added_cart'] = false;
	$temp['is_added_wishlist'] = false;
	
	if ($userid != null) {
		$cart_item = $wpdb->get_row(" SELECT * FROM {$wpdb->prefix}iqonic_add_to_cart WHERE user_id='{$userid}' AND pro_id='{$product_id}'", OBJECT );
		
		if ($cart_item != null ) {
			$temp['is_added_cart'] = true;
		}
		
		$wishlist_item = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}iqonic_wishlist_product WHERE user_id='{$userid}' AND pro_id='{$product_id}'", OBJECT );
		if ($wishlist_item != null ) {
			$temp['is_added_wishlist'] = true;
		}
	}
	
	$author_id = get_post_field( 'post_author', $product_id );

	if(isDokanActive() == true){
		$store = dokan()->vendor->get( $author_id );

		$store_address = $store->get_address();

		$temp['store'] = array(
			'id'        => $store->get_id(),
			'name'      => $store->get_name(),
			'shop_name' => $store->get_shop_name(),
			'url'       => $store->get_shop_url()
		);

		if ($store_address != []) {
			$temp['store']['address'] = $store_address;
		}
		$temp['store']['location'] = $store->get_location();
		$temp['store']['store_open_close'] =  [
			'enabled'      => $store->is_store_time_enabled(),
			'time'         => (object) $store->get_store_time(),
			'open_notice'  => $store->get_store_open_notice(),
			'close_notice' => $store->get_store_close_notice(),
		];
	}
	if (isset($temp['upsell_ids']) && count($temp['upsell_ids'])) {
		$upsell_products = [];

		foreach ($temp['upsell_ids'] as $key => $p_id) {

			$upsell_product = wc_get_product($p_id);

			if ($upsell_product != null) {
				$upsell_products[] = [
					'id'                    => $upsell_product->get_id(),
					'name'                  => $upsell_product->get_name(),
					'slug'                  => $upsell_product->get_slug(),
					'price'                 => $upsell_product->get_price(),
					'regular_price'         => $upsell_product->get_regular_price(),
					'sale_price'            => $upsell_product->get_sale_price() ? $upsell_product->get_sale_price() : '',
					'images'                => iqonic_get_product_images_helper( $upsell_product ),
				];
			}
		}

		if (count($upsell_products)) {
			$temp['upsell_id'] = $upsell_products;
		}
	}
	$temp['woofv_video_embed'] = woobox_woo_featured_video($product->get_id());
	return $temp;

}

function woobox_woo_featured_video($product_id) {
	$woofv_video_embed = get_post_meta( $product_id, '_woofv_video_embed', true );
	if ( $woofv_video_embed == null ) {
		$woofv_video_embed = (object) [];
	}
	return $woofv_video_embed;
}

function iqonic_get_product_downloads( $product ) {
	$downloads = array();

	if ( $product->is_downloadable() ) {
		foreach ( $product->get_downloads() as $file_id => $file ) {
			$downloads[] = array(
				'id'   => $file_id, // MD5 hash.
				'name' => $file['name'],
				'file' => $file['file'],
			);
		}
	}

	return $downloads;
}

function iqonic_get_taxonomy_terms_helper( $product, $taxonomy = 'cat' ) {
	$terms = array();

	foreach ( wc_get_object_terms( $product->get_id(), 'product_' . $taxonomy ) as $term ) {
		$terms[] = array(
			'id'   => $term->term_id,
			'name' => $term->name,
			'slug' => $term->slug,
		);
	}

	return $terms;
}

function iqonic_get_product_images_helper( $product ) {
	$images = array();
	$attachment_ids = array();

	if ( !$product ){
		return $images;
	}
	// Add featured image.
	if ( $product->get_image_id() ) {
		$attachment_ids[] = $product->get_image_id();
	}

	$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

	foreach ( $attachment_ids as $position => $attachment_id ) {
		$attachment_post = get_post( $attachment_id );
		if ( is_null( $attachment_post ) ) {
			continue;
		}

		$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! is_array( $attachment ) ) {
			continue;
		}

		$images[] = array(
			'id'            => (int) $attachment_id,
			'date_created'  => wc_rest_prepare_date_response( $attachment_post->post_date_gmt ),
			'date_modified' => wc_rest_prepare_date_response( $attachment_post->post_modified_gmt ),
			'src'           => current( $attachment ),
			'name'          => get_the_title( $attachment_id ),
			'alt'           => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			'position'      => (int) $position,
		);
	}

	if ( empty( $images ) ) {
		$images[] = array(
			'id'            => 0,
			'date_created'  => wc_rest_prepare_date_response( current_time( 'mysql' ) ), // Default to now.
			'date_modified' => wc_rest_prepare_date_response( current_time( 'mysql' ) ),
			'src'           => wc_placeholder_img_src(),
			'name'          => __( 'Placeholder', 'woocommerce' ),
			'alt'           => __( 'Placeholder', 'woocommerce' ),
			'position'      => 0,
		);
	}

	return $images;
}

function iqonic_get_product_attributes( $product ) {
	$attributes = array();

	if ( $product->is_type( 'variation' ) ) {
		// Variation attributes.
		foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
			$name = str_replace( 'attribute_', '', $attribute_name );

			if ( ! $attribute ) {
				continue;
			}

			// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
			if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
				$option_term = get_term_by( 'slug', $attribute, $name );
				$attributes[] = array(
					'id'     => wc_attribute_taxonomy_id_by_name( $name ),
					'name'   => iqonic_get_product_attribute_taxonomy_label( $name ),
					'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
				);
			} else {
				$attributes[] = array(
					'id'     => 0,
					'name'   => $name,
					'option' => $attribute,
				);
			}
		}
	} else {
		foreach ( $product->get_attributes() as $attribute ) {
			if ( $attribute['is_taxonomy'] ) {
				$attributes[] = array(
					'id'        => wc_attribute_taxonomy_id_by_name( $attribute['name'] ),
					'name'      => iqonic_get_product_attribute_taxonomy_label( $attribute['name'] ),
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => iqonic_get_product_attribute_options( $product->get_id(), $attribute ),
				);
			} else {
				$attributes[] = array(
					'id'        => 0,
					'name'      => $attribute['name'],
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => iqonic_get_product_attribute_options( $product->get_id(), $attribute ),
				);
			}
		}
	}

	return $attributes;
}

function iqonic_get_product_attribute_taxonomy_label( $name ) {
	$tax    = get_taxonomy( $name );
	$labels = get_taxonomy_labels( $tax );

	return $labels->singular_name;
}

function iqonic_get_product_attribute_options( $product_id, $attribute ) {
	if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
		return wc_get_product_terms( $product_id, $attribute['name'], array( 'fields' => 'names' ) );
	} elseif ( isset( $attribute['value'] ) ) {
		return array_map( 'trim', explode( '|', $attribute['value'] ) );
	}

	return array();
}

function iqonic_get_product_default_attributes( $product ) {
	$default = array();

	if ( $product->is_type( 'variable' ) ) {
		foreach ( array_filter( (array) $product->get_default_attributes(), 'strlen' ) as $key => $value ) {
			if ( 0 === strpos( $key, 'pa_' ) ) {
				$default[] = array(
					'id'     => wc_attribute_taxonomy_id_by_name( $key ),
					'name'   => iqonic_get_product_attribute_taxonomy_label( $key ),
					'option' => $value,
				);
			} else {
				$default[] = array(
					'id'     => 0,
					'name'   => wc_attribute_taxonomy_slug( $key ),
					'option' => $value,
				);
			}
		}
	}

	return $default;
}

function woobox_get_product_helper($id,$num_pages = '',$i='')
{
		global $product;
		$product = wc_get_product($id);
        $array['num_pages'] = $num_pages;
        $array['srno'] = $i;

        $array['pro_id'] = $product->get_id();
        $array['categories'] = $product->get_category_ids();

        $array['name'] = $product->get_name();

        $array['type'] = $product->get_type();
        $array['slug'] = $product->get_slug();
        $array['date_created'] = $product->get_date_created();
        $array['date_modified'] = $product->get_date_modified();
        $array['status'] = $product->get_status();
        $array['featured'] = $product->get_featured();
        $array['catalog_visibility'] = $product->get_catalog_visibility();
        $array['description'] = $product->get_description();
        $array['short_description'] = $product->get_short_description();
        $array['sku'] = $product->get_sku();

        $array['virtual'] = $product->get_virtual();
        $array['permalink'] = get_permalink($product->get_id());
        $array['price'] = $product->get_price();
        $array['regular_price'] = $product->get_regular_price();
        $array['sale_price'] = $product->get_sale_price();
        $array['brand'] = $product->get_attribute('brand');
        $array['size'] = $product->get_attribute('size');
        $array['color'] = $product->get_attribute('color');

        $array['weight_attribute'] = $product->get_attribute('weight');

        $array['tax_status'] = $product->get_tax_status();
        $array['tax_class'] = $product->get_tax_class();
        $array['manage_stock'] = $product->get_manage_stock();
        $array['stock_quantity'] = $product->get_stock_quantity();
        $array['stock_status'] = $product->get_stock_status();
        $array['backorders'] = $product->get_backorders();
        $array['sold_individually'] = $product->get_sold_individually();
        $array['get_purchase_note'] = $product->get_purchase_note();
        $array['shipping_class_id'] = $product->get_shipping_class_id();

        $array['weight'] = $product->get_weight();
        $array['length'] = $product->get_length();
        $array['width'] = $product->get_width();
        $array['height'] = $product->get_height();
        // $array['dimensions'] = html_entity_decode($product->wc_format_dimensions());
		// $array['dimensions'] = html_entity_decode( wc_format_dimensions( $product->get_dimensions(false) ) );
		$array['dimensions'] = array(
			'length' => $product->get_length(),
			'width'  => $product->get_width(),
			'height' => $product->get_height(),
		);

        // Get Linked Products
        $array['upsell_ids'] = $product->get_upsell_ids();
        $array['cross_sell_ids'] = $product->get_cross_sell_ids();
        $array['parent_id'] = $product->get_parent_id();

        $array['reviews_allowed'] = $product->get_reviews_allowed();
        $array['rating_counts'] = $product->get_rating_counts();
        $array['average_rating'] = $product->get_average_rating();
        $array['review_count'] = $product->get_review_count();

        $thumb = wp_get_attachment_image_src($product->get_image_id() , "thumbnail");
        $full = wp_get_attachment_image_src($product->get_image_id() , "full");
        $array['thumbnail'] = $thumb[0];
        $array['full'] = $full[0];
        $gallery = array();
        foreach ($product->get_gallery_image_ids() as $img_id)
        {
            $g = wp_get_attachment_image_src($img_id, "full");
            $gallery[] = $g[0];
        }
        $array['gallery'] = $gallery;
        $gallery = array();


        return $array;


}

function woobox_throw_error($msg)
{
     $response = new WP_REST_Response(array(
        "code" => "Error",
        "message" => $msg,
        "data" => array(
            "status" => 404
        )
    )
);
    $response->set_status(404);
    return $response;
}

function allow_payment_without_login( $allcaps, $caps, $args ) {
    // Check we are looking at the WooCommerce Pay For Order Page
    if ( !isset( $caps[0] ) || $caps[0] != 'pay_for_order' )
        return $allcaps;
    // Check that a Key is provided
    if ( !isset( $_GET['key'] ) )
        return $allcaps;

    // Find the Related Order
    $order = wc_get_order( $args[2] );
    if( !$order )
        return $allcaps; # Invalid Order

    // Get the Order Key from the WooCommerce Order
    $order_key = $order->get_order_key();
    // Get the Order Key from the URL Query String
    $order_key_check = $_GET['key'];

    // Set the Permission to TRUE if the Order Keys Match
    $allcaps['pay_for_order'] = ( $order_key == $order_key_check );

    return $allcaps;
}
add_filter( 'user_has_cap', 'allow_payment_without_login', 10, 3 );

function get_enable_category($arr)
{
    $a = (array) $arr;

    $term_meta = get_option("enable_" . $a['term_id']);

    if(!empty($term_meta['enable']))
    {
        return $a;
    }

}

function get_category_child($arr)
{
    $a = (array) $arr;
    if($a)
    {
        $child_terms_ids = get_term_children( $a['term_id'], 'product_cat' );

        $temp = array_map('get_enable_subcategory',$child_terms_ids);

        // $temp = array_filter($temp,function($var)
        // {
        //     return $var !== null;
        // });

        $a['subcategory'] = woobox_filter_array($temp);

        return $a;
    }
}

function woobox_attach_category_image($arr)
{
    $a = (array) $arr;
    if($a)
    {
        $thumb_id = get_term_meta( $a['term_id'], 'thumbnail_id', true );
        $term_img = wp_get_attachment_url(  $thumb_id );

        if($term_img)
        {
            $a['image'] = $term_img;
        }
        else
        {
            $a['image'] = "";
        }
        return $a;
    }
}

function get_enable_subcategory($arr)
{
    $a = (array) $arr;
    foreach($a as $val)
    {
        $term_meta = get_option("enable_" . $val);
        if($term_meta)
        {
            return $val;
        }
    }
}

function woobox_filter_array($arr)
{
    $res = array();
    foreach($arr as $key=>$val)
    {
        if($val != null)
        {
            array_push($res,$val);
        }
    }
    return $res;

}

function get_zone_type ($zone_locations) {
    $type = "";

    $types = $zone_locations->pluck('type')->unique()->toArray();

    if (in_array("state", $types) && in_array("postcode", $types)) {
        $type = "state_postcode";
    } elseif(in_array("country", $types) && in_array("postcode", $types)) {
        $type = "country_postcode";
    } elseif (in_array("state", $types)) {
        $type = "country_state";
    } elseif (in_array("country", $types)) {
        $type = "country";
    }

    return $type;
}

function woobox_check_postcode($zone_locations,$parameters,$postcode){
    $codes = $zone_locations->where('type','postcode')->unique()->map( function ($pcode) use($parameters,$postcode) {

        if (strpos($pcode->code, '...') !== false) {

            $post_code = explode('...', $pcode->code);

            if ($post_code[0] <= $parameters['postcode'] && $post_code[1] >= $parameters['postcode'] ) {
                return $pcode;
            }else{
                return null;
            }
        } elseif (strpos($pcode->code, '*') !== false) {
            if ($pcode->code === $postcode) {
                return $pcode;
            }else{
                return null;
            }
        } elseif($pcode->code == $parameters['postcode']){
            return $pcode;
        }else{
            return null;
        }
    })->filter()->values()->count();

    return $codes;
}
function get_default_shipping_method($shipping_methods){
    $free_shipping = [];
    if(count($shipping_methods) > 0){
        foreach($shipping_methods as $method){
            array_push($free_shipping,$method);
        }
    }
    return array_values($free_shipping);
}

function woobox_get_blogpost_data($wp_query = null, $user_id = null)
{
    $temp = array();
    global $post;
    global $wpdb;

	$image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), [300, 300]);	
    
    $temp = [
        'ID'                => $post->ID,
        'image'             => !empty($image) ? $image[0] : null,
        'post_title'        => get_the_title(),
        'post_content'      => esc_html(get_the_content()),
        'post_excerpt'      => esc_html(get_the_excerpt()),
        'post_date'         => $post->post_date,
        'post_date_gmt'     => $post->post_date_gmt,
        'readable_date'     => get_the_date(),
        'share_url'         => get_the_permalink(),
        'human_time_diff'   => human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago'),
        'no_of_comments'    => get_comments_number(),
        'post_author_name'  => get_the_author( 'display_name' , $post->post_author )
    ];
    return $temp;
}

function isDokanActive() {

    if (!function_exists('get_plugins')) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugins = get_plugins();
    
    foreach ($plugins as $key => $value) {
        if($value['TextDomain'] === 'dokan-lite') {
            return (is_plugin_active($key) ? true : false);
        }
    }
    return false ;
}

if (!function_exists('iqonic_get_product_details_with_3d_model')) {
    function iqonic_get_product_details_with_3d_model($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return null;
        }
        
        $product_data = iqonic_get_product_details_helper($product_id);
        
        // Add 3D model information
        $model_file = get_post_meta($product_id, 'woobox_3d_model_file', true);
        
        if (!empty($model_file)) {
            $product_data['has_3d_model'] = true;
            $product_data['model_preview'] = array(
                'file' => $model_file,
                'poster' => get_post_meta($product_id, 'woobox_3d_model_poster', true),
                'autorotate' => get_post_meta($product_id, 'woobox_3d_model_autorotate', true) === 'yes'
            );
        } else {
            $product_data['has_3d_model'] = false;
        }
        
        return $product_data;
    }
}

function woobox_get_product_3d_model_data($product_id) {
    $model_data = array(
        'model_url' => get_post_meta($product_id, 'woobox_3d_model_file', true),
        'autorotate' => get_post_meta($product_id, 'woobox_3d_model_autorotate', true) === 'yes',
        'poster' => get_post_meta($product_id, 'woobox_3d_model_poster', true),
        'config' => get_post_meta($product_id, 'woobox_3d_model_config', true),
    );
    
    if (!empty($model_data['model_url'])) {
        $model_data['viewer_url'] = plugin_dir_url(dirname(__FILE__)) . 'view-3d-model.php?model_id=' . $product_id;
    } else {
        $model_data = null;
    }
    
    return $model_data;
}
