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
// Module: manufacturers.inc.php
// Description: generates a list of product categories
// Input variables: shop = shopping mall shop ID, catalog = where to link the manufacturers
// layout = 1 : default listing using tables
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
if (!$shop || !is_numeric($shop)) {
	$shop = "1";
	$shopurlstring = "";
} else $shopurlstring = "&amp;shop=$shop";

if (!$membershops) $shopsearch = "%";
else $shopsearch = $shop;

// Check selected theme for language support...
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($lang && is_array($themelanguages)) if (!in_array("$lang",$themelanguages)) unset($lang);

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/catalogue.inc.php";

// REQUEST_URI fix for Windows+IIS...
if (!isset($REQUEST_URI) and isset($_SERVER['SCRIPT_NAME'])) {
	$REQUEST_URI = $_SERVER['SCRIPT_NAME'];
	if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $REQUEST_URI .= '?' . $_SERVER['QUERY_STRING'];
}

// Get the URL to this page...
if ($_SERVER['HTTPS'] == "on") $linksurl = "https://";
else $linksurl = "http://";
$requesturi = explode("?",$REQUEST_URI);
$patharray = explode("/",$requesturi[0]);
$newpath = "";
if ($catalog) {
	foreach($patharray as $pathnumber=>$pathpart) if ($pathnumber != count($patharray)-1) $newpath .= "$pathpart/";
	$newpath .= "$catalog";
} else $newpath = $requesturi[0];
$linksurl .= $_SERVER['HTTP_HOST'].$newpath;
$linksurl .= "?";
$linksurl = str_replace("/catalog/vendor","/",$linksurl);
$linksurl = str_replace("/catalog/","/",$linksurl);
$linksurl = str_replace("/catalog?","/",$linksurl);
$linksurl = str_replace("/affiliate/","/",$linksurl);
$linksurl = str_replace("/members/","/",$linksurl);

// Check for manufacturers...
if ($shop == "1") $sql="SELECT DISTINCT(manufacturer) FROM product WHERE manufacturer != '' AND manufacturer IS NOT NULL AND (userid LIKE '$shopsearch' OR inmainshop='1') AND active='1' ORDER BY manufacturer";
else $sql="SELECT DISTINCT(manufacturer) FROM product WHERE manufacturer != '' AND manufacturer IS NOT NULL AND userid LIKE '$shopsearch' AND active='1' ORDER BY manufacturer";
$result = @mysqli_query($db, $sql);

if (@mysqli_num_rows($result)) {

	// List manufacturers...
	if ($layout == "2") echo "<ul>\n";

	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$manufacturer = @mysqli_result($result, $i, "manufacturer");
		if ($searchstring == $manufacturer && $m == "1") $cssclass = "ashopselectedcategory";
		else $cssclass = "ashopcategory";
		if ($layout == "2") echo "<li class=\"$cssclass\"><a href=\"{$linksurl}searchstring=$manufacturer&m=1$shopurlstring\">$manufacturer</a></li>";
		else {
			echo "<table cellspacing=\"0\" class=\"$cssclass\"><tr><td width=\"16\" valign=\"top\"><img src=\"$ashopurl/images/invisible.gif\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"invisible\" /></td><td><a href=\"{$linksurl}searchstring=$manufacturer&amp;m=1$shopurlstring\" style=\"text-decoration: none\"><span class=\"{$cssclass}text\">$manufacturer</span></a></td></tr></table>
			";
		}
	}

	if ($layout == "2") echo "</ul>";
	else echo "";
}

$layout = "";
?>