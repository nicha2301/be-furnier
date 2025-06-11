<?php
/**
 * Custom field for 3D models in WooCommerce Products
 * 
 * @package woobox-api
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Add support for 3D model file types to WordPress media library
 */
function woobox_add_3d_mime_types($mimes) {
    $mimes['glb'] = 'model/gltf-binary';
    return $mimes;
}
add_filter('upload_mimes', 'woobox_add_3d_mime_types');

/**
 * Allow uploading 3D model files by bypassing WordPress MIME type check
 */
function woobox_bypass_mime_check_for_3d_models($data, $file, $filename, $mimes, $real_mime = null) {
    if (!empty($data['ext']) && !empty($data['type'])) {
        return $data;
    }

    $wp_filetype = wp_check_filetype($filename, $mimes);
    $ext = $wp_filetype['ext'];
    $type = $wp_filetype['type'];

    // Handle 3D model file formats
    if (!$ext) {
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($file_ext === 'glb') {
            $ext = 'glb';
            $type = 'model/gltf-binary';
        }
    }

    if ($ext) {
        $data['ext'] = $ext;
        $data['type'] = $type;
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'woobox_bypass_mime_check_for_3d_models', 10, 5);

/**
 * Add admin menu for 3D Model Management
 */
function woobox_add_3d_model_admin_menu() {
    add_menu_page(
        __('3D Models', 'woobox'),
        __('3D Models', 'woobox'),
        'manage_options',
        'woobox-3d-models',
        'woobox_3d_models_page',
        'dashicons-format-image',
        56
    );
}
add_action('admin_menu', 'woobox_add_3d_model_admin_menu');

/**
 * 3D Models admin page
 */
function woobox_3d_models_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('3D Models Management', 'woobox'); ?></h1>
        
        <p><?php echo esc_html__('Use this page to manage 3D models for your WooCommerce products.', 'woobox'); ?></p>
        
        <div class="card">
            <h2><?php echo esc_html__('Upload 3D Model', 'woobox'); ?></h2>
            <p><?php echo esc_html__('Upload your 3D model files (GLB format) to use with your products.', 'woobox'); ?></p>
            <p>
                <a href="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'upload-3d-model.php'); ?>" class="button button-primary" target="_blank">
                    <?php echo esc_html__('Upload Model', 'woobox'); ?>
                </a>
            </p>
        </div>
        
        <div class="card">
            <h2><?php echo esc_html__('Products with 3D Models', 'woobox'); ?></h2>
            <?php
            // Query products with 3D models
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'woobox_3d_model_file',
                        'value' => '',
                        'compare' => '!=',
                    ),
                ),
            );
            $products_query = new WP_Query($args);
            
            if ($products_query->have_posts()) :
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Product', 'woobox'); ?></th>
                            <th><?php echo esc_html__('3D Model', 'woobox'); ?></th>
                            <th><?php echo esc_html__('Actions', 'woobox'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($products_query->have_posts()) : $products_query->the_post(); 
                        $product_id = get_the_ID();
                        $model_file = get_post_meta($product_id, 'woobox_3d_model_file', true);
                        $model_filename = basename($model_file);
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($product_id)); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($model_filename); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit#woobox_3d_model')); ?>" class="button button-small">
                                    <?php echo esc_html__('Edit', 'woobox'); ?>
                                </a>
                                <a href="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'view-3d-model.php?model_id=' . $product_id); ?>" class="button button-small" target="_blank">
                                    <?php echo esc_html__('View Model', 'woobox'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php echo esc_html__('No products with 3D models found.', 'woobox'); ?></p>
            <?php
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </div>
    <?php
}

/**
 * Add 3D Model tab to WooCommerce product data tabs
 */
add_filter('woocommerce_product_data_tabs', function($tabs) {
    $tabs['woobox_3d_model'] = [
        'label' => __('3D Models', 'woobox'),
        'target' => 'woobox_3d_model_data',
        'priority' => 26
    ];
    return $tabs;
});

/**
 * Add 3D Model panel to WooCommerce product data panels
 */
