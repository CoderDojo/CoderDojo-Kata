<?php
$wgShowExceptionDetails = true;

$wgLogo = "$wgStylePath/CoderDojoKata/images/logo.png";
$wgFavicon = "$wgStylePath/CoderDojoKata/images/favicon.ico";

/**
 * CUSTOM NAMESPACES
 */
// ORGANISER RESOURCE - 31xx
define("NS_ORGANISER_RESOURCE", 3100);
define("NS_ORGANISER_RESOURCE_TALK", 3101);
$wgExtraNamespaces[NS_ORGANISER_RESOURCE] = "Organiser_Resource";
$wgExtraNamespaces[NS_ORGANISER_RESOURCE_TALK] = "Organiser_Resource_talk";
$wgNamespaceProtection[NS_ORGANISER_RESOURCE] = array( 'editorganiser' );
$wgNamespacesWithSubpages[NS_ORGANISER_RESOURCE] = true;
$wgGroupPermissions['sysop']['editorganiser'] = true;
$wgContentNamespaces[] = 3100;
//
// ORGANISER HOWTO - 311x
define("NS_ORGANISER_HOWTO", 3110);
define("NS_ORGANISER_HOWTO_TALK", 3111);
$wgExtraNamespaces[NS_ORGANISER_HOWTO] = "Organiser_HowTo";
$wgExtraNamespaces[NS_ORGANISER_HOWTO_TALK] = "Organiser_HowTo_talk";
$wgNamespaceProtection[NS_ORGANISER_HOWTO] = array( 'editorganiser' );
$wgNamespacesWithSubpages[NS_ORGANISER_HOWTO] = true;
$wgContentNamespaces[] = 3110;
//
// ORGANISER TIP - 312x
define("NS_ORGANISER_TIP", 3120);
define("NS_ORGANISER_TIP_TALK", 3121);
$wgExtraNamespaces[NS_ORGANISER_TIP] = "Organiser_Tip";
$wgExtraNamespaces[NS_ORGANISER_TIP_TALK] = "Organiser_Tip_talk";
$wgNamespaceProtection[NS_ORGANISER_TIP] = array( 'editorganiser' );
$wgNamespacesWithSubpages[NS_ORGANISER_TIP] = true;
$wgContentNamespaces[] = 3120;
//
// TECHNICAL RESOURCE - 33xx
define("NS_TECHNICAL_RESOURCE", 3300);
define("NS_TECHNICAL_RESOURCE_TALK", 3301);
$wgExtraNamespaces[NS_TECHNICAL_RESOURCE] = "Technical_Resource";
$wgExtraNamespaces[NS_TECHNICAL_RESOURCE_TALK] = "Technical_Resource_talk";
$wgNamespaceProtection[NS_TECHNICAL_RESOURCE] = array( 'edittechnical' );
$wgNamespacesWithSubpages[NS_TECHNICAL_RESOURCE] = true;
$wgGroupPermissions['sysop']['edittechnical'] = true;
$wgContentNamespaces[] = 3300;
//
// MENTOR TUTORIAL - 331x
define("NS_MENTOR_TUTORIAL", 3310);
define("NS_MENTOR_TUTORIAL_TALK", 3311);
$wgExtraNamespaces[NS_MENTOR_TUTORIAL] = "Mentor_Tutorial";
$wgExtraNamespaces[NS_MENTOR_TUTORIAL_TALK] = "Mentor_Tutorial_talk";
$wgNamespaceProtection[NS_MENTOR_TUTORIAL] = array( 'edittechnical' );
$wgNamespacesWithSubpages[NS_MENTOR_TUTORIAL] = true;
$wgContentNamespaces[] = 3310;
//
// MENTOR COURSES - 332x
define("NS_MENTOR_COURSE", 3320);
define("NS_MENTOR_COURSE_TALK", 3321);
$wgExtraNamespaces[NS_MENTOR_COURSE] = "Mentor_Course";
$wgExtraNamespaces[NS_MENTOR_COURSE_TALK] = "Mentor_Course_talk";
$wgNamespaceProtection[NS_MENTOR_COURSE] = array( 'edittechnical' );
$wgNamespacesWithSubpages[NS_MENTOR_COURSE] = true;
$wgContentNamespaces[] = 3320;
//
// NINJA RESOURCE - 35xx
define("NS_NINJA_RESOURCE", 3500);
define("NS_NINJA_RESOURCE_TALK", 3501);
$wgExtraNamespaces[NS_NINJA_RESOURCE] = "Ninja_Resource";
$wgExtraNamespaces[NS_NINJA_RESOURCE_TALK] = "Ninja_Resource_talk";
$wgNamespaceProtection[NS_NINJA_RESOURCE] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_RESOURCE] = true;
$wgGroupPermissions['sysop']['editninja'] = true;
$wgContentNamespaces[] = 3500;
//
// NINJA GAME - 351x
define("NS_NINJA_GAME", 3510);
define("NS_NINJA_GAME_TALK", 3511);
$wgExtraNamespaces[NS_NINJA_GAME] = "Ninja_Game";
$wgExtraNamespaces[NS_NINJA_GAME_TALK] = "Ninja_Game_talk";
$wgNamespaceProtection[NS_NINJA_GAME] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_GAME] = true;
$wgContentNamespaces[] = 3510;
//
// NINJA SUSHI - 352x
define("NS_NINJA_SUSHI", 3520);
define("NS_NINJA_SUSHI_TALK", 3521);
$wgExtraNamespaces[NS_NINJA_SUSHI] = "Ninja_Sushi";
$wgExtraNamespaces[NS_NINJA_SUSHI_TALK] = "Ninja_Sushi_talk";
$wgNamespaceProtection[NS_NINJA_SUSHI] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_SUSHI] = true;
$wgContentNamespaces[] = 3520;
//
// NINJA TUTORIAL - 353x
define("NS_NINJA_TUTORIAL", 3530);
define("NS_NINJA_TUTORIAL_TALK", 3531);
$wgExtraNamespaces[NS_NINJA_TUTORIAL] = "Ninja_Tutorial";
$wgExtraNamespaces[NS_NINJA_TUTORIAL_TALK] = "Ninja_Tutorial_talk";
$wgNamespaceProtection[NS_NINJA_TUTORIAL] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_TUTORIAL] = true;
$wgContentNamespaces[] = 3530;
//
// NINJA PROJECT - 354x
define("NS_NINJA_PROJECT", 3540);
define("NS_NINJA_PROJECT_TALK", 3541);
$wgExtraNamespaces[NS_NINJA_PROJECT] = "Ninja_Project";
$wgExtraNamespaces[NS_NINJA_PROJECT_TALK] = "Ninja_Project_talk";
$wgNamespaceProtection[NS_NINJA_PROJECT] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_PROJECT] = true;
$wgContentNamespaces[] = 3540;
//
// NINJA VIDEO - 355x
define("NS_NINJA_VIDEO", 3550);
define("NS_NINJA_VIDEO_TALK", 3551);
$wgExtraNamespaces[NS_NINJA_VIDEO] = "Ninja_Video";
$wgExtraNamespaces[NS_NINJA_VIDEO_TALK] = "Ninja_Video_talk";
$wgNamespaceProtection[NS_NINJA_VIDEO] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_VIDEO] = true;
$wgContentNamespaces[] = 3550;
//
// NINJA COURSES - 356x
define("NS_NINJA_COURSE", 3560);
define("NS_NINJA_COURSE_TALK", 3561);
$wgExtraNamespaces[NS_NINJA_COURSE] = "Ninja_Course";
$wgExtraNamespaces[NS_NINJA_COURSE_TALK] = "Ninja_Course_talk";
$wgNamespaceProtection[NS_NINJA_COURSE] = array( 'editninja' );
$wgNamespacesWithSubpages[NS_NINJA_COURSE] = true;
$wgContentNamespaces[] = 3560;
//

