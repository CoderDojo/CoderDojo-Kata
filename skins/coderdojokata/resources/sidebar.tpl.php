
<ul class="nav nav-pills nav-stacked">
        <li class="main"><a href="<?php echo $viewHelper["ArticlePath"], "Main_Page"; ?>">Home</a></li>

        <li id="KataMenuItem" class="main dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Kata <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "About_Kata"; ?>">About</a>
                        </li>
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "How_To_Publish_Contents_On_Kata"; ?>">How to publish contents</a>
                        </li>
                </ul>
        </li>

        <li id="OrganiserMenuItem" class="organiser-resource dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Organiser Resources <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Organiser_Resource:Organiser_Resources"; ?>">Learn more</a>
                        </li>
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Organiser_Resource:How_to_start_a_dojo"; ?>">How to start a dojo</a>
                        </li>
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Organiser_Resource:Top_Tips"; ?>">Top Tips</a>
                        </li>
                </ul>
        </li>

        <li id="TechnicalMenuItem" class="technical-resource dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Technical Resources <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Special:KataMentors"; ?>">Resources</a>
                        </li>
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Technical_Resource:Technical_Resources"; ?>">Learn more</a>
                        </li>
                </ul>
        </li>
		
        <li id="NinjaMenuItem" class="ninja-resource dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">Ninja Resources <span class="caret"></span></a>
                <ul class="dropdown-menu" role="menu">
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Special:KataNinjas"; ?>">Explore</a>
                        </li>
                        <li>
                                <a href="<?php echo $viewHelper["ArticlePath"], "Ninja_Resource:Ninja_Resources"; ?>">Learn more</a>
                        </li>
                </ul>
        </li>
</ul>
