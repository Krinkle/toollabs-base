<?php
namespace Krinkle\Toolbase;

use Exception;

/**
 * Base class for Toolforge tools.
 *
 * @since 0.1.0
 * @example
 *
 *     $Tool = BaseTool::newFromArray( $config );
 *
 */
class BaseTool {

	/* Accessing these outside the class is discouraged, use or create get/setters instead */
	var $displayTitle = '';
	var $remoteBasePath = '';
	var $revisionId = '';
	var $styles = array();
	var $scripts = array();
	var $scriptsHead = array();
	var $mainOutput = array( 'head' => '', 'body' => '' );
	var $authors = array(
		'@Krinkle' => 'https://github.com/Krinkle',
	);
	var $licenses = array(
		'MIT',
	);
	var $layout = array(
		'top' => true,
		'footer' => true,
		'header' => true, /* array(
			'titleText' => null,
			'captionText' => null,
			'captionHtml' => null,
			'html' => null,
		) */
	);
	protected $requireJS = false;

	var $headTitle = '';
	private $I18N = null;

	/**
	 * @var array $sourceInfo Properties:
	 * issueTrackerUrl, repoViewUrl, repoDir, repoCommitID, repoCommitUrl.
	 */
	protected $sourceInfo = [];

	/* The last instance created via newFromArray */
	private static $instance = null;

	public static function getInstance(): ?self {
		return self::$instance;
	}

	private function __construct() {
	}

	public static function newFromArray( $config ) {

		// Init logger status first
		$req = new Request( $_GET );
		if ( $req->hasKey( 'debug' ) ) {
		    $isDebug = $req->getFuzzyBool( 'debug' );
		    // Persist or unpersist accordingly,
		    // to allow for form submissions and subsequent browsing.
		    $req->setCookie( 'debug', $isDebug ? '1' : null );
		} else {
		    $isDebug = $req->getCookie( 'debug' ) === '1';
		}
		if ( $isDebug ) {
			Logger::setEnabled();
		}

		$scope = Logger::createScope( __METHOD__ );

		$t = new BaseTool();

		if ( isset( $config['remoteBasePath'] ) ) {
			$t->remoteBasePath = rtrim( $config['remoteBasePath'], '/' ) . '/';
		}

		if ( isset( $config['sourceInfo'] ) ) {
			$t->sourceInfo = $config['sourceInfo'];
		}

		$t->I18N = $config['I18N'] ?? null;

		$t->displayTitle = $config['displayTitle'] ?? '';
		$t->revisionId = $config['revisionId'] ?? '';

		$t->styles = array(
			'https://tools-static.wmflabs.org/cdnjs/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css',
			'base/main.css',
		);
		$t->scripts = array(
			'https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/3.2.1/jquery.min.js',
			'https://tools-static.wmflabs.org/cdnjs/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js',
		);

		if ( isset( $config['authors'] ) ) {
			$t->authors = $config['authors'];
		}
		if ( isset( $config['licenses'] ) ) {
			$t->licenses = $config['licenses'];
		}
		if ( isset( $config['layout'] ) ) {
			$t->layout = array_merge( $t->layout, $config['layout'] );
		}
		if ( isset( $config['styles'] ) ) {
			$t->styles = $t->expandUrlArray(
				array_merge( $t->styles, $config['styles'] )
			);
		}
		if ( isset( $config['scripts'] ) ) {
			$t->scripts = $t->expandUrlArray(
				array_merge( $t->scripts, $config['scripts'] )
			);
		}
		if ( isset( $config['scriptsHead'] ) ) {
			$t->scriptsHead = $t->expandUrlArray(
				array_merge( $t->scriptsHead, $config['scriptsHead'] )
			);
		}
		if ( isset( $config['requireJS'] ) ) {
			$t->requireJS = $config['requireJS'];
		}

		$t->headTitle = $t->displayTitle;

		Logger::debug( 'Tool "' . $t->displayTitle . '" instantiated' );

		self::$instance = $t;

		return $t;
	}

	/**
	 * Disable or customize parts of the default layout
	 * @param string $key
	 * @param bool|string|array $value
	 */
	public function setLayout( $key, $value ) {
		$this->layout[ $key ] = $value;
	}

