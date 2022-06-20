<?php
namespace Krinkle\Toolbase;

use Exception;
use PDO;

/**
 * @since 0.5.0
 */
class LabsDB {
	/**
	 * @var array PDO objects keyed by hostname
	 */
	protected static $dbConnections = [];

	/** @var array */
	protected static $dbInfos;

	/** @var array */
	protected static $wikiInfos;

	private static $replicaUsername;
	private static $replicaPassword;

	/**
	 * Read `replica.my.cnf` from the webserver user's home directory.
	 */
	private static function readReplicaConf(): void {
		if ( self::$replicaUsername && self::$replicaPassword ) {
			return;
		}

		$info = posix_getpwuid(posix_geteuid());
		$homeDir = $info['dir'];
		$file = $homeDir  . '/replica.my.cnf';
		if ( !is_readable( $file ) || !is_file( $file ) ) {
			throw new Exception( 'Failed to fetch credentials from replica.my.cnf' );
		}
		$cnf = parse_ini_file( $file );
		if ( !$cnf || !$cnf['user'] || !$cnf['password'] ) {
			throw new Exception( 'Failed to fetch credentials from replica.my.cnf' );
		}
		self::$replicaUsername = $cnf['user'];
		self::$replicaPassword = $cnf['password'];
	}

	/**
	 * @since 2.0.0
	 */
	public static function getReplicaUser(): string {
		self::readReplicaConf();
		return self::$replicaUsername;
	}

	/**
	 * @since 2.0.0
	 */
	public static function getReplicaPassword(): string {
		self::readReplicaConf();
		return self::$replicaPassword;
	}

	/**
	 * Get a database connection by hostname
	 *
	 * Returns a previously established connection or initiates a new one.
	 */
	public static function getConnection( $hostname, $dbname ): PDO {
		if ( isset( self::$dbConnections[ $hostname ] ) ) {
			$conn = self::$dbConnections[ $hostname ];
		} else {
			$scope = Logger::createScope( __METHOD__ );
			try {
				$conn = new LoggedPDO(
					'mysql:host=' . $hostname . ';dbname=' . $dbname . '_p;charset=utf8',
					self::getReplicaUser(),
					self::getReplicaPassword()
				);
			    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			} catch ( Exception $e ) {
				throw new Exception( "Connection to '$hostname' failed: " . $e->getMessage() );
			}
			self::$dbConnections[ $hostname ] = $conn;
			return $conn;
		}

		// Re-used connection, switch database first.
		self::selectDB( $conn, $dbname );

		return $conn;
	}

	public static function getMetaDB(): PDO {
		static $metaServer;
		// meta_p is replicated on all shards, any of these is fine
		static $servers = array(
			's1.labsdb',
			's2.labsdb',
			's3.labsdb',
			's4.labsdb',
			's5.labsdb',
			's6.labsdb',
			's7.labsdb',
		);

		// See if we have a connection to any of the shards already
		if ( !$metaServer ) {
			foreach ( $servers as $server ) {
				if ( isset( self::$dbConnections[ $server ] ) ) {
					$metaServer = $server;
					break;
				}
			}
		}

		// Fallback to making a new connection to s7
		if ( !$metaServer ) {
			$metaServer = 's7.labsdb';
		}

		return self::getConnection( $metaServer, 'meta' );
	}

