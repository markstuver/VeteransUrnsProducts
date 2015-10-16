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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

if ($customermustregister == "1" && empty($_COOKIE["customersessionid"]) && empty($redirect)) {
	header("Location: signupform.php?action=checkout");
	exit;
}

// Initialize variables...
if (!isset($cat)) $cat = 0;
if (!isset($exp)) $exp = 0;
if (!isset($lang)) $lang = "";
if (!isset($usethemebuttons)) $usethemebuttons = "";
if (!isset($usethemetemplates)) $usethemetemplates = "";
if (!isset($themelanguages)) $themelanguages = "";
if (!isset($returnurl)) $returnurl = "";
if (strstr($returnurl, "discount.php")) $returnurl = "";
if (!isset($error)) $error = 0;
if (!isset($id)) $id = 0;
if (!isset($discountcode)) $discountcode = "";
if (!isset($taxandshipping)) $taxandshipping = "";
if (!isset($payoption)) $payoption = 0;
if (!isset($shipid)) $shipid = 0;
if (!isset($taxandshippingcost)) $taxandshippingcost = "";
if (!isset($shippingfirstname)) $shippingfirstname = "";
if (!isset($shippinglastname)) $shippinglastname = "";
if (!isset($shippingaddress)) $shippingaddress = "";
if (!isset($shippingcity)) $shippingcity = "";
if (!isset($shippingzip)) $shippingzip = "";
if (!isset($shippingstate)) $shippingstate = "";
if (!isset($shippingcountry)) $shippingcountry = "";
if (!isset($shippingphone)) $shippingphone = "";
if (!isset($shippingemail)) $shippingemail = "";
if (!isset($affiliate)) $affiliate = 0;
if (empty($pappath) && !is_numeric($affiliate)) $affiliate = 0;
if (!isset($upsellitems)) $upsellitems = 0;
if (!isset($upsold)) $upsold = 0;
if (isset($optin) && $optin != "true" && $optin != "false") $optin = "";
if (isset($party) && !is_numeric($party)) $party = "";
if (isset($orderid) && !is_numeric($orderid)) $orderid = "";
if (!isset($referral)) $referral = "";
if (isset($referral) && !is_numeric($referral)) $referral = "";
$tempcookie = array();
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";
if (!is_numeric($cat)) $cat = 0;
$checkexp = str_replace("|","",$exp);
if (!is_numeric($checkexp)) $exp = 0;
$basket = urldecode($basket);
$basket = html_entity_decode($basket);
$basket = str_replace("<","",$basket);
$basket = str_replace(">","",$basket);
$basket = str_replace("\"","",$basket);
if (isset($returnurl) && !ashop_is_url($returnurl)) unset($returnurl);
if (!empty($sid) && !ashop_is_md5($sid)) $sid = "";
if (!empty($shop) && !is_numeric($shop)) $shop = "";
if (empty($shop) || $shop == 0) {
	$shop = 1;
	$shopurlstring = "";
	$shophtmlstring = "";
	$shopredirect = "";
} else {
	$shopurlstring = "&shop=$shop";
	$shophtmlstring = "&amp;shop=$shop";
	$shopredirect = "|shop=$shop";
}

// Combine the same products in the basket cookie...
$basket = ashop_combineproducts($basket);

// Use relative paths and escape &-characters in returnurl...
$returnurl = str_replace("$ashopurl/","",$returnurl);
$returnurl = str_replace("$ashopsurl/","",$returnurl);
$returnurl = str_replace("&","|",$returnurl);

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/checkout.inc.php";

// Check for sale agreement...
if (file_exists("$ashoppath/agreement-$lang.txt")) $agreementexists = TRUE;
else if (file_exists("$ashoppath/agreement.txt")) $agreementexists = TRUE;
else $agreementexists = FALSE;

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/checkout.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

// Get customer profile and price level...
if (!empty($_COOKIE["customersessionid"])) {
	$customerresult = @mysqli_query($db,"SELECT level, firstname, lastname FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$pricelevel = @mysqli_result($customerresult,0,"level");
} else $pricelevel = 0;
if ($pricelevel > 0) {
	$templatefile = "wscheckout";
	$sourcetext = "Wholesale Cart";
	$wholesale = "1";
} else {
	$templatefile = "checkout";
	$sourcetext = "Shopping Cart";
	$wholesale = "0";
}

// Update opt in for this order...
if ($optin == "true" && !empty($orderid)) {
	@mysqli_query($db,"UPDATE orders SET allowemail='1' WHERE orderid='$orderid'");
	exit;
} else if ($optin == "false" && !empty($orderid)) {
	@mysqli_query($db,"UPDATE orders SET allowemail='0' WHERE orderid='$orderid'");
	exit;
}

// Update party ID for this order...
if (isset($party)) {
	if (!empty($orderid)) {
		$checkpartyorder = @mysqli_query($db,"SELECT orderid FROM orders WHERE orderid='$orderid' AND (date='' OR date IS NULL)");
		if ($party == "0") {
			if (@mysqli_num_rows($checkpartyorder)) @mysqli_query($db,"UPDATE orders SET partyid=NULL WHERE orderid='$orderid'");
		} else {
			if (@mysqli_num_rows($checkpartyorder)) @mysqli_query($db,"UPDATE orders SET partyid='$party' WHERE orderid='$orderid'");
		}
	}
	exit;
}

// Check if the customer's IP or email is banned...
$ipnumber = $_SERVER["REMOTE_ADDR"];
$bannedcheck = @mysqli_query($db,"SELECT * FROM customerblacklist WHERE blacklistitem='$ipnumber'");
if (@mysqli_num_rows($bannedcheck)) {
	$basket = "";
	$taxandshipping = "";
}

// Get currency rate if needed...
if (isset($curr) && preg_match("/^[a-z]*$/", $curr) && strlen($curr) == 3 && $curr != $ashopcurrency) $crate = getcurrency($curr);
else {
	$curr = "";
	$crate = 0;
}

if (isset($_POST["relay"])) {
	if ($cancel_x) {
		if (isset($_POST["returnurl"])) {
			$returnurl = str_replace("|","&",$_POST["returnurl"]);
			header("Location: $returnurl");
		}
		else header("Location: index.php");
		exit;
	}
	if ($agreementexists && !$agree_x) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
		echo "<br /><br /><p align=\"center\"><span class=\"ashopcheckoutagreement\"><b>".PURCHASEAGREEMENT."</b></span><br /><br /><table class=\"ashopcheckoutagreementtable\"><tr><td colspan=\"2\" class=\"ashopcheckoutagreement\">";
		if (file_exists("$ashoppath/agreement-$lang.txt")) include "$ashoppath/agreement-$lang.txt";
		else include("$ashoppath/agreement.txt");
		echo "<form method=\"POST\" action=\"$relay\">";
		foreach($_POST as $fieldname=>$fieldvalue) echo "<input type=\"hidden\" name=\"$fieldname\" value=\"$fieldvalue\">";
		echo "<input type=\"hidden\" name=\"securitykey\" value=\"".md5($ashoppath)."\"></td></tr><tr><td width=\"50%\" align=\"left\"><a href=\"$ashopurl\"><img src=\"images/notaccept-$lang.png\" class=\"ashopbutton\" border=\"0\"></a></td><td width=\"50%\" align=\"right\"><input type=\"image\" src=\"images/accept-$lang.png\" class=\"ashopbutton\" name=\"agree\"></td></tr></table></form></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
		exit;
	}
	if ($_POST["relay"] == "https://www.sfipay.com/handle.php") {
		$payoptarray = explode("?",$return_url);
		$payopt = explode("=",$payoptarray[1]);
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("payopt",$payopt[1]);
	} else if ($_POST["relay"] == "http://www.payso.com/sales.php") {
		$relay = "{$_POST["relay"]}?OA={$_POST["OA"]}&amount={$_POST["payso_amount"]}&email=1&ship=1&postback=1&cur=US&url={$_POST["url"]}";
	}
	// Make sure the page isn't stored in the browsers cache...
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	echo "<html><head><title>".REDIRECTSERV."</title>\n".CHARSET."</head><body onload=\"document.forms[0].submit()\"><form method=\"POST\" action=\"$relay\">";
	foreach($_POST as $fieldname=>$fieldvalue) if($fieldname != "relay" && $fieldname != "x" && $fieldname != "y") echo "<input type=\"hidden\" name=\"$fieldname\" value=\"$fieldvalue\">";
	echo "<input type=\"hidden\" name=\"securitykey\" value=\"".md5($ashoppath)."\"></form></body></html>";
	exit;
}

// Remove preliminary order...
if ($redirect) {
	$redirect = str_replace("|","&",$redirect);
	$redirect = str_replace("\n","",$redirect);
	$redirect = str_replace("\r","",$redirect);
	if ($id) {
		settype($id, 'integer');
		$result = @mysqli_query($db,"SELECT date FROM orders WHERE orderid='$id'");
		$checkdate = @mysqli_result($result,0,"date");
		if (!$checkdate) {
			$sql = "DELETE FROM orders WHERE orderid='$id'";
			$result = @mysqli_query($db,$sql);
		}
	}
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	if (!strstr($redirect,"http")) $redirect = $ashopurl."/$redirect";
	setcookie("fixbackbutton", "");
	header("Location: $redirect");
	exit;
}

// Check for storewide discounts...
$result = @mysqli_query($db,"SELECT * FROM storediscounts LIMIT 1");
if (@mysqli_num_rows($result)) $storediscounts = TRUE;
else $storediscounts = FALSE;

// Check for per product discounts...
$result = @mysqli_query($db,"SELECT * FROM discount LIMIT 1");
if (@mysqli_num_rows($result)) $perproductdiscounts = TRUE;
else $perproductdiscounts = FALSE;

// Start session for product or category discounts...
if ($perproductdiscounts || $storediscounts) session_start();

// Apply storewide discount if submitted...
$storediscountamount = 0;
$shippingdiscountamount = 0;
if ($storediscounts) {
	if ($discountcode) {
		$result = @mysqli_query($db,"SELECT * FROM storediscounts WHERE code='$discountcode' AND (categoryid='' OR categoryid IS NULL)");
		if (@mysqli_num_rows($result)) {
			$discountcustomer = @mysqli_result($personaldiscountresult, 0, "customerid");

			// Check if this customer is allowed to use the personal discount...
			if (!empty($discountcustomer) && is_numeric($discountcustomer)) {
				if (!empty($_COOKIE["customersessionid"])) {
					$discountcustomerresult = @mysqli_query($db,"SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
					$discountcustomerid = @mysqli_result($discountcustomerresult,0,"customerid");
					
					if (empty($discountcustomerid)) {
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
						echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".ONETIMENOTLOGGEDIN."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
						exit;
					}

					if ($discountcustomerid != $discountcustomer) {
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
						echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".NOTALLOWED."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
						exit;
					}
				}
			}

			$discountall = @mysqli_result($result, 0, "discountid");
			setcookie("discountall","$discountall");
			$discounttype = @mysqli_result($result, 0, "type");
			if ($discounttype == "$") $storediscountamount = @mysqli_result($result, 0, "value");
			else if ($discounttype == "s") {
				$shippingarray = ashop_gethandlingcost($taxandshipping);
				$shippingdiscountamount = $shippingarray["shipping"];
			}
			$discountaffiliate = @mysqli_result($result, 0, "affiliate");
			if ($discountaffiliate) {
				setcookie("affiliate","$discountaffiliate", mktime(0,0,0,12,1,2020));
				$affiliate = $discountaffiliate;
			}
			$discountcustomer = @mysqli_result($result, 0, "customerid");
			$sid = "";
		}
	} 
	
	if ($discountall) {
		// Apply amount discount...
		$storediscountresult = @mysqli_query($db,"SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='$' AND (categoryid='' OR categoryid IS NULL)");
		if (@mysqli_num_rows($storediscountresult)) {
			$storediscountrow = @mysqli_fetch_array($storediscountresult);
			if ($storediscountrow["value"]) $storediscountamount = $storediscountrow["value"];
		}

		// Apply shipping discount...
		$storediscountresult = @mysqli_query($db,"SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='s' AND (categoryid='' OR categoryid IS NULL)");
		if (@mysqli_num_rows($storediscountresult)) {
			$shippingarray = ashop_gethandlingcost($taxandshipping);
			$shippingdiscountamount = $shippingarray["shipping"];
		}
	}
}

// Apply per product discount if submitted...
if ($perproductdiscounts) {
	if ($discountcode) {
		// Get customer ID in case this is a personal or one time discount...
		if (!empty($_COOKIE["customersessionid"])) {
			$onetimecustomerresult = @mysqli_query($db,"SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
			$onetimecustomerid = @mysqli_result($onetimecustomerresult,0,"customerid");
		}
		$sql="SELECT * FROM discount WHERE code='$discountcode'";
		$result = @mysqli_query($db,"$sql");
		while ($discountrow = @mysqli_fetch_array($result)) {
			$discountid = $discountrow["discountid"];
			$discountaffiliate = $discountrow["affiliate"];
			if ($discountaffiliate) {
				setcookie("affiliate","$discountaffiliate", mktime(0,0,0,12,1,2020));
				$affiliate = $discountaffiliate;
			}
			$thisdiscountcustomer = $discountrow["customerid"];
			// Check if this is a personal discount...
			if (empty($thisdiscountcustomer)) {
				// Check if there are other discounts with this code that are not personal...
				$checkpersonal1 = @mysqli_query($db,"SELECT * FROM discount WHERE code='$discountcode' AND (customerid='' OR customerid IS NULL)");
				if (!@mysqli_num_rows($checkpersonal1)) {
					// This is a personal only discount. Check if the customer is logged in...
					if (empty($onetimecustomerid)) {
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
						echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".ONETIMENOTLOGGEDIN."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
						exit;
					}

					// The customer is logged in. Check if this customer is allowed to use the discount...
					if ($onetimecustomerid != $thisdiscountcustomer) {

						// Not the right customer, but there could be a similar code for this customer...
						$checkpersonal2 = @mysqli_query($db,"SELECT * FROM discount WHERE code='$discountcode' AND customerid='$onetimecustomerid'");
						if (!@mysqli_num_rows($checkpersonal2)) {
							if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
							else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
							echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".NOTALLOWED."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
							if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
							else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
							exit;
						}
					}
				}
			}
			$discountproductid = $discountrow["productid"];
			// Remove any other discounts for this product...
			if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$olddiscountid = str_replace("discount","",$cookiename);
					$oldproductdiscounts = @mysqli_query($db,"SELECT * FROM discount WHERE productid='$discountproductid' AND discountid='$olddiscountid'");
					if (@mysqli_num_rows($oldproductdiscounts)) $_SESSION["discount$olddiscountid"] = "";
				}
			}
			$discountcookiestring = md5($discountproductid.$discountcode."ashopdiscounts");
			if ($onetime) {
				if (!empty($onetimecustomerid)) {
					if (!empty($onetimecustomerid) && is_numeric($onetimecustomerid)) {
						$discountresult = @mysqli_query($db,"SELECT * FROM onetimediscounts WHERE customerid='$onetimecustomerid' AND discountid='$discountid'");
						if (!@mysqli_num_rows($discountresult)) {
							@mysqli_query($db,"INSERT INTO onetimediscounts (customerid,discountid) VALUES ('$onetimecustomerid','$discountid')");
							if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
							$p3psent = TRUE;
							$_SESSION["discount$discountid"] = $discountcookiestring;
						} else {
							if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
							$p3psent = TRUE;
							$_SESSION["discount$discountid"] = "";
							unset($discount);
						}
					} else {
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
						echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">Error</span><br /><br /><span class=\"ashopmessage\">".ONETIMENOTLOGGEDIN."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
						if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
						else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
						exit;
					}
				} else {
					if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
					else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
					echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">Error</span><br /><br /><span class=\"ashopmessage\">".ONETIMENOTLOGGEDIN."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
					if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
					else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
					exit;
				}
			} else {
				if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
				$p3psent = TRUE;
				$_SESSION["discount$discountid"] = $discountcookiestring;
			}
			eval("\$discount$discountid = \"$discountcookiestring\";");
			$tempcookie["discount$discountid"] = $discountcookiestring;
		}
	}
}

