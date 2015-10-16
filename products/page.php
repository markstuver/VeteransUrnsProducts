<?php
// AShop
// Copyright 2012 - AShop Software - http://www.ashopsoftware.com
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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
if (isset($id) && !is_numeric($id)) $id = "";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/pages.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/pages-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/pages-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/pages.html");

// Get page content...
$feature = "AShopPage";
include "includes/product.inc.php";
$feature = "";

// Print footer from templates...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/pages-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/pages-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/pages.html");
?>