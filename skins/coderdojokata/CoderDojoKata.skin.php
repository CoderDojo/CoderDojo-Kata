<?php
/**
 * Skin file for CoderDojo Kata skin
 *
 * @file
 * @ingroup Skins
 * @author CoderDojo Foundation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * SkinTemplate class for CoderDojo Kata skin
 * @ingroup Skins
 * @author CoderDojo Foundation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class SkinCoderDojoKata extends SkinTemplate
{
	var $skinname = 'CoderDojoKata';
	var $stylename = 'CoderDojoKata';
	var $template = 'CoderDojoKataTemplate';
	var $useHeadElement = true;
 
	/**
	 * Add JavaScript via ResourceLoader
	 *
	 * @param OutputPage $out
	 */
	public function initPage( OutputPage $out ) {
		parent::initPage( $out );
		$out->addModules( array( 'skins.coderdojokata' ) );
	}
 
	/**
	 * Add CSS via ResourceLoader
	 *
	 * @param $out OutputPage
	 */
	function setupSkinUserCss( OutputPage $out ) 
	{
		parent::setupSkinUserCss( $out );
		$out->addModuleStyles( 'skins.coderdojokata' );
	}
	
}


/**
 * BaseTemplate class for CoderDojo Kata skin
 *
 * @ingroup Skins
 * @author CoderDojo Foundation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
class CoderDojoKataTemplate extends BaseTemplate
{

	/**
	 * Render one or more navigations elements by name, automatically reveresed
	 * when UI is in RTL mode
	 *
	 * @param array $elements
	 */
	protected function renderNavigation( $elements ) {
		// If only one element was given, wrap it in an array, allowing more
		// flexible arguments
		if ( !is_array( $elements ) ) {
			$elements = array( $elements );
			// If there's a series of elements, reverse them when in RTL mode
		} elseif ( $this->data['rtl'] ) {
			$elements = array_reverse( $elements );
		}
		// Render elements
		?><ul class="nav navbar-nav navbar-right" role="tablist"><?php
		foreach ( $elements as $name => $element ) {
			switch ( $element ) {
				case 'NAMESPACES':
					foreach ( $this->data['namespace_urls'] as $link ) {
						?>
						<li <?php
						echo $link['attributes']
						?>><a href="<?php
								echo htmlspecialchars( $link['href'] )
								?>" <?php
								echo $link['key']
								?>><?php
									echo htmlspecialchars( $link['text'] )
									?></a></li>
					<?php
					}
					?>
					<?php
					break;
				case 'VARIANTS':
					?>
					<div id="p-variants" role="navigation" class="vectorMenu<?php
					if ( count( $this->data['variant_urls'] ) == 0 ) {
						echo ' emptyPortlet';
					}
					?>" aria-labelledby="p-variants-label">
						<?php
						// Replace the label with the name of currently chosen variant, if any
						$variantLabel = $this->getMsg( 'variants' )->text();
						foreach ( $this->data['variant_urls'] as $link ) {
							if ( stripos( $link['attributes'], 'selected' ) !== false ) {
								$variantLabel = $link['text'];
								break;
							}
						}
						?>
						<h3 id="p-variants-label"><span><?php echo htmlspecialchars( $variantLabel ) ?></span><a href="#"></a></h3>

						<div class="menu">
							<ul>
								<?php
								foreach ( $this->data['variant_urls'] as $link ) {
									?>
									<li<?php
									echo $link['attributes']
									?>><a href="<?php
										echo htmlspecialchars( $link['href'] )
										?>" lang="<?php
										echo htmlspecialchars( $link['lang'] )
										?>" hreflang="<?php
										echo htmlspecialchars( $link['hreflang'] )
										?>" <?php
										echo $link['key']
										?>><?php
											echo htmlspecialchars( $link['text'] )
											?></a></li>
								<?php
								}
								?>
							</ul>
						</div>
					</div>
					<?php
					break;
				case 'VIEWS':
					foreach ( $this->data['view_urls'] as $link ) {
						?><li<?php
						echo $link['attributes']
						?>><a href="<?php
								echo htmlspecialchars( $link['href'] )
								?>" <?php
								echo $link['key']
								?>><?php
									// $link['text'] can be undefined - bug 27764
									if ( array_key_exists( 'text', $link ) ) {
										echo array_key_exists( 'img', $link )
											? '<img src="' . $link['img'] . '" alt="' . $link['text'] . '" />'
											: htmlspecialchars( $link['text'] );
									}
									?></a></li>
					<?php
					}
					break;
				case 'ACTIONS':
					if ( count( $this->data['action_urls'] ) > 0 ) {
					?><li id="p-cactions" role="navigation" class="dropdown" aria-labelledby="p-cactions-label">
							<h3 id="p-cactions-label" class="hidden"><span><?php
								$this->msg( 'vector-more-actions' )
							?></span><a href="#"></a></h3>
							<a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
							<ul class="dropdown-menu" <?php $this->html( 'userlangattributes' ) ?>>
								<?php
								foreach ( $this->data['action_urls'] as $link ) {
									?>
									<li<?php
									echo $link['attributes']
									?>>
										<a href="<?php
										echo htmlspecialchars( $link['href'] )
										?>" <?php
										echo $link['key'] ?>><?php echo htmlspecialchars( $link['text'] )
											?></a>
									</li>
								<?php
								}
								?>
							</ul>
						</li>
						<?php
					}
					break;
				case 'PERSONAL':
					?>
					<div id="p-personal" role="navigation" class="<?php
					if ( count( $this->data['personal_urls'] ) == 0 ) {
						echo ' emptyPortlet';
					}
					?>" aria-labelledby="p-personal-label">
						<h3 id="p-personal-label"><?php $this->msg( 'personaltools' ) ?></h3>
						<ul<?php $this->html( 'userlangattributes' ) ?>>
							<?php
							$personalTools = $this->getPersonalTools();
							foreach ( $personalTools as $key => $item ) {
								echo $this->makeListItem( $key, $item );
							}
							?>
						</ul>
					</div>
					<?php
					break;
				case 'SEARCH':
					?>
					<li id="p-search" role="search">
						<h3 class="hidden"<?php $this->html( 'userlangattributes' ) ?>>
							<label for="searchInput"><?php $this->msg( 'search' ) ?></label>
						</h3>
						<form style="margin-top: 10px; margin-bottom: 0;" action="<?php $this->text( 'wgScript' ) ?>" id="searchform">
							<?php
							if ( $this->config->get( 'VectorUseSimpleSearch' ) ) {
							?>
							<div id="simpleSearch">
								<?php
							} else {
							?>
								<div>
									<?php
							}
							?>
							<?php
							echo $this->makeSearchInput( array( 'id' => 'searchInput' ) );
							echo Html::hidden( 'title', $this->get( 'searchtitle' ) );
							// We construct two buttons (for 'go' and 'fulltext' search modes),
							// but only one will be visible and actionable at a time (they are
							// overlaid on top of each other in CSS).
							// * Browsers will use the 'fulltext' one by default (as it's the
							//   first in tree-order), which is desirable when they are unable
							//   to show search suggestions (either due to being broken or
							//   having JavaScript turned off).
							// * The mediawiki.searchSuggest module, after doing tests for the
							//   broken browsers, removes the 'fulltext' button and handles
							//   'fulltext' search itself; this will reveal the 'go' button and
							//   cause it to be used.
							echo $this->makeSearchButton(
								'fulltext',
								array( 'id' => 'mw-searchButton', 'class' => 'searchButton mw-fallbackSearchButton' )
							);
							echo $this->makeSearchButton(
								'go',
								array( 'id' => 'searchButton', 'class' => 'searchButton' )
							);
							?>
								</div>
						</form>
					</li>
					<?php

					break;
			}
		}
		?></ul><?php
	}
	
	/**
	 * View Helper pattern for simplifying access to:
	 * -  ThisPage > Current page id
	 * -  SiteName > Site Name
	 * -  Namespace > Namespace of the current page (blank if normal page)
	 * -  Categories > Return an array of the parent categories of the current page
	 * -  ArticlePath > Root path URL to wiki contents
	 * -  SkinTemplate > Name of the skin template that needs to be loaded for render the page
	 * -  ImagePath > Shortcut to Skin Images Path
	 * -  ResourcePath > Shortcut to Skin Resources Path
	 */
	public function getViewHelper()
	{
		$context = RequestContext::getMain();
		$categories = $context->getOutput()->getCategories();
		$namespace = !empty($context->getTitle()->skinNamespace) ? $context->getTitle()->skinNamespace : $context->getTitle()->getNamespace();
		if(!empty($namespace))
		{
			$namespaceName = $GLOBALS["wgExtraNamespaces"][$namespace];
		}
		
		$skinTemplate = "content";
		$cssClasses = '';
		if(!empty($namespace))
		{
			$cssClasses = " " . strtolower(strtr($namespaceName, "_", "-"));
		}
		
		
		// Check the namespace to load the right template
		switch (true)
		{
			case ($namespace == NS_SPECIAL):
				break;
				
			case ($namespace == NS_MAIN):
				
				$skinTemplate = "content";
				$cssClasses = " main" . $cssClasses;
				$kataSection = "Kata :: ";
				
				if ($context->getTitle()->getDBkey() == "Main_Page")
				{
					$skinTemplate = "mainPage";
				}
				break;
				
			case (($namespace >= NS_ORGANISER_RESOURCE) && ($namespace <= NS_ORGANISER_RESOURCE + 99)):
				
				$skinTemplate = "content";
				$cssClasses = " organiser-resource" . $cssClasses;
				$kataSection = "Organiser Resources > ";
				break;
				
			case (($namespace >= NS_TECHNICAL_RESOURCE) && ($namespace <= NS_TECHNICAL_RESOURCE + 99)):
				
				$skinTemplate = "content";
				$cssClasses = " technical-resource" . $cssClasses;
				$kataSection = "Technical Resources > ";
				break;
				
			case (($namespace >= NS_NINJA_RESOURCE) && ($namespace <= NS_NINJA_RESOURCE + 99)):
				
				$skinTemplate = "content";
				$cssClasses = " ninja-resource" . $cssClasses;
				$kataSection = "Ninja Resources > ";
				break;
				
			case ($namespace == NS_TEMPLATE):
				break;
				
			case ($namespace == NS_CATEGORY):
				break;
		}
		
		$viewHelper = array(
				"ThisPage" => $this->data["thispage"],
				"SiteName" => $this->data["sitename"],
				"Section" => $kataSection,
				"Namespace" => $namespace,
				"Categories" => $categories,
				"ArticlePath" => substr($this->data["articlepath"], 0, -2),
				"SkinTemplate" => $skinTemplate,
				"CssClasses" => trim($cssClasses),
				"ImagePath" => "{$this->data["stylepath"]}/{$this->data["skinname"]}/images/",
				"ResourcePath" => "{$this->data["stylepath"]}/{$this->data["skinname"]}/resources/"
		);
		
		return $viewHelper;
		
	}
	
	protected function setupNavigation() {
		// Build additional attributes for navigation urls
		$nav = $this->data['content_navigation'];

		if ( $this->config->get( 'VectorUseIconWatch' ) ) {
			$mode = $this->getSkin()->getUser()->isWatched( $this->getSkin()->getRelevantTitle() )
				? 'unwatch'
				: 'watch';

			if ( isset( $nav['actions'][$mode] ) ) {
				$nav['views'][$mode] = $nav['actions'][$mode];
				$nav['views'][$mode]['class'] = rtrim( 'icon ' . $nav['views'][$mode]['class'], ' ' );
				$nav['views'][$mode]['primary'] = true;
				unset( $nav['actions'][$mode] );
			}
		}

		$xmlID = '';
		foreach ( $nav as $section => $links ) {
			foreach ( $links as $key => $link ) {
				if ( $section == 'views' && !( isset( $link['primary'] ) && $link['primary'] ) ) {
					$link['class'] = rtrim( 'collapsible ' . $link['class'], ' ' );
				}

				$xmlID = isset( $link['id'] ) ? $link['id'] : 'ca-' . $xmlID;
				$nav[$section][$key]['attributes'] =
					' id="' . Sanitizer::escapeId( $xmlID ) . '"';
				if ( $link['class'] ) {
					$nav[$section][$key]['attributes'] .=
						' class="' . htmlspecialchars( $link['class'] ) . '"';
					unset( $nav[$section][$key]['class'] );
				}
				if ( isset( $link['tooltiponly'] ) && $link['tooltiponly'] ) {
					$nav[$section][$key]['key'] =
						Linker::tooltip( $xmlID );
				} else {
					$nav[$section][$key]['key'] =
						Xml::expandAttributes( Linker::tooltipAndAccesskeyAttribs( $xmlID ) );
				}
			}
		}
		$this->data['namespace_urls'] = $nav['namespaces'];
		$this->data['view_urls'] = $nav['views'];
		$this->data['action_urls'] = $nav['actions'];
		$this->data['variant_urls'] = $nav['variants'];

		// Reverse horizontally rendered navigation elements
		if ( $this->data['rtl'] ) {
			$this->data['view_urls'] =
				array_reverse( $this->data['view_urls'] );
			$this->data['namespace_urls'] =
				array_reverse( $this->data['namespace_urls'] );
			$this->data['personal_urls'] =
				array_reverse( $this->data['personal_urls'] );
		}

	}
	/**
	 * Outputs the entire contents of the page
	 */
	public function execute() 
	{
		$this->setupNavigation();
		$context = RequestContext::getMain();
		if ($context->canUseWikiPage()) {
			$wikiPage = $context->getWikiPage();
		}
		
		$viewHelper = $this->getViewHelper();
	
		$this->html( 'headelement' );
		
?>
<!-- put everything inside <body></body> (body tags excuded) -->

<!-- CONTAINER::PAGE -->
<div id="PageContainer" class="container-fluid">

	<!-- CONTAINER::HEADER -->
	<div id="HeaderContainer" class="row">

<?php include "resources/header.tpl.php";?>

	</div>
	<!-- CONTAINER::HEADER END -->

	<!-- CONTAINER::BODY -->
	<div id="BodyContainer" class="row">

	<!-- CONTAINER::COL1BODY -->
		<div id="Col1BodyContainer" class="col-md-2">

			<!-- CONTAINER::SIDEBAR -->
			<div id="SideBar" class="col-md-12">

				<div class="kata-seperator"></div>

<?php include "resources/sidebar.tpl.php";?>

			</div>
			<!-- CONTAINER::SIDEBAR END -->
		</div>
		<!-- CONTAINER::COL1BODY END -->

		<!-- CONTAINER::COL2BODY -->
		<div id="Col2BodyContainer" class="col-md-10">

			<!-- CONTAINER::MAINCONTAINER -->
			<div id="MainContainer" class="row <?php echo $viewHelper['CssClasses']; ?>">
	   
<?php include "resources/{$viewHelper['SkinTemplate']}.tpl.php";?>

			</div>
			<!-- CONTAINER::MAINCONTAINER END -->
		</div>
		<!-- CONTAINER::COL2BODY END -->
	</div>
	<!-- CONTAINER::BODY END -->

	<!-- CONTAINER::FOOTER -->
	<div id="FooterContainer" class="row">
	
<?php include "resources/footer.tpl.php";?>

	</div>
	<!-- CONTAINER::FOOTER END -->

</div>
<!-- CONTAINER::PAGE END -->

<?php
	$this->printTrail ();
?>
<?php if (@$_REQUEST['debug'] == 'true') { ?>
<hr>
<hr>
<h1>$wikiPage</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($wikiPage); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getTitle()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getTitle()); ?>
	</pre>
</div>
<hr>
<hr>
<?php if (!is_null($wiki)) { ?>
<h1>$wikiPage->getContent()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($wikiPage->getContent()); ?>
	</pre>
</div>
<hr>
<hr>
<?php } ?>
<h1>$this->data['content_navigation']</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($this->data['content_navigation']); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$this->data['content_actions']</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($this->data['content_actions']); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getOutput()->getCategories()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getOutput()->getCategories()); ?>
	</pre>
</div>

<hr>
<hr>
<h1>$context->getConfig()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php //print_r($context->getConfig()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getLanguage()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getLanguage()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getMain()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getMain()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getOutput()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getOutput()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getRequest()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getRequest()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getSkin()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getSkin()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getTitle()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getTitle()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getUser()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getUser()); ?>
	</pre>
</div>
<hr>
<hr>
<h1>$context->getWikiPage()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r($context->getWikiPage()); ?>
	</pre>
</div>

<hr>
<hr>
<h1>RequestContext::getMain()</h1>
<hr>
<hr>
<div>
	<pre>
		<?php print_r(RequestContext::getMain()); ?>
	</pre>
</div>
<?php } // end if debug ?>

</body>
</html>
<?php
	}
}
?>
