<?php
/**
 * Copy this file and rename it to LocalConfig.php.
 */

$devDir = '/Users/' . get_current_user() . '/Development';
require_once $devDir . '/toollabs-base/LocalConfig-krinkledev.php';

$kgConf->remoteBase = '//krinkle.dev/mw-tool-example/public_html/base';

$kgCache->addStore(
        new FileCacheStore( array(
                'dir' => __DIR__ . '/cache',
        ) )
);
