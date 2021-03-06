<?php

/**
 * Parameter criterion stating that the value must be a non numeric one
 *
 * @since 3.0
 *
 * @file CriterionIsNonNumeric.php
 * @ingroup Maps
 * @ingroup Criteria
 *
 * @author Daniel Werner
 */
class CriterionIsNonNumeric extends ItemParameterCriterion {

	/**
	 * Constructor
	 *
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @see ItemParameterCriterion::validate
	 */
	protected function doValidation( $value, Parameter $parameter, array $parameters ) {
		return !is_numeric( $value );
	}

	/**
	 * @see ItemParameterCriterion::getItemErrorMessage
	 */
	protected function getItemErrorMessage( Parameter $parameter ) {
		return wfMsgExt( 'validation-error-no-non-numeric', 'parsemag', $parameter->getOriginalName() );
	}

	/**
	 * @see ItemParameterCriterion::getFullListErrorMessage
	 */
	protected function getFullListErrorMessage( Parameter $parameter ) {
		global $wgLang;
		return wfMsgExt( 'validation-error-no-non-numerics', 'parsemag', $parameter->getOriginalName() );
	}
}