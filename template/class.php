<?php
use Krinkle\Toolbase\KrToolBaseClass;
use Krinkle\Toolbase\Cache;

class ExampleTool extends KrToolBaseClass {

	protected $settingsKeys = [
		'foo',
	];

	protected function show() {
		global $kgBase;

		$kgBase->setHeadTitle( 'Home' );
		$kgBase->setLayout( 'header', [
			'titleText' => 'Welcome',
			'captionHtml' => 'Some text here',
		] );

		$kgBase->addOut( '<div class="container">' );

		$kgBase->addOut( kfAlertHtml( 'info', '<strong>Welcome!</strong> Hello there.' ) );

		$kgBase->addOut( 'Hello world' );

		// Cache::makeKey( 'exampletool-something', $variable );

		// Close container
		$kgBase->addOut( '</div>' );
	}
}
