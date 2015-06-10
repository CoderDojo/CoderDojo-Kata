<?php
/**
* 'SpecialManualSitemap' class
*
* The XML file is ordered by decreasing popularity order (ie. maximum number of hits).
* User should have the 'bureaucrat' rights.
* Ignores MediaWiki (and MediaWiki talk) namespace.
* Redirect pages are ignored.

*/
class SpecialManualSitemap extends SpecialPage {

        var $file_name = "sitemap.xml"; // relative to $wgSitename (must be writable)

        /*
         * see http://www.manual.com/schemas/sitemap/0.84/sitemap.xsd for more details
         */
        var $DEFAULT_SITEMAP_HEADER = '<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="extensions/ManualSitemap/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        var $DEFAULT_PRIORITY = 0.5;
        var $DEFAULT_CHANGE_FREQ = "daily";

        var $file_handle;
        var $file_exists;

        var $count, $cursor_pos = 0;

        var $form_action;
        var $article_priorities = "constant";
        var $estimate_change_freq = false;
	var $sorting_criterion = "POP";

    function ManualSitemapPage() {
        SpecialPage::SpecialPage('ManualSitemap', 'ManualSitemap'); //$wgGroupPermissions['sysop']['DeleteOldRevisions'] = true;
                        global $wgRequest;
                $request =& $wgRequest;

                $file_name = $request->getText( 'wpFileName' );

                if( $file_name ) {
                        $this->file_name = $file_name;
                }

                $change_freq = $request->getCheck( 'wpChangeFreq' );

                if( $change_freq ) {
                        $this->estimate_change_freq = $change_freq ;
                }

                $priority = $request->getText( 'wpPriorityType' );

                if( $priority ) {
                        $this->article_priorities = $priority;
                }

		$sorting_criterion = $request->getText( 'wpSortCriterion' );

		if( $sorting_criterion ) {
			$this->sorting_criterion = $sorting_criterion;
		}

    }

	public function __construct() {
		parent::__construct( 'ManualSitemap', 'manualsitemap', true );
	}
    function execute() {
        global $wgUser, $wgOut;

		if ( ! $wgUser->isAllowed("manualsitemap") ) {
			$wgOut->permissionRequired( 'manualsitemap' );
			return;
		}
        $this->setHeaders();
        $this->initialize();
    }

        function utf8_write( $handle, $data ) {
                fwrite( $handle, utf8_encode( $data ) ) ;
        }

        function getName() {
                return 'ManualSitemap';
        }
        
	public function getDescription() {
		return ( 'Special ' . ( $this->getName() ) );
	}

        function isExpensive() {
                return false;
        }

        function isSyndicated() {
                return false;
        }

        function initialize() {
                global $wgExtensionCredits,$wgOut, $wgRequest;


                $wgOut->addHTML($this->addPageOptions());
                $request =& $wgRequest;

                if(!($request->getText( 'wpSortCriterion' )))
                  return;


                $this->file_exists = file_exists ( $this->file_name ) ;
                $this->file_handle = fopen( $this->file_name, 'w' ) or die( "Cannot write to '$this->file_name.'" );
                $this->utf8_write( $this->file_handle, $this->DEFAULT_SITEMAP_HEADER );

               $dbr =& wfGetDB( DB_SLAVE );
               $res = $dbr->query($this->GetSQL());
               $this->count = $dbr->numRows($res);

                $wgOut->addHTML($this->getPageHeader().'<ol start="1" class="special">');

			          while($row = $dbr->fetchObject( $res ))
				          $wgOut->addHTML( $this->formatResult($row ));

                $wgOut->addHTML('</ol>');

                $close_tag = "\n</urlset>";
                $this->utf8_write( $this->file_handle, $close_tag ) ;

                fclose( $this->file_handle );
        }

