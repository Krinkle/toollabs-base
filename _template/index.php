<?php
/**
 * _B_L_A_N_K_
 * Created on January 1, 2012
 *
 * @author Timo Tijhof <krinklemail@gmail.com>, 2012
 * @license CC-BY-SA 3.0 Unported: creativecommons.org/licenses/by/3.0/
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
require_once( __DIR__ . '/../ts-krinkle-basetool/InitTool.php' );
require_once( KR_TSINT_START_INC );

// Class for this tool
#require_once( __DIR__ . '/class.php' );
#$kgTool = new KrBLANK();

// Local settings
#require_once( __DIR__ . '/local.php' );

$I18N = new TsIntuition( '_B_L_A_N_K_' );

$toolConfig = array(
	'displayTitle'     => '_B_L_A_N_K_',
	'remoteBasePath'   => $kgConf->getRemoteBase() . '/_B_L_A_N_K_/',
	'localBasePath'    => __DIR__,
	'revisionId'       => '0.0.0',
	'revisionDate'     => '1970-01-01',
	'I18N'             => $I18N,
);

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
#$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'ts-krinkle-_B_L_A_N_K_' );

$kgBaseTool->doHtmlHead();
$kgBaseTool->doStartBodyWrapper();


/**
 * Setup
 * -------------------------------------------------
 */


/**
 * Database connections
 * -------------------------------------------------
 */
#kfConnectRRServerByDBName( 'commonswiki_p' );
#kfConnectToolserverDB();
#kfConnectToAllWikiDbServers();


/**
 * Output
 * -------------------------------------------------
 */
$kgBaseTool->addOut( 'Hello world' );
$kgBaseTool->addOut( '<script>jQuery(document).ready(function ($) {
	$("body").append( "<em>JavaScript is working</em>" );
} );</script>' );


/**
 * Close up
 * -------------------------------------------------
 */
#kfCloseAllConnections();
$kgBaseTool->flushMainOutput();
