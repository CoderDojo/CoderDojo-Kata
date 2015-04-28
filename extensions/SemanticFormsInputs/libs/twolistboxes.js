/**
 * Javascript code to be used with input type 'two listboxes'.
 *
 * @author Yury Katkov
 *
 */
window.SFI_TwoListboxes_init = function(input_id, params){
	var $input = jQuery( "#" + input_id );

	$input.multiSelect({
		selectableHeader : '<input type="text" id="search_' + input_id + '" autocomplete = "off" class="two-listboxes-search"/>'
	});

	$input.next().find( 'input.two-listboxes-search' ).each( function( index ){
		jQuery( this ).quicksearch( jQuery( this ).next().find( 'li.ms-elem-selectable' ));
	}

	);
};