/*!
 * VisualEditor UserInterface MWLinkTargetInputWidget class.
 *
 * @copyright 2011-2014 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Creates an ve.ui.MWLinkTargetInputWidget object.
 *
 * @class
 * @extends ve.ui.LinkTargetInputWidget
 * @mixins OO.ui.LookupInputWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLinkTargetInputWidget = function VeUiMWLinkTargetInputWidget( config ) {
	// Config intialization
	config = config || {};

	// Parent constructor
	ve.ui.LinkTargetInputWidget.call( this, config );

	// Mixin constructors
	OO.ui.LookupInputWidget.call( this, this, config );

	// Properties
	this.annotation = null;
	this.choosing = false;

	// Events
	this.lookupMenu.connect( this, { choose: 'onLookupMenuItemChoose' } );

	// Initialization
	this.$element.addClass( 've-ui-mwLinkTargetInputWidget' );
	this.lookupMenu.$element.addClass( 've-ui-mwLinkTargetInputWidget-menu' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLinkTargetInputWidget, ve.ui.LinkTargetInputWidget );

OO.mixinClass( ve.ui.MWLinkTargetInputWidget, OO.ui.LookupInputWidget );

/* Methods */

/**
 * Handle menu item select event.
 *
 * If no item is selected then the input must be invalid, so clear the annotation.
 * We shouldn't just leave the previous annotation as the user has no way of knowing
 * what that might be. For example if "Foo{}Bar" is typed, this.annotation will be
 * a link to "Foo".
 *
 * @method
 * @param {OO.ui.MenuItemWidget|null} item Selected item
 * @fires change
 */
