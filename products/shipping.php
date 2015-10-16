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
if ($mode != "include") session_start();
include "admin/config.inc.php";
if (!function_exists("ashop_shippingform")) include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";
include "counter.php";

// Initialize variables...
if (!isset($cat)) $cat = 0;
if (!isset($exp)) $exp = 0;
if (!empty($_COOKIE["shop"])) $shop = $_COOKIE["shop"];
if (isset($shop) && !is_numeric($shop)) $shop = 1;
if (!isset($lang)) $lang = "";
if (!isset($usethemebuttons)) $usethemebuttons = "";
if (!isset($usethemetemplates)) $usethemetemplates = "";
if (!isset($returnurl)) $returnurl = "";
if (!isset($quote)) $quote = "";
if (!isset($sid)) $sid = "";

// Validate variables...
if (isset($action) && $action != "basket" && $action != "checkout") $action = "";
if (!is_numeric($cat)) $cat = 0;
$checkexp = str_replace("|","",$exp);
if (!is_numeric($checkexp)) $exp = 0;
$basket = urldecode($basket);
$basket = html_entity_decode($basket);
$basket = str_replace("<","",$basket);
$basket = str_replace(">","",$basket);
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Combine the same products in the basket cookie...
if (!empty($basket)) $basket = ashop_combineproducts($basket);

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$customerrow = @mysqli_fetch_array($customerresult);
	$pricelevel = $customerrow["level"];
	if ($pricelevel > 0) {
		$wholesale = md5("ashopisgreat");
		$displaywithtax = $displaywswithtax;
	}
} else {
	$pricelevel = 0;
	$wholesale = "";
}

// Check if the script was called from the wholesale catalog...
if (!empty($wholesale) && $wholesale == md5("ashopisgreat")) {
	$customerid = $customerrow["customerid"];
	$shippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customerid'");
	if (!@mysqli_num_rows($shippingresult)) {
		@mysqli_query($db, "INSERT INTO shipping (shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingzip, shippingcity, shippingstate, shippingcountry, shippingphone, shippingemail, sameasbilling, customerid) VALUES ('{$customerrow["businessname"]}', '{$customerrow["firstname"]}', '{$customerrow["lastname"]}', '{$customerrow["address"]}', '{$customerrow["zip"]}', '{$customerrow["city"]}', '{$customerrow["state"]}', '{$customerrow["country"]}', '{$customerrow["phone"]}', '{$customerrow["email"]}', '1', '$customerid')");
		$shipid = @mysqli_insert_id($db);
	} else $shipid = @mysqli_result($shippingresult, 0, "shippingid");
	if (!empty($shipid) && $mode != "include") {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("shipid","$shipid");
	}
} else if (!empty($customerrow)) {
	$customerid = $customerrow["customerid"];
	$shippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customerid'");
	if (@mysqli_num_rows($shippingresult)) $shipid = @mysqli_result($shippingresult, 0, "shippingid");
	if (!empty($shipid) && $mode != "include") {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("shipid","$shipid");
	} else {
		$shippingfirstname = $customerrow["firstname"];
		$shippinglastname = $customerrow["lastname"];
		$shippingemail = $customerrow["email"];
		$shippingaddress = $customerrow["address"];
		$shippingcity = $customerrow["city"];
		$shippingzip = $customerrow["zip"];
		$shippingcountry = $customerrow["country"];
		$shippingstate = $customerrow["state"];
		$shippingphone = $customerrow["phone"];
	}
	if (!$Submit_x && $action == "checkout") {
		if (empty($shipid)) $changeshipping = "true";
		else {
			$checkshippingaddress = @mysqli_result($shippingresult,0,"shippingaddress");
			$checkshippingzip = @mysqli_result($shippingresult,0,"shippingzip");
			$checkshippingcountry = @mysqli_result($shippingresult,0,"shippingcountry");
			$checkshippingcity = @mysqli_result($shippingresult,0,"shippingcity");
			if (empty($checkshippingcity) || empty($checkshippingcountry) || empty($checkshippingzip) || empty($checkshippingaddress)) $changeifshipping = "true";
			if (empty($checkshippingcountry) || empty($checkshippingzip)) $changeiftax = "true";
		}
	}
}

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
include "language/$lang/shipping.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Convert multiple origin countries to an array...
$shipfromcountries = explode("-", $shipfromcountry);

if ($localshipping) {
	if (in_array("US",$shipfromcountries)) {
		if (!in_array("CA",$shipfromcountries)) $statesandprovinces = $americanstates;
		else $statesandprovinces = $uscanstates;
	} else if (in_array("CA",$shipfromcountries)) $statesandprovinces = $canprovinces;
}

// Remove back button fix...
if (isset($_COOKIE["fixbackbutton"])) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("fixbackbutton","");
}

// Convert eMerchant $quote to $basket...
if ($quote) $basket = $quote;

// Make sure the HTTP_REFERER variable is set...
$HTTP_REFERER = $_SERVER["HTTP_REFERER"];
if ($returnurl) $HTTP_REFERER = $returnurl;
else if (empty($HTTP_REFERER)) $HTTP_REFERER = "$ashopurl/shipping.php";

