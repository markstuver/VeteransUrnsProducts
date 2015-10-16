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
// Module: topform.inc.php
// Description: generates a form with search box, subtotal box and buttons to view cart or check out
// Input variables: cat = category ID, exp = expanded category ID,
// layout = 1 : show all form fields in one row
// layout = 2 : show all form fields in two rows
// layout = 3 : show search fields only
// layout = 4 : show subtotal only
// layout = 5 : show buttons only
// layout = 6 : show customer profile links only

unset($_GET["layout"]);
unset($_POST["layout"]);
@session_start();
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Validate $layout...
if (isset($layout) && !is_numeric($layout)) unset($layout);
if (isset($layout) && ($layout > 6 || $layout < 1)) unset($layout);
if (!isset($layout)) $layout = 0;

// Validate $msg...
$msg = urldecode($msg);
$msg = html_entity_decode($msg);
$msg = strip_tags($msg);

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Get currency rate if needed...
if (isset($curr) && !isset($crate)) {
	if (isset($curr) && preg_match("/^[a-z]*$/", $curr) && strlen($curr) == 3 && $curr != $ashopcurrency) $crate = getcurrency($curr);
	else {
		$curr = "";
		$crate = 0;
	}
}

// Check if the constants are available...
if (!$currencysymbols) include "admin/ashopconstants.inc.php";

	// Include language file...
	if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
	include "language/$lang/catalogue.inc.php";

	// Prepare for layout modes 1, 2, 3 and 5...
	if ($layout != 4 && $layout != 6) {
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
		} else $shopurlstring = "&amp;shop=$shop";

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

		// Correct button paths for subdirectories...
		if (strstr($_SERVER["REQUEST_URI"],"/affiliate/") || strstr($_SERVER["REQUEST_URI"],"/members/") || strstr($_SERVER["REQUEST_URI"],"/wholesale/")) $buttonpath = "../".$buttonpath;

		// Get member template path if no theme is used...
		if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

		// Search for category by name...
		if ($findcatbyname) {
			$result = @mysqli_query($db, "SELECT categoryid FROM category WHERE upper(name) LIKE '%".strtoupper($cat)."%'");
			if (@mysqli_num_rows($result)) {
				$cat = @mysqli_result($result,0,"categoryid");
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

		// Get number of categories...
		if (empty($categoriescount)) {
			if ($hideemptycategories) $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.$activestring='1' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
			else $result = @mysqli_query($db, "SELECT categoryid FROM category WHERE (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber");
			$categoriescount = @mysqli_num_rows($result);
		}
	}

// Initialize variables...
if (!isset($search)) $search = "";
if (!isset($basket)) $basket = "";
if (!isset($msg)) $msg = "";

// Check catalog type...
if (isset($_GET["catalogtype"]) || isset($_POST["catalogtype"]) || isset($_COOKIE["catalogtype"])) unset($catalogtype);
if (isset($catalogtype) && ($catalogtype != "rt" && $catalogtype != "ws")) unset($catalogtype);
if (!isset($catalogtype)) $catalogtype = "rt";

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"]) && empty($pricelevel)) {
	$customerresult = @mysqli_query($db, "SELECT level FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	if (@mysqli_num_rows($customerresult)) $pricelevel = @mysqli_result($customerresult,0,"level");
	else $pricelevel = 0;
}
if ($pricelevel > 0) $catalogtype = "ws";
else $catalogtype = "rt";

if ($catalogtype == "ws") {
	$shop = "1";
	$displaywithtax = $displaywswithtax;
}

// Show top form with search field, subtotal and shopping cart buttons...
if ($layout) $topformlayout = $layout;
if (!$topformlayout || !is_numeric($topformlayout) || $topformlayout > 6) $topformlayout = 2;
if ($topformlayout == 1 || $topformlayout == 2) echo "<a name=\"$returntotoplink\"></a>
<table class=\"ashoptopform\"><tr>";
if ($topformlayout == 4 || $topformlayout == 5 || $topformlayout == 6) $search = "off";
if (($categoriescount > 1 || $topformlayout == 3) && $search != "off") {
	if (!$search) $search = "index.php";
	if ($topformlayout == 3) echo "
	<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"left\" valign=\"bottom\" style=\"white-space:nowrap;\"><form action=\"$search\" method=\"post\" style=\"margin-bottom: 0px;\"> <input type=\"hidden\" name=\"exp\" value=\"$exp\" /><input type=\"hidden\" name=\"shop\" value=\"$shop\" /><input class=\"ashopsearchfield\" type=\"text\" size=\"20\" name=\"searchstring\" /> <input type=\"image\" src=\"{$buttonpath}images/search-$lang.png\" class=\"ashopbutton\" style=\"vertical-align: bottom; border: none;\" /></form></td></tr></table>
	";
	else echo "<td align=\"left\" valign=\"bottom\" style=\"white-space:nowrap;\"><form action=\"$search\" method=\"post\" style=\"margin-bottom: 2px;\"> <input type=\"hidden\" name=\"exp\" value=\"$exp\" /><input type=\"hidden\" name=\"shop\" value=\"$shop\" /><input class=\"ashopsearchfield\" type=\"text\" size=\"20\" name=\"searchstring\" /> <input type=\"image\" src=\"{$buttonpath}images/search-$lang.png\" class=\"ashopbutton\" style=\"vertical-align: bottom; border: none;\" /></form></td>
	";
}
if ($topformlayout == 1 || $topformlayout == 2) {
	// Show customer profile links...
	if ($search == "off" && file_exists("$ashoppath/customerprofile.php") && $customerlogin != "off") {
		echo "<td align=\"left\">";
		if (!empty($_COOKIE["customersessionid"])) {
			$customerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
			$customerfirstname = @mysqli_result($customerresult, 0, "firstname");
			$customerlastname = @mysqli_result($customerresult, 0, "lastname");
			echo "<span class=\"ashopcustomertext5\">&nbsp;".WELCOME." $customerfirstname $customerlastname! - <a href=\"$ashopurl/customerprofile.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".PROFILE."</a> <a href=\"$ashopurl/login.php?logout\">".LOGOUT."</a></span>";
		} else {
			echo "<span class=\"ashopcustomertext5\">&nbsp;<a href=\"$ashopurl/signupform.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".REGISTER."</a> <a href=\"$ashopurl/login.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".LOGIN."</a></span>";
		}
		echo "</td>";
	}
	echo "<td align=\"right\">";
}
if ($topformlayout == 1 || $topformlayout == 2) echo "
<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td align=\"right\" width=\"160\" id=\"cartbox\">";
if ($topformlayout != 3 && $topformlayout != 5 && $topformlayout != 6) {
	$subtotal = 0;
	$totaltax = 0;
	// Calculate subtotal...
	$basket = ashop_combineproducts($basket);
	$totalqty = ashop_totalqty($basket);
	$basket = ashop_applydiscounts($db, $basket);
	$productsincart = ashop_parseproductstring($db, $basket);
	if ($productsincart) {
		foreach($productsincart as $productnumber => $thisproduct) {
			$productid = $thisproduct["productid"];
			$quantity = $thisproduct["quantity"];
			$thisproductdiscount = "0";
			if ($thisproduct["discounted"] == "true") {
				if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
					if (strstr($cookiename,"discount")) {
						$discountid = str_replace("discount","",$cookiename);
						$sql="SELECT * FROM discount WHERE productid='$productid' AND discountid='$discountid'";
						$result2 = @mysqli_query($db, "$sql");
						if (@mysqli_num_rows($result2)) $thisproductdiscount = $cookievalue;
					}
				}
				if (isset($_COOKIE) && is_array($_COOKIE)) foreach ($_COOKIE as $cookiename=>$cookievalue) {
					if (strstr($cookiename,"discount")) {
						$discountid = str_replace("discount","",$cookiename);
						$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
						$result2 = @mysqli_query($db, "$sql");
						if (@mysqli_num_rows($result2)) {
							$discountcategory = @mysqli_result($result2, 0, "categoryid");
							$result3 = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$productid' AND categoryid='$discountcategory'");
							if (@mysqli_num_rows($result3)) $thisproductdiscount = $cookievalue;
						}
					}
				}
			}
			// Get correct price for this level of customer...
			if ($pricelevel < 1) $price = $thisproduct["price"];
			else if ($pricelevel == 1) $price = $thisproduct["wholesaleprice"];
			else {
				$pricelevels = $thisproduct["wspricelevels"];
				$price = $pricelevels[$pricelevel-2];
			}

			// Calculate subtotal...
			if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $quantity;
			else {
				if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
				else $subtotalqty = ashop_categoryqty($db, $basket, $thisproduct["qtycategory"]);
			}
			$price = ashop_subtotal($db, $productid, $subtotalqty, $quantity, $thisproductdiscount, $price, $thisproduct["qtytype"]);
			if ($thisproduct["taxable"] && $displaywithtax == 1) $totaltax += $price;
			$subtotal += $price;
		}
		// Apply storewide discount...
		if ($discountall) {
			$storediscountresult = @mysqli_query($db, "SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='$'");
			if (@mysqli_num_rows($storediscountresult)) {
				$storediscountrow = @mysqli_fetch_array($storediscountresult);
				if ($storediscountrow["value"]) {
					$subtotal -= $storediscountrow["value"];
					$totaltax -= $storediscountrow["value"];
				}
			}
		}
		// Calculate tax...
		if ($displaywithtax == 1) {
			$taxmultiplier = $taxpercentage/100;
			$tax = $totaltax*$taxmultiplier;
			$subtotal += $tax;
		}
		if ($subtotal < 0) $subtotal = 0;
	}

	if ($topformlayout == "4") {
		if (file_exists("$ashoppath/customerprofile.php") && $customerlogin != "off") {
			if (!empty($_COOKIE["customersessionid"])) {
				$customerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
				$customerfirstname = @mysqli_result($customerresult, 0, "firstname");
				$customerlastname = @mysqli_result($customerresult, 0, "lastname");
				echo "&nbsp;<span class=\"ashopcustomertext4\">$customerfirstname $customerlastname - <a href=\"$ashopurl/customerprofile.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".PROFILE."</a> <a href=\"$ashopurl/login.php?logout\">".LOGOUT."</a></span><br />";
			} else {
				echo "&nbsp;<span class=\"ashopcustomertext4\"><a href=\"$ashopurl/signupform.php";
				if (!empty($shop) && $shop > 1) echo "?shop=$shop";
				echo "\">".REGISTER."</a> <a href=\"$ashopurl/login.php";
				if (!empty($shop) && $shop > 1) echo "?shop=$shop";
				echo "\">".LOGIN."</a></span><br />";
			}
		}
		if (!$confirmmessage == "off") echo "<div id=\"confirmmsg\" class=\"ashopconfirmmessage\">$msg</div>";
		echo "<span class=\"ashopsubtotaltext2\">&nbsp;";
	} else echo "<span class=\"ashopsubtotaltext\">";

	// Convert currency...
	if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		$tempcurrency = $ashopcurrency;
		$ashopcurrency = $curr;
		$tempsubtotal = $subtotal;
		$subtotal = $subtotal*$crate;
	}
	if ($device == "mobile") echo "<div data-role=\"fieldcontain\" style=\"margin-top: 0; margin-bottom: 0;\"><label for=\"amount\">".SUBTOTAL.": ".$currencysymbols[$ashopcurrency]["pre"]." </label>";
	else echo SUBTOTAL.": ".$currencysymbols[$ashopcurrency]["pre"]." ";
	echo "<input class=\"ashopsubtotalfield\" type=\"text\" name=\"amount\" size=\"10\" readonly=\"readonly\" value=\"";
	if (!$subtotal) echo "0";
	else echo number_format($subtotal,$showdecimals,$decimalchar,$thousandchar);
	echo "\" />".$currencysymbols[$ashopcurrency]["post"]."</span>";
	if ($device == "mobile") echo "</div>";
	// Convert back to main currency...
	if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		$ashopcurrency = $tempcurrency;
		$subtotal = $tempsubtotal;
	}
}

