<?php

namespace SWL\MediaWiki\Hooks;

use DatabaseUpdater;

/**
 * Fired when MediaWiki is updated to allow extensions to update the database
 *
 * https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
 *
 * @ingroup SWL
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class ExtensionSchemaUpdater {

	protected $databaseUpdater;
	protected $configuration;

	/**
	 * @since 1.0
	 *
	 * @param DatabaseUpdater $databaseUpdater
	 */
	public function __construct( DatabaseUpdater $databaseUpdater ) {
		$this->databaseUpdater = $databaseUpdater;
	}

	/**
	 * @since 1.0
	 *
	 * @param array $configuration
	 */
	public function setConfiguration( array $configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function execute() {
		return $this->isSupportedDBType() && $this->hasDatabaseSchema() ? $this->performUpdate() : true;
	}

	private function isSupportedDBType() {
		return in_array( $this->databaseUpdater->getDB()->getType(), array( 'mysql', 'sqlite' ) );
	}

	private function hasDatabaseSchema() {
		return isset( $this->configuration['egSwlSqlDatabaseSchemaPath'] );
	}

	protected function performUpdate() {

		$tables = array(
			'swl_groups',
			'swl_changes',
			'swl_sets',
			'swl_edits_per_group',
			'swl_sets_per_group',
			'swl_users_per_group'
		);

		foreach ( $tables as $tableName ) {
			$this->databaseUpdater->addExtensionUpdate( $this->buildExtensionUpdateDefinition( $tableName ) );
		}

		return true;
	}

	protected function buildExtensionUpdateDefinition( $tableName ) {
		return array(
			'addTable',
			$tableName,
			$this->configuration['egSwlSqlDatabaseSchemaPath'],
			true
		);
	}

}
