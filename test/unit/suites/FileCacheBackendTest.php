<?php
use org\bovigo\vfs\vfsStream;
use Krinkle\Toolbase\FileCacheStore;

class FileCacheStoreTest extends CacheTestCase {
	protected static $root;

	public static function setUpBeforeClass(): void {
		self::$root = vfsStream::setup( 'test/cache' );
	}

	protected function setUp(): void {
		parent::setUp();

		$this->cache = new FileCacheStore(array(
			'dir' => vfsStream::url( 'test/cache' )
		));
	}
}
