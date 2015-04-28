<?php

namespace SWL;

use DatabaseBase;

/**
 * @ingroup semantic-watchlist
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class TableUpdater {

	/**
	 * @var DatabaseBase
	 */
	private $dbConnection;

	/**
	 * @since 1.0
	 *
	 * @param DatabaseBase $dbConnection
	 */
	public function __construct( DatabaseBase $dbConnection ) {
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @since 1.0
	 *
	 * @param $userId
	 * @param array $groupIds
	 */
	public function updateGroupIdsForUser( $userId, array $groupIds ) {

		$this->dbConnection->begin();

		$this->dbConnection->delete(
			'swl_users_per_group',
			array(
				'upg_user_id' => $userId
			)
		);

		foreach ( $groupIds as $groupId ) {
			$this->insertGroup( $userId, $groupId );
		}

		$this->dbConnection->commit();

		return true;
	}

	private function insertGroup( $userId, $groupId ) {
		$this->dbConnection->insert(
			'swl_users_per_group',
			array(
				'upg_user_id'  => $userId,
				'upg_group_id' => $groupId
			)
		);
	}

}
