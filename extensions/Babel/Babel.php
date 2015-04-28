<?php
/**
 * Babel Extension
 *
 * Adds a parser function to allow automated generation of a babel userbox
 * column with the ability to include custom templates.
 *
 * @file
 * @ingroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:Babel
 *
 * @author Robert Leverington <robert@rhl.me.uk>
 * @copyright Copyright © 2008 - 2011 Robert Leverington.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Invalid entry point.' );
}

$GLOBALS['wgExtensionCredits']['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'Babel',
	'version' => '[https://www.mediawiki.org/wiki/MLEB MLEB 2014.09]',
	'author' => 'Robert Leverington',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Babel',
	'descriptionmsg' => 'babel-desc',
);

$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'BabelStatic::onParserFirstCallInit';
$GLOBALS['wgHooks']['AbortNewAccount'][] = 'BabelAutoCreate::onAbortNewAccount';

$dir = __DIR__ . '/';

$GLOBALS['wgMessagesDirs']['Babel'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['Babel'] = $dir . 'Babel.i18n.php';
$GLOBALS['wgExtensionMessagesFiles']['BabelMagic'] = $dir . 'Babel.i18n.magic.php';

$GLOBALS['wgAutoloadClasses']['Babel'] = $dir . 'Babel.class.php';
$GLOBALS['wgAutoloadClasses']['BabelLanguageCodes'] = $dir . 'BabelLanguageCodes.class.php';
$GLOBALS['wgAutoloadClasses']['BabelStatic'] = $dir . 'BabelStatic.class.php';
$GLOBALS['wgAutoloadClasses']['BabelAutoCreate'] = $dir . 'BabelAutoCreate.class.php';

$GLOBALS['wgResourceModules']['ext.babel'] = array(
	'styles' => 'resources/ext.babel.css',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Babel',
);

// Configuration setttings.
// Language names and codes constant database files, the defaults should suffice.
$GLOBALS['wgBabelLanguageCodesCdb'] = $dir . 'codes.cdb';
$GLOBALS['wgBabelLanguageNamesCdb'] = $dir . 'names.cdb';
// Array of possible levels, and their category name - variables: %code% %wikiname% %nativename%
// Set to false to disable categories for a particular level.
// Alphabetical levels should be in upper case.
$GLOBALS['wgBabelCategoryNames'] = array(
	'0' => '%code%-0',
	'1' => '%code%-1',
	'2' => '%code%-2',
	'3' => '%code%-3',
	'4' => '%code%-4',
	'5' => '%code%-5',
	'N' => '%code%-N'
);
// Category name for the main (non-level) category of each language.
// Set to false to disable main category.
$GLOBALS['wgBabelMainCategory'] = '%code%';
// Default level.
$GLOBALS['wgBabelDefaultLevel'] = 'N';
// Use the viewing user's language for babel box header's and footer's
// May fragment parser cache, but otherwise shouldn't cause problems
$GLOBALS['wgBabelUseUserLanguage'] = false;
// A boolean (true or false) indicating whether ISO 639-3 codes should be
// preferred over ISO 639-1 codes.
$GLOBALS['wgBabelPreferISO639_3'] = false; // Not yet used.

/* Other settings, to be made in-wiki:
MediaWiki:Babel-template
    The name format of template names used in the babel extension.
MediaWiki:Babel-portal
    The name format of the portal link for each language.
*/
