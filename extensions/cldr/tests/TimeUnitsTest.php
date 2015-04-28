<?php
/**
 * Tests for TimeUnits
 * @author Santhosh Thottingal
 * @copyright Copyright © 2007-2013
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class TimeUnitsTest extends MediaWikiTestCase {

	/** @dataProvider providerTimeUnit */
	function testTimeUnit(
		$language,
		$tsTime, // The timestamp to format
		$currentTime, // The time to consider "now"
		$expectedOutput, // The expected output
		$desc // Description
	) {
		$tsTime = new MWTimestamp( $tsTime );
		$currentTime = new MWTimestamp( $currentTime );
		$this->assertEquals(
			$expectedOutput,
			$tsTime->getHumanTimestamp( $currentTime, null, Language::factory( $language ) ),
			$desc
		);
	}

	public static function providerTimeUnit() {
		return array(
			array(
				'en',
				'20111231170000',
				'20120101000000',
				'7 hours ago',
				'"Yesterday" across years',
			),
			array(
				'en',
				'20120717190900',
				'20120717190929',
				'29 seconds ago',
				'"Just now"',
			),
			array(
				'en',
				'20120717190900',
				'20120717191530',
				'6 minutes ago',
				'X minutes ago',
			),
			array(
				'en',
				'20121006173100',
				'20121006173200',
				'1 minute ago',
				'"1 minute ago"',
			),
			array(
				'en',
				'20120617190900',
				'20120717190900',
				'1 month ago',
				'Month difference'
			),
			array(
				'en',
				'19910130151500',
				'20120716193700',
				'21 years ago',
				'Different year',
			),
			array(
				'en',
				'20120714184300',
				'20120715040000',
				'9 hours ago',
				'Today at another time',
			),
			array(
				'en',
				'20120617190900',
				'20120717190900',
				'1 month ago',
				'Another month'
			),
			array(
				'en',
				'19910130151500',
				'20120716193700',
				'21 years ago',
				'Different year',
			),
			array(
				'ml',
				'20111231170000',
				'20120101000000',
				'7 മണിക്കൂർ മുമ്പ്',
				'"Yesterday" across years',
			),
			array(
				'ml',
				'20120717190900',
				'20120717190929',
				'29 സെക്കൻറ് മുമ്പ്',
				'"Just now"',
			),
			array(
				'ml',
				'20120717190900',
				'20120717191530',
				'6 മിനിറ്റ് മുമ്പ്',
				'X minutes ago',
			),
			array(
				'ml',
				'20121006173100',
				'20121006173200',
				'1 മിനിറ്റ് മുമ്പ്',
				'"1 minute ago"',
			),
			array(
				'ml',
				'20120617190900',
				'20120717190900',
				'1 മാസം മുമ്പ്',
				'Month difference'
			),
			array(
				'ml',
				'19910130151500',
				'20120716193700',
				'21 വർഷം മുമ്പ്',
				'Different year',
			),
			array(
				'ml',
				'20120714184300',
				'20120715040000',
				'9 മണിക്കൂർ മുമ്പ്',
				'Today at another time',
			),
			array(
				'ml',
				'20120617190900',
				'20120717190900',
				'1 മാസം മുമ്പ്',
				'Another month'
			),
			array(
				'ml',
				'19910130151500',
				'20120716193700',
				'21 വർഷം മുമ്പ്',
				'Different year',
			),
		);
	}
}

