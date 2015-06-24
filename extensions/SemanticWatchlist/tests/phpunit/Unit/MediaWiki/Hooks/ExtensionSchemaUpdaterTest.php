<?php

namespace SWL\Tests\MediaWiki\Hooks;

use SWL\MediaWiki\Hooks\ExtensionSchemaUpdater;

/**
 * @covers \SWL\MediaWiki\Hooks\ExtensionSchemaUpdater
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
class ExtensionSchemaUpdaterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$databaseUpdater = $this->getMockBuilder( 'DatabaseUpdater' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SWL\MediaWiki\Hooks\ExtensionSchemaUpdater',
			new ExtensionSchemaUpdater( $databaseUpdater )
		);
	}

	public function testNoExtensionUpdateForInvalidDBType() {

		$dbConnection = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dbConnection->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( 'foo' ) );

		$databaseUpdater = $this->getMockBuilder( 'DatabaseUpdater' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getDB',
				'addExtensionUpdate' ) )
			->getMockForAbstractClass();

		$databaseUpdater->expects( $this->once() )
			->method( 'getDB' )
			->will( $this->returnValue( $dbConnection ) );

		$databaseUpdater->expects( $this->never() )
			->method( 'addExtensionUpdate' )
			->will( $this->returnValue( true ) );

		$instance = new ExtensionSchemaUpdater( $databaseUpdater );

		$this->assertTrue( $instance->execute() );
	}

	/**
	 * @dataProvider dbTypeProvider
	 */
	public function testExtensionUpdateForValidDBType( $dbType ) {

		$dbConnection = $this->getMockBuilder( 'DatabaseBase' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dbConnection->expects( $this->once() )
			->method( 'getType' )
			->will( $this->returnValue( $dbType ) );

		$databaseUpdater = $this->getMockBuilder( 'DatabaseUpdater' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getDB',
				'addExtensionUpdate' ) )
			->getMockForAbstractClass();

		$databaseUpdater->expects( $this->once() )
			->method( 'getDB' )
			->will( $this->returnValue( $dbConnection ) );

		$databaseUpdater->expects( $this->at( 6 ) )
			->method( 'addExtensionUpdate' )
			->will( $this->returnValue( true ) );

		$configuration = array( 'egSwlSqlDatabaseSchemaPath' => 'foo' );

		$instance = new ExtensionSchemaUpdater( $databaseUpdater );
		$instance->setConfiguration( $configuration );

		$this->assertTrue( $instance->execute() );
	}

	public function dbTypeProvider() {

		$provider = array(
			array( 'sqlite' ),
			array( 'mysql' )
		);

		return $provider;
	}

}
