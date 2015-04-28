<?php
/**
 * Implement the 'templatedata' query module in the API.
 * Format JSON only.
 *
 * @file
 */

/**
 * @ingroup API
 * @emits error.code templatedata-corrupt
 */
class ApiTemplateData extends ApiBase {

	/**
	 * Override built-in handling of format parameter.
	 * Only JSON is supported.
	 *
	 * @return ApiFormatBase
	 */
	public function getCustomPrinter() {
		$params = $this->extractRequestParams();
		$format = $params['format'];
		$allowed = array( 'json', 'jsonfm' );
		if ( in_array( $format, $allowed ) ) {
			return $this->getMain()->createPrinterByName( $format );
		}
		return $this->getMain()->createPrinterByName( $allowed[0] );
	}

	/**
	 * @return ApiPageSet
	 */
	private function getPageSet() {
		if ( !isset( $this->mPageSet ) ) {
			$this->mPageSet = new ApiPageSet( $this );
		}
		return $this->mPageSet;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$result = $this->getResult();

		if ( is_null( $params['lang'] ) ) {
			$langCode = false;
		} elseif ( !Language::isValidCode( $params['lang'] ) ) {
			$this->dieUsage( 'Invalid language code for parameter lang', 'invalidlang' );
		} else {
			$langCode = $params['lang'];
		}

		$pageSet = $this->getPageSet();
		$pageSet->execute();
		$titles = $pageSet->getGoodTitles(); // page_id => Title object

		if ( !count( $titles ) ) {
			$result->addValue( null, 'pages', (object) array() );
			return;
		}

		$db = $this->getDB();
		$res = $db->select( 'page_props',
			array( 'pp_page', 'pp_value' ), array(
				'pp_page' => array_keys( $titles ),
				'pp_propname' => 'templatedata'
			),
			__METHOD__,
			array( 'ORDER BY', 'pp_page' )
		);

		$resp = array();

		foreach ( $res as $row ) {
			$rawData = $row->pp_value;
			$tdb = TemplateDataBlob::newFromDatabase( $rawData );
			$status = $tdb->getStatus();

			if ( !$status->isOK() ) {
				$this->dieUsage(
					'Page #' . intval( $row->pp_page ) . ' templatedata contains invalid data: '
						. $status->getMessage(), 'templatedata-corrupt'
				);
			}

			if ( $langCode ) {
				$data = $tdb->getDataInLanguage( $langCode );
			} else {
				$data = $tdb->getData();
			}

			$resp[$row->pp_page] = array(
				'title' => strval( $titles[$row->pp_page] ),
			) + (array) $data;
		}

		// Set top level element
		$result->addValue( null, 'pages', (object) $resp );

		$values = $pageSet->getNormalizedTitlesAsResult();
		if ( $values ) {
			$result->addValue( null, 'normalized', $values );
		}
		$redirects = $pageSet->getRedirectTitlesAsResult();
		if ( $redirects ) {
			$result->addValue( null, 'redirects', $redirects );
		}
	}

	public function getAllowedParams( $flags = 0 ) {
		return $this->getPageSet()->getFinalParams( $flags ) + array(
			'format' => array(
				ApiBase::PARAM_DFLT => 'json',
				ApiBase::PARAM_TYPE => array( 'json', 'jsonfm' ),
			),
			'lang' => null
		);
	}

	public function getParamDescription() {
		return $this->getPageSet()->getParamDescription() + array(
			'format' => 'The format of the output',
			'lang' => 'Return localized values in this language (by default all available' .
				' translations are returned)',
		);
	}

	public function getDescription() {
		return 'Data stored by the TemplateData extension';
	}

	public function getExamples() {
		return array(
			'api.php?action=templatedata&titles=Template:Stub|Template:Example',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:TemplateData';
	}
}
