<?php
namespace Coderockz\Woo_Delivery\Block;

require_once CODEROCKZ_WOO_DELIVERY_DIR . 'includes/class-coderockz-woo-delivery-helper.php';
require_once CODEROCKZ_WOO_DELIVERY_DIR . 'includes/class-coderockz-woo-delivery-delivery-option.php';
require_once CODEROCKZ_WOO_DELIVERY_DIR . 'includes/class-coderockz-woo-delivery-time-option.php';
require_once CODEROCKZ_WOO_DELIVERY_DIR . 'includes/class-coderockz-woo-delivery-pickup-time-option.php';

class Coderockz_Woo_Delivery_Settings {
    private static $instance = null;
    private $helper;
    private $settings = [];

    private $delivery_date_settings;
    private $pickup_date_settings;
    private $delivery_time_settings;
    private $pickup_time_settings;
    private $delivery_option_settings;
    private $other_settings;
    private $localization_settings;

    private function __construct() {
        $this->helper = new \Coderockz_Woo_Delivery_Helper();
        $this->load_settings();
        $this->set_timezone();
        $this->settings['has_virtual_downloadable_products'] = $this->helper->check_virtual_downloadable_products();
        $this->set_other_settings();
        $this->set_delivery_dates();
        $this->set_delivery_times();
        $this->set_pickup_dates();
        $this->set_pickup_times();
        $this->set_disable_dates();
        $this->set_passed_dates();
        $this->set_localization();
    }

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

    private function load_settings() {
        $this->delivery_date_settings = get_option( 'coderockz_woo_delivery_date_settings' );
        $this->pickup_date_settings = get_option( 'coderockz_woo_delivery_pickup_date_settings' );
        $this->delivery_time_settings = get_option( 'coderockz_woo_delivery_time_settings' );
        $this->pickup_time_settings = get_option( 'coderockz_woo_delivery_pickup_settings' );
        $this->delivery_option_settings = get_option( 'coderockz_woo_delivery_option_delivery_settings' );
        $this->other_settings = get_option( 'coderockz_woo_delivery_other_settings' );
        $this->localization_settings = get_option( 'coderockz_woo_delivery_localization_settings' );
    }

    private function set_timezone() {
        $timezone = $this->helper->get_the_timezone();
        $this->settings['timezone'] = $timezone;
        date_default_timezone_set( $timezone );
        $this->settings['today'] = date( 'Y-m-d', time() );
    }

    private function set_other_settings() {
        $this->settings['delivery_heading_checkout'] = ( isset( $this->other_settings['delivery_heading_checkout'] ) && !empty( $this->other_settings['delivery_heading_checkout'] ) ) ? stripslashes( $this->other_settings['delivery_heading_checkout'] ) : __( "Delivery/Pickup options", "woo-delivery" );

        $this->settings['enable_delivery_option'] = ( isset( $this->delivery_option_settings['enable_option_time_pickup'] ) && !empty( $this->delivery_option_settings['enable_option_time_pickup'] ) ) ? $this->delivery_option_settings['enable_option_time_pickup'] : false;

        $this->settings['delivery_option_field_label'] = ( isset( $this->delivery_option_settings['delivery_option_label'] ) && !empty( $this->delivery_option_settings['delivery_option_label'] ) ) ? stripslashes( $this->delivery_option_settings['delivery_option_label'] ) : __( "Order Type", "woo-delivery" );

        $this->settings['delivery_options'] = \Coderockz_Woo_Delivery_Delivery_Option::delivery_option( $this->delivery_option_settings, "exclude_empty_option" );
        unset( $this->settings['delivery_options'][""] ); //double check to exclude empty option
    }

