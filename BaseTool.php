<?php
/**
 * BaseTool.php
 * Created on January 15th, 2011
 *
 * @since 0.1
 * @author Krinkle <krinklemail@gmail.com>, 2010 - 2012
 *
 * @package KrinkleToolsCommon
 * @license Public domain, WTFPL
 */

/**
 * @class BaseTool
 *
 * Base class for all tools created after January 2011.
 * @example
 * <code>
 *     $Tool = BaseTool::newFromArray( $config );
 * </code>
 */
class BaseTool {

	/* Public member variables */
	/* Accessing these outside the class is discouraged, use or create get/setters instead */
	var $displayTitle = '';
	var $krinklePrefix = true;
	var $remoteBasePath = '';
	var $localBasePath = '';
	var $revisionId = '0.0.0';
	var $revisionDate = '1970-01-01';
	var $styles = array();
	var $scripts = array();
	var $mainOutput = array( 'head' => '', 'body' => '' );
	var $headTitle = '';
	var $bodyClosed = null;
	var $sessionNamespace = 'default';
	var $I18N = null;


	public static function newFromArray( $config ) {
		global $kgConf;

		$t = new BaseTool();

		if ( isset( $config['simplePath'] ) ) {
			$t->remoteBasePath = $kgConf->getRemoteBase() . $config['simplePath'];
			$t->localBasePath = $kgConf->getLocalHome() . '/public_html' . $config['simplePath'];
		} else {
			$t->remoteBasePath = isset( $config['remoteBasePath'] ) ? $config['remoteBasePath'] : '';
			$t->localBasePath = isset( $config['localBasePath'] ) ? $config['localBasePath'] : '';
		}

		$kgConf->I18N = isset( $config['I18N'] ) ? $config['I18N'] : null;

		$t->displayTitle = isset( $config['displayTitle'] ) ? $config['displayTitle'] : '';
		$t->krinklePrefix = isset( $config['krinklePrefix'] ) ? $config['krinklePrefix'] : true;
		$t->sessionNamespace = isset( $config['sessionNamespace'] ) ? $config['sessionNamespace'] : 'default';
		$t->revisionId = isset( $config['revisionId'] ) ? $config['revisionId'] : '';
		$t->revisionDate = isset( $config['revisionDate'] ) ? $config['revisionDate'] : '';
		$t->styles = !empty( $config['styles'] ) ? $t->getStyles( $config['styles'] ) : $t->getStyles();
		$t->scripts = !empty( $config['scripts'] ) ? $t->getScripts( $config['scripts'] ) : $t->getScripts();


		kfLog( 'New tool "' . $t->displayTitle . '" created!', __METHOD__ );

		return $t;
	}

	public function getStyles( $custom = array() ) {
		global $kgConf;

		$styles = array(
			$kgConf->getRemoteBase() . '/main.css',
		);

		foreach ( $custom as $style ) {
			$styles[] = $this->expandURL( $style );
		}

		return $styles;
	}

	public function getScripts( $custom = array() ) {
		global $kgConf;

		$scripts = array(
			$kgConf->getJQueryURI(),
			$kgConf->getRemoteBase() . '/main.js',
		);

		foreach ( $custom as $script ) {
			$scripts[] = $this->expandURL( $script );
		}

		return $scripts;
	}

	public function addStyles( $style ) {
		// Allow arrays for multiple styles
		if ( is_array( $style ) ) {
			foreach( $style as $styleItem ) {
				// recursively
				$this->addStyles( $styleItem );
			}
			return true;
		} elseif ( is_string( $style ) ) {
			$this->styles[] = $this->expandURL( $style );
			return true;
		} else {
			return false;
		}
	}

	public function addScripts( $script ) {
		// Allow arrays for multiple scripts
		if ( is_array( $script ) ) {
			foreach( $script as $scriptItem ) {
				// recursively
				$this->addScripts( $scriptItem );
			}
			return true;
		} elseif ( is_string( $script ) ) {
			$this->scripts[] = $this->expandURL( $script );
			return true;
		} else {
			return false;
		}
	}

	public function setSessionVar( $key, $val ) {
		@$_SESSION['KrinkleTools'][$this->sessionNamespace][$key] = $val;
		return true;
	}

	public function getSessionVar( $key ) {
		return @$_SESSION['KrinkleTools'][$this->sessionNamespace][$key];
	}

	public function expandURL( $url, $protocolRelativeOK = true ) {
		// '//dom.ain/fi.le'
		if ( substr( $url, 0, 2 ) == '//' ) {
			return ( !$protocolRelativeOK ? 'http:' : '' ) . $url;

		// '/fi.le'
		} elseif ( substr( $url, 0, 1 ) == '/' ) {
			global $kgConf;
			return $kgConf->getRemoteBase() . $url;

		// '..://..'
		} elseif ( strpos( $url, '://' ) !== false ) {
			return $url;

		// 'fi.le'
		} else {
			return $this->remoteBasePath . $url;
		}
	}

