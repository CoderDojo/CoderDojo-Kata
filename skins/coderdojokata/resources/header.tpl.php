<header>
	<div class="row kata">
		<div class="col-md-12">
			<div class="logo kata">
				<a href="<?php echo $viewHelper["ArticlePath"], "Main_Page"; ?>"><img alt="CoderDojo.org"
					src="<?php echo $viewHelper["ImagePath"], "logo.png"; ?>" width="48px"
					height="48px"> <span class="kata-logo-text"> <?php echo $viewHelper["SiteName"]; ?></span>
				</a>
			</div>
			<div class="login kata">
			<span ><a class="new" href="<?php echo $viewHelper["ArticlePath"], "How_To_Publish_Contents_On_Kata"; ?>">How to publish contents</a></span> - 
				<?php 
					$this->html( 'userlangattributes' );
					$personalTools = $this->getPersonalTools();
					foreach ( $personalTools as $key => $item ) {
						echo $this->makeListItem( $key, $item, array("tag" => "span") );
					}
				?>
			</div>
		</div>
	</div>
</header>