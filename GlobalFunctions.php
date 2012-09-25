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

	$msg = number_format( kfTimeSince( KR_MICROSECONDS ), 7 ) . ': ' . $msg;
	if ( $echo == KR_LOG_ECHO ) {
		echo $msg;
	}
	return $kgConf->setRunlog( $current . '> ' . $msg . "\n" . $kgConf->getRunlog() );
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
function kfEscapeHTML( $str ) {
	return htmlentities( $str, ENT_QUOTES, 'UTF-8' );
}

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
 * URI interaction
 * -------------------------------------------------
 */
// Returns true or fallback
function getParamExists( $key, $fallback = false, $map = null ) {
	if ( is_null( $map ) ) { $map = $_GET; }

	if ( array_key_exists( $key, $map ) ) {
		return true;
	} else {
		return false;
	}
}

// Returns 1 or fallback
function getParamBool( $key, $fallback = 0, $map = null ) {
	if ( is_null( $map ) ) { $map = $_GET; }

	if ( array_key_exists( $key, $map ) ) {
		if ( $map[$key] == '1' ) {
			return 1;
		} else {
			return $fallback;
		}
	} else {
		return $fallback;
	}
}

// Returns 'on' or fallback
function getParamCheck( $key, $fallback = false, $map = null ) {
	if ( is_null( $map ) ) { $map = $_GET; }

	if ( array_key_exists( $key, $map ) ) {
		if ( $map[$key] == 'on' ) {
			return 'on';
		} else {
			return $fallback;
		}
	} else {
		return $fallback;
	}
}

// Returns intval of parameter value, fallback if nothing
function getParamInt( $key, $fallback = 0, $map = null ) {
	if ( is_null( $map ) ) { $map = $_GET; }

	if ( array_key_exists( $key, $map ) ) {
		if ( !empty( $map[$key] ) ) {
			return intval( $map[$key] );
		} else {
			return $fallback;
		}
	} else {
		return $fallback;
	}
}

// Returns strval of parameter value, fallback if nothing
function getParamVar( $key, $fallback = '', $map = null ) {
	if ( is_null( $map ) ) { $map = $_GET; }

	if ( array_key_exists( $key, $map ) ) {
		if ( isset( $map[$key] ) ) {
			return strval( $map[$key] );
		} else {
			return $fallback;
		}
	} else {
		return $fallback;
	}
}

// Relay functions for POST
// @deprecated, use Request Class
function postParamExists($key,$fallback = false){ return getParamExists($key,$fallback,$_POST); }
function postParamBool($key,$fallback = 0){ return getParamBool($key, $fallback, $_POST); }
function postParamCheck($key,$fallback = false){ return getParamCheck($key, $fallback, $_POST); }
function postParamInt($key,$fallback = 0){ return getParamInt($key, $fallback, $_POST); }
function postParamVar($key,$fallback = ''){ return getParamVar($key, $fallback, $_POST); }

/**
 * Database related functions
 * -------------------------------------------------
 */
function kfDbUsername(){
	global $kgConf;

	// Cache
	if ( is_string( $kgConf->getDbUsername() ) ) {
		return $kgConf->getDbUsername();
	} else {
		// Read from file and cache it in GlobalConfig
		$mycnf = parse_ini_file( $kgConf->getlocalHome() . '/.my.cnf' );
		$kgConf->setDbUsername( $mycnf['user'] );
		unset( $mycnf );
		return $kgConf->getDbUsername();
	}

}

function kfDbPassword(){
	global $kgConf;

	// Cache
	if ( is_string( $kgConf->getDbPassword() ) ) {
		return $kgConf->getDbPassword();
	} else {
		// Read from file and cache it in GlobalConfig
		$mycnf = parse_ini_file( $kgConf->getlocalHome() . '/.my.cnf' );
		$kgConf->setDbPassword( $mycnf['password'] );
		unset( $mycnf );
		return $kgConf->getDbPassword();
	}

}

/**
 * Database interaction functions
 * -------------------------------------------------
 */
