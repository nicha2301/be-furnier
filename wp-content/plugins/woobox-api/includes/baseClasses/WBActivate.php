<?php

namespace Includes\baseClasses;
use WC_Customer;

class WBActivate extends WBBase {

	public static function activate() {
		( new WBGetDependency( 'jwt-authentication-for-wp-rest-api' ) )->getPlugin();
		( new WBGetDependency( 'woocommerce' ) )->getPlugin();
		( new WBGetDependency( 'dokan-lite' ) )->getPlugin();
		( new WBGetDependency( 'redux-framework' ) )->getPlugin();
		
		( new WBGetDependency( 'wc-rest-payment' ) )->getPlugin();
		( new WBGetDependency( 'woo-advanced-shipment-tracking' ) )->getPlugin();
		( new WBGetDependency( 'woocommerce-gateway-paypal-express-checkout' ) )->getPlugin();
		
		( new WBGetDependency( 'woocommerce-gateway-stripe' ) )->getPlugin();
		( new WBGetDependency( 'woo-razorpay' ) )->getPlugin();
		( new WBGetDependency( 'woo-delivery' ) )->getPlugin();
		( new WBGetDependency( 'woo-advanced-shipment-tracking' ) )->getPlugin();
		( new WBGetDependency( 'woo-featured-video' ) )->getPlugin();

		require_once WOOBOX_API_DIR . 'includes/db/class.woobox.db.php';
		
	}

	public function init() {

		// API handle
		( new WBApiHandler() )->init();

		// Action to change authentication api response ...
		add_filter( 'jwt_auth_token_before_dispatch', array($this, 'jwtAuthenticationResponse'), 10, 2 );


	}

	public function jwtAuthenticationResponse( $data, $user ) {

		$img       = get_user_meta( $user->ID, 'woobox_profile_image' );
		$user_info = get_userdata( $user->ID );

		$data['first_name'] = $user_info->first_name;
		$data['last_name']  = $user_info->last_name;
		$data['user_id']    = $user->ID;
		$data['user_role'] 	= $user->roles;
		$data['avatar'] = get_avatar_url($user->ID);

		$customer = (new WC_Customer( $user->ID ))->get_data();
		$data['billing'] 	= $customer['billing'];
	    $data['shipping'] 	= $customer['shipping'];
		if ( $img ) {
			$data['woobox_profile_image'] = $img[0];
		} else {
			$data['woobox_profile_image'] = "";
		}

		return $data;
	}

}


