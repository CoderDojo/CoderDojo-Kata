<?php

/**
 * File holding the SFIMenuSelect class.
 *
 * @author Stephan Gambke
 *
 * @file
 * @ingroup SFI
 */
if ( !defined( 'SFI_VERSION' ) ) {
	die( 'This file is part of the Semantic Forms Inputs extension, it is not a valid entry point.' );
}

/**
 * This class represents the MenuSelect input.
 *
 * @ingroup SFI
 */
class SFIMenuSelect extends SFFormInput {


	/**
	 * Constructor.
	 *
	 * @param String $input_number
	 *		The number of the input in the form.
	 * @param String $cur_value
	 *		The current value of the input field.
	 * @param String $input_name
	 *		The name of the input.
	 * @param String $disabled
	 *		Is this input disabled?
	 * @param Array $other_args
	 *		An associative array of other parameters that were present in the
	 *		input definition.
	 */
	public function __construct( $input_number, $cur_value, $input_name, $disabled, $other_args ) {

		parent::__construct( $input_number, $cur_value, $input_name, $disabled, $other_args );

		self::setup();

		$this->addJsInitFunctionData( 'SFI_MS_init', 'null' );


	}

	/**
	 * Returns the name of the input type this class handles: menuselect.
	 *
	 * This is the name to be used in the field definition for the "input type"
	 * parameter.
	 *
	 * @return String The name of the input type this class handles.
	 */
	public static function getName() {
		return 'menuselect';
	}

	/**
	 * Static setup method for input type "menuselect".
	 * Adds the Javascript code and css used by all menuselects.
	*/
	static private function setup() {

		global $wgHooks;

		static $hasRun = false;

		if ( !$hasRun ) {
			$hasRun = true;

			$wgHooks['MakeGlobalVariablesScript'][] = 'SFIMenuSelect::setGlobalVariables';

		}

	}

	static public function setGlobalVariables( &$vars ) {
		global $sfigSettings;
		$vars['sfigScriptPath'] = $sfigSettings->scriptPath;
		return true;
	}

	/**
	 * Returns the HTML code to be included in the output page for this input.
	 *
	 * Ideally this HTML code should provide a basic functionality even if the
	 * browser is not Javascript capable. I.e. even without Javascript the user
	 * should be able to input values.
	 *
	 */
	public function getHtmlText(){

		global $wgUser, $wgParser;
		global $sfigSettings;

		// first: set up HTML attributes
		$inputFieldDisabled =
			array_key_exists( 'disable input field', $this->mOtherArgs )
			|| $this->mIsDisabled
			|| ( $sfigSettings->menuSelectDisableInputField && !array_key_exists( 'enable input field', $this->mOtherArgs ) );

		// second: assemble HTML
		// create visible input field (for display) and invisible field (for data)
		$html = SFIUtils::textHTML( $this->mCurrentValue, '', $inputFieldDisabled, $this->mOtherArgs, "input_{$this->mInputNumber}_show", null, "createboxInput" )
				. Html::rawElement('span', array(
					'class' => 'inputSpan' . ($this->mIsMandatory ? ' mandatoryFieldSpan' : '')
				) ,
					Html::element( "input", array(
					'id' => "input_{$this->mInputNumber}",
					'type' => 'hidden',
					'name' => $this->mInputName,
					'value' => $this->mCurrentValue
				) ) );


		$html .= "<span class='SFI_menuselect' id='span_{$this->mInputNumber}_tree'>";


		// parse menu structure

		// FIXME: SF does not parse options correctly. Users have to replace | by {{!}}
		$structure = str_replace( '{{!}}', '|', $this->mOtherArgs['structure'] );
		$options = ParserOptions::newFromUser( $wgUser );

		$structure = $wgParser->doBlockLevels( $structure, true );
		$wgParser->replaceLinkHolders( $structure );

		$html .= str_replace( '<li', '<li class=\'ui-state-default\'', $structure );

		$html .= "</span>";

		// wrap in div
		$html = '<div>' .$html . '</div>';

		return $html;


	}

	/**
	 * Returns the set of SMW property types which this input can
	 * handle, but for which it isn't the default input.
	 */
	public static function getOtherPropTypesHandled() {
		return array( '_str', '_wpg' );
	}

	/**
	 * Returns the set of parameters for this form input.
	 */
	public static function getParameters() {
		$params = parent::getParameters();
		$params['structure'] = array(
			'name' => 'structure',
			'type' => 'text',
			'description' => wfMsg( 'semanticformsinputs-menuselect-structure' ),
			'default' => "* item 1\n** item 11\n** item 12\n* item 2\n** item 21\n** item 22"
		);
		$params[$sfigSettings->menuSelectDisableInputField?'enable input field':'disable input field'] = array(
			'name' => $sfigSettings->menuSelectDisableInputField?'enable input field':'disable input field',
			'type' => 'boolean',
			'description' => wfMsg( 'semanticformsinputs-menuselect-enableinputfield' ),
		);
		return $params;
	}

	/**
	 * Returns the names of the resource modules this input type uses.
	 *
	 * Returns the names of the modules as an array or - if there is only one
	 * module - as a string.
	 *
	 * @return null|string|array
	 */
	public function getResourceModuleNames() {
		return 'ext.semanticformsinputs.menuselect';
	}

}
