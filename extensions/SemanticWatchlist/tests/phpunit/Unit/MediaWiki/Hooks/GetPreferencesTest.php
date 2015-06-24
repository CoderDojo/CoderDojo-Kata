<?php

namespace SWL\Tests\MediaWiki\Hooks;

use SWL\MediaWiki\Hooks\GetPreferences;
use SMW\DIProperty;

/**
 * @covers \SWL\MediaWiki\Hooks\GetPreferences
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
class GetPreferencesTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( 'Language' )
			->disableOriginalConstructor()
			->getMock();

		$preferences = array();

		$this->assertInstanceOf(
			'\SWL\MediaWiki\Hooks\GetPreferences',
			new GetPreferences( $user, $language, $preferences )
		);
	}

	public function testExecuteOnEnabledEmailNotifyPreference() {

		$swlGroup    = array();
		$preferences = array();

		$configuration = array(
			'egSWLEnableEmailNotify' => true,
			'egSWLEnableTopLink'     => false
		);

		$instance = $this->acquireInstance( $configuration, $swlGroup, $preferences );

		$this->assertTrue( $instance->execute() );
		$this->assertCount( 1, $preferences );
	}

	public function testExecuteOnEnabledTopLinkPreference() {

		$swlGroup    = array();
		$preferences = array();

		$configuration = array(
			'egSWLEnableEmailNotify' => false,
			'egSWLEnableTopLink'     => true
		);

		$instance = $this->acquireInstance( $configuration, $swlGroup, $preferences );

		$this->assertTrue( $instance->execute() );
		$this->assertCount( 1, $preferences );
	}

	public function testExecuteOnSingleCategoryGroupPreference() {

		$swlGroup = $this->getMockBuilder( 'SWLGroup' )
			->disableOriginalConstructor()
			->getMock();

		$swlGroup->expects( $this->once() )
			->method( 'getProperties' )
			->will( $this->returnValue( array( 'FooProperty' ) ) );

		$swlGroup->expects( $this->exactly( 2 ) )
			->method( 'getCategories' )
			->will( $this->returnValue( array( 'FooCategory' ) ) );

		$swlGroup->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( 9999 ) );

		$swlGroup->expects( $this->once() )
			->method( 'getName' )
			->will( $this->returnValue( 'Foo' ) );

		$preferences = array();

		$configuration = array(
			'egSWLEnableEmailNotify' => false,
			'egSWLEnableTopLink'     => false
		);

		$instance = $this->acquireInstance( $configuration, array( $swlGroup ), $preferences );

		$this->assertTrue( $instance->execute() );
		$this->assertCount( 1, $preferences );
	}

	protected function acquireInstance( $configuration, $swlGroup, &$preferences ) {

		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$language = $this->getMockBuilder( 'Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->any() )
			->method( 'getCode' )
			->will( $this->returnValue( 'en' ) );

		$instance = $this->getMockBuilder( '\SWL\MediaWiki\Hooks\GetPreferences' )
			->setConstructorArgs( array( $user, $language, &$preferences ) )
			->setMethods( array( 'getAllSwlGroups' ) )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'getAllSwlGroups' )
			->will( $this->returnValue( $swlGroup ) );

		$instance->setConfiguration( $configuration );

		return $instance;
	}

}
