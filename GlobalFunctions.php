<?php
/**
 * GlobalFunctions.php :: Common functions globally available.
 * Created on January 15th, 2011
 *
 * @since 0.1
 * @author Krinkle <krinklemail@gmail.com>, 2010 - 2012
 *
 * @package KrinkleToolsCommon
 * @license Public domain, WTFPL
 */
require_once( __DIR__ . '/GlobalDefinitions.php' );
require_once( __DIR__ . '/GlobalConfig.php' );

// Never twice, but if not done already, make sure GlobalConfig is initiated
if ( !is_object( $kgConf ) ) {
	$kgConf = new GlobalConfig();
}

/**
 * Debug functions
 * -------------------------------------------------
 */
function kfLog( $msg = '', $current = '', $echo = KR_LOG_RETURN ) {
	global $kgConf;

	$msg =
		number_format( kfTimeSince( KR_MICROSECONDS ), 7 ) . ': '
		. $current . '> '
		. $msg;
	if ( $echo == KR_LOG_ECHO ) {
		echo $msg;
	}
	return $kgConf->setRunlog( $msg . "\n" . $kgConf->getRunlog() );
}

// @param $echo Boolean: KR_LOG_ECHO or KR_LOG_RETURN
// @param $mode Integer: KR_FLUSH_CLEARTEXT, KR_FLUSH_HTMLPRE
function kfLogFlush( $echo = KR_LOG_ECHO, $mode = KR_FLUSH_HTMLPRE ) {
	global $kgConf;
	$kgConf->setRunlogFlushCount( $kgConf->getRunlogFlushCount()+1 );

	// Generate output
	$output = '------- [ KrinkleTool Runlog | Flush ' . $kgConf->getRunlogFlushCount()
		. ' @ ' . date( 'r' )
		. " ]----------\n"
		. $kgConf->getRunlog()
		. "\n";

	// Reset log
	$kgConf->setRunlog( '' );

	switch( $mode ) {
		case KR_FLUSH_HTMLPRE:
			$output = '<pre>' . htmlspecialchars( $output ) . '</pre>';
			break;
		case KR_FLUSH_CLEARTEXT:
			// Nothing
			break;
		default:
			// Nothing

	}

	// Echo or return
	if ( $echo === KR_LOG_ECHO ) {
		echo $output;
		return true;
	} else {
		return $output;
	}

}

// Time since config was initiated
// @param $micro Interger: KR_MICROSECONDS or KR_SECONDS
function kfTimeSince( $detail = KR_MICROSECONDS ) {
	global $kgConf;
	if ( $detail == KR_MICROSECONDS ) {
		return microtime(true) - $kgConf->getStartTimeMicro();
	} else {
		return time() - $kgConf->getStartTime();
	}
}


/**
 * String & integer functions
 * -------------------------------------------------
 */

function kfStripStr( $str ) {
	return htmlspecialchars( addslashes( strip_tags( trim( $str ) ) ) );
}

function kfEscapeRE( $str ) {
	return preg_quote( $str, KR_REGEX_DELIMITER );
}

function kfStrLastReplace( $search, $replace, $subject ) {
	return substr_replace( $subject, $replace, strrpos( $subject, $search ), strlen( $search ) );
}

function is_odd( $num ) {
	return (bool)($num % 2 );
}


/**
 * UI Message wrappers
 * -------------------------------------------------
 */
function kfMsgBlock( $message /* [, $kind [, $level ] ] */ ) {
	$class = func_get_args();
	array_shift( $class ); // remove $message
	$class[] = 'basetool-msg';
	$class[] = 'ns';
	return Html::rawElement( 'div', array( 'class' => implode( ' ', $class ) ), $message );
}

/**
 * Database related functions
 * -------------------------------------------------
 */
function kfDbUsername(){
	global $kgConf;

	return $kgConf->getDbUsername();
}

function kfDbPassword(){
	global $kgConf;

	$kgConf->getDbPassword();
}

