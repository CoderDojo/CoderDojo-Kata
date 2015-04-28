<?php

/**
 * File defining the settings for the Semantic Watchlist extension.
 * More info can be found at http://www.mediawiki.org/wiki/Extension:Semantic_Watchlist#Settings
 *
 *                          NOTICE:
 * Changing one of these settings can be done by copying or cutting it,
 * and placing it in LocalSettings.php, AFTER the inclusion of this extension.
 *
 * @file SemanticWatchlist.settings.php
 * @ingroup SemanticWatchlist
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

# Users that can use the semantic watchlist.
$GLOBALS['wgGroupPermissions']['*'            ]['semanticwatch'] = false;
$GLOBALS['wgGroupPermissions']['user'         ]['semanticwatch'] = true;
$GLOBALS['wgGroupPermissions']['autoconfirmed']['semanticwatch'] = true;
$GLOBALS['wgGroupPermissions']['bot'          ]['semanticwatch'] = false;
$GLOBALS['wgGroupPermissions']['sysop'        ]['semanticwatch'] = true;

# Users that can modify the watchlist groups via Special:WatchlistConditions
$GLOBALS['wgGroupPermissions']['*'            ]['semanticwatchgroups'] = false;
$GLOBALS['wgGroupPermissions']['user'         ]['semanticwatchgroups'] = false;
$GLOBALS['wgGroupPermissions']['autoconfirmed']['semanticwatchgroups'] = false;
$GLOBALS['wgGroupPermissions']['bot'          ]['semanticwatchgroups'] = false;
$GLOBALS['wgGroupPermissions']['sysop'        ]['semanticwatchgroups'] = true;

# Enable email notification or not?
$GLOBALS['egSWLEnableEmailNotify'] = true;

# Send an email for every change (as opposed to a "something changed email" for the first $GLOBALS['egSWLMaxMails'] changes)?
$GLOBALS['egSWLMailPerChange'] = true;

# The maximum amount of generic emails to send about changes until the user actually checks his semantic watchlist.
$GLOBALS['egSWLMaxMails'] = 1;

# The default value for the user preference to send email notifications.
$GLOBALS['wgDefaultUserOptions']['swl_email'] = true;

# The default value for the user preference to display a top link to the semantic watchlist.
$GLOBALS['wgDefaultUserOptions']['swl_watchlisttoplink'] = true;

# Enable displaying a top link to the semantic watchlist?
$GLOBALS['egSWLEnableTopLink'] = true;

# Send email to editor
$GLOBALS['egSWLEnableSelfNotify'] = false;
