<?php
namespace Krinkle\Toolbase;

/**
 * Helper class for generating HTML tags.
 *
 * This file is inspired by MediaWiki 1.22 (Html class).
 *
 * @since 0.9.0
 */
class Html {
	// Void elements per WHATWG HTML spec.
	// https://html.spec.whatwg.org/multipage/syntax.html#elements-2
	private static $voidElements = [
		'area',
		'base',
		'br',
		'col',
		'embed',
		'hr',
		'img',
		'input',
		'link',
		'meta',
		'param',
		'source',
		'track',
		'wbr',
	];

	/**
	 * Create an HTML tag with raw content (unescaped).
	 *
	 * @param string $element The element name.
	 * @param array $attribs Associative array of attributes.
	 * @param string $content The raw content.
	 * @return string HTML
	 */
	public static function rawElement( $element, array $attribs = array(), $content = '' ) {
		$start = "<$element" . self::expandAttributes( $attribs ) . '>';
		if ( $content === '' && in_array( $element, self::$voidElements ) ) {
			return $start;
		}
		return "$start$content</$element>";
	}

	/**
	 * Create an HTML tag with text content (escaped).
	 *
	 * @param string $element The element name.
	 * @param array $attribs Associative array of attributes.
	 * @param string $content The text content.
	 * @return string HTML
	 */
	public static function element( $element, array $attribs = array(), $content = '' ) {
		return self::rawElement( $element, $attribs, strtr( $content, array(
			'&' => '&amp;',
			'<' => '&lt;',
		) ) );
	}

	/**
	 * Convert an array of HTML attributes to an HTML string.
	 *
	 * In addition to simple string values, boolean and array values are also supported.
	 *
	 * For boolean values, false is ignored, and true is interpreted as empty string.
	 * For array values, the string is joined as space-separated.
	 *
	 * @param array $attribs Associative array of attributes.
	 * @return string HTML
	 */
	private static function expandAttributes( array $attribs ) {
		$ret = '';
		foreach ( $attribs as $key => $value ) {
			if ( $value === false ) {
				continue;
			}
			if ( $value === true ) {
				$value = '';
			} elseif ( is_array( $value ) ) {
				$value = trim( join( ' ', $value ) );
			}
			$encoded = htmlspecialchars( $value, ENT_QUOTES );
			// PHP htmlspecialchars does not encode these by default:
			$encoded = strtr( $encoded, [
				"\n" => '&#10;',
				"\r" => '&#13;',
				"\t" => '&#9;',
			] );
			$ret .= " $key=\"$encoded\"";
		}
		return $ret;
	}
}
