<?php

namespace SWL\Tests\MediaWiki\Hooks;

use SWL\MediaWiki\Hooks\UserSaveOptions;

/**
 * @covers \SWL\MediaWiki\Hooks\UserSaveOptions
 *
 * @group semantic-watchlist
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class UserSaveOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$tableUpdater = $this->getMockBuilder( '\SWL\TableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$options = array();

		$this->assertInstanceOf(
			'\SWL\MediaWiki\Hooks\UserSaveOptions',
			new UserSaveOptions( $tableUpdater, $user, $options )
		);
	}

	public function testExecuteWithEmptyOption() {

		$instance = $this->createUserSaveOptionsInstance(
			array(),
			array()
		);

		$this->assertTrue( $instance->execute() );
	}

	public function testExecuteWithValidSwlOption() {

		$instance = $this->createUserSaveOptionsInstance(
			array( 'swl_watchgroup_9999' => true ),
			array( 9999 )
		);

		$this->assertTrue( $instance->execute() );
	}

	public function testExecuteWithInvalidSwlOption() {

		$instance = $this->createUserSaveOptionsInstance(
			array( '9999' => true ),
			array()
		);

		$this->assertTrue( $instance->execute() );
	}

	private function createUserSaveOptionsInstance( $options, $expected ) {

		$tableUpdater = $this->getMockBuilder( '\SWL\TableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$tableUpdater->expects( $this->once() )
			->method( 'updateGroupIdsForUser' )
			->with(
				$this->anything(),
				$this->equalTo( $expected ) )
			->will( $this->returnValue( true ) );

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		return new UserSaveOptions(
			$tableUpdater,
			$user,
			$options
		);
	}

}
