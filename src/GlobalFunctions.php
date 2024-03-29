<?php

use Krinkle\Toolbase\Html;
use Krinkle\Toolbase\HttpRequest;
use Krinkle\Toolbase\LabsDB;
use Krinkle\Toolbase\Logger;

/**
 * @deprecated since 2.0.0 Use Logger::debug() instead.
 */
function kfLog( $msg ): void {
	Logger::debug( $msg );
}

function kfEscapeRE( $str ) {
	return preg_quote( $str, '/' );
}

function kfStrLastReplace( $search, $replace, $subject ) {
	return substr_replace( $subject, $replace, strrpos( $subject, $search ), strlen( $search ) );
}

/**
 * HTML templates
 * -------------------------------------------------
 */

/**
 * @param string $type One of success, info, warning, or danger.
 * @param string $text
 * @return string Html
 */
function kfAlertText( $type, $text ) {
	$class = 'alert';
	$class .= $type ? ' alert-' . $type : ' alert-default';
	return Html::element( 'div', array( 'class' => $class ), $text );
}

function kfAlertHtml( $type, $html ) {
	$class = 'alert';
	$class .= $type ? ' alert-' . $type : ' alert-default';
	return Html::rawElement( 'div', array( 'class' => $class ), $html );
}

function kfGetAllWikiOptionHtml( $options = array() ) {
	$scope = Logger::createScope( __FUNCTION__ );

	// Options
	$defaultOptions = array(
		'group' => true,
		'current' => null,
		'exclude' => array(),
	);
	$options += $defaultOptions;

	$wikiInfos = LabsDB::getAllWikiInfos();
	$optionsHtml = '';
	$optionsHtmlGroups = array();
	foreach ( $wikiInfos as $wikiInfo ) {
		if ( in_array( $wikiInfo['dbname'], $options['exclude'] ) ) {
			continue;
		}
		$hostname = parse_url( $wikiInfo['url'], PHP_URL_HOST );
		if ( !$hostname ) {
			Logger::debug( "Unable to parse hostname of {$wikiInfo['dbname']}: '{$wikiInfo['url']}'" );
			continue;
		}
		$optionHtml = Html::element( 'option', array(
			'value' => $wikiInfo['dbname'],
			'selected' => $wikiInfo['dbname'] === $options['current'],
			'data-url' => $hostname
		), $hostname );
		if ( $options['group'] ) {
			if ( !isset( $optionsHtmlGroups[ $wikiInfo['family'] ] ) ) {
				$optionsHtmlGroups[ $wikiInfo['family'] ] = '';
			}
			$optionsHtmlGroups[ $wikiInfo['family'] ] .= $optionHtml;
		} else {
			$optionsHtml .= $optionHtml;
		}

	}

	if ( $options['group'] ) {
		foreach ( $optionsHtmlGroups as $family => $groupHtml ) {
			$optionsHtml .=
				Html::rawElement( 'optgroup',
					array( 'label' => $family ),
					$groupHtml
				);

		}
	}

	return $optionsHtml;
}

/**
 * API Builder
 * -------------------------------------------------
 */

/**
 * Sanatize callback
 *
 * @param string $str
 * @return string
 */
function kfSanatizeJsCallback( $str ) {
	// Valid: foo.bar_Baz["quux"]['01']
	return preg_replace( "/[^a-zA-Z0-9_\.\]\[\'\"]/", '', $str );
}

/**
 * Build API response
 *
 * @param string $specialFormat If $format is set to this format this function will not output
 *  anything and return true. This can be used for a GUI front-end.
 */
function kfApiExport( $data = array( 'krApiExport' => 'Example' ), $format = 'dump', $callback = null, $specialFormat = null ) {

	if ( $specialFormat !== null && $format === $specialFormat ) {
		return true;
	}

	switch ( $format ) {
		case 'php':
			header( 'Content-Type: application/vnd.php.serialized; charset=utf-8', true );
			// // @phan-suppress-next-line SecurityCheck-XSS
			print serialize( $data );
			exit;

		case 'json':
		case 'jsonp':

			// Serve as AJAX object object or JSONP callback
			if ( $callback === null ) {
				header( 'Content-Type: application/json; charset=utf-8', true );
				print json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			} else {
				header( 'Content-Type: text/javascript; charset=utf-8', true );

				// Sanatize callback
				$callback = kfSanatizeJsCallback( $callback );
				print $callback . '(' . json_encode( $data ) .')';
			}
			exit;

		case 'dump':

			// No text/plain due to IE7 mime-type sniff bug causing html parsing
			header( 'Content-Type: text/text; charset=utf-8', true );
			var_dump( $data );
			exit;

		default:
			// HTTP 400 Bad Request
			http_response_code( 400 );
			header( 'Content-Type: text/plain; charset=utf-8', true );
			print 'Invalid format.';
			exit;
	}

}

