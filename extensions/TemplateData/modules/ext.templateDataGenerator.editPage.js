( function ( $, mw ) {
	/**
	 * TemplateData Generator button fixture
	 * The button will appear on Template namespaces only, above the edit textbox
	 *
	 * @author Moriel Schottlender
	 */
	'use strict';

	$( function () {
		var $textbox = $( '#wpTextbox1' );

		// Check if there's an editor textarea and if we're in the proper namespace
		if ( $textbox.length && mw.config.get( 'wgCanonicalNamespace' ) === 'Template' ) {
			mw.libs.templateDataGenerator.init( $( '#mw-content-text' ), $textbox );
		}

	} );

}( jQuery, mediaWiki ) );
