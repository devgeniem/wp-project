<?php
/**
 * Development environment config
 *
 * @package devgeniem/wp-project
 */

define( 'SAVEQUERIES', true );
define( 'WP_DEBUG', true );
define( 'SCRIPT_DEBUG', true );

/**
 * We use onnimonni/signaler for https in local development
 */

$disable_local_ssl = env( 'DISABLE_LOCAL_SSL' ) ?: 0;

define( 'FORCE_SSL_ADMIN', ( $disable_local_ssl ) ? false : true );

/**
 * Use object cache so that we don't have parity problems with production
 * but only cache values for 300 seconds so that developers can be more productive
 */
define( 'WP_REDIS_MAXTTL', 300 );

// This defines the nginx full page cache headers
header( 'Cache-Control: max-age=60, stale-while-revalidate=180' );