// Make sure the return URL is set if needed...
if (!$returnurl && !strstr($HTTP_REFERER, "index.php") && !strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php") && !strstr($HTTP_REFERER, "discount.php")) $returnurl = $HTTP_REFERER;
$returnurl = str_replace("&","|",$returnurl);
if (ini_get('magic_quotes_gpc')) $returnurl = stripslashes($returnurl);

// Use relative paths in return URL...
$returnurl = str_replace("$ashopurl/","",$returnurl);
$returnurl = str_replace("$ashopsurl/","",$returnurl);
if ($returnurl == "/") $returnurl = "";

// Unset the return URL if requested...
if ($returntoshop == "true") unset($returnurl);

// Check if the shopping cart is empty...
if ($mode != "include") {
	if (!$basket && !$taxandshipping) {
		// Create security hash...
		if (!empty($taxandshipping)) $sid = md5($basket.$taxandshipping.$ashoppath);
		setcookie("sid","$sid");
		if (!strstr($HTTP_REFERER, "index.php") && !strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php") && !strstr($HTTP_REFERER, "discount.php")) { header ("Location: $action.php?shop=$shop&returnurl=$returnurl"); exit; }
		else if ($returnurl) { header ("Location: $action.php?shop=$shop&returnurl=$returnurl"); exit; }
		else { header ("Location: $action.php?cat=$cat&shop=$shop"); exit; }
	}
}

// Initialize variables...
$physicalgoods = 0;
$shippinggoods = 0;
$subtotal = 0;
$shipping = 0;
if ($memberpayoptions && !empty($shop)) {
	$basket = ashop_memberproductstring($db, $basket, $shop);
	if (empty($basket)) $taxandshipping = 0;
}
$productsincart = ashop_parseproductstring($db, $basket);
$totalqty = ashop_totalqty($basket);
$numberofproducts = 0;

// Check if shipping calculation is needed...
$activateusps = FALSE;
$activateups = FALSE;
$activatefedex = FALSE;
if ($productsincart) {
	foreach($productsincart as $productnumber => $thisproduct) {
		$thisitem = $thisproduct["productid"];
		$sql="SELECT shipping, taxable FROM product WHERE productid=$thisitem";
		$result = @mysqli_query($db, "$sql");
		$thisshipping = @mysqli_result($result, 0, "shipping");
		if ($thisshipping == "usps" || ($thisshipping == "storewide" && $storeshippingmethod == "usps")) $activateusps = TRUE;
		else if ($thisshipping == "ups" || ($thisshipping == "storewide" && $storeshippingmethod == "ups")) $activateups = TRUE;
		else if ($thisshipping == "fedex" || ($thisshipping == "storewide" && $storeshippingmethod == "fedex")) $activatefedex = TRUE;
		$thistaxable = @mysqli_result($result, 0, "taxable");
		if (($thisshipping || $thistaxable) && !($thisproduct["disableshipping"] && $thisproduct["disabletax"])) {
			$physicalgoods = 1;
			$numberofproducts+=$thisproduct["quantity"];
			if ($changeiftax == "true") $changeshipping = "true";
		}
		if ($thisshipping && !$thisproduct["disableshipping"]) {
			$shippinggoods = 1;
			if ($changeifshipping == "true") $changeshipping = "true";
		}
	}
	if (!$physicalgoods && $mode != "include") {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("taxandshipping","");
		// Create security hash...
		if (!empty($taxandshipping)) $sid = md5($basket.$taxandshipping.$ashoppath);
		setcookie("sid","$sid");
		if (isset($_COOKIE['basket'])) {
			if (!strstr($HTTP_REFERER, "index.php") && !strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php")) {
				if (strstr($SERVER_SOFTWARE, "IIS")) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?shop=$shop&returnurl=$returnurl\"></head></html>";
				else header ("Location: $action.php?shop=$shop&returnurl=$returnurl"); 
				exit; 
			} else if ($returnurl) { 
				if (strstr($SERVER_SOFTWARE, "IIS")) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?shop=$shop&returnurl=$returnurl\"></head></html>";
				else header ("Location: $action.php?shop=$shop&returnurl=$returnurl"); 
				exit; 
			} else { 
				if (strstr($SERVER_SOFTWARE, "IIS")) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?cat=$cat&shop=$shop\"></head></html>";
				else header ("Location: $action.php?cat=$cat&shop=$shop"); 
				exit; 
			}
		} else {
			if (!strstr($HTTP_REFERER, "index.php") && !strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php")) { 
				if (strstr($SERVER_SOFTWARE, "IIS")) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?basket=$basket&shop=$shop&returnurl=$HTTP_REFERER\"></head></html>";
				else header ("Location: $action.php?basket=$basket&shop=$shop&returnurl=$HTTP_REFERER");
				exit; 
			} else if ($returnurl) { 
				if (strstr($SERVER_SOFTWARE, "IIS")) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?basket=$basket&shop=$shop&returnurl=$returnurl\"></head></html>";
				else header ("Location: $action.php?basket=$basket&shop=$shop&returnurl=$returnurl");
				exit;
			} else { 
				if (strstr($SERVER_SOFTWARE, "IIS")) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?basket=$basket&cat=$cat&shop=$shop\"></head></html>";
				else header ("Location: $action.php?basket=$basket&cat=$cat&shop=$shop");
				exit; 
			}
		}
	}
} else if ($mode != "include") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("taxandshipping","");
	// Create security hash...
	if (!empty($taxandshipping)) $sid = md5($basket.$taxandshipping.$ashoppath);
	setcookie("sid","$sid");
}

// Check if local or international ship options should be displayed...
if(!$shipid && $localshipping && count($shipfromcountries) == 1 && $shippinggoods) {
	$destcntry = $shipfromcountries[0];
	if ($destcntry != "US" && $destcntry != "CA" && $destcntry != "AU" && $action == "basket") {
		$deststate = "not selected";
		$destprovince = "not selected";
		$destzip = "00000";
	} else if (!$destcntry || !$destzip || !$destcity) $checkcountry = "true";
}
if(!$shipid && $salestaxtype == "euvat" && $physicalgoods && !$shippinggoods) {
	$destcntry = $vatorigincountry;
	if ($action == "basket") {
		$deststate = "not selected";
		$destprovince = "not selected";
		$destzip = "00000";
	} else if (!$destcntry || !$destzip) $checkcountry = "true";
}
if (!$destcntry && $shipid) {
	$sql = "SELECT shippingcountry FROM shipping WHERE shippingid='$shipid'";
	$result = @mysqli_query($db, "$sql");
	$selectedcountry = @mysqli_result($result, 0, "shippingcountry");
} else $selectedcountry = $destcntry;
$shipoptiontype = "";
if (in_array($selectedcountry, $shipfromcountries)) $shipoptiontype = "local";
else if ($selectedcountry) $shipoptiontype = "international";

// Only allow selection of states in the selected country...
if ($selectedcountry == "US") $statesandprovinces = $americanstates;
else if ($selectedcountry == "CA") $statesandprovinces = $canprovinces;
else if ($selectedcountry == "AU") $statesandprovinces = $australianstates;
else if ($selectedcountry == "AT") $statesandprovinces = $austriastates;
else if ($selectedcountry == "BE") $statesandprovinces = $belgiumstates;
else if ($selectedcountry == "DE") $statesandprovinces = $germanystates;
else if ($selectedcountry == "ES") $statesandprovinces = $spainstates;
else if ($selectedcountry == "FR") $statesandprovinces = $francestates;
else if ($selectedcountry == "GB") $statesandprovinces = $ukstates;
else if ($selectedcountry == "IT") $statesandprovinces = $italystates;
else if ($selectedcountry == "LU") $statesandprovinces = $luxembourgstates;
else if ($selectedcountry == "NL") $statesandprovinces = $netherlandsstates;
else $statesandprovinces = $uscanstates;

// Get any previously selected option for this customer...
if (!strstr($taxandshipping, "so")) {
	if ($shipid) $previousshipoptionresult = @mysqli_query($db, "SELECT selectedoption FROM shipping WHERE shippingid = '$shipid'");
	$previousshipoption = @mysqli_result($previousshipoptionresult, 0, "selectedoption");
	if ($previousshipoption) $taxandshipping .= "so$previousshipoption"."a";
}

// Check for shipping options...
$upsservice = "00";
$fedexservice = "00";
if ($shipoptionstype == "custom" && $shippingmethod == "custom") {
	$result = @mysqli_query($db, "SELECT * FROM shipoptions LIMIT 1");
	if (@mysqli_num_rows($result)) $activateshipoptions = TRUE;
	else $activateshipoptions = FALSE;

	// Get shipping options...
	$selectedshipoptions = "";
	$sql = "SELECT * FROM shipoptions";
	if ($shipoptiontype) $sql .= " WHERE shipped='$shipoptiontype' OR shipped='both'";
	else $sql .= " WHERE shipped='both'";
	$sql .= " ORDER BY shipoptionid DESC";
	$result = @mysqli_query($db, $sql);
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$shipoptionid = @mysqli_result($result, $i, "shipoptionid");
		$shipoptions["$shipoptionid"] = @mysqli_result($result, $i, "description");
		if (strstr($taxandshipping, "so$shipoptionid"."a")) $selectedshipoptions = "so$shipoptionid"."a";
	}
} else if ($shipoptionstype == "usps" && $activateusps && $shippingmethod == "usps") {
	$activateshipoptions = TRUE;
	if ($selectedcountry == "US") $uspsshipoptions = $uspsservicesusa_num;
	else $uspsshipoptions = $uspsservicesworld_num;

	if ($shipoptiontype) foreach ($uspsshipoptions as $shipoptionid=>$shipoptiondescription) {
		$shipoptions["$shipoptionid"] = $shipoptiondescription;
		if (strstr($taxandshipping, "so$shipoptionid"."a")) {
			$selectedshipoptions = "so$shipoptionid"."a";
			$uspsservice = $shipoptionid;
		}
	}
} else if ($shipoptionstype == "ups" && $activateups && $shippingmethod == "ups") {
	$activateshipoptions = TRUE;
	if ($upscountry == "US") {
		if ($selectedcountry == "US") $upsshipoptions = $upsservicesusa;
		else if ($selectedcountry == "CA") $upsshipoptions = $upsservicestocan;
		else $upsshipoptions = $upsservicesworld;
	} else if ($upscountry == "CA") {
		if ($selectedcountry == "CA") $upsshipoptions = $upsservicescan;
		else if ($selectedcountry == "US") $upsshipoptions = $upsservicestousa;
		else $upsshipoptions = $upsservicesworld;
	}

	if ($shipoptiontype) foreach ($upsshipoptions as $shipoptionid=>$shipoptiondescription) {
		$shipoptions["$shipoptionid"] = $shipoptiondescription;
		if (strstr($taxandshipping, "so$shipoptionid"."a")) {
			$selectedshipoptions = "so$shipoptionid"."a";
			$upsservice = $shipoptionid;
		}
	}
} else if ($shipoptionstype == "fedex" && $activatefedex && $shippingmethod == "fedex") {
	$activateshipoptions = TRUE;

	if ($selectedcountry == "US") foreach ($fedexservicesusa as $servicecode => $servicename) {
		if ($servicecode != "70" && $servicecode != "80" && $servicecode != "83") $fedexshipoptions["$servicecode"] = $servicename;
	} else foreach ($fedexservicesworld as $servicecode => $servicename) {
		if ($servicecode != "70" && $servicecode != "86") $fedexshipoptions["$servicecode"] = $servicename;
	}

	if ($shipoptiontype) foreach ($fedexshipoptions as $shipoptionid=>$shipoptiondescription) {
		$shipoptions["$shipoptionid"] = $shipoptiondescription;
		if (strstr($taxandshipping, "so$shipoptionid"."a")) {
			$selectedshipoptions = "so$shipoptionid"."a";
			$fedexservice = $shipoptionid;
		}
	}
}