    private function set_delivery_dates() {
        $this->settings['enable_delivery_date'] = ( isset( $this->delivery_date_settings['enable_delivery_date'] ) && !empty( $this->delivery_date_settings['enable_delivery_date'] ) ) ? $this->delivery_date_settings['enable_delivery_date'] : false;

        $this->settings['delivery_date_selectable_days'] = ( isset( $this->delivery_date_settings['selectable_date'] ) && !empty( $this->delivery_date_settings['selectable_date'] ) ) ? $this->delivery_date_settings['selectable_date'] : 365;

        $delivery_days_str = isset( $this->delivery_date_settings['delivery_days'] ) && $this->delivery_date_settings['delivery_days'] != "" ? $this->delivery_date_settings['delivery_days'] : "6,0,1,2,3,4,5";
        $delivery_days = explode( ',', $delivery_days_str );
        $week_days = ['0', '1', '2', '3', '4', '5', '6'];
        $this->settings['disable_week_days'] = array_values( array_diff( $week_days, $delivery_days ) );

        $this->settings['delivery_date_field_label'] = ( isset( $this->delivery_date_settings['field_label'] ) && !empty( $this->delivery_date_settings['field_label'] ) ) ? $this->delivery_date_settings['field_label'] : __( "Delivery Date", "woo-delivery" );

        $this->settings['auto_select_first_date'] = ( isset( $this->delivery_date_settings['auto_select_first_date'] ) && !empty( $this->delivery_date_settings['auto_select_first_date'] ) ) ? $this->delivery_date_settings['auto_select_first_date'] : false;

        $this->settings['delivery_date_mandatory'] = ( isset( $this->delivery_date_settings['delivery_date_mandatory'] ) && !empty( $this->delivery_date_settings['delivery_date_mandatory'] ) ) ? $this->delivery_date_settings['delivery_date_mandatory'] : false;

        $this->settings['delivery_date_format'] = ( isset( $this->delivery_date_settings['date_format'] ) && !empty( $this->delivery_date_settings['date_format'] ) ) ? $this->delivery_date_settings['date_format'] : "F j, Y";

        $this->settings['week_starts_from'] = ( isset( $this->delivery_date_settings['week_starts_from'] ) && !empty( $this->delivery_date_settings['week_starts_from'] ) ) ? $this->delivery_date_settings['week_starts_from'] : "0";

        $this->settings['selectable_date'] = ( isset( $this->delivery_date_settings['selectable_date'] ) && !empty( $this->delivery_date_settings['selectable_date'] ) ) ? $this->delivery_date_settings['selectable_date'] : "365";
    }

    private function set_delivery_times() {
        $this->settings['enable_delivery_time'] = ( isset( $this->delivery_time_settings['enable_delivery_time'] ) && !empty( $this->delivery_time_settings['enable_delivery_time'] ) ) ? $this->delivery_time_settings['enable_delivery_time'] : false;

        $this->settings['delivery_time_starts'] = ( isset( $this->delivery_time_settings['delivery_time_starts'] ) && !empty( $this->delivery_time_settings['delivery_time_starts'] ) ) ? $this->delivery_time_settings['delivery_time_starts'] : "0";

        $this->settings['delivery_time_ends'] = ( isset( $this->delivery_time_settings['delivery_time_ends'] ) && !empty( $this->delivery_time_settings['delivery_time_ends'] ) ) ? $this->delivery_time_settings['delivery_time_ends'] : "1440";

        $this->settings['delivery_time_field_label'] = ( isset( $this->delivery_time_settings['field_label'] ) && !empty( $this->delivery_time_settings['field_label'] ) ) ? $this->delivery_time_settings['field_label'] : __( "Delivery Time", "woo-delivery" );

        $this->settings['delivery_time_mandatory'] = ( isset( $this->delivery_time_settings['delivery_time_mandatory'] ) && !empty( $this->delivery_time_settings['delivery_time_mandatory'] ) ) ? $this->delivery_time_settings['delivery_time_mandatory'] : false;

        $this->settings['auto_select_first_time'] = ( isset( $this->delivery_time_settings['auto_select_first_time'] ) && !empty( $this->delivery_time_settings['auto_select_first_time'] ) ) ? $this->delivery_time_settings['auto_select_first_time'] : false;

        $this->settings['disabled_current_time_slot'] = ( isset( $this->delivery_time_settings['disabled_current_time_slot'] ) && !empty( $this->delivery_time_settings['disabled_current_time_slot'] ) ) ? $this->delivery_time_settings['disabled_current_time_slot'] : false;

        $this->settings['max_order_per_slot'] = ( isset( $this->delivery_time_settings['max_order_per_slot'] ) && !empty( $this->delivery_time_settings['max_order_per_slot'] ) ) ? $this->delivery_time_settings['max_order_per_slot'] : 10000000000000;

        $this->settings['delivery_time_options'] = \Coderockz_Woo_Delivery_Time_Option::delivery_time_option( $this->delivery_time_settings, "exclude_empty_option" );
        unset( $this->settings['delivery_time_options'][""] ); //double check to exclude empty option
    }

