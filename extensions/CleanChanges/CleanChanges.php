<?php
if ( !defined( 'MEDIAWIKI' ) ) die();
/**
 * An extension to show a nice compact changes list and few extra filters for
 * Special:RecentChanges.php
 *
 * @file
 * @ingroup Extensions
 *
 * @author Niklas Laxström
 * @copyright Copyright © 2008-2012, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/* Set up messages and includes */
$dir = __DIR__;
$wgMessagesDirs['CleanChanges'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['CleanChanges'] = "$dir/CleanChanges.i18n.php";
$wgAutoloadClasses['NCL'] =  "$dir/CleanChanges_body.php";

/* Hook into code */
$wgHooks['FetchChangesList'][] = 'NCL::hook';
$wgHooks['MakeGlobalVariablesScript'][] = 'NCL::addScriptVariables';

/* Extension information */
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Clean Changes',
	'version' => '2014-03-26',
	'author' => 'Niklas Laxström',
	'descriptionmsg' => 'cleanchanges-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:CleanChanges',
);

$wgCCUserFilter = true;
$wgCCTrailerFilter = false;

$wgExtensionFunctions[] = 'ccSetupFilters';
$wgAutoloadClasses['CCFilters'] = "$dir/Filters.php";

function ccSetupFilters() {
	global $wgCCUserFilter, $wgCCTrailerFilter, $wgHooks;

	if ( $wgCCUserFilter ) {
		$wgHooks['SpecialRecentChangesQuery'][] = 'CCFilters::user';
		$wgHooks['SpecialRecentChangesPanel'][] = 'CCFilters::userForm';
	}
	if ( $wgCCTrailerFilter ) {
		$wgHooks['SpecialRecentChangesQuery'][] = 'CCFilters::trailer';
		$wgHooks['SpecialRecentChangesPanel'][] = 'CCFilters::trailerForm';
	}
}

$resourcePaths = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'CleanChanges'
);

// Client-side resource modules
$wgResourceModules['ext.cleanchanges'] = array(
	'scripts' => 'cleanchanges.js',
) + $resourcePaths;