// Check if the customer has submitted shipping address...
if (!$deststate || $deststate == "none" || $deststate == "other") {
	if ($destprovince) $deststate = $destprovince;
	else if ($checkcountry != "true") $deststate = "";
}
if (!$destprovince && $destcntry != "US" && $destcntry != "CA" && $destcntry != "AU" && $destcntry != "AT" && $destcntry != "BE" && $destcntry != "DE" && $destcntry != "ES" && $destcntry != "FR" && $destcntry != "GB" && $destcntry != "IT" && $destcntry != "LU" && $destcntry != "NL") $deststate = "other";
if ($destfirstname || $destlastname || $destaddress || $destzip || $destcity) {
	if ($checkcountry != "true" && ($destfirstname && $destlastname && $destaddress && $destzip && ($destcntry != "US" || ($deststate && $deststate != "none" && $deststate != "other")) && $destcity && $destcntry && $destcntry != "none") || ($destcntry && ($shippinggoods != 1 || $action != "checkout"))) {
		$sql="SELECT shippingid, selectedoption FROM shipping WHERE (shippingbusiness = '$destbusiness' AND shippingfirstname = '$destfirstname' AND shippinglastname = '$destlastname' AND shippingaddress = '$destaddress' AND shippingzip = '$destzip' AND shippingstate = '$deststate' AND shippingcity = '$destcity' AND shippingcountry = '$destcntry') OR (vat = '$destvat' AND vat <> '' AND vat NOT NULL)";
		$result = @mysqli_query($db, "$sql");

		// Update selected shipping options...
		$previousshipoption = @mysqli_result($result, 0, "selectedoption");
		$selectedshipoptions = "";
		$selectedoption = 0;
		if ($shipoptions && ($shipoption == "on" || $shipoption > 0)) {
			if (count($shipoptions) == 1 && $shipoption == "on") {
				$selectedoptionid = key($shipoptions);
				$selectedshipoptions = "so".$selectedoptionid."a";
				if ($shipoptionstype == "usps") $uspsservice = $selectedoptionid;
				else if ($shipoptionstype == "ups") $upsservice = $selectedoptionid;
				else if ($shipoptionstype == "fedex") $fedexservice = $selectedoptionid;
				$selectedoption = $selectedoptionid;
			} else {
				$selectedshipoptions = "so$shipoption"."a";
				if ($shipoptionstype == "usps") $uspsservice = $shipoption;
				else if ($shipoptionstype == "ups") $upsservice = $shipoption;
				else if ($shipoptionstype == "fedex") $fedexservice = $shipoption;
				$selectedoption = $shipoption;
			}
		} else if ($previousshipoption) $selectedshipoptions = "so$previousshipoption"."a";
		else $selectedshipoptions = "";

		// Store shipping info...
		if ($destsameasbilling == "yes") $destsameasbilling = "'1'";
		else $destsameasbilling = "NULL";
		$sql="SELECT shippingid FROM shipping WHERE shippingid = '$shipid'";
		$result2 = @mysqli_query($db, "$sql");
		if (@mysqli_num_rows($result) != 0 || @mysqli_num_rows($result2) != 0) {
			$shippingid = @mysqli_result($result, 0, "shippingid");
			if (!$shippingid) $shippingid = @mysqli_result($result2, 0, "shippingid");
			if ($customerid) $sql="UPDATE shipping SET shippingbusiness = '$destbusiness', shippingfirstname = '$destfirstname', shippinglastname = '$destlastname', shippingaddress = '$destaddress', shippingaddress2 = '$destaddress2', shippingzip = '$destzip', shippingstate = '$deststate', shippingcity = '$destcity', shippingcountry = '$destcntry', vat = '$destvat', shippingphone='$destphone', shippingemail='$destemail', sameasbilling=$destsameasbilling, selectedoption='$selectedoption', customerid='$customerid' WHERE shippingid = $shippingid";
			else $sql="UPDATE shipping SET shippingbusiness = '$destbusiness', shippingfirstname = '$destfirstname', shippinglastname = '$destlastname', shippingaddress = '$destaddress', shippingaddress2 = '$destaddress2', shippingzip = '$destzip', shippingstate = '$deststate', shippingcity = '$destcity', shippingcountry = '$destcntry', vat = '$destvat', shippingphone='$destphone', shippingemail='$destemail', sameasbilling=$destsameasbilling, selectedoption='$selectedoption' WHERE shippingid = $shippingid";
			$result = @mysqli_query($db, "$sql");
		} else {
			if ($customerid) $sql = "INSERT INTO shipping (shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingzip, shippingcity, shippingstate, shippingcountry, vat, shippingphone, shippingemail, sameasbilling, selectedoption, customerid) VALUES ('$destbusiness', '$destfirstname', '$destlastname', '$destaddress', '$destaddress2', '$destzip', '$destcity', '$deststate', '$destcntry', '$destvat', '$destphone', '$destemail', $destsameasbilling, '$selectedoption', '$customerid')";
			else $sql = "INSERT INTO shipping (shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingzip, shippingcity, shippingstate, shippingcountry, vat, shippingphone, shippingemail, sameasbilling, selectedoption) VALUES ('$destbusiness', '$destfirstname', '$destlastname', '$destaddress', '$destaddress2', '$destzip', '$destcity', '$deststate', '$destcntry', '$destvat', '$destphone', '$destemail', $destsameasbilling, '$selectedoption')";
			$result = @mysqli_query($db, "$sql");
			$shippingid = @mysqli_insert_id($db);
		}
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie ("shipid", "$shippingid");
		$shipid = $shippingid;
	} else if ($checkcountry != "true") $errorstring = "<span class=\"ashopshippingerror\">".FIELDMISSING."</span><br /><br />";
}

