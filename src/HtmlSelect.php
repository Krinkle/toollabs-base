<?php
namespace Krinkle\Toolbase;

/**
 * Multi-select options menu
 *
 * @since 0.8.0
 */
class HtmlSelect {
	protected $default = null;
	protected $options = [];
	protected $attributes = [];

	public function __construct( array $options = [] ) {
		$this->addOptions( $options );
	}

	public function setAttribute( string $name, $value ): void {
		$this->attributes[$name] = $value;
	}

	public function setName( string $value ): void {
		$this->setAttribute( 'name', $value );
	}

	public function setDefault( $default ): void {
		$this->default = $default;
	}

	public function addOption( string $value, string $text = null ): void {
		$this->options[$value] = $text ?? $value;
	}

	/**
	 * Example:
	 *
	 *  $select->addOptions([
	 *      'foo1' => 'Foo One'
	 *  ]);
	 *  $select->addOptions([
	 *      'foo1'
	 *  ]);
	 *  $select->addOptions([
	 *      'foo1' => 'foo1'
	 *  ]);
	 *
	 * @param string|array $options
	 */
	public function addOptions( $options ): void {
		foreach ( $options as $key => $option ) {
			if ( is_int( $key ) ) {
				$this->addOption( $option );
			} else {
				$this->addOption( $key, $option );
			}
		}
	}

	private function formatOptions(): string {
		$data = '';
		foreach( $this->options as $value => $text ) {
			$attribs = [ 'value' => $value ];
			if ( $value === $this->default ) {
				$attribs['selected'] = true;
			}
			$data .= Html::element( 'option', $attribs, $text );
		}

		return $data;
	}

	public function getHTML(): string {
		return Html::rawElement( 'select', $this->attributes, $this->formatOptions() );
	}

}
