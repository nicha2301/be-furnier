<?php
global $app_opt_name;
$woobox_option = get_option('woobox_app_options');
if(isset($woobox_option['woobox_notification_switch']))
{
    if($woobox_option['woobox_notification_switch'] == "1")
    {
        add_action( 'draft_to_publish', 'my_product_update' );
    }
}
    

function my_product_update( $post ) {
    global $app_opt_name;
    $woobox_option = get_option('woobox_app_options');

    if ( $post->post_type == "product" ) {
         $productId = $post->ID;
         $product = wc_get_product( $productId );
         $array['name'] = $product->get_name();

         $headings      = array(
            "en" => 'New Product Added'
        );
        $content = array(
            "en" => $array['name']
        );

        $hashes_array = array();
        if(!empty($woobox_option['notification_icon']['url']))
        {
            $icon = $woobox_option['notification_icon']['url'];
        }
        else 
        {
            $icon = "http://i.imgur.com/N8SN8ZS.png";
        }

        array_push($hashes_array, array(
            "id" => "like-button",
            "text" => "Like",
            "icon" => '',
            "url" => "https://yoursite.com"
        ));

        $fields = array(
            'app_id' => $woobox_option['one_app_id'],
            'included_segments' => array(
                'All'
            ),
            'data' => array(
                "foo" => "bar",
                "product_id" => $productId
            ),
            'headings' => $headings,
            'contents' => $content,
            'web_buttons' => $hashes_array
        );


        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Basic '.$woobox_option['one_rest_api_key']
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        // print_r($response);
        // print_r($woobox_option['one_rest_api_key']);
        // print_r($woobox_option['one_app_id']);
        // die;
    }
}
?>