/* Fetch details for prerequisite discounts...
$prereqdiscountcodes = array();
foreach ($_COOKIE as $cookiename=>$cookievalue) {
	if (substr($cookiename,0,14) == "prereqdiscount") {
		$prereqdiscountid = str_replace("prereqdiscount","",$cookiename);
		$result = @mysqli_query($db,"SELECT code FROM storediscounts WHERE discountid='$prereqdiscountid'");
		$prereqdiscountcode = @mysqli_result($result,0,"code");
		$prereqdiscounthash = md5($prereqdiscountcode."ashopdiscounts");
		if ($cookievalue == $prereqdiscounthash) $prereqdiscountcodes[] = $prereqdiscountcode;
	}
}*/

// Check loyalty rewards...
if (!empty($virtualcashamount) && is_numeric($virtualcashamount) && !empty($_COOKIE["customersessionid"]) && !empty($virtualcashpercent)) {
	$customerwalletresult = @mysqli_query($db,"SELECT virtualcash FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$customerwallet = @mysqli_result($customerwalletresult,0,"virtualcash");
	if ($virtualcashamount > $customerwallet) $virtualcashamount = $customerwallet;
} else $virtualcashamount = 0;

// Parse shopping cart string...
$newbasket = ashop_nozeroqty($basket);
$newbasket = ashop_applydiscounts($db, $newbasket);
if ($memberpayoptions) {
	$newbasket = ashop_memberproductstring($db, $newbasket, $shop);
	$basket = $newbasket;
	if (empty($basket)) $taxandshipping = 0;
}
$itemcountarray = explode("a",$newbasket);
if (!empty($itemcountarray) && is_array($itemcountarray)) $itemcount = count($itemcountarray);
$productsincart = ashop_parseproductstring($db, $newbasket);

// Calculate subtotal...
$subtotal = 0;
$recurringtotal = 0;
$description = "";
$checkoutdescription = "";
$detailedcheckoutdescription = "";
$isproductstring = "Price::Qty::ProductID::Product::Currency|";
$twocoproductstring = "";
$paypalproductstring = "";
$nabtransactproductstring = "";
$totalqty = ashop_totalqty($newbasket);
$membershops = array();
$fpupdate = array();
$fpmanipulated = FALSE;
$doupsell = FALSE;
$discountsstring = "";
$nmi_prodnumber = 1;
$nmi_recurringstring = "";
$showshippingform = "";
$discountableitems = array();
$prereqdiscounts = array();
$prereqcodeslist = "";
if (!empty($prereqdiscountcodes)) {
	foreach ($prereqdiscountcodes as $prereqdiscountcode) $prereqcodeslist .= $prereqdiscountcode.",";
	$prereqcodeslist = substr($prereqcodeslist,0,-1);
}
if ($productsincart) {
	foreach($productsincart as $productnumber => $thisproduct) {
		$productid = $thisproduct["productid"];
		$quantity = $thisproduct["quantity"];
		$price = $thisproduct["price"];
		if ($pricelevel < 1) $price = $thisproduct["price"];
		else if ($pricelevel == 1) $price = $thisproduct["wholesaleprice"];
		else {
			$pricelevels = $thisproduct["wspricelevels"];
			$price = $pricelevels[$pricelevel-2];
		}

		// Apply per category discount if submitted...
		$thisproductdiscount = "0";
		if ($storediscounts) {
			if ($discountcode || !empty($prereqcodeslist)) {
				// Get customer ID in case this is a personal or one time discount...
				if (!empty($_COOKIE["customersessionid"])) {
					$onetimecustomerresult = @mysqli_query($db,"SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
					$onetimecustomerid = @mysqli_result($onetimecustomerresult,0,"customerid");
				}
				$categoriesresult = @mysqli_query($db,"SELECT categoryid FROM productcategory WHERE productid='$productid'");
				$categorieslist = "";
				while ($categoriesrow = @mysqli_fetch_array($categoriesresult)) $categorieslist .= "'{$categoriesrow["categoryid"]}',";
				$categorieslist = substr($categorieslist,0,-1);
				if (!empty($categorieslist)) {
					if (!empty($prereqcodeslist) && !empty($discountcode)) {
						if (strstr($prereqcodeslist,",")) $result = @mysqli_query($db,"SELECT * FROM storediscounts WHERE (code='$discountcode' OR code IN ($prereqcodeslist)) AND categoryid IN ($categorieslist)");
						else $result = @mysqli_query($db,"SELECT * FROM storediscounts WHERE (code='$discountcode' OR code='$prereqcodeslist') AND categoryid IN ($categorieslist)");
					} else if (!empty($prereqcodeslist)) {
						if (strstr($prereqcodeslist,",")) $result = @mysqli_query($db,"SELECT * FROM storediscounts WHERE code IN ($prereqcodeslist) AND categoryid IN ($categorieslist)");
						else $result = @mysqli_query($db,"SELECT * FROM storediscounts WHERE code='$prereqcodeslist' AND categoryid IN ($categorieslist)");
					} else $result = @mysqli_query($db,"SELECT * FROM storediscounts WHERE code='$discountcode' AND categoryid IN ($categorieslist)");
				}
				while ($discountrow = @mysqli_fetch_array($result)) {
					$discountid = $discountrow["discountid"];
					$thisdiscountcustomer = $discountrow["customerid"];
					$thisdiscountaffiliate = $discountrow["affiliate"];
					$discounttype = $discountrow["type"];
					if ($discounttype == "i" && !array_key_exists($discountid,$prereqdiscounts)) {
						$prereqdiscounts[$discountid]["value"] = intval($discountrow["value"]);
						$prereqdiscounts[$discountid]["prerequisite"] = $discountrow["prerequisite"];
					}
					// Check if this is a personal discount...
					if (empty($thisdiscountcustomer)) {
						// Check if there are other discounts with this code that are not personal...
						$checkpersonal1 = @mysqli_query($db,"SELECT * FROM storediscounts WHERE code='$discountcode' AND categoryid IN ($categorieslist) AND customerid='' OR customerid IS NULL");
						if (!@mysqli_num_rows($checkpersonal1)) {
							// This is a personal only discount. Check if the customer is logged in...
							if (empty($onetimecustomerid)) {
								if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
								else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
								echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".ONETIMENOTLOGGEDIN."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
								if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
								else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
								exit;
							}
							
							// The customer is logged in. Check if this customer is allowed to use the discount...
							if ($onetimecustomerid != $thisdiscountcustomer) {

								// Not the right customer, but there could be a similar code for this customer...
								$checkpersonal2 = @mysqli_query($db,"SELECT * FROM storediscounts WHERE code='$discountcode' AND categoryid IN ($categorieslist) AND customerid='$onetimecustomerid'");
								if (!@mysqli_num_rows($checkpersonal2)) {
									if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
									else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
									echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".NOTALLOWED."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
									if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
									else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
									exit;
								}
							}
						}
					}
					$discountcookiestring = md5($discountcode."ashopdiscounts");
					if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
					$p3psent = TRUE;
					if ($thisdiscountaffiliate) {
						setcookie("affiliate","$discountaffiliate", mktime(0,0,0,12,1,2020));
						$affiliate = $thisdiscountaffiliate;
					}
					if ($discounttype != "i") {
						$_SESSION["discount$discountid"] = $discountcookiestring;
						eval("\$discount$discountid = \"$discountcookiestring\";");
						$tempcookie["discount$discountid"] = $discountcookiestring;
						$thisproductdiscount = $discountcookiestring;
						$thisproduct["discounted"] = "true";
					} else {
						if ($discountcode) {
							setcookie("prereqdiscount$discountid", $discountcookiestring);
							eval("\$prereqdiscount$discountid = \"$discountcookiestring\";");
							$tempcookie["prereqdiscount$discountid"] = $discountcookiestring;
						}
						for ($discountable = 1; $discountable <= $quantity; $discountable++) $discountableitems[$discountid][] = $price;
					}
				}
			}
		}

		$billtemplate = $thisproduct["billtemplate"];
		if (!$billtemplate) {
			$recurringprice = $thisproduct["recurringprice"];
			$productrecurringperiod = $thisproduct["recurringperiod"];
		}
		$parameters = $thisproduct["parameters"];
		$name = $thisproduct["name"];
		$twoco_description = strip_tags($thisproduct["description"]);
		$twoco_description = substr($twoco_description,0,254);
		$twoco_name = substr($name,0,127);
		$type = $thisproduct["type"];
		$skucode = $thisproduct["sku"];
		$useinventory = $thisproduct["useinventory"];
		$checksaasuinventory = "";
		if ($useinventory && $saasuwsaccesskey && $saasufileid) {
			$checksaasuinventory = ashop_saasu_getinventory($skucode);
			if ($checksaasuinventory != "nodata") {
				$inventory = $checksaasuinventory;
				if ($type) {
					if ($inventory < $thisproduct["inventory"]) {
						$subtractfrominventory = $thisproduct["inventory"] - $inventory;
						@mysqli_query($db,"UPDATE productinventory SET inventory = '$inventory' WHERE productid='$productid' AND type='$type'");
						@mysqli_query($db,"UPDATE product SET inventory = inventory-'$subtractfrominventory' WHERE productid='$productid'");
					} else if ($inventory > $thisproduct["inventory"]) {
						$addtoinventory = $thisproduct["inventory"] - $inventory;
						@mysqli_query($db,"UPDATE productinventory SET inventory = '$inventory' WHERE productid='$productid' AND type='$type'");
						@mysqli_query($db,"UPDATE product SET inventory = inventory+'$addtoinventory' WHERE productid='$productid'");
					}
				} else @mysqli_query($db,"UPDATE product SET inventory = '$inventory' WHERE productid='$productid'");
			} else $inventory = $thisproduct["inventory"];
		} else $inventory = $thisproduct["inventory"];
		$twocoproductstring .= "<input type=\"hidden\" name=\"c_prod_{$productnumber}\" value=\"$productid,$quantity\">
		<input type=\"hidden\" name=\"c_name_{$productnumber}\" value=\"$twoco_name\">
		<input type=\"hidden\" name=\"c_description_{$productnumber}\" value=\"$twoco_description\">
		<input type=\"hidden\" name=\"c_price_{$productnumber}\" value=\"".number_format($price,2,'.','')."\">";

		$nabtransactproductstring .= "<input type=\"hidden\" name=\"$twoco_name\" value=\"$quantity,".number_format($price,2,'.','')."\">\n";

		// Check that the product hasn't been sold out...
		if ($useinventory && !$inventory) {
			// Sorry sold out...
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
			echo "<br /><br /><p align=\"center\"><span class=\"ashopmessage\">".OUTOFSTOCK."<br /><br /><b>$name";
			if ($parameters) echo " $parameters";
			echo "</b><br /><br /><a href=\"basket.php\">".CHANGECART1."</a> ".CHANGECART2."</span></p>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
			exit;
		} else if ($useinventory && $inventory < $quantity) {
			// Sorry not enough...
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
			echo "<br /><br /><p align=\"center\"><span class=\"ashopmessage\">".NOTENOUGHINSTOCK1."<br /><br /><b>$name";
			if ($parameters) echo " $parameters";
			echo "</b><br /><br />".NOTENOUGHINSTOCK2."<br /><br /><a href=\"basket.php\">".CHANGECART1."</a> ".CHANGECART2."</span></p>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
			exit;
		}

		// Check if this customer is allowed to buy this many items...
		if (!empty($thisproduct["qtylimit"]) && $thisproduct["qtylimit"] > 0) {
			if (!empty($_COOKIE["customersessionid"])) {
				$qtycustomerresult = @mysqli_query($db,"SELECT customerid FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
				$qtycustomerid = @mysqli_result($qtycustomerresult,0,"customerid");
			} else $qtycustomerid = "";
			if (empty($qtycustomerid)) {
				// Must be logged in...
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
				echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".MUSTLOGIN."<br /><br /><a href=\"$ashopurl/login.php\">".TRYAGAIN."</a>!</span></p>";
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
				else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
				exit;
			}
			else if ($quantity > $thisproduct["qtylimit"]) {
				// Already too many of this item in the cart...
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
				echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".NOTALLOWEDQTY." <b>$name</b>.<br /><br /><a href=\"$ashopurl/basket.php\">".TRYAGAIN."</a>!</span></p>";
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
				else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
				exit;
			}
			// Check if this customer has already bought this item before...
			else {
				$previouslybought = 0;
				$checkproductstring = "b".$productid."a";
				$previouspurchaseresult = @mysqli_query($db,"SELECT products FROM orders WHERE date IS NOT NULL AND date != '' AND products LIKE '%$checkproductstring%' AND customerid='$qtycustomerid'");
				while ($previouspurchaserow = @mysqli_fetch_array($previouspurchaseresult)) {
					$previouspurchaseproducts = $previouspurchaserow["products"];
					$previouslybought += ashop_checkproduct($productid, $previouspurchaseproducts, "");
				}
				if ($quantity+$previouslybought > $thisproduct["qtylimit"]) {
					if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
					else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
					echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".NOTALLOWEDQTY." <b>$name</b>.<br /><br /><a href=\"$ashopurl/basket.php\">".TRYAGAIN."</a>!</span></p>";
					if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
					else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
					exit;
				}
			}
		}
		if (!empty($thisproduct["qtytlimit"]) && $thisproduct["qtytlimit"] > 0) {
			if ($quantity > $thisproduct["qtytlimit"]) {
				// Too many of this item in the cart...
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
				echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">".ERROR."</span><br /><br /><span class=\"ashopmessage\">".NOTALLOWEDQTY." <b>$name</b>.<br /><br /><a href=\"$ashopurl/basket.php\">".TRYAGAIN."</a>!</span></p>";
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
				else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
				exit;
			}
		}

		// Check if this is a floating price auction and if this is the winner...
		if ($thisproduct["fpendprice"]) {
			if ($_COOKIE["bidderhash"] && ashop_checkbidcode($db, $_COOKIE["bidderhash"])) {
				$bidderhash = explode("|",$_COOKIE["bidderhash"]);
				$thisbidder = $bidderhash[0];
				if ($quantity > 1 || ($thisbidder != $thisproduct["fpwinner"] && $price != $thisproduct["endprice"])) {
					// Auction is not won or has been manipulated...
					$fpmanipulated = TRUE;
				} else {
					// This is the winner, let him buy the product...
					$fpupdate[] = $productid;
				}
			}
		}

		// Check if sales tax and/or shipping should be charged...
		$thisshipping = $thisproduct["shipping"];
		$thistaxable = $thisproduct["taxable"];
		$checksid = md5($basket.$taxandshipping.$ashoppath);

		// Check if a full shipping address has been supplied or just the basics for calculation...
		$checkfullshipping = @mysqli_query($db,"SELECT shippingfirstname,shippingaddress FROM shipping WHERE shippingid='$shipid' AND shippingfirstname IS NOT NULL AND shippingfirstname != '' AND shippingaddress IS NOT NULL AND shippingaddress != '' AND shippingzip IS NOT NULL AND shippingzip != '' AND shippingstate IS NOT NULL AND shippingstate != ''");
		$fullshippingavailable = @mysqli_num_rows($checkfullshipping);
		if (($thisshipping && !$thisproduct["disableshipping"])) {
			if ($fullshippingavailable) {
				if ($checksid != $sid) {
					// Shipping profile is available, redirect to shipping.php...
					if ($returnurl) header("Location: shipping.php?cal=true&action=checkout&returnurl=$returnurl&payoption=$payoption");
					else header("Location: shipping.php?cal=true&action=checkout&cat=$cat&payoption=$payoption$shopurlstring");
					exit;
				}
			} else $showshippingform = "full";
		}

		// Check if sales tax should be charged...
		$checktaxshipping = @mysqli_query($db,"SELECT shippingfirstname,shippingaddress FROM shipping WHERE shippingid='$shipid' AND shippingzip IS NOT NULL AND shippingzip != '' AND shippingstate IS NOT NULL AND shippingstate != '' AND shippingcountry IS NOT NULL AND shippingcountry != ''");
		$taxshippingavailable = @mysqli_num_rows($checktaxshipping);
		if (empty($showshippingform) && $thistaxable && !$thisproduct["disabletax"] && $checksid != $sid) {
			// Shipping calculation is needed...
			if ($taxshippingavailable) {
				// Shipping profile is available, redirect to shipping.php...
				if ($returnurl) header("Location: shipping.php?cal=true&action=checkout&returnurl=$returnurl&payoption=$payoption");
				else header("Location: shipping.php?cal=true&action=checkout&cat=$cat&payoption=$payoption$shopurlstring");
				exit;
			} else if (empty($showshippingform)) $showshippingform = "short";
		}

		// Check if related products should be offered...
		if ($upsellitems > $upsold && is_array($thisproduct["upsell1"])) $doupsell = TRUE;
	
		// Get the products membershopid from the database...
		$thismembershop = $thisproduct["userid"];
		if (!is_array($membershops) || !in_array($thismembershop, $membershops)) $membershops[] = $thismembershop;

		// Check discounts...
		if ($thisproduct["discounted"] == "true") {
			if (isset($_SESSION) && is_array($_SESSION)) foreach ($_SESSION as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$sql="SELECT * FROM discount WHERE productid='$productid' AND discountid='$discountid'";
					$result2 = @mysqli_query($db,"$sql");
					if (@mysqli_num_rows($result2)) {
						$thisproductdiscount = $cookievalue;
						if (!empty($discountsstring)) $discountsstring .= "|";
						$discountsstring .= "$productid:$discountid";
					} else {
						$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
						$result2 = @mysqli_query($db,"$sql");
						if (@mysqli_num_rows($result2)) {
							$discountcategory = @mysqli_result($result2, 0, "categoryid");
							$result3 = @mysqli_query($db,"SELECT * FROM productcategory WHERE productid='$productid' AND categoryid='$discountcategory'");
							if (@mysqli_num_rows($result3)) {
								$thisproductdiscount = $cookievalue;
								if (!empty($discountsstring)) $discountsstring .= "|";
								$discountsstring .= "$productid:$discountid";
							}
						}
					}
				}
			}
			if (isset($tempcookie) && is_array($tempcookie)) foreach ($tempcookie as $cookiename=>$cookievalue) {
				if (strstr($cookiename,"discount")) {
					$discountid = str_replace("discount","",$cookiename);
					$sql="SELECT * FROM discount WHERE productid='$productid' AND discountid='$discountid'";
					$result2 = @mysqli_query($db,"$sql");
					if (@mysqli_num_rows($result2)) {
						$thisproductdiscount = $cookievalue;
						if (!empty($discountsstring)) $discountsstring .= "|";
						$discountsstring .= "$productid:$discountid";
					} else {
						$sql="SELECT * FROM storediscounts WHERE discountid='$discountid' AND categoryid!='' AND categoryid IS NOT NULL";
						$result2 = @mysqli_query($db,"$sql");
						if (@mysqli_num_rows($result2)) {
							$discountcategory = @mysqli_result($result2, 0, "categoryid");
							$result3 = @mysqli_query($db,"SELECT * FROM productcategory WHERE productid='$productid' AND categoryid='$discountcategory'");
							if (@mysqli_num_rows($result3)) {
								$thisproductdiscount = $cookievalue;
								if (!empty($discountsstring)) $discountsstring .= "|";
								$discountsstring .= "$productid:$discountid";
							}
						}
					}
				}
			}
		}

		// Show discount info...
		if ($thisproductdiscount != "0" || $thisproduct["storewidediscount"] != "false") $name = "$name (".DISCOUNTED.")";

		// Calculate subtotal...
		if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $quantity;
		else {
			if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
			else $subtotalqty = ashop_categoryqty($db, $newbasket, $thisproduct["qtycategory"]);
		}
		$thistotal = ashop_subtotal($db, $productid, $subtotalqty, $quantity, $thisproductdiscount, $price, $thisproduct["qtytype"]);
		$subtotal += $thistotal;
		$pricetext = "";
		if ($thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "2") $price = $thistotal/$quantity;
		else if ($thisproduct["qtytype"] == "3" || $thisproduct["qtytype"] == "4") $pricetext = $thisproduct["pricetext"];			
		else $price = ashop_subtotal($db, $productid, $subtotalqty, 1, $thisproductdiscount, $price, $thisproduct["qtytype"]);

		// Create PayPal item parameters...
		$pp_productnumber = $productnumber+1;
		$pp_price = $thistotal/$quantity;
		$paypalproductstring .= "
		<input type=\"hidden\" name=\"quantity_{$pp_productnumber}\" value=\"$quantity\">
		<input type=\"hidden\" name=\"item_name_{$pp_productnumber}\" value=\"$name\">
		<input type=\"hidden\" name=\"amount_{$pp_productnumber}\" value=\"".number_format($pp_price,2,'.','')."\">";

		// Calculate recurring total...
		if ($recurringprice) {
			$nmi_recurringstring .= "&product_sku_{$nmi_prodnumber}={$skucode}";
			$nmi_prodnumber++;
			$recurringtotal += $recurringprice*$quantity;
		}

		$description .= "$quantity: $name";
		// Get product image info...
		$productimage = ashop_productimages($productid);
		if ($productimage["thumbnail"]) {
			$checkoutdescription .= "<a href=\"javascript:void(0);\" onmousemove=\"return overlibImage('','prodimg/$productid/{$productimage["thumbnail"]}');\" onmouseout=\"return overlibMouseout();\"><img src=\"images/thumbnail.gif\" border=\"0\" align=\"absbottom\"></a> $quantity: $name";
			if ($showimagesincart) $detailedcheckoutdescription .= "<tr><td width=\"$thumbnailwidth\"><img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" alt=\"$name\" width=\"$thumbnailwidth\"></td><td align=\"center\"><span class=\"ashopcartcontents\">$quantity</span></td>
			<td align=\"left\"><span class=\"ashopcartcontents\">$name";
			else $detailedcheckoutdescription .= "<tr><td align=\"center\"><span class=\"ashopcartcontents\">$quantity</span></td>
			<td align=\"left\"><span class=\"ashopcartcontents\">$name";
		} else {
			$checkoutdescription .= "$quantity: $name";
			$detailedcheckoutdescription .= "<tr>";
			if ($showimagesincart) $detailedcheckoutdescription .= "<td>&nbsp;</td>";
			$detailedcheckoutdescription .= "<td align=\"center\"><span class=\"ashopcartcontents\">$quantity</span></td>
			<td align=\"left\"><span class=\"ashopcartcontents\">$name";
		}
		//if ($thisproduct["discounted"] == "true") $detailedcheckoutdescription .= " (".DISCOUNTED.")";
		if ($parameters) {
			$description .= " $parameters";
			$checkoutdescription .= " $parameters";
			$detailedcheckoutdescription .= " $parameters";
		}
		$isproductstring .= number_format($price,2,'.','')."::$quantity::$productid::$name::{US}";
		$description .= ", ";
		$checkoutdescription .= "<br />";
		// Convert currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$tempcurrency = $ashopcurrency;
			$ashopcurrency = $curr;
			$tempprice = $price;
			$price = $price*$crate;
			$tempthistotal = $thistotal;
			$thistotal = $thistotal*$crate;
		}	
		$detailedcheckoutdescription .= "</span></td><td align=\"right\" width=\"120\"><span class=\"ashopcartcontents\">";
		if (!empty($pricetext)) $detailedcheckoutdescription .= $pricetext;
		else $detailedcheckoutdescription .= $currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
		$detailedcheckoutdescription .= "</span></td><td align=\"right\" width=\"70\"><span class=\"ashopcartcontents\">";
		$detailedcheckoutdescription .= $currencysymbols[$ashopcurrency]["pre"].number_format($thistotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
		$detailedcheckoutdescription .= "</span></td><td width=\"20\"><a href=\"basket.php?remove=";
		$detailedcheckoutdescription .= $productnumber+1;
		if ($returnurl) $detailedcheckoutdescription .= "&returnurl=$returnurl";
		else $detailedcheckoutdescription .= "&cat=$cat$shopurlstring";
		$detailedcheckoutdescription .= "\"><img src=\"images/icon_delete.png\" alt=\"".REMOVE."\" border=\"0\"></a></td></tr>";
		$isproductstring .= "|";
		// Convert back to main currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$ashopcurrency = $tempcurrency;
			$price = $tempprice;
			$thistotal = $tempthistotal;
		}
    }
	$description = substr($description,0,-2);
	$checkoutdescription = substr($checkoutdescription,0,-6);
	$isproductstring = substr($isproductstring,0,-1);
}

// Calculate prerequisite discounts...
$totalprereqdiscountamount = 0;
if (!empty($discountableitems)) {
	foreach ($discountableitems as $thisprereqdiscountid=>$thisprereqdiscount) {
		$thisprereqdiscountitems = count($thisprereqdiscount);
		$thisprereqdiscountamount = 0;
		if ($thisprereqdiscountitems > $prereqdiscounts[$thisprereqdiscountid]["prerequisite"]) {
			$thisprereqdiscountitems = $thisprereqdiscountitems-$prereqdiscounts[$thisprereqdiscountid]["prerequisite"];
			if ($thisprereqdiscountitems < $prereqdiscounts[$thisprereqdiscountid]["value"]) $thisprereqfreeitems = $thisprereqdiscountitems;
			else $thisprereqfreeitems = $prereqdiscounts[$thisprereqdiscountid]["value"];
			if ($thisprereqfreeitems > 0) {
				sort($thisprereqdiscount);
				for ($thisfreeitem = 0; $thisfreeitem < $thisprereqfreeitems; $thisfreeitem++) {
					$thisprereqdiscountamount += $thisprereqdiscount[$thisfreeitem];
				}
			}
		}
		$storediscountamount += $thisprereqdiscountamount;
		$totalprereqdiscountamount += $thisprereqdiscountamount;
	}
	if ($totalprereqdiscountamount > 0) setcookie("prereqdiscountamount", $totalprereqdiscountamount);
}

$subtotal -= $storediscountamount;
if ($subtotal <= 0) {
	$subtotal = 0;
	$virtualcashamount = 0;
}
if ($virtualcashamount > $subtotal) $virtualcashamount = $subtotal;
if (!empty($virtualcashamount)) $subtotal -= $virtualcashamount;

// Make sure all discounts are applied...
$newbasket = ashop_applydiscounts($db, $newbasket);

// Create a list of all membershops included in this order...
$shops = "";
if ($membershops) foreach ($membershops as $i => $membershopid) if ($membershopid) $shops .= "|$membershopid|";
$shops = str_replace("||","|",$shops);

// Extract and include tax and shipping...
if ($taxandshipping) {
  $items = explode("a", $taxandshipping);
  $arraycount = 1;
  $productcost = $subtotal;
  $taxandshippingcost = 0;
  if ($items[0] && count($items)==1) $arraycount = 0;
  for ($i = 0; $i < count($items)-$arraycount; $i++) {
	$thisitem = explode("b", $items[$i]);
	if ($thisitem[0] == "sh" || $thisitem[0] == "st" || $thisitem[0] == "sd") {
		$price = $thisitem[1];
		if($thisitem[0] == "sh" && $shippingdiscountamount) $price = 0;
		if($thisitem[0] == "sd") $subtotal -= $price;
		else if ($thisitem[0] == "st" && $displaywithtax == 2) { }
		else $subtotal += $price;
		if($thisitem[0] == "sd") $taxandshippingcost -= $price;
		else $taxandshippingcost += $price;
		if ($thisitem[0] == "sh") {
			$name = SHIPPING;
			$totalshipping = number_format($price,2,'.','');
		}
		if ($thisitem[0] == "st") {
			$name = SALESTAX;
			$totalsalestax = number_format($price,2,'.','');
		}
		if ($thisitem[0] == "sd") {
			$name = QTYDISCOUNT;
			$totalshippingddiscount = number_format($price,2,'.','');
		}
		$detailedcheckoutdescription .= "\n<tr>";
		if ($showimagesincart) $detailedcheckoutdescription .= "<td>&nbsp;</td>";
		$detailedcheckoutdescription .= "<td>&nbsp;</td>
		<td align=\"left\">
		<span class=\"ashopcartcontents\">$name</span></td>
		<td align=\"right\">
		<span class=\"ashopcartcontents\">";
		if ($thisitem[0] == "sd") $detailedcheckoutdescription .= "-";
		// Convert currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$tempcurrency = $ashopcurrency;
			$ashopcurrency = $curr;
			$tempprice = $price;
			$price = $price*$crate;
		}
		$detailedcheckoutdescription .= $currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span></td>
		<td align=\"right\"><span class=\"ashopcartcontents\">";
		if ($thisitem[0] == "sd") $detailedcheckoutdescription .= "-";
		$detailedcheckoutdescription .= $currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span></td>
		<td>&nbsp;</td></tr>";
		// Convert back to main currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$ashopcurrency = $tempcurrency;
			$price = $tempprice;
		}
	}
  }
  $newbasket = $newbasket.$taxandshipping;
  $isproductstring .= "|".number_format($taxandshippingcost,2,'.','')."::1::shp::Tax and Shipping::{US}";

  // Create PayPal tax parameter...
  if ($totalsalestax && $displaywithtax != 2) $paypalproductstring .= "
  <input type=\"hidden\" name=\"tax_cart\" value=\"$totalsalestax\">";
}

