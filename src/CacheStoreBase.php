<?php
namespace Krinkle\Toolbase;

abstract class CacheStoreBase implements CacheInterface {
	/**
	 * @param int|float $ttl
	 * @return int|float Timestamp in seconds
	 */
	protected function convertExpiry( $ttl ) {
		if ( $ttl !== 0 ) {
			return time() + $ttl;
		}
		return $ttl;
	}

	protected function encode( $data ) {
		if ( is_int( $data ) ) {
			return $data;
		}
		return serialize( $data );
	}

	protected function decode( $data ) {
		if ( is_int( $data ) || ctype_digit( $data ) ) {
			return (int)$data;
		}
		return unserialize( $data );
	}
}
