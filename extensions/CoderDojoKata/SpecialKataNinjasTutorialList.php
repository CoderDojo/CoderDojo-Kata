<?php

// class SpecialKataMentors extends PageQueryPage {
class SpecialKataNinjasTutorialList extends SpecialPage {
	
	private $authorSize = 10;
	private $parserMaxIncludeSize = 67108864;
	private $parserTags = array("author", "description", "image", "level", "technology", "topic", "language", "sessions");
	/*
		validParentCategories needs to contains valid Parent Categories
		and they must be lowercase and must have ' ' (whitespaces) replaced with '_' (underscores)
	*/
	private $validParentCategories = array(
		'ninja_games',
		'ninja_tutorials'
	);
	
	private $pageSize = 10;
	private $paginatedTutorials = array();
	private $nspace = NS_NINJA_RESOURCE;

	function __construct() {
		parent::__construct('KataNinjasTutorialList');
	}


	function getQueryParentCategory($subCategory) {
		/*
			SELECT
				catlink.cl_to as parent_cat
			FROM
				`mw_page` as kpage
				inner join
				`mw_categorylinks` as catlink
				on kpage.page_id = catlink.cl_from
				where LCASE(CONVERT(kpage.page_title using utf8)) = 'html'
				AND LCASE(CONVERT(catlink.cl_to using utf8)) in ('technology', 'topic')
		*/
		$subCat = strtolower($subCategory);
		return array (
			'fields' => array ( 'catlink.cl_to as parent_cat', 'kpage.page_title as category' ),
			'tables' => array ( 'kpage' => 'page', 'catlink' => 'categorylinks' ),
			'join_conds' => array (
				'catlink' => array (
					'INNER JOIN', 'kpage.page_id = catlink.cl_from'
				)
			),
			'conds' => array ( 
				"LCASE(CONVERT(kpage.page_title using utf8)) = '$subCat'",
				"LCASE(CONVERT(catlink.cl_to using utf8)) in ('".join("','", $this->validParentCategories)."')"
			)
		);

	}

	function getQueryTutorialsByCategory($namespace, $categories, $rating, $sklvl) {
		/*
			select
				count(distinct cl.cl_to) as numCategories, kp.page_title as page_title, count(distinct pl.pl_title) as sessions, group_concat(DISTINCT allcat.cl_to separator ', ') as categories
			from
				`mw_categorylinks` as cl
				join
				`mw_page` as kp
					on cl.cl_from = kp.page_id and cl1.cl_type = 'page'
				join
				`mw_pagelinks` as pl
					on pl.pl_from = kp.page_id
				join
				`mw_categorylinks` as allcat
					on allcat.cl_from = kp.page_id
				where
					kp.page_namespace = '...' and cl.cl_to in ('HTML') and kp.page_title not like '%/%'
				group by kp.page_id
				having numCategories = 1
		*/
		$dbr = wfGetDB( DB_SLAVE );
		$discardCategories = array();
		$levels = array();
		$res = $this->executeQuery($dbr, $this->getQuerySubCategories('Level'));
		while ($obj = $dbr->fetchObject($res)) {
			array_push($levels, $obj->subcat);
			array_push($discardCategories, $obj->subcat);
		}
		$res = $this->executeQuery($dbr, $this->getQuerySubCategories('Language'));
		while ($obj = $dbr->fetchObject($res)) {
			array_push($discardCategories, $obj->subcat);
		}
		$categoryList = "'".strtolower(str_replace(' ', '_', join("','", array_filter($categories))))."'";
		$categoryNum = count(array_filter($categories));
		// TODO make sure W4G rating extension is installed before trying to use its table...
		$q = array (
			'options' => array('GROUP BY' => 'kp.page_id', 'HAVING' => "numCategories = $categoryNum"),
			'fields' => array (
				'kp.page_id as page_id',
				'kp.page_title as page_title',
				'group_concat(DISTINCT allcat.cl_to separator \', \') as categories',
				'count(distinct cl.cl_to) as numCategories',
				'rating.avg as rating',
				'skillLevel.cl_to as skillLevel'
			),
			// 'tables' => array ( 'cl' => 'categorylinks', 'kp' => 'page', 'pl' => 'pagelinks', 'allcat' => 'categorylinks' ),
			'tables' => array ( 'cl' => 'categorylinks', 'kp' => 'page', 'rev' => 'revision', 'skillLevel' => 'categorylinks', 'allcat' => 'categorylinks', 'rating' => 'w4grb_avg'),
			'join_conds' => array (
				'kp' => array (
					'INNER JOIN', 'cl.cl_from = kp.page_id and cl.cl_type = \'page\''
				),
				'rev' => array (
					'LEFT JOIN', 'kp.page_latest = rev.rev_id'
				),
				'skillLevel' => array(
					'LEFT JOIN', 'skillLevel.cl_from = kp.page_id and LCASE(CONVERT(skillLevel.cl_to using utf8)) in (\''.strtolower(join('\',\'', $levels)).'\')'
				),
				'allcat' => array (
					'INNER JOIN', 'allcat.cl_from = kp.page_id and LCASE(CONVERT(allcat.cl_to using utf8)) not in (\''.strtolower(join('\',\'', $discardCategories)).'\')'
				),
				'rating' => array (
					'LEFT JOIN', 'kp.page_id = rating.pid'
				)
			),
			'conds' => array (
				"kp.page_namespace = '$namespace'", // limit namespace
				"LCASE(CONVERT(cl.cl_to using utf8)) in ($categoryList)", // limit parent category
				"kp.page_title not like '%/%'" // don't consider subpages
			),
		);
		if (!empty($rating)) {
			array_push($q['conds'], 'rating.avg >= '.($rating * 20) . ' and rating.avg < ' . (($rating + 1) * 20) );
		}
		if (!empty($sklvl)) {
			array_push($q['conds'], 'LCASE(CONVERT(skillLevel.cl_to using utf8)) = \''.strtolower($sklvl).'\'');
		}
		return $q;
	}
	
