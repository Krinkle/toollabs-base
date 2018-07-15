[![Packagist](https://img.shields.io/packagist/v/Krinkle/toollabs-base.svg?style=flat)](https://packagist.org/packages/Krinkle/toollabs-base) [![Build Status](https://travis-ci.org/Krinkle/toollabs-base.svg?branch=master)](https://travis-ci.org/Krinkle/toollabs-base)

# Tool Labs Base

## Install

It's recommended you use [Composer](https://getcomposer.org).

* Run `composer require Krinkle/toollabs-base`.
* Create a symlink from "base/" in your application's root public directory (e.g. public_html) to `vendor/krinkle/toollabs-base/public_html`.
* Include `vendor/autoload.php` in your program.

## Example

<pre lang="php">
require_once __DIR__ . '/vendor/autoload.php';
// require_once __DIR__ . '/config.php';

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => 'Example',
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
) );
$kgBase->flushMainOutput();
</pre>

See [Template](/template) for an example.

## Versioning

This library follows the [Semantic Versioning guidelines](https://semver.org/).

Releases will be numbered in the following format: `<major>.<minor>.<patch>`.
