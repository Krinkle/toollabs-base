<?php
/**
 * @since 0.3.0
 * @author Krinkle, 2012-2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */
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

	protected function show() {}

	public function handleException( Exception $e ) {
		global $kgBaseTool;
		$kgBaseTool->addOut( $e->getMessage(), 'pre' );
		exit( 1 );
	}

	public function run() {
		new kfLogSection( get_class( $this ) . '::run' );

		set_exception_handler( array( $this, 'handleException' ) );

		try {
			$this->show();
		} catch ( Exception $e ) {
			global $kgBaseTool;
			$kgBaseTool->addOut( $e->getMessage(), 'pre' );
		}
	}
}
