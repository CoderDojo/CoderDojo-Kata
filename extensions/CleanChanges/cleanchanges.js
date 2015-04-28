(function ( mw, $ ) {
	'use strict';

	window.toggleVisibilityE = function ( levelId, otherId, linkId, type ) {
		var thisLevel = document.getElementById( levelId ),
			otherLevel = document.getElementById( otherId ),
			linkLevel = document.getElementById( linkId );

		if ( thisLevel.style.display === 'none' ) {
			thisLevel.style.display = type;
			otherLevel.style.display = 'none';
			linkLevel.style.display = 'inline';
		} else {
			thisLevel.style.display = 'none';
			otherLevel.style.display = 'inline';
			linkLevel.style.display = 'none';
		}
	};

	window.showUserInfo = function ( sourceVar, targetId ) {
		$( '#' + targetId ).html( mw.config.get( sourceVar ) );
	};
}( mediaWiki, jQuery ) );
