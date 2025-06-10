<?php

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use WP_Post;
use Includes\baseClasses\WBBase;

class WBSliderController extends WBBase {

    public $module = 'slider';

    public $nameSpace;

    function __construct() {

        $this->nameSpace = WOOBOX_API_NAMESPACE;

        add_action( 'rest_api_init', function () {

            register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-slider', array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'woobox_get_slider' ],
                'permission_callback' => '__return_true'
            ));

         });

    }

    public  function woobox_get_slider($request)
    {
        
        
    
        global $app_opt_name;
        $woobox_option = get_option('woobox_app_options');
        
        $array = array();
        $master = array();
    
        
    
        if (isset($woobox_option['opt-slides']) && !empty($woobox_option['opt-slides']))
        {
            foreach ($woobox_option['opt-slides'] as $slide)
            {
                
                $array['image'] = $slide['image'];
                $array['thumb'] = $slide['thumb'];
                
                $array['url'] = $slide['url'];
    
                if (!empty($slide['image']))
                {
                    array_push($master, $array);
                }
    
            }
    
            
            
        }
    
        $response = new WP_REST_Response($master);
        $response->set_status(200);
    
        return $response;
    
    }


}

