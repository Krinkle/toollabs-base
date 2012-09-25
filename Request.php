<?php
/**
 * Request.php
 * Created on March 15, 2011
 *
 * This file is inspired by MediaWiks' WebRequest class.
 * svn.wikimedia.org/viewvc/mediawiki/trunk/phase3/includes/WebRequest.php?view=markup&pathrev=82694
 *
 * @since 0.1
 * @author Krinkle <krinklemail@gmail.com>, 2011 - 2012
 *
 * @package KrinkleToolsCommon
 * @license Public domain, WTFPL
 */

class Request {

	/* Initialize */
	function __construct( $raw ) {

		$this->raw = $raw;

	}

	/* Simple return functions */

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
			return (array)$val;
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

	/* Other utilities */

	/** @return bool */
	public function wasPosted() {
		return isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	public function getQueryString(){
		return http_build_query( $this->raw );
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