<?php
/**
 * Hooks for UniversalLanguageSelector extension.
 *
 * Copyright (C) 2012 Alolita Sharma, Amir Aharoni, Arun Ganesh, Brandon Harris,
 * Niklas Laxström, Pau Giner, Santhosh Thottingal, Siebrand Mazeland and other
 * contributors. See CREDITS for a list.
 *
 * UniversalLanguageSelector is dual licensed GPLv2 or later and MIT. You don't
 * have to do anything special to choose one license or the other and you don't
 * have to notify anyone which license you are using. You are free to use
 * UniversalLanguageSelector in commercial projects as long as the copyright
 * header is left intact. See files GPL-LICENSE and MIT-LICENSE for details.
 *
 * @file
 * @ingroup Extensions
 * @licence GNU General Public Licence 2.0 or later
 * @licence MIT License
 */

class UniversalLanguageSelectorHooks {
	/**
	 * Whether ULS user toolbar (language selection and settings) is enabled.
	 *
	 * @param User $user
	 * @return bool
	 */
	public static function isToolbarEnabled( $user ) {
		global $wgULSEnable, $wgULSEnableAnon;

		if ( !$wgULSEnable ) {
			return false;
		}
		if ( !$wgULSEnableAnon && $user->isAnon() ) {
			return false;
		}

		return true;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @return bool
	 * Hook: BeforePageDisplay
	 */
	public static function addModules( $out, $skin ) {
		global $wgULSCompactLinks, $wgULSPosition, $wgULSGeoService, $wgULSEventLogging,
			$wgInterwikiMagic, $wgHideInterlanguageLinks;

		// Load the style for users without JS, to hide the useless links
		$out->addModuleStyles( 'ext.uls.nojs' );

		// If EventLogging integration is enabled, load the schema module
		// and the event logging functions module
		if ( $wgULSEventLogging ) {
			$out->addModules( array(
				'schema.UniversalLanguageSelector',
				'ext.uls.eventlogger',
			) );
		}

		// If the extension is enabled, basic features (API, language data) available.
		$out->addModules( 'ext.uls.init' );

		// If compact ULS beta feature is enabled and is actually functional
		// (see onGetBetaFeaturePreferences)
		if ( $wgULSCompactLinks &&
			$wgInterwikiMagic === true &&
			$wgHideInterlanguageLinks === false &&
			class_exists( 'BetaFeatures' ) &&
			BetaFeatures::isFeatureEnabled( $out->getUser(), 'uls-compact-links' )
		) {
			$out->addModules( 'ext.uls.compactlinks' );
		}

		if ( is_string( $wgULSGeoService ) ) {
			$out->addModules( 'ext.uls.geoclient' );
		}

		if ( self::isToolbarEnabled( $out->getUser() ) ) {
			// Enable UI language selection for the user.
			$out->addModules( 'ext.uls.interface' );
		}

		if ( $wgULSPosition === 'personal' ) {
			$out->addModules( 'ext.uls.pt' );
		} else {
			$out->addModules( 'ext.uls.interlanguage' );
		}

		return true;
	}

	/**
	 * @param $testModules array of javascript testing modules. 'qunit' is fed
	 * using tests/qunit/QUnitTestResources.php.
	 * @param ResourceLoader $resourceLoader
	 * @return bool
	 * Hook: ResourceLoaderTestModules
	 */
	public static function addTestModules( array &$testModules, ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['ext.uls.tests'] = array(
			'scripts' => array( 'tests/qunit/ext.uls.tests.js' ),
			'dependencies' => array( 'jquery.uls', 'ext.uls.preferences' ),
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'UniversalLanguageSelector',
		);

		return true;
	}

	/**
	 * Add some tabs for navigation for users who do not use Ajax interface.
	 * Hook: PersonalUrls
	 */
	static function addPersonalBarTrigger( array &$personal_urls, &$title ) {
		global $wgULSPosition;

		if ( $wgULSPosition !== 'personal' ) {
			return true;
		}

		$context = RequestContext::getMain();
		if ( !self::isToolbarEnabled( $context->getUser() ) ) {
			return true;
		}

		// The element id will be 'pt-uls'
		$lang = $context->getLanguage();
		$personal_urls = array(
			'uls' => array(
				'text' => $lang->fetchLanguageName( $lang->getCode() ),
				'href' => '#',
				'class' => 'uls-trigger autonym',
				'active' => true
			)
		) + $personal_urls;

		return true;
	}

	protected static function isSupportedLanguage( $language ) {
		wfProfileIn( __METHOD__ );
		$supported = Language::fetchLanguageNames( null, 'mwfile' ); // since 1.20
		wfProfileOut( __METHOD__ );

		return isset( $supported[$language] );
	}

	/**
	 * @param array $preferred
	 * @return string
	 */
	protected static function getDefaultLanguage( array $preferred ) {
		wfProfileIn( __METHOD__ );
		$supported = Language::fetchLanguageNames( null, 'mwfile' ); // since 1.20

		// look for a language that is acceptable to the client
		// and known to the wiki.
		foreach ( $preferred as $code => $weight ) {
			if ( isset( $supported[$code] ) ) {
				wfProfileOut( __METHOD__ );

				return $code;
			}
		}

		// Some browsers might only send codes like de-de.
		// Try with bare code.
		foreach ( $preferred as $code => $weight ) {
			$parts = explode( '-', $code, 2 );
			$code = $parts[0];
			if ( isset( $supported[$code] ) ) {
				wfProfileOut( __METHOD__ );

				return $code;
			}
		}

		wfProfileOut( __METHOD__ );

		return '';
	}

	/**
	 * Hook to UserGetLanguageObject
	 * @param User $user
	 * @param string $code
	 * @param RequestContext $context
	 * @return bool
	 */
	public static function getLanguage( User $user, &$code, RequestContext $context ) {
		global $wgULSAnonCanChangeLanguage, $wgULSLanguageDetection;

		if ( !self::isToolbarEnabled( $user ) ) {
			return true;
		}

		$request = $context->getRequest();

		$languageToSave = $request->getVal( 'setlang' );
		if ( $request->getVal( 'uselang' ) && !$languageToSave ) {
			// uselang can be used for temporary override of language preference
			// when setlang is not provided
			return true;
		}

		// Registered users - simple
		if ( !$user->isAnon() ) {
			// Language change
			if ( self::isSupportedLanguage( $languageToSave ) ) {
				$user->setOption( 'language', $languageToSave );
				$user->saveSettings();
				// Apply immediately
				$code = $languageToSave;
			}

			// Otherwise just use what is stored in preferences
			return true;
		}

		// Logged out users - less simple
		if ( !$wgULSAnonCanChangeLanguage ) {
			return true;
		}

		// Language change
		if ( self::isSupportedLanguage( $languageToSave ) ) {
			$request->response()->setcookie( 'language', $languageToSave );
			$code = $languageToSave;

			return true;
		}

		// Try cookie
		$languageToUse = $request->getCookie( 'language' );
		if ( self::isSupportedLanguage( $languageToUse ) ) {
			$code = $languageToUse;

			return true;
		}

		// As last resort, try Accept-Language headers if allowed
		if ( $wgULSLanguageDetection ) {
			$preferred = $request->getAcceptLang();
			$default = self::getDefaultLanguage( $preferred );
			if ( $default !== '' ) {
				$code = $default;
			}
		}

		// Fall back to other hooks or content language
		return true;
	}

	/**
	 * Hook: ResourceLoaderGetConfigVars
	 * @param array $vars
	 * @return bool
	 */
	public static function addConfig( &$vars ) {
		global $wgULSGeoService,
			$wgULSIMEEnabled, $wgULSWebfontsEnabled,
			$wgULSPosition, $wgULSNoWebfontsSelectors,
			$wgULSAnonCanChangeLanguage,
			$wgULSEventLogging,
			$wgULSImeSelectors, $wgULSNoImeSelectors,
			$wgULSFontRepositoryBasePath,
			$wgExtensionAssetsPath;

		// Place constant stuff here (not depending on request context)
		if ( is_string( $wgULSGeoService ) ) {
			$vars['wgULSGeoService'] = $wgULSGeoService;
		}

		$vars['wgULSIMEEnabled'] = $wgULSIMEEnabled;
		$vars['wgULSWebfontsEnabled'] = $wgULSWebfontsEnabled;
		$vars['wgULSPosition'] = $wgULSPosition;
		$vars['wgULSAnonCanChangeLanguage'] = $wgULSAnonCanChangeLanguage;
		$vars['wgULSEventLogging'] = $wgULSEventLogging;
		$vars['wgULSImeSelectors'] = $wgULSImeSelectors;
		$vars['wgULSNoImeSelectors'] = $wgULSNoImeSelectors;
		$vars['wgULSNoWebfontsSelectors'] = $wgULSNoWebfontsSelectors;

		if ( is_string( $wgULSFontRepositoryBasePath ) ) {
			$vars['wgULSFontRepositoryBasePath'] = $wgULSFontRepositoryBasePath;
		} else {
			$vars['wgULSFontRepositoryBasePath'] = $wgExtensionAssetsPath .
				'/UniversalLanguageSelector/data/fontrepo/fonts/';
		}

		return true;
	}

	/**
	 * Hook: MakeGlobalVariablesScript
	 * @param array $vars
	 * @param OutputPage $out
	 * @return bool
	 */
	public static function addVariables( &$vars, OutputPage $out ) {
		// Place request context dependent stuff here
		$vars['wgULSAcceptLanguageList'] = array_keys( $out->getRequest()->getAcceptLang() );

		// An optimization to avoid loading all of uls.data just to get the autonym
		$lang = $out->getLanguage();
		$vars['wgULSCurrentAutonym'] = $lang->fetchLanguageName( $lang->getCode() );

		return true;
	}

	public static function onGetPreferences( $user, &$preferences ) {
		$preferences['uls-preferences'] = array(
			'type' => 'api',
		);

		// A link shown for accessing ULS language settings from preferences screen
		$preferences['languagesettings'] = array(
			'type' => 'info',
			'raw' => true,
			'section' => 'personal/i18n',
			'default' => "<a id='uls-preferences-link' href='#'></a>",
			// The above link will have text set from javascript. Just to avoid
			// showing the link when javascript is disabled.
		);

		return true;
	}

	public static function onGetBetaFeaturePreferences( $user, &$prefs ) {
		global $wgExtensionAssetsPath, $wgULSCompactLinks,
			$wgHideInterlanguageLinks, $wgInterwikiMagic;

		if ( $wgULSCompactLinks &&
			$wgInterwikiMagic === true &&
			$wgHideInterlanguageLinks === false
		) {
			$imagesDir = "$wgExtensionAssetsPath/UniversalLanguageSelector/resources/images";
			$prefs['uls-compact-links'] = array(
				'label-message' => 'uls-betafeature-label',
				'desc-message' => 'uls-betafeature-desc',
				'screenshot' => array(
					'ltr' => "$imagesDir/compact-links-ltr.png",
					'rtl' => "$imagesDir/compact-links-rtl.png",
				),
				'info-link' =>
					'https://www.mediawiki.org/wiki/Universal_Language_Selector/Design/Interlanguage_links',
				'discussion-link' =>
					'https://www.mediawiki.org/wiki/Talk:Universal_Language_Selector/Design/Interlanguage_links',
			);
		}
	}

	/**
	 * Hook: SkinTemplateOutputPageBeforeExec
	 * @param Skin $skin
	 * @param QuickTemplate $template
	 * @return bool
	 */
	public static function onSkinTemplateOutputPageBeforeExec( Skin &$skin,
		QuickTemplate &$template
	) {
		global $wgULSPosition;

		if ( $wgULSPosition !== 'interlanguage' ) {
			return true;
		}

		if ( !self::isToolbarEnabled( $skin->getUser() ) ) {
			return true;
		}

		// A dummy link, just to make sure that the section appears
		$template->data['language_urls'][] = array(
			'href' => '#',
			'text' => '',
			'class' => 'uls-p-lang-dummy',
		);

		return true;
	}

	/**
	 * Add basic webfonts support to the mobile interface (via MobileFrontend extension)
	 * Hook: EnterMobileMode
	 * @param MobileContext $context
	 * @return bool
	 */
	public static function onEnterMobileMode( $context ) {
		global $wgULSEnable, $wgULSMobileWebfontsEnabled;

		// Currently only supported in mobile Beta mode
		if ( $wgULSEnable && $wgULSMobileWebfontsEnabled && $context->isBetaGroupMember() ) {
			$context->getOutput()->addModules( 'ext.uls.webfonts.mobile' );
		}

		return true;
	}
}
