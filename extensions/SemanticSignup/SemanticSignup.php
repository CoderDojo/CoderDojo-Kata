<?php
/**
 * Initialization file for the Semantic Signup extension.
 * This documenation group collects source code files belonging to SemanticSignup.
 *
 * Documentation: https://www.mediawiki.org/wiki/Extension:SemanticSignup
 * Support: https://www.mediawiki.org/wiki/Extension talk:SemanticSignup
 * Source code: https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/SemanticSignup.git
 *
 * @file SemanticSignup.php
 * @ingroup Extensions
 * @defgroup SemanticSignup SemanticSignup
 * @ingroup SemanticSignup
 *
 * @licence GNU GPL v3+
 * @link https://www.mediawiki.org/wiki/Extension:SemanticSignup
 * @author Jeroen De Dauw <jeroendedauw@gmail.com>
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.17', '<' ) ) {
	die( '<b>Error:</b> SemanticSignup requires MediaWiki 1.17 or above.' );
}

// Show a warning if Semantic MediaWiki is not loaded.
if ( !defined( 'SMW_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="http://semantic-mediawiki.org/wiki/Semantic_MediaWiki">Semantic MediaWiki</a> installed in order to use SemanticSignup.' );
}

if ( version_compare( SMW_VERSION, '1.5 alpha', '<' ) ) {
	die( '<b>Error:</b> Semantic Signup requires Semantic MediaWiki 1.5 or above.' );
}

if ( !defined( 'SF_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="http://semantic-mediawiki.org/wiki/Semantic_Forms">Semantic Forms</a> installed in order to use SemanticSignup.' );
}

define( 'SemanticSignup_VERSION', '0.5.0' );

$wgExtensionCredits[defined( 'SEMANTIC_EXTENSION_TYPE' ) ? 'semantic' : 'specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SemanticSignup',
	'version' => SemanticSignup_VERSION,
	'author' => array(
		'Serg Kutny',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		'[https://www.mediawiki.org/wiki/User:Nischayn22 Nischay Nahata]',
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:SemanticSignup',
	'descriptionmsg' => 'ses-desc'
);

$wgMessagesDirs['SemanticSignup'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SemanticSignup'] = __DIR__ . '/SemanticSignup.i18n.php';
$wgExtensionMessagesFiles['SemanticSignupMagic'] = __DIR__ . '/SemanticSignup.i18n.magic.php';
$wgExtensionMessagesFiles['SemanticSignupAlias'] = __DIR__ . '/SemanticSignup.i18n.aliases.php';

$wgAutoloadClasses['SemanticSignupSettings'] = __DIR__ . '/SemanticSignup.settings.php';
$wgAutoloadClasses['SemanticSignupHooks'] = __DIR__ . '/SemanticSignup.hooks.php';
$wgAutoloadClasses['SpecialSemanticSignup'] = __DIR__ . '/includes/SpecialSemanticSignup.php';
$wgAutoloadClasses['SES_DataChecker'] = __DIR__ . '/includes/SES_DataChecker.php';
$wgAutoloadClasses['SES_UserAccountDataChecker'] = __DIR__ . '/includes/SES_UserAccountDataChecker.php';
$wgAutoloadClasses['SES_SignupFields'] = __DIR__ . '/includes/SES_SignupFields.php';
$wgAutoloadClasses['CreateUserFieldsTemplate'] = __DIR__ . '/includes/CreateUserFieldsTemplate.php';

$wgSpecialPages['SemanticSignup'] = 'SpecialSemanticSignup';

$egSemanticSignupSettings = array();

$wgHooks['UserCreateForm'][] = 'SemanticSignupHooks::onUserCreateForm';
$wgHooks['ParserFirstCallInit'][] = 'SemanticSignupHooks::onParserFirstCallInit';
