<?php
namespace Krinkle\Toolbase;

interface CacheInterface {
	/**
	 * @param string $key
	 * @return mixed|bool Retreived data or boolean false
	 */
	public function get( string $key );

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl In seconds from now, 0 for indefinitely
	 * @return bool
	 */
	public function set( string $key, $data, int $ttl = 0 ): bool;

	/**
	 * @param string $key
	 */
	public function delete( string $key ): void;
}
