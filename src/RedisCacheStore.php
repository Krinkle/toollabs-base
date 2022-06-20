<?php
namespace Krinkle\Toolbase;

use Exception;
use Redis;

class RedisCacheStore extends CacheStoreBase {
	/** @var Redis */
	protected $client;

	/** @var string */
	protected $prefix;

	protected static $defaults = array(
		'port' => 6379,
		'timeout' => 2,
		'prefix' => '',
	);

	protected static $presets = array(
		'toollabs' => array(
			'host' => 'tools-redis',
			'port' => 6379,
		),
	);

	/**
	 * Configuration:
	 * - string host
	 * - int port
	 * - float timeout Value in seconds (0 for unlimited)
	 * - string prefix
	 */
	public function __construct( array $config ) {
		if ( !class_exists( 'Redis' ) ) {
			throw new Exception( 'Redis class not loaded' );
		}

		if ( isset( $config['preset'] ) ) {
			if ( !isset( self::$presets[ $config['preset'] ] ) ) {
				throw new Exception( "Unknown Redis preset '{$config['preset']}'" );
			}
			$config = array_merge( self::$presets[ $config['preset'] ], $config );
			if ( $config['preset'] === 'toollabs'
				&& ( !isset( $config['prefix'] ) || strlen( $config['prefix'] ) < 10 )
			) {
				throw new Exception( 'Redis prefix is required in Tool Labs.' );
			}
		}

		if ( !isset( $config['host'] ) ) {
			throw new Exception( 'Redis host not specified.' );
		}

		$config += self::$defaults;

		$client = new Redis();
		$client->connect( $config['host'], $config['port'], $config['timeout'] );

		$this->client = $client;
		$this->prefix = $config['prefix'];
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get( string $key ) {
		$encdata = $this->client->get( $this->prefix . $key );
		if ( $encdata === false ) {
			return false;
		}
		return $this->decode( $encdata );
	}

	/**
	 * @param string $key
	 * @param int|string $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( string $key, $data, int $ttl = 0 ): bool {
		$encdata = $this->encode( $data );
		if ( $ttl === 0 ) {
			return $this->client->set( $this->prefix . $key, $encdata );
		}
		return $this->client->setex( $this->prefix . $key, $ttl, $encdata );

	}

	/**
	 * @param string $key
	 */
	public function delete( string $key ): void {
		// Redis::delete returns int, number of keys deleted
		$this->client->delete( $this->prefix . $key );
	}
}
