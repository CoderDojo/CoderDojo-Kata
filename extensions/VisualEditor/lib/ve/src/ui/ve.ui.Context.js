/*!
 * VisualEditor UserInterface Context class.
 *
 * @copyright 2011-2014 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * UserInterface context.
 *
 * @class
 * @abstract
 * @extends OO.ui.Element
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {Object} [config] Configuration options
 */
ve.ui.Context = function VeUiContext( surface, config ) {
	// Parent constructor
	OO.ui.Element.call( this, config );

	// Properties
	this.surface = surface;
	this.visible = false;
	this.inspector = null;
	this.inspectors = this.createInspectorWindowManager();
	this.menu = new ve.ui.ContextMenuWidget( { $: this.$ } );
	this.lastSelectedNode = null;
	this.afterContextChangeTimeout = null;
	this.afterContextChangeHandler = ve.bind( this.afterContextChange, this );
	this.updateDimensionsDebounced = ve.debounce( ve.bind( this.updateDimensions, this ) );

	// Events
	this.surface.getModel().connect( this, { contextChange: 'onContextChange' } );
	this.inspectors.connect( this, { opening: 'onInspectorOpening' } );
	this.menu.connect( this, { choose: 'onContextItemChoose' } );

	// Initialization
	this.$element.addClass( 've-ui-context' );
	this.menu.toggle( false ).$element.addClass( 've-ui-context-menu' );
	this.inspectors.$element.addClass( 've-ui-context-inspectors' );
};

/* Inheritance */

OO.inheritClass( ve.ui.Context, OO.ui.Element );

/* Methods */

/**
 * Handle context change event.
 *
 * While an inspector is opening or closing, all changes are ignored so as to prevent inspectors
 * that change the selection from within their setup or teardown processes changing context state.
 *
 * The response to selection changes is deferred to prevent teardown processes handlers that change
 * the selection from causing this function to recurse. These responses are also debounced for
 * efficiency, so that if there are three selection changes in the same tick, #afterContextChange only
 * runs once.
 *
 * @see #afterContextChange
 */
ve.ui.Context.prototype.onContextChange = function () {
	if ( this.inspector && ( this.inspector.isOpening() || this.inspector.isClosing() ) ) {
		// Cancel debounced change handler
		clearTimeout( this.afterContextChangeTimeout );
		this.afterContextChangeTimeout = null;
	} else {
		if ( this.afterContextChangeTimeout === null ) {
			// Ensure change is handled on next cycle
			this.afterContextChangeTimeout = setTimeout( this.afterContextChangeHandler );
		}
	}
	// Purge available tools cache
	this.availableTools = null;
};

/**
 * Handle debounced context change events.
 */
ve.ui.Context.prototype.afterContextChange = function () {
	var selectedNode = this.surface.getModel().getSelectedNode();

	// Reset debouncing state
	this.afterContextChangeTimeout = null;

	if ( this.isVisible() ) {
		if ( this.menu.isVisible() ) {
			if ( this.isInspectable() ) {
				// Change state: menu -> menu
				this.populateMenu();
				this.updateDimensionsDebounced();
			} else {
				// Change state: menu -> closed
				this.menu.toggle( false );
				this.toggle( false );
			}
		} else if ( this.inspector && ( !selectedNode || ( selectedNode !== this.lastSelectedNode ) ) ) {
			// Change state: inspector -> (closed|menu)
			// Unless there is a selectedNode that hasn't changed (e.g. your inspector is editing a node)
			this.inspector.close();
		}
	} else {
		if ( this.isInspectable() ) {
			// Change state: closed -> menu
			this.menu.toggle( true );
			this.populateMenu();
			this.toggle( true );
		}
	}

	this.lastSelectedNode = selectedNode;
};

/**
 * Handle an inspector opening event.
 *
 * @param {OO.ui.Window} win Window that's being opened
 * @param {jQuery.Promise} opening Promise resolved when window is opened; when the promise is
 *   resolved the first argument will be a promise which will be resolved when the window begins
 *   closing, the second argument will be the opening data
 * @param {Object} data Window opening data
 */
ve.ui.Context.prototype.onInspectorOpening = function ( win, opening ) {
	this.inspector = win;

	opening
		.progress( ve.bind( function ( data ) {
			if ( data.state === 'setup' ) {
				if ( this.menu.isVisible() ) {
					// Change state: menu -> inspector
					this.menu.toggle( false );
				} else if ( !this.isVisible() ) {
					// Change state: closed -> inspector
					this.toggle( true );
				}
			}
			this.updateDimensionsDebounced();
		}, this ) )
		.always( ve.bind( function ( opened ) {
			opened.always( ve.bind( function ( closed ) {
				closed.always( ve.bind( function () {
					var inspectable = !!this.getAvailableTools().length;

					this.inspector = null;

					if ( inspectable ) {
						// Change state: inspector -> menu
						this.menu.toggle( true );
						this.populateMenu();
						this.updateDimensionsDebounced();
					} else {
						// Change state: inspector -> closed
						this.toggle( false );
					}

					// Restore selection
					if ( this.getSurface().getModel().getSelection() ) {
						this.getSurface().getView().focus();
					}
				}, this ) );
			}, this ) );
		}, this ) );
};

