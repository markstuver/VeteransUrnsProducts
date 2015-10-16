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
// Input variables: cat = category ID, exp = expanded category ID
// layout = 1 : default listing using tables
// layout = 2 : unordered list for further layout with CSS

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
	$checkexp = str_replace("|","",$exp);
	if (!is_numeric($checkexp)) unset($exp);

	// Select shop...
	if ((isset($_GET["shop"]) || isset($_POST["shop"]))) {
		if ($_GET["shop"]) $newshop = $_GET["shop"];
		if ($_POST["shop"]) $newshop = $_POST["shop"];
		unset($shop);
		$shop = $newshop;
	}
	if (!$shop) {
		$shop = "1";
		$shopurlstring = "";
	} else $shopurlstring = "&shop=$shop";

	if (!$membershops) $shopsearch = "%";
	else $shopsearch = $shop;

	// Apply selected theme...
	$buttonpath = "";
	$templatepath = "/templates";
	if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
	if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
	if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
	if ($lang && is_array($themelanguages)) {
		if (!in_array("$lang",$themelanguages)) unset($lang);
	}

	// Include language file...
	if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
	include "language/$lang/catalogue.inc.php";

	// Get member template path if no theme is used...
	if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/$ashopuser/catalogue.html")) $templatepath = "/members/$ashopuser";

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

// Check catalog type...
if (isset($_GET["catalogtype"]) || isset($_POST["catalogtype"]) || isset($_COOKIE["catalogtype"])) unset($catalogtype);
if (isset($catalogtype) && ($catalogtype != "rt" && $catalogtype != "ws")) unset($catalogtype);
if (!isset($catalogtype)) $catalogtype = "rt";
if ($catalogtype == "ws") {
	$shop = "1";
	$shopsearch = "1";
}

// Convert exclude list to array...
if (!empty($exclude)) {
	$excludecategories = explode("|",$exclude);
	foreach($excludecategories as $excludecategory) if (!is_numeric($excludecategory)) {
		$excludecategories = array();
		$exclude = "";
	}
}

