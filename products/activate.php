<?php
// AShop
// Copyright 2012 - AShop Software - http://www.ashopsoftware.com
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

include "config.inc.php";
if ($_POST["secret"] && $_POST["orderid"]) {
	$checksecret = md5("{$ashoppath}daopayactivation{$orderid}");
	if ($_POST["secret"] == $checksecret) $userid = "1";
	else exit;
} else {
	include "checklogin.inc.php";
	if ($activate) $orderid = $activate;
}
include "ashopfunc.inc.php";
include "ashopconstants.inc.php";

// Open database...
$db = @mysql_connect("$databaseserver", "$databaseuser", "$databasepasswd");
@mysql_select_db("$databasename",$db);

// Get order info...
if (substr($orderid, 0, 2) == "ws") {
	$orderid = substr($orderid, 2);
	$wholesaleorder = TRUE;
} else $wholesaleorder = FALSE;

$sql="SELECT * FROM orders WHERE orderid='$orderid'";
$result = @mysql_query("$sql",$db);
$password = @mysql_result($result,0,"password");
$customerid = @mysql_result($result, 0, "customerid");
$products = @mysql_result($result, 0, "products");
$parsed_price = @mysql_result($result, 0, "price");
$payoption = @mysql_result($result, 0, "payoptionid");
$transactionid = @mysql_result($result, 0, "remoteorderid");
$sql="SELECT * FROM payoptions WHERE payoptionid='$payoption'";
$result = @mysql_query("$sql",$db);
$deliverpending = @mysql_result($result,0,"deliverpending");
$gateway = @mysql_result($result, 0, "gateway");
if (($gateway == "authnetaimdelayed" || $gateway == "authnetsimdelayed") && $transactionid) {
	$authnetpassword = @mysql_result($result, 0, "transactionkey");
	$user = @mysql_result($result, 0, "merchantid");
	$testmode = @mysql_result($result, 0, "testmode");
	$postfields = "x_encap_char=&x_delim_char=|&x_login=$user&x_amount=$parsed_price&x_tran_key=$authnetpassword&x_trans_id=$transactionid&x_type=PRIOR_AUTH_CAPTURE&x_version=3.1&x_delim_data=TRUE&x_relay_response=FALSE";
	if ($testmode == "TRUE") $postfields .= "&x_test_request=TRUE";
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if (strstr(curl_version(), "SSL") || (is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS")))) {
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($ch, CURLOPT_URL,"https://secure.authorize.net/gateway/transact.dll");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$htmlresult=curl_exec ($ch);
			$curlerror = curl_error($ch);
			curl_close ($ch);
		}
	} else {
		exec('curl -d "' . $postfields . '" https://secure.authorize.net/gateway/transact.dll', $resultarray);
		$htmlresult = $resultarray[0];
	}
	$resultarray=explode("|", $htmlresult);
	if ($resultarray[0] != "1") {
		// Display error message...
		$responsemessage = $resultarray[3];
		if ($responsemessage) {
			echo "<html><head><title>Authorize.Net Error!</title></head>
			<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
			<tr><td align=\"center\"><img src=\"images/logo.gif\"><br><hr width=\"90%\" size=\"0\" noshade><br>$message<br><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>Authorize.Net Order Activation Failed!</b></font><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$responsemessage</font><br></td></tr></table></body></html>";
		} else {
			echo "<html><head><title>Authorize.Net Error!</title></head>
			<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
			<tr><td align=\"center\"><img src=\"images/logo.gif\"><br><hr width=\"90%\" size=\"0\" noshade><br>$message<br><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>Authorize.Net Order Activation Failed!</b></font><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Could not communicate with payment server.</font><br></td></tr></table></body></html>";
		}
		exit;
	} else @mysql_query("DELETE FROM paymentinfo WHERE orderid='$orderid'");
}

