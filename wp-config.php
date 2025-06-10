<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */
 
 // Enable WP_DEBUG mode
define('WP_DEBUG', true);

// Enable Debug logging to the /wp-content/debug.log file
define('WP_DEBUG_LOG', true);

// Disable display of errors and warnings
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
 
define( 'WP_HOME', 'http://192.168.1.166/wordpress' );
define( 'WP_SITEURL', 'http://192.168.1.166/wordpress' );

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ar_app' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'h[C;DUt&*d~{[1k>Q=Y!&7.1!i/x}{`MPc^jH^FfHV9y$@dgw&k|RJ)mt6t$skj7' );
define( 'SECURE_AUTH_KEY',  'Tb3[q puLD#N_Rr1n~5TUAHvB$NP3avw2-D1YW&a*XrmvEC;Ge~/{A2%]S|y3 (u' );
define( 'LOGGED_IN_KEY',    '`y2^Zs|kbv-g)~1,(hRX3xM0<&Nf)^hoCoaKvtBPd$s:F]9S 8Twj!]jc=A}&s5:' );
define( 'NONCE_KEY',        'NnFMlL`GRp:9H@q[Ihf@bB#vGB/Et:kGac,?lA}2xg<Xnf25#w|T_|@%(|91(8%>' );
define( 'AUTH_SALT',        ',Q4K::i0PKsa.{J[V0ZC +4QUup:B{5VQXChdti~b)~*SQeh1B}|]OCB#5sTea[F' );
define( 'SECURE_AUTH_SALT', 'Y{A)Mts2<A kF^{yOf>.-y{<_$<mJ/34WGaf;et!23IB7FO}iOMIEoZ@LA~M/B]1' );
define( 'LOGGED_IN_SALT',   'ta(yNC:D`_j-}(woboyH(fZn+67PTL*B+}`<8wx?77L}{0R8|1*}=s`X:BoqR}5<' );
define( 'NONCE_SALT',       ':AD!]/*kGPARo),5?/d=z`-C.<ju=]COo,$lH`fI><iB=Q+QoqJ:*XEfor4_7m4 ' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
