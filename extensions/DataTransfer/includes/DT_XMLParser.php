<?php
/**
 * Classes for parsing XML representing wiki pages and their template calls
 *
 * @author Yaron Koren
 */

class DTWikiTemplate {
	private $mName = null;
	private $mFields = array();

	public function DTWikiTemplate( $name ) {
		$this->mName = $name;
	}

	function addField( $name, $value ) {
		$this->mFields[$name] = $value;
	}

	function createText() {
		$multi_line_template = false;
		$text = '{{' . $this->mName;
		foreach ( $this->mFields as $field_name => $field_val ) {
			if ( is_numeric( $field_name ) ) {
				$text .= "|$field_val";
			} else {
				$text .= "\n|$field_name=$field_val";
				$multi_line_template = true;
			}
		}
		if ( $multi_line_template )
			$text .= "\n";
		$text .= '}}' . "\n";
		return $text;
	}
}

class DTWikiPage {
	private $mPageName = null;
	private $mElements = array();

	public function DTWikiPage( $name ) {
		$this->mPageName = $name;
	}

	function getName() {
		return $this->mPageName;
	}

	function addTemplate( $template ) {
		$this->mElements[] = $template;
	}

	function addFreeText( $free_text ) {
		$this->mElements[] = $free_text;
	}

	function createText() {
		$text = "";
		foreach ( $this->mElements as $elem ) {
			if ( $elem instanceof DTWikiTemplate ) {
				$text .= $elem->createText();
			} else {
				$text .= $elem;
			}
		}
		return $text;
	}
}

class DTXMLParser {
	var $mDebug = false;
	var $mSource = null;
	var $mCurFieldName = null;
	var $mCurFieldValue = '';
	var $mCurTemplate = null;
	var $mCurPage = null; // new DTWikiPage();
	var $mPages = array();

	function __construct( $source ) {
		$this->mSource = $source;
	}

	function debug( $text ) {
		// print "$text. ";
	}

	function throwXMLerror( $text ) {
		print htmlspecialchars( $text );
	}

	function doParse() {
		$parser = xml_parser_create( "UTF-8" );

		# case folding violates XML standard, turn it off
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, false );

		xml_set_object( $parser, $this );
		xml_set_element_handler( $parser, "in_start", "" );

