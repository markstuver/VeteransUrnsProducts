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

if (preg_match("/\Wcatalog.inc.php/",$_SERVER["PHP_SELF"])>0) {
	header("Location: ../index.php");
	exit;
}

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";
if (!isset($currencynames)) include "admin/ashopconstants.inc.php";
include "counter.php";

// Initialize variables...
if (!isset($cat)) $cat = 0;
if (!isset($exp)) $exp = 0;
if (!isset($shop)) $shop = 1;
if (!isset($lang)) $lang = "";
if (!isset($defaultlang)) $defaultlang = "";
if (!isset($p3psent)) $p3psent = FALSE;
if (!isset($categories)) $categories = "";
if (!isset($usethemebuttons)) $usethemebuttons = "";
if (!isset($usethemetemplates)) $usethemetemplates = "";
if (!isset($topform)) $topform = "";
if (!isset($bidderhash)) $bidderhash = "";
if (!isset($product)) $product = 0;
if (!isset($thisscreenname)) $thisscreenname = "";
if (!isset($error)) $error = 0;
if (!isset($affiliate)) $affiliate = 0;
if (!isset($catalog)) $catalog = "";
if (!isset($shipid)) $shipid = 0;
if (!isset($specialoffer)) $specialoffer = "";
if (!isset($upsold)) $upsold = 0;
if (isset($product) && !is_numeric($product)) $product = 0;

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
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($_GET["resultpage"])) unset($_GET["resultpage"]);

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db, "SELECT level, firstname, lastname, customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$pricelevel = @mysqli_result($customerresult,0,"level");
} else $pricelevel = 0;
if ($pricelevel > 0) {
	$activestring = "wholesaleactive";
	$templatefile = "wscatalogue";
	$displaywithtax = $displaywswithtax;
} else {
	$activestring = "active";
	$templatefile = "catalogue";
}

// Get affiliate exclusion list for affiliate's editing...
$affexcludecategories = array();
$affexcludeproducts = array();
if (!empty($_COOKIE["affiliatesesid"]) && ashop_is_md5($_COOKIE["affiliatesesid"])) {
	$affiliateresult = @mysqli_query($db, "SELECT excludecategories, excludeproducts FROM affiliate WHERE sessionid='{$_COOKIE["affiliatesesid"]}'");
	$affiliaterow = @mysqli_fetch_array($affiliateresult);
	$affexcludecategories = explode("|",$affiliaterow["excludecategories"]);
	$affexcludeproducts = explode("|",$affiliaterow["excludeproducts"]);
}

// Get main affiliate exclusion list...
$excludecategories = array();
$excludeproducts = array();
$excludeproductsquery = "";
if (!empty($affiliate) && is_numeric($affiliate)) {
	$affiliateresult = @mysqli_query($db, "SELECT excludecategories, excludeproducts, hideprice FROM affiliate WHERE affiliateid='{$affiliate}'");
	$affiliaterow = @mysqli_fetch_array($affiliateresult);
	$excludecategories = explode("|",$affiliaterow["excludecategories"]);
	$excludeproducts = explode("|",$affiliaterow["excludeproducts"]);
	if (!empty($excludeproducts)) {
		$excludeproductquerylist = "";
		foreach ($excludeproducts as $thisexcludeproduct) if (!empty($thisexcludeproduct)) $excludeproductquerylist .= "'$thisexcludeproduct', ";
		$excludeproductquerylist = substr($excludeproductquerylist,0,-2);
		if (!empty($excludeproductquerylist)) $excludeproductsquery = " AND product.productid NOT IN (".$excludeproductquerylist.")";
	}
	$hideprice = $affiliaterow["hideprice"];
}

// Get currency rate if needed...
if (isset($curr) && preg_match("/^[a-z]*$/", $curr) && strlen($curr) == 3 && $curr != $ashopcurrency) $crate = getcurrency($curr);
else {
	$curr = "";
	$crate = 0;
}

// Remember sort order...
if (isset($_POST["sortby"])) {
	if ($_POST["sortby"] == "name" || $_POST["sortby"] == "lowprice" || $_POST["sortby"] == "highprice") $sortby = $_POST["sortby"];
	else $sortby = "";
}

// Set up sell parameters if requested...
if ($specialoffer == "true") {
	if ($upsold >= $upsellitems) {
		header("Location: checkout.php");
		exit;
	}
	$templatefile = "upsell";
	$categories = "off";
	$topform = "off";
	$upsold++;
	if (!$p3psent) {
		header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
	}
	setcookie("upsold", "$upsold");
	// Determine which product to show...
	$productsincart = ashop_parseproductstring($db, $basket);
	$upsellproducts = array();
	if ($productsincart) {
		foreach($productsincart as $productnumber => $thisproduct) {
			$upsellarray = $thisproduct["upsell1"];
			if (is_array($upsellarray)) {
				$upsellproductid = $upsellarray["productid"];
				$upsellpriority = $upsellarray["priority"];
				if (!is_array($upsellproducts["$upsellpriority"]) || !in_array($upsellproductid, $upsellproducts["$upsellpriority"])) $upsellproducts["$upsellpriority"][] = $upsellproductid;
			}
			$upsellarray = $thisproduct["upsell2"];
			if (is_array($upsellarray)) {
				$upsellproductid = $upsellarray["productid"];
				$upsellpriority = $upsellarray["priority"];
				if (!is_array($upsellproducts["$upsellpriority"]) || !in_array($upsellproductid, $upsellproducts["$upsellpriority"])) $upsellproducts["$upsellpriority"][] = $upsellproductid;
			}
		}
		$product = 0;
		$checkproduct = 0;
		$prioritylevel = 9;
		while (!$product && $prioritylevel >= 0) {
			if (isset($upsellproducts["$prioritylevel"])) {
				$checkprioritylevel = array();
				while (count($checkprioritylevel) < count($upsellproducts["$prioritylevel"])) {
					srand ((double) microtime() * 1000000);
					$selectrandom = rand(0,count($upsellproducts["$prioritylevel"])-1);
					$checkproduct = $upsellproducts["$prioritylevel"][$selectrandom];
					$checkprioritylevel[] = $checkproduct;
					if ($checkproduct != $added && !ashop_checkproduct($checkproduct, $basket)) $product = $checkproduct;
				}
			}
			$prioritylevel--;
		}
		if (!$product) {
			if (!$p3psent) {
				header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
				$p3psent = TRUE;
			}
			setcookie("upsold", "$upsellitems");
			header("Location: checkout.php");
			exit;
		}
	}
} else if ($specialoffer == "false") {
	if (!$p3psent) {
		header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
	}
	setcookie("upsold", "$upsellitems");
	header("Location: checkout.php");
	exit;
}

// Select shop...
if ((isset($_GET["shop"]) || isset($_POST["shop"]))) {
	if (isset($_GET["shop"])) $newshop = $_GET["shop"];
	if (isset($_POST["shop"])) $newshop = $_POST["shop"];
	unset($shop);
	$shop = $newshop;
}
if (!$shop || !is_numeric($shop)) {
	$shop = "1";
	$shopurlstring = "";
} else $shopurlstring = "&amp;shop=$shop";

if (!$membershops) $shopsearch = "%";
else $shopsearch = $shop;
if (!$p3psent && !$categories && !headers_sent()) {
	header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
}

// Check if Google Checkout should be used for buy buttons...
if ($shoppingcart == "0") {
	$gcocheckresult = @mysqli_query($db, "SELECT * FROM payoptions WHERE (wholesaleonly!='1' OR wholesaleonly IS NULL) AND (emerchantonly!='1' OR emerchantonly IS NULL) AND gateway='googleco'");
	if (@mysqli_num_rows($gcocheckresult) == 1) {
		$gcoid = @mysqli_result($gcocheckresult,0,"merchantid");
		$gcokey = @mysqli_result($gcocheckresult,0,"secret");
		$gcotest = @mysqli_result($gcocheckresult,0,"testmode");
	} else $gcoid = "";
} else $gcoid = "";

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
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/$templatefile.html")) $templatepath = "/members/files/$ashopuser";