	public function setSourceInfoGithub( $owner, $repo, $repoDir = null ) {
		$scope = Logger::createScope( __METHOD__ );

		$this->sourceInfo = array(
			'repoOwner' => $owner,
			'repoName' => $repo,
			'issueTrackerUrl' => "https://github.com/$owner/$repo/issues",
			'repoViewUrl' => "https://github.com/$owner/$repo",
		);

		if ( is_dir( $repoDir ) ) {
			$gitInfo = new GitInfo( $repoDir );
			$repoCommitID = $gitInfo->getHeadSHA1();
			if ( $repoCommitID !== false ) {
				$this->sourceInfo['repoDir'] = $repoDir;
				$this->sourceInfo['repoCommitID'] = substr( $repoCommitID, 0, 8 );
				$this->sourceInfo['repoCommitUrl'] = "https://github.com/$owner/$repo/commit/$repoCommitID";
			} else {
				Logger::debug( "GitInfo for '$repoDir' failed." );
			}
		}
	}

	public function getSourceInfo() {
		$sourceInfo = $this->sourceInfo + array(
			'repoOwner' => false,
			'repoName' => false,
			'issueTrackerUrl' => false,
			'repoViewUrl' => false,
			'repoDir' => false,
			'repoCommitID' => false,
			'repoCommitUrl' => false,
		);
		return $sourceInfo;
	}

	/**
	 * @since 2.0.0
	 */
	public function getUserAgent(): string {
		return trim( sprintf( '%s (%s) %s',
			$this->sourceInfo['repoName']
				?: strtr( strip_tags( $this->displayTitle ), ' ', '_' )
				?: '-',
			$this->sourceInfo['repoCommitID'] ?: $this->revisionId ?: '-',
			$this->sourceInfo['repoViewUrl'] ?: $_SERVER['SERVER_NAME'] ?: ''
		) );
	}

