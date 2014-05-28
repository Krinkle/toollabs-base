<?php
/**
 * @since 0.5.0
 * @author Krinkle, 2014
 * @license Public domain, WTFPL
 * @package toollabs-base
 */
class LabsDB {
	/**
	 * @var array PDO objects keyed by hostname
	 */
	protected static $dbConnections = array();

	/**
	 * @var Array Cache of db information
	 */
	protected static $dbInfos = array();

	/**
	 * @var bool Whether $dbInfos contains everything
	 */
	protected static $dbInfoHasAll = false;

	/**
	 * Get a database connection by hostname
	 *
	 * Returns a previously established connection or initiates a new one.
	 *
	 * @return PDO
	 * @throws If connection failed
	 */
	public static function getConnection( $hostname, $dbname ) {
		if ( isset( self::$dbConnections[ $hostname ] ) ) {
			$conn = self::$dbConnections[ $hostname ];
		} else {
			try {
				$conn = new PDO(
					'mysql:host=' . $hostname . ';dbname=' . $dbname . '_p;',
					kfDbUsername(),
					kfDbPassword()
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

	/**
	 * @return PDO
	 */
	public static function getMetaDB() {
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
	 *
	 * @return PDO
	 * @throws If dbname could not be found
	 */
	public static function getDB( $dbname ) {
		if ( $dbname === 'meta' ) {
			return self::getMetaDB();
		}

		$wikiInfo = self::getDbInfo( $dbname );
		if ( !$wikiInfo['slice'] ) {
			throw new Exception( "Incomplete database information for '$dbname'" );
		}

		return self::getConnection( $wikiInfo['slice'], $dbname );
	}

	/**
	 * @param PDO $conn
	 */
	public static function selectDB( $conn, $dbname ) {
        $stmt = $conn->prepare( 'USE `' . $dbname . '_p`;' );
        $stmt->execute();
        unset( $stmt );
	}

	/**
	 *
	 * @param PDO $conn
	 * @param string $db Database name
	 * @param string $sql SQL query (with placeholders)
	 * @param array $bindings Bindings of type PDO::PARAM_STR. Use prepare() if you
	 *  need different types or if you want to execute multiple times.
	 * @return array Rows
	 */
	public static function query( $conn, $sql, $bindings = null ) {
		if ( $bindings ) {
			$m = $conn->prepare( $sql );
			$m->execute( $bindings );
		} else {
			$m = $conn->query( $sql );
			$m->execute();
		}
		return $m->fetchAll( PDO::FETCH_ASSOC );
	}

	/**
	 * Get information for all (replicated) open wikis.
	 *
	 * See https://wikitech.wikimedia.org/wiki/Nova_Resource:Tools/Help#Metadata_database
	 * @return Array
	 */
	public static function getAllWikiInfos() {
		static $wikiInfos;

		if ( $wikiInfos !== null ) {
			return $wikiInfos;
		}

		$rows = self::query( self::getMetaDB(),
			'SELECT dbname, family, url, slice
			FROM wiki
			WHERE is_closed = 0
			ORDER BY url ASC'
		);
		foreach ( $rows as &$row ) {
			self::$dbInfos[ $row['dbname'] ] = $row;
		}
		self::$dbInfoHasAll = true;

		$wikiInfos = self::$dbInfos;
		// Filter out NULL values for url as meta_p.wiki also contains dbname='centralauth'
		// which has 'url' set to NULL (see wmbug.com/65789)
		// Could simply be done in SQL, but we want to cache all db infos, so we do both.
		foreach ( $wikiInfos as $i => &$wikiInfo ) {
			if ( !$wikiInfo['url'] ) {
				unset( $wikiInfos[ $i ] );
			}
		}

		return $wikiInfos;
	}

	/**
	 * @param string $dbname
	 * @return Array
	 */
	public static function getDbInfo( $dbname ) {
		if ( !isset( self::$dbInfos[ $dbname ] ) ) {
			if ( self::$dbInfoHasAll ) {
				throw new Exception( "Unable to find '$dbname'" );
			}
			$info = self::query(
				self::getMetaDB(),
				'SELECT dbname, family, url, slice
					FROM wiki
					WHERE is_closed = 0
					AND dbname = :dbname
					LIMIT 1',
				array(
					':dbname' => $dbname,
				)
			);
			if ( !$info ) {
				throw new Exception( "Unable to find '$dbname'" );
			}
			self::$dbInfos[ $dbname ] = $info;
		}
		return self::$dbInfos[ $dbname ];
	}

	public static function purgeConnections() {
		// PDO doesn't have an explicit close method.
		// Just dereference them.
		self::$dbConnections = array();
	}
}
