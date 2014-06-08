<?php

class Wiki {
	protected static $instances = array();

	protected $dbname;

	protected static $siprops = array(
		'general',
		'namespaces',
	);

	/**
	 * @param string $dbname
	 * @return Wiki
	 */
	public static function byDbname( $dbname ) {
		if ( isset( $instances[ $dbname ] ) ) {
			return $instances[ $dbname ];
		}

		$object = new Wiki( $dbname );
		$instances[ $dbname ] = $object;
		return $object;
	}

	/**
	 * @param string $dbname
	 */
	protected function __construct( $dbname ) {
		$this->dbname = $dbname;
	}

	/**
	 * @return Array
	 */
	public function getWikiInfo() {
		return LabsDB::getDbInfo( $this->dbname );
	}

	/**
	 * @return object|bool
	 */
	public function fetchSiteInfo() {
		$section = new KfLogSection( __METHOD__ );

		$wikiInfo = $this->getWikiInfo();
		$data = kfApiRequest( $wikiInfo['url'], array(
			'meta' => 'siteinfo',
			'siprop' => implode( '|', self::$siprops ),
		) );
		if ( !$data || isset( $data->error ) || !isset( $data->query ) ) {
			return false;
		}
		foreach ( self::$siprops as $siprop ) {
			if ( !isset( $data->query->$siprop ) ) {
				return false;
			}
		}
		return $data->query;
	}

	/**
	 * @param string $prop See self::$siprops
	 * @return array|null
	 */
	public function getSiteInfo( $prop ) {
		global $kgCache;

		$key = kfCacheKey( 'base', 'mwapi', $this->dbname, 'siteinfo', $prop );
		$value = $kgCache->get( $key );
		if ( $value === false ) {
			if ( !in_array( $prop, self::$siprops ) ) {
				return null;
			}
			$values = $this->fetchSiteInfo();
			if ( $values !== false ) {
				foreach ( self::$siprops as $siprop ) {
					$kgCache->set(
						kfCacheKey( 'base', 'mwapi', $this->dbname, 'siteinfo', $siprop  ),
						$values->$siprop,
						3600
					);
				}
				$value = $values->$prop;
			} else {
				// Don't try again within this request nor for the next few
				$value = null;
				$kgCache->set( $key, $value, 60 * 5 );
			}
		}
		return $value;
	}

	/**
	 * @return array
	 */
	public function getNamespaces() {
		static $namespaces = null;
		if ( $namespaces === null ) {
			$namespaces = array();
			$data = $this->getSiteInfo( 'namespaces' );
			if ( $data ) {
				foreach ( $data as $i => &$ns ) {
					$namespaces[ $ns->id ] = $ns->{"*"};
				}
			}
		}
		return $namespaces;
	}

}
