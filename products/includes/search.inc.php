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

if (preg_match("/\Wsearch.inc.php/",$_SERVER["PHP_SELF"])>0) {
	header("Location: ../index.php");
	exit;
}

include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";
include "counter.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Validate variables...
if (!is_numeric($cat)) unset($cat);
$checkexp = str_replace("|","",$exp);
if (!is_numeric($checkexp)) unset($exp);
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($_GET["resultpage"])) unset($_GET["resultpage"]);
if (strstr($searchstring,"searchuser")) $searchuser = trim(str_replace("searchuser","",$searchstring));
if ($searchuser && !is_numeric($searchuser)) unset($searchuser);
if (get_magic_quotes_gpc()) {
  $searchstring = stripslashes($searchstring);
}
$searchstring = strip_tags($searchstring);
$searchstring = @mysqli_real_escape_string($db, $searchstring);
$searchstring = str_replace("\"","",$searchstring);
$searchstring = str_replace("%22","",$searchstring);
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

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

// Select shop...
if ((isset($_GET["shop"]) || isset($_POST["shop"]))) {
	if (isset($_GET["shop"])) $newshop = $_GET["shop"];
	if (isset($_POST["shop"])) $newshop = $_POST["shop"];
	unset($shop);
	$shop = $newshop;
}
if (empty($shop) || !is_numeric($shop)) {
	$shop = "1";
	$shopurlstring = "";
} else $shopurlstring = "&shop=$shop";

if (!$membershops) $shopsearch = "%";
else $shopsearch = $shop;
if (!$p3psent && !$categories && !headers_sent()) {
	header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
}

// Check if Google Checkout should be used for buy buttons...
if ($shoppingcart == "0") {
	$gcocheckresult = @mysqli_query($db, "SELECT * FROM payoptions WHERE (wholesaleonly!='1' OR wholesaleonly IS NULL) AND (emerchantonly!='1' OR emerchantonly IS NULL) AND gateway='googleco' AND userid='$shop'");
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
include "language/$lang/search.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/$templatefile.html")) $templatepath = "/members/files/$ashopuser";