/**
 * MEDIAWIKI EXTENSIONS
 */
require_once "$IP/extensions/ParserFunctions/ParserFunctions.php";
$wgPFEnableStringFunctions = true;

require_once "$IP/extensions/TemplateData/TemplateData.php";
$wgTemplateDataUseGUI = true;

require_once "$IP/extensions/Babel/Babel.php";

require_once "$IP/extensions/cldr/cldr.php";

require_once "$IP/extensions/CleanChanges/CleanChanges.php";
$wgCCTrailerFilter = true;
$wgCCUserFilter = false;
$wgDefaultUserOptions['usenewrc'] = 1;

require_once "$IP/extensions/LocalisationUpdate/LocalisationUpdate.php";
$wgLocalisationUpdateDirectory = "$IP/cache";

require_once "$IP/extensions/Translate/Translate.php";
$wgGroupPermissions['user']['translate'] = true;
$wgGroupPermissions['user']['translate-messagereview'] = true;
$wgGroupPermissions['user']['translate-groupreview'] = true;
$wgGroupPermissions['user']['translate-import'] = true;
$wgGroupPermissions['sysop']['pagetranslation'] = true;
$wgGroupPermissions['sysop']['translate-manage'] = true;
$wgTranslateDocumentationLanguageCode = 'qqq';
$wgExtraLanguageNames['qqq'] = 'Message documentation'; # No linguistic content. Used for documenting messages

