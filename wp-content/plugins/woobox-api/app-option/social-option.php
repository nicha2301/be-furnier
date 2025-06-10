<?php
/*
 * key Options
 */
$app_opt_name;

Redux::setSection( $app_opt_name, array(
    'title' => esc_html__( 'Social Links', 'woobox' ),
    'id'    => 'social_link',
    'icon'  => 'el el-link',
    'subsection' => false,
    'desc'  => esc_html__( '', 'woobox' ),
    'fields'           => array(
        array(
            'id'        => 'whatsapp',
            'type'      => 'text',
            'title'     => esc_html__( 'WhatsApp','woobox'),  
            'desc'      => __('<b>Please Enter Number With Country Code</b><p><i>For e.g. <b>919876543210</b></i></p><p><b>91</b> Is Country Code</p>'),         
            'validate' => 'numeric'
        ),

        array(
            'id'        => 'facebook',
            'type'      => 'text',
            'title'     => esc_html__( 'Facebook','woobox'),       
            'validate' => 'url'    
            
        ),

        array(
            'id'        => 'twitter',
            'type'      => 'text',
            'title'     => esc_html__( 'Twitter','woobox'),   
            'validate' => 'url'        
        ),

        array(
            'id'        => 'instagram',
            'type'      => 'text',
            'title'     => esc_html__( 'Instagram','woobox'),  
            'validate' => 'url'          
        ),

        array(
            'id'        => 'contact',
            'type'      => 'text',
            'title'     => esc_html__( 'Customer Care Number','woobox'),    
            'validate' => 'numeric'        
        ),

        array(
            'id'        => 'privacy_policy',
            'type'      => 'text',
            'title'     => esc_html__( 'Privacy Policy Url','woobox'),  
            'validate' => 'url'          
        ),

        array(
            'id'        => 'term_condition',
            'type'      => 'text',
            'title'     => esc_html__( 'Term & Condition Url','woobox'),  
            'validate' => 'url'          
        ),

        array(
            'id'        => 'copyright_text',
            'type'      => 'textarea',
            'title'     => esc_html__( 'Copyright Text','woobox'), 
        ),    
    )

));