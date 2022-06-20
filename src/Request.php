<?php
namespace Krinkle\Toolbase;

/**
 * Interact with request and session data for incoming web request
 *
 * This file is inspired by MediaWiki 1.22 (WebRequest class).
 *
 * @since 0.1.0
 */
class Request {
	/** @var array */
	protected $raw;

	/** @var array|null */
	protected $headers;

	public function __construct( array $raw ) {
		$this->raw = $raw;
	}

	public function getRawVal( $arr, $key, $default ) {
		return $arr[$key] ?? $default;
	}

	public function getVal( $key, $default = null ): ?string {
		$val = $this->getRawVal( $this->raw, $key, $default );
		if ( is_array( $val ) ) {
			$val = $default;
		}
		if ( is_null( $val ) ) {
			return null;
		} else {
			return (string)$val;
		}
	}

	public function getArray( $name, $default = null ): ?array {
		$val = $this->getRawVal( $this->raw, $name, $default );
		if ( is_null( $val ) ) {
			return null;
		} else {
			return (array)$val;
		}
	}

	/**
	 * Is the key is set, no matter the value. Useful with HTML checkboxes.
	 */
	public function hasKey( $key ): bool {
		return array_key_exists( $key, $this->raw );
	}

	public function getInt( $key, $default = 0 ): int {
		return intval( $this->getVal( $key, $default ) );
	}

	public function getFuzzyBool( $key, $default = false ): bool {
		return $this->hasKey( $key ) && $this->getVal( $key ) !== 'false';
	}

	public function getCookie( $key, $default = null ) {
		return $this->getRawVal( $_COOKIE, $key, $default );
	}

	public function setCookie( string $key, ?string $value, $expire = 0 ) {
		if ( $value === null && $expire === 0 ) {
			// Delete cookie
			$expire = -1;
		}

		$options = is_array( $expire ) ? $expire : array( 'expire' => $expire );
		$options += array(
			'expire' => 0,
			'path' => '/',
			'domain' => '',
			'secure' => false,
			// By default disallow access by JavaScript to cookies set here
			'httpOnly' => true,
		);
		// http://www.php.net/setcookie
		return setcookie(
			$key,
			$value ?? '',
			$options['expire'],
			$options['path'],
			$options['domain'],
			$options['secure'],
			$options['httpOnly']
		);
	}

	/**
	 * Get data from session
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getSessionData( string $key ) {
		self::ensureSession();
		if ( !isset( $_SESSION[ $key ] ) ) {
			return null;
		}
		return $_SESSION[$key];
	}

	/**
	 * Set session data
	 *
	 * @param string $key
	 * @param mixed $data
	 */
	public function setSessionData( string $key, $data ) {
		self::ensureSession();
		$_SESSION[ $key ] = $data;
	}

	public function wasPosted(): bool {
		return isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	public function getQueryString(): string {
		return http_build_query( $this->raw );
	}

	/**
	 * Detect HTTPS from HTTP protocol.
	 */
	public function getProtocol(): string {
		if ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ) ||
			( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
			$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) ) {
			return 'https';
		} else {
			return 'http';
		}
	}

	/**
	 * Lazy-initialise the session
	 */
	protected static function ensureSession() {
		// If the cookie or session id is already set we already have a session and should abort
		if ( isset( $_COOKIE[session_name()] ) || session_id() ) {
			return;
		}

		session_start();
	}

	/**
	 * Initialise the header list
	 */
	private function initHeaders() {
		if ( $this->headers !== null ) {
			return;
		}

		$this->headers = [];

		$apacheHeaders = apache_request_headers();
		foreach ( $apacheHeaders as $key => $val ) {
			$this->headers[strtoupper( $key )] = $val;
		}
	}

	/**
	 * Get an array containing all request headers
	 *
	 * @return array Headers keyed by name (normalised to upper case)
	 */
	public function getAllHeaders(): array {
		$this->initHeaders();
		return $this->headers;
	}

	/**
	 * Get a request header, or false if it isn't set
	 *
	 * @param string $name Case-insensitive header name
	 * @return string|bool False on failure
	 */
	public function getHeader( $name ) {
		$this->initHeaders();
		$name = strtoupper( $name );
		if ( !isset( $this->headers[$name] ) ) {
			return false;
		}
		return $this->headers[$name];
	}

	/**
	 * Respond with 304 Last Modified if appropiate
	 *
	 * @param int $modifiedTime
	 * @return bool True if 304 header was sent
	 */
	public function tryLastModified( $modifiedTime ): bool {
		$clientCache = $this->getHeader( 'If-Modified-Since' );
		if ( $clientCache !== false ) {
			# IE sends sizes after the date like "Wed, 20 Aug 2003 06:51:19 GMT; length=5202"
			# which would break strtotime() parsing.
			$clientCache = preg_replace( '/;.*$/', '', $clientCache );
			$clientCacheTime = @strtotime( $clientCache );
			if ( $modifiedTime <= $clientCacheTime ) {
				header( 'Status: 304 Not Modified', true, 304 );
				return true;
			}
		}
		return false;
	}

	/**
	 * Output a HTTP header, wrapper for PHP's header()
	 * @param string $key Header name
	 * @param string $value Header value
	 * @param bool $replace Replace current similar header
	 */
	public function setHeader( $key, $value, $replace = true ) {
		header( strtolower( $key ) . ": $value", $replace );
	}
}
