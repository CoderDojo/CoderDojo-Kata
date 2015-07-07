<?php
/**
 * MediaWiki EmbedPDF extension
 *
 * @file
 * @ingroup Extensions
 * @version 0.2
 * @author Dmitry Shurupov
 * @link https://www.mediawiki.org/wiki/Extension:EmbedPDF Documentation
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is not a valid entry point to MediaWiki.' );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'EmbedPDF',
	'author' => 'Dmitry Shurupov',
	'version' => '0.2',
	'url' => 'https://www.mediawiki.org/wiki/Extension:EmbedPDF',
	'description' => 'Allows to embed .pdf documents on a wiki page.',
);

$wgHooks['ParserFirstCallInit'][] = 'registerEmbedPDFHandler';
// Register the <pdf> tag with the parser
function registerEmbedPDFHandler( &$parser ) {
	$parser->setHook( 'pdf', 'embedPDFHandler' );
	return true;
}

function makeHTMLforPDF( $path, $argv ) {
	// Use user-supplied values for the width and height parameters, if
	// they are set and also do some very basic input validation
	if ( empty( $argv['width'] ) ) {
		$width = '1000';
	} else {
		$width = ( is_numeric( $argv['width'] ) ? $argv['width'] : 1000 );
	}

	if ( empty( $argv['height'] ) ) {
		$height = '700';
	} else {
		$height = ( is_numeric( $argv['height'] ) ? $argv['height'] : 700 );
	}

	return '<object data="' . $path . '" width="' . $width . '" height="' .
		$height . '" type="application/pdf"></object>';
}

function embedPDFHandler( $input, $argv ) {
	if ( !$input ) {
		return '<span style="color: red;">Error: empty param in &lt;pdf&gt;!</span>';
	}

	if ( preg_match( '/^[^\/]+\.pdf$/i', $input ) ) {
		$img = wfFindFile( $input );
		if ( is_object( $img ) ) {
			return makeHTMLforPDF( $img->getURL(), $argv );
		}
	}

	if ( preg_match( '/^http\:\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@\?\^\=\%\&:\/\~\+\#]*[\w\-\@\?\^\=\%\&\/\~\+\#])?\.pdf$/i', $input ) ) {
		return makeHTMLforPDF( $input, $argv );
	} else {
		return '<span style="color: red;">Error: bad URI in &lt;pdf&gt;!</span>';
	}
}
