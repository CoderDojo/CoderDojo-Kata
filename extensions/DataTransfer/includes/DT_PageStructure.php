<?php
/**
 * Class that represents a single "component" of a page - either a template
 * or a piece of free text.
 *
 * @author Yaron Koren
 */
class DTPageComponent {
	var $mIsTemplate = false;
	var $mTemplateName;
	static $mUnnamedFieldCounter;
	var $mFields;
	var $mFreeText;
	static $mFreeTextIDCounter = 1;
	var $mFreeTextID;

	public static function newTemplate( $templateName ) {
		$dtPageComponent = new DTPageComponent();
		$dtPageComponent->mTemplateName = trim( $templateName );
		$dtPageComponent->mIsTemplate = true;
		$dtPageComponent->mFields = array();
		self::$mUnnamedFieldCounter = 1;
		return $dtPageComponent;
	}
	public static function newFreeText( $freeText ) {
		$dtPageComponent = new DTPageComponent();
		$dtPageComponent->mIsTemplate = false;
		$dtPageComponent->mFreeText = $freeText;
		$dtPageComponent->mFreeTextID = self::$mFreeTextIDCounter++;
		return $dtPageComponent;
	}

	public function addNamedField( $fieldName, $fieldValue ) {
		$this->mFields[trim( $fieldName )] = trim( $fieldValue );
	}

	public function addUnnamedField( $fieldValue ) {
		$fieldName = self::$mUnnamedFieldCounter++;
		$this->mFields[$fieldName] = trim( $fieldValue );
	}

	public function toWikitext() {
		if ( $this->mIsTemplate ) {
			$wikitext = '{{' . $this->mTemplateName;
			foreach ( $this->mFields as $fieldName => $fieldValue ) {
				if ( is_numeric( $fieldName ) ) {
					$wikitext .= '|' . $fieldValue;
				} else {
					$wikitext .= "\n|$fieldName=$fieldValue";
				}
			}
			$wikitext .= "\n}}";
			return $wikitext;
		} else {
			return $this->mFreeText;
		}
	}

	public function toXML( $isSimplified ) {
		global $wgDataTransferViewXMLParseFields;
		global $wgDataTransferViewXMLParseFreeText;
		global $wgParser, $wgTitle;

		if ( $this->mIsTemplate ) {
			global $wgContLang;
			$namespace_labels = $wgContLang->getNamespaces();
			$template_label = $namespace_labels[NS_TEMPLATE];
			$field_str = str_replace( ' ', '_', wfMessage( 'dt_xml_field' )->inContentLanguage()->text() );
			$name_str = str_replace( ' ', '_', wfMessage( 'dt_xml_name' )->inContentLanguage()->text() );

			$bodyXML = '';
			foreach ( $this->mFields as $fieldName => $fieldValue ) {
				// If this field itself holds template calls,
				// get the XML for those calls.
				if ( is_array( $fieldValue ) ) {
					$fieldValueXML = '';
					foreach ( $fieldValue as $subComponent ) {
						$fieldValueXML .= $subComponent->toXML( $isSimplified );
					}
				} elseif ( $wgDataTransferViewXMLParseFields ) {
					// Avoid table of contents and "edit" links
					$fieldValue = $wgParser->parse( "__NOTOC__ __NOEDITSECTION__\n" . $fieldValue, $wgTitle, new ParserOptions() )->getText();
				}

				if ( $isSimplified ) {
					if ( is_numeric( $fieldName ) ) {
						// add "Field" to the beginning of the file name, since
						// XML tags that are simply numbers aren't allowed
						$fieldTag = $field_str . '_' . $fieldName;
					} else {
						$fieldTag = str_replace( ' ', '_', trim( $fieldName ) );
					}
					$attrs = null;
				} else {
					$fieldTag = $field_str;
					$attrs = array( $name_str => $fieldName );
				}
				if ( is_array( $fieldValue ) ) {
					$bodyXML .= Xml::tags( $fieldTag, $attrs, $fieldValueXML );
				} else {
					$bodyXML .= Xml::element( $fieldTag, $attrs, $fieldValue );
				}
			}

			if ( $isSimplified ) {
				$templateName = str_replace( ' ', '_', $this->mTemplateName );
				return Xml::tags( $templateName, null, $bodyXML );
			} else {
				return Xml::tags( $template_label, array( $name_str => $this->mTemplateName ), $bodyXML );
			}
		} else {
			$free_text_str = str_replace( ' ', '_', wfMessage( 'dt_xml_freetext' )->inContentLanguage()->text() );
			if ( $wgDataTransferViewXMLParseFreeText ) {
				$freeText = $this->mFreeText;
				// Undo the escaping that happened before.
				$freeText = str_replace( array( '&#123;', '&#125;' ), array( '{', '}' ), $freeText );
				// Get rid of table of contents.
				$mw = MagicWord::get( 'toc' );
				if ( $mw->match( $freeText ) ) {
					$freeText = $mw->replace( '', $freeText );
				}
				// Avoid "edit" links.
				$freeText = $wgParser->parse( "__NOTOC__ __NOEDITSECTION__\n" . $freeText, $wgTitle, new ParserOptions() )->getText();
			} else {
				$freeText = $this->mFreeText;
			}
			return XML::element( $free_text_str, array( 'id' => $this->mFreeTextID ), $freeText );
		}
	}
}

