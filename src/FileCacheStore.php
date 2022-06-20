<?php
namespace Krinkle\Toolbase;

use Exception;

class FileCacheStore extends CacheStoreBase {
	/** @var string */
	protected $dir;

	/**
	 * Configuration:
	 * - string dir
	 */
	public function __construct( array $config ) {
		if ( !isset( $config['dir'] ) ) {
			throw new Exception( 'Cache directory not specified.' );
		}

		if ( !is_dir( $config['dir'] ) || !is_writable( $config['dir'] ) ) {
			throw new Exception( 'Cache directory not found or not writable.' );
		}

		$this->dir = $config['dir'];
	}

	protected function getFilepath( string $key ): string {
		return $this->dir . DIRECTORY_SEPARATOR . sha1( $key ) . '.json';
	}

	/** @return array|bool */
	protected function read( string $key ) {
		$fp = $this->getFilepath( $key );
		if ( !is_readable( $fp ) ) {
			return false;
		}
		return json_decode( file_get_contents( $fp ), /* assoc = */ true );
	}

	/** @return bool */
	protected function write( string $key, array $store ): bool {
		$fp = $this->getFilepath( $key );
		return file_put_contents( $fp, json_encode( $store ) ) !== false;
	}

	protected function expire( array $store, string $key ): bool {
		if ( $store['expiryTime'] === 0 || $store['expiryTime'] > time() ) {
			return false;
		}

		$this->delete( $key );
		return true;
	}

	/**
	 * @param string $key
	 * @return mixed|bool
	 */
	public function get( string $key ) {
		$store = $this->read( $key );
		if ( !$store ) {
			return false;
		}

		if ( $this->expire( $store, $key ) ) {
			return false;
		}

		return $this->decode( $store['value'] );
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( string $key, $data, int $ttl = 0 ): bool {
		return $this->write( $key, array(
			'value' => $this->encode( $data ),
			'expiryTime' => $this->convertExpiry( $ttl ),
		) );
	}

	/**
	 * @param string $key
	 */
	public function delete( string $key ): void {
		$fp = $this->getFilepath( $key );
		if ( file_exists( $fp ) ) {
			unlink( $fp );
		}
	}
}
