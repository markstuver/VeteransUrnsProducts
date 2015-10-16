<?php
// AShop
// Copyright 2014 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

// Check if the script is run in JavaScript mode...
if ($_GET["mode"] == "js") {
	$mode = "js";

	// Include configuration file and functions...
	if (!$databaseserver || !$databaseuser) include "../admin/config.inc.php";
	if (!function_exists(ashop_mailsafe)) include "../admin/ashopfunc.inc.php";
} else if ($_GET["mode"] == "sl") {
	$mode = "sl";

	// Include configuration file and functions...
	if (!$databaseserver || !$databaseuser) include "../admin/config.inc.php";
	if (!function_exists(ashop_mailsafe)) include "../admin/ashopfunc.inc.php";	
} else {
	$mode = "";

	// Include configuration file and functions...
	if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
	if (!function_exists(ashop_mailsafe)) include "admin/ashopfunc.inc.php";
}

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Validate variables...
if (!is_numeric($items) || $items > 10 || $items < 0) $items = 10;

if (!is_numeric($feature) || $feature > 10 || $feature < 0) $feature = 0;
else $items = 1;

// Get the current featured products from the database...
if ($feature) $featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE featured='$feature'");
else $featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE featured>'0' ORDER BY featured ASC");
if (@mysqli_num_rows($featuredproductresult)) {
	$thisitem = 0;
	while ($featuredproductrow = @mysqli_fetch_array($featuredproductresult)) {
		if ($thisitem >= $items) break;
		$featured = $featuredproductrow["productid"];
		$featuredproducturl = $featuredproductrow["detailsurl"];
		$featuredproductname = $featuredproductrow["name"];
		$featuredproductdescr = $featuredproductrow["description"];
		$featuredproductprice = $currencysymbols[$ashopcurrency]["pre"].number_format($featuredproductrow["price"],2,'.','').$currencysymbols[$ashopcurrency]["post"];
		if (!$featuredproducturl) {
			$featuredproducturl = "$ashopurl/index.php?product=$featured";
		}
		if ($mode == "js") {
			if ($showprice == TRUE) $featuredproductdescr = $featuredproductprice;
			else {
				$featuredproductdescr = str_replace("\r\n","",$featuredproductdescr);
				$featuredproductdescr = str_replace("\n","",$featuredproductdescr);
				$featuredproductdescr = str_replace("'","&#039;",$featuredproductdescr);
				$featuredproductdescr = substr($featuredproductdescr,0,247)."...";
			}
			echo "document.write('<span class=\"ashopproductname\"><a href=\"$featuredproducturl\" style=\"text-decoration: none;\">$featuredproductname</a></span>');\n";
			if (file_exists("$ashoppath/prodimg/$featured.gif")) echo "document.write('<br><br><a href=\"$featuredproducturl\"><img src=\"prodimg/$featured.gif\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>');\n";
			else if (file_exists("$ashoppath/prodimg/$featured.jpg")) echo "document.write('<br><br><a href=\"$featuredproducturl\"><img src=\"prodimg/$featured.jpg\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>');\n";
			echo "document.write('<br><span class=\"ashopproducttext\" style=\"font-weight: bold; font-size: 18px;\">$featuredproductdescr</span>');\n";
		} else if ($mode == "sl") {
			if ($showprice == TRUE) $featuredproductdescr = $featuredproductprice;
			echo "
			<div class=\"contentdiv\">
			<div><a href=\"$featuredproducturl\">$featuredproductname</a></div>
			";
			if (file_exists("$ashoppath/prodimg/$featured.gif")) echo "<img src=\"prodimg/$featured.gif\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"1\" style=\"float: right; margin: 0 0 1px 5px\">";
			else if (file_exists("$ashoppath/prodimg/$featured.jpg")) echo "<img src=\"prodimg/$featured.jpg\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"1\" style=\"float: right; margin: 0 0 1px 5px\">";
			echo "$featuredproductdescr
			</div>";
		} else {
			if ($showprice == TRUE) $featuredproductdescr = $featuredproductprice;
			echo "<span class=\"ashopproductname\"><a href=\"$featuredproducturl\" style=\"text-decoration: none;\">$featuredproductname</a></span>";
			if (file_exists("$ashoppath/prodimg/$featured.gif")) echo "<br><br><a href=\"$featuredproducturl\"><img src=\"prodimg/$featured.gif\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>";
			else if (file_exists("$ashoppath/prodimg/$featured.jpg")) echo "<br><br><a href=\"$featuredproducturl\"><img src=\"prodimg/$featured.jpg\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\"></a><br>";
			echo "<br><span class=\"ashopproducttext\" style=\"font-weight: bold; font-size: 18px;\">$featuredproductdescr</span>";
		}
		$thisitem++;
	}
}

// Clean up...
$items = "";
$mode = "";
?>