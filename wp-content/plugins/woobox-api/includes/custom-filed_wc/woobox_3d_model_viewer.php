<?php
/**
 * Model Viewer Integration for WooCommerce
 * 
 * @package woobox-api
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Enqueue model-viewer script
 */
function woobox_enqueue_model_viewer_script() {
    wp_enqueue_script(
        'model-viewer', 
        'https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js', 
        array(), 
        '1.12.0', 
        true
    );
    wp_script_add_data('model-viewer', 'type', 'module');
    
    // Enqueue dashicons for the 3D viewer controls
    wp_enqueue_style('dashicons');
    
    // Add custom styles for 3D viewer buttons
    wp_add_inline_style('dashicons', '
        .view-3d-model-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #2271b1;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
            line-height: 1.5;
            cursor: pointer;
        }
        .view-3d-model-btn:hover {
            background-color: #135e96;
            color: #fff;
        }
        .view-3d-model-btn .dashicons {
            margin-right: 5px;
            font-size: 16px;
            line-height: 1.5;
        }
    ');
}
add_action('wp_enqueue_scripts', 'woobox_enqueue_model_viewer_script');

/**
 * Get the 3D model viewer URL
 */
function woobox_get_3d_model_viewer_url($product_id) {
    return plugin_dir_url(dirname(dirname(__FILE__))) . 'view-3d-model.php?model_id=' . $product_id;
}

/**
 * Add shortcode for displaying 3D model viewer
 */
function woobox_model_viewer_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'src' => '',
            'alt' => 'A 3D model',
            'ar' => 'true',
            'autoplay' => 'false',
            'autorotate' => 'false',
            'shadow-intensity' => '1',
            'camera-controls' => 'true',
            'poster' => '',
            'width' => '100%',
            'height' => '500px',
        ),
        $atts,
        'model_viewer'
    );
    
    if (empty($atts['src'])) {
        return '<p>Error: No model source specified</p>';
    }
    
    $autorotate = filter_var($atts['autorotate'], FILTER_VALIDATE_BOOLEAN) ? 'auto-rotate' : '';
    $ar = filter_var($atts['ar'], FILTER_VALIDATE_BOOLEAN) ? 'ar' : '';
    $camera_controls = filter_var($atts['camera-controls'], FILTER_VALIDATE_BOOLEAN) ? 'camera-controls' : '';
    $poster_attr = !empty($atts['poster']) ? 'poster="' . esc_url($atts['poster']) . '"' : '';
    
    $output = '
    <model-viewer 
        src="' . esc_url($atts['src']) . '" 
        alt="' . esc_attr($atts['alt']) . '" 
        ' . $autorotate . '
        ' . $ar . '
        ' . $camera_controls . '
        ' . $poster_attr . '
        shadow-intensity="' . esc_attr($atts['shadow-intensity']) . '"
        style="width: ' . esc_attr($atts['width']) . '; height: ' . esc_attr($atts['height']) . ';"
    ></model-viewer>';
    
    return $output;
}
add_shortcode('model_viewer', 'woobox_model_viewer_shortcode');

/**
 * Add 3D model viewer tab in product single page
 */
function woobox_add_3d_model_tab($tabs) {
    global $product;
    
    $model_file = get_post_meta($product->get_id(), 'woobox_3d_model_file', true);
    
    if (!empty($model_file)) {
        $tabs['3d_model'] = array(
            'title'    => __('3D Model', 'woobox'),
            'priority' => 25,
            'callback' => 'woobox_3d_model_tab_content',
        );
    }
    
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'woobox_add_3d_model_tab');

/**
 * 3D Model tab content
 */
function woobox_3d_model_tab_content() {
    global $product;
    
    $model_file = get_post_meta($product->get_id(), 'woobox_3d_model_file', true);
    $model_poster = get_post_meta($product->get_id(), 'woobox_3d_model_poster', true);
    $model_autorotate = get_post_meta($product->get_id(), 'woobox_3d_model_autorotate', true) === 'yes';
    
    if (empty($model_file)) {
        echo '<p>' . __('No 3D model available for this product.', 'woobox') . '</p>';
        return;
    }
    
    $shortcode_atts = array(
        'src' => $model_file,
        'alt' => $product->get_name(),
        'autorotate' => $model_autorotate ? 'true' : 'false',
    );
    
    if (!empty($model_poster)) {
        $shortcode_atts['poster'] = $model_poster;
    }
    
    echo do_shortcode('[model_viewer ' . woobox_build_shortcode_attributes($shortcode_atts) . ']');
    
    // Add View Fullscreen button
    echo '<a href="' . esc_url(woobox_get_3d_model_viewer_url($product->get_id())) . '" target="_blank" class="view-3d-model-btn">';
    echo '<span class="dashicons dashicons-fullscreen"></span>' . __('View 3D Model Fullscreen', 'woobox');
    echo '</a>';
}

/**
 * Helper function to build shortcode attributes
 */
function woobox_build_shortcode_attributes($atts) {
    $shortcode_str = '';
    
    foreach ($atts as $key => $value) {
        $shortcode_str .= $key . '="' . esc_attr($value) . '" ';
    }
    
    return $shortcode_str;
}

/**
 * Add 3D model to product page (before add to cart button)
 */
function woobox_display_3d_model_preview() {
    global $product;
    
    $model_file = get_post_meta($product->get_id(), 'woobox_3d_model_file', true);
    
    if (!empty($model_file)) {
        $model_poster = get_post_meta($product->get_id(), 'woobox_3d_model_poster', true);
        $model_autorotate = get_post_meta($product->get_id(), 'woobox_3d_model_autorotate', true) === 'yes';
        
        echo '<div class="woobox-3d-model-preview">';
        echo '<h3>' . __('3D Model Preview', 'woobox') . '</h3>';
        
        $shortcode_atts = array(
            'src' => $model_file,
            'alt' => $product->get_name(),
            'autorotate' => $model_autorotate ? 'true' : 'false',
            'height' => '300px',
        );
        
        if (!empty($model_poster)) {
            $shortcode_atts['poster'] = $model_poster;
        }
        
        echo do_shortcode('[model_viewer ' . woobox_build_shortcode_attributes($shortcode_atts) . ']');
        
        // Add View Fullscreen button
        echo '<a href="' . esc_url(woobox_get_3d_model_viewer_url($product->get_id())) . '" target="_blank" class="view-3d-model-btn">';
        echo '<span class="dashicons dashicons-fullscreen"></span>' . __('View 3D Model Fullscreen', 'woobox');
        echo '</a>';
        
        echo '</div>';
    }
}
add_action('woocommerce_single_product_summary', 'woobox_display_3d_model_preview', 25); 