<?php
/**
 * Development environment config
 *
 * @package devgeniem/wp-project
 */

use Roots\WPConfig\Config;

Config::define( 'SAVEQUERIES', true );
Config::define( 'WP_DEBUG', true );
Config::define( 'SCRIPT_DEBUG', true );

/**
 * Use object cache so that we don't have parity problems with production
 * but only cache values for 60 seconds so that developers can be more productive
 */
Config::define( 'WP_REDIS_MAXTTL', 60 );

// Enable plugin and theme updates and installation from the admin
Config::define( 'DISALLOW_FILE_MODS', false );

/**
 * Use elasticsearch from local linked docker container
 */
if ( env( 'ELASTICSEARCH_1_PORT_9200_TCP' ) ) {
    Config::define( 'EP_HOST', str_replace( 'tcp://', 'http://', env( 'ELASTICSEARCH_1_PORT_9200_TCP' ) ) );
}
