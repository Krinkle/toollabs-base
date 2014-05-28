<?php
/**
 * Configuration
 *
 * @since 0.1.0
 * @author Krinkle, 2010-2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */

class GlobalConfig {

	/**
	 * Set from LocalConfig
	 */
	public $remoteBase = 'http://example.org/tools/foo';
	public $userAgent = 'BaseTool/0.3.0 (https://github.com/Krinkle/toollabs-base)';

	// Set by BaseTool
	public $I18N = null;

	protected $logSectionStack = array(
		'(init)'
	);

	protected $confInitiated = false;
	protected $debugMode = false;
	protected $runlog = '';
	protected $runlogFlushCount = 0;
	protected $dbUsername;
	protected $dbPassword;

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

		// User agent (required to get data from wmf domains)
		ini_set( 'user_agent', $this->userAgent );

		// Allow request parameter to toggle debug mode
		if ( !$kgReq->hasKey( 'debug' ) ) {
			// If nothing in the request, re-use the setting from the session
			// This makes it easier to debug in a workflow without having to
			// append it to every individual request.
			$isDebug = isset( $_SESSION['debug'] );
		} else {
			// If something in the request, put it in the session to remember it
			// in the next request
			$isDebug = $kgReq->getFuzzyBool( 'debug' );
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
		$this->runlog .= $this->getLogSection() . '> '
			. $val
			. "\n";
	}

	/**
	 * Clear the run log
	 */
	public function clearDebugLog() {
		$this->runlog = '';
	}

	public function getLogSection() {
		return end( $this->logSectionStack );
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