/**
 * Database interaction functions
 * -------------------------------------------------
 */
// Get an array of objects for all results from the mysql_query() call
function mysql_object_all( $result ) {
	$all = array();
	while ( $all[] = mysql_fetch_object($result) ) {
	}
	unset( $all[count( $all )-1] );
	return $all;
}

// Function to sanitize values. Prevents SQL injection
function mysql_clean( $str ) {
	global $kgConf;

	$str = @trim( $str );
	if ( get_magic_quotes_gpc() ) {
		$str = stripslashes( $str );
	}
	if ( $kgConf->getDbConnect() ) {
		return mysql_real_escape_string( $str, $kgConf->getDbConnect() );
	}
	return mysql_real_escape_string( $str );
}


/**
 * Checks if the hostname looks real
 * Creates a connection to the server and returns the mysql link resource
 * Optionally selects the correct database as well.
 *
 * @return Mixed: Mysql link or boolean false
 */
function kfConnectRRServerByHostname( $hostname = null, $dbname = false /* optional */,
 $setAsMainConnect = KR_KEEP_PRIM_DBCON /* optional */ ) {
	global $kgConf;

	// Make sure the input is valid
	if (	!is_string( $hostname )
		||	substr( $hostname, -15, 15 ) !== '.toolserver.org'
		||	kfStripStr( strtolower( $hostname ) ) !== $hostname
		||	substr_count ( $hostname, 'toolserver.org' ) !== 1
		) {
		kfLog( "Invalid hostname ('$hostname') given.", __FUNCTION__ );
		return false;
	}

	$dbConnect = mysql_connect( $hostname, kfDbUsername(), kfDbPassword() );
	if ( !$dbConnect ) {
		return false;
	}

	if ( $dbname ) {
		$dbSelect = mysql_select_db( $dbname, $dbConnect );
		if ( !$dbSelect ) {
			return false;
		}
	}
	if ( $setAsMainConnect == KR_SET_AS_PRIM_DBCON ) {
		return $kgConf->setDbConnect( $dbConnect, $hostname );
	} else {
		return $dbConnect;
	}
}

/**
 * Checks if the databasename looks real
 * Sets the main database connection if everything is good
 *
 * @return Boolean: True if database connection went fine
 */
function kfConnectRRServerByDBName( $dbname = false ) {
	global $kgConf;

	// Make sure the input is valid
	// Only accepts lowercase strings that only contain
	// '_p' once and at the end of the string.
	if (	!is_string( $dbname )
		||	substr( $dbname, -2, 2 ) !== '_p'
		||	kfStripStr( strtolower( $dbname ) ) !== $dbname
		||	substr_count ( $dbname, '_p' ) !== 1
		) {
		kfLog( "Invalid dbname ('$dbname') given.", __FUNCTION__ );
		return false;
	}

	$tsSqlSubdomain = str_replace( '_p', '-p', $dbname );
	$hostname = $tsSqlSubdomain . '.rrdb.toolserver.org';

	return $kgConf->setDbConnect( kfConnectRRServerByHostname( $hostname, $dbname ), $hostname );
}

/**
 * @param $query string: A SELECT query string
 * @param $connect [optional]
 * @param $origin [optional]
 * @return Array of objects from the result
 */
function kfDoSelectQueryRaw( $query, $connect = null, $origin = null ) {
	global $kgConf;

	if ( is_null( $connect ) || is_string( $connect ) ) {
		$origin = is_string( $connect ) ? $connect : __FUNCTION__;
		$origin .= "@{$kgConf->dbConnectHostname}";
		$connect = $kgConf->getDbConnect();
	} else {
		$origin = is_string( $origin ) ? $origin : __FUNCTION__;
	}
	if ( !$connect ) {
		return false;
	}
	$result = mysql_query( "/* $origin */ $query", $connect );
	if ( $result ) {
		$rows = mysql_object_all( $result );
		mysql_free_result( $result );
		return $rows;
	} else {
		kfLog( mysql_error() . "\nQuery:\n" . $query, $origin );
		return array();
	}
}

