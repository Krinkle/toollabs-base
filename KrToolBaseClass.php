<?php

class KrToolBaseClass {

	protected $settings = array();

	protected $settingsKeys = array();

	public function setSettings( $settings ) {
		foreach ( $this->settingsKeys as $key ) {
			if ( !array_key_exists( $key, $settings ) ) {
				throw new InvalidArgumentException( "Settings must have key $key." );
			}
		}
		$this->settings = $settings;
	}

	public function getSetting( $key ) {
		return isset( $this->settings[$key] ) ? $this->settings[$key] : null;
	}
}
