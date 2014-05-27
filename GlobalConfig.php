<?php
/**
 * Configuration
 *
 * @since 0.1
 * @author Krinkle, 2010-2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */
require_once __DIR__ . '/GlobalDefinitions.php';

class GlobalConfig {

	/**
	 * Variables that are read-only
	 */
	var $remoteBase = 'http://example.org/tools/foo';
	var $fullSimpleDatefmt = 'Y-m-d H:i:s';
	var $fullReadableDatefmt = 'l, j F Y H:i:s';
	var $userAgent = 'BaseTool/0.3.0 (https://github.com/Krinkle/toollabs-base)';
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

	protected $currentLogSection = '(init)';
	protected $logSectionStack = array();

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
	 * Return the debug mode
	 * @deprecated since 0.3: Use isDebugMode() instead.
	 */
	public function getDebugMode() { return $this->isDebugMode(); }

	/**
	 * Set the debug mode
	 *
	 * @return bool
	 */
	public function setDebugMode( $val ) {
		$this->debugMode = (bool)$val;
		return true;
	}

	/**
	 * Return the run log of everything that happened so far
	 */
	public function getDebugLog() { return $this->runlog; }

	/**
	 * Write one or more lines to the debug log
	 */
	public function writeDebugLog( $val ) {
		$this->runlog .=
			number_format( kfTimeSince( KR_MICROSECONDS ), 7 ) . ': '
			. $this->currentLogSection . '> '
			. $val;
	}

	/**
	 * Clear the run log
	 */
	public function clearDebugLog() {
		$this->runlog = '';
	}

	public function getLogSection() {
		return $this->currentLogSection;
	}

	public function startLogSection( $name ) {
		$this->logSectionStack[] = $name;
	}

	public function endLogSection( $name ) {
		$item = array_pop( $this->logSectionStack );
		if ( $item !== $name ) {
			kfLog( "Log section mismatch (in: $item, out: $name)" );
		}
	}

	protected function fetchDbCredentials() {
		// Read and cache in-class
		$cnf = parse_ini_file( $this->getLocalHome() . '/replica.my.cnf' );
		if ( !$cnf || !$cnf['user'] || !$cnf['password'] ) {
			throw new Exception( 'Failed to fetch credentials from replica.my.cnf' );
			return;
		}
		$this->dbUsername = $cnf['user'];
		$this->dbPassword = $cnf['password'];
	}


	/**
	 * Returns the database username
	 */
	public function getDbUsername() {
		$this->fetchDbCredentials();
		return $this->dbUsername;
	}

	/**
	 * Returns the database password
	 */
	public function getDbPassword() {
		$this->fetchDbCredentials();
		return $this->dbPassword;
	}

}