	/**
	 * Get a database connection by dbname.
	 *
	 * Usage:
	 *
	 *     $conn = LabsDB::getDB( 'aawiki' );
	 *     $rows = LabsDB::query( $conn, 'SELECT * WHERE name = "str"' );
	 *
	 *     $conn = LabsDB::getDB( 'aawiki' );
	 *     $rows = LabsDB::query( $conn, 'SELECT * WHERE name = :name',
	 *         array( ':name' => "string" )
	 *     );
	 *
	 *     $conn = LabsDB::getDB( 'aawiki' );
	 *     $m = $conn->prepare( 'SELECT * WHERE total = :total' );
	 *     $m->bindParam( ':total', $total, PDO::PARAM_INT );
	 *     $m->execute();
	 *     $rows = $m->fetchAll( PDO::FETCH_ASSOC );
	 */
	public static function getDB( $dbname ): PDO {
		if ( $dbname === 'meta' ) {
			return self::getMetaDB();
		}

		$wikiInfo = self::getDbInfo( $dbname );
		if ( !$wikiInfo['slice'] ) {
			throw new Exception( "Incomplete database information for '$dbname'" );
		}

		return self::getConnection( $wikiInfo['slice'], $dbname );
	}

	public static function selectDB( PDO $conn, string $dbname ): void {
        $stmt = $conn->prepare( 'USE `' . $dbname . '_p`;' );
        $stmt->execute();
        unset( $stmt );
	}

	/**
	 * @param PDO $conn
	 * @param string $sql SQL query with placeholders
	 * @param array|null $bindings Bindings of type PDO::PARAM_STR.
	 *  Use prepare() if you need different types or to execute multiple times.
	 * @return array Rows
	 */
	public static function query( PDO $conn, string $sql, array $bindings = null ): array {
		$scope = Logger::createScope( __METHOD__ );

		if ( $bindings ) {
			$m = $conn->prepare( $sql );
			$m->execute( $bindings );
		} else {
			$m = $conn->query( $sql );
			$m->execute();
		}
		return $m->fetchAll( PDO::FETCH_ASSOC );
	}

	protected static function fetchAllDbInfos(): array {
		$rows = self::query( self::getMetaDB(),
			'SELECT dbname, family, url, slice
			FROM wiki
			WHERE is_closed = 0
			ORDER BY url ASC'
		);

		$dbInfos = [];
		foreach ( $rows as &$row ) {
			$dbInfos[ $row['dbname'] ] = $row;
		}

		return $dbInfos;
	}

	/**
	 * Get information for all (replicated) databases.
	 *
	 * See https://wikitech.wikimedia.org/wiki/Nova_Resource:Tools/Help#Metadata_database
	 */
	public static function getAllDbInfos(): array {
		if ( !isset( self::$dbInfos ) ) {
			global $kgCache;
			$key = Cache::makeKey( 'toolbase-labsdb-dbinfos' );
			// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
			$value = $kgCache->get( $key );
			if ( $value === false ) {
				$value = self::fetchAllDbInfos();
				// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
				$kgCache->set( $key, $value, 3600 * 24 );
			}
			self::$dbInfos = $value;
		}

		return self::$dbInfos;
	}

	public static function getDbInfo( string $dbname ): array {
		$dbInfos = self::getAllDbInfos();

		if ( !isset( $dbInfos[ $dbname ] ) ) {
			throw new Exception( "Unable to find '$dbname'" );
		}

		return $dbInfos[ $dbname ];
	}

	/**
	 * Like getAllDbInfos, but without databases that aren't wikis.
	 *
	 * Because meta_p.wiki also contains dbname='centralauth' we need to
	 * filter out non-wikis. Do so by removing rows with NULL values for url
	 * (see wmbug.com/65789). Could simply be done in SQL, but we want to
	 * cache all db infos, so do here instead.
	 */
	public static function getAllWikiInfos(): array {
		if ( !isset( self::$wikiInfos ) ) {
			$wikiInfos = self::getAllDbInfos();
			foreach ( $wikiInfos as $dbname => &$wikiInfo ) {
				if ( !$wikiInfo['url'] ) {
					unset( $wikiInfos[ $dbname ] );
				}
			}

			self::$wikiInfos = $wikiInfos;
		}

		return self::$wikiInfos;
	}

	public static function purgeConnections(): void {
		// PDO doesn't have an explicit close method.
		// Just dereference them.
		self::$dbConnections = array();
	}
}

