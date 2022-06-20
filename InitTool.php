<?php
/**
 * @license 0BSD, Unlicense
 * @since 0.1.0
 * @file
 */

use Krinkle\Toolbase\Cache;
use Krinkle\Toolbase\MemoryCacheStore;
use Krinkle\Toolbase\Request;

require_once __DIR__ . '/src/GlobalFunctions.php';

global $kgReq;
$kgReq = new Request( $_POST + $_GET );

global $kgCache;
$kgCache = new Cache( array(
	new MemoryCacheStore()
) );
$kgCache->enableHarvest();
