<?php
/**
 * Class holding the data of a page to be imported
 *
 * @author Yaron Koren
 */

class DTPage {
	var/**
	 * Lets the user import a CSV file to turn into wiki pages
	 *
	 * @author Yaron Koren
	 */
		$mName;
	var $mTemplates;
	var $mFreeText;

	public function DTPage () {
		$this->mTemplates = array();
	}

	function setName ( $name ) {
		$this->mName = $name;
	}

	function getName () {
		return $this->mName;
	}

	function addTemplateField ( $template_name, $field_name, $value ) {

		if ( !array_key_exists( $template_name, $this->mTemplates ) ) {
			$this->mTemplates[$template_name] = array();
		}
		$this->mTemplates[$template_name][$field_name] = $value;
	}

	function setFreeText ( $free_text ) {
		$this->mFreeText = $free_text;
	}

	function createText () {
		$text = "";
		foreach ( $this->mTemplates as $template_name => $fields ) {
			$text .= '{{' . $template_name . "\n";
			foreach ( $fields as $field_name => $val ) {
				$text .= "|$field_name=$val\n";
			}
			$text .= '}}' . "\n";
		}
		$text .= $this->mFreeText;
		return $text;
	}
}
