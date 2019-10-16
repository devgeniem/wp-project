<?php
// @codingStandardsIgnoreFile
// HyperDB
// This file should be installed at ABSPATH/wp-content/db.php

/** Load the wpdb class while preventing instantiation **/
$wpdb = true;
if ( defined('WPDB_PATH') )
	require_once(WPDB_PATH);
else
	require_once( ABSPATH . WPINC . '/wp-db.php' );

if ( defined('DB_CONFIG_FILE') && file_exists( DB_CONFIG_FILE ) ) {

	/** The config file was defined earlier. **/

} elseif ( file_exists( ABSPATH . 'db-config.php') ) {

	/** The config file resides in ABSPATH. **/
	define( 'DB_CONFIG_FILE', ABSPATH . 'db-config.php' );

} elseif ( file_exists( dirname(ABSPATH) . '/db-config.php' ) && ! file_exists( dirname(ABSPATH) . '/wp-settings.php' ) ) {

	/** The config file resides one level above ABSPATH but is not part of another install. **/
	define( 'DB_CONFIG_FILE', dirname(ABSPATH) . '/db-config.php' );

} else {

	/** Lacking a config file, revert to the standard database class. **/
	$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
	return;

}

/**
 * Common definitions
 */
define( 'HYPERDB_LAG_OK', 1 );
define( 'HYPERDB_LAG_BEHIND', 2 );
define( 'HYPERDB_LAG_UNKNOWN', 3 );

class hyperdb extends wpdb {
	/**
	 * The last table that was queried
	 * @var string
	 */
	var $last_table;

	/**
	 * After any SQL_CALC_FOUND_ROWS query, the query "SELECT FOUND_ROWS()"
	 * is sent and the mysql result resource stored here. The next query
	 * for FOUND_ROWS() will retrieve this. We do this to prevent any
	 * intervening queries from making FOUND_ROWS() inaccessible. You may
	 * prevent this by adding "NO_SELECT_FOUND_ROWS" in a comment.
	 * @var resource
	 */
	var $last_found_rows_result;

	/**
	 * Whether to store queries in an array. Useful for debugging and profiling.
	 * @var bool
	 */
	var $save_queries = false;

	/**
	 * The current mysql link resource
	 * @var resource
	 */
	var $dbh;

	/**
	 * Associative array (dbhname => dbh) for established mysql connections
	 * @var array
	 */
	var $dbhs;

	/**
	 * The multi-dimensional array of datasets and servers
	 * @var array
	 */
	var $hyper_servers = array();

	/**
	 * Optional directory of tables and their datasets
	 * @var array
	 */
	var $hyper_tables = array();

	/**
	 * Optional directory of callbacks to determine datasets from queries
	 * @var array
	 */
	var $hyper_callbacks = array();

	/**
	 * Custom callback to save debug info in $this->queries
	 * @var callable
	 */
	var $save_query_callback = null;

	/**
	 * Whether to use a persistent connection to the server or not
	 * @var bool
	 */
	var $persistent = false;

	/**
	 * The maximum number of db links to keep open. The least-recently used
	 * link will be closed when the number of links exceeds this.
	 * @var int
	 */
	var $max_connections = 10;

	/**
	 * Whether to check with fsockopen prior to connecting to the server
	 * @var bool
	 */
	var $check_tcp_responsiveness = true;

	/**
	 * Minimum number of connections to try before bailing
	 * @var int
	 */
	var $min_tries = 3;

	/**
	 * Send Reads To Masters. This disables slave connections while true.
	 * Otherwise it is an array of written tables.
	 * @var array
	 */
	var $srtm = array();

	/**
	 * The log of db connections made and the time each one took
	 * @var array
	 */
	var $db_connections;

	/**
	 * The list of unclosed connections sorted by LRU
	 */
	var $open_connections = array();

	/**
	 * Lookup array (dbhname => host:port)
	 * @var array
	 */
	var $dbh2host = array();

	/**
	 * The last server used and the database name selected
	 * @var array
	 */
	var $last_used_server;

	/**
	 * Lookup array (dbhname => (server, db name) ) for re-selecting the db
	 * when a link is re-used.
	 * @var array
	 */
	var $used_servers = array();

	/**
	 * Whether to save debug_backtrace in save_query_callback. You may wish
	 * to disable this, e.g. when tracing out-of-memory problems.
	 */
	var $save_backtrace = true;

