<?php

/**
 * Background job to import a page into the wiki, for use by Data Transfer
 *
 * @author Yaron Koren
 */
class DTImportJob extends Job {

	function __construct( $title, $params = '', $id = 0 ) {
		parent::__construct( 'dtImport', $title, $params, $id );
	}

	/**
	 * Run a dtImport job
	 * @return boolean success
	 */
	function run() {
		wfProfileIn( __METHOD__ );

		if ( is_null( $this->title ) ) {
			$this->error = "dtImport: Invalid title";
			wfProfileOut( __METHOD__ );
			return false;
		}

		if ( method_exists( 'WikiPage', 'getContent' ) ) {
			$wikiPage = new WikiPage( $this->title );
			if ( !$wikiPage ) {
				$this->error = 'dtImport: Wiki page not found "' . $this->title->getPrefixedDBkey() . '"';
				wfProfileOut( __METHOD__ );
				return false;
			}
		} else {
			$article = new Article( $this->title );
			if ( !$article ) {
				$this->error = 'dtImport: Article not found "' . $this->title->getPrefixedDBkey() . '"';
				wfProfileOut( __METHOD__ );
				return false;
			}
		}
		$for_pages_that_exist = $this->params['for_pages_that_exist'];
		if ( $for_pages_that_exist == 'skip' && $this->title->exists() ) {
			return true;
		}

		// Change global $wgUser variable to the one specified by
		// the job only for the extent of this import.
		global $wgUser;
		$actual_user = $wgUser;
		$wgUser = User::newFromId( $this->params['user_id'] );
		$text = $this->params['text'];
		if ( $this->title->exists() ) {
			if ( $for_pages_that_exist == 'append' ) {
				if ( method_exists( 'WikiPage', 'getContent' ) ) {
					// MW >= 1.21
					$existingText = $wikiPage->getContent()->getNativeData();
				} else {
					$existingText = $article->getContent();
				}
				$text = $existingText . "\n" . $text;
			} elseif ( $for_pages_that_exist == 'merge' ) {
				$existingPageStructure = DTPageStructure::newFromTitle( $this->title );
				$newPageStructure = new DTPageStructure;
				$newPageStructure->parsePageContents( $text );
				$existingPageStructure->mergeInPageStructure( $newPageStructure );
				$text = $existingPageStructure->toWikitext();
			}
			// otherwise, $for_pages_that_exist == 'overwrite'
		}
		$edit_summary = $this->params['edit_summary'];
		if ( method_exists( 'WikiPage', 'getContent' ) ) {
			$new_content = new WikitextContent( $text );
			$wikiPage->doEditContent( $new_content, $edit_summary );

		} else {
			$article->doEdit( $text, $edit_summary );
		}
		$wgUser = $actual_user;
		wfProfileOut( __METHOD__ );
		return true;
	}
}
