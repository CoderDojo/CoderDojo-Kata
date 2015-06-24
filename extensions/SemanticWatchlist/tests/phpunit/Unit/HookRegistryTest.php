<?php

namespace SWL\Tests;

use SWL\HookRegistry;
use SMW\DIWikiPage;
use Title;

/**
 * @covers \SWL\HookRegistry
 *
 * @group semantic-watchlist
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class HookRegistryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SWL\HookRegistry',
			new HookRegistry( array() )
		);
	}

	public function testRegister() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->any() )
			->method( 'getCode' )
			->will( $this->returnValue( 'en' ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$configuration = array(
			'egSWLEnableTopLink'         => false,
			'egSWLEnableEmailNotify'     => false,
			'egSwlSqlDatabaseSchemaPath' => '../foo',
			'wgLang' => $language
		);

		$wgHooks = array();

		$instance = new HookRegistry( $configuration );
		$instance->register( $wgHooks );

		$this->assertNotEmpty(
			$wgHooks
		);

		$this->doTestPersonalUrls( $wgHooks, $user );
		$this->doTestUserSaveOptions( $wgHooks, $user );
		$this->doTestLoadExtensionSchemaUpdates( $wgHooks );
		$this->doTestGetPreferences( $wgHooks, $user );
		$this->doTestStoreUpdate( $wgHooks );
	}

	private function doTestPersonalUrls( $wgHooks, $user ) {

		$title = Title::newFromText( __METHOD__ );

		$skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( $user ) );

		$personal_urls = array();

		$this->assertThatHookIsExcutable(
			$wgHooks,
			'PersonalUrls',
			array( &$personal_urls, $title, $skinTemplate )
		);
	}

	private function doTestUserSaveOptions( $wgHooks, $user ) {

		$options = array();

		$this->assertThatHookIsExcutable(
			$wgHooks,
			'UserSaveOptions',
			array( $user, &$options )
		);
	}

	private function doTestLoadExtensionSchemaUpdates( $wgHooks ) {

		$databaseBase = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$databaseUpdater = $this->getMockBuilder( '\DatabaseUpdater' )
			->disableOriginalConstructor()
			->setMethods( array( 'getDB' ) )
			->getMockForAbstractClass();

		$databaseUpdater->expects( $this->any() )
			->method( 'getDB' )
			->will( $this->returnValue( $databaseBase ) );

		$this->assertThatHookIsExcutable(
			$wgHooks,
			'LoadExtensionSchemaUpdates',
			array( $databaseUpdater )
		);
	}

	private function doTestGetPreferences( $wgHooks, $user ) {

		$preferences = array();

		$this->assertThatHookIsExcutable(
			$wgHooks,
			'GetPreferences',
			array( $user, &$preferences )
		);
	}

	public function doTestStoreUpdate( $wgHooks ) {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->assertThatHookIsExcutable(
			$wgHooks,
			'SMWStore::updateDataBefore',
			array( $store, $semanticData )
		);
	}

	private function assertThatHookIsExcutable( $wgHooks, $hookName, $arguments ) {
		foreach ( $wgHooks[ $hookName ] as $hook ) {
			$this->assertInternalType(
				'boolean',
				call_user_func_array( $hook, $arguments )
			);
		}
	}

}