// Get an array of objects for all results from the mysql_query() call
function mysql_object_all( $result ) {
	$all = array();
	while ( $all[] = mysql_fetch_object($result) ){ }
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

function kfConnectToolserverDB() {
	global $kgConf;
	return $kgConf->setDbConnect( kfConnectRRServerByHostname( 'sql.toolserver.org', 'toolserver' ), 'sql.toolserver.org' );
}

// @param $query String: A SELECT query string
// @return array of objects from the result
function kfDoSelectQueryRaw( $query, $connect = null ) {
	global $kgConf;
	if ( is_null( $connect ) ) {
		$connect = $kgConf->getDbConnect();
	}
	if ( !$connect ) {
		return false;
	}
	$return = mysql_query( $query, $connect );
	if ( $return ) {
		return mysql_object_all( $return );
	} else {
		kfLog( $query, __FUNCTION__ );
		return mysql_object_all( $return );
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
		$selectWiki = _html('alws-selectwiki', 'krinkle');
		$mostUsed = _html('alws-group-mustused', 'krinkle');
		$allWikisAZ = _html('alws-group-allaz', 'krinkle');
	} else {
		$selectWiki = '(select wiki)';
		$mostUsed = 'Most used wikis';
		$allWikisAZ = 'All wikis alphabetically';
	}

	// Spit it out
	$html = Html::openElement( 'select' );
	$html = '<select id="' . $options['name'] . '" name="' . $options['name'] . '"><option value="">' . $selectWiki . '</option>';
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
			$html = Html::element( 'label', array( 'for' => $options['name'] ), _( 'alws-label', 'krinkle' ) ) . $html;
		} else {
			$html = Html::element( 'label', array( 'for' => $options['name'] ), 'Wikis' ) . $html;
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

// Primitive html building
// @deprecated, use Html Class instead
function kfTag( $str, $wrapTag = 0, $attributes = array() ) {
	if ( is_string( $str ) ) {
		if ( is_string( $wrapTag ) ) {
			return Html::element( $wrapTag, $attributes, $str );
		} else {
			return $str;
		}
	} else {
		return '';
	}
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
	return preg_replace( "/[^][.\\'\\\"_A-Za-z0-9]/", '', $str );
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
				header( 'Content-Type: text/javascript; charset=utf-8', /*replace=*/true );
				echo json_encode( $data );
				die;
			} else {
				header( 'Content-Type: application/json; charset=utf-8', /*replace=*/true );

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
			header( 'Content-Type: text/plain; charset=utf-8', /*replace=*/true );
			echo 'Invalid format.';
			exit;
	}

}
function kfApiFormats(){
	return array( 'php', 'json', 'dump', 'print' );
}
function kfApiFormatHTML( $text ) {

	// Escape everything first for full coverage
	$text = htmlspecialchars( $text );

	// encode all comments or tags as safe blue strings
	$text = preg_replace( '/\&lt;(!--.*?--|.*?)\&gt;/', '<span style="color: blue;">&lt;\1&gt;</span>', $text );

	/**
	 * Temporary fix for bad links in help messages. As a special case,
	 * XML-escaped metachars are de-escaped one level in the help message
	 * for legibility. Should be removed once we have completed a fully-HTML
	 * version of the help message.
	 */
	if ( true ) {
		$text = preg_replace( '/&amp;(amp|quot|lt|gt);/', '&\1;', $text );
	}

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
				'checkout-cr-rev' => "http://www.mediawiki.org/wiki/Special:Code/MediaWiki/$coRev",
				'checkout-url' => $coUrl,
				'repo-url' => $repoUrl,
				'directory-path' => str_replace( $repoUrl, '', $coUrl ),
				'directory-rev' => $dirRev,
				'directory-up-date' => trim( $lines[9] ),
				'directory-cr-rev' => "http://www.mediawiki.org/wiki/Special:Code/MediaWiki/$dirRev",
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
 * @param string $path (optional) Full path to where the git repository is.
 * By default it will assume the current directory is already the git repository.
 *
 * @param string $branch (optional) Branch to reset to, defaults to 'master'.
 * Set to null to not switch branches (only cleanup and reset to HEAD).
 *
 * @param bool $mayUnlock (optional) Whether or not it may remove
 * an index.lock file if that is blocking the reset.
 *
 * @return bool|string: Boolean false on failure, or a string
 * with the output of the commands.
 */
function kfGitCleanReset( $otherPath = null, $branch = 'master', $mayUnlock = false ) {
	$orgPath = __DIR__;

	if ( $otherPath ) {
		if ( !is_dir( $otherPath ) ) {
			return false;
		}

		// Navigate to the repo so we can execute the git commands
		chdir( $path );
	}

	$out = '';
	$cmds = array(
		'git clean -d -x --force',
		'git reset --hard HEAD',
	);
	if ( $mayUnlock ) {
		array_unshift( $cmds, 'rm -f .git/index.lock' );
	}
	if ( $branch ) {
		array_push( $cmds, 'git checkout ' . kfEscapeShellArg( $branch ) );
	}

	foreach ( $cmds as $cmd ) {
		$out .= "$ $cmd\n";
		$out .= kfShellExec( $cmd ) . "\n";
	}

	// Go back to the original dir if we changed it
	if ( $otherPath ) {
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
 * @param Array $wikiData - all data (dbname, sitename, url, apiurl etc.) for the selected
 *							  wiki (from function kfGetWikiDataFromDBName() ).
 * @param Array $params   - api query (eg. array( 'action' => 'query' etc. ) ).
 * @return Array  		- unserialized result of the API.
 * @return Boolean false  - ... if something went wrong.
 */
function kfQueryWMFAPI( $wikiData , $params ) {
	if ( !is_array( $wikiData ) || !is_array( $params ) || !isset( $wikiData['apiurl'] ) ) {
		return false;
	}
	$params['format'] = 'php';
	$return = file_get_contents( $wikiData['apiurl'] . '?' . http_build_query( $params ) );
	if ( $return === false ) {
		return false;
	}
	$return = unserialize( $return );
	if ( !is_array( $return ) ) {
		return false;
	}
	if ( isset( $return['error'] ) ) {
		return false;
	}
	return $return;
}
// ^ [Krinkle] 2011-01-05 http://lists.wikimedia.org/pipermail/toolserver-l/2011-February/003873.html