/**
 * Handle context item choose events.
 *
 * @param {ve.ui.ContextItemWidget} item Chosen item
 */
ve.ui.Context.prototype.onContextItemChoose = function ( item ) {
	if ( item ) {
		item.getCommand().execute( this.surface );
	}
};

/**
 * Check if context is visible.
 *
 * @return {boolean} Context is visible
 */
ve.ui.Context.prototype.isVisible = function () {
	return this.visible;
};

/**
 * Check if current content is inspectable.
 *
 * @return {boolean} Content is inspectable
 */
ve.ui.Context.prototype.isInspectable = function () {
	return !!this.getAvailableTools().length;
};

/**
 * Check if current content is inspectable.
 *
 * @return {boolean} Content is inspectable
 */
ve.ui.Context.prototype.hasInspector = function () {
	var i, availableTools = this.getAvailableTools();
	for ( i = availableTools.length - 1; i >= 0; i-- ) {
		if ( availableTools[i].tool.prototype instanceof ve.ui.InspectorTool ) {
			return true;
		}
	}
	return false;
};

/**
 * Get available tools.
 *
 * Result is cached, and cleared when the model or selection changes.
 *
 * @returns {Object[]} List of objects containing `tool` and `model` properties, representing each
 *   compatible tool and the node or annotation it is compatible with
 */
ve.ui.Context.prototype.getAvailableTools = function () {
	if ( !this.availableTools ) {
		this.availableTools = ve.ui.toolFactory.getToolsForFragment(
			this.surface.getModel().getFragment( null, false )
		);
	}
	return this.availableTools;
};

/**
 * Get the surface the context is being used with.
 *
 * @return {ve.ui.Surface}
 */
ve.ui.Context.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Get inspector window set.
 *
 * @return {OO.ui.WindowManager}
 */
ve.ui.Context.prototype.getInspectors = function () {
	return this.inspectors;
};

/**
 * Get context menu.
 *
 * @return {ve.ui.ContextMenuWidget}
 */
ve.ui.Context.prototype.getMenu = function () {
	return this.menu;
};

/**
 * Create a inspector window manager.
 *
 * @method
 * @abstract
 * @return {ve.ui.WindowManager} Inspector window manager
 * @throws {Error} If this method is not overridden in a concrete subclass
 */
ve.ui.Context.prototype.createInspectorWindowManager = function () {
	throw new Error( 've.ui.Context.createInspectorWindowManager must be overridden in subclass' );
};

/**
 * Update the contents of the menu.
 *
 * @chainable
 */
ve.ui.Context.prototype.populateMenu = function () {
	var i, len, tool,
        items = [],
        tools = this.getAvailableTools();

	this.menu.clearItems();
	if ( tools.length ) {
		for ( i = 0, len = tools.length; i < len; i++ ) {
			tool = tools[i];
			items.push( new ve.ui.ContextItemWidget(
				tool.tool.static.name, tool.tool, tool.model, { $: this.$ }
			) );
		}
		this.menu.addItems( items );
	}

	return this;
};

/**
 * Toggle the visibility of the context.
 *
 * @param {boolean} [show] Show the context, omit to toggle
 * @return {jQuery.Promise} Promise resolved when context is finished showing/hiding
 */
ve.ui.Context.prototype.toggle = function ( show ) {
	show = show === undefined ? !this.visible : !!show;
	if ( show !== this.visible ) {
		this.visible = show;
		this.$element.toggle();
	}
	return $.Deferred().resolve().promise();
};

/**
 * Update the size and position of the context.
 *
 * @chainable
 */
ve.ui.Context.prototype.updateDimensions = function () {
	// Override in subclass if context is positioned relative to content
	return this;
};

/**
 * Destroy the context, removing all DOM elements.
 */
ve.ui.Context.prototype.destroy = function () {
	// Disconnect events
	this.surface.getModel().disconnect( this );
	this.inspectors.disconnect( this );
	this.menu.disconnect( this );

	// Destroy inspectors WindowManager
	this.inspectors.destroy();

	// Stop timers
	clearTimeout( this.afterContextChangeTimeout );

	this.$element.remove();
	return this;
};