/**
 * Class that holds the structure of a single wiki page. It is used for both
 * turning wikitext into XML, and vice versa.
 *
 * @author Yaron Koren
 */
class DTPageStructure {
	var $mPageTitle;
	var $mComponents = array();

	function addComponent( $dtPageComponent ) {
		$this->mComponents[] = $dtPageComponent;
		DTPageComponent::$mFreeTextIDCounter = 1;
	}

	public static function newFromTitle( $pageTitle ) {
		$pageStructure = new DTPageStructure();
		$pageStructure->mPageTitle = $pageTitle;

		if ( method_exists( 'WikiPage', 'getContent' ) ) {
			$wiki_page = new WikiPage( $pageTitle );
			$page_contents = $wiki_page->getContent()->getNativeData();
		} else {
			$article = new Article( $pageTitle );
			$page_contents = $article->getContent();
		}

		$pageStructure->parsePageContents( $page_contents );

		// Now, go through the field values and see if any of them
		// hold template calls - if any of them do, parse the value
		// as if it's the full contents of a page, and add the
		// resulting "components" to that field.
		foreach ( $pageStructure->mComponents as $pageComponent ) {
			if ( $pageComponent->mIsTemplate ) {
				foreach ( $pageComponent->mFields as $fieldName => $fieldValue ) {
					if ( strpos( $fieldValue, '{{' ) !== false ) {
						$dummyPageStructure = new DTPageStructure();
						$dummyPageStructure->parsePageContents( $fieldValue );
						$pageComponent->mFields[$fieldName] = $dummyPageStructure->mComponents;
					}
				}
			}
		}
		return $pageStructure;
	}

	/**
	 * Parses the contents of a wiki page, turning template calls into
	 * an arracy of DTPageComponent objects.
	 */
	public function parsePageContents( $page_contents ) {
		// escape out variables like "{{PAGENAME}}"
		$page_contents = str_replace( '{{PAGENAME}}', '&#123;&#123;PAGENAME&#125;&#125;', $page_contents );
		// escape out parser functions
		$page_contents = preg_replace( '/{{(#.+)}}/', '&#123;&#123;$1&#125;&#125;', $page_contents );
		// escape out transclusions, and calls like "DEFAULTSORT"
		$page_contents = preg_replace( '/{{(.*:.+)}}/', '&#123;&#123;$1&#125;&#125;', $page_contents );
		// escape out variable names
		$page_contents = str_replace( '{{{', '&#123;&#123;&#123;', $page_contents );
		$page_contents = str_replace( '}}}', '&#125;&#125;&#125;', $page_contents );
		// escape out tables
		$page_contents = str_replace( '{|', '&#123;|', $page_contents );
		$page_contents = str_replace( '|}', '|&#125;', $page_contents );

		// traverse the page contents, one character at a time
		$uncompleted_curly_brackets = 0;
		$free_text = "";
		$template_name = "";
		$field_name = "";
		$field_value = "";
		$field_has_name = false;
		for ( $i = 0; $i < strlen( $page_contents ); $i++ ) {
			$c = $page_contents[$i];
			if ( $uncompleted_curly_brackets == 0 ) {
				if ( $c == "{" || $i == strlen( $page_contents ) - 1 ) {
					if ( $i == strlen( $page_contents ) - 1 )
						$free_text .= $c;
					$uncompleted_curly_brackets++;
					$free_text = trim( $free_text );
					if ( $free_text != "" ) {
						$freeTextComponent = DTPageComponent::newFreeText( $free_text );
						$this->addComponent( $freeTextComponent );
						$free_text = "";
					}
				} elseif ( $c == "{" ) {
					// do nothing
				} else {
					$free_text .= $c;
				}
			} elseif ( $uncompleted_curly_brackets == 1 ) {
				if ( $c == "{" ) {
					$uncompleted_curly_brackets++;
					$creating_template_name = true;
				} elseif ( $c == "}" ) {
					$uncompleted_curly_brackets--;
					// is this needed?
					// if ($field_name != "") {
					//	$field_name = "";
					// }
					if ( $page_contents[$i - 1] == '}' ) {
						$this->addComponent( $curTemplate );
					}
					$template_name = "";
				}
			} elseif ( $uncompleted_curly_brackets == 2 ) {
				if ( $c == "}" ) {
					$uncompleted_curly_brackets--;
				}
				if ( $c == "{" ) {
					$uncompleted_curly_brackets++;
					$field_value .= $c;
				} else {
					if ( $creating_template_name ) {
						if ( $c == "|" || $c == "}" ) {
							$curTemplate = DTPageComponent::newTemplate( $template_name );
							$template_name = str_replace( ' ', '_', trim( $template_name ) );
							$template_name = str_replace( '&', '&amp;', $template_name );
							$creating_template_name = false;
							$creating_field_name = true;
							$field_id = 1;
						} else {
							$template_name .= $c;
						}
					} else {
						if ( $c == "|" || $c == "}" ) {
							if ( $field_has_name ) {
								$curTemplate->addNamedField( $field_name, $field_value );
								$field_value = "";
								$field_has_name = false;
							} else {
								// "field_name" is actually the value
								$curTemplate->addUnnamedField( $field_name );
							}
							$creating_field_name = true;
							$field_name = "";
						} elseif ( $c == "=" ) {
							// handle case of = in value
							if ( ! $creating_field_name ) {
								$field_value .= $c;
							} else {
								$creating_field_name = false;
								$field_has_name = true;
							}
						} elseif ( $creating_field_name ) {
							$field_name .= $c;
						} else {
							$field_value .= $c;
						}
					}
				}
			} else { // greater than 2
				if ( $c == "}" ) {
					$uncompleted_curly_brackets--;
				} elseif ( $c == "{" ) {
					$uncompleted_curly_brackets++;
				}
				$field_value .= $c;
			}
		}
	}

