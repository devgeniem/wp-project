<?php
/**
 * This file contains WordPress config and replaces the usual wp-config.php
 *
 * @package devgeniem/wp-project
 */

use Roots\WPConfig\Config;

$root_dir    = dirname( __DIR__ );
$webroot_dir = $root_dir . '/web';

/**
 * Expose global env() function from oscarotero/env
 */
Env::init();

/**
 * Use Dotenv to load .env file in root
 * Most environment variables are set from docker environment
 */
$dotenv = Dotenv\Dotenv::create( $root_dir );
if ( file_exists( $root_dir . '/.env' ) ) {
    $dotenv->load();
}

/**
 * Set up our global environment constant
 * Default: development
 */
define( 'WP_ENV', strtolower( env( 'WP_ENV' ) ) ?: 'development' );

/**
 * Set URLs for WP
 *
 * SERVER_NAME is used because WordPress uses it by default in some contexts and we
 * don't want to have million different variables to set.
 *
 * Deduct them from request parameters if developer didn't set the SERVER_NAME.
 *
 * We can always just use nginx to redirect aliases to canonical url
 * This helps changing between dev->stage->production
 */
if ( env( 'WP_HOME' ) && env( 'WP_SITEURL' ) ) {
    Config::define( 'WP_HOME', env( 'WP_HOME' ) );
    Config::define( 'WP_SITEURL', env( 'WP_SITEURL' ) );
}
elseif ( env( 'SERVER_NAME' ) ) {
    // Use provided scheme when possible but use https as default fallback
    if ( defined( 'REQUEST_SCHEME' ) ) {
        Config::define( 'WP_HOME', REQUEST_SCHEME . '://' . env( 'SERVER_NAME' ) );
        Config::define( 'WP_SITEURL', REQUEST_SCHEME . '://' . env( 'SERVER_NAME' ) );
    }
    else {
        Config::define( 'WP_HOME', 'https://' . env( 'SERVER_NAME' ) );
        Config::define( 'WP_SITEURL', 'https://' . env( 'SERVER_NAME' ) );
    }
}
elseif ( defined( 'REQUEST_SCHEME' ) && defined( 'HTTP_HOST' ) ) {
    Config::define( 'WP_HOME', env( 'REQUEST_SCHEME' ) . '://' . env( 'HTTP_HOST' ) );
    Config::define( 'WP_SITEURL', env( 'REQUEST_SCHEME' ) . '://' . env( 'HTTP_HOST' ) );
}

/**
 * Custom Content Directory
 */
Config::define( 'CONTENT_DIR', '/app' );
Config::define( 'WP_CONTENT_DIR', $webroot_dir . Config::get( 'CONTENT_DIR' ) );
Config::define( 'WP_CONTENT_URL', Config::get( 'WP_HOME' ) . Config::get( 'CONTENT_DIR' ) );

/**
 * Deprecated variables which some stupid plugins still use
 *
 * @see wp-includes/default-constants.php:162
 * @see wp-includes/default-constants.php:189
 */
Config::define( 'PLUGINDIR', Config::get( 'CONTENT_DIR' ) . '/plugins' );
Config::define( 'MUPLUGINDIR', Config::get( 'CONTENT_DIR' ) . '/mu-plugins' );

/**
 * DB settings - Use MYSQL_DATABASE, MYSQL_USER, MYSQL_PWD, MYSQL_HOST first
 * but fallback to docker container links
 */
Config::define( 'DB_NAME', env( 'MYSQL_DATABASE' ) ?: env( 'DB_ENV_MYSQL_PASSWORD' ) );
Config::define( 'DB_USER', env( 'MYSQL_USER' ) ?: env( 'DB_ENV_MYSQL_USER' ) );
Config::define( 'DB_HOST', env( 'MYSQL_HOST' ) ?: env( 'DB_PORT_3306_TCP_ADDR' ) );
Config::define( 'DB_READ_HOST', env( 'MYSQL_READ_HOST' ) );
Config::define( 'DB_PASSWORD', env( 'MYSQL_PWD' ) ?: env( 'DB_ENV_MYSQL_PASSWORD' ) );
Config::define( 'DB_CHARSET', env( 'DB_CHARSET' ) ?: 'utf8mb4' );
Config::define( 'DB_COLLATE', env( 'DB_COLLATE' ) ?: 'utf8mb4_swedish_ci' );
// @codingStandardsIgnoreStart - allow overriding WordPress globals
$table_prefix = env( 'DB_PREFIX' ) ?: 'wp_';
//@codingStandardsIgnoreEnd

