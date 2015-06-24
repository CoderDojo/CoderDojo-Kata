<?php

class SpecialKataNinjas extends SpecialPage {
	
	private $ninjaTabs = array(
		array('name' => 'Games', 'sectionID' => 'sectionGames', 'namespace' => NS_NINJA_GAME, 'parentCategory' => 'Ninja_Games', 'fetchResults' => 'fetchGenericResults', 'populationFunction' => 'populateGenericTab', 'defaultSelected' => true),
		array('name' => 'Tutorials', 'sectionID' => 'sectionTutorials', 'namespace' => NS_NINJA_TUTORIAL, 'parentCategory' => 'Ninja_Tutorials', 'fetchResults' => 'fetchGenericResults', 'populationFunction' => 'populateGenericTab', 'defaultSelected' => false),
		array('name' => 'Videos', 'sectionID' => 'sectionVideos', 'namespace' => NS_NINJA_VIDEO, 'parentCategory' => 'Featured_Video', 'fetchResults' => 'fetchVideoResults', 'populationFunction' => 'populateVideosTab', 'defaultSelected' => false)
	);
	
	private $categoryLimit = 18;
	private $videoPerPage = 10;
	private $paginatedVideos = array();
	private $parserTags = array("author", "description", "image", "level", "technology", "topic", "language", "sessions");
	private $parserMaxIncludeSize = 67108864;

	function __construct() {
		parent::__construct('KataNinjas');
	}

	function getQueryResources($namespace, $parentCategory, $letter = null) {
		//$query = $this->getQueryElementsByNamespace($namespace);
		$query = $this->getQueryElementsByCategory($namespace, $parentCategory);
		$query['options']['ORDER BY'] = 'cl2.cl_to';

		if (!empty($letter)) {
			array_push($query['conds'], "LCASE(CONVERT(cl2.cl_to using utf8)) like '".strtolower($letter)."%'");
		}
		return $query;
	}
	
