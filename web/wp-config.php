<?php
##############################################################################
##### We highly suggest not put anything sensitive in this file directly #####
##### Use .env instead.                                                  #####
##############################################################################

#Load composer libraries
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$root_dir = dirname(__DIR__);
$webroot_dir = $root_dir . '/htdocs';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * System environment provides envs for wordpress by default.
 * If you want to override or have more envs put them into .env file
 */
if (file_exists($root_dir . '/.env')) {
  Dotenv::makeMutable();
  Dotenv::load($root_dir);
}


/**
 * DB settings  
 */
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_PORT_3306_TCP_ADDR') ? getenv('DB_PORT_3306_TCP_ADDR') : getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT') ? getenv('DB_PORT') : 3306 );
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = getenv('DB_PREFIX') ? getenv('DB_PREFIX') : 'wp_';

/**
 * Redis as object cache
 * You need to have this plugin in https://wordpress.org/plugins/wp-redis/
 * wp-content/object-cache.php in order to use redis for transients and cache
 */
$redis_server = array( 'host' => getenv('REDIS_HOST'), 'port' => getenv('REDIS_PORT'));

/**
 * Content Directory is moved out of the wp-core.
 */
define('CONTENT_DIR', '/content');
define('WP_CONTENT_DIR', $webroot_dir . CONTENT_DIR);
define('WP_CONTENT_URL', CONTENT_DIR);

/**
 * Change uploads directory so that we can use glusterfs more easily
 * Note: this is only tested in single site installation
 */
define('WP_UPLOADS_DIR', getenv('WP_UPLOADS_DIR') ? getenv('WP_UPLOADS_DIR') : '/data/uploads' );
define('WP_UPLOADS_URL', getenv('WP_UPLOADS_URL') ? getenv('WP_UPLOADS_URL') : '/uploads' );

/**
 * Don't allow any other write method than direct
 */
define( 'FS_METHOD', 'direct' );

/**
 * Authentication Unique Keys and Salts
 * You can find them by running $ wp-list-env
 */
define('AUTH_KEY',         getenv('AUTH_KEY'));
define('SECURE_AUTH_KEY',  getenv('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY',    getenv('LOGGED_IN_KEY'));
define('NONCE_KEY',        getenv('NONCE_KEY'));
define('AUTH_SALT',        getenv('AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT',   getenv('LOGGED_IN_SALT'));
define('NONCE_SALT',       getenv('NONCE_SALT'));

/**
 * SSL ADMIN
 * Allow overriding it in dev environment so we can use phantomjs to test logging in.
 */
define('FORCE_SSL_ADMIN', true);

/**
 * Use different domain for the wp-admin if available
 */
if ( getenv('HTTPS_DOMAIN_ALIAS') )
  define('HTTPS_DOMAIN_ALIAS', getenv('HTTPS_DOMAIN_ALIAS'));


if ( getenv('WP_ENV') == 'production')
  define('DISALLOW_FILE_MODS', true); /* Deny all file changes in production. This is for security and integrity. */
else
  define('DISALLOW_FILE_EDIT', true); /* This disables the theme/plugin file editor */

/**
 * Custom Settings
 */
define('AUTOMATIC_UPDATER_DISABLED', true); /* automatic updates are handled by wordpress-palvelu */
define('PLL_COOKIE', false); /* this allows caching sites with polylang, disable if weird issues occur */

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

/**
 * Log error data but don't show it in the frontend.
 */
ini_set('log_errors', 'On');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
  define('ABSPATH', $webroot_dir . '/wp/');
}

require_once(ABSPATH . 'wp-settings.php');
