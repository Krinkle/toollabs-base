## v2.1.0

2023-03-07

### Added

* Add `BaseTool::setSourceInfoGerrit()`.
  To advertise a Phabricator project,
  use `'sourceInfo' => [ 'issueTrackerUrl' => … ]` in the constructor.

### Changes

* `BaseTool::setSourceInfoGithub()` now adds keys to `sourceInfo` instead
  of replacing the array entirely. The unused `repoOwner` key was removed.

## v2.0.0
2022-06-19

This release requires PHP 7.3 or later.

### Changes

* All classes have been moved under the `Krinkle\Toolbase\` namespace.
* Most methods now have PHP 7 return types and parameter types.
* GlobalFunctions: `kfApiRequest` now returns JSON as associative array instead of stdClass.

### Removed

* InitTool: The global `user_agent` INI value is no longer modified.
  Note that requests made via the HttpRequest class are unaffected, as these already
  set their own user-agent based on metadata from the BaseTool object.

* Request: Support for `$kgConf->getCookiePrefix()` was removed.
  Toolforge now hosts tools under their own subdomain.
  If prefixes are required for other reasons, include them explicitly when calling `setCookie()`.

* GlobalFunctions: `kfCacheKey` was removed in favour of `Cache::makeKey()`.
* GlobalFunctions: `kfLogSection` was removed in favour of `Logger::createScope()`.
* GlobalFunctions: `kfLogFlush` was removed in favour of `Logger::flush()`.
* GlobalFunctions: `kfDbUsername` was removed in favour of `LabsDB::getReplicaUser()`.
* GlobalFunctions: `kfDbPassword` was removed in favour of `LabsDB::getReplicaPassword()`.
* GlobalConfig: This class was removed, its moved to `Logger`, `LabsDB`, and `BaseTool`.
* `$kgConf`: The GlobalConfig singleton was removed in favour of `BaseTool::getInstance()`.

## v1.0.2
2018-10-29

### Changes

* BaseTool: Change default license display to plain text.

## v1.0.1
2018-10-04

### Bug fixes

* Request: Avoid use of deprecated `count(null)` for PHP 7.2.
* HttpRequest: The default User-Agent string now includes
  the Composer package name instead of the legacy class name.

## v1.0.0
2018-07-15

### Removed

* BaseTool: The path to 'base' can no longer be overidden via kgConf->remoteBase.
  Use the BaseTool 'remoteBasePath' option to specify the main path for your tool.
  Within that path, "base/" should point to toollabs-base/public_html.
* GlobalConfig: The 'remoteBase' and 'userAgent' options were removed.
* GlobalConfig: The 'getRemoteBase' method was removed.

## v0.9.0
2018-07-15

### Improved

* BaseTool: Support PHP 7 Throwable errors in the error handler.
* page: Update Bootstrap to v3.3.7.
* page: Update jQuery to v3.2.1.
* page: Improved color contrast of footer text and footer links,
  to comply with WCAG 2.0 (AA).

### Removed

* MediaWiki classes GitInfo, Sanitizer and Html have been removed.
  The Html::element() method, however, was re-introduced as convenience
  shortcut using plain htmlspecialchars().

## v0.8.1
2017-02-27

### Bug fixes
* BaseTool: Make `revisionId` parameter optional. Prevent "Version: v" from
  being displayed on the page if there is no manual version set. Information
  from `setSourceInfoGithub` is not affected.

## v0.8.0
2016-08-26

### Enhancements
* [BREAKING CHANGE] HtmlSelect: Replaced with a different implementation.

## v0.7.0
2015-07-05

Installation as git submodule is no longer supported.
Use [Composer](https://getcomposer.org/) to install krinkle/toollabs-base.

### Maintenance
* Loading vendor/autoload.php will automatically load InitTool.php.

## v0.6.0
2015-07-03

### Enhancements
* Request: Add `getAllHeaders`, `getHeader` and `setHeader` methods.
* Request: Add `tryLastModified` method.
* page: Update jQuery to v1.11.2.
* page: Upgrade Bootstrap to v3.3.4.

### Bug fixes
* LabsDB: Ensure charset=utf8 is set in MySQL connection.
* HttpRequest: Set followRedirect=true.
* page: Return HTTP 500 on the exception page.

### Maintenance
* BaseTool: Load jQuery and Bootstrap from tools-static.wmflabs.org.

## v0.5.0
2015-01-08

### Enhancements
* Request: Add `getProtocol` method.
* GlobalFunctions: Add kfAlertText and kfAlertHtml for Bootstrap-style alerts.
* BaseTool: Add 'requireJS' option.
* New Sanitizer class (from MediaWiki).
* New LabsDB class. For interacting with the labsdb servers in Wikimedia Labs.
  Automatically resolves dbnames to mysql shards and re-uses connections.
* New HttpRequest class.
* Html: Update to version from MediaWiki 1.24.
* page: Update jQuery from v1.7.2 to v1.11.1.
* page: Rewrite using Bootstrap 3.
* page: New layout, header and footer generators for quick page creation
  (enabled by default, can be configured using the setLayout method).

### Other changes
* page: Load jQuery from code.jquery.com instead of ajax.googleapis.com.
* page: Remove legacy JS (jquery-placeholder, jquery-makeCollapsible).
* page: Remove legacy CSS.
* BaseTool: Remove 'krinklePrefix' option (obsolete).
* BaseTool: Remove 'revisionDate' option.
* BaseTool: Remove 'doHtmlHead' method (obsolete).
* BaseTool: Remove 'doStartBodyWrapper' method (obsolete).
* page: Remove jQuery UI (unused).
* page: Remove KR_MINIFY_ON and KR_MINIFY_OFF constants (obsolete).
* GlobalFunctions: Remove 'dieError' method (unused).
* GlobalFunctions: Remove 'kfMsgBlock' method.
* Remove all remaining Toolserver-related functionality.
* GlobalFunctions: Remove all functions and constants related to database
  interaction. Use the new LabsDB class and PDO methods instead of the legacy
  mysql_ functions.
* GlobalFunctions: Remove 'is_odd'.
* GlobalFunctions: Remove 'kfGetSvnInfo' and 'kfGetSvnrev'.
* GlobalFunctions: Remove 'kfQueryWMFAPI'.
* GlobalFunctions: Remove 'kfStripStr'.
* GlobalFunctions: Remove 'kfWikiHref'.
* GlobalFunctions: Remove 'wikiDataFromRow'.
* GlobalConfig: Use protected visiblity for members that should not be overridden by LocalConfig.
* GlobalConfig: Remove deprecated 'getDebugMode' method.
* GlobalConfig: Remove 'fullSimpleDatefmt' (unused).
* GlobalConfig: Remove 'fullReadableDatefmt' (unused).
* Request: Remove deprecated 'exists' method.
* Request: Remove deprecated 'getBool' method.

## v0.4.0
2014-04-29

### Changes
* Moved repository from https://github.com/Krinkle/ts-krinkle-basetool
  to https://github.com/Krinkle/toollabs-base (with redirect).
* BaseTool: Remove deprecated 'simplePath' option.
* BaseTool: Remove unused 'localBasePath' option.
* GlobalConfig: Remove 'localHome' member.
* GlobalConfig: Remove obsolete 'setDbUsername' method.
* GlobalConfig: Remove obsolete 'setDbPassword' method.
* GlobalFunctions: Remove deprecated getParam* and postParam functions.
* GlobalFunctions: Remove deprecated kfTag function. Use Html::element instead.
* GlobalFunctions: Remove obsolete 'kfConnectToolserverDB' function.
* GlobalFunctions: kfDbUsername/kfDbPassword now use replica.my.cnf (name of file provided by Tool Labs).
* GlobalFunctions: Use JSON instead of serialised PHP in kfQueryWMFAPI.
* Request: Deprecate `getBool` method. Use `hasKey` instead.

## v0.3.0
2014-04-28

### Changes
* page: Use Intuition to determine `<html dir>` and `<html lang>`.
* page: Drop default attributes for `<link>` and `<script>` per HTML5 style.
* page: Use protocol-relative urls.
* GlobalFunctions: kfGetWikiData functions now return the 'url' property as protocol-relative.
  A new 'canonical_url' property was added that still contains a full url including protocol
  (canonical url have the http-protocol, which matches the canonical url WMF uses).
* GlobalConfig: Deprecate 'getDebugMode' method in favour of 'isDebugMode'.
* GlobalConfig: Set `error_reporting( E_ALL );` and `ini_set( 'display_errors', 1 );` in debug mode.
* GlobalFunctions: getParamVar now uses `isset()` instead of `strlen()` to decide whether to use
  the fallback value. This makes `&foo=` yield an empty string.
* GlobalFunctions: Remove 'kfEscapeHTML' (was a wrapper for `htmlentities`). Use
  `htmlspecialchars()` instead.
* Move repository from https://svn.toolserver.org/svnroot/krinkle/trunk/common
  to https://github.com/Krinkle/ts-krinkle-basetool.git
* BaseTool: Deprecate 'simplePath' option.
* init: Implement LocalConfig.php.
* init: Move `KR_TSINT_START_INC` into LocalConfig.sample.php
* Request: Deprecate `exists` method. Use `getBool` instead.
* page: Load scripts in the document body instead of the head.
* BaseTool: Deprecate `getScripts` and `getStyles` methods. Use `expandUrlsArray` instead.
* Add `_template` directory to use as boilerplate for new tools.
* page: Integrate support for Git and GitHub in the page header (display currently deployed version,
  and generate links to repository viewer and issue tracker).
* page: Update jQuery from v1.5.1 to v1.7.2.
* page: Update jQuery UI from v1.8.11 to v1.8.19.
* jQuery UI: Use the default Smoothness theme instead of Wikimedia's custom Vector theme.
* GitInfo: Update to version from MediaWiki 1.21.
* GlobalFunctions: Add `http_response_code` polyfil for PHP 5.3.

## v0.2.0
2012-01-29

### Changes
* Add GlobalConfig.
* Add Html, HtmlSelect, Request. Based on MediaWiki.
* First published in Krinkle's Toolserver SVN repository under [krinkle]/trunk/common.
  https://svn.toolserver.org/svnroot/krinkle/trunk/common

## v0.1.0
2011-01-15

Initial version checked into Subversion as of 2011-01-15.