	function getQueryElementsByCategory($namespace, $parentCategory) {
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
				"LCASE(CONVERT(cl1.cl_to using utf8)) = '".strtolower($parentCategory)."'", // limit parent category
				"kp2.page_title not like '%/%'", // don't consider subpages
				"kp2.page_is_redirect = '0'" // don't consider moved pages
			)
		);
	}
	
	function getTopElementsByNamespace($namespace, $limit = 5) {
		/*
			select
				w4g.avg as perc_rating, w4g.avg DIV 20 as rating, kp.page_title as title
			from
				`mw_page` as kp
				join
				`mw_w4grb_avg` as w4g
					on kp.page_id = w4g.pid
				where
					kp.page_namespace = ...
				order by w4g.avg desc
				limit 5
		*/
		return array (
			// 'options' => 'DISTINCT',
			'options' => array('ORDER BY' => 'w4g.avg DESC', 'LIMIT' => "$limit"),
			'fields' => array ( 'kp.page_id as id', 'w4g.avg as perc_rating', 'w4g.avg DIV 20 as rating', 'kp.page_title as title' ),
			'tables' => array ( 'kp' => 'page', 'w4g' => 'w4grb_avg' ),
			'join_conds' => array (
				'w4g' => array (
					'LEFT JOIN', 'kp.page_id = w4g.pid'
				)
			),
			'conds' => array (
				"kp.page_namespace = '$namespace'", // limit namespace
				"kp.page_title not like '%/%'", // don't consider subpages
				"kp.page_is_redirect = '0'" // don't consider moved pages
			)
		);
	}

	function getQueryElementsByNamespace($namespace) {
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
				"kp2.page_title not like '%/%'", // don't consider subpages
				"kp2.page_is_redirect = '0'" // don't consider moved pages
			)
		);
	}
	
	function getQueryVideos($namespace, $category = null, $featuredVideoID = null) {
		/*
			select
				kp.page_title as title
			from
				`mw_page` as kp
				join
				`mw_categorylinks` as cl
					on kp.page_id = cl.cl_from
				where
					kp.page_namespace = ...
					 and cl.cl_to = 'Featured_Video'
		*/
		$query = array (
			// 'options' => 'DISTINCT',
			'fields' => array ( 'kp.page_id as id', 'kp.page_title as title' ),
			'tables' => array ( 'kp' => 'page', 'cl' => 'categorylinks' ),
			'join_conds' => array (
				'cl' => array (
					'LEFT JOIN', 'kp.page_id = cl.cl_from'
				)
			),
			'conds' => array (
				"kp.page_namespace = '$namespace'", // limit namespace
				"kp.page_title not like '%/%'", // don't consider subpages
				"kp.page_is_redirect = '0'" // don't consider moved pages
			)
		);
		
		if(!empty($category)) {
			array_push($query['conds'], "LCASE(CONVERT(cl.cl_to using utf8)) = '".strtolower($category)."'");
		}
		if(!empty($featuredVideoID)) {
			array_push($query['conds'], "kp.page_id != '$featuredVideoID'");
		}
		
		return $query;
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
		ContextSource::getContext()->getTitle()->skinNamespace = NS_NINJA_RESOURCE;

		$request = $this->getRequest();
		$out = $this->getOutput();
		$this->setHeaders();

		$out->setPageTitle("Kata for Ninjas");

		# Get request data from, e.g.
		$useskin = $request->getText( 'useskin' );
		$selectedSection = $request->getText( 'section' );
		
		if(!empty($selectedSection) && strlen($selectedSection) > 0) {
			for($i=0; $i < count($this->ninjaTabs); $i++) {
				$this->ninjaTabs[$i]['defaultSelected'] = ($this->ninjaTabs[$i]['sectionID'] == $selectedSection);
			}
		}

		# Do stuff
		# ...

		$dbr = wfGetDB( DB_SLAVE );

		// this div is created in the skin :)
		$out->addHTML(
			Html::openElement( 'div', array( 'class' => 'kata-ninjas' ) ). "\n"
		);
		$out->addHTML(
			Html::openElement('div', array( 'class' => 'row kata-category-title')). "\n".
			Html::openElement('div', array( 'class' => 'col-xs-12')). "\n".
			Html::element('h2', array(), 'Kata for Ninjas').
			Html::closeElement( 'div' )."\n".
			Html::closeElement( 'div' )."\n".
			Html::openElement('div', array( 'style' => 'margin-top: 2rem;')). "\n"
		);
		
		$this->printTabHeaders($out);
		
		$out->addHTML(Html::openElement('div', array( 'class' => 'tab-content')). "\n");
		
		foreach($this->ninjaTabs as $tab) {
			$items = $this->$tab['fetchResults']($dbr, $tab);
			$this->$tab['populationFunction']($out, $tab, $items);
		}
		
		$out->addHTML(
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n"
		);

		// close div.kata-mentors
		$out->addHTML(
			Html::closeElement( 'div' )
		);
	}
	
	function printTabHeaders($out) {
		$out->addHTML(Html::openElement('ul', array( 'class' => 'nav nav-tabs')). "\n");
		
		foreach($this->ninjaTabs as $tab) {
			$out->addHTML(
				Html::openElement('li', array( 'class' => ($tab['defaultSelected'] ? 'active' : ''))). "\n".
				Html::element('a', array( 'data-toggle' => 'tab', 'href' => '#'.$tab['sectionID']), $tab['name']). "\n".
				Html::closeElement('li'). "\n"
			);
		}
		
		$out->addHTML(Html::closeElement('ul'). "\n");
	}
	
	function printVideoPagination($out, $totalPages, $selectedPage, $tab) {
		$urlParams = $this->getRequest()->getQueryValues();
		unset($urlParams['title']);
		$urlParams['page'] = ($selectedPage-1);
		$urlParams['section'] = $tab['sectionID']; 
		$out->addHTML(
			Html::openElement('ul', array('class' => 'pagination')). "\n".
			Html::openElement('li', array('class' => $selectedPage > 1 ? null : 'disabled')). "\n".
			// Html::element('a', array('href' => $selectedPage > 1 ? '?'.http_build_query($urlParams) : null), '«'). "\n".
			(
				$selectedPage > 1 ?
				Linker::link(SpecialPage::getTitleFor($this->getName()),
								'«',
								array(),
								$urlParams,
								array()
							)
				:
				Html::element('span', null, '«')
			).
			Html::closeElement('li')
		);
		
		for($i = 1; $i <= $totalPages; $i++) {
			$urlParams['page'] = $i;
			$out->addHTML(
				Html::openElement('li', array('class' => $selectedPage == $i ? 'active' : '')). "\n".
				// Html::element('a', array('href' => '?'.http_build_query($urlParams)), "$i"). "\n".
				Linker::link(SpecialPage::getTitleFor($this->getName()),
								"$i",
								array(),
								$urlParams,
								array()
							).
				Html::closeElement('li')
			);
		}
		$urlParams['page'] = ($selectedPage+1);
		$out->addHTML(
			Html::openElement('li', array('class' => $selectedPage < $totalPages ? null : 'disabled')). "\n".
			// Html::element('a', array('href' => $selectedPage < $totalPages ? '?'.http_build_query($urlParams) : null), '»'). "\n".
			(
				$selectedPage < $totalPages ?
				Linker::link(SpecialPage::getTitleFor($this->getName()),
								'»',
								array(),
								$urlParams,
								array()
							)
				:
				Html::element('span', null, '»')
			).
			Html::closeElement('li'). "\n".
			Html::closeElement('ul')
		);
	}
	
	function populateGenericTab($out, $tab, $items) {
		$out->addHTML(Html::openElement('div', array('id' => $tab['sectionID'], 'class' => 'tab-pane fade'.($tab['defaultSelected'] ? ' in active' : ''))). "\n");
		$this->printTop5Section($out, $tab, $items['topSection']);
		$this->printAlphabetSection($out, $tab, $items['alphabetSection'], $items['availableLetters']);
		$out->addHTML(Html::closeElement('div'). "\n");
	}
	
	function printTop5Section($out, $tab, $items) {
		$out->addHTML(
			Html::openElement('div', array('class' => 'panel-body')). "\n".
			Html::openElement('div', array('class' => 'row kata-box')). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-title')). "\n".
			Html::element('h4', array(), 'Top 5 '.$tab['name']). "\n".
			Html::closeElement('div'). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-content')). "\n"
		);
		
		for($j=0; $j < count($items); $j++) {
			$item = $items[$j];
			$out->addHTML(
				Html::openElement('div', array('class' => 'col-md-4 col-xs-12 video-block')). "\n".
				Html::element('div', array('class' => 'title'), ''.($j + 1).'. '.$item['name']). "\n".
				Html::openElement('div', array('style' => 'display: inline-block;')). "\n".
				Html::openElement('span', array('class' => 'kata-rating kata-rating-'.$item['rating'])). "\n".
				Html::element('img', array('class' => 'ninja-images', 'src' => $item['imageUrl'])). "\n".
				Html::closeElement('span'). "\n".
				Html::openElement('div', array('class' => 'link')). "\n".
				Html::element('a', array('href' => $item['link']), 'Link to game'). "\n".
				Html::closeElement('div'). "\n".
				Html::closeElement('div'). "\n".
				Html::closeElement('div'). "\n"
			);
		}
		
		$out->addHTML(
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n"
		);
	}
	
	function printAlphabetSection($out, $tab, $items, $availableLetters) {
		$out->addHTML(
			Html::openElement('div', array('class' => 'panel-body')). "\n".
			Html::openElement('div', array('class' => 'row kata-box')). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-title')). "\n".
			Html::element('h4', array(), 'More '.$tab['name']). "\n".
			Html::closeElement('div'). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-content')). "\n".
			Html::openElement('div', array('class' => 'alpha-filter')). "\n"
		);
		
		$toBeFiltered = count($items) > $this->categoryLimit;
		if($toBeFiltered) {
			$out->addHTML( Html::openElement('div', array('class' => 'filters clearfix')). "\n" );
			
			$letters = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
			$letter = $this->getRequest()->getText('letter');
			if (empty($letter)) {
				$letter = $letters[0];
			}
			foreach ($letters as $l) {
				$out->addHTML(Html::openElement('span', array('class' => 'letter'.($availableLetters[$l] ? (strtolower($l) == strtolower($letter) ? ' selected' : '') : ' disabled'), 'data-filter' => $l)));
				if ($availableLetters[$l]) {
					$out->addHTML(
						Linker::link(SpecialPage::getTitleFor($this->getName()),
							$l,
							array('title' => strtoupper($l)),
							array(),
							array()
						)
					);
				} else {
					$out->addHTML(
							Html::element('a', array(), $l)
					);
				}
				$out->addHTML(Html::closeElement('span'));
			}
			
			$out->addHTML( Html::closeElement('div'). "\n" );
		}
		
		$out->addHTML(
			Html::openElement('div', array('class' => 'kata-tags'.($toBeFiltered ? ' filter-elements' : ''))). "\n"
		);
		
		foreach($items as $item) {
			$attrs = array('class' => 'kata-tag'.($toBeFiltered ? ' filter-elements' : ''));
			if ($toBeFiltered) {
				$attrs['data-filter'] = $item['filter'];
			}
			$out->addHTML(
				Html::openElement('div', $attrs). "\n".
				Linker::link($item['link']['title'],
					' '.$item['name'].Html::element('span', array(), $item['count']),
					$item['link']['customAttribs'],
					$item['link']['query'],
					$item['link']['options']
				).
				Html::closeElement('div'). "\n"
			);
		}
		
		$out->addHTML(
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n"
		);
	}
	
	function fetchGenericResults($dbr, $tab) {
		$possibilities = array();
		$availableLetters = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
		foreach ($availableLetters as $l) {
			$availableLetters[$l] = false;
		}
		$res = $this->executeQuery($dbr, $this->getQueryResources($tab['namespace'], $tab['parentCategory']));
		while ($obj = $dbr->fetchObject($res)) {
			$l = strtoupper($obj->cat_title{0});
			$l = ereg_replace('\\W', '0', $l);
			$availableLetters[$l] = true;
			array_push($possibilities, array(
				'name' => str_replace('_', ' ', $obj->cat_title),
				'link' => array(
					'title' => SpecialPage::getTitleFor('KataNinjasTutorialList'),
					'customAttribs' => array(),
					'query' => array('category' => $obj->cat_title),
					'options' => array()
				),
				'filter' => $l,
				'count' => (empty($obj->pages) ? 0 : $obj->pages)
			));
		}
		
		$topElements = array();
		$res = $this->executeQuery($dbr, $this->getTopElementsByNamespace($tab['namespace']));
		$courseSpecialTitle = SpecialPage::getTitleFor('KataCourse');
		while ($obj = $dbr->fetchObject($res)) {
			$templateData = $this->getTemplateData($obj->id);
			
			array_push($topElements, array(
				'name' => str_replace('_', ' ', $obj->title),
				'imageUrl' => $this->getImageUrl($templateData['image']), //'http://placekitten.com/g/400/250',
				'rating' => !is_null($obj->rating) ? $obj->rating : 0,
				'link' => $courseSpecialTitle->getLinkURL(array('page' =>  $GLOBALS["wgExtraNamespaces"][$tab['namespace']].':'.str_replace('_', ' ', $obj->title)))
			));
		}
		
		return array(
			'topSection' => $topElements,
			'availableLetters' => $availableLetters,
			'alphabetSection' => $possibilities//[$letter] ? $possibilities[$letter] : array()
		);
	}
	
	function populateVideosTab($out, $tab, $items) {
		$out->addHTML(Html::openElement('div', array('id' => $tab['sectionID'], 'class' => 'tab-pane fade'.($tab['defaultSelected'] ? ' in active' : ''))). "\n");
		
		//Featured Video
		$fvideo = $items['featuredVideo'];
		$out->addHTML(
			Html::openElement('div', array('class' => 'panel-body')). "\n".
			Html::openElement('div', array('class' => 'row kata-box')). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-title')). "\n".
			Html::element('h4', array(), 'Featured Video'). "\n".
			Html::closeElement('div'). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-content')). "\n".
			$this->getVideoHTML($fvideo, 'featured-video', 600, 400).
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n"
		);//End Featured Video
		
		//More Videos
		$pageNumber = filter_var($this->getRequest()->getText( 'page' ) ? $this->getRequest()->getText( 'page' ) : 1, FILTER_VALIDATE_INT);
		$videos = $this->getVideosInPage($items['moreVideos'], $pageNumber);
		$out->addHTML(
			Html::openElement('div', array('class' => 'panel-body')). "\n".
			Html::openElement('div', array('class' => 'row kata-box')). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-title')). "\n".
			Html::element('h4', array(), 'More Videos'). "\n".
			Html::closeElement('div'). "\n".
			Html::openElement('div', array('class' => 'col-xs-12 kata-box-content')). "\n"
		);
		
		$this->printVideoPagination($out, $this->getTotalPages($items['moreVideos']), $pageNumber, $tab);
		
		foreach($videos as $video) {
			$out->addHTML($this->getVideoHTML($video, 'more-video', 400, 250));
		}
		
		$this->printVideoPagination($out, $this->getTotalPages($items['moreVideos']), $pageNumber, $tab);
		
		$out->addHTML(
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n".
			Html::closeElement('div'). "\n"
		);//End More Videos
		
		$out->addHTML(Html::closeElement('div'). "\n");
	}
	
	function getVideoHTML($video, $videoClass, $videoWidth, $videoHeight) {
		return Html::openElement('div', array('class' => "kata-tags single-column $videoClass")). "\n".
				Html::openElement('div', array('class' => 'description')). "\n".
				Html::element('div', array('class' => 'title'), $video['title']). "\n".
				Html::element('div', array(), $video['description']). "\n".
				Html::closeElement('div'). "\n".
				Html::openElement('div', array('class' => 'preview')). "\n".
				$this->getVideoElement($video['service'], $video['previewLink'], $videoWidth, $videoHeight). "\n".
				Html::closeElement('div'). "\n".
				Html::closeElement('div'). "\n";
	}
	
	function fetchVideoResults($dbr, $tab) {
		$featuredVideoID = null;
		$featuredVideo = array();
		$res = $this->executeQuery($dbr, $this->getQueryVideos($tab['namespace'], $tab['parentCategory']));
		if($obj = $dbr->fetchObject($res)) {
			$featuredVideoID = $obj->id;
			$templateData = $this->getTemplateData($obj->id);
			
			$featuredVideo = array(
				'title' => str_replace('_', ' ', $obj->title),
				'description' => $templateData['description'],
				'previewLink' => $templateData['link'],
				'service' => 'youtube' // Will we add more services?
			);
		}
		
		$moreVideos = array();
		$res = $this->executeQuery($dbr, $this->getQueryVideos($tab['namespace'], null, $featuredVideoID));
		while($obj = $dbr->fetchObject($res)) {
			$templateData = $this->getTemplateData($obj->id);
			
			array_push($moreVideos, array(
				'title' => str_replace('_', ' ', $obj->title),
				'description' =>  $templateData['description'],
				'previewLink' => $templateData['link'],
				'service' => 'youtube' // Will we add more services?
			));
		}
		
		return array(
			'featuredVideo' => $featuredVideo,
			'moreVideos' => $moreVideos
		);
	}
	
	function getVideosInPage($allVideos, $pageNumber) {
		$paginatedVideos = $this->paginateVideos($allVideos);
		if(!is_int($pageNumber) || count($paginatedVideos) < $pageNumber || $pageNumber < 1) {
			return array();
		}
		
		return $paginatedVideos[$pageNumber - 1];
	}
	
	function getTotalPages($allVideos) {
		return count($this->paginateVideos($allVideos));
	}
	
	function paginateVideos($videos) {
		if($this->paginatedVideos) {
			return $this->paginatedVideos;
		}
		
		if(count($videos) <= $this->videoPerPage) {
			return array($videos);
		}
		$page = array_slice($videos, 0, $this->videoPerPage);
		$remainingPages = $this->paginateVideos(array_slice($videos, $this->videoPerPage));
		$this->paginatedVideos = array_merge(array($page), $remainingPages);
		return $this->paginatedVideos;
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
			if (isset($tag['linkParams'])) {
				$out->addHTML(
					Linker::link($tag['linkParams']['title'],
						' '.
						$tag['title'].Html::element('span', array(), $tag['count']),
						$tag['linkParams']['customAttribs'],
						$tag['linkParams']['query'],
						$tag['linkParams']['options']
					)
				);
			} else {
				$url = (isset($tag['url'])) ? $tag['url'] : "";
				$out->addHtml(
					Html::openElement('a', array('href' => $url)).
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
	
	function getTemplateData($id){
		$wikiPage = WikiPage::newFromID($id);
		$parserOptions = new ParserOptions();
		$wikiPageContent = $wikiPage->getParserOutput($parserOptions);
		
		if(!is_object($wikiPageContent))
			return $this->handlePageNotFound();
		
		return $this->parseTemplateData($wikiPage);
	}
	
	function initTemplateDataArray(){
		$data = array();
		foreach ($this->parserTags as $value)
			$data[$value] = "";
		
		return $data;
	}
	
	function parseTemplateData($wikiPage){
		global $wgParser;
		
		$doc = new DOMDocument();
		$data = $this->initTemplateDataArray();
		$options = null;
		$xml = null;
		$namespaceText = str_replace("_", " ", $wikiPage->getTitle()->getNsText());
		
		$options = ParserOptions::newFromContext($this->context);
		$options->setRemoveComments(false);
		$options->setTidy(true);
		$options->setMaxIncludeSize($this->parserMaxIncludeSize);
	
		$wgParser->startExternalParse($wikiPage->getTitle(), $options, OT_PREPROCESS);
		$dom = $wgParser->preprocessToDom($wikiPage->getText());
		$xml = (method_exists( $dom, 'saveXML')) ? $dom->saveXML() : $dom->__toString();
	
		$doc->preserveWhiteSpace = false;
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		
		$templates = $xpath->query('//root/template', $doc);
		foreach ($templates as $template) {
			$index = 0;
			$data = $this->initTemplateDataArray();
			
			$templateNodeName = $xpath->query('title', $template);
			if($templateNodeName->length < 1)
				continue;
				
			$templateFullName = $templateNodeName->item(0)->nodeValue;
			$templateFullName = explode(":", $templateFullName);
			
			if(!is_array($templateFullName) || empty($templateFullName) || $templateFullName[0] != $namespaceText)
				continue;
			
			$names = $xpath->query( 'part/name', $template );
			$values = $xpath->query( 'part/value', $template );
				
			foreach ($names as $name) {
				if(in_array(trim($name->nodeValue), $this->parserTags))
					$data[trim($name->nodeValue)] = $values->item($index)->nodeValue;
					
				$index++;
			}
			
			$data = array_map('trim', $data);
		}
	
		return $data;
	}
	
	function getImageUrl($imageName) {
		$image = 'http://placekitten.com/g/600/400';
		if(filter_var($imageName, FILTER_VALIDATE_URL)) {
			$image = $imageName;
		} else if($imageName) {
			$image = preg_match('/^File:/i', $imageName) ? wfLocalFile($imageName)->getFullUrl() : 'File:'.wfLocalFile($imageName)->getFullUrl();
		}
		return $image;
	}
	
	function getVideoElement($videoService, $videoUrl, $width, $height) {
		$videoService = EmbedVideo\VideoService::newFromName($videoService);
		$videoService->setVideoID($videoUrl);
		$videoService->setWidth($width);
		$videoService->setHeight($height);
		
		return $videoService->getHtml() ? $videoService->getHtml() : HTML::element('img', array('src' => "http://placekitten.com/g/$width/$height"));
	}
}

?>