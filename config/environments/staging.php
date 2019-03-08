<?php
/**
 * Staging environment config
 *
 * @package devgeniem/wp-project
 */

use Roots\WPConfig\Config;

/**
 * Expiration time for WP object cache in seconds.
 */
Config::define( 'WP_REDIS_MAXTTL', 60 * 60 * 4 );