// List categories...
	if ($catalogtype == "rt") {

		// Check which categories contain retail products...
		if ($hideemptycategories && (empty($cattree) || !is_numeric($cattree))) {
			if ($shop > 1) $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product WHERE productcategory.productid=product.productid AND product.active='1' AND product.userid='$shop'");
			else $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product WHERE productcategory.productid=product.productid AND product.active='1'");
			while ($row = @mysqli_fetch_array($result)) {
				$usecategories[] = $row["categoryid"];
				$result2 = @mysqli_query($db, "SELECT parentcategoryid, grandparentcategoryid FROM category WHERE categoryid='{$row["categoryid"]}'");
				$retailparent = @mysqli_result($result2, 0, "parentcategoryid");
				$retailgrandparent = @mysqli_result($result2, 0, "grandparentcategoryid");
				if ($retailparent != $row["categoryid"]) $usecategories[] = $retailparent;
				if ($retailgrandparent != $row["categoryid"]) $usecategories[] = $retailgrandparent;
			}
		} else $usecategories = "all";
	} else if ($catalogtype == "ws") {

		// Check which categories contain wholesale products...
		if ($hideemptycategories && (empty($cattree) || !is_numeric($cattree))) {
			$result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product WHERE productcategory.productid=product.productid AND product.wholesaleactive='1' AND product.userid='1'");
			while ($row = @mysqli_fetch_array($result)) {
				$usecategories[] = $row["categoryid"];
				$result2 = @mysqli_query($db, "SELECT parentcategoryid, grandparentcategoryid FROM category WHERE categoryid='{$row["categoryid"]}'");
				$wholesaleparent = @mysqli_result($result2, 0, "parentcategoryid");
				$wholesalegrandparent = @mysqli_result($result2, 0, "grandparentcategoryid");
				if ($wholesaleparent != $row["categoryid"]) $usecategories[] = $wholesaleparent;
				if ($wholesalegrandparent != $row["categoryid"]) $usecategories[] = $wholesalegrandparent;
			}
		} else $usecategories = "all";
	}
	$subcat = 0;
	if (!empty($cat) && is_numeric($cat)) {
		$subcatcheck = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$cat'");
		$parentcat = @mysqli_result($subcatcheck,0,"parentcategoryid");
		$grandparentcat = @mysqli_result($subcatcheck,0,"grandparentcategoryid");
		if ($parentcat == $cat && $grandparentcat == $cat) $sql = "SELECT * FROM category WHERE grandparentcategoryid='$cat' AND categoryid=parentcategoryid AND categoryid!=grandparentcategoryid AND (userid LIKE '$shopsearch' OR memberclone='1')";
		else {
			$subcat = $cat;
			$sql = "SELECT * FROM category WHERE parentcategoryid = '$cat' AND categoryid != grandparentcategoryid AND categoryid != parentcategoryid AND (userid LIKE '$shopsearch' OR memberclone='1') ORDER BY ordernumber";
		}
	} else $sql="SELECT * FROM category WHERE grandparentcategoryid = categoryid AND (userid LIKE '$shopsearch' OR memberclone='1') AND (language = '$lang' OR language = 'any' OR language IS NULL OR language='') ORDER BY ordernumber";
    $result = @mysqli_query($db, $sql);
	$itemnumber = 1;
	$cellwidthpercent = round(100/$directorycolumns);
	if (@mysqli_num_rows($result)) {
		echo "<table class=\"ashopdirectorytable\" cellpadding=\"10\" cellspacing=\"0\">
	  ";
    for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	  $explink = "";
      $categoryname = @mysqli_result($result, $i, "name");
	  $categorydescription = @mysqli_result($result, $i, "description");
      $categoryid = @mysqli_result($result, $i, "categoryid");
	  if ($usecategories != "all" && (!is_array($usecategories) || !in_array($categoryid, $usecategories))) continue;
	  if (is_array($excludecategories) && in_array($categoryid, $excludecategories)) continue;
	  $result2 = @mysqli_query($db, "SELECT * FROM category WHERE grandparentcategoryid = '$categoryid' AND grandparentcategoryid != categoryid AND (userid LIKE '$shopsearch' OR memberclone='1')");
	  if (file_exists("$ashoppath/images/directoryicon.gif")) $itemimage = "directoryicon.gif";

	  // Get product count...
	  if ($shop == "1") $userselectstring = "(product.userid LIKE '$shopsearch' OR inmainshop='1')";
	  else $userselectstring = "product.userid LIKE '$shopsearch'";
	  if ($catalogtype == "rt" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND (category.categoryid='$categoryid' OR category.grandparentcategoryid='$categoryid' OR category.parentcategoryid='$categoryid') AND product.productid=productcategory.productid AND $userselectstring AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.active='1'");
	  else if ($catalogtype == "ws" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND (category.categoryid='$categoryid' OR category.grandparentcategoryid='$categoryid' OR category.parentcategoryid='$categoryid') AND product.productid=productcategory.productid AND product.userid='1' AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.wholesaleactive='1'");
	  $productcount = @mysqli_num_rows($prodcountresult);
	  if (empty($productcount)) $productcount = 0;
	  if (!empty($cat) && is_numeric($cat)) $subsql = "SELECT * FROM category WHERE parentcategoryid = '$categoryid' AND categoryid != grandparentcategoryid AND categoryid != parentcategoryid AND (userid LIKE '$shopsearch' OR memberclone='1') ORDER BY ordernumber";
	  else $subsql = "SELECT * FROM category WHERE grandparentcategoryid = '$categoryid' AND categoryid != grandparentcategoryid AND categoryid = parentcategoryid AND (userid LIKE '$shopsearch' OR memberclone='1') ORDER BY ordernumber";
	  if (!empty($subcat)) $subsql = "";
	  $subresult = @mysqli_query($db, $subsql);
	  $catexplink = "";
	  if (@mysqli_num_rows($subresult)) $catexplink = "$categoryid|";
	  if ($itemnumber == 1) echo "<tr>";
	  echo "<td class=\"ashopdirectoryitem\" valign=\"top\" width=\"{$cellwidthpercent}%\">
	  ";
	  if ($itemimage) echo "<img src=\"$ashopurl/images/$itemimage\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"$categoryname\" />";
	  echo "<a href=\"{$linksurl}";
	  if ($catexplink) echo "exp=$catexplink&";
	  echo "cat=$categoryid$shopurlstring\"><b>$categoryname</b></a>";
	  if ($enableproductcount) echo " ($productcount)";
	  echo "
	  ";
	  $subitemlist = "";
	  $subitemnumber = 0;
	  while ($subitemrow = @mysqli_fetch_array($subresult)) {
		  if (strlen($subitemlist) > 300) break;
		  $explink = "";
		  $subcategoryname = $subitemrow["name"];
		  $subcategoryid = $subitemrow["categoryid"];
		  if ($usecategories != "all" && (!is_array($usecategories) || !in_array($subcategoryid, $usecategories))) continue;
		  if (is_array($excludecategories) && in_array($subcategoryid, $excludecategories)) continue;
		  $subitemlist .= "
		  <a href=\"{$linksurl}cat=$subcategoryid$shopurlstring\"><span class=\"ashopdirectorysubitem\">$subcategoryname</span></a>, ";
		  $subitemnumber++;
	  }
	  if (@mysqli_num_rows($subresult) > 4) $subitemlist .= "...";
	  else $subitemlist = substr($subitemlist,0,-2);
	  if (!empty($subitemlist)) echo "<br />$subitemlist";

	  if (!empty($categorydescription)) echo "<p class=\"ashopdirectorydescription\">$categorydescription</p>";
	  else echo "<br />";
	  echo "</td>";
	  $itemnumber++;
	  if ($itemnumber > $directorycolumns) {
		  echo "</tr>";
		  $itemnumber = 1;
	  }
	}
	if ($itemnumber <= $directorycolumns) {
		while ($itemnumber <= $directorycolumns) {
			echo "<td class=\"ashopdirectoryitem\" valign=\"top\" width=\"{$cellwidthpercent}%\">&nbsp;</td>";
			$itemnumber++;
		}
		echo "</tr>";
	}
echo "</table>";
	}


// Close database...
if ($dbOpenedLocally) {
	@mysqli_close($db);
	$databaseserver = "";
	$databaseuser = "";
}
?>