// Mark the order as paid...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
$dateshort = date("Y-m-d", time()+$timezoneoffset);
$sql="UPDATE orders SET paid='$date' WHERE orderid='$orderid'";
$result = @mysql_query("$sql",$db);
$sql="UPDATE memberorders SET paid='$date' WHERE orderid='$orderid'";
$result = @mysql_query("$sql",$db);

// Move invoice to receipts if the receipt is missing...
if (is_dir("$ashoppath/admin/receipts") && is_dir("$ashoppath/emerchant/invoices")) {
	if (!file_exists("$ashoppath/admin/receipts/$orderid") && file_exists("$ashoppath/emerchant/invoices/$orderid")) {
		copy("$ashoppath/emerchant/invoices/$orderid","$ashoppath/admin/receipts/$orderid");
		unlink("$ashoppath/emerchant/invoices/$orderid");
	}
}

// Get subscriptions...
unset($fulfilmentgroups);
$subscriptionlinks = "";
$downloadable = 0;
$descriptionstring = "";
unset($alreadysubscribed);
$productsincart = ashop_parseproductstring($db, $products);
if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
    $thisproductid = $thisproduct["productid"];
	$thisprice = $thisproduct["price"];
	$thisproductname = $thisproduct["name"];
	$thisquantity = $thisproduct["quantity"];
	$result = @mysql_query("SELECT * FROM productfiles WHERE productid='$thisproductid'",$db);
	$files = @mysql_num_rows($result);
	$sql="SELECT * FROM product WHERE productid=$thisproductid";
	$result = @mysql_query("$sql",$db);
	$subscriptiondir = @mysql_result($result,0,"subscriptiondir");
	$subscriptionurl = @mysql_result($result,0,"protectedurl");
	$producttype = @mysql_result($result,0,"prodtype");
	if ($files && $producttype != "subscription" && $password) $downloadable = 1;
	else if ($subscriptiondir && $password) {
		// Check for Password Robot directory...
		if ($probotpath && file_exists("$probotpath/data/groups.txt")) {
			$fp = fopen ("$probotpath/data/groups.txt","r");
			while (!feof ($fp)) {
				$fileline = rtrim(fgets($fp, 4096));
				$probotgrouparray = explode("\t",$fileline);
				if ($probotgrouparray[2] == $subscriptiondir) $prgroupname = $probotgrouparray[1];
			}
		}
		if (!is_array($alreadysubscribed) || !in_array($subscriptiondir,$alreadysubscribed)) {
			$alreadysubscribed[] = $subscriptiondir;
			if ($probotpath && file_exists("$probotpath/data/groups/$prgroupname.txt")) {
				if ($subscriptionurl) $subscriptionlinks .= "<a href=\"$subscriptionurl\">$thisproductname</a><br>";
				else $subscriptionlinks .= "$thisproductname<br>";
			} else if ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt") && file_exists("$papluspath/$subscriptiondir/d_active.txt")) {
				if ($subscriptionurl) $subscriptionlinks .= "<a href=\"$subscriptionurl\">$thisproductname</a><br>";
				else $subscriptionlinks .= "$thisproductname<br>";
			} else {
				if ($subscriptionurl) $subscriptionlinks .= "<a href=\"$subscriptionurl\">$thisproductname</a><br>";
				else $subscriptionlinks .= "<a href=\"$ashopurl/$subscriptiondir\">$thisproductname</a><br>";
			}
		}
	}
	$fulfilmentoption = @mysql_result($result,0,"fulfilment");
	$ffproductid = @mysql_result($result,0,"ffproductid");
	$fflabelnumber = @mysql_result($result,0,"fflabelnumber");
	$ffpackagenumber = @mysql_result($result,0,"ffpackagenumber");
	$ffparamnames = explode("|",@mysql_result($result,0,"ffparamnames"));
	unset($ffparamquerystring);
	if(is_array($thisproduct["parametervalues"])) foreach ($thisproduct["parametervalues"] as $ffparamnumber => $ffparamvalue) $ffparamquerystring .= $ffparamnames[$ffparamnumber]."=$ffparamvalue&";
	$ffparamquerystring = substr($ffparamquerystring, 0, -1);
	if ($fulfilmentoption && !$thisproduct["disablefulfilment"]) {
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["productid"] = $thisproduct["productid"];
		$fulfilmentgroups[$fulfilmentoption][$thisproductid]["name"] = $thisproductname;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["ebayid"] = $thisproductname.$thisproduct["ebayid"];
		$fulfilmentgroups[$fulfilmentoption][$thisproductid]["quantity"] = $thisquantity;
		$fulfilmentgroups[$fulfilmentoption][$thisproductid]["price"] = $thisprice;
		$fulfilmentgroups[$fulfilmentoption][$thisproductid]["ffproductid"] = $ffproductid;
		$fulfilmentgroups[$fulfilmentoption][$thisproductid]["fflabelnumber"] = $fflabelnumber;
		$fulfilmentgroups[$fulfilmentoption][$thisproductid]["ffpackagenumber"] = $ffpackagenumber;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffparamquerystring"] = $ffparamquerystring;
	}
	$descriptionstring .= $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
}

