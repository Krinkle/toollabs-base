<?php
/**
 * GlobalConfig.php
 * Created on January 16th, 2011
 *
 * @since 0.1
 * @author Krinkle <krinklemail@gmail.com>, 2010 - 2012
 *
 * @package KrinkleToolsCommon
 * @license Public domain, WTFPL
 */
require_once( __DIR__ . '/GlobalDefinitions.php' );

class GlobalConfig {

	/**
	 * Variables that are read-only
	 */
	var $remoteBase = '//toolserver.org/~krinkle';
	var $jQueryVersion = '1.7.2';
	var $jQueryUIVersion = '1.8.19';
	var $fullSimpleDatefmt = 'Y-m-d H:i:s';
	var $fullReadableDatefmt = 'l, j F Y H:i:s';
	var $userAgent = 'KrinkleTools/2.0 (Wikimedia Toolserver; toolserver.org/~krinkle) Contact/krinkle@toolserver.org';
	var $selfClosingTags = array( 'link', 'input', 'br', 'img' );

	var $I18N = null;

	/**
	 * Variables that are modifiable through set*-functions
	 */
	var $confInitiated = false;
	var $debugMode = false;
	var $startTime = null;
	var $startTimeMicro = null;
	var $runlog = '';
	var $runlogFlushCount = 0;
	var $dbUsername = null;
	var $dbPassword = null;
	var $dbConnect = null;
	var $dbConnectHostname = null;
	var $dbExtraConnections = array();


	/**
	 * Initiated certain configuration variables
	 * that depend on other factors (ie. environment, request parameters etc.)
	 *
	 * @return Boolean: True if initiation request was execututed (ie. first call), false on later ones
	 */
	public function initConfig(){
		if ( $this->confInitiated ) {
			return false;
		}

		// Session and time
		global $kgReq;
		session_start();
		date_default_timezone_set( 'UTC' );
		$this->startTime = time();
		$this->startTimeMicro = microtime( true );

		// User agent (required to get data from wmf domains)
		ini_set( 'user_agent', $this->userAgent );

		// Determine debug mode
		// Does the current request want to change/set the debug mode ?
		$isDebug = $kgReq->hasKey( 'debug' );
		if ( !$isDebug ) {
			// If nothing in the request, re-use the setting from the session
			// This makes it easier to debug in a workflow without having to
			// append it all the time.
			$isDebug = isset( $_SESSION['debug'] );
		} else {
			// If something in the request, put it in the session to remember it
			// in the next request
			$_SESSION['debug'] = $isDebug;
		}
		$this->debugMode = $isDebug;

		$this->confInitiated = true;
		return true;

	}


	/**
	 * get*-functions for read-only members
	 * -------------------------------------------------
	 */

	/**
	 * Get remote base
	 *
	 * @return String: Remote base path complete from the protocol:// without trailing slash
	 */
	public function getRemoteBase() { return $this->remoteBase; }

	/**
	 * Get path to home directory
	 *
	 * @return String: Home directory of tool user account (eg. /home/username or /data/project/mytool)
	 */
	public function getLocalHome() {
		$info = posix_getpwuid(posix_geteuid());
		return $info['dir'];
	}

	/**
	 * Get uri to jquery library
	 *
	 * @return String: Valid address to the script
	 */
	public function getJQueryURI( $minified = KR_MINIFY_ON ) {
		$uri = "//ajax.googleapis.com/ajax/libs/jquery/{$this->jQueryVersion}/jquery.";
		if ( $minified === KR_MINIFY_OFF ) {
			return $uri . 'js';
		} else {
			return $uri . 'min.js';
		}
	}

	/**
	 * Get uri to jquery library
	 *
	 * @return String: Valid address to the script
	 */
	public function getJQueryUI( $minified = KR_MINIFY_ON ) {
		$scripts = array();
		$styles = array();
		$suffix = $minified === KR_MINIFY_OFF ? 'js' : 'min.js';

		// Example : //ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/jquery-ui.min.js
		$scripts[] = "//ajax.googleapis.com/ajax/libs/jqueryui/{$this->jQueryUIVersion}/jquery-ui.$suffix";

		// Example : //ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/i18n/jquery-ui-i18n.min.js
		$scripts[] = "//ajax.googleapis.com/ajax/libs/jqueryui/{$this->jQueryUIVersion}/i18n/jquery-ui-i18n.$suffix";

		// Example : //ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/smoothness/jquery-ui.css
		$styles[] = "//ajax.googleapis.com/ajax/libs/jqueryui/{$this->jQueryUIVersion}/themes/smoothness/jquery-ui.css";

		return array( 'scripts' => $scripts, 'styles' => $styles );
	}