if ($topformlayout == 2 || $topformlayout == 1) echo "</td>";
if ($topformlayout == 2) echo "</tr></table></td></tr><tr><td align=\"right\" valign=\"bottom\" colspan=\"3\">";
if ($topformlayout == 1) echo "<td align=\"left\" valign=\"bottom\">";
if ($topformlayout == 1 || $topformlayout == 2 || $topformlayout == 5) {
	if (strstr($_SERVER["REQUEST_URI"],"/affiliate/") || strstr($_SERVER["REQUEST_URI"],"/members/") || strstr($_SERVER["REQUEST_URI"],"/wholesale/")) echo "<form method=\"post\" target=\"_parent\" action=\"../checkout.php\" name=\"cartbuttons\" style=\"margin-bottom: 0px;\"";
	else echo "<form method=\"post\" target=\"_parent\" action=\"checkout.php\" name=\"cartbuttons\" style=\"margin-bottom: 0px;\"";
	if ($device == "mobile") echo " data-ajax=\"false\"";
	echo ">";
	if ($shopurlstring) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
	echo "<input type=\"hidden\" name=\"cat\" value=\"$cat\" />";
	if ($device == "mobile") {
		echo "<div data-role=\"controlgroup\" data-type=\"horizontal\"><a href=\"basket.php?cat=$cat$shopurlstring\" target=\"_parent\" data-role=\"button\" data-theme=\"b\">".VIEWCART."</a> <input type=\"submit\" value=\"".CHECKOUT."\"  data-theme=\"a\" /></div>";
	} else {
		if (strstr($_SERVER["REQUEST_URI"],"/affiliate/") || strstr($_SERVER["REQUEST_URI"],"/members/") || strstr($_SERVER["REQUEST_URI"],"/wholesale/")) echo "<a href=\"../basket.php?cat=$cat$shopurlstring\" target=\"_parent\"><img src=\"{$buttonpath}images/catviewcart-$lang.png\" class=\"ashopbutton\" alt=\"".VIEWCART.":\" style=\"border: none;\" /></a>&nbsp;<input type=\"image\" src=\"{$buttonpath}images/catcheckout-$lang.png\" class=\"ashopbutton\" alt=\"".CHECKOUT."\" style=\"border: none;\" />
		";
		else echo "<a href=\"basket.php?cat=$cat$shopurlstring\" target=\"_parent\"><img src=\"{$buttonpath}images/catviewcart-$lang.png\" class=\"ashopbutton\" alt=\"".VIEWCART.":\" style=\"border: none;\" /></a>&nbsp;<input type=\"image\" src=\"{$buttonpath}images/catcheckout-$lang.png\" class=\"ashopbutton\" alt=\"".CHECKOUT."\" style=\"border: none;\" />
		";
	}
	echo "
	</form>
	";
}
if ($topformlayout == 1 || $topformlayout == 2) echo "</td></tr></table>\n";
if ($topformlayout == 1) echo "</td></tr></table>\n";
if ($topformlayout == 2 || $topformlayout == 1) echo "<table class=\"ashoptopform\"><tr><td valign=\"top\" align=\"right\"><div id=\"confirmmsg\" class=\"ashopconfirmmessage\">$msg</div></td></tr></table>
";
if ($topformlayout == 6) {
	if (!empty($_COOKIE["customersessionid"])) {
		$customerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		$customerfirstname = @mysqli_result($customerresult, 0, "firstname");
		$customerlastname = @mysqli_result($customerresult, 0, "lastname");
		echo "<span class=\"ashopcustomertext4\">".WELCOME." $customerfirstname $customerlastname! <a href=\"$ashopurl/customerprofile.php";
		if (!empty($shop) && $shop > 1) echo "?shop=$shop";
		echo "\"";
		if ($device == "mobile") echo " data-ajax=\"false\"";
		echo ">".PROFILE."</a> <a href=\"$ashopurl/login.php?logout\"";
		if ($device == "mobile") echo " data-ajax=\"false\"";
		echo ">".LOGOUT."</a></span>";
	} else {
		echo "<span class=\"ashopcustomertext4\">".WELCOMEGUEST." - <a href=\"$ashopurl/signupform.php";
		if (!empty($shop) && $shop > 1) echo "?shop=$shop";
		echo "\"";
		if ($device == "mobile") echo " data-ajax=\"false\"";
		echo ">".REGISTER."</a> <a href=\"$ashopurl/login.php";
		if (!empty($shop) && $shop > 1) echo "?shop=$shop";
		echo "\"";
		if ($device == "mobile") echo " data-ajax=\"false\"";
		echo ">".LOGIN."</a></span>";
	}
}

$layout = "";
?>