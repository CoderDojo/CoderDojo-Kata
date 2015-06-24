<?php

namespace SWL\Tests;

use SWL\TableUpdater;

/**
 * @covers \SWL\TableUpdater
 *
 * @group semantic-watchlist
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class TableUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$dbConnection = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SWL\TableUpdater',
			new TableUpdater( $dbConnection )
		);
	}

	public function testUpdateGroupIdsForUserToReplaceDatasetByUserId() {

		$userId = 1111;
		$groupIds = array( 1, 9999 );

		$dbConnection = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'isOpen', 'delete', 'insert' ) )
			->getMockForAbstractClass();

		$dbConnection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( 'swl_users_per_group' ),
				$this->equalTo( array( 'upg_user_id' => $userId ) ) );

		$dbConnection->expects( $this->any() )
			->method( 'isOpen' )
			->will( $this->returnValue( true ) );

		$dbConnection->expects( $this->at( 2 ) )
			->method( 'insert' )
			->will( $this->returnValue( true ) );

		$instance = new TableUpdater( $dbConnection );

		$this->assertTrue(
			$instance->updateGroupIdsForUser( $userId, $groupIds )
		);
	}

	public function testUpdateGroupIdsForUserToOnlyDeleteDatasetByUserId() {

		$userId = 1111;
		$groupIds = array();

		$dbConnection = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( array( 'isOpen', 'delete', 'insert' ) )
			->getMockForAbstractClass();

		$dbConnection->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( 'swl_users_per_group' ),
				$this->equalTo( array( 'upg_user_id' => $userId ) ) );

		$dbConnection->expects( $this->any() )
			->method( 'isOpen' )
			->will( $this->returnValue( true ) );

		$dbConnection->expects( $this->never() )
			->method( 'insert' )
			->will( $this->returnValue( true ) );

		$instance = new TableUpdater( $dbConnection );

		$this->assertTrue(
			$instance->updateGroupIdsForUser( $userId, $groupIds )
		);
	}

}