	/**
	 * A compelete date format in a simple way that is sortable and universal
	 *
	 * @return String: Dateformat for PHP date()
	 */
	public function getFullSimpleDatefmt() { return $this->fullSimpleDatefmt; }

	/**
	 * A complete date format that isn't sortable but more human readable
	 *
	 * @return String: Dateformat for PHP date()
	 */
	public function getFullReadableDatefmt() { return $this->fullReadableDatefmt; }

	/**
	 * Timestamp in seconds since epoch
	 *
	 * @return Interger
	 */
	public function getStartTime() { return $this->startTime; }

	/**
	 * Timestamp in microseconds since epoch
	 *
	 * @return Interger
	 */
	public function getStartTimeMicro() { return $this->startTimeMicro; }


	/**
	 * Getters and settes for mutable members
	 * -------------------------------------------------
	 */

	/**
	 * Wether debug mode is enabled
	 * @return bool
	 */
	public function isDebugMode() { return (bool)$this->debugMode; }

	/**
	 * Returns the debug mode
	 * @deprecated since 0.3: Use isDebugMode() instead.
	 */
	public function getDebugMode() { return (bool)$this->debugMode; }

	/**
	 * Sets the debug mode
	 *
	 * @return Boolean
	 */
	public function setDebugMode( $val ) { $this->debugMode = (bool)$val; return true; }


	/**
	 * Returns the run log of everything that happened so far
	 */
	public function getRunlog() { return $this->runlog; }

	/**
	 * Overwrites the run log
	 *
	 * @return Boolean
	 */
	public function setRunlog( $val ) { $this->runlog = $val; return true; }


	/**
	 * Returns the number of times the run log has been flushed
	 */
	public function getRunlogFlushCount() { return $this->runlogFlushCount; }

	/**
	 * Overwrites the flush count of the run log
	 */
	public function setRunlogFlushCount( $val ) { $this->runlogFlushCount = $val; return true; }


	/**
	 * Returns the database username
	 */
	public function getDbUsername() {
		if ( $this->dbUsername === null ) {
			// Read and cache in-class
			$cnf = parse_ini_file( $this->getLocalHome() . '/replica.my.cnf' );
			$this->dbUsername = $cnf['user'];
			unset( $cnf );
		}
		return $this->dbUsername;
	}


	/**
	 * Returns the database password
	 */
	public function getDbPassword() {
		if ( $this->dbPassword === null ) {
			// Read and cache in-class
			$cnf = parse_ini_file( $this->getLocalHome() . '/replica.my.cnf' );
			$this->dbPassword = $cnf['password'];
			unset( $cnf );
		}
		return $this->dbPassword;
	}


	/**
	 * Get the main database connection
	 *
	 * @return Mixed: The requested connection or boolean false
	 */
	public function getDbConnect() {
		if ( is_resource( $this->dbConnect ) && get_resource_type( $this->dbConnect ) == 'mysql link' ) {
			return $this->dbConnect;
		}
		return false;
	}

	/**
	 * Sets the main database connection (if valid), returns false otherwise
	 *
	 * @return Boolean: True if value is a valid database connection
	 */
	public function setDbConnect( $val, $hostname = null ) {
		if ( $val && is_resource( $val ) && get_resource_type( $val ) == 'mysql link' ) {
			$this->dbConnect = $val;
			$this->dbConnectHostname = $hostname;
			kfLog( "Connection to $hostname set.", __METHOD__ );
			return true;
		}
		kfLog( "Connection to $hostname not set.", __METHOD__ );
		return false;
	}


	/**
	 * Get a database connection by id
	 *
	 * @return Mixed: The requested connection or boolean false
	 */
	public function getDbExtraConnect( $id ) {
		if ( array_key_exists( $id, $this->dbExtraConnections )
		    && is_resource( $this->dbExtraConnections[$id] )
		    && get_resource_type( $this->dbExtraConnections[$id] ) == 'mysql link'
		   ) {
			return $this->dbExtraConnections[$id];
		}
		return false;
	}

	/**
	 * Sets the main database connection (if valid), returns false otherwise
	 *
	 * @return Boolean: True if value is a valid database connection
	 */
	public function setDbExtraConnect( $id, $connect ) {
		if ( is_resource( $connect ) && get_resource_type( $connect ) == 'mysql link' ) {
			$this->dbExtraConnections[$id] = $connect;
			return true;
		}
		return false;
	}

	/**
	 * Returns an array with all database connections
	 */
	public function getAllDbConnects() {
		$candidates = $this->dbExtraConnections;
		$candidates[] = $this->dbConnect;
		return $candidates;
	}

}