// Create PayPal discount parameter...
$totalstorediscount = $storediscountamount+$totalshippingddiscount+$virtualcashamount;
if ($totalstorediscount) $paypalproductstring .= "
<input type=\"hidden\" name=\"discount_amount_cart\" value=\"$totalstorediscount\">";


// Apply storewide discount on basket string if needed...
if ($discountall) $newbasket = $newbasket."D$discountall";

// Add storewide discount to detailed shopping cart view...
if ($storediscountamount) {
	// Convert currency...
	if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		$tempcurrency = $ashopcurrency;
		$ashopcurrency = $curr;
		$tempstorediscount = $storediscountamount;
		$storediscountamount = $storediscountamount*$crate;
	}
	$detailedcheckoutdescription .= "<tr>";
	if ($showimagesincart) $detailedcheckoutdescription .= "<td>&nbsp;</td>";
	$detailedcheckoutdescription .= "<td align=\"center\">&nbsp;</td>
	<td align=\"left\"><span class=\"ashopcartcontents\">Discount</span></td>
	<td align=\"right\" width=\"120\">&nbsp;</td>
	<td align=\"right\" width=\"70\"><span class=\"ashopcartcontents\">-";
	$detailedcheckoutdescription .= number_format($storediscountamount,$showdecimals,$decimalchar,$thousandchar);
	$detailedcheckoutdescription .= "</span></td><td width=\"50\">&nbsp;</td></tr>";
	// Convert back to main currency...
	if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
		$ashopcurrency = $tempcurrency;
		$storediscountamount = $tempstorediscount;
	}
}

