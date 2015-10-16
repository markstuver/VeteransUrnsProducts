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

include "config.inc.php";
include "ashopfunc.inc.php";
if ($_POST["secret"] && $_POST["orderid"]) {
	$checksecret = md5("{$ashoppath}daopayactivation{$orderid}");
	if ($_POST["secret"] == $checksecret) $userid = "1";
	else exit;
} else {
	include "checklogin.inc.php";
	if ($activate) $orderid = $activate;
}
include "ashopconstants.inc.php";
include "keycodes.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get order info...
if (substr($orderid, 0, 2) == "ws") {
	$orderid = substr($orderid, 2);
	$wholesaleorder = TRUE;
} else $wholesaleorder = FALSE;

$sql="SELECT * FROM orders WHERE orderid='$orderid'";
$result = @mysqli_query($db,"$sql");
$invoiceid = @mysqli_result($result,0,"invoiceid");
$password = @mysqli_result($result,0,"password");
$customerid = @mysqli_result($result, 0, "customerid");
$products = @mysqli_result($result, 0, "products");
$parsed_price = @mysqli_result($result, 0, "price");
$payoption = @mysqli_result($result, 0, "payoptionid");
$transactionid = @mysqli_result($result, 0, "remoteorderid");
$status = @mysqli_result($result, 0, "status");
$sql="SELECT * FROM payoptions WHERE payoptionid='$payoption'";
$result = @mysqli_query($db,"$sql");
$deliverpending = @mysqli_result($result,0,"deliverpending");
$gateway = @mysqli_result($result, 0, "gateway");
if (($gateway == "authnetaimdelayed" || $gateway == "authnetsimdelayed") && $transactionid) {
	$authnetpassword = @mysqli_result($result, 0, "transactionkey");
	$user = @mysqli_result($result, 0, "merchantid");
	$testmode = @mysqli_result($result, 0, "testmode");
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
	} else @mysqli_query($db,"DELETE FROM paymentinfo WHERE orderid='$orderid'");
} else if ($gateway == "googleco") {
	if ($status != "GOOGLEAUTH") {
		header("Location: salesreport.php");
		exit;
	}
	$gcokey = @mysqli_result($result, 0, "secret");
	$gcoid = @mysqli_result($result, 0, "merchantid");
	$googlecorequest = "_type=charge-and-ship-order&google-order-number=$transactionid";
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if (strstr(curl_version(), "SSL") || (is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS")))) {
			$authkey = base64_encode("$gcoid:$gcokey");
			$googlecoheaders[] = "Authorization: Basic $authkey";
			$googlecoheaders[] = "Content-Type: application/xml;charset=UTF-8";
			$googlecoheaders[] = "Accept: application/xml;charset=UTF-8";
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt ($ch, CURLOPT_URL,"https://sandbox.google.com/checkout/api/checkout/v2/requestForm/Merchant/$gcoid");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $googlecoheaders);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $googlecorequest);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$htmlresult=curl_exec ($ch);
			$curlerror = curl_error($ch);
			curl_close ($ch);
		}
	} else {
		exec('curl -k -u '.$gcoid.':'.$gcokey.' -d "' . $googlecorequest . '" https://sandbox.google.com/checkout/api/checkout/v2/requestForm/Merchant/$gcoid', $resultarray);
		$htmlresult = $resultarray[0];
	}
	if (empty($htmlresult)) {
		header("Location: salesadmin.php?msg=googlechargeerror");
		exit;
	}
	$googlesuccess = FALSE;
	$responsearray = explode("&",$htmlresult);
	foreach ($responsearray as $responserow) if ($responserow == "charge-amount-notification.order-summary.financial-order-state=CHARGED") $googlesuccess = TRUE;
	if (!$googlesuccess) {
		header("Location: salesadmin.php?msg=googlechargeerror");
		exit;
	}
} else if ($gateway == "klarna") {
	require_once "$ashoppath/payment/Klarna/Klarna.php";
	require_once "$ashoppath/payment/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc";
	require_once "$ashoppath/payment/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc";

	$merchantid = @mysqli_result($result, 0, "merchantid");
	$securitysecret = @mysqli_result($result, 0, "secret");
	$testmode = @mysqli_result($result, 0, "testmode");

	$k = new Klarna();

	if ($testmode == "1") $k->config(
		$merchantid,		
		"$securitysecret",  
		KlarnaCountry::SE,	// Purchase country
		KlarnaLanguage::SV,	// Purchase language
		KlarnaCurrency::SEK,// Purchase currency
		Klarna::BETA,		// Server
		'json',				
		'./pclasses.json'     
	);
	else $k->config(
		$merchantid,		
		"$securitysecret",  
		KlarnaCountry::SE,	// Purchase country
		KlarnaLanguage::SV,	// Purchase language
		KlarnaCurrency::SEK,// Purchase currency
		Klarna::LIVE,		// Server
		'json',				
		'./pclasses.json'     
	);

	try {
		$result = $k->activate($transactionid, null);

		// For optional arguments, flags, partial activations and so on, refer to the documentation.
		// See Klarna::setActivateInfo

		$risk = $result[0];  // "ok" or "no_risk"
		$invNo = $result[1]; // "9876451"

		@mysqli_query($db,"UPDATE orders SET remoteorderid='$invNo' WHERE orderid='$orderid'");

	} catch(Exception $e) {
		$klarnaerrormessage = urlencode($e->getMessage()." (#".$e->getCode().")");
		header("Location: salesadmin.php?klarnaerror=$klarnaerrormessage");
		exit;
	}
}

