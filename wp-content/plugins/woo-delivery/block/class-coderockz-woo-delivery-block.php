<?php
namespace Coderockz\Woo_Delivery\Block;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

class Coderockz_Woo_Delivery_Block {
    protected static $instance = null;
    static $IDENTIFIER = 'coderockz_woo_delivery';

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception( "Cannot unserialize." );
    }

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [$this, 'register_woo_delivery_block'] );
        add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [$this, 'reset_session'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'register_block'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'add_data'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'extension_data_declaration'] );
        add_action( 'woocommerce_blocks_loaded', [$this, 'order_type_change_callback'] );
        add_action( 'wp_footer', [$this, 'localize_settings'] );
    }

    function reset_session() {
        WC()->session->set( 'on_change', false );
        WC()->session->set( 'order_type', NULL );
        WC()->session->set( 'delivery_date', NULL );
        WC()->session->set( 'delivery_time', NULL );
        WC()->session->set( 'pickup_date', NULL );
        WC()->session->set( 'pickup_time', NULL );
    }

    function register_woo_delivery_block() {
        register_block_type( 'coderockz-woo-delivery/delivery-block' );
    }

    function register_block() {
        require_once 'class-coderockz-woo-delivery-block-integration.php';
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ( $integration_registry ) {
                $integration_registry->register( new Coderockz_Woo_Delivery_Block_Integration() );
            }
        );
    }

    function add_data() {
        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'      => CartSchema::IDENTIFIER,
                'namespace'     => self::$IDENTIFIER,
                'data_callback' => [__CLASS__, 'data'],
                'schema_type'   => ARRAY_A,
            )
        );
    }

    static function data() {
        require_once 'class-coderockz-woo-delivery-settings.php';
        $data = Coderockz_Woo_Delivery_Settings::get_instance()->get_settings();

        if ( $data['has_virtual_downloadable_products'] ) {
            return $data;
        }

        $data['delivery_time_options'] = self::reset_time_options( $data['delivery_time_options'] );
        $data['pickup_time_options'] = self::reset_time_options( $data['pickup_time_options'] );

        $data['order_type'] = WC()->session->get( 'order_type' );

        $data['delivery_date'] = self::validate_and_set_date( $data, 'delivery', WC()->session->get( 'delivery_date' ) );
        $data['pickup_date'] = self::validate_and_set_date( $data, 'pickup', WC()->session->get( 'pickup_date' ) );

        if ( $data['delivery_date'] === $data['today'] || empty( $data['delivery_date'] ) ) {
            $data['delivery_time_options'] = self::disable_passed_and_current_time_options( $data, 'delivery' );
        }
        $data['delivery_time_options'] = self::disable_max_orders_time_options( $data, 'delivery' );
        $data['delivery_time'] = self::validate_and_set_time( $data, 'delivery', WC()->session->get( 'delivery_time' ) );

        if ( $data['pickup_date'] === $data['today'] || empty( $data['pickup_date'] ) ) {
            $data['pickup_time_options'] = self::disable_passed_and_current_time_options( $data, 'pickup' );
        }
        $data['pickup_time_options'] = self::disable_max_orders_time_options( $data, 'pickup' );
        $data['pickup_time'] = self::validate_and_set_time( $data, 'pickup', WC()->session->get( 'pickup_time' ) );

        return $data;
    }

    function extension_data_declaration() {
        woocommerce_store_api_register_endpoint_data(
            array(
                'endpoint'        => CheckoutSchema::IDENTIFIER,
                'namespace'       => 'coderockz-woo-delivery',
                'schema_type'     => ARRAY_A,
                'schema_callback' => [__CLASS__, 'data_structure'],
            )
        );
    }

    static function data_structure() {
        require_once 'class-coderockz-woo-delivery-settings.php';
        $settings = Coderockz_Woo_Delivery_Settings::get_instance()->get_settings();
        return array(
            'order_type'    => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Type of order', 'woo-delivery' ),
                'enum'        => array_merge( array_keys( $settings['delivery_options'] ), ["", null] ),
            ),
            'delivery_date' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Delivery Date', 'woo-delivery' ),
            ),
            'delivery_time' => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Delivery Time', 'woo-delivery' ),
            ),
            'pickup_date'   => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Pickup Date', 'woo-delivery' ),
            ),
            'pickup_time'   => array(
                'type'        => ['string', 'null'],
                'description' => __( 'Pickup Time', 'woo-delivery' ),
            ),
        );
    }

    function order_type_change_callback() {
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_order_type_change',
            'callback'  => [$this, 'order_type_change'],
        ] );
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_delivery_date_change',
            'callback'  => [$this, 'delivery_date_change'],
        ] );
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_delivery_time_change',
            'callback'  => [$this, 'delivery_time_change'],
        ] );
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_pickup_date_change',
            'callback'  => [$this, 'pickup_date_change'],
        ] );
        woocommerce_store_api_register_update_callback( [
            'namespace' => self::$IDENTIFIER . '_pickup_time_change',
            'callback'  => [$this, 'pickup_time_change'],
        ] );
    }

    function order_type_change( $data ) {
        $order_type = sanitize_text_field( $data['order_type'] );
        WC()->session->set( 'on_change', false );
        WC()->session->set( "order_type", $order_type );
    }

    function delivery_date_change( $data ) {
        $delivery_date = sanitize_text_field( $data['delivery_date'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "delivery_date", $delivery_date );
    }

    function delivery_time_change( $data ) {
        $delivery_time = sanitize_text_field( $data['delivery_time'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "delivery_time", $delivery_time );
    }

    function pickup_date_change( $data ) {
        $pickup_date = sanitize_text_field( $data['pickup_date'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "pickup_date", $pickup_date );
    }

    function pickup_time_change( $data ) {
        $pickup_time = sanitize_text_field( $data['pickup_time'] );
        WC()->session->set( 'on_change', true );
        WC()->session->set( "pickup_time", $pickup_time );
    }

    static function validate_and_set_date( $settings, $type, $selected ) {
        if ( $type === 'delivery' ) {
            $disable_week_days = $settings['disable_week_days'];
            $disable_dates = array_merge( $settings['disable_dates'], $settings['disable_delivery_date_passed_time'] );
            $enable_date = $settings['enable_delivery_date'];
            $auto_select_first_date = $settings['auto_select_first_date'];
            $session_name = 'delivery_date';
        } else {
            $disable_week_days = $settings['pickup_disable_week_days'];
            $disable_dates = array_merge( $settings['pickup_disable_dates'], $settings['disable_pickup_date_passed_time'] );
            $enable_date = $settings['enable_pickup_date'];
            $auto_select_first_date = $settings['pickup_auto_select_first_date'];
            $session_name = 'pickup_date';
        }
        $current = \DateTime::createFromFormat( 'Y-m-d', $settings['today'] );

        if ( !empty( $selected ) ) {
            $selected_date = \DateTime::createFromFormat( 'Y-m-d', $selected );
            if ( $selected_date >= $current && !in_array( $selected_date->format( 'w' ), $disable_week_days )
                && !in_array( $selected_date->format( 'Y-m-d' ), $disable_dates ) ) {
                return $selected_date->format( "Y-m-d" );
            }
        }

        $on_change = WC()->session->get( "on_change", false );

        if ( $enable_date && $auto_select_first_date && !$on_change ) {
            while (
                in_array( $current->format( 'w' ), $disable_week_days )
                || in_array( $current->format( 'Y-m-d' ), $disable_dates )
            ) {
                $current->modify( "+1 day" );
            }
            $formatted_date = $current->format( "Y-m-d" );
            WC()->session->set( $session_name, $formatted_date );
            return $formatted_date;
        }

        WC()->session->set( $session_name, NULL );
        return NULL;
    }

    static function reset_time_options( $old_time_options ) {
        $time_options = [];
        foreach ( $old_time_options as $key => $value ) {
            $time_options[$key] = [
                "title"    => is_array( $value ) ? $value['title'] : $value,
                "disabled" => false,
            ];
        }
        return $time_options;
    }

    static function disable_passed_and_current_time_options( $settings, $type ) {
        $date_time_obj = new \DateTime();
        $current_time_in_minutes = ( $date_time_obj->format( "G" ) * 60 ) + $date_time_obj->format( "i" );
        $time_options = $type === 'delivery' ? $settings["delivery_time_options"] : $settings["pickup_time_options"];

        foreach ( $time_options as $key => $data ) {
            $times_in_minutes = [];
            $times = explode( " - ", $key );
            $time_one = explode( ":", $times[0] );
            $time_two = explode( ":", $times[1] );

            $times_in_minutes[0] = intval( $time_one[0] ) * 60 + intval( $time_one[1] );
            $times_in_minutes[1] = intval( $time_two[0] ) * 60 + intval( $time_two[1] );

            if ( $times_in_minutes[0] <= $current_time_in_minutes && $times_in_minutes[1] <= $current_time_in_minutes ) {

                $time_options[$key]["disabled"] = true;

            } elseif ( $times_in_minutes[0] <= $current_time_in_minutes && $times_in_minutes[1] > $current_time_in_minutes ) {

                $disabled_current_time_slot = $type === 'delivery' ? $settings['disabled_current_time_slot'] : $settings['pickup_disabled_current_time_slot'];

                if ( $disabled_current_time_slot ) {
                    $time_options[$key]["disabled"] = true;
                }
            }
        }
        return $time_options;
    }

    static function get_order_times( $enable_date, $date, $hpos, $type ) {
        $date_key = $type === 'delivery' ? 'delivery_date' : 'pickup_date';
        $time_key = $type === 'delivery' ? 'delivery_time' : 'pickup_time';
        $date = date( "Y-m-d", strtotime( $date ) );

        if ( !$enable_date ) {
            $start_date = new \DateTime( $date . ' 00:00:00' );
            $start_date->setTimezone( new \DateTimeZone( 'UTC' ) );
            $end_date = new \DateTime( $date . ' 23:59:59' );
            $end_date->setTimezone( new \DateTimeZone( 'UTC' ) );
            $datetime_range = $start_date->getTimestamp() . '...' . $end_date->getTimestamp();
            if ( $hpos ) {
                $args = array(
                    'limit'            => -1,
                    'type'             => array( 'shop_order' ),
                    'date_created_gmt' => $datetime_range,
                    'meta_query'       => array(
                        array(
                            'key'     => 'delivery_type',
                            'value'   => $type,
                            'compare' => '=',
                        ),
                    ),
                    'return'           => 'ids',
                );
            } else {
                $args = array(
                    'limit'            => -1,
                    'date_created_gmt' => $datetime_range,
                    'delivery_type'    => $type,
                    'return'           => 'ids',
                );
            }
        } else {
            if ( $hpos ) {
                $args = array(
                    'limit'      => -1,
                    'type'       => array( 'shop_order' ),
                    'meta_query' => array(
                        array(
                            'key'     => $date_key,
                            'value'   => $date,
                            'compare' => '=',
                        ),
                    ),
                    'return'     => 'ids',
                );
            } else {
                $args = array(
                    'limit'   => -1,
                    $date_key => $date,
                    'return'  => 'ids',
                );
            }
        }
        $order_ids = wc_get_orders( $args );

        $times = [];
        foreach ( $order_ids as $order ) {
            $order_ref = wc_get_order( $order );
            if ( $hpos ) {
                $time = $order_ref->get_meta( $time_key, true );
            } else {
                $time = get_post_meta( $order, $time_key, true );
            }
            if ( isset( $time ) ) {
                $times[] = $time;
            }
        }
        return $times;
    }

    static function disable_max_orders_time_options( $settings, $type ) {
        $enable_date = $type === 'delivery' ? $settings['enable_delivery_date'] : $settings['enable_pickup_date'];
        $selected_date = $type === 'delivery' ? $settings['delivery_date'] : $settings['pickup_date'];
        $date = empty( $selected_date ) ? $settings['today'] : $selected_date;
        $hpos = false;

        if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
            $hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }

        $times = self::get_order_times( $enable_date, $date, $hpos, $type );
        $count_times = array_count_values( $times );
        $time_options = $type === 'delivery' ? $settings["delivery_time_options"] : $settings["pickup_time_options"];
        $max_order_per_slot = $type === 'delivery' ? $settings['max_order_per_slot'] : $settings['pickup_max_order_per_slot'];
        $max_limit_notice = $type === 'delivery' ? $settings['order_limit_notice'] : $settings['pickup_limit_notice'];

        foreach ( $time_options as $key => $data ) {
            if ( isset( $count_times[$key] ) && ( $count_times[$key] >= $max_order_per_slot ) && ( $max_order_per_slot > 0 ) ) {
                $time_options[$key]["title"] = $time_options[$key]["title"] . ' ' . $max_limit_notice;
                $time_options[$key]["disabled"] = true;
            }
        }
        return $time_options;
    }

    static function validate_and_set_time( $settings, $type, $selected ) {

        $time_options = $type === 'delivery' ? $settings["delivery_time_options"] : $settings["pickup_time_options"];
        $enable_time = $type === 'delivery' ? $settings['enable_delivery_time'] : $settings['enable_pickup_time'];
        $auto_select_first_time = $type === 'delivery' ? $settings['auto_select_first_time'] : $settings['pickup_auto_select_first_time'];
        $session_name = $type === 'delivery' ? 'delivery_time' : 'pickup_time';

        if ( !empty( $selected ) ) {
            if ( isset( $time_options[$selected]['disabled'] ) && !$time_options[$selected]['disabled'] ) {
                return $selected;
            }
        }

        $on_change = WC()->session->get( "on_change", false );

        if ( $enable_time && $auto_select_first_time && !$on_change ) {
            foreach ( $time_options as $key => $data ) {
                if ( !$data['disabled'] ) {
                    WC()->session->set( $session_name, $key );
                    return $key;
                }
            }
        }

        WC()->session->set( $session_name, NULL );
        return NULL;
    }

    function localize_settings() {
        $coderockz_other_settings = get_option( 'coderockz_woo_delivery_other_settings' );
        $woocommerce_ship_to_destination = get_option( 'woocommerce_ship_to_destination' );
        $needs_shipping = false;
        if ( WC()->cart ) {
            $needs_shipping = WC()->cart->needs_shipping();
        }

        $coderockz_block_field_position = ( isset( $coderockz_other_settings['block_field_position'] ) && !empty( $coderockz_other_settings['block_field_position'] ) ) ? $coderockz_other_settings['block_field_position'] : "contact-information";

        if ( $coderockz_block_field_position === 'contact-information' ) {
            $block_field_position = "woocommerce/checkout-contact-information-block";
        } else {
            if ( $woocommerce_ship_to_destination === 'billing_only' || !$needs_shipping || $coderockz_block_field_position === 'billing-address') {
                $block_field_position = "woocommerce/checkout-billing-address-block";
            } else {
                $block_field_position = "woocommerce/checkout-shipping-address-block";
            }
        }

        wp_localize_script( 'coderockz-woo-delivery-block', 'coderockz_woo_delivery_localize_settings',
            array(
                'block_field_position' => $block_field_position,
            )
        );
    }
}

Coderockz_Woo_Delivery_Block::get_instance();