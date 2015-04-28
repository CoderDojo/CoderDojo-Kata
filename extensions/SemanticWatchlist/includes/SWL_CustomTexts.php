<?php

/**
 * Class to provide access to custom texts for emails
 *
 * @since 0.2
 *
 * @file SWL_CustomTexts.php
 * @ingroup SemanticWatchlist
 *
 * @author Nischay Nahata
 */
class SWLCustomTexts {

	/**
	 * Group for this CustomTexts
	 *
	 * @since 0.2
	 *
	 * @var SWLGroup
	 */
	private $group;

	/**
	 * Array holding custom texts to be sent in mails
	 *
	 * @since 0.2
	 *
	 * @var array or null
	 */
	private $customTexts = null;

	public function __construct( SWLGroup $group ) {
		$this->group = $group;
	}

	/**
	 * Sets an array of CustomTexts by reading from the db
	 * for this group.
	 *
	 * @since 0.2
	 */
	private function initCustomTexts() {
		if( !is_null( $this->customTexts ) ) {
			return;
		}
		$this->customTexts = array();
		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow(
			'swl_groups',
			'group_custom_texts',
			array( 'group_name' => $this->group->getName() ),
			'SWL::initCustomTexts'
		);
		$set = explode( '|', $row->group_custom_texts );

		foreach( $set as $elem ) {
			$parts = explode( '~', $elem );
			if( !array_key_exists( $parts[0], $this->customTexts ) ) {
				$this->customTexts[$parts[0]] = array();
			}
			$this->customTexts[$parts[0]][$parts[1]] = $parts[2];
		}
	}

	/**
	 * Returns an array of CustomTexts set by the admin in WatchlistConditions
	 * for this group and property.
	 *
	 * @since 0.2
	 *
	 * @param SMWDIProperty $property
	 * @param String $newValue
	 *
	 * @return String or false
	 */
	public function getPropertyCustomText( SMWDIProperty $property, $newValue ) {
		$this->initCustomTexts();
		if( array_key_exists( $property->getLabel(), $this->customTexts ) && array_key_exists( $newValue, $this->customTexts[$property->getLabel()] ) ) {
			return $this->customTexts[$property->getLabel()][$newValue];
		} else {
			return false;
		}
	}
}
