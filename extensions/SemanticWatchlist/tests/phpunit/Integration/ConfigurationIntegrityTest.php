<?php

namespace SWL\Tests;

/**
 * @group semantic-watchlist
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author mwjames
 */
class ConfigurationIntegrityTest extends \PHPUnit_Framework_TestCase {

	public function testCanReadDatabaseSchema() {
		$this->assertTrue( is_readable( $GLOBALS['egSwlSqlDatabaseSchemaPath'] ) );
	}

}