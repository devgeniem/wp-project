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
define( 'FORCE_SSL_ADMIN', true );

/**
 * Use object cache so that we don't have parity problems with production
 * but only cache values for 60 seconds so that developers can be more productive
 */
define( 'WP_REDIS_MAXTTL', 60 );

/**
 * Use elasticsearch from local linked docker container
 */
if ( env( 'ELASTICSEARCH_1_PORT_9200_TCP' ) ) {
    define( 'EP_HOST', str_replace( 'tcp://', 'http://', env( 'ELASTICSEARCH_1_PORT_9200_TCP' ) ) );
}
