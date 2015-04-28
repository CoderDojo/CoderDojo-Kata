<?php
/**
 * Localization API for ULS
 *
 * Copyright (C) 2013 Alolita Sharma, Amir Aharoni, Arun Ganesh, Brandon Harris,
 * Niklas Laxström, Pau Giner, Santhosh Thottingal, Siebrand Mazeland and other
 * contributors. See CREDITS for a list.
 *
 * UniversalLanguageSelector is dual licensed GPLv2 or later and MIT. You don't
 * have to do anything special to choose one license or the other and you don't
 * have to notify anyone which license you are using. You are free to use
 * UniversalLanguageSelector in commercial projects as long as the copyright
 * header is left intact. See files GPL-LICENSE and MIT-LICENSE for details.
 *
 * @file
 * @ingroup Extensions
 * @licence GNU General Public Licence 2.0 or later
 * @licence MIT License
 */

/**
 * @ingroup API
 */
class ApiULSLocalization extends ApiBase {

	public function execute() {
		$this->getMain()->setCacheMode( 'public' );
		$this->getMain()->setCacheMaxAge( 2419200 );

		$params = $this->extractRequestParams();
		$language = $params['language'];
		if ( !Language::isValidCode( $language ) ) {
			$this->dieUsage( 'Invalid language', 'invalidlanguage' );
		}
		$contents = ULSJsonMessageLoader::getMessages( $language );
		// Output the file's contents raw
		$this->getResult()->addValue( null, 'text', json_encode( $contents ) );
		$this->getResult()->addValue( null, 'mime', 'application/json' );
	}

	public function getCustomPrinter() {
		return new ApiFormatRaw(
			$this->getMain(),
			$this->getMain()->createPrinterByName( 'json' )
		);
	}

	public function getAllowedParams() {
		return array(
			'language' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	public function getParamDescription() {
		return array(
			'language' => 'Language string',
		);
	}

	public function getDescription() {
		return 'Get the localization of ULS in given language';
	}

	public function getExamples() {
		return array(
			'api.php?action=ulslocalization&language=ta',
			'api.php?action=ulslocalization&language=hi',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': ' . ULS_VERSION;
	}
}
