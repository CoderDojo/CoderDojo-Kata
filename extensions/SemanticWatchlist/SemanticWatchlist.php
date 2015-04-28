<?php

use SWL\HookRegistry;

/**
 * Semantic Watchlist (SWL) - Provides a watchlist and notifier for changes to semantic properties.
 *
 * @license GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $GLOBALS['wgVersion'], '1.19c', '<' ) ) {
	die( '<b>Error:</b> Semantic Watchlist requires MediaWiki 1.19 or above.' );
}

if ( !defined( 'SMW_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="https://semantic-mediawiki.org/">Semantic MediaWiki</a> installed in order to use Semantic Watchlist.' );
}

if ( version_compare( SMW_VERSION, '1.9', '<' ) ) {
	die( '<b>Error:</b> Semantic Watchlist requires Semantic MediaWiki 1.9 or above.' );
}

define( 'SWL_VERSION', '1.0' );

$GLOBALS['wgExtensionCredits']['semantic'][] = array(
	'path' => __FILE__,
	'name' => 'Semantic Watchlist',
	'version' => SWL_VERSION,
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw] for [http://www.wikiworks.com/ WikiWorks]',
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Semantic_Watchlist',
	'descriptionmsg' => 'semanticwatchlist-desc',
	'license-name'   => 'GPL-3.0+'
);

$GLOBALS['egSWLScriptPath'] = $GLOBALS['wgExtensionAssetsPath'] === false ? $GLOBALS['wgScriptPath'] . '/extensions/SemanticWatchlist' : $GLOBALS['wgExtensionAssetsPath'] . '/SemanticWatchlist';

$GLOBALS['wgMessagesDirs']['semantic-watchlist'] = __DIR__ . '/i18n';
$GLOBALS['wgExtensionMessagesFiles']['semantic-watchlist']      = __DIR__ . '/SemanticWatchlist.i18n.php';
$GLOBALS['wgExtensionMessagesFiles']['semantic-watchlist-alias'] = __DIR__ . '/SemanticWatchlist.i18n.alias.php';

$GLOBALS['wgAutoloadClasses']['SWLHooks']					  	= __DIR__ . '/SemanticWatchlist.hooks.php';

$GLOBALS['wgAutoloadClasses']['ApiAddWatchlistGroup']		  	= __DIR__ . '/api/ApiAddWatchlistGroup.php';
$GLOBALS['wgAutoloadClasses']['ApiDeleteWatchlistGroup']		= __DIR__ . '/api/ApiDeleteWatchlistGroup.php';
$GLOBALS['wgAutoloadClasses']['ApiEditWatchlistGroup']		 	= __DIR__ . '/api/ApiEditWatchlistGroup.php';
$GLOBALS['wgAutoloadClasses']['ApiQuerySemanticWatchlist']	 	= __DIR__ . '/api/ApiQuerySemanticWatchlist.php';

$GLOBALS['wgAutoloadClasses']['SWLChangeSet']		  			= __DIR__ . '/includes/SWL_ChangeSet.php';
$GLOBALS['wgAutoloadClasses']['SWLEdit']		  				= __DIR__ . '/includes/SWL_Edit.php';
$GLOBALS['wgAutoloadClasses']['SWLEmailer']		  			    = __DIR__ . '/includes/SWL_Emailer.php';
$GLOBALS['wgAutoloadClasses']['SWLGroup']		  				= __DIR__ . '/includes/SWL_Group.php';
$GLOBALS['wgAutoloadClasses']['SWLGroups']		  				= __DIR__ . '/includes/SWL_Groups.php';
$GLOBALS['wgAutoloadClasses']['SWLPropertyChange']		  		= __DIR__ . '/includes/SWL_PropertyChange.php';
$GLOBALS['wgAutoloadClasses']['SWLPropertyChanges']		  	    = __DIR__ . '/includes/SWL_PropertyChanges.php';
$GLOBALS['wgAutoloadClasses']['SWLCustomTexts']		  		    = __DIR__ . '/includes/SWL_CustomTexts.php';

$GLOBALS['wgAutoloadClasses']['SpecialSemanticWatchlist']	  	= __DIR__ . '/specials/SpecialSemanticWatchlist.php';
$GLOBALS['wgAutoloadClasses']['SpecialWatchlistConditions']	    = __DIR__ . '/specials/SpecialWatchlistConditions.php';

$GLOBALS['wgSpecialPages']['SemanticWatchlist'] = 'SpecialSemanticWatchlist';
$GLOBALS['wgSpecialPageGroups']['SemanticWatchlist'] = 'changes';

$GLOBALS['wgSpecialPages']['WatchlistConditions'] = 'SpecialWatchlistConditions';
$GLOBALS['wgSpecialPageGroups']['WatchlistConditions'] = 'changes';

$GLOBALS['wgAPIModules']['addswlgroup'] = 'ApiAddWatchlistGroup';
$GLOBALS['wgAPIModules']['deleteswlgroup'] = 'ApiDeleteWatchlistGroup';
$GLOBALS['wgAPIModules']['editswlgroup'] = 'ApiEditWatchlistGroup';
$GLOBALS['wgAPIListModules']['semanticwatchlist'] = 'ApiQuerySemanticWatchlist';

$moduleTemplate = array(
	'localBasePath' => __DIR__,
	'remoteBasePath' => $GLOBALS['egSWLScriptPath']
);

$GLOBALS['wgResourceModules']['ext.swl.watchlist'] = $moduleTemplate + array(
	'styles' => array( 'specials/ext.swl.watchlist.css' ),
	'scripts' => array(),
	'dependencies' => array(),
	'messages' => array()
);

$GLOBALS['wgResourceModules']['ext.swl.watchlistconditions'] = $moduleTemplate + array(
	'styles' => array( 'specials/ext.swl.watchlistconditions.css' ),
	'scripts' => array(
		'specials/jquery.watchlistcondition.js',
		'specials/ext.swl.watchlistconditions.js'
	),
	'dependencies' => array(),
	'messages' => array(
		'swl-group-name',
		'swl-group-legend',
		'swl-group-properties',
		'swl-properties-list',
		'swl-group-remove-property',
		'swl-group-add-property',
		'swl-group-page-selection',
		'swl-group-save',
		'swl-group-saved',
		'swl-group-saving',
		'swl-group-remove',
		'swl-group-category',
		'swl-group-namespace',
		'swl-group-concept',
		'swl-group-confirm-remove',
		'swl-custom-legend',
		'swl-custom-remove-property',
		'swl-custom-text-add',
		'swl-custom-input',
	)
);

require_once 'SemanticWatchlist.settings.php';

$GLOBALS['wgAvailableRights'][] = 'semanticwatch';
$GLOBALS['wgAvailableRights'][] = 'semanticwatchgroups';

$GLOBALS['egSwlSqlDatabaseSchemaPath'] = __DIR__ . '/src/swl-table-schema.sql';

/**
 * @codeCoverageIgnore
 *
 * @since 1.0
 */
call_user_func( function () {

	$GLOBALS['wgExtensionFunctions'][] = function() {

		$configuration = array(
			'egSWLEnableTopLink'         => $GLOBALS['egSWLEnableTopLink'],
			'egSWLEnableEmailNotify'     => $GLOBALS['egSWLEnableEmailNotify'],
			'egSwlSqlDatabaseSchemaPath' => $GLOBALS['egSwlSqlDatabaseSchemaPath'],
			'wgLang' => $GLOBALS['wgLang']
		);

		$hookRegistry = new HookRegistry(
			$configuration
		);

		$hookRegistry->register( $GLOBALS['wgHooks'] );
	};

} );
