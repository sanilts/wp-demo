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

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp-demo' );

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
define( 'AUTH_KEY',         '(SAbBqGFLPZNyOV:<ej/s}4eW-7S@=LMxRaG[~)yniAS8)w487mx[|mRg#=#*&zC' );
define( 'SECURE_AUTH_KEY',  'Nfr:T?.:`]h%[f7er:l^J[Q|#$YR|v<+anF5rd?wCJEt!iN%6p+1Wd/TP4DJE~(k' );
define( 'LOGGED_IN_KEY',    'WD` T9XH6+sPUh7Q3?=Mp/aY`[0X-I$0rcs8xl_zQulP%#C=aEkXz#neMuA5.v04' );
define( 'NONCE_KEY',        'Ch#b1C1[rc*v`~XY]#I,TZ{Jw|Y?{dK)VBBG$4o=S3[rZj+xb% A&iOm{tFq*B:&' );
define( 'AUTH_SALT',        'sP,q#>J`+HnzaKGk]X9}xf?lEnvWBilLgZk$__)Yd;HpgshMTW[&75FC;/@z3%vG' );
define( 'SECURE_AUTH_SALT', 'd[KPK{ aE>!rPnw9t_A}aTtww3]`!)W+w!<RQ9y[8lBtlP{<0.]7unc&sw;&-_Aw' );
define( 'LOGGED_IN_SALT',   '~Z7/HSt`]g4wUPJ,YIf%G[c9Bxzn_B^$aMO?>=wx&%^:-`3:;s$f>r|8x^Y]1Q/V' );
define( 'NONCE_SALT',       '{34Vmx/}}ldO/APH~PLrgqo:c6m(zxmnte&D`0Aa+,#y.M0^|H9XX7uFF+f`s;SR' );

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