	public function expandUrlArray( $items = array() ) {
		$expanded = array();

		foreach ( $items as $item ) {
			$expanded[] = $this->expandURL( $item );
		}

		return $expanded;
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

	public function expandURL( $url ) {
		// Handle '//domain.example/path/file.ext'
		if ( substr( $url, 0, 2 ) == '//' ) {
			// Expand legacy use of protocol-relative to plain HTTPS.
			return 'https:' . $url;
		}

		// Handle 'scheme://..'
		// "ASCII alpha, followed by zero or more of alphanumeric, U+002B (+),
		// U+002D (-), and U+002E (.)" – https://url.spec.whatwg.org/#url-scheme-string
		if ( preg_match( '/^[a-z][a-z0-9+.-]*:/', $url ) ) {
			return $url;
		}

		// Handle'/path/file.ext' or 'path/file.ext'
		return $this->remoteBasePath . $url;
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

	public function setHeadTitle( $str = null ) {
		if ( is_string( $str ) ) {
			$this->headTitle = $this->displayTitle . ' - ' . $str;
			return true;
		} else {
			$this->headTitle = $this->displayTitle;
			return false;
		}
	}

	public function getPageTop() {
		if ( !$this->layout['top'] ) {
			return '';
		}

		$titleVal = htmlspecialchars( $this->displayTitle );

		$toolnav = array();

		if ( $this->I18N ) {
			$toolnav[] = $this->I18N->dashboardBacklink();
		}

		$toolnav = '<li>' . implode( '</li><li>', $toolnav ) . '</li>';

		$html = <<<HTML
<header class="navbar navbar-static-top base-nav" id="top">
	<div class="container">
		<div class="navbar-header">
			<a class="navbar-brand" href="{$this->remoteBasePath}">{$titleVal}</a>
		</div>
		<nav class="collapse navbar-collapse bs-navbar-collapse">
			<ul class="nav navbar-nav navbar-right">
				$toolnav
			</ul>
		</nav>
	</div>
</header>
HTML;
		return $html;
	}

	public function getPageHeader() {
		$data = $this->layout['header'];
		if ( !$data ) {
			return '';
		}
		$htmlContent = '';
		if ( isset( $data['html'] ) ) {
			$htmlContent .= $data['html'];
		} else {
			if ( isset( $data['titleText'] ) ) {
				$htmlContent .= Html::element( 'h1', array(), $data['titleText'] );
			} else {
				$htmlContent .= Html::element( 'h1', array(), $this->displayTitle );
			}
			if ( isset( $data['captionHtml'] ) ) {
				$htmlContent .= Html::rawElement( 'p', array(), $data['captionHtml'] );
			} elseif ( isset( $data['captionText'] ) ) {
				$htmlContent .= Html::element( 'p', array(), $data['captionText'] );
			}
		}

		$html = <<<HTML
<div class="base-header" id="header"><div class="container">
	$htmlContent
</div></div>
HTML;
		return $html;
	}

	public function getPageFooter() {
		if ( !$this->layout['footer'] ) {
			return '';
		}

		$authorNodes = array();
		foreach ( $this->authors as $author => $link ) {
			if ( is_int( $author ) ) {
				$author = $link;
				$link = null;
			}
			if ( $link ) {
				$authorNodes[] = Html::element( 'a', array( 'href' => $link, 'target' => '_blank' ), $author );
			} else {
				$authorNodes[] = Html::element( 'span', array(), $author );
			}
		}
		$licenseNodes = array();
		foreach ( $this->licenses as $license => $link ) {
			if ( is_int( $license ) ) {
				$license = $link;
				$link = null;
			}
			if ( $link ) {
				$licenseNodes[] = Html::element( 'a', array( 'href' => $link, 'target' => '_blank' ), $license );
			} else {
				$licenseNodes[] = Html::element( 'span', array(), $license );
			}
		}

		// TODO: Localise
		$authors = implode( ', ', $authorNodes );
		$licenses = implode( ', ', $licenseNodes );

		$items = array();

		$sourceInfo = $this->getSourceInfo();

		$version = $this->revisionId ? "v{$this->revisionId}" : '';
		if ( $sourceInfo['repoCommitID'] !== false ) {
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
			$version .= " ($sourceVersion)";
		}
		$items[] = "Currently $version";

		if ( $sourceInfo['repoViewUrl'] ) {
			$items[] = Html::element( 'a', array(
				'dir' => 'ltr',
				'lang' => 'en',
				'href' => $sourceInfo['repoViewUrl']
			), 'Source repository' );
		}

		if ( $sourceInfo['issueTrackerUrl'] ) {
			$items[] = Html::element( 'a', array(
				'dir' => 'ltr',
				'lang' => 'en',
				'href' => $sourceInfo['issueTrackerUrl']
			), 'Issue tracker' );
		}

		$toolnav = '<li>' . implode( '</li><li>·</li><li>', $items ) . '</li>';

		if ( !Logger::isEnabled() ) {
			$debugFooter = '';
		} else {
			$debugFooter = '<div class="container"><div class="panel panel-info">'
				. '<div class="panel-heading"><h3 class="panel-title">Debug log</h3></div>'
				. '<div class="panel-body">'
				. Logger::flush( Logger::MODE_HTML )
				. '</div>'
				. '</div></div>';
		}

		$html = <<<HTML
<footer class="base-footer" role="contentinfo">
	<div class="container">
		<p>Built by $authors.</p>
		<p>Code licensed under $licenses.</p>
		<ul class="base-footer-links muted">
		$toolnav
		</ul>
	</div>
</footer>
$debugFooter
HTML;
		return $html;
	}


	public function flushMainOutput(): void {
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

		global $kgReq;
		// window.KRINKLE
		$this->addHeadOut(
			'<script>'
			. 'document.documentElement.className = document.documentElement.className.replace(/\bnojs\b/,\'js\');'
			. 'window.KRINKLE = ' . json_encode(array(
				'baseTool' => array(
					'basePath' => $this->remoteBasePath,
					'req' => array(
						 'wasPosted' => $kgReq->wasPosted(),
					),
				),
			))
			. ';</script>'
		);

		$documentClassses = array(
			'client-nojs',
		);
		$contentLanguageCode = $this->I18N ? $this->I18N->getLang() : 'en-US';
		$contentLanguageDir = $this->I18N ? $this->I18N->getDir() : 'ltr';

		// Scripts
		$resourcesBody = '';
		if ( is_array( $this->scripts ) ) {
			foreach( $this->scripts as $script ) {
				$resourcesBody .= '<script defer src="' . htmlspecialchars( $script ) . '"></script>' . "\n";
			}
		}

		if ( $this->requireJS ) {
			$documentClassses[] = 'client-requirejs';
			$resourcesBody .= '<div class="requirejs-msg">'
				. kfAlertHtml( 'warning', 'This tool requires JavaScript and/or modern browser features that are not supported by your browser.' )
				. '</div>';
		}

		$this->addOut( $resourcesBody );

		$innerHTML =
			"<head>\n"
			. '<meta charset="utf-8">'
			. "\n<title>" . $this->headTitle . "</title>\n"
			. trim( $this->mainOutput['head'] )
			. "\n</head>\n"
			. "<body>\n"
			. $this->getPageTop()
			. $this->getPageHeader()
			. trim( $this->mainOutput['body'] )
			. $this->getPageFooter()
			. "\n</body>"
		;

		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!DOCTYPE html>'
			. Html::rawElement( 'html',
				array(
					'dir' => $contentLanguageDir,
					'lang' => $contentLanguageCode,
					'class' => $documentClassses,
				),
				$innerHTML
			);
	}

	public function redirect( $url, $status = 302 ) {
		header( "Location: $url", true, $status );
		return true;
	}

	public function generatePermalink( $params = array(), $url = false ) {
		$link = $url ?: $this->remoteBasePath;
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

	public function __destruct() {
		LabsDB::purgeConnections();
	}
}