// Mark the order as paid...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
$dateshort = date("Y-m-d", time()+$timezoneoffset);
$sql="UPDATE orders SET paid='$date' WHERE orderid='$orderid'";
$result = @mysqli_query($db,"$sql");
$sql="UPDATE memberorders SET paid='$date' WHERE orderid='$orderid'";
$result = @mysqli_query($db,"$sql");

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
	$result = @mysqli_query($db,"SELECT * FROM productfiles WHERE productid='$thisproductid'");
	$files = @mysqli_num_rows($result);
	$sql="SELECT * FROM product WHERE productid=$thisproductid";
	$result = @mysqli_query($db,"$sql");
	$subscriptiondir = @mysqli_result($result,0,"subscriptiondir");
	$subscriptionurl = @mysqli_result($result,0,"protectedurl");
	$producttype = @mysqli_result($result,0,"prodtype");
	if ($files && $password) $downloadable = 1;
	if ($subscriptiondir && $password) {
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
	if ($fulfilmentoption && !$thisproduct["disablefulfilment"]) {
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["productid"] = $thisproduct["productid"];
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["name"] = $thisproductname.$thisproduct["parameters"];
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["ebayid"] = $thisproductname.$thisproduct["ebayid"];
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["quantity"] = $thisquantity;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["price"] = $thisprice;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffproductid"] = $ffproductid;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["fflabelnumber"] = $fflabelnumber;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffpackagenumber"] = $ffpackagenumber;
		$fulfilmentgroups[$fulfilmentoption][$productnumber]["ffparamquerystring"] = $ffparamquerystring;
	}
	$descriptionstring .= $thisquantity.": ".$thisproductname.$thisproduct["parameters"];
}

// Get customer info...
$sql="SELECT * FROM customer WHERE customerid='$customerid'";
$result = @mysqli_query($db,"$sql");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = ashop_mailsafe(@mysqli_result($result, 0, "email"));
// Make sure plus signs are kept in email addresses...
$email = str_replace(" ","+",$email);
$lang = @mysqli_result($result, 0, "preflanguage");
$parsed_address = @mysqli_result($result, 0, "address");
$parsed_zip = @mysqli_result($result, 0, "zip");
$parsed_city = @mysqli_result($result, 0, "city");
$parsed_state = @mysqli_result($result, 0, "state");
$parsed_country = @mysqli_result($result, 0, "country");
$parsed_phone = @mysqli_result($result, 0, "phone");
if (!$wholesaleorder) {
	$sql="SELECT * FROM shipping WHERE customerid=$customerid";
	$result = @mysqli_query($db,"$sql");
	$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
	$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
	$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
	$shippingcity = @mysqli_result($result, 0, "shippingcity");
	$shippingstate = @mysqli_result($result, 0, "shippingstate");
	$shippingzip = @mysqli_result($result, 0, "shippingzip");
	$shippingcountry = @mysqli_result($result, 0, "shippingcountry");
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
		$result = @mysqli_query($db,"SELECT * FROM shipoptions WHERE shipoptionid='$value'");
		if ($selectedshipoptions) $selectedshipoptions .= ", ";
		$selectedshipoptions .= @mysqli_result($result, 0, "description");
	}
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