    private function set_pickup_dates() {
        $this->settings['enable_pickup_date'] = ( isset( $this->pickup_date_settings['enable_pickup_date'] ) && !empty( $this->pickup_date_settings['enable_pickup_date'] ) ) ? $this->pickup_date_settings['enable_pickup_date'] : false;

        $this->settings['pickup_date_selectable_days'] = ( isset( $this->pickup_date_settings['selectable_date'] ) && !empty( $this->pickup_date_settings['selectable_date'] ) ) ? $this->pickup_date_settings['selectable_date'] : 365;

        $pickup_days_str = isset( $this->pickup_date_settings['pickup_days'] ) && $this->pickup_date_settings['pickup_days'] != "" ? $this->pickup_date_settings['pickup_days'] : "6,0,1,2,3,4,5";
        $pickup_days = explode( ',', $pickup_days_str );
        $week_days = ['0', '1', '2', '3', '4', '5', '6'];
        $this->settings['pickup_disable_week_days'] = array_values( array_diff( $week_days, $pickup_days ) );

        $this->settings['pickup_date_field_label'] = ( isset( $this->pickup_date_settings['pickup_field_label'] ) && !empty( $this->pickup_date_settings['pickup_field_label'] ) ) ? stripslashes( $this->pickup_date_settings['pickup_field_label'] ) : __( "Pickup Date", "woo-delivery" );

        $this->settings['pickup_auto_select_first_date'] = ( isset( $this->pickup_date_settings['auto_select_first_pickup_date'] ) && !empty( $this->pickup_date_settings['auto_select_first_pickup_date'] ) ) ? $this->pickup_date_settings['auto_select_first_pickup_date'] : false;

        $this->settings['pickup_date_mandatory'] = ( isset( $this->pickup_date_settings['pickup_date_mandatory'] ) && !empty( $this->pickup_date_settings['pickup_date_mandatory'] ) ) ? $this->pickup_date_settings['pickup_date_mandatory'] : false;

        $this->settings['pickup_date_format'] = ( isset( $this->pickup_date_settings['date_format'] ) && !empty( $this->pickup_date_settings['date_format'] ) ) ? $this->pickup_date_settings['date_format'] : "F j, Y";

        $this->settings['pickup_week_starts_from'] = ( isset( $this->pickup_date_settings['week_starts_from'] ) && !empty( $this->pickup_date_settings['week_starts_from'] ) ) ? $this->pickup_date_settings['week_starts_from'] : "0";

        $this->settings['pickup_selectable_date'] = ( isset( $this->pickup_date_settings['selectable_date'] ) && !empty( $this->pickup_date_settings['selectable_date'] ) ) ? $this->pickup_date_settings['selectable_date'] : "365";
    }