/**
 * Opens up half a dozen connections (one for each database server)
 * and stores them in GlobalConfig by S# ids.
 *
 * Note: Does not include sql.toolserver.org
 *
 * Example: Get s2 through $kgConfig->getDbExtraConnect( 'sql-s2-rr' )
 *
 * @return Boolean: True on success
 */
function kfConnectToAllWikiDbServers(){
	global $kgConf;
	$servers = array( 'sql-s1-rr', 'sql-s2-rr', 'sql-s3-rr', 'sql-s4-rr', 'sql-s5-rr', 'sql-s6-rr', 'sql-s7-rr' );
	foreach ( $servers as $server ) {
		$kgConf->setDbExtraConnect( $server, kfConnectRRServerByHostname( $server . '.toolserver.org' ) );
	}
}

// Closes the main and any extra connections
// and removes them from the global config
function kfCloseAllConnections(){
	global $kgConf;
	$connections = $kgConf->getAllDbConnects();
	foreach( $connections as $connect ) {
		if ( is_resource( $connect ) ) mysql_close( $connect );
	}
	$kgConf->dbConnect = null;
	$kgConf->dbConnectName = null;
	$kgConf->dbExtraConnections = array();
	return true;
}

/**
 * Other functions
 * -------------------------------------------------
 */

// Optionally pass a custom connect variable
// Defaults to the main dbConnect (if it's for sql.ts.o) or makes a temporary new connection
function kfGetAllWikiSelect( $options = array(), $sqlToolserverConnect = null ) {
	global $kgConf;

	$isTemp = false;
	if ( is_null( $sqlToolserverConnect ) ) {
		if ( $kgConf->getDbConnect() && $kgConf->dbConnectHostname == 'sql.toolserver.org' ) {
			$sqlToolserverConnect = $kgConf->getDbConnect();
		} else {
			$isTemp = true;
			$sqlToolserverConnect = kfConnectRRServerByHostname( 'sql.toolserver.org', 'toolserver' );
			kfLog( 'Created a temp. connection for kfGetAllWikiSelect.', __FUNCTION__ );
		}
	}

	// Options
	$defaultOptions = array(
		'withLabel' => true,
		'name' => 'wikidb',
		'id' => 'wikidb',
		'current' => '',
		'exclude' => array(),
	);
	$options = array_merge( $defaultOptions, $options );

	// Get wikis
	mysql_select_db( 'toolserver', $sqlToolserverConnect );
	$dbResults = kfDoSelectQueryRaw( 'SELECT * FROM wiki WHERE is_closed = 0', $sqlToolserverConnect );

	// Don't close connections not created by this function
	if ( $isTemp ) {
		mysql_close( $sqlToolserverConnect );
	}
	if ( empty( $dbResults ) ) {
		kfLog( 'Wiki information acquirement failed.', __FUNCTION__ );
		return '';
	}

	// Messages
	if ( !is_null( $kgConf->I18N ) ) {
		$selectWiki = _html( 'alws-selectwiki', 'krinkle' );
		$mostUsed = _html( 'alws-group-mustused', 'krinkle' );
		$allWikisAZ = _html( 'alws-group-allaz', 'krinkle' );
	} else {
		$selectWiki = '(select wiki)';
		$mostUsed = 'Most used wikis';
		$allWikisAZ = 'All wikis alphabetically';
	}

	// Spit it out
	$html = Html::openElement( 'select' );
	$html = '<select id="' . $options['id'] . '" name="' . $options['name'] . '"><option value="">' . $selectWiki . '</option>';
	$outputA = '';
	$outputB = '';
	$selectAttr = ' selected="selected"';
	foreach( $dbResults as $wiki ) {
		if ( !in_array( $wiki->dbname, $options['exclude'] ) ) {
			if ( in_array( $wiki->dbname, array( 'enwiki_p', 'commonswiki_p', 'nlwiki_p', 'dewiki_p', 'eswiki_p' ) ) ) {
				$outputA .=
					'<option value="' . $wiki->dbname . '" '
						 . ( $options['current'] == $wiki->dbname ? $selectAttr : '') . ' >'
					. $wiki->dbname
					. '</option>';
			} else {
				$outputB .=
					'<option value="' . $wiki->dbname . '" '
						 . ( $options['current'] == $wiki->dbname ? $selectAttr : '') . ' >'
					. $wiki->dbname
					. '</option>';
			}
		}
	}
	$html .= '<optgroup label="' . $mostUsed . '">'
		. $outputA . '</optgroup>'
		. '<optgroup label="' . $allWikisAZ . '">'
		. $outputB . '</optgroup>';
	$html .= '</select>';
	if ( $options['withLabel'] ) {
		if ( !is_null( $kgConf->I18N ) ) {
			$html = Html::element( 'label', array( 'for' => $options['id'] ), _( 'alws-label', 'krinkle' ) ) . $html;
		} else {
			$html = Html::element( 'label', array( 'for' => $options['id'] ), 'Wikis' ) . $html;
		}
	}
	return $html;
}

