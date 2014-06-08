<?php
/**
 * Copy this file and rename it to LocalConfig.php.
 */

define( 'KR_TSINT_START_INC', dirname( dirname( dirname( __DIR__ ) ) ) . '/intuition/ToolStart.php' );

$kgConf->remoteBase = '//localhost.dev/Krinkle/mw-tool-example/public_html/basetool';

$kgCache->addStore(
        new FileCacheStore( array(
                'dir' => __DIR__ . '/cache',
        ) )
);
