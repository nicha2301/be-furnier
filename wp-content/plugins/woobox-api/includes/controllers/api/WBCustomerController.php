<?php

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Post;
use Wp_User;
use Includes\baseClasses\WBBase;

class WBCustomerController extends WBBase {


    public $module = 'customer';

    public $nameSpace;

    function __construct() {

        $this->nameSpace = WOOBOX_API_NAMESPACE;

        add_action( 'rest_api_init', function () {

            register_rest_route( $this->nameSpace . '/api/v2/' . $this->module, '/save-profile-image', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_v2_save_profile_image' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/change-password', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_change_password' ],
				'permission_callback' => '__return_true'
            ));
            
            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/forget-password', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_forgot_password' ],
				'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/social_login', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_customer_by_social' ],
				'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, 'add-address/', array(
                'methods'             => WP_REST_Server::ALLMETHODS,
                'callback'            => [ $this, 'woobox_set_multiple_address' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, 'get-address/', array(
                'methods'             => WP_REST_Server::ALLMETHODS,
                'callback'            => [ $this, 'woobox_get_multiple_address' ],
                'permission_callback' => '__return_true'
            ));

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, 'delete-address/', array(
                'methods'             => WP_REST_Server::ALLMETHODS,
                'callback'            => [ $this, 'woobox_delete_multiple_address' ],
                'permission_callback' => '__return_true'
            ));

        });

    }

    public function woobox_v2_save_profile_image($request)
    {
        $header = $request->get_headers();
        $parameters = $request->get_params();
        
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $userid = $data['user_id'];
        $users = get_userdata( $userid );
        if( isset($_FILES['profile_image']) && $_FILES['profile_image'] != null ){
            $profile_image = media_handle_upload( 'profile_image', 0 );
            
            update_user_meta( $userid, 'woobox_profile_image', wp_get_attachment_url($profile_image) );
        }
		
		$response['woobox_profile_image'] = get_user_meta($userid, 'woobox_profile_image', true );
		$response['message'] = 'Profile has been updated succesfully';

        return comman_custom_response( $response );
    }

    public function woobox_change_password($request) {

		$data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
		}

		$parameters = $request->get_params();

		$userdata = get_user_by('ID', $data['user_id']);
		
		if ($userdata == null) {
			
			if ($userdata == null) {
				$message = __('User not found');
				return comman_message_response($message,400);
			}
		}

		$status_code = 200;

		if (wp_check_password($parameters['old_password'], $userdata->data->user_pass)){
			wp_set_password($parameters['new_password'], $userdata->ID);
			$message = __("Password has been changed successfully");
		}else {
			$status_code = 400;
			$message = __("Old password is invalid");
		}
		return comman_message_response($message,$status_code);
	}

    public function woobox_forgot_password($request) {
		$parameters = $request->get_params();
		$email = $parameters['email'];
		
		$user = get_user_by('email', $email);
		$message = null;
		$status_code = null;
		
		if($user) {      

			$title = 'New Password';
            $password = wbGenerateString();
            $message = '<label><b>Hello,</b></label>';
            $message.= '<p>Your recently requested to reset your password. Here is the new password for your App</p>';
            $message.='<p><b>New Password </b> : '.$password.'</p>';
            $message.='<p>Thanks,</p>';

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$is_sent_wp_mail = wp_mail($email,$title,$message,$headers);

            if($is_sent_wp_mail) {
				wp_set_password( $password, $user->ID);
				$message = __('Password has been sent successfully to your email address.');
				$status_code = 200;
			} elseif (mail( $email, $title, $message, $headers )) {
				wp_set_password( $password, $user->ID);
				$message = __('Password has been sent successfully to your email address.');
				$status_code = 200;
			} else {
				$message = __('Email not sent');
				$status_code = 400;
			}
		} else {
			$message = __('User not found with this email address');
			$status_code = 400;
		}
		return comman_message_response($message,$status_code);
    }

    public function woobox_get_customer_by_social ( $request ) {

        $parameters = $request->get_params();
		$email = $parameters['email'];
		$password = $parameters['accessToken'];
        $user = get_user_by('email', $email);

        $address = array(
            'first_name' => $parameters['firstName'],
            'last_name'  => $parameters['lastName'],            
            'email'      => $email  
        );

        if ( !$user ) {
            $user = wp_create_user( $email, $password, $email );
            wp_update_user([
				'ID' => $user,
				'display_name' => $parameters['firstName'] .' '. $parameters['lastName'],
			]);
            update_user_meta( $user, 'loginType', $parameters['loginType']);
            update_user_meta( $user, "billing_first_name", $address['first_name'] );
            update_user_meta( $user, "billing_last_name", $address['last_name']);
            update_user_meta( $user, "billing_email", $address['email'] );

            update_user_meta( $user, "shipping_first_name", $address['first_name'] );
            update_user_meta( $user, "shipping_last_name", $address['last_name']);

            update_user_meta( $user, 'first_name', trim( $address['first_name'] ) );
            update_user_meta( $user, 'last_name', trim( $address['last_name'] ) );            
        } else {
            update_user_meta( $user->ID, 'loginType', $parameters['loginType']);
            /*
            $loginType = get_user_meta( $user->ID, 'loginType' , true );
            if( !isset($loginType) || $loginType == ''){
                return comman_message_response('You are already registered with us.',400);
            }
           */
            wp_set_password( $password, $user->ID);
        }
        $u = new WP_User( $user);
        $u->set_role( 'customer' );

        $response = wbGenerateToken( "username=".$email."&password=".$password  );
        
        return comman_custom_response(json_decode($response['body'],true));
    }

    public function woobox_get_multiple_address($request)
    {
        global $wpdb;
        $parameters = $request->get_params();
        
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $table = $wpdb->prefix . 'iqonic_multiple_address';
        $userid = $data['user_id'];

        $address = $wpdb->get_results("SELECT * FROM {$table} WHERE `user_id` = $userid ", OBJECT);

        return comman_custom_response($address);
    }

    public function woobox_set_multiple_address($request)
    {
        global $wpdb;
        $parameters = $request->get_params();
        
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $table = $wpdb->prefix . 'iqonic_multiple_address';
        $userid = $data['user_id'];

        $parameters['user_id'] = $userid;
        $parameters['created_at'] = current_time('mysql');

        if(isset($parameters['ID']) && !empty($parameters['ID'])){
            $cond = [ "ID" => $parameters['ID'] ];
            $res = $wpdb->update($table, $parameters, $cond);            
        } else {
           $res = $wpdb->insert($table, $parameters); 
        }

        if ( $res > 0 ){
            $status_code = 200;
            $message = __("Address Saved");
        } else {
			$status_code = 400;
			$message = __("Address Not Saved");
		}
		return comman_message_response($message,$status_code);
    }


    public function woobox_delete_multiple_address($request)
    {
        global $wpdb;
        $parameters = $request->get_params();
        
        $data = wbValidationToken($request);

		if (!$data['status']) {
			return comman_custom_response($data,401);
        }
        
        $table = $wpdb->prefix . 'iqonic_multiple_address';
        $userid = $data['user_id'];

		$results = $wpdb->delete( $table , array ('ID' => $parameters['ID'] , 'user_id' => $userid ) );
		$status_code = 200;
		if ( $results ) {
			$message = 'Address Deleted Successfully';
		} else {
			$message = 'Address Not Deleted';
			$status_code = 400;
		}

		return comman_message_response( $message , $status_code);
    }
    
}