// Get the last used shipping address...
if ($shipid && $checkcountry != "true" && !$errorstring) {
	$sql = "SELECT * FROM shipping WHERE shippingid='$shipid'";
	$result = @mysqli_query($db, "$sql");
	$shippingbusiness = @mysqli_result($result, 0, "shippingbusiness");
	if ($shippingbusiness) $residential = "no";
	else $residential = "yes";
	$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
	$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
	$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
	$shippingaddress2 = @mysqli_result($result, 0, "shippingaddress2");
	$shippingzip = @mysqli_result($result, 0, "shippingzip");
	$shippingcity = @mysqli_result($result, 0, "shippingcity");
	$shippingstate = @mysqli_result($result, 0, "shippingstate");
	$shippingcountry = @mysqli_result($result, 0, "shippingcountry");
	$shippingvat = @mysqli_result($result, 0, "vat");
	$shippingphone = @mysqli_result($result, 0, "shippingphone");
	$shippingemail = @mysqli_result($result, 0, "shippingemail");
	$shippingsameasbilling = @mysqli_result($result, 0, "sameasbilling");
	if ($shippingcountry != "US" && $shippingcountry != "CA" && $shippingcountry != "AU" && in_array($shippingstate,$uscanstates)) $shippingstate = "other";
}

// Check if the shipping address is being changed from a full address of just the short form...
if ($changeshipping == "true" && ($shippingfirstname || $shippinglastname || $shippingaddress)) $showfullform = "true";
else if ($destfirstname || $destlastname || $destaddress) $showfullform = "true";
else $showfullform = "";

// Make international shipping work with FedEx...
if ($shippingcountry == "US" || $shippingcountry == "CA") $fedexshippingstate = $shippingstate;
else $fedexshippingstate = "";

if($shippinggoods && $cal == "true" && (!$shippingfirstname || !$shippinglastname || !$shippingaddress || !$shippingzip || !$shippingcity)) $changeshipping = "true";

