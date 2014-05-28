<?php
/**
 * Configuration
 *
 * @since 0.1.0
 * @author Krinkle, 2010-2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */
require_once __DIR__ . '/GlobalDefinitions.php';

class GlobalConfig {

	/**
	 * Set from LocalConfig
	 */
	public $remoteBase = 'http://example.org/tools/foo';
	public $userAgent = 'BaseTool/0.3.0 (https://github.com/Krinkle/toollabs-base)';

	// Set by BaseTool
	public $I18N = null;

	protected $currentLogSection = '(init)';
	protected $logSectionStack = array();

	protected $confInitiated = false;
	protected $startTime = null;
	protected $startTimeMicro = null;
	protected $debugMode = false;
	protected $runlog = '';
	protected $runlogFlushCount = 0;
	protected $dbUsername = null;
	protected $dbPassword = null;

	/**
	 * Initiated certain configuration variables
	 * that depend on other factors (ie. environment, request parameters etc.)
	 *
	 * @return Boolean: True if initiation request was execututed (ie. first call), false on later ones
	 */
	public function initConfig() {
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
	 * Get remote base
	 *
	 * @return string Remote base path complete from the protocol:// without trailing slash
	 */
	public function getRemoteBase() { return $this->remoteBase; }

	/**
	 * Get path to home directory
	 *
	 * @return string Home directory of tool user account (eg. /home/username or /data/project/mytool)
	 */
	public function getLocalHome() {
		$info = posix_getpwuid(posix_geteuid());
		return $info['dir'];
	}

	/**
	 * Get timestamp in seconds since epoch
	 *
	 * @return int
	 */
	public function getStartTime() { return $this->startTime; }

	/**
	 * Get timestamp in microseconds since epoch
	 *
	 * @return int
	 */
	public function getStartTimeMicro() { return $this->startTimeMicro; }

	/**
	 * Wether debug mode is enabled
	 *
	 * @return bool
	 */
	public function isDebugMode() { return (bool)$this->debugMode; }

	/**
	 * Set the debug mode
	 */
	public function setDebugMode( $val ) {
		$this->debugMode = (bool)$val;
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
	 * Get the database username
	 */
	public function getDbUsername() {
		$this->fetchDbCredentials();
		return $this->dbUsername;
	}

	/**
	 * Get the database password
	 */
	public function getDbPassword() {
		$this->fetchDbCredentials();
		return $this->dbPassword;
	}

}