// Get payment options...
if ($memberpayoptions) $payoptionowner = $shop;
else $payoptionowner = 1;
if ($recurringtotal) $limitgateways = " AND (gateway='payza' OR gateway='paypal' OR gateway='paypalsandbox' OR gateway='ccbill' OR gateway='networkmerchants' OR gateway='authorizenetaim' OR gateway='netbillingrecurring')";
else $limitgateways = "";
if ($pricelevel > 0) $sql="SELECT * FROM payoptions WHERE (emerchantonly = '' OR emerchantonly IS NULL OR emerchantonly = '0') AND (retailonly = '' OR retailonly IS NULL OR retailonly = '0') AND gateway!='googleco'$limitgateways AND userid='$payoptionowner'";
else $sql="SELECT * FROM payoptions WHERE (emerchantonly = '' OR emerchantonly IS NULL OR emerchantonly = '0') AND (wholesaleonly = '' OR wholesaleonly IS NULL OR wholesaleonly = '0') AND gateway!='googleco'$limitgateways AND userid='$payoptionowner'";
if ($payoption) $sql .= " AND payoptionid='$payoption'";
$sql .= " ORDER BY ordernumber";
if (!$showshippingform) $payoptionresult = @mysqli_query($db,"$sql");
$numberofpayoptions = @mysqli_num_rows($payoptionresult);
if ($numberofpayoptions == "1") {
	$gw = @mysqli_result($payoptionresult, 0, "gateway");
	$checkgw = $gw;
	// Redirect to up sell if needed...
	if ($doupsell && $checkgw != "3dlevelbilling" && $checkgw != "authorizenetaim" && $checkgw != "daopay" && $checkgw != "echo" && $checkgw != "iongate" && $checkgw != "linkpointform" && $checkgw != "manual" && $checkgw != "offline" && $checkgw != "viaklix" && $checkgw != "networkmerchants" && $checkgw != "eprocessingform" && $checkgw != "payflowpro" && $checkgw != "test" && $checkgw != "virtualmerchant" && $checkgw != "authnetecheckaim" && $checkgw != "quickbooks" && $checkgw != "paypaldp") {
		header("Location: index.php?specialoffer=true");
		exit;
	}
} else {
	for ($gwn = 0; $gwn <= @mysqli_num_rows($payoptionresult); $gwn++) {
		$checkgw = @mysqli_result($payoptionresult, $gwn, "gateway");
		// Redirect to up sell if needed...
		if ($doupsell && $checkgw != "3dlevelbilling" && $checkgw != "authorizenetaim" && $checkgw != "daopay" && $checkgw != "echo" && $checkgw != "iongate" && $checkgw != "linkpointform" && $checkgw != "manual" && $checkgw != "offline" && $checkgw != "viaklix" && $checkgw != "networkmerchants" && $checkgw != "eprocessingform" && $checkgw != "payflowpro" && $checkgw != "test" && $checkgw != "virtualmerchant" && $checkgw != "authnetecheckaim" && $checkgw != "quickbooks" && $checkgw != "paypaldp") {
			header("Location: index.php?specialoffer=true");
			exit;
		}
	}
}

// Create TeleSign Code if needed...
if($telesignid) $telesigncode = ashop_telesigncode();
else $telesigncode = "";