// Get customer info...
$sql="SELECT * FROM customer WHERE customerid='$customerid'";
$result = @mysql_query("$sql",$db);
$firstname = @mysql_result($result, 0, "firstname");
$lastname = @mysql_result($result, 0, "lastname");
$email = ashop_mailsafe(@mysql_result($result, 0, "email"));
$lang = @mysql_result($result, 0, "preflanguage");
$parsed_address = @mysql_result($result, 0, "address");
$parsed_zip = @mysql_result($result, 0, "zip");
$parsed_city = @mysql_result($result, 0, "city");
$parsed_state = @mysql_result($result, 0, "state");
$parsed_country = @mysql_result($result, 0, "country");
$parsed_phone = @mysql_result($result, 0, "phone");
if (!$wholesaleorder) {
	$sql="SELECT * FROM shipping WHERE customerid=$customerid";
	$result = @mysql_query("$sql",$db);
	$shippingfirstname = @mysql_result($result, 0, "shippingfirstname");
	$shippinglastname = @mysql_result($result, 0, "shippinglastname");
	$shippingaddress = @mysql_result($result, 0, "shippingaddress");
	$shippingcity = @mysql_result($result, 0, "shippingcity");
	$shippingstate = @mysql_result($result, 0, "shippingstate");
	$shippingzip = @mysql_result($result, 0, "shippingzip");
	$shippingcountry = @mysql_result($result, 0, "shippingcountry");
} else {
	$shippingfirstname = $firstname;
	$shippinglastname = $lastname;
	$shippingaddress = $parsed_address;
	$shippingcity = $parsed_city;
	$shippingstate = $parsed_state;
	$shippingzip = $parsed_zip;
	$shippingcountry = $parsed_country;
}
$shipto = "$shippingfirstname $shippinglastname<br>\r\n";
$shipto .= "$shippingaddress<br>\r\n";
$shipto .= "$shippingcity, $shippingstate $shippingzip<br>\r\n";
$shipto .= $countries["$shippingcountry"];

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "$ashoppath/language/$lang/ad_activate.inc.php";

// Make variables compatible with fulfilment options...
$parsed_invoice = $orderid;
$parsed_firstname = $firstname;
$parsed_lastname = $lastname;
$parsed_email = $email;
$handlingcosts = ashop_gethandlingcost($products);
$selectedshipoptions = "";
if ($handlingcosts) foreach($handlingcosts as $handlingname => $value) {
	if (strstr($handlingname, "so")) {
		$result = @mysql_query("SELECT * FROM shipoptions WHERE shipoptionid='$value'",$db);
		if ($selectedshipoptions) $selectedshipoptions .= ", ";
		$selectedshipoptions .= @mysql_result($result, 0, "description");
	}
}