    private function set_pickup_times() {
        $this->settings['enable_pickup_time'] = ( isset( $this->pickup_time_settings['enable_pickup_time'] ) && !empty( $this->pickup_time_settings['enable_pickup_time'] ) ) ? $this->pickup_time_settings['enable_pickup_time'] : false;

        $this->settings['pickup_time_starts'] = ( isset( $this->pickup_time_settings['pickup_time_starts'] ) && !empty( $this->pickup_time_settings['pickup_time_starts'] ) ) ? $this->pickup_time_settings['pickup_time_starts'] : "0";

        $this->settings['pickup_time_ends'] = ( isset( $this->pickup_time_settings['pickup_time_ends'] ) && !empty( $this->pickup_time_settings['pickup_time_ends'] ) ) ? $this->pickup_time_settings['pickup_time_ends'] : "1440";

        $this->settings['pickup_time_field_label'] = ( isset( $this->pickup_time_settings['field_label'] ) && !empty( $this->pickup_time_settings['field_label'] ) ) ? stripslashes( $this->pickup_time_settings['field_label'] ) : __( "Pickup Time", "woo-delivery" );

        $this->settings['pickup_time_mandatory'] = ( isset( $this->pickup_time_settings['pickup_time_mandatory'] ) && !empty( $this->pickup_time_settings['pickup_time_mandatory'] ) ) ? $this->pickup_time_settings['pickup_time_mandatory'] : false;

        $this->settings['pickup_auto_select_first_time'] = ( isset( $this->pickup_time_settings['auto_select_first_time'] ) && !empty( $this->pickup_time_settings['auto_select_first_time'] ) ) ? $this->pickup_time_settings['auto_select_first_time'] : false;

        $this->settings['pickup_disabled_current_time_slot'] = ( isset( $this->pickup_time_settings['disabled_current_pickup_time_slot'] ) && !empty( $this->pickup_time_settings['disabled_current_pickup_time_slot'] ) ) ? $this->pickup_time_settings['disabled_current_pickup_time_slot'] : false;

        $this->settings['pickup_max_order_per_slot'] = ( isset( $this->pickup_time_settings['max_pickup_per_slot'] ) && !empty( $this->pickup_time_settings['max_pickup_per_slot'] ) ) ? $this->pickup_time_settings['max_pickup_per_slot'] : 10000000000000;

        $this->settings['pickup_time_options'] = \Coderockz_Woo_Delivery_Pickup_Option::pickup_time_option( $this->pickup_time_settings, "exclude_empty_option" );
        unset( $this->settings['pickup_time_options'][""] ); //double check to exclude empty option
    }

    private function set_disable_dates() {
        $off_days = ( isset( $this->delivery_date_settings['off_days'] ) && !empty( $this->delivery_date_settings['off_days'] ) ) ? $this->delivery_date_settings['off_days'] : array();

        $this->settings['disable_dates'] = [];
        $this->settings['pickup_disable_dates'] = [];

        if ( count( $off_days ) ) {
            foreach ( $off_days as $year => $months ) {
                foreach ( $months as $month => $days ) {
                    $month_num = date_parse( $month )['month'];
                    if ( strlen( $month_num ) == 1 ) {
                        $month_num_final = "0" . $month_num;
                    } else {
                        $month_num_final = $month_num;
                    }
                    $days = explode( ',', $days );
                    foreach ( $days as $day ) {
                        $this->settings['disable_dates'][] = $year . "-" . $month_num_final . "-" . $day;
                        $this->settings['pickup_disable_dates'][] = $year . "-" . $month_num_final . "-" . $day;
                    }
                }
            }
        }
        $this->settings['disable_dates'] = array_unique( $this->settings['disable_dates'] );
        $this->settings['disable_dates'] = array_values( $this->settings['disable_dates'] );

        $this->settings['pickup_disable_dates'] = array_unique( $this->settings['pickup_disable_dates'] );
        $this->settings['pickup_disable_dates'] = array_values( $this->settings['pickup_disable_dates'] );
    }

