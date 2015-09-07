<?php
/**
 * Example configuration
 */

$devDir = '/Users/' . get_current_user() . '/Development';
define( 'KR_TSINT_START_INC', $devDir. '/intuition/ToolStart.php' );

$kgConf->remoteBase = '//krinkle.dev/toollabs-base/template/public_html/base';
#$kgConf->remoteBase = '//krinkle.dev/mw-tool-example/public_html/base';

$kgCache->set(
	kfCacheKey( 'base', 'labsdb', 'meta', 'dbinfos' ),
	array(
		'krinklewiki' => array(
			'dbname' => 'krinklewiki',
			'family' => 'wikipedia',
			'url' => 'http://alpha.wikipedia.krinkle.dev',
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

$kgCache->addStore(
        new FileCacheStore( array(
                'dir' => __DIR__ . '/cache',
        ) )
);
