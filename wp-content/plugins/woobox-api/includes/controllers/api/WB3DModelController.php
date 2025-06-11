<?php
/**
 * 3D Model API Controller
 *
 * @package woobox-api
 */

namespace Includes\Controllers\Api;
use WP_REST_Response;
use WP_REST_Server;
use WP_Query;
use Includes\baseClasses\WBBase;
use WP_REST_Request;
use WP_Error;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * 3D Model Controller Class
 */
class WB3DModelController extends WBBase {

    /**
     * Module
     *
     * @var string
     */
    protected $module = '3d-model';

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct();
        $this->namespace = $this->nameSpace . '/api/v1/' . $this->module;
        add_action('rest_api_init', [$this, 'register_routes']);
        
        // Add 3D model data to WooCommerce API
        add_filter('woocommerce_rest_prepare_product_object', [$this, 'add_3d_model_to_api'], 10, 3);
        add_filter('woocommerce_rest_product_schema', [$this, 'add_3d_model_schema'], 10);
    }

    /**
     * Add 3D model data to product schema
     * 
     * @param array $schema Product schema array
     * @return array Modified schema
     */
    public function add_3d_model_schema($schema) {
        $schema['properties']['model_url'] = array(
            'description' => __('3D model file URL for the product', 'woobox'),
            'type'        => 'string',
            'context'     => ['view', 'edit'],
        );
        
        $schema['properties']['model_3d'] = array(
            'description' => __('3D model data for the product', 'woobox'),
            'type'        => 'object',
            'context'     => ['view', 'edit'],
            'properties'  => array(
                'file' => array(
                    'description' => __('3D model file URL', 'woobox'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                ),
                'poster' => array(
                    'description' => __('3D model poster URL', 'woobox'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                ),
                'autorotate' => array(
                    'description' => __('Whether model should autorotate', 'woobox'),
                    'type'        => 'boolean',
                    'context'     => ['view', 'edit'],
                ),
                'config' => array(
                    'description' => __('Additional configuration for 3D model viewer', 'woobox'),
                    'type'        => 'object',
                    'context'     => ['view', 'edit'],
                ),
            ),
        );
        
        return $schema;
    }

    /**
     * Add 3D model data to product response
     * 
     * @param WP_REST_Response $response Current response
     * @param object $post Product object
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Modified response
     */
    public function add_3d_model_to_api($response, $post, $request) {
        $product_id = $post->get_id();
        $model_file = get_post_meta($product_id, 'woobox_3d_model_file', true);
        
        if (!empty($model_file)) {
            $data = $response->get_data();
            $data['model_url'] = $model_file;
            $response->set_data($data);
        }
        
        return $response;
    }

    /**
     * Register Routes
     */
    public function register_routes() {
        register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-product-models', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'woobox_get_product_models' ],
            'permission_callback' => '__return_true'
        ));

        register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/get-model-details', array(
            'methods'             => WP_REST_Server::ALLMETHODS,
            'callback'            => [ $this, 'woobox_get_model_details' ],
            'permission_callback' => '__return_true'
        ));

        register_rest_route( $this->nameSpace . '/api/v1/' . $this->module, '/upload-model', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'woobox_upload_model' ],
            'permission_callback' => [ $this, 'woobox_check_admin_permission' ]
        ));
    }

    /**
     * Check if user has admin permissions
     */
    public function woobox_check_admin_permission($request) {
        $data = wbValidationToken($request);
        if (!$data['status']) return false;
        
        $user_id = $data['user_id'];
        $user = get_user_by('ID', $user_id);
        
        return $user && $user->has_cap('manage_woocommerce');
    }

    /**
     * Get 3D models for a product
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function woobox_get_product_models($request) {
        $parameters = $request->get_params();
        
        if (!isset($parameters['product_id'])) {
            return new WP_Error('missing_product_id', __('Product ID is required', 'woobox'), array('status' => 400));
        }
        
        $product_id = absint($parameters['product_id']);
        $model_data = woobox_get_product_3d_model_data($product_id);
        
        if ($model_data === null) {
            return new WP_Error('no_model', __('No 3D model available for this product', 'woobox'), array('status' => 404));
        }
        
        $response = new WP_REST_Response($model_data);
        $response->set_status(200);
        
        return $response;
    }

    /**
     * Get details of a specific product's 3D model
     */
    public function woobox_get_model_details($request) {
        $parameters = $request->get_params();
        
        if (!isset($parameters['product_id'])) {
            return new WP_REST_Response(
                array(
                    'status'  => 400,
                    'message' => __('Product ID is required', 'woobox')
                ), 
                400
            );
        }
        
        $product_id = intval($parameters['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_REST_Response(
                array(
                    'status'  => 404,
                    'message' => __('Product not found', 'woobox')
                ), 
                404
            );
        }
        
        $model_file = get_post_meta($product_id, 'woobox_3d_model_file', true);
        
        if (empty($model_file)) {
            return new WP_REST_Response(
                array(
                    'status'  => 404,
                    'message' => __('No 3D model found for this product', 'woobox')
                ), 
                404
            );
        }
        
        $model_poster = get_post_meta($product_id, 'woobox_3d_model_poster', true);
        $model_autorotate = get_post_meta($product_id, 'woobox_3d_model_autorotate', true) === 'yes';
        $model_config = get_post_meta($product_id, 'woobox_3d_model_config', true);
        
        // Parse additional configuration if available
        $config = array();
        if (!empty($model_config)) {
            $config = json_decode($model_config, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $config = array();
            }
        }
        
        $model_data = array(
            'product_id'  => $product_id,
            'product_name' => $product->get_name(),
            'model' => array(
                'file'       => $model_file,
                'poster'     => $model_poster,
                'autorotate' => $model_autorotate,
                'config'     => $config
            )
        );
        
        return new WP_REST_Response(
            array(
                'status'  => 200,
                'message' => __('3D Model details retrieved successfully', 'woobox'),
                'data'    => $model_data
            ), 
            200
        );
    }

    /**
     * Upload a 3D model file for a product
     */
    public function woobox_upload_model($request) {
        $parameters = $request->get_params();
        
        if (!isset($parameters['product_id'])) {
            return new WP_REST_Response(
                array(
                    'status'  => 400,
                    'message' => __('Product ID is required', 'woobox')
                ), 
                400
            );
        }
        
        $product_id = intval($parameters['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_REST_Response(
                array(
                    'status'  => 404,
                    'message' => __('Product not found', 'woobox')
                ), 
                404
            );
        }
        
        if (!isset($_FILES['model_file'])) {
            return new WP_REST_Response(
                array(
                    'status'  => 400,
                    'message' => __('No file was uploaded', 'woobox')
                ), 
                400
            );
        }
        
        $file = $_FILES['model_file'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        
        // Check file extension
        $allowed_types = array('glb');
        if (!in_array(strtolower($file_ext), $allowed_types)) {
            return new WP_REST_Response(
                array(
                    'status'  => 400,
                    'message' => __('Invalid file type. Only GLB files are allowed', 'woobox')
                ), 
                400
            );
        }
        
        // Upload the file to the WordPress media library
        $upload = wp_upload_bits($file['name'], null, file_get_contents($file['tmp_name']));
        
        if ($upload['error']) {
            return new WP_REST_Response(
                array(
                    'status'  => 500,
                    'message' => $upload['error']
                ), 
                500
            );
        }
        
        // Get file metadata
        $wp_filetype = wp_check_filetype($upload['file'], null);
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        
        // Insert the attachment
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        // Update product meta with the uploaded model
        update_post_meta($product_id, 'woobox_3d_model_file', $upload['url']);
        
        // Update other model settings if provided
        if (isset($parameters['autorotate'])) {
            update_post_meta($product_id, 'woobox_3d_model_autorotate', $parameters['autorotate'] ? 'yes' : 'no');
        }
        
        if (isset($parameters['poster']) && !empty($parameters['poster'])) {
            update_post_meta($product_id, 'woobox_3d_model_poster', sanitize_url($parameters['poster']));
        }
        
        if (isset($parameters['config']) && !empty($parameters['config'])) {
            update_post_meta($product_id, 'woobox_3d_model_config', sanitize_textarea_field($parameters['config']));
        }
        
        return new WP_REST_Response(
            array(
                'status'  => 200,
                'message' => __('3D Model uploaded successfully', 'woobox'),
                'file'    => $upload['url']
            ), 
            200
        );
    }
}

// Initialize the controller
new WB3DModelController(); 