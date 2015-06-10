<?php

#
# Special:ManualSitemap MediaWiki extension
# Version 1.2
#
# Copyright  2006 Fran&ccedil;ois Boutines-Vignard, 2008-2012 Jehy.
#
# A special page to generate Google Sitemap XML files.
# see http://www.google.com/schemas/sitemap/0.84/sitemap.xsd for details.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html
#
# Revisions:
#GoogleSitemap
# 0.0.2: date format correction, lighter markup. (2006/09/15)
# 0.0.3: added 'priority' and 'changefreq' tags management in the 'Options' form. (2006/09/16)
# 0.0.4: Unicode support, gmdate format, exponential and quadratic priorities. (2006/09/17)
# 0.0.5: Possibility to sort by last page revision. (2006/09/19)

#ManualSitemap
# 0.1: Jehy took maintenance. Bugfix, new options (2008/11/12)
# 0.2: Thomas added functions for excluding pages, warning if notify fails and setting of servers base url (2009/04/08)
# 1.0: Script rewritten, allowing easier usage (2009/11/30)
# 1.1: Added discussion pages exclusion option
# 1.2: Fixed compatibility issues for MW 1.19.2


$wgExtensionCredits['specialpage'][] = array (
	'path'=>__FILE__,
	'name' => 'Special:ManualSitemap',
	'description' => 'Adds a [[Special:ManualSitemap|special page]] to create a XML Google Sitemap file, along with some reporting.',
	'url' => 'http://jehy.ru/wiki-extensions.en.html',
	'author' => 'Fran&#231;ois Boutines-Vignard, Jehy http://jehy.ru/index.en.html, [http://www.thomas-schweitzer.de Thomas]',
	'version' => '1.2'
);
$wgSpecialPages['ManualSitemap'] = 'SpecialManualSitemap';
$wgAutoloadClasses['SpecialManualSitemap'] 		= dirname( __FILE__ ) . '/SpecialManualSitemap.php';
$wgAvailableRights[] = 'manualsitemap';
$wgGroupPermissions['bureaucrat']['manualsitemap'] = true;
$wgGroupPermissions['sysop']['manualsitemap'] = true;
?>
