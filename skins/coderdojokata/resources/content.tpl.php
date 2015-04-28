<div id="CommandBar" class="navbar navbar-default" role="navigation">
	<div class="container-fluid">
		<?php 
			$navElements = array('NAMESPACES');
			$requestedPageTitle = RequestContext::getMain()->getRequest()->getValues();
			$requestedPageTitle = $requestedPageTitle['title'];
			if (strpos($requestedPageTitle, "Special:") === FALSE || RequestContext::getMain()->allowEdit === TRUE) {
				$navElements = array( 'NAMESPACES', 'VIEWS', 'ACTIONS' /*, "SEARCH" */ );
			}
			$this->renderNavigation($navElements );
		?>
		<?php /*
		<ul class="nav navbar-nav navbar-right" role="tablist">
			<li><a href="#">Page</a></li><!-- NAMESPACES -->
			<li><a href="#">Discussion</a></li>
			<li><a href="#">Read</a></li><!-- VIEWS -->
			<li><a href="#">Edit</a></li>
			<li><a href="#">View history</a></li>
			<li class="dropdown"><!-- ACTIONS -->
				<a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
				<ul class="dropdown-menu" role="menu">
					<li><a href="#">Delete</a></li>
					<li><a href="#">Move</a></li>
					<li><a href="#">Protect</a></li>
					<li><a href="#">Watch</a></li>
				</ul> 
			</li>
		</ul>
		*/?>
	</div>
</div>

<div id="content">
	<div class="kata-category-title">
		<h2><?php echo $viewHelper["Section"]; ?><?php $this->html( 'title' ); ?></h2>
	</div>

	<!-- kata-content -->
	<div class="kata-content" role="main">
		<a id="top"></a>
		<div id="mw-js-message" style="display: none;"
			<?php $this->html( 'userlangattributes' ) ?>></div>
			<?php if ( $this->data['sitenotice'] ): ?>
			<!-- sitenotice -->
			<div id="siteNotice"><?php $this->html( 'sitenotice' ) ?></div>
			<!-- /sitenotice -->
			<?php endif; ?>
		<!-- firstHeading -->
		<h1 id="firstHeading" class="firstHeading"
			lang="<?php
			$this->data ['pageLanguage'] = $this->getSkin ()->getTitle ()->getPageViewLanguage ()->getCode ();
			$this->html ( 'pageLanguage' );
			?>">
		</h1>
		<!-- /firstHeading -->
		<!-- bodyContent -->
		<div id="bodyContent">
			<?php if ( $this->data['isarticle'] ): ?>
				<!-- tagline -->
				<div id="siteSub"><?php $this->msg( 'tagline' ) ?></div>
				<!-- /tagline -->
			<?php endif; ?>
			<!-- subtitle -->
			<div id="contentSub" <?php $this->html( 'userlangattributes' ) ?>><?php $this->html( 'subtitle' ) ?></div>
			<!-- /subtitle -->
			<?php if ( $this->data['undelete'] ): ?>
				<!-- undelete -->
				<div id="contentSub2"><?php $this->html( 'undelete' ) ?></div>
				<!-- /undelete -->
			<?php endif; ?>
			<?php if( $this->data['newtalk'] ): ?>
				<!-- newtalk -->
				<div class="usermessage"><?php $this->html( 'newtalk' )  ?></div>
				<!-- /newtalk -->
			<?php endif; ?>
			<?php if ( $this->data['showjumplinks'] ): ?>
				<!-- jumpto -->
				<div id="jump-to-nav" class="mw-jump">
					<?php $this->msg( 'jumpto' )?>
					<a href="#mw-navigation"><?php $this->msg( 'jumptonavigation' ) ?></a><?php $this->msg( 'comma-separator' )?>
					<a href="#p-search"><?php $this->msg( 'jumptosearch' ) ?></a>
				</div>
				<!-- /jumpto -->
			<?php endif; ?>
			<!-- bodycontent -->
			<?php $this->html( 'bodycontent' )?>
			<!-- /bodycontent -->
			<?php if ( $this->data['printfooter'] ): ?>
				<!-- printfooter -->
				<div class="printfooter">
					<?php $this->html( 'printfooter' ); ?>
				</div>
				<!-- /printfooter -->
			<?php endif; ?>
			<?php if ( $this->data['catlinks'] ): ?>
				<!-- catlinks -->
				<?php $this->html( 'catlinks' ); ?>
				<!-- /catlinks -->
			<?php endif; ?>
			<?php if ( $this->data['dataAfterContent'] ): ?>
				<!-- dataAfterContent -->
				<?php $this->html( 'dataAfterContent' ); ?>
				<!-- /dataAfterContent -->
			<?php endif; ?>
			<div class="visualClear"></div>
			<!-- debughtml -->
			<?php $this->html( 'debughtml' ); ?>
			<!-- /debughtml -->
		</div>
		<!-- /bodyContent -->
	</div>
	<!-- /kata-content -->
</div>
