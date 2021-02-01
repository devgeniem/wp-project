<?php
/**
 * This file contains WordPress config and replaces the usual wp-config.php
 *
 * @package devgeniem/wp-project
 *
 * phpcs:disable WordPress.WP.DiscouragedConstants.PLUGINDIRDeclarationFound
 * phpcs:disable WordPress.WP.DiscouragedConstants.MUPLUGINDIRDeclarationFound
 * phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
 */

$root_dir    = dirname( __DIR__ );
$webroot_dir = $root_dir . '/web';

/**
 * Expose global env() function from oscarotero/env
 */
Env::init();

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * Most of ENV are set from docker environment
 */
$dotenv = new Dotenv\Dotenv( $root_dir );
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
 * SERVER_NAME is used because WordPress uses it by default in some contexts and we
 * don't want to have million different variables to set.
 *
 * Deduct them from request parameters if developer didn't set the SERVER_NAME.
 *
 * We can always just use nginx to redirect aliases to canonical url
 * This helps changing between dev->stage->production
 */
if ( env( 'WP_HOME' ) && env( 'WP_SITEURL' ) ) {
    define( 'WP_HOME', env( 'WP_HOME' ) );
    define( 'WP_SITEURL', env( 'WP_SITEURL' ) );
}
elseif ( env( 'SERVER_NAME' ) ) {
    // Use provided scheme when possible but use https as default fallback
    if ( defined( 'REQUEST_SCHEME' ) ) {
        define( 'WP_HOME', REQUEST_SCHEME . '://' . env( 'SERVER_NAME' ) );
        define( 'WP_SITEURL', REQUEST_SCHEME . '://' . env( 'SERVER_NAME' ) );
    }
    else {
        define( 'WP_HOME', 'https://' . env( 'SERVER_NAME' ) );
        define( 'WP_SITEURL', 'https://' . env( 'SERVER_NAME' ) );
    }
}
elseif ( defined( 'REQUEST_SCHEME' ) && defined( 'HTTP_HOST' ) ) {
    define( 'WP_HOME', env( 'REQUEST_SCHEME' ) . '://' . env( 'HTTP_HOST' ) );
    define( 'WP_SITEURL', env( 'REQUEST_SCHEME' ) . '://' . env( 'HTTP_HOST' ) );
}

/**
 * Custom Content Directory
 */
define( 'CONTENT_DIR', '/app' );
define( 'WP_CONTENT_DIR', $webroot_dir . CONTENT_DIR );
define( 'WP_CONTENT_URL', WP_HOME . CONTENT_DIR );

/**
 * Deprecated variables which some stupid plugins still use
 *
 * @see wp-includes/default-constants.php:162
 * @see wp-includes/default-constants.php:189
 */
define( 'PLUGINDIR', CONTENT_DIR . '/plugins' );
define( 'MUPLUGINDIR', CONTENT_DIR . '/mu-plugins' );

/**
 * DB settings - Use MYSQL_DATABASE, MYSQL_USER, MYSQL_PWD, MYSQL_HOST first
 * but fallback to docker container links
 */
define( 'DB_NAME', env( 'MYSQL_DATABASE' ) ?: env( 'DB_ENV_MYSQL_PASSWORD' ) );
define( 'DB_USER', env( 'MYSQL_USER' ) ?: env( 'DB_ENV_MYSQL_USER' ) );
define( 'DB_HOST', env( 'MYSQL_HOST' ) ?: env( 'DB_PORT_3306_TCP_ADDR' ) );
define( 'DB_READ_HOST', env( 'MYSQL_READ_HOST' ) );
define( 'DB_PASSWORD', env( 'MYSQL_PWD' ) ?: env( 'DB_ENV_MYSQL_PASSWORD' ) );
define( 'DB_CHARSET', env( 'DB_CHARSET' ) ?: 'utf8mb4' );
define( 'DB_COLLATE', env( 'DB_COLLATE' ) ?: 'utf8mb4_swedish_ci' );
$table_prefix = env( 'DB_PREFIX' ) ?: 'wp_';

/**
 * Define Nginx fullpage cache folder
 */
define( 'RT_WP_NGINX_HELPER_CACHE_PATH', '/tmp/nginx/fullpage/' );

/**
 * Use redis for object cache
 */
define( 'WP_REDIS_CLIENT', env( 'WP_REDIS_CLIENT' ) );
define( 'WP_REDIS_HOST', env( 'REDIS_HOST' ) ?: env( 'REDIS_PORT_6379_TCP_ADDR' ) );
// Local environment uses REDIS_PORT=tcp://172.17.0.6:6379 and this fixes it.
$redis_tmp_port = explode( ':', env( 'REDIS_PORT' ) );
define( 'WP_REDIS_PORT', env( 'REDIS_PORT' ) ? intval( end( $redis_tmp_port ) ) : 6379 );
unset( $redis_tmp_port );

define( 'WP_REDIS_PASSWORD', env( 'REDIS_PASSWORD' ) ?: '' );

# Most configs use REDIS_DATABASE instead of WP_REDIS_DATABASE.
# Keep WP_REDIS_DATABASE support for legacy reasons.
if ( ! empty( env( 'REDIS_DATABASE' ) ) ) {
    define( 'WP_REDIS_DATABASE', env( 'REDIS_DATABASE' ) );
}
else {
    define( 'WP_REDIS_DATABASE', env( 'WP_REDIS_DATABASE' ) ?: '0' );
}

