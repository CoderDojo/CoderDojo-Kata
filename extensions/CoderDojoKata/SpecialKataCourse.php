<?php
class SpecialKataCourse extends SpecialPage {
	public $context = null;
	public $serverPath = "";
	
	private $specialPageID = 'KataCourse';
	private $dbr = null;
	private $pageContentTitle = null;
	private $request = null;
	private $out = null;
	
	private $currentPageTitle = null;
	private $rootPageTitle = null;
	
	private $namespaceTextID = null;
	private $namespaceText = null;
	private $namespaceID = null;
	private $language = null;
	
	private $isSubpage = false;
	private $isValid = true;
	private $isUserAnonymous = true;
	private $isVideoEnabled = false;
	
	private $parserMaxIncludeSize = 67108864;
	private $parserSessionTag = "sessions";
	private $parserVideoTag = "link";
	
	private $useSkin = "";

	function __construct() {
		parent::__construct($this->specialPageID);
	}
	
	function setPageTitleObject(){
		global $wgServer, $wgScriptPath, $wgExtraNamespaces, $wgUser;
		
		$this->serverPath = $wgServer . $wgScriptPath;
		$this->isUserAnonymous = $wgUser->isAnon();
		
		$this->currentPageTitle = Title::newFromText($this->pageContentTitle);
		$this->currentPageTitle = (empty($this->currentPageTitle) ? Title::newFromText(wfMsg('katacourse_pnf_title')) : $this->currentPageTitle);
		
		$this->namespaceTextID = $this->currentPageTitle->getNsText();
		$this->namespaceText = str_replace("_", " ", $this->namespaceTextID);
		$this->namespaceID = array_search($this->namespaceTextID, $wgExtraNamespaces);
		$this->isSubpage = $this->currentPageTitle->isSubpage();
		
		$this->context = ContextSource::getContext();
		$this->context->getTitle()->skinNamespace = ($this->namespaceID) ? $this->namespaceID : 3320;
		
		$this->language = $this->currentPageTitle->getPageViewLanguage();
		$this->rootPageTitle = $this->currentPageTitle->getRootTitle();

		// "emulate" the page we're rendering so the skin can correctly build action links (edit, etc)
		$this->context->setTitle($this->currentPageTitle);
		RequestContext::getMain()->allowEdit = TRUE;
	}
	
	function initPage(){
		$this->setHeaders();
		$this->request = $this->getRequest();
		$this->pageContentTitle =  htmlspecialchars($this->request->getText('page'));
		$this->dbr = wfGetDB( DB_SLAVE );
		$this->setPageTitleObject();
		$this->isVideoEnabled = ($this->namespaceID == 3550) ? true : false;
	}
	
	function execute() {
		$this->out = $this->getOutput();
		$this->initPage();
		
		$pageContent = $this->getContentPageData();
		$this->out->setPageTitle($pageContent["title"]);
		$this->out->addHTML($this->generatePage($pageContent));
	}
	
	function getRatingBar(){
		global $wgW4GRB_Settings;
		$htmlRatingBar = "";
		
		if(!$this->isSubpage && $this->isValid) {
			$wgW4GRB_Settings['auto-include'] = true;
			$htmlRatingBar = W4GrbHTML($this->getOutput(), $this->currentPageTitle);
			$wgW4GRB_Settings['auto-include'] = false;
		}
		
		return $htmlRatingBar;
	}
	
	
	function generatePage($pageContent){
		$htmlPage = Html::openElement("div", array("class" => "kata-course"));
			$htmlPage .= $this->generateBreadcrumbs();
			
			$htmlPage .= Html::openElement("div", array("class" => "row kata-box"));
				$htmlPage .= Html::openElement("table", array("class" => "kata-tutorial-container"));
					$htmlPage .= Html::openElement("tr", array());
						$htmlPage .= Html::rawElement("td", array("colspan" => "2"), "&nbsp;");
					$htmlPage .= Html::closeElement("tr");
					$htmlPage .= Html::openElement("tr", array());
						$htmlPage .= Html::rawElement("td", array("class" => "kata-tutorial-navigation-container"), $this->generateCourseNavigation($pageContent["index"]));
						$htmlPage .= Html::openElement("td", array("class" => "kata-tutorial-content-container"));
							$htmlPage .= Html::openElement("div", array("class" => "mw-content-ltr"));
								$htmlPage .= Html::rawElement("div", array("class" => "kata-tutorial-step-title"), $pageContent["title"]);
								$htmlPage .= Html::rawElement("div", array("class" => "kata-tutorial-step-content"), $pageContent["content"]);
								$htmlPage .= Html::rawElement("div", array("class" => "kata-tutorial-rating"), $this->getRatingBar());
							$htmlPage .= Html::closeElement("div");
						$htmlPage .= Html::closeElement("td");
					$htmlPage .= Html::closeElement("tr");
				$htmlPage .= Html::closeElement("table");
			$htmlPage .= Html::closeElement("div");
		
		$htmlPage .= Html::closeElement("div");
		
		return $htmlPage;
	}
	
