<?php
/**
 * A special page holding special convenience links for sysops
 *
 * @author Yaron Koren
 */

if ( !defined( 'MEDIAWIKI' ) ) die();

// credits
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Admin Links',
	'version' => '0.2',
	'author' => 'Yaron Koren',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Admin_Links',
	'descriptionmsg' => 'adminlinks-desc',
);

$wgAdminLinksIP = __DIR__ . '/';
$wgMessagesDirs['AdminLinks'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['AdminLinks'] = $wgAdminLinksIP . 'AdminLinks.i18n.php';
$wgExtensionMessagesFiles['AdminLinksAlias'] = $wgAdminLinksIP . 'AdminLinks.alias.php';
$wgSpecialPages['AdminLinks'] = 'AdminLinks';
$wgSpecialPageGroups['AdminLinks'] = 'users';
$wgHooks['PersonalUrls'][] = 'AdminLinks::addURLToUserLinks';
$wgAvailableRights[] = 'adminlinks';
// by default, sysops see the link to this page
$wgGroupPermissions['sysop']['adminlinks'] = true;
$wgAutoloadClasses['AdminLinks']
	= $wgAutoloadClasses['ALTree']
	= $wgAutoloadClasses['ALSection']
	= $wgAutoloadClasses['ALRow']
	= $wgAutoloadClasses['ALItem']
	= $wgAdminLinksIP . 'AdminLinks_body.php';