// Get shipping address if provided...
if ($_COOKIE["customersessionid"]) {
	$result = @mysqli_query($db,"SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
	$thiscustomerid = @mysqli_result($result, 0, "customerid");
	$allowemail = @mysqli_result($result, 0, "allowemail");
	$shippingfirstname = @mysqli_result($result, 0, "firstname");
	$shippinglastname = @mysqli_result($result, 0, "lastname");
	$shippingaddress = @mysqli_result($result, 0, "address");
	$shippingaddress2 = @mysqli_result($result, 0, "address2");
	$shippingzip = @mysqli_result($result, 0, "zip");
	$shippingcity = @mysqli_result($result, 0, "city");
	$shippingstate = @mysqli_result($result, 0, "state");
	$shippingcountry = @mysqli_result($result, 0, "country");
	$shippingphone = @mysqli_result($result, 0, "phone");
	$shippingemail = @mysqli_result($result, 0, "email");
	$result = @mysqli_query($db,"SELECT shippingid FROM shipping WHERE customerid='$thiscustomerid'");
	if (@mysqli_num_rows($result)) $shipid = @mysqli_result($result, 0, "shippingid");
	else {
		@mysqli_query($db,"INSERT INTO shipping (shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingzip, shippingcity, shippingstate, shippingcountry, shippingphone, shippingemail, customerid) VALUES ('$shippingfirstname', '$shippinglastname', '$shippingaddress', '$shippingaddress2', '$shippingzip', '$shippingcity', '$shippingstate', '$shippingcountry', '$shippingphone', '$shippingemail', '$thiscustomerid')");
		$shipid = @mysqli_insert_id($db);
	}
	// Check if there are any parties available...
	if (file_exists("$ashoppath/customerparties.php")) {
		$today = date("Y-m-d", time()+$timezoneoffset);
		$today .= " 00:01 AM";
		$tomorrow = date("Y-m-d", time()+$timezoneoffset+86400);
		$tomorrow .= " 00:01 AM";
		$partiesresult = @mysqli_query($db,"SELECT * FROM party WHERE date<'$tomorrow' AND approved='1' AND approved IS NOT NULL AND (ended='0' OR ended IS NULL) ORDER BY date ASC");
		if (@mysqli_num_rows($partiesresult)) {
			$partieslist = "<select name=\"partyid\" id=\"partyid\" onchange=\"updateparty();\">\n<option value=\"0\">".NONE."</option>\n";
			while ($partiesrow = @mysqli_fetch_array($partiesresult)) {
				$partieslist .= "<option value=\"".$partiesrow["partyid"]."\">".$partiesrow["date"].", ".$partiesrow["location"]."</option>\n";
			}
			$partieslist .= "</select>\n";
		}
	}
} else if ($shipid) {
	$result = @mysqli_query($db,"SELECT * FROM shipping WHERE shippingid='$shipid'");
	$sameasbilling = @mysqli_result($result, 0, "sameasbilling");
	if ($sameasbilling) {
		$shippingbusiness = @mysqli_result($result, 0, "shippingbusiness");
		$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
		$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
		$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
		$shippingaddress2 = @mysqli_result($result, 0, "shippingaddress2");
		$shippingzip = @mysqli_result($result, 0, "shippingzip");
		$shippingcity = @mysqli_result($result, 0, "shippingcity");
		$shippingstate = @mysqli_result($result, 0, "shippingstate");
		$shippingcountry = @mysqli_result($result, 0, "shippingcountry");
		$shippingphone = @mysqli_result($result, 0, "shippingphone");
		$shippingemail = @mysqli_result($result, 0, "shippingemail");
	}
} else if (strstr($description,"1: Shopping Mall Fee [ID: ")) {
	$shoppingmallid = str_replace("1: Shopping Mall Fee [ID: ","",$description);
	$shoppingmallid = substr($shoppingmallid,0,strpos($shoppingmallid,"]"));
	if (!empty($shoppingmallid) && is_numeric($shoppingmallid) && $shoppingmallid > 1) {
		$result = @mysqli_query($db,"SELECT * FROM user WHERE userid='$shoppingmallid'");
		$shippingbusiness = @mysqli_result($result, 0, "shopname");
		$shippingfirstname = @mysqli_result($result, 0, "firstname");
		$shippinglastname = @mysqli_result($result, 0, "lastname");
		$shippingaddress = @mysqli_result($result, 0, "address");
		$shippingaddress2 = @mysqli_result($result, 0, "address2");
		$shippingzip = @mysqli_result($result, 0, "zip");
		$shippingcity = @mysqli_result($result, 0, "city");
		$shippingstate = @mysqli_result($result, 0, "state");
		$shippingcountry = @mysqli_result($result, 0, "country");
		$shippingphone = @mysqli_result($result, 0, "phone");
		$shippingemail = @mysqli_result($result, 0, "email");
	}
}
// Make sure the customer details are valid...
if ($shippingbusiness == "Unknown") $shippingbusiness = "";
if ($shippingfirstname == "Unknown") $shippingfirstname = "";
if ($shippinglastname == "Unknown") $shippinglastname = "";
if ($shippingaddress == "Unknown" || $shippingaddress == "Unknown, Unknown") $shippingaddress = "";
if ($shippingaddress2 == "Unknown") $shippingaddress2 = "";
if ($shippingzip == "Unknown") $shippingzip = "";
if ($shippingcity == "Unknown") $shippingcity = "";
if ($shippingstate == "Unknown") $shippingstate = "";
if ($shippingcountry == "Unknown") $shippingcountry = "";
if ($shippingphone == "Unknown") $shippingphone = "";
if ($shippingemail == "Unknown") $shippingemail = "";

// Check if the customer's email is banned...
$bannedcheck = @mysqli_query($db,"SELECT * FROM customerblacklist WHERE blacklistitem='$shippingemail'");
$emaildomain = substr($email,strpos($shippingemail,"@")+1);
$domainbannedcheck = @mysqli_query($db,"SELECT * FROM customerblacklist WHERE blacklistitem='$emaildomain'");
if (@mysqli_num_rows($bannedcheck) || @mysqli_num_rows($domainbannedcheck)) {
	$basket = "";
	$newbasket = "";
	$taxandshipping = "";
	if (!empty($orderid) && is_numeric($orderid)) @mysqli_query($db,"DELETE FROM orders WHERE orderid='$orderid'");
}

// Store the preliminary order in the database...
if (!$shipid) $shipid = 0;
if (empty($description)) $newbasket = "";
if (!empty($newbasket) && ($numberofpayoptions > 1 || !$fixbackbutton)) {
	$tempdate = date("Y-m-d H:i:s", time()+$timezoneoffset);
	if (!isset($allowemail)) $allowemail = "0";
	settype($shipid, 'integer');
	settype($subtotal, 'float');
	$subtotal = number_format($subtotal,2,'.','');
	$safemysqldescription = @mysqli_escape_string($db, $description);
	if (!empty($recurringprice) && $recurringprice > 0) $sql = "INSERT INTO orders (customerid, products, description, price, ip, userid, language, telesigncode, returnurl, allowemail, wholesale, source, tempdate, affiliateid, referral, virtualcash, recurringfee, productdiscounts, pricelevel, remoteorderid) VALUES ('$shipid','$newbasket','$safemysqldescription','$subtotal', '{$_SERVER["REMOTE_ADDR"]}', '$shops', '$lang', '$telesigncode', '".str_replace("|","&",$returnurl)."', '$allowemail', '$wholesale', '$sourcetext', '$tempdate', '$affiliate', '$referral', '$virtualcashamount', '$recurringtotal', '$discountsstring', '$pricelevel', 'SUBSCRIPTIONSIGNUP')";
	else $sql = "INSERT INTO orders (customerid, products, description, price, ip, userid, language, telesigncode, returnurl, allowemail, wholesale, source, tempdate, affiliateid, referral, virtualcash, recurringfee, productdiscounts, pricelevel) VALUES ('$shipid','$newbasket','$safemysqldescription','$subtotal', '{$_SERVER["REMOTE_ADDR"]}', '$shops', '$lang', '$telesigncode', '".str_replace("|","&",$returnurl)."', '$allowemail', '$wholesale', '$sourcetext', '$tempdate', '$affiliate', '$referral', '$virtualcashamount', '$recurringtotal', '$discountsstring', '$pricelevel')";
	$result = @mysqli_query($db,"$sql");
	if (@mysqli_affected_rows($db) != 1) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
		echo "<br /><br /><p align=\"center\"><span class=\"ashopmessageheader\">Error</span><br /><br /><span class=\"ashopmessage\">".DATABASEERROR."<a href=\"javascript:document.location.reload(true)\">".TRYAGAIN."</a>!</span></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
		exit;
	} else {
		$orderid = @mysqli_insert_id($db);
		// Update floating price information if needed...
		if (is_array($fpupdate)) foreach ($fpupdate as $fpnumber=>$productid) @mysqli_query($db,"UPDATE floatingprice SET orderid='$orderid' WHERE productid='$productid'");
	}
}

// Only show gateways that can be used with the selected currency...
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";

// Make sure PayPal Express payments are automatically submitted...
if ($checkgw == "paypalec") {
	$storediscounts = FALSE;
	$perproductdiscounts = FALSE;
	$virtualcashpercent = 0;
}

