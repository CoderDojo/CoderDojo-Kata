/**
 * Javascript code to be used with input type 'datecheck'.
 *
 * @author Simon Bachenberg
 *
 */

window.SFI_DateCheck_init = function(input_id, params) {
    $.validate({
		borderColorOnError : '', // Border color of elements with invalid value; empty string to not change border color as it messes up the style of the input box
		errorElementClass : 'form-error',  // Class that will be put on elements with invalid value
        onError: function() {
            if (!document.getElementById("form_error_header")) {
                jQuery("#contentSub").append('<div id="form_error_header" class="errorbox" style="font-size: medium"><img src="' + mw.config.get('sfgScriptPath') + '/skins/MW-Icon-AlertMark.png" />&nbsp;' + mw.message('sf_formerrors_header').escaped() + '</div>');
            }
        }
    });
};