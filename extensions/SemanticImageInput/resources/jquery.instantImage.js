/**
 * JavaScript for the Semantic Image Input MediaWiki extension.
 * 
 * TODO: this was written in a sprint; could be made less evil.
 * 
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 */

(function( $, mw ) { $.fn.instantImage = function( options ) {
		
	var settings = $.extend( {
		'imagename': 'Beatles',
		'inputname': '',
		'apipath': 'https://en.wikipedia.org/w/api.php?callback=?',
		'imagewidth': 200
	}, options );
	
	return this.each( function() {
		
		var _this = this;
		var $this = $( this );
		
		this.loadedFirstReq = null;
		this.images = null;
		this.raw = null;
		
		this.getMainTitle = function( callback ) {
			$.getJSON(
				settings.apipath,
				{
					'action': 'query',
					'format': 'json',
					'titles': settings.imagename,
					'redirects': 1
				},
				function( data ) {
					if ( data.query && data.query.redirects ) {
						settings.imagename = data.query.redirects[0].to;
					}
					
					callback();
				}
			);
		};
		
		this.getImages = function( callback ) {
			$.getJSON(
				settings.apipath,
				{
					'action': 'query',
					'format': 'json',
					'prop': 'images',
					'titles': settings.imagename,
					'redirects': 1,
					'imlimit': 500
				},
				function( data ) {
					var imgNames = [];
					
					if ( data.query && data.query.pages ) {
						for ( pageid in data.query.pages ) {
							var images = data.query.pages[pageid].images;
							
							if ( typeof images !== 'undefined' ) {
								for ( var i = images.length - 1; i >= 0; i-- ) {
									imgNames.push( images[i].title );
								}
							}
							
							_this.images = imgNames;
							callback();
							return;
						}
					}
					
					_this.showNoImage();
				}
			);	
		};
		
		this.getRaw = function( callback ) {
			$.getJSON(
				settings.apipath,
				{
					'action': 'query',
					'format': 'json',
					'prop': 'revisions',
					'rvprop': 'content',
					'titles': settings.imagename
				},
				function( data ) {
					if ( data.query ) {
						for ( pageWikiID in data.query.pages ) {
							if ( data.query.pages[pageWikiID].revisions ) {
								_this.raw = data.query.pages[pageWikiID].revisions[0]["*"];
								callback();
								return;
							}
						}
					}
					
					_this.showNoImage();
				}
			);
		};
		
		this.getFirstImage = function() {
			var image = false;
			var lowest = this.raw.length;
			
			for ( var i = this.images.length - 1; i >= 0; i-- ) {
				var img = this.images[i].split( ':', 2 );
				var index = this.raw.indexOf( img[img.length > 1 ? 1 : 0] );
				
				if ( index !== -1 && index < lowest ) {
					lowest = index;
					image = this.images[i];
				}
			}
			
			return image;
		};
		
		this.showNoImage = function() {
			$this.html( 'No image found.' );
		};
		
		this.showImage = function( image ) {
			if ( image === false ) {
				this.showNoImage();
				return;
			}
			
			$.getJSON(
				settings.apipath,
				{
					'action': 'query',
					'format': 'json',
					'prop': 'imageinfo',
					'iiprop': 'url',
					'titles': image,
					'iiurlwidth': settings.imagewidth
				},
				function( data ) {
					if ( data.query && data.query.pages ) {
						var pages = data.query.pages;
						
						for ( p in pages ) {
							var info = pages[p].imageinfo;
							for ( i in info ) {
								if ( info[i].thumburl.indexOf( '/wikipedia/commons/' ) !== -1 ) {
									$( 'input[name="' + settings.inputname + '"]' ).val( image );
									
									$this.html( $( '<img />' ).attr( {
										'src': info[i].thumburl,
										'width': settings.imagewidth
									} ) );
									
									return;
								}
							}
							
							_this.showNoImage();
						}
					}
				}
			);
		};
		
		this.dispReqResult = function( images ) {
			if ( !_this.loadedFirstReq ) {
				_this.loadedFirstReq = true;
			}
			else {
				_this.showImage( _this.getFirstImage() );
			}
		};
		
		this.start = function() {
			this.loadedFirstReq = false;
			
			if ( settings.iteminput ) {
				settings.imagename = settings.iteminput.val();
			}
			
			if ( settings.imagename.trim() === '' ) {
				$this.html( '' );
			}
			else {
				this.getMainTitle( function() {
					_this.getImages( _this.dispReqResult );
					_this.getRaw( _this.dispReqResult );
				} );
			}
		};
		
		this.init = function() {
			if ( settings.iteminput ) {
				settings.iteminput.change( function() { _this.start(); } );
			}
			
			this.start();
		};
		
		this.init();
	} );
	
}; })( window.jQuery, window.mediaWiki );