// Automatically submit single payment options...
if ($device != "mobile" && !$partieslist && ($itemcount < 100 || $gw != "paypal") && !$agreementexists && !$fpmanipulated && (!$virtualcashpercent || $subtotal == "0.00") && (($numberofpayoptions == "1"  && file_exists("$ashoppath/admin/gateways$pathprefix/$gw.gw") && $basket && !$storediscounts && !$perproductdiscounts) || ($subtotal == "0.00" && $basket && !$recurringtotal))) {
	// Make sure the page isn't stored in the browsers cache...
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	if (!$fixbackbutton) {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("fixbackbutton", "$orderid");
		if ($subtotal == "0.00") {
			$payoptionid = 0;
			$payoptionfee = 0;
			$gw = "manual";
		} else {
			$payoptionid = @mysqli_result($payoptionresult, 0, "payoptionid");
			$payoptionfee = @mysqli_result($payoptionresult, 0, "fee");
			if (empty($payoptionfee) || $payoptionfee == 0) {
				$payoptionfee = 0.00;
				// Create PayPal shipping parameter...
				if (($gw == "paypal" || $gw == "paypalsandbox") && $totalshipping) $paypalproductstring .= "
				<input type=\"hidden\" name=\"handling_cart\" value=\"$totalshipping\">";
			} else if ($gw == "paypal" || $gw == "paypalsandbox") {
				$totalshipping += $payoptionfee;
				$paypalproductstring .= "
				<input type=\"hidden\" name=\"handling_cart\" value=\"$totalshipping\">";
			}
			$merchantid = @mysqli_result($payoptionresult, 0, "merchantid");
			$transactionkey = @mysqli_result($payoptionresult, 0, "transactionkey");
			$secret = @mysqli_result($payoptionresult, $option, "secret");
			$logourl = @mysqli_result($payoptionresult, 0, "logourl");
			$vspartner = @mysqli_result($payoptionresult, 0, "vspartner");
			$pageid = @mysqli_result($payoptionresult, 0, "pageid");
			$gwbgcolor = @mysqli_result($payoptionresult, 0, "bgcolor");
			$gwbgurl = @mysqli_result($payoptionresult, 0, "bgurl");
			$testmode = @mysqli_result($payoptionresult, 0, "testmode");
			$initialperiod = @mysqli_result($payoptionresult, 0, "initialperiod");
			if (!empty($initialperiod) && strstr($initialperiod,"|")) {
				$initialperiodarray = explode("|",$initialperiod);
				$initialperiod = $initialperiodarray[0];
				$initialperiodunits = $initialperiodarray[1];
			} else {
				$initialperiod = "";
				$initialperiodunits = "";
			}
			$recurringperiod = @mysqli_result($payoptionresult, 0, "recurringperiod");
			if (!empty($recurringperiod) && strstr($recurringperiod,"|")) {
				if (!empty($productrecurringperiod) && strstr($productrecurringperiod,"|")) $recurringperiod = $productrecurringperiod;
				$recurringperiodarray = explode("|",$recurringperiod);
				$recurringperiod = $recurringperiodarray[0];
				$recurringperiodunits = $recurringperiodarray[1];
				if (!empty($productrecurringperiod) && strstr($productrecurringperiod,"|")) {
					$initialperiod = $recurringperiodarray[0];
					$initialperiodunits = $recurringperiodarray[1];
				}
			} else {
				$recurringperiod = "";
				$recurringperiodunits = "";
			}
			$rebills = @mysqli_result($payoptionresult, 0, "rebills");
		}
		if ($payoptionfee != "0.00" && $gw == "inetsecure") $isproductstring .= "|".number_format($payoptionfee,2,'.','')."::1::tsf::Transaction Fee::{US}";
		if ($recurringtotal > 0 && $gw == "payza") {
			$alertpaystartfee = $subtotal+$payoptionfee;
			$subtotal = $recurringtotal-$payoptionfee;
		}
		include "admin/gateways$pathprefix/$gw.gw";
		if ($subtotal == "0.00") $paymenturl = "giftform.php";
		if ($noquerystring == "TRUE" || $agreementexists) { $relayurl = $paymenturl; $paymenturl = "checkout.php"; }
		if ($gw == "icpaydigital" || $gw == "icpayphysical" || $gw == "digiwebsales") {
			header("Location: ".generateicpurl($merchantid, $secret, "$payoptionid"."ashoporderstring".substr($newbasket,0,50), $description, $orderid, number_format($subtotal+$payoptionfee,2,'.',''), $affiliate, $payoptionid));
			exit;
		}
		echo "\n\n<html><head><title>".REDIRECTFORM."</title>\n".CHARSET."<link rel=\"stylesheet\" href=\"includes/ashopcss.inc.php\" type=\"text/css\"></head><body onload=\"document.paymentform.submit()\"><br /><br /><br /><p align=\"center\"><span class=\"ashopcarttext\" style=\"font-size: 16px;\">".REDIRECTSERV."</span></p><form name=\"paymentform\" ";
		if ($gw == "nbepay" || $gw == "micropaymenteb2p" || $gw == "micropaymentcc" || $gw == "micropaymentdd" || $gw == "micropaymentbt") echo " method=\"get\"";
		else echo "method=\"post\"";
		echo " action=\"$paymenturl\">\n";
		if ($gw_merchantid) echo "<input type=\"hidden\" name=\"$gw_merchantid\" value=\"$merchantid\">\n";
		if ($gw == "auriga") echo $gw_version;
		if ($gw_orderid) echo "<input type=\"hidden\" name=\"$gw_orderid\" value=\"$orderid\">\n";
		if ($gw == "auriga") echo $gw_currency;
		if ($gw_amount) {
			echo "<input type=\"hidden\" name=\"$gw_amount\" value=\"";
			if ($subtotal == "0.00") echo "0";
			else if ($gw == "dibs") echo number_format(($subtotal+$payoptionfee)*100,0);
			else if ($gw == "auriga") echo number_format(($subtotal+$payoptionfee)*100,0,'','');
			else echo number_format($subtotal+$payoptionfee,2,'.','');
			echo "\">\n";
			if ($recurringtotal > 0 && $gw == "payza") $subtotal = $alertpaystartfee;
		}
		if ($gw_extrafields) echo "$gw_extrafields\n";
		if ($gw == "authorizenetsim" || $gw == "authecheck" || $gw == "authnetsimdelayed" || $gw == "firstdataglobal") @generate_fingerprint($transactionkey, $merchantid, number_format($subtotal+$payoptionfee,2,'.',''));
		if ($ashopcurrency == "tec") {
			$tec_amount = number_format($subtotal+$payoptionfee,2,'.','');
			echo "<input type=\"hidden\" name=\"tec_hash\" value=\"".md5($secret.$postbackurl.$newbasket.$tec_amount)."\">";
		}
		if ($relayurl) echo "<input type=\"hidden\" name=\"relay\" value=\"$relayurl\">\n";
		if ($gw_logourl) echo "<input type=\"hidden\" name=\"$gw_logourl\" value=\"$logourl\">\n";
		if ($gw_vspartner) echo "<input type=\"hidden\" name=\"$gw_vspartner\" value=\"$vspartner\">\n";
		if ($gw_pageid) echo "<input type=\"hidden\" name=\"$gw_pageid\" value=\"$pageid\">\n";
		if ($gw_returnurl1) echo "<input type=\"hidden\" name=\"$gw_returnurl1\" value=\"$postbackurl\">\n";
		if ($gw_description) {
			if (($gw == "paypal" || $gw == "paypalsandbox") && strlen($description) > 127) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"".substr($description,0,124)."...\">\n";
			else if ($gw == "verotelflexpay" && strlen($description) > 100) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"".substr($description,0,97)."...\">\n";
			else echo "<input type=\"hidden\" name=\"$gw_description\" value=\"$description\">\n";
		}
		if ($gw_basket) {
			if ($gw == "paypal" || $gw == "paypalsandbox") echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring".substr($newbasket,0,50)."\">\n";
			else  echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring$newbasket\">\n";
		}
		if ($gw_firstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_firstname\" value=\"$shippingfirstname\">\n";
		if ($gw_lastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_lastname\" value=\"$shippinglastname\">\n";
		if ($gw_address && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_address\" value=\"$shippingaddress\">\n";
		if ($gw_city && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_city\" value=\"$shippingcity\">\n";
		if ($gw_zip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_zip\" value=\"$shippingzip\">\n";
		if ($gw_state && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_state\" value=\"$shippingstate\">\n";
		if ($gw_country && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_country\" value=\"$shippingcountry\">\n";
		if ($gw_phone && $shippingphone) echo "<input type=\"hidden\" name=\"$gw_phone\" value=\"$shippingphone\">\n";
		if ($gw_email && $shippingemail) echo "<input type=\"hidden\" name=\"$gw_email\" value=\"$shippingemail\">\n";		
		if ($gw_shipfirstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_shipfirstname\" value=\"$shippingfirstname\">\n";
		if ($gw_shiplastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_shiplastname\" value=\"$shippinglastname\">\n";
		if ($gw_shipaddress && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_shipaddress\" value=\"$shippingaddress\">\n";
		if ($gw_shipcity && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_shipcity\" value=\"$shippingcity\">\n";
		if ($gw_shipzip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_shipzip\" value=\"$shippingzip\">\n";
		if ($gw_shipstate && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_shipstate\" value=\"$shippingstate\">\n";
		if ($gw_shipcountry && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_shipcountry\" value=\"$shippingcountry\">\n";
		if ($sendpayoptionid == "TRUE") echo "<input type=\"hidden\" name=\"payoption\" value=\"$payoptionid\">";
		if ($gw_returnurl2) {
			if ($gw == "nabtransact") echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$postbackurl2?payopt=$payoptionid&ofinv=$orderid&fromshop=$shop&returnurl=$returnurl\">\n";
			else echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$postbackurl?payopt=$payoptionid&ofinv=$orderid&fromshop=$shop&returnurl=$returnurl\">\n";
		}
		if ($gw_cancel) {
			if ($returnurl) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$returnurl\">\n";
			else if ($shop > 1) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl/index.php?shop=$shop\">\n";
			else echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl\">\n";
		}
		if ($testmode == "1") echo "$testrequest\n";
		if ($gw == "2checkout" || $gw == "2checkoutv2") echo $twocoproductstring;
		if ($gw == "paypal" || $gw == "paypalsandbox") echo $paypalproductstring;
		if ($gw == "nabtransact") echo $nabtransactproductstring;
		if ($gw == "networkmerchants" && !empty($nmi_recurringstring)) echo "<input type=\"hidden\" name=\"nmi_recurring\" value=\"$nmi_recurringstring\">\n";
		if ($affiliate && $gw_affiliate) echo "<input type=\"hidden\" name=\"$gw_affiliate\" value=\"$affiliate\">\n";
		if ($gwbgcolor && $gw_bgcolor) echo "<input type=\"hidden\" name=\"$gw_bgcolor\" value=\"$gwbgcolor\">\n";
		if ($gwbgurl && $gw_bgurl) echo "<input type=\"hidden\" name=\"$gw_bgurl\" value=\"$gwbgurl\">\n";
		if ($gw == "payza") echo "<input type=\"hidden\" name=\"apc_3\" value=\"r\">\n";
		if ($gw != "auriga" && $gw != "micropaymenteb2p" && $gw != "micropaymentcc" && $gw != "micropaymentdd" && $gw != "micropaymentbt" && $gw != "nabtransact" && $taxandshippingcost) echo "<input type=\"hidden\" name=\"productcost\" value=\"".number_format($productcost,2,'.','')."\"><input type=\"hidden\" name=\"taxandshippingcost\" value=\"".number_format($taxandshippingcost,2,'.','')."\">";
		if ($gw == "auriga") echo "<input type=\"hidden\" name=\"MAC\" value=\"".aurigamac()."\" />";
		if ($returnurl && (strstr($paymenturl, "orderform.php") || strstr($paymenturl, "giftform.php"))) echo "<input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">";
		if ((strstr($paymenturl, "orderform.php") || strstr($relayurl, "orderform.php")) && $shop && $shop != "1") echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\">";
		if (strstr($paymenturl, "orderform.php") || strstr($relayurl, "orderform.php")) echo "<input type=\"hidden\" name=\"lang\" value=\"$lang\">";
		echo "</form></body></html>";
	} else {
		$result = @mysqli_query($db,"SELECT date FROM orders WHERE orderid='$fixbackbutton'");
		$checkdate = @mysqli_result($result,0,"date");
		if (!$checkdate) {
			$sql = "DELETE FROM orders WHERE orderid='$fixbackbutton'";
			$result = @mysqli_query($db,$sql);
		}
		if(!strstr($HTTP_REFERER,"basket.php") && !strstr($HTTP_REFERER,"add")) {
			if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			$p3psent = TRUE;
			setcookie("fixbackbutton", "");
		}
		echo "<html><head><script type=\"text/javascript\">history.go(-1);</script><title>$ashopname</title>\n".CHARSET."</head><body></body></html>";
	}
	exit;
}

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/$templatefile-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/$templatefile.html");
$catalogscript = "index.php";

// Add overlib to activate fancy thumbnail display...
?>
<script type="text/javascript" src="includes/overlib.js"></script>
<script type="text/javascript" src="includes/prototype.js"></script>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
var cancelImage=false;
function overlibImage(caption,imagePath)
{
  bgImage=new Image();
  bgImage.src=imagePath;
  if(!bgImage.complete)
  {
    overlib("loading image..");
    cancelImage=false;
    bgImage.onload=function()
    {   
      if(!cancelImage)
      {
        var substringpos=bgImage.src.length-imagePath.length;
        if(bgImage.src.substring(substringpos)==imagePath)
        {
          overlib(caption,TEXTCOLOR,'#FFFFFF',BACKGROUND,imagePath,FGCOLOR,'',WIDTH,bgImage.width,HEIGHT,bgImage.height);
        }
      }
    }
  }
  else
  {
    overlib(caption,TEXTCOLOR,'#FFFFFF',BACKGROUND,imagePath,FGCOLOR,'',WIDTH,bgImage.width,HEIGHT,bgImage.height);
  }
}

function overlibMouseout()
{
  cancelImage=true;
  return nd();
}

function updateoptin() {
	var optin = document.getElementById('allowemail').checked;
	var myAjax = new Ajax.Request(
		'checkout.php', 
		{
			method: 'post', 
			parameters: 'optin='+optin+'&orderid=<?php echo $orderid ?>&dummy='+ new Date().getTime()
		}
	);
}

function updateparty() {
	var partyid = document.getElementById('partyid').value;
	var myAjax = new Ajax.Request(
		'checkout.php', 
		{
			method: 'post', 
			parameters: 'party='+partyid+'&orderid=<?php echo $orderid ?>&dummy='+ new Date().getTime()
		}
	);
}

function checkagree(payform) {
	if (document.getElementById('agree').checked) payform.submit();
	else {
		document.getElementById('agreementnotice').innerHTML = '<br /><?php echo YOUHAVETOREADANDACCEPT; ?>';		
		return false;
	}
}

function showlicense() {
	window.open("license.php","_blank","toolbar=no, location=no, scrollbars=yes, width=500, height=600")
}
/* ]]> */
</script>
<?php
if ($device != "mobile") echo "
    <table class=\"ashopcheckoutframe\">
      
  <tr align=\"center\"> 
    <td><br />
	  <table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" align=\"center\">
	  <tr><td align=\"right\" valign=\"top\">";
echo "
            <a href=\"checkout.php?id={$orderid}&amp;redirect=";
if ($returnurl) echo "$returnurl";
else { 
	echo $catalogscript;
	if ($cat) echo "?cat=$cat";
	if ($shop && $shop != "1") echo "|shop=$shop";
}
echo "\"";
if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
echo ">";
if ($device == "mobile") echo CONTINUESHOPPING;
else echo "<img src=\"{$buttonpath}images/continue-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".CONTINUESHOPPING."\" />";
echo "</a>";
if ($device != "mobile") echo "</td>";
			
if ($shoppingcart) {
	if ($device != "mobile") echo "<td align=\"left\" valign=\"top\">";
	echo "<a href=\"checkout.php?id=$orderid&amp;redirect=basket.php";
	if ($returnurl) echo "?returnurl=$returnurl|sid=$sid";
	else echo "?cat=$cat$shopredirect|sid=$sid";
	echo "\"";
	if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
	echo ">";
	if ($device == "mobile") echo VIEWCART;
	else echo "<img src=\"{$buttonpath}images/viewcart-$lang.png\" class=\"ashopbutton\" alt=\"".VIEWCART."\" border=\"0\" />";
	echo "</a>";
	if ($device != "mobile") echo "</td>";
}
if ($shipid && $taxandshipping && !$showshippingform) {
	if ($device != "mobile") echo "<td align=\"left\" valign=\"top\">";
	echo "<a href=\"";
	if ($returnurl) echo "shipping.php?changeshipping=true&amp;action=checkout&amp;returnurl=$returnurl";
	else echo "shipping.php?changeshipping=true&amp;action=checkout&amp;cat=$cat$shophtmlstring";
	echo "\"";
	if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
	echo ">";
	if ($device == "mobile") echo CHANGESHIPPING;
	else echo "<img src=\"{$buttonpath}images/shipping-$lang.png\" class=\"ashopbutton\" alt=\"".CHANGESHIPPING."\" border=\"0\" />";
	echo "</a>";
	if ($device != "mobile") echo "</td>";
}

if ($device != "mobile") echo "
</tr></table>";
	  
if (!$newbasket || $fpmanipulated) echo "<p><span class=\"ashopcheckouttext1\">".EMPTYCART."</span></p>";
else {
	if ($cartlistoncheckout) {
		echo "<p><span class=\"ashopcarttext\">
        ".CARTCONTAINS."</span></p>
      <p><table class=\"ashopcartframe3\">
        <tr align=\"center\"> 
          <td>
			<table class=\"ashopcarttable\" border=\"1\" cellpadding=\"5\">
				<tr class=\"ashoptableheader\">";
				if ($showimagesincart) echo "<td align=\"center\"><span class=\"ashopcartlabel\">".PICTURE."</span></td>";
				echo "
				<td align=\"center\"><span class=\"ashopcartlabel\">".QTY."</span></td>
				<td align=\"left\"><span class=\"ashopcartlabel\">".PRODUCT."</span></td>
				<td align=\"right\"><span class=\"ashopcartlabel\">".PRICE."</span></td>
				<td align=\"right\"><span class=\"ashopcartlabel\">".AMOUNT2."</span></td>
				<td>&nbsp;</td></tr>
				$detailedcheckoutdescription";
				if ($showimagesincart) echo "<td align=\"right\" BGCOLOR=\"$categorycolor\" colspan=4>";
				else echo "<td align=\"right\" BGCOLOR=\"$categorycolor\" colspan=3>";
				// Convert currency...
				$csubtotal = $subtotal;
				if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
					$tempcurrency = $ashopcurrency;
					$ashopcurrency = $curr;
					$tempsubtotal = $csubtotal;
					$csubtotal = $csubtotal*$crate;
				}
				echo "<span class=\"ashopcarttotals\"><b>".TOTAL.":</b></span></td>
				<td align=\"right\"><INPUT TYPE=HIDDEN NAME=\"sum\" VALUE=\"".number_format($subtotal,2,'.','')."\">
				<span class=\"ashopcartcontents\">".$currencysymbols[$ashopcurrency]["pre"].number_format($csubtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
				// Convert back to main currency...
				if (!empty($curr) && !empty($crate) && is_numeric($crate)) $ashopcurrency = $tempcurrency;
				if ($csubtotal != $subtotal) echo "<br />(".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"].")";
				echo "</span></td><td BGCOLOR=\"$categorycolor\">&nbsp;</td></tr></TABLE></td></tr></TABLE></p>";
		if ($recurringtotal) {
			// Convert currency...
			if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
				$tempcurrency = $ashopcurrency;
				$ashopcurrency = $curr;
				$temprecurringtotal = $recurringtotal;
				$recurringtotal = $recurringtotal*$crate;
			}
			echo "<span class=\"ashopcheckouttext1\">".RECURRINGFEE."</span> <span class=\"ashopcheckouttext2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($recurringtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span>";
			// Convert back to main currency...
			if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
				$ashopcurrency = $tempcurrency;
				$productcost = $tempproductcost;
				$taxandshippingcost = $temptaxandshippingcost;
			}
		}
	} else {
		// Convert currency...
		$csubtotal = $subtotal;
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$tempcurrency = $ashopcurrency;
			$ashopcurrency = $curr;
			$tempproductcost = $productcost;
			$productcost = $productcost*$crate;
			$temptaxandshippingcost = $taxandshippingcost;
			$taxandshippingcost = $taxandshippingcost*$crate;
			$tempsubtotal = $csubtotal;
			$csubtotal = $csubtotal*$crate;
		}
		echo "<p><span class=\"ashopcheckouttext1\">".PRODINCART."</span><br /><span class=\"ashopcheckouttext2\">$checkoutdescription</span><br /><br />";
		if ($taxandshippingcost) echo "<span class=\"ashopcheckouttext1\">".PRICE."</span> <span class=\"ashopcheckouttext2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($productcost,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"].", </span><span class=\"ashopcheckouttext1\">".TAX."</span> <span class=\"ashopcheckouttext2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($taxandshippingcost,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."<br />";
		echo "</span><span class=\"ashopcheckouttext1\">".AMOUNT."</span> <span class=\"ashopcheckouttext2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($csubtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
		// Convert back to main currency...
		if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
			$ashopcurrency = $tempcurrency;
			$productcost = $tempproductcost;
			$taxandshippingcost = $temptaxandshippingcost;
		}
		if ($csubtotal != $subtotal) echo CHARGEDAS." ".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
		echo "</span>";
		if ($recurringtotal) {
			// Convert currency...
			if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
				$tempcurrency = $ashopcurrency;
				$ashopcurrency = $curr;
				$temprecurringtotal = $recurringtotal;
				$recurringtotal = $recurringtotal*$crate;
			}
			echo "<br /><span class=\"ashopcheckouttext1\">".RECURRINGFEE."</span> <span class=\"ashopcheckouttext2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($recurringtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span>";
			// Convert back to main currency...
			if (!empty($curr) && !empty($crate) && is_numeric($crate)) {
				$ashopcurrency = $tempcurrency;
				$productcost = $tempproductcost;
				$taxandshippingcost = $temptaxandshippingcost;
			}
		}
		echo "</p>";
	}
	// Handle parties...
	if (!empty($partieslist)) {
		echo "\n\n<table class=\"ashoppartiestable\"><tr><td align=\"center\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td width=\"30\">&nbsp;</td><td align=\"left\" valign=\"top\"><span class=\"ashoppartiestext\"><b>".PARTY.": </b> $partieslist</span></td></tr></table></td></tr></table>";

	}
	// Handle virtual cash...
	if (!empty($virtualcashpercent)) {
		if ($taxandshippingcost) $virtualcashreward = $productcost*($virtualcashpercent/100);
		else $virtualcashreward = $subtotal*($virtualcashpercent/100);

		$customerwalletresult = @mysqli_query($db,"SELECT virtualcash FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		$customerwallet = @mysqli_result($customerwalletresult,0,"virtualcash");

		if (!empty($customerwallet) || !empty($virtualcashreward)) {
			echo "\n\n<form name=\"virtualcashform\" method=\"post\" action=\"checkout.php\"><input type=\"hidden\" name=\"sid\" value=\"$sid\"><input type=\"hidden\" name=\"shop\" value=\"$shop\"><input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">\n<table class=\"ashopvirtualcashtable\"><tr><td align=\"center\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td width=\"30\">&nbsp;</td><td align=\"left\" valign=\"top\" colspan=\"3\"><span class=\"ashopvirtualcashtext\"><b>".VIRTUALCASH."</b>";
			if (!empty($virtualcashreward)) echo "<br />".WILLREWARDYOU.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($virtualcashreward,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]." ".VIRTUALCASH2;
			if (!empty($virtualcashamount)) {
				echo "<br />You have applied ".$currencysymbols[$ashopcurrency]["pre"].number_format($virtualcashamount,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]." virtual cash on this order.";
				$customerwallet -= $virtualcashamount;
			}
			echo "</span></td></tr><tr><td width=\"30\">&nbsp;</td><td align=\"left\"><span class=\"ashopvirtualcashtext\">".CURRENTLYHAVE." ".$currencysymbols[$ashopcurrency]["pre"].number_format($customerwallet,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]." ".VIRTUALCASH2."</span></td><td align=\"right\" valign=\"top\"><span class=\"ashopvirtualcashtext\">".APPLYVC." ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"virtualcashamount\" value=\"".number_format($virtualcashamount,2,'.','')."\" size=\"5\">".$currencysymbols[$ashopcurrency]["post"]."</span></td><td width=\"80\" align=\"right\" valign=\"bottom\">";
			if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" value=\"".APPLY."\" />";
			else echo "<input type=\"image\" border=\"0\" src=\"{$buttonpath}images/apply-$lang.png\" class=\"ashopbutton\" alt=\"".APPLY."\">";
			echo "</span></td></tr></table></td></tr></table></form></p>";
		}
	}
	if (($storediscounts || $perproductdiscounts) && $discountoncheckout) {
		echo "\n\n<form name=\"discountform\" method=\"post\" action=\"checkout.php\">\n<table class=\"ashopdiscounttable\"><tr><td align=\"center\"><span class=\"ashopdiscounttext\">$discountmessage</span><br /><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td width=\"30\">&nbsp;</td><td align=\"left\" valign=\"top\"><span class=\"ashopdiscounttext\">".ENTERCODE." <input type=\"text\" name=\"discountcode\" size=\"28\"></span></td><td width=\"80\" align=\"right\" valign=\"bottom\">";
		if ($device == "mobile") echo "&nbsp;</td></tr><tr><td colspan=\"3\"><input type=\"submit\" data-role=\"button\" value=\"".APPLY."\" />";
		else echo "<input type=\"image\" border=\"0\" src=\"{$buttonpath}images/apply-$lang.png\" class=\"ashopbutton\" alt=\"".APPLY."\" />";
		echo "</td></tr></table></td></tr></table></form></p>";
	}

	// Print shipping form, if needed...
	if ($showshippingform == "full") ashop_shippingform("checkout","true");
	if ($showshippingform == "short") ashop_shippingform("checkout","");

    // Print order forms...
	$gw = "";
	if ($numberofpayoptions) {
		if ($device == "mobile") echo "
		<script language=\"javascript\" type=\"text/javascript\">
		/* <![CDATA[ */
			function payoptionrefresh() {
				jQuery(\"input[type='radio']\").checkboxradio(\"refresh\");
			}
		/* ]]> */
		</script>";
		echo "<table class=\"ashopcheckouttable\" cellspacing=\"0\" cellpadding=\"5\"><tr class=\"ashoptableheader\"><td align=\"center\"><span class=\"ashopcheckouttext1\">";
		if ($subtotal > 0) {
			if ($numberofpayoptions > 1) echo CHOOSE;
			else echo PAYMENTTHROUGH;
		}
		echo "</span>\n
		<script type=\"text/javascript\">\n\t var payoptionform = new Object();\n</script>\n";
		if ($subtotal == 0) $numberofpayoptions = 1;
		for ($option = 0; $option < $numberofpayoptions; $option++) {
			$noquerystring = "";
			$relayurl = "";
			if ($subtotal == 0) $gw = "manual";
			else $gw = @mysqli_result($payoptionresult, $option, "gateway");
			if (file_exists("$ashoppath/admin/gateways$pathprefix/$gw.gw")) {
				if ($subtotal == 0) {
					$payoptionid = 0;
					$payoptionname = "";
					$payoptiondescr = "";
					$payoptionfee = 0;

				} else {
					$payoptionid = @mysqli_result($payoptionresult, $option, "payoptionid");
					$payoptionname = @mysqli_result($payoptionresult, $option, "name");
					$payoptiondescr = @mysqli_result($payoptionresult, $option, "description");
					$payoptionfee = @mysqli_result($payoptionresult, $option, "fee");
					if (empty($payoptionfee) || $payoptionfee == 0) {
						$payoptionfee = 0.00;
						// Create PayPal shipping parameter...
						if (($gw == "paypal" || $gw == "paypalsandbox") && $totalshipping) $paypalproductstring .= "
						<input type=\"hidden\" name=\"handling_cart\" value=\"$totalshipping\">";
					} else if ($gw == "paypal" || $gw == "paypalsandbox") {
						$totalshipping += $payoptionfee;
						$paypalproductstring .= "
						<input type=\"hidden\" name=\"handling_cart\" value=\"$totalshipping\">";
					}
					$merchantid = @mysqli_result($payoptionresult, $option, "merchantid");
					$transactionkey = @mysqli_result($payoptionresult, $option, "transactionkey");
					$secret = @mysqli_result($payoptionresult, $option, "secret");
					$logourl = @mysqli_result($payoptionresult, $option, "logourl");
					$vspartner = @mysqli_result($payoptionresult, $option, "vspartner");
					$pageid = @mysqli_result($payoptionresult, $option, "pageid");
					$gwbgcolor = @mysqli_result($payoptionresult, $option, "bgcolor");
					if ($gw == "micropaymenteb2p" || $gw == "micropaymentcc" || $gw == "micropaymentdd" || $gw == "micropaymentbt") $gwbgcolor = str_replace("#","",$gwbgcolor);
					$gwbgurl = @mysqli_result($payoptionresult, $option, "bgurl");
					$testmode = @mysqli_result($payoptionresult, $option, "testmode");
					$initialperiod = @mysqli_result($payoptionresult, $option, "initialperiod");
					if (!empty($initialperiod) && strstr($initialperiod,"|")) {
						$initialperiodarray = explode("|",$initialperiod);
						$initialperiod = $initialperiodarray[0];
						$initialperiodunits = $initialperiodarray[1];
					} else {
						$initialperiod = "";
						$initialperiodunits = "";
					}
					$recurringperiod = @mysqli_result($payoptionresult, $option, "recurringperiod");
					if (!empty($recurringperiod) && strstr($recurringperiod,"|")) {
						if (!empty($productrecurringperiod) && strstr($productrecurringperiod,"|")) $recurringperiod = $productrecurringperiod;
						$recurringperiodarray = explode("|",$recurringperiod);
						$recurringperiod = $recurringperiodarray[0];
						$recurringperiodunits = $recurringperiodarray[1];
						if (!empty($productrecurringperiod) && strstr($productrecurringperiod,"|")) {
							$initialperiod = $recurringperiodarray[0];
							$initialperiodunits = $recurringperiodarray[1];
						}
					} else {
						$recurringperiod = "";
						$recurringperiodunits = "";
					}
					$rebills = @mysqli_result($payoptionresult, 0, "rebills");
					if ($payoptionfee != "0.00" && $gw == "inetsecure") $isproductstring .= "|".number_format($payoptionfee,2,'.','')."::1::tsf::Transaction Fee::{US}";

					if ($recurringtotal > 0 && $gw == "payza") {
						$alertpaystartfee = $subtotal+$payoptionfee;
						$subtotal = $recurringtotal-$payoptionfee;
					}
				}

				include "admin/gateways$pathprefix/$gw.gw";

				if ($noquerystring == "TRUE") { $relayurl = $paymenturl; $paymenturl = "checkout.php"; }

				if ($subtotal == 0) $paymenturl = "giftform.php";

				if (($gw == "manual" || $gw == "daopay") && $shippingfirstname && $shippinglastname && $shippingaddress && $shippingcity && $shippingzip && $shippingemail && !empty($_COOKIE["customersessionid"])) $paymenturl = $paymenturl2;
		
				echo "\n\n<p><form name=\"paymentform$payoptionid\"";
				if ($gw == "nbepay" || $gw == "micropaymenteb2p" || $gw == "micropaymentcc" || $gw == "micropaymentdd" || $gw == "micropaymentbt") echo " method=\"get\"";
				else echo " method=\"post\"";
				if ($agreementexists) echo " onsubmit=\"checkagree(this); return false;\"";
				echo " action=\"$paymenturl\">\n<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td width=\"60\" valign=\"top\" align=\"right\">";
				if ($device == "mobile") echo "&nbsp;";
				else if ($itemcount < 100 || $gw != "paypal") {
					if ($numberofpayoptions > 1) echo "<input type=\"radio\" name=\"payoption\" onclick=\"payoptionform = paymentform$payoptionid; for (var i = 0; i < document.getElementsByName('payoption').length; i++) if (document.getElementsByName('payoption')[i] != this) document.getElementsByName('payoption')[i].checked = false;\">";
					else echo "<script language=\"javascript\" type=\"text/javascript\">
					/* <![CDATA[ */
					payoptionform = document.getElementsByName('paymentform$payoptionid')[0];
					/* ]]> */
					</script>";
				} else echo "<input type=\"radio\" name=\"payoption\" disabled>";
				echo "</td><td align=\"left\" valign=\"top\">";
				if ($itemcount > 99 && $gw == "paypal") $payoptionname = $payoptionname."<br /><span style=\"color: #FF0000;\">".PAYPALITEMLIMIT." $itemcount.</span>";
				if ($device == "mobile") {
					if ($numberofpayoptions > 1) echo "<input type=\"radio\" name=\"payoption\" id=\"payoption\" onclick=\"payoptionform = paymentform$payoptionid; for (var i = 0; i < document.getElementsByName('payoption').length; i++) if (document.getElementsByName('payoption')[i] != this) { document.getElementsByName('payoption')[i].checked = false; payoptionrefresh(); }\" /><label for=\"payoption\">$payoptionname</label>";
					else echo "<script language=\"javascript\" type=\"text/javascript\">
					/* <![CDATA[ */
					payoptionform = document.getElementsByName('paymentform$payoptionid')[0];
					/* ]]> */
					</script>";
				} else echo "<span class=\"ashopcheckoutcontents\">
				<b>$payoptionname</b>";
				if ($payoptiondescr) echo "<br />$payoptiondescr";
				if ($payoptionfee != "0.00") echo "<br />".TRANSACTIONFEE.": <b>".$currencysymbols[$ashopcurrency]["pre"]."$payoptionfee".$currencysymbols[$ashopcurrency]["post"]."</b>";

				echo "</span>\n";

				if ($gw_merchantid) echo "<input type=\"hidden\" name=\"$gw_merchantid\" value=\"$merchantid\">\n";
				if ($gw == "auriga") echo $gw_version;
				if ($gw_orderid) echo "<input type=\"hidden\" name=\"$gw_orderid\" value=\"$orderid\">\n";
				if ($gw == "auriga") echo $gw_currency;
				
				if ($gw_amount) {
					echo "<input type=\"hidden\" name=\"$gw_amount\" value=\"";
					if ($subtotal == "0.00") echo "0";
					else if ($gw == "dibs" || $gw == "micropaymenteb2p" || $gw == "micropaymentcc" || $gw == "micropaymentdd" || $gw == "micropaymentbt") echo number_format(($subtotal+$payoptionfee)*100,0,'','');
					else if ($gw == "auriga") echo number_format(($subtotal+$payoptionfee)*100,0,'','');
					else echo number_format($subtotal+$payoptionfee,2,'.','');
					echo "\">\n";
					if ($recurringtotal > 0 && $gw == "payza") $subtotal = $alertpaystartfee;
				}

				if ($gw_extrafields) echo "$gw_extrafields\n";

				if ($gw == "authorizenetsim" || $gw == "authecheck" || $gw == "authnetsimdelayed" || $gw == "firstdataglobal") @generate_fingerprint($transactionkey, $merchantid, number_format($subtotal+$payoptionfee,2,'.',''));
				
				if ($ashopcurrency == "tec") {
					$tec_amount = number_format($subtotal+$payoptionfee,2,'.','');
					echo "<input type=\"hidden\" name=\"tec_hash\" value=\"".md5($secret.$postbackurl.$newbasket.$tec_amount)."\">";
				}

				if ($gw == "cybersource") {
					include "admin/HOP.php";
					InsertSignature3(number_format($subtotal+$payoptionfee,2,'.',''), $ashopcurrency, "sale");
				}

				if ($relayurl) echo "<input type=\"hidden\" name=\"relay\" value=\"$relayurl\">\n";
				if ($gw_logourl) echo "<input type=\"hidden\" name=\"$gw_logourl\" value=\"$logourl\">\n";
				if ($gw_vspartner) echo "<input type=\"hidden\" name=\"$gw_vspartner\" value=\"$vspartner\">\n";
				if ($gw_pageid) echo "<input type=\"hidden\" name=\"$gw_pageid\" value=\"$pageid\">\n";
				if ($gw_returnurl1) echo "<input type=\"hidden\" name=\"$gw_returnurl1\" value=\"$postbackurl\">\n";
				if ($gw_description) {
					if (($gw == "paypal" || $gw == "paypalsandbox") && strlen($description) > 127) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"".substr($description,0,124)."...\">\n";
					else if ($gw == "verotelflexpay" && strlen($description) > 100) echo "<input type=\"hidden\" name=\"$gw_description\" value=\"".substr($description,0,97)."...\">\n";
					else echo "<input type=\"hidden\" name=\"$gw_description\" value=\"$description\">\n";
				}
				if ($gw_basket) {
					if ($gw == "paypal" || $gw == "paypalsandbox") echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring".substr($newbasket,0,50)."\">\n";
					else  echo "<input type=\"hidden\" name=\"$gw_basket\" value=\"$payoptionid"."ashoporderstring$newbasket\">\n";
				}
				
				if ($gw_firstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_firstname\" value=\"$shippingfirstname\">\n";
				if ($gw_lastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_lastname\" value=\"$shippinglastname\">\n";
				if ($gw_address && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_address\" value=\"$shippingaddress\">\n";
				if ($gw_city && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_city\" value=\"$shippingcity\">\n";
				if ($gw_zip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_zip\" value=\"$shippingzip\">\n";
				if ($gw_state && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_state\" value=\"$shippingstate\">\n";
				if ($gw_country && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_country\" value=\"$shippingcountry\">\n";
				if ($gw_phone && $shippingphone) echo "<input type=\"hidden\" name=\"$gw_phone\" value=\"$shippingphone\">\n";
				if ($gw_email && $shippingemail) echo "<input type=\"hidden\" name=\"$gw_email\" value=\"$shippingemail\">\n";				
				if ($gw_shipfirstname && $shippingfirstname) echo "<input type=\"hidden\" name=\"$gw_shipfirstname\" value=\"$shippingfirstname\">\n";
				if ($gw_shiplastname && $shippinglastname) echo "<input type=\"hidden\" name=\"$gw_shiplastname\" value=\"$shippinglastname\">\n";
				if ($gw_shipaddress && $shippingaddress) echo "<input type=\"hidden\" name=\"$gw_shipaddress\" value=\"$shippingaddress\">\n";
				if ($gw_shipcity && $shippingcity) echo "<input type=\"hidden\" name=\"$gw_shipcity\" value=\"$shippingcity\">\n";
				if ($gw_shipzip && $shippingzip) echo "<input type=\"hidden\" name=\"$gw_shipzip\" value=\"$shippingzip\">\n";
				if ($gw_shipstate && $shippingstate) echo "<input type=\"hidden\" name=\"$gw_shipstate\" value=\"$shippingstate\">\n";
				if ($gw_shipcountry && $shippingcountry) echo "<input type=\"hidden\" name=\"$gw_shipcountry\" value=\"$shippingcountry\">\n";
				if ($sendpayoptionid == "TRUE") echo "<input type=\"hidden\" name=\"payoption\" value=\"$payoptionid\">";
				if ($gw_returnurl2) {
					if ($gw == "nabtransact") echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$postbackurl2?payopt=$payoptionid&ofinv=$orderid&fromshop=$shop&returnurl=$returnurl\">\n";
					else echo "<input type=\"hidden\" name=\"$gw_returnurl2\" value=\"$postbackurl?payopt=$payoptionid&amp;ofinv=$orderid&amp;fromshop=$shop&amp;returnurl=$returnurl\">\n";
				}
				if ($gw_cancel) {
					if ($returnurl) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$returnurl\">\n";
					else if ($shop > 1) echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl/index.php?shop=$shop\">\n";
					else echo "<input type=\"hidden\" name=\"$gw_cancel\" value=\"$ashopurl\">\n";
				}
				if ($testmode == "1") echo "$testrequest\n";
				if ($gw == "2checkout" || $gw == "2checkoutv2") echo $twocoproductstring;
				if ($gw == "paypal" || $gw == "paypalsandbox") echo $paypalproductstring;
				if ($gw == "nabtransact") echo $nabtransactproductstring;
				if ($gw == "networkmerchants" && !empty($nmi_recurringstring)) echo "<input type=\"hidden\" name=\"nmi_recurring\" value=\"$nmi_recurringstring\">\n";
				if ($affiliate && $gw_affiliate) echo "<input type=\"hidden\" name=\"$gw_affiliate\" value=\"$affiliate\">\n";
				if ($gwbgcolor && $gw_bgcolor) echo "<input type=\"hidden\" name=\"$gw_bgcolor\" value=\"$gwbgcolor\">\n";
				if ($gwbgurl && $gw_bgurl) echo "<input type=\"hidden\" name=\"$gw_bgurl\" value=\"$gwbgurl\">\n";
				if ($gw == "payza") echo "<input type=\"hidden\" name=\"apc_3\" value=\"r\">\n";
				if ($gw != "auriga" && $gw != "micropaymenteb2p" && $gw != "micropaymentcc" && $gw != "micropaymentdd" && $gw != "micropaymentbt" && $gw != "nabtransact" && $taxandshippingcost) echo "<input type=\"hidden\" name=\"productcost\" value=\"".number_format($productcost,2,'.','')."\"><input type=\"hidden\" name=\"taxandshippingcost\" value=\"".number_format($taxandshippingcost,2,'.','')."\">";
				if ($returnurl && strstr($paymenturl, "orderform.php")) echo "<input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">";
				if ((strstr($paymenturl, "orderform.php") || strstr($relayurl, "orderform.php") || $gw == "manual") && $shop && $shop != "1") echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\">";
				if (strstr($paymenturl, "orderform.php") || strstr($relayurl, "orderform.php") || $gw == "manual") echo "<input type=\"hidden\" name=\"lang\" value=\"$lang\">";
				if (($gw == "manual" || $gw == "daopay") && $shippingfirstname && $shippinglastname && $shippingaddress && $shippingcity && $shippingzip && $shippingemail && !empty($_COOKIE["customersessionid"])) {
					$md5total = $subtotal + $payoptionfee;
					$md5total = number_format($md5total,2,'.','');
					$authkey = md5("{$ashoppath}{$payoptionid}ashoporderstring{$newbasket}ashopkey$md5total");
					echo "<input type=\"hidden\" name=\"authkey\" value=\"$authkey\">";		
				}

				// Generate security seal for micropayment gateway...
				if ($gw == "micropaymenteb2p" || $gw == "micropaymentcc" || $gw == "micropaymentbt" || $gw == "micropaymentdd") {
					$mp_params = "payoption=on&project=$merchantid&invoice=$orderid&amount=".number_format(($subtotal+$payoptionfee)*100,0,'','')."&paymethod=$gw&title=$description&bgcolor=$gwbgcolor";
					$mp_seal = md5($mp_params.$transactionkey); 
					echo "<input type=\"hidden\" name=\"seal\" value=\"$mp_seal\">\n";
				}

				if ($gw == "auriga") echo "<input type=\"hidden\" name=\"MAC\" value=\"".aurigamac()."\" />";
				if ($gw == "icpaydigital" || $gw == "icpayphysical" || $gw == "digiwebsales") {
					echo "</td><td width=\"100\" align=\"right\" valign=\"bottom\"><a href=\"";
					echo generateicpurl($merchantid, $secret, "$payoptionid"."ashoporderstring".substr($newbasket,0,50), $description, $orderid, number_format($subtotal+$payoptionfee,2,'.',''), $affiliate, $payoptionid);
					echo "\"><img border=\"0\" src=\"{$buttonpath}images/next-$lang.png\" class=\"ashopbutton\" alt=\"Place order\"></a></td></tr></table></td></tr></table></form></p>";
				} else echo "</td><td width=\"100\" align=\"right\" valign=\"bottom\">&nbsp;</td></tr></table></form></p>";
			}
		}
		if (empty($_COOKIE["customersessionid"]) || $agreementexists) echo "<div style=\"width: 380px; text-align: left;\">";
		if (empty($_COOKIE["customersessionid"])) {
			echo "<span class=\"ashopcheckouttext2\">";
			if ($device == "mobile") echo "<fieldset data-role=\"controlgroup\"><input type=checkbox id=\"allowemail\" onChange=\"updateoptin();\" /><label for=\"allowemail\">".YESEMAILME."</label></fieldset>";
			else echo "<input type=checkbox id=\"allowemail\" onChange=\"updateoptin();\" /> ".YESEMAILME;
			echo "</span>";
		}
		if ($agreementexists) {
			echo "<br /><span class=\"ashopcheckouttext2\">";
			if ($device == "mobile") echo "<fieldset data-role=\"controlgroup\"><input type=checkbox id=\"agree\" /><label for=\"agree\">".AGREE." ".TERMS."</label></fieldset><a href=\"javascript: showlicense();\">".READTERMS."</a>";
			else echo "<input type=checkbox id=\"agree\"> ".AGREE." <a href=\"javascript: showlicense();\">".TERMS."</a>";
			echo ".</span><div id=\"agreementnotice\" class=\"ashopalert\" style=\"text-align: center;\"></div>";
		}
		if (empty($_COOKIE["customersessionid"]) || $agreementexists) echo "</div>";
		echo "<br />
		</td></tr><tr><td align=\"right\">";
		if ($device == "mobile") echo "<input type=\"submit\" data-role=\"button\" onclick=\"";
		else echo "
		<input type=\"image\" border=\"0\" src=\"{$buttonpath}images/next-$lang.png\" class=\"ashopbutton\" alt=\"".THEWORDNEXT."\" onclick=\"";
		if ($agreementexists) echo "if (payoptionform.submit) checkagree(payoptionform);\"";
		else echo "if (payoptionform.submit) payoptionform.submit();\"";
		if ($device == "mobile") echo " value=\"".THEWORDNEXT."\"";
		echo " />";
		echo "</td></tr></table>";
	}
}
echo "
<p align=\"center\"><span class=\"ashopcheckouttext2\">";
if (($storediscounts || $perproductdiscounts) && !$discountoncheckout && $newbasket) {
	echo REDEEMCOUPON." <a href=\"discount.php";
	if ($returnurl || $cat) echo "?";
	if ($returnurl) echo "returnurl=$returnurl";
	if ($cat) {
		if ($returnurl) echo "&amp;cat=$cat";
		else echo "cat=$cat";
	}
	echo "\">".HERE."</a>.<br />";
}
echo IPLOG1.": {$_SERVER["REMOTE_ADDR"]} ".IPLOG2."</span></p>";
echo "</td></tr></table>";

// Close database...

@mysqli_close($db);

// Print footer using template...

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/$templatefile-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/$templatefile.html");
?>