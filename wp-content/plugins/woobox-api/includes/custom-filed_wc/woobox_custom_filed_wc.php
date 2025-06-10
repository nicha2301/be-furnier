<?php
//Product Cat creation page
function woobox_taxonomy_add_new_meta_field() {
    ?>
    <div class="form-field">
        <label for="term_meta[enable]"><?php _e('Enable', 'woobox'); ?></label>
        <input type="checkbox" value="check" name="term_meta[enable]" id="term_meta[enable]" checked>
        
    </div>
   
    <?php
}

add_action('product_cat_add_form_fields', 'woobox_taxonomy_add_new_meta_field', 10, 2);

//Product Cat Edit page
function woobox_taxonomy_edit_meta_field($term) {

    //getting term ID
     $term_id = $term->term_id;

    // retrieve the existing value(s) for this meta field. This returns an array
    $term_meta = get_option("enable_" . $term_id);
    
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="term_meta[enable]"><?php _e('Enable', 'woobox'); ?></label></th>
        <td>
            <?php
            
                if((isset($term_meta['enable']) && $term_meta['enable'] == 'check')) 
                {
                    $chk = 'checked';
                }
                else
                {
                    $chk = '';
                }
            ?>

            <input type="checkbox" value="check" name="term_meta[enable]" id="term_meta[enable]" <?php echo esc_attr( $chk ); ?>>
            
        </td>
    </tr>
   
    <?php
}

add_action('product_cat_edit_form_fields', 'woobox_taxonomy_edit_meta_field', 10, 2);

// Save extra taxonomy fields callback function.
function woobox_save_taxonomy_custom_meta($term_id) {
    $term_meta = array();
    if (isset($_POST['term_meta']) && !empty($_POST['term_meta'])) {
        $term_meta = get_option("enable_" . $term_id);
        $cat_keys = array_keys($_POST['term_meta']);
        foreach ($cat_keys as $key) {
            if (isset($_POST['term_meta'][$key])) {
                $term_meta[$key] = $_POST['term_meta'][$key];
            }
        }
        // Save the option array.

        
    }
    update_option("enable_" . $term_id, $term_meta);
}

add_action('edited_product_cat', 'woobox_save_taxonomy_custom_meta', 10, 2);
add_action('create_product_cat', 'woobox_save_taxonomy_custom_meta', 10, 2);

add_filter('woocommerce_product_data_tabs', function($tabs) {
	$tabs['additional_info'] = [
		'label' => __('Woobox Additional Information', 'woocommerce'),
		'target' => 'additional_product_data',
		'priority' => 25
	];
	return $tabs;
});

add_action('woocommerce_product_data_panels', function() {
	?>
    <div id="additional_product_data" class="panel woocommerce_options_panel hidden">
        <?php
            global $woocommerce, $post;
            echo '<div class="options_group">';
        
            woocommerce_wp_checkbox( array( 
                'id'            => 'woobox_deal_of_the_day', 
                'wrapper_class' => '', 
                'label'         => __('Deal Of The Day', 'woocommerce' )
                )
            );
            woocommerce_wp_checkbox( array( 
                'id'            => 'woobox_suggested_for_you', 
                'wrapper_class' => '', 
                'label'         => __('Suggested For You', 'woocommerce' ), 
                )
            );
            woocommerce_wp_checkbox( array( 
                'id'            => 'woobox_offer', 
                'wrapper_class' => '', 
                'label'         => __('Offers', 'woocommerce' )
                )
            );
            woocommerce_wp_checkbox( array(
                'id' => 'woobox_you_may_like',
                'wrapper_class' => '',
                'label' => __('You may Like', 'woocommerce' )
            ));
            
            echo '</div>';
        ?>
    </div>
<?php
});

add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );

function woo_add_custom_general_fields_save($post_id ){
	
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }
    $post_type = get_post_type($post_id);
    if ( 'product' !== $post_type ) return $post_id;

    if (isset($_POST['woobox_deal_of_the_day'])) {
        update_post_meta( $post_id, 'woobox_deal_of_the_day', $_POST['woobox_deal_of_the_day'] );
    } else {
        delete_post_meta( $post_id, 'woobox_deal_of_the_day' );
    }
    if (isset($_POST['woobox_suggested_for_you'])) {
        update_post_meta( $post_id, 'woobox_suggested_for_you', $_POST['woobox_suggested_for_you'] );
    } else {
        delete_post_meta( $post_id, 'woobox_suggested_for_you' );
    }
    if (isset($_POST['woobox_offer'])) {
        update_post_meta( $post_id, 'woobox_offer', $_POST['woobox_offer'] );
    } else {
        delete_post_meta( $post_id, 'woobox_offer' );
    }
    if (isset($_POST['woobox_you_may_like'])) {
        update_post_meta( $post_id, 'woobox_you_may_like', $_POST['woobox_you_may_like'] );
    } else {
        delete_post_meta( $post_id, 'woobox_you_may_like' );
    }
}
?>