if ($shipid && $changeshipping != "true" && $checkcountry != "true") {
	$shippingerror = "";
	// Calculate shipping cost for all items in the shopping cart...
	if ($productsincart) {
		$totalweight = "";
		$totaldeclaredvalue = "";
		$getstoreshippingbyprice = FALSE;
		// Combine the same products with different attributes...
		foreach($productsincart as $productnumber => $thisproduct) {
			$thisitem = $thisproduct["productid"];
			$thisquantity = $thisproduct["quantity"];
			if (!$thisproduct["disableshipping"]) $combinedqty[$thisitem] += $thisquantity;
		}
		reset($productsincart);
		foreach($productsincart as $productnumber => $thisproduct) {
			$thisitem = $thisproduct["productid"];
			$thisquantity = $thisproduct["quantity"];
			if ($combinedqty[$thisitem] > $thisquantity && !$hascalculated[$thisitem]) {
				$thisquantity = $combinedqty[$thisitem];
				$hascalculated[$thisitem] = TRUE;
			} else if ($hascalculated[$thisitem]) continue;

			// Get quantity type for quantity pricing calculation...
			if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $thisquantity;
			else {
				if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
				else $subtotalqty = ashop_categoryqty($db, $basket, $thisproduct["qtycategory"]);
			}

			// Get the price and discount of the product for sales tax calculation...
			$thisprice = $thisproduct["price"];
			if (isset($_COOKIE) && is_array($_COOKIE)) foreach ($_COOKIE as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
					$result2 = @mysqli_query($db, "$sql");
					if (@mysqli_num_rows($result2)) {
						$discountcategory = @mysqli_result($result2, 0, "categoryid");
						$result3 = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$thisitem' AND categoryid='$discountcategory'");
						if (@mysqli_num_rows($result3)) $thisproductdiscount = $cookievalue;
					}
				}
			}
			if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$sql="SELECT * FROM discount WHERE productid='$thisitem' AND discountid='$discountid'";
					$result2 = @mysqli_query($db, "$sql");
					if (@mysqli_num_rows($result2)) $thisproductdiscount = $cookievalue;
				}
			}
			$thistotal = ashop_subtotal($db, $thisitem, $subtotalqty, $thisquantity, $thisproductdiscount, $thisproduct["price"], $thisproduct["qtytype"]);
			if ($wholesale) {
				if ($pricelevel == 1) $thistotal = $thisquantity*$thisproduct["wholesaleprice"];
				else {
					$pricelevels = $thisproduct["wspricelevels"];
					$thistotal = $thisquantity*$pricelevels[$pricelevel-2];
				}
			}
			$taxable = 0;
			$thisshipping = "";
			$sql="SELECT shipping, intshipping, countryshipping, taxable, weight FROM product WHERE productid=$thisitem";
			$result = @mysqli_query($db, "$sql");
			if (!$thisproduct["disabletax"]) $taxable = @mysqli_result($result, 0, "taxable");
			$subtotal += $thistotal;
			if ($taxable == 1) $taxtotal += $thistotal;
			else if ($taxable == 2) $taxlevel2total += $thistotal;
			if (!$thisproduct["disableshipping"]) {
				$thismainshipping = @mysqli_result($result, 0, "shipping");
				if (in_array($shippingcountry, $shipfromcountries)) $thisshipping = $thismainshipping;
				else if ($thismainshipping != "usps" && $thismainshipping != "ups" && $thismainshipping != "fedex" && $thismainshipping != "wml" && $thismainshipping != "storewide" && $thismainshipping != "quantity") {
					$checkcountryshipping = @mysqli_result($result, 0, "countryshipping");
					unset($thiscountryshippingrates);
					if (!empty($checkcountryshipping)) {
						$thiscountryshippingrates = array();
						$checkcountryshippingarray = explode("|",$checkcountryshipping);
						foreach ($checkcountryshippingarray as $thiscountryshipping) {
							$thiscountryshippingarray = explode(":",$thiscountryshipping);
							$thiscountryshippingrates["{$thiscountryshippingarray[0]}"] = $thiscountryshippingarray[1];
						}
					}
					if (!empty($thiscountryshippingrates) && is_array($thiscountryshippingrates) && array_key_exists($shippingcountry,$thiscountryshippingrates)) $thisshipping = $thiscountryshippingrates[$shippingcountry];
					else {
						$checkintshipping = @mysqli_result($result, 0, "intshipping");
						if (!empty($checkintshipping)) $thisshipping = $checkintshipping;
						else $thisshipping = $thismainshipping;
					}
				} else $thisshipping = $thismainshipping;
			}
			if ($thisshipping == "usps" || $thisshipping == "ups" || $thisshipping == "fedex" || $thisshipping == "wml") {
				$sql="SELECT * FROM packages WHERE productid=$thisitem";
				$result = @mysqli_query($db, "$sql");
				unset($upsfedexshipping);
				for ($j = 0; $j < @mysqli_num_rows($result); $j++) {
					$thisdeclaredvalue = floor($thisprice/@mysqli_num_rows($result));
					$thisoriginzip = @mysqli_result($result, $j, "originzip");
					$thisorigincountry = @mysqli_result($result, $j, "origincountry");
					$thisoriginstate = @mysqli_result($result, $j, "originstate");
					$thisweight = @mysqli_result($result, $j, "weight");
					$thisclass = @mysqli_result($result, $j, "freightclass");
					if ($thisshipping == "usps") $upsfedexshipping += uspsshipping($thisoriginzip, $shippingzip, $shippingcountry, $thisweight, $thisdeclaredvalue, $uspsservice);
					else if ($thisshipping == "ups") {
						if ($shippingstate == "PR") $upsshippingcountry = "PR";
						else $upsshippingcountry = $shippingcountry;
						$upsfedexshipping += upsshipping($thisoriginzip, $shippingzip, $upscountry, $upsshippingcountry, $thisweight, $residential, $upsservice);
					}
					else if ($thisshipping == "fedex") $upsfedexshipping += fedexshipping($thisoriginzip, $shippingzip, $thisorigincountry, $shippingcountry, $thisoriginstate, $fedexshippingstate, $thisweight, $thisdeclaredvalue,$fedexservice);
					else if ($thisshipping == "wml") $upsfedexshipping += wmlshipping($thisoriginzip, $shippingzip, $thisweight, $thisclass);
					if ($upsfedexshipping == "error") $shippingerror = "true";
				}
				$shipping += $thisquantity * $upsfedexshipping;
			}

			else if (strstr($thisshipping,"zone")) {
				$thiszoneshipping  = zoneshipping( $shippingzip, $shippingcountry, $thisitem ); 
				$shipping += $thisquantity * $thiszoneshipping;
			}

			else if ($thisshipping == "quantity") {
				$result = @mysqli_query($db, "SELECT MAX(quantity) AS maxqty FROM quantityrates WHERE productid='$thisitem' AND quantity <='$thisquantity'");
				$maxquantity = @mysqli_result($result,0,"maxqty");
				$result = @mysqli_query($db, "SELECT rate FROM quantityrates WHERE productid='$thisitem' AND quantity='$maxquantity'");
				$quantityrate = @mysqli_result($result,0,"rate");
				$shipping += $thisquantity * $quantityrate;
			}

			else if ($thisshipping == "storewide") {
				$totaldeclaredvalue += $thistotal;
				$thisweight = @mysqli_result($result, 0, "weight");
				$totalweight += $thisquantity*$thisweight;
				if ($storeshippingmethod == "byprice") $getstoreshippingbyprice = TRUE;
			}
			
			else if ($thisshipping) $shipping += $thisquantity * $thisshipping;
		}
		// Calculate storewide shipping...
		if ($totalweight || $getstoreshippingbyprice) {
			$storeshipping = "";
			if ($storeshippingmethod == "usps" || $storeshippingmethod == "ups" || $storeshippingmethod == "fedex") {
				$totalpackages = floor($totalweight/$storeshippingmaxweight);
				$restpackage = $totalweight-($totalpackages*$storeshippingmaxweight);
				$numberofpackages = $totalpackages;
				if ($restpackage) $numberofpackages += 1;
				$thisdeclaredvalue = floor($totaldeclaredvalue/$numberofpackages);
				if ($totalpackages) for ($i = 0; $i < $totalpackages; $i++) {
					$thisorigincountry = $upscountry;
					if ($storeshippingmethod == "usps") $storeshipping += uspsshipping($storeshippingfromzip, $shippingzip, $shippingcountry, $storeshippingmaxweight, $thisdeclaredvalue,$uspsservice);
					else if ($storeshippingmethod == "ups") $storeshipping += upsshipping($storeshippingfromzip, $shippingzip, $thisorigincountry, $shippingcountry, $storeshippingmaxweight, $residential,$upsservice);
					else if ($storeshippingmethod == "fedex") $storeshipping += fedexshipping($storeshippingfromzip, $shippingzip, $thisorigincountry, $shippingcountry, $storeshippingfromstate, $fedexshippingstate, $storeshippingmaxweight, $thisdeclaredvalue,$fedexservice);
					if ($storeshipping == "error") $shippingerror = "true";
				}
				if ($restpackage) {
					$thisorigincountry = $upscountry;
					if ($storeshippingmethod == "usps") $storeshipping += uspsshipping($storeshippingfromzip, $shippingzip, $shippingcountry, $restpackage, $thisdeclaredvalue,$uspsservice);
					else if ($storeshippingmethod == "ups") $storeshipping += upsshipping($storeshippingfromzip, $shippingzip, $thisorigincountry, $shippingcountry, $restpackage, $residential,$upsservice);
					else if ($storeshippingmethod == "fedex") $storeshipping += fedexshipping($storeshippingfromzip, $shippingzip, $thisorigincountry, $shippingcountry, $storeshippingfromstate, $fedexshippingstate, $restpackage, $thisdeclaredvalue,$fedexservice);
					if ($storeshipping == "error") $shippingerror = "true";
				}
			} else if ($storeshippingmethod == "perpound") $storeshipping = $storeshippingbasecharge+($totalweight*$storeshippingperpound);
			else if ($storeshippingmethod == "byweight") $storeshipping = weightshipping($totalweight);
			else if($storeshippingmethod == "byprice") $storeshipping = priceshipping($totaldeclaredvalue);
			if ($storeshipping) $shipping += $storeshipping;
		}
	}
	if ($shippinggoods) {
		if (in_array($shippingcountry, $shipfromcountries)) $shipping += $handlinglocal;
		else $shipping += $handlingint;
	}

	// Add shipping option fees if any...
	if ($shippinggoods && $shipoptions && $shipoptionstype == "custom") foreach ($shipoptions as $shipoptionid => $shipoptiondescr) {
		if (strstr($selectedshipoptions, "so$shipoptionid"."a")) {
			$result = @mysqli_query($db, "SELECT fee, disableshipping FROM shipoptions WHERE shipoptionid='$shipoptionid'");
			$shipoptiondisableshipping = @mysqli_result($result, 0, "disableshipping");
			if ($shipoptiondisableshipping == 1) $shipping = @mysqli_result($result, 0, "fee");
			else $shipping += @mysqli_result($result, 0, "fee");
		}
	}

	// Calculate sales tax if needed...
	$dotaxcalculation = "false";
	$dopstcalculation = "false";
	$dohstcalculation = "false";
	switch ($salestaxtype) {
		case "ussalestax":
			if($shippingstate == $taxstate && $shippingcountry == "US" && !$wholesale) {
				$dotaxcalculation = "true";
				// Check for additional local rates...
				$checktaxcity = strtoupper(trim($shippingcity));
				$extrataxresult = @mysqli_query($db, "SELECT * FROM localtax WHERE UPPER(city)='$checktaxcity'");
				if (@mysqli_num_rows($extrataxresult)) {
					$extratax = @mysqli_result($extrataxresult,0,"rate");
					$taxpercentage += $extratax;
				}
			}
		break;
		case "cancstpst":
			if($shippingcountry == "CA") $dohstcalculation = "true";
		break;
		case "euvat":
			if((in_array($shippingcountry, $ecmembers) && !$shippingvat && !$wholesale) || (in_array($shippingcountry, $ecmembers) && $shippingcountry == $vatorigincountry)) $dotaxcalculation = "true";
		break;
		case "australiagst":
			if($shippingcountry == "AU") $dotaxcalculation = "true";		
		break;
		case "safricanvat":
			$dotaxcalculation = "true";
		break;
	}

	// Check if free shipping applies...
	if ($freeshippinglimit && $subtotal > $freeshippinglimit) {
		if ($freeshippingonlylocal && in_array($shippingcountry, $shipfromcountries)) $shipping = "0.00";
		else if (!$freeshippingonlylocal) $shipping = "0.00";
	}

	if ($dotaxcalculation == "true") {

		// Subtract storewide discount of amount type if any...
		if ($discountall) {
			$storediscountresult = @mysqli_query($db, "SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='$'");
			if (@mysqli_num_rows($storediscountresult)) {
				$storediscountrow = @mysqli_fetch_array($storediscountresult);
				if ($storediscountrow["value"]) $taxtotal -= $storediscountrow["value"];
			}
		}

		$taxmultiplier = ($taxpercentage / 100);
		if ($displaywithtax == 2) {
			$excludingtax = $taxtotal / ($taxmultiplier+1);
			$tax = $taxtotal - $excludingtax;
		} else $tax = $taxtotal * $taxmultiplier;
		if ($taxlevel2total) {
			$taxlevel2multiplier = ($taxpercentage2 / 100);
			if ($displaywithtax == 2) {
				$excludingtax = $taxlevel2total / ($taxlevel2multiplier+1);
				$tax += $taxlevel2total - $excludingtax;
			} else $tax += $taxlevel2total * $taxlevel2multiplier;
		}
		if ($shippingtax) {
			if ($displaywithtax == 2) {
				$excludingtax = $shipping / ($taxmultiplier+1);
				$tax += $shipping - $excludingtax;
			} else $tax += $shipping * $taxmultiplier;
		}
		$tax = round((($tax*100)/100)+0.0001, 3);
		if ($tax < 0) $tax = 0;
	}
	if ($dopstcalculation == "true") {
		$taxmultiplier = ($pstpercentage / 100);
		$psttotal = $tax+$taxtotal;
		if ($shippingtax && !in_array($shippingstate, $hstprovinces)) $psttotal += $shipping;
		if ($displaywithtax == 2) {
			$excludingtax = $psttotal / ($taxmultiplier+1);
			$pst = $psttotal - $excludingtax;
		} else $pst = $psttotal * $taxmultiplier;
		$tax += round((($pst*100)/100)+0.0001, 3);
	}
	if ($dohstcalculation == "true") {
		if (!empty($hstpercentage) && strstr($hstpercentage,"|")) {
			$cantaxarray = explode("|",$hstpercentage);
			if (is_array($cantaxarray)) foreach ($cantaxarray as $cantaxarraypart) {
				$thisprovincearray = explode(":",$cantaxarraypart);
				if ($thisprovincearray[0] == $shippingstate) {
					$gstpercentage = $thisprovincearray[1];
					$pstpercentage = $thisprovincearray[2];
					$pstcompounded = $thisprovincearray[3];
					$gstmultiplier = ($gstpercentage / 100);
					if ($shippingtax) $gsttotal = $taxttotal + $shipping;
					else $gsttotal = $taxtotal;
					if ($displaywithtax == 2) {
						$excludingtax = $gsttotal / ($gstmultiplier+1);
						$gst = $gsttotal - $excludingtax;
					} else $gst = $gsttotal * $gstmultiplier;
					$pstmultiplier = ($pstpercentage / 100);
					if ($pstcompounded == "1") $psttotal = $taxtotal + $gst;
					else $psttotal = $taxtotal;
					if ($displaywithtax == 2) {
						$excludingtax = $psttotal / ($pstmultiplier+1);
						$pst = $psttotal - $excludingtax;
					} else $pst = $psttotal * $pstmultiplier;
					$tax = $gst+$pst;
					$tax = round((($tax*100)/100)+0.0001, 3);
				}
			}
		}
	}

	// Calculate quantity discounts...
	if ($shippinggoods) {
		$totaldiscount = "";
		$result = @mysqli_query($db, "SELECT * FROM shipdiscounts ORDER BY quantity DESC");
		if (@mysqli_num_rows($result)) for($i=0; $i<@mysqli_num_rows($result); $i++) {
			$thisdiscountvalue = @mysqli_result($result, $i, "value");
			$thisdiscountquantity = @mysqli_result($result, $i, "quantity");
			$thisdiscountlocal = @mysqli_result($result, $i, "local");
			$thisdiscountshipoption = @mysqli_result($result, $i, "shipoptionid");
			if($numberofproducts >= $thisdiscountquantity && (($thisdiscountlocal && $shipoptiontype == "local") || (!$thisdiscountlocal && $shipoptiontype == "international")) && (strstr($selectedshipoptions, "so$thisdiscountshipoption"."a") || !$thisdiscountshipoption) && !$totaldiscount) $totaldiscount = $thisdiscountvalue;
		}
	}

	if (!$shippingerror) {

		// Store shipping and tax as products...
		$newtaxandshipping = "";
		if(!$shippinggoods || ($shippingcountry && $shippingzip)) {
			if ($shipping && $basket) $newtaxandshipping .= "shb".round($shipping,2)."a";
			if (!$tax) $tax = "0.00";
			if (($tax || $dotaxcalculation) && $basket) $newtaxandshipping .= "stb".round($tax,2)."a";
			if ($totaldiscount && $basket) $newtaxandshipping .= "sdb".round($totaldiscount,2)."a";
			if ($selectedshipoptions) $newtaxandshipping .= $selectedshipoptions;
		}
		if ($quote) {
			echo $newtaxandshipping;
			exit;
		}
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("taxandshipping","$newtaxandshipping");

		// Create security hash...
		if (!empty($newtaxandshipping)) $sid = md5($basket.$newtaxandshipping.$ashoppath);
		setcookie("sid","$sid");

		// Redirect to $action...
		if (isset($_COOKIE['basket'])) {
			if ($returnurl) { 
				if (strstr($SERVER_SOFTWARE, "IIS") || $p3psent) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?returnurl=$returnurl&sid=$sid&shop=$shop&payoption=$payoption\"></head></html>";
				else header("Location: $action.php?returnurl=$returnurl&sid=$sid&shop=$shop&payoption=$payoption"); 
				exit; 
			} else { 
				if (strstr($SERVER_SOFTWARE, "IIS") || $p3psent) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?cat=$cat&sid=$sid&shop=$shop&payoption=$payoption\"></head></html>";
				else header("Location: $action.php?cat=$cat&sid=$sid&shop=$shop&payoption=$payoption"); 
				exit; 
			}
		} else {
			if ($returnurl) { 
				if (strstr($SERVER_SOFTWARE, "IIS") || $p3psent) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?basket=$basket&returnurl=$returnurl&sid=$sid&shop=$shop\"></head></html>";
				else header("Location: $action.php?basket=$basket&returnurl=$returnurl&sid=$sid&shop=$shop"); 
				exit;
			} else {
				if (strstr($SERVER_SOFTWARE, "IIS") || $p3psent) echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=$action.php?basket=$basket&cat=$cat&sid=$sid&shop=$shop\"></head></html>";
				else header("Location: $action.php?basket=$basket&cat=$cat&sid=$sid&shop=$shop"); 
				exit;
			}
		}
	} else {
		if ($errorstring) $errorstring = "<span class=\"ashopshippingerror\">$errorstring</span><br /><br />";
		else $errorstring = "<span class=\"ashopshippingerror\">".INCORRECTZIP."</span><br /><br />";
	}
}

