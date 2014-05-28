<?php
/**
 * Main class
 *
 * @package mw-tool-example
 */
class Example extends KrToolBaseClass {

	protected $settingsKeys = array(
		'foo',
		'bar',
		'baz',
	);

	protected function show() {
		global $kgBaseTool;

		$kgBaseTool->setHeadTitle( 'Home' );
		$kgBaseTool->setLayout( 'header', array(
			'titleText' => 'Welcome',
			'captionHtml' => 'Some text here',
		) );

		$kgBaseTool->addOut( '<div class="container">' );

		$kgBaseTool->addOut( kfAlertHtml( 'info', '<strong>Welcome!</strong> Hello there.' ) );

		$kgBaseTool->addOut( 'Hello world' );

		// Close wrapping container
		$kgBaseTool->addOut( '</div></div>' );
	}
}