/**
 * Define Nginx fullpage cache folder
 */
Config::define( 'RT_WP_NGINX_HELPER_CACHE_PATH', '/dev/cache/' );

/**
 * Use Redis for object cache
 */
Config::define( 'WP_REDIS_CLIENT', env( 'WP_REDIS_CLIENT' ) );
Config::define( 'WP_REDIS_HOST', env( 'REDIS_HOST' ) ?: env( 'REDIS_PORT_6379_TCP_ADDR' ) );
// Local enviroment uses REDIS_PORT=tcp://172.17.0.6:6379 and this fixes it.
$redis_tmp_port = explode( ':', env( 'REDIS_PORT' ) );
Config::define( 'WP_REDIS_PORT', env( 'REDIS_PORT' ) ? intval( end( $redis_tmp_port ) ) : 6379 );
unset( $redis_tmp_port );

Config::define( 'WP_REDIS_PASSWORD', env( 'REDIS_PASSWORD' ) ?: '' );
Config::define( 'WP_REDIS_DATABASE', env( 'WP_REDIS_DATABASE' ) ?: '0' );
Config::define( 'WP_CACHE_KEY_SALT', env( 'WP_CACHE_KEY_SALT' ) ?: '' );

/**
 * Expiration time for WP object cache in seconds.
 */
Config::define( 'WP_REDIS_MAXTTL', 60 * 60 * 24 * 30 );

/**
 * Authentication Unique Keys and Salts
 */
Config::define( 'AUTH_KEY', env( 'AUTH_KEY' ) );
Config::define( 'SECURE_AUTH_KEY', env( 'SECURE_AUTH_KEY' ) );
Config::define( 'LOGGED_IN_KEY', env( 'LOGGED_IN_KEY' ) );
Config::define( 'NONCE_KEY', env( 'NONCE_KEY' ) );
Config::define( 'AUTH_SALT', env( 'AUTH_SALT' ) );
Config::define( 'SECURE_AUTH_SALT', env( 'SECURE_AUTH_SALT' ) );
Config::define( 'LOGGED_IN_SALT', env( 'LOGGED_IN_SALT' ) );
Config::define( 'NONCE_SALT', env( 'NONCE_SALT' ) );

/**
 * Custom Settings
 */
Config::define( 'AUTOMATIC_UPDATER_DISABLED', true );
Config::define( 'DISABLE_WP_CRON', env( 'DISABLE_WP_CRON' ) ?: false );
// Disable the plugin and theme file editor in the admin
Config::define( 'DISALLOW_FILE_EDIT', true );
// Disable plugin and theme updates and installation from the admin
Config::define( 'DISALLOW_FILE_MODS', true );
Config::define( 'FS_METHOD', 'direct' );

/**
 * Debugging Settings
 */
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'SCRIPT_DEBUG', false );

/**
 * Always use HTTPS in admin
 */
Config::define( 'FORCE_SSL_ADMIN', true );

/**
 * Change uploads directory so that we can use glusterfs (or other replication) more easily
 * Note: this is only tested in single site installation
 * Uses: web/app/mu-plugins/moved-uploads.php
 */
Config::define( 'WP_UPLOADS_DIR', env( 'WP_UPLOADS_DIR' ) ?: '/var/www/uploads' );
Config::define( 'WP_UPLOADS_URL', env( 'WP_UPLOADS_URL' ) ?: Config::get( 'WP_HOME' ) . '/uploads' );

/**
 * Select default theme which is activated during project startup
 * Use this when the project has default theme to use.
 * Skip this define if this env is the default value
 */