// Print header from template...
if ($mode != "include") {
	if ($wholesale) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wscart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wscart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/wscart.html");
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	}
}

// Verify form fields...
$path = "";
echo "
<script language=\"JavaScript\" src=\"{$path}includes/prototype.js\" type=\"text/javascript\"></script>
<script language=\"JavaScript\" src=\"{$path}includes/shipoptions.js\" type=\"text/javascript\"></script>
<script language=\"JavaScript\" src=\"{$path}includes/switchstates.js.php\" type=\"text/javascript\"></script>
<script language=\"JavaScript\" type=\"text/javascript\">
/* <![CDATA[ */
	function verifyform(shippingform) {
		var allformfieldsfilled = 1;";
if ($shippinggoods == 1 && ($action == "checkout" || $showfullform)) echo "
		if (shippingform.destfirstname.value == '') allformfieldsfilled = 0;
		if (shippingform.destlastname.value == '') allformfieldsfilled = 0;
		if (shippingform.destaddress.value == '') allformfieldsfilled = 0;
		if (shippingform.destcity.value == '') allformfieldsfilled = 0;
		if (shippingform.destphone.value == '') allformfieldsfilled = 0;
		if (shippingform.destemail.value == '') allformfieldsfilled = 0;";
else if ($salestaxtype == "ussalestax") echo "
		if (shippingform.destcity.value == '') allformfieldsfilled = 0;";
if ($salestaxtype != "euvat" || $shippinggoods == 1) echo "
		if ((shippingform.destcntry.value == 'US' || shippingform.destcntry.value == 'CA' || shippingform.destcntry.value == 'AU' || shippingform.destcntry.value == 'AT' || shippingform.destcntry.value == 'BE' || shippingform.destcntry.value == 'DE' || shippingform.destcntry.value == 'ES' || shippingform.destcntry.value == 'FR' || shippingform.destcntry.value == 'GB' || shippingform.destcntry.value == 'IT' || shippingform.destcntry.value == 'LU' || shippingform.destcntry.value == 'NL') && (shippingform.deststate.value == 'none' || shippingform.deststate.value == 'other')) allformfieldsfilled = 0;
		";
echo "  
		if (shippingform.destzip.value == '') allformfieldsfilled = 0;
		if (shippingform.destcntry.value == 'none') allformfieldsfilled = 0;
		if (allformfieldsfilled == 0) {
			document.getElementById('message').innerHTML = '".FILLINALL."<br /><br />';
			return false;
		} else return true;
    }
