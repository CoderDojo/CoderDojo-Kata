<?php
/**
 * Displays an interface to let the user export pages from the wiki in XML form
 *
 * @author Yaron Koren
 */

class DTViewXML extends SpecialPage {
	/**
	 * Constructor
	 */
	public function DTViewXML() {
		parent::__construct( 'ViewXML' );
	}

	function execute( $query ) {
		$this->setHeaders();
		self::doSpecialViewXML( $query );
	}

	static function getCategoriesList() {
		global $wgContLang, $dtgContLang;
		$dt_props = $dtgContLang->getPropertyLabels();
		$exclusion_cat_name = str_replace( ' ', '_', $dt_props[DT_SP_IS_EXCLUDED_FROM_XML] );
		$exclusion_cat_full_name = $wgContLang->getNSText( NS_CATEGORY ) . ':' . $exclusion_cat_name;
		$dbr = wfGetDB( DB_SLAVE );
		$categorylinks = $dbr->tableName( 'categorylinks' );
		$res = $dbr->query( "SELECT DISTINCT cl_to FROM $categorylinks" );
		$categories = array();
		while ( $row = $dbr->fetchRow( $res ) ) {
			$cat_name = $row[0];
			// Add this category to the list, if it's not the
			// "Excluded from XML" category, and it's not a child
			// of that category.
			if ( $cat_name != $exclusion_cat_name ) {
				$title = Title::newFromText( $cat_name, NS_CATEGORY );
				$parent_categories = $title->getParentCategoryTree( array() );
				if ( ! self::treeContainsElement( $parent_categories, $exclusion_cat_full_name ) )
					$categories[] = $cat_name;
			}
		}
		$dbr->freeResult( $res );
		sort( $categories );
		return $categories;
	}

