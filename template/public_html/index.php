<?php
/**
 * Main index
 *
 * @copyright 2018 [Your Name]
 */

/**
 * Configuration
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';

$tool = new ExampleTool();
if ( file_exists( __DIR__ . '/../config.php' ) ) {
	// Optional overrides
	require __DIR__ . '/../config.php';
}

$I18N = new Intuition( 'example' );

$kgBase = BaseTool::newFromArray( array(
	'displayTitle' => 'Example',
	'revisionId' => '0.0.0',
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
	'I18N' => $I18N,
) );
$kgBase->setSourceInfoGithub( 'Krinkle', 'mw-tool-example', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();