// Get unlock keys included in the order...
$sql="SELECT keytext, productid FROM unlockkeys WHERE orderid='";
if ($wholesaleorder) $sql .= "ws";
$sql .= "$orderid'";
$result = @mysqli_query($db,"$sql");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$keytext = @mysqli_result($result,$i,"keytext");
	if (!empty($keycodeencryptionkey) && !empty($keytext)) {
		$keytext = trim($keytext);
		$keytext = ashop_decrypt($keytext, $keycodeencryptionkey);
	}
	$productid = @mysqli_result($result,$i,"productid");
	$sql = "SELECT name FROM product WHERE productid=$productid";
	$result2 = @mysqli_query($db,"$sql");
	$thisproductname = @mysqli_result($result2,0,"name");
	$unlockkeystring .= "$thisproductname:<br><b>$keytext</b><br>\n";
	if (is_array($fulfilmentgroups)) foreach ($fulfilmentgroups as $fulfilmentoption=>$productinfo) {
		if (is_array($productinfo)) foreach ($productinfo as $productnumber=>$productinfo) {
			if ($fulfilmentgroups[$fulfilmentoption][$productnumber]["productid"] == $thisproductid) $fulfilmentgroups[$fulfilmentoption][$productnumber]["keys"][$i] = $keytext;
		}
	}
}

// Handle fulfilment options...

// Include all per order fulfilment options...
$orderfulfilmentresult = @mysqli_query($db,"SELECT * FROM fulfiloptions WHERE perorder='1'");
while($orderfulfilmentrow = @mysqli_fetch_array($orderfulfilmentresult)) {
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
		$result = @mysqli_query($db,"SELECT * FROM fulfiloptions WHERE fulfiloptionid='$fulfilmentoptionid'");
		$row = @mysqli_fetch_array($result);
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
	$receipt = str_replace("%invoice%",$invoiceid,$receipt);
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
	
	if ($subscriptionlinks && $authorized[0] != "PENDING") {
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
$result = @mysqli_query($db,"$sql");
$affiliateid = @mysqli_result($result,0,"affiliateid");
$commission = @mysqli_result($result,0,"commission");
$affiliatelevel = @mysqli_result($result,0,"commissionlevel");
if ($affiliateid && $commission) {
	$sql="INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$affiliateid', '$orderid', '0', '0', '$commission')";
	$result = @mysqli_query($db,"$sql");

	$sql="DELETE FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='0'";
	$result = @mysqli_query($db,"$sql");
	
	$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
	$result = @mysqli_query($db,"$sql");
	$affiliatemail = ashop_mailsafe(@mysqli_result($result, 0, "email"));
	$affiliatepassword = @mysqli_result($result, 0, "password");
	$affiliateapiurl = @mysqli_result($result, 0, "apiurl");
	$affiliatefirstname = @mysqli_result($result, 0, "firstname");
	$affiliatelastname = @mysqli_result($result, 0, "lastname");

	// Check if the affiliate should be upgraded...
	if ($affiliatelevel == "1" && !empty($upgradeaffiliate) && $upgradeaffiliate > 0) {
		$sql="SELECT orderid FROM orderaffiliate WHERE affiliateid='$affiliateid'";
		$result = @mysqli_query($db,"$sql");
		$affiliateorders = @mysqli_num_rows($result);
		if ($affiliateorders >= $upgradeaffiliate) $result = @mysqli_query($db,"UPDATE affiliate SET commissionlevel='2' WHERE affiliateid='$affiliateid'");
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
$result = @mysqli_query($db,"$sql");
while ($row = @mysqli_fetch_array($result)) {
	$affiliateid = $row["affiliateid"];
	$commission = $row["commission"];
	$secondtier = $row["secondtier"];
	if ($affiliateid && $commission) {
		@mysqli_query($db,"DELETE FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='$secondtier'");

		if ($secondtieractivated) {
			@mysqli_query($db,"INSERT INTO orderaffiliate (affiliateid, orderid, paid, secondtier, commission) VALUES ('$affiliateid', '$orderid', '0', '$secondtier', '$commission')");

			$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
			$subresult = @mysqli_query($db,"$sql");
			$affiliatemail = ashop_mailsafe(@mysqli_result($subresult, 0, "email"));
			$affiliatepassword = @mysqli_result($subresult, 0, "password");
			$affiliateapiurl = @mysqli_result($subresult, 0, "apiurl");
			$affiliatefirstname = @mysqli_result($subresult, 0, "firstname");
			$affiliatelastname = @mysqli_result($subresult, 0, "lastname");
			
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

// No redirection if we are activating a Google Checkout order...
if (empty($gcactivate) || $gcactivate != "true") {

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
		$result = @mysqli_query($db,"$sql");
		if (@mysqli_num_rows($result)) {
			$neworderid = @mysqli_result($result, 0, "orderid");
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

	if ($unlockkeystring || $subscriptionlinks || $downloadable) header("Location: salesadmin.php?activate=true&salesreport=$salesreport");
	else header("Location: salesadmin.php?activate=$orderid&salesreport=$salesreport");
}

// Close database...
@mysqli_close($db);
?>