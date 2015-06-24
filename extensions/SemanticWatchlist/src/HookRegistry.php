<?php

namespace SWL;

use SWL\MediaWiki\Hooks\PersonalUrls;
use SWL\MediaWiki\Hooks\UserSaveOptions;
use SWL\MediaWiki\Hooks\GetPreferences;
use SWL\MediaWiki\Hooks\ExtensionSchemaUpdater;
use SWL\TableUpdater;

use User;
use Title;
use Language;

/**
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class HookRegistry {

	/**
	 * @var array
	 */
	private $configuration;

	/**
	 * @since 1.0
	 *
	 * @param array $configuration
	 */
	public function __construct( array $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * @since  1.0
	 *
	 * @param array &$wgHooks
	 */
	public function register( &$wgHooks ) {

		$configuration = $this->configuration;

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
		 */
		$wgHooks['PersonalUrls'][] = function( array &$personal_urls, \Title $title, \SkinTemplate $skin ) use ( $configuration ) {

			$personalUrls = new PersonalUrls(
				$personal_urls,
				$title,
				$skin->getUser()
			);

			$personalUrls->setConfiguration( $configuration );

			return $personalUrls->execute();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserSaveOptions
		 */
		$wgHooks['UserSaveOptions'][] = function( \User $user, array &$options ) use ( $configuration ) {

			$tableUpdater = new TableUpdater( wfGetDB( DB_MASTER ) );

			$userSaveOptions = new UserSaveOptions(
				$tableUpdater,
				$user,
				$options
			);

			return $userSaveOptions->execute();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
		 */
		$wgHooks['LoadExtensionSchemaUpdates'][] = function( \DatabaseUpdater $databaseUpdater ) use ( $configuration ) {

			$extensionSchemaUpdater = new ExtensionSchemaUpdater(
				$databaseUpdater
			);

			$extensionSchemaUpdater->setConfiguration( $configuration );

			return $extensionSchemaUpdater->execute();
		};

		/**
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
		 */
		$wgHooks['GetPreferences'][] = function( User $user, array &$preferences ) use ( $configuration ) {

			$userLanguage = Language::factory(
				$configuration['wgLang']->getCode()
			);

			$getPreferences = new GetPreferences(
				$user,
				$userLanguage,
				$preferences
			);

			$getPreferences->setConfiguration( $configuration );

			return $getPreferences->execute();
		};

		$wgHooks['AdminLinks'][] = 'SWLHooks::addToAdminLinks';
		$wgHooks['SMWStore::updateDataBefore'][] = 'SWLHooks::onDataUpdate';

		if ( $configuration['egSWLEnableEmailNotify'] ) {
			$wgHooks['SWLGroupNotify'][] = 'SWLHooks::onGroupNotify';
		}
	}

}