// Show "Please wait" page while completing the search...
if($searchstring && !$showresult) {
	echo "<span class=\"ashoppageheadertext1\">".SEARCHING."</span><meta http-equiv=\"Refresh\" content=\"0; URL=$search?searchstring=$searchstring&showresult=true&exp=$exp&resultpage=$resultpage&categories=$categories&search=$search$shopurlstring";
	if (!empty($m) && $m == "1") echo "&m=1";
	echo "\">";
} else {
ob_start();

// Get default category...
if (empty($numberofcategories)) {
	if ($hideemptycategories) $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.$activestring='1' AND product.userid LIKE '$shopsearch' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
	else $result = @mysqli_query($db, "SELECT categoryid FROM category WHERE (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber");
	$numberofcategories = @mysqli_num_rows($result);
	if ($numberofcategories > 0 && !$cat) $cat = @mysqli_result($result, 0, "categoryid");
}

// Check for floating price products...
$checkfpresult = @mysqli_query($db, "SELECT * FROM floatingprice LIMIT 1");
$activatefloatingprice = @mysqli_num_rows($checkfpresult);

// Print top of page...
echo "
	<script language=\"JavaScript\" src=\"includes/prototype.js\" type=\"text/javascript\"></script>
	<script type=\"text/javascript\" src=\"includes/jquery-1.7.1.min.js\"></script>
	<script type=\"text/javascript\" src=\"includes/jquery-ui-1.8.16.custom.min.js\"></script>
	<script type=\"text/javascript\" src=\"includes/jquery.colorbox-min.js\"></script>
	<script type=\"text/javascript\" src=\"includes/addtocart.js\"></script>
	<script language=\"JavaScript\" type=\"text/javascript\">
	/* <![CDATA[ */
		 jQuery.noConflict();
		 jQuery(document).ready(function() {
			jQuery(\".gallery\").colorbox({iframe:true, width:\"800px\", height:\"530px\"});
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
if (file_exists("$ashoppath/includes/flowplayer-3.2.6.min.js")) echo "
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

// Show search page header...
echo "<table class=\"ashoppageheader\"><tr>
	<td valign=\"top\"><h1 class=\"ashoppageheadertext1\">";
	if (isset($m) && $m == "1") echo $searchstring;
	else echo SEARCHRESULT;
	echo ":</h1></td>
	<td align=\"right\" width=\"200\" class=\"ashoppageheadertext2\" valign=\"top\"><form name=\"sortbyform\" method=\"post\" action=\"\"><select name=\"sortby\" class=\"ashopsortorderselector\" onchange=\"sortbyform.submit()\"><option value=\"default\">".SORTBY.":</option><option value=\"lowprice\""; if ($sortby == "lowprice") echo " selected"; echo ">".LOWESTPRICE."</option><option value=\"highprice\""; if ($sortby == "highprice") echo " selected"; echo ">".HIGHESTPRICE."</option><option value=\"name\""; if ($sortby == "name") echo " selected"; echo ">".NAME."</option></select></form>";
	if ($activatefloatingprice && $thisscreenname) echo "<br />$thisscreenname: <span id=\"bidsinfo\">$thisbids</span> ".BIDSLEFT;
	echo "</td></tr></table>";

// List found products...
if ($searchstring) {
	// Check for category limiting...
	if ($searchcategories) {
		$includecategoriesarray = explode("|",$searchcategories);
		// Add subcategories, if any...
		foreach ($includecategoriesarray as $includecategoryid) {
			$includesubcategoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE (grandparentcategoryid='$includecategoryid' OR parentcategoryid='$includecategoryid') AND categoryid != '$includecategoryid'");
			while ($includesubcategoryrow = @mysqli_fetch_array($includesubcategoryresult)) {
				if (!in_array($includesubcategoryrow["categoryid"],$includecategoriesarray)) $includecategoriesarray[] .= $includesubcategoryrow["categoryid"];
			}
		}
		// Generate SQL...´
		$categoriessql = " AND (";
		foreach ($includecategoriesarray as $includecategoryid) $categoriessql .= "(productcategory.categoryid = '$includecategoryid' AND productcategory.productid=product.productid) OR ";
		$categoriessql = substr($categoriessql,0,-4);
		$categoriessql .= ")";
	}
	// Check for category exclusion...
	if ($nosearchcategories) {
		$excludecategoriesarray = explode("|",$nosearchcategories);
		// Add subcategories, if any...
		foreach ($excludecategoriesarray as $excludecategoryid) {
			$excludesubcategoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE (grandparentcategoryid='$excludecategoryid' OR parentcategoryid='$excludecategoryid') AND categoryid != '$excludecategoryid'");
			while ($excludesubcategoryrow = @mysqli_fetch_array($excludesubcategoryresult)) {
				if (!in_array($excludesubcategoryrow["categoryid"],$excludecategoriesarray)) $excludecategoriesarray[] .= $excludesubcategoryrow["categoryid"];
			}
		}
		// Create an array of categories that should not be excluded...
		$includecategoriesarray = array();
		$includecategoriesresult = @mysqli_query($db, "SELECT categoryid FROM category");
		while ($includecategoryrow = @mysqli_fetch_array($includecategoriesresult)) {
			if (!in_array($includecategoryrow["categoryid"],$excludecategoriesarray)) $includecategoriesarray[] .= $includecategoryrow["categoryid"];
		}
		// Generate SQL...´
		$categoriessql = " AND (";
		foreach ($includecategoriesarray as $includecategoryid) $categoriessql .= "(productcategory.categoryid = '$includecategoryid' AND productcategory.productid=product.productid) OR ";
		$categoriessql = substr($categoriessql,0,-4);
		$categoriessql .= ")";
	}
	if ($usecondensedlayout == "true" && $device != "mobile") echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td bgcolor=\"$itembordercolor\"><img src=\"images/invisible.gif\" height=\"2\" alt=\".\" /></td></tr></table>";
	if ($searchuser) $sql = "SELECT * FROM product WHERE userid LIKE '$searchuser' AND (copyof='' OR copyof='0' OR copyof IS NULL) AND $activestring='1' AND (prodtype!='content' OR prodtype IS NULL)";
	else {
		$searchwords = explode(" ", $searchstring);
		$sql="SELECT * from product";
		if ($category || $categoriessql) $sql .= ", productcategory";
		$sql .= " WHERE (copyof='' OR copyof='0' OR copyof IS NULL) AND";
		if ($shop == "1") $sql .= " (userid LIKE '$shopsearch' OR inmainshop='1') AND $activestring='1' AND (prodtype!='content' OR prodtype IS NULL) AND";
		else $sql .= " userid LIKE '$shopsearch' AND $activestring='1' AND (prodtype!='content' OR prodtype IS NULL) AND";
		foreach($searchwords as $wordnumber => $thisword) {
			// Store search stats...
			$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
			@mysqli_query($db, "INSERT INTO searchstatistics (date,keyword) VALUES ('$date','".strtolower($thisword)."')");
			if (!empty($m) && $m == "1") {
				if ($wordnumber == 0) $sql.=" UPPER(manufacturer) LIKE '%".strtoupper($thisword)."%'";
				else $sql.=" AND UPPER(manufacturer) LIKE '%".strtoupper($thisword)."%'";
			} else {
				if ($wordnumber == 0) $sql.=" (UPPER(description) LIKE '%".strtoupper($thisword)."%' OR UPPER(name) LIKE '%".strtoupper($thisword)."%' OR UPPER(manufacturer) LIKE '%".strtoupper($thisword)."%')";
				else $sql.=" AND (UPPER(description) LIKE '%".strtoupper($thisword)."%' OR UPPER(name) LIKE '%".strtoupper($thisword)."%' OR UPPER(manufacturer) LIKE '%".strtoupper($thisword)."%')";
			}
		}
		if ($category) $sql .= " AND (productcategory.categoryid = '$category' AND productcategory.productid=product.productid)";
		if ($categoriessql) $sql .= $categoriessql;
		if ($excludeproductsquery) $sql .= $excludeproductsquery;
		if ($sortby == "lowprice") $sql .= " ORDER BY price ASC";
		else if ($sortby == "highprice") $sql .= " ORDER BY price DESC";
		else if ($sortby == "name") $sql .= " ORDER BY name ASC";
		else $sql.=" ORDER BY name";
	}
	$result = @mysqli_query($db, $sql);
	if (!@mysqli_num_rows($result)) {
		echo "<br /><span class=\"ashopproducttext\">".NOMATCH."</span>";
	}
	$numberofrows = intval(@mysqli_num_rows($result));
	$numberofpages = ceil($numberofrows/$displayitems);
	unset($resultpage);
	$resultpage = $_GET["resultpage"];
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
	if ($device == "mobile") echo "<ul id=\"product-list\" data-role=\"listview\" data-theme=\"d\" data-inset=\"true\">";
	else echo "<table class=\"ashopitemsframe\" cellspacing=\"{$cellspacing}\">";
    while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	  $shoppingcart = $realshoppingcart;
	  $thisrow++;
	  $unlockkeys = 0;
	  $windowheight = 200;
      $productid = $row["productid"];
	  $copyof = $row["copyof"];
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

	  /* Apply referral discounts...
	  if (!empty($affiliate)) {
		  if ($copyof) $result2 = @mysqli_query($db, "SELECT discount.* FROM discount,referraldiscount WHERE referraldiscount.affiliateid='$affiliate' AND referraldiscount.code=discount.code AND discount.productid='$copyof'");
		  else $result2 = @mysqli_query($db, "SELECT discount.* FROM discount,referraldiscount WHERE referraldiscount.affiliateid='$affiliate' AND referraldiscount.code=discount.code AND discount.productid='$productid'");
		  if (@mysqli_num_rows($result2)) {
			  $salediscount = TRUE;
			  $discounttype = @mysqli_result($result2,0,"type");
			  $discountvalue = @mysqli_result($result2,0,"value");
			  $discountcode = @mysqli_result($result2,0,"code");
		  }
	  }*/
	  
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

	  // Check for preview file...
	  $previewfile = "";
	  $previewurl = "";
	  if (is_dir("$ashoppath/previews/$productid")) {
		  $findfile = opendir("$ashoppath/previews/$productid");
		  while (false !== ($foundfile = readdir($findfile)) && !$previewfile) { 
			  if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") $previewfile = $foundfile;
			  unset($foundfile);
		  }
		  unset($findfile);
	  }
	  if ($previewfile) {
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
			  <script type=\"text/javascript\">flowplayer(\"player$productid\", \"includes/flowplayer-3.2.7.swf\", { clip: { autoPlay: false, autoBuffering: true } });</script>";			  
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
			  if ($secondsleft <= 0) $secondsleft = "SOLD!";
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
				  if ($activateinhours) $activatestring .= "$activateinhours ".HOURS;
				  $activatein -= $activateinhours*3600;
				  $activateinminutes = ceil($activatein/60);
				  if ($activateinminutes) $activatestring .= "$activateinminutes ".MINUTES;
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
			  if (empty($endprice) && empty($activatestring)) $pricehtml .= "<span class=\"ashopproducttext\">".AUCTIONENDSIN.":</span><br />";
			  $pricehtml .= "<span class=\"ashopproductbid\"><div id=\"countdown{$productid}\">$secondsleft</div></span><span class=\"ashopproducttext\">".CURRENTBID.":</span><span class=\"ashopproductbid\"><div id=\"price{$productid}\">".$currencysymbols[$ashopcurrency]["pre"]."$currentprice".$currencysymbols[$ashopcurrency]["post"]."</div></span><span class=\"ashopproductlabel\"><div id=\"screenname{$productid}\" class=\"ashopproducttext\" style=\"font-weight: normal;\">";
			  if (empty($endprice)) $pricehtml .= BIDDER;
			  else $pricehtml .= WONBY;
			  $pricehtml .= ": <b>$screenname</b></div></span>";
			  if ($auctiontype == "standard" && !empty($_COOKIE["customersessionid"])) $pricehtml .= "<br /><div class=\"ashopbidbutton\" id=\"bidbutton{$productid}\"$hidestring><form action=\"bidregister.php\" method=\"post\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"catalog\" value=\"$catalog\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"image\" src=\"{$buttonpath}images/bid-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".PLACEBID."\" align=\"top\" /></form></div>";
			  else if (!$endprice && $activatebidding && $auctiontype == "penny") $pricehtml .= "<div class=\"ashopbidbutton\" id=\"bidbutton{$productid}\"$hidestring><input type=\"image\" src=\"{$buttonpath}images/bid-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"Place Bid\" align=\"top\" onclick=\"placebid($productid,$thisbidder); return false;\" /></div>";
			  else {
				  $pricehtml .= "<div id=\"bidbutton{$productid}\"></div>";
				  if (!$endprice) {
					  if ($auctiontype == "standard") $pricehtml .= "<span class=\"ashopproducttext\"><a href=\"login.php\">".LOGIN."</a> ".THEWORDOR." <a href=\"signupform.php\">".REGISTERS."</a> ".TOBID."</span>";
					  else if ($auctiontype == "penny") $pricehtml .= "<span class=\"ashopproducttext\"><a href=\"bidregister.php\">".REGISTER."</a> ".TOBID."</span>";
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
		if (!$search) $search = "search.php";
		echo "
		<table class=\"ashoppagestable\"><tr><td align=\"center\"><span class=\"ashoppageslist\">".PAGE.": ";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"$search?searchstring=$searchstring&exp=$exp&resultpage=$previouspage$shopurlstring\">".PREVIOUS."</a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"$search?searchstring=$searchstring&exp=$exp&resultpage=$i$shopurlstring\">$i</a>";
			else echo "<span style=\"font-size: larger;\">$i</span>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"$search?searchstring=$searchstring&exp=$exp&resultpage=$nextpage$shopurlstring\" style=\"text-decoration: none\">".NEXTPAGE."</a>>></span>";
		}
		echo "
		</td></tr></table>";
	}
}

ob_end_flush();
}
?>