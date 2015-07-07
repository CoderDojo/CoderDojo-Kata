<?php

// class SpecialKataMentors extends PageQueryPage {
class SpecialKataMentors extends SpecialPage {
	private $parentCategory = 'KataMentors';

	function __construct() {
		parent::__construct('KataMentors');
	}


	function getQuerySoftwares($letter = null) {
		$query = $this->getQueryTutorialsByCategory(NS_MENTOR_COURSE, "Technology");
		$query['options']['ORDER BY'] = 'cl2.cl_to';

		if (!empty($letter)) {
			array_push($query['conds'], "cl2.cl_to like '$letter%'");
		}
		return $query;
	}


	function getQueryTutorialsByCategory($namespace, $parentCategory) {
		/*
			select
				cl2.cl_to as cat_title, count(cl2.cl_from) as pages
			from
				`mw_categorylinks` as cl1
				join
				`mw_page` as kp1
					on cl1.cl_from = kp1.page_id and cl1.cl_type = 'subcat'
				join
				`mw_categorylinks` as cl2
					on cl2.cl_to = kp1.page_title and cl2.cl_type = 'page'
				join
				`mw_page` as kp2
					on cl2.cl_from = kp2.page_id
				where
					kp2.page_namespace = ...
					 and cl1.cl_to = 'Topic'
				group by cl2.cl_to
		*/
		return array (
			// 'options' => 'DISTINCT',
			'options' => array('GROUP BY' => 'cl2.cl_to'),
			'fields' => array ( 'cl2.cl_to as cat_title', 'count(cl2.cl_from) as pages' ),
			'tables' => array ( 'cl1' => 'categorylinks', 'kp1' => 'page', 'cl2' => 'categorylinks', 'kp2' => 'page' ),
			'join_conds' => array (
				'kp1' => array (
					'INNER JOIN', 'cl1.cl_from = kp1.page_id and cl1.cl_type = \'subcat\''
				),
				'cl2' => array (
					'LEFT JOIN', 'cl2.cl_to = kp1.page_title and cl2.cl_type = \'page\'' ),
				'kp2' => array(
					'LEFT JOIN', 'cl2.cl_from = kp2.page_id'
				)
			),
			'conds' => array (
				"kp2.page_namespace = '$namespace'", // limit namespace
				"cl1.cl_to = '$parentCategory'", // limit parent category
				"kp2.page_title not like '%/%'" // don't consider subpages
			),
		);
	}

	// public function formatResult( $skin, $row ) {
	// 	global $wgContLang;

	// 	$title = Title::makeTitleSafe( $row->namespace, $row->title );

	// 	if ( $title instanceof Title ) {
	// 		$text = $wgContLang->convert( $title->getPrefixedText() );
	// 		return Linker::linkKnown( $title, htmlspecialchars( $text ) );
	// 	} else {
	// 		return Html::element( 'span', array( 'class' => 'mw-invalidtitle' ),
	// 			Linker::getInvalidTitleDescription( $this->getContext(), $row->namespace, $row->title ) );
	// 	}
	// }

