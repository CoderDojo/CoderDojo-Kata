<?php

/**
 * Basic cache invalidation for Parsoid
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	echo "Parsoid extension\n";
	exit( 1 );
}

/**
 * Class containing basic setup functions.
 */
class ParsoidSetup {
	/**
	 * Set up Parsoid.
	 *
	 * @return void
	 */
	public static function setup() {
		global $wgAutoloadClasses, $wgJobClasses,
			$wgExtensionCredits, $wgExtensionMessagesFiles, $wgMessagesDirs,
			$wgResourceModules;

		$dir = __DIR__;

		# Set up class autoloading
		$wgAutoloadClasses['ParsoidHooks'] = "$dir/Parsoid.hooks.php";
		$wgAutoloadClasses['ParsoidCacheUpdateJob'] = "$dir/ParsoidCacheUpdateJob.php";
		$wgAutoloadClasses['CurlMultiClient'] = "$dir/CurlMultiClient.php";

		# Add the parsoid job types
		$wgJobClasses['ParsoidCacheUpdateJobOnEdit'] = 'ParsoidCacheUpdateJob';
		$wgJobClasses['ParsoidCacheUpdateJobOnDependencyChange'] = 'ParsoidCacheUpdateJob';
		# Old type for transition
		# @TODO: remove when old jobs are drained
		$wgJobClasses['ParsoidCacheUpdateJob'] = 'ParsoidCacheUpdateJob';

		$wgExtensionCredits['other'][] = array(
			'path' => __FILE__,
			'name' => 'Parsoid',
			'author' => array(
				'Gabriel Wicke',
				'Subramanya Sastry',
				'Mark Holmquist',
				'Adam Wight',
				'C. Scott Ananian'
			),
			'version' => '0.2.0',
			'url' => 'https://www.mediawiki.org/wiki/Extension:Parsoid',
			'descriptionmsg' => 'parsoid-desc',
			'license-name' => 'GPL-2.0+',
		);

		# Register localizations.
		$wgMessagesDirs['Parsoid'] = __DIR__ . '/i18n';
		$wgExtensionMessagesFiles['Parsoid'] = $dir . '/Parsoid.i18n.php';

		# Name modules
		$wgResourceModules += array(
			'ext.parsoid.styles' => array(
				'localBasePath' => __DIR__ . '/modules',
				'remoteExtPath' => 'Parsoid/php/modules',
				'styles' => 'parsoid.styles.css',
				'targets' => array( 'desktop', 'mobile' ),
			),
		);

		# Set up a default configuration
		self::setupDefaultConfig();

		# Now register our hooks.
		self::registerHooks();
	}


	/**
	 * Set up default config values. Override after requiring the extension.
	 *
	 * @return void
	 */
	protected static function setupDefaultConfig() {
		global $wgParsoidCacheServers, $wgParsoidSkipRatio, $wgDBname,
			$wgParsoidCacheUpdateTitlesPerJob, $wgParsoidMaxBacklinksInvalidate,
			$wgParsoidWikiPrefix;

		/**
		 * An array of Varnish caches in front of Parsoid to keep up to date.
		 *
		 * Formats:
		 * 'http://localhost'
		 * 'http://localhost:80'
		 * 'https://127.0.0.1:8080'
		 */
		$wgParsoidCacheServers = array( 'http://localhost' );

		/**
		 * The wiki prefix sent to Parsoid. You can add these with
		 * parsoidConfig.setInterwiki in the Parsoid localsettings.js.
		 */
		$wgParsoidWikiPrefix = $wgDBname;

		/**
		 * The maximum number of backlinks (templates and files) to update.
		 */
		$wgParsoidMaxBacklinksInvalidate = false;

		/**
		 * The maximum number of titles to process in a single
		 * ParsoidCacheUpdateJob
		 */
		$wgParsoidCacheUpdateTitlesPerJob = 50;

		/**
		 * The portion of update requests to skip for basic load shedding. A
		 * float between 0 (none are skipped) and 1 (all are skipped).
		 */
		$wgParsoidSkipRatio = 0.0;
	}


	/**
	 * Register hook handlers.
	 *
	 * @return void
	 */
	protected static function registerHooks() {
		global $wgHooks;

		# Article edit/create
		$wgHooks['ArticleEditUpdates'][] = 'ParsoidHooks::onArticleEditUpdates';
		# Article delete/restore
		$wgHooks['ArticleDeleteComplete'][] = 'ParsoidHooks::onArticleDeleteComplete';
		$wgHooks['ArticleUndelete'][] = 'ParsoidHooks::onArticleUndelete';
		# Revision delete/restore
		$wgHooks['ArticleRevisionVisibilitySet'][] = 'ParsoidHooks::onArticleRevisionVisibilitySet';
		# Article move
		$wgHooks['TitleMoveComplete'][] = 'ParsoidHooks::onTitleMoveComplete';
		# File upload
		$wgHooks['FileUpload'][] = 'ParsoidHooks::onFileUpload';
	}
}

# Load hooks that are always set
ParsoidSetup::setup();
