<?php

namespace SM\Test;

use SMW\Test\QueryPrinterRegistryTestCase;

/**
 * @covers SMKMLPrinter
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class KMLPrinterTest extends QueryPrinterRegistryTestCase {

	/**
	 * @see ResultPrinterTestCase::getFormats
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function getFormats() {
		return array( 'kml' );
	}

	/**
	 * @see ResultPrinterTestCase::getClass
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMKMLPrinter';

	}
}