	/**
	 * Helper function for mergeInPageStructure().
	 */
	private function getSingleInstanceTemplates() {
		$instancesPerTemplate = array();
		foreach ( $this->mComponents as $pageComponent ) {
			if ( $pageComponent->mIsTemplate ) {
				$templateName = $pageComponent->mTemplateName;
				if ( array_key_exists( $templateName, $instancesPerTemplate ) ) {
					$instancesPerTemplate[$templateName]++;
				} else {
					$instancesPerTemplate[$templateName] = 1;
				}
			}
		}

		$singleInstanceTemplates = array();
		foreach ( $instancesPerTemplate as $templateName => $instances ) {
			if ( $instances == 1 ) {
				$singleInstanceTemplates[] = $templateName;
			}
		}
		return $singleInstanceTemplates;
	}

	private function getIndexOfTemplateName( $templateName ) {
		foreach ( $this->mComponents as $i => $pageComponent ) {
			if ( $pageComponent->mTemplateName == $templateName ) {
				return $i;
			}
		}
		return null;
	}

	/**
	 * Used when doing a "merge" in an XML or CSV import.
	 */
	public function mergeInPageStructure( $secondPageStructure ) {
		// If there are any templates that have one instance in both
		// pages, replace values for their fields with values from
		// the second page.
		$singleInstanceTemplatesHere = $this->getSingleInstanceTemplates();
		$singleInstanceTemplatesThere = $secondPageStructure->getSingleInstanceTemplates();
		$singleInstanceTemplatesInBoth = array_intersect( $singleInstanceTemplatesHere, $singleInstanceTemplatesThere );
		foreach ( $secondPageStructure->mComponents as $pageComponent ) {
			if ( in_array( $pageComponent->mTemplateName, $singleInstanceTemplatesInBoth ) ) {
				$indexOfThisTemplate = $this->getIndexOfTemplateName( $pageComponent->mTemplateName );
				foreach ( $pageComponent->mFields as $fieldName => $fieldValue ) {
					$this->mComponents[$indexOfThisTemplate]->mFields[$fieldName] = $fieldValue;
				}
			} else {
				$this->mComponents[] = $pageComponent;
			}
		}
	}

	public function toWikitext() {
		$wikitext = '';
		foreach ( $this->mComponents as $pageComponent ) {
			$wikitext .= $pageComponent->toWikitext() . "\n";
		}
		return trim( $wikitext );
	}

	public function toXML( $isSimplified ) {
		$page_str = str_replace( ' ', '_', wfMessage( 'dt_xml_page' )->inContentLanguage()->text() );
		$id_str = str_replace( ' ', '_', wfMessage( 'dt_xml_id' )->inContentLanguage()->text() );
		$title_str = str_replace( ' ', '_', wfMessage( 'dt_xml_title' )->inContentLanguage()->text() );

		$bodyXML = '';
		foreach ( $this->mComponents as $pageComponent ) {
			$bodyXML .= $pageComponent->toXML( $isSimplified );
		}
		$articleID = $this->mPageTitle->getArticleID();
		$pageName = $this->mPageTitle->getText();
		if ( $isSimplified ) {
			return Xml::tags( $page_str, null, Xml::tags( $id_str, null, $articleID ) . Xml::tags( $title_str, null, $pageName ) . $bodyXML );
		} else {
			return Xml::tags( $page_str, array( $id_str => $articleID, $title_str => $pageName ), $bodyXML );
		}
	}

}