function kfWikiHref( $wikidata, $title, $query = array() ) {
	return $wikidata['url'] . '/w/index.php?' . http_build_query( array_merge (
		array( 'title' => $title ),
		$query
	) );
}

function kfGetWikiDataFromDBName( $dbname ) {

	$connect = kfConnectRRServerByHostname( 'sql.toolserver.org', 'toolserver' );
	if ( !$connect ) {
		return false;
	}

	$dbQuery = " /* LIMIT:15 */
		SELECT
			dbname,
			lang,
			family,
			domain,
			size,
			is_meta,
			is_closed,
			is_multilang,
			is_sensitive,
			root_category,
			server,
			script_path
		FROM wiki
		WHERE dbname='" . mysql_clean( $dbname ) . "'
		ORDER BY size DESC
		LIMIT 1;
	";
	$dbReturn = kfDoSelectQueryRaw( $dbQuery, $connect );

	mysql_close( $connect );

	if ( !is_array( $dbReturn ) || !isset( $dbReturn[0] ) ) {
		return false;
	} else {
		$dbResults = $dbReturn[0];
	}

	return wikiDataFromRow( $dbResults );
}


/**
 * Create the 'data' array as known from my getWikiAPI.
 * getWikiAPI uses this as well.
 *
 * @param $row The row from mysql_fetch_object() of a query like "SELECT * FROM toolserver.wiki WHERE .."
 */
function wikiDataFromRow( $row ) {
	if ( !isset( $row->domain ) && isset( $row->lang ) && isset( $row->family ) ) {
		$row->domain = "{$row->lang}.{$row->family}.org";
	}
	return array_merge( (array)$row, array(
		'wikicode' => substr( $row->dbname, 0, -2 ),
		'localdomain' => kfStrLastReplace( '.org', '', $row->domain ),
		'url' => '//' . $row->domain,
		'canonical_url' => 'http://' . $row->domain,
		'apiurl' => 'http://' . $row->domain . $row->script_path . 'api.php',
	));
}

// Sanatize callback
function kfSanatizeJsCallback( $str ) {
	// Valid: foo.bar_Baz["quux"]['01']
	return preg_replace( "/[^a-zA-Z0-9_\.\]\[\'\"]/", '', $str );
}


/**
 * Function for API modules
 *
 * @param $specialFormat string If $format is set to this format this function will not output
 *  anything and return true. This can be used for a GUI front-end.
 */
