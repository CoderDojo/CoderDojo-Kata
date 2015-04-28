<?php
/**
 * Replace Text - a MediaWiki extension that provides a special page to
 * allow administrators to do a global string find-and-replace on all the
 * content pages of a wiki.
 *
 * http://www.mediawiki.org/wiki/Extension:Replace_Text
 *
 * The special page created is 'Special:ReplaceText', and it provides
 * a form to do a global search-and-replace, with the changes to every
 * page showing up as a wiki edit, with the administrator who performed
 * the replacement as the user, and an edit summary that looks like
 * "Text replace: 'search string' * to 'replacement string'".
 *
 * If the replacement string is blank, or is already found in the wiki,
 * the page provides a warning prompt to the user before doing the
 * replacement, since it is not easily reversible.
 */

if ( !defined( 'MEDIAWIKI' ) ) { die(); }

define( 'REPLACE_TEXT_VERSION', '1.0' );

// credits
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Replace Text',
	'version' => REPLACE_TEXT_VERSION,
	'author' => array( 'Yaron Koren', 'Niklas Laxström' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Replace_Text',
	'descriptionmsg'  => 'replacetext-desc',
);

$rtgIP = __DIR__ . '/';
$wgMessagesDirs['ReplaceText'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['ReplaceText'] = $rtgIP . 'ReplaceText.i18n.php';
$wgExtensionMessagesFiles['ReplaceTextAlias'] = $rtgIP . 'ReplaceText.alias.php';
$wgJobClasses['replaceText'] = 'ReplaceTextJob';

// This extension uses its own permission type, 'replacetext'
$wgAvailableRights[] = 'replacetext';
$wgGroupPermissions['sysop']['replacetext'] = true;

$wgHooks['AdminLinks'][] = 'rtAddToAdminLinks';

$wgSpecialPages['ReplaceText'] = 'ReplaceText';
$wgSpecialPageGroups['ReplaceText'] = 'wiki';
$wgAutoloadClasses['ReplaceText'] = $rtgIP . 'SpecialReplaceText.php';
$wgAutoloadClasses['ReplaceTextJob'] = $rtgIP . 'ReplaceTextJob.php';

/**
 * This function should really go into a "ReplaceText_body.php" file.
 *
 * Handler for 'AdminLinks' hook in the AdminLinks extension
 *
 * @param $admin_links_tree ALTree
 * @return bool
 */
function rtAddToAdminLinks( ALTree &$admin_links_tree ) {
	$general_section = $admin_links_tree->getSection( wfMessage( 'adminlinks_general' )->text() );
	$extensions_row = $general_section->getRow( 'extensions' );

	if ( is_null( $extensions_row ) ) {
		$extensions_row = new ALRow( 'extensions' );
		$general_section->addRow( $extensions_row );
	}

	$extensions_row->addItem( ALItem::newFromSpecialPage( 'ReplaceText' ) );

	return true;
}