add_action('woocommerce_product_data_panels', function() {
    ?>
    <div id="woobox_3d_model_data" class="panel woocommerce_options_panel hidden">
        <?php
            global $woocommerce, $post;
            
            echo '<div class="options_group">';
            
            // 3D Model File Upload Field
            $model_file = get_post_meta($post->ID, 'woobox_3d_model_file', true);
            ?>
            <p class="form-field">
                <label for="woobox_3d_model_file"><?php esc_html_e('3D Model File', 'woobox'); ?></label>
                <input type="text" class="short" style="width: 80%;" name="woobox_3d_model_file" id="woobox_3d_model_file" value="<?php echo esc_attr($model_file); ?>" />
                <button type="button" class="button woobox_upload_3d_model_button"><?php esc_html_e('Upload', 'woobox'); ?></button>
                <span class="description"><?php esc_html_e('Upload a 3D model file (supported format: .glb)', 'woobox'); ?></span>
            </p>

            <?php if (!empty($model_file)) : ?>
                <p class="form-field">
                    <label><?php esc_html_e('Preview', 'woobox'); ?></label>
                    <a href="<?php echo esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'view-3d-model.php?model_id=' . $post->ID); ?>" class="button" target="_blank">
                        <?php esc_html_e('View 3D Model', 'woobox'); ?>
                    </a>
                </p>
            <?php endif; ?>

            <?php
            // 3D Model Options
            woocommerce_wp_checkbox([
                'id'            => 'woobox_3d_model_autorotate',
                'wrapper_class' => '',
                'label'         => __('Auto-rotate Model', 'woobox'),
                'description'   => __('Enable auto-rotation for 3D model viewer', 'woobox')
            ]);
            
            woocommerce_wp_text_input([
                'id'            => 'woobox_3d_model_poster',
                'wrapper_class' => '',
                'label'         => __('Poster Image URL', 'woobox'),
                'description'   => __('URL to an image used as a poster before the 3D model is loaded', 'woobox'),
                'desc_tip'      => true
            ]);

            woocommerce_wp_textarea_input([
                'id'            => 'woobox_3d_model_config',
                'wrapper_class' => '',
                'label'         => __('Additional Configuration', 'woobox'),
                'description'   => __('JSON format configuration options for 3D model viewer', 'woobox'),
                'desc_tip'      => true
            ]);
            
            echo '</div>';
        ?>
    </div>
    <script>
        jQuery(document).ready(function($){
            $('.woobox_upload_3d_model_button').click(function(e){
                e.preventDefault();
                
                var custom_uploader = wp.media({
                    title: '<?php esc_html_e('Select 3D Model File', 'woobox'); ?>',
                    button: {
                        text: '<?php esc_html_e('Use this file', 'woobox'); ?>'
                    },
                    multiple: false
                });

                custom_uploader.on('select', function() {
                    var attachment = custom_uploader.state().get('selection').first().toJSON();
                    $('#woobox_3d_model_file').val(attachment.url);
                });

                custom_uploader.open();
            });
        });
    </script>
<?php
});

/**
 * Save 3D model meta when product is saved
 */
add_action('woocommerce_process_product_meta', 'woobox_save_3d_model_fields');

function woobox_save_3d_model_fields($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    
    $post_type = get_post_type($post_id);
    if ('product' !== $post_type) return $post_id;

    // Save 3D model file URL
    if (isset($_POST['woobox_3d_model_file'])) {
        update_post_meta($post_id, 'woobox_3d_model_file', sanitize_text_field($_POST['woobox_3d_model_file']));
    } else {
        delete_post_meta($post_id, 'woobox_3d_model_file');
    }
    
    // Save auto-rotate option
    if (isset($_POST['woobox_3d_model_autorotate'])) {
        update_post_meta($post_id, 'woobox_3d_model_autorotate', 'yes');
    } else {
        update_post_meta($post_id, 'woobox_3d_model_autorotate', 'no');
    }
    
    // Save poster image
    if (isset($_POST['woobox_3d_model_poster'])) {
        update_post_meta($post_id, 'woobox_3d_model_poster', sanitize_url($_POST['woobox_3d_model_poster']));
    } else {
        delete_post_meta($post_id, 'woobox_3d_model_poster');
    }
    
    // Save additional configuration
    if (isset($_POST['woobox_3d_model_config'])) {
        update_post_meta($post_id, 'woobox_3d_model_config', sanitize_textarea_field($_POST['woobox_3d_model_config']));
    } else {
        delete_post_meta($post_id, 'woobox_3d_model_config');
    }
}