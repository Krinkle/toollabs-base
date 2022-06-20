<?php
/**
 * Tool configuration (example for Toolforge)
 */

$kgCache->addStore(
	new RedisCacheStore( array(
		'preset' => 'toollabs',
		'prefix' => 'tools.example:',
	) )
);