	function execute($subPage) {
		ContextSource::getContext()->getTitle()->skinNamespace = NS_TECHNICAL_RESOURCE;

		$request = $this->getRequest();
		$out = $this->getOutput();
		$this->setHeaders();

		$out->setPageTitle("Kata for Mentors");

		# Get request data from, e.g.
		$type = $request->getText( 'type' );
		$cat_name = $request->getText( 'cat_name' );
		// print_r($type);
		// print_r($cat_name);


		# Do stuff
		# ...

		$dbr = wfGetDB( DB_SLAVE );

		// this div is created in the skin :)
		// $out->addHTML(
		// 	Html::openElement( 'div', array( 'class' => 'kata-mentors' ) ). "\n"
		// );
		// $out->addHTML(
		// 	Html::openElement('div', array( 'class' => 'row kata-category-title')). "\n".
		// 	Html::openElement('div', array( 'class' => 'col-xs-12')). "\n".
		// 	Html::element('h2', array(), 'Kata for Mentors').
		// 	Html::closeElement( 'div' )."\n".
		// 	Html::closeElement( 'div' )."\n"
		// );


		// BEGIN kata-box languages
		/*
		$this->openKataBox($out, 'Tutorials in different languages');
		$this->printTags($out, array(
			array('title' => 'I\'m', 'count' => 123),
			array('title' => 'clueless', 'count' => 234),
			array('title' => 'on', 'count' => 345),
			array('title' => 'this', 'count' => 456),
			array('title' => 'part', 'count' => 567),
			array('title' => ':(', 'count' => 678)
		));
		$this->closeKataBox($out);
		*/
		// END kata-box languages

		$KataMentorsTutorialListTitle = SpecialPage::getTitleFor('KataMentorsTutorialList');
		// BEGIN kata-box categories
		$res = $this->executeQuery($dbr, $this->getQueryTutorialsByCategory(NS_MENTOR_COURSE, "Topic"));
		$tags = array();
		while ($obj = $dbr->fetchObject($res)) {
			// print_r($obj);
			array_push($tags, array(
				'title' => str_replace('_', ' ', $obj->cat_title),
				'linkParams' => array(
					'title' => $KataMentorsTutorialListTitle,
					'customAttribs' => array(),
					'query' => array('category' => $obj->cat_title),
					'options' => array()
				),
				'count' => (empty($obj->pages) ? 0 : $obj->pages) // not using cat_pages 'cause same category could be used in a different namespace
			));
		}
		$this->openKataBox($out, 'Categories');
		$this->printTags($out, $tags);
		$this->closeKataBox($out);
		// END kata-box categories

		// BEGIN kata-box alpha-filter
		$letter = $request->getText('letter');
		$letters = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		$tags = array();
		if (empty($letter)) {
			$letter = $letters[0];
		}
		$availableLetters = array();
		foreach ($letters as $l) {
			$availableLetters[$l] = false;
		}
		// if (!empty($letter)) {
			// $res = $this->executeQuery($dbr, $this->getQuerySoftwares($letter));
			$res = $this->executeQuery($dbr, $this->getQuerySoftwares());
			while ($obj = $dbr->fetchObject($res)) {
				// print_r($obj);
				$l = strtoupper($obj->cat_title{0});
				$l = ereg_replace('\\W', '0', $l);
				if ($availableLetters[$l] === false) {
					$availableLetters[$l] = 0;
				}
				$availableLetters[$l] += intval($obj->pages); // count pages
				// $availableLetters[$l]++; // count categories
				array_push($tags, array(
					'title' => str_replace('_', ' ', $obj->cat_title),
					'linkParams' => array(
						'title' => $KataMentorsTutorialListTitle,
						'customAttribs' => array(),
						'query' => array('category' => $obj->cat_title),
						'options' => array()
					),
					'filter' => $l,
					'count' => (empty($obj->pages) ? 0 : $obj->pages) // not using cat_pages 'cause same category could be used in a different namespace
				));
			}
		// }
		$this->openKataBox($out, 'Software by Alphabet');
		$out->addHTML(Html::openElement('div', array('class'=>'alpha-filter')));
		$out->addHTML(Html::openElement('div', array('class'=>'filters clearfix')));
		foreach ($letters as $l) {
			$out->addHTML(Html::openElement('span', array('class' => 'letter'.($availableLetters[$l] ? ($l == $letter ? ' selected' : '') : ' disabled'), 'data-filter' => $l)));
			if ($availableLetters[$l] !== false) {
				$out->addHTML(
						Linker::link(SpecialPage::getTitleFor($this->getName()),
							$l,
							array('title' => "There ".($availableLetters[$l] == 1 ? "is" : "are")." ".$availableLetters[$l]." course".($availableLetters[$l] == 1 ? "" : "s")." in categories starting with letter ".strtoupper($l)),
							// array('letter' => $l),
							array(),
							array()
						));
			} else {
				$out->addHTML(
						Html::element('a', array('title' => "At the moment there are no courses in categories starting with letter ".strtoupper($l)), $l)
				);
			}
			$out->addHTML(Html::closeElement('span'));
		}
		$out->addHTML(Html::closeElement('div'));
		$this->printTags($out, $tags, true);
		$out->addHTML(Html::closeElement('div'));
		$this->closeKataBox($out);
		// END kata-box alpha-filter


		// close div.kata-mentors
		// $out->addHTML(
		// 	Html::closeElement( 'div' )
		// );
	}

	function executeQuery($dbr, $query) {
		$fname = get_class( $this ) . "::getPages";
		$tables = isset( $query['tables'] ) ? (array)$query['tables'] : array();
		$fields = isset( $query['fields'] ) ? (array)$query['fields'] : array();
		$conds = isset( $query['conds'] ) ? (array)$query['conds'] : array();
		$options = isset( $query['options'] ) ? (array)$query['options'] : array();
		$join_conds = isset( $query['join_conds'] ) ? (array)$query['join_conds'] : array();

		$res = $dbr->select(
			$tables, $fields, $conds, $fname,
			$options, $join_conds
		);
		return $res;
	}

	function printTags($out, $tags, $filterable = false) {
		$out->addHTML(
			Html::openElement('div', array( 'class' => 'kata-tags'.($filterable ? ' filter-elements' : '') ) )
		);
		foreach ($tags as $tag) {
			$attrs = array('class' => 'kata-tag'.($filterable ? ' filter-elements' : ''));
			if ($filterable) {
				$attrs['data-filter'] = $tag['filter'];
			}
			$out->addHTML(
				Html::openElement('div', $attrs )
			);
			$linkTitle = "There ".($tag['count'] == 1 ? "is" : "are")." ".$tag['count']." course".($tag['count'] == 1 ? "" : "s")." in this category";
			if (isset($tag['linkParams'])) {
				$out->addHTML(
					Linker::link($tag['linkParams']['title'],
						' '.
						$tag['title'].Html::element('span', array(), $tag['count']),
						array_merge($tag['linkParams']['customAttribs'], array('title' => $linkTitle)),
						$tag['linkParams']['query'],
						$tag['linkParams']['options']
					)
				);
			} else {
				$url = (isset($tag['url'])) ? $tag['url'] : "";
				$out->addHtml(
					Html::openElement('a', array('href' => $url, 'title' => $linkTitle)).
					$tag['title'].
					' '.
					Html::element('span', array(), $tag['count']).
					Html::closeElement('a')
				);
			}
			$out->addHTML(
				Html::closeElement('div')
			);
		}
		$out->addHTML(
			Html::closeElement( 'div' ) // kata-tags
		);
	}

	function openKataBox($out, $boxTitle) {
		$out->addHTML(
			Html::openElement('div', array( 'class' => 'row kata-box' ) ) . "\n"
		);
		if (isset($boxTitle)) {
			$out->addHTML(
				Html::openElement('div', array( 'class' => 'col-xs-12 kata-box-title' ) ) .
				Html::element('h4', array(), $boxTitle).
				Html::closeElement( 'div' )
			);
		}
		$out->addHTML(
			Html::openElement('div', array( 'class' => 'col-xs-12 kata-box-content' ) )
		);
	}
	function closeKataBox($out) {
		$out->addHTML(
			Html::closeElement( 'div' ). // kata-box-content
			Html::closeElement( 'div' )  // kata-box
		);
	}
}

?>