	function getQuerySubCategories($parentCategory) {
		/*
			select
				kp.page_title as subcat
			from
				`mw_page` as kp
				join
				`mw_categorylinks` as cl
					on kp.page_id = cl.cl_from and cl.cl_type = 'subcat'
				where
					kp.page_namespace = '...' and cl.cl_to = 'Topic' and kp.page_title not like '%/%'
		*/
		$category = strtolower(str_replace(' ', '_', $parentCategory));
		return array (
			'fields' => array ( 'kp.page_title as subcat' ),
			'tables' => array ( 'kp' => 'page', 'cl' => 'categorylinks' ),
			'join_conds' => array (
				'cl' => array (
					'INNER JOIN', 'kp.page_id = cl.cl_from and cl.cl_type = \'subcat\''
				)
			),
			'conds' => array (
				"LCASE(CONVERT(cl.cl_to using utf8)) = '$category'", // limit parent category
				"kp.page_title not like '%/%'" // don't consider subpages
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
	
	function getWikiPageAuthors( $wikiPage ){
		$authorsText = "";
		$authorsList = array_unique($wikiPage->getLastNAuthors($this->authorSize));
		$authorsIndex = 1;
		$authorsCount = count($authorsList);
		
		foreach ($authorsList as $author) {
			$authorsText .= $author . (($authorsIndex == $authorsCount) ? "" : ",");
			$authorsIndex++;
		}
		
		return $authorsText;
	}
	
	function isTemplateDataItemEmpty($data, $itemName){
		return $data[$itemName] == wfMsg('kataninjastutoriallist_notavailable');
	}

	function execute( $subPage) {
		ContextSource::getContext()->getTitle()->skinNamespace = NS_NINJA_RESOURCE;
		
		$request = $this->getRequest();
		$out = $this->getOutput();
		$this->setHeaders();

		$out->setPageTitle("Kata for Ninjas");

		# Get request data from, e.g.
		$category = $request->getText( 'category' );
		$parentCat = "";
		$pageNumber = filter_var($request->getText( 'page' ) ? $request->getText( 'page' ) : 1, FILTER_VALIDATE_INT);
		
		# Do stuff
		# ...

		$dbr = wfGetDB( DB_SLAVE );
		
		$res = $this->executeQuery($dbr, $this->getQueryParentCategory($category));
		if($obj = $dbr->fetchObject($res)) {
			$parentCat = str_replace('_', ' ', $obj->parent_cat);
			$category = str_replace('_', ' ', $obj->category);
		}

		// this div is created in the skin :)
		// $out->addHTML(
		// 	Html::openElement( 'div', array( 'class' => 'kata-mentors' ) ). "\n"
		// );
		$out->addHTML(
			Html::openElement('div', array( 'class' => 'kata-ninjas kata-ninjas-list')). "\n".
			Html::openElement('div', array( 'class' => 'row kata-box')). "\n".
			Html::openElement('div', array( 'class' => 'kata-breadcrumbs')). "\n".
			($parentCat ? Html::element('div', array( 'class' => 'kata-breadcrumb'), $parentCat) : "").
			Html::element('div', array( 'class' => 'kata-breadcrumb kata-breadcrumb-selected'), $category).
			Html::closeElement( 'div' )."\n".
			Html::closeElement( 'div' )."\n"
		);

		// BEGIN filters
		$levelSelection = $request->getText( 'level' );
		$ratingSelection = $request->getText('rating');
		if (empty($ratingSelection) || !in_array($ratingSelection, array(1,2,3,4,5))) {
			$ratingSelection = '';
		}
		$ratingFilterOptions =  array(
			array('name' => 'Select an option', 'value' => ''),
			array('name' => '*****', 'value' => '5'),
			array('name' => '****', 'value' => '4'),
			array('name' => '***', 'value' => '3'),
			array('name' => '**', 'value' => '2'),
			array('name' => '*', 'value' => '1'),
		);
		foreach ($ratingFilterOptions as $k => &$v) {
			$v['selected'] = $v['value'] == $ratingSelection;
		};
		$this->printFilters($out, array(
			array('title' => 'Skill Level', 'name' => 'level', 'options' => $this->populateFilter($dbr, 'Level', $levelSelection, false)),
			array('title' => 'Ratings', 'name' => 'rating', 'options' => $ratingFilterOptions)
		), $category);
		// END filters
		
		if (strtolower($parentCat) == 'ninja games') {
			$this->nspace = NS_NINJA_GAME;
		} else if (strtolower($parentCat) == 'ninja tutorials') {
			$this->nspace = NS_NINJA_TUTORIAL;
		}
		
		$res = $this->executeQuery($dbr, $this->getQueryTutorialsByCategory($this->nspace, array($category), $ratingSelection, $levelSelection));
		$tutorials = array();
		while($obj = $dbr->fetchObject($res)) {
			$contentPageData = $this->getContentPageData($obj->page_id);
			$templatedata = $contentPageData["index"];
			
			$tutorial = array(
				'name' => str_replace('_', ' ', $obj->page_title),
				'author' => $this->isTemplateDataItemEmpty($templatedata, "author") ? $this->getWikiPageAuthors($contentPageData["page"]) : $templatedata["author"], //'Bill Lowe',
				'description' => $templatedata["description"], // 'Lorem ipsum dolor sit amet, consectetur adipisci elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquid ex ea commodi consequat. Quis aute iure reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint obcaecat cupiditat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
				'image' => $this->getImageUrl($templatedata['image']), //'http://placekitten.com/g/250/250',
				'skillLevel' => !empty($obj->skillLevel) ? $obj->skillLevel : wfMsg('kataninjastutoriallist_notavailable'), //'Beginner',
				'category' => $obj->categories,
				'rating' => $obj->rating,
				'templatedata' => $templatedata,
				'content' => $contentPageData
			);
			
			array_push($tutorials, $tutorial);
		}
		
		// BEGIN tutorials list
		$this->printPagination($out, $this->getTotalPages($tutorials), $pageNumber);
		$this->printTutorials($out, $this->getTutorialsInPage($tutorials, $pageNumber));
		$this->printPagination($out, $this->getTotalPages($tutorials), $pageNumber);
		// END tutorials list
		
		// BEGIN scripts
		$out->addHTML('
			<script>
				function kataDisableEmptyInputs(form){
					$(form).find(":input").each(function(){
						var t = $(this), v = t.val();
						!v && (t.attr("disabled", "disabled"));
					});
				}
			</script>
		');
		// END scripts
		
		// close div.kata-mentors
		$out->addHTML(
			Html::closeElement( 'div' )
		);
	}


	function getContentPageData($id){
		$wikiPage = WikiPage::newFromID($id);
		$parserOptions = new ParserOptions();
		$wikiPageContent = $wikiPage->getParserOutput($parserOptions);
		// $wikiPageContent = $this->parseWikiPageContent($wikiPage);
		if(!is_object($wikiPageContent))
			return $this->handlePageNotFound();
		
		return array(
			"index" => $this->parseTemplateData($wikiPage),	
			"content" => $wikiPageContent->mText,
			"page" => $wikiPage
		);
	}
	
	function getTutorialsInPage($allTutorials, $pageNumber) {
		$paginatedTutorials = $this->paginateTutorials($allTutorials);
		if(!is_int($pageNumber) || count($paginatedTutorials) < $pageNumber || $pageNumber < 1) {
			return array();
		}
		
		return $paginatedTutorials[$pageNumber - 1];
	}
	
	function getTotalPages($allTutorials) {
		return count($this->paginateTutorials($allTutorials));
	}
	
	function paginateTutorials($tutorials) {
		if($this->paginatedTutorials) {
			return $this->paginatedTutorials;
		}
		
		if(count($tutorials) <= $this->pageSize) {
			return array($tutorials);
		}
		$page = array_slice($tutorials, 0, $this->pageSize);
		$remainingPages = $this->paginateTutorials(array_slice($tutorials, $this->pageSize));
		$this->paginatedTutorials = array_merge(array($page), $remainingPages);
		return $this->paginatedTutorials;
	}
	
	function populateFilter($dbr, $category, $subcategory, $isCurrentCategory = false) {
		$res = $this->executeQuery($dbr, $this->getQuerySubCategories($category));
		$options = array();
		$defaultOpt = array('name' => 'Select an option', 'value' => '', 'selected' => true);
		
		while($obj = $dbr->fetchObject($res)) {
			$tmp = array('name' => str_replace('_', ' ', $obj->subcat), 'value' => $obj->subcat);
			
			if(strtolower(str_replace(' ', '_', $subcategory)) == strtolower($obj->subcat)) {
					$tmp['selected'] = true;
					$defaultOpt['selected'] = false;
			}
			array_push($options, $tmp);
		}
		
		if(!$isCurrentCategory) {
			array_unshift($options, $defaultOpt);
		}
		
		return $options;
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

	function printFilters($out, $filters, $category) {
		$selfTitle = SpecialPage::getTitleFor($this->getName());
		$formUrl = $selfTitle->getLinkURL();

		$out->addHTML(
			Html::openElement('div', array( 'class' => 'row kata-box')) . "\n".
			Html::openElement('div', array( 'class' => 'col-xs-12 kata-box-content' )) . "\n".
			Html::openElement('form', array( 'action' => $formUrl, 'method' => 'GET', 'onsubmit' => 'return kataDisableEmptyInputs(this);' )). "\n".
			Html::element('input', array('type' => 'hidden', 'value' => $category, 'name' => 'category')). "\n".
			Html::openElement('table', array( 'class' => 'kata-filter-section' ))
		);
		$headers = Html::openElement('tr', array());
		$options = Html::openElement('tr', array());
		foreach ($filters as $filter) {
			$headers .= Html::element('th', array('class' => 'kata-filter-element'), $filter['title']);
			$options .= Html::openElement('td', array('class' => 'kata-filter-element')) .
						$this->createOptions($filter['options'], $filter['name']) .
						Html::closeElement('td');
		}
		$headers .= Html::element('th', array( 'class' => 'kata-filter-element')) .
					Html::closeElement('tr');
		$options .= Html::openElement('td', array( 'class' => 'kata-filter-element')) .
					Html::element('input', array( 'class' => 'kata-button', 'type' => 'submit', 'value' => 'Apply' )) .
					Html::closeElement('td') .
					Html::closeElement('tr');
		$out->addHTML(
			$headers . "\n".
			$options . "\n".
			Html::closeElement( 'table' ) . "\n".
			Html::closeElement( 'form' ) . "\n".
			Html::closeElement( 'div' ) . "\n".
			Html::closeElement( 'div' )
		);
	}
	
	function createOptions($options, $filterName) {
		$html = Html::openElement('select', array( 'class' => 'kata-select', 'name' => $filterName ));
		foreach ($options as $option) {
			$html .= Html::element('option', array('value' =>  $option['value'], 'selected' => @$option['selected']), $option['name']);
		}
		$html .= Html::closeElement('select');
		return $html;
	}
	
	function printPagination($out, $totalPages, $selectedPage) {
		$urlParams = $this->getRequest()->getQueryValues();
		unset($urlParams['title']);
		$urlParams['page'] = ($selectedPage-1);
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
	
	function initTemplateDataArray(){
		$data = array();
		foreach ($this->parserTags as &$value)
			$data[$value] = wfMsg('kataninjastutoriallist_notavailable');
		
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
	
	function printTutorials($out, $tutorials) {
		foreach($tutorials as $tutorial) {
			$this->appendTutorial($out, $tutorial);
		}
	}
	
	function appendTutorial($out, $tutorial) {
		$courseSpecialTitle = SpecialPage::getTitleFor('KataCourse');
		$courseUrl = $courseSpecialTitle->getLinkURL(array('page' => $GLOBALS["wgExtraNamespaces"][$this->nspace].':'.$tutorial['name']));
		$rating = $tutorial['rating'];
		$rating = empty($rating) ? 0 : (floor($rating / 20));
		$out->addHTML(
			Html::openElement('div', array( 'class' => 'row kata-box')) . "\n".
			Html::openElement('div', array( 'class' => 'col-xs-12 kata-box-content' )) . "\n".
			// Html::openElement('form', array( 'method' => 'GET', 'action' => $courseUrl )) . "\n".
			Html::openElement('table', array( 'class' => 'kata-result-section' )) . "\n".
			
			Html::openElement('tr', array()) . "\n".
			Html::element('th', array( 'class' => 'kata-result-element kata-course-name' ), 'Name:') . "\n".
			Html::element('th', array( 'class' => 'kata-result-element kata-course-author' ), 'Author:') . "\n".
			Html::element('th', array( 'class' => 'kata-result-element-description' ), 'Description:') . "\n".
			Html::openElement('td', array( 'class' => 'kata-result-element kata-course-image', 'rowspan' => '4' )) . "\n".
			Html::openElement('span', array('data-rating' => $rating, 'class' => "kata-rating kata-rating-$rating")).
			Html::element('img', array( 'src' => $tutorial['image'] )) . "\n".
			Html::closeElement('span') . "\n".
			Html::closeElement('td') . "\n".
			Html::closeElement('tr') . "\n".
			
			Html::openElement('tr', array()) . "\n".
			Html::element('td', array( 'class' => 'kata-result-element kata-course-name' ), $tutorial['name']) . "\n".
			Html::element('td', array( 'class' => 'kata-result-element kata-course-author' ), $tutorial['author']) . "\n".
			Html::element('td', array( 'class' => 'kata-result-element-description', 'rowspan' => '5' ), $tutorial['description']) . "\n".
			Html::closeElement('tr') . "\n".
			
			Html::openElement('tr', array()) . "\n".
			Html::element('th', array( 'class' => 'kata-result-element kata-course-skill-level' ), 'Skill Level:') . "\n".
			Html::element('th', array( 'class' => 'kata-result-element kata-course-sessions' ), 'Category:') . "\n".
			Html::closeElement('tr') . "\n".
			
			Html::openElement('tr', array()) . "\n".
			Html::element('td', array( 'class' => 'kata-result-element kata-course-skill-level' ), $tutorial['skillLevel']) . "\n".
			Html::element('td', array( 'class' => 'kata-result-element kata-course-sessions' ), $tutorial['category']) . "\n".
			Html::closeElement('tr') . "\n".
			
			Html::openElement('tr', array()) . "\n".
			Html::element('td', array( 'class' => 'kata-result-element', 'colspan' => '3' )) . "\n".
			Html::openElement('td', array( 'class' => 'kata-result-element kata-course-action' )) . "\n".
			// Html::element('button', array( 'type' => 'submit', 'class' => 'kata-button' ), 'Start Tutorial') . "\n".
			Html::element('a', array( 'href' => $courseUrl, 'class' => 'kata-button' ), 'Start Tutorial') . "\n".
			Html::closeElement('td') . "\n".
			Html::closeElement('tr') . "\n".
			
			Html::closeElement( 'table' ) . "\n".
			// Html::closeElement( 'form' ) . "\n".
			Html::closeElement( 'div' ) . "\n".
			Html::closeElement( 'div' )
		);
	}
	
	function getImageUrl($imageName) {
		$image = 'http://placekitten.com/g/250/250';
		if(filter_var($imageName, FILTER_VALIDATE_URL)) {
			$image = $imageName;
		} else if($imageName && $imageName != wfMsg('kataninjastutoriallist_notavailable')) {
			$image = preg_match('/^File:/i', $imageName) ? wfLocalFile($imageName)->getFullUrl() : 'File:'.wfLocalFile($imageName)->getFullUrl();
		}
		return $image;
	}
}

?>