ve.ui.MWLinkTargetInputWidget.prototype.onLookupMenuItemChoose = function ( item ) {
	if ( item ) {
		// WARNING: This assumes that #setAnnotation will emit `change` events synchronously
		// TODO: Consider how this trick can be solved better, and possibly pushed upstream to
		// OO.ui.LookupInputWidget so others don't fall into this trap
		this.choosing = true;
		this.setAnnotation( item.getData() );
		this.choosing = false;
	} else if ( this.annotation ) {
		this.annotation = null;
		this.emit( 'change', this.getValue() );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkTargetInputWidget.prototype.onLookupInputChange = function () {
	// WARNING: See #onLookupMenuItemChoose for why this is fragile
	if ( !this.choosing ) {
		this.openLookupMenu();
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkTargetInputWidget.prototype.isValid = function () {
	if ( this.annotation instanceof ve.dm.MWExternalLinkAnnotation ) {
		return this.annotation.getAttribute( 'href' )
			.match( /(^|\s)((https?:\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/gi );
	}
	return !!this.getValue();
};

/**
 * Gets a new request object of the current lookup query value.
 *
 * @method
 * @returns {jqXHR} AJAX object without success or fail handlers attached
 */
ve.ui.MWLinkTargetInputWidget.prototype.getLookupRequest = function () {
	if ( mw.Title.newFromText( this.value ) ) {
		return ve.init.target.constructor.static.apiRequest( {
			action: 'query',
			generator: 'prefixsearch',
			gpssearch: this.value,
			gpsnamespace: 0,
			prop: 'info|pageprops',
			ppprop: 'disambiguation',
			redirect: ''
		} );
	} else {
		// Don't send invalid titles to the API.
		// Just pretend it returned nothing so we can show the 'invalid title' section
		return $.Deferred().resolve( [] ).promise( { abort: function () {
			// Do nothing. This is just so OOUI doesn't break due to abort being undefined.
		} } );
	}
};

/**
 * Get lookup cache item from server response data.
 *
 * @method
 * @param {Mixed} data Response from server
 */
ve.ui.MWLinkTargetInputWidget.prototype.getLookupCacheItemFromData = function ( data ) {
	return data.query && data.query.pages || {};
};

/**
 * Get list of menu items from a server response.
 *
 * @param {Object} data Query result
 * @returns {OO.ui.MenuItemWidget[]} Menu items
 */
ve.ui.MWLinkTargetInputWidget.prototype.getLookupMenuItemsFromData = function ( data ) {
	var i, len, item, pageExistsExact, pageExists, index, matchingPage,
		menu$ = this.lookupMenu.$,
		items = [],
		existingPages = [],
		matchingPages = [],
		disambigPages = [],
		redirectPages = [],
		titleObj = mw.Title.newFromText( this.value ),
		linkCacheUpdate = {};

	for ( index in data ) {
		matchingPage = data[index];
		linkCacheUpdate[matchingPage.title] = { missing: false, redirect: false, disambiguation: false };
		existingPages.push( matchingPage.title );

		if ( matchingPage.redirect !== undefined ) {
			redirectPages.push( matchingPage.title );
			linkCacheUpdate[matchingPage.title].redirect = true;
		} else if ( matchingPage.pageprops !== undefined && matchingPage.pageprops.disambiguation !== undefined ) {
			disambigPages.push( matchingPage.title );
			linkCacheUpdate[matchingPage.title].disambiguation = true;
		} else {
			matchingPages.push( matchingPage.title );
		}
	}

	// If not found, run value through mw.Title to avoid treating a match as a
	// mismatch where normalisation would make them matching (bug 48476)
	pageExistsExact = ve.indexOf( this.value, existingPages ) !== -1;
	pageExists = pageExistsExact || (
		titleObj && ve.indexOf( titleObj.getPrefixedText(), existingPages ) !== -1
	);

	if ( !pageExists ) {
		linkCacheUpdate[this.value] = { missing: true, redirect: false, disambiguation: false };
	}

	ve.init.platform.linkCache.set( linkCacheUpdate );

	// External link
	if ( ve.init.platform.getExternalLinkUrlProtocolsRegExp().test( this.value ) ) {
		items.push( new OO.ui.MenuSectionItemWidget(
			'externalLink',
			{ $: menu$, label: ve.msg( 'visualeditor-linkinspector-suggest-external-link' ) }
		) );
		items.push( new OO.ui.MenuItemWidget(
			this.getExternalLinkAnnotationFromUrl( this.value ),
			{ $: menu$, rel: 'externalLink', label: this.value }
		) );
	}

	// Internal link
	if ( !pageExists ) {
		if ( titleObj ) {
			items.push( new OO.ui.MenuSectionItemWidget(
				'newPage',
				{ $: menu$, label: ve.msg( 'visualeditor-linkinspector-suggest-new-page' ) }
			) );
			items.push( new OO.ui.MenuItemWidget(
				this.getInternalLinkAnnotationFromTitle( this.value ),
				{ $: menu$, rel: 'newPage', label: this.value }
			) );
		} else {
			// If no title object could be created, it means the title is illegal
			item = new OO.ui.MenuSectionItemWidget(
				'illegalTitle',
				{ $: menu$, label: ve.msg( 'visualeditor-linkinspector-illegal-title' ) }
			);
			item.$element.addClass( 've-ui-mwLinkTargetInputWidget-warning' );
			items.push( item );
		}
	}

	// Matching pages
	if ( matchingPages && matchingPages.length ) {
		items.push( new OO.ui.MenuSectionItemWidget(
			'matchingPages',
			{ $: menu$, label: ve.msg( 'visualeditor-linkinspector-suggest-matching-page', matchingPages.length ) }
		) );
		// Offer the exact text as a suggestion if the page exists
		if ( pageExists && !pageExistsExact ) {
			matchingPages.unshift( this.value );
		}
		for ( i = 0, len = matchingPages.length; i < len; i++ ) {
			items.push( new OO.ui.MenuItemWidget(
				this.getInternalLinkAnnotationFromTitle( matchingPages[i] ),
				{ $: menu$, rel: 'matchingPage', label: matchingPages[i] }
			) );
		}
	}

	// Disambiguation pages
	if ( disambigPages.length ) {
		items.push( new OO.ui.MenuSectionItemWidget(
			'disambigPages',
			{ $: menu$, label: ve.msg( 'visualeditor-linkinspector-suggest-disambig-page', disambigPages.length ) }
		) );
		for ( i = 0, len = disambigPages.length; i < len; i++ ) {
			items.push( new OO.ui.MenuItemWidget(
				this.getInternalLinkAnnotationFromTitle( disambigPages[i] ),
				{ $: menu$, rel: 'disambigPage', label: disambigPages[i] }
			) );
		}
	}

	// Redirect pages
	if ( redirectPages.length ) {
		items.push( new OO.ui.MenuSectionItemWidget(
			'redirectPages',
			{ $: menu$, label: ve.msg( 'visualeditor-linkinspector-suggest-redirect-page', redirectPages.length ) }
		) );
		for ( i = 0, len = redirectPages.length; i < len; i++ ) {
			items.push( new OO.ui.MenuItemWidget(
				this.getInternalLinkAnnotationFromTitle( redirectPages[i] ),
				{ $: menu$, rel: 'redirectPage', label: redirectPages[i] }
			) );
		}
	}

	return items;
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkTargetInputWidget.prototype.initializeLookupMenuSelection = function () {
	var item;

	if ( this.annotation ) {
		this.lookupMenu.selectItem( this.lookupMenu.getItemFromData( this.annotation ) );
	}

	item = this.lookupMenu.getSelectedItem();
	if ( !item ) {
		// Parent method
		OO.ui.LookupInputWidget.prototype.initializeLookupMenuSelection.call( this );
	}

	// Update annotation to match selected item
	item = this.lookupMenu.getSelectedItem();
	if ( item ) {
		// Set annotation directly, bypassing re-setting the value of the input
		this.annotation = item.getData();
		this.emit( 'change', this.getValue() );
	}
};

/**
 * Set the value of the input.
 *
 * Overrides setValue to keep annotations in sync.
 *
 * @method
 * @param {string} value New value
 */
ve.ui.MWLinkTargetInputWidget.prototype.setValue = function ( value ) {
	// Keep annotation in sync with value by skipping parent and calling grandparent method
	OO.ui.TextInputWidget.prototype.setValue.call( this, value );
};

/**
 * Gets an internal link annotation.
 *
 * File: or Category: links will be prepended with a colon so they are interpreted as a links rather
 * than image inclusions or categorizations.
 *
 * @method
 * @param {string} target Page title
 * @returns {ve.dm.MWInternalLinkAnnotation}
 */
ve.ui.MWLinkTargetInputWidget.prototype.getInternalLinkAnnotationFromTitle = function ( target ) {
	var title = mw.Title.newFromText( target );

	if (
		title &&
		( title.getNamespaceId() === 6 || title.getNamespaceId() === 14 ) &&
		target[0] !== ':'
	) {
		// Prepend links to File and Category namespace with a colon
		target = ':' + target;
	}

	return new ve.dm.MWInternalLinkAnnotation( {
		type: 'link/mwInternal',
		attributes: {
			title: target,
			// bug 62816: we really need a builder for this stuff
			normalizedTitle: ve.dm.MWInternalLinkAnnotation.static.normalizeTitle( target ),
			lookupTitle: ve.dm.MWInternalLinkAnnotation.static.getLookupTitle( target )
		}
	} );
};

/**
 * Gets an external link annotation.
 *
 * @method
 * @param {string} target Web address
 * @returns {ve.dm.MWExternalLinkAnnotation}
 */
ve.ui.MWLinkTargetInputWidget.prototype.getExternalLinkAnnotationFromUrl = function ( target ) {
	return new ve.dm.MWExternalLinkAnnotation( {
		type: 'link/mwExternal',
		attributes: {
			href: target
		}
	} );
};

/**
 * Gets a target from an annotation.
 *
 * @method
 * @param {ve.dm.MWExternalLinkAnnotation|ve.dm.MWInternalLinkAnnotation} annotation Annotation
 * @returns {string} Target
 */
ve.ui.MWLinkTargetInputWidget.prototype.getTargetFromAnnotation = function ( annotation ) {
	if ( annotation instanceof ve.dm.MWExternalLinkAnnotation ) {
		return annotation.getAttribute( 'href' );
	} else if ( annotation instanceof ve.dm.MWInternalLinkAnnotation ) {
		return annotation.getAttribute( 'title' );
	}

	return '';
};

/**
 * @inheritdoc
 */
ve.ui.MWLinkTargetInputWidget.prototype.getHref = function () {
	var title;

	if ( this.annotation instanceof ve.dm.MWExternalLinkAnnotation ) {
		return this.annotation.getAttribute( 'href' );
	} else if ( this.annotation instanceof ve.dm.MWInternalLinkAnnotation ) {
		title = mw.Title.newFromText( this.annotation.getAttribute( 'title' ) );
		return title.getUrl();
	}

	return '';
};
