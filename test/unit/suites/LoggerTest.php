<?php
use Krinkle\Toolbase\Logger;

class LoggerTest extends PHPUnit\Framework\TestCase {

	public function setUp(): void {
		Logger::setEnabled( true );
		Logger::clearBuffer();
	}

	public function tearDown(): void {
		Logger::setEnabled( false );
	}

	public function testBasic() {
		$scope1 = Logger::createScope( 'Foo' );
		Logger::debug( 'Hello' );
		$scope2 = Logger::createScope( 'Bar' );
		Logger::debug( 'there' );
		$scope2 = null;
		Logger::debug( 'world' );
		$this->assertEquals(
			"Foo> Hello\nBar> there\nFoo> world\n",
			Logger::getBuffer()
		);
		$this->assertEquals(
			"Foo> Hello\nBar> there\nFoo> world\n",
			Logger::flush( Logger::MODE_TEXT )
		);
	}
}
