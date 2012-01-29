<?php
/**
 * GlobalConfig.php
 * Created on January 16th, 2011
 *
 * @since 0.1.1
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
	var $localHome = '/home/krinkle';
	var $jQueryVersion = '1.5.1';
	var $jQueryUIVersion = '1.8.11';
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
		session_start();
		date_default_timezone_set( 'UTC' );
		$this->startTime = time();
		$this->startTimeMicro = microtime( true );

		// User agent (required to get data from wmf domains)
		ini_set( 'user_agent', $this->userAgent );

		// Determine debug mode
		// Does the current request want to change/set the debug mode ?
		$debugVal = getParamExists( 'debug' ) ? getParamVar( 'debug' ) : null;
		if ( is_null( $debugVal ) ) {
			// If nothing in the request, re-use the setting from the session
			// This makes it easier to debug in a workflow without having to
			// append it all the time.
			$debugVal = getParamExists( 'debug', false, $_SESSION ) ? getParamVar( 'debug', null, $_SESSION ) : null;
		} else {
			// If something in the request, put it in the session to remember it
			// in the next request
			$_SESSION['debug'] = $debugVal;
		}
		$this->debugMode = $debugVal == 'true' ? true : false;

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
	 * @return String: Local home starting at /home (eg. /home/username or /home/projects/f/o/o/foobar)
	 */
	public function getLocalHome() { return $this->localHome; }

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

		// Example : //ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/i18n/jquery-ui-i18n.min.js
		$scripts[] = "//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/i18n/jquery-ui-i18n.$suffix";

		// Example : //ajax.googleapis.com/ajax/libs/jqueryui/1.8.6/themes/smoothness/jquery-ui.css
		$styles[] = "//ajax.googleapis.com/ajax/libs/jqueryui/{$this->jQueryUIVersion}/themes/smoothness/jquery-ui.css";
		$styles[] = "//meta.wikimedia.org/w/resources/jquery.ui/themes/vector/jquery.ui.theme.css";

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
	 * get*- and set* -functions for modifiable members
	 * -------------------------------------------------
	 */

	/**
	 * Returns the debug mode
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
	public function getDbUsername() { return $this->dbUsername; }

	/**
	 * Sets the database username
	 *
	 * @return Boolean
	 */
	public function setDbUsername( $val ) { $this->dbUsername = $val; return true; }


	/**
	 * Returns the database password
	 */
	public function getDbPassword() { return $this->dbPassword; }

	/**
	 * Sets the database password
	 *
	 * @return Boolean
	 */
	public function setDbPassword( $val ) { $this->dbPassword = $val; return true; }


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