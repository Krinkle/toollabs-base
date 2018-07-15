<?php
/**
 * @package krinkle/toollabs-base
 * @since v0.3.0
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

	/**
	 * @param Exception|Throwable $e
	 */
	protected function outputException( $e ) {
		global $kgBase;
		http_response_code( 500 );
		$kgBase->addOut( $e->getMessage() . "\n" . $e->getTraceAsString() , 'pre' );
	}

	/**
	 * @param Exception|Throwable $e
	 */
	public function handleException( $e ) {
		$this->outputException( $e );
		exit( 1 );
	}

	public function run() {
		$section = new kfLogSection( __METHOD__ );

		set_exception_handler( array( $this, 'handleException' ) );

		try {
			$this->show();
		} catch ( Exception $e ) {
			$this->outputException( $e );
		}
	}
}
