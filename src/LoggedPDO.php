<?php
namespace Krinkle\Toolbase;

use PDO;

/**
 * @phan-file-suppress UnusedPluginFileSuppression
 * @phan-file-suppress PhanParamSignatureMismatchInternal for PDO::prepare and ::query
 * @phan-file-suppress PhanParamSignatureRealMismatchHasParamTypeInternal
 * @phan-file-suppress PhanParamSignatureRealMismatchTooManyRequiredParametersInternal
 */
class LoggedPDO extends PDO {
	public function __construct( $dsn, $username = null, $password = null ) {
		parent::__construct( $dsn, $username, $password );
	}

	public function prepare( string $query, $options = null ) {
		Logger::debug( self::generalizeSQL( "query-prepare: $query" ) );
		return parent::prepare( $query );
	}

	public function query( string $query, $fetchMode = null, ...$fetchModeArgs ) {
		Logger::debug( self::generalizeSQL( "query: $query" ) );
		return parent::query( $query );
	}

	/**
	 * Remove most variables from an SQL query and replace them with X or N markers.
	 *
	 * Based on Database.php of mediawik-core 1.24-alpha
	 *
	 * @param string $sql
	 * @return string
	 */
	protected static function generalizeSQL( string $sql ): string {
		$sql = str_replace( "\\\\", '', $sql );
		$sql = str_replace( "\\'", '', $sql );
		$sql = str_replace( "\\\"", '', $sql );
		$sql = preg_replace( "/'.*'/s", "'X'", $sql );
		$sql = preg_replace( '/".*"/s', "'X'", $sql );

		$sql = preg_replace( '/\s+/', ' ', $sql );

		$sql = preg_replace( '/-?\d+(,-?\d+)+/s', 'N,...,N', $sql );
		$sql = preg_replace( '/-?\d+/s', 'N', $sql );

		return $sql;
	}
}
