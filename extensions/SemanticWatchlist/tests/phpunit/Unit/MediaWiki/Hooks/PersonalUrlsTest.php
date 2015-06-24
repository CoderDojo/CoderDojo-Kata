<?php

namespace SWL\Tests\MediaWiki\Hooks;

use SWL\MediaWiki\Hooks\PersonalUrls;

use Title;

/**
 * @covers \SWL\MediaWiki\Hooks\PersonalUrls
 *
 * @ingroup Test
 *
 * @group SWL
 * @group SWLExtension
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class PersonalUrlsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$personalUrls = array();

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SWL\MediaWiki\Hooks\PersonalUrls',
			new PersonalUrls( $personalUrls, $title, $user )
		);
	}

	public function testExecuteOnEnabledTopLink() {

		$configuration = array( 'egSWLEnableTopLink' => true );
		$personalUrls  = array( 'watchlist' => true );

		$title = Title::newFromText( __METHOD__ );

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'isLoggedIn' )
			->will( $this->returnValue( true ) );

		$user->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->equalTo( 'swl_watchlisttoplink' ) )
			->will( $this->returnValue( true ) );

		$instance = new PersonalUrls( $personalUrls, $title, $user );
		$instance->setConfiguration( $configuration );

		$this->assertTrue( $instance->execute() );
		$this->assertCount( 2, $personalUrls );
	}

	public function testExecuteOnDisabledTopLink() {

		$configuration = array( 'egSWLEnableTopLink' => false );
		$personalUrls  = array();

		$title = Title::newFromText( __METHOD__ );

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PersonalUrls( $personalUrls, $title, $user );
		$instance->setConfiguration( $configuration );

		$this->assertTrue( $instance->execute() );
		$this->assertEmpty( $personalUrls );
	}

	public function testExecuteOnLoggedOutUser() {

		$configuration = array( 'egSWLEnableTopLink' => true );
		$personalUrls  = array();

		$title = Title::newFromText( __METHOD__ );

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'isLoggedIn' )
			->will( $this->returnValue( false ) );

		$instance = new PersonalUrls( $personalUrls, $title, $user );
		$instance->setConfiguration( $configuration );

		$this->assertTrue( $instance->execute() );
		$this->assertEmpty( $personalUrls );
	}

}
