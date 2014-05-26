## Tool Labs Base 0.3

THIS IS NOT A RELEASE YET

### Changes since 0.2

* ([TS-JIRA KRINKLE-6](https://jira.toolserver.org/browse/KRINKLE-6)) Use
  Toolserver Intuition instance to determine `<html dir>` and `<html lang>`.
* Droppping default attributes for `<link>` and `<script>`. We're using
  HTML5 structure.
* ([TS-JIRA KRINKLE-8](https://jira.toolserver.org/browse/KRINKLE-8)) Output protocol-url and support canonical urls
  `kfGetWikiData` functions now return the 'url' property as protocol-relative.
  A new 'canonical_url' property was added that still contains a full url
  including protocol (canonical url have the http-protocol, which matches the
  canonical url WMF uses).
* Deprecated `GlobalConfig::getDebugMode()` in favor of `GlobalConfig::isDebugMode()`.
* If debug mode is enabled, InitTools sets `error_reporting( E_ALL );` and
  `ini_set( 'display_errors', 1 );`.
* getParamVar now uses `isset()` instead of `strlen()` to decide wether to use the
  fallback value. So that &foo= will give an empty string as intended.
* Removed global `kfEscapeHTML()` (was a wrapper for
  `htmlentities(.., ENT_QUOTES, UTF-8)`). No need to entity encode everything,
  plain calls to `htmlspecialchars()` are good enough.
* Moved repository from https://svn.toolserver.org/svnroot/krinkle/trunk/common
  to https://github.com/Krinkle/ts-krinkle-basetool.git
* Deprecated BaseTool config option `simplePath`.
* Added support for LocalConfig.php
* Moved `KR_TSINT_START_INC` into LocalConfig.sample.php
* Deprecated `Request::exists`, use `Request::getBool` instead.
* BaseTool now loads scripts in the body instead of the head.
* BaseTool gets support for head-scripts.
* Deprecated `BaseTool::getScripts` and `BaseTool::getStyles`, use
  `BaseTool::expandUrlsArray` instead.

## Krinkle BaseTool 0.2
2012-01-29

### Changes since 0.1
* Added GlobalConfig.
* Added Html, HtmlSelect, Request. Based on MediaWiki.
* First published in Krinkle's Toolserver SVN repository under [krinkle]/trunk/common.
  https://svn.toolserver.org/svnroot/krinkle/trunk/common

## Krinkle BaseTool 0.1
2011-01-15

Initial version checked into Subversion as of 2011-01-15.
