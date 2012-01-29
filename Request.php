<?php
/**
 * Request.php
 * Created on March 15, 2011
 *
 * This file is inspired by MediaWiks' WebRequest class.
 * svn.wikimedia.org/viewvc/mediawiki/trunk/phase3/includes/WebRequest.php?view=markup&pathrev=82694
 *
 * @since 0.1.2
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

		// If it's set and not an array, return it
		// Otherwise $default
		if ( isset( $arr[$key] ) ) {
			if ( is_array( $arr[$key] ) ) {
				return $default;
			} else {
				return (string)$arr[$key];
			}
		} else {
			return $default;
		}
	}

	public function getVal( $key, $default = null ) {
		return $this->getRawVal( $this->raw, $key, $default );
	}

	public function exists( $key, $negative = false ) {
		return array_key_exists( $key, $this->raw ) ? $negative : true;
	}

	public function getBool( $key, $default = false ) {
		return (bool)$this->getVal( $key, $default );
	}

	public function getInt( $key, $default = 0 ) {
		return intval( $this->getVal( $key, $default ) );
	}

	public function getFuzzyBool( $key, $default = false ) {
		return $this->getBool( $key, $default ) && $this->getVal( $key ) != 'false';
	}

	/* Other utilities */

	public function getQueryString(){
		return http_build_query( $this->raw );
	}

}