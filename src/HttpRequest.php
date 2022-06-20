<?php
namespace Krinkle\Toolbase;

/**
 * @since 0.5.0
 */
class HttpRequest {
	protected $url;
	protected $method;
	protected $error;

	protected $reqHeaders = array();
	protected $reqData;

	protected $respContent = '';
	protected $respHeaderText = '';
	protected $restHeaderList;
	protected $respVersion;
	protected $respStatus;
	protected $respHeaders = array();

	/**
	 * Comply with <https://meta.wikimedia.org/wiki/User-Agent_policy>
	 */
	public static function getUserAgent() {
		$tool = BaseTool::getInstance();
		$toolUA = $tool ? $tool->getUserAgent() : 'unspecified';
		return "$toolUA; krinkle/toollabs-base (https://github.com/Krinkle/toollabs-base)";
	}

	/** @return bool|string */
	public static function get( $url ) {
		$req = new static( $url, 'GET' );
		if ( $req->execute() ) {
			return $req->getContent();
		}
		return false;
	}

	/** @return bool|string */
	public static function post( $url, Array $data ) {
		$req = new static( $url, 'POST', array(
			'data' => $data
		) );
		if ( $req->execute() ) {
			return $req->getContent();
		}
		return false;
	}

	/**
	 * @param string $url
	 * @param string $method GET, POST or HEAD
	 * @param array $options
	 */
	public function __construct( string $url, string $method, $options = array() ) {
		$this->url = $url;
		$this->method = $method;

		if ( isset( $options['data'] ) ) {
			$this->reqData = $options['data'];
		}
	}

	protected function getHeaderList(): array {
		$list = [];
		foreach ( $this->reqHeaders as $name => $value ) {
			$list[] = "$name: $value";
		}
		return $list;
	}

	protected function read( $fh, $content ): int {
		$this->respContent .= $content;
		return strlen( $content );
	}

	protected function readHeader( $fh, $content ): int {
		$this->respHeaderText .= $content;
		return strlen( $content );
	}

	protected function parseHeader() {
		$lastname = '';

		foreach ( $this->reqHeaders as $header ) {
			if ( preg_match( "#^HTTP/([0-9.]+) (.*)#", $header, $match ) ) {
				$this->respVersion = $match[1];
				$this->respStatus = $match[2];
			} elseif ( preg_match( "#^[ \t]#", $header ) ) {
				$last = count( $this->respHeaders[$lastname] ) - 1;
				$this->respHeaders[$lastname][$last] .= "\r\n$header";
			} elseif ( preg_match( "#^([^:]*):[\t ]*(.*)#", $header, $match ) ) {
				$this->respHeaders[strtolower( $match[1] )][] = $match[2];
				$lastname = strtolower( $match[1] );
			}
		}
	}

	public function execute(): bool {
		$curlOptions = array(
			// in seconds
			CURLOPT_TIMEOUT => 10,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
			CURLOPT_WRITEFUNCTION => array( $this, 'read' ),
			CURLOPT_HEADERFUNCTION => array( $this, 'readHeader' ),
			CURLOPT_MAXREDIRS => 2,
			CURLOPT_FOLLOWLOCATION => true,
			 // All supported encodings
			CURLOPT_ENCODING => '',
			CURLOPT_USERAGENT => self::getUserAgent(),
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_HTTPHEADER => $this->getHeaderList(),
		);

		if ( $this->method === 'HEAD' ) {
			$curlOptions[CURLOPT_NOBODY] = true;
			$curlOptions[CURLOPT_HEADER] = true;
		} elseif ( $this->method == 'POST' ) {
			$curlOptions[CURLOPT_POST] = true;
			$curlOptions[CURLOPT_POSTFIELDS] = $this->reqData;
			// Suppress 'Expect: 100-continue' header, as some servers will reject
			// it with an HTTP 417 and cURL won't auto-retry with HTTP 1.0 fallback.
			$this->reqHeaders['Expect'] = '';
		}

		$curlHandle = curl_init( $this->url );

		if ( !curl_setopt_array( $curlHandle, $curlOptions ) ) {
			$this->error = 'Error setting curl options.';
			return false;
		}

		$curlRes = curl_exec( $curlHandle );
		if ( curl_errno( $curlHandle ) == CURLE_OPERATION_TIMEOUTED ) {
			$this->error = 'Fetching url timed out: ' . $this->url;
			return false;
		} elseif ( $curlRes === false ) {
			$this->error = 'cURL Error: ' . curl_error( $curlHandle );
			return false;
		} else {
			$this->restHeaderList = explode( "\r\n", $this->respHeaderText );
		}

		curl_close( $curlHandle );

		$this->parseHeader();

		if ( (int)$this->respStatus >= 300 ) {
			list( $code, $message ) = explode( " ", $this->respStatus, 2 );
			$this->error = 'Internal request failed: HTTP ' . $code;
			return false;
		}

		return true;
	}

	public function getContent(): string {
		return $this->respContent;
	}

	public function getStatus(): int {
		return (int)$this->respStatus;
	}

	public function getError(): ?string {
		return $this->error;
	}
}
