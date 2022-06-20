<?php
namespace Krinkle\Toolbase;

/**
 * Multi-cache interface
 *
 * Inspired by php-UniversalCache <https://github.com/c9s/php-UniversalCache>
 * Inspired by ObjectCache and BagOStuff <https://github.com/wikimedia/mediawiki-core>
 *
 * @since 0.5.0
 */
class Cache implements CacheInterface {
	protected $frontend;
	protected $stores;

	/**
	 * @since 2.0.0
	 */
	public static function makeKey( ...$args ): string {
		$key = 'kf-' . implode( ':', $args );
		return str_replace( ' ', '_', $key );
	}

	/**
	 * @param CacheInterface[] $stores
	 */
	public function __construct( array $stores ) {
		$this->stores = $stores;

		foreach ( $stores as $i => $store ) {
			Logger::debug( "Registered " . get_class( $store ) );
		}
	}

	/**
	 * Enable harvest behaviour for the first cache store.
	 *
	 * The store marked as "harvester" will receive set() commands
	 * when a multi-store get() results a miss from this one,
	 * that way it will be populated for the next request.
	 *
	 * Typically this is an instance of MemoryCacheStore.
	 *
	 * Example configuration:
	 *
	 *     $tmpCache = new MemoryCacheStore();
	 *     $redisCache = new RedisCacheStore( .. );
	 *     $cache = new Cache( array( $tmpCache, $redisCache ) );
	 *
	 * When a value is stored, it will be in both. Within that
	 * request it will be retreived from memory only without having
	 * to hit Redis.
	 *
	 * On subsequent requests, though, it would always fallback to
	 * Redis. Even if it is called multiple times within the
	 * subsequent request, it never comes back in memory store.
	 *
	 *     $cache->enableHarvest();
	 *
	 * Enabling harvest behaviour will automatically hold on to the
	 * value retrieved from Redis, in memory, within the current request.
	 *
	 * NB: When a value is harvested, the default expiry will be used (this
	 * information can generally not be covered from an existing store).
	 * This is generally not an issue as memory stores just expire at the end
	 * of the request.
	 */
	public function enableHarvest(): void {
		$this->frontend = $this->stores[0];
	}

	public function addStore( CacheInterface $store ): void {
		$this->stores[] = $store;

		Logger::debug( "Registered " . get_class( $store ) );
	}

	/**
	 * @param string $key
	 * @return mixed|bool
	 */
	public function get( string $key ) {
		foreach ( $this->stores as $store ) {
			$data = $store->get( $key );
			if ( $data !== false ) {
				Logger::debug( "Cache hit for '$key' in " . get_class( $store ) );
				// Logger::debug we have a frontend and this wasn't from there,
				// be sure to populate it.
				if ( $this->frontend && $store !== $this->frontend ) {
					$this->frontend->set( $key, $data );
				}
				return $data;
			}
		}
		Logger::debug( "Cache miss for '$key'" );
		return false;
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( string $key, $data, $ttl = 0 ): bool {
		foreach ( $this->stores as $store ) {
			if ( !$store->set( $key, $data, $ttl ) ) {
				Logger::debug( "Failed to store value for '$key' in " . get_class( $store ) );
			}
		}
		return true;
	}

	/**
	 * @param string $key
	 */
	public function delete( string $key ): void {
		foreach ( $this->stores as $store ) {
			$store->delete( $key );
		}
	}
}