function kfApiExport( $data = array( 'krApiExport' => 'Example' ), $format = '', $callback = null, $specialFormat = '' ) {

	if ( $format == $specialFormat ) {
		return true;
	}

	if ( empty( $format ) ) {
		$format = 'php_print';
	}

	switch ( $format ) {
		case 'php':
			header( 'Content-Type: application/vnd.php.serialized; charset=utf-8', /*replace=*/true );
			die( serialize( $data ) );
			break;

		case 'json':
		case 'jsonp':

			// Serve as AJAX object object or JSONP callback
			if ( $callback === null ) {
				header( 'Content-Type: application/json; charset=utf-8', /*replace=*/true );
				echo json_encode( $data );
				die;
			} else {
				header( 'Content-Type: text/javascript; charset=utf-8', /*replace=*/true );

				// Sanatize callback
				$callback = kfSanatizeJsCallback( $callback );
				echo $callback . '(' . json_encode( $data ) .')';
				die;
			}
			break;

		case 'dump':
		case 'php_dump':

			// No text/html due to IE7 bug
			header( 'Content-Type: text/text; charset=utf-8', /*replace=*/true );
			var_dump( $data );
			die;
			break;

		case 'text': // for compatiblity with MediaWiki
		case 'print':
		case 'php_print':

			header( 'Content-Type: text/html; charset=utf-8', /*replace=*/true );
			echo '<pre>' . htmlspecialchars( print_r( $data, true ) ) . '</pre>';
			die;
			break;

		default:
			// HTTP 400 Bad Request
			http_response_code( 400 );
			header( 'Content-Type: text/plain; charset=utf-8', /*replace=*/true );
			echo 'Invalid format.';
			exit;
	}

}
function kfApiFormats() {
	return array( 'php', 'json', 'dump', 'print' );
}
function kfApiFormatHTML( $text ) {

	// Escape everything first for full coverage
	$text = htmlspecialchars( $text );

	// encode all comments or tags as safe blue strings
	$text = preg_replace( '/\&lt;(!--.*?--|.*?)\&gt;/', '<span style="color: blue;">&lt;\1&gt;</span>', $text );

	return $text;
}

/**
 * Get array of SVN information about a directory.
 * Requires SVN 1.4 or higher, will return empty array otherwise.
 *
 * $path string (optional)
 */
function kfGetSvnInfo( $path = '' ) {
	if ( substr( $path, -1) != '/' ) {
		$path = "$path/";
	}
	if ( file_exists( $path . '.svn/entries' ) )  {
		$lines = file($path . '.svn/entries');
		// pre 1.4 svn used xml for this file
		if ( !is_numeric( trim( $lines[3] ) ) ) {
			$return = array( 'checkout-rev' => 0 );
		} else {
			$coRev = intval( trim( $lines[3] ) );
			$dirRev = intval( trim( $lines[10] ) );
			$coUrl = trim( $lines[4] );
			$repoUrl = trim( $lines[5] );
			$return = array(
				'checkout-rev' => $coRev,
				'checkout-cr-rev' => "https://www.mediawiki.org/wiki/Special:Code/MediaWiki/$coRev",
				'checkout-url' => $coUrl,
				'repo-url' => $repoUrl,
				'directory-path' => str_replace( $repoUrl, '', $coUrl ),
				'directory-rev' => $dirRev,
				'directory-up-date' => trim( $lines[9] ),
				'directory-cr-rev' => "https:://www.mediawiki.org/wiki/Special:Code/MediaWiki/$dirRev",
			);
		}
		define( 'SVN_REVISION', $return['checkout-rev'] );
		return $return;
	} else {
		return array();
	}
}

/**
 * Get current SVN revision of a directory.
 * Requires SVN 1.4 or higher, will return 0 otherwise.
 *
 * $path string (optional) Path ending with trailing slash.
 */
function kfGetSvnrev( $path = '' ) {
	// Already done ?
	if ( defined('SVN_REVISION') ) {
		return SVN_REVISION;
	}
	// kfGetSvnInfo() sets SVN_VERSION
	if ( kfGetSvnInfo( $path ) && defined('SVN_REVISION') ) {
		return SVN_VERSION;
	}
	define( 'SVN_REVISION', 0);
	return 0;
}

