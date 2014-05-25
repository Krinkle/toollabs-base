<?php
/**
 * Main index
 *
 * @package mw-tool-example
 * @license http://krinkle.mit-license.org/
 * @author Timo Tijhof, 2010-2014
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
#require_once __DIR__ . '/../lib/basetool/InitTool.php';
require_once __DIR__ . '/../InitTool.php';
require_once KR_TSINT_START_INC;

// Class for this tool
#require_once __DIR__ . '/class.php';
#$kgTool = new KrExample();

// Local settings
#require_once __DIR__ . '/config.php';

$I18N = new TsIntuition( 'example' );

$toolConfig = array(
	'displayTitle' => 'Example',
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.0.0',
	'I18N' => $I18N,
);

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
#$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-example', __DIR__ );

/**
 * Setup
 * -------------------------------------------------
 */


/**
 * Output
 * -------------------------------------------------
 */
$kgBaseTool->setHeadTitle( 'Home' );
$kgBaseTool->setLayout( 'header', array(
	'titleText' => 'Welcome',
	'captionHtml' => 'Some text here',
) );

$kgBaseTool->addOut( '<div class="container"><div class="row">' );

$kgBaseTool->addOut( kfAlertHtml( 'info', '<strong>Welcome!</strong> Hello there.' ) );

$kgBaseTool->addOut( 'Hello world' );

// Close wrapping container/row
$kgBaseTool->addOut( '</div></div>' );


/**
 * Close up
 * -------------------------------------------------
 */
$kgBaseTool->flushMainOutput();
