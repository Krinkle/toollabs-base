<?php
namespace Krinkle\Toolbase;

use Wikimedia\ScopedCallback;

class Logger {
	public const MODE_HTML = 1;
	public const MODE_TEXT = 2;

	private static $enabled = false;
	private static $stack = [
		'(init)'
	];
	private static $buffer = '';

	private static function getTopSection(): ?string {
		return end( self::$stack ) ?: null;
	}

	private static function startSection( string $name ): void {
		self::$stack[] = $name;
	}

	private static function endSection( string $name ): void {
		if ( $name === self::getTopSection() ) {
			array_pop( self::$stack );
		} else {
			self::debug( "Warning: Logger ignores end of log section '$name'." );
		}
	}

	public static function setEnabled( bool $enabled = true ): void {
		self::$enabled = $enabled;
	}

	public static function isEnabled(): bool {
		return self::$enabled;
	}

	/**
	 * Begin a scoped section, prefixing subsequent debug lines.
	 */
	public static function createScope( string $name ): ScopedCallback {
		self::startSection( $name );

		return new ScopedCallback( static function () use ( $name ) {
			self::endSection( $name );
		} );
	}

	/**
	 * Write a line to the debug log
	 */
	public static function debug( string $val ): void {
		if ( !self::$enabled ) {
			return;
		}

		self::$buffer .= self::getTopSection() . '> '
			. $val
			. "\n";
	}

	/**
	 * Get debug log so far.
	 */
	public static function getBuffer(): string {
		return self::$buffer;
	}

	public static function clearBuffer(): void {
		self::$buffer = '';
	}

	/**
	 * @param int $mode One of MODE_HTML or MODE_TEXT
	 */
	public static function flush( $mode = self::MODE_HTML ): string {
		$output = self::getBuffer();
		self::clearBuffer();

		if ( $mode === self::MODE_HTML ) {
			$output = '<pre>' . htmlspecialchars( $output ) . '</pre>';
		}
		return $output;
	}
}
