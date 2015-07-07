<?php

class SpecialKataSearch extends SMWAskPage {
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Main entrypoint for the special page.
	 *
	 * @param string $p
	 */
	public function execute( $p ) {
		global $wgOut, $wgRequest, $smwgQEnabled;

		$wgOut->addModules( 'ext.smw.style' );
		$wgOut->addModules( 'ext.smw.ask' );

		$this->setHeaders();

		if ( !$smwgQEnabled ) {
			$wgOut->addHTML( '<br />' . wfMessage( 'smw_iq_disabled' )->escaped() );
		} else {
			if ( $wgRequest->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $wgRequest->getVal( 'showformatoptions' );
				$params = $wgRequest->getArray( 'params' );
				$wgOut->disable();
				echo $this->showFormatOptions( $format, $params );
			} else {
				$this->extractQueryParameters( $p );
				$this->makeHTMLResult();
			}
		}

		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}

	protected function makeHTMLResult(){
		global $wgOut;

		// TODO: hold into account $smwgAutocompleteInSpecialAsk

		// $result = '';

		// // build parameter strings for URLs, based on current settings
		// $urlArgs['q'] = $this->m_querystring;

		// $tmp_parray = array();
		// foreach ( $this->m_params as $key => $value ) {
		// 	if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
		// 		$tmp_parray[$key] = $value;
		// 	}
		// }

		// $urlArgs['p'] = SMWInfolink::encodeParameters( $tmp_parray );
		// $printoutstring = '';

		// /**
		//  * @var PrintRequest $printout
		//  */
		// foreach ( $this->m_printouts as $printout ) {
		// 	$printoutstring .= $printout->getSerialisation() . "\n";
		// }

		// if ( $printoutstring !== '' ) {
		// 	$urlArgs['po'] = $printoutstring;
		// }

		// if ( array_key_exists( 'sort', $this->m_params ) ) {
		// 	$urlArgs['sort'] = $this->m_params['sort'];
		// }

		// if ( array_key_exists( 'order', $this->m_params ) ) {
		// 	$urlArgs['order'] = $this->m_params['order'];
		// }

		// if ( $this->m_querystring !== '' ) {
		// 	// FIXME: this is a hack
		// 	SMWQueryProcessor::addThisPrintout( $this->m_printouts, $this->m_params );
		// 	$params = SMWQueryProcessor::getProcessedParams( $this->m_params, $this->m_printouts );
		// 	$this->m_params['format'] = $params['format']->getValue();

		// 	$this->params = $params;

		// 	$queryobj = SMWQueryProcessor::createQuery(
		// 		$this->m_querystring,
		// 		$params,
		// 		SMWQueryProcessor::SPECIAL_PAGE,
		// 		$this->m_params['format'],
		// 		$this->m_printouts
		// 	);

		// 	/**
		// 	 * @var SMWQueryResult $res
		// 	 */

		// 	// Determine query results
		// 	$res = $this->getStoreFromParams( $params )->getQueryResult( $queryobj );

		// 	// Try to be smart for rss/ical if no description/title is given and we have a concept query:
		// 	if ( $this->m_params['format'] == 'rss' ) {
		// 		$desckey = 'rssdescription';
		// 		$titlekey = 'rsstitle';
		// 	} elseif ( $this->m_params['format'] == 'icalendar' ) {
		// 		$desckey = 'icalendardescription';
		// 		$titlekey = 'icalendartitle';
		// 	} else { $desckey = false;
		// 	}

		// 	if ( ( $desckey ) && ( $queryobj->getDescription() instanceof SMWConceptDescription ) &&
		// 	     ( !isset( $this->m_params[$desckey] ) || !isset( $this->m_params[$titlekey] ) ) ) {
		// 		$concept = $queryobj->getDescription()->getConcept();

		// 		if ( !isset( $this->m_params[$titlekey] ) ) {
		// 			$this->m_params[$titlekey] = $concept->getText();
		// 		}

		// 		if ( !isset( $this->m_params[$desckey] ) ) {
		// 			// / @bug The current SMWStore will never return SMWConceptValue (an SMWDataValue) here; it might return SMWDIConcept (an SMWDataItem)
		// 			$dv = end( \SMW\StoreFactory::getStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMWDIProperty( '_CONC' ) ) );
		// 			if ( $dv instanceof SMWConceptValue ) {
		// 				$this->m_params[$desckey] = $dv->getDocu();
		// 			}
		// 		}
		// 	}

		// 	$printer = SMWQueryProcessor::getResultPrinter( $this->m_params['format'], SMWQueryProcessor::SPECIAL_PAGE );

		// 	global $wgRequest;

		// 	$hidequery = $wgRequest->getVal( 'eq' ) == 'no';

		// 	if ( !$printer->isExportFormat() ) {
		// 		if ( $res->getCount() > 0 ) {
		// 			if ( $this->m_editquery ) {
		// 				$urlArgs['eq'] = 'yes';
		// 			}
		// 			elseif ( $hidequery ) {
		// 				$urlArgs['eq'] = 'no';
		// 			}

		// 			$navigation = $this->getNavigationBar( $res, $urlArgs );
		// 			$result .= '<div style="text-align: center;">' . "\n" . $navigation . "\n</div>\n";
		// 			$query_result = $printer->getResult( $res, $params, SMW_OUTPUT_HTML );

		// 			if ( is_array( $query_result ) ) {
		// 				$result .= $query_result[0];
		// 			} else {
		// 				$result .= $query_result;
		// 			}

		// 			$result .= '<div style="text-align: center;">' . "\n" . $navigation . "\n</div>\n";
		// 		} else {
		// 			$result = '<div style="text-align: center;">' . wfMessage( 'smw_result_noresults' )->escaped() . '</div>';
		// 		}
		// 	}
		// }

		// if ( isset( $printer ) && $printer->isExportFormat() ) {
		// 	$wgOut->disable();

		// 	/**
		// 	 * @var SMWIExportPrinter $printer
		// 	 */
		// 	$printer->outputAsFile( $res, $params );
		// } else {
		// 	if ( $this->m_querystring ) {
		// 		$wgOut->setHTMLtitle( $this->m_querystring );
		// 	} else {
		// 		$wgOut->setHTMLtitle( wfMessage( 'ask' )->text() );
		// 	}

			//$urlArgs['offset'] = $this->m_params['offset'];
			//$urlArgs['limit'] = $this->m_params['limit'];


			$wgOut->addHTML( '<p>hi</p>' );
		//}
	}
}

?>