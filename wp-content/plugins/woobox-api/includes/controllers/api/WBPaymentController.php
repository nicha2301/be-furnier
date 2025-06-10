<?php

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Post;
use Includes\baseClasses\WBBase;

class WBPaymentController extends WBBase {

    public $module = 'payment';

    public $nameSpace;

    function __construct() {

        $this->nameSpace = WOOBOX_API_NAMESPACE;

        add_action( 'rest_api_init', function () {

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-active-payment-gateway', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_active_payment_gateway' ],
                'permission_callback' => '__return_true'
            ));


         });

    }

    public function woobox_get_active_payment_gateway($request)
    {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $enabled_gateways = [];
        $master = [];
    
        if( $gateways ) {
            foreach( $gateways as $gateway ) {
    
                if( $gateway->enabled == 'yes' ) {
    
                    $enabled_gateways['id'] = $gateway->id;
                    $enabled_gateways['method_title'] = $gateway->method_title;
                    $enabled_gateways['method_description'] = $gateway->method_description;
    
                    array_push($master,$enabled_gateways);
    
                }
            }
        }
    
        $response = new WP_REST_Response($master);
        $response->set_status(200);
    
        return $response;
    }
    

}