	function generateBreadcrumbs(){
		$htmlBreadcrumbs = Html::openElement("div", array("class" => "row kata-box"));
			$htmlBreadcrumbs .= Html::openElement("div", array("class" => "kata-breadcrumbs"));
				$htmlBreadcrumbs .= Html::rawElement("div", array("class" => "kata-breadcrumb"), $this->namespaceText);
				if($this->isSubpage) {
					$htmlBreadcrumbs .= Html::element("a",
					                                  array(
					                                        "class" => "kata-breadcrumb",
					                                        'href' => SpecialPage::getTitleFor($this->getName())->getLinkURL(
					                                        				array('page' => $this->namespaceTextID . ':' . $this->rootPageTitle->getText())
					                                        			)
					                                  ),
					                                  $this->rootPageTitle->getText());
				}
				$htmlBreadcrumbs .= Html::rawElement("div", array("class" => "kata-breadcrumb  kata-breadcrumb-selected"), $this->currentPageTitle->getSubpageText());
			$htmlBreadcrumbs .= Html::closeElement("div");
		$htmlBreadcrumbs .= Html::closeElement("div");
		
		return $htmlBreadcrumbs;
	}
	
	function generateCourseNavigation($pageList){
		$htmlList = Html::openElement("table", array("class" => "kata-tutorial-navigation"));
		$rowItemCSSClass = "kata-tutorial-navigation-item";
		foreach ((array) $pageList as $pageIndex) {
			$pageTitle = Title::newFromText($pageIndex);
			$htmlList .= Html::openElement("tr", array());
				$rowItemCSSClass = ($this->currentPageTitle->getSubpageText() == $pageTitle->getSubpageText()) ? 
										"kata-tutorial-navigation-item kata-tutorial-navigation-item-selected" : 
										"kata-tutorial-navigation-item";
				$htmlList .= Html::openElement("td", array("class" => $rowItemCSSClass));
					$htmlList .= Html::rawElement(	"a",
													array('href' => $this->serverPath ."/index.php/Special:KataCourse?page=" . $pageIndex . $this->useSkin),
													$pageTitle->getSubpageText());
				$htmlList .= Html::closeElement("td");
			$htmlList .= Html::closeElement("tr");
		}
		$htmlList .= Html::closeElement("table");
	
		return $htmlList;
	}
	
	function getContentPageData(){
		$wikiPage = WikiPage::factory($this->currentPageTitle);
		$wikiPageContent = $this->parseWikiPageContent($wikiPage);
		if(!is_object($wikiPageContent))
			return $this->handlePageNotFound();
		
		$templateData =  $this->parseTemplateData($wikiPage);
		$content = ($this->isVideoEnabled) ? 
						$templateData[$this->parserVideoTag] . $wikiPageContent->mText : 
						$wikiPageContent->mText;
		
		return array(
			"title"	=> $this->currentPageTitle->getSubpageText(),
			"index" => $templateData[$this->parserSessionTag],	
    		"content" => $content);
	}
	
	
	
	function handlePageNotFound(){
		$this->isValid = false;
		return array(
				"title"	=> wfMsg('katacourse_pnf_title'),
				"index" => array(),
				"content" => wfMsg('katacourse_pnf_content'));
	}
	
	
	function parseTemplateData($wikiPage){
		global $wgParser;
		$data = array();
		$tags = ($this->isVideoEnabled) ? 
					array($this->parserVideoTag, $this->parserSessionTag) : 
					array($this->parserSessionTag);
				
		$data = SpecialKataTemplateParser::parseTemplate($wgParser, $this->context, $wikiPage, $tags, "");
		$data = $this->parseSessions($data);
		if($this->isVideoEnabled)
			$data = $this->parseVideo($data);
		
		return $data;
	}
	
	
	function parseSessions($data){
		if(!empty($data[$this->parserSessionTag])){
			$data[$this->parserSessionTag] = explode(",", $data[$this->parserSessionTag]);
			$data[$this->parserSessionTag] = array_map('trim', $data[$this->parserSessionTag]);
		}else{
			$data[$this->parserSessionTag] = null;
		}
		
		return $data;
	}
	
	
	function parseVideo($data){
		global $wgParser;
		
		if(!empty($data[$this->parserVideoTag])){
			$data[$this->parserVideoTag] = "{{#evt:service=youtube|id=" . $data[$this->parserVideoTag] . "|alignment=left}}";
			$data[$this->parserVideoTag] = SpecialKataTemplateParser::parseWikiText($wgParser, $this->context, $data[$this->parserVideoTag]);
		}
	
		return $data;
	}
	
		
	function httpRequest($url){
		$data = null;
		$request = curl_init();
		
		curl_setopt($request, CURLOPT_URL, $url);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		
		$data = curl_exec($request);
		
		curl_close($request);
		
		return $data;
	}
	
	function parseWikiPageContent($wikiPage){
		$parserOptions = new ParserOptions();
		$parsedPage = $wikiPage->getParserOutput($parserOptions);
	
		return $parsedPage;
	}
}