        function getPageHeader() { // has text
                global $wgServer, $wgScriptPath, $wgSitename, $ManualSitemap_Notify;

                $url = "$wgServer$wgScriptPath/$this->file_name";

                $misc_estimate = $this->estimate_change_freq?" and estimated change frequencies":"";
                $misc_file_action = $this->file_exists?"rebuilt":"created";

                $default_text="Sitemap <strong><a href=\"$url\" title=\"$wgSitename Sitemap\">$url</a></strong> was $misc_file_action for the following <strong>$this->count</strong> pages <small><em>(with $this->article_priorities priority$misc_estimate)</em></small>.<br />\n"; #English

                $info="";

                if( $this->offset != 0 ) {
                        $class="errorbox";
                        $info="<strong>This selection misses the $this->offset most viewed pages of $wgSitename, however</strong>...<br />\n"; #English
                } else {
                        $class = "successbox";
                }

				// Try to notify websites about the sitemap change. If not possible show links to the user.
				$notifyProblemMessage = "";
				if(is_array($ManualSitemap_Notify))
				for ( $i = 0; $i < sizeof($ManualSitemap_Notify); $i++ )
        {
					$handle = @fopen($ManualSitemap_Notify[$i], 'r');
					if ( $handle)
						fclose($handle);
					else {
						if (!$notifyProblemMessage)
							$notifyProblemMessage = '<br />Following search engines did not respond to query. Click the links to inform them manually.<ul>';
						$notifyProblemMessage .= '<li>' . parse_url($ManualSitemap_Notify[$i], PHP_URL_HOST) . ': <a target="_blank" href="'. $ManualSitemap_Notify[$i].'">click to notify</a></li>';
				      }
				}
				if ( strlen($notifyProblemMessage) > 0 )
					$notifyProblemMessage .= "</ul>";

                return "<div class=\"$class\">$info$default_text$notifyProblemMessage</div><div class=\"visualClear\"></div>\n";
        }

        function addPageOptions() {
                return "
                        <div id='userloginForm'>
                         <form id='sitemaps' method='post' enctype='multipart/form-data' action='$this->form_action'>
                          <h2>Options</h2>
                          <table>
                           <tr>
                            <td>
                             <label for='wpFileName1'>File name</label>
                            </td>
                            <td>
                             <input tabindex='1' type='text' name='wpFileName' id='wpFileName1' title='file to overwrite' value='$this->file_name' disabled=true></input>
                            </td>
                           </tr>
			   <tr>
                            <td>
                             <label for='wpSortCriterion1'>Sorting criterion</label>
                            </td>
			    <td>
			     <input type=radio name='wpSortCriterion' id='wpSortCriterion1' value='POP' checked='checked'>Popularity</input><br />
			     <input type=radio name='wpSortCriterion' id='wpSortCriterion1' value='REV'>Last revision</input>
			    </td>
			   </tr>
                           <tr>
                            <td>
                             <label for='wpChangeFreq1'>Estimate revision frequencies</label>
                            </td>
                            <td>
                             <input tabindex='2' type='checkbox' name='wpChangeFreq' id='wpChangeFreq1' title='daily, weekly, monthly...'></input>
                            </td>
                           </tr>
                           <tr>
                            <td>
                             <label for='wpPriorityType1'>Priority</label>
                            </td>
                            <td>
                             <select tabindex='3' name='wpPriorityType' id='wpPriorityType1' title='set relative priority based on page ranks'>
                                        <option>constant</option>
                                        <option>linear</option>
                                        <option>quadratic</option>
                                        <option>cubic</option>
                                        <option>exponential</option>
                                        <option>smooth</option>
                                        <option>random</option>
                                        <option>reverse</option>
                             </select>
                            </td>
                           </tr>
                           <tr>
                            <td>
                             <input tabindex='2' type='submit' value='Update Sitemap'></input>
                            </td>
                           </tr>
                          </table>
                         </form>
                        </div>
                        <div class=\"visualClear\"></div>
                        <br /><hr />\n\n";
        }


