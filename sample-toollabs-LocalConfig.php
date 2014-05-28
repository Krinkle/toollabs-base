<?php
/**
 * Copy this file and rename it to LocalConfig.php.
 */

define( 'KR_TSINT_START_INC', '/data/project/intuition/src/Intuition/ToolStart.php' );

$kgConf->remoteBase = '//tools.wmflabs.org/example/basetool';

if ( true ) {
        $kgCache->addStore(
                new RedisCacheStore( array(
                        'preset' => 'toollabs',
                        'prefix' => 'tools.example:',
                ) )
         );
} else {
        $kgCache->addStore(
                new FileCacheStore( array(
                        'dir' => '/data/project/example/cache/objects',
                ) )
        );
}
