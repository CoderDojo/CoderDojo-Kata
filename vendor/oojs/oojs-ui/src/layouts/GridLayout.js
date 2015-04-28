/**
 * Layout made of proportionally sized columns and rows.
 *
 * @class
 * @extends OO.ui.Layout
 * @deprecated Use OO.ui.MenuLayout or plain CSS instead.
 *
 * @constructor
 * @param {OO.ui.PanelLayout[]} panels Panels in the grid
 * @param {Object} [config] Configuration options
 * @cfg {number[]} [widths] Widths of columns as ratios
 * @cfg {number[]} [heights] Heights of rows as ratios
 */
OO.ui.GridLayout = function OoUiGridLayout( panels, config ) {
	// Allow passing positional parameters inside the config object
	if ( OO.isPlainObject( panels ) && config === undefined ) {
		config = panels;
		panels = config.panels;
	}

	var i, len, widths;

	// Configuration initialization
	config = config || {};

	// Parent constructor
	OO.ui.GridLayout.super.call( this, config );

	// Properties
	this.panels = [];
	this.widths = [];
	this.heights = [];

	// Initialization
	this.$element.addClass( 'oo-ui-gridLayout' );
	for ( i = 0, len = panels.length; i < len; i++ ) {
		this.panels.push( panels[ i ] );
		this.$element.append( panels[ i ].$element );
	}
	if ( config.widths || config.heights ) {
		this.layout( config.widths || [ 1 ], config.heights || [ 1 ] );
	} else {
		// Arrange in columns by default
		widths = this.panels.map( function () { return 1; } );
		this.layout( widths, [ 1 ] );
	}
};

/* Setup */

OO.inheritClass( OO.ui.GridLayout, OO.ui.Layout );

/* Events */

/**
 * @event layout
 */

/**
 * @event update
 */

/* Methods */

/**
 * Set grid dimensions.
 *
 * @param {number[]} widths Widths of columns as ratios
 * @param {number[]} heights Heights of rows as ratios
 * @fires layout
 * @throws {Error} If grid is not large enough to fit all panels
 */
OO.ui.GridLayout.prototype.layout = function ( widths, heights ) {
	var x, y,
		xd = 0,
		yd = 0,
		cols = widths.length,
		rows = heights.length;

	// Verify grid is big enough to fit panels
	if ( cols * rows < this.panels.length ) {
		throw new Error( 'Grid is not large enough to fit ' + this.panels.length + 'panels' );
	}

	// Sum up denominators
	for ( x = 0; x < cols; x++ ) {
		xd += widths[ x ];
	}
	for ( y = 0; y < rows; y++ ) {
		yd += heights[ y ];
	}
	// Store factors
	this.widths = [];
	this.heights = [];
	for ( x = 0; x < cols; x++ ) {
		this.widths[ x ] = widths[ x ] / xd;
	}
	for ( y = 0; y < rows; y++ ) {
		this.heights[ y ] = heights[ y ] / yd;
	}
	// Synchronize view
	this.update();
	this.emit( 'layout' );
};

/**
 * Update panel positions and sizes.
 *
 * @fires update
 */
OO.ui.GridLayout.prototype.update = function () {
	var x, y, panel, width, height, dimensions,
		i = 0,
		top = 0,
		left = 0,
		cols = this.widths.length,
		rows = this.heights.length;

	for ( y = 0; y < rows; y++ ) {
		height = this.heights[ y ];
		for ( x = 0; x < cols; x++ ) {
			width = this.widths[ x ];
			panel = this.panels[ i ];
			dimensions = {
				width: ( width * 100 ) + '%',
				height: ( height * 100 ) + '%',
				top: ( top * 100 ) + '%'
			};
			// If RTL, reverse:
			if ( OO.ui.Element.static.getDir( document ) === 'rtl' ) {
				dimensions.right = ( left * 100 ) + '%';
			} else {
				dimensions.left = ( left * 100 ) + '%';
			}
			// HACK: Work around IE bug by setting visibility: hidden; if width or height is zero
			if ( width === 0 || height === 0 ) {
				dimensions.visibility = 'hidden';
			} else {
				dimensions.visibility = '';
			}
			panel.$element.css( dimensions );
			i++;
			left += width;
		}
		top += height;
		left = 0;
	}

	this.emit( 'update' );
};

/**
 * Get a panel at a given position.
 *
 * The x and y position is affected by the current grid layout.
 *
 * @param {number} x Horizontal position
 * @param {number} y Vertical position
 * @return {OO.ui.PanelLayout} The panel at the given position
 */
OO.ui.GridLayout.prototype.getPanel = function ( x, y ) {
	return this.panels[ ( x * this.widths.length ) + y ];
};