/**
 * @param Array $options (optional):
 * - string dir: Full path to where the git repository is.
 *    By default it will assume the current directory is already the git repository.
 * - string checkout: Will be checked out and reset to its HEAD. Otherwise stays in
 *    the current branch and resets to its head.
 * - unlock: Whether or not it should ensure there is no lock.
 *
 * @return bool|string: Boolean false on failure, or a string
 * with the output of the commands.
 */
function kfGitCleanReset( $options = array() ) {
	$orgPath = __DIR__;

	if ( isset( $options['dir'] ) ) {
		if ( !is_dir( $options['dir'] ) ) {
			return false;
		}

		// Navigate to the repo so we can execute the git commands
		chdir( $options['dir'] );
	}

	$out = '';
	$cmds = array();
	if ( isset( $options['unlock'] ) && $options['unlock'] ) {
		$cmds[] = 'rm -f .git/index.lock';
	}
	$cmds[] = 'git clean -q -d -x -f';
	$cmds[] = 'git reset -q --hard';
	if ( isset( $options['checkout'] ) ) {
		$cmds[] = 'git checkout -q -f ' . kfEscapeShellArg( $options['checkout'] );
	}

	foreach ( $cmds as $cmd ) {
		$out .= "$ $cmd\n";
		$out .= kfShellExec( $cmd ) . "\n";
	}

	// Go back to the original dir if we changed it
	if ( isset( $options['dir'] ) ) {
		chdir( $orgPath );
	}

	return $out;
}

function kfShellExec( $cmd ) {
	$retval = null;

	ob_start();
	passthru( $cmd, $retval );
	$output = ob_get_contents();
	ob_end_clean();

	if ( $retval != 0 ) {
		return "Command failed:\n$cmd\nReturn: $retval $output";
	}

	return $output;
}

function kfEscapeShellArg() {
	$args = func_get_args();
	$args = array_map( 'escapeshellarg', $args );
	return implode( ' ', $args );
}

/**
 * @source php.net/filesize#100097
 */
function kfFormatBytes( $size, $precision = 2 ) {
	$units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
	for ( $i = 0; $size >= 1024 && $i < 4; $i++ ) {
		$size /= 1024;
	}
	return round( $size, 2 ) . $units[$i];
}


// Untill a better solution exists, call the real api or use raw sql
// Most of the time raw sql will be used (which has downsides)
// other times, for more complicated stuff (multiple joins, caching, paging,
// generators for other properties etc.) we lazy-opt for using the live api
// That's what this function does.
/**
 * Get's the query, forces format=php, makes the request,
 * checks for errors, returns the unserialized data from the API or false.
 *
 * [Krinkle] 2011-01-05 https://lists.wikimedia.org/pipermail/toolserver-l/2011-February/003873.html
 *
 * @param Array $wikiData Data for the selected wiki (from kfGetWikiDataFromDBName).
 * @param Array $params Query parameters for MediaWiki API
 * @return Array|bool Unserialized data from the API response, or boolean false
 */
function kfQueryWMFAPI( $wikiData, $params ) {
	if ( !is_array( $wikiData ) || !is_array( $params ) || !isset( $wikiData['apiurl'] ) ) {
		return false;
	}
	$params['format'] = 'json';
	$response = file_get_contents( $wikiData['apiurl'] . '?' . http_build_query( $params ) );
	if ( $response === false ) {
		return false;
	}
	$data = json_decode( $response, /* $assoc */ true );
	if ( !is_array( $data ) ) {
		return false;
	}
	if ( isset( $data['error'] ) ) {
		return false;
	}
	return $data;
}

// php.net/http_response_code
if (!function_exists('http_response_code')) {
	function http_response_code($code = null) {

		if ($code !== null) {
			switch ($code) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default:
					$code = 500;
					$text = 'Unknown-Http-Status-Code';
				break;
			}

			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

			header($protocol . ' ' . $code . ' ' . $text);

			$GLOBALS['http_response_code'] = $code;

		} else {
			$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
		}

		return $code;
	}
}
