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

if (!headers_sent() && isset($_COOKIE["fixbackbutton"])) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("fixbackbutton", "");
}

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";

if (preg_match("/\Wproduct.php/",$_SERVER["PHP_SELF"])>0) {
	header("Location: index.php?product=$product");
	exit;
}

if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";
if (!isset($currencynames)) include "admin/ashopconstants.inc.php";
include "counter.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

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
$url = "";
if (isset($product) && !is_numeric($product)) $url = $ashopurl."/catalog/".$product.".html";
if (isset($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = @mysqli_real_escape_string($db, $_COOKIE["customersessionid"]);

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
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db, "SELECT level, firstname, lastname, customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$pricelevel = @mysqli_result($customerresult,0,"level");
	$customerid = @mysqli_result($customerresult,0,"customerid");
} else $pricelevel = 0;
if ($pricelevel > 0) {
	$activestring = "wholesaleactive";
	$templatefile = "wscatalogue";
	$displaywithtax = $displaywswithtax;
} else {
	$activestring = "active";
	$templatefile = "catalogue";
}

// Get affiliate exclusion list...
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
	$hideprice = $affiliaterow["hideprice"];
}

// Convert URI and make it safe...
if (empty($url)) {
	if (!isset($_SERVER['REQUEST_URI']) and isset($_SERVER['SCRIPT_NAME'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
	if ($_SERVER['HTTPS'] == "on") $url = "https://";
	else $url = "http://";
	$url .= $HTTP_HOST.$REQUEST_URI;
}
if (strpos($url,"/catalog/")) {
	$url = str_replace("/catalog/vendor/","/catalog/",$url);
	$url = stripslashes($url);
	$url = @mysqli_real_escape_string($db, $url);
	$url = str_replace("\'","",$url);
	$url = str_replace("\"","",$url);
	$url = str_replace("\n","",$url);
	$url = str_replace(";","",$url);
} else $url = "";
$checkexp = str_replace("|","",$exp);
if (!is_numeric($checkexp)) unset($exp);

// Get product by url...
if (!empty($url)) {
	$productresult = @mysqli_query($db, "SELECT * FROM product WHERE detailsurl='$url'");
	$product = @mysqli_result($productresult,0,"productid");
}

// Check if product is hidden by affiliate...
if (in_array($product, $excludeproducts)) {
	header("Location: $ashopurl");
	exit;
}

// Post review...
if (!empty($addreview) && $addreview == "true") {
	if (empty($rating)) $rating = 0;
	if (is_numeric($rating) && $rating < 6 && $rating > -1 && !empty($_COOKIE["customersessionid"]) && !empty($productid) && is_numeric($productid)) {
		$product = $productid;
		$checkreviews = @mysqli_query($db, "SELECT * FROM reviews WHERE productid='$productid' AND customerid='$customerid'");
		if (!@mysqli_num_rows($checkreviews)) {
			$comment = stripslashes($comment);
			$comment = strip_tags($comment);
			$comment = @mysqli_real_escape_string($db, $comment);
			$comment = str_replace("\'","",$comment);
			$comment = str_replace("\\r\\n","<br />",$comment);
			$comment = str_replace("\\n","<br />",$comment);
			$reviewtime = date("Y-m-d H:i:s", time()+$timezoneoffset);
			@mysqli_query($db, "INSERT INTO reviews (customerid, productid, rating, time, comment) VALUES ('$customerid', '$productid', '$rating', '$reviewtime', '$comment')");
		}
	}
}

// Get currency rate if needed...
if (isset($curr) && preg_match("/^[a-z]*$/", $curr) && strlen($curr) == 3 && $curr != $ashopcurrency) $crate = getcurrency($curr);
else {
	$curr = "";
	$crate = 0;
}

// Get the product data...
if (!empty($product)) {
    $productresult = @mysqli_query($db, "SELECT * from product WHERE productid = '$product'");
	$productrow = @mysqli_fetch_array($productresult);
	if ($membershops == "1" && $categories != "off") $shop = $productrow["userid"];
	$ashopmetakeywords = $productrow["metakeywords"];
	$ashopmetadescription = $productrow["metadescription"];
	if (!empty($productrow["name"])) $ashoptitle = $ashopname." - ".$productrow["name"];
	else $ashoptitle = $ashopname;

	// Check if this product's recurring period should override the main setting...
	if (!empty($productrow["recurringperiod"])) {
		$recurringperiodcheck = @mysqli_query($db, "SELECT payoptionid FROM payoptions WHERE recurringperiod!='{$productrow["recurringperiod"]}' AND recurringperiod IS NOT NULL AND recurringperiod!=''");
		if (@mysqli_num_rows($recurringperiodcheck)) $shoppingcart = 0;
	}
}

$templatefile = "catalogue";

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
} else $shopurlstring = "&shop=$shop";

if (!$membershops) $shopsearch = "%";
else $shopsearch = $shop;
// Reload variables if the shop has been changed...
if ($shop != "1" && $categories != "off") {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$shop'");
	$row = @mysqli_fetch_array($result);
	if ($row["shopname"]) $ashopname = $row["shopname"];
	if ($row["theme"]) $ashoptheme = $row["theme"];
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
	if ($row["font"]) $font = $row["font"];
	if ($row["pageheader"]) $pageheader = $row["pageheader"];
	if ($row["pagefooter"]) $pagefooter = $row["pagefooter"];
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
include "language/$lang/catalogue.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/$templatefile.html")) $templatepath = "/members/files/$ashopuser";
  
// Get default category...
if (empty($numberofcategories)) {
	if ($hideemptycategories) $result = @mysqli_query($db, "SELECT DISTINCT productcategory.categoryid FROM productcategory, product, category WHERE productcategory.productid=product.productid AND productcategory.categoryid=category.categoryid AND product.$activestring='1' AND product.userid LIKE '$shopsearch' AND (category.userid LIKE '$shop' OR category.memberclone='1') AND (category.language = '$lang' OR category.language = 'any') ORDER BY category.ordernumber");
	else $result = @mysqli_query($db, "SELECT categoryid FROM category WHERE (userid LIKE '$shop' OR memberclone='1') AND (language = '$lang' OR language = 'any') ORDER BY ordernumber");
	$numberofcategories = @mysqli_num_rows($result);
	if ($numberofcategories > 0 && !$cat && !$firstpageexists) $cat = @mysqli_result($result, 0, "categoryid");
}

// Print header from template...
if ($categories != "off") {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
}

// Check for floating price products...
$checkfpresult = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$product' LIMIT 1");
$activatefloatingprice = @mysqli_num_rows($checkfpresult);

// Print top of page...
echo "
	<script language=\"JavaScript\" src=\"includes/prototype.js\" type=\"text/javascript\"></script>
	<script language=\"JavaScript\" src=\"includes/addtocart.js\" type=\"text/javascript\"></script>";
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
			 if ($categories == "off") {
				 echo "+'&amp;returnurl=";
				 $url = "http://";
				 $url .= $HTTP_HOST;
				 if ($REQUEST_URI) $url .= $REQUEST_URI;
				 else if ($_SERVER["HTTP_X_REWRITE_URL"]) $url .= $_SERVER["HTTP_X_REWRITE_URL"];
				 $url = str_replace("&","|",$url);
				 $url = str_replace("$ashopurl/","",$url);
				 $url = str_replace("$ashopsurl/","",$url);
				 echo "$url'"; 
			 } else echo "+'&amp;cat=$cat&amp;exp=$exp';";
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
		 function changeimage(zoom,picture,height)
		 {
			 document.getElementById('productimage').src=picture;
			 document.getElementById('productimage').height=height;
			 document.getElementById('productimagelink').href='prodimg/'+zoom;
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
	  echo "	  
	  <noscript>".JAVASCRIPTOOPS1."<a href=\"http://www.netscape.com\">Netscape</a> ".THEWORDAND." <a href=\"http://www.microsoft.com/ie\">Microsoft</a> ".JAVASCRIPTOOPS2."
      </noscript>
	  <script type=\"text/javascript\" src=\"includes/jquery-1.11.2.min.js\"></script>
	  <script type=\"text/javascript\" src=\"includes/jquery-ui.min.js\"></script>
	  <script type=\"text/javascript\" src=\"includes/jquery.colorbox-min.js\"></script>
	  <script type=\"text/javascript\">
	  /* <![CDATA[ */
	  jQuery.noConflict();
	  jQuery(document).ready(function() {
		  jQuery(\"#productdetails\").tabs();";
if ($addreview == "true") echo "
		  jQuery(\"#productdetails\").tabs('select', 2);";
echo "
		jQuery(\".gallery\").colorbox({iframe:true, width:\"800px\", height:\"530px\", opacity:\"0.7\"});
		jQuery(\".zoombox\").colorbox({rel:'zoombox', opacity:\"0.7\"});
	  });
	  var subjectline=\"".PRODUCTTIPFROM." \";
	  var tellafriendmessage=\"".HIYOUMAYWANTTOCHECKOUT." \"+window.location;
	  function validateemail(strEmail){
		  validRegExp = /^[^@]+@[^@]+.[a-z]{2,}$/i;
		  if (strEmail.search(validRegExp) == -1) {
			  alert('".AVALIDEMAILREQUIRED."');
			  return false;
		  } 
		  return true; 
	  }
	  function tellafriend() {
		  if (validateemail(document.getElementById('friendmailer').value)) {
			  window.location = \"mailto:\"+document.getElementById('friendmailer').value+\"?subject=\"+subjectline+\"&body=\"+tellafriendmessage;
			  return true;
		  }
	  }
	  /* ]]> */
	  </script>
	  <script type=\"text/javascript\" src=\"includes/review.js\"></script>
      <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr>";

// Show customer profile box...
if ($numberofcategories > 1 && $categories != "off") {
	echo "<td class=\"ashopcategoriesbox\">";
	if (file_exists("$ashoppath/customerprofile.php")) {
		echo "<table class=\"ashopboxtable\" cellspacing=\"0\"><tr><td class=\"ashopboxheader\">&nbsp;&nbsp;<img src=\"$ashopurl/images/customericon.gif\" style=\"vertical-align: text-bottom;\" alt=\"categories\" />&nbsp;&nbsp;&nbsp;".CUSTOMER."</td></tr>
		<tr><td class=\"ashopboxcontent\" align=\"center\">";
		if (!empty($_COOKIE["customersessionid"])) {
			$customerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
			$customerfirstname = @mysqli_result($customerresult, 0, "firstname");
			$customerlastname = @mysqli_result($customerresult, 0, "lastname");
			echo "&nbsp;<span class=\"ashopcustomertext2\">".WELCOME." $customerfirstname $customerlastname!<br />[<a href=\"customerprofile.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".PROFILE."</a>] [<a href=\"login.php?logout\">".LOGOUT."</a>]</span><br /><br />";
		} else {
			echo "&nbsp;<span class=\"ashopcustomertext2\">[<a href=\"signupform.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".REGISTER."</a>] [<a href=\"login.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".LOGIN."</a>]</span><br /><br />";
		}
		echo "</td></tr></table><br />";
	}
}

// List categories...
if ($numberofcategories > 1 && $categories != "off") {
	include "includes/categories.inc.php";
	echo "</td>";
}

echo "<td valign=\"top\">";

// Print shopping cart form if needed...
if ($shoppingcart && $topform != "off") include "includes/topform.inc.php";

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

// Show the product detail page...
if (!empty($productrow) && is_array($productrow)) {
	  $unlockkeys = 0;
	  $windowheight = 200;
      $productid = $productrow["productid"];
	  $copyof = $productrow["copyof"];
	  if ($copyof) {
		  $productid = $copyof;
		  $originalresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$copyof'");
		  $productrow = @mysqli_fetch_array($originalresult);
	  }
	  $buyproductid = "s$productid";
      $productname = $productrow["name"];
	  $safeproductname = $productname;
	  $productname = str_replace("&quot;","\"",$productname);
	  $productname = str_replace("&amp;","&",$productname);
	  $productname = str_replace("&","&amp;",$productname);
	  $productname = str_replace("\"","&quot;",$productname);
	  $owner = $productrow["userid"];
	  $skucode = $productrow["skucode"];
	  if ($owner != "1") {
		$memberresult = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='$owner'");
		$ownername = @mysqli_result($memberresult, 0, "shopname");
		if ($membershops) $ownerurl = "index.php?shop=$owner";
		else $ownerurl = "index.php?searchstring=searchuser$owner";
		if ($ownername && (empty($shop) || $shop == "1")) {
			if ($producturl) $productname .= "</a>";
			$productname .= " <i>".THEWORDBY." <a href=\"$ownerurl\">$ownername</a></i>";
		}
	  }
	  $qtytlimit = $productrow["qtytlimit"];
	  $producttype = $productrow["prodtype"];
      $description = $productrow["description"];
	  $longdescription = $productrow["longdescription"];
	  $licensetext = $productrow["licensetext"];
	  $subscriptiondir = $productrow["subscriptiondir"];
	  if ($licensetext != "") $windowheight += 50;

	  // Check which tabs should be shown...
	  $activatesocialnetworking = $productrow["activatesocialnetworking"];
	  $activatereviews = $productrow["activatereviews"];

	  // Check if there is a sale...
	  $personalsale = FALSE;
	  if ($customerid) {
		  if ($copyof) $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$copyof' AND onetime='0' AND (code='' OR code IS NULL) AND customerid='$customerid'");
		  else $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' AND onetime='0' AND (code='' OR code IS NULL) AND customerid='$customerid'");
		  if (@mysqli_num_rows($result2)) $personalsale = TRUE;
	  }
	  if (!$personalsale) {
		  if ($copyof) $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$copyof' AND onetime='0' AND (code='' OR code IS NULL)");
		  else $result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' AND onetime='0' AND (code='' OR code IS NULL)");
	  }
	  if (@mysqli_num_rows($result2)) {
		  $discountcustomerid = @mysqli_result($result2,0,"customerid");
		  if (empty($discountcustomerid) || $discountcustomerid == $customerid) {
			  $salediscount = TRUE;
			  $discounttype = @mysqli_result($result2,0,"type");
			  $discountvalue = @mysqli_result($result2,0,"value");
		  } else $salediscount = FALSE;
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
	  if (empty($pricelevel) || $pricelevel < 1) $baseprice = $productrow["price"];
	  else if ($pricelevel == 1) $baseprice = $productrow["wholesaleprice"];
	  else {
		  $pricelevels = $productrow["wspricelevels"];
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

	  // Get the right price...
	  $taxmultiplier = 1+($taxpercentage/100);
	  if ($productrow["taxable"] && $displaywithtax == 1) $productprice = $productprice*$taxmultiplier;

	  if ($regprice) {
		  if ($productrow["taxable"] && $displaywithtax == 1) $regprice = $regprice*$taxmultiplier;
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
	  if ($productrow["qtytype"]) {
		  $qtypricingresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid' AND customerlevel='$pricelevel' ORDER BY levelquantity DESC");
		  if (@mysqli_num_rows($qtypricingresult)) {
			  if ($productrow["qtytype"] == "1" || $productrow["qtytype"] == "2") {
				  $previouslevel = "";
				  $price = "<table width=\"200\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\">";
				  $qtylevel = 0;
				  while ($qtyrow = @mysqli_fetch_array($qtypricingresult)) {
					  $levelprice = $qtyrow["levelprice"];
					  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
						  $tempcurrency = $ashopcurrency;
						  $ashopcurrency = $curr;
						  $tempproductprice = $levelprice;
						  $levelprice = $levelprice*$crate;
					  }
					  $levelqty =  $qtyrow["levelquantity"]+1;
					  if (!$previouslevel) $leveldescription = "> {$qtyrow["levelquantity"]}";
					  else $leveldescription = "$levelqty - $previouslevel";
					  $qtyprices[$qtylevel] = "<tr><td class=\"ashopproducttext\" align=\"left\">$leveldescription ".ITEMS."</td><td class=\"ashopproducttext\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"].number_format($levelprice,2,'.','').$currencysymbols[$ashopcurrency]["post"]." ".PERITEM."</td></tr>";
					  $previouslevel = $levelqty-1;
					  // Convert back to main currency...
					  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
						  $ashopcurrency = $tempcurrency;
						  $levelprice = $tempproductprice;
					  }
					  $qtylevel++;
				  }
				  krsort($qtyprices);
				  foreach ($qtyprices as $thisqtyprice) $price .= $thisqtyprice;
				  $price .= "</table>";
			  } else $price = $productrow["pricetext"];
		  }
	  }
	  // Convert back to main currency...
	  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		  $ashopcurrency = $tempcurrency;
		  $productprice = $tempproductprice;
	  }

	  // Check inventory status...
	  if ($productrow["useinventory"]) {
		  if ($productrow["inventory"]<1) $avail = "<span id=\"inventory\"><span class=\"ashopproductoutofstock\">".OUTOFSTOCK." </span></span>";
		  else if ($productrow["inventory"] < $productrow["lowlimit"]) $avail = "<span id=\"inventory\"><span class=\"ashopproductlowstock\">".LOWSTOCK." ({$productrow["inventory"]}) </span></span>";
		  else $avail = "<span id=\"inventory\"><span class=\"ashopproductinstock\">".INSTOCK." </span></span>";
	  } else $avail = "";
	  $filetypes = "";
	  $filesize = 0;
	  $totalfilesize = 0;
	  $previousfiletypes = "";
	  $attributefiles = 0; 
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

	  // Get product attributes...
	  if ($copyof) $parameterresult = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$copyof' ORDER BY parameterid");
	  else $parameterresult = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid");
	  $numberofparams = @mysqli_num_rows($parameterresult);
	  
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
			  $xspfcode = "
			  <a href=\"$previewurl\" style=\"display:block;width:310px;height:230px\" id=\"player$productid\"> </a> 
			  <script type=\"text/javascript\">flowplayer(\"player$productid\", \"includes/flowplayer-3.2.7.swf\", { clip: { autoPlay: false, autoBuffering: true } });</script>";			  
		  } else $xspfcode = "";
	  }

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
			  if ($auctiontype == "standard" && !empty($_COOKIE["customersessionid"])) $pricehtml .= "<div class=\"ashopbidbutton\" id=\"bidbutton{$productid}\"$hidestring><a href=\"bidregister.php?productid=$productid\"><img src=\"{$buttonpath}images/bid-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".PLACEBID."\" align=\"top\" /></a></div>";
			  else if (!$endprice && $activatebidding && $auctiontype == "penny") $pricehtml .= "<div class=\"ashopbidbutton\" id=\"bidbutton{$productid}\"$hidestring><input type=\"image\" src=\"{$buttonpath}images/bid-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".PLACEBID."\" align=\"top\" onclick=\"placebid($productid,$thisbidder); return false;\" /></div>";
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

	if ($device == "mobile") echo "<ul id=\"product-list\" data-role=\"listview\" data-theme=\"d\" data-inset=\"true\"><li><h3 style=\"white-space: normal;\">$productname</h3>";
	echo "<table class=\"ashopitemsframe\" cellspacing=\"{$cellspacing}\">";

		  $thumbnailshown = FALSE;

		  // Get product image info...
		  $productimage = ashop_productimages($productid);

		  echo "
		  <tr>
		  <td width=\"100%\" class=\"ashopitembox\">";
		  if ($device != "mobile") echo "
		  <table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"100%\"><tr>";
		  if ($productimage["product"]) {
			  $imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["product"]}");
			  if ($imagesize[0] == $imagewidth) {
				  $imagesizestring = $imagesize[3];
				  $imageheight = $imagesize[1];
			  } else {
				  $imagesizeratio = $imagewidth/$imagesize[0];
				  $imageheight = $imagesize[1]*$imagesizeratio;
				  $imagesizestring = "width=\"$imagewidth\" height=\"$imageheight\"";
			  }
			  if ($device != "mobile") echo "
			  <td align=\"center\" valign=\"top\" style=\"width:{$imagewidth}px;\">";
			  if ($productimage["main"] && $device != "mobile" && $keeplargeprodimg == "true") echo "
			  <a id=\"productimagelink\" href=\"prodimg/$productid/{$productimage["main"]}\" class=\"zoombox\"><img id=\"productimage\" class=\"ashopproductimage\" src=\"prodimg/$productid/{$productimage["product"]}\" alt=\"$safeproductname\" $imagesizestring border=\"0\" /></a>";
			  else echo "<img id=\"productimage\" src=\"prodimg/$productid/{$productimage["product"]}\" alt=\"$safeproductname\" $imagesizestring />";
			  $thumbnailshown = TRUE;
			  $mainpictureformat = "gif";
		  } else if (file_exists("$ashoppath/images/noimage.gif")) {
			  $imagesize = getimagesize("$ashoppath/images/noimage.gif");
			  $imagewidth = $imagesize[0];
			  $imageheight = $imagesize[1];
			  $imagesizestring = "width=\"$imagewidth\" height=\"$imageheight\"";
			  if ($device != "mobile") echo "
			  <td align=\"center\" valign=\"top\"  style=\"width:{$imagewidth}px;\">";
			  echo "
			  <img id=\"productimage\" src=\"images/noimage.gif\" alt=\"$safeproductname\" $imagesizestring />";
			  $thumbnailshown = TRUE;
		  }
		  if ($thumbnailshown == TRUE && $productimage["additionalimages"]) {
			  $thisproductimage = ashop_productimages($productid,1);
			  if ($productimage["mini"] && $productimage["product"]) {
				  $miniimagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["mini"]}");
				  echo "<table class=\"ashoppictureselector\" style=\"width: {$imagewidth}px;\"><tr><td width=\"45\" align=\"center\"><a href=\"javascript: changeimage(";
				  if ($productimage["main"]) echo "'$productid/{$productimage["main"]}'";
				  else echo "''";
				  echo ",'prodimg/$productid/{$productimage["product"]}',{$imageheight});\"><img src=\"prodimg/$productid/{$productimage["mini"]}\" {$miniimagesize[3]} border=\"0\" alt=\"$safeproductname\" /></a></td>";
				  $numberofpictures = floor($imagewidth/45);
				  for ($picturenumber = 1; $picturenumber <= $numberofpictures-1; $picturenumber++) {
					  $thisproductimage = ashop_productimages($productid,$picturenumber);
					  echo "<td width=\"45\" align=\"center\">";
					  if ($thisproductimage["mini"] && $thisproductimage["product"]) {
						  $miniimagesize = getimagesize("$ashoppath/prodimg/$productid/$picturenumber/{$thisproductimage["mini"]}");
						  $fullimagesize = getimagesize("$ashoppath/prodimg/$productid/$picturenumber/{$thisproductimage["product"]}");
						  if ($thisproductimage["main"]) echo "<a href=\"javascript: changeimage('$productid/$picturenumber/{$thisproductimage["main"]}'";
						  else echo "''";
						  echo ",'prodimg/$productid/$picturenumber/{$thisproductimage["product"]}',{$fullimagesize[1]});\"><img src=\"prodimg/$productid/$picturenumber/{$thisproductimage["mini"]}\" {$miniimagesize[3]} border=\"0\" alt=\"$safeproductname\" /></a>";
					  } else echo "&nbsp;";
					  echo "</td>";
				  }
				  echo "</tr></table>";
				  if (file_exists("$ashoppath/prodimg/{$productid}_{$picturenumber}.gif") || file_exists("$ashoppath/prodimg/{$productid}_{$picturenumber}.jpg")) echo "<span class=\"ashopproductsmalltext\"><a class=\"gallery\" href=\"gallery.php?productid=$productid\">".MOREIMAGES."</a></span>";
			  }
			  if ($device != "mobile") echo "</td>\n";

		  }
		  if ($device != "mobile") {
			  echo	"<td valign=\"top\" width=\"100%\">";
			  echo "<table class=\"ashoppageheader\" cellpadding=\"7\" cellspacing=\"0\" style=\"padding: 0;\"><tr><td valign=\"top\"><h1 class=\"ashoppageheadertext1\">$productname</h1>";
			  if ($activatefloatingprice && $thisscreenname) echo "</td></tr><tr><td align=\"right\" class=\"ashoppageheadertext2\" valign=\"top\">$thisscreenname: <span id=\"bidsinfo\">$thisbids</span> ".BIDSLEFT;
			  echo "</td></tr>";
			  if (!empty($_COOKIE["affiliatesesid"])) {
				  if (!in_array($product, $affexcludeproducts)) echo "<tr><td><span class=\"ashopproducttext\"><a href=\"affiliate/affiliate.php?product=$product\">Hide this product</a></span></td></tr>";
				  else echo "<tr><td><span class=\"ashopproducttext\"><a href=\"affiliate/affiliate.php?product=$product\">Show this product</a></span></td></tr>";
			  }
			  echo "</table>";
		  }
		  if ($shoppingcart > "0" && $shoppingcart < "3") {
			  if ($licensetext) echo "
			  <form name=\"product$productid\" action=\"\" method=\"post\" onsubmit=\"if (checkLicense(this)) return buyitem('$buyproductid', product$productid.quantity.value); else return false;\" data-ajax=\"false\">";
			  else echo "
			  <form name=\"product$productid\" action=\"\" method=\"post\" onsubmit=\"return buyitem('$buyproductid', product$productid.quantity.value);\" data-ajax=\"false\">";
		  } else if ($shoppingcart == "0") {
			  if (!empty($gcoid)) ashop_googlecheckoutbutton($db, "1b{$productid}a", $gcoid, $gcokey, $gcotest, 1, 1);
		  }
		  echo "
		  <table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"100%\"><tr><td>";
		  if($enablecustomerlogin && !empty($_COOKIE["customersessionid"])) {
			  echo " - <a href=\"javascript: addToWishlist('$buyproductid', $windowheight, ";
			  if ($numberofparams) echo "1";
			  else echo "0";
			  echo ")\"><span class=\"ashopproductwishlist\">".ADDTOWISHLIST."</span></a>";
		  }
		  if (!$attributefiles && $files && $producttype != "subscription" && !file_exists("$ashoppath/includes/aws/aws-config.php")) {
				echo " <span class=\"ashopproductinfo\">".DIRECTDOWNLOAD;
				if ($showfileinfo) echo " ($filetypes$filesize)";
				echo "</span>";
		  } else if (($subscriptiondir && $producttype == "subscription") || file_exists("$ashoppath/includes/aws/aws-config.php")) echo " <span class=\"ashopproductinfo\">".INSTANTACCESS."</span>";
		  else if ($unlockkeys) echo " <span class=\"ashopproductinfo\">".EMAILDELIVERY."</span>";
		  if ($device == "mobile") {
			  $description = str_replace("<p>","<br />",$description);
			  $description = str_replace("</p>","<br />",$description);
			  $description = str_replace("<div>","",$description);
			  $description = str_replace("</div>","<br />",$description);
			  $description = strip_tags($description, '<a><b><i><u><br>');
		  }
		  echo "</td></tr>
            <tr><td><span class=\"ashopproducttext\">$description</span></td></tr>";
		  if ($skucode) {
			  if ($device == "mobile") echo "<tr><td>".SKU.": <span id=\"sku\">$skucode</span></td></tr>";
			  else echo "<tr><td><div class=\"ashopproductlabel\">".SKU.":</div><span class=\"ashopproducttext\"> <span id=\"sku\">$skucode</span></span></td></tr>";
		  }

		  echo "<tr><td>";
		  if ($hideprice) { }
		  else if ($pricehtml) echo $pricehtml;
		  else {
			  if ($device == "mobile") echo PRICE.": ";
			  else echo "<div class=\"ashopproductlabel\" style=\"padding-top: 4px;\">".PRICE.":</div><span class=\"ashopproducttext\">";
			  if ($regprice) echo " ".$regprice."<span class=\"ashopproductsale\">";
			  if ($device == "mobile") echo " <span id=\"pricetag\">$price</span>";
			  else echo " <span class=\"ashopproductprice\" id=\"pricetag\">$price</span></span>";
			  if ($regprice) echo "</span>";
		  }
		  if ($avail) echo ", $avail";
		  echo "</td></tr>";

		  // Check attribute specific inventory...
		  $activateattribinv = FALSE;
		  if ($productrow["useinventory"]) $attributeinventory = @mysqli_query($db, "SELECT type, skucode, inventory FROM productinventory WHERE productid='$productid'");
		  if ($productrow["useinventory"] && @mysqli_num_rows($attributeinventory)) {
			  $activateattribinv = TRUE;
			  echo "<script type=\"text/javascript\">\n\tfunction checkinventory() {\n\t\tvar attribinv = true;\n\t\t";
			  while ($row = @mysqli_fetch_array($attributeinventory)) {
				  $attributetypearray = explode("|",$row["type"]);
				  $attribinv = $row["inventory"];
				  $attribsku = $row["skucode"];
				  echo "if (";
				  foreach ($attributetypearray as $parameternumber=>$parameterid) {
					  echo "document.getElementById('parameter$parameternumber').value == '$parameterid' && ";
				  }
				  echo "attribinv == true) {\n\t\t\tdocument.getElementById('inventory').innerHTML = ";
				  if ($attribinv < 1) echo "'<span class=\"ashopproductoutofstock\">".OUTOFSTOCK."</span>';\n";
				  else if ($attribinv < $productrow["lowlimit"]) echo "'<span class=\"ashopproductlowstock\">".LOWSTOCK." ({$productrow["inventory"]}) </span>';\n";
				  else echo "'<span class=\"ashopproductinstock\">".INSTOCK." </span>';\n";
				  if (!empty($attribsku)) echo "\t\t\tdocument.getElementById('sku').innerHTML = '$attribsku';\n";
				  echo "}\n";
			  }
			  echo "}\n</script>";
		  }

		  // Generate attribute selectors...
		  $parameternumber = 0;
		  if ($numberofparams) while ($paramrow = @mysqli_fetch_array($parameterresult)) {
			  $parameterid = $paramrow["parameterid"];
			  $parameterids[] = $parameterid;
			  $caption = $paramrow["caption"];
			  $inputrows = $paramrow["inputrows"];
			  $paramvalueresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='$parameterid' ORDER BY valueid");
			  if (@mysqli_num_rows($paramvalueresult) > 1) {
				  echo "<tr><td>";
				  if ($device == "mobile") echo "<div data-role=\"fieldcontain\" style=\"margin-top: 0; margin-bottom: 0;\"><label for=\"parameter$parameterid\">$caption:</label>";
				  else echo "<div class=\"ashopproductlabel\" style=\"padding-top: 3px;\">$caption:&nbsp; </div>";
				  echo "<select name=\"parameter$parameterid\" id=\"parameter$parameternumber\"";
				  if ($paramrow["buybuttons"]) {
					  $attributeprice = array();
					  echo " onchange=\"$('pricetag').update(attributeprice[document.getElementsByName('parameter$parameterid')[0].value]);";
					  if ($activateattribinv) echo " checkinventory();";
					  echo "\"";
				  } else if ($activateattribinv) echo " onchange=\"checkinventory();\"";
				  echo ">";
				  $valuecount = 0;
				  while ($valuerow = @mysqli_fetch_array($paramvalueresult)) {
					  $valueid = $valuerow["valueid"];
					  $value = $valuerow["value"];
					  $attributeprices = $valuerow["price"];
					  if ($attributeprices && !$productrow["qtytype"]) {
						  $thisparameterprices = explode("|",$attributeprices);
						  $thisattributeprice = $thisparameterprices[$pricelevel];
						  // Convert currency...
						  if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
							  $tempcurrency = $ashopcurrency;
							  $ashopcurrency = $curr;
							  $thisattributeprice = $thisattributeprice*$crate;
						  }
						  $thisattributeprice = $currencysymbols[$ashopcurrency]["pre"].number_format($thisattributeprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
						  if ($valuecount == 0) $price = $thisattributeprice;
						  $valuecount++;
						  $attributeprice[$valueid] = $thisattributeprice;
						  // Convert back to main currency...
						  if (!empty($curr) && !empty($crate) && is_numeric($crate)) $ashopcurrency = $tempcurrency;
					  }					  
					  echo "<option value=\"$valueid\">$value</option>";
				  }
				  if ($paramrow["buybuttons"]) {
					  echo "<script type=\"text/javascript\">\n\t var attributeprice = new Object();\n";
					  $attributepricecounter = 1;
					  $defaultattributeprice = $price;
					  foreach ($attributeprice as $valueid=>$thisattributeprice) {
						  if ($attributepricecounter == 1) $defaultattributeprice = $thisattributeprice;
						  echo "attributeprice[\"$valueid\"] = \"$thisattributeprice\";\n";
						  $attributepricecounter++;
					  }
					  echo "$('pricetag').update('$defaultattributeprice');\n</script>";
				  }
				  if ($device == "mobile") echo "</div>";
				  echo "</td></tr>";
			  } else if (@mysqli_num_rows($paramvalueresult) == 1) {
				  $valueid = @mysqli_result($subresult, 0, "valueid");
				  echo "<input name=\"parameter$parameterid\" type=\"hidden\" value=\"$valueid\">";
			  } else {
				  if ($inputrows <= 1) echo "<tr><td><div class=\"ashopproductlabel\" style=\"padding-top: 3px;\">$caption:&nbsp; </div><input type=\"text\" size=\"30\" name=\"parameter$parameterid\"></td></tr>";
				  else echo "<tr><td><div class=\"ashopproductlabel\" style=\"padding-top: 3px;\">$caption:&nbsp; </div><textarea name=\"parameter$parameterid\" cols=\"25\" rows=\"$inputrows\"></textarea></td></tr>";
			  }
			  $parameternumber++;
		  }
		  echo "<tr><td>";
		  if ($numberofparams) {
			  echo "
			  <script type=\"text/javascript\">\n\t var productattributes = new Array(";
			  foreach ($parameterids as $parameternumber=>$parameterid) {
				  echo "\"$parameterid\"";
				  if ($parameternumber < count($parameterids)-1) echo ", ";
			  }
			  echo ");\n";
			  if ($activateattribinv) echo "checkinventory();\n";
			  echo "</script>";
		  }
		  echo "
		  </td></tr>";

		  if ($previewurl) {
			  if ($xspfcode) echo "<tr><td>$xspfcode</td></tr>";
			  else echo "<tr><td><span class=\"ashopproducttext\"><a href=\"$previewurl\" target=\"_blank\">".DOWNLOADDEMO."</a></span></td></tr>";
		  }
		  echo "<tr><td>";
		  if ($shoppingcart > "0" && $shoppingcart < "3") {
			  if ($producttype == "subscription" || $shoppingcart == "2" || $pricehtml) echo "<input type=\"hidden\" name=\"quantity\" value=\"1\" /> ";
			  else {
				  if ($device == "mobile") {
					  echo "<div data-role=\"fieldcontain\" style=\"margin-top: 0; margin-bottom: 0;\"><label for=\"quantity$productid\">".QUANTITY.": </label>";
					  if (!empty($qtytlimit)) {
						  echo "<select class=\"ashopquantityselect\" id=\"quantity$productid\" name=\"quantity\">\n";
						  for ($qty = 1; $qty <= $qtytlimit; $qty++) echo "<option value=\"$qty\">$qty</option>\n";
						  echo "</select>\n";
					  } else echo "<input class=\"ashopquantityfield\" type=\"text\" id=\"quantity$productid\" name=\"quantity\" size=\"2\" value=\"1\" />";
					  echo "</div>";
				  } else {
					  echo "<div class=\"ashopproductlabel\" style=\"padding-top: 8px; font-weight: normal;\">".QUANTITY.": </div>";
					  if (!empty($qtytlimit)) {
						  echo "<select class=\"ashopquantityselect\" id=\"quantity$productid\" name=\"quantity\">\n";
						  for ($qty = 1; $qty <= $qtytlimit; $qty++) echo "<option value=\"$qty\">$qty</option>\n";
						  echo "</select>\n";
					  } else echo "<input class=\"ashopquantityfield\" type=\"text\" name=\"quantity\" size=\"2\" value=\"1\" /> ";
				  }
			  }
			  echo "<input type=\"hidden\" name=\"attribute\" value=\"0\" />";
			  if ($licensetext) echo "<div class=\"ashopproductsmalltext\" style=\"margin-top: 4px; margin-bottom: 4px;\"><input type=\"checkbox\" name=\"acceptlicense\"> ".AGREE1." <a href=\"javascript:showlicense($productid)\">".AGREE2."</a> ".AGREE3." $productname.</div>";
			  if (!$pricehtml) {
				  if ($shoppingcart != "2") echo "&nbsp;";
			  } else {
				  echo "<div id=\"buybutton{$productid}\"";
				  if (!$endprice) echo " style=\"display: none;\"";
				  echo ">";
			  }
			  if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" id=\"addtocart$productid\" name=\"buy\" value=\"".ADDTOCART."\" />";
			  else echo "<input type=\"image\" src=\"{$buttonpath}images/buysp-$lang.png\" class=\"ashopbutton\" id=\"addtocart$productid\" name=\"buy\" alt=\"".ADDTOCART."\" style=\"vertical-align: text-bottom; border: none;\" />";
			  if ($pricehtml) echo "</div>";
			  echo "</td></tr></table></form>\n";
		  } else if ($shoppingcart == "0") {
			  if (empty($gcoid)) {
			  echo "
			  <form name=\"product$productid\" action=\"buy.php\" method=\"post\"";
			  if ($licensetext) echo " onsubmit=\"return checkLicense(this)";
			  echo ">
			  <input type=\"hidden\" name=\"item\" value=\"$productid\" />
			  <input type=\"hidden\" name=\"quantity\" value=\"1\" />
			  <input type=\"hidden\" name=\"cat\" value=\"$cat\" />
			  <input type=\"hidden\" name=\"shop\" value=\"$shop\" />
			  <input type=\"hidden\" name=\"attribute\" value=\"\" />
			  <input type=\"hidden\" name=\"redirect\" value=\"checkout.php\" />";
			  if ($salediscount) echo "<input type=\"hidden\" name=\"discount\" value=\"$discountcode\" />";
			  if ($licensetext) echo "<span class=\"ashopproductsmalltext\"><input type=\"checkbox\" name=\"acceptlicense\"> ".AGREE1." <a href=\"javascript:showlicense($productid)\">".AGREE2."</a> ".AGREE3." $productname.</span><br /><br />";
			  echo "<input type=\"image\" border=\"0\" src=\"{$buttonpath}images/buy";
			  if ($files && $producttype != "subscription") echo "d";
			  echo "-$lang.png\" class=\"ashopbutton\" name=\"buy\" alt=\"".BUY."\" />
			  </td></tr></table></form>";
			  }
		  }
	echo "</td></tr>";
	if (!empty($longdescription) || $activatesocialnetworking == 1 || $activatereviews == 1) {
		  if (!isset($_SERVER['REQUEST_URI']) and isset($_SERVER['SCRIPT_NAME'])) {
			  $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
			  if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		  }
		  if ($_SERVER['HTTPS'] == "on") $shareurl = "https://";
		  else $shareurl = "http://";
		  $shareurl .= $HTTP_HOST.$REQUEST_URI;
		  $shareurl = urlencode($shareurl);
		  $shareproductname = strip_tags($productname);
		  $sharetopic = urlencode($shareproductname);
		  $tweettopic = substr($shareproductname,0,120);
		  $facebookshareurl = "http://www.facebook.com/sharer.php?u=$shareurl&amp;t=$shareproductname&amp;src=sp";
		  $twittershareurl = "http://twitter.com/share?text=$tweettopic&amp;url=$shareurl";
		  $myspaceshareurl = "http://www.myspace.com/Modules/PostTo/Pages/?u=$shareurl";
		  
		  echo "
		  <tr><td colspan=\"2\">";
		  if ($device == "mobile") echo "<div data-role=\"collapsible-set\" data-theme=\"c\" data-content-theme=\"d\">
		  ";
		  else {
			  echo "
			  <div id=\"productdetails\">
			  <ul>
			  <li><a href=\"$url#first-tab\"><span>".PRODUCTDETAILS."</span></a></li>";
			  if ($activatesocialnetworking == 1) echo "
			  <li><a href=\"$url#second-tab\"><span>".TELLAFRIEND."</span></a></li>";
			  if ($activatereviews == 1) echo "
			  <li><a href=\"$url#third-tab\"><span>".REVIEWS."</span></a></li>";
			  echo "
			  </ul>";
		  }
		  if ($device == "mobile") {
			  $longdescription = str_replace("<p>","",$longdescription);
			  $longdescription = str_replace("</p>","<br /><br />",$longdescription);
			  $longdescription = str_replace("<h1>","<strong>",$longdescription);
			  $longdescription = str_replace("<h2>","<strong>",$longdescription);
			  $longdescription = str_replace("<h3>","<strong>",$longdescription);
			  $longdescription = str_replace("<h4>","<strong>",$longdescription);
			  $longdescription = str_replace("</h1>","</strong>",$longdescription);
			  $longdescription = str_replace("</h2>","</strong>",$longdescription);
			  $longdescription = str_replace("</h3>","</strong>",$longdescription);
			  $longdescription = str_replace("</h4>","</strong>",$longdescription);
			  $longdescription = strip_tags($longdescription, '<a><b><i><u><br><strong>');
			  echo "
			  <div data-role=\"collapsible\">
			  <h3>".PRODUCTDETAILS."</h3>
			  $longdescription
			  </div>";
		  } else echo "
		  <div id=\"first-tab\" class=\"ashopproducttext\">
		  $longdescription
		  </div>";
		  if ($activatesocialnetworking == 1) {
			  if ($device == "mobile") echo "
			  <div data-role=\"collapsible\">
			  <h3>".TELLAFRIEND."</h3>";
			  else echo "
			  <div id=\"second-tab\">";
			  echo "
			  Email:
			  <input type=\"text\" id=\"friendmailer\" class=\"ashoptellafriendfield\" size=\"35\" />";
			  if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" value=\"".SUBMIT."\" onclick=\"tellafriend();\" />";
			  else echo "<input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" alt=\"".SUBMIT."\" onclick=\"tellafriend();\" style=\"vertical-align: text-bottom; border: none;\" />";
			  echo " <a href=\"$facebookshareurl\" target=\"_blank\"><img src=\"images/facebook.png\" style=\"vertical-align: text-bottom; border: none;\" alt=\"Facebook\" /></a> <a href=\"$twittershareurl\" target=\"_blank\"><img src=\"images/twitter.png\" style=\"vertical-align: text-bottom; border: none;\" alt=\"Twitter\" /></a> <a href=\"$myspaceshareurl\" target=\"_blank\"><img src=\"images/myspace.png\" style=\"vertical-align: text-bottom; border: none;\" alt=\"MySpace\" /></a>";
			  echo "
			  </div>";
		  }
		  if ($activatereviews == 1) {
			  if ($device == "mobile") echo "
			  <div data-role=\"collapsible\">
			  <h3>".REVIEWS."</h3>";
			  else echo "
			  <div id=\"third-tab\">";
			  // Get customer ratings and reviews...
			  $reviewsresult = @mysqli_query($db, "SELECT * FROM reviews WHERE productid='$productid' ORDER BY reviewid DESC");
			  $numberofreviews = @mysqli_num_rows($reviewsresult);
			  $rating = 0;
			  if($numberofreviews != 0)	{
				  echo "<div class=\"ashopproducttabheader\">".PRODUCTRATING."</div>";
				  while($reviewrow = @mysqli_fetch_array($reviewsresult)) $rating += $reviewrow["rating"];
				  $avgrating = round($rating/$numberofreviews);
				  echo "<p>";
				  if($avgrating == 5) echo "<img src='images/star5.gif' alt='5'>";
				  else if($avgrating == 4) echo "<img src='images/star4.gif' alt='4'>";
				  else if($avgrating == 3) echo "<img src='images/star3.gif' alt='3'>";
				  else if($avgrating == 2) echo "<img src='images/star2.gif' alt='2'>";
				  else if($avgrating == 1) echo "<img src='images/star1.gif' alt='1'>";
				  else if($avgrating == 0) echo "<img src='images/star0.gif' alt='0'>";
				  echo "<br>$numberofreviews ".NUMBEROFREVIEWS."</p>";
			  }
			  
			  echo "<br /><div class=\"ashopproducttabheader\">".CUSTOMERREVIEWS."</div>";

			  @mysqli_data_seek($reviewsresult, 0);

			  while($reviewrow = @mysqli_fetch_array($reviewsresult)) {
				  // Figures out which star rating image to print...
				  if($reviewrow["rating"] == 5) $rating = "<img src='images/star5.gif' alt='5'>";
				  else if($reviewrow["rating"] == 4) $rating = "<img src='images/star4.gif' alt='4'>";
				  else if($reviewrow["rating"] == 3) $rating = "<img src='images/star3.gif' alt='3'>";
				  else if($reviewrow["rating"] == 2) $rating = "<img src='images/star2.gif' alt='2'>";
				  else if($reviewrow["rating"] == 1) $rating = "<img src='images/star1.gif' alt='1'>";
				  else if($reviewrow["rating"] == 0) $rating = "<img src='images/star0.gif' alt='0'>";
				  
				  // Get the name of the customer who posted the review...
				  $reviewcustomerid = $reviewrow["customerid"];
				  $reviewcustomerresult = @mysqli_query($db, "SELECT firstname,lastname FROM customer WHERE customerid='$reviewcustomerid'");
				  $reviewcustomerrow = @mysqli_fetch_array($reviewcustomerresult);
				  $reviewcustomer = $reviewcustomerrow["firstname"]." ".$reviewcustomerrow["lastname"];
				  echo "<div class=\"ashopproducttabreview\">
				  ".$rating." ".$reviewrow["title"]." ".THEWORDBY." ".$reviewcustomer."</p>";
				  
				  // Prints pros, cons, and other thoughts fields
				  if($reviewrow["comment"]) echo "<p class='style2'>".$reviewrow["comment"]."</p>";
				  echo "</div>";
			  }
			  
			  echo "<br /><br /><div class=\"ashopproducttabheader\">".ADDYOURREVIEWHERE."</div>
			  ";
			  
			  if (empty($_COOKIE["customersessionid"])) echo "<p>".YOUHAVETOBELOGGEDINTOREVIEW."</p>";
			  else {
				  echo "<br /><form name=\"review\" method=\"post\" action=\"\"";
				  if ($device == "mobile") echo " data-ajax=\"false\"";
				  echo ">
			  <table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
			  <tr><td align=\"left\">".RATING.": </td>
			  <td align=\"left\"><script language=\"JavaScript\" type=\"text/javascript\">loadStars();</script>
			  <img src=\"images/staroff.gif\" onmouseover=\"highlight(this.id)\" onclick=\"setStar(this.id)\" onmouseout=\"losehighlight(this.id)\" id=\"1\" style=\"width:12px; height:12px; float:left;\" />
			  <img src=\"images/staroff.gif\" onmouseover=\"highlight(this.id)\" onclick=\"setStar(this.id)\" onmouseout=\"losehighlight(this.id)\" id=\"2\" style=\"width:12px; height:12px; float:left;\" />
			  <img src=\"images/staroff.gif\" onmouseover=\"highlight(this.id)\" onclick=\"setStar(this.id)\" onmouseout=\"losehighlight(this.id)\" id=\"3\" style=\"width:12px; height:12px; float:left;\" />
			  <img src=\"images/staroff.gif\" onmouseover=\"highlight(this.id)\" onclick=\"setStar(this.id)\" onmouseout=\"losehighlight(this.id)\" id=\"4\" style=\"width:12px; height:12px; float:left;\" />
			  <img src=\"images/staroff.gif\" onmouseover=\"highlight(this.id)\" onclick=\"setStar(this.id)\" onmouseout=\"losehighlight(this.id)\" id=\"5\" style=\"width:12px; height:12px; float:left;\" />
			  <div id=\"vote\" style=\"font-family:arial; color:red;\"></div>
			  <input type=\"hidden\" name=\"rating\">
			  <input type=\"hidden\" name=\"addreview\" value=\"true\">
			  <input type=\"hidden\" name=\"productid\" value=\"$productid\"></td></tr>
			  <tr><td align=\"left\" valign=\"top\"><br />".COMMENT.": </td>
			  <td align=\"left\"><textarea name=\"comment\" cols=\"57\" rows=\"6\"></textarea></td></tr>
			  <tr><td>&nbsp;</td><td align=\"left\">";
				  if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" value=\"".SUBMITREVIEW."\" />";
				  else echo "<input type=\"image\" src=\"images/submit-$lang.png\" class=\"ashopbutton\" alt=\"".SUBMITREVIEW."\" />";
				  echo "</td></tr>
				  </table></form>";
			  }

			  echo"
			  </div>";
		  }
		  echo "
		  </div>		  
		  </td></tr>";
	}
	echo "
	</table>";
	if ($device != "mobile") echo "</td></tr></table>";
	else echo "</li></ul>";
}

// Close database...
if (substr($_SERVER['PHP_SELF'],-11) == "product.php") @mysqli_close($db);

// Print error messages...

if ($error) {
	echo "<span class=\"ashopalert\">".ERROR1."<br />
         ".ERROR2." ";
	if ($error==1) echo ERROR3."</span>";
	else if ($error==2) echo ERROR4."</span>";
}
echo "
</td></tr></table>";

// Print footer using template...
if ($categories != "off") {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
}
?>