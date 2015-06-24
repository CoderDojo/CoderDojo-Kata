<?php

namespace SWL\MediaWiki\Hooks;

use SWL\tableUpdater;

use User;

/**
 * Called just before saving user preferences/options in order to find the
 * watchlist groups the user watches, and update the swl_users_per_group table.
 *
 * https://www.mediawiki.org/wiki/Manual:Hooks/UserSaveOptions
 *
 * @ingroup SWL
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class UserSaveOptions {

	/**
	 * @var TableUpdater
	 */
	private $tableUpdater;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @since 1.0
	 *
	 * @param TableUpdater $tableUpdater
	 * @param User $user
	 * @param array &$options
	 */
	public function __construct( TableUpdater $tableUpdater, User $user, array &$options ) {
		$this->tableUpdater = $tableUpdater;
		$this->user = $user;
		$this->options =& $options;
	}

	/**
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function execute() {

		$groupIds = array();

		foreach ( $this->options as $name => $value ) {
			if ( strpos( $name, 'swl_watchgroup_' ) === 0 && $value ) {
				$groupIds[] = (int)substr( $name, strrpos( $name, '_' ) + 1 );
			}
		}

		return $this->performUpdate( $groupIds );
	}

	protected function performUpdate( array $groupIds ) {
		return $this->tableUpdater->updateGroupIdsForUser(
			$this->user->getId(),
			$groupIds
		);
	}

}