// Skip Codesniffer rules on normally stupid string interpolation
//@codingStandardsIgnoreStart
if ( env( 'WP_DEFAULT_THEME' ) && env( 'WP_DEFAULT_THEME' ) !== 'THEME' . 'NAME' ) {
    Config::define( 'WP_DEFAULT_THEME', env( 'WP_DEFAULT_THEME' ) );
}
//@codingStandardsIgnoreEnd

/**
 * Settings for packages in devgeniem/wp-safe-fast-and-clean-collection
 */
// wp-no-admin-ajax changes the /wp-admin/admin-ajax.php to pretty url
Config::define( 'WP_NO_ADMIN_AJAX_URL', '/ajax/' );

/**
 * Only keep the last 5 revisions of a post. Having hundreds of revisions of
 * each post might cause sites to slow down, sometimes significantly due to a
 * massive, and usually unecessary bloating the wp_posts and wp_postmeta tables.
 */
Config::define( 'WP_POST_REVISIONS', env( 'WP_POST_REVISIONS' ) ?: 5 );

/**
 * Define newsletter plugin logging into php logging directory
 * Uses: https://wordpress.org/plugins/newsletter/
 */
Config::define( 'NEWSLETTER_LOG_DIR', dirname( ini_get( 'error_log' ) ) . '/newsletter/' );

/**
 * Polylang settings
 * Uses: https://wordpress.org/plugins/polylang/
 */
// Disables Polylang cookies and allows better caching
Config::define( 'PLL_COOKIE', false );

// This setting allows Polylang functions to work correctly when used with wp-cli
if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'PLL_ADMIN' ) ) {
    Config::define( 'PLL_ADMIN', true );
}

/**
 * Define memory limit so that wp-cli can use more memory than the default 40M
 */
Config::define( 'WP_MEMORY_LIMIT', env( 'PHP_MEMORY_LIMIT' ) ?: '128M' );

/**
 * Load custom configs according to WP_ENV environment variable
 */
$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if ( file_exists( $env_config ) ) {
    include_once $env_config;
}

/**
 * Setup WP Stateless to upload all the files into the Google Bucket.
 */
Config::define( 'WP_STATELESS_MEDIA_BUCKET', env( 'GOOGLE_CLOUD_STORAGE_BUCKET_NAME' ) );
Config::define( 'WP_STATELESS_MEDIA_MODE', 'stateless' );
Config::define( 'WP_STATELESS_MEDIA_BODY_REWRITE', 'false' );
Config::define( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT ', env( 'GOOGLE_SERVICE_ACCOUNT_EMAIL' ) );
Config::define( 'WP_STATELESS_MEDIA_JSON_KEY', env( 'GOOGLE_CLOUD_STORAGE_ACCESS_KEY' ) );
Config::define( 'WP_STATELESS_MEDIA_CACHE_BUSTING', 'true' );

// Replace the default bucket link and use the current domain. We serve uploads through a Nginx proxy cache.
$scheme = defined( 'REQUEST_SCHEME' ) ? REQUEST_SCHEME : 'https';
// @codingStandardsIgnoreStart - a safer way to access server variables on PHP7.x
$server_name = filter_var( $_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL );
// @codingStandardsIgnoreEnd
Config::define( 'WP_STATELESS_BUCKET_LINK_REPLACE', $scheme . '://' . $server_name . '/uploads/' );

/**
 * Define read only options to override WordPress options.
 *
 * 1 : I would like my blog to be visible to everyone, including search engines.
 * 0 : I would like to block search engines, but allow normal visitors.
 */
Config::define( 'WP_READONLY_OPTIONS', [ 'blog_public' => env( 'WP_BLOG_PUBLIC' ) ?? 1 ] );

/**
 * Define Google API-Key
 */
Config::define( 'GOOGLE_MAPS_APIKEY', env( 'GOOGLE_MAPS_APIKEY' ) );

/**
 * Bootstrap WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', $webroot_dir . '/wp/' );
}

Config::apply();

/**
 * Remove temporary variables from the global context
 */
unset( $root_dir, $webroot_dir, $env_config );