function kfApiFormats() {
	return array(
		'json' => array(
			'params' => array(
				'format' => 'json',
			),
			'label' => 'JSON'
		),
		'jsonp' => array(
			'params' => array(
				'format' => 'jsonp',
				'callback' => 'example',
			),
			'label' => 'JSON-P'
		),
		'php' => array(
			'params' => array(
				'format' => 'php',
			),
			'label' => 'Serialized PHP'
		),
		'dump' => array(
			'params' => array(
				'format' => 'dump',
			),
			'label' => 'Dump'
		),
	);
}

/**
 * Version control
 * -------------------------------------------------
 */

/**
 * @param array $options (optional):
 * - string dir: Full path to where the git repository is.
 *    By default it will assume the current directory is already the git repository.
 * - string checkout: Will be checked out and reset to its HEAD. Otherwise stays in
 *    the current branch and resets to its head.
 * - unlock: Whether or not it should ensure there is no lock.
 *
 * @return bool|string Boolean false on failure, or a string
 * with the output of the commands.
 */
function kfGitCleanReset( array $options = [] ) {
	$orgPath = __DIR__;

	if ( isset( $options['dir'] ) ) {
		if ( !is_dir( $options['dir'] ) ) {
			return false;
		}

		// Navigate to the repo so we can execute the git commands
		chdir( $options['dir'] );
	}

	$out = '';
	$cmds = array();
	if ( isset( $options['unlock'] ) && $options['unlock'] ) {
		$cmds[] = 'rm -f .git/index.lock';
	}
	$cmds[] = 'git clean -q -d -x -f';
	$cmds[] = 'git reset -q --hard';
	if ( isset( $options['checkout'] ) ) {
		$cmds[] = 'git checkout -q -f ' . kfEscapeShellArg( $options['checkout'] );
	}

	foreach ( $cmds as $cmd ) {
		$out .= "$ $cmd\n";
		$out .= kfShellExec( $cmd ) . "\n";
	}

	// Go back to the original dir if we changed it
	if ( isset( $options['dir'] ) ) {
		chdir( $orgPath );
	}

	return $out;
}

/**
 * Shell
 * -------------------------------------------------
 */

function kfShellExec( $cmd ) {
	$retval = null;

	ob_start();
	passthru( $cmd, $retval );
	$output = ob_get_contents();
	ob_end_clean();

	if ( $retval != 0 ) {
		return "Command failed:\n$cmd\nReturn: $retval $output";
	}

	return $output;
}

function kfEscapeShellArg() {
	$args = func_get_args();
	$args = array_map( 'escapeshellarg', $args );
	return implode( ' ', $args );
}

function kfFormatBytes( $size, $precision = 2 ) {
	$units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
	for ( $i = 0; $size >= 1024 && $i < 4; $i++ ) {
		$size /= 1024;
	}
	return round( $size, 2 ) . $units[$i];
}

/**
 * HTTP
 * -------------------------------------------------
 */

/**
 * Get data from MediaWiki API.
 * *
 * @param string $url Base url for wiki (from LabsDb::getDbInfo).
 * @param array $params Query parameters for MediaWiki API
 * @return array|false Data from the API response, or boolean false
 */
function kfApiRequest( string $url, array $params = [] ) {
	$scope = Logger::createScope( __FUNCTION__ );

	$params['format'] = 'json';
	if ( !isset( $params['action'] ) ) {
		$params['action'] = 'query';
	}

	$apiUrl = "$url/w/api.php?" . http_build_query( $params );
	Logger::debug( "HTTP GET $apiUrl" );
	$response = HttpRequest::get( $apiUrl );
	if ( !$response ) {
		return false;
	}

	$data = json_decode( $response, true );
	if ( !is_array( $data ) || isset( $data['error'] ) ) {
		return false;
	}

	return $data;
}
