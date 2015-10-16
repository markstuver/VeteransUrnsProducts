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
// Module: categories.inc.php
// Description: generates a list of product categories
// Input variables: cat = category ID

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Validate variables...
if ($cat && !is_numeric($cat)) {
	$cat = stripslashes($cat);
	$cat = @mysqli_real_escape_string($db, $cat);
	$cat = strtolower($cat);
	$cat = str_replace("\'","",$cat);
	$cat = str_replace("\"","",$cat);
	$cat = str_replace("/","",$cat);
	$cat = str_replace("\n","",$cat);
	$cat = str_replace(";","",$cat);
	$cat = str_replace("select","",$cat);
	$cat = str_replace("insert","",$cat);
	$cat = str_replace("update","",$cat);
	$cat = str_replace("delete","",$cat);
	$cat = str_replace("create","",$cat);
	$cat = str_replace("modify","",$cat);
	$cat = str_replace("password","",$cat);
	$cat = str_replace("user","",$cat);
	$cat = str_replace("concat","",$cat);
	$cat = str_replace("from","",$cat);
	$cat = str_replace("username","",$cat);
	$cat = str_replace("<","",$cat);
	$cat = str_replace(">","",$cat);
	$findcatbyname = TRUE;
} else $findcatbyname = FALSE;

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/storefront.inc.php";

// Search for category by name...
if ($findcatbyname) {
	$result = @mysqli_query($db, "SELECT categoryid FROM category WHERE upper(name) LIKE '%".strtoupper($cat)."%'");
	if (@mysqli_num_rows($result)) {
		$cat = @mysqli_result($result,0,"categoryid");
	}
}

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
$linksurl .= $HTTP_HOST.$newpath;
$parametersarray = explode("&",str_replace("?","",$requesturi[1]));
$newparameters = "";
foreach($parametersarray as $paramnumber=>$parameterpair) {
	$parameter = explode("=",$parameterpair);
	if ($parameter[0] != "exp" && $parameter[0] != "cat" && $parameter[0] != "searchstring" && $parameter[0] != "showresult" && $parameter[0] != "msg" && $parameter[0] != "resultpage" && $parameter[0] != "search" && $parameter[0] != "categories" && $paramnumber != count($parametersarray)-1) $newparameters .= $parameter[0]."=".$parameter[1]."&";
}
if ($newparameters) $linksurl .= "?$newparameters";
else $linksurl .= "?";

// Update product list...
if ($targetframe && $cat) echo "
<script language=\"javascript\">
<!--
	top.$targetframe.location='index.php?search=$search&cat=$cat';
-->
</script>";

// Generate breadcrumb path...
if (!empty($cat) && is_numeric($cat)) {
	$catcheck = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$cat'");
	if (@mysqli_num_rows($catcheck)) {
		$parentname = "";
		$grandparentname = "";
		$parentcat = @mysqli_result($catcheck,0,"parentcategoryid");
		$grandparentcat = @mysqli_result($catcheck,0,"grandparentcategoryid");
		$categoryname = @mysqli_result($catcheck, 0, "name");
		$categoryid = @mysqli_result($catcheck, 0, "categoryid");
		if ($parentcat != $cat) {
			$parentcheck = @mysqli_query($db, "SELECT * FROM category WHERE parentcategoryid = '$parentcat'");
			$parentname = @mysqli_result($parentcheck, 0, "name");
		}
		if ($grandparentcat != $cat) {
			$grandparentcheck = @mysqli_query($db, "SELECT * FROM category WHERE grandparentcategoryid = '$grandparentcat'");
			$grandparentname = @mysqli_result($grandparentcheck, 0, "name");
		}
		echo "<table class=\"ashoppageheader\"><tr><td valign=\"top\"><a href=\"{$linksurl}\"><span class=\"ashoppageheadertext2\">".HOME."</span></a><span class=\"ashoppageheadertext2\"> &raquo; </span>";
		if (!empty($grandparentname)) echo "<a href=\"{$linksurl}cat=$grandparentcat&exp=$grandparentcat\"><span class=\"ashoppageheadertext2\">$grandparentname</span></a><span class=\"ashoppageheadertext2\"> &raquo; </span>";
		if (!empty($parentname)) echo "<a href=\"{$linksurl}cat=$parentcat&exp=$parentcat\"><span class=\"ashoppageheadertext2\">$parentname</span></a><span class=\"ashoppageheadertext2\"> &raquo; </span>";
		echo "<span class=\"ashoppageheadertext1\">$categoryname</span></td></tr></table>";
	}
}
?>