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
	var $scriptsHead = array();
	var $mainOutput = array( 'head' => '', 'body' => '' );
	var $headTitle = '';
	var $bodyClosed = null;
	var $sessionNamespace = 'default';
	var $I18N = null;

	/**
	 * @var array $sourceInfo Properties:
	 * issueTrackerUrl, repoViewUrl, repoDir, repoCommitID, repoCommitUrl.
	 */
	protected $sourceInfo = null;

	public static function newFromArray( $config ) {
		global $kgConf;

		$t = new BaseTool();

		/**
		 * @deprecated simplePath: Use remoteBasePath and localBasePath instead:
		 * <code>
		 * 'remoteBasePath'   => $kgConf->getRemoteBase() . '/OrphanTalk2/',
		 * </code>
		 */
		if ( isset( $config['simplePath'] ) ) {
			$t->remoteBasePath = $kgConf->getRemoteBase() . $config['simplePath'];
			$t->localBasePath = $kgConf->getLocalHome() . '/public_html' . $config['simplePath'];
		}

		if ( isset( $config['remoteBasePath'] ) ) {
			$t->remoteBasePath = $config['remoteBasePath'];
		}

		/** @deprecated localBasePath: No longer used */
		if ( isset( $config['localBasePath'] ) ) {
			$t->localBasePath = $config['localBasePath'];
		}

		if ( isset( $config['sourceInfo'] ) ) {
			$this->sourceInfo = $config['sourceInfo'];
		}

		$kgConf->I18N = isset( $config['I18N'] ) ? $config['I18N'] : null;

		$t->displayTitle = isset( $config['displayTitle'] ) ? $config['displayTitle'] : '';
		$t->krinklePrefix = isset( $config['krinklePrefix'] ) ? $config['krinklePrefix'] : true;
		$t->sessionNamespace = isset( $config['sessionNamespace'] ) ? $config['sessionNamespace'] : 'default';
		$t->revisionId = isset( $config['revisionId'] ) ? $config['revisionId'] : '';
		$t->revisionDate = isset( $config['revisionDate'] ) ? $config['revisionDate'] : '';

		$t->styles = array(
			$kgConf->remoteBase . '/main.css',
		);
		$t->scripts = array(
			$kgConf->remoteBase . '/main.js',
		);
		$t->scriptsHead = array(
			$kgConf->getJQueryURI()
		);

		if ( !empty( $config['styles'] ) ) {
			$t->styles = $t->expandUrlArray(
				array_merge( $t->styles, $config['styles'] )
			);
		}
		if ( !empty( $config['scripts'] ) ) {
			$t->scripts = $t->expandUrlArray(
				array_merge( $t->scripts, $config['scripts'] )
			);
		}
		if ( !empty( $config['scriptsHead'] ) ) {
			$t->scriptsHead = $t->expandUrlArray(
				array_merge( $t->scriptsHead, $config['scriptsHead'] )
			);
		}


		kfLog( 'New tool "' . $t->displayTitle . '" created!', __METHOD__ );

		return $t;
	}

	public function setSourceInfoGithub( $owner, $repo, $repoDir = null ) {
		$this->sourceInfo = array(
			'issueTrackerUrl' => "https://github.com/$owner/$repo/issues",
			'repoViewUrl' => "https://github.com/$owner/$repo",
		);

		if ( is_dir( $repoDir ) ) {
			$gitInfo = new GitInfo( $repoDir );
			$repoCommitID = $gitInfo->getHeadSHA1();
			if ( $repoCommitID ) {
				$this->sourceInfo['repoDir'] = $repoDir;
				$this->sourceInfo['repoCommitID'] = substr( $repoCommitID, 0, 8 );
				$this->sourceInfo['repoCommitUrl'] = "https://github.com/$owner/$repo/commit/$repoCommitID";
			}
		}
	}

	public function getSourceInfo() {
		$sourceInfo = array(
			'issueTrackerUrl' => 'https://jira.toolserver.org/browse/KRINKLE',
			'repoViewUrl' => false,
			'repoDir' => false,
			'repoCommitID' => false,
			'repoCommitUrl' => false,
		);
		if ( is_array( $this->sourceInfo ) ) {
			$sourceInfo = $this->sourceInfo + $sourceInfo;
		}
		return $sourceInfo;
	}

	public function expandUrlArray( $items = array() ) {
		$expanded = array();

		foreach ( $items as $item ) {
			$expanded[] = $this->expandURL( $item );
		}

		return $expanded;
	}

	/** @deprecated */
	public function getStyles( $custom = array() ) {
		return $this->expandUrlArray( $custom );
	}

	/** @deprecated */
	public function getScripts( $custom = array() ) {
		return $this->expandUrlArray( $custom );
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
		$this->addHeadOut( $head );
		return true;
	}

	public function doStartBodyWrapper() {
		global $kgConf;
		$titleVal = htmlspecialchars( $this->displayTitle );

		$versionHtml = $this->revisionId;
		$sourceInfo = $this->getSourceInfo();

		if ( $sourceInfo['repoCommitID'] ) {
			$sourceVersion = $sourceInfo['repoCommitID'];
			if ( $sourceInfo['repoCommitUrl'] ) {
				$sourceVersion = Html::element( 'a', array(
					'dir' => 'ltr',
					'lang' => 'en',
					'href' => $sourceInfo['repoCommitUrl'],
				), $sourceVersion );
			} else {
				$sourceVersion = Html::element( 'span', array(
					'dir' => 'ltr',
					'lang' => 'en',
				), $sourceVersion );
			}
			$versionHtml .= " ($sourceVersion)";
		}

		if ( !is_null( $kgConf->I18N ) ) {
			$opts = array(
				'domain' => 'general',
				'escape' => 'htmlentities',
				'variables' => array( 1 => $this->revisionDate )
			);
			$line = str_replace( '$1', $versionHtml, $kgConf->I18N->msg( 'toolversionstamp',  $opts ) );
			$myAccount = $kgConf->I18N->dashboardBacklink();
		} else {
			$line = "Version {$versionHtml} as uploaded on {$this->revisionDate}";
			$myAccount = '';
		}

		if ( $sourceInfo['issueTrackerUrl'] ) {
			$line .= ' &bull; ' . Html::element( 'a', array(
				'dir' => 'ltr',
				'lang' => 'en',
				'href' => $sourceInfo['issueTrackerUrl']
			), 'Issue tracker' );
		}

		if ( $sourceInfo['repoViewUrl'] ) {
			$line .= ' &bull; ' . Html::element( 'a', array(
				'dir' => 'ltr',
				'lang' => 'en',
				'href' => $sourceInfo['repoViewUrl']
			), 'Source code' );
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
				if ( $kgConf->isDebugMode() ) {
					$this->addOut( kfLogFlush( KR_LOG_RETURN ) );
				}
				if ( $this->bodyClosed === false ) {
					$this->doCloseBodyWrapper();
				}

				// Stylesheets
				$resourcesHead = '';
				if ( is_array( $this->styles ) ) {
					foreach( $this->styles as $style ) {
						$resourcesHead .= '<link rel="stylesheet" href="' . htmlspecialchars( $style ) . '"/>' . "\n";
					}
				}
				if ( is_array( $this->scriptsHead ) ) {
					foreach( $this->scriptsHead as $script ) {
						$resourcesHead .= '<script src="' . htmlspecialchars( $script ) . '"></script>' . "\n";
					}
				}
				$this->addHeadOut( $resourcesHead );

				// window.KRINKLE
				$this->addHeadOut(
					'<script>'
					. 'window.KRINKLE = ' . json_encode(array(
						'baseTool' => array(
							'basePath' => $this->remoteBasePath,
						),
					))
					. ';</script>'
				);

				// Scripts
				$resourcesBody = '';
				if ( is_array( $this->scripts ) ) {
					foreach( $this->scripts as $script ) {
						$resourcesBody .= '<script src="' . htmlspecialchars( $script ) . '"></script>' . "\n";
					}
				}
				$this->addOut( $resourcesBody );

				$innerHTML =
					"<head>\n"
					.	'<meta charset="utf-8">'
					.	"\n<title>" . $this->headTitle . "</title>\n"
					.	trim($this->mainOutput['head'])
					.	"\n</head>\n"
					.	"<body>\n"
					.	trim($this->mainOutput['body'])
					.	"\n</body>"
				;

				header( 'Content-Type: text/html; charset=utf-8' );
				$contentLanguageCode = !is_null( $kgConf->I18N ) ? $kgConf->I18N->getLang() : 'en-US';
				$contentLanguageDir = !is_null( $kgConf->I18N ) ? $kgConf->I18N->getDir() : 'ltr';
				echo <<<HTML
<!DOCTYPE html>
<html dir="$contentLanguageDir" lang="$contentLanguageCode">

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
