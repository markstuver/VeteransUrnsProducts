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
// Input variables: cat = category ID, exp = expanded category ID
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
if (!$shop || !is_numeric($shop)) $shop = "1";

// Get shop details...
if ($shop != "1") {
	$result = @mysqli_query($db, "SELECT shopname, username FROM user WHERE userid='$shop'");
	$ashopuser = @mysqli_result($result, 0, "username");
	$ashopname = @mysqli_result($result, 0, "shopname");
}

if (!empty($ashopuser) && $ashopuser != "ashopadmin" && file_exists("$ashoppath/members/files/$ashopuser/logo.gif")) $ashoplogohtml = "<img src=\"$ashopurl/members/files/$ashopuser/logo.gif\" alt=\"$ashopname\" border=\"0\">";
else $ashoplogohtml = "<img src=\"$ashopurl/images/logo.gif\" alt=\"$ashopname\" border=\"0\">";
?>