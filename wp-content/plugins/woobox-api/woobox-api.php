<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Woobox Api
 * Plugin URI:        https://iqonic.design/
 * Description:       Plugin Use For Custom Woccommerce Api Like Cart , Wishlist, Filter Product , Get Category
 * Version:           3.0.0
 * Author:            Iqonic Design
 * Author URI:        https://iqonic.design/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woobox
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
use Includes\baseClasses\WBActivate;
use Includes\baseClasses\WBDeactivate;

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOOBOX_API_VERSION', '1.1.0' );

defined( 'ABSPATH' ) or die( 'Something went wrong' );

// Require once the Composer Autoload
if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
} else {
	die( 'Something went wrong' );
}

if (!defined('WOOBOX_API_DIR'))
{
	define('WOOBOX_API_DIR', plugin_dir_path(__FILE__));
}

if (!defined('WOOBOX_API_DIR_URI'))
{
	define('WOOBOX_API_DIR_URI', plugin_dir_url(__FILE__));
}


if (!defined('WOOBOX_API_NAMESPACE'))
{
	define('WOOBOX_API_NAMESPACE', "woobox-api");
}

if (!defined('WOOBOX_API_PREFIX'))
{
	define('WOOBOX_API_PREFIX', "wb_");
}


if (!defined('JWT_AUTH_SECRET_KEY')){
	define('JWT_AUTH_SECRET_KEY', 'your-top-secrect-key');
}

if (!defined('JWT_AUTH_CORS_ENABLE')){
	define('JWT_AUTH_CORS_ENABLE', true);
}
if (!class_exists('ReduxFramework'))
{
}
include( WOOBOX_API_DIR . 'app-option/option-set.php' );

include( WOOBOX_API_DIR . 'includes/custom-filed_wc/woobox_custom_filed_wc.php' );
include( WOOBOX_API_DIR . 'includes/custom-filed_wc/woobox_3d_model_field.php' );
include( WOOBOX_API_DIR . 'includes/custom-filed_wc/woobox_3d_model_viewer.php' );
include( WOOBOX_API_DIR . 'includes/notification/class.sendnotification.php' );

require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );
/**
 * The code that runs during plugin activation
 */
register_activation_hook( __FILE__, [ WBActivate::class, 'activate'] );

/**
 * The code that runs during plugin deactivation
 */
register_deactivation_hook( __FILE__, [WBDeactivate::class, 'init'] );


( new WBActivate )->init();