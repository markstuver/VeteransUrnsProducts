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
// cattree = top category ID
// level = 1, 2 or 3 - categories, subcategories or subsubcategories
// exclude = category ID1|category ID2|category ID3...

// Include configuration file and functions...
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
	if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

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
	if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

	// Search for category by name...
	if ($findcatbyname) {
		$result = @mysqli_query($db, "SELECT categoryid FROM category WHERE upper(name) LIKE '%".strtoupper($cat)."%'");
		if (@mysqli_num_rows($result)) {
			$cat = @mysqli_result($result,0,"categoryid");
		}
	}

	// Get affiliate exclusion list...
	$excludeproducts = array();
	$excludeproductsquery = "";
	if (!empty($affiliate) && is_numeric($affiliate)) {
		$affiliateresult = @mysqli_query($db, "SELECT excludecategories, excludeproducts FROM affiliate WHERE affiliateid='{$affiliate}'");
		$affiliaterow = @mysqli_fetch_array($affiliateresult);
		$exclude = $affiliaterow["excludecategories"];
		$excludeproducts = explode("|",$affiliaterow["excludeproducts"]);
		if (!empty($excludeproducts)) {
			$excludeproductquerylist = "";
			foreach ($excludeproducts as $thisexcludeproduct) if (!empty($thisexcludeproduct)) $excludeproductquerylist .= "'$thisexcludeproduct', ";
			$excludeproductquerylist = substr($excludeproductquerylist,0,-2);
			if (!empty($excludeproductquerylist)) $excludeproductsquery = " AND product.productid NOT IN (".$excludeproductquerylist.")";
		}
	}

	// Get customer profile and price level...
	if (!empty($_COOKIE["customersessionid"]) && empty($pricelevel)) {
		$customerresult = @mysqli_query($db, "SELECT level FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		if (@mysqli_num_rows($customerresult)) $pricelevel = @mysqli_result($customerresult,0,"level");
		else $pricelevel = 0;
	}
	if ($pricelevel > 0) $catalogtype = "ws";
	else $catalogtype = "rt";

	if ($catalogtype == "ws") $activestring = "wholesaleactive";
	else $activestring = "active";

	// Check for first page content...
	if (empty($firstpageexists)) {
		if ($device == "mobile") $firstpagename = "AShopFirstPageMobile";
		else $firstpagename = "AShopFirstPage";
		$firstpageresult = @mysqli_query($db, "SELECT * FROM product WHERE name='$firstpagename' AND prodtype='content' AND userid='$shop' AND (language='$lang' OR language='any')");
		$firstpageexists = @mysqli_num_rows($firstpageresult);
	}

	// Get default category...
	if (empty($cattree) || !is_numeric($cattree) && empty($numberofcategories)) {
		if ($hideemptycategories) {
			if ($shop > 1) $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.userid LIKE '$shopsearch' AND product.$activestring='1' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
			else $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.$activestring='1' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
		} else $result = @mysqli_query($db, "SELECT categoryid FROM category WHERE (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber");
		$numberofcategories = @mysqli_num_rows($result);
		if ($numberofcategories > 0 && !$cat && !$firstpageexists) $cat = @mysqli_result($result, 0, "categoryid");
	}

	// REQUEST_URI fix for Windows+IIS...
	if (!isset($_SERVER['REQUEST_URI']) and isset($_SERVER['SCRIPT_NAME'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}

	// Get the URL to this page...
	if ($_SERVER['HTTPS'] == "on") $linksurl = "https://";
	else $linksurl = "http://";
	$requesturi = explode("?",$_SERVER['REQUEST_URI']);
	$patharray = explode("/",$requesturi[0]);
	$newpath = "";
	if (empty($catalog)) $catalog = "index.php";
	if ($catalog) {
		foreach($patharray as $pathnumber=>$pathpart) if ($pathnumber != count($patharray)-1) $newpath .= "$pathpart/";
		$newpath .= "$catalog";
	} else $newpath = $requesturi[0];
	$linksurl .= $_SERVER['HTTP_HOST'].$newpath;
	$parametersarray = explode("&",str_replace("?","",$requesturi[1]));
	$newparameters = "";
	foreach($parametersarray as $paramnumber=>$parameterpair) {
		$parameter = explode("=",$parameterpair);
		if (!empty($parameter[0]) && $parameter[0] != "referer" && $parameter[0] != "product" && $parameter[0] != "exp" && $parameter[0] != "shop" && $parameter[0] != "cat" && $parameter[0] != "searchstring" && $parameter[0] != "showresult" && $parameter[0] != "msg" && $parameter[0] != "resultpage" && $parameter[0] != "search" && $parameter[0] != "categories" && $parameter[0] != "m" && $paramnumber != count($parametersarray)) $newparameters .= $parameter[0]."=".$parameter[1]."&amp;";
	}
	if ($newparameters) $linksurl .= "?$newparameters";
	else $linksurl .= "?";
	$linksurl = str_replace("/catalog/vendor/","/",$linksurl);
	$linksurl = str_replace("/catalog/","/",$linksurl);
	$linksurl = str_replace("/catalog?","/",$linksurl);
	$linksurl = str_replace($ashopurl."/","",$linksurl);
	if (!empty($ashopsurl)) $linksurl = str_replace($ashopsurl."/","",$linksurl);
	$linksurl = str_replace($ashopurl,"",$linksurl);
	if (!empty($ashopsurl)) $linksurl = str_replace($ashopsurl,"",$linksurl);
	$linksurl = str_replace("affiliate","..",$linksurl);
	$linksurl = str_replace("members","..",$linksurl);

	// Update product list...
	if ($targetframe && $cat) echo "
	<script language=\"javascript\" type=\"text/javascript\">
	/* <![CDATA[ */
		top.$targetframe.location='index.php?search=$search&cat=$cat';
	/* ]]> */
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
	//foreach($excludecategories as $excludecategory) if (!is_numeric($excludecategory)) {
	//	$excludecategories = array();
	//	$exclude = "";
	//}
}

// List categories...
if ($numberofcategories > 0 || (!empty($cattree) && is_numeric($cattree))) {

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

	if ($cat) {
       $sql="SELECT grandparentcategoryid from category WHERE categoryid = '$cat' AND (userid LIKE '$shop' OR memberclone='1') ORDER BY ordernumber";
       $result = @mysqli_query($db, $sql);
       $grandparent = @mysqli_result($result, 0, "grandparentcategoryid");
    }
	if ($exp) $exparray = explode("|",substr($exp,0,-1));
	else unset($exparray);
	if (!empty($cattree) && is_numeric($cattree)) $sql = "SELECT categoryid, name FROM category WHERE categoryid='$cattree' AND (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any')";
	else $sql="SELECT categoryid, name FROM category WHERE grandparentcategoryid = categoryid AND (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber";
    $result = @mysqli_query($db, $sql);
	if ($layout == "2" && @mysqli_num_rows($result)) {
		if ($device) echo "	<div data-role=\"collapsible\" data-theme=\"b\" data-content-theme=\"d\" data-collapsed=\"1\">\n<h3>".CATEGORIES."</h3>\n<ul data-role=\"listview\" data-inset=\"true\">\n";
		else echo "<ul>\n";
	}
    for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	  $explink = "";
      $catname = @mysqli_result($result, $i, "name");
	  $catname = str_replace("&amp;","&",$catname);
	  $catname = str_replace("&","&amp;",$catname);
      $categoryid = @mysqli_result($result, $i, "categoryid");
	  if ($usecategories != "all" && (!is_array($usecategories) || !in_array($categoryid, $usecategories))) continue;
	  if (is_array($excludecategories) && in_array($categoryid, $excludecategories)) continue;
	  $result2 = @mysqli_query($db, "SELECT * FROM category WHERE grandparentcategoryid = '$categoryid' AND grandparentcategoryid != categoryid AND (userid LIKE '$shop' OR memberclone='1')");
	  if (!@mysqli_num_rows($result2)) {
		  $imagelink = FALSE;
		  $categoryimage = "invisible.gif";
      } else if (isset($exparray) && in_array($categoryid,$exparray)) {
		  $imagelink = TRUE;
		  $categoryimage = "minus.gif";
		  if ($exparray) foreach ($exparray as $expandedcat) if ($expandedcat && $expandedcat != $categoryid) $explink .= $expandedcat."|";
	  } else {
		  $imagelink = TRUE;
		  if (isset($exparray)) foreach ($exparray as $expandedcat) $explink .= $expandedcat."|";
		  $explink .= $categoryid."|";
		  $categoryimage = "plus.gif";
	  }
	  if (!$imagelink || (isset($exparray) && in_array($categoryid,$exparray))) $catexplink = $exp;
	  else $catexplink = $explink;
	  // Get product count...
	  if ($shop == "1") $userselectstring = "(product.userid LIKE '$shopsearch' OR product.inmainshop='1')";
	  else $userselectstring = "product.userid LIKE '$shopsearch'";
	  $subcatresult = @mysqli_query($db, "SELECT * FROM category WHERE (grandparentcategoryid='$categoryid' OR parentcategoryid='$categoryid') AND categoryid!='$categoryid'");
	  if (@mysqli_num_rows($subcatresult)) $catcheck = "(category.categoryid='$categoryid' OR category.grandparentcategoryid='$categoryid' OR category.parentcategoryid='$categoryid')";
	  else $catcheck = "category.categoryid='$categoryid'";
	  if ($catalogtype == "rt" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND $catcheck AND product.productid=productcategory.productid AND $userselectstring AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.active='1'$excludeproductsquery");
	  else if ($catalogtype == "ws" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND $catcheck AND product.productid=productcategory.productid AND product.userid='1' AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.wholesaleactive='1'$excludeproductsquery");
	  $productcount = @mysqli_num_rows($prodcountresult);
	  if (empty($productcount)) $productcount = 0;
	  if ($categoryid == $cat && (empty($level) || $level == "1")) {
		  if ($layout == "2") {
			  echo "<li class=\"ashopselectedcategory\"><a href=\"{$linksurl}exp=$explink&cat=$categoryid$shopurlstring\"";
			  if ($device) echo " data-ajax=\"false\"";
			  echo ">$catname</a>";
		  } else {
			  echo "<table cellspacing=\"0\" class=\"ashopselectedcategory\"><tr><td width=\"16\" valign=\"top\">
			  ";
			  if ($imagelink) {
				  echo "<a href=\"{$linksurl}";
				  if ($explink) echo "exp=$explink&amp;";
				  echo "cat=$cat$shopurlstring\" style=\"text-decoration: none\" rel=\"nofollow\">";
			  }
			  echo "<img src=\"$ashopurl/images/$categoryimage\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"$categoryimage\" />";
			  if ($imagelink) echo "</a>";
			  echo "</td><td><a href=\"{$linksurl}";
			  if ($catexplink) echo "exp=$catexplink&amp;";
			  echo "cat=$categoryid$shopurlstring\" style=\"text-decoration: none\"><span class=\"ashopselectedcategorytext\">$catname</span></a>";
			  if ($enableproductcount) echo "<span class=\"ashopselectedcategorytext\"> ($productcount)</span>";
			  echo "</td></tr></table>
			  ";
		  }
	  } else if (empty($level) || $level == "1") {
		  if ($layout == "2") {
			  echo "<li class=\"ashopcategory\"><a href=\"{$linksurl}exp=$explink&cat=$categoryid$shopurlstring\"";
			  if ($device) echo " data-ajax=\"false\"";
			  echo ">$catname</a>";
		  } else {
			  echo "<table cellspacing=\"0\" class=\"ashopcategory\"><tr><td width=\"16\" valign=\"top\">
			  ";
			  if ($imagelink) {
				  echo "<a href=\"{$linksurl}";
				  if ($explink) echo "exp=$explink&amp;";
				  echo "cat=$cat$shopurlstring\" style=\"text-decoration: none\" rel=\"nofollow\">";
			  }
			  echo "<img src=\"$ashopurl/images/$categoryimage\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"$categoryimage\" />";
			  if ($imagelink) echo "</a>";
			  echo "</td><td><a href=\"{$linksurl}";
			  if ($catexplink) echo "exp=$catexplink&amp;";
			  echo "cat=$categoryid$shopurlstring\" style=\"text-decoration: none\"><span class=\"ashopcategorytext\">$catname</span></a>";
			  if ($enableproductcount) echo "<span class=\"ashopcategorytext\"> ($productcount)</span>";
			  echo "</td></tr></table>
			  ";
		  }
	  }
      if (((isset($exparray) && in_array($categoryid,$exparray)) || ($layout == "2" && $device != "mobile")) && (empty($level) || $level == "2")) {
         $subsql="SELECT * FROM category WHERE grandparentcategoryid = '$categoryid' AND categoryid != grandparentcategoryid AND categoryid = parentcategoryid AND (userid LIKE '$shop' OR memberclone='1') ORDER BY ordernumber";
         $subresult = @mysqli_query($db, $subsql);
		 if ($layout == "2") {
			 if (@mysqli_num_rows($subresult) && $device != "mobile") echo "\n  <ul>";
		 }
         for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
			$explink = "";
            $subcategoryname = @mysqli_result($subresult, $j, "name");
			$subcategoryname = str_replace("&amp;","&",$subcategoryname);
			$subcategoryname = str_replace("&","&amp;",$subcategoryname);
            $subcategoryid = @mysqli_result($subresult, $j, "categoryid");
			if ($usecategories != "all" && (!is_array($usecategories) || !in_array($subcategoryid, $usecategories))) continue;
			if (is_array($excludecategories) && in_array($subcategoryid, $excludecategories)) continue;
			$subresult2 = @mysqli_query($db, "SELECT * FROM category WHERE parentcategoryid = '$subcategoryid' AND parentcategoryid != categoryid AND (userid LIKE '$shop' OR memberclone='1')");
			if (!@mysqli_num_rows($subresult2)) {
				$imagelink = FALSE;
				$categoryimage = "invisible.gif";
			} else if ($exparray && in_array($subcategoryid,$exparray)) {
				$imagelink = TRUE;
				$categoryimage = "minus.gif";
				if ($exparray) foreach ($exparray as $expandedcat) if ($expandedcat && $expandedcat != $subcategoryid) $explink .= $expandedcat."|";
			} else {
				$imagelink = TRUE;
				if ($exparray) foreach ($exparray as $expandedcat) $explink .= $expandedcat."|";
				$explink .= $subcategoryid."|";
				$categoryimage = "plus.gif";
			}
			if (!$imagelink || ($exparray && in_array($subcategoryid,$exparray))) $subcatexplink = $exp;
			else $subcatexplink = $explink;
			// Get product count...
			$subcatresult = @mysqli_query($db, "SELECT * FROM category WHERE (grandparentcategoryid='$subcategoryid' OR parentcategoryid='$subcategoryid') AND categoryid!='$subcategoryid'");
			if (@mysqli_num_rows($subcatresult)) $catcheck = "(category.categoryid='$subcategoryid' OR category.grandparentcategoryid='$subcategoryid' OR category.parentcategoryid='$subcategoryid')";
			else $catcheck = "category.categoryid='$subcategoryid'";
			if ($catalogtype == "rt" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND $catcheck AND product.productid=productcategory.productid AND $userselectstring AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.active='1'$excludeproductsquery");
			else if ($catalogtype == "ws" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND $catcheck AND product.productid=productcategory.productid AND product.userid='1' AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.wholesaleactive='1'$excludeproductsquery");
			$productcount = @mysqli_num_rows($prodcountresult);
			if (empty($productcount)) $productcount = 0;
			if ($subcategoryid == $cat) {
				if ($layout == "2") {
					echo "\n   <li class=\"ashopselectedcategory\"><a href=\"{$linksurl}cat=$subcategoryid$shopurlstring";
					if ($device) echo "&exp=$subcatexplink\" data-ajax=\"false\" class=\"ashopmobilesubcategory\"";
					else echo "\"";
					echo ">$subcategoryname</a>";
				} else {
					echo "<table cellspacing=\"0\" class=\"ashopselectedcategory\"><tr><td width=\"16\">&nbsp;</td><td width=\"16\" valign=\"top\">
					";
					if ($imagelink) {
						echo "<a href=\"{$linksurl}";
						if ($explink) echo "exp=$explink&amp;";
						echo "cat=$cat$shopurlstring\" style=\"text-decoration: none\" rel=\"nofollow\">";
					}
					echo "<img src=\"$ashopurl/images/$categoryimage\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"$categoryimage\" />";
					if ($imagelink) echo "</a>";
					echo "</td><td><a href=\"{$linksurl}";
					if ($subcatexplink) echo "exp=$subcatexplink&amp;";
					echo "cat=$subcategoryid$shopurlstring\" style=\"text-decoration: none\"><span class=\"ashopselectedcategorytext\">$subcategoryname</span></a>";
					if ($enableproductcount) echo "<span class=\"ashopselectedcategorytext\"> ($productcount)</span>";
					echo "</td></tr></table>
					";
				}
			} else {
				if ($layout == "2") {
					echo "\n   <li class=\"ashopcategory\"><a href=\"{$linksurl}cat=$subcategoryid$shopurlstring";
					if ($device) echo "&exp=$subcatexplink\" data-ajax=\"false\" class=\"ashopmobilesubcategory\"";
					else echo "\"";
					echo ">$subcategoryname</a>";
				} else {
					echo "<table cellspacing=\"0\" class=\"ashopcategory\"><tr><td width=\"16\">&nbsp;</td><td width=\"16\" valign=\"top\">
					";
					if ($imagelink) {
						echo "<a href=\"{$linksurl}";
						if ($explink) echo "exp=$explink&amp;";
						echo "cat=$cat$shopurlstring\" style=\"text-decoration: none\" rel=\"nofollow\">";
					}
					echo "<img src=\"$ashopurl/images/$categoryimage\" border=\"0\" width=\"12\" vspace=\"3\" alt=\"$categoryimage\" />";
					if ($imagelink) echo "</a>";
					echo "</td><td><a href=\"{$linksurl}";
					if ($subcatexplink) echo "exp=$subcatexplink&amp;";
					echo "cat=$subcategoryid$shopurlstring\" style=\"text-decoration: none\"><span class=\"ashopcategorytext\"> $subcategoryname</span></a>";
					if ($enableproductcount) echo "<span class=\"ashopcategorytext\"> ($productcount)</span>";
					echo "</td></tr></table>
					";
				}
			}
			if ((($exparray && in_array($subcategoryid,$exparray)) || $layout == "2") && (empty($level) || $level == "3")) {
				$subsubsql="SELECT * FROM category WHERE parentcategoryid = '$subcategoryid' AND categoryid != parentcategoryid AND (userid LIKE '$shop' OR memberclone='1') ORDER BY ordernumber";
				$subsubresult = @mysqli_query($db, $subsubsql);
				if ($layout == "2") {
					if (@mysqli_num_rows($subsubresult) && $device != "mobile") echo "\n    <ul>";
				}
				for ($k = 0; $k < @mysqli_num_rows($subsubresult); $k++) {
					$subsubcategoryname = @mysqli_result($subsubresult, $k, "name");
					$subsubcategoryname = str_replace("&amp;","&",$subsubcategoryname);
					$subsubcategoryname = str_replace("&","&amp;",$subsubcategoryname);
					$subsubcategoryid = @mysqli_result($subsubresult, $k, "categoryid");
					if ($usecategories != "all" && (!is_array($usecategories) || !in_array($subsubcategoryid, $usecategories))) continue;
					if (is_array($excludecategories) && in_array($subsubcategoryid, $excludecategories)) continue;
					// Get product count...
					if ($catalogtype == "rt" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND category.categoryid='$subsubcategoryid' AND product.productid=productcategory.productid AND $userselectstring AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.active='1'$excludeproductsquery");
					else if ($catalogtype == "ws" && $enableproductcount) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND category.categoryid='$subsubcategoryid' AND product.productid=productcategory.productid AND product.userid='1' AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.wholesaleactive='1'$excludeproductsquery");
					$productcount = @mysqli_num_rows($prodcountresult);
					if (empty($productcount)) $productcount = 0;
					if ($subsubcategoryid == $cat) {
						if ($layout == "2") {
							echo "\n     <li class=\"ashopselectedsubsubcategory\"><a href=\"{$linksurl}cat=$subsubcategoryid$shopurlstring\"";
							if ($device) echo " data-ajax=\"false\" class=\"ashopmobilesubsubcategory\"";
							echo ">$subsubcategoryname</a></li>";
						} else {
							echo "<table cellspacing=\"0\" class=\"ashopselectedsubsubcategory\">
							<tr><td width=\"20\">&nbsp;</td><td><span class=\"ashopselectedsubsubcategorytext\"> - </span><a href=\"{$linksurl}exp=$exp&amp;cat=$subsubcategoryid$shopurlstring\" style=\"text-decoration: none\"><span class=\"ashopselectedsubsubcategorytext\">$subsubcategoryname</span></a>";
							if ($enableproductcount) echo "<span class=\"ashopselectedcategorytext\"> ($productcount)</span>";
						}
					} else {
						if ($layout == "2") {
							echo "\n     <li class=\"ashopsubsubcategory\"><a href=\"{$linksurl}cat=$subsubcategoryid$shopurlstring\"";
							if ($device) echo " data-ajax=\"false\" class=\"ashopmobilesubsubcategory\"";
							echo ">$subsubcategoryname</a></li>";
						} else {
							echo "<table cellspacing=\"0\" class=\"ashopsubsubcategory\"><tr><td width=\"20\">&nbsp;</td><td><span class=\"ashopsubsubcategorytext\"> - </span><a href=\"{$linksurl}exp=$exp&amp;cat=$subsubcategoryid$shopurlstring\" style=\"text-decoration: none\"><span class=\"ashopsubsubcategorytext\">$subsubcategoryname</span></a>";
							if ($enableproductcount) echo "<span class=\"ashopcategorytext\"> ($productcount)</span>";
						}
					}
					if ($layout != "2") echo "</td></tr></table>
					";
				}
				// End list of subsubcategories...
				if ($layout == "2") {
					if (@mysqli_num_rows($subsubresult) && $device != "mobile") echo "\n    </ul>";	
				}
			}
			// End subcategory list item...
			if ($layout == "2") echo "</li>\n";					
         }
		 // End list of subcategories...
		 if ($layout == "2") {
			 if (@mysqli_num_rows($subresult) && $device != "mobile") echo "\n  </ul>\n";	 
		 }
      }
	  // End category list item...
	  if ($layout == "2") echo "</li>\n";
    }
	// End list of categories...
	if ($layout == "2") {
		echo "\n</ul>\n";
		if ($device) echo "</div>";
	}

	if ($layout != "2") {

		if($ashopaffiliateid && $catalogtype != "ws") echo "<center><a href=\"http://www.ashopsoftware.com\" onClick=\"window.open('http://www.ashopsoftware.com/affiliate.php?id=$ashopaffiliateid' , 'PGM' , 'scrollbars=yes, toolbar=yes, status=yes, menubar=yes location=yes resizable=yes'); return false; \" target=\"_blank\"><img src=\"images/ashoplogo.gif\" border=\"0\" alt=\"".POWEREDBYASHOP."\" /></a></center>";

	}
}

$layout = "";
$cattree = "";
$level = "";
$exclude = "";
?>