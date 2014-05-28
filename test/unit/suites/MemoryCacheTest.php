<?php

class MemoryCacheTest extends CacheTestBase {

	protected function setUp() {
		parent::setUp();
		$this->cache = new MemoryCacheBackend();
	}

	public function testPersistanceGet() {
		// Not kept, memory does not persist
		$this->assertFalse( $this->cache->get( 'keep' ) );
	}
}
