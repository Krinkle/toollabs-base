<?php
use org\bovigo\vfs\vfsStream;

class FileCacheTest extends CacheTestBase {
	protected static $root;

	public static function setUpBeforeClass() {
		self::$root = vfsStream::setup( 'test/cache' );
	}

	protected function setUp() {
		parent::setUp();

		$this->cache = new FileCacheBackend(array(
			'dir' => vfsStream::url( 'test/cache' )
		));
	}
}
