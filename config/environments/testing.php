<?PHP
/**
 * CI testing environment
 *
 * @package geniem/wp-project
 */

use Roots\WPConfig\Config;

// CI server doesn't have or use ssl.
Config::define( 'FORCE_SSL_ADMIN', false );
