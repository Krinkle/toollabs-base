<?php
/**
 * Caching classes
 *
 * Inspired by php-UniversalCache <https://github.com/c9s/php-UniversalCache>
 * Inspired by ObjectCache and BagOStuff <https://github.com/wikimedia/mediawiki-core>
 *
 * @since 0.5.0
 * @author Krinkle, 2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */

class Cache implements CacheBackend {
	protected $backends;

	/**
	 * @param array|CacheBackend $backends
	 */
	public function __construct( Array $backends ) {
		$this->backends = $backends;
		foreach ( $backends as $i => $backend ) {
			kfLog( "Registered " . get_class( $backend ) );
		}
	}

	public function addBackend( CacheBackend $backend ) {
		$this->backends[] = $backend;
		kfLog( "Registered " . get_class( $backend ) );
	}

	/**
	 * @param string $key
	 * @return mixed|bool
	 */
	public function get( $key ) {
		foreach ( $this->backends as $backend ) {
			$data = $backend->get( $key );
			if ( $data !== false ) {
				kfLog( "Cache hit for '$key'" );
				return $data;
			}
		}
		kfLog( "Cache miss for '$key'" );
		return false;
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 ) {
		foreach ( $this->backends as $backend ) {
			if ( !$backend->set( $key, $data, $ttl ) ) {
				kLog( "Failed to store value for '$key' in " . get_class( $backend ) );
			}
		}
		return true;
	}

	/**
	 * @param string $key
	 */
	public function delete( $key ) {
		foreach ( $this->backends as $backend ) {
			$backend->delete( $key );
		}
	}
}

interface CacheBackend {
	/**
	 * @param string $key
	 * @return mixed|bool Retreived data or boolean false
	 */
	public function get( $key );

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl In seconds from now, 0 for indefinitely
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 );

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key );
}

abstract class CacheBackendBase implements CacheBackend {
	/**
	 * @param int $ttl
	 * @return int Timestamp in seconds
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

/**
 * Inspired by php-MemoryCache <https://github.com/c9s/php-UniversalCache>
 * Inspired by HashBagOStuff <https://github.com/wikimedia/mediawiki-core>
 */
class MemoryCacheBackend extends CacheBackendBase {
	/** @var array */
	protected $store;

	/**
	 * @return bool
	 */
	protected function expire( $key ) {
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
	public function get( $key ) {
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
	public function set( $key, $data, $ttl = 0 ) {
		$this->store[ $key ] = array(
			$data,
			$this->convertExpiry( $ttl )
		);
		return true;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key ) {
		if ( isset( $this->store[ $key ] ) ) {
			unset( $this->store[ $key ] );
			return true;
		}
		return false;
	}
}

class FileCacheBackend extends CacheBackendBase {
	/** @var string */
	protected $dir;

	/**
	 * Configuration:
	 * - string dir
	 */
	public function __construct( Array $config ) {
		if ( !isset( $config['dir'] ) ) {
			throw new Exception( 'Cache directory not specified.' );
		}

		if ( !is_dir( $config['dir'] ) || !is_writable( $config['dir'] ) ) {
			throw new Exception( 'Cache directory not found or not writable.' );
		}

		$this->dir = $config['dir'];
	}

	protected function getFilepath( $key ) {
		return $this->dir . DIRECTORY_SEPARATOR . sha1( $key ) . '.json';
	}

	/** @return array|bool */
	protected function read( $key ) {
		$fp = $this->getFilepath( $key );
		if ( !is_readable( $fp ) ) {
			return false;
		}
		return json_decode( file_get_contents( $fp ), /* assoc = */ true );
	}

	/** @return bool */
	protected function write( $key, Array $store ) {
		$fp = $this->getFilepath( $key );
		return file_put_contents( $fp, json_encode( $store ) ) !== false;
	}

	/**
	 * @return bool
	 */
	protected function expire( $store, $key ) {
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
	public function get( $key ) {
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
	public function set( $key, $data, $ttl = 0 ) {
		return $this->write( $key, array(
			'value' => $this->encode( $data ),
			'expiryTime' => $this->convertExpiry( $ttl ),
		) );
	}

	/**
	 * @param string $key
	 */
	public function delete( $key ) {
		$fp = $this->getFilepath( $key );
		if ( file_exists( $fp ) ) {
			unlink( $fp );
		}
	}

}

class RedisCacheBackend extends CacheBackendBase {
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
	public function __construct( Array $config ) {
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
	public function get( $key ) {
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
	public function set( $key, $data, $ttl = 0 ) {
		$encdata = $this->encode( $data );
		if ( $ttl === 0 ) {
			return $this->client->set( $this->prefix . $key, $encdata );
		}
		return $this->client->setex( $this->prefix . $key, $ttl, $encdata );

	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key ) {
		// Redis::delete returns number of keys deleted
		return $this->client->delete( $this->prefix . $key ) === 1;
	}
}
