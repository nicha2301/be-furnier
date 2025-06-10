<?php
namespace Coderockz\Woo_Delivery\Block;

class Coderockz_Woo_Delivery_Block_Storage {
    protected static $instance = null;

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
        add_action( 'woocommerce_store_api_checkout_update_order_from_request', [$this, 'update_block_order_meta'], 10, 2 );
    }

    function update_block_order_meta( $order, $request ) {
        $extensions = $request->get_param( 'extensions' );
        $data = $extensions['coderockz-woo-delivery'] ?? [];
        $order_id = $order->get_id();
        $hpos = false;
        if ( class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
            $hpos = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }

        require_once 'class-coderockz-woo-delivery-settings.php';
        $settings = Coderockz_Woo_Delivery_Settings::get_instance()->get_settings();

        require_once CODEROCKZ_WOO_DELIVERY_DIR . 'includes/class-coderockz-woo-delivery-helper.php';
        $helper = new \Coderockz_Woo_Delivery_Helper();

        if ( $helper->check_virtual_downloadable_products() || empty( $data ) ) {
            return;
        }

        $errors = new \WP_Error();

        self::validation( $data, $settings, $errors );

        if ( !empty( $data['delivery_time'] ) && $settings['enable_delivery_time'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'delivery' ) ) {
            self::check_time_validity( $data['delivery_date'], $data['delivery_time'], 'delivery', $settings, $hpos, $errors );
        }
        if ( !empty( $data['pickup_time'] ) && $settings['enable_pickup_time'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'pickup' ) ) {
            self::check_time_validity( $data['pickup_date'], $data['pickup_time'], 'pickup', $settings, $hpos, $errors );
        }

        if ( $errors->has_errors() ) {
            $error_messages = $errors->get_error_messages();
            $combined_error_message = implode( "<br>", $error_messages );
            throw new \WC_Data_Exception( 'CODEROCKZ_WOO_ERROR', $combined_error_message );
        }

        if ( $settings['enable_delivery_option'] && !empty( $data['order_type'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'delivery_type', sanitize_text_field( $data['order_type'] ) );
            } else {
                update_post_meta( $order_id, 'delivery_type', sanitize_text_field( $data['order_type'] ) );
            }
        } elseif ( !$settings['enable_delivery_option'] && ( ( $settings['enable_delivery_time'] && !$settings['enable_pickup_time'] ) || ( $settings['enable_delivery_date'] && !$settings['enable_pickup_date'] ) ) && !empty( $data['delivery_time'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'delivery_type', 'delivery' );
            } else {
                update_post_meta( $order_id, 'delivery_type', 'delivery' );
            }
        } elseif ( !$settings['enable_delivery_option'] && ( ( !$settings['enable_delivery_time'] && $settings['enable_pickup_time'] ) || ( !$settings['enable_delivery_date'] && $settings['enable_pickup_date'] ) ) && !empty( $data['pickup_time'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'delivery_type', 'pickup' );
            } else {
                update_post_meta( $order_id, 'delivery_type', 'pickup' );
            }
        }

        if ( $settings['enable_delivery_date'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'delivery' ) && !empty( $data['delivery_date'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'delivery_date', sanitize_text_field( $data['delivery_date'] ) );
            } else {
                update_post_meta( $order_id, 'delivery_date', sanitize_text_field( $data['delivery_date'] ) );
            }
        }

        if ( $settings['enable_delivery_time'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'delivery' ) && !empty( $data['delivery_time'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'delivery_time', sanitize_text_field( $data['delivery_time'] ) );
            } else {
                update_post_meta( $order_id, 'delivery_time', sanitize_text_field( $data['delivery_time'] ) );
            }
        }

        if ( $settings['enable_pickup_date'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'pickup' ) && !empty( $data['pickup_date'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'pickup_date', sanitize_text_field( $data['pickup_date'] ) );
            } else {
                update_post_meta( $order_id, 'pickup_date', sanitize_text_field( $data['pickup_date'] ) );
            }
        }

        if ( $settings['enable_pickup_time'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'pickup' ) && !empty( $data['pickup_time'] ) ) {
            if ( $hpos ) {
                $order->update_meta_data( 'pickup_time', sanitize_text_field( $data['pickup_time'] ) );
            } else {
                update_post_meta( $order_id, 'pickup_time', sanitize_text_field( $data['pickup_time'] ) );
            }
        }

        $order->save();

        self::reset_session();
    }

    static function validation( $data, $settings, $errors ) {

        if ( $settings['enable_delivery_option'] && empty( $data['order_type'] ) ) {
            $errors->add( 'error', $settings['checkout_delivery_option_notice'] );
        }

        if ( $settings['enable_delivery_date'] && $settings['delivery_date_mandatory'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'delivery' ) && empty( $data['delivery_date'] ) ) {
            $errors->add( 'error', $settings['checkout_date_notice'] );
        }

        if ( $settings['enable_delivery_time'] && $settings['delivery_time_mandatory'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'delivery' ) && empty( $data['delivery_time'] ) ) {
            $errors->add( 'error', $settings['checkout_time_notice'] );
        }

        if ( $settings['enable_pickup_date'] && $settings['pickup_date_mandatory'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'pickup' ) && empty( $data['pickup_date'] ) ) {
            $errors->add( 'error', $settings['checkout_pickup_date_notice'] );
        }

        if ( $settings['enable_pickup_time'] && $settings['pickup_time_mandatory'] && ( !$settings['enable_delivery_option'] || $data['order_type'] === 'pickup' ) && empty( $data['pickup_time'] ) ) {
            $errors->add( 'error', $settings['checkout_pickup_time_notice'] );
        }
    }

    static function check_time_validity( $date, $time, $type, $settings, $hpos, $errors ) {
        date_default_timezone_set( $settings['timezone'] );
        $selected_date = empty( $date ) ? $settings['today'] : $date;
        $selected_date = date( 'Y-m-d', strtotime( $selected_date ) );
        $date_key = $type === 'delivery' ? 'delivery_date' : 'pickup_date';
        $time_key = $type === 'delivery' ? 'delivery_time' : 'pickup_time';
        $max_order_per_slot = $type === 'delivery' ? $settings['max_order_per_slot'] : $settings['pickup_max_order_per_slot'];

        $time_arr = explode( ' - ', $time );
        $last_time_arr = explode( ':', $time_arr[1] );
        $last_time_in_minutes = (int) $last_time_arr[0] * 60 + (int) $last_time_arr[1];

        $date_time_obj = new \DateTime( 'now', new \DateTimeZone( $settings['timezone'] ) );
        $current_time_in_minutes = ( $date_time_obj->format( "G" ) * 60 ) + $date_time_obj->format( "i" );

        if ( $selected_date === $settings['today'] && $current_time_in_minutes >= $last_time_in_minutes ) {
            if($type == 'delivery') {
                $errors->add( 'error', __( 'Selected delivery time has already passed.', "woo-delivery" ) );
            } elseif($type == 'pickup') {
                $errors->add( 'error', __( 'Selected pickup time has already passed.', "woo-delivery" ) );
            }
            
            return;
        }

        if ( empty( $date ) ) {
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
                            'key'     => $time_key,
                            'value'   => $time,
                            'compare' => '=',
                        ),
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
                    $time_key          => $time,
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
                            'value'   => $selected_date,
                            'compare' => '=',
                        ),
                        array(
                            'key'     => $time_key,
                            'value'   => $time,
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'delivery_type',
                            'value'   => $type,
                            'compare' => '=',
                        ),
                    ),
                    'return'     => 'ids',
                );
            } else {
                $args = array(
                    'limit'         => -1,
                    $date_key       => $selected_date,
                    $time_key       => $time,
                    'delivery_type' => $type,
                    'return'        => 'ids',
                );
            }
        }
        $order_ids = wc_get_orders( $args );

        $times = [];
        foreach ( $order_ids as $order ) {
            $order_ref = wc_get_order( $order );
            if ( $hpos ) {
                $time_value = $order_ref->get_meta( $time_key, true );
            } else {
                $time_value = get_post_meta( $order, $time_key, true );
            }
            if ( isset( $time_value ) ) {
                $times[] = $time_value;
            }
        }

        $count_times = array_count_values( $times );

        if ( isset( $count_times[$time] ) && ( $count_times[$time] >= $max_order_per_slot ) && ( $max_order_per_slot > 0 ) ) {

            if($type == 'delivery') {
                $errors->add( 'error', __( 'Maximum order limit exceed for this delivery time slot.', "woo-delivery" ) );
            } elseif($type == 'pickup') {
                $errors->add( 'error', __( 'Maximum order limit exceed for this pickup time slot.', "woo-delivery" ) );
            }
        }
    }

    static function reset_session() {
        WC()->session->__unset( 'on_change' );
        WC()->session->__unset( 'order_type' );
        WC()->session->__unset( 'delivery_date' );
        WC()->session->__unset( 'delivery_time' );
        WC()->session->__unset( 'pickup_date' );
        WC()->session->__unset( 'pickup_time' );
    }

}

Coderockz_Woo_Delivery_Block_Storage::get_instance();