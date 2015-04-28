/*!
 * VisualEditor Initialization Target class.
 *
 * @copyright 2011-2014 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * Generic Initialization target.
 *
 * @class
 * @abstract
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {jQuery} $container Conainter to render target into, must be attached to the DOM
 * @throws {Error} Container must be attached to the DOM
 */
ve.init.Target = function VeInitTarget( $container ) {
	// Mixin constructors
	OO.EventEmitter.call( this );

	if ( !$.contains( $container[0].ownerDocument, $container[0] ) ) {
		throw new Error( 'Container must be attached to the DOM' );
	}

	// Properties
	this.$element = $container;
	this.surface = null;
	this.toolbar = null;
	this.debugBar = null;

	// Register
	ve.init.target = this;
};

/**
 * Destroy the target
 */
ve.init.Target.prototype.destroy = function () {
	if ( this.surface ) {
		this.surface.destroy();
		this.surface = null;
	}
	if ( this.toolbar ) {
		this.toolbar.destroy();
		this.toolbar = null;
	}
	if ( this.$element ) {
		this.$element.remove();
		this.$element = null;
	}
	ve.init.target = null;
};

/* Events */

/**
 * Fired when the #surface is ready.
 *
 * By default the surface's document is not focused. If the target wants
 * the browsers' focus to be in the surface (ready for typing and cursoring)
 * call `this.surface.getView().focus()` in a handler for this event.
 *
 * @event surfaceReady
 */

/* Inheritance */

OO.mixinClass( ve.init.Target, OO.EventEmitter );

/* Static Properties */

ve.init.Target.static.toolbarGroups = [
	// History
	{
		header: OO.ui.deferMsg( 'visualeditor-toolbar-history' ),
		include: [ 'undo', 'redo' ]
	},
	// Format
	{
		header: OO.ui.deferMsg( 'visualeditor-toolbar-paragraph-format' ),
		type: 'menu',
		indicator: 'down',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-format-tooltip' ),
		include: [ { group: 'format' } ],
		promote: [ 'paragraph' ],
		demote: [ 'preformatted' ]
	},
	// Style
	{
		header: OO.ui.deferMsg( 'visualeditor-toolbar-text-style' ),
		type: 'list',
		indicator: 'down',
		icon: 'text-style',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		include: [ { group: 'textStyle' }, 'language', 'clear' ],
		promote: [ 'bold', 'italic' ],
		demote: [ 'strikethrough', 'code', 'underline', 'language', 'clear' ]
	},
	// Link
	{
		header: OO.ui.deferMsg( 'visualeditor-linkinspector-title' ),
		include: [ 'link' ]
	},
	// Structure
	{
		header: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		type: 'list',
		icon: 'bullet-list',
		indicator: 'down',
		include: [ { group: 'structure' } ],
		demote: [ 'outdent', 'indent' ]
	},
	// Insert
	{
		header: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		type: 'list',
		icon: 'insert',
		label: '',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		indicator: 'down',
		include: '*',
		demote: [ 'specialcharacter' ]
	}
];

ve.init.Target.static.surfaceCommands = [
	'undo',
	'redo',
	'bold',
	'italic',
	'link',
	'clear',
	'underline',
	'subscript',
	'superscript',
	'code',
	'strikethrough',
	'indent',
	'outdent',
	'commandHelp',
	'paragraph',
	'heading1',
	'heading2',
	'heading3',
	'heading4',
	'heading5',
	'heading6',
	'preformatted',
	'pasteSpecial'
];

/**
 * Surface paste rules
 *
 * One set for external (non-VE) paste sources and one for all paste sources.
 *
 * @see ve.dm.ElementLinearData#sanitize
 * @type {Object}
 */
ve.init.Target.static.pasteRules = {
	external: {
		blacklist: [
			// Annotations
			// TODO: allow spans
			'textStyle/span',
			// Nodes
			'alienInline', 'alienBlock'
		]
	},
	all: null
};

/* Methods */

/**
 * Create a surface.
 *
 * @method
 * @param {ve.dm.Document} dmDoc Document model
 * @param {Object} [config] Configuration options
 * @returns {ve.ui.Surface}
 */
ve.init.Target.prototype.createSurface = function ( dmDoc, config ) {
	return new ve.ui.DesktopSurface( dmDoc, config );
};

/**
 * Get the target's surface
 *
 * @return {ve.ui.Surface} Surface
 */
ve.init.Target.prototype.getSurface = function () {
	return this.surface;
};

/**
 * Get the target's toolbar
 *
 * @return {ve.ui.TargetToolbar} Toolbar
 */
ve.init.Target.prototype.getToolbar = function () {
	return this.toolbar;
};

/**
 * Get the target's debug bar
 *
 * @return {ve.ui.DebugBar} Toolbar
 */
ve.init.Target.prototype.getDebugBar = function () {
	return this.debugBar;
};

/**
 * Set up the toolbar and insert it into the DOM.
 *
 * The default implementation inserts it before the surface, but subclasses can override this.
 *
 * @param {Object} [config] Configuration options
 */
ve.init.Target.prototype.setupToolbar = function ( config ) {
	if ( !this.surface ) {
		throw new Error( 'Surface must be setup before Toolbar' );
	}
	this.toolbar = new ve.ui.TargetToolbar( this, this.surface, config );
	this.toolbar.setup( this.constructor.static.toolbarGroups );
	this.surface.addCommands( this.constructor.static.surfaceCommands );
	this.toolbar.$element.insertBefore( this.surface.$element );
};

/**
 * Set up the debug bar and insert it into the DOM.
 *
 * The default implementation inserts it after the surface, but subclasses can override this.
 */
ve.init.Target.prototype.setupDebugBar = function () {
	if ( !this.surface ) {
		throw new Error( 'Surface must be setup before DebugBar' );
	}
	this.debugBar = new ve.ui.DebugBar( this.surface );
	this.debugBar.$element.insertAfter( this.surface.$element );
};
