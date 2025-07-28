<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';
$cfg['directory_list'][] = 'vendor/';
$cfg['exclude_analysis_directory_list'][] = 'vendor/';

$cfg['minimum_target_php_version'] = '7.4';

$cfg['autoload_internal_extension_signatures'] = [
	'redis' => '.phan/internal_stub_redis.phan_php',
];

$cfg['suppress_issue_types'] = [
	// Disabled, indefinitely
	'PhanUnusedVariable', // Incompatible with wikimedia/scoped-callback
	'SecurityCheck-LikelyFalsePositive',
	'PhanUnusedProtectedNoOverrideMethodParameter',
	'PhanDeprecatedFunctionInternal', // Redis::delete alias (PHP 7.4-8.3, fine on 8.4?)

	// Maybe later
	'MediaWikiNoBaseException',
	'PhanThrowTypeAbsent', // Missing @throws docs
];
return $cfg;