// Check for first page content...
if (empty($firstpageexists)) {
	if ($device == "mobile") $firstpagename = "AShopFirstPageMobile";
	else $firstpagename = "AShopFirstPage";
	$firstpageresult = @mysqli_query($db, "SELECT * FROM product WHERE name='$firstpagename' AND prodtype='content' AND userid='$shop' AND (language='$lang' OR language='any')");
	$firstpageexists = @mysqli_num_rows($firstpageresult);
}
   
// Search for category by name...
   if ($findcatbyname) {
		$result = @mysqli_query($db, "SELECT categoryid FROM category WHERE upper(name) LIKE '%".strtoupper($cat)."%'");
		if (@mysqli_num_rows($result)) {
			$cat = @mysqli_result($result,0,"categoryid");
		} else {
			$cat = 0;
			$numberofcategories = 0;
		}
	}

// Get default category...
if (empty($numberofcategories)) {
	if ($hideemptycategories) {
		if ($shop > 1) $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.$activestring='1' AND product.userid LIKE '$shopsearch' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
		else $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.$activestring='1' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
	} else $result = @mysqli_query($db, "SELECT categoryid FROM category WHERE (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber");
	$numberofcategories = @mysqli_num_rows($result);
	if ($numberofcategories > 0 && !$cat && !$firstpageexists) $cat = @mysqli_result($result, 0, "categoryid");
}

// Set the page title...
if (!empty($cat) && is_numeric($cat)) {
    $sql="SELECT name, description, productlayout FROM category WHERE categoryid = '$cat' AND (userid LIKE '$shop' OR memberclone='1')";
    $catresult = @mysqli_query($db, $sql);
    $categoryname = @mysqli_result($catresult, 0, "name");
    $categorydescr = @mysqli_result($catresult, 0, "description");
	$productlayout = @mysqli_result($catresult, 0, "productlayout");
	if ($productlayout == 1) $usecondensedlayout = "";
	else if ($productlayout == 2) $usecondensedlayout = "true";
	else if ($productlayout == 3) $shoppingcart = "3";
	else if ($productlayout == 4) $device = "mobile";
}

// Check for floating price products...
$checkfpresult = @mysqli_query($db, "SELECT floatingprice.productid FROM floatingprice, product WHERE product.productid=floatingprice.productid AND product.active='1' LIMIT 1");
$activatefloatingprice = @mysqli_num_rows($checkfpresult);

// Print top of page...
echo "
	<script language=\"JavaScript\" src=\"includes/prototype.js\" type=\"text/javascript\"></script>
	<script type=\"text/javascript\" src=\"includes/jquery-1.11.2.min.js\"></script>
	<script type=\"text/javascript\" src=\"includes/jquery-ui-1.8.16.custom.min.js\"></script>
	<script type=\"text/javascript\" src=\"includes/jquery.colorbox-min.js\"></script>";
if ($eucookiecheck) echo "
	<script type=\"text/javascript\" src=\"includes/jconsent/jconsent-1.0.3.js\"></script>";
