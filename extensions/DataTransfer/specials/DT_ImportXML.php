<?php
/**
 * Lets the user import an XML file to turn into wiki pages
 *
 * @author Yaron Koren
 */

if ( !defined( 'MEDIAWIKI' ) ) die();

class DTImportXML extends SpecialPage {
	/**
	 * Constructor
	 */
	public function DTImportXML() {
		parent::__construct( 'ImportXML' );
	}

	function execute( $query ) {
		$this->setHeaders();

		if ( ! $this->getUser()->isAllowed( 'datatransferimport' ) ) {
			throw new PermissionsError( 'datatransferimport' );
		}

		$request = $this->getRequest();
		if ( $request->getCheck( 'import_file' ) ) {
			$text = DTUtils::printImportingMessage();
			$uploadResult = ImportStreamSource::newFromUpload( "file_name" );
			$source = $uploadResult->value;
			$importSummary = $request->getVal( 'import_summary' );
			$forPagesThatExist = $request->getVal( 'pagesThatExist' );
			$text .= self::modifyPages( $source, $importSummary, $forPagesThatExist );
		} else {
			$formText = DTUtils::printFileSelector( wfMessage( 'dt_filetype_xml' )->text() );
			$formText .= DTUtils::printExistingPagesHandling();
			$formText .= DTUtils::printImportSummaryInput( wfMessage( 'dt_filetype_xml' )->text() );
			$formText .= DTUtils::printSubmitButton();
			$text = "\t" . Xml::tags( 'form',
				array(
					'enctype' => 'multipart/form-data',
					'action' => '',
					'method' => 'post'
				), $formText ) . "\n";
		}

		$this->getOutput()->addHTML( $text );
	}

	function modifyPages( $source, $editSummary, $forPagesThatExist ) {
		$text = "";
		$xml_parser = new DTXMLParser( $source );
		$xml_parser->doParse();
		$jobs = array();
		$job_params = array();
		$job_params['user_id'] = $this->getUser()->getId();
		$job_params['edit_summary'] = $editSummary;
		$job_params['for_pages_that_exist'] = $forPagesThatExist;

		foreach ( $xml_parser->mPages as $page ) {
			$title = Title::newFromText( $page->getName() );
			$job_params['text'] = $page->createText();
			$jobs[] = new DTImportJob( $title, $job_params );
		}
		// MW 1.21+
		if ( class_exists( 'JobQueueGroup' ) ) {
			JobQueueGroup::singleton()->push( $jobs );
		} else {
			Job::batchInsert( $jobs );
		}
		$text .= $this->msg( 'dt_import_success' )->numParams( count( $jobs ) )->params( 'XML' )
			->parseAsBlock();
		return $text;
	}
}
