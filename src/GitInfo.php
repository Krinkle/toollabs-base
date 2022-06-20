<?php
namespace Krinkle\Toolbase;

/**
 * A class to help return information about a Git repository.
 *
 * This file is inspired by MediaWiki 1.22 (GitInfo class).
 *
 * @internal
 * @since 0.9.0
 */

class GitInfo {
	/**
	 * @var string Path to a .git directory
	 */
	protected $dir;

	/**
	 * Whether a given string looks like a base 16 (hexadecimal) string.
	 *
	 * @param string $str
	 * @return bool
	 */
	public static function isSHA1( $str ) {
		return !!preg_match( '/^[0-9A-F]{40}$/i', $str );
	}

	/**
	 * @param string $dir Path to a .git directory
	 */
	public function __construct( $dir ) {
		$this->dir = "{$dir}/.git";
	}

	/**
	 * Return the SHA1 for the current HEAD of the Git repository.
	 *
	 * @return string|bool A SHA1, or boolean false
	 */
	public function getHeadSHA1() {
		$headFile = "{$this->dir}/HEAD";
		if ( !is_readable( $headFile ) ) {
			return false;
		}

		$head = file_get_contents( $headFile );
		if ( preg_match( "/ref: (.*)/", $head, $m ) ) {
			$head = rtrim( $m[1] );
		} else {
			$head = rtrim( $head );
		}

		if ( self::isSHA1( $head ) ) {
			// Detached head
			return $head;
		}

		// Resolve ref
		$refFile = "{$this->dir}/{$head}";
		if ( !is_readable( $refFile ) ) {
			return false;
		}

		return rtrim( file_get_contents( $refFile ) );
	}
}