	static function getNamespacesList() {
		$dbr = wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );
		$res = $dbr->query( "SELECT DISTINCT page_namespace FROM $page" );
		$namespaces = array();
		while ( $row = $dbr->fetchRow( $res ) ) {
			$namespaces[] = $row[0];
		}
		$dbr->freeResult( $res );
		return $namespaces;
	}

	static function getGroupings() {
		global $smwgIP;

		if ( ! isset( $smwgIP ) ) {
			return array();
		} else {
			$groupings = array();
			$store = smwfGetStore();
			// SMWDIProperty was added in SMW 1.6
			if ( class_exists( 'SMWDIProperty' ) ) {
				$grouping_prop = SMWDIProperty::newFromUserLabel( '_DT_XG' );
			} else {
				$grouping_prop = SMWPropertyValue::makeProperty( '_DT_XG' );
			}
			$grouped_props = $store->getAllPropertySubjects( $grouping_prop );
			foreach ( $grouped_props as $grouped_prop ) {
				$res = $store->getPropertyValues( $grouped_prop, $grouping_prop );
				$num = count( $res );
				if ( $num > 0 ) {
					if ( class_exists( 'SMWDIProperty' ) ) {
						$grouping_label = $res[0]->getSortKey();
					} else {
						$grouping_label = $res[0]->getShortWikiText();
					}
					$groupings[] = array( $grouped_prop, $grouping_label );
				}
			}
			return $groupings;
		}
	}

	static function getSubpagesForPageGrouping( $page_name, $relation_name ) {
		$dbr = wfGetDB( DB_SLAVE );
		$smw_relations = $dbr->tableName( 'smw_relations' );
		$smw_attributes = $dbr->tableName( 'smw_attributes' );
		$res = $dbr->query( "SELECT subject_title FROM $smw_relations WHERE object_title = '$page_name' AND relation_title = '$relation_name'" );
		$subpages = array();
		while ( $row = $dbr->fetchRow( $res ) ) {
			$subpage_name = $row[0];
			$query_subpage_name = str_replace( "'", "\'", $subpage_name );
			// get the display order
			$res2 = $dbr->query( "SELECT value_num FROM $smw_attributes WHERE subject_title = '$query_subpage_name' AND attribute_title = 'Display_order'" );
			if ( $row2 = $dbr->fetchRow( $res2 ) ) {
				$display_order = $row2[0];
			} else {
				$display_order = - 1;
			}
			$dbr->freeResult( $res2 );
			// HACK - page name is the key, display order is the value
			$subpages[$subpage_name] = $display_order;
		}
		$dbr->freeResult( $res );
		uasort( $subpages, "cmp" );
		return array_keys( $subpages );
	}


	/*
	 * Get all the pages that belong to a category and all its
	 * subcategories, down a certain number of levels - heavily based
	 * on SMW's SMWInlineQuery::includeSubcategories()
	 */
	static function getPagesForCategory( $top_category, $num_levels ) {
		if ( 0 == $num_levels ) return $top_category;

		$db = wfGetDB( DB_SLAVE );
		$fname = "getPagesForCategory";
		$categories = array( $top_category );
		$checkcategories = array( $top_category );
		$titles = array();
		for ( $level = $num_levels; $level > 0; $level-- ) {
			$newcategories = array();
			foreach ( $checkcategories as $category ) {
				$res = $db->select( // make the query
					array( 'categorylinks', 'page' ),
					array( 'page_id', 'page_title', 'page_namespace' ),
					array( 'cl_from = page_id',
						'cl_to = ' . $db->addQuotes( $category )
					),
					$fname
				);
				if ( $res ) {
					while ( $row = $db->fetchRow( $res ) ) {
						if ( array_key_exists( 'page_title', $row ) ) {
							$page_namespace = $row['page_namespace'];
							if ( $page_namespace == NS_CATEGORY ) {
								$new_category = $row[ 'page_title' ];
								if ( !in_array( $new_category, $categories ) ) {
									$newcategories[] = $new_category;
								}
							} else {
								$titles[] = Title::newFromID( $row['page_id'] );
							}
						}
					}
					$db->freeResult( $res );
				}
			}
			if ( count( $newcategories ) == 0 ) {
				return $titles;
			} else {
				$categories = array_merge( $categories, $newcategories );
			}
			$checkcategories = array_diff( $newcategories, array() );
		}
		return $titles;
	}

	static function getPagesForNamespace( $namespace ) {
		$dbr = wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );
		$res = $dbr->query( "SELECT page_id FROM $page WHERE page_namespace = '$namespace'" );
		$titles = array();
		while ( $row = $dbr->fetchRow( $res ) ) {
			$titles[] = Title::newFromID( $row[0] );
		}
		$dbr->freeResult( $res );
		return $titles;
	}

	/**
	 * Helper function for getXMLForPage()
	 */
	static function treeContainsElement( $tree, $element ) {
		// escape out if there's no tree (i.e., category)
		if ( $tree == null ) {
			return false;
		}

		foreach ( $tree as $node => $child_tree ) {
			if ( $node === $element ) {
				return true;
			} elseif ( count( $child_tree ) > 0 ) {
				if ( self::treeContainsElement( $child_tree, $element ) ) {
					return true;
				}
			}
		}
		// no match found
		return false;
	}


	static function getXMLForPage( $title, $simplified_format, $groupings, $depth = 0 ) {
		if ( $depth > 5 ) { return ""; }

		global $wgContLang, $dtgContLang;

		// if this page belongs to the exclusion category, exit
		$parent_categories = $title->getParentCategoryTree( array() );
		$dt_props = $dtgContLang->getPropertyLabels();
		// $exclusion_category = $title->newFromText($dt_props[DT_SP_IS_EXCLUDED_FROM_XML], NS_CATEGORY);
		$exclusion_category = $wgContLang->getNSText( NS_CATEGORY ) . ':' . str_replace( ' ', '_', $dt_props[DT_SP_IS_EXCLUDED_FROM_XML] );
		if ( self::treeContainsElement( $parent_categories, $exclusion_category ) ) {
			return "";
		}

		$pageStructure = DTPageStructure::newFromTitle( $title );
		$text = $pageStructure->toXML( $simplified_format );

		// handle groupings, if any apply here; first check if SMW is installed
		global $smwgIP;
		if ( isset( $smwgIP ) ) {
			$store = smwfGetStore();
			$page_title = $title->getText();
			$page_namespace = $title->getNamespace();
			// Escaping is needed for SMWSQLStore3 - this may be a bug in SMW.
			$escaped_page_title = str_replace( ' ', '_', $page_title );
			foreach ( $groupings as $pair ) {
				list( $property_page, $grouping_label ) = $pair;
				$options = new SMWRequestOptions();
				$options->sort = "subject_title";
				// get actual property from the wiki-page of the property
				if ( class_exists( 'SMWDIProperty' ) ) {
					$wiki_page = new SMWDIWikiPage( $escaped_page_title, $page_namespace, null );
					$property = SMWDIProperty::newFromUserLabel( $property_page->getTitle()->getText() );
				} else {
					$wiki_page = SMWDataValueFactory::newTypeIDValue( '_wpg', $escaped_page_title );
					$property = SMWPropertyValue::makeProperty( $property_page->getTitle()->getText() );
				}
				$res = $store->getPropertySubjects( $property, $wiki_page, $options );
				$num = count( $res );
				if ( $num > 0 ) {
					$grouping_label = str_replace( ' ', '_', $grouping_label );
					$text .= "<$grouping_label>\n";
					foreach ( $res as $subject ) {
						$subject_title = $subject->getTitle();
						$text .= self::getXMLForPage( $subject_title, $simplified_format, $groupings, $depth + 1 );
					}
					$text .= "</$grouping_label>\n";
				}
			}
		}

		// escape back the curly brackets that were escaped out at the beginning
		$text = str_replace( '&amp;#123;', '{', $text );
		$text = str_replace( '&amp;#125;', '}', $text );
		return $text;
	}

	static function doSpecialViewXML() {
		global $wgOut, $wgRequest, $wgContLang;

		$namespace_labels = $wgContLang->getNamespaces();
		$category_label = $namespace_labels[NS_CATEGORY];
		$name_str = str_replace( ' ', '_', wfMessage( 'dt_xml_name' )->inContentLanguage()->text() );
		$namespace_str = str_replace( ' ', '_', wfMessage( 'dt_xml_namespace' )->text() );
		$pages_str = str_replace( ' ', '_', wfMessage( 'dt_xml_pages' )->inContentLanguage()->text() );

		$form_submitted = false;
		$cats = $wgRequest->getArray( 'categories' );
		$nses = $wgRequest->getArray( 'namespaces' );
		$requestedTitles = $wgRequest->getVal( 'titles' );
		if ( count( $cats ) > 0 || count( $nses ) > 0 || $requestedTitles != null ) {
			$form_submitted = true;
		}

		if ( $form_submitted ) {
			$wgOut->disable();

			// Cancel output buffering and gzipping if set
			// This should provide safer streaming for pages with history
			wfResetOutputBuffers();
			header( "Content-type: application/xml; charset=utf-8" );

			$groupings = self::getGroupings();
			$simplified_format = $wgRequest->getVal( 'simplified_format' );
			$text = "<$pages_str>";
			if ( $cats ) {
				foreach ( $cats as $cat => $val ) {
					if ( $simplified_format )
						$text .= '<' . str_replace( ' ', '_', $cat ) . ">\n";
					else
						$text .= "<$category_label $name_str=\"$cat\">\n";
					$titles = self::getPagesForCategory( $cat, 10 );
					foreach ( $titles as $title ) {
						$text .= self::getXMLForPage( $title, $simplified_format, $groupings );
					}
					if ( $simplified_format ) {
						$text .= '</' . str_replace( ' ', '_', $cat ) . ">\n";
					} else {
						$text .= "</$category_label>\n";
					}
				}
			}

			if ( $nses ) {
				foreach ( $nses as $ns => $val ) {
			 		if ( $ns == 0 ) {
						$ns_name = "Main";
					} else {
						$ns_name = MWNamespace::getCanonicalName( $ns );
					}
					if ( $simplified_format ) {
						$text .= '<' . str_replace( ' ', '_', $ns_name ) . ">\n";
					} else {
						$text .= "<$namespace_str $name_str=\"$ns_name\">\n";
					}
					$titles = self::getPagesForNamespace( $ns );
					foreach ( $titles as $title ) {
						$text .= self::getXMLForPage( $title, $simplified_format, $groupings );
					}
					if ( $simplified_format )
						$text .= '</' . str_replace( ' ', '_', $ns_name ) . ">\n";
					else
						$text .= "</$namespace_str>\n";
				}
			}

			// The user can specify a set of page names to view
			// the XML of, using a "titles=" parameter, separated
			// by "|", in the manner of the MediaWiki API.
			// Hm... perhaps all of Special:ViewXML should just
			// be replaced by an API action?
			if ( $requestedTitles ) {
				$pageNames = explode( '|', $requestedTitles );
				foreach ( $pageNames as $pageName ) {
					$title = Title::newFromText( $pageName );
					$text .= self::getXMLForPage( $title, $simplified_format, $groupings );
				}
			}

			$text .= "</$pages_str>";
			print $text;
		} else {
			// set 'title' as hidden field, in case there's no URL niceness
			global $wgContLang;
			$mw_namespace_labels = $wgContLang->getNamespaces();
			$special_namespace = $mw_namespace_labels[NS_SPECIAL];
			$text = <<<END
	<form action="" method="get">
	<input type="hidden" name="title" value="$special_namespace:ViewXML">

END;
			$text .= "<p>" . wfMessage( 'dt_viewxml_docu' )->text() . "</p>\n";
			$text .= "<h2>" . wfMessage( 'dt_viewxml_categories' )->text() . "</h2>\n";
			$categories = self::getCategoriesList();
			foreach ( $categories as $category ) {
				$text .= Html::input( "categories[$category]", null, 'checkbox' );
				$title = Title::makeTitle( NS_CATEGORY, $category );
				$link = Linker::link( $title, htmlspecialchars( $title->getText() ) );
				$text .= " $link<br />\n";
			}
			$text .= "<h2>" . wfMessage( 'dt_viewxml_namespaces' )->text() . "</h2>\n";
			$namespaces = self::getNamespacesList();
			foreach ( $namespaces as $nsCode ) {
				if ( $nsCode === '0' ) {
					$nsName = wfMessage( 'blanknamespace' )->escaped();
				} else {
					$nsName = htmlspecialchars( $wgContLang->getFormattedNsText( $nsCode ) );
					if ( $nsName === '' ) continue;
				}
				$text .= Html::input( "namespaces[$nsCode]", null, 'checkbox' );
				$text .= ' ' . str_replace( '_', ' ', $nsName ) . "<br />\n";
			}
			$text .= "<br /><p><label><input type=\"checkbox\" name=\"simplified_format\" /> " . wfMessage( 'dt_viewxml_simplifiedformat' )->text() . "</label></p>\n";
			$text .= "<input type=\"submit\" value=\"" . wfMessage( 'viewxml' )->text() . "\">\n";
			$text .= "</form>\n";

			$wgOut->addHTML( $text );
		}
	}
}
