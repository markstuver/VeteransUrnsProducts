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
// --------------------------------------------------------------------
// Module: menu.inc.php
// Description: generates a list of menu items
// Input variables: lang = language code, shop = shopping mall member ID

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Handle links to and from subdirectories...
$subdir = "";
if (strstr($_SERVER['REQUEST_URI'],"affiliate/")) $subdir = "affiliate/";
else if (strstr($_SERVER['REQUEST_URI'],"members/")) $subdir = "members/";
else if (strstr($_SERVER['REQUEST_URI'],"wholesale/")) $subdir = "wholesale/";
else if (strstr($_SERVER['REQUEST_URI'],"emerchant/")) $subdir = "emerchant/";
else if (strstr($_SERVER['REQUEST_URI'],"payment/")) $subdir = "payment/";

// Select shop...
if ((isset($_GET["shop"]) || isset($_POST["shop"]))) {
	if ($_GET["shop"]) $newshop = $_GET["shop"];
	if ($_POST["shop"]) $newshop = $_POST["shop"];
	unset($shop);
	$shop = $newshop;
}
if (!$shop || !is_numeric($shop)) {
	$shop = "1";
	$shopurlstring = "";
} else $shopurlstring = "&shop=$shop";

if (!$membershops) $shopsearch = "%";
else $shopsearch = $shop;

// Validate language...
if (isset($lang)) {
	$lang = strtolower($lang);
	if (strlen($lang) != 2) $lang = "";
	if (!preg_match("/^[a-z]*$/", $lang)) $lang = "";
}
if (!$lang) $lang = $defaultlanguage;

$sql = "SELECT itemid, caption, url FROM menuitem WHERE itemid=parentitemid AND (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber";
$result = @mysqli_query($db, $sql);
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	echo "<ul>\n";
	$caption = @mysqli_result($result, $i, "caption");
	$caption = str_replace("&amp;","&",$caption);
	$caption = str_replace("&","&amp;",$caption);
	$menuitemurl = @mysqli_result($result, $i, "url");
	if (!empty($subdir) && substr($menuitemurl,0,7) != "http://" && substr($menuitemurl,0,8) != "https://") {
		if (strstr($menuitemurl,$subdir)) $menuitemurl = str_replace("$subdir","",$menuitemurl);
		else $menuitemurl = "../$menuitemurl";
	}
	$menuitemid = @mysqli_result($result, $i, "itemid");
	echo "\t<li";
	if (strlen($caption) > 13) echo " style=\"width: 150px\"";
	echo "><a href=\"$menuitemurl\"";
	if ($device) echo " data-ajax=\"false\"";
	echo ">$caption</a>";
	$subsql = "SELECT itemid, caption, url FROM menuitem WHERE parentitemid='$menuitemid' AND itemid!='$menuitemid' AND (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber";
	$subresult = @mysqli_query($db, $subsql);
	if (@mysqli_num_rows($subresult)) echo "\n\t\t<ul>\n";
	for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
		$subcaption = @mysqli_result($subresult, $j, "caption");
		$subcaption = str_replace("&amp;","&",$subcaption);
		$subcaption = str_replace("&","&amp;",$subcaption);
		$submenuitemurl = @mysqli_result($subresult, $j, "url");
		if (!empty($subdir) && substr($submenuitemurl,0,7) != "http://" && substr($submenuitemurl,0,8) != "https://") {
			if (strstr($submenuitemurl,$subdir)) $submenuitemurl = str_replace("$subdir","",$submenuitemurl);
			else $submenuitemurl = "../$submenuitemurl";
		}
		echo "\t\t\t<li><a href=\"$submenuitemurl\"";
		if ($device) echo " data-ajax=\"false\"";
		echo ">$subcaption</a></li>\n";
	}
	if (@mysqli_num_rows($subresult)) echo "\t\t</ul>\n";
	echo "</li>\n</ul>\n";
}
?>