		function getSQL() {
			global $ManualSitemap_ExcludeSites,$ManualSitemap_Exclude;
			$dbr =& wfGetDB( DB_SLAVE );
			$page = $dbr->tableName( 'page' );
			$revision = $dbr->tableName( 'revision' );

			$criterion = $this->sorting_criterion=="REV"?"rev_timestamp":"page_counter";
			$sql='SELECT "Popularpages" AS type,page_id AS id,page_namespace AS namespace, page_title AS title, ( MAX( rev_timestamp ) ) AS last_modification,'.$criterion.' AS value
				FROM '.$page.', '.$revision.' WHERE ( page_namespace <> 8 AND page_namespace <> 9';
      if(is_array($ManualSitemap_Exclude))
        foreach($ManualSitemap_Exclude as $key=>$val)
          if($val)
            $sql.=' AND page_namespace <>"'.$key.'"';

			// Exclude some pages by their title.
			if (sizeof($ManualSitemap_ExcludeSites)) {
				$sql.=" AND page_title NOT IN ('" .implode("','", $ManualSitemap_ExcludeSites). "')";
			}

			$sql.=') AND page_is_redirect = 0 AND rev_page = page_id GROUP BY page_id';
				 return $sql;
        }

        function sortDescending() {
                return true;
        }


        function formatResult($result ) {
                global $wgLang, $wgContLang, $wgServer, $ManualSitemap_ServerBase,$wgUser;

        $skin = $wgUser->getSkin();

				$serverBase = $wgServer;
				if ( strlen($ManualSitemap_ServerBase) > 0 ) {
					$serverBase = $ManualSitemap_ServerBase;
				}

                $title = Title::makeTitle( $result->namespace, $result->title );
                $link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );

                $url = $title->escapeLocalURL();
                $this->form_action=$title->escapeLocalURL( 'action=submit' );

                // The date must conform to ISO 8601 (http://www.w3.org/TR/NOTE-datetime)
                // UTC (Coordinated Universal Time) is used, manual currently ignores time however
                $last_modification = gmdate( "Y-m-d\TH:i:s\Z", wfTimestamp( TS_UNIX, $result->last_modification ) );

				$this->addURL( $serverBase, $url, $last_modification, $result->id );

                ++$this->cursor_pos;

				return "<li>{$link} <small>($serverBase$url)</small></li>";
        }

        function addURL( $base, $url, $last_modification, $page_id ) { // parameters must be valid XML data
                $result="  <url>\n    <loc>$base$url</loc>\n    <priority>".round($this->getPriority(),1)."</priority>\n    <lastmod>$last_modification</lastmod>\n    <changefreq>".$this->getChangeFreq($page_id)."</changefreq>\n  </url>\n";
                $this->utf8_write( $this->file_handle, $result );
        }

        function getPriority() { // must return valid XML data
                $x = $this->cursor_pos / $this->count;

                switch( $this->article_priorities ) {
                        case "constant"    : $p= $this->DEFAULT_PRIORITY;break;
                        case "linear"      : $p= 1.0 - $x;break;
                        case "quadratic"   : $p= pow( 1.0 - $x, 2.0 ) ;break;
                        case "cubic"       : $p= 3.0 * pow( ( 1.0 - $x ), 2.0 ) - 2.0 * pow( ( 1.0 - $x ), 3.0 );break;
                        case "exponential" : $p= exp( -6 * $x );break;# exp(-6) ~= 0,002479
                        case "smooth"      : $p= cos( $x * pi() / 2.0 );break;
                        case "random"      : $p= mt_rand() / mt_getrandmax();break;
                        case "reverse"     : $p= $x;break;

                        default: $p= $this->DEFAULT_PRIORITY;break;
                }
                return $p;
        }

        function getChangeFreq( $page_id ) { // must return valid XML data
                if( $this->estimate_change_freq ) {
                        $dbr =& wfGetDB( DB_SLAVE );

                        $revision = $dbr->tableName( 'revision' );

                        $sql = "SELECT
                                        MIN(rev_timestamp) AS creation_timestamp,
                                        COUNT(rev_timestamp) AS revision_count
                                        FROM $revision WHERE rev_page = $page_id";

                        $res = $dbr->query( $sql );
                        $count = $dbr->numRows( $res );

                        if( $count < 1 ) {
                                return $this->DEFAULT_CHANGE_FREQ;
                        } else {
                                $item1 =( $dbr->fetchObject( $res ) );

                                $cur = time() ; // now
                                $first = wfTimestamp( TS_UNIX, $item1->creation_timestamp );

                                // there were $item1->revision_count revisions in ($cur - $first) seconds
                                $diff = ($cur - $first) / $item1->revision_count ;

                                switch( true ) {
                                        # case $diff < 60: return "always"; // I suspect Manual to ignore these pages more often...
                                        case $diff < 3600: return "hourly";
                                        case $diff < 24*3600: return "daily";
                                        case $diff < 7*24*3600: return "weekly";
                                        case $diff < 30.33*24*3600: return "monthly";
                                        case $diff < 365.25*24*3600: return "yearly";
                                        default: return $this->DEFAULT_CHANGE_FREQ;
                                        # return "never"; // for archived pages only
                                }
                        }
                } else {
                        return $this->DEFAULT_CHANGE_FREQ;
                }
        }
}


?>
