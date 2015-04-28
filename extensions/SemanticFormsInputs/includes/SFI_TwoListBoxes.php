<?php
/**
 * File holding the SFITwoListBoxes class
 *
 * @author Yury Katkov
 * @file
 * @ingroup SemanticFormsInputs
 */

/**
 * The SFITwoListBoxes class that holds the code for 'two listboxes' form input
 * This class is an extension of Listbox input and it can do all that ListBox can do.
 * Not much code here; the main part is in javascript and css
 *
 * @ingroup SemanticFormsInputs
 */
    class SFITwoListBoxes extends SFListBoxInput {

    public function __construct( $input_number, $cur_value, $input_name, $disabled, $other_args ) {
        parent::__construct( $input_number, $cur_value, $input_name, $disabled, $other_args );
        $this->addJsInitFunctionData( 'SFI_TwoListboxes_init', $this->setupJsInitAttribs() );
    }

    public static function getName() {
        return 'two listboxes';
    }

    public static function getOtherPropTypesHandled() {
        return array( '_str', '_wpg' );
    }


    public function getResourceModuleNames() {
        return array( 'ext.semanticformsinputs.twolistboxes' );
    }

    public static function canHandleLists() {
    		return true;
    }

    /**
     * Prepares attributes for javascript that will be run.
     *
     * <b>It's a stub now</b>
     * @return string attributes for javascript file
     */
    private function setupJsInitAttribs() {
        $jsattribs = array();
        return json_encode( $jsattribs );
    }
}