define( 'WP_CACHE_KEY_SALT', env( 'WP_CACHE_KEY_SALT' ) ?: '' );

/**
 * Authentication Unique Keys and Salts
 */
define( 'AUTH_KEY', env( 'AUTH_KEY' ) );
define( 'SECURE_AUTH_KEY', env( 'SECURE_AUTH_KEY' ) );
define( 'LOGGED_IN_KEY', env( 'LOGGED_IN_KEY' ) );
define( 'NONCE_KEY', env( 'NONCE_KEY' ) );
define( 'AUTH_SALT', env( 'AUTH_SALT' ) );
define( 'SECURE_AUTH_SALT', env( 'SECURE_AUTH_SALT' ) );
define( 'LOGGED_IN_SALT', env( 'LOGGED_IN_SALT' ) );
define( 'NONCE_SALT', env( 'NONCE_SALT' ) );

/**
 * Custom Settings
 */
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'DISABLE_WP_CRON', env( 'DISABLE_WP_CRON' ) ?: false );
define( 'DISALLOW_FILE_EDIT', true );
define( 'FS_METHOD', 'direct' );

/**
 * Change uploads directory so that we can use glusterfs (or other replication) more easily
 * Note: this is only tested in single site installation
 * Uses: web/app/mu-plugins/moved-uploads.php
 */
define( 'WP_UPLOADS_DIR', env( 'WP_UPLOADS_DIR' ) ?: '/var/www/uploads' );
define( 'WP_UPLOADS_URL', env( 'WP_UPLOADS_URL' ) ?: WP_HOME . '/uploads' );

/**
 * Select default theme which is activated during project startup
 * Use this when the project has default theme to use.
 * Skip this define if this env is the default value
 */
// Skip Codesniffer rules on normally stupid string interpolation
// @codingStandardsIgnoreStart
if ( env( 'WP_DEFAULT_THEME' ) && env( 'WP_DEFAULT_THEME' ) !== 'THEME' . 'NAME' ) {
    define( 'WP_DEFAULT_THEME', env( 'WP_DEFAULT_THEME' ) );
}
// @codingStandardsIgnoreEnd

/**
 * Settings for packages in devgeniem/wp-safe-fast-and-clean-collection
 */
// wp-no-admin-ajax changes the /wp-admin/admin-ajax.php to pretty url
define( 'WP_NO_ADMIN_AJAX_URL', '/ajax/' );

/**
 * Only keep the last 5 revisions of a post. Having hundreds of revisions of
 * each post might cause sites to slow down, sometimes significantly due to a
 * massive, and usually unnecessary bloating the wp_posts and wp_postmeta tables.
 */
define( 'WP_POST_REVISIONS', env( 'WP_POST_REVISIONS' ) ?: 5 );

/**
 * Define newsletter plugin logging into php logging directory
 * Uses: https://wordpress.org/plugins/newsletter/
 */
define( 'NEWSLETTER_LOG_DIR', dirname( ini_get( 'error_log' ) ) . '/newsletter/' );

/**
 * Polylang settings
 * Uses: https://wordpress.org/plugins/polylang/
 */
// Disables Polylang cookies and allows better caching
define( 'PLL_COOKIE', false );

// This setting allows Polylang functions to work correctly when used with wp-cli
if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'PLL_ADMIN' ) ) {
    define( 'PLL_ADMIN', true );
}

/**
 * Define memory limit so that wp-cli can use more memory than the default 40M
 */
define( 'WP_MEMORY_LIMIT', env( 'PHP_MEMORY_LIMIT' ) ?: '128M' );

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
define( 'WP_STATELESS_MEDIA_BUCKET', env( 'GOOGLE_CLOUD_STORAGE_BUCKET_NAME' ) );
define( 'WP_STATELESS_MEDIA_MODE', 'cdn' );
define( 'WP_STATELESS_MEDIA_BODY_REWRITE', 'false' );
define( 'WP_STATELESS_MEDIA_SERVICE_ACCOUNT ', env( 'GOOGLE_SERVICE_ACCOUNT_EMAIL' ) );
define( 'WP_STATELESS_MEDIA_JSON_KEY', env( 'GOOGLE_CLOUD_STORAGE_ACCESS_KEY' ) );
define( 'WP_STATELESS_MEDIA_CACHE_BUSTING', 'true' );

// Replace the default bucket link and use the current domain. We serve uploads through a Nginx proxy cache.
$scheme = defined( 'REQUEST_SCHEME' ) ? REQUEST_SCHEME : 'https';
// @codingStandardsIgnoreStart - a safer way to access server variables on PHP7.x
$server_name = filter_var( $_SERVER['SERVER_NAME'], FILTER_SANITIZE_URL );
// @codingStandardsIgnoreEnd
define( 'WP_STATELESS_BUCKET_LINK_REPLACE', $scheme . '://' . $server_name . '/uploads/' );

/**
 * Define read only options to override WordPress options.
 */
define( 'WP_READONLY_OPTIONS', [
    // 1 : I would like my blog to be visible to everyone, including search engines.
    // 0 : I would like to block search engines, but allow normal visitors.
    'blog_public' => env( 'WP_BLOG_PUBLIC' ) ?? 1,
] );

/**
 * Define Google API-Key
 */
define( 'GOOGLE_MAPS_APIKEY', env( 'GOOGLE_MAPS_APIKEY' ) );

/**
 * Bootstrap WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', $webroot_dir . '/wp/' );
}

/**
 * Remove temporary variables from the global context
 */
unset( $root_dir, $webroot_dir, $env_config );
