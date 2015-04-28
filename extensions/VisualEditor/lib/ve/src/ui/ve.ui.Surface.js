/*!
 * VisualEditor UserInterface Surface class.
 *
 * @copyright 2011-2014 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * A surface is a top-level object which contains both a surface model and a surface view.
 *
 * @class
 * @abstract
 * @extends OO.ui.Element
 *
 * @constructor
 * @param {HTMLDocument|Array|ve.dm.LinearData|ve.dm.Document} dataOrDoc Document data to edit
 * @param {Object} [config] Configuration options
 */
ve.ui.Surface = function VeUiSurface( dataOrDoc, config ) {
	var documentModel;

	// Parent constructor
	OO.ui.Element.call( this, config );

	// Mixin constructor
	OO.EventEmitter.call( this, config );

	// Properties
	this.globalOverlay = new ve.ui.Overlay( { classes: ['ve-ui-overlay-global'] } );
	this.localOverlay = new ve.ui.Overlay( { $: this.$, classes: ['ve-ui-overlay-local'] } );
	this.$blockers = this.$( '<div>' );
	this.$controls = this.$( '<div>' );
	if ( dataOrDoc instanceof ve.dm.Document ) {
		// ve.dm.Document
		documentModel = dataOrDoc;
	} else if ( dataOrDoc instanceof ve.dm.LinearData || ve.isArray( dataOrDoc ) ) {
		// LinearData or raw linear data
		documentModel = new ve.dm.Document( dataOrDoc );
	} else {
		// HTMLDocument
		documentModel = ve.dm.converter.getModelFromDom( dataOrDoc );
	}
	this.model = new ve.dm.Surface( documentModel );
	this.view = new ve.ce.Surface( this.model, this, { $: this.$ } );
	this.dialogs = this.createDialogWindowManager();
	this.commands = {};
	this.triggers = {};
	this.pasteRules = {};
	this.enabled = true;
	this.context = this.createContext();
	this.filibuster = null;

	// Events
	this.dialogs.connect( this, { closing: 'onDialogClosing' } );

	// Initialization
	this.$element
		.addClass( 've-ui-surface' )
		.append( this.view.$element );
	this.localOverlay.$element.append( this.$blockers, this.$controls );
	this.globalOverlay.$element.append( this.dialogs.$element );

	// Make instance globally accessible for debugging
	ve.instances.push( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.Surface, OO.ui.Element );

OO.mixinClass( ve.ui.Surface, OO.EventEmitter );

/* Events */

/**
 * When a command is added to the surface.
 *
 * @event addCommand
 * @param {string} name Symbolic name of command and trigger
 * @param {ve.ui.Command} command Command that's been registered
 * @param {ve.ui.Trigger[]} triggers Triggers to associate with command
 */

/**
 * When a surface is destroyed.
 *
 * @event destroy
 */

/* Methods */

/**
 * Destroy the surface, releasing all memory and removing all DOM elements.
 *
 * @method
 * @fires destroy
 */
ve.ui.Surface.prototype.destroy = function () {
	// Remove instance from global array
	ve.instances.splice( ve.instances.indexOf( this ), 1 );

	// Stop periodic history tracking in model
	this.model.stopHistoryTracking();

	// Disconnect events
	this.dialogs.disconnect( this );

	// Destroy the ce.Surface, the ui.Context and the dialogs WindowManager
	this.view.destroy();
	this.context.destroy();
	this.dialogs.destroy();

	// Remove DOM elements
	this.$element.remove();
	this.globalOverlay.$element.remove();
	this.localOverlay.$element.remove();

	// Let others know we have been destroyed
	this.emit( 'destroy' );
};

/**
 * Handle dialog teardown events
 */
ve.ui.Surface.prototype.onDialogClosing = function ( win, closing ) {
	closing.progress( ve.bind( function ( data ) {
		if ( data.state === 'teardown' ) {
			// Return focus to view
			this.getView().focus();
			// Re-assert selection
			this.getModel().getFragment().select();
		}
	}, this ) );
};

/**
 * Initialize surface.
 *
 * This must be called after the surface has been attached to the DOM.
 */
ve.ui.Surface.prototype.initialize = function () {
	this.getView().$element.after( this.localOverlay.$element );
	// Attach globalOverlay to the global <body>, not the local frame's <body>
	$( 'body' ).append( this.globalOverlay.$element );

	// The following classes can be used here:
	// ve-ui-surface-dir-ltr
	// ve-ui-surface-dir-rtl
	this.$element.addClass( 've-ui-surface-dir-' + this.getDir() );

	this.getView().initialize();
	this.getModel().startHistoryTracking();
};

/**
 * Create a context.
 *
 * @method
 * @abstract
 * @return {ve.ui.Context} Context
 * @throws {Error} If this method is not overridden in a concrete subclass
 */
ve.ui.Surface.prototype.createContext = function () {
	throw new Error( 've.ui.Surface.createContext must be overridden in subclass' );
};

/**
 * Create a dialog window manager.
 *
 * @method
 * @abstract
 * @return {ve.ui.WindowManager} Dialog window manager
 * @throws {Error} If this method is not overridden in a concrete subclass
 */
ve.ui.Surface.prototype.createDialogWindowManager = function () {
	throw new Error( 've.ui.Surface.createDialogWindowManager must be overridden in subclass' );
};

/**
 * Get the bounding rectangle of the surface, relative to the viewport.
 * @returns {Object} Object with top, bottom, left, right, width and height properties.
 */
ve.ui.Surface.prototype.getBoundingClientRect = function () {
	// We would use getBoundingClientRect(), but in iOS7 that's relative to the
	// document rather than to the viewport
	return this.$element[0].getClientRects()[0];
};

/**
 * Check if editing is enabled.
 *
 * @method
 * @returns {boolean} Editing is enabled
 */
ve.ui.Surface.prototype.isEnabled = function () {
	return this.enabled;
};

/**
 * Get the surface model.
 *
 * @method
 * @returns {ve.dm.Surface} Surface model
 */
ve.ui.Surface.prototype.getModel = function () {
	return this.model;
};

/**
 * Get the surface view.
 *
 * @method
 * @returns {ve.ce.Surface} Surface view
 */
ve.ui.Surface.prototype.getView = function () {
	return this.view;
};

/**
 * Get the context menu.
 *
 * @method
 * @returns {ve.ui.Context} Context user interface
 */
ve.ui.Surface.prototype.getContext = function () {
	return this.context;
};

/**
 * Get dialogs window set.
 *
 * @method
 * @returns {OO.ui.WindowManager} Dialogs window set
 */
ve.ui.Surface.prototype.getDialogs = function () {
	return this.dialogs;
};

/**
 * Get the local overlay.
 *
 * Local overlays are attached to the same frame as the surface.
 *
 * @method
 * @returns {ve.ui.Overlay} Local overlay
 */
ve.ui.Surface.prototype.getLocalOverlay = function () {
	return this.localOverlay;
};

/**
 * Get the global overlay.
 *
 * Global overlays are attached to the top-most frame.
 *
 * @method
 * @returns {ve.ui.Overlay} Global overlay
 */
ve.ui.Surface.prototype.getGlobalOverlay = function () {
	return this.globalOverlay;
};

/**
 * Get command associated with trigger string.
 *
 * @method
 * @param {string} trigger Trigger string
 * @returns {ve.ui.Command|undefined} Command
 */
ve.ui.Surface.prototype.getCommand = function ( trigger ) {
	return this.commands[trigger];
};

/**
 * Get triggers for a specified name.
 *
 * @method
 * @param {string} name Trigger name
 * @returns {ve.ui.Trigger[]} Triggers
 */
ve.ui.Surface.prototype.getTriggers = function ( name ) {
	return this.triggers[name];
};

/**
 * Disable editing.
 *
 * @method
 */
ve.ui.Surface.prototype.disable = function () {
	this.view.disable();
	this.model.disable();
	this.enabled = false;
};

/**
 * Enable editing.
 *
 * @method
 */
ve.ui.Surface.prototype.enable = function () {
	this.enabled = true;
	this.view.enable();
	this.model.enable();
};

/**
 * Execute an action or command.
 *
 * @method
 * @param {ve.ui.Trigger|string} action Trigger or symbolic name of action
 * @param {string} [method] Action method name
 * @param {Mixed...} [args] Additional arguments for action
 * @returns {boolean} Action or command was executed
 */
ve.ui.Surface.prototype.execute = function ( action, method ) {
	var trigger, obj, ret;

	if ( !this.enabled ) {
		return;
	}

	if ( action instanceof ve.ui.Trigger ) {
		// Lookup command by trigger
		trigger = action.toString();
		if ( trigger in this.commands ) {
			// Have command call execute with action arguments
			return this.commands[trigger].execute( this );
		}
	} else if ( typeof action === 'string' && typeof method === 'string' ) {
		// Validate method
		if ( ve.ui.actionFactory.doesActionSupportMethod( action, method ) ) {
			// Create an action object and execute the method on it
			obj = ve.ui.actionFactory.create( action, this );
			ret = obj[method].apply( obj, Array.prototype.slice.call( arguments, 2 ) );
			return ret === undefined || !!ret;
		}
	}
	return false;
};

/**
 * Add all commands from initialization options.
 *
 * Commands and triggers must be registered under the same name prior to adding them to the surface.
 *
 * @method
 * @param {string[]} names List of symbolic names of commands in the command registry
 * @throws {Error} If command has not been registered
 * @throws {Error} If trigger has not been registered
 * @throws {Error} If trigger is not complete
 * @fires addCommand
 */
ve.ui.Surface.prototype.addCommands = function ( names ) {
	var i, j, len, key, command, triggers, trigger;

	for ( i = 0, len = names.length; i < len; i++ ) {
		command = ve.ui.commandRegistry.lookup( names[i] );
		if ( !command ) {
			throw new Error( 'No command registered by that name: ' + names[i] );
		}
		// Normalize trigger key
		triggers = ve.ui.triggerRegistry.lookup( names[i] );
		if ( !triggers ) {
			throw new Error( 'No triggers registered by that name: ' + names[i] );
		}
		for ( j = triggers.length - 1; j >= 0; j-- ) {
			trigger = triggers[j];
			key = trigger.toString();
			// Validate trigger
			if ( key.length === 0 ) {
				throw new Error( 'Incomplete trigger: ' + trigger );
			}
			this.commands[key] = command;
		}
		this.triggers[names[i]] = triggers;
		this.emit( 'addCommand', names[i], command, triggers );
	}
};

/**
 * Get sanitization rules for rich paste
 *
 * @returns {Object} Paste rules
 */
ve.ui.Surface.prototype.getPasteRules = function () {
	return this.pasteRules;
};

/**
 * Set sanitization rules for rich paste
 *
 * @see ve.dm.ElementLinearData#sanitize
 * @param {Object} pasteRules Paste rules
 */
ve.ui.Surface.prototype.setPasteRules = function ( pasteRules ) {
	this.pasteRules = pasteRules;
};

/**
 * Surface 'dir' property (GUI/User-Level Direction)
 *
 * @returns {string} 'ltr' or 'rtl'
 */
ve.ui.Surface.prototype.getDir = function () {
	return this.$element.css( 'direction' );
};

ve.ui.Surface.prototype.initFilibuster = function () {
	var surface = this;
	this.filibuster = new ve.Filibuster()
		.wrapClass( ve.EventSequencer )
		.wrapNamespace( ve.dm, 've.dm' )
		.wrapNamespace( ve.ce, 've.ce' )
		.wrapNamespace( ve.ui, 've.ui', [
			// blacklist
			ve.ui.Surface.prototype.startFilibuster,
			ve.ui.Surface.prototype.stopFilibuster
		] )
		.setObserver( 'dm doc', function () {
			return JSON.stringify( surface.model.documentModel.data.data );
		} )
		.setObserver( 'dm range', function () {
			var selection = surface.model.selection;
			if ( !selection ) {
				return null;
			}
			return [ selection.from, selection.to ].join( ',' );
		} )
		.setObserver( 'DOM doc', function () {
			return surface.view.$element.html();
		} )
		.setObserver( 'DOM selection', function () {
			var nativeRange,
				nativeSelection = surface.view.nativeSelection;
			if ( nativeSelection.rangeCount === 0 ) {
				return null;
			}
			nativeRange = nativeSelection.getRangeAt( 0 );
			return JSON.stringify( {
				startContainer: nativeRange.startContainer.outerHTML,
				startOffset: nativeRange.startOffset,
				endContainer: nativeRange.endContainer.outerHTML,
				endOffset: nativeRange.endOffset
			} );
		} );
};

ve.ui.Surface.prototype.startFilibuster = function () {
	if ( !this.filibuster ) {
		this.initFilibuster();
	} else {
		this.filibuster.clearLogs();
	}
	this.filibuster.start();
};

ve.ui.Surface.prototype.stopFilibuster = function () {
	this.filibuster.stop();
};