echo "
	<script type=\"text/javascript\" src=\"includes/addtocart.js\"></script>
	<script language=\"JavaScript\" type=\"text/javascript\">
	/* <![CDATA[ */
		 jQuery.noConflict();
		 jQuery(document).ready(function() {
			jQuery(\".gallery\").colorbox({iframe:true, width:\"800px\", height:\"530px\", opacity:\"0.7\"});";
if ($eucookiecheck) echo "
			jQuery.ws.jconsent({displaySettingsSelector: '.displayCookieSettings', delayTime: '500', autoReloadPage: 'true', message: '".COOKIEINFO."', permanentMessage: '".THANKYOU."'});";
echo "
		 });
	/* ]]> */
    </script>
";
if ($activatefloatingprice) {
	echo "
	<script language=\"JavaScript\" src=\"includes/bidengine.js\" type=\"text/javascript\"></script>
	<script language=\"JavaScript\" type=\"text/javascript\">
	/* <![CDATA[ */

		 var counterday = '".COUNTERDAY."';
		 var counterdays = '".COUNTERDAYS."';
		 var counterhours = '".COUNTERHOURS."';
		 var counterminutes = '".COUNTERMINUTES."';
		 var counterseconds = '".COUNTERSECONDS."';
		 var bidderword = '".BIDDER."';
		 var wonby = '".WONBY."';
		 starttime = new Object();
		 precurrency = new Object();";
		 if (!empty($currencysymbols[$ashopcurrency]["pre"])) echo "
		 precurrency = '".$currencysymbols[$ashopcurrency]["pre"]."';";
		 else echo "
		 precurrency = '';";
		 echo "
		 postcurrency = new Object();";
		 if (!empty($currencysymbols[$ashopcurrency]["post"])) echo "
		 postcurrency = '".$currencysymbols[$ashopcurrency]["post"]."';";
		 else echo "
		 postcurrency = '';";
		 echo "
		 fplength = new Object();
		 activated = new Object();
		 auctiontype = new Object();
		 activatetime = new Object();
		 var IDs=new Array();
		 var localtime = new Date().getTime();
		 localtime = localtime/1000;
		 var lastupdate = Math.round(localtime);
		 timediff = localtime - ".time()."-1;
		 if(isIE()){ var timeout_val=800; }
		 else{ var timeout_val=1000; }
		 window.setInterval(\"countdown()\",timeout_val);";
} else echo "<script language=\"JavaScript\" type=\"text/javascript\">
/* <![CDATA[ */";
if ($specialoffer == "true") echo "
var specialoffer = true;
";
else echo "
var specialoffer = '';
";
echo "

function addToWishlist(itemno, windowheight, popup)
		 {
			 if (popup >0) window.open(\"addtowishlist.php?item=\"+itemno,\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=\"+windowheight);
			 else ";
			 if ($confirmaddtocart != "no") echo "window.open(\"addtowishlist.php?item=\"+itemno,\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=\"+windowheight);";
			 else echo "document.location.href='addtowishlist.php?item='+itemno";
			 echo "+'&cat=$cat&exp=$exp';";
			 echo "
		 }
	     function showlicense(product)
	     {
		    window.open(\"license.php?\"+product,\"_blank\",\"toolbar=no, location=no, scrollbars=yes, width=500, height=600\")
	     }
	     function checkLicense(form)
		 {
			 if (form.acceptlicense.checked) return true;
			 else {
				 w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
				 w.document.write('<html><head><title>".ACCEPTLICENSE."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}.fontsize2 { font-size: {$fontsize2}px}.fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".LICENSEMESSAGE."</span><br /><br /><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSEWINDOW."</a></span></font></font><br /></center></body></html>');
				 return false;
			 }
		 }
	  /* ]]> */
      </script>
	  ";
if (file_exists("$ashoppath/includes/aws/aws-config.php")) {
	require_once "includes/aws/aws-config.php";
	echo "<script type=\"text/javascript\" src=\"includes/jwplayer/jwplayer.js\"></script>
		<script type=\"text/javascript\">jwplayer.key=\"$jwplayerkey\";</script>";
} else if (file_exists("$ashoppath/includes/flowplayer-3.2.6.min.js")) echo "
	  <script type=\"text/javascript\" src=\"includes/flowplayer-3.2.6.min.js\"></script>";

// Check if bidding should be activated...
if ($activatefloatingprice) {
	$activatebidding = FALSE;
	if (isset($_COOKIE["bidderhash"])) {
		$activatebidding = ashop_checkbidcode($db, $_COOKIE["bidderhash"]);
		if ($activatebidding) {
			$bidderhash = explode("|",$_COOKIE["bidderhash"]);
			$thisbidder = $bidderhash[0];
			if (is_numeric($thisbidder)) {
				$bidderresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$thisbidder'");
				$bidderrow = @mysqli_fetch_array($bidderresult);
				$thisscreenname = $bidderrow["screenname"];
				$thisbids = $bidderrow["numberofbids"];
				$thisbidcode = $bidderrow["bidcode"];
				if (!empty($thisbidcode) && is_numeric($thisbidcode) && empty($thisbids)) $thisscreenname = "";
			}
		}
	}
}

// Show category name and description...
if ($cat && !$product && $showcategoryname != "off") {
	if ($device == "mobile") echo "<ul id=\"product-list\" data-role=\"listview\" data-theme=\"d\" data-inset=\"true\"><li><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
	else echo "<table class=\"ashoppageheader\">";
	echo "<tr>
	<td valign=\"top\" align=\"left\"><h1 class=\"ashoppageheadertext1\">$categoryname: </h1><span class=\"ashoppageheadertext2\">$categorydescr</span></td>
	<td align=\"right\" width=\"200\" class=\"ashoppageheadertext2\" valign=\"top\">";
	if ($activatefloatingprice && $thisscreenname) echo "$thisscreenname: <span id=\"bidsinfo\">$thisbids</span> ".BIDSLEFT."<br />";
	echo "<form name=\"sortbyform\" method=\"post\" action=\"\" style=\"margin-bottom: 0px;\"><select name=\"sortby\" class=\"ashopsortorderselector\" onchange=\"sortbyform.submit()\"><option value=\"default\">".SORTBY.":</option><option value=\"lowprice\""; if ($sortby == "lowprice") echo " selected"; echo ">".LOWESTPRICE."</option><option value=\"highprice\""; if ($sortby == "highprice") echo " selected"; echo ">".HIGHESTPRICE."</option><option value=\"name\""; if ($sortby == "name") echo " selected"; echo ">".NAME."</option></select></form></td></tr>";
	if (!empty($_COOKIE["affiliatesesid"])) {
		if (!in_array($cat, $affexcludecategories)) echo "<tr><td><span class=\"ashopproducttext\"><a href=\"affiliate/affiliate.php?category=$cat\">Hide this category</a></span></td><td>&nbsp;</td>";
		else echo "<tr><td><span class=\"ashopproducttext\"><a href=\"affiliate/affiliate.php?category=$cat\">Show this category</a></span></td><td>&nbsp;</td>";
	}
	echo "</table>";
	if ($device == "mobile") echo "</li>";
}

// Show up sell content...
if ($specialoffer == "true" && is_numeric($product)) {
	echo "<div class=\"ashoppageheadertext1\">".UPSELLTITLE."</div>
		<div class=\"ashoppageheadertext2\">".UPSELLTEXT."</div><br />";
}

// List products belonging to this category...
if ($firstpageexists && !$cat && !$product) {
	$description = @mysqli_result($firstpageresult,0,"description");
	if (strstr($description, "%features%")) {
		if ($device == "mobile") {
			$featuresperrow = 1;
			$featurehtml = "<center>";
		} else {
			$featuresperrow = 3;
			$featurehtml = "\n<table width=\"100%\" cellspacing=\"4\" cellpadding=\"0\" border=\"0\"><tbody>";
		}
		$featurerowcount = 1;
		for ($feature = 1; $feature <= $numberoffeatures; $feature++) {
			if ($device == "mobile") $featurehtml .= "";
			else {
				if ($featurerowcount == 1) $featurehtml .= "<tr>";
				$featurehtml .= "<td width=\"33%\" valign=\"top\" align=\"center\">";
			}
			$featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE featured='$feature' AND (prodtype!='content' OR prodtype IS NULL) AND $activestring='1'$excludeproductsquery");
			if (@mysqli_num_rows($featuredproductresult)) {
				$featurehtml .= "\n<table width=\"180\" cellspacing=\"0\" cellpadding=\"5\" style=\"border: 1px solid #C0C0C0; height: 180px;\"><tbody><tr><td align=\"center\">";
				$featuredproductrow = @mysqli_fetch_array($featuredproductresult);
				$featuredproductid = $featuredproductrow["productid"];
				$featuredproductcopyof = $featuredproductrow["copyof"];
				$featuredproducturl = $featuredproductrow["detailsurl"];
				$featuredproductname = $featuredproductrow["name"];
				$featuredproductdescr = $featuredproductrow["description"];
				if (substr($featuredproductdescr,0,3) == "<p>") $featuredproductdescr = substr($featuredproductdescr,3);
				if (substr($featuredproductdescr,-4) == "</p>") $featuredproductdescr = substr($featuredproductdescr,0,strlen($featuredproductdescr)-4);
				if (!$featuredproducturl) {
					$featuredproducturl = "$ashopurl/index.php?product=$featuredproductid";
				}

				// Check if there is a sale...
				$salediscount = FALSE;
				if ($featuredproductcopyof) $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$featuredproductcopyof' AND onetime='0' AND (code='' OR code IS NULL)");
				else $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$featuredproductid' AND onetime='0' AND (code='' OR code IS NULL)");
				if (@mysqli_num_rows($result2)) {
					$discountcustomerid = @mysqli_result($result2,0,"customerid");
					if ($discountcustomerid && !empty($_COOKIE["customersessionid"])) $checkcustomerid = @mysqli_result($customerresult,0,"customerid");
					else $checkcustomerid = "";
					if (empty($discountcustomerid) || $discountcustomerid == $checkcustomerid) {
						$salediscount = TRUE;
						$discounttype = @mysqli_result($result2,0,"type");
						$discountvalue = @mysqli_result($result2,0,"value");
					}
				} else $salediscount = FALSE;

				// Get the right price...
				if (empty($pricelevel) || $pricelevel < 1) $baseprice = $featuredproductrow["price"];
				else if ($pricelevel == 1) $baseprice = $featuredproductrow["wholesaleprice"];
				else {
					$pricelevels = $featuredproductrow["wspricelevels"];
					$pricelevels = explode("|",$pricelevels);
					$baseprice = $pricelevels[$pricelevel-2];
				}
				$productprice = $baseprice;

				// Apply sale discount...
				if ($salediscount) {
					$regprice = $productprice;
					if ($discounttype == "%") $productprice = $productprice - ($productprice * ($discountvalue/100));
					else if ($discounttype == "$") $productprice -= $discountvalue;
				} else $regprice = 0;

				// Show with tax...
				$taxmultiplier = 1+($taxpercentage/100);
				if ($featuredproductrow["taxable"] && $displaywithtax == 1) $productprice = $productprice*$taxmultiplier;

				if ($regprice) {
					if ($featuredproductrow["taxable"] && $displaywithtax == 1) $regprice = $regprice*$taxmultiplier;
					// Convert currency...
					if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
						$tempcurrency = $ashopcurrency;
						$ashopcurrency = $curr;
						$tempregprice = $regprice;
						$regprice = $regprice*$crate;
					}
					$regprice = "<span style=\"text-decoration: line-through;\">".$currencysymbols[$ashopcurrency]["pre"].number_format($regprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span>";
					// Convert back to main currency...
					if (!empty($curr) && !empty($crate) && is_numeric($crate)) $ashopcurrency = $tempcurrency;
				}

				// Convert currency...
				if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
					$tempcurrency = $ashopcurrency;
					$ashopcurrency = $curr;
					$tempproductprice = $productprice;
					$productprice = $productprice*$crate;
				}
				$price = $currencysymbols[$ashopcurrency]["pre"].number_format($productprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
				if ($featuredproductrow["qtytype"]) {
					$qtypricingresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$pricelevel' ORDER BY levelprice ASC LIMIT 1");
					if (@mysqli_num_rows($qtypricingresult)) {
						if ($featuredproductrow["qtytype"] == "1" || $featuredproductrow["qtytype"] == "2") {
							$minimumprice = @mysqli_result($qtypricingresult,0,"levelprice");
							if (!empty($curr) && !empty($crate) && is_numeric($crate)) $minimumprice = $minimumprice*$crate;
							$price = THEWORDFROM." ".$currencysymbols[$ashopcurrency]["pre"].number_format($minimumprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
						} else $price = $featuredproductrow["pricetext"];
					}
				}

				// Convert back to main currency...
				if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
					$ashopcurrency = $tempcurrency;
					$productprice = $tempproductprice;
				}

				// Get product image info...
				$productimage = ashop_productimages($featuredproductid);
				
				$featurehtml .= "<span class=\"ashopproductname\"><a href=\"$featuredproducturl\" style=\"text-decoration: none;\"";
				if ($device == "mobile") $featurehtml .= " data-ajax=\"false\"";
				$featurehtml .= ">$featuredproductname</a></span>";
				if ($productimage["thumbnail"]) {
					$featurehtml .= "<br /><br /><a href=\"$featuredproducturl\"";
					if ($device == "mobile") $featurehtml .= " data-ajax=\"false\"";
					$featurehtml .= "><img src=\"prodimg/$featuredproductid/{$productimage["thumbnail"]}\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\" /></a><br />";
				} else $featurehtml .= "<br /><span class=\"ashopproducttext\">$featuredproductdescr</span>";
				$featurehtml .= "<br /><span class=\"ashopproductprice\">";
				if ($regprice) $featurehtml .= " ".$regprice."<span class=\"ashopproductsale\">";
				$featurehtml .= " $price</span>";
				if ($regprice) $featurehtml .= "</span>";
				$featurehtml .= "</td></tr></tbody></table>";
			}
			if ($device == "mobile") $featurehtml .= "";
			else {
				$featurehtml .= "</td>";
				if ($featurerowcount == $featuresperrow) {
					$featurehtml .= "</tr>";
					$featurerowcount = 1;
				} else $featurerowcount++;
			}
		}
		if ($device != "mobile" && $featurerowcount < $featuresperrow) {
			for ($feature = $featurerowcount; $feature <= 3; $feature++) $featurehtml .= "<td>&nbsp;</td>";
			$featurehtml .= "</tr>";
		}
		if ($device == "mobile") $featurehtml .= "</center>";
		else $featurehtml .= "</tbody></table>";
		$description = str_replace("%features%",$featurehtml,$description);
	}
	echo $description;
}

else {
	if ($usecondensedlayout == "true" && $device != "mobile") echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td bgcolor=\"$itembordercolor\"><img src=\"images/invisible.gif\" height=\"2\" alt=\".\" /></td></tr></table>";
    if ($product) $sql = "SELECT * from product WHERE productid = '$product'";
    else {
		if ($includesubcategories) {
			// Get child categories...
			$catlist = "";
			$result = @mysqli_query($db, "SELECT categoryid FROM category WHERE parentcategoryid='$cat' OR grandparentcategoryid='$cat' OR categoryid='$cat'");
			while ($row = @mysqli_fetch_array($result)) $catlist .= "'".$row["categoryid"]."',";
			$catlist = substr($catlist,0,-1);
			$sql="SELECT product.* from productcategory, product WHERE productcategory.categoryid IN ($catlist) AND product.productid = productcategory.productid AND $activestring='1' AND ";
		} else $sql="SELECT product.* from productcategory, product WHERE productcategory.categoryid = '$cat' AND product.productid = productcategory.productid AND $activestring='1' AND ";
		if ($shop == "1") $sql .= "(product.userid LIKE '$shopsearch' OR product.inmainshop='1')";
		else $sql .= "product.userid LIKE '$shopsearch'";
		$sql .= $excludeproductsquery;
		if ($sortby == "lowprice") $sql .= " ORDER BY product.price ASC";
		else if ($sortby == "highprice") $sql .= " ORDER BY product.price DESC";
		else if ($sortby == "name") $sql .= " ORDER BY product.name ASC";
		else $sql .= " ORDER BY product.ordernumber $ashopsortorder";
	}
    $result = @mysqli_query($db, $sql);
	$numberofrows = intval(@mysqli_num_rows($result));
	$numberofpages = ceil($numberofrows/$displayitems);
	$resultpage = 0;
	if (isset($_GET["resultpage"])) $resultpage = $_GET["resultpage"];
	if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $displayitems;
	else {
		$resultpage = 1;
		$startrow = 0;
	}
	$startpage = $resultpage - 9;
	if ($numberofpages - $resultpage < 10) {
		$pagesleft = $numberofpages - $resultpage;
		$startpage = $startpage - (10 - $pagesleft);
	}
	if ($startpage < 1) $startpage = 1;
	$stoprow = $startrow + $displayitems;
	@mysqli_data_seek($result, $startrow);
	$thisrow = $startrow;
	$currentitemnumber = 1;
	$cellwidth = floor(100/$itemsperrow);
	$cellspacing = $itemborderwidth*2;
	$realshoppingcart = $shoppingcart;
	if ($device == "mobile" && ($product || $showcategoryname == "off")) echo "<ul id=\"product-list\" data-role=\"listview\" data-theme=\"d\" data-inset=\"true\">";
	else if ($device != "mobile") echo "<table class=\"ashopitemsframe\" cellspacing=\"{$cellspacing}\">";
    while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	  $shoppingcart = $realshoppingcart;
	  $thisrow++;
	  $unlockkeys = 0;
	  $windowheight = 200;
      $productid = $row["productid"];
	  $copyof = $row["copyof"];
	  if ($copyof) {
		  $productid = $copyof;
		  $originalresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$copyof'");
		  $row = @mysqli_fetch_array($originalresult);
	  }
	  if ($product) $buyproductid = "s$productid";
	  else $buyproductid = $productid;
      $productname = $row["name"];
	  $producturl = $row["detailsurl"];
	  if (!empty($shop) && $shop > 1) $producturl = str_replace("/catalog/","/catalog/vendor/",$producturl);
	  $safeproductname = $productname;
	  $owner = $row["userid"];
	  if ($owner != "1") {
		$memberresult = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='$owner'");
		$ownername = @mysqli_result($memberresult, 0, "shopname");
		if ($membershops) $ownerurl = "index.php?shop=$owner";
		else $ownerurl = "index.php?searchstring=searchuser$owner";
		if ($ownername && (empty($shop) || $shop == "1")) {
			if ($producturl) $productname .= "</a>";
			$productname .= " <i>".THEWORDBY." </i><a href=\"$ownerurl\"><i>$ownername</i>";
		}
	  }
	  $qtytlimit = $row["qtytlimit"];
	  $producttype = $row["prodtype"];
      $description = $row["description"];
	  if ($producttype != "content") {
		  if (substr($description,0,3) == "<p>") $description = substr($description,3);
		  if (substr($description,-4) == "</p>") $description = substr($description,0,strlen($description)-4);
	  }
	  if ($producttype == "content") {
		  if ($itemsperrow > 1 && $device != "mobile") {
			  if ($currentitemnumber > "1") {
				  for ($itemnumber = $currentitemnumber; $itemnumber <= $itemsperrow; $itemnumber++) echo "<td>&nbsp;</td>";
				  echo "</tr>";
			  }
		  }
		  for ($feature = 1; $feature <= 10; $feature++) {
			  $featurehtml = "";
			  $featurecheck = strpos($description, "%feature{$feature}%");
			  if ($featurecheck === false) {
			  } else {
				  $featuredproductresult = @mysqli_query($db, "SELECT * FROM product WHERE featured='$feature' AND (prodtype != 'content' OR prodtype IS NULL) AND $activestring='1'");
				  if (@mysqli_num_rows($featuredproductresult)) {
					  $featuredproductrow = @mysqli_fetch_array($featuredproductresult);
					  $featuredproductid = $featuredproductrow["productid"];
					  $featuredproductcopyof = $featuredproductrow["copyof"];
					  $featuredproducturl = $featuredproductrow["detailsurl"];
					  $featuredproductname = $featuredproductrow["name"];
					  $featuredproductdescr = $featuredproductrow["description"];
					  if (!$featuredproducturl) {
						  $featuredproducturl = "$ashopurl/index.php?product=$featuredproductid";
					  }

					  // Check if there is a sale...
					  $salediscount = FALSE;
					  if ($featuredproductcopyof) $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$featuredproductcopyof' AND onetime='0' AND (code='' OR code IS NULL)");
					  else $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$featuredproductid' AND onetime='0' AND (code='' OR code IS NULL)");
					  if (@mysqli_num_rows($result2)) {
						  $discountcustomerid = @mysqli_result($result2,0,"customerid");
						  if ($discountcustomerid && !empty($_COOKIE["customersessionid"])) $checkcustomerid = @mysqli_result($customerresult,0,"customerid");
						  else $checkcustomerid = "";
						  if (empty($discountcustomerid) || $discountcustomerid == $checkcustomerid) {
							  $salediscount = TRUE;
							  $discounttype = @mysqli_result($result2,0,"type");
							  $discountvalue = @mysqli_result($result2,0,"value");
						  }
					  } else $salediscount = FALSE;

					  /* Apply referral discounts...
					  if (!empty($affiliate)) {
						  if ($featuredproductcopyof) $result2 = @mysqli_query($db, "SELECT discount.* FROM discount,referraldiscount WHERE referraldiscount.affiliateid='$affiliate' AND referraldiscount.code=discount.code AND discount.productid='$featuredproductcopyof'");
						  else $result2 = @mysqli_query($db, "SELECT discount.* FROM discount,referraldiscount WHERE referraldiscount.affiliateid='$affiliate' AND referraldiscount.code=discount.code AND discount.productid='$featuredproductid'");
						  if (@mysqli_num_rows($result2)) {
							  $salediscount = TRUE;
							  $discounttype = @mysqli_result($result2,0,"type");
							  $discountvalue = @mysqli_result($result2,0,"value");
							  $discountcode = @mysqli_result($result2,0,"code");
						  }
					  }*/

					  // Get the right price...
					  if (empty($pricelevel) || $pricelevel < 1) $baseprice = $featuredproductrow["price"];
					  else if ($pricelevel == 1) $baseprice = $featuredproductrow["wholesaleprice"];
					  else {
						  $pricelevels = $featuredproductrow["wspricelevels"];
						  $pricelevels = explode("|",$pricelevels);
						  $baseprice = $pricelevels[$pricelevel-2];
					  }
					  $productprice = $baseprice;
					  
					  // Apply sale discount...
					  if ($salediscount) {
						  $regprice = $productprice;
						  if ($discounttype == "%") $productprice = $productprice - ($productprice * ($discountvalue/100));
						  else if ($discounttype == "$") $productprice -= $discountvalue;
					  } else $regprice = 0;
					  
					  // Show with tax...
					  $taxmultiplier = 1+($taxpercentage/100);
					  if ($featuredproductrow["taxable"] && $displaywithtax == 1) $productprice = $productprice*$taxmultiplier;

					  if ($regprice) {
						  if ($featuredproductrow["taxable"] && $displaywithtax == 1) $regprice = $regprice*$taxmultiplier;
						  // Convert currency...
						  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
							  $tempcurrency = $ashopcurrency;
							  $ashopcurrency = $curr;
							  $tempregprice = $regprice;
							  $regprice = $regprice*$crate;
						  }
						  $regprice = "<span style=\"text-decoration: line-through;\">".$currencysymbols[$ashopcurrency]["pre"].number_format($regprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span>";
						  // Convert back to main currency...
						  if (!empty($curr) && !empty($crate) && is_numeric($crate)) $ashopcurrency = $tempcurrency;
					  }

					  // Convert currency...
					  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
						  $tempcurrency = $ashopcurrency;
						  $ashopcurrency = $curr;
						  $tempproductprice = $productprice;
						  $productprice = $productprice*$crate;
					  }
					  $price = $currencysymbols[$ashopcurrency]["pre"].number_format($productprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
					  if ($featuredproductrow["qtytype"]) {
						  $qtypricingresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$pricelevel' ORDER BY levelprice ASC LIMIT 1");
						  if (@mysqli_num_rows($qtypricingresult)) {
							  if ($featuredproductrow["qtytype"] == "1" || $featuredproductrow["qtytype"] == "2") {
								  $minimumprice = @mysqli_result($qtypricingresult,0,"levelprice");
								  if (!empty($curr) && !empty($crate) && is_numeric($crate)) $minimumprice = $minimumprice*$crate;
								  $price = THEWORDFROM." ".$currencysymbols[$ashopcurrency]["pre"].number_format($minimumprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
							  } else $price = $featuredproductrow["pricetext"];
						  }
					  }
					  
					  // Convert back to main currency...
					  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
						  $ashopcurrency = $tempcurrency;
						  $productprice = $tempproductprice;
					  }

					  // Get product image info...
					  $productimage = ashop_productimages($featuredproductid);

					  $featurehtml = "<span class=\"ashopproductname\"><a href=\"$featuredproducturl\" style=\"text-decoration: none;\"";
					  if ($device == "mobile") $featurehtml .= " data-ajax=\"false\"";
					  $featurehtml .= ">$featuredproductname</a></span>";
					  if ($productimage["thumbnail"]) {
						  $featurehtml .= "<br /><br /><a href=\"$featuredproducturl\"";
						  if ($device == "mobile") $featurehtml .= " data-ajax=\"false\"";
						  $featurehtml .= "><img src=\"prodimg/$featuredproductid/{$productimage["thumbnail"]}\" alt=\"$featuredproductname\" width=\"$thumbnailwidth\" border=\"0\" /></a><br />";
					  } else $featurehtml .= "<br /><span class=\"ashopproducttext\">$featuredproductdescr</span>";
					  $featurehtml .= "<br /><span class=\"ashopproductprice\">";
					  if ($regprice) $featurehtml .= " ".$regprice."<span class=\"ashopproductsale\">";
					  $featurehtml .= " $price</span>";
					  if ($regprice) $featurehtml .= "</span>";
				  }
				  $description = str_replace("%feature{$feature}%",$featurehtml,$description);
			  }
		  }
		  if ($device == "mobile") {
			  $mobiledescription = str_replace("<p>","<br />",$description);
			  $mobiledescription = str_replace("</p>","",$mobiledescription);
			  $mobiledescription = str_replace("<h1>","<strong>",$mobiledescription);
			  $mobiledescription = str_replace("</h1>","</strong>",$mobiledescription);
			  $mobiledescription = str_replace("<h2>","<strong>",$mobiledescription);
			  $mobiledescription = str_replace("</h2>","</strong>",$mobiledescription);
			  $mobiledescription = str_replace("<h3>","<strong>",$mobiledescription);
			  $mobiledescription = str_replace("</h3>","</strong>",$mobiledescription);
			  $mobiledescription = str_replace("<h4>","<strong>",$mobiledescription);
			  $mobiledescription = str_replace("</h4>","</strong>",$mobiledescription);
			  $mobiledescription = str_replace("<h5>","<strong>",$mobiledescription);
			  $mobiledescription = str_replace("</h5>","</strong>",$mobiledescription);
			  $mobiledescription = str_replace("<h6>","<strong>",$mobiledescription);
			  $mobiledescription = str_replace("</h6>","</strong>",$mobiledescription);
			  echo "<li class=\"productRow\">$mobiledescription</li>";
		  } else echo "<tr><td colspan=\"$itemsperrow\">$description</td></tr>";
		  $currentitemnumber = 1;
		  continue;
	  }
	  $licensetext = $row["licensetext"];
	  $subscriptiondir = $row["subscriptiondir"];
	  if ($licensetext != "") $windowheight += 50;

	  // Check if there is a sale...
	  $salediscount = FALSE;
	  if ($copyof) $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$copyof' AND onetime='0' AND (code='' OR code IS NULL)");
	  else $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' AND onetime='0' AND (code='' OR code IS NULL)");
	  if (@mysqli_num_rows($result2)) {
		  $discountcustomerid = @mysqli_result($result2,0,"customerid");
		  if ($discountcustomerid && !empty($_COOKIE["customersessionid"])) $checkcustomerid = @mysqli_result($customerresult,0,"customerid");
		  else $checkcustomerid = "";
		  if (empty($discountcustomerid) || $discountcustomerid == $checkcustomerid) {
			  $salediscount = TRUE;
			  $discounttype = @mysqli_result($result2,0,"type");
			  $discountvalue = @mysqli_result($result2,0,"value");
		  }
	  } else $salediscount = FALSE;

	  // Get the right price...
	  if (empty($pricelevel) || $pricelevel < 1) $baseprice = $row["price"];
	  else if ($pricelevel == 1) $baseprice = $row["wholesaleprice"];
	  else {
		  $pricelevels = $row["wspricelevels"];
		  $pricelevels = explode("|",$pricelevels);
		  $baseprice = $pricelevels[$pricelevel-2];
	  }
	  $productprice = $baseprice;

	  if (empty($productprice)) $productprice = "0.00";

	  // Apply sale discount...
	  if ($salediscount) {
		  $regprice = $productprice;
		  if ($discounttype == "%") $productprice = $productprice - ($productprice * ($discountvalue/100));
		  else if ($discounttype == "$") $productprice -= $discountvalue;
	  } else $regprice = 0;

	  // Show with tax...
	  $taxmultiplier = 1+($taxpercentage/100);
	  if ($row["taxable"] && $displaywithtax == 1) $productprice = $productprice*$taxmultiplier;

	  if ($regprice) {
		  if ($row["taxable"] && $displaywithtax == 1) $regprice = $regprice*$taxmultiplier;
		  // Convert currency...
		  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			  $tempcurrency = $ashopcurrency;
			  $ashopcurrency = $curr;
			  $tempregprice = $regprice;
			  $regprice = $regprice*$crate;
		  }		  
		  $regprice = "<span style=\"text-decoration: line-through;\">".$currencysymbols[$ashopcurrency]["pre"].number_format($regprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span>";
		  // Convert back to main currency...
		  if (!empty($curr) && !empty($crate) && is_numeric($crate)) $ashopcurrency = $tempcurrency;
	  }

	  // Convert currency...
	  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		  $tempcurrency = $ashopcurrency;
		  $ashopcurrency = $curr;
		  $tempproductprice = $productprice;
		  $productprice = $productprice*$crate;
	  }
      $price = $currencysymbols[$ashopcurrency]["pre"].number_format($productprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
	  if ($row["qtytype"]) {
		  $qtypricingresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$pricelevel' ORDER BY levelprice ASC LIMIT 1");
		  if (@mysqli_num_rows($qtypricingresult)) {
			  if ($row["qtytype"] == "1" || $row["qtytype"] == "2") {
				  $minimumprice = @mysqli_result($qtypricingresult,0,"levelprice");
				  if (!empty($curr) && !empty($crate) && is_numeric($crate)) $minimumprice = $minimumprice*$crate;
				  $price = THEWORDFROM." ".$currencysymbols[$ashopcurrency]["pre"].number_format($minimumprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
			  } else $price = $row["pricetext"];
		  }
	  }
	  // Convert back to main currency...
	  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		  $ashopcurrency = $tempcurrency;
		  $productprice = $tempproductprice;
	  }

	  // Check if this product's recurring period should override the main setting...
	  $overrideshoppingcart = FALSE;
	  if (!empty($row["recurringperiod"])) {
		  $recurringperiodcheck = @mysqli_query($db, "SELECT payoptionid FROM payoptions WHERE recurringperiod!='{$row["recurringperiod"]}' AND recurringperiod IS NOT NULL AND recurringperiod!=''");
		  if (@mysqli_num_rows($recurringperiodcheck)) $overrideshoppingcart = TRUE;
	  }

	  // Check inventory status...
	  if ($row["useinventory"]) {
		  if ($row["inventory"]<1) $avail = "<span class=\"ashopproductoutofstock\">".OUTOFSTOCK." </span>";
		  else if ($row["inventory"] < $row["lowlimit"]) $avail = "<span class=\"ashopproductlowstock\">".LOWSTOCK." ({$row["inventory"]}) </span>";
		  else $avail = "<span class=\"ashopproductinstock\">".INSTOCK." </span>";
	  } else $avail = "";
	  $filetypes = "";
	  $filesize = 0;
	  $totalfilesize = 0;
	  $previousfiletypes = "";
	  $pricehtml = "";
	  if ($copyof) $filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$copyof'");
	  else $filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
	  $files = @mysqli_num_rows($filesresult);
	  while($filerow = @mysqli_fetch_array($filesresult)) {
		  $filename = $filerow["filename"];
		  $fileid = $filerow["fileid"];
		  $fileurl = $filerow["url"];
		  $filetype = explode(".",$filename);
		  $filetype = strtolower($filetype[1]);
		  $firstletter = substr($filetype,0,1);
		  $firstletter = strtoupper($firstletter);
		  if(!is_array($previousfiletypes) || !in_array($filetype, $previousfiletypes)) $filetypes .= substr_replace($filetype,$firstletter,0,1).", ";
		  $previousfiletypes[] = $filetype;
		  if ($fileurl) $totalfilesize += ashop_remotefilesize($fileurl);
		  else if (file_exists("$ashopspath/products/$fileid")) $totalfilesize += filesize("$ashopspath/products/$fileid");
	  }
	  $filesize = floor($totalfilesize/1048576);
	  if ($filesize == 0) {
		  $filesize = floor($totalfilesize/1024);
		  if ($filesize == 0) $filesize = $totalfilesize." bytes";
		  else $filesize .= " kB";
	  } else $filesize .= " MB";

	  // Check if there are AWS videos available for this product...
	  $isawsvideo = 0;
	  if ($files > 0) {
		  if ($copyof) $awsfilesresult = @mysqli_query($db, "SELECT id FROM productfiles WHERE productid='$copyof' AND storage='1' LIMIT 1");
		  else $awsfilesresult = @mysqli_query($db, "SELECT id FROM productfiles WHERE productid='$productid' AND storage='1' LIMIT 1");
		  $isawsvideo = @mysqli_num_rows($awsfilesresult);
	  }

	  // Get attributes, if any, for the product...
	  if ($copyof) $parameterresult = @mysqli_query($db, "SELECT parameterid FROM parameters WHERE productid='$copyof' LIMIT 1");
	  else $parameterresult = @mysqli_query($db, "SELECT parameterid FROM parameters WHERE productid='$productid' LIMIT 1");
	  $parametercount = @mysqli_num_rows($parameterresult);
	  if ($parametercount) {
		  while ($parameterrow = @mysqli_fetch_array($parameterresult)) {
			  $parameterid = $parameterrow["parameterid"];
			  $parametervalueresult = @mysqli_query($db, "SELECT valueid FROM parametervalues WHERE parameterid='$parameterid'");
			  if (@mysqli_num_rows($parametervalueresult) == 1) $parametercount--;
		  }
		  if ($parametercount) {
			  $realshoppingcart = $shoppingcart;
			  $shoppingcart = 3;
		  }
	  }

	  // Check for unlock keys...
	  if ($copyof) $result2 = @mysqli_query($db, "SELECT * FROM unlockkeys WHERE productid='$copyof' LIMIT 1");
	  else $result2 = @mysqli_query($db, "SELECT * FROM unlockkeys WHERE productid='$productid' LIMIT 1");
	  if (@mysqli_num_rows($result2)) $unlockkeys = 1;

	  // Check for video stream preview...
	  $previewfile = "";
	  $previewurl = "";
	  if ($copyof) $videopreviewresult = @mysqli_query($db, "SELECT * FROM productpreviewfiles WHERE productid='$copyof'");
	  else $videopreviewresult = @mysqli_query($db, "SELECT * FROM productpreviewfiles WHERE productid='$productid'");
	  if (@mysqli_num_rows($videopreviewresult)) {
		  $fpwidth = 310/$itemsperrow;
		  $fpheight = 230/$itemsperrow;
		  $videopreviewrow = @mysqli_fetch_array($videopreviewresult);
		  $videopreviewfilename = $videopreviewrow["filename"];
		  $videopreviewfileinfo = pathinfo($videopreviewfilename);
		  $videopreviewextension = strtolower($videopreviewfileinfo["extension"]);
		  $xspfcode = "<div id=\"videoplayerdiv\"></div>
		  <script>
			jwplayer(\"videoplayerdiv\").setup({
				file: \"{$cloudfrontpreviewsurl}$videopreviewextension:$awsdirectory/$videopreviewfilename\",
				width: \"$fpwidth\",
				height: \"$fpheight\"
			});
		  </script>
		  ";
		  $previewurl = "1";
	  } else $xspfcode = "";

	  // Check for preview file...
	  if (is_dir("$ashoppath/previews/$productid")) {
		  $findfile = opendir("$ashoppath/previews/$productid");
		  while (false !== ($foundfile = readdir($findfile)) && !$previewfile) { 
			  if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") $previewfile = $foundfile;
			  unset($foundfile);
		  }
		  unset($findfile);
	  }
	  if ($previewfile && empty($previewurl)) {
		  $fileinfo = pathinfo("$previewfile");
		  $extension = $fileinfo["extension"];
		  $previewurl = "$ashopurl/previews/$productid/$previewfile";
		  $musicpreviewurl = "previews/$productid/$previewfile";
		  $musicplayerbg = str_replace("#","",$catalogheader);
		  if (strtolower($extension) == "mp3" && file_exists("$ashoppath/includes/musicplayer.swf")) $xspfcode = "
		  <audio id=\"player$productid\" style=\"width: 60px;\"><source src=\"$musicpreviewurl\" type=\"audio/mpeg\">
		  <object type=\"application/x-shockwave-flash\" width=\"25\" height=\"20\" align=\"absbottom\" data=\"includes/musicplayer.swf\" bgcolor=\"$itembgcolor\">
		  <param name=\"movie\" value=\"includes/musicplayer.swf\" />
		  <param name=\"FlashVars\" value=\"mp3=$musicpreviewurl&showslider=0&width=25&bgcolor1=$musicplayerbg&bgcolor2=$musicplayerbg\"/>
		  </object> <span class=\"ashopproducttext\">&nbsp;".PRESSPLAY."</span></audio>
		  <script type=\"text/javascript\">var canplayhtmlfive = !!(document.getElementById('player$productid').canPlayType && document.getElementById('player$productid').canPlayType('audio/mpeg;').replace(/no/, ''));
		  if (canplayhtmlfive) document.write('<img id=\"playbutton$productid\" style=\"vertical-align: middle;\" src=\'images/playbutton.gif\' alt=\'Play\' onClick=\'if (document.getElementById(\"player$productid\").paused) { document.getElementById(\"player$productid\").play(); document.getElementById(\"playbutton$productid\").src=\"images/pausebutton.gif\"; } else { document.getElementById(\"player$productid\").pause(); document.getElementById(\"playbutton$productid\").src=\"images/playbutton.gif\"; }\' /> <span class=\"ashopproducttext\">&nbsp;".PRESSPLAY."</span>');</script>";
		  else if (strtolower($extension) == "xspf" && file_exists("$ashoppath/includes/musicplayer.swf")) $xspfcode = "
		  <object type=\"application/x-shockwave-flash\" width=\"200\" height=\"17\" align=\"absbottom\" data=\"includes/playlistplayer.swf?playlist_url=$musicpreviewurl&player_title=".urlencode($productname)."\">
		  <param name=\"movie\" value=\"includes/playlistplayer.swf?playlist_url=$musicpreviewurl&player_title=".urlencode($productname)."\"/>
		  </object><br /><span class=\"ashopproducttext\">".PRESSPLAY."</span>";
		  else if ((strtolower($extension) == "mp4" || strtolower($extension) == "flv") && file_exists("$ashoppath/includes/flowplayer-3.2.7.swf")) {
			  $fpwidth = 310/$itemsperrow;
			  $fpheight = 230/$itemsperrow;
			  $xspfcode = "
			  <a href=\"$previewurl\" style=\"display:block;width:{$fpwidth}px;height:{$fpheight}px\" id=\"player$productid\"> </a> 
			  <script type=\"text/javascript\">flowplayer(\"player$productid\", \"includes/flowplayer-3.2.7.swf\", { clip: { autoPlay: false, autoBuffering: true, bufferLength: 3 } });</script>";			  
		  } else $xspfcode = "";
	  }

	  // Get product image info...
	  $productimage = ashop_productimages($productid);

	  // Check for floating price...
	  if ($activatefloatingprice) {
		  $floatingpriceresult = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$productid'");
		  if (@mysqli_num_rows($floatingpriceresult)) {
			  $floatingpricerow = @mysqli_fetch_array($floatingpriceresult);
			  $starttime = $floatingpricerow["starttime"];
			  if (!$starttime) $starttime = 0;
			  $length = $floatingpricerow["length"];
			  $seconds = time() - $starttime;
			  $secondsleft = $length - $seconds;
			  if ($secondsleft > $length) $secondsleft = $length;
			  if ($starttime <= 0) $secondsleft = $length;
			  if ($secondsleft <= 0) $secondsleft = SOLD;
			  else {
				  $secleft = $secondsleft;
				  $daysleft = floor($secleft/86400);
				  $secleft -= $daysleft*86400;
				  $hoursleft = floor($secleft/3600);
				  $secleft -= $hoursleft*3600;
				  $minutesleft = floor($secleft/60);
				  $secleft -= $minutesleft*60;
				  $secondsleft = "";
				  if ($daysleft == 1) $secondsleft .= "$daysleft ".COUNTERDAY.", ";
				  else if ($daysleft > 1) $secondsleft .= "$daysleft ".COUNTERDAYS.", ";
				  if ($hoursleft > 0) $secondsleft .= "$hoursleft ".COUNTERHOURS.", ";
				  if ($minutesleft > 0) $secondsleft .= "$minutesleft ".COUNTERMINUTES.", ";
				  $secondsleft .= "$secleft ".COUNTERSECONDS;
			  }
			  $activatetime = $floatingpricerow["activatetime"];
			  if ($activatetime > time()) {
				  $activated = 0;
				  $activatestring = AUCTIONSTARTSIN;
				  $activatein = $activatetime-time();
				  $activateindays = floor($activatein/86400);
				  if ($activateindays) $activatestring .= "$activateindays ".DAYS;
				  $activatein -= $activateindays*86400;
				  $activateinhours = floor($activatein/3600);
				  if ($activateinhours) {
					  if ($activateindays) $activatestring .= ", ";
					  $activatestring .= "$activateinhours ".HOURS;
				  }
				  $activatein -= $activateinhours*3600;
				  $activateinminutes = ceil($activatein/60);
				  if ($activateinminutes) {
					  if ($activateinhours || $activateindays) $activatestring .= ", ";
					  $activatestring .= "$activateinminutes ".MINUTES;
				  }
				  $secondsleft = "";
			  } else {
				  $activated = 1;
				  $activatestring = "";
				  $hidestring = "";
			  }
			  $startprice = $floatingpricerow["startprice"];
			  $endprice = $floatingpricerow["endprice"];
			  if (!empty($endprice)) $hidestring = " style=\"display: none;\"";
			  $priceincrement = $floatingpricerow["priceincrement"];
			  $bids = $floatingpricerow["bids"];
			  $bidder = $floatingpricerow["bidderid"];
			  $auctiontype = $floatingpricerow["type"];
			  $currentprice = number_format($startprice + ($priceincrement*$bids),2,'.','');
			  if (!empty($endprice)) $currentprice = $endprice;
			  $bidderresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$bidder'");
			  $screenname = @mysqli_result($bidderresult,0,"screenname");
			  $pricehtml = "
			  <span class=\"ashopproductlabel\"><div id=\"activateinfo{$productid}\">$activatestring</div></span>";
			  if (!empty($activatestring)) $pricehtml .= "<span class=\"ashopproductbid\"><div id=\"countdown{$productid}\">$secondsleft</div></span><span class=\"ashopproducttext\"></span><span class=\"ashopproductbid\"><div id=\"price{$productid}\"></div></span><span class=\"ashopproductlabel\"";
			  if (empty($endprice) && empty($activatestring)) $pricehtml .= "<span class=\"ashopproducttext\">".AUCTIONENDSIN.":</span><br />";
			  if (empty($activatestring)) $pricehtml .= "<span class=\"ashopproductbid\"><div id=\"countdown{$productid}\">$secondsleft</div></span><span class=\"ashopproducttext\">".CURRENTBID.":</span><span class=\"ashopproductbid\"><div id=\"price{$productid}\">".$currencysymbols[$ashopcurrency]["pre"]."$currentprice".$currencysymbols[$ashopcurrency]["post"]."</div></span><span class=\"ashopproductlabel\"";
			  if ($itemsperrow > 2) $pricehtml .= " style=\"float:none;\"";
			  $pricehtml .= "><div id=\"screenname{$productid}\" class=\"ashopproducttext\" style=\"font-weight: normal;\">";
			  if (empty($endprice)) $pricehtml .= BIDDER;
			  else $pricehtml .= WONBY;
			  $pricehtml .= ": <b>$screenname</b></div></span>";
			  if ($auctiontype == "standard" && !empty($_COOKIE["customersessionid"])) $pricehtml .= "<br /><div class=\"ashopbidbutton\" id=\"bidbutton{$productid}\"$hidestring><form action=\"bidregister.php\" method=\"post\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"catalog\" value=\"$catalog\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"image\" src=\"{$buttonpath}images/bid-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".PLACEBID."\" align=\"top\" /></form></div>";
			  else if (!$endprice && $activatebidding && $auctiontype == "penny") $pricehtml .= "<div class=\"ashopbidbutton\" id=\"bidbutton{$productid}\"$hidestring><input type=\"image\" src=\"{$buttonpath}images/bid-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"Place Bid\" align=\"top\" onclick=\"placebid($productid,$thisbidder); return false;\" /></div>";
			  else {
				  $pricehtml .= "<div id=\"bidbutton{$productid}\"></div>";
				  if (!$endprice) {
					  if ($auctiontype == "standard") $pricehtml .= "<br /><span class=\"ashopproducttext\"><a href=\"login.php\">".LOGIN."</a> ".THEWORDOR." <a href=\"signupform.php\">".REGISTERS."</a> ".TOBID."</span>";
					  else if ($auctiontype == "penny") $pricehtml .= "<br /><span class=\"ashopproducttext\"><a href=\"bidregister.php\">".REGISTER."</a> ".TOBID."</span>";
				  }				  
			  }
			  $pricehtml .= "<script language=\"JavaScript\" type=\"text/javascript\">IDs[IDs.length] = $productid; starttime[{$productid}] = $starttime; fplength[{$productid}] = $length; activated[{$productid}] = $activated; activatetime[{$productid}] = $activatetime-timediff; auctiontype[{$productid}] = '$auctiontype';";
			  if (!$activated) $pricehtml .= " $('bidbutton{$productid}').style.display='none'; $('price{$productid}').style.display='none'; $('screenname{$productid}').style.display='none';";
			  $pricehtml .= "</script>";
		  }
	  }

	  if ($device == "mobile") {
		  // Mobile layout...
		  include "views/mobile.inc.php";

	  } else if ($usecondensedlayout != "true") {
		  // Default layout...
		  if ($currentitemnumber == "1") echo "\n<tr>";
		  echo "\n<td width=\"$cellwidth%\" class=\"ashopitembox\">";

		  include "views/default.inc.php";

		  echo "</td>";
		  if ($currentitemnumber == $itemsperrow) echo "</tr>";

	  } else {
		  // Condensed layout...
		  if ($currentitemnumber == "1") echo "\n<tr>";
		  echo "<td width=\"$cellwidth%\" valign=\"top\">";

		  include "views/condensed.inc.php";

		  echo "</td>";

		  if ($currentitemnumber == $itemsperrow) echo "
		  </tr><tr><td colspan=\"$itemsperrow\"><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td bgcolor=\"$itembordercolor\"><img src=\"images/invisible.gif\" height=\"2\" alt=\".\" /></td></tr></table></td></tr>";

	  }
	  $currentitemnumber++;
	  if ($currentitemnumber > $itemsperrow) $currentitemnumber = 1;
	}
	if ($itemsperrow > 1 && $currentitemnumber < $itemsperrow+1 && $device != "mobile") {
		for ($filling = $currentitemnumber; $filling <= $itemsperrow; $filling++) echo "<td width=\"$cellwidth\">&nbsp;</td>";
		$currentitemnumber = 1;
		echo "</tr>";
	}
	if ($device == "mobile") echo "</ul>";
	else echo "</table>";
	if ($numberofpages > 1) {
		if (!$catalog) $catalog = "index.php";
		echo "
		<table class=\"ashoppagestable\"><tr><td align=\"center\"><span class=\"ashoppageslist\">".PAGE.": ";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"$catalog?cat=$cat&exp=$exp&resultpage=$previouspage$shopurlstring\">".PREVIOUS."</a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"$catalog?cat=$cat&exp=$exp&resultpage=$i$shopurlstring\">$i</a>";
			else echo "<span style=\"font-size: larger;\">$i</span>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"$catalog?cat=$cat&exp=$exp&resultpage=$nextpage$shopurlstring\" style=\"text-decoration: none\">".NEXTPAGE."</a>>></span>";
		}
		echo "
		</td></tr></table>";
	}
}

// Print check out link if up selling...
if ($specialoffer == "true") {
	if ($device == "mobile") echo "<form name=\"shoppingcart\" style=\"margin-bottom: 0px;\" data-ajax=\"false\"><input type=\"hidden\" name=\"subtotal\" /></form><div class=\"ashopproducttext\" align=\"center\"><a href=\"index.php?specialoffer=false\" data-ajax=\"false\">".PROCEEDTOCHECKOUT."</a></div>";
	else echo "<form name=\"shoppingcart\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"subtotal\" /></form><div class=\"ashopproducttext\" align=\"center\"><a href=\"index.php?specialoffer=false\">".PROCEEDTOCHECKOUT."</a></div>";
}
?>