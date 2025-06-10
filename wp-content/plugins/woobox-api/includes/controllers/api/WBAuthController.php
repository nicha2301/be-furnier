<?php

namespace Includes\Controllers\Api;

use Includes\baseClasses\WBBase;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

class WBAuthController extends WBBase {

	public $module = 'auth';

	public $nameSpace;

	function __construct() {

		$this->nameSpace = WOOBOX_API_NAMESPACE;

		add_action( 'rest_api_init', function () {

			register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/registration', array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'createUser' ],
				'permission_callback' => '__return_true',
			) );

		} );
	}

	public function createUser($request) {

		$reqArr = $request->get_params();

		$validation = wbValidateRequest([
			'user_login' => 'required',
			'first_name' => 'required',
			'last_name' => 'required',
			'user_email' => 'email',
			'user_pass' => 'required',
		], $reqArr);
		
		$error = new WP_Error();
		if (count($validation)) {
			return comman_message_response($validation[0] , 400);
		}
		
		$res = wp_insert_user($reqArr);

		if (isset($res->errors)) {
			return comman_message_response(wbGetErrorMessage($res),400);
		}

		wp_update_user([
			'ID' => $res,
			'first_name' => $reqArr['first_name'],
			'last_name' => $reqArr['last_name']
		]);

		$users = get_userdata( $res );
		
		// WooCommerce specific code
		if (class_exists('WooCommerce')) {
			if( isset($reqArr['usertype']) && $reqArr['usertype'] == 'seller' ){
				$users->set_role('seller');
				update_user_meta( $res, 'dokan_enable_selling', $reqArr['dokan_enable_selling'] );
			} else {
				$users->set_role('customer');
			}
		} else {
			$users->set_role('subscriber');
		}
		$response['data'] = [
			"first_name" => $users->first_name,
			"last_name" => $users->last_name,
			"user_email" => $users->user_email,
			"user_login" => $users->user_login
		];

		$response['message'] = __('Register succesfully');
		return comman_custom_response($response);
	}
}