/* ]]> */
</script>";

// Ask for shipping address...
if ($mode != "include") echo "<table class=\"ashopshippingframe\">
<tr><td align=\"center\">";
echo "<div class=\"ashopshippingerror\" id=\"message\"></div>";
if ($mode != "include") echo "<span class=\"ashopshippingerror\">$errorstring </span><span class=\"ashopshippingtext1\">".PLEASEENTER."</span><br />";
echo "<form method=\"post\" action=\"shipping.php\" name=\"shippingform\" onsubmit=\"return verifyform(this);\"";
if ($device == "mobile") echo " data-ajax=\"false\"";
echo "><input type=\"hidden\" name=\"cat\" value=\"$cat\" />
<table class=\"ashopshippingbox\" cellpadding=\"5\" cellspacing=\"0\">";
if ($mode == "include") {
	if ($shippinggoods == 1) echo "<tr class=\"ashoptableheader\"><td align=\"center\"><span class=\"ashopcheckouttext1\">".SHIPPINGINFO."</span></td></tr>";
	else echo "<tr class=\"ashoptableheader\"><td align=\"center\"><span class=\"ashopcheckouttext1\">".TAXINFO."</span></td></tr>";
}
echo "
<tr><td align=\"left\">
<table cellspacing=\"0\" cellpadding=\"3\" width=\"100%\">";
if ($shippinggoods == 1 && ($action == "checkout" || $showfullform)) {
	echo "
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">".BUSINESS."</span></td>
                  <td width=\"269\"> 
                    <input type=\"text\" name=\"destbusiness\" size=\"30\" value=\""; if (!$shippingbusiness) echo $destbusiness; else echo $shippingbusiness; echo "\" />
                  </td>
                </tr>
				<tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".FIRSTNAME."</span></td>
                  <td width=\"269\"> 
                    <input type=\"text\" name=\"destfirstname\" size=\"30\" value=\""; if (!$shippingfirstname) echo $destfirstname; else echo $shippingfirstname; echo "\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".LASTNAME."</span></td>
                  <td width=\"269\"> 
                    <input type=\"text\" name=\"destlastname\" size=\"30\" value=\""; if (!$shippinglastname) echo $destlastname; else echo $shippinglastname; echo "\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".ADDRESS1."</span></td>
                  <td width=\"269\"> 
                    <input type=\"text\" name=\"destaddress\" size=\"30\" value=\""; if (!$shippingaddress) echo $destaddress; else echo $shippingaddress; echo "\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">".ADDRESS2."</span></td>
                  <td width=\"269\"> 
                    <input type=\"text\" name=\"destaddress2\" size=\"30\" value=\""; if (!$shippingaddress2) echo $destaddress2; else echo $shippingaddress2; echo "\" />
                  </td>
                </tr>
				<tr>
                  <td align=\"right\" height=\"25\" width=\"159\"><span class=\"ashopshippingtext2\">* ".CITY."</span></td>
                  <td width=\"269\" height=\"25\"> 
                    <input type=\"text\" name=\"destcity\" size=\"20\" value=\""; if (!$shippingcity) echo $destcity; else echo $shippingcity; echo "\" />
                  </td>
                </tr>";
} else if ($salestaxtype == "ussalestax") {
	echo "<tr>
                  <td align=\"right\" height=\"25\" width=\"159\"><span class=\"ashopshippingtext2\">* ".CITY."</span></td>
                  <td width=\"269\" height=\"25\"> 
                    <input type=\"text\" name=\"destcity\" size=\"20\" value=\""; if (!$shippingcity) echo $destcity; else echo $shippingcity; echo "\" />
                  </td>
                </tr>";
}

echo "<tr><td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".ZIPCODE."</span></td>
                  <td width=\"269\"> 
                    <input type=\"text\" name=\"destzip\" size=\"10\" value=\""; if ($shippingzip == "00000") $shippingzip = ""; if (!$shippingzip) echo $destzip; else echo $shippingzip; echo "\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".COUNTRY."</span></td>
                  <td width=\"269\" valign=\"top\"><select name=\"destcntry\" onchange=\"switchStates(document.getElementById('state'),document.shippingform.destprovince,document.shippingform.destcntry.value);";
if ($shippinggoods == 1) echo " getshipoptions(document.shippingform.destcntry.value);";
echo "\" onClick=\"if (typeof(countryinterval) != 'undefined') window.clearInterval(countryinterval);\">";
if(!$localshipping || count($shipfromcountries) > 1) echo "<option value=\"none\">".CHOOSECOUNTRY."</option>";

if ($shipfromcountries) foreach ($shipfromcountries as $thiscountry) {
	echo "<option value=\"$thiscountry\"";
	if (!$shippingcountry) {
		if ($thiscountry == $destcntry) echo " selected";
	} else if ($thiscountry == $shippingcountry) echo " selected";
	echo ">$countries[$thiscountry]</option>\n";
}

if (!$localshipping) foreach ($countries as $shortcountry => $longcountry) {
	if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
	echo "<option value=\"$shortcountry\"";
	if (!$shippingcountry) {
		if ($shortcountry == $destcntry) echo " selected";
	} else if ($shortcountry == $shippingcountry) echo " selected";
	echo ">$longcountry</option>\n";
}

echo "</select></td></tr>
";
if ($localshipping && count($shipfromcountries) > 1) echo "<tr><td></td><td><span class=\"ashopshippingnotice\">".ONLYSHIPTO1."</span></td></tr>";
else if ($localshipping) echo "<tr><td></td><td><span class=\"ashopshippingnotice\">".ONLYSHIPTO2."</span></td></tr>
";