    private function set_passed_dates() {
        $date_time_obj = new \DateTime();
        $current_time = ( $date_time_obj->format( "G" ) * 60 ) + $date_time_obj->format( "i" );
        $current_date = $date_time_obj->format( "Y-m-d" );

        $this->settings['disable_delivery_date_passed_time'] = [];
        $this->settings['disable_pickup_date_passed_time'] = [];

        if ( $this->settings['enable_delivery_time'] ) {
            $time_slot_end = [0];
            $time_slot_end[] = (int) $this->settings['delivery_time_ends'];
            $highest_timeslot_end = max( $time_slot_end );

            if ( $current_time > $highest_timeslot_end ) {
                $this->settings['disable_delivery_date_passed_time'][] = $current_date;
            }
            if ( $this->settings['disabled_current_time_slot'] ) {
                $delivery_time_options = $this->settings['delivery_time_options'];
                end( $delivery_time_options );
                $last_time_slot_str = key( $delivery_time_options );
                $last_time_slot = explode( " - ", $last_time_slot_str );
                $start_time_of_last_slot = explode( ":", $last_time_slot[0] );
                $start_time_of_last_slot_in_minutes = intval( $start_time_of_last_slot[0] ) * 60 + intval( $start_time_of_last_slot[1] );

                if ( $current_time > $start_time_of_last_slot_in_minutes ) {
                    $this->settings['disable_delivery_date_passed_time'][] = $current_date;
                }
            }
        }

        if ( $this->settings['enable_pickup_time'] ) {
            $time_slot_end = [0];
            $time_slot_end[] = (int) $this->settings['pickup_time_ends'];
            $highest_timeslot_end = max( $time_slot_end );

            if ( $current_time > $highest_timeslot_end ) {
                $this->settings['disable_pickup_date_passed_time'][] = $current_date;
            }
            if ( $this->settings['pickup_disabled_current_time_slot'] ) {
                $pickup_time_options = $this->settings['pickup_time_options'];
                end( $pickup_time_options );
                $last_time_slot_str = key( $pickup_time_options );
                $last_time_slot = explode( " - ", $last_time_slot_str );
                $start_time_of_last_slot = explode( ":", $last_time_slot[0] );
                $start_time_of_last_slot_in_minutes = intval( $start_time_of_last_slot[0] ) * 60 + intval( $start_time_of_last_slot[1] );
                if ( $current_time > $start_time_of_last_slot_in_minutes ) {
                    $this->settings['disable_pickup_date_passed_time'][] = $current_date;
                }
            }
        }
    }

    private function set_localization() {
        $this->settings['order_limit_notice'] = ( isset( $this->localization_settings['order_limit_notice'] ) && !empty( $this->localization_settings['order_limit_notice'] ) ) ? "(" . $this->localization_settings['order_limit_notice'] . ")" : __( "(Maximum delivery limit exceed)", "woo-delivery" );

        $this->settings['pickup_limit_notice'] = ( isset( $this->localization_settings['pickup_limit_notice'] ) && !empty( $this->localization_settings['pickup_limit_notice'] ) ) ? "(" . $this->localization_settings['pickup_limit_notice'] . ")" : __( "(Maximum pickup limit exceed)", "woo-delivery" );

        $this->settings['checkout_delivery_option_notice'] = ( isset( $this->localization_settings['checkout_delivery_option_notice'] ) && !empty( $this->localization_settings['checkout_delivery_option_notice'] ) ) ? stripslashes( $this->localization_settings['checkout_delivery_option_notice'] ) : __( "Please select order type", "woo-delivery" );

        $this->settings['checkout_date_notice'] = ( isset( $this->localization_settings['checkout_date_notice'] ) && !empty( $this->localization_settings['checkout_date_notice'] ) ) ? stripslashes( $this->localization_settings['checkout_date_notice'] ) : __( "Please enter delivery date", "woo-delivery" );

        $this->settings['checkout_pickup_date_notice'] = ( isset( $this->localization_settings['checkout_pickup_date_notice'] ) && !empty( $this->localization_settings['checkout_pickup_date_notice'] ) ) ? stripslashes( $this->localization_settings['checkout_pickup_date_notice'] ) : __( "Please enter pickup date", "woo-delivery" );

        $this->settings['checkout_time_notice'] = ( isset( $this->localization_settings['checkout_time_notice'] ) && !empty( $this->localization_settings['checkout_time_notice'] ) ) ? stripslashes( $this->localization_settings['checkout_time_notice'] ) : __( "Please select delivery time", "woo-delivery" );

        $this->settings['checkout_pickup_time_notice'] = ( isset( $this->localization_settings['checkout_pickup_time_notice'] ) && !empty( $this->localization_settings['checkout_pickup_time_notice'] ) ) ? stripslashes( $this->localization_settings['checkout_pickup_time_notice'] ) : __( "Please select pickup time", "woo-delivery" );

        $this->settings['select_order_type_text'] = __( "Select order type", "woo-delivery" );
        $this->settings['select_delivery_time_text'] = __( "Select delivery time", "woo-delivery" );
        $this->settings['select_pickup_time_text'] = __( "Select pickup time", "woo-delivery" );
    }

    public function get_options( $key ) {
        return isset( $this->$key ) ? $this->$key : null;
    }

    public function get_setting( $key ) {
        return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
    }

    public function get_settings() {
        return $this->settings;
    }
}
