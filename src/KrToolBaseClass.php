<?php
namespace Krinkle\Toolbase;

use Exception;
use InvalidArgumentException;
use Throwable;

/**
 * @since 0.3.0
 */
class KrToolBaseClass {
	protected $settings = [];
	protected $settingsKeys = [];

	public function setSettings( array $settings ) {
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
		return $this->settings[$key] ?? null;
	}

	protected function show() {}

	protected function outputException( Throwable $e ) {
		global $kgBase;
		http_response_code( 500 );
		$kgBase->addOut( $e->getMessage() . "\n" . $e->getTraceAsString() , 'pre' );
	}

	/**
	 * @param Throwable $e
	 * @return never
	 */
	public function handleException( Throwable $e ) {
		$this->outputException( $e );
		exit( 1 );
	}

	public function run() {
		$scope = Logger::createScope( __METHOD__ );

		set_exception_handler( array( $this, 'handleException' ) );

		try {
			$this->show();
		} catch ( Exception $e ) {
			$this->outputException( $e );
		}
	}
}