//require_once "$IP/extensions/UniversalLanguageSelector/UniversalLanguageSelector.php";

require_once "$IP/extensions/SemanticMediaWiki/SemanticMediaWiki.php";
enableSemantics($wgDomain);
$wgIncludejQueryMigrate = true;
require_once "$IP/extensions/SemanticForms/SemanticForms.php";
require_once "$IP/extensions/SemanticFormsInputs/SemanticFormsInputs.php";
require_once "$IP/extensions/SemanticCompoundQueries/SemanticCompoundQueries.php";
require_once "$IP/extensions/SemanticDrilldown/SemanticDrilldown.php";
require_once "$IP/extensions/SemanticImageInput/SemanticImageInput.php";
$wgUseInstantCommons  = true;
require_once "$IP/extensions/SemanticInternalObjects/SemanticInternalObjects.php";
require_once "$IP/extensions/DataTransfer/DataTransfer.php";
#$wgGroupPermissions['user']['datatransferimport'] = true;
require_once "$IP/extensions/ExternalData/ExternalData.php";
//require_once "$IP/extensions/SemanticSignup/SemanticSignup.php";
require_once "$IP/extensions/AdminLinks/AdminLinks.php";
#$wgGroupPermissions['my-group']['adminlinks'] = true;
require_once "$IP/extensions/ApprovedRevs/ApprovedRevs.php";
require_once "$IP/extensions/HeaderTabs/HeaderTabs.php";
require_once "$IP/extensions/ReplaceText/ReplaceText.php";
require_once "$IP/extensions/Widgets/Widgets.php";

require_once "$IP/extensions/EmbedVideo/EmbedVideo.php";

/**
 * MEDIAWIKI SKINS
 */
require_once "$IP/skins/Vector/Vector.php";

/**
 * CUSTOM EXTENSIONS
 */
require_once "$IP/extensions/CoderDojoKata/CoderDojoKata.php";

require_once "$IP/extensions/W4G/w4g_rb.php";
$wgW4GRB_Path = "/extensions/W4G";
$wgW4GRB_Settings['ajax-fresh-data']=true;
$wgW4GRB_Settings['allow-unoptimized-queries']=true;
$wgW4GRB_Settings['auto-include']=false;
$wgW4GRB_Settings['fix-spaces']=true;
$wgW4GRB_Settings['max-bars-per-page']=1;
$wgW4GRB_Settings['max-items-per-list']=200;
$wgW4GRB_Settings['default-items-per-list']=30;
$wgW4GRB_Settings['max-lists-per-page']=5;
 $wgW4GRB_Settings['show-voter-names']=false;
 $wgW4GRB_Settings['anonymous-voting-enabled'] = true;

/**
 * CUSTOM SKINS
 */
require_once "$IP/skins/CoderDojoKata/CoderDojoKata.php";
