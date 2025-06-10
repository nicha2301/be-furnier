<?php 
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();


$table_name = $wpdb->prefix . 'iqonic_wishlist_product'; // do not forget about tables prefix

$sql = "CREATE TABLE `{$wpdb->prefix}iqonic_wishlist_product` (
    ID bigint(20) NOT NULL AUTO_INCREMENT,    
    user_id bigint(20) UNSIGNED NOT NULL,
    pro_id bigint(20) UNSIGNED NOT NULL,
    wishlist_id bigint(20) UNSIGNED NOT NULL,    
    created_at datetime  NULL,
    
    PRIMARY KEY  (ID)
  ) $charset_collate;";
  //dbDelta($sql);

  maybe_create_table($table_name,$sql);

  $table_name = $wpdb->prefix . 'iqonic_add_to_cart'; // do not forget about tables prefix

  $sql = "CREATE TABLE `{$wpdb->prefix}iqonic_add_to_cart` (
    ID bigint(20) NOT NULL AUTO_INCREMENT,    
    user_id bigint(20) UNSIGNED NOT NULL,
    pro_id bigint(20) UNSIGNED NOT NULL,
    quantity bigint(20) UNSIGNED NOT NULL,
    color varchar(50)  NOT NULL,
    size varchar(50)  NOT NULL,   
    created_at datetime  NULL,  
    PRIMARY KEY  (ID)
  ) $charset_collate;";

maybe_create_table($table_name,$sql);
//dbDelta($sql);

$table_name = $wpdb->prefix . 'iqonic_multiple_address'; // do not forget about tables prefix

  $sql = "CREATE TABLE `{$wpdb->prefix}iqonic_multiple_address` (
    ID bigint(20) NOT NULL AUTO_INCREMENT,    
    user_id bigint(20) UNSIGNED NOT NULL,    
    first_name varchar(250)  NOT NULL,
    last_name varchar(250)  NOT NULL,   
    company varchar(250)  NOT NULL,   
    contact varchar(20)  NOT NULL,   
    address_1 varchar(250)  NOT NULL,   
    address_2 varchar(250)  NOT NULL,   
    state varchar(250)  NOT NULL,   
    city varchar(250)  NOT NULL,   
    country varchar(250)  NOT NULL,   
    postcode varchar(250)  NOT NULL,   
    created_at datetime  NULL,  
    PRIMARY KEY  (ID)
  ) $charset_collate;";

maybe_create_table($table_name,$sql);
  //dbDelta($sql);
?>