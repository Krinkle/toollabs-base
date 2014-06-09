<?php
/**
 * Copy this file and rename it to LocalConfig.php.
 */

$devDir = dirname( dirname( dirname( __DIR__ ) ) );
require_once $devDir . '/toollabs-base/LocalConfig-krinkledev.php';

$kgConf->remoteBase = '//krinkle.dev/Krinkle/mw-tool-example/public_html/basetool';

$kgCache->addStore(
        new FileCacheStore( array(
                'dir' => __DIR__ . '/cache',
        ) )
);
