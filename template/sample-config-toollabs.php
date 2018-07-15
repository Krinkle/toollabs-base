<?php
/**
 * Tool configuration (example for a tool in Tool Labs)
 */

$kgConf->cookiePrefix = 'example_';

$kgCache->addStore(
	new RedisCacheStore( array(
		'preset' => 'toollabs',
		'prefix' => 'tools.example:',
	) )
);
