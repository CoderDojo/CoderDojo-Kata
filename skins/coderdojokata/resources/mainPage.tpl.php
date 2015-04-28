
<!--Page 0 markup begins here-->

<div class="container-fluid">
	<div class="row dl-menu">
		<div class="col-xs-4 kata-home-panel organise">Organise</div>
		<div class="col-xs-4 kata-home-panel mentors">Mentors</div>
		<div class="col-xs-4 kata-home-panel ninjas" style="">Ninjas</div>
	</div>
</div>


<div class="row">
	<div class="col-md-12">
		<div class="logo kata home">
			<a href="https://coderdojo.com"><img alt="CoderDojo.org"
				src="<?php echo $viewHelper["ImagePath"], "logo.png"; ?>"
				width="48px" height="48px"> <span class="kata-logo-text"><?php echo $viewHelper["SiteName"]; ?></span></a>
		</div>
		<span class="logo-text hidden-xs" style="">An open forum for the
			CoderDojo community to share resources with one another. </span>
	</div>
</div>


<div class="container-fluid">
<!-- 
	<div class="col-xs-4 kata kata-panel " style="">
		<div class="organise"></div>
		<img width="500"
			src="https://coderdojo.org/wp-content/uploads/2014/07/communitysupportmilano.png"
			alt="communitysupportmilano">
		<div style="" class="details">
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam
				molestie placerat metus, sed volutpat purus porta nec. Nunc eleifend
				nulla id lectus congue, tempus dignissim orci scelerisque. Sed a
				scelerisque ante. Vestibulum eget ante non velit blandit
				scelerisque. Cras et porta nibh. Nam molestie tortor sed felis
				tristique, nec feugiat eros tempus. Donec facilisis erat non libero
				tempus, id efficitur diam maximus. In at ante ligula. Sed ac tortor
				lectus. Praesent elementum pulvinar eleifend. Nullam facilisis sem
				sit amet nunc fermentum, sed euismod ante malesuada.</p>
		</div>
		<div class=" kata-home-panel kata-home-panel-button organise-button ">
			<a href="learnMore.html">Learn More</a>

		</div>
	</div>
	<div class="col-xs-4 kata kata-panel " style="">
		<div class="mentors"></div>
		<img width="500"
			src="https://coderdojo.org/wp-content/uploads/2014/07/communitysupportmilano.png"
			alt="communitysupportmilano">
		<div class="details">
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam
				tincidunt vel neque id placerat. Sed luctus ligula nec convallis
				rhoncus. Sed iaculis, quam congue rutrum lobortis, tellus nulla
				ultricies orci, et molestie urna magna eget leo. Etiam pretium
				pharetra massa non accumsan. Ut ultricies dolor nec dui vulputate
				tincidunt. Ut eget dolor eget tortor tempus congue at id justo. Nam
				et mauris id elit vulputate vestibulum. Ut sodales venenatis lectus
				quis euismod. Sed scelerisque auctor pharetra. Praesent pretium
				hendrerit risus at auctor. Phasellus malesuada leo ut odio posuere,
				id bibendum eros mollis.</p>
		</div>
		<div
			class=" kata-home-panel kata-home-panel-button view-tutorials-button">
			<a href="learnMore.html">View Tutorials</a>

		</div>
	</div>
	<div class="col-xs-4 kata kata-panel " style="">
		<div class="ninjas"></div>
		<img width="500"
			src="https://coderdojo.org/wp-content/uploads/2014/07/communitysupportmilano.png"
			alt="communitysupportmilano">
		<div class="details">
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.
				Vestibulum sed felis ullamcorper, posuere dolor sed, volutpat
				tortor. Nulla vestibulum neque quis odio porta, eget pulvinar tortor
				dapibus. Nulla aliquet purus eu pellentesque commodo. In quis ante
				tortor. Vivamus hendrerit luctus mauris id iaculis. Quisque
				consectetur, purus sed eleifend lacinia, massa libero aliquet ante,
				in euismod sem ligula sed nisl. Nulla leo ipsum, ornare vel blandit
				lobortis, iaculis id felis. Phasellus odio leo, tempus dignissim
				eleifend ac, rhoncus quis libero. Pellentesque sodales, nisi ut
				finibus elementum, est risus facilisis metus, at vestibulum enim
				magna quis nulla. Maecenas quis libero ac turpis mollis blandit at
				accumsan felis. Sed et nisl ligula. Nam eget vulputate arcu, sit
				amet molestie ligula. In cursus nec erat vitae aliquam. Sed turpis
				magna, ultricies eu posuere ac, porttitor in ante.</p>
		</div>
		<div class=" kata-home-panel kata-home-panel-button explore-button ">
			<a href="learnMore.html">Explore</a>

		</div>
	</div>
 -->
	<div class="row same-height"><?php $this->html( 'bodycontent' ) ?></div>
<script>
(function() {
function fixHeights() {
	$('.same-height').each(function() {
		var row = $(this);
		var cols = row.find('.kata-panel .details');
		var max = 0;
		cols.height('auto');
		cols.each(function() {
			var h = $(this).height();
			if (h > max) {
				max = h;
			}
		});
		cols.height(max);
	});
}
fixHeights();
$(window).on('resize', fixHeights);
}());
</script>
</div>
<!--Page 0 markup ends here-->
