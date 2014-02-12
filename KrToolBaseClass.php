<?php

class KrToolBaseClass {

	protected $settings = array();

	protected $settingsKeys = array();

	public function setSettings( $settings ) {
		foreach ( $this->settingsKeys as $key ) {
			if ( !isset( $this->settings[ $key ] ) && !array_key_exists( $key, $settings ) ) {
				throw new InvalidArgumentException( "Settings must have key $key." );
			}
		}
		foreach ( $settings as $key => $value ) {
			if ( in_array( $key, $this->settingsKeys ) ) {
				$this->settings[ $key ] = $value;
			}
		}
	}

	public function getSetting( $key ) {
		return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
	}
}
