<?php
/**
 * Main index
 *
 * @author Timo Tijhof, 2013-2014
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-example
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
require_once __DIR__ . '/class.php';
$kgTool = new Example();

// Local settings
#require_once __DIR__ . '/config.php';

$I18N = new Intuition( 'example' );

$toolConfig = array(
	'displayTitle' => 'Example',
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.0.0',
	'I18N' => $I18N,
);

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-example', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$kgTool->run();
$kgBaseTool->flushMainOutput();