// Get unlock keys included in the order...
$sql="SELECT keytext, productid FROM unlockkeys WHERE orderid='";
if ($wholesaleorder) $sql .= "ws";
$sql .= "$orderid'";
$result = @mysql_query("$sql",$db);
for ($i = 0; $i < @mysql_num_rows($result); $i++) {
	$keytext = @mysql_result($result,$i,"keytext");
	$productid = @mysql_result($result,$i,"productid");
	$sql = "SELECT name FROM product WHERE productid=$productid";
	$result2 = @mysql_query("$sql",$db);
	$thisproductname = @mysql_result($result2,0,"name");
	$unlockkeystring .= "$thisproductname:<br><b>$keytext</b><br>";
	if (is_array($fulfilmentgroups)) foreach ($fulfilmentgroups as $fulfilmentoption=>$productinfo) {
		if (is_array($productinfo)) foreach ($productinfo as $productnumber=>$productinfo) {
			if ($fulfilmentgroups[$fulfilmentoption][$productnumber]["productid"] == $thisproductid) $fulfilmentgroups[$fulfilmentoption][$productnumber]["keys"][$i] = $keytext;
		}
	}
}

// Send login details and/or unlock keys to the customer...
if ($unlockkeystring || $subscriptionlinks || $downloadable) {
	// Read order activation template...
	if ($receiptformat == "pdf") $receiptformat = "html";
	if (file_exists("$ashoppath/templates/messages/orderactivationmessage-$lang.{$receiptformat}")) $receiptfile = "$ashoppath/templates/messages/orderactivationmessage-$lang.$receiptformat";
	else $receiptfile = "$ashoppath/templates/messages/orderactivationmessage.{$receiptformat}";
	$fp = fopen ("$receiptfile","r");
	if ($fp) {
		while (!feof ($fp)) $receipttemplate .= fgets($fp, 4096);
		fclose($fp);
	}
	$receipt = str_replace("%ashopname%",$ashopname,$receipttemplate);
	$receipt = str_replace("%ashopemail%",$ashopemail,$receipt);
	$receipt = str_replace("%dateshort%",$dateshort,$receipt);
	$receipt = str_replace("%invoice%",$parsed_invoice,$receipt);
	$receipt = str_replace("%transactionid%",$transactionid,$receipt);
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
	$receipt = str_replace("%receipt_description%",$descriptionstring,$receipt);
	$receipt = str_replace("%amount%",$currencysymbols[$ashopcurrency]["pre"]."$parsed_price".$currencysymbols[$ashopcurrency]["post"],$receipt);
	
	if ($downloadable) {
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

	if ($receiptformat == "html") $headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	else {
		$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
		$receipt = strip_tags($receipt);
	}
	if (!$deliverpending) @ashop_mail("$email",un_html($ashopname)." - Order Activation","$receipt","$headers");
}

// Give affiliate credit if necessary...
$sql = "SELECT * FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='0'";
$result = @mysql_query("$sql",$db);
$affiliateid = @mysql_result($result,0,"affiliateid");
$commission = @mysql_result($result,0,"commission");
$affiliatelevel = @mysql_result($result,0,"commissionlevel");
if ($affiliateid && $commission) {
	$sql="INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$affiliateid', '$orderid', '0', '0', '$commission')";
	$result = @mysql_query("$sql", $db);

	$sql="DELETE FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='0'";
	$result = @mysql_query("$sql", $db);
	
	$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
	$result = @mysql_query("$sql", $db);
	$affiliatemail = ashop_mailsafe(@mysql_result($result, 0, "email"));
	$affiliatepassword = @mysql_result($result, 0, "password");
	$affiliateapiurl = @mysql_result($result, 0, "apiurl");
	$affiliatefirstname = @mysql_result($result, 0, "firstname");
	$affiliatelastname = @mysql_result($result, 0, "lastname");

	// Check if the affiliate should be upgraded...
	if ($affiliatelevel == "1" && !empty($upgradeaffiliate) && $upgradeaffiliate > 0) {
		$sql="SELECT orderid FROM orderaffiliate WHERE affiliateid='$affiliateid'";
		$result = @mysql_query("$sql",$db);
		$affiliateorders = @mysql_num_rows($result);
		if ($affiliateorders >= $upgradeaffiliate) $result = @mysql_query("UPDATE affiliate SET commissionlevel='2' WHERE affiliateid='$affiliateid'",$db);
	}

	// Notify affiliate by email...
	$message="<html><head><title>Your link to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>Your link to $ashopname generated a sale</p><p>Thank you for your help!</p><p>You can log in to check how much you have earned at: <a href=\"$ashopurl/affiliate/login.php\">$ashopurl/affiliate/login.php</a></p></font></body></html>";
	$headers = "From: ".un_html($ashopname)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

	@ashop_mail("$affiliatemail",un_html($ashopname)." affiliate notification","$message","$headers");

	// Send API notification to affiliate...
	$notifystatus = 1;
	if ($affiliateapiurl && $commission > 0) $notifystatus = ashop_notifyaffiliate($affiliatepassword,$affiliateapiurl,$customerid,$orderid,$commission,$descriptionstring);
	if (!$notifystatus) {
		// Notify affiliate administrator of the problem...
		$message="<html><head><title>Affiliate API notification error!</title></head><body><font face=\"$font\"><p>The affiliate API URL for $affiliateid $affiliatefirstname $affiliatelastname could not be reached to notify him/her of this order: $orderid</p></font></body></html>";
		$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		
		@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate API notification error","$message","$headers");
	}
}

// Give referring affiliates credit if necessary...
$sql = "SELECT * FROM pendingorderaff WHERE orderid='$orderid' AND secondtier!='0' AND secondtier IS NOT NULL";
$result = @mysql_query("$sql",$db);
while ($row = @mysql_fetch_array($result)) {
	$affiliateid = $row["affiliateid"];
	$commission = $row["commission"];
	$secondtier = $row["secondtier"];
	if ($affiliateid && $commission) {
		$sql="DELETE FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='$secondtier'";
		$result = @mysql_query("$sql", $db);

		if ($secondtieractivated) {
			@mysql_query("INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$affiliateid', '$orderid', '0', '$secondtier', '$commission')", $db);

			$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
			$subresult = @mysql_query("$sql", $db);
			$affiliatemail = ashop_mailsafe(@mysql_result($subresult, 0, "email"));
			$affiliatepassword = @mysql_result($subresult, 0, "password");
			$affiliateapiurl = @mysql_result($subresult, 0, "apiurl");
			$affiliatefirstname = @mysql_result($subresult, 0, "firstname");
			$affiliatelastname = @mysql_result($subresult, 0, "lastname");
			
			// Notify affiliate by email...
			$message="<html><head><title>A link from an affiliate you have referred to $ashopname has generated a sale!</title></head><body><font face=\"$font\"><p>A link from an affiliate you have referred to $ashopname generated a sale</p><p>Thank you for your help!</p><p>You can log in to check how much you have earned at: <a href=\"$ashopurl/affiliate/login.php\">$ashopurl/affiliate/login.php</a></p></font></body></html>";
			$headers = "From: ".un_html($ashopname)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			
			@ashop_mail("$affiliatemail",un_html($ashopname)." affiliate notification","$message","$headers");

			// Send API notification to affiliate...
			$notifystatus = 1;
			if ($affiliateapiurl && $commission > 0) $notifystatus = ashop_notifyaffiliate($affiliatepassword,$affiliateapiurl,$customerid,$orderid,$commission,$descriptionstring);
			if (!$notifystatus) {
				// Notify affiliate administrator of the problem...
				$message="<html><head><title>Affiliate API notification error!</title></head><body><font face=\"$font\"><p>The affiliate API URL for $affiliateid $affiliatefirstname $affiliatelastname could not be reached to notify him/her of this order: $orderid</p></font></body></html>";
				$headers = "From: ".un_html($ashopname,1)."<$affiliaterecipient>\nX-Sender: <$affiliaterecipient>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$affiliaterecipient>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

				@ashop_mail("$affiliaterecipient",un_html($ashopname,1)." affiliate API notification error","$message","$headers");
			}
		}
	}
}

// Handle fulfilment options...

// Include all per order fulfilment options...
$orderfulfilmentresult = @mysql_query("SELECT * FROM fulfiloptions WHERE perorder='1'",$db);
while($orderfulfilmentrow = @mysql_fetch_array($orderfulfilmentresult)) {
	$fulfilmentoption = $orderfulfilmentrow["fulfiloptionid"];
	$fulfilmentgroups[$fulfilmentoption][0]["productid"] = $parsed_invoice;
	$fulfilmentgroups[$fulfilmentoption][0]["name"] = $descriptionstring;
	$fulfilmentgroups[$fulfilmentoption][0]["ebayid"] = "";
	$fulfilmentgroups[$fulfilmentoption][0]["quantity"] = 1;
	$fulfilmentgroups[$fulfilmentoption][0]["ffproductid"] = "";
	$fulfilmentgroups[$fulfilmentoption][0]["fflabelnumber"] = "";
	$fulfilmentgroups[$fulfilmentoption][0]["ffpackagenumber"] = "";
	$fulfilmentgroups[$fulfilmentoption][0]["ffparamquerystring"] = "";
}

// Generate a unique password...
if ( ! function_exists('makePassword') ) {
	function makePassword() {
		$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
		srand ((double) microtime() * 1000000);
		$pwLength = "7"; // this sets the limit on how long the password is.
		for($i = 1; $i <=$pwLength; $i++) {
			$newPass .= $alphaNum[(rand(0,31))];
		}
		return ($newPass);
	}
}
if($fulfilmentgroups && !$deliverpending) foreach($fulfilmentgroups as $fulfilmentoptionid => $productsinfo) {
	if($fulfilmentoptionid) {
		$result = @mysql_query("SELECT * FROM fulfiloptions WHERE fulfiloptionid='$fulfilmentoptionid'",$db);
		$row = @mysql_fetch_array($result);
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
		include "fulfilment/$fulfilmentmethod.ff";
	}
}

if($salesreport && ($unlockkeystring || $subscriptionlinks || $downloadable)) {
	$reportfields = explode("|", $salesreport);
	$reporttype = $reportfields[0];
	$startyear = $reportfields[1];
	$startmonth = $reportfields[2];
	$startday = $reportfields[3];
	$toyear = $reportfields[4];
	$tomonth = $reportfields[5];
	$today = $reportfields[6];
	$orderby = $reportfields[7];
	$ascdesc = $reportfields[8];
	$generate = $reportfields[9];
	header("Location: salesreport.php?msg=activated&reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
	exit;
}

if($processmore == "true" && !$startyear) {
	$sql="SELECT paymentinfo.orderid FROM paymentinfo, orders WHERE paymentinfo.orderid=orders.orderid AND orders.customerid='$customerid' ORDER BY paymentinfo.orderid DESC";
	$result = @mysql_query("$sql",$db);
	if (@mysql_num_rows($result)) {
		$neworderid = @mysql_result($result, 0, "orderid");
		header("Location: process.php?orderid=$neworderid&activated=$orderid");
		exit;
	}
}

if ($tocustomer == "true") {
	header("Location: editcustomer.php?customerid=$customerid&activate=true");
	exit;
}

if ($towscustomer == "true") {
	header("Location: edituser.php?customerid=$customerid&activate=true");
	exit;
}

// Close database...
@mysql_close($db);

if ($unlockkeystring || $subscriptionlinks || $downloadable) header("Location: salesadmin.php?activate=true&salesreport=$salesreport");
else header("Location: salesadmin.php?activate=$orderid&salesreport=$salesreport");
?>