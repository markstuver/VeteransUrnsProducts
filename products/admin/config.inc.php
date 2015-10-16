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

// ----------------------------------------------------------------------- //
//																		   //
//      Edit the following parameters to correspond to your database       //
//		server, name and the username + password used to access your	   //
//		database.														   //
//																		   //
// ----------------------------------------------------------------------- //

$databaseserver = "vetProducts.db.12114447.hostedresource.com"; // <-- change to your database server
$databasename = "vetProducts"; // <-- change to your database name
$databaseuser = "vetProducts"; // <-- change to the username for your database
$databasepasswd = "H!alo!3800!"; // <-- change to the password for your database
$noinactivitycheck = "true";
$adminpanelcolor = "7589e7";


// ----------------------------------------------------------------------- //
//																		   //
//						 Do not edit below this!						   //
//																		   //
// ----------------------------------------------------------------------- //

// Get preferences from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename") or die("Error! Could not connect to database server!");
$result = @mysqli_query($db, "SELECT * FROM preferences");
if (@mysqli_num_rows($result)) while ($row = @mysqli_fetch_array($result)) $$row["prefname"] = $row["prefvalue"];
if (isset($_COOKIE["shop"])) $shop = $_COOKIE["shop"];
if (isset($_POST["shop"])) $shop = $_POST["shop"];
if (isset($_GET["shop"])) $shop = $_GET["shop"];
if (isset($shop) && !is_numeric($shop)) unset($shop);
if ((isset($shop) && $shop != "1") || (isset($userid) && $userid != "1")) {
if (isset($shop) && $shop != "1") $memberidnumber = $shop;
else $memberidnumber = $userid;
$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$memberidnumber'");
$row = @mysqli_fetch_array($result);
if ($row["shopname"]) $ashopname = $row["shopname"];
if ($row["theme"]) $ashoptheme = $row["theme"];
if ($row["username"]) $ashopuser = $row["username"];
if ($row["bgcolor"]) $bgcolor = $row["bgcolor"];
if ($row["textcolor"]) $textcolor = $row["textcolor"];
if ($row["linkcolor"]) $linkcolor = $row["linkcolor"];
if ($row["formsbgcolor"]) $formsbgcolor = $row["formsbgcolor"];
if ($row["formstextcolor"]) $formstextcolor = $row["formstextcolor"];
if ($row["itembordercolor"]) $itembordercolor = $row["itembordercolor"];
if ($row["itembgcolor"]) $itembgcolor = $row["itembgcolor"];
if ($row["itemtextcolor"]) $itemtextcolor = $row["itemtextcolor"];
if ($row["categorycolor"]) $categorycolor = $row["categorycolor"];
if ($row["categorytextcolor"]) $categorytextcolor = $row["categorytextcolor"];
if ($row["selectedcategory"]) $selectedcategory = $row["selectedcategory"];
if ($row["selectedcategorytext"]) $selectedcategorytext = $row["selectedcategorytext"];
if ($row["font"]) $font = $row["font"];
if ($row["pageheader"]) $pageheader = $row["pageheader"];
if ($row["pagefooter"]) $pagefooter = $row["pagefooter"];
if ($row["metakeywords"]) $ashopmetakeywords = $row["metakeywords"];
if ($row["metadescription"]) $ashopmetadescription = $row["metadescription"];
if ($row["alertcolor"]) $alertcolor = $row["alertcolor"];
if ($row["catalogheader"]) $catalogheader = $row["catalogheader"];
if ($row["catalogheadertext"]) $catalogheadertext = $row["catalogheadertext"];
if ($row["formsbordercolor"]) $formsbordercolor = $row["formsbordercolor"];
if ($row["itemborderwidth"]) $itemborderwidth = $row["itemborderwidth"];
if ($row["fontsize1"]) $fontsize1 = $row["fontsize1"];
if ($row["fontsize2"]) $fontsize2 = $row["fontsize2"];
if ($row["fontsize3"]) $fontsize3 = $row["fontsize3"];
if ($row["tablesize1"]) $tablesize1 = $row["tablesize1"];
if ($row["tablesize2"]) $tablesize2 = $row["tablesize2"];
}
if (empty($ashoppath) && empty($updating)) {
	header("Location: install.php");
	exit;
}

// Version check...
include "$ashoppath/admin/version.inc.php";
if ($ashopversion != $version && !$updating) die("<b>Error!</b> Version mismatch! Run the update script.");

// Fix incompatible php settings...
if (ini_get("register_globals") != 1 || !get_magic_quotes_gpc()) include "$ashoppath/admin/vars.inc.php";
error_reporting (E_ALL ^ E_NOTICE);
if (strlen($_COOKIE["basket"]) > 800) exit;

// Auction maintenance...
include "$ashoppath/admin/updatebids.php";
?>