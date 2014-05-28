<?php
/**
 * Interact with request and session data for incoming web request
 *
 * This file is inspired by MediaWiks' WebRequest class.
 *
 * https://svn.wikimedia.org/viewvc/mediawiki/trunk/phase3/includes/WebRequest.php?view=markup&pathrev=82694
 *
 * @since 0.1.0
 * @author Krinkle, 2011-2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */

class Request {

	/* Initialize */
	function __construct( $raw ) {
		$this->raw = $raw;
	}

	/* Simple getters */

	public function getRawVal( $arr, $key, $default ) {
		return isset( $arr[$key] ) ? $arr[$key] : $default;
	}

	public function getVal( $key, $default = null ) {
		$val = $this->getRawVal( $this->raw, $key, $default );
		if ( is_array( $val ) ) {
			$val = $default;
		}
		if ( is_null( $val ) ) {
			return null;
		} else {
			return (string)$val;
		}
	}

	/** @return array|null */
	public function getArray( $name, $default = null ) {
		$val = $this->getRawVal( $this->raw, $name, $default );
		if ( is_null( $val ) ) {
			return null;
		} else {
			return (array) $val;
		}
	}

	/**
	 * Is the key is set, no matter the value. Useful when dealing with HTML checkboxes.
	 * @return bool
	 */
	public function hasKey( $key ) {
		return array_key_exists( $key, $this->raw );
	}

	/** @return int */
	public function getInt( $key, $default = 0 ) {
		return intval( $this->getVal( $key, $default ) );
	}

	public function getFuzzyBool( $key, $default = false ) {
		return $this->getBool( $key, $default ) && $this->getVal( $key ) != 'false';
	}

	/* Utility methods */

	/** @return bool */
	public function wasPosted() {
		return isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	public function getQueryString(){
		return http_build_query( $this->raw );
	}

	/**
	 * Detect the protocol from $_SERVER.
	 * This is for use prior to Setup.php, when no WebRequest object is available.
	 * At other times, use the non-static function getProtocol().
	 *
	 * @return array
	 */
	public function getProtocol() {
		if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ||
			( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
			$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) ) {
			return 'https';
		} else {
			return 'http';
		}
	}

	/** @deprecated */
	public function exists( $key, $negative = null ) {
		return $this->hasKey( $key );
	}

	/** @deprecated */
	public function getBool( $key, $negative = null ) {
		return $this->hasKey( $key );
	}

}