echo "<tr id=\"stateselector\"";
if (!$shippingstate || !in_array($shippingcountry,$countrieswithstates)) echo " style=\"display:none\"";
echo ">
";

if ($salestaxtype != "euvat" || $shippinggoods == 1) {
	echo "<td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".STATE."</span></td>
                  <td width=\"269\">
				  <select name=\"deststate\" id=\"state\"><option value=\"none\">".CHOOSESTATE."</option>";

	$showprovince = 1;
	foreach ($statesandprovinces as $longstate => $shortstate) if (($deststate || $shippingstate) && ($shortstate == $deststate || $shortstate == $shippingstate)) $showprovince = 0;

	foreach ($statesandprovinces as $longstate => $shortstate) {
		echo "<option value=\"$shortstate\"";
		if (!$shippingstate) {
			if ($shortstate == $deststate) {
				echo " selected";
				$showprovince = 0;
			}
		} else {
			if ($shortstate == $shippingstate) {
				echo " selected";
				$showprovince = 0;
			}
		}
		echo ">$longstate</option>\n";
	}
	
	echo "</select></td></tr>";
	
	echo "<tr id=\"regionrow\"";
	if ($shippingstate == "not selected") $shippingstate = "";
	if ($deststate == "not selected") $deststate = "";
	if (!$shippingstate || in_array($shippingcountry,$countrieswithstates)) echo " style=\"display:none\"";
	echo "><td align=\"right\" height=\"25\" width=\"159\"><span class=\"ashopshippingtext2\">".PROVINCE."</span></td><td width=\"269\" height=\"25\"><input type=\"text\" name=\"destprovince\" id=\"shadowed\" size=\"20\" value=\"";
	if (!$shippingstate && $showprovince && $deststate != "other" && $deststate != "none") echo $deststate;
	else if ($showprovince) echo $shippingstate;
	echo "\" />
                  </td>
                </tr>";
}

if (($localshipping && count($shipfromcountries) < 2 && !$shippingstate) || !empty($destcntry) || !empty($shippingcountry)) {
	echo "<script language=\"javascript\" type=\"text/javascript\">/* <![CDATA[ */ switchStates(document.shippingform.deststate,document.shippingform.destprovince,document.shippingform.destcntry.value);\n";
	if (!empty($shippingstate)) echo "document.shippingform.deststate.value = '$shippingstate';\n";
	echo "/* ]]> */</script>\n";
}

if ($shippinggoods == 1 && ($action == "checkout" || $showfullform)) {
	echo "<tr><td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".PHONE."</span></td><td width=\"269\"><input type=\"text\" name=\"destphone\" size=\"20\" value=\""; if (!$shippingphone) echo $destphone; else echo $shippingphone; echo "\" /></td></tr>";
	echo "<tr><td align=\"right\" width=\"159\"><span class=\"ashopshippingtext2\">* ".EMAIL."</span></td><td width=\"269\"><input type=\"text\" name=\"destemail\" size=\"30\" value=\""; if (!$shippingemail) echo $destemail; else echo $shippingemail; echo "\" /></td></tr>";
}

if ($requestvat) {
	echo "<tr>
                  <td align=\"right\" height=\"25\" width=\"159\"><span class=\"ashopshippingtext2\">".VATNUMBER."</span></td>
                  <td width=\"269\" height=\"25\"> 
                    <input type=\"text\" name=\"destvat\" size=\"20\" value=\""; if (!$shippingvat) echo $destvat; else echo $shippingvat; echo "\" />
                  </td>
                </tr>";
} else if ($requestabn) {
	echo "<tr>
                  <td align=\"right\" height=\"25\" width=\"159\"><span class=\"ashopshippingtext2\">".ABNNUMBER."</span></td>
                  <td width=\"269\" height=\"25\"> 
                    <input type=\"text\" name=\"destvat\" size=\"20\" value=\""; if (!$shippingvat) echo $destvat; else echo $shippingvat; echo "\" />
                  </td>
                </tr>";
}

echo "<tr><td></td><td><span class=\"ashopshippingnotice\">".REQUIREDFIELD."</span></td></tr>
<tr><td align=\"right\" width=\"159\"></td><td id=\"shipoptionlist\" width=\"269\">";
$shipoptionnumber = 0;
if ($shipoptions && $shippinggoods == 1) {

	foreach ($shipoptions as $shipoptionid => $shipoptiondescr) {
		$shipoptionnumber++;
		if (count($shipoptions) == 1) {
			echo "<input type=\"checkbox\" name=\"shipoption\"";
			if (strstr($taxandshipping, "so$shipoptionid")) echo "checked";
			echo " /><span class=\"ashopshippingtext2\"> $shipoptiondescr</span>";
		} else {
			echo "<input type=\"radio\" name=\"shipoption\" value=\"$shipoptionid\" ";
			if (strstr($taxandshipping, "so$shipoptionid") || (count($shipoptions) > 1 && $shipoptionnumber == 1)) echo "checked";
			echo " /><span class=\"ashopshippingtext2\"> $shipoptiondescr</span>";
			if ($shipoptionnumber < count($shipoptions)) echo "<br />";
		}
	}
} else echo "<input type=\"hidden\" name=\"shipoption\" value=\"0\" />";
echo "</td></tr>";
if (!$_COOKIE["customersessionid"] && !$wholesale && $shippinggoods == 1 && ($action == "checkout" || $showfullform)) {
	echo "<tr><td align=\"right\" width=\"159\"></td><td width=\"269\"><span class=\"ashopshippingtext2\"><input type=\"checkbox\" name=\"destsameasbilling\" value=\"yes\""; if ($shippingsameasbilling == "1") echo " checked"; echo " /> ".ISSAMEASBILLING."</span></td></tr>";
} else echo "<input type=\"hidden\" name=\"destsameasbilling\" value=\"no\" /></td></tr>";

echo "</table></td></tr>
	  <tr>
                  <td colspan=\"2\" align=\"center\">
				      <p><input type=\"hidden\" name=\"checkcountry\" value=\"false\" />
				      <input type=\"hidden\" name=\"basket\" value=\"$basket\" />
				      <input type=\"hidden\" name=\"action\" value=\"$action\" />
					  <input type=\"hidden\" name=\"shop\" value=\"$shop\" />";

if ($returnurl || (!strstr($HTTP_REFERER, "index.php") && !strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php"))) {
	echo "<input type=\"hidden\" name=\"returnurl\" value=\"";
	if ($returnurl) echo $returnurl;
	else echo $HTTP_REFERER;
	echo "\" />";
}

if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" name=\"Submit\" value=\"".SUBMIT."\" />";
else echo "<input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" style=\"border: none;\" alt=\"".SUBMIT."\" name=\"Submit\" />";
echo "</p>
                  </td>
                </tr>
              </table>
     </form></td></tr></table>";

echo "<script language=\"JavaScript\" type=\"text/javascript\">
/* <![CDATA[ */
	var currentcntry = document.shippingform.destcntry.value;
	function makechange() {
		if (document.shippingform.destcntry.value != window.currentcntry) {
			switchStates(document.shippingform.deststate,document.shippingform.destprovince,document.shippingform.destcntry.value);
			window.currentcntry = document.shippingform.destcntry.value;
		}
	}
	var countryinterval = window.setInterval(\"makechange()\",1000);
/* ]]> */
</script>";

if ($mode != "include") {
	if ($wholesale) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wscart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/wscart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/wscart.html");
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	}
}
?>