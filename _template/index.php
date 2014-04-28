<?php
/**
 * Example
 *
 * Created on January 1, 2014
 *
 * @license http://krinkle.mit-license.org/
 * @author Timo Tijhof, 2010-2014
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
require_once( __DIR__ . '/../lib/basetool/InitTool.php' );
require_once( KR_TSINT_START_INC );

// Class for this tool
#require_once( __DIR__ . '/class.php' );
#$kgTool = new KrExample();

// Local settings
#require_once( __DIR__ . '/local.php' );

$I18N = new TsIntuition( 'example' );

$toolConfig = array(
	'displayTitle' => 'Example',
	'krinklePrefix' => false,
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.0.0',
	'I18N' => $I18N,
);

$kgTool = BaseTool::newFromArray( $toolConfig );
#$kgTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-example', __DIR__ );

$kgTool->doHtmlHead();
$kgTool->doStartBodyWrapper();


/**
 * Setup
 * -------------------------------------------------
 */


/**
 * Output
 * -------------------------------------------------
 */
$kgTool->addOut( 'Hello world' );


/**
 * Close up
 * -------------------------------------------------
 */
$kgTool->flushMainOutput();
