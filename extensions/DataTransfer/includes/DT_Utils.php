<?php

/**
 * Utility functions for the Data Transfer extension.
 *
 * @author Yaron Koren
 */
class DTUtils  {

	static function printImportingMessage() {
		return "\t" . Xml::element( 'p', null, wfMessage( 'dt_import_importing' )->text() ) . "\n";
	}

	static function printFileSelector( $fileType ) {
		$text = "\n\t" . Xml::element( 'p', null, wfMessage( 'dt_import_selectfile', $fileType )->text() ) . "\n";
		$text .= <<<END
	<p><input type="file" name="file_name" size="25" /></p>

END;
		$text .= "\t" . '<hr style="margin: 10px 0 10px 0" />' . "\n";
		return $text;
	}

	static function printExistingPagesHandling() {
		$text = "\t" . Xml::element( 'p', null, wfMessage( 'dt_import_forexisting' )->text() ) . "\n";
		$existingPagesText = "\n\t" .
			Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'overwrite',
					'checked' => 'checked'
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_overwriteexisting' )->text() . "<br />" . "\n" .
			"\t" . Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'merge',
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_mergeintoexisting' )->text() . "<br />" . "\n\t" .
			"\t" . Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'skip',
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_skipexisting' )->text() . "<br />" . "\n" .
			"\t" . Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'append',
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_appendtoexisting' )->text() . "<br />" . "\n\t";
		$text .= "\t" . Xml::tags( 'p', null, $existingPagesText ) . "\n";
		$text .= "\t" .  '<hr style="margin: 10px 0 10px 0" />' . "\n";
		return $text;
	}

	static function printImportSummaryInput( $fileType ) {
		$importSummaryText = "\t" . Xml::element( 'input',
			array(
				'type' => 'text',
				'id' => 'wpSummary', // ID is necessary for CSS formatting
				'class' => 'mw-summary',
				'name' => 'import_summary',
				'value' => wfMessage( 'dt_import_editsummary', $fileType )->inContentLanguage()->text()
			)
		) . "\n";
		return "\t" . Xml::tags( 'p', null,
			wfMessage( 'dt_import_summarydesc' )->text() . "\n" .
			$importSummaryText ) . "\n";
	}

	static function printSubmitButton() {
		$formSubmitText = Xml::element( 'input',
			array(
				'type' => 'submit',
				'name' => 'import_file',
				'value' => wfMessage( 'import-interwiki-submit' )->text()
			)
		);
		return "\t" . Xml::tags( 'p', null, $formSubmitText ) . "\n";
	}
}