		$offset = 0; // for context extraction on error reporting
		do {
			$chunk = $this->mSource->readChunk();
			if ( !xml_parse( $parser, $chunk, $this->mSource->atEnd() ) ) {
				wfDebug( "WikiImporter::doImport encountered XML parsing error\n" );
				// return new WikiXmlError( $parser, wfMessage( 'import-parse-failure' )->escaped(), $chunk, $offset );
			}
			$offset += strlen( $chunk );
		} while ( $chunk !== false && !$this->mSource->atEnd() );
		xml_parser_free( $parser );
	}

	function donothing( $parser, $x, $y = "" ) {
		# $this->debug( "donothing" );
	}


	function in_start( $parser, $name, $attribs ) {
		// $this->debug( "in_start $name" );
		$pages_str = str_replace( ' ', '_', wfMessage( 'dt_xml_pages' )->inContentLanguage()->text() );
		if ( $name != $pages_str ) {
			print( "Expected '$pages_str', got '$name'" );
		}
		xml_set_element_handler( $parser, "in_pages", "out_pages" );
	}

	function in_pages( $parser, $name, $attribs ) {
		$this->debug( "in_pages $name" );
		$page_str = str_replace( ' ', '_', wfMessage( 'dt_xml_page' )->inContentLanguage()->text() );
		if ( $name == $page_str ) {
			$title_str = str_replace( ' ', '_', wfMessage( 'dt_xml_title' )->inContentLanguage()->text() );
			if ( array_key_exists( $title_str, $attribs ) ) {
				$this->mCurPage = new DTWikiPage( $attribs[$title_str] );
			xml_set_element_handler( $parser, "in_page", "out_page" );
			} else {
				$this->throwXMLerror( "'$title_str' attribute missing for page" );
				return;
			}
		} else {
			$this->throwXMLerror( "Expected <$page_str>, got <$name>" );
		}

		return;
	}

	function out_pages( $parser, $name ) {
		$this->debug( "out_pages $name" );
		xml_set_element_handler( $parser, "donothing", "donothing" );
	}

	function in_category( $parser, $name, $attribs ) {
		$this->debug( "in_category $name" );
		$page_str = str_replace( ' ', '_', wfMessage( 'dt_xml_page' )->inContentLanguage()->text() );
		if ( $name == $page_str ) {
			if ( array_key_exists( $title_str, $attribs ) ) {
				$this->mCurPage = new DTWikiPage( $attribs[$title_str] );
			xml_set_element_handler( $parser, "in_page", "out_page" );
			} else {
				$this->throwXMLerror( "'$title_str' attribute missing for page" );
				return;
			}
		} else {
			$this->throwXMLerror( "Expected <$page_str>, got <$name>" );
			return;
		}
	}

	function out_category( $parser, $name ) {
		$this->debug( "out_category $name" );
		if ( $name != "category" ) {
			$this->throwXMLerror( "Expected </category>, got </$name>" );
			return;
		}
		xml_set_element_handler( $parser, "donothing", "donothing" );
	}

	function in_page( $parser, $name, $attribs ) {
		$this->debug( "in_page $name" );
		$template_str = str_replace( ' ', '_', wfMessage( 'dt_xml_template' )->inContentLanguage()->text() );
		$name_str = str_replace( ' ', '_', wfMessage( 'dt_xml_name' )->inContentLanguage()->text() );
		$free_text_str = str_replace( ' ', '_', wfMessage( 'dt_xml_freetext' )->inContentLanguage()->text() );
		if ( $name == $template_str ) {
			if ( array_key_exists( $name_str, $attribs ) ) {
				$this->mCurTemplate = new DTWikiTemplate( $attribs[$name_str] );
			xml_set_element_handler( $parser, "in_template", "out_template" );
			} else {
				$this->throwXMLerror( "'$name_str' attribute missing for template" );
				return;
			}
		} elseif ( $name == $free_text_str ) {
			xml_set_element_handler( $parser, "in_freetext", "out_freetext" );
			xml_set_character_data_handler( $parser, "freetext_value" );
		} else {
			$this->throwXMLerror( "Expected <$template_str>, got <$name>" );
			return;
		}
	}

	function out_page( $parser, $name ) {
		$this->debug( "out_page $name" );
		$page_str = str_replace( ' ', '_', wfMessage( 'dt_xml_page' )->inContentLanguage()->text() );
		if ( $name != $page_str ) {
			$this->throwXMLerror( "Expected </$page_str>, got </$name>" );
			return;
		}
		$this->mPages[] = $this->mCurPage;
		xml_set_element_handler( $parser, "in_pages", "out_pages" );
	}

	function in_template( $parser, $name, $attribs ) {
		$this->debug( "in_template $name" );
		$field_str = str_replace( ' ', '_', wfMessage( 'dt_xml_field' )->inContentLanguage()->text() );
		if ( $name == $field_str ) {
			$name_str = str_replace( ' ', '_', wfMessage( 'dt_xml_name' )->inContentLanguage()->text() );
			if ( array_key_exists( $name_str, $attribs ) ) {
				$this->mCurFieldName = $attribs[$name_str];
			// $this->push( $name );
			$this->workRevisionCount = 0;
			$this->workSuccessCount = 0;
			$this->uploadCount = 0;
			$this->uploadSuccessCount = 0;
			xml_set_element_handler( $parser, "in_field", "out_field" );
			xml_set_character_data_handler( $parser, "field_value" );
			} else {
				$this->throwXMLerror( "'$name_str' attribute missing for field" );
				return;
			}
		} else {
			$this->throwXMLerror( "Expected <$field_str>, got <$name>" );
			return;
		}
	}

	function out_template( $parser, $name ) {
		$this->debug( "out_template $name" );
		$template_str = str_replace( ' ', '_', wfMessage( 'dt_xml_template' )->inContentLanguage()->text() );
		if ( $name != $template_str ) {
			$this->throwXMLerror( "Expected </$template_str>, got </$name>" );
			return;
		}
		$this->mCurPage->addTemplate( $this->mCurTemplate );
		xml_set_element_handler( $parser, "in_page", "out_page" );
	}

	function in_field( $parser, $name, $attribs ) {
		// xml_set_element_handler( $parser, "donothing", "donothing" );
	}

	function out_field( $parser, $name ) {
		$this->debug( "out_field $name" );
		$field_str = str_replace( ' ', '_', wfMessage( 'dt_xml_field' )->inContentLanguage()->text() );
		if ( $name == $field_str ) {
			$this->mCurTemplate->addField( $this->mCurFieldName, $this->mCurFieldValue );
			$this->mCurFieldValue = '';
		} else {
			$this->throwXMLerror( "Expected </$field_str>, got </$name>" );
			return;
		}
		xml_set_element_handler( $parser, "in_template", "out_template" );
	}

	function field_value( $parser, $data ) {
		$this->mCurFieldValue .= $data;
	}

	function in_freetext( $parser, $name, $attribs ) {
		// xml_set_element_handler( $parser, "donothing", "donothing" );
	}

	function out_freetext( $parser, $name ) {
		xml_set_element_handler( $parser, "in_page", "out_page" );
	}

	function freetext_value( $parser, $data ) {
		$this->mCurPage->addFreeText( $data );
	}
}
