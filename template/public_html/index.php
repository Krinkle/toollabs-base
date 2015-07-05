<?php
/**
 * Main index
 *
 * @author Timo Tijhof, 2015
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-example
 */

/**
 * Configuration
 * -------------------------------------------------
 */

// BaseTool & Localization
require_once __DIR__ . '/../vendor/autoload.php';
require_once KR_TSINT_START_INC;

// Class for this tool
require_once __DIR__ . '/../class.php';
$tool = new ExampleTool();

// Local settings
#require_once __DIR__ . '/../config.php';

$I18N = new Intuition( 'example' );

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => 'Example',
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.0.0',
	'I18N' => $I18N,
) );
$kgBase->setSourceInfoGithub( 'Krinkle', 'mw-tool-example', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();
