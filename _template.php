<?php
/**
 * _B_L_A_N_K_
 * Created on June 2, 2012
 *
 * @author Timo Tijhof <krinklemail@gmail.com>, 2012
 * @license CC-BY-SA 3.0 Unported: creativecommons.org/licenses/by/3.0/
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
require_once( '/home/krinkle/common/InitTool.php' );
require_once( KR_TSINT_START_INC );
// Sandbox
#require_once( '../ts-krinkle-basetool/InitTool.php' );
#require_once( KR_TSINT_SANDBOX_START_INC );

$I18N = new TsIntuition( 'general' );

$toolConfig = array(
	'displayTitle'	=> '_B_L_A_N_K_',
	'simplePath'	=> '/tmp/index.php',
	'revisionId'	=> '0.0.0',
	'revisionDate'	=> '1970-01-01',
	'I18N'			=> $I18N,
);

$Tool = BaseTool::newFromArray( $toolConfig );

$Tool->doHtmlHead();
$Tool->doStartBodyWrapper();


/**
 * Database connections
 * -------------------------------------------------
 */
// kfConnectRRServerByDBName( 'commonswiki_p' );
// kfConnectToolserverDB();
// kfConnectToAllWikiDbServers();


/**
 * Settings
 * -------------------------------------------------
 */
$toolSettings = array(

);

// Parameters
$Params = array(
	'foo' => $kgReq->getVal( 'foo' ),
);


/**
 * Close up
 * -------------------------------------------------
 */
// kfCloseAllConnections();
$Tool->addOut( '<script>document.write( "Base Template JS!" );</script>' );
$Tool->flushMainOutput();