	/**
	 * Add a string to the output memory
	 *
	 * @param $str string String to be added to the memory
	 * @param $wrapTag string (optional) Name of the tag to wrap the string in.
	 *  If this is used the contents of $str will be html-escaped!
	 * @param $attributes string (optional) When using a wrapTag these attributes
	 *  will be applied as well. Both the keys and the values will be escaped, don't do
	 *  so they should be passed raw to addOut()
	 * @return boolean Returns true on success, false on failure
	 */
	public function addOut( $str, $wrapTag = 0, $attributes = array() ) {
		if ( is_string( $str ) ) {
			if ( is_string( $wrapTag ) ) {
				$str = Html::element( $wrapTag, $attributes, $str );
			}
			$this->mainOutput['body'] .= $str;
			return true;
		} else {
			return false;
		}
	}
	public function addHtml( $str ) {
		return $this->addOut( $str );
	}

	public function addHeadOut( $str ) {
		if ( is_string( $str ) ) {
			$this->mainOutput['head'] .= $str;
			return true;
		} else {
			return false;
		}
	}

	public function setHeadTitle( $str ) {
		if ( is_string( $str ) && !empty( $str ) ) {
			$this->headTitle = ( $this->krinklePrefix ? 'Krinkle | ' : '' ) . $this->displayTitle . ' - ' . $str;
			return true;
		} else {
			$this->headTitle = ( $this->krinklePrefix ? 'Krinkle | ' : '' ) . $this->displayTitle;
			return false;
		}
	}

	public function doHtmlHead() {
		$this->headTitle = ( $this->krinklePrefix ? 'Krinkle | ' : '' ) . $this->displayTitle;
		$head = '';
		if ( is_array( $this->styles ) ) {
			foreach( $this->styles as $style ) {
				$head .= '<link type="text/css" rel="stylesheet" href="' . kfEscapeHTML( $style ) . '?v=2"/>' . "\n";
			}
		}
		if ( is_array( $this->scripts ) ) {
			foreach( $this->scripts as $script ) {
				$head .= '<script type="text/javascript" src="' . kfEscapeHTML( $script ) . '"></script>' . "\n";
			}
		}
		$this->addHeadOut( $head );
		return true;
	}

	public function doStartBodyWrapper() {
		global $kgConf;
		$titleVal = kfEscapeHTML( $this->displayTitle );

		if ( !is_null( $kgConf->I18N ) ) {

			$opts = array(
				'domain' => 'general',
				'escape' => 'htmlentities',
				'variables' => array( 1 => $this->revisionDate )
			);
			$line = str_replace( '$1', $this->revisionId, $kgConf->I18N->msg( 'toolversionstamp',  $opts ) );
			$myAccount = $kgConf->I18N->dashboardBacklink();
		} else {
			$line = "Version {$this->revisionId} as uploaded on {$this->revisionDate}";
			$myAccount = '';
		}
		$h1_pre = $this->krinklePrefix ? '<a href="' . $kgConf->getRemoteBase() .'"><small>Krinkle</small></a> | ' : '';
		$body = <<<HTML

	<div id="page-wrap">

		<h1>$h1_pre<a href="{$this->remoteBasePath}">{$titleVal}</a></h1>
		$myAccount<small><em>$line</em></small>
		<hr/>
HTML;
		$this->addOut( $body );
		$this->bodyClosed = false;
		return true;
	}

	// @return Boolean: True if body got closed, False if it was already closed
	public function doCloseBodyWrapper() {
		if ( $this->bodyClosed === false ) {
			$body = <<<HTML

	</div>
HTML;
			$this->addOut( $body );
			$this->bodyClosed = true;
			return true;
		} else {
			return false;
		}

	}


	public function flushMainOutput( $mode = KR_OUTPUT_BROWSER_HTML5 ) {
		global $kgConf;

		switch( $mode ) {
			case KR_OUTPUT_BROWSER_HTML5:
				if ( $kgConf->getDebugMode() ) {
					$this->addOut( kfLogFlush( KR_LOG_RETURN ) );
				}
				if ( $this->bodyClosed === false ) {
					$this->doCloseBodyWrapper();
				}

				$innerHTML =
					"<head>\n"
					.	'<meta charset="utf-8">'
					.	"\n<title>" . $this->headTitle . "</title>\n"
					.	$this->mainOutput['head']
					.	"\n</head>\n"
					.	"<body>\n"
					.	$this->mainOutput['body']
					.	"\n</body>"
				;

				header( 'Content-Type: text/html; charset=utf-8' );
				echo <<<HTML
<!DOCTYPE html>
<html dir="ltr" lang="en-US">

HTML;
				echo $innerHTML;
				echo <<<HTML

</html>
HTML;
				break;
			default:
				echo $this->mainOutput['body'];
		}
		return true;
	}

	public function redirect( $url, $status = 302 ) {
		header( "Location: $url", true, $status );
		return true;
	}

	public function dieError( $message ) {
		$this->addOut( kfMsgBlock( $message, 'error' ) );
		$this->flushMainOutput();
		kfCloseAllConnections();
		die;
	}

	public function generatePermalink( $params = array(), $url = false ) {

		$link = $url ? $url : $this->remoteBasePath;
		$one = true;
		foreach ( $params as $key => $val ) {

			if ( $val !== '' && $val !== false && $val !== 0 ) {

				$link .= $one ? '?' : '&';
				if ( $one ) {
					$one = false;
				}
				$link .= rawurlencode( $key ) . '=' . rawurlencode( $val );
			}

		}
		return $link;
	}


}
