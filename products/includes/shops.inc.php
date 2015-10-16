<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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
// Module: shops.inc.php
// Description: generates a list of shopping mall shops
// Input variables: catalog = where to link the shops
// layout = 1 : default listing using a drop down box
// layout = 2 : unordered list for further layout with CSS

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Select shop...
if ((isset($_GET["shop"]) || isset($_POST["shop"]))) {
	if ($_GET["shop"]) $newshop = $_GET["shop"];
	if ($_POST["shop"]) $newshop = $_POST["shop"];
	unset($shop);
	$shop = $newshop;
}
if (!$shop || !is_numeric($shop)) $shop = "1";

// Check selected theme for language support...
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($lang && is_array($themelanguages)) if (!in_array("$lang",$themelanguages)) unset($lang);

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/catalogue.inc.php";

// Validate $catalog...
str_ireplace("http://","",$catalog);
str_ireplace("https://","",$catalog);
if (empty($catalog)) $catalog = "index.php";

// Check for manufacturers...
$sql="SELECT userid, shopname FROM user WHERE userid>'1' ORDER BY shopname";
$result = @mysqli_query($db, $sql);

if (@mysqli_num_rows($result)) {

	// List manufacturers...
	if ($layout == "2") echo "<ul>\n";
	else echo "<select name=\"shop\"><option value=\"1\">".MAINSHOP."</option>\n";

	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$shopid = @mysqli_result($result, $i, "userid");
		$shopname = @mysqli_result($result, $i, "shopname");
		if ($layout == "2") echo "<li class=\"ashopcategory\"><a href=\"{$catalog}?shop=$shopid\">$shopname</a></li>";
		else {
			echo "<option value=\"{$shopid}\""; if ($shopid == $shop) echo " selected"; echo ">$shopname</option>\n";
		}
	}

	if ($layout == "2") echo "</ul>";
	else echo "</select>";
}

$layout = "";
?>