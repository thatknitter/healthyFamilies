<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'healthyFamilies');

/** MySQL database username */
define('DB_USER', 'fltAdmin');

/** MySQL database password */
define('DB_PASSWORD', 'Shescaredme3');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '[?:07ho93 omW.y`S*RmGB0o+/zOF0s||BVi<V(rXu+i1`+Y#WJ{@.Qp 4|cQ(SJ');
define('SECURE_AUTH_KEY',  'mH{P`ny`]<?ptfO0uCZQqu_+%C-B</f73{^7]tLMm)@E0pl{.F^%];R$^:;/RvOX');
define('LOGGED_IN_KEY',    '+FRl5w{i_rhka>F;/uq|{eM+)`2){m58;SiRk{HSc48ifKbxy7GTun!?c@YNn$F^');
define('NONCE_KEY',        ')GvT$.g+o.q.lY[PPUt|4)s~f Cm>Hb&;V^/fX<DN%<ze0Q!4,NnU;3CrP%7^6_G');
define('AUTH_SALT',        'OyI)|A6VQaYb=>qP+pT5kxCBc%-9E=1Spt/;d2-1{-qh+<l0[|vWhb$4FH6hj<qV');
define('SECURE_AUTH_SALT', 'm!]nt4n9v=H]A9q_%/7h2>A(u!hx@.{z[X%+x-XneckFqpoUshQj=1=YK0s2{?s6');
define('LOGGED_IN_SALT',   'c~p>+$bo*6$L>Lu/-:}UVl0 I{I:2-xlx9)C3Df+btLw-*(Dfs%QT>-r+-ySF<QD');
define('NONCE_SALT',       '[=yv8(}ny~zJKlo5$!A`?-f1riLV^}<.9)1*0>]EJlyMp|@; ut|Y-{|gYkMAyXB');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
