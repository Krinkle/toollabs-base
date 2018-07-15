<?php
/**
 * @covers Html
 */
class HtmlTest extends PHPUnit_Framework_TestCase {

	public function testRawElement() {
		$this->assertEquals(
			'<p><span>Text &amp;</span></p>',
			Html::rawElement('p', [], '<span>Text &amp;</span>')
		);
	}

	public function testElementText() {
		$this->assertEquals(
			'<h2>This &amp; &lt;that>.</h2>',
			Html::element('h2', [], 'This & <that>.')
		);
	}

	public function testElementVoid() {
		$this->assertEquals(
			'<link rel="stylesheet">',
			Html::element('link', [ 'rel' => 'stylesheet' ])
		);
	}

	public function testElementBoolAttr() {
		$this->assertEquals(
			'<input required="">',
			Html::element('input', [ 'required' => true, 'readonly' => false ])
		);
	}

	public function testElementArrayAttr() {
		$this->assertEquals(
			'<input class="foo bar">',
			Html::element('input', [ 'class' => [ 'foo', 'bar', '' ] ])
		);
	}
}
