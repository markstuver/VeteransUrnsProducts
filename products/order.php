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

@set_time_limit(0);
unset($shop);
include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";
if (!isset($currencysymbols)) include "admin/ashopconstants.inc.php";
if (!isset($keycodeencryptionkey)) include "admin/keycodes.inc.php";
if (!isset($customerencryptionkey)) include "admin/customers.inc.php";

// Validate variables...
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Set the ofinv variable for Verotel Flexpay orders...
if (empty($ofinv) && !empty($referenceID) && is_numeric($referenceID)) {
	$ofinv = $referenceID;
	$flexpayresult = @mysql_query("SELECT payoptionid FROM payoptions WHERE gateway='verotelflexpay'",$db);
	$payopt = @mysql_result($flexpayresult, 0, "payoptionid");
}

// Get corresponding orderid from IDEAL transaction id...
if (!empty($transaction_id) && ashop_is_md5($transaction_id)) {
	$ofinvresult = @mysqli_query($db,"SELECT orderid FROM orders WHERE remoteorderid='$transaction_id'");
	$ofinv = @mysqli_result($ofinvresult,0,"orderid");
}

// Convert ebayid to valid product string...
if ($_POST["for_auction"] == "true" && $_POST["payment_status"] == "Completed") {
	$sql = "SELECT * FROM payoptions WHERE gateway = 'paypal' ORDER BY payoptionid";
	$result = @mysqli_query($db,"$sql");
	$payoptionid = @mysqli_result($result, 0, "payoptionid");
	$result = @mysqli_query($db,"SELECT productid FROM product WHERE ebayid='{$_POST["item_number"]}'");
	$productid = @mysqli_result($result,0,"productid");
	$result = @mysqli_query($db,"SELECT * FROM parameters WHERE productid='$productid'");
	$attributes = "";
	if (@mysqli_num_rows($result)) for ($i = 0; $i < @mysqli_num_rows($result); $i++) $attributes .= "1b";
	$ebayitem = $_POST["item_number"];
	$_POST["item_number"] = "{$payoptionid}ashoporderstring1b$attributes$productid"."a";
	$basketstring = "1b$attributes$productid"."a";
	$ip_number = "eBay";

	// Add an order record for this payment...
	$sql = "INSERT INTO orders (products) VALUES ('$basketstring')";
	$result = @mysqli_query($db,"$sql");
	$ebayorderid = @mysqli_insert_id($db);

} else {
	$ebayitem = "";
	$ebayorderid = "";
}

// Make sure GET variables from NAB Transact payments are processed...
if (!empty($_GET["bank_reference"]) && !empty($_GET["nab"]) && empty($_GET["payopt"])) {
	$_POST["products"] = $_GET["products"];
	$_POST["bank_reference"] = $_GET["bank_reference"];
	$_POST["payment_amount"] = $_GET["payment_amount"];
	$_POST["payment_date"] = $_GET["payment_date"];
	$_POST["payment_number"] = $_GET["payment_number"];
	$_POST["payment_reference"] = $_GET["payment_reference"];
	$_POST["nab"] = $_GET["nab"];
}

// Get the payment option information...
$gw = "";
foreach ($_POST as $value) {
	if (strstr($value, "ashoporderstring") && !strstr($value, "order.php")) {
		$orderstring = explode("ashoporderstring", $value);
		$payoptionid = $orderstring[0];
		$parsed_products = $orderstring[1];
		if (strstr($parsed_products, "D")) {
			$orderstring = explode("D", $parsed_products);
			$parsed_products = $orderstring[0];
			$discountall = $orderstring[1];
		}
	} 
}
if (!($payoptionid) && $payopt) $payoptionid = $payopt;

// Restore item_number variable for eBay orders...
if (!empty($ebayitem)) $_POST["item_number"] = $ebayitem;

if (!isset($payoptionid)) { 
	header("Location: index.php");
	exit;
}
if ($payoptionid > 0) {
	$sql = "SELECT * FROM payoptions WHERE payoptionid = '$payoptionid'";
	$result = @mysqli_query($db,"$sql");
	$gw = @mysqli_result($result, 0, "gateway");
	$merchantid = @mysqli_result($result, 0, "merchantid");
	if ($gw == "authorizenetaim" || $gw == "authnetecheckaim" || $gw == "authnetaimdelayed" || $gw == "psbill" || $gw == "paypalpdt" || $gw == "transfirst" || $gw == "securenet" || $gw == "micropaymenteb2p" || $gw == "micropaymentcc" || $gw == "micropaymentdd" || $gw == "micropaymentbt") $secret = @mysqli_result($result, 0, "transactionkey");
	else $secret = @mysqli_result($result, 0, "secret");
	$secureipn = @mysqli_result($result, 0, "secure");
	$autodelivery = @mysqli_result($result, 0, "autodelivery");
	$deliverpending = @mysqli_result($result, 0, "deliverpending");
	if($responsemsg == "OL") $payoptionname = "credit card ending in $remoteorderid";
	else $payoptionname = @mysqli_result($result, 0, "name");
	$payoptionfee = @mysqli_result($result, 0, "fee");
	$thankyoutext = @mysqli_result($result, 0, "thankyoutext");
	$smspayment = @mysqli_result($result, 0, "smspayment");
	if ($gw == "2checkoutv2") {
		$twocodemo = @mysqli_result($result, 0, "testmode");
		if ($twocodemo != 1) $_POST["demo"] = "N";
	}
} else {
	$gw = "manual";
	$payoptionname = "none";
}

// Get the right gateway module for the selected currency...
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "admin/gateways$pathprefix/$gw.gw";
if (strpos($gw_extrafields, "localprocessing") === TRUE) $localprocessing = 1;
else $localprocessing = 2;

// Fix image url to avoid security message on certain gateways...
if($resultpagessl == "TRUE") $logourl = @mysqli_result($result, 0, "logourl");
else $logourl = "$ashopurl/images/logo.gif";
if (empty($logourl)) $logourl = "images/logo.gif";

// Clear the shopping cart cookies if they are not empty...
if (($visibleorderprocessing == "TRUE" && $gw != "2checkoutv2") || $payopt) {
	// Get the domain for cookies...
	$ashopurlarray = parse_url($ashopurl);
	$ashopurlhost = $ashopurlarray['host'];
	if (substr($ashopurlhost,0,4) == "www.") $ashopurldomain = substr($ashopurlhost,4);
	else $ashopurldomain = $ashopurlhost;
	header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	if ($basket != "") {
		setcookie ("basket", "",0,'',"$ashopurldomain");
	}
	if ($taxandshipping != "") {
		setcookie ("taxandshipping", "");
	}
	if ($sid != "") {
		setcookie ("sid", "");
	}
	if ($fixbackbutton != "") {
		setcookie ("fixbackbutton", "");
	}
	if (isset($_COOKIE['payopt'])) {
		setcookie ("payopt", "");
	}
	if($resultpagessl == "TRUE") ashop_showtemplateheaderssl("$ashoppath$templatepath/thankyou.html",$logourl);
	else ashop_showtemplateheader("$ashoppath$templatepath/thankyou.html");
}

// Get default language module for non visible order processing...
if ($payopt) {
	$lang = $defaultlanguage;
	include "language/$lang/order.inc.php";
	if ($fromshop > 1 && $orderpagelink) {
		if ($membershops) $orderpagelink = "$ashopurl/index.php?shop=$fromshop";
		else if (!$orderpagelink) $orderpagelink = $ashopurl;
		//else if (strpos("?",$orderpagelink)) $orderpagelink .= "&shop=$fromshop";
		//else $orderpagelink .= "?shop=$fromshop";
		$shopresult = @mysqli_query($db,"SELECT * FROM user WHERE userid='$fromshop'");
		$shopname[1] = @mysqli_result($shopresult, 0, "shopname");
	}
}

if (!$payopt || $emquote) {

	// Parse input...
	if ($_POST["adminkey"]) {
		$parsed_invoice=$_POST['invoice'];
		$parsed_email=ashop_mailsafe($_POST['email']);
		$parsed_firstname=ashop_mailsafe($_POST['firstname']);
		$parsed_lastname=ashop_mailsafe($_POST['lastname']);
		$parsed_address=$_POST['address'];
		$parsed_zip=$_POST['zip'];
		$parsed_city=$_POST['city'];
		$parsed_state=$_POST['state'];
		$parsed_country=$_POST['country'];
		$parsed_remoteorderid=$_POST['orderreference'];
		if (!$parsed_remoteorderid) $parsed_remoteorderid=$_POST['invoice'];
		$parsed_phone=$_POST['phone'];
		$parsed_price=$_POST['amount'];
		$parsed_price=number_format($parsed_price,2,'.','');
	} else {
		$gatewayresponse = parse_response($_POST);
		$parsed_email=ashop_mailsafe($gatewayresponse['email']);
		$parsed_firstname=ashop_mailsafe($gatewayresponse['firstname']);
		$parsed_lastname=ashop_mailsafe($gatewayresponse['lastname']);
		if ($_POST["address"] && $_POST["address2"]) $parsed_address = "{$_POST["address"]}, {$_POST["address2"]}";
		else $parsed_address=$gatewayresponse['address'];
		$parsed_zip=$gatewayresponse['zip'];
		$parsed_city=$gatewayresponse['city'];
		$parsed_state=$gatewayresponse['state'];
		$parsed_country=$gatewayresponse['country'];
		$parsed_phone=$gatewayresponse['phone'];
		$parsed_price=$gatewayresponse['price'];
		$parsed_price=number_format($parsed_price,2,'.','');
		$parsed_affiliate=$gatewayresponse['affiliate'];
		$parsed_remoteorderid=$gatewayresponse['remoteorderid'];
		$parsed_invoice=$gatewayresponse['invoicenum'];
		if (!empty($ebayorderid) && is_numeric($ebayorderid)) $parsed_invoice = $ebayorderid;
	}

	// Protect against duplicate requests...
	$now = time();
	@mysqli_query($db,"INSERT INTO orderrequests (orderid,date) VALUES ('$parsed_invoice','$now')");
	$orderrequestid = @mysqli_insert_id($db);
	sleep(1);
	$orderrequestcheck = @mysqli_query($db,"SELECT MAX(requestid) AS requestid FROM orderrequests WHERE orderid='$parsed_invoice'");
	$lastrequest = @mysqli_result($orderrequestcheck,0,"requestid");
	if ($orderrequestid != $lastrequest) exit;
	else {
		$anhourago = strtotime('1 hour ago');
		@mysqli_query($db,"DELETE FROM orderrequests WHERE date<'$anhourago'");
	}

	// Make sure plus signs are kept in email addresses...
	$parsed_email = str_replace(" ","+",$parsed_email);

	// Check if the customer's email is banned...
	$bannedcheck = @mysqli_query($db,"SELECT * FROM customerblacklist WHERE blacklistitem='$parsed_email'");
	$emaildomain = substr($email,strpos($parsed_email,"@")+1);
	$domainbannedcheck = @mysqli_query($db,"SELECT * FROM customerblacklist WHERE blacklistitem='$emaildomain'");
	if (@mysqli_num_rows($bannedcheck) || @mysqli_num_rows($domainbannedcheck)) $parsed_price = -10.00;

	// Parse additional customer information if available...
	if ($_POST["customerinfo"] && $payoptionid) {
		$htmlcustomerinfo = "";
		$txtcustomerinfo = "";
		$customerinfofields .= "|";
		$customerinfofields = explode("|",$_POST["customerinfo"]);
		if (is_array($customerinfofields)) foreach ($customerinfofields as $fieldnumber=>$fieldstring) {
			$thisfield = explode(":",$fieldstring);
			$field = $thisfield[0];
			$value = $thisfield[1];
			if ($field && $value) $fieldresult = @mysqli_query($db,"SELECT * FROM formfields WHERE name LIKE '$field' AND payoptionid='$payoptionid'");
			if (@mysqli_num_rows($fieldresult)) {
				$fieldrow = @mysqli_fetch_array($fieldresult);
				$fieldlabel = $fieldrow["label"];
				$htmlcustomerinfo .= "$fieldlabel: $value<br>";
				$txtcustomerinfo .= "$fieldlabel: $value\n";
			}
		}
	}
	$sql="SELECT * FROM orders WHERE orderid = '$parsed_invoice'";
	$orderresult = @mysqli_query($db,$sql);
	$ordersource = @mysqli_result($orderresult,0,"source");
	$pricelevel = @mysqli_result($orderresult,0,"pricelevel");
	$subscrsignupcheck = @mysqli_result($orderresult,0,"remoteorderid");
	if (empty($pricelevel)) $pricelevel = 0;
	$orderaffiliate = "";
	$productstringmanipulated = FALSE;
	if (!@mysqli_num_rows($orderresult) && !$_POST["adminkey"]) {
		//$sql = "INSERT INTO orders (orderid) VALUES ('$parsed_invoice')";
		//$result = @mysqli_query($db,"$sql");
		$productstringmanipulated = TRUE;
	} else {
		$virtualcash = @mysqli_result($orderresult,0,"virtualcash");
		$ip_number = @mysqli_result($orderresult,0,"ip");
		$pdiscounts = @mysqli_result($orderresult,0,"productdiscounts");
		$productdiscounts = array();
		if (!empty($pdiscounts)) {
			$pdiscountsarray = explode("|",$pdiscounts);
			if (!empty($pdiscountsarray) && is_array($pdiscountsarray)) foreach ($pdiscountsarray as $pdiscount) {
				$pdiscountarray = explode(":",$pdiscount);
				$pdiscountproductid = $pdiscountarray[0];
				$pdiscountid = $pdiscountarray[1];
				$productdiscounts["$pdiscountproductid"] = $pdiscountid;
			}
		}
		// Check if the customer's IP is banned...
		$bannedcheck = @mysqli_query($db,"SELECT * FROM customerblacklist WHERE blacklistitem='$ip_number'");
		if (@mysqli_num_rows($bannedcheck)) $parsed_price = -1;
		$shop = @mysqli_result($orderresult,0,"userid");

		// Check if the referring affiliate is on file for this order...
		$orderaffiliate = @mysqli_result($orderresult,0,"affiliateid");
		if (is_numeric($orderaffiliate) && $orderaffiliate != $parsed_affiliate) $parsed_affiliate = $orderaffiliate;

		// Check if the date of the referral is on file...
		$orderreferral = @mysqli_result($orderresult,0,"referral");
		if (!is_numeric($orderreferral)) $orderreferral = "";

		// Check if product string has been truncated and replace with original if needed...
		$checkproducts = @mysqli_result($orderresult,0,"products");
		if ($checkproducts != $parsed_products) {
			if (strpos($checkproducts,$parsed_products) !== FALSE) $parsed_products = $checkproducts;
			else $productstringmanipulated = TRUE;
		}
		if (strstr($parsed_products, "D")) {
			$orderstring = explode("D", $parsed_products);
			$parsed_products = $orderstring[0];
			$discountall = $orderstring[1];
		}
		if (substr($shop,0,1) == "|") $shop = substr($shop,1);
		if (substr($shop,-1) == "|") $shop = substr($shop,0,-1);
		$shops = explode("|",$shop);
		$lang = @mysqli_result($orderresult,0,"language");
		$allowemail = @mysqli_result($orderresult,0,"allowemail");
		if (!isset($allowemail)) $allowemail = "0";
		if (!$returnurl) $returnurl = @mysqli_result($orderresult,0,"returnurl");
		if ($returnurl) $orderpagelink = $returnurl;
		if ($shop != "1") {
			$shops = explode("|",$shop);
			foreach ($shops as $shop) {
				$result = @mysqli_query($db,"SELECT * FROM user WHERE userid='$shop'");
				$shopuser["$shop"] = @mysqli_result($result,0,"username");
				$shopname["$shop"] = ashop_mailsafe(stripslashes(@mysqli_result($result,0,"shopname")));
				$shopaddress["$shop"] = stripslashes(@mysqli_result($result,0,"address"))."<br>".stripslashes(@mysqli_result($result,0,"city").", ".@mysqli_result($result,0,"state")." ".@mysqli_result($result,0,"zip"))."<br>".stripslashes(@mysqli_result($result,0,"country"))."<br>";
				$shopemail["$shop"] = ashop_mailsafe(stripslashes(@mysqli_result($result,0,"email")));
				$shopphone["$shop"] = stripslashes(@mysqli_result($result,0,"phone"));
			}
			if (!$returnurl) {
				//if ($membershops && $orderpagelink && strpos("?",$orderpagelink)) $orderpagelink .= "&shop=$shop";
				//else if ($membershops && $orderpagelink) $orderpagelink .= "?shop=$shop";
				if ($membershops) $orderpagelink = "$ashopurl/index.php?shop=$shop";
				else if (!$orderpagelink) $orderpagelink = "$ashopurl";
			}
		} else $shopname[] = ashop_mailsafe($ashopname);
	}
		
	// Include language file...
	if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
	include "language/$lang/order.inc.php";

	// Create DAOPay message if needed...
	if ($gw == "daopay") {
		// Get the country code...
		foreach ($countries as $shortcountry => $longcountry) if ($parsed_country == $longcountry) $daopaycountry = $shortcountry;
		$daopayquerystring = "appcode=$merchantid&orderno=yes&format=hash&country=$daopaycountry&price=$parsed_price";
		if ($smspayment) $daopayquerystring .= "&smspay=true";
		$daopaypostheader = "GET /svc/numgen?$daopayquerystring HTTP/1.0\r\nHost: daopay.com\r\n\r\n";
		$daopayfp = fsockopen ("daopay.com", 80);
		unset($daopayresponse);
		if ($daopayfp) {
			fwrite ($daopayfp, $daopaypostheader);
			while (!feof($daopayfp)) $daopayresponse .= fgets ($daopayfp, 1024);
			fclose ($daopayfp);
		}
		$daopayresponse = explode("\n",$daopayresponse);
		foreach($daopayresponse as $daopaypart) {
			$daopaypair = explode("=",trim($daopaypart));
			if ($daopaypair[0] == "country") $daopaycountry = $daopaypair[1];
			if ($daopaypair[0] == "hint") $daopayhint = $daopaypair[1];
			if ($daopaypair[0] == "ppm") $daopayppm = $daopaypair[1];
			if ($daopaypair[0] == "ppc") $daopayppc = $daopaypair[1];
			if ($daopaypair[0] == "callcost") $daopaycallcost = $daopaypair[1];
			if ($daopaypair[0] == "currency") $daopaycurrency = strtolower($daopaypair[1]);
			if ($daopaypair[0] == "number") $daopaynumber = $daopaypair[1];
			if ($daopaypair[0] == "price") $daopayprice = $daopaypair[1];
			if ($daopaypair[0] == "enterkey") $daopayenterkey = $daopaypair[1];
			if ($daopaypair[0] == "extension") $daopayextension = $daopaypair[1];
			if ($daopaypair[0] == "orderno") $daopayorderno = $daopaypair[1];
			if ($daopaypair[0] == "keyword") $daopaykeyword = $daopaypair[1];
			if ($daopaypair[0] == "tariff") $daopaytariff = $daopaypair[1];
			if ($daopaypair[0] == "smscount") $daopaysmscount = $daopaypair[1];
		}
		if (!$daopayorderno) {
			echo "<table class=\"ashopmessagetable\">
			<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".TRANSNOTAPPROVED."</span></p>
			<p><span class=\"ashopmessage\">".REASON."DaoPay Error</span></p></td></tr></table>";
			ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
			exit;
		} else $parsed_remoteorderid = $daopayorderno;
		if ($smspayment) {
			$daopaymessage = "<br><br><center><table width=\"600\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\"><tr><td width=\"120\" valign=\"top\"><A HREF=\"http://www.daopay.com\"><IMG SRC=\"images/ldaopaysms.jpg\" ALT=\"www.daopay.com\" border=\"0\"></A></td><td>";
			$daopaymessage .= "<font face=\"$font\" size=\"3\"><b>".DAOPAY5."<br><font size=\"5\">$daopaykeyword $daopayorderno</font>";
			if ($daopaysmscount > 1) $daopaymessage .= "<br>$daopaysmscount ".DAOPAY7;
			$daopaymessage .= " ".DAOPAY6."<br><font size=\"5\">$daopaynumber</font> ($daopaytariff $daopaycallcost)<br>".DAOPAY2.": ".$currencysymbols[$daopaycurrency]["pre"]."$daopayprice".$currencysymbols[$daopaycurrency]["post"];
			if ($daopayhint) $daopaymessage .= "<br><br><font color=\"$alertcolor\">$daopayhint</font>";
			$daopaymessage .= "</b></font></td></tr></table></center><br>";
		} else {
			$daopaymessage = "<br><br><center><table width=\"600\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\"><tr><td width=\"120\" valign=\"top\"><A HREF=\"http://www.daopay.com\"><IMG SRC=\"images/ldaopaysms.jpg\" ALT=\"www.daopay.com\" border=\"0\"></A></td><td>";
			$daopaymessage .= "<font face=\"$font\" size=\"3\"><b>".DAOPAY1."<br><font size=\"5\">$daopaynumber</font> ($daopaycallcost)<br>".DAOPAY4."<br><font size=\"5\">$daopayorderno$daopayenterkey</font><br>".DAOPAY2.": ".$currencysymbols[$daopaycurrency]["pre"]."$daopayprice".$currencysymbols[$daopaycurrency]["post"]." ".DAOPAY3;
			if ($daopayhint) $daopaymessage .= "<br><br><font color=\"$alertcolor\">$daopayhint</font>";
			$daopaymessage .= "</b></font></td></tr></table></center><br>";
		}
		$thankyoutext = $daopaymessage.$thankyoutext;
	}

    // Set the payment option name to None when no payment is required...
	if ($payoptionname == "none") $payoptionname = NONE;

	// Check if the order was accepted by the payment processing provider...
	if ($_POST["adminkey"]) {
		if ($_POST["adminkey"] == md5("$databasepasswd$ashoppath"."prelcomplete")) {
			if ($gw == "manual" || $gw == "offline") {
				if ($deliverpending) $authorized[0] = "PROCESS";
				else $authorized[0] = "PENDING";
			} else $authorized[0] = "VERIFIED";
		} else {
			$authorized[0] = "INVALID";
			$authorized[1] = "SUSPECT";
		}
	} else $authorized = authenticate($_POST, $secret, $merchantid);
	if ($pendingpayment) $authorized[0] = "PENDING";

	// Handle subscription signups...
	if ($authorized[0] == "SUBSCRIPTION" && $subscrsignupcheck == "SUBSCRIPTIONSIGNUP") $authorized[0] = "VERIFIED";

	// Make sure the product string can not be manipulated...
	if ($productstringmanipulated) {
		$authorized[0] = "INVALID";
		$authorized[1] = "SUSPECT";
	}

	// Let free orders pass through...
	if ($payoptionid == "0" && $parsed_price == "0.00" && $authorized[0] == "PENDING") $authorized[0] = "VERIFIED";

	// Check if this is a subscription payment...
	$subscriptionpayment = FALSE;
	if ($authorized[0] == "SUBSCRIPTION") {
		$subscriptionpayment = TRUE;
		$authorized[0] = "VERIFIED";
		$checkpaid = @mysqli_result($orderresult,0,"paid");
		if (!empty($checkpaid)) {
			$recurringfee = @mysqli_result($orderresult,0,"recurringfee");
			$checkrecurringresult = @mysqli_query($db,"SELECT MAX(recurringorder) as lastrecurring FROM orders WHERE originalorderid='$parsed_invoice'");
			$recurringorder = @mysqli_result($checkrecurringresult,0,"lastrecurring");
			if (!$recurringorder) $recurringorder = 1;
			$originalorder = $parsed_invoice;			
			ashop_copyrow("orders", "orderid", $parsed_invoice);
			$parsed_invoice = @mysqli_insert_id($db);
			@mysqli_query($db,"UPDATE orders SET date=NULL, paid=NULL, billdate=NULL, duedate=NULL, recurringorder='$recurringorder', originalorderid='$originalorder' WHERE orderid='$parsed_invoice'");
			// Remove store discount...
			$discountall = "";
		}
	}

	if (!$authorized || $authorized[0] == "INVALID") {
		if ($authorized[1] == "MERCHANTID") {
			$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail",un_html($ashopname,1)." - payment could not be verified","The payment gateway could not verify that this was a valid payment, because the Merchant ID did not match the account holder ID of the payment gateway account. If you have recently changed your account, make sure the changes are reflected in your AShop payment option. If you have not changed anything, this could be an indication that someone has tried to circumvent the security of your AShop order processing, but this attempt has been successfully blocked by your AShop. The payment was sent by: $parsed_email, ".stripslashes($parsed_firstname).", ".stripslashes($parsed_lastname)." from IP number: $ip_number, order ID: $parsed_invoice, products: $parsed_products","$headers");
		}

		if ($authorized[1] == "SUSPECT") {
			$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail",un_html($ashopname,1)." - payment could not be verified","The payment gateway could not verify that this was a valid payment. This could be caused by a temporary communication problem or by manipulated payment data. If the payment has been successfully received despite this problem you can complete the order manually through the sales report page in your administration panel. The payment was sent by: $parsed_email, ".stripslashes($parsed_firstname).", ".stripslashes($parsed_lastname)." from IP number: $ip_number, order ID: $parsed_invoice, products: $parsed_products","$headers");
		}

		if ($authorized[1] == "NOCURL") {
			$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail",un_html($ashopname,1)." - PayPal Curl Error","PayPal Curl Error! The Curl PHP extension returned the error message: ".$authorized[2]." during communication with PayPals IPN server! This order was placed by: ".stripslashes($parsed_firstname)." ".stripslashes($parsed_lastname).", email: $parsed_email, ordered products: $parsed_products, placed from: $ip_number","$headers");
		}

		if ($visibleorderprocessing == "TRUE") {
			echo "<table class=\"ashopmessagetable\">
			<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".TRANSNOTAPPROVED."</span></p>
			<p><span class=\"ashopmessage\">".REASON.": {$authorized[1]}</span></p></td></tr></table>";
			ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
		}
		exit;
	}
} else if ($returnurl) $orderpagelink = $returnurl;