	/**
	 * Maximum lag in seconds. Set null to disable. Requires callbacks.
	 * @var integer
	 */
	var $default_lag_threshold = null;

	/**
	 * Gets ready to make database connections
	 * @param array db class vars
	 */
	function __construct( $args = null ) {
		if ( is_array($args) )
			foreach ( get_class_vars(__CLASS__) as $var => $value )
				if ( isset($args[$var]) )
					$this->$var = $args[$var];

		$this->init_charset();
	}

	/**
	 * Triggers __construct() for backwards compatibility with PHP4
	 */
	function hyperdb( $args = null ) {
		return $this->__construct($args);
	}

	/**
	 * Sets $this->charset and $this->collate
	 */
	function init_charset() {
		if ( function_exists('is_multisite') && is_multisite() ) {
			$this->charset = 'utf8';
			if ( defined( 'DB_COLLATE' ) && DB_COLLATE )
				$this->collate = DB_COLLATE;
			else
				$this->collate = 'utf8_general_ci';
		} elseif ( defined( 'DB_COLLATE' ) ) {
			$this->collate = DB_COLLATE;
		}

		if ( defined( 'DB_CHARSET' ) )
			$this->charset = DB_CHARSET;
	}

	/**
	 * Add the connection parameters for a database
	 */
	function add_database( $db ) {
		extract($db, EXTR_SKIP);
		isset($dataset) or $dataset = 'global';
		isset($read)    or $read = 1;
		isset($write)   or $write = 1;
		unset($db['dataset']);

		if ( $read )
			$this->hyper_servers[ $dataset ][ 'read' ][ $read ][] = $db;
		if ( $write )
			$this->hyper_servers[ $dataset ][ 'write' ][ $write ][] = $db;
	}

	/**
	 * Specify the dateset where a table is found
	 */
	function add_table( $dataset, $table ) {
		$this->hyper_tables[ $table ] = $dataset;
	}

	/**
	 * Add a callback to a group of callbacks.
	 * The default group is 'dataset', used to examine
	 * queries and determine dataset.
	 */
	function add_callback( $callback, $group = 'dataset' ) {
		$this->hyper_callbacks[ $group ][] = $callback;
	}

	/**
	 * Find the first table name referenced in a query
	 * @param string query
	 * @return string table
	 */
	function get_table_from_query ( $q ) {
		// Remove characters that can legally trail the table name
		$q = rtrim($q, ';/-#');
		// allow (select...) union [...] style queries. Use the first queries table name.
		$q = ltrim($q, "\t (");

		// Quickly match most common queries
		if ( preg_match('/^\s*(?:'
				. 'SELECT.*?\s+FROM'
				. '|INSERT(?:\s+IGNORE)?(?:\s+INTO)?'
				. '|REPLACE(?:\s+INTO)?'
				. '|UPDATE(?:\s+IGNORE)?'
				. '|DELETE(?:\s+IGNORE)?(?:\s+FROM)?'
				. ')\s+`?([\w-]+)`?/is', $q, $maybe) )
			return $maybe[1];

		// Refer to the previous query
		if ( preg_match('/^\s*SELECT.*?\s+FOUND_ROWS\(\)/is', $q) )
			return $this->last_table;

		// SHOW TABLE STATUS and SHOW TABLES
		if ( preg_match('/^\s*(?:'
				. 'SHOW\s+TABLE\s+STATUS.+(?:LIKE\s+|WHERE\s+Name\s*=\s*)'
				. '|SHOW\s+(?:FULL\s+)?TABLES.+(?:LIKE\s+|WHERE\s+Name\s*=\s*)'
				. ')\W([\w-]+)\W/is', $q, $maybe) )
			return $maybe[1];

		// Big pattern for the rest of the table-related queries in MySQL 5.0
		if ( preg_match('/^\s*(?:'
				. '(?:EXPLAIN\s+(?:EXTENDED\s+)?)?SELECT.*?\s+FROM'
				. '|INSERT(?:\s+LOW_PRIORITY|\s+DELAYED|\s+HIGH_PRIORITY)?(?:\s+IGNORE)?(?:\s+INTO)?'
				. '|REPLACE(?:\s+LOW_PRIORITY|\s+DELAYED)?(?:\s+INTO)?'
				. '|UPDATE(?:\s+LOW_PRIORITY)?(?:\s+IGNORE)?'
				. '|DELETE(?:\s+LOW_PRIORITY|\s+QUICK|\s+IGNORE)*(?:\s+FROM)?'
				. '|DESCRIBE|DESC|EXPLAIN|HANDLER'
				. '|(?:LOCK|UNLOCK)\s+TABLE(?:S)?'
				. '|(?:RENAME|OPTIMIZE|BACKUP|RESTORE|CHECK|CHECKSUM|ANALYZE|OPTIMIZE|REPAIR).*\s+TABLE'
				. '|TRUNCATE(?:\s+TABLE)?'
				. '|CREATE(?:\s+TEMPORARY)?\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?'
				. '|ALTER(?:\s+IGNORE)?\s+TABLE'
				. '|DROP\s+TABLE(?:\s+IF\s+EXISTS)?'
				. '|CREATE(?:\s+\w+)?\s+INDEX.*\s+ON'
				. '|DROP\s+INDEX.*\s+ON'
				. '|LOAD\s+DATA.*INFILE.*INTO\s+TABLE'
				. '|(?:GRANT|REVOKE).*ON\s+TABLE'
				. '|SHOW\s+(?:.*FROM|.*TABLE)'
				. ')\s+`?([\w-]+)`?/is', $q, $maybe) )
			return $maybe[1];
	}

	/**
	 * Determine the likelihood that this query could alter anything
	 * @param string query
	 * @return bool
	 */
	function is_write_query( $q ) {
		// Quick and dirty: only SELECT statements are considered read-only.
		$q = ltrim($q, "\r\n\t (");
		return !preg_match('/^(?:SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\s/i', $q);
	}

	/**
	 * Set a flag to prevent reading from slaves which might be lagging after a write
	 */
	function send_reads_to_masters() {
		$this->srtm = true;
	}

	/**
	 * Callbacks are executed in the order in which they are registered until one
	 * of them returns something other than null.
	 */
	function run_callbacks( $group, $args = null) {
		if ( !isset( $this->hyper_callbacks[ $group ] ) || !is_array( $this->hyper_callbacks[ $group ] ) )
			return null;

		if ( !isset($args) ) {
			$args = array( &$this );
		} elseif ( is_array( $args ) ) {
			$args[] = &$this;
		} else {
			$args = array( $args, &$this );
		}

		foreach ( $this->hyper_callbacks[ $group ] as $func ) {
			$result = call_user_func_array($func, $args);
			if ( isset($result) )
				return $result;
		}
	}

	/**
	 * Figure out which database server should handle the query, and connect to it.
	 * @param string query
	 * @return resource mysql database connection
	 */
	function db_connect( $query = '' ) {
		if ( empty( $query ) )
			return false;

		$this->last_table = $this->table = $this->get_table_from_query($query);

		if ( isset($this->hyper_tables[$this->table]) ) {
			$dataset = $this->hyper_tables[$this->table];
			$this->callback_result = null;
		} elseif ( null !== $this->callback_result = $this->run_callbacks( 'dataset', $query ) ) {
			if ( is_array($this->callback_result) )
				extract( $this->callback_result, EXTR_OVERWRITE );
			else
				$dataset = $this->callback_result;
		}

		if ( ! isset($dataset) )
			$dataset = 'global';

		if ( ! $dataset )
			return $this->bail("Unable to determine which dataset to query. ($this->table)");
		else
			$this->dataset = $dataset;

		$this->run_callbacks( 'dataset_found', $dataset );

		if ( empty( $this->hyper_servers ) ) {
			if ( $this->dbh instanceof mysqli )
				return $this->dbh;
			if (
				!defined('DB_HOST')
				|| !defined('DB_USER')
				|| !defined('DB_PASSWORD')
				|| !defined('DB_NAME') )
				return $this->bail("We were unable to query because there was no database defined.");
			$persist = $this->persistent ? 'p:' : '';
			$host_parts = explode( ':', DB_HOST );
			$host = array_shift( $host_parts );
			$port = array_shift( $host_parts );
			$port = $port ? : 3306;
			$this->dbh = @mysqli_connect($persist.$host, DB_USER, DB_PASSWORD, '', $port);
			if ( !( $this->dbh instanceof mysqli ) )
				return $this->bail("We were unable to connect to the database. (DB_HOST)");
			if ( ! mysqli_select_db( $this->dbh, DB_NAME ) )
				return $this->bail("We were unable to select the database.");
			if ( ! empty( $this->charset ) ) {
				$_collate = ! empty( $this->collate ) ? $this->collate : null;
				$this->set_charset( $this->dbh, $this->charset, $_collate );
			}

			return $this->dbh;
		}

		// Determine whether the query must be sent to the master (a writable server)
		if ( !empty( $use_master ) || $this->srtm === true || isset($this->srtm[$this->table]) ) {
			$use_master = true;
		} elseif ( $is_write = $this->is_write_query($query) ) {
			$use_master = true;
			if ( is_array($this->srtm) )
				$this->srtm[$this->table] = true;
		} elseif ( !isset($use_master) && is_array($this->srtm) && !empty($this->srtm) ) {
			// Detect queries that have a join in the srtm array.
			$use_master = false;
			$query_match = substr( $query, 0, 1000 );
			foreach ( $this->srtm as $key => $value ) {
				if ( false !== stripos( $query_match, $key ) ) {
					$use_master = true;
					break;
				}
			}
		} else {
			$use_master = false;
		}

		if ( $use_master ) {
			$this->dbhname = $dbhname = $dataset . '__w';
			$operation = 'write';
		} else {
			$this->dbhname = $dbhname = $dataset . '__r';
			$operation = 'read';
		}

		// Try to reuse an existing connection
		while ( isset( $this->dbhs[$dbhname] ) && ( $this->dbhs[$dbhname] instanceof mysqli ) ) {
			// Find the connection for incrementing counters
			foreach ( array_keys($this->db_connections) as $i )
				if ( $this->db_connections[$i]['dbhname'] == $dbhname )
					$conn =& $this->db_connections[$i];

			if ( isset($server['name']) ) {
				$name = $server['name'];
				// A callback has specified a database name so it's possible the existing connection selected a different one.
				if ( $name != $this->used_servers[$dbhname]['name'] ) {
					if ( !mysqli_select_db( $this->dbhs[$dbhname], $name ) ) {
						// this can happen when the user varies and lacks permission on the $name database
						if ( isset( $conn['disconnect (select failed)'] ) )
							++$conn['disconnect (select failed)'];
						else
							$conn['disconnect (select failed)'] = 1;

						$this->disconnect($dbhname);
						break;
					}
					$this->used_servers[$dbhname]['name'] = $name;
				}
			} else {
				$name = $this->used_servers[$dbhname]['name'];
			}

			$this->current_host = $this->dbh2host[$dbhname];

			// Keep this connection at the top of the stack to prevent disconnecting frequently-used connections
			if ( $k = array_search($dbhname, $this->open_connections) ) {
				unset($this->open_connections[$k]);
				$this->open_connections[] = $dbhname;
			}

			$this->last_used_server = $this->used_servers[$dbhname];
			$this->last_connection = compact('dbhname', 'name');

			if ( !mysqli_ping($this->dbhs[$dbhname]) ) {
				if ( isset( $conn['disconnect (ping failed)'] ) )
					++$conn['disconnect (ping failed)'];
				else
					$conn['disconnect (ping failed)'] = 1;

				$this->disconnect($dbhname);
				break;
			}

			if ( isset( $conn['queries'] ) )
				++$conn['queries'];
			else
				$conn['queries'] = 1;

			return $this->dbhs[$dbhname];
		}

		if ( $use_master && defined( "MASTER_DB_DEAD" ) ) {
			return $this->bail("We're updating the database, please try back in 5 minutes. If you are posting to your blog please hit the refresh button on your browser in a few minutes to post the data again. It will be posted as soon as the database is back online again.");
		}

		if ( empty($this->hyper_servers[$dataset][$operation]) )
			return $this->bail("No databases available with $this->table ($dataset)");

		// Put the groups in order by priority
		ksort($this->hyper_servers[$dataset][$operation]);

		// Make a list of at least $this->min_tries connections to try, repeating as necessary.
		$servers = array();
		do {
			foreach ( $this->hyper_servers[$dataset][$operation] as $group => $items ) {
				$keys = array_keys($items);
				shuffle($keys);
				foreach ( $keys as $key )
					$servers[] = compact('group', 'key');
			}

			if ( !$tries_remaining = count( $servers ) )
				return $this->bail("No database servers were found to match the query. ($this->table, $dataset)");

			if ( !isset( $unique_servers ) )
				$unique_servers = $tries_remaining;

		} while ( $tries_remaining < $this->min_tries );

		// Connect to a database server
		do {
			$unique_lagged_slaves = array();
			$success = false;

			foreach ( $servers as $group_key ) {
				--$tries_remaining;

				// If all servers are lagged, we need to start ignoring the lag and retry
				if ( count( $unique_lagged_slaves ) == $unique_servers )
					break;

				// $group, $key
				extract($group_key, EXTR_OVERWRITE);

				// $host, $user, $password, $name, $read, $write [, $lag_threshold, $connect_function, $timeout ]
				extract($this->hyper_servers[$dataset][$operation][$group][$key], EXTR_OVERWRITE);
				$port = null;

				// Split host:port into $host and $port
				if ( strpos($host, ':') )
					list($host, $port) = explode(':', $host);

				// Overlay $server if it was extracted from a callback
				if ( isset($server) && is_array($server) )
					extract($server, EXTR_OVERWRITE);

				// Split again in case $server had host:port
				if ( strpos($host, ':') )
					list($host, $port) = explode(':', $host);

				// Make sure there's always a port number
				if ( empty($port) )
					$port = 3306;

				// Use a default timeout of 200ms
				if ( !isset($timeout) )
					$timeout = 0.2;

				// Get the minimum group here, in case $server rewrites it
				if ( !isset( $min_group ) || $min_group > $group )
					$min_group = $group;

				// Can be used by the lag callbacks
				$this->lag_cache_key = "$host:$port";
				$this->lag_threshold = isset($lag_threshold) ? $lag_threshold : $this->default_lag_threshold;

				// Check for a lagged slave, if applicable
				if ( !$use_master && !$write && !isset( $ignore_slave_lag )
					&& isset($this->lag_threshold) && !isset( $server['host'] )
					&& ( $lagged_status = $this->get_lag_cache() ) === HYPERDB_LAG_BEHIND
				) {
					// If it is the last lagged slave and it is with the best preference we will ignore its lag
					if ( !isset( $unique_lagged_slaves["$host:$port"] )
						&& $unique_servers == count( $unique_lagged_slaves ) + 1
						&& $group == $min_group )
					{
						$this->lag_threshold = null;
					} else {
						$unique_lagged_slaves["$host:$port"] = $this->lag;
						continue;
					}
				}

				$this->timer_start();

				// Connect if necessary or possible
				$tcp = null;
				if ( $use_master || !$tries_remaining || !$this->check_tcp_responsiveness
					|| true === $tcp = $this->check_tcp_responsiveness($host, $port, $timeout) )
				{
					$persist = $this->persistent ? 'p:' : '';
					$this->dbhs[$dbhname] = @mysqli_connect( "$persist$host", $user, $password, '', $port );
				} else {
					$this->dbhs[$dbhname] = false;
				}

				$elapsed = $this->timer_stop();

				if ( $this->dbhs[$dbhname] instanceof mysqli ) {
					/**
					 * If we care about lag, disconnect lagged slaves and try to find others.
					 * We don't disconnect if it is the last lagged slave and it is with the best preference.
					 */
					if ( !$use_master && !$write && !isset( $ignore_slave_lag )
						&& isset($this->lag_threshold) && !isset( $server['host'] )
						&& $lagged_status !== HYPERDB_LAG_OK
						&& ( $lagged_status = $this->get_lag() ) === HYPERDB_LAG_BEHIND
						&& !(
							!isset( $unique_lagged_slaves["$host:$port"] )
							&& $unique_servers == count( $unique_lagged_slaves ) + 1
							&& $group == $min_group
						)
					) {
						$success = false;
						$unique_lagged_slaves["$host:$port"] = $this->lag;
						$this->disconnect( $dbhname );
						$this->dbhs[$dbhname] = false;
						$msg = "Replication lag of {$this->lag}s on $host:$port ($dbhname)";
						$this->print_error( $msg );
						continue;
					} elseif ( mysqli_select_db( $this->dbhs[ $dbhname ], $name ) ) {
						$success = true;
						$this->current_host = "$host:$port";
						$this->dbh2host[$dbhname] = "$host:$port";
						$queries = 1;
						$lag = isset( $this->lag ) ? $this->lag : 0;
						$this->last_connection = compact('dbhname', 'host', 'port', 'user', 'name', 'tcp', 'elapsed', 'success', 'queries', 'lag');
						$this->db_connections[] = $this->last_connection;
						$this->open_connections[] = $dbhname;
						break;
					}
				}

				$success = false;
				$this->last_connection = compact('dbhname', 'host', 'port', 'user', 'name', 'tcp', 'elapsed', 'success');
				$this->db_connections[] = $this->last_connection;
				$msg = date( "Y-m-d H:i:s" ) . " Can't select $dbhname - \n";
				$msg .= "'referrer' => '{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}',\n";
				$msg .= "'server' => {$server},\n";
				$msg .= "'host' => {$host},\n";
				if ( $this->dbh instanceof mysqli ) {
					$msg .= "'error' => " . mysqli_error( $this->dbh ) . ",\n";
					$msg .= "'errno' => " . mysqli_errno( $this->dbh ) . ",\n";
				}
				$msg .= "'tcp_responsive' => " . ( $tcp === true ? 'true' : $tcp ) . ",\n";
				$msg .= "'lagged_status' => " . ( isset( $lagged_status ) ? $lagged_status : HYPERDB_LAG_UNKNOWN );

				$this->print_error( $msg );
			}

			if ( !$success || !isset($this->dbhs[$dbhname]) || !( $this->dbhs[$dbhname] instanceof mysqli ) ) {
				if ( !isset( $ignore_slave_lag ) && count( $unique_lagged_slaves ) ) {
					// Lagged slaves were not used. Ignore the lag for this connection attempt and retry.
					$ignore_slave_lag = true;
					$tries_remaining = count( $servers );
					continue;
				}

				$error_details = array(
					'host' => $host,
					'port' => $port,
					'operation' => $operation,
					'table' => $this->table,
					'dataset' => $dataset,
					'dbhname' => $dbhname
				);
				$this->run_callbacks( 'db_connection_error', $error_details );

				return $this->bail( "Unable to connect to $host:$port to $operation table '$this->table' ($dataset)" );
			}

			break;
		} while ( true );

		if ( !isset( $charset ) )
			$charset = null;

		if ( !isset( $collate ) )
			$collate = null;

		$this->set_charset($this->dbhs[$dbhname], $charset, $collate);

		$this->dbh = $this->dbhs[$dbhname]; // needed by $wpdb->_real_escape()

		$this->last_used_server = compact('host', 'user', 'name', 'read', 'write');

		$this->used_servers[$dbhname] = $this->last_used_server;

		while ( !$this->persistent && count($this->open_connections) > $this->max_connections ) {
			$oldest_connection = array_shift($this->open_connections);
			if ( $this->dbhs[$oldest_connection] != $this->dbhs[$dbhname] )
				$this->disconnect($oldest_connection);
		}

		return $this->dbhs[$dbhname];
	}


	/*
 	 * Force addslashes() for the escapes.
 	 *
 	 * HyperDB makes connections when a query is made
 	 * which is why we can't use mysqli_real_escape_string() for escapes.
 	 * This is also the reason why we don't allow certain charsets. See set_charset().
 	 */
    function _real_escape( $string ) {
		return addslashes( $string );
	}

	/**
	 * Sets the connection's character set.
	 * @param resource $dbh     The resource given by mysqli_connect
	 * @param string   $charset The character set (optional)
	 * @param string   $collate The collation (optional)
	 */
	function set_charset($dbh, $charset = null, $collate = null) {
		if ( ! isset( $charset ) )
			$charset = $this->charset;
		if ( ! isset( $collate ) )
			$collate = $this->collate;

		if ( ! in_array( strtolower( $charset ), array( 'utf8mb4', 'utf8', 'latin1' ) ) )
			wp_die( "$charset charset isn't supported in HyperDB for security reasons" );

		if ( $this->has_cap( 'collation', $dbh ) && !empty( $charset ) ) {
			if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset', $dbh ) ) {
				mysqli_set_charset( $dbh, $charset );
				$this->real_escape = true;
			} else {
				$query = $this->prepare( 'SET NAMES %s', $charset );
				if ( ! empty( $collate ) )
					$query .= $this->prepare( ' COLLATE %s', $collate );
				mysqli_query( $dbh, $query );
			}
		}
	}

	/**
	 * Disconnect and remove connection from open connections list
	 * @param string $tdbhname
	 */
	function disconnect($dbhname) {
		if ( $k = array_search($dbhname, $this->open_connections) )
			unset($this->open_connections[$k]);

		if ( $this->dbhs[$dbhname] instanceof mysqli )
			mysqli_close($this->dbhs[$dbhname]);

		unset($this->dbhs[$dbhname]);
	}

	/**
	 * Kill cached query results
	 */
	function flush() {
		$this->last_error = '';
		$this->num_rows = 0;
		parent::flush();
	}

	/**
	 * Basic query. See docs for more details.
	 * @param string $query
	 * @return int number of rows
	 */
	function query($query) {
		// some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
		if ( function_exists('apply_filters') )
			$query = apply_filters('query', $query);

		// initialise return
		$return_val = 0;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		if ( preg_match('/^\s*SELECT\s+FOUND_ROWS(\s*)/i', $query) && ($this->last_found_rows_result instanceof mysqli_result) ) {
			$this->result = $this->last_found_rows_result;
			$elapsed = 0;
		} else {
			$this->dbh = $this->db_connect( $query );

			if ( !( $this->dbh instanceof mysqli ) )
				return false;

			$this->timer_start();
			$this->result = mysqli_query( $this->dbh, $query );
			$elapsed = $this->timer_stop();
			++$this->num_queries;

			if ( preg_match('/^\s*SELECT\s+SQL_CALC_FOUND_ROWS\s/i', $query) ) {
				if ( false === strpos($query, "NO_SELECT_FOUND_ROWS") ) {
					$this->timer_start();
					$this->last_found_rows_result = mysqli_query( $this->dbh, "SELECT FOUND_ROWS()" );
					$elapsed += $this->timer_stop();
					++$this->num_queries;
					$query .= "; SELECT FOUND_ROWS()";
				}
			} else {
				$this->last_found_rows_result = null;
			}

			if ( $this->save_queries ) {
				if ( is_callable($this->save_query_callback) )
					$this->queries[] = call_user_func_array( $this->save_query_callback, array( $query, $elapsed, $this->save_backtrace ? debug_backtrace( false ) : null, &$this ) );
				else
					$this->queries[] = array( $query, $elapsed, $this->get_caller() );
			}
		}

		// If there is an error then take note of it
		if ( $this->last_error = mysqli_error($this->dbh) ) {
			$this->print_error($this->last_error);
			return false;
		}

		if ( preg_match("/^\\s*(insert|delete|update|replace|alter) /i",$query) ) {
			$this->rows_affected = mysqli_affected_rows($this->dbh);

			// Take note of the insert_id
			if ( preg_match("/^\\s*(insert|replace) /i",$query) ) {
				$this->insert_id = mysqli_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$i = 0;
			$this->col_info = array();
			while ($i < @mysqli_num_fields($this->result)) {
				$this->col_info[$i] = @mysqli_fetch_field($this->result);
				$i++;
			}
			$num_rows = 0;
			$this->last_result = array();
			while ( $row = @mysqli_fetch_object($this->result) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			@mysqli_free_result($this->result);

			// Log number of rows the query returned
			$this->num_rows = $num_rows;

			// Return number of rows selected
			$return_val = $this->num_rows;
		}

		return $return_val;
	}

	/**
	 * Whether or not MySQL database is at least the required minimum version.
	 * The additional argument allows the caller to check a specific database.
	 *
	 * @since 2.5.0
	 * @uses $wp_version
	 *
	 * @return WP_Error
	 */
	function check_database_version( $dbh_or_table = false ) {
		global $wp_version;
		// Make sure the server has MySQL 4.1.2
		$mysql_version = preg_replace( '|[^0-9\.]|', '', $this->db_version( $dbh_or_table ) );
		if ( version_compare($mysql_version, '4.1.2', '<') )
			return new WP_Error( 'database_version', sprintf(__('<strong>ERROR</strong>: WordPress %s requires MySQL 4.1.2 or higher'), $wp_version) );
	}

	/**
	 * This function is called when WordPress is generating the table schema to determine wether or not the current database
	 * supports or needs the collation statements.
	 * The additional argument allows the caller to check a specific database.
	 * @return bool
	 */
	function supports_collation( $dbh_or_table = false ) {
		return $this->has_cap( 'collation', $dbh_or_table );
	}

	/**
	 * Generic function to determine if a database supports a particular feature
	 * The additional argument allows the caller to check a specific database.
	 * @param string $db_cap the feature
	 * @param false|string|resource $dbh_or_table the databaese (the current database, the database housing the specified table, or the database of the mysql resource)
	 * @return bool
	 */
	function has_cap( $db_cap, $dbh_or_table = false ) {
		$version = $this->db_version( $dbh_or_table );

		switch ( strtolower( $db_cap ) ) :
		case 'collation' :
		case 'group_concat' :
		case 'subqueries' :
			return version_compare($version, '4.1', '>=');
		case 'set_charset' :
			return version_compare($version, '5.0.7', '>=');
		endswitch;

		return false;
	}

	/**
	 * The database version number
	 * @param false|string|resource $dbh_or_table the databaese (the current database, the database housing the specified table, or the database of the mysql resource)
	 * @return false|string false on failure, version number on success
	 */
	function db_version( $dbh_or_table = false ) {
		if ( !$dbh_or_table && $this->dbh )
			$dbh =& $this->dbh;
		elseif ( $dbh_or_table instanceof mysqli )
			$dbh =& $dbh_or_table;
		else
			$dbh = $this->db_connect( "SELECT FROM $dbh_or_table $this->users" );

		if ( $dbh )
			return preg_replace('/[^0-9.].*/', '', mysqli_get_server_info( $dbh ));
		return false;
	}

	/**
	 * Get the name of the function that called wpdb.
	 * @return string the name of the calling function
	 */
	function get_caller() {
		// requires PHP 4.3+
		if ( !is_callable('debug_backtrace') )
			return '';

		$bt = debug_backtrace( false );
		$caller = '';

		foreach ( (array) $bt as $trace ) {
			if ( isset($trace['class']) && is_a( $this, $trace['class'] ) )
				continue;
			elseif ( !isset($trace['function']) )
				continue;
			elseif ( strtolower($trace['function']) == 'call_user_func_array' )
				continue;
			elseif ( strtolower($trace['function']) == 'apply_filters' )
				continue;
			elseif ( strtolower($trace['function']) == 'do_action' )
				continue;

			if ( isset($trace['class']) )
				$caller = $trace['class'] . '::' . $trace['function'];
			else
				$caller = $trace['function'];
			break;
		}
		return $caller;
	}

	/**
	 * Check the responsiveness of a tcp/ip daemon
	 * @return (bool) true when $host:$post responds within $float_timeout seconds, else (bool) false
	 */
	function check_tcp_responsiveness( $host, $port, $float_timeout ) {
		if ( function_exists( 'apc_store' ) ) {
			$use_apc = true;
			$apc_key = "{$host}{$port}";
			$apc_ttl = 10;
		} else {
			$use_apc = false;
		}

		if ( $use_apc ) {
			$cached_value = apc_fetch( $apc_key );
			switch ( $cached_value ) {
				case 'up':
					$this->tcp_responsive = 'true';
					return true;
				case 'down':
					$this->tcp_responsive = 'false';
					return false;
			}
		}

		$socket = @ fsockopen( $host, $port, $errno, $errstr, $float_timeout );
		if ( $socket === false ) {
			if ( $use_apc )
				apc_store( $apc_key, 'down', $apc_ttl );
			return "[ > $float_timeout ] ($errno) '$errstr'";
		}

		fclose( $socket );

		if ( $use_apc )
			apc_store( $apc_key, 'up', $apc_ttl );

		return true;
	}

	function get_lag_cache() {
		$this->lag = $this->run_callbacks( 'get_lag_cache' );

		return $this->check_lag();
	}

	function get_lag() {
		$this->lag = $this->run_callbacks( 'get_lag' );

		return $this->check_lag();
	}

	function check_lag() {
		if ( $this->lag === false )
			return HYPERDB_LAG_UNKNOWN;

		if ( $this->lag > $this->lag_threshold )
			return HYPERDB_LAG_BEHIND;

		return HYPERDB_LAG_OK;
	}

	// Helper functions for configuration

} // class hyperdb

$wpdb = new hyperdb();

require( DB_CONFIG_FILE );

?>
