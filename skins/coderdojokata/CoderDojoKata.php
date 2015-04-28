<?php
/**
 * CoderDojo Kata skin
 *
 * @file
 * @ingroup Skins
 * @author CoderDojo Foundation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
 
if ( !defined( 'MEDIAWIKI' ) )
{
   die( 'This is an extension to the MediaWiki package and cannot be run standalone.' );
}
 
$wgExtensionCredits['skin'][] = array(
	'path' => __FILE__,
	'name' => 'CoderDojo Kata', 
	'namemsg' => 'skinname-coderdojokata',
	'version' => '1.0',
	'url' => 'https://www.mediawiki.org/wiki/Skin:CoderDojoKata',
	'author' => 'CoderDojo Foundation, Lorenzo Cipriani, Claudio Bertoli, Andrea Paolucci, Pier Paolo Manca',
	'descriptionmsg' => 'coderdojokata-desc',
	'license' => 'GPL-2.0+',
);

$wgValidSkinNames['coderdojokata'] = 'CoderDojoKata';
 
$wgAutoloadClasses['SkinCoderDojoKata'] = __DIR__ . '/CoderDojoKata.skin.php';
$wgMessagesDirs['CoderDojoKata'] = __DIR__ . '/i18n';

$wgResourceModules['skins.coderdojokata'] = array(
	'scripts' => array(
		'CoderDojoKata/resources/collapsibleTabs.js',
		'CoderDojoKata/resources/vector.js',
		'CoderDojoKata/resources/bootstrap.min.js',
		'CoderDojoKata/resources/alpha-filter.js',
	),
	'position' => 'top',
	'dependencies' => array(
		'jquery.accessKeyLabel',
		'jquery.client',
		'jquery.mwExtension',
		'mediawiki.page.ready',
		'jquery.checkboxShiftClick',
		'jquery.makeCollapsible',
		'jquery.throttle-debounce',
		'jquery.tabIndex'
	),
	'styles' => array(
		'CoderDojoKata/resources/bootstrap.min.css' => array( 'media' => 'screen' ),
		'CoderDojoKata/resources/style.css' => array( 'media' => 'screen' ),
		'CoderDojoKata/resources/theme.css' => array( 'media' => 'screen' ),
		'CoderDojoKata/resources/screen.css' => array( 'media' => 'screen' )
	),
	'remoteBasePath' => &$GLOBALS['wgStylePath'],
	'localBasePath' => &$GLOBALS['wgStyleDirectory'],
);

?>
