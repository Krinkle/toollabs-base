<?php
/**
 * InitTool.php :: Main initialization file.
 * This is the one file individual Tool scripts include to start.
 *
 * @since 0.1
 * @author Krinkle <krinklemail@gmail.com>, 2010 - 2012
 *
 * @package KrinkleToolsCommon
 * @license Public domain, WTFPL
 */

require_once( __DIR__ . '/GlobalDefinitions.php' );
require_once( __DIR__ . '/GlobalConfig.php' );

// Never overwrite $kgConfig, but if not set already
// make sure GlobalConfig is initiated
if ( !isset( $kgConf ) || !is_object( $kgConf ) ) {
	$kgConf = new GlobalConfig();
}

require_once( __DIR__ . '/Request.php' );

// POST overrides GET data
// We don't use $_REQUEST here to avoid interference from cookies...
$kgReq = new Request( $_POST + $_GET );

function kfIncludeMwHtml(){
	global $wgWellFormedXml, $wgHtml5, $wgJsMimeType;
	$wgWellFormedXml = true;
	$wgHtml5 = true;
	$wgJsMimeType = 'text/javascript';

	// MediaWiki's /includes/Html.php
	// Patched to remove the Html::htmlHeader() method.
	require_once( __DIR__ . '/mw/Html.php' );
}

kfIncludeMwHtml();

require_once( __DIR__ . '/HtmlSelect.php' );

require_once( __DIR__ . '/GlobalFunctions.php' );

// Must be after GlobalFunctions
$kgConf->initConfig();

// Debug
if ( $kgConf->isDebugMode() ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
}

require_once( __DIR__ . '/BaseTool.php' );
// $Tool = BaseTool::newFromArray( array( /* ... */ ) );
