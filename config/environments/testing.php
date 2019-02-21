<?PHP
/**
 * CI testing environment
 *
 * @package geniem/wp-project
 */

// CI server doesn't have or use ssl.
Config::define( 'FORCE_SSL_ADMIN', false );
