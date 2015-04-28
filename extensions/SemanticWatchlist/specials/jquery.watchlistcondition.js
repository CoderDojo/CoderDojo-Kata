/**
 * JavaScript for Special:WatchlistConditions in the Semantic Watchlist extension.
 * @see http://www.mediawiki.org/wiki/Extension:Semantic_Watchlist
 *
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 * @author Nischayn22
 */

(function( $, mw ){ $.fn.watchlistcondition = function( group, options ) {

	var self = this;
	this.group = group;

	this.buildHtml = function() {
		this.html( $( '<legend />' ).text( mw.msg( 'swl-group-legend' ) ) );

		var table = $( '<table />' ).attr( { 'class': 'swltable' } );

		this.nameInput = $( '<input />' ).attr( {
			'type': 'text',
			'value': group.name,
			'size': 30
		} );

		this.nameInput.keyup( function() {
			self.attr( 'groupname', $( this ).val() );
		} );

		var name = $( '<p />' ).text( mw.msg( 'swl-group-name' ) + ' ' ).append( this.nameInput );
		table.append( name );

		this.propertiesInput = $( '<input />' ).attr( {
			'type': 'text',
			'value': group.properties.join('|'),
			'size': 30,
			'placeholder' : mw.msg( 'swl-properties-list' )
		} );

		this.propertiesInput.keyup( function() {
			self.attr( 'properties', $( this ).val() );
		} );

		var conditionValue, conditionType;

		switch ( true ) {
			case group.categories.length > 0:
				conditionValue = group.categories[0];
				conditionType = 'category';
				break;
			case group.namespaces.length > 0:
				conditionValue = group.namespaces[0];
				conditionType = 'namespace';
				break;
			case group.concepts.length > 0:
				conditionValue = group.concepts[0];
				conditionType = 'concept';
				break;
		}

		this.conditionTypeInput = $( '<select />' );
		var conditionTypes = [ 'category', 'namespace', 'concept' ];
		var conditionTypeGroups = [ 'categories', 'namespaces', 'concepts' ];

		for ( i in conditionTypes ) {
			// Give grep a chance to find the usages:
			// swl-group-category, swl-group-namespace, swl-group-concept
			var optionElement = $( '<option />' )
				.text( mw.msg( 'swl-group-' + conditionTypes[i] ) )
				.attr( { 'value': conditionTypes[i], 'type': conditionTypeGroups[i] } );

			if ( conditionType == conditionTypes[i] ) {
				optionElement.attr( 'selected', 'selected' );
			}

			this.conditionTypeInput.append( optionElement );
		}

		this.conditionNameInput = $( '<input />' ).attr( {
			'type': 'text',
			'class' : 'conditionInput',
			'value': conditionValue,
			'size': 30
		} );

		table.append( $( '<p/>' )
			.append( mw.msg( 'swl-group-properties' ) )
			.append( '&nbsp;' )
			.append( this.propertiesInput )
			.append( '&nbsp;' )
			.append( mw.msg( 'swl-group-page-selection' ) )
			.append( '&nbsp;' )
			.append( this.conditionTypeInput )
			.append( '&nbsp;' )
			.append( this.conditionNameInput )
		);

		table.append( $( '<fieldset/>' ).attr({ 'class' : 'customTexts' }).append( $( '<legend/>' ).html( mw.msg( 'swl-custom-legend' ) ) ) );

		this.addCustomTextDiv = function( customText ) {

			var customTextDiv = $( '<div/>' ).attr({
				'class' : 'customText',
			});
			var propertyInput = '<input type="text" size="15" id="propertyInput" value="'+customText[0]+'" />';
			var newValueInput = '<input type="text" size="15" id="newValueInput" value="'+customText[1]+'" />';
			var customTextInput = '<p>' + '<textarea rows="3" cols="80" id="customTextInput">' + customText[2] + '</textarea>' + '</p>';

			var removeButton = $( '<input />' ).attr( {
				'type': 'button',
				'value': mw.msg( 'swl-custom-remove-property' )
			} ).click( function() {
				var container = $(this).closest( $( '.customText' ) );
				container.slideUp( 'fast', function() { container.remove(); } );
			} );

			var customTextTable = $( '<table/>' );
			customTextTable.append( $( '<tr/>' ).
				append( $( '<td/>' ).html( mw.msg( 'swl-custom-input', propertyInput, newValueInput, customTextInput ))).
				append( $( '<td style="text-align: right;"/>' ).html( removeButton ) ));

			customTextDiv.append( customTextTable );
			table.find('.addCustomText').before( customTextDiv );
		}

		var addCustomTextButton = $( '<input />' ).attr( {
			'type': 'button',
			'value': mw.msg( 'swl-custom-text-add' ),
			'class' : 'addCustomText'
		} ).click( function() {
			self.addCustomTextDiv( new Array( '', '', '' ) )
		} );

		table.find('.customTexts').append( addCustomTextButton );

		for ( i in group.customTexts ) {
			self.addCustomTextDiv( group.customTexts[i].split( '~' ) );
		}

		this.append( table );

		this.append(
			$( '<input />' ).attr( {
				'type': 'button',
				'value': mw.msg( 'swl-group-remove' )
			} ).click( function() {
				if ( confirm( mw.msg( 'swl-group-confirm-remove', self.nameInput.val() ) ) ) {
					this.disabled = true;
					var button = this;

					self.hide();
					self.attr( 'display', 'none' );
				}
			} )
		);
	};

	this.doSave = function( callback ) {
		var customTexts = new Array();
		self.find( '.customText' ).each( function( index, element ) {
			element = $( element );
			customTexts.push( element.find( '#propertyInput' ).val() + '~' + element.find( '#newValueInput' ).val() + '~' + element.find( '#customTextInput' ).val() );
		} );
		var args = {
			'action': ( this.group.id == '' ? 'addswlgroup' : 'editswlgroup' ),
			'format': 'json',
			'id': this.group.id,
			'name': self.attr( 'groupname' ),
			'properties': self.attr( 'properties' ),
			'customTexts' : customTexts.join( '|' )
		};
		args[self.find('select :selected').attr('type')] = self.find( '.conditionInput' ).val();

	 	$.getJSON(
			mw.util.wikiScript( 'api' ),
			args,
			function( data ) {
				callback( data.success );
			}
		);
	};

	this.doDelete = function( callback ) {
		$.getJSON(
			mw.util.wikiScript( 'api' ),
			{
				'action': 'deleteswlgroup',
				'format': 'json',
				'ids': this.group.id
			},
			function( data ) {
				callback( data.success );
			}
		);
	};

	return this;

}; })( jQuery, mediaWiki );
