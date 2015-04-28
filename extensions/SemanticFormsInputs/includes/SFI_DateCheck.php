<?php

/**
 * File holding the SFIDateCheck class
 *
 * @author  Simon Bachenberg <simon.bachenberg@gmail.com>
 * @file
 * @ingroup SemanticFormsInputs
 */

/**
 * DateCheck uses JQuery Form Validator <http://formvalidator.net/> to check if the insert Date has the right Format.
 *
 * @ingroup SemanticFormsInputs
 * @author  Simon Bachenberg <simon.bachenberg@gmail.com>
 *
 */
class SFIDateCheck extends SFFormInput {

	protected $mIsMandatory;
	protected $mDateFormat;

	public function __construct( $input_number, $cur_value, $input_name, $disabled, $other_args ) {

		parent::__construct( $input_number, $cur_value, $input_name, $disabled, $other_args );

		if ( array_key_exists( 'date format', $this->mOtherArgs ) ) {
			$this->mDateFormat = $this->mOtherArgs[ 'date format' ];
		} else {
			$this->mDateFormat = 'YYYY/MM/DD';
		}

		if ( array_key_exists( 'mandatory', $this->mOtherArgs ) ) {
			$this->mIsMandatory = 'false';
		} else {
			$this->mIsMandatory = 'true';
		}
		$this->addJsInitFunctionData( 'SFI_DateCheck_init', $this->setupJsInitAttribs() );
	}

	/**
	 * Prepares attributes for javascript that will be run.
	 *
	 * <b>It's a stub now</b>
	 *
	 * @return string attributes for javascript file
	 */
	private function setupJsInitAttribs() {

		$jsattribs = array();

		return json_encode( $jsattribs );
	}

	public static function getName() {

		return 'datecheck';
	}

	public static function getOtherPropTypesHandled() {

		return array( '_str' );
	}

	public function getResourceModuleNames() {

		return array( 'ext.semanticformsinputs.datecheck' );
	}

	public function getHtmlText() {

		global $sfgFieldNum;

		$disabled = "";
		if ( $this->mIsDisabled ) {
			$disabled = 'disabled=""';
		}

		return '<input id="input_' . $sfgFieldNum . '" ' . $disabled . ' name="' . $this->mInputName .
			   '" data-validation-optional="' . $this->mIsMandatory .
			   '" data-validation="date" data-validation-format="' . $this->mDateFormat . '" value="' .
			   $this->mCurrentValue . '" type="text" data-validation-error-msg="' .
			   wfMessage( 'semanticformsinputs-wrongformat' ) . '" >';
	}

}
