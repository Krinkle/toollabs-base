<?php
/**
 * @covers HtmlSelect
 */
class HtmlSelectTest extends PHPUnit_Framework_TestCase {

	public function testConstructor() {
		$select = new HtmlSelect([
			'bar',
			'foo',
		]);
		$this->assertEquals(
			'<select><option value="bar">bar</option><option value="foo">foo</option></select>',
			$select->getHTML()
		);
	}

	public function testDefault() {
		$select = new HtmlSelect([
			'bar',
			'foo',
		]);
		$select->setDefault( 'foo' );
		$this->assertEquals(
			'<select><option value="bar">bar</option><option value="foo" selected="">foo</option></select>',
			$select->getHTML()
		);
	}

	public function testLabel() {
		$select = new HtmlSelect([
			'b' => 'Bar',
			'f' => 'Foo',
		]);
		$this->assertEquals(
			'<select><option value="b">Bar</option><option value="f">Foo</option></select>',
			$select->getHTML()
		);
	}

	public function testName() {
		$select = new HtmlSelect();
		$select->setName( 'foo' );
		$this->assertEquals(
			'<select name="foo"></select>',
			$select->getHTML()
		);
	}

	public function testAll() {
		$select = new HtmlSelect([
			'b' => 'Bar',
			'f' => 'Foo',
			'b' => 'Baz',
		]);
		$select->setName( 'foo' );
		$select->setDefault( 'b' );
		$this->assertEquals(
			'<select name="foo"><option value="b" selected="">Baz</option><option value="f">Foo</option></select>',
			$select->getHTML()
		);
	}
}
