<?php
/**
 * LocalConfig for BaseTool install on krinkle.dev.
 */

define( 'KR_TSINT_START_INC', $devDir . '/intuition/ToolStart.php' );

$kgCache->set(
	kfCacheKey( 'base', 'labsdb', 'meta', 'dbinfos' ),
	array(
		'krinklewiki' => array(
			'dbname' => 'krinklewiki',
			'family' => 'wikipedia',
			'url' => 'http://alpha.wikipedia.krinkle.dev',
			'slice' => 's0.local'
		)
	)
);
