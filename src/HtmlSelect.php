<?php
/**
 * HtmlSelect class
 *
 * @author Timo Tijhof, 2016
 * @license Public domain
 * @package toollabs-base
 * @since v0.8.0
 */

class HtmlSelect {
	protected $default = null;
	protected $options = array();
	protected $attributes = array();

	public function __construct( array $options = [] ) {
		$this->addOptions( $options );
	}

	public function setAttribute( $name, $value ) {
		$this->attributes[$name] = $value;
	}

	public function setName( $value ) {
		$this->setAttribute( 'name', $value );
	}

	public function setDefault( $default ) {
		$this->default = $default;
	}

	public function addOption( $value, $text = null ) {
		$attribs = array( 'value' => $value );
		$text = ($text !== null) ? $text : $value;

		$this->options[$value] = $text;
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
	public function addOptions( $options ) {
		foreach ( $options as $key => $option ) {
			if ( is_int( $key ) ) {
				$this->addOption( $option );
			} else {
				$this->addOption( $key, $option );
			}
		}
	}

	private function formatOptions() {
		$data = '';
		foreach( $this->options as $value => $text ) {
			$attribs = array( 'value' => $value );
			if ( $value === $this->default ) {
				$attribs['selected'] = true;
			}
			$data .= Html::element( 'option', $attribs, $text );
		}

		return $data;
	}

	public function getHTML() {
		return Html::rawElement( 'select', $this->attributes, $this->formatOptions() );
	}

}
