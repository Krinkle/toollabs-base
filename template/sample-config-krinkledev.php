<?php
/**
 * Tool configuration (example for localhost development)
 */

$tool->setSettings(array(
	'foo' => 'example',
));

$kgCache->set(
	Krinkle\Toolbase\Cache::makeKey( 'toolbase-labsdb-dbinfos' ),
	array(
		'mywiki' => array(
			'dbname' => 'mywiki',
			'family' => 'wikipedia',
			'url' => 'http://mw.localhost:8080',
			'slice' => 's0.local'
		),
		'metawiki' => array(
			'dbname' => 'metawiki',
			'family' => 'special',
			'url' => 'https://meta.wikimedia.org',
			'slice' => 's0.local'
		)
	)
);
