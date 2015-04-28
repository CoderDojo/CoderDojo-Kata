<?php
/**
 * Class that holds methods used to call Semantic MediaWiki's #subobject
 * parser function (and, for SMW 1.9 and higher, the #set_recurring_event
 * function) to store data when #set_internal or #set_internal_recurring_event
 * are called.
 *
 * @author Yaron Koren
 * @author mwjames
 */
class SIOSubobjectAlias {

	public static function doSetInternal( &$parser ) {
		// For SMW 1.8, this is a hack, since SMW's
		// SMWSubobject::render() call is not meant to be called
		// outside of SMW. Fortunately, for SMW 1.9 and higher,
		// a less hacky approach exists.

		$origArgs = func_get_args();
		// $parser is also $origArgs[0].
		$subobjectArgs = array( &$parser );
		// Blank first argument, so that subobject ID will be
		// an automatically-generated random number.
		$subobjectArgs[1] = '';
		// "main" property, pointing back to the page.
		$mainPageName = $parser->getTitle()->getText();
		$mainPageNamespace = $parser->getTitle()->getNsText();
		if ( $mainPageNamespace != '' ) {
			$mainPageName = $mainPageNamespace . ':' . $mainPageName;
		}
		if ( $origArgs[1] == '' ) {
			die( "Error: first argument to #set_internal cannot be blank." );
		}
		$subobjectArgs[2] = $origArgs[1] . '=' . $mainPageName;

		for ( $i = 2; $i < count( $origArgs ); $i++ ) {
			$propAndValue = explode( '=', $origArgs[$i], 2 );
			if ( count( $propAndValue ) != 2 ) continue;

			list( $prop, $value ) = $propAndValue;
			$prop = trim( $prop );
			$value = trim( $value );
			// If the property name ends with '#list', it's
			// a comma-delimited group of values.
			if ( substr( $prop, - 5 ) == '#list' ) {
				$prop = substr( $prop, 0, strlen( $prop ) - 5 );
				// #subobject has a different syntax for lists
				$actualValues = explode( ',', $value );
				$subobjectArgs[] = "$prop=" . $actualValues[0];
				for ( $j = 1; $j < count( $actualValues ); $j++ ) {
					$subobjectArgs[] = $actualValues[$j];
				}
			} else {
				$subobjectArgs[] = $origArgs[$i];
			}
		}

		if ( class_exists( 'SMW\SubobjectParserFunction' ) ) {
			// SMW 1.9+
			$subobjectFunction = \SMW\ParserFunctionFactory::newFromParser( $parser )->getSubobjectParser();
			return $subobjectFunction->parse( new SMW\ParserParameterFormatter( $subobjectArgs ) );
		} else {
			// SMW 1.8
			call_user_func_array( array( 'SMWSubobject', 'render' ), $subobjectArgs );
		}
		return;
	}

	/**
	 * For SMW 1.8, calls SMW's own #subobject for each instance of
	 * this recurring event. For SMW 1.9 and higher, calls
	 * #set_recurring_event (which itself uses subobjects).
	 */
	public static function doSetInternalRecurringEvent( &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		if ( class_exists( 'SMW\RecurringEventsParserFunction' ) ) {
			// SMW 1.9+
			$recurringEventFunction = \SMW\ParserFunctionFactory::newFromParser( $parser )->getRecurringEventsParser();
			return $recurringEventFunction->parse( new SMW\ParserParameterFormatter( $params ) );
		} else {
			// SMW 1.8
			$results = SMWSetRecurringEvent::getDatesForRecurringEvent( $params );
			if ( $results == null ) {
				return null;
			}

			list( $property, $all_date_strings, $unused_params ) = $results;

			// First param should be a standalone property name.
			$objToPagePropName = array_shift( $params );

			// Mimic a call to #subobject for each date.
			foreach ( $all_date_strings as $date_string ) {
				$first_params = array(
					&$parser,
					'',
					$objToPagePropName . '=' . $parser->getTitle()->getText(),
					"$property=$date_string"
				);

				$cur_params = array_merge( $first_params, $unused_params );
				call_user_func_array( array( 'SMWSubobject', 'render' ), $cur_params );
			}
		}
	}

}
