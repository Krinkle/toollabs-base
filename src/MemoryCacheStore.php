<?php
namespace Krinkle\Toolbase;

/**
 * Inspired by php-MemoryCache <https://github.com/c9s/php-UniversalCache>
 * Inspired by HashBagOStuff <https://github.com/wikimedia/mediawiki-core>
 */
class MemoryCacheStore extends CacheStoreBase {
	/** @var array */
	protected $store = [];

	protected function expire( $key ): bool {
		$expiryTime = $this->store[ $key ][ 1 ];

		if ( $expiryTime === 0 || $expiryTime > time() ) {
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
		if ( !isset( $this->store[ $key ] ) ) {
			return false;
		}

		if ( $this->expire( $key ) ) {
			return false;
		}

		return $this->store[ $key ][ 0 ];
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( string $key, $data, int $ttl = 0 ): bool {
		$this->store[ $key ] = array(
			$data,
			$this->convertExpiry( $ttl )
		);
		return true;
	}

	/**
	 * @param string $key
	 */
	public function delete( string $key ): void {
		if ( isset( $this->store[ $key ] ) ) {
			unset( $this->store[ $key ] );
		}
	}
}