if (!$payopt) {

	// Check if the order is not a duplicate...
	$duplicate = "";
	if ($parsed_invoice) {
		$sql="SELECT * FROM orders WHERE orderid = '$parsed_invoice'";
		$result = @mysqli_query($db,$sql);
		$billdate = @mysqli_result($result,0,"billdate");
		if ($billdate && $payoptionid == "0") $receiptformat = "html";
		$duedate = @mysqli_result($result,0,"duedate");
		if (!$billdate) $checkdate = @mysqli_result($result,0,"date");
		else $checkdate = @mysqli_result($result,0,"paid");
		if ($checkdate) $duplicate = "TRUE";
	}

	// Check if PAP is used for affiliate tracking instead of the built in tracking...
	if (!empty($parsed_affiliate) && !empty($pappath)) {
		$papaffiliate = $parsed_affiliate;
		$parsed_affiliate = "";
	}

	// Check if the order contains downloadable goods, tangible goods or subscriptions, check if a third party tool is used, calculate affiliate commission, create description string...
	unset($fulfilmentgroups);
	$downloadgoods = 0;
	$checkprice = 0;
	$totaldiscount = 0;
	$totalpersonaldiscount = 0;
	$provision = 0;
	$provision2 = 0;
	$secondtierprovision = 0;
	$secondtierprovision2 = 0;
	$tierprovision = array();
	$tangiblegoods = 0;
	$listmessengergroups = "";
	$listmaillists = "";
	$iemlists = "";
	$mailchimplists = "";
	$phpbbgroups = "";
	$arpresponders = "";
	$arpreachresponders = "";
	$infresponders = "";
	$infrespondersoff = "";
	$autoresponders = "";
	$autorespondersoff = "";
	$descriptionstring = "";
	$rdescriptionstring = "";
	$adescriptionstring = array();
	$affiliateapidescriptionstring = "";
	$extraadescription = "";
	$subscriptiongoods = 0;
	$alreadysubscribed = "";
	$productprices = "";
	$paidproductprices = "";
	$billnumber = 1;
	unset($pricesarray);
	unset($paidpricesarray);
	unset($commentdescription);
	unset($rcommentdescription);
	unset($acommentdescription);
	unset($productdescription);
	unset($rproductdescription);
	unset($affiliatecommission);
	unset($memberprice);
	unset($memberdiscount);
	unset($memberdescription);
	unset($vendors);
	unset($vendorlist);
	unset($sdescriptionstring);
	$fpmanipulated = FALSE;
	$ffproductinfos = array();
	$saasuitemslist = "";
	$saasuamount = 0.00;
	$skucodes = array();
	$categorydiscountusage = array();
	$switchinvoiceowner = FALSE;
	$activatevendor = FALSE;

	// Get information about eMerchant quotes...
	if (($_POST["adminkey"] && $_POST["emerchantquote"]) || $billdate) {
		if ($billdate) $quoteresult = @mysqli_query($db,"SELECT * FROM emerchant_tempinvoices WHERE orderid='$parsed_invoice'");
		else $quoteresult = @mysqli_query($db,"SELECT * FROM emerchant_quotes WHERE id='{$_POST["emerchantquote"]}'");
		$quoterow = @mysqli_fetch_array($quoteresult);
		if (!$billdate) $quotesource = "eM: {$quoterow["user"]}";
		$quotepricesarray = explode("|",$quoterow["productprices"]);
		if ($quotepricesarray) foreach ($quotepricesarray as $pricepart) {
			$thisquoteprice = explode(":",$pricepart);
			$quoteproductprices["$thisquoteprice[0]"] = $thisquoteprice[1];
		}
		$quotedescriptionstring = "";
		$rquotedescriptionstring = "";
		$quotecommentsamount = 0;
		$quotecommentsarray = explode("|",$quoterow["comments"]);
		$quotecommentpricesarray = explode("|",$quoterow["commentprices"]);
		if ($quotecommentsarray) foreach($quotecommentsarray as $quotecommentnumber=>$quotecommentid) {
			$quotecommentresult = @mysqli_query($db,"SELECT * FROM emerchant_notes WHERE id='$quotecommentid'");
			$quotecommentrow = @mysqli_fetch_array($quotecommentresult);
			if ($quotecommentrow["note"]) {
				$commentdescription[] = $quotecommentrow["note"];
				$rcommentdescription[] = "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">{$quotecommentrow["note"]}</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($quotecommentpricesarray["$quotecommentnumber"],$showdecimals,$decimalchar,$thousandchar)."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($quotecommentpricesarray["$quotecommentnumber"],$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
				$acommentdescription[] = "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td align=\"middle\" width=\"70\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td width=\"363\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">{$quotecommentrow["note"]}</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($quotecommentpricesarray["$quotecommentnumber"],$showdecimals,$decimalchar,$thousandchar)."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($quotecommentpricesarray["$quotecommentnumber"],$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
			}
		}
		if ($quotecommentpricesarray) foreach($quotecommentpricesarray as $quotecommentpricenumber=>$quotecommentprice) $quotecommentsamount += $quotecommentprice;
	} else {
		if (!$parsed_products) {
			echo "No products in order!";
			exit;
		}
	}

	// Get the shipping info and customerid from the database...
	$shipto = "";
	if ($payoptionid != "0" && $billdate) {
		$customerid = @mysqli_result($result, 0, "customerid");
		$result = @mysqli_query($db,"SELECT * FROM shipping WHERE customerid='$customerid'");
		$shippingid = @mysqli_result($result, 0, "shippingid");
		$shippingresultexists = TRUE;
	} else {
		$shippingid = @mysqli_result($result, 0, "customerid");
		$shippingresultexists = FALSE;
	}
	if ($shippingid > 0) {
		if (!$shippingresultexists) {
			$sql="SELECT * FROM shipping WHERE shippingid='$shippingid'";
			$result = @mysqli_query($db,"$sql");
			$customerid = @mysqli_result($result, 0, "customerid");
		}
		$shippingbusiness = @mysqli_result($result, 0, "shippingbusiness");
		$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
		$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
		$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
		$shippingaddress2 = @mysqli_result($result, 0, "shippingaddress2");
		$shippingcity = @mysqli_result($result, 0, "shippingcity");
		$shippingstate = @mysqli_result($result, 0, "shippingstate");
		$shippingzip = @mysqli_result($result, 0, "shippingzip");
		$shippingcountry = @mysqli_result($result, 0, "shippingcountry");
		$vat = @mysqli_result($result, 0, "vat");
		$shippingemail = @mysqli_result($result, 0, "shippingemail");
		if ($shippingemail) {
			if ($shippingemail != $parsed_email) $alternativeemail = $shippingemail;
		}			
		$shippingphone = @mysqli_result($result, 0, "shippingphone");
		if ($shippingphone) {
			$checkphone1 = str_replace(" ","",$shippingphone);
			$checkphone1 = str_replace("(","",$checkphone1);
			$checkphone1 = str_replace(")","",$checkphone1);
			$checkphone1 = str_replace("-","",$checkphone1);
			$checkphone1 = str_replace("[","",$checkphone1);
			$checkphone1 = str_replace("]","",$checkphone1);
			$checkphone1 = str_replace(".","",$checkphone1);
			$checkphone1 = str_replace(",","",$checkphone1);
			$checkphone2 = str_replace(" ","",$parsed_phone);
			$checkphone2 = str_replace("(","",$checkphone2);
			$checkphone2 = str_replace(")","",$checkphone2);
			$checkphone2 = str_replace("-","",$checkphone2);
			$checkphone2 = str_replace("[","",$checkphone2);
			$checkphone2 = str_replace("]","",$checkphone2);
			$checkphone2 = str_replace(".","",$checkphone2);
			$checkphone2 = str_replace(",","",$checkphone2);
			if ($checkphone1 != $checkphone2) $alternativephone = $shippingphone;
		}
		if ($shippingbusiness) $shipto = "$shippingbusiness<br>\r\n$shippingfirstname $shippinglastname<br>\r\n";
		else $shipto = "$shippingfirstname $shippinglastname<br>\r\n";
		$shipto .= "$shippingaddress<br>\r\n";
		if ($shippingaddress2) $shipto .= "$shippingaddress2<br>\r\n";
		$shipto .= "$shippingcity, ";
		if ($shippingstate != "other") $shipto .= "$shippingstate ";
		$shipto .= "$shippingzip<br>\r\n";
		$shipto .= $countries["$shippingcountry"];
		if ($shippingphone) $shipto .= "<br>\r\nPhone: $shippingphone";

		// Store or update customer shipping info in a SAASU file if available...
		if ($saasuwsaccesskey && $saasufileid) $saasushippingcontactid = ashop_saasu_postcontact($shippingfirstname, $shippinglastname, $shippingbusiness, $vat, $shippingemail, $shippingphone, $customerid, $shippingaddress, $shippingzip, $shippingcity, $shippingstate, $countries["$shippingcountry"]);
	}

	// Generate a unique password...
	function makePassword() {
		$alphaNum = array('2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
		$newPass = "";
		srand ((double) microtime() * 1000000);
		$pwLength = "7"; // this sets the limit on how long the password is.
		for($i = 1; $i <=$pwLength; $i++) {
			$newPass .= $alphaNum[(rand(0,31))];
		}
		return ($newPass);
	}

	// Set a unique email address if it is missing...
	if (!$parsed_email) {
		$ashopdomainarray = explode("@", $ashopemail);
		$uniquekey = makePassword();
		$uniquetoken = uniqid($uniquekey);
		$parsed_email = $uniquetoken."@".$ashopdomainarray[1];
	}

	// Make sure all customerinfo is database safe...
	if (!get_magic_quotes_gpc()) {
		$dbsafe_firstname = addslashes($parsed_firstname);
		$dbsafe_lastname = addslashes($parsed_lastname);
		$dbsafe_email = addslashes($parsed_email);
		$dbsafe_address = addslashes($parsed_address);
		$dbsafe_zip = addslashes($parsed_zip);
		$dbsafe_city = addslashes($parsed_city);
		$dbsafe_state = addslashes($parsed_state);
		$dbsafe_country = addslashes($parsed_country);
		$dbsafe_phone = addslashes($parsed_phone);
	} else {
		$dbsafe_firstname = $parsed_firstname;
		$dbsafe_lastname = $parsed_lastname;
		$dbsafe_email = $parsed_email;
		$dbsafe_address = $parsed_address;
		$dbsafe_zip = $parsed_zip;
		$dbsafe_city = $parsed_city;
		$dbsafe_state = $parsed_state;
		$dbsafe_country = $parsed_country;
		$dbsafe_phone = $parsed_phone;
	}

	// Store customerinfo...
	$customerpassword = "";
	if ($customerid > 0) {
		$sql = "SELECT * FROM customer WHERE customerid='$customerid'";
		$result = @mysqli_query($db,"$sql");
		// Check that the customer actually exists and nothing has gone wrong...
		if (!@mysqli_num_rows($result)) {
			$sql = "INSERT INTO customer (firstname, lastname, email, address, zip, city, state, country, phone, preflanguage, allowemail, extrainfo, alternativeemails, alternativephones, affiliateid, referral) VALUES ('$dbsafe_firstname', '$dbsafe_lastname', '$dbsafe_email', '$dbsafe_address', '$dbsafe_zip', '$dbsafe_city', '$dbsafe_state', '$dbsafe_country', '$dbsafe_phone', '$lang', '$allowemail', '$txtcustomerinfo','$alternativeemail','$alternativephone','$parsed_affiliate','$orderreferral')";
			$result = @mysqli_query($db,"$sql");
			$customerid = @mysqli_insert_id($db);
		// If the customer exists, update the customers info...
		} else {
			if (empty($parsed_email)) $parsed_email = @mysqli_result($result,0,"email");
			$customeraffiliate = @mysqli_result($result,0,"affiliateid");
			$customerpassword = @mysqli_result($result,0,"password");

			// Check if the original affiliate referral date for this customer should be used...
			$customerreferral = @mysqli_result($result,0,"referral");
			if (empty($orderreferral) || $orderreferral < $customerreferral) $orderreferral = $customerreferral;

			// Check if the original referring affiliate for this customer should be kept...
			if (!empty($customeraffiliate) && is_numeric($customeraffiliate) && $customeraffiliate != $parsed_affiliate) $parsed_affiliate = $customeraffiliate;

			$alternativephones = @mysqli_result($result,0,"alternativephones");
			if ($alternativephone) {
				if ($alternativephones && !strstr($alternativephones, $alternativephone)) $alternativephones .= ", $alternativephone";
				else $alternativephones = $alternativephone;
			}
			$alternativeemails = @mysqli_result($result,0,"alternativeemails");
			if ($alternativeemail) {
				if ($alternativeemails && !strstr($alternativeemails, $alternativeemail)) $alternativeemails .= ", $alternativeemail";
				else $alternativeemails = $alternativeemail;
			}
			$sql = "UPDATE customer SET";
			if (!empty($dbsafe_firstname)) $sql .= " firstname = '$dbsafe_firstname',";
			if (!empty($dbsafe_lastname))  $sql .= " lastname = '$dbsafe_lastname',";
			if (!empty($dbsafe_address)) $sql .= " address = '$dbsafe_address',";
			if (!empty($dbsafe_zip)) $sql .= " zip = '$dbsafe_zip',";
			if (!empty($dbsafe_city)) $sql .= " city = '$dbsafe_city',";
			if (!empty($dbsafe_state)) $sql .= " state = '$dbsafe_state',";
			if (!empty($dbsafe_country)) $sql .= " country = '$dbsafe_country',";
			if (!empty($dbsafe_phone)) $sql .= " phone = '$dbsafe_phone',";
			if (!empty($lang)) $sql .= " preflanguage = '$lang',";
			$sql .= " allowemail='$allowemail', extrainfo='$txtcustomerinfo', alternativeemails='$alternativeemails', alternativephones='$alternativephones', referral='$orderreferral' WHERE customerid = $customerid";
			$result = @mysqli_query($db,"$sql");
		}
	} else {
		// Check if this is a returning or a new customer...
		$sql = "SELECT * FROM customer WHERE email='$parsed_email' OR alternativeemails LIKE '$parsed_email' OR alternativeemails LIKE '%, $parsed_email' OR alternativeemails LIKE '$parsed_email,%'";
		$result = @mysqli_query($db,"$sql");

		// The customer exists but has not bought any shippable products, update info...
		if (@mysqli_num_rows($result) != 0) {
			$customeraffiliate = @mysqli_result($result,0,"affiliateid");
			$customerpassword = @mysqli_result($result,0,"password");

			// Check if the original affiliate referral date for this customer should be used...
			$customerreferral = @mysqli_result($result,0,"referral");
			if (empty($orderreferral) || $orderreferral < $customerreferral) $orderreferral = $customerreferral;

			// Check if the original referring affiliate for this customer should be kept...
			if (!empty($customeraffiliate) && is_numeric($customeraffiliate) && $customeraffiliate != $parsed_affiliate) $parsed_affiliate = $customeraffiliate;
			$customerid = @mysqli_result($result, 0, "customerid");
			$sql = "UPDATE customer SET";
			if (!empty($dbsafe_firstname)) $sql .= " firstname = '$dbsafe_firstname',";
			if (!empty($dbsafe_lastname))  $sql .= " lastname = '$dbsafe_lastname',";
			if (!empty($dbsafe_address)) $sql .= " address = '$dbsafe_address',";
			if (!empty($dbsafe_zip)) $sql .= " zip = '$dbsafe_zip',";
			if (!empty($dbsafe_city)) $sql .= " city = '$dbsafe_city',";
			if (!empty($dbsafe_state)) $sql .= " state = '$dbsafe_state',";
			if (!empty($dbsafe_country)) $sql .= " country = '$dbsafe_country',";
			if (!empty($dbsafe_phone)) $sql .= " phone = '$dbsafe_phone',";
			if (!empty($lang)) $sql .= " preflanguage = '$lang',";
			$sql .= " allowemail='$allowemail', extrainfo='$txtcustomerinfo', referral='$orderreferral' WHERE customerid = $customerid";
			$result = @mysqli_query($db,"$sql");

		// The customer is new, store customer info...
		} else {
			$sql = "INSERT INTO customer (firstname, lastname, email, address, zip, city, state, country, phone, preflanguage, allowemail, extrainfo, alternativeemails, alternativephones, affiliateid, referral) VALUES ('$dbsafe_firstname', '$dbsafe_lastname', '$dbsafe_email', '$dbsafe_address', '$dbsafe_zip', '$dbsafe_city', '$dbsafe_state', '$dbsafe_country', '$dbsafe_phone', '$lang', '$allowemail', '$txtcustomerinfo', '$alternativeemail', '$alternativephone', '$parsed_affiliate', '$orderreferral')";
			$result = @mysqli_query($db,"$sql");
			$customerid = @mysqli_insert_id($db);
		}
	}

	// Check if there is no original referring affiliate for this customer, but there is one now...
	if (!empty($parsed_affiliate) && empty($customeraffiliate)) {
		$customeraffiliateresult = @mysqli_query($db,"SELECT affiliateid FROM customer WHERE customerid='$customerid'");
		$customeraffiliate = @mysqli_result($customeraffiliateresult,0,"affiliateid");
		if (empty($customeraffiliate)) @mysqli_query($db,"UPDATE customer SET affiliateid='$parsed_affiliate' WHERE customerid='$customerid'");
	}

	// Verify fraud risk through minFraud...
	if (!empty($minfraudkey)) {
		$minfraudscore = ashop_minfraudscore($ip_numbersafe_emailsafe_citysafe_statesafe_zipsafe_country,$parsed_invoice,$customerid,$shippingaddress,$shippingcity,$shippingstate,$shippingzip,$shippingcountry);
		if ($minfraudscore > $minfraudthreshold) $authorized[0] = "PENDING";
	}

	// Check if this is a wholesale customer...
	$checkcustomerresult = @mysqli_query($db,"SELECT level FROM customer WHERE customerid='$customerid'");
	$checkpricelevel = @mysqli_result($checkcustomerresult,0,"level");
	if (empty($checkpricelevel)) $checkpricelevel = 0;
	if ($pricelevel == 0 && $checkpricelevel > 0) {
		$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname,1)." - retail price paid by wholesale customer, refund may be needed","The wholesale customer was not logged in and paid retail price instead of wholesale price. Customer email: $parsed_email, ".stripslashes($parsed_firstname).", ".stripslashes($parsed_lastname).", $parsed_products, $parsed_invoice, $ip_number","$headers");
	}

	// Check if the time limit on affiliate referral for this customer has been exceeded...
	if (!empty($orderreferral) && !empty($referrallength) && $referrallength > 0) {
		$referraltimestamp = strtotime($orderreferral);
		$thisreferrallength = time()-$referraltimestamp;
		$thisreferrallength = round($thisreferrallength/86400);
		if ($thisreferrallength > $referrallength) $parsed_affiliate = "";
	}

	// Get information about purchased products...
	$productsincart = ashop_parseproductstring($db, $parsed_products);
	$totalqty = ashop_totalqty($parsed_products);

	if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
		$thisproductid = $thisproduct["productid"];
		$thisproductcopyof = $thisproduct["copyof"];
		$thisproductowner = $thisproduct["userid"];
		$thisproducttype = $thisproduct["type"];
		$thisquantity = $thisproduct["quantity"];
		$thisproductname = str_replace(",","&#44;",$thisproduct["name"]);
		$thisproductname = str_replace(":","&#58;",$thisproductname);
		$ffproductinfos["$productnumber"]["productid"] = $thisproductid;
		$ffproductinfos["$productnumber"]["name"] = $thisproductname;

		// Get the right price...
		if ($quoteproductprices["$thisproductid"]) $thisprice = $quoteproductprices["$thisproductid"];
		else if ($subscriptionpayment) $thisprice = $thisproduct["recurringprice"];
		else {
			if ($pricelevel < 1) $thisprice = $thisproduct["price"];
			else if ($pricelevel == 1) $thisprice = $thisproduct["wholesaleprice"];
			else {
				$pricelevels = $thisproduct["wspricelevels"];
				$thisprice = $pricelevels[$pricelevel-2];
			}
		}
		if ($thisproduct["recurringprice"]) $thisrecurringprice = $thisproduct["recurringprice"];
		else $thisrecurringprice = $thisproduct["price"];
		
		// Check if this product has been sold at a discounted price...
		if ($thisproduct["discounted"] == "true") {
			if ($thisproduct["storewidediscount"] != "true") {
				$thisdiscountid = $productdiscounts["$thisproductid"];
				$sql="SELECT * FROM discount WHERE productid='$thisproductid' AND discountid='$thisdiscountid'";
				$result = @mysqli_query($db,"$sql");
				if (@mysqli_num_rows($result)) {
					$discounttype = @mysqli_result($result, 0, "type");
					$discountvalue = @mysqli_result($result, 0, "value");
					$discountcode = @mysqli_result($result, 0, "code");
					$discountcustomer = @mysqli_result($result, 0, "customerid");
					$thisproductdiscount = md5($thisproductid.$discountcode."ashopdiscounts");
					if ($discounttype == "%") {
						$thisdiscount = $thisprice * ($discountvalue/100);
						$totaldiscount += $thisquantity * $thisdiscount;
						if ($discountcustomer) $totalpersonaldiscount += $thisquantity * $thisdiscount;
						if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $thisquantity * $thisdiscount;
					} else if ($discounttype == "$") {
						$totaldiscount += $thisquantity * $discountvalue;
						if ($discountcustomer) $totalpersonaldiscount += $thisquantity * $discountvalue;
						if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $thisquantity * $discountvalue;
					}
				} else {
					$sql="SELECT * FROM storediscounts WHERE discountid='$thisdiscountid' AND categoryid!='' AND categoryid IS NOT NULL";
					$result = @mysqli_query($db,"$sql");
					if (@mysqli_num_rows($result)) {
						$discountcategory = @mysqli_result($result, 0, "categoryid");
						$result2 = @mysqli_query($db,"SELECT * FROM productcategory WHERE productid='$thisproductid' AND categoryid='$discountcategory'");
						if (@mysqli_num_rows($result2)) {
							$discounttype = @mysqli_result($result, 0, "type");
							$discountvalue = @mysqli_result($result, 0, "value");
							$discountcode = @mysqli_result($result, 0, "code");
							$discountused = @mysqli_result($result, 0, "used");
							$discountid = @mysqli_result($result, 0, "discountid");

							// Update usage stats...
							if (!in_array($discountid,$categorydiscountusage)) {
								$discountused++;
								@mysqli_query($db,"UPDATE storediscounts SET used='$discountused' WHERE discountid='$discountid'");
								$categorydiscountusage[] = $discountid;
							}

							$thisproductdiscount = md5($discountcode."ashopdiscounts");
							if ($discounttype == "%") {
								$thisdiscount = $thisprice * ($discountvalue/100);
								$totaldiscount += $thisquantity * $thisdiscount;
								if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $thisquantity * $thisdiscount;
							} else if ($discounttype == "$") {
								$totaldiscount += $thisquantity * $discountvalue;
								if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $thisquantity * $discountvalue;
							}
						}
					}
				}
			}
			$thisproductname .= " (discounted)";
		}
		$pricesarray["$thisproductid"] = $thisprice;
		$recurringpricesarray["$thisproductid"] = $thisrecurringprice;
		$thissku = $thisproduct["sku"];
		if (!empty($thissku)) $skucodes[$productnumber] = $thissku;
		else $skucodes[$productnumber] = "0000$productnumber";

		// Remove items from inventory...
		if ($thisproduct["useinventory"]) {
			@mysqli_query($db,"UPDATE product SET inventory=inventory-{$thisquantity} WHERE skucode='$thissku'");
			@mysqli_query($db,"UPDATE productinventory SET inventory=inventory-{$thisquantity} WHERE skucode='$thissku'");
		}

		// Check if this is a floating price product and if this customer is allowed to buy it...
		if ($thisproduct["fporder"]) {
			if ($thisquantity > 1 || ($thisproduct["fporder"] != $parsed_invoice)) $fpmanipulated = TRUE;
			else {
				@mysqli_query($db,"UPDATE product SET active='0' WHERE productid='$thisproductid'");
				@mysqli_query($db,"UPDATE floatingprice SET orderid='', starttime='0', endprice=NULL, bids=NULL, bidderid=NULL WHERE productid='$thisproductid'");
			}
		}

		// Set quantity for subtotal calculation...
		if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $thisquantity;
		else {
			if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
			else $subtotalqty = ashop_categoryqty($db, $parsed_products, $thisproduct["qtycategory"]);
		}
		$qtypricingresult = @mysqli_query($db,"SELECT * FROM qtypricelevels WHERE productid='$thisproductid'");
		$qtypricing = @mysqli_num_rows($qtypricingresult);
		$result = @mysqli_query($db,"SELECT * FROM product WHERE productid='$thisproductid'");
		$shipping = @mysqli_result($result,0,"shipping");
		$producttype = @mysqli_result($result,0,"prodtype");
		if ($subscriptionpayment && $producttype == "subscription") $producttype = "";
		$vendorid = @mysqli_result($result,0,"vendorid");
		$thisproductreceipttext = @mysqli_result($result,0,"receipttext");
		if ($vendorid) {
			if (!is_array($vendors) || !in_array($vendorid,$vendors)) {
				$vendors[] = $vendorid;
				$vendorlist .= "|$vendorid|";
			}
		}
		if ($thisquantity > 0) {
			$thislistmessengergroup = @mysqli_result($result,0,"listmessengergroup");
			$thislistmaillist = @mysqli_result($result,0,"listmaillist");
			$thisiemlist = @mysqli_result($result,0,"iemlist");
			$thismailchimplist = @mysqli_result($result,0,"mailchimplist");
			$thisphpbbgroup = @mysqli_result($result,0,"phpbbgroup");
			$thisarpresponder = @mysqli_result($result,0,"arpresponder");
			$thisarpreachresponder = @mysqli_result($result,0,"arpreachresponder");
			$thisinfresponder = @mysqli_result($result,0,"infresponder");
			$thisinfresponderoff = @mysqli_result($result,0,"infresponderoff");
			$thisautoresponder = @mysqli_result($result,0,"autoresponder");
			$thisautoresponderoff = @mysqli_result($result,0,"autoresponderoff");
			$checkfiles = @mysqli_query($db,"SELECT * FROM productfiles WHERE productid='$thisproductid'");
			$files = @mysqli_num_rows($checkfiles);
			$subscriptiondir = @mysqli_result($result,0,"subscriptiondir");
			$fulfilmentoption = @mysqli_result($result,0,"fulfilment");
			$ffproductid = @mysqli_result($result,0,"ffproductid");
			$fflabelnumber = @mysqli_result($result,0,"fflabelnumber");
			$ffpackagenumber = @mysqli_result($result,0,"ffpackagenumber");
			$ffparamnames = explode("|",@mysqli_result($result,0,"ffparamnames"));
			unset($ffparamquerystring);
			if(is_array($thisproduct["parametervalues"])) foreach ($thisproduct["parametervalues"] as $ffparamnumber => $ffparamvalue) {
				$ffparamquerystring .= $ffparamnames[$ffparamnumber]."=$ffparamvalue&";
				if ($thisproduct["producttype"] == "mallfee" && is_numeric($ffparamvalue)) $activatevendor = $ffparamvalue;
			}
			$ffparamquerystring = substr($ffparamquerystring, 0, -1);
		}
		if ($fulfilmentoption && !$thisproduct["disablefulfilment"]) {
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["productid"] = $thisproduct["productid"];
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["sku"] = $thisproduct["sku"];
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["name"] = $thisproductname.$thisproduct["parameters"];
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["ebayid"] = $thisproductname.$thisproduct["ebayid"];
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["quantity"] = $thisquantity;
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["price"] = $thisprice;
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffproductid"] = $ffproductid;
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["fflabelnumber"] = $fflabelnumber;
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffpackagenumber"] = $ffpackagenumber;
			$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffparamquerystring"] = $ffparamquerystring;
			if (substr_count($ffparamquerystring,"email")) $unlockkeymultiplier["$thisproductid"] = substr_count($ffparamquerystring,"email");
		}
		if (($_POST["adminkey"] && $_POST["emerchantquote"]) || $billdate) {
			if ($thisproductowner > "1" && !$switchinvoiceowner) {
				$switchownerresult = @mysqli_query($db,"SELECT * FROM user WHERE userid='$thisproductowner'");
				$ashopname = @mysqli_result($switchownerresult, 0, "shopname");
				$ashopemail = @mysqli_result($switchownerresult, 0, "email");
				$ashopphone = @mysqli_result($switchownerresult, 0, "phone");
				$ashopaddress = @mysqli_result($switchownerresult, 0, "address").", ".@mysqli_result($switchownerresult, 0, "city").", ".@mysqli_result($switchownerresult, 0, "state")." ".@mysqli_result($switchownerresult, 0, "zip");
				$switchinvoiceowner = TRUE;
			}
			if ($thissku) $productdescription[] = $thisquantity.": ".$thisproductname.$thisproduct["parameters"]." ($thissku)";
			else $productdescription[] = $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
			$rproductdescription[] = "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"]."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($thisprice,$showdecimals,$decimalchar,$thousandchar)."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format(ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]),$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
			$aproductdescription["$productnumber"] = "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td align=\"middle\" width=\"70\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thissku</font></td><td width=\"363\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"]."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($thisprice,$showdecimals,$decimalchar,$thousandchar)."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format(ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]),$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
			if ($thisproductowner > 1) {
				if ($memberdescription[$thisproductowner]) $memberdescription[$thisproductowner] .= ", ".$thisquantity.": ".$thisproductname.$thisproduct["parameters"];
				else $memberdescription[$thisproductowner] .= $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
			}
			if($thisproduct["userid"] > 1) {
				$sdescriptionstring[$thisproduct["userid"]] .= "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"]."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
				if ($qtypricing) $sdescriptionstring[$thisproduct["userid"]] .= "QtyBased";
				else $sdescriptionstring[$thisproduct["userid"]] .= number_format($thisprice,$showdecimals,$decimalchar,$thousandchar);
				$sdescriptionstring[$thisproduct["userid"]] .= "</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format(ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]),$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";				
			}
		} else {
			$descriptionstring .= $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
			$affiliateapidescriptionstring .= $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
			if ($thissku) {
				$orderby = $productnumber;
				$descriptionstring .= " ($thissku)";
				if ($thisproductreceipttext) $descriptionstring .= " - $thisproductreceipttext";
			} else $orderby = $productnumber;
			if ($thisproductowner > 1) {
				if ($memberdescription[$thisproductowner]) $memberdescription[$thisproductowner] .= ", ".$thisquantity.": ".$thisproductname.$thisproduct["parameters"];
				else $memberdescription[$thisproductowner] .= $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
			}
			if($thisproduct["userid"] > 1) {
				$sdescriptionstring[$thisproduct["userid"]] .= "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td align=\"middle\" width=\"70\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thissku</font></td><td width=\"363\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"]."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
				if ($qtypricing) $sdescriptionstring[$thisproduct["userid"]] .= "QtyBased";
				else $sdescriptionstring[$thisproduct["userid"]] .= number_format($thisprice,$showdecimals,$decimalchar,$thousandchar);
				$sdescriptionstring[$thisproduct["userid"]] .= "</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format(ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]),$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";				
			}
			$rdescriptionstring .= "<tr><td align=\"middle\" width=\"30\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"];
			if ($thisproductreceipttext) $rdescriptionstring .= "<br>$thisproductreceipttext";
			$rdescriptionstring .= "</font></td><td align=\"right\" width=\"60\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
			$adescriptionstring["$orderby"] = "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td align=\"middle\" width=\"70\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thissku</font></td><td width=\"363\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"]."</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
			if ($qtypricing) {
				$rdescriptionstring .= "QtyBased";
				$adescriptionstring["$orderby"] .= "QtyBased";
			} else {
				$rdescriptionstring .= number_format($thisprice,$showdecimals,$decimalchar,$thousandchar);
				$adescriptionstring["$orderby"] .= number_format($thisprice,$showdecimals,$decimalchar,$thousandchar);
			}
			$rdescriptionstring .= "</font></td><td align=\"right\" width=\"60\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format(ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]),$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
			$adescriptionstring["$orderby"] .= "</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format(ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]),$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
			if (count($productsincart) > 1 && $productnumber < count($productsincart)-1) {
				$descriptionstring .= "\r\n";
				$affiliateapidescriptionstring .= ", ";
			}
		}
		if ($thislistmessengergroup) $listmessengergroups .= $thislistmessengergroup."a";
		if ($thislistmaillist) $listmaillists .= $thislistmaillist."a";
		if ($thisiemlist) $iemlists .= $thisiemlist."a";
		if ($thismailchimplist) $mailchimplists .= $thismailchimplist."a";
		if ($thisphpbbgroup) $phpbbgroups .= $thisphpbbgroup."a";
		if ($thisarpresponder) $arpresponders .= $thisarpresponder."a";
		if ($thisarpreachresponder) $arpreachresponders .= $thisarpreachresponder."a";
		if ($thisinfresponder) $infresponders .= $thisinfresponder."a";
		if ($thisinfresponderoff) $infrespondersoff .= $thisinfresponderoff."a";
		if ($thisautoresponder) $autoresponders .= $thisautoresponder."a";
		if ($thisautoresponderoff) $autorespondersoff .= $thisautoresponderoff."a";
		if ($shipping) $tangiblegoods = 1;
		$safethisproductname = @mysqli_real_escape_string($db, $thisproductname.$thisproduct["parameters"]);
		if (empty($thissku)) $shippingsku = $thisproductid;
		else $shippingsku = $thissku;
		if ($thisproduct["shipping"] && $thisproduct["disableshipping"] != 1) @mysqli_query($db,"INSERT INTO shippingstatus (orderid, productname, skucode, quantity, status) VALUES ('$parsed_invoice', '$safethisproductname', '$shippingsku', '$thisquantity', '0')");
		if ($files && $thisproduct["download"] != "none") $downloadgoods = 1;
		if ($subscriptiondir && $producttype == "subscription") {
			$subscriptiongoods = 1;
			// Check for Password Robot directory...
			if ($subscriptiondir && $probotpath && file_exists("$probotpath/data/groups.txt")) {
				$fp = fopen ("$probotpath/data/groups.txt","r");
				while (!feof ($fp)) {
					$fileline = rtrim(fgets($fp, 4096));
					$probotgrouparray = explode("\t",$fileline);
					if ($probotgrouparray[2] == $subscriptiondir) $prgroupname = $probotgrouparray[1];
				}
			}
			if ($subscriptiondir && (file_exists("$ashoppath/$subscriptiondir/.htpasswd") || ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt")) || ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt")))) {
				if ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt")) $fp = fopen ("$probotpath/data/groups/$prgroupname.txt","r");
				else if ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt")) $fp = fopen ("$papluspath/$subscriptiondir/d_pass.txt","r");
				else $fp = fopen ("$ashoppath/$subscriptiondir/.htpasswd","r");
				while (!feof ($fp)) {
					$fileline = fgets($fp, 4096);
					if (strstr($fileline,"$parsed_email")) $subscriberfound = "TRUE";
				}
				fclose($fp);
			}
			if ($subscriberfound) $alreadysubscribed .= "TRUE";
			else $alreadysubscribed .= "FALSE";
		}
		$percentstorediscount = 0;
		$thissubtotal = ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]);
		$paidpricesarray["$thisproductid"] = $thissubtotal;
		if ($percentstorediscount > 0) $totaldiscount += $percentstorediscount;

		// Set SAASU item XML code...
		if ($saasuwsaccesskey && $saasufileid) {
			$fullprice = $thisquantity*$thisprice;
			if ($fullprice > $thissubtotal) {
				$difference = $fullprice-$thissubtotal;
				$percentagediscount = ($difference/$fullprice)*100;
			} else $percentagediscount = "";
			$thissaasuitemid = ashop_saasu_getitemuid($thissku);
			if ($thissaasuitemid && $thissaasuitemid != "nodata") {
				$saasuamount += $thissubtotal;
				$saasuitemslist .= "<itemInvoiceItem>
					<quantity>$thisquantity</quantity>
					<inventoryItemUid>$thissaasuitemid</inventoryItemUid>
					<description>$thisproductname</description>
					<taxCode>$saasutaxcode</taxCode>
					<unitPriceInclTax>$thisprice</unitPriceInclTax>
					<percentageDiscount>$percentagediscount</percentageDiscount>
				</itemInvoiceItem>
				";
			}
		}

		if ($thisproductowner > 1) {
			$ownercommissionresult = @mysqli_query($db,"SELECT commissionlevel FROM user WHERE userid='$thisproductowner'");
			$ownercommissionlevel = @mysqli_result($ownercommissionresult,0,"commissionlevel");
			if (empty($ownercommissionlevel) && !is_numeric($ownercommissionlevel)) $ownercommissionlevel = $memberpercent;
			$thisaffsubtotal = $thissubtotal * ($ownercommissionlevel/100);
		} else $thisaffsubtotal = $thissubtotal;

		// Subtract any storewide percentage discounts...
		$thisaffsubtotal -= $percentstorediscount;

		// Check if repeat order should give affiliate commission and if this is a repeat order...
		$disablerepeatcom = 0;
		if ($thisproduct["affiliaterepeatcommission"] == "0") {
			$checkrepeatorder = @mysqli_query($db,"SELECT orderid FROM orders WHERE (products LIKE '%b{$thisproductid}a%' OR products LIKE '%b{$thisproductid}d') AND customerid='$customerid' AND orderid != '$parsed_invoice' LIMIT 1");
			$disablerepeatcom = @mysqli_num_rows($checkrepeatorder);
		}

		// Calculate level 1 affiliate commission...
		if ($pricelevel < 1) {
			$thisaffcomtype = $thisproduct["affiliatecomtype"];
			$thisaffcom = $thisproduct["affiliatecom"];
		} else if ($wholesaleaffiliate == "1") {
			$thisaffcomtype = $thisproduct["wholesalecomtype"];
			$thisaffcom = $thisproduct["wholesalecom"];
		}
		if ($thisaffcomtype == "percent" && !$disablerepeatcom) $provision += $thisaffsubtotal * ($thisaffcom/100);
		else if ($thisaffcomtype == "money" && !$disablerepeatcom) $provision += $thisquantity * $thisaffcom;

		// Calculate level 2 affiliate commission...
		if ($pricelevel < 1) {
			$thisaffcomtype = $thisproduct["affiliatecomtype2"];
			$thisaffcom = $thisproduct["affiliatecom2"];
			if ($thisaffcomtype == "percent" && !$disablerepeatcom) $provision2 += $thisaffsubtotal * ($thisaffcom/100);
			else if ($thisaffcomtype == "money" && !$disablerepeatcom) $provision2 += $thisquantity * $thisaffcom;

			// Calculate level 1 second tier commissions...
			$thistier2affcomtype = $thisproduct["affiliatetier2comtype"];
			$thistier2affcom = $thisproduct["affiliatetier2com"];
			if ($thistier2affcomtype == "percent" && !$disablerepeatcom) $secondtierprovision += $thisaffsubtotal * ($thistier2affcom/100);
			else if ($thistier2affcomtype == "money" && !$disablerepeatcom) $secondtierprovision += $thisquantity * $thistier2affcom;

			// Calculate multi tier commissions...
			$thisaffiliatetierlowerby = $thisproduct["affiliatetierlowerby"];
			if (!empty($thisaffiliatetierlowerby) && is_numeric($thisaffiliatetierlowerby) && $thisaffiliatetierlowerby > 0 && !$disablerepeatcom) {
				$tier = 3;
				$thistieraffcom = $thistier2affcom - $thisaffiliatetierlowerby;
				if ($thistier2affcomtype == "percent") $thistierprovision = $thisaffsubtotal * ($thistieraffcom/100);
				else if ($thistier2affcomtype == "money") $thistierprovision = $thisquantity * $thistieraffcom;
				while ($thistieraffcom > 0) {
					$tierprovision[$tier] += $thistierprovision;
					$tier++;
					$thistieraffcom -= $thisaffiliatetierlowerby;
					if ($thistier2affcomtype == "percent") $thistierprovision = $thisaffsubtotal * ($thistieraffcom/100);
					else if ($thistier2affcomtype == "money") $thistierprovision = $thisquantity * $thistieraffcom;
					if (!empty($maxaffiliatetiers) && $maxaffiliatetiers > 0 && $tier > $maxaffiliatetiers) $thistieraffcom = 0;
				}
			}
			
			// Calculate level 2 second tier commissions...
			$thistier2affcomtype = $thisproduct["affiliatetier2comtype2"];
			$thistier2affcom = $thisproduct["affiliatetier2com2"];
			if ($thistier2affcomtype == "percent" && !$disablerepeatcom) $secondtierprovision2 += $thisaffsubtotal * ($thistier2affcom/100);
			else if ($thistier2affcomtype == "money" && !$disablerepeatcom) $secondtierprovision2 += $thisquantity * $thistier2affcom;
		}

		$checkprice += $thissubtotal;

		// Store separate amounts for the owner shops...
		if ($thisproductowner > 1) {
			if ($thisaffcomtype == "percent") $affiliatecommission[$thisproductowner] += $thisaffsubtotal * ($thisaffcom/100);
			else if ($thisaffcomtype == "money") $affiliatecommission[$thisproductowner] += $thisquantity * $thisaffcom;
			if ($thisaffcom) $thissecondtiercommission = $thisaffsubtotal * ($secondtierperc/100);
			if ($thissecondtiercommission) $affiliatecommission[$thisproductowner] += $thissecondtiercommission;
			$memberprice[$thisproductowner] += $thissubtotal;
		}
		
		// Handle eMerchant AutoBills...
		if (file_exists("$ashoppath/emerchant/quote.php") && $thisproduct["billtemplate"] && !$_POST["adminkey"]) {
			$billtemplateresult = @mysqli_query($db,"SELECT * FROM emerchant_billtemplates WHERE billtemplateid='{$thisproduct["billtemplate"]}' AND templatetype='autobill'");
			$billtemplaterow = @mysqli_fetch_array($billtemplateresult);
			$billduedays = $billtemplaterow["duedays"];
			$thisdate = date("Y-m-d", time()+$timezoneoffset);
			$thisdatelong = date("Y-m-d H:i:s", time()+$timezoneoffset);
			$billduedatearray = explode("-",$thisdate);
			$billduedatetimestamp = mktime(0,0,0,$billduedatearray[1],$billduedatearray[2]+$billduedays,$billduedatearray[0]);
			$billduedate = date("Y-m-d",$billduedatetimestamp);
			$billreminderdays = $billtemplaterow["reminderdays"];
			$billremindertimestamp = mktime(0,0,0,$billduedatearray[1],$billduedatearray[2]+$billduedays-$billreminderdays,$billduedatearray[0]);
			$billreminderdate = date("Y-m-d",$billremindertimestamp);
			$billremindermessage = str_replace("'","\'",$billtemplaterow["remindermessage"]);
			$billpastduedays = $billtemplaterow["pastduedays"];
			$billpastduetimestamp = mktime(0,0,0,$billduedatearray[1],$billduedatearray[2]+$billduedays+$billpastduedays,$billduedatearray[0]);
			$billpastduedate = date("Y-m-d",$billpastduetimestamp);
			$billpastduemessage = str_replace("'","\'",$billtemplaterow["pastduemessage"]);
			$billrecurring = $billtemplaterow["recurring"];
			$billrecurringtimes = $billtemplaterow["recurringtimes"];
			$billproducts = $thisproduct["segment"];
			$billqty = ashop_totalqty($billproducts);
			$billprice = $billqty*$recurringpricesarray["$thisproductid"];
			$billproductprices = $thisproductid.":".$recurringpricesarray["$thisproductid"];
			$billsendbilldays = $billtemplaterow["sendbilldays"];
			if (!$billsendbilldays) $billsendbilldays = 0;
			@mysqli_query($db,"INSERT INTO emerchant_tempinvoices (orderid, billnumber, duedate, products, productprices, itemorder, price) values ('$parsed_invoice', '$billnumber', '$billduedate', '$billproducts', '$billproductprices', 'p', '$billprice')");
			@mysqli_query($db,"INSERT INTO emerchant_bills (orderid, billnumber, reminderdate, pastduedate, recurring, pastduemessage, remindermessage, recurringtimes, sendbilldays) VALUES ('$parsed_invoice', '$billnumber', '$billreminderdate', '$billpastduedate', '$billrecurring', '$billpastduemessage', '$billremindermessage', '$billrecurringtimes', '$billsendbilldays')");
			@mysqli_query($db,"UPDATE orders SET billdate='$thisdatelong' WHERE orderid='$parsed_invoice'");
			$billnumber++;
		}
	}

	// Create description strings for eMerchant quotes...
	if (($_POST["adminkey"] && $_POST["emerchantquote"]) || $billdate) {
		if (is_array($productdescription)) reset($productdescription);
		if (is_array($rproductdescription)) reset($rproductdescription);
		if (is_array($commentdescription)) reset($commentdescription);
		if (is_array($rcommentdescription)) reset($rcommentdescription);
		if (is_array($acommentdescription)) reset($acommentdescription);
		for ($ch = 0; $ch < strlen($quoterow["itemorder"]); $ch++) {
			if (substr($quoterow["itemorder"],$ch,1) == "p") {
				$descriptionstring .= current($productdescription).", ";
				$rdescriptionstring .= current($rproductdescription);
				next($productdescription);
				next($rproductdescription);
			} else if (substr($quoterow["itemorder"],$ch,1) == "c") {
				$descriptionstring .= current($commentdescription).", ";
				$rdescriptionstring .= current($rcommentdescription);
				$extraadescription .= current($acommentdescription);
				next($commentdescription);
				next($rcommentdescription);
				next($acommentdescription);
			}
		}
		$descriptionstring = substr_replace($descriptionstring, "", -2);
	}
	unset($emerchantcomment);
	$emcommentresult = @mysqli_query($db,"SELECT * FROM emerchant_bills WHERE orderid='$parsed_invoice'");
	$emcommentrow = @mysqli_fetch_array($emcommentresult);
	$emerchantcomment = $emcommentrow["billcomment"];
	if ($emerchantcomment) {
		$emerchantcomment = str_replace("%orderid%",$parsed_invoice,$emerchantcomment);
		$emerchantcomment = str_replace("%duedate%",$duedate,$emerchantcomment);
		$emerchantcomment = str_replace("%startdate%",$emcommentrow["startdate"],$emerchantcomment);
		$emerchantcomment = str_replace("%enddate%",$emcommentrow["enddate"],$emerchantcomment);
		$descriptionstring .= ", $emerchantcomment";
		$rdescriptionstring .= "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">{$emerchantcomment}</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td></tr>\r\n";
		$extraadescription .= "<tr><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td align=\"middle\" width=\"70\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td width=\"363\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">{$emerchantcomment}</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">&nbsp;</font></td></tr>\r\n";
	}
	
	// Apply storewide discount if needed...
	if (!empty($discountall) && is_numeric($discountall)) {

		// Update usage stats...
		$storediscountresult = @mysqli_query($db,"SELECT used FROM storediscounts WHERE discountid='$discountall'");
		$storediscountused = @mysqli_result($storediscountresult,0,"used");
		$storediscountused++;
		@mysqli_query($db,"UPDATE storediscounts SET used='$storediscountused' WHERE discountid='$discountall'");

		// Apply amount discount to the order...		
		$storediscountresult = @mysqli_query($db,"SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='$'");
		if (@mysqli_num_rows($storediscountresult)) {
			$discountvalue = @mysqli_result($storediscountresult, 0, "value");
			$totaldiscount += $discountvalue;
			$giftcertificate = @mysqli_result($storediscountresult, 0, "giftcertificate");
			if ($discountvalue) {
				$newdiscountvalue = $discountvalue - $checkprice;
				if ($discountvalue > $checkprice) {
					$discountvalue = $checkprice;
					$nogift = TRUE;
				}
				$checkprice -= $discountvalue;
				if ($giftcertificate == "1") {
					if ($newdiscountvalue < 0) $newdiscountvalue = 0;
					@mysqli_query($db,"UPDATE storediscounts SET value='$newdiscountvalue' WHERE discountid='$discountall'");
				}
				$rdescriptionstring .= "<tr><td align=\"middle\" width=\"30\">&nbsp;</td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".DISCOUNT."</font></td><td align=\"right\" width=\"60\">&nbsp;</td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">-".number_format($discountvalue,$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
				$extraadescription .= "<tr><td align=\"middle\" width=\"30\">&nbsp;</td><td align=\"middle\" width=\"70\">&nbsp;</td><td width=\"363\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".DISCOUNT."</font></td><td align=\"right\" width=\"60\">&nbsp;</td><td align=\"right\" width=\"60\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">-".number_format($discountvalue,$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>\r\n";
			}
		}
	}
	
	// Make sure the right product description format is used...
	if ($receiptformat == "txt") {
		$rdescriptionstring = str_replace("&#44;",",",$descriptionstring);
		$rdescriptionstring = str_replace("&#58;",":",$rdescriptionstring);
	}

	// Store current prices for future reference...
	if ($_POST["adminkey"] && $_POST["emerchantquote"]) {
		$productprices = $quoterow["productprices"];
		$paidproductprices = $productprices;
	} else {
		if ($pricesarray) foreach($pricesarray as $productid => $price) $productprices .= "$productid:$price|";
		$productprices = substr($productprices,0,-1);
		if ($paidpricesarray) foreach($paidpricesarray as $productid => $paidprice) $paidproductprices .= "$productid:$paidprice|";
		$paidproductprices = substr($paidproductprices,0,-1);
	}
	
	// Get shipping and tax...
	$handlingcosts = ashop_gethandlingcost($parsed_products);
	$shippingcost = $handlingcosts["shipping"];
	$salestax = $handlingcosts["salestax"];
	$shippingdiscount = $handlingcosts["discount"];
	// Apply free shipping discount if needed...
	if ($discountall) {
		$storediscountresult = @mysqli_query($db,"SELECT * FROM storediscounts WHERE discountid='$discountall' AND type='s'");
		if (@mysqli_num_rows($storediscountresult)) $shippingdiscount += $shippingcost;
	}
	$totaldiscount += $shippingdiscount;
	if ($_POST["adminkey"] && $_POST["emerchantquote"]) unset($shippingdiscount);

	// Store shipping cost as a SAASU item if necessary...
	if ($shippingcost && $saasuwsaccesskey && $saasufileid) {
		if ($shippingdiscount) $percentagediscount = ($shippingdiscount/$shippingcost)*100;
		else $percentagediscount = "";
		$thissaasuitemid = ashop_saasu_getitemuid("SHIPPING");
		if ($thissaasuitemid && $thissaasuitemid != "nodata") {
			$saasuamount += $shippingcost;
			$saasuitemslist .= "<itemInvoiceItem>
			<quantity>1</quantity>
			<inventoryItemUid>$thissaasuitemid</inventoryItemUid>
			<description>Shipping</description>
			<taxCode>$saasutaxcode</taxCode>
			<unitPriceInclTax>$shippingcost</unitPriceInclTax>
			<percentageDiscount>$percentagediscount</percentageDiscount>
			</itemInvoiceItem>
			";
		}
	}

	// Get selected shipping options...
	$shipoptionsfee = 0;
	$selectedshipoptions = "";
	if ($handlingcosts) foreach($handlingcosts as $handlingname => $value) {
		if (strstr($handlingname, "so")) {
			if ($shipoptionstype == "custom") {
				$result = @mysqli_query($db,"SELECT * FROM shipoptions WHERE shipoptionid='$value'");
				$shipoptionsfee += @mysqli_result($result, 0, "fee");
				if ($selectedshipoptions) $selectedshipoptions .= ", ";
				$selectedshipoptions .= @mysqli_result($result, 0, "description");
			} else if ($shipoptionstype == "ups") {
				$checkupscountry = trim(strtoupper($parsed_country));
				if ($checkupscountry == "CANADA" || $checkupscountry == "CA" || $checkupscountry == "CAN") $upstocountry = "CA";
				else if ($checkupscountry == "USA" || $checkupscountry == "US" || $checkupscountry == "UNITED STATES" || $checkupscountry == "UNITED STATES OF AMERICA") $upstocountry = "US";
				if ($upscountry == "US") {
					if ($upstocountry == "US") $upsshipoptions = $upsservicesusa;
					else if ($upstocountry == "CA") $upsshipoptions = $upsservicestocan;
					else $upsshipoptions = $upsservicesworld;
				} else if ($upscountry == "CA") {
					if ($upstocountry == "CA") $upsshipoptions = $upsservicescan;
					else if ($upstocountry == "US") $upsshipoptions = $upsservicestousa;
					else $upsshipoptions = $upsservicesworld;
				}
				if ($selectedshipoptions) $selectedshipoptions .= ", ";
				$selectedshipoptions .= $upsshipoptions[$value];
			} else if ($shipoptionstype == "fedex") {
				$checkfedexcountry = trim(strtoupper($parsed_country));
				if ($checkfedexcountry == "USA" || $checkfedexcountry == "US" || $checkfedexcountry == "UNITED STATES" || $checkfedexcountry == "UNITED STATES OF AMERICA") $fedextocountry = "US";
				if ($fedextocountry == "US") $fedexshipoptions = $fedexservicesusa;
				else $fedexshipoptions = $fedexservicesworld;
				if ($selectedshipoptions) $selectedshipoptions .= ", ";
				$selectedshipoptions .= $fedexshipoptions[$value];
			}
		}
	}

	// Set shipping and tax for eBay orders...
	if (!empty($ebayorderid) && is_numeric($ebayorderid) && !empty($ebayitem)) {
		$shippingcost = $_POST["shipping"];
		$salestax = $_POST["tax"];
	}

	// Check that the right amount has been paid...
	$rsubtotal = $checkprice + $quotecommentsamount;
	if ($billdate) $checkprice = $rsubtotal;
	$checkprice -= $virtualcash;
	$checkprice -= $totalpersonaldiscount;
	$rewardvirtualcash = $checkprice;
	if ($displaywithtax != 2) $checkprice += $shippingcost + $salestax + $payoptionfee;
	else $checkprice += $shippingcost + $payoptionfee;
	$checkprice -= $shippingdiscount;
	if ($subscriptionpayment && !empty($recurringfee)) {
		$checkprice = $recurringfee;
		$shippingcost = 0.00;
		$salestax = 0.00;
		$payoptionfee = 0.00;
	}
	if ($shippingcost) $tangiblegoods = 1;
	if(!$_POST["adminkey"] && number_format($parsed_price,2,'.','') != number_format($checkprice,2,'.','')) {
		$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname,1)." - incorrect amount paid","Incorrect amount paid. Paid $parsed_price but should have paid $checkprice. Customer email: $parsed_email, ".stripslashes($parsed_firstname).", ".stripslashes($parsed_lastname).", $parsed_products, $parsed_invoice, $ip_number","$headers");
		if ($visibleorderprocessing == "TRUE") {
			echo "<table class=\"ashopmessagetable\" align=\"center\">
			<tr><td align=\"center\"><br><br><p><span class=\"ashopmessageheader\">".TRANSNOTAPPROVED."</span></p>
			<p><span class=\"ashopmessage\">".REASON.": ".INCORRECT."</span></p></td></tr></table>";
			ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
		}
		exit;
	}

	// Display floating price manipulation message if needed...
	if ($fpmanipulated == "TRUE") {
		if ($visibleorderprocessing == "TRUE") {
			echo "<table class=\"ashopmessagetable\" align=\"center\">
			<tr><td align=\"center\"><br><br><p><span class=\"ashopmessageheader\">".TRANSNOTAPPROVED."</span></p>
			<p><span class=\"ashopmessage\">".REASON.": ".AUCTIONMANIPULATED."</span></p></td></tr></table>";
			ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
		}
		$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname,1)." - auction manipulated","An auction has been manipulated to place this order. Customer email: $parsed_email, ".stripslashes($parsed_firstname).", ".stripslashes($parsed_lastname).", $parsed_products, $parsed_invoice, $ip_number","$headers");
		exit;
	}

	// Display duplicate order message if needed...
	if (strstr($alreadysubscribed, "TRUE") && !strstr($alreadysubscribed, "FALSE")) $duplicate = "TRUE";

	if ($duplicate && !$subscriptionpayment) {
		if ($visibleorderprocessing == "TRUE") {
			echo "<table class=\"ashopmessagetable\" align=\"center\">
			<tr><td align=\"center\"><br><br><p><span class=\"ashopmessageheader\">".ALREADY2."</span></p>
			<p><span class=\"ashopmessage\"><a href=\"$orderpagelink\">".RETURNTO." ".end($shopname)."</a></span></p></td></tr></table>";
			ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
		}
		if (!empty($alreadysubscribed)) {
			$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail",un_html($ashopname,1)." - duplicate membership","This customer has ordered access to a membership area he/she already has access to: customer email: $parsed_email, ".stripslashes($parsed_firstname).", ".stripslashes($parsed_lastname).". A refund could be needed. Order details: $parsed_products, $parsed_invoice, $ip_number","$headers");
		}
		exit;
	}

	if ($downloadgoods || $subscriptiongoods || $phpbbgroups) {
		$password = makePassword();
		$unique = 0;
		while (!$unique) {
			$sql="SELECT password FROM orders WHERE password='$password'";
			$result = @mysqli_query($db,"$sql");
			if (@mysqli_num_rows($result) == 0) $unique = 1;
			else $password = makePassword();
		}
	}

	if ($subscriptiongoods && !$subscriptionpayment) {
		// Activate subscriptions...
		$subscriptionlinks = "";
		$duplicatesubscription = "";
		$items = explode("a", $parsed_products);
		$arraycount = 1;
		if ($items[0] && count($items)==1) $arraycount = 0;
		for ($i = 0; $i < count($items)-$arraycount; $i++) {
			$thisitem = explode("b", $items[$i]);
			$prethisproductid = $thisitem[count($thisitem)-1];
			$thisproductid = explode("d", $prethisproductid);
			$thisproductid = $thisproductid[0];
			$sql="SELECT subscriptiondir, name, length, protectedurl FROM product WHERE productid=$thisproductid AND prodtype='subscription'";
			$result = @mysqli_query($db,"$sql");
			$subscriptiondir = @mysqli_result($result,0,"subscriptiondir");
			$subscriptionurl = @mysqli_result($result,0,"protectedurl");
			$name = @mysqli_result($result,0,"name");
			$length = @mysqli_result($result,0,"length");
			// Check for Password Robot directory...
			if ($subscriptiondir && $probotpath && file_exists("$probotpath/data/groups.txt")) {
				$fp = fopen ("$probotpath/data/groups.txt","r");
				while (!feof ($fp)) {
					$fileline = rtrim(fgets($fp, 4096));
					$probotgrouparray = explode("\t",$fileline);
					if ($probotgrouparray[2] == $subscriptiondir) $prgroupname = $probotgrouparray[1];
				}
			}
			if ($subscriptiondir && (file_exists("$ashoppath/$subscriptiondir/.htpasswd") || ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt") && file_exists("$papluspath/$subscriptiondir/d_active.txt")) || ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt")))) {
				$subscriberfound = "";
				if ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt")) $fp = fopen ("$probotpath/data/groups/$prgroupname.txt","r");
				else if ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt")) $fp = fopen ("$papluspath/$subscriptiondir/d_pass.txt","r");
				else $fp = fopen ("$ashoppath/$subscriptiondir/.htpasswd","r");
				while (!feof ($fp)) {
					$fileline = fgets($fp, 4096);
					if (strstr($fileline,"$parsed_email")) $subscriberfound = "TRUE";
				}
				fclose($fp);
				if ($subscriberfound) {
					if ($subscriptionurl) $subscriptionlinks .= "".SUBSCRALREADY1."<a href=\"$subscriptionurl\">$name</a>".SUBSCRALREADY2."<br>";
					else $subscriptionlinks .= SUBSCRALREADY1."<a href=\"$ashopurl/$subscriptiondir\">$name</a>".SUBSCRALREADY2."<br>";
					$duplicatesubscription = "TRUE";
				} else {
					if ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt")) {
						$fp = fopen ("$probotpath/data/groups/$prgroupname.txt","a");
						fwrite($fp, "$parsed_email:".crypt("$password","As")."\n");
					} else if ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt")) {
						$fp = fopen ("$papluspath/$subscriptiondir/d_pass.txt","a");
						fwrite($fp, "$parsed_email:".crypt("$password","As")."\r\n");
					} else {
						$fp = fopen ("$ashoppath/$subscriptiondir/.htpasswd", "a");
						fwrite($fp, "$parsed_email:".crypt("$password","As")."\n");
					}
					fclose($fp);
					if ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt") && file_exists("$probotpath/data/accounts.txt")) {
						$start = time();
						$end = time()+(86400*$length);
						$startdate = date ("Y-m-d",$start);
						$enddate = date ("Y-m-d",$end);
						$fp = fopen ("$probotpath/data/accounts.txt", "a");
						fwrite($fp, "{$parsed_email}\t{$password}\t{$parsed_firstname} {$parsed_lastname}\t{$parsed_email}\t{$prgroupname}\tActive\t{$length}\t{$startdate}\t{$enddate}\n");
						fclose($fp);
						if ($subscriptionurl) {
							if ($receiptformat == "html" || $receiptformat == "pdf") $subscriptionlinks .= "<a href=\"$subscriptionurl\">$name</a><br>";
							else $subscriptionlinks .= "$subscriptionurl";
						} else $subscriptionlinks .= "$name<br>";
					} else if ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt") && file_exists("$papluspath/$subscriptiondir/d_active.txt")) {
						$enddate = time()+$timezoneoffset+(86400*$length);
						$fp = fopen ("$papluspath/$subscriptiondir/d_active.txt","a");
						fwrite($fp, "$parsed_email|".crypt("$password","As")."|$password||".date ("m-d-Y",time()+$timezoneoffset)."|$length|$enddate|$subscriptiondir|".stripslashes($parsed_firstname)." ".stripslashes($parsed_lastname)."|$parsed_email|||||||||||||||||||||||\r\n");
						fclose($fp);
						if ($subscriptionurl) {
							if ($receiptformat == "html" || $receiptformat == "pdf") $subscriptionlinks .= "<a href=\"$subscriptionurl\">$name</a><br>";
							else $subscriptionlinks .= "$subscriptionurl";
						} else $subscriptionlinks .= "$name<br>";
					} else {
						if ($subscriptionurl) {
							if ($receiptformat == "html" || $receiptformat == "pdf") $subscriptionlinks .= "<a href=\"$subscriptionurl\">$name</a><br>";
							else $subscriptionlinks .= "$subscriptionurl";
						} else {
							if ($receiptformat == "html" || $receiptformat == "pdf") $subscriptionlinks .= "<a href=\"$ashopurl/$subscriptiondir\">$name</a><br>";
							else $subscriptionlinks .= "$ashopurl/$subscriptiondir";
						}
					}
				}
			}
		}
	}

	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	$dateshort = date("Y-m-d", time()+$timezoneoffset);

	// Make sure the customer has a login...
	if (empty($customerpassword)) {
		$checkcustomerpasswordresult = @mysqli_query($db,"SELECT password FROM customer WHERE customerid='$customerid'");
		$checkcustomerpassword = @mysqli_result($checkcustomerpasswordresult,0,"password");
		if (empty($checkcustomerpassword)) {
			$newcustomerpassword = makePassword();
			// Encrypt password if encryption key is available...
			if (!empty($customerencryptionkey) && !empty($newcustomerpassword)) $updatecustomerpassword = ashop_encrypt($newcustomerpassword, $customerencryptionkey);
			else $updatecustomerpassword = $newcustomerpassword;
			@mysqli_query($db,"UPDATE customer SET username='$dbsafe_email', password='$updatecustomerpassword' WHERE customerid='$customerid'");
		} else $newcustomerpassword = "";
	} else $newcustomerpassword = "";

	// Get the customer's current wallet if needed...
	if (!empty($virtualcash) || !empty($rewardvirtualcash)) {
		$walletresult = @mysqli_query($db,"SELECT virtualcash FROM customer WHERE customerid='$customerid'");
		$customerwallet = @mysqli_result($walletresult,0,"virtualcash");
	}

	// Withdraw used virtual cash from the customer's wallet...
	if (!empty($virtualcash) && is_numeric($virtualcash) && $virtualcash > 0.00) {
		if ($customerwallet >= $virtualcash) $customerwallet -= $virtualcash;
		else {
			if ($visibleorderprocessing == "TRUE") {
				echo "<table class=\"ashopmessagetable\">
				<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".TRANSNOTAPPROVED."</span></p>
				<p><span class=\"ashopmessage\">".REASON.": ".INCORRECT."</span></p></td></tr></table>";
				ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
			}
			exit;
		}
	}

	// Reward the customer with virtual cash for this order...
	if (!empty($virtualcashpercent) && !empty($rewardvirtualcash) && is_numeric($rewardvirtualcash)) {
		if (!empty($virtualcash) && is_numeric($virtualcash)) $rewardvirtualcash -= $virtualcash;
		$rewardvirtualcash = $rewardvirtualcash * ($virtualcashpercent/100);
		$customerwallet += $rewardvirtualcash;
	}

	// Update the customer's wallet...
	if (!(empty($virtualcash) && $virtualcash > 0.00) || (!empty($rewardvirtualcash) && $rewardvirtualcash > 0.00)) @mysqli_query($db,"UPDATE customer SET virtualcash='$customerwallet' WHERE customerid='$customerid'");

	// Store or update customer info in a SAASU file if available...
	if ($saasuwsaccesskey && $saasufileid) $saasucontactid = ashop_saasu_postcontact($parsed_firstname, $parsed_lastname, $shippingbusiness, $vat, $parsed_email, $parsed_phone, $customerid, $parsed_address, $parsed_zip, $parsed_city, $parsed_state, $parsed_country);

	// Check if the sales tax needs to be converted to Canadian format...
	if ($shippingcountry == "CA" && $salestaxtype == "cancstpst") {
		if (($taxpercentage || $pstpercentage) && ($shippingstate == $taxstate || in_array($shippingstate, $hstprovinces))) {
			$gst = $taxpercentage * ($salestax/($taxpercentage + $pstpercentage));
			$pst = $pstpercentage * ($salestax/($taxpercentage + $pstpercentage));
		} else $gst = $salestax;
		$taxstring = "c|".number_format($gst,2,'.','')."|".number_format($pst,2,'.','');
	} else $taxstring = $salestax;

	// Store order...
	if ($authorized[0] != "PENDING" && $authorized[0] != "PROCESS") $paiddate = $date;
	else $paiddate = "";
	if ($quotesource) $source = $quotesource;
	else if ($ordersource == "Auction") $source = "Auction";
	else $source = "Shopping Cart";
	if ($payoptionid == "0" && $billdate) {
		$paiddate = "";
		$usebillreceipt = TRUE;
		$source = "eM: Invoice";
	} else $usebillreceipt = FALSE;
	$descriptionstring = @mysqli_escape_string($db, $descriptionstring);
	$seqinvoiceidresult = @mysqli_query($db,"SELECT MAX(invoiceid) AS invoiceid FROM orders");
	$invoiceid = @mysqli_result($seqinvoiceidresult,0,"invoiceid");
	$invoiceid++;
	$sql = "UPDATE orders SET source='$source', customerid='$customerid', remoteorderid='$parsed_remoteorderid', products='$parsed_products', description='$descriptionstring', date='$date', paid='$paiddate', price='$parsed_price', ip='$ip_number', password='$password', payoptionid=$payoptionid, shipping='$shippingcost', tax='$taxstring', discount='$totaldiscount', productprices='$productprices', paidproductprices='$paidproductprices', vendors='$vendorlist', affiliateid='$parsed_affiliate', invoiceid='$invoiceid', storediscount='$discountall' WHERE orderid='$parsed_invoice'";
	$result = @mysqli_query($db,"$sql");
	if ($billdate && !$usebillreceipt) {
		$rebillresult = @mysqli_query($db,"SELECT * FROM emerchant_bills WHERE orderid='$parsed_invoice'");
		$recurring = @mysqli_result($rebillresult,0,"recurring");
		if (!$recurring) {
			@mysqli_query($db,"DELETE FROM emerchant_bills WHERE orderid='$parsed_invoice'");
			@mysqli_query($db,"DELETE FROM emerchant_tempinvoices WHERE orderid='$parsed_invoice'");
		} else @mysqli_query($db,"UPDATE emerchant_bills SET remindersent='2', pastduesent='2'");
	}

	// Create separate order entries for Digital Mall member shops...
	$result = @mysqli_query($db,"SELECT userid FROM orders WHERE orderid='$parsed_invoice'");
	$members = explode("|", @mysqli_result($result, 0, "userid"));
	foreach ($members as $membernumber=>$memberid) {
		if ($memberid > 1) {
			$thismemberproducts = ashop_memberproductstring($db, $parsed_products, $memberid);
			$thismemberdescription = @mysqli_escape_string($db, $memberdescription[$memberid]);
			if($parsed_affiliate!="" && $affiliatecommission[$memberid]) $thismemberaffcommission = $affiliatecommission[$memberid];
			if ($ordersource == "Auction") $thismemberauction = 1;
			else $thismemberauction = 0;
			// Check if this order has already been paid...
			$checkmemberorder = @mysqli_query($db,"SELECT orderid FROM memberorders WHERE orderid='$parsed_invoice' AND userid='$memberid'");
			if (@mysqli_num_rows($checkmemberorder)) @mysqli_query($db,"UPDATE memberorders SET customerid='$customerid', products='$thismemberproducts', description='$thismemberdescription', price='{$memberprice[$memberid]}', discount='{$memberdiscount[$memberid]}', affiliatecommission='$thismemberaffcommission', auction='$thismemberauction' WHERE orderid='$parsed_invoice' AND userid='$memberid'");
			else @mysqli_query($db,"INSERT INTO memberorders (customerid, orderid, userid, products, description, date, paid, price, discount, affiliatecommission, auction) VALUES ('$customerid', '$parsed_invoice', '$memberid', '$thismemberproducts', '$thismemberdescription', '$date', '$paiddate', '{$memberprice[$memberid]}', '{$memberdiscount[$memberid]}', '$thismemberaffcommission', '$thismemberauction')");
		}
	}

	// Store the order in a SAASU file if available...
	if ($saasuwsaccesskey && $saasufileid && $saasuitemslist) ashop_saasu_postinvoice($saasucontactid, $saasushippingcontactid, $date, $saasuamount, $parsed_invoice);

	// Update shipping info if needed...
	if ($shippingid > 0) {
		$sql = "DELETE FROM shipping WHERE customerid=$customerid AND shippingid<>$shippingid";
		$result = @mysqli_query($db,"$sql");
		$sql = "UPDATE shipping SET customerid=$customerid WHERE shippingid=$shippingid";
		$result = @mysqli_query($db,"$sql");
	}

	// Handle unlock keys...
	$unlockkeystring = "";
	$items = explode("a", $parsed_products);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thisitem = explode("b", $items[$i]);
		$thisquantity = $thisitem[0];
		$thisproductidb = $thisitem[count($thisitem)-1];
		$thisproductidd = explode("d", $thisproductidb);
		$thisproductid = $thisproductidd[0];
		$sql="SELECT name FROM product WHERE productid='$thisproductid'";
		$result = @mysqli_query($db,"$sql");
		$thisproductname = @mysqli_result($result,0,"name");
		$sql="SELECT * FROM unlockkeys WHERE productid='$thisproductid' LIMIT 1";
		$result = @mysqli_query($db,"$sql");
		if (@mysqli_num_rows($result)) {
			if ($unlockkeymultiplier["$thisproductid"]) $thisquantity = $thisquantity*$unlockkeymultiplier["$thisproductid"];
			for ($q = 0; $q < $thisquantity; $q++) {
				$locktimeoutstart = time();
				// Make sure we get exclusive access to the unlock keys to avoid duplicates...
				while (ashop_getlock("unlockkeys") === false) {
					// Avoid endless loops...
					$locktimeout = time()-$locktimeoutstart;
					if ($locktimeout > 120) break;
				}
				$sql="SELECT * FROM unlockkeys WHERE productid='$thisproductid' AND orderid IS NULL ORDER BY keyid";
				$result = @mysqli_query($db,"$sql");
				$numberofkeys = @mysqli_num_rows($result)-1;
				if ($randomkeycodes == "1") {
					// Select a random unused unlock key for this product...
					list($usec, $sec) = explode(' ', microtime());
					$make_seed = (float) $sec + ((float) $usec * 100000);
					mt_srand($make_seed);
					$randval = mt_rand(0, $numberofkeys);
					$keytext = @mysqli_result($result,$randval,"keytext");
					$keyid = @mysqli_result($result,$randval,"keyid");
				} else {
					$keytext = @mysqli_result($result,0,"keytext");
					$keyid = @mysqli_result($result,0,"keyid");
				}
				if (!empty($keycodeencryptionkey) && !empty($keytext)) {
					$keytext = trim($keytext);
					$keytext = ashop_decrypt($keytext, $keycodeencryptionkey);
				}
				if (is_array($fulfilmentgroups)) foreach ($fulfilmentgroups as $fulfilmentoption=>$productinfo) {
					if (is_array($productinfo)) foreach ($productinfo as $productnumber=>$productinfo) {
						if ($fulfilmentgroups[$fulfilmentoption][$productnumber]["productid"] == $thisproductid) $fulfilmentgroups[$fulfilmentoption][$productnumber]["keys"][$q] = $keytext;
					}
				}
				$thisnoticesent = 0;
				$thisrefillsent = 0;
				eval ("\$thisnoticesent = \"\$noticesent$thisproductid\";");
				eval ("\$thisrefillsent = \"\$refillsent$thisproductid\";");
					if (!$keytext && !$thisnoticesent) {
						if ($unlockkeystring) $unlockkeystring .= "\n";
						$unlockkeystring .= UNLOCKKEY1." $thisproductname ".UNLOCKKEY2."\n";
						$adminunlockkeystring.="<p>A customer has purchased <b>$thisproductname</b> but there was no unused unlock key available in the database for the shop $ashopname! Send an unlock key by email to <a href=\"mailto:$parsed_email\">".stripslashes($parsed_firstname)." ".stripslashes($parsed_lastname)."</a>. You should also <a href=\"$ashopurl/admin\">click here</a> to login to the administration area for your shop. From there you will be able to refill the unlock keys by editing the product in your catalogue.</p>";
						eval ("\$noticesent$thisproductid = 1;");
					} else $unlockkeystring .= "$thisproductname: <br><b>$keytext</b><br>\n";

				// Assign this unlock key to the customer...
				$sql="UPDATE unlockkeys SET orderid='$parsed_invoice' WHERE keyid='$keyid'";
				$result = @mysqli_query($db,"$sql");
				
				// Alert shop administrator if the database is running low on available keys...
				if ($keytext && $numberofkeys < 5 && !$thisrefillsent) {
					$adminunlockkeystring.="<p>The number of unused unlock keys for <b>$thisproductname</b> in the shop $ashopname is low! <a href=\"$ashopurl\admin\">Click here</a> to login to the administration area for your shop. From there you will be able to refill the unlock keys by editing the product in your catalogue.</p>";
					eval ("\$refillsent$thisproductid = 1;");
				}
				ashop_releaselock("unlockkeys");
			}
		}
	}

	// Handle PAP integration...
	if (!empty($pappath) && !empty($papaffiliate) && file_exists("$pappath/accounts/settings.php")) {
		$fp = fopen ("$pappath/accounts/settings.php", "r");
		if ($fp) {
			while (!feof($fp)) {
				$buffer = fgets($fp,128);
				if (strpos($buffer, "serverName") == 0 && is_integer(strpos($buffer, "serverName"))) {
					$papdomain = str_replace("serverName=","",$buffer);
					$papdomain = trim($papdomain);
				}
				if (strpos($buffer, "baseServerUrl") == 0 && is_integer(strpos($buffer, "baseServerUrl"))) {
					$papuri = str_replace("baseServerUrl=","",$buffer);
					$papuri = trim($papuri);
				}
				$papurl = "http://".$papdomain.$papuri;
			}
			fclose ($fp);
		}
		$papurl = $papurl."scripts/sale.php";
		if (function_exists('curl_version')) {
			$curlversion = curl_version();
			if (strstr($curlversion, "SSL") || (is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS")))) {
				$papquery = "TotalCost=$parsed_price&OrderID=$parsed_invoice&AffiliateID=$papaffiliate&data1=$parsed_firstname $parsed_lastname&data2=$parsed_email";
				/*if (!empty($discountall)) {
					// Get the discount code that the customer used...
					$papdiscountresult = @mysqli_query($db,"SELECT code FROM storediscounts WHERE discountid='$discountall'");
					$papdiscountcode = @mysqli_result($papdiscountresult, 0, "code");
					$papquery .= "&Coupon=$papdiscountcode";
				}*/
				$ch = curl_init();
				if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
				curl_setopt($ch, CURLOPT_URL,$papurl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $papquery);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				$papresult = curl_exec($ch);
				$papcurlerror = curl_error($ch);
				curl_close ($ch);
			}
		}
	}

	// Handle autoresponder integration...
	if (!empty($autoresponders) && !empty($autoresponderid) && is_numeric($autoresponderid) && !empty($activateautoresponder) && $activateautoresponder == "1") {
		$autorespondersarray = explode("a", $autoresponders);
		foreach($autorespondersarray as $thisresponderid) {
			if (!$thisresponderid) break;
			$responderresult = @mysqli_query($db,"SELECT profileid FROM autoresponders WHERE responderid='$thisresponderid'");
			$autoresponderprofileid = @mysqli_result($responderresult, 0, "profileid");
			$querystring = "v=$autoresponderid&w=$autoresponderprofileid&subscription_type=E&id=$thisresponderid&first_name=$parsed_firstname&last_name=$parsed_lastname&email=$parsed_email&phone1=$parsed_phone&street=$parsed_address&city=$parsed_city&state=$parsed_state&zip=$parsed_zip&country=$parsed_country&posted=true";
			$postheader = "POST /formcapture.php HTTP/1.0\r\nHost: autoresponder-service.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
			$fp = @fsockopen ("autoresponder-service.com", 80, $errno, $errstr, 10);
			$res = "";
			if ($fp) {
				@fputs ($fp, $postheader.$querystring);
				//while (!feof($fp)) $res .= fgets ($fp, 1024);
				@fclose ($fp);
			}
		}
	}
	if (!empty($autorespondersoff) && !empty($autoresponderid) && is_numeric($autoresponderid) && !empty($activateautoresponder) && $activateautoresponder == "1") {
		$autoresponderoffarray = explode("a", $autorespondersoff);
		foreach($autoresponderoffarray as $thisresponderoffid) {
			if (!$thisresponderoffid) break;
			$responderresult = @mysqli_query($db,"SELECT profileid FROM autoresponders WHERE responderid='$thisresponderoffid'");
			$autoresponderoffprofileid = @mysqli_result($responderresult, 0, "profileid");
			$querystring = "v=$autoresponderid&w=$autoresponderoffprofileid&subscription_type=E&id=$thisresponderoffid&email=$parsed_email&posted=true&cp_action=UNS";
			$postheader = "POST /formcapture.php HTTP/1.0\r\nHost: autoresponder-service.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
			$fp = @fsockopen ("autoresponder-service.com", 80, $errno, $errstr, 10);
			$res = "";
			if ($fp) {
				@fputs ($fp, $postheader.$querystring);
				//while (!feof($fp)) $res .= fgets ($fp, 1024);
				@fclose ($fp);
			}
		}
	}

	// Handle AWeber integration...
	if (!empty($autoresponders) && !empty($aweberauthcode) && $allowemail) {
		require_once('includes/aweber/aweber_api.php');
		$auth = explode("|",$aweberauthcode);
		list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = $auth;
		try {
			$aweber = new AWeberAPI($consumerKey, $consumerSecret);
			$aweber->adapter->debug = false;
			$account = $aweber->getAccount($accessKey, $accessSecret);
			$autorespondersarray = explode("a", $autoresponders);
			foreach($autorespondersarray as $thisresponderid) {
				if (!$thisresponderid) break;
				$list = $account->lists->getById($thisresponderid);
				$params = array(
					'email' => "$parsed_email",
					'ip_address' => "$ip_number",
					'ad_tracking' => "Signup from AShop",
					'misc_notes' => "Order ID: $parsed_invoice",
					'name' => "$parsed_firstname $parsed_lastname"
				);
				$subscribers = $list->subscribers;
				$new_subscriber = $subscribers->create($params);
			}
		} catch(AWeberAPIException $exc) {
			$error = $exc->message;
		}
	}

	// Close database to be able to connect to ListMessenger...
	@mysqli_close($db);

	// Handle ListMessenger integration...
	function parselmconfigstring($lmconfigstring) {
		$returnstring = "";
		$returnstring = substr($lmconfigstring, strpos($lmconfigstring, "\"")+1);
		$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
		$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
		$returnstring = substr($returnstring, 0, strpos($returnstring, "\""));
		return $returnstring;
	}
	if ($listmessengerpath && !file_exists("$listmessengerpath/config.inc.php") && file_exists("$listmessengerpath/includes/config.inc.php")) {
		$listmessengerversion = "pro";
		$listmessengerpath .= "/includes";
	}
	if ($listmessengergroups && $listmessengerpath && file_exists("$listmessengerpath/config.inc.php") && $allowemail) {
		$fp = fopen ("$listmessengerpath/config.inc.php", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "DATABASE_HOST")) {
				$lmhost = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "DATABASE_NAME")) {
				$lmname = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "DATABASE_USER")) {
				$lmuser = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "DATABASE_PASS")) {
				$lmpass = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "TABLES_PREFIX")) {
				$lmprefix = parselmconfigstring($buffer);
			}
		}
		fclose ($fp);
		$lmdb = @mysqli_connect("$lmhost", "$lmuser", "$lmpass", "$lmname");
		$lmgroupsarray = explode("a", $listmessengergroups);
		foreach($lmgroupsarray as $groupid) {
			if ($listmessengerversion != "pro") {
				$sql = "SELECT * FROM {$lmprefix}user_list WHERE user_address='$parsed_email' AND group_id=$groupid";
				$result = @mysqli_query($lmdb,$sql);
				if (!@mysqli_num_rows($result)) {
					$sql = "INSERT INTO {$lmprefix}user_list (group_id, user_address, user_name) VALUES ($groupid, '$parsed_email', '$parsed_firstname $parsed_lastname')";
					$result = @mysqli_query($lmdb,$sql);
				}
			} else {
				$signupdate = time()+$timezoneoffset;
				$sql = "SELECT * FROM {$lmprefix}users WHERE email_address='$parsed_email' AND group_id=$groupid";
				$result = @mysqli_query($lmdb,$sql);
				if (!@mysqli_num_rows($result)) {
					$sql = "INSERT INTO {$lmprefix}users (group_id, signup_date, email_address, firstname, lastname) VALUES ($groupid, '$signupdate', '$parsed_email', '$parsed_firstname', '$parsed_lastname')";
					$result = @mysqli_query($lmdb,$sql);
				}
			}
		}
		@mysqli_close($lmdb);
	}

	// Handle ListMail Pro integration...
	if ($listmaillists && $listmailurl && $allowemail) {
		$lmpgroupsarray = explode("a", $listmaillists);
		foreach($lmpgroupsarray as $lmpgroupid) {
			if (!$lmpgroupid) break;
			$querystring = "list=$lmpgroupid&fname=$parsed_firstname&lname=$parsed_lastname&email=$parsed_email&user1=$parsed_phone&user2=$parsed_address&user3=$parsed_city&user4=$parsed_state&user5=$parsed_zip&user6=$parsed_country";
			if (strpos($listmailurl, "/", 8)) {
				$urlpath = "/".substr($listmailurl, strpos($listmailurl, "/", 8)+1);
				$urldomain = substr($listmailurl, 0, strpos($listmailurl, "/", 8));
			} else {
				$urlpath = "/";
				$urldomain = $listmailurl;
			}
			if ($urlpath == "/") $scriptpath = "signup.php";
			else $scriptpath = "/signup.php";
			$urldomain = str_replace("http://", "", $urldomain);
			$postheader = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
			$fp = fsockopen ($urldomain, 80, $errno, $errstr, 10);
			unset($res);
			if ($fp) {
				fputs ($fp, $postheader.$querystring);
				fclose ($fp);
			}			
		}
	}

	// Handle Interspire Email Marketer integration...
	if ($iemlists && $iemurl && $iemuser && $iemtoken && $allowemail) {
		$iemlistsarray = explode("a", $iemlists);
		foreach($iemlistsarray as $iemlistid) {
			if (!$iemlistid) break;
			$iemxml = "<xmlrequest><username>$iemuser</username><usertoken>$iemtoken</usertoken><requesttype>subscribers</requesttype><requestmethod>AddSubscriberToList</requestmethod><details><emailaddress>$parsed_email</emailaddress><mailinglist>$iemlistid</mailinglist><format>html</format><confirmed>yes</confirmed><customfields><item><fieldid>2</fieldid><value>$parsed_firstname</value></item><item><fieldid>3</fieldid><value>$parsed_lastname</value></item></customfields></details></xmlrequest>";
			$iemch = @curl_init($iemurl);
			curl_setopt($iemch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($iemch, CURLOPT_POST, 1);
			curl_setopt($iemch, CURLOPT_POSTFIELDS, $iemxml);
			$iemresult = @curl_exec($iemch);
		}
	}

	// Handle MailChimp integration...
	if ($mailchimplists && $mailchimpapikey && $allowemail) {
		require_once "includes/MCAPI.class.php";
		$api = new MCAPI($mailchimpapikey);
		$mailchimplistsarray = explode("a", $mailchimplists);
		foreach($mailchimplistsarray as $mailchimplistid) {
			if (!empty($mailchimplistid)) {
				$merge_vars = array('FNAME'=>$parsed_firstname, 'LNAME'=>$parsed_lastname);
				$retval = $api->listSubscribe( $mailchimplistid, $parsed_email, $merge_vars, 'html', 'false' );
			}
		}
	}

	// Handle punbb integration...
	if ($phpbbgroups && $phpbbpath && file_exists("$phpbbpath/config.php")) {
		$fp = fopen ("$phpbbpath/config.php", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "\$db_host") == 0 && is_integer(strpos($buffer, "\$db_host"))) {
				$phpbbhost = substr($buffer, strpos($buffer, "'")+1);
				$phpbbhost = substr($phpbbhost, 0, strpos($phpbbhost, "'"));
			}
			if (strpos($buffer, "\$db_name") == 0 && is_integer(strpos($buffer, "\$db_name"))) {
				$phpbbname = substr($buffer, strpos($buffer, "'")+1);
				$phpbbname = substr($phpbbname, 0, strpos($phpbbname, "'"));
			}
			if (strpos($buffer, "\$db_username") == 0 && is_integer(strpos($buffer, "\$db_username"))) {
				$phpbbuser = substr($buffer, strpos($buffer, "'")+1);
				$phpbbuser = substr($phpbbuser, 0, strpos($phpbbuser, "'"));
			}
			if (strpos($buffer, "\$db_password") == 0 && is_integer(strpos($buffer, "\$db_password"))) {
				$phpbbpass = substr($buffer, strpos($buffer, "'")+1);
				$phpbbpass = substr($phpbbpass, 0, strpos($phpbbpass, "'"));
			}
			if (strpos($buffer, "\$db_prefix") == 0 && is_integer(strpos($buffer, "\$db_prefix"))) {
				$phpbbtablepref = substr($buffer, strpos($buffer, "'")+1);
				$phpbbtablepref = substr($phpbbtablepref, 0, strpos($phpbbtablepref, "'"));
			}
			if (strpos($buffer, "\$db_type") == 0 && is_integer(strpos($buffer, "\$db_type"))) {
				$phpbbdbms = substr($buffer, strpos($buffer, "'")+1);
				$phpbbdbms = substr($phpbbdbms, 0, strpos($phpbbdbms, "'"));
			}
		}
		fclose ($fp);
		if (stristr($phpbbdbms, "mysql")) {

			if ( ! function_exists('random_key') ) {
				function random_key() {
					$key = '';
					$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
					for ($i = 0; $i < 12; ++$i) $key .= substr($chars, (mt_rand() % strlen($chars)), 1);				
					return $key;
				}
			}

			$phpbbsalt = random_key();
			$phpbbpassword = sha1($phpbbsalt.sha1($password));

			$phpbbdb = @mysqli_connect("$phpbbhost", "$phpbbuser", "$phpbbpass", "$phpbbname");
			$phpbbgroupsarray = explode("a", $phpbbgroups);
			foreach($phpbbgroupsarray as $phpbbgroup) {
				$sql = "SELECT * FROM $phpbbtablepref"."users WHERE email = '$parsed_email'";
				$result = @mysqli_query($phpbbdb,$sql);
				if (!@mysqli_num_rows($result) && $phpbbgroup) {
					if ($parsed_lastname) {
						$phpbbusername = trim(strtolower("$parsed_firstname$parsed_lastname"));
						$phpbbusername = str_replace(" ","",$phpbbusername);
					} else {
						$phpbbusername = substr($parsed_email, 0, strpos($parsed_email, "@"));
						$phpbbusername = str_replace(".","",$phpbbusername);
						$phpbbusername = str_replace("-","",$phpbbusername);
						$phpbbusername = trim(strtolower("$phpbbusername"));
					}
					$addnumber = 0;
					$unique = 0;
					while (!$unique) {
						$sql = "SELECT * FROM $phpbbtablepref"."users WHERE username = '$phpbbusername'";
						$result = @mysqli_query($phpbbdb,$sql);
						if (!@mysqli_num_rows($result)) $unique = 1;
						else {
							$phpbbusername = substr_replace($phpbbusername, $addnumber, strlen($phpbbusername)-1);
							$addnumber++;
						}
					}
					$sql = "INSERT INTO $phpbbtablepref"."users	(group_id, username, registered, password, salt, email) VALUES ('$phpbbgroup', '$phpbbusername', " . time() . ", '$phpbbpassword', '$phpbbsalt', '$parsed_email')";
					$result = @mysqli_query($phpbbdb,$sql);

					// Send mail to customer to inform about membership in phpbb forum...
					$message="<html><head><title>".PHPBB1." $ashopname ".PHPBB2."</title>\n".CHARSET."</head><body><font face=\"$font\"><p>".PHPBB3." $ashopname! ".PHPBB4;
					if ($phpbburl) $message .= ": <a href=\"$phpbburl\">".str_replace("http://","",$phpbburl)."</a>";
					$message .=".</p><p>".PHPBB5.":</p><p>----------------------------<br>".PHPBB6.": $phpbbusername<br>".PHPBB7.": $password<br>----------------------------</p><p>".PHPBB8."</p></font></body></html>";
					$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

					@ashop_mail("$parsed_email",PHPBB1." ".un_html($ashopname,1)." ".PHPBB2,"$message","$headers");
				} else if ($phpbbgroup) {
					$phpbbuserid = @mysqli_result($result,0,"id");
					$sql = "UPDATE $phpbbtablepref"."users	SET group_id='$phpbbgroup' WHERE id='$phpbbuserid'";
					$result = @mysqli_query($phpbbdb,$sql);
				}
			}
		}
		@mysqli_close($phpbbdb);
	}

	// Handle ARP3 integration...
	if ($arpresponders && $arpluspath && file_exists("$arpluspath/arp3-config.pl") && $allowemail) {
		$fp = fopen ("$arpluspath/arp3-config.pl", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "\$db_host") == 0 && is_integer(strpos($buffer, "\$db_host"))) {
				$arphost = substr($buffer, strpos($buffer, "\"")+1);
				$arphost = substr($arphost, 0, strpos($arphost, "\""));
			}
			if (strpos($buffer, "\$db_name") == 0 && is_integer(strpos($buffer, "\$db_name"))) {
				$arpname = substr($buffer, strpos($buffer, "\"")+1);
				$arpname = substr($arpname, 0, strpos($arpname, "\""));
			}
			if (strpos($buffer, "\$db_login") == 0 && is_integer(strpos($buffer, "\$db_login"))) {
				$arpuser = substr($buffer, strpos($buffer, "\"")+1);
				$arpuser = substr($arpuser, 0, strpos($arpuser, "\""));
			}
			if (strpos($buffer, "\$db_password") == 0 && is_integer(strpos($buffer, "\$db_password"))) {
				$arppass = substr($buffer, strpos($buffer, "\"")+1);
				$arppass = substr($arppass, 0, strpos($arppass, "\""));
			}
			if (strpos($buffer, "\$db_table_SETTINGS") == 0 && is_integer(strpos($buffer, "\$db_table_SETTINGS"))) {
				$arpsettingstable = substr($buffer, strpos($buffer, "'")+1);
				$arpsettingstable = substr($arpsettingstable, 0, strpos($arpsettingstable, "'"));
			}
		}
		fclose ($fp);
		$arpdb = @mysqli_connect("$arphost", "$arpuser", "$arppass", "$arpname");
		$arpsql = "SELECT cgi_arplus_url FROM $arpsettingstable";
		$arpresult = @mysqli_query($arpdb,$arpsql);
		$arpurl = @mysqli_result($arpresult, 0, "cgi_arplus_url");
		@mysqli_close($arpdb);
		if ($arpurl) {
			$arprespondersarray = explode("a", $arpresponders);
			foreach($arprespondersarray as $arpresponderid) {
				if ($arpresponderid) {
					$querystring = "first_name=$parsed_firstname&last_name=$parsed_lastname&street=$parsed_address&city=$parsed_city&state=$parsed_state&zip=$parsed_zip&country=$parsed_country&phone1=$parsed_phone&email=$parsed_email&id=$arpresponderid&subscription_type=E&arp_action=SUB";
					if (strpos($arpurl, "/", 8)) {
						$urlpath = "/".substr($arpurl, strpos($arpurl, "/", 8)+1);
						$urldomain = substr($arpurl, 0, strpos($arpurl, "/", 8));
					} else {
						$urlpath = "/";
						$urldomain = $arpurl;
					}
					$scriptpath = "$urlpath/arp3-formcapture.pl";
					$urldomain = str_replace("http://", "", $urldomain);
					$header = "POST $scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla 4.0\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
					$fp = fsockopen ("$urldomain", 80);
					$response = fwrite ($fp, $header . $querystring);
					fclose ($fp);
				}
			}
		}
	}

	// Handle ARP Reach integration...
	if ($arpreachresponders && $arpreachpath && file_exists("$arpreachpath/config.php") && $allowemail) {
		$fp = fopen ("$arpreachpath/config.php", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "\$config['database_host']") == 0 && is_integer(strpos($buffer, "\$config['database_host']"))) {
				$arpreachhost = substr($buffer, strpos($buffer, " = '")+4);
				$arpreachhost = substr($arpreachhost, 0, strpos($arpreachhost, "'"));
			}
			if (strpos($buffer, "\$config['database_name']") == 0 && is_integer(strpos($buffer, "\$config['database_name']"))) {
				$arpreachname = substr($buffer, strpos($buffer, " = '")+4);
				$arpreachname = substr($arpreachname, 0, strpos($arpreachname, "'"));
			}
			if (strpos($buffer, "\$config['database_username']") == 0 && is_integer(strpos($buffer, "\$config['database_username']"))) {
				$arpreachuser = substr($buffer, strpos($buffer, " = '")+4);
				$arpreachuser = substr($arpreachuser, 0, strpos($arpreachuser, "'"));
			}
			if (strpos($buffer, "\$config['database_password']") == 0 && is_integer(strpos($buffer, "\$config['database_password']"))) {
				$arpreachpass = substr($buffer, strpos($buffer, " = '")+4);
				$arpreachpass = substr($arpreachpass, 0, strpos($arpreachpass, "'"));
			}
			if (strpos($buffer, "\$config['database_table_prefix']") == 0 && is_integer(strpos($buffer, "\$config['database_table_prefix']"))) {
				$arpreachtable = substr($buffer, strpos($buffer, " = '")+4);
				$arpreachtable = substr($arpreachtable, 0, strpos($arpreachtable, "'"));
			}
			if (strpos($buffer, "\$config['application_url']") == 0 && is_integer(strpos($buffer, "\$config['application_url']"))) {
				$arpreachurl = substr($buffer, strpos($buffer, " = '")+4);
				$arpreachurl = substr($arpreachurl, 0, strpos($arpreachurl, "'"));
			}
		}
		fclose ($fp);
		$arpreachdb = @mysqli_connect("$arpreachhost", "$arpreachuser", "$arpreachpass", "$arpreachname");
		$arpreachsql = "SELECT action_access_key FROM {$arpreachtable}systemsettings";
		$arpreachresult = @mysqli_query($arpreachdb,$arpreachsql);
		$arpreachaccesskey = @mysqli_result($arpreachresult, 0, "action_access_key");
		// Insert the contact...
		$timestamp = time();
		$contactkey = makepasswd();
		@mysqli_query($arpreachdb,"INSERT INTO {$arpreachtable}contacts (stamp_create, `key`, email_address, first_name, last_name) VALUES ('$timestamp', '$contactkey', '$parsed_email', '$parsed_firstname', '$parsed_lastname')");
		@mysqli_close($arpreachdb);
		if ($arpreachurl) {
			$arpreachrespondersarray = explode("a", $arpreachresponders);
			foreach($arpreachrespondersarray as $arpreachresponderid) {
				if ($arpreachresponderid) {
					$querystring = "access_key=$arpreachaccesskey&email=$parsed_email&actions=$arpreachresponderid";
					if (strpos($arpreachurl, "/", 8)) {
						$urlpath = "/".substr($arpreachurl, strpos($arpreachurl, "/", 8)+1);
						$urldomain = substr($arpreachurl, 0, strpos($arpreachurl, "/", 8));
					} else {
						$urlpath = "/";
						$urldomain = $arpreachurl;
					}
					$scriptpath = "$urlpath/a.php/actions/remote";
					$urldomain = str_replace("http://", "", $urldomain);
					$header = "POST $scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nUser-Agent: Mozilla 4.0\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
					$fp = fsockopen ("$urldomain", 80);
					$response = fwrite ($fp, $header . $querystring);
					fclose ($fp);
				}
			}
		}
	}

	// Handle Infinty Responder integration...
	if (($infresponders || $infrespondersoff) && $infinitypath && file_exists("$infinitypath/config.php") && $allowemail) {
		$fp = fopen ("$infinitypath/config.php", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "\$MySQL_server") == 0 && is_integer(strpos($buffer, "\$MySQL_server"))) {
				$infinityhost = substr($buffer, strpos($buffer, "'")+1);
				$infinityhost = substr($infinityhost, 0, strpos($infinityhost, "\""));
			}
			if (strpos($buffer, "\$MySQL_database") == 0 && is_integer(strpos($buffer, "\$MySQL_database"))) {
				$infinityname = substr($buffer, strpos($buffer, "'")+1);
				$infinityname = substr($infinityname, 0, strpos($infinityname, "'"));
			}
			if (strpos($buffer, "\$MySQL_user") == 0 && is_integer(strpos($buffer, "\$MySQL_user"))) {
				$infinityuser = substr($buffer, strpos($buffer, "'")+1);
				$infinityuser = substr($infinityuser, 0, strpos($infinityuser, "'"));
			}
			if (strpos($buffer, "\$MySQL_password") == 0 && is_integer(strpos($buffer, "\$MySQL_password"))) {
				$infinitypass = substr($buffer, strpos($buffer, "'")+1);
				$infinitypass = substr($infinitypass, 0, strpos($infinitypass, "'"));
			}
		}
		fclose ($fp);
		$infinitydb = @mysqli_connect("$infinityhost", "$infinityuser", "$infinitypass", "$infinityname");

		if ($infrespondersoff) $infrespondersoffarray = explode("a", $infrespondersoff);
		if (is_array($infrespondersoffarray)) foreach($infrespondersoffarray as $infresponderoffid) {
			if ($infresponderoffid) {
				$infresult = @mysqli_query($infinitydb,"DELETE FROM InfResp_subscribers WHERE ResponderID = '$infresponderoffid' AND EmailAddress='$parsed_email'");
			}
		}

		if ($infresponders) $infrespondersarray = explode("a", $infresponders);
		if (is_array($infrespondersarray)) foreach($infrespondersarray as $infresponderid) {
			if ($infresponderid) {
				$infresult = @mysqli_query($infinitydb,"SELECT EmailAddress FROM InfResp_subscribers WHERE ResponderID = '$infresponderid' AND EmailAddress='$parsed_email'");
				if (!@mysqli_num_rows($infresult)) {
					$infnotunique = TRUE;
					$infstarttime = time();
					while ($infnotunique) {
						$infuniqueid = substr(md5(ashop_randomstring(15, 15, TRUE, FALSE, TRUE)),0,15);
						$infresult2 = @mysqli_query($infinitydb,"SELECT UniqueCode FROM InfResp_subscribers WHERE UniqueCode = '$infuniqueid'");
						if (!@mysqli_num_rows($infresult2)) $infnotunique = FALSE;
					}
					$inftimestamp = time();
					@mysqli_query($infinitydb,"INSERT INTO InfResp_subscribers (ResponderID, SentMsgs, EmailAddress, TimeJoined, Real_TimeJoined, CanReceiveHTML, LastActivity, FirstName, LastName, IP_Addy, ReferralSource, UniqueCode, Confirmed) VALUES('$infresponderid','', '$parsed_email', '$inftimestamp', '$inftimestamp', '1', '$inftimestamp', '$parsed_firstname', '$parsed_lastname', '$ip_number', 'AShop Add', '$infuniqueid', '1')");
				}
			}
		}
	}

	// Reopen database...
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");


	// Reward affiliate...
	if($parsed_affiliate!="") {
		$sql="SELECT * FROM affiliate WHERE affiliateid='$parsed_affiliate'";
		$result = @mysqli_query($db,"$sql");
		if (!@mysqli_num_rows($result)) $parsed_affiliate = "";
		else {
			$affiliatemail = ashop_mailsafe(@mysqli_result($result, 0, "email"));
			$affiliatefirstname = ashop_mailsafe(@mysqli_result($result, 0, "firstname"));
			$affiliatelastname = ashop_mailsafe(@mysqli_result($result, 0, "lastname"));
			$affiliatereferredby = @mysqli_result($result, 0, "referedby");
			$affiliatelevel = @mysqli_result($result, 0, "commissionlevel");
			$affiliatepassword = @mysqli_result($result, 0, "password");
			$affiliateapiurl = @mysqli_result($result, 0, "apiurl");
			if ($affiliatereferredby) {
				$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliatereferredby'";
				$result = @mysqli_query($db,"$sql");
				$referreremail = ashop_mailsafe(@mysqli_result($result, 0, "email"));
				$tierreferredby = @mysqli_result($result, 0, "referedby");
				$referrerlevel = @mysqli_result($result, 0, "commissionlevel");
				$referrerpassword = @mysqli_result($result, 0, "password");
				$referrerapiurl = @mysqli_result($result, 0, "apiurl");
			}
		}

		// Check that affiliate is not the customer...
		if($affiliatemail && $affiliatemail != $parsed_email && $authorized[0] != "PENDING" && $authorized[0] != "PROCESS" && ($pricelevel < 1 || $wholesaleaffiliate == "1")) {
			$affiliateupdated = FALSE;
			if ($affiliatelevel == 2 && $provision2 > 0) {
				@mysqli_query($db,"INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$parsed_affiliate', '$parsed_invoice', 0, 0, '$provision2')");
				$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$parsed_affiliate'";
				$result = @mysqli_query($db,"$sql");
				$affiliateupdated = TRUE;
			} else if ($provision > 0) {
				@mysqli_query($db,"INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$parsed_affiliate', '$parsed_invoice', 0, 0, '$provision')");
				$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$parsed_affiliate'";
				$result = @mysqli_query($db,"$sql");
				$affiliateupdated = TRUE;
			}			

			// Check if the affiliate should be upgraded...
			if ($affiliatelevel == "1" && !empty($upgradeaffiliate) && $upgradeaffiliate > 0 && $affiliateupdated) {
				$sql="SELECT orderid FROM orderaffiliate WHERE affiliateid='$parsed_affiliate'";
				$result = @mysqli_query($db,"$sql");
				$affiliateorders = @mysqli_num_rows($result);
				if ($affiliateorders >= $upgradeaffiliate) $result = @mysqli_query($db,"UPDATE affiliate SET commissionlevel='2' WHERE affiliateid='$parsed_affiliate'");
			}

			if ($affiliateupdated) {

				// Notify affiliate by email...
				$message="<html><head><title>Your link to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>Your link to $ashopname generated a sale on $date</p><p>Thank you for your help!</p><p>You can log in to check how much you have earned at: <a href=\"$ashopurl/affiliate/login.php\">$ashopurl/affiliate/login.php</a></p></font></body></html>";
				$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				
				@ashop_mail("$affiliatemail",un_html($ashopname,1)." affiliate notification","$message","$headers");

				// Send API notification to affiliate...
				$notifystatus = 1;
				if ($affiliateapiurl && $affiliatelevel == 2 && $provision2 > 0) $notifystatus = ashop_notifyaffiliate($affiliatepassword,$affiliateapiurl,$customerid,$parsed_invoice,$provision2,$affiliateapidescriptionstring);
				else if ($affiliateapiurl && $affiliatelevel != 2 && $provision > 0) $notifystatus = ashop_notifyaffiliate($affiliatepassword,$affiliateapiurl,$customerid,$parsed_invoice,$provision,$affiliateapidescriptionstring);
				if (!$notifystatus) $affiliateerror = "<p><b>Note!</b> The affiliate's API URL could not be reached! Notify the affiliate to manually update his/her records with this order: $parsed_invoice.</p>";
				else $affiliateerror = "";

				// Notify affiliate administrator by email...
				$message="<html><head><title>An affiliate link to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>The affiliate $parsed_affiliate $affiliatefirstname $affiliatelastname generated a sale on $date</p>$affiliateerror</font></body></html>";
				$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				
				@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate sales notification","$message","$headers");
			}

			// Handle secondtier affiliates...
			if ($affiliatereferredby && $secondtieractivated) {
				$affiliateupdated = FALSE;
				if ($referrerlevel == 2 && $secondtierprovision2 > 0) {
					$sql="INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$affiliatereferredby', '$parsed_invoice', 0, 1, '$secondtierprovision2')";
					$result = @mysqli_query($db,"$sql");
					$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$affiliatereferredby'";
					$result = @mysqli_query($db,"$sql");
					$affiliateupdated = TRUE;
				} else if ($secondtierprovision > 0) {
					$sql="INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$affiliatereferredby', '$parsed_invoice', 0, 1, '$secondtierprovision')";
					$result = @mysqli_query($db,"$sql");
					$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$affiliatereferredby'";
					$result = @mysqli_query($db,"$sql");
					$affiliateupdated = TRUE;
				}

				if ($affiliateupdated) {

					// Notify affiliate by email...
					$message="<html><head><title>A link from an affiliate you have referred to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>A link from an affiliate you have referred to $ashopname generated a sale on $date</p><p>Thank you for your help!</p><p>You can log in to check how much you have earned at: <a href=\"$ashopurl/affiliate/login.php\">$ashopurl/affiliate/login.php</a></p></font></body></html>";
					$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

					@ashop_mail("$referreremail",un_html($ashopname,1)." affiliate notification","$message","$headers");

					// Send API notification to affiliate...
					$notifystatus = 1;
					if ($referrerapiurl && $referrerlevel == 2 && $secondtierprovision2 > 0) $notifystatus = ashop_notifyaffiliate($referrerpassword,$affiliateapiurl,$customerid,$parsed_invoice,$secondtierprovision2,$affiliateapidescriptionstring);
					else if ($referrerapiurl && $referrerlevel != 2 && $secondtierprovision > 0) $notifystatus = ashop_notifyaffiliate($referrerpassword,$affiliateapiurl,$customerid,$parsed_invoice,$secondtierprovision,$affiliateapidescriptionstring);
					if (!$notifystatus) {
						// Notify affiliate administrator of the problem...
						if (empty($invoiceid)) $invoiceid = $parsed_invoice;
						$message="<html><head><title>Affiliate API notification error!</title></head><body><font face=\"$font\"><p>The affiliate API URL for $parsed_affiliate $affiliatefirstname $affiliatelastname could not be reached to notify him/her of this order: $invoiceid</p></font></body></html>";
						$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

						@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate API notification error","$message","$headers");
					}
				}

				$tier = 3;
				while (!empty($tierreferredby) && !empty($tierprovision[$tier]) && $tierprovision[$tier] > 0) {
					$sql="SELECT * FROM affiliate WHERE affiliateid='$tierreferredby'";
					$result = @mysqli_query($db,"$sql");
					if (@mysqli_num_rows($result)) {
						$thistieraffid = $tierreferredby;
						$referreremail = ashop_mailsafe(@mysqli_result($result, 0, "email"));
						$tierreferredby = @mysqli_result($result, 0, "referedby");
						$referrerpassword = @mysqli_result($result, 0, "password");
						$referrerapiurl = @mysqli_result($result, 0, "apiurl");
						$secondtier = $tier-1;
						$sql="INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$thistieraffid', '$parsed_invoice', 0, '$secondtier', '{$tierprovision[$tier]}')";
						$result = @mysqli_query($db,"$sql");
						$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$tierreferredby'";
						$result = @mysqli_query($db,"$sql");

						// Notify affiliate by email...
						$message="<html><head><title>A link from an affiliate you have referred to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>A link from an affiliate you have referred to $ashopname generated a sale on $date</p><p>Thank you for your help!</p><p>You can log in to check how much you have earned at: <a href=\"$ashopurl/affiliate/login.php\">$ashopurl/affiliate/login.php</a></p></font></body></html>";
						$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
						
						@ashop_mail("$referreremail",un_html($ashopname,1)." affiliate notification","$message","$headers");

						// Send API notification to affiliate...
						$notifystatus = 1;
						if ($referrerapiurl && $tierprovision[$tier] > 0) $notifystatus = ashop_notifyaffiliate($referrerpassword,$referrerapiurl,$customerid,$parsed_invoice,$tierprovision[$tier],$affiliateapidescriptionstring);
						if (!$notifystatus) {
							if (empty($invoiceid)) $invoiceid = $parsed_invoice;
							// Notify affiliate administrator of the problem...
							$message="<html><head><title>Affiliate API notification error!</title></head><body><font face=\"$font\"><p>The affiliate API URL for $parsed_affiliate $affiliatefirstname $affiliatelastname could not be reached to notify him/her of this order: $invoiceid</p></font></body></html>";
							$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

							@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate API notification error","$message","$headers");
						}

						$tier++;
					} else $tierreferredby = "";
				}
			}

		} else if ($affiliatemail && $affiliatemail != $parsed_email && ($pricelevel < 1 || $wholesaleaffiliate == "1")) {
			if ($affiliatelevel == 2 && $provision2 > 0) {
				$sql="INSERT INTO pendingorderaff (affiliateid, orderid, secondtier, commission) VALUES ('$parsed_affiliate', '$parsed_invoice', 0, '$provision2')";
				$result = @mysqli_query($db,"$sql");
				$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$parsed_affiliate'";
				$result = @mysqli_query($db,"$sql");

				// Notify affiliate administrator by email...
				$message="<html><head><title>An affiliate link to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>The affiliate $parsed_affiliate $affiliatefirstname $affiliatelastname generated a sale on $date</p></font></body></html>";
				$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

				@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate sales notification","$message","$headers");
			} else if ($provision > 0) {
				$sql="INSERT INTO pendingorderaff (affiliateid, orderid, secondtier, commission) VALUES ('$parsed_affiliate', '$parsed_invoice', 0, '$provision')";
				$result = @mysqli_query($db,"$sql");
				$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$parsed_affiliate'";
				$result = @mysqli_query($db,"$sql");

				// Notify affiliate administrator by email...
				$message="<html><head><title>An affiliate link to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>The affiliate $parsed_affiliate $affiliatefirstname $affiliatelastname generated a sale on $date</p></font></body></html>";
				$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

				@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate sales notification","$message","$headers");
			}

			// Handle secondtier affiliates...
			if ($affiliatereferredby && $secondtieractivated) {
				if ($referrerlevel == 2 && $secondtierprovision2 > 0) {
					$sql="INSERT INTO pendingorderaff (affiliateid, orderid, secondtier, commission) VALUES ('$affiliatereferredby', '$parsed_invoice', 1, '$secondtierprovision2')";
					$result = @mysqli_query($db,"$sql");
					$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$affiliatereferredby'";
					$result = @mysqli_query($db,"$sql");
				} else if ($secondtierprovision > 0) {
					$sql="INSERT INTO pendingorderaff (affiliateid, orderid, secondtier, commission) VALUES ('$affiliatereferredby', '$parsed_invoice', 1, '$secondtierprovision')";
					$result = @mysqli_query($db,"$sql");
					$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$affiliatereferredby'";
					$result = @mysqli_query($db,"$sql");
				}

				$tier = 3;
				while (!empty($tierreferredby) && !empty($tierprovision[$tier]) && $tierprovision[$tier] > 0) {
					$sql="SELECT * FROM affiliate WHERE affiliateid='$tierreferredby'";
					$result = @mysqli_query($db,"$sql");
					if (@mysqli_num_rows($result)) {
						$thistieraffid = $tierreferredby;
						$referreremail = ashop_mailsafe(@mysqli_result($result, 0, "email"));
						$tierreferredby = @mysqli_result($result, 0, "referedby");
						$secondtier = $tier-1;
						$sql="INSERT INTO pendingorderaff (affiliateid, orderid, secondtier, commission) VALUES ('$thistieraffid', '$parsed_invoice', '$secondtier', '{$tierprovision[$tier]}')";
						$result = @mysqli_query($db,"$sql");
						$sql = "UPDATE affiliate SET lastdate='$date' WHERE affiliateid='$tierreferredby'";
						$result = @mysqli_query($db,"$sql");
						$tier++;
					} else $tierreferredby = "";
				}
			}
		} else @mysqli_query($db,"UPDATE memberorders SET affiliatecommission='0' WHERE orderid='$parsed_invoice'");
	}

	// Activate paid shopping mall vendor account...
	if (!empty($activatevendor) && is_numeric($activatevendor) && $autoapprovemembers) {
		// Queue shop for installation if cPanel integration is activated...
		if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) {
			if (!file_exists("$ashopspath/updates/makeshop")) {
				mkdir("$ashopspath/updates/makeshop");
				@chmod("$ashopspath/updates/makeshop", 0755);
			}
			$makeshophash = md5($ashoppath.$activatevendor);
			$fp = fopen ("$ashopspath/updates/makeshop/$activatevendor","w");
			fwrite($fp, $makeshophash);
			fclose($fp);
			$params = array("lang"=>$lang);
			ashop_postasync("$ashopurl/admin/makeshop.php",$params);
		} else {
			include "$ashoppath/includes/PasswordHash.php";
			$passhasher = new PasswordHash(8, FALSE);
			$password = makePassword();
			$passhash = $passhasher->HashPassword($password);
			@mysqli_query($db,"UPDATE user SET password='$passhash' WHERE userid='$activatevendor'");
			$activatevendorresult = @mysqli_query($db,"SELECT * FROM user WHERE userid='$activatevendor'");
			$activatevendorrow = @mysqli_fetch_array($activatevendorresult);
			if (file_exists("$ashoppath/templates/messages/membersignupapproved-$lang.html")) $messagefile = "$ashoppath/templates/messages/membersignupapproved-$lang.html";
			else $messagefile = "$ashoppath/templates/messages/membersignupapproved.html";
			$fp = fopen ("$messagefile","r");
			if ($fp) {
				while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
				fclose($fp);
			} else {
				$messagetemplate="<html><head><title>".THANKYOUFORJOINING." $ashopname!</title></head><body><font face=\"$font\"><p>".THANKYOUFORJOINING." $ashopname!</p><p>".YOURUSERNAMEIS." <b>$shopuser</b>".YOURPASSWORDIS." <b>$password</b>.<br>".YOUCANLOGIN." <a href=\"$ashopurl/admin/login.php\">$ashopurl/admin/login.php</a>.";
				if ($membershops) $messagetemplate .= "<br>".YOUSHOPISLOCATED." <a href=\"$ashopurl/index.php?shop=$userid\">$ashopurl/index.php?shop=$userid</a>";
				$messagetemplate .= "</p></font></body></html>";
			}
			$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
			$message = str_replace("%username%",$activatevendorrow["username"],$message);
			$message = str_replace("%password%",$password,$message);
			$message = str_replace("%shopname%",$activatevendorrow["shopname"],$message);
			$message = str_replace("%description%",$activatevendorrow["shopdescription"],$message);
			$message = str_replace("%firstname%",$activatevendorrow["firstname"],$message);
			$message = str_replace("%lastname%",$activatevendorrow["lastname"],$message);
			$message = str_replace("%email%",$activatevendorrow["email"],$message);
			$message = str_replace("%address%",$activatevendorrow["address"],$message);
			$message = str_replace("%state%",$activatevendorrow["state"],$message);
			$message = str_replace("%zip%",$activatevendorrow["zip"],$message);
			$message = str_replace("%city%",$activatevendorrow["city"],$message);
			$message = str_replace("%country%",$activatevendorrow["country"],$message);
			$message = str_replace("%phone%",$activatevendorrow["phone"],$message);
			$message = str_replace("%url%",$activatevendorrow["url"],$message);
			// Get current date and time...
			$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
			$message = str_replace("%date%",$date,$message);
			
			$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$email",un_html($ashopname)." ".SHOPPINGMALLAPPLICATION,"$message","$headers");
		}
	}

	// Check for license agreement and read if available...
	$licenseagreement = "";
	if (file_exists("$ashoppath/agreement.txt")) {
		$fp = fopen ("$ashoppath/agreement.txt","r");
		if ($fp) {
			while (!feof ($fp)) $licenseagreement .= fgets($fp, 4096);
			fclose($fp);
		}
	}

	 // Include all per order fulfilment options...
	 $orderfulfilmentresult = @mysqli_query($db,"SELECT * FROM fulfiloptions WHERE perorder='1'");
	 while($orderfulfilmentrow = @mysqli_fetch_array($orderfulfilmentresult)) {
		 $fulfilmentoption = $orderfulfilmentrow["fulfiloptionid"];
		 $fulfilmentgroups[$fulfilmentoption][0]["productid"] = $parsed_invoice;
		 $fulfilmentgroups[$fulfilmentoption][0]["name"] = $parsed_description;
		 $fulfilmentgroups[$fulfilmentoption][0]["ebayid"] = $ebayitem;
		 $fulfilmentgroups[$fulfilmentoption][0]["quantity"] = 1;
		 $fulfilmentgroups[$fulfilmentoption][0]["ffproductid"] = "";
		 $fulfilmentgroups[$fulfilmentoption][0]["fflabelnumber"] = "";
		 $fulfilmentgroups[$fulfilmentoption][0]["ffpackagenumber"] = "";
		 $fulfilmentgroups[$fulfilmentoption][0]["ffparamquerystring"] = "";
	 }
		
	 // Run fulfilment options...
	 $returnmessages = array();
	 if($fulfilmentgroups && $authorized[0] != "PENDING") foreach($fulfilmentgroups as $fulfilmentoptionid => $productsinfo) {
		 if($fulfilmentoptionid) {
			 $result = @mysqli_query($db,"SELECT * FROM fulfiloptions WHERE fulfiloptionid='$fulfilmentoptionid'");
			 $row = @mysqli_fetch_array($result);
			 $fulfiloptionname = $row["name"];
			 $fulfilmentmethod = $row["method"];
			 $fulfilmentuserid = $row["userid"];
			 $fulfilmentpassword = $row["password"];
			 $fulfilmentemail = $row["email"];
			 $fulfilmentmessage = $row["message"];
			 $fulfilmenturl = $row["url"];
			 $fulfilmentparameters = $row["parameternames"];
			 $fulfilmentextrafields = $row["extrafields"];
			 $fulfilreturnmessage = $row["returnmessage"];
			 $fulfildiscount = $row["discount"];
			 $fulfildiscounttype = $row["discounttype"];
			 $fulfilecardimage = $row["ecardimage"];
			 $fulfilecardfont = $row["ecardfont"];
			 $fulfilecardtextcolor = $row["ecardtextcolor"];
			 $fulfiltop = $row["ecardtexttop"];
			 $fulfilleft = $row["ecardtextleft"];
			 $fulfilright = $row["ecardtextright"];
			 $fulfillevel = $row["level"];
			 $dofulfilment = 1;
			 include "admin/fulfilment/$fulfilmentmethod.ff";
		 }
	}

	// Read receipt template...
	if ($usebillreceipt && file_exists("$ashoppath/emerchant/invoice.html")) {
		$receiptfile = "$ashoppath/emerchant/invoice.html";
		$receiptformat = "html";
	} else if (!$usebillreceipt) {
		if ($receiptformat == "pdf") $receiptsuffix = "html";
		else $receiptsuffix = $receiptformat;
		if ($pricelevel > 0) {
			if (file_exists("$ashoppath/templates/messages/wsreceipt-$lang.{$receiptsuffix}")) $receiptfile = "$ashoppath/templates/messages/wsreceipt-$lang.$receiptsuffix";
			else $receiptfile = "$ashoppath/templates/messages/wsreceipt.{$receiptsuffix}";
		} else {
			if (file_exists("$ashoppath/templates/messages/receipt-$lang.{$receiptsuffix}")) $receiptfile = "$ashoppath/templates/messages/receipt-$lang.$receiptsuffix";
			else $receiptfile = "$ashoppath/templates/messages/receipt.{$receiptsuffix}";
		}
	}
	$fp = fopen ("$receiptfile","r");
	if ($fp) {
		while (!feof ($fp)) $receipttemplate .= fgets($fp, 4096);
		fclose($fp);
	}
	if (empty($invoiceid)) $invoiceid = $parsed_invoice;
	$receipt = str_replace("%ashopname%",$ashopname,$receipttemplate);
	$receipt = str_replace("%ashopemail%",$ashopemail,$receipt);
	$receipt = str_replace("%dateshort%",$dateshort,$receipt);
	$receipt = str_replace("%duedate%",$duedate,$receipt);
	$receipt = str_replace("%invoice%",$invoiceid,$receipt);
	$receipt = str_replace("%transactionid%",$parsed_remoteorderid,$receipt);
	$receipt = str_replace("%customer_firstname%",stripslashes($parsed_firstname),$receipt);
	$receipt = str_replace("%customer_lastname%",stripslashes($parsed_lastname),$receipt);
	$receipt = str_replace("%customer_address%",stripslashes($parsed_address),$receipt);
	$receipt = str_replace("%customer_city%",stripslashes($parsed_city),$receipt);
	if ($parsed_state == "none") $parsed_state = "";
	$receipt = str_replace("%customer_state%",stripslashes($parsed_state),$receipt);
	$receipt = str_replace("%customer_zip%",stripslashes($parsed_zip),$receipt);
	$receipt = str_replace("%customer_country%",stripslashes($parsed_country),$receipt);
	$receipt = str_replace("%customer_email%",$parsed_email,$receipt);
	$receipt = str_replace("%customer_phone%",$parsed_phone,$receipt);
	$receipt = str_replace("%customer_ip%",$ip_number,$receipt);
	if ($_POST["customerinfo"]) {
		if ($receiptformat == "html" || $receiptformat == "pdf") $receipt = str_replace("%customer_info%",$htmlcustomerinfo,$receipt);
		else $receipt = str_replace("%customer_info%",$txtcustomerinfo,$receipt);
	} else $receipt = str_replace("%customer_info%","",$receipt);
	$receipt = str_replace("%receipt_description%",$rdescriptionstring,$receipt);

	$receipt = str_replace("%payoption%",$payoptionname,$receipt);
	if (!empty($payoptionfee) && $payoptionfee > 0) $payoptionstring = "(".$currencysymbols[$ashopcurrency]["pre"].number_format($payoptionfee,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"].")";
	else $payoptionstring = "";
	$receipt = str_replace("%payoptionfee%",$payoptionstring,$receipt);

	$receipt = str_replace("%subtotal%",number_format($rsubtotal,$showdecimals,$decimalchar,$thousandchar),$receipt);
	$receipt = str_replace("%salestax%",number_format($salestax,$showdecimals,$decimalchar,$thousandchar),$receipt);
	$receipt = str_replace("%shipping%",number_format($shippingcost,$showdecimals,$decimalchar,$thousandchar),$receipt);

	if (!empty($newcustomerpassword)) {
		$receipt = str_replace("%newcustomerpassword%",$newcustomerpassword,$receipt);
		str_replace("\n<!-- Newcustomerpassword -->\n","",$receipt);
		str_replace("\n<!-- /Newcustomerpassword -->\n","\n",$receipt);
		str_replace("<!-- Newcustomerpassword -->","",$receipt);
		str_replace("<!-- /Newcustomerpassword -->","",$receipt);
	} else {
		$splitreceipt1 = explode("<!-- Newcustomerpassword -->", $receipt);
		$splitreceipt2 = explode("<!-- /Newcustomerpassword -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n")."\n".ltrim($splitreceipt2[1],"\n");
	}

	if ($shippingdiscount) {
		$receipt = str_replace("%shippingdiscount%",number_format($shippingdiscount,$showdecimals,$decimalchar,$thousandchar),$receipt);
		str_replace("\n<!-- Shippingdiscount -->\n","",$receipt);
		str_replace("\n<!-- /Shippingdiscount -->\n","\n",$receipt);
		str_replace("<!-- Shippingdiscount -->","",$receipt);
		str_replace("<!-- /Shippingdiscount -->","",$receipt);
	} else {
		$splitreceipt1 = explode("<!-- Shippingdiscount -->", $receipt);
		$splitreceipt2 = explode("<!-- /Shippingdiscount -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n")."\n".ltrim($splitreceipt2[1],"\n");
	}

	if ($virtualcash && $virtualcash != "0.00") {
		$receipt = str_replace("%virtualcash%",number_format($virtualcash,$showdecimals,$decimalchar,$thousandchar),$receipt);
		str_replace("\n<!-- Virtualcash -->\n","",$receipt);
		str_replace("\n<!-- /Virtualcash -->\n","\n",$receipt);
		str_replace("<!-- Virtualcash -->","",$receipt);
		str_replace("<!-- /Virtualcash -->","",$receipt);
	} else {
		$splitreceipt1 = explode("<!-- Virtualcash -->", $receipt);
		$splitreceipt2 = explode("<!-- /Virtualcash -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n")."\n".ltrim($splitreceipt2[1],"\n");
	}

	$receipt = str_replace("%amount%",$currencysymbols[$ashopcurrency]["pre"].number_format($parsed_price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"],$receipt);

    // Add special instructions...
	if(($tangiblegoods && $shipto) ||
	($downloadgoods && $authorized[0] != "PENDING") || 
	($unlockkeystring && $authorized[0] != "PENDING") || 
	($subscriptiongoods && $authorized[0] != "PENDING") || 
	$authorized[0] == "PENDING" || (!empty($returnmessages) && is_array($returnmessages))) {
		if ($tangiblegoods && $shipto) {
			$receipt = str_replace("%customer_shippingaddress%",$shipto,$receipt);
			str_replace("\n<!-- Shippingaddress -->\n","",$receipt);
			str_replace("\n<!-- /Shippingaddress -->\n","\n",$receipt);
			str_replace("<!-- Shippingaddress -->","",$receipt);
			str_replace("<!-- /Shippingaddress -->","",$receipt);
			if ($selectedshipoptions) {
				$receipt = str_replace("%shipoptions%",$selectedshipoptions,$receipt);
				str_replace("\n<!-- Shippingoption -->\n","",$receipt);
				str_replace("\n<!-- /Shippingoption -->\n","\n",$receipt);
				str_replace("<!-- Shippingoption -->","",$receipt);
				str_replace("<!-- /Shippingoption -->","",$receipt);
			} else {
				$splitreceipt1 = explode("<!-- Shippingoption -->", $receipt);
				$splitreceipt2 = explode("<!-- /Shippingoption -->", $splitreceipt1[1]);
				$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
			}
		} else {
			$splitreceipt1 = explode("<!-- Shippingaddress -->", $receipt);
			$splitreceipt2 = explode("<!-- /Shippingaddress -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}

		if ($downloadgoods && $authorized[0] != "PENDING") {
			$limiteddays = "$alloweddownloaddays";
			$unlimiteddays = UNLIMITED;
			if ($downloaddays = ( $alloweddownloaddays > 0 ? $limiteddays : $unlimiteddays ));
			$limiteddownloads = "$alloweddownloads";
			$unlimiteddownloads = UNLIMITED;
			if ($downloadtimes = ( $alloweddownloads > 0 ? $limiteddownloads : $unlimiteddownloads ));

			$receipt = str_replace("%ashopurl%",$ashopurl,$receipt);
			$receipt = str_replace("%customer_email%",$parsed_email,$receipt);
			$receipt = str_replace("%password%",$password,$receipt);
			$receipt = str_replace("%downloadtimes%",$downloadtimes,$receipt);
			$receipt = str_replace("%downloaddays%",$downloaddays,$receipt);
			str_replace("\n<!-- Downloads -->\n","",$receipt);
			str_replace("\n<!-- /Downloads -->\n","\n",$receipt);
			str_replace("<!-- Downloads -->","",$receipt);
			str_replace("<!-- /Downloads -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Downloads -->", $receipt);
			$splitreceipt2 = explode("<!-- /Downloads -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}

		if ($unlockkeystring && $authorized[0] != "PENDING") {
			$receipt = str_replace("%unlockkeys%",$unlockkeystring,$receipt);
			str_replace("\n<!-- Unlockkeys -->\n","",$receipt);
			str_replace("\n<!-- /Unlockkeys -->\n","\n",$receipt);
			str_replace("<!-- Unlockkeys -->","",$receipt);
			str_replace("<!-- /Unlockkeys -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Unlockkeys -->", $receipt);
			$splitreceipt2 = explode("<!-- /Unlockkeys -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}
		
		if ($subscriptiongoods && $authorized[0] != "PENDING") {
			$receipt = str_replace("%password%",$password,$receipt);
			$receipt = str_replace("%subscriptionlinks%",$subscriptionlinks,$receipt);
			str_replace("\n<!-- Subscriptions -->\n","",$receipt);
			str_replace("\n<!-- /Subscriptions -->\n","\n",$receipt);
			str_replace("<!-- Subscriptions -->","",$receipt);
			str_replace("<!-- /Subscriptions -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Subscriptions -->", $receipt);
			$splitreceipt2 = explode("<!-- /Subscriptions -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}
		if ($authorized[0] != "PENDING") {
			$splitreceipt1 = explode("<!-- Manualpayment -->", $receipt);
			$splitreceipt2 = explode("<!-- /Manualpayment -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		} else {
			str_replace("\n<!-- Manualpayment -->\n","",$receipt);
			str_replace("\n<!-- /Manualpayment -->\n","\n",$receipt);
			str_replace("<!-- Manualpayment -->","",$receipt);
			str_replace("<!-- /Manualpayment -->","",$receipt);
		}
		if ($licenseagreement) {
			$receipt = str_replace("%licenseagreement%",$licenseagreement,$receipt);
			str_replace("\n<!-- Licenseagreement -->\n","",$receipt);
			str_replace("\n<!-- /Licenseagreement -->\n","\n",$receipt);
			str_replace("<!-- Licenseagreement -->","",$receipt);
			str_replace("<!-- /Licenseagreement -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Licenseagreement -->", $receipt);
			$splitreceipt2 = explode("<!-- /Licenseagreement -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}
		if (is_array($returnmessages)) foreach ($returnmessages as $returnmessage) {
			if ($returnmessage) {
				$receipt = str_replace("<!-- /Specialinstructions -->","$returnmessage\n<!-- /Specialinstructions -->\n",$receipt);
			}
		}
		str_replace("\n<!-- Specialinstructions -->\n","",$receipt);
		str_replace("\n<!-- /Specialinstructions -->\n","\n",$receipt);
		str_replace("<!-- Specialinstructions -->","",$receipt);
		str_replace("<!-- /Specialinstructions -->","",$receipt);
	} else {
		$splitreceipt1 = explode("<!-- Specialinstructions -->", $receipt);
		$splitreceipt2 = explode("<!-- /Specialinstructions -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n")."\n\n".ltrim($splitreceipt2[1],"\n");
	}

	if ($usebillreceipt) {
		$receipt = str_replace("%paymentlink%","$ashopurl/payment.php?invoice=$parsed_invoice",$receipt);
		str_replace("\n<!-- Paymentlink -->\n","",$receipt);
		str_replace("\n<!-- /Paymentlink -->\n","\n",$receipt);
		str_replace("<!-- Paymentlink -->","",$receipt);
		str_replace("<!-- /Paymentlink -->","",$receipt);
	} else {
		$splitreceipt1 = explode("<!-- Paymentlink -->", $receipt);
		$splitreceipt2 = explode("<!-- /Paymentlink -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
	}

	$receipt = str_replace("%ashopname%",$ashopname,$receipt);
	$receipt = str_replace("%ashopaddress%",$ashopaddress,$receipt);
	$receipt = str_replace("%ashopemail%",$ashopemail,$receipt);
	$receipt = str_replace("%ashopphone%",$ashopphone,$receipt);
	 
	if ($shop != "1" && $shop && $shopname) {
		$receipt = str_replace("%membershop%","<a href=\"$ashopurl/index.php?shop=$shop\">".end($shopname)."</a>",$receipt);
		str_replace("\n<!-- Membershop -->\n","",$receipt);
		str_replace("\n<!-- /Membershop -->\n","\n",$receipt);
		str_replace("<!-- Membershop -->","",$receipt);
		str_replace("<!-- /Membershop -->","",$receipt);
	} else {
		$splitreceipt1 = explode("<!-- Membershop -->", $receipt);
		$splitreceipt2 = explode("<!-- /Membershop -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
	}

	// Create PDF from receipt...
	$attachment = "";
	if ($receiptformat == "pdf") {
		$receiptformat = "html";
		if (!$usebillreceipt && is_dir("$ashoppath/admin/receipts") && is_writable("$ashoppath/admin/receipts")) {
			$pdfauthor = $ashopname;
			$pdftitle = $ashopname." - ".INVOICENO.": $invoiceid";
			$pdfsubject = $ashopmetadescription;
			$pdfkeywords = $ashopmetakeywords;
			$pdfcontent = $receipt;
			$pdfcontent = str_replace("width=\"70\"", "width=\"60\"", $pdfcontent);
			$pdfcontent = str_replace(" size=\"2\"", " size=\"10\"", $pdfcontent);
			$pdfcontent = str_replace(" width=\"433\"", " width=\"433\" align=\"left\"", $pdfcontent);
			$pdfcontent = str_replace("<td valign=\"top\" bgcolor=\"#ffffff\">", "<td valign=\"top\" align=\"left\" bgcolor=\"#ffffff\">", $pdfcontent);
			$pdffilename = "$ashoppath/admin/receipts/{$parsed_invoice}.pdf";
			include "$ashoppath/includes/htmltopdf.inc.php";
			$attachment = $pdffilename;
		}
		if (file_exists("$ashoppath/templates/messages/pdforder-$lang.{$receiptsuffix}")) $receiptfile = "$ashoppath/templates/messages/pdforder-$lang.$receiptsuffix";
		else $receiptfile = "$ashoppath/templates/messages/pdforder.{$receiptsuffix}";
		$fp = fopen ("$receiptfile","r");
		$receipttemplate = "";
		if ($fp) {
			while (!feof ($fp)) $receipttemplate .= fgets($fp, 4096);
			fclose($fp);
		}
		$receipt = str_replace("%ashopname%",$ashopname,$receipttemplate);
		$receipt = str_replace("%ashopemail%",$ashopemail,$receipt);
		$receipt = str_replace("%dateshort%",$dateshort,$receipt);
		$receipt = str_replace("%duedate%",$duedate,$receipt);
		$receipt = str_replace("%invoice%",$invoiceid,$receipt);
		$receipt = str_replace("%transactionid%",$parsed_remoteorderid,$receipt);
		$receipt = str_replace("%customer_firstname%",stripslashes($parsed_firstname),$receipt);
		$receipt = str_replace("%customer_lastname%",stripslashes($parsed_lastname),$receipt);
		$receipt = str_replace("%customer_address%",stripslashes($parsed_address),$receipt);
		$receipt = str_replace("%customer_city%",stripslashes($parsed_city),$receipt);
		$receipt = str_replace("%customer_state%",stripslashes($parsed_state),$receipt);
		$receipt = str_replace("%customer_zip%",stripslashes($parsed_zip),$receipt);
		$receipt = str_replace("%customer_country%",stripslashes($parsed_country),$receipt);
		$receipt = str_replace("%customer_email%",$parsed_email,$receipt);
		$receipt = str_replace("%customer_phone%",$parsed_phone,$receipt);
	}

	 if ($receiptformat == "html") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	 else {
		 $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
		 $receipt = strip_tags($receipt);
	 }

	 if ($usebillreceipt) @ashop_mail("$parsed_email",un_html($ashopname,1)." - ".INVOICENO.": $invoiceid","$receipt","$headers");
	 else @ashop_mail("$parsed_email",un_html($ashopname,1)." - ".RECEIPT,"$receipt","$headers",$attachment);

	 // Store receipt if possible...
	 if (!$usebillreceipt && is_dir("$ashoppath/admin/receipts") && is_writable("$ashoppath/admin/receipts")) {
		 $fp = @fopen ("$ashoppath/admin/receipts/$parsed_invoice", "w");
		 if ($fp) @fwrite($fp, str_replace("\n","\r\n",$receipt));
		 @fclose($fp);
		 @chmod("$ashoppath/admin/receipts/$parsed_invoice", 0666);
	 } else if ($usebillreceipt && is_dir("$ashoppath/emerchant/invoices") && is_writable("$ashoppath/emerchant/invoices")) {
		 $fp = @fopen ("$ashoppath/emerchant/invoices/$parsed_invoice", "w");
		 if ($fp) @fwrite($fp, str_replace("\n","\r\n",$receipt));
		 @fclose($fp);
		 @chmod("$ashoppath/emerchant/invoices/$parsed_invoice", 0666);
	 }
	 
	 // Notify administrator by email...
	 $rfont = "font face=\"Arial, Helvetica, sans-serif\" size=\"2\"";
	 $message="<html><head><title>$ashopname - Order</title></head><body><font face=\"$font\">";
	 if ($adminunlockkeystring) $message.= "$adminunlockkeystring";	
	 $message.="<p>The following transaction took place $date from: $ip_number</p>\r\n<p><b>Order ID:</b> $invoiceid<br>";
	 if (file_exists("$ashoppath/emerchant/quote.php")) $message.="<b>Source:</b> $source<br>";
	 $message .="<br><b>Sold To:</b><br>";
	 $shopmessage = $message;
	 if ($defaultlanguage == "sv") {
		 $message .= stripslashes($parsed_firstname)." ".stripslashes($parsed_lastname)."<br>\n".stripslashes($parsed_address)."<br>\n";
		 if ($parsed_state && $parsed_state != "other" && $parsed_state != "none") $message .= stripslashes("$parsed_state").", ";
		 $message .= stripslashes("$parsed_zip $parsed_city")."<br>\n".stripslashes($parsed_country)."<br>\n$parsed_email<br>\n$parsed_phone<br>";
	 } else $message .= stripslashes($parsed_firstname)." ".stripslashes($parsed_lastname)."<br>
	".stripslashes($parsed_address)."<br>
          ".stripslashes("$parsed_city, $parsed_state $parsed_zip")."<br>
          ".stripslashes($parsed_country)."<br>
	  $parsed_email, $parsed_phone<br>";
	 if ($dmshowcustomers) $shopmessage = $message;
	 else $shopmessage .= stripslashes($parsed_firstname)." ".stripslashes($parsed_lastname)."<br>";
	 $message .= "Customer ID: $customerid";
	 $shopmessage .= "Customer ID: $customerid";
	 if ($_POST["customerinfo"]) $message .= "<br>$htmlcustomerinfo";
	 $messagepart = "</p>\r\n<p><b>Products:</b><br>
<table bordercolor=\"#cccccc\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\" border=\"0\"><tr bgcolor=\"#ffffcc\">
    <td align=\"middle\" width=\"30\"><b><$rfont><u>Qty</u></font></b></td><td align=\"middle\" width=\"70\"><b><$rfont><u>SKU</u></font></b></td><td width=\"363\"><b><$rfont><u>Description</u></font></b></td>
    <td align=\"right\" width=\"60\"><b><$rfont><u>Price</u></font></b></td>
    <td align=\"right\" width=\"60\"><b><$rfont><u>Amount</u></font></b></td></tr>%adescriptionstring%
  <tr>
    <td align=\"right\" valign=\"bottom\" bgcolor=\"#ffffff\" colspan=\"3\" rowspan=\"";
	if($shippingdiscount) $messagepart .= "5";
	else $messagepart .= "4";
	$messagepart .= "\"><$rfont><b>Paid By:</b> $payoptionname ";
	if ($payoptionfee) $messagepart .= "(".$currencysymbols[$ashopcurrency]["pre"]."$payoptionfee".$currencysymbols[$ashopcurrency]["post"].")";
	$messagepart .= "</font></td>";
	$message .= $messagepart;
	$shopmessage .= $messagepart;
	$message .= "
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffcc\"><b><$rfont>Subtotal:</font></b></td>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffff\"><$rfont>".number_format($rsubtotal,$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>
  <tr>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffcc\"><b><$rfont>Tax:</font></b></td>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffff\"><$rfont>".number_format($salestax,$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>
  <tr>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffcc\"><b><$rfont>Shipping:</font></b></td>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffff\"><$rfont>".number_format($shippingcost,$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>";
	if($shippingdiscount) $message .= "
  <tr>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffcc\"><b><$rfont>Discount:</font></b></td>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffff\"><$rfont>-".number_format($shippingdiscount,$showdecimals,$decimalchar,$thousandchar)."</font></td></tr>";
	$message .= "
  <tr>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffcc\"><$rfont><b>Total:</b></font></td>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffff\"><$rfont><b>".$currencysymbols[$ashopcurrency]["pre"]."$parsed_price".$currencysymbols[$ashopcurrency]["post"]."</b></font></td></tr></table></p>\r\n";
	$shopmessage .= "
  <tr>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffcc\"><$rfont><b>Total:</b></font></td>
    <td align=\"right\" width=\"60\" bgcolor=\"#ffffff\"><$rfont><b>".$currencysymbols[$ashopcurrency]["pre"]."%memberprice%".$currencysymbols[$ashopcurrency]["post"]."</b></font></td></tr></table></p>\r\n";
	 if ($tangiblegoods && $shipto) {
		 if ($shopemail) {
			 $shopmessage .= "<p><b>Ship To:</b><br>$shipto</p>\r\n";
			 if ($selectedshipoptions) $shopmessage .= "<p>Selected shipping option: $selectedshipoptions</p>\r\n";
		 }
		 $message .= "<p><b>Ship To:</b><br>$shipto</p>\r\n";
		 if ($selectedshipoptions) $message .= "<p>Selected shipping option: $selectedshipoptions</p>\r\n";
	 }
	 if ($duplicatesubscription) {
		 $message.= "<p><b>The customer has purchased duplicate subscriptions to the same directory! A refund is required!</b></p>\r\n";
		 $shopmessage.= "<p><b>The customer has purchased duplicate subscriptions to the same directory! A refund is required!</b></p>\r\n";
	 }
	 if ($parsed_affiliate) $message.= "<p><b>Referring affiliate:</b> $parsed_affiliate, $affiliatefirstname $affiliatelastname</p>\r\n";
	 if ($authorized[0] == "PENDING" || $authorized[0] == "PROCESS") {
		 if($responsemsg == "OL") $message .= "<p><a href=\"$ashopurl/admin/login.php?process";
		 else $message.= "<p><a href=\"$ashopurl/admin/activate.php?activate";
		 $message.="=$parsed_invoice\" target=\"_blank\">";
		 if($responsemsg == "OL") $message.= "Process payment for";
		 else $message.= "Activate";
		 $message .=" this order</a></p>\r\n";
	 }
	 $message.="</font></body></html>";

	 // Sort product list by SKU code...
	 $adescription = "";
	 if ($skucodes && is_array($skucodes)) asort($skucodes);
	 if ($adescriptionstring && is_array($adescriptionstring)) foreach($skucodes as $aproductnumber=>$askucode) $adescription .= $adescriptionstring[$aproductnumber];
	 if ($aproductdescription && is_array($aproductdescription)) foreach($skucodes as $aproductnumber=>$askucode) $adescription .= $aproductdescription[$aproductnumber];
	 $adescription .= $extraadescription;

	 $message = str_replace("%adescriptionstring%","$adescription",$message);
	 $shopmessage.="</font></body></html>";
	 $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

	 if ($usebillreceipt) {
		 //@ashop_mail("$ashopemail",un_html($ashopname)." - Invoice No: $parsed_invoice","$receipt","$headers");
	 } else {
		 @ashop_mail("$ashopemail",un_html($ashopname,1)." - order ID: $invoiceid","$message","$headers");
		 if ($shopemail) {
			foreach ($shopemail as $shopid=>$thisshopemail) {
				$thisshopmessage = str_replace("%adescriptionstring%",$sdescriptionstring["$shopid"],$shopmessage);
				$thisshopmessage = str_replace("%memberprice%",number_format($memberprice["$shopid"],$showdecimals,$decimalchar,$thousandchar),$thisshopmessage);
				@ashop_mail("$thisshopemail",un_html($shopname["$shopid"],1)." - order ID: $invoiceid","$thisshopmessage","$headers");
			}
		 }
	 }
}

// Make sure the name of the shop is displayed...
if (!$shopname) $shopname[1] = $ashopname;

// Show information about the transaction...
if ($visibleorderprocessing == "TRUE" || $payopt) {

	if ($gw == "2checkoutv2" && ! $payopt) {
		echo "<html><head>
		<title>Redirect</title>
		<meta http-equiv=\"Refresh\" content=\"0; URL=$ashopurl/order.php?payopt=$payoptionid&ofinv=$parsed_invoice\">
		</head>
		<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" alink=\"#000000\" vlink=\"#000000\">
		<center>
		<p>&nbsp;</p>
		<p><font face=\"Arial, Helvetica, sans-serif\"><b>If this page does not redirect...</b></font></p>
		<p><b><font face=\"Arial, Helvetica, sans-serif\">click <a href=\"$ashopurl/order.php?payopt=$payoptionid&ofinv=$parsed_invoice\">HERE</a></font></b></p>
		</center>
		</body></html>";
		exit;
	}

	if ($ofinv) {
		$ofinvresult = @mysqli_query($db,"SELECT * FROM orders WHERE orderid='$ofinv'");
		$parsed_products = @mysqli_result($ofinvresult,0,"products");
		$parsed_price = @mysqli_result($ofinvresult,0,"price");
		$descriptionstring = @mysqli_result($ofinvresult,0,"description");
		$parsed_remoteorderid = @mysqli_result($ofinvresult,0,"remoteorderid");
		$orderstatus = @mysqli_result($ofinvresult,0,"status");
		if ($orderstatus == "Cancelled") $thankyoutext = "<br /><br /><table class=\"ashopthankyouframe\"><tr align=\"center\"><td><span class=\"ashopthankyouheader\">Payment failed!</span></td></tr><tr align=\"center\"><td><span class=\"ashopthankyoutext2\">The payment was cancelled by you.</span></td></tr></table>";
		if ($orderstatus == "Expired") $thankyoutext = "<br /><br /><table class=\"ashopthankyouframe\"><tr align=\"center\"><td><span class=\"ashopthankyouheader\">Payment failed!</span></td></tr><tr align=\"center\"><td><span class=\"ashopthankyoutext2\">The payment has expired.</span></td></tr></table>";
		if ($orderstatus == "Failure") $thankyoutext = "<br /><br /><table class=\"ashopthankyouframe\"><tr align=\"center\"><td><span class=\"ashopthankyouheader\">Payment failed!</span></td></tr><tr align=\"center\"><td><span class=\"ashopthankyoutext2\">The payment could not be completed. There are no further details available as to why.</span></td></tr></table>";
		$invoiceid = @mysqli_result($ofinvresult,0,"invoiceid");
		$parsed_invoice = $ofinv;
		if (empty($invoiceid)) $invoiceid = $parsed_invoice;
		$password = @mysqli_result($ofinvresult,0,"password");
		$customerid = @mysqli_result($ofinvresult,0,"customerid");
		$ofcustresult = @mysqli_query($db,"SELECT * FROM customer WHERE customerid='$customerid'");
		$parsed_firstname = @mysqli_result($ofcustresult,0,"firstname");
		$parsed_lastname = @mysqli_result($ofcustresult,0,"lastname");
		$parsed_email = @mysqli_result($ofcustresult,0,"email");
		$ofshiptoresult = @mysqli_query($db,"SELECT * FROM shipping WHERE customerid='$customerid'");
		$row = @mysqli_fetch_array($ofshiptoresult);
		if ($row["shippingbusiness"]) $shipto = "{$row["shippingbusiness"]}<br>\r\n{$row["shippingfirstname "]} {$row["shippinglastname"]}<br>\r\n";
		else $shipto = "{$row["shippingfirstname"]} {$row["shippinglastname"]}<br>\r\n";
		$shipto .= "{$row["shippingaddress"]}<br>\r\n";
		if ($row["shippingaddress2"]) $shipto .= "{$row["shippingaddress2"]}<br>\r\n";
		$shipto .= "{$row["shippingcity"]}, {$row["shippingstate"]} {$row["shippingzip"]}<br>\r\n";
		$shipto .= $countries[$row["shippingcountry"]];
		$ofkeyresult = @mysqli_query($db,"SELECT * FROM unlockkeys WHERE orderid='$ofinv' ORDER BY productid");
		$unlockkeystring = "";
		while ($row = @mysqli_fetch_array($ofkeyresult)) {
			$keyproduct = $row["productid"];
			$productresult = @mysqli_query($db,"SELECT * FROM product WHERE productid='$keyproduct'");
			$thisproductname = @mysqli_result($productresult,0,"name");
			$unlockkeystring .= "$thisproductname: <br><b>{$row["keytext"]}</b><br>";
		}
		// Get information about purchased products...
		$productsincart = ashop_parseproductstring($db, $parsed_products);
		if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
			$thisproductid = $thisproduct["productid"];
			$checkfiles = @mysqli_query($db,"SELECT * FROM productfiles WHERE productid='$thisproductid'");
			$files = @mysqli_num_rows($checkfiles);
			if ($files && $thisproduct["download"] != "none") $downloadgoods = 1;
		}
	}

	// Login to the delivery area if autodelivery is activated...
	if ($autodelivery && $downloadgoods && $authorized[0] != "PENDING" && $parsed_email && $password) {
		if ($localprocessing) $deliveryurl = "$ashopsurl/deliver.php";
		else $deliveryurl = "$ashopurl/deliver.php";
		echo "<html><head><title>".REDIRECTING."</title>\n".CHARSET."</head><body>
		<form name=\"deliveryform\" method=\"post\" action=\"$deliveryurl\">
		<input type=\"hidden\" name=\"email\" value=\"$parsed_email\">
		<input type=\"hidden\" name=\"password\" value=\"$password\"></form>
		<script language=\"JavaScript\">document.deliveryform.submit()</script>
		</body></html>";
		exit;
	}

	// Get thank you message for free gifts...
	if (empty($virtualcashamount) && ($parsed_price == "0.00" || $parsed_price == 0 || !$parsed_price) && $gw != "ccbill" && $gw != "ccbillrecurring") {
		if (empty($nogift)) {
			if ($downloadgoods) $thankyoutext = DOWNLOADGIFTTHANKYOU;
			else $thankyoutext = GIFTTHANKYOU;
		} else {
			if ($downloadgoods) $thankyoutext = DOWNLOADZEROPRICETHANKYOU;
			else $thankyoutext = ZEROPRICETHANKYOU;
		}
	}

	// Print thank you message...
	$thankyoutext = str_replace("%amount%",$parsed_price,$thankyoutext);
	$thankyoutext = str_replace("%orderid%",$invoiceid,$thankyoutext);
	$thankyoutext = str_replace("%gatewayorderid%",$parsed_remoteorderid,$thankyoutext);
	$thankyoutext = str_replace("%description%",$descriptionstring,$thankyoutext);
	$thankyoutext = str_replace("%firstname%",$parsed_firstname,$thankyoutext);
	$thankyoutext = str_replace("%lastname%",$parsed_lastname,$thankyoutext);
	if ($shipto) $thankyoutext = str_replace("%shippingaddress%",$shipto,$thankyoutext);
	else $thankyoutext = str_replace("%shippingaddress%",NOSHIP,$thankyoutext);
	$thankyoutext = str_replace("%email%",$parsed_email,$thankyoutext);
	$thankyoutext = str_replace("%password%",$password,$thankyoutext);
	$thankyoutext = str_replace("%keycodes%",$unlockkeystring,$thankyoutext);
	if (is_array($shopname)) $useshopname = end($shopname);
	else $useshopname = $shopname;
	$thankyoutext = str_replace("%shopname%",$useshopname,$thankyoutext);
	$thankyoutext = str_replace("%shopaddress%",$shopaddress,$thankyoutext);
	$thankyoutext = str_replace("%shopemail%",$shopemail,$thankyoutext);
	$thankyoutext = str_replace("%shopphone%",$shopphone,$thankyoutext);
	if (!$membershops) $useshopname = $ashopname;
	echo "<!--success-->$thankyoutext
	<br><table class=\"ashopthankyouframe\"><tr><td align=\"center\"><br><span class=\"ashopthankyoutext2\"><a href=\"$orderpagelink\">".BACK."$useshopname</a><br></span></td></tr>
		</table>";

	// Print delivery form if needed...
	if ($subscriptiongoods && $authorized[0] != "PENDING") echo "
	<br><table class=\"ashopthankyouframe\">
    <tr><td><span class=\"ashopthankyoutext2\"><p>".ACCESSP."</p><p>$subscriptionlinks</p></span></td></tr></table><br>";
	if ($downloadgoods && $authorized[0] != "PENDING") {
		echo "
        <br><table class=\"ashopthankyouframe\">
		<tr><td><span class=\"ashopthankyoutext2\">".ACCESSD."</span></td></tr><tr><td>
        <form method=\"post\" action=\"$ashopurl/deliver.php\">
        <table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
        <tr><td><span class=\"ashopthankyoutext2\">".EMAIL.": </span></td><td><input type=\"text\" name=\"email\"></td><td>&nbsp;</td></tr>
        <tr><td><span class=\"ashopthankyoutext2\">".PASS.": </span></td><td><input type=\"password\" name=\"password\"></td>";
		if ($device == "mobile") echo "<td>&nbsp;</td></tr><tr><td colspan=\"2\"><input type=\"submit\" value=\"Login\"></td></tr></table></form></td></tr></table><br>";
		else echo "<td><input type=\"submit\" value=\"Login\"></td></tr></table></form></td></tr></table><br>";
	}

	ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
}
// Close database...
@mysqli_close($db);
?>