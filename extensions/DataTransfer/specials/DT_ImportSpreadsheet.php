<?php
/**
 * Lets the user import a spreadsheet file to turn into wiki pages
 *
 * @author Stephan Gambke
 */

class DTImportSpreadsheet extends DTImportCSV {

	public function __construct( $name='ImportSpreadsheet' ) {
		parent::__construct( $name );
	}

	protected function printForm() {
		$formText = DTUtils::printFileSelector( $this->getFiletype() );
		$formText .= DTUtils::printExistingPagesHandling();
		$formText .= DTUtils::printImportSummaryInput( $this->getFiletype() );
		$formText .= DTUtils::printSubmitButton();
		$text = "\t" . Xml::tags( 'form',
				array(
					'enctype' => 'multipart/form-data',
					'action' => '',
					'method' => 'post'
				), $formText ) . "\n";
		return $text;
	}

	protected function importFromFile( $file, $encoding, &$pages ) {

		if ( is_null( $file ) ) {
			return wfMessage( 'emptyfile' )->text();
		}

		$metadata = stream_get_meta_data( $file );
		$filename = $metadata['uri'];

		@$objPHPExcel = PHPExcel_IOFactory::load( $filename );

		$table = $objPHPExcel->getSheet(0)->toArray( '', true, true, false );

		return $this->importFromArray( $table, $pages );

	}

	protected function getFiletype() {
		return wfMessage( 'dt_filetype_spreadsheet' )->text();
	}
}