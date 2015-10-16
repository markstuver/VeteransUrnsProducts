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

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid=$payoption";
else {
	if ($ashopcurrency != "nzd") $sql = "SELECT * FROM payoptions WHERE gateway='ewayuk'";
	else $sql = "SELECT * FROM payoptions WHERE gateway='ewaynz'";
}
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "payoptionid");
$gateway = @mysqli_result($result, 0, "gateway");
if ($ashopcurrency == "usd") include "gateways/$gateway.gw";
else include "gateways$ashopcurrency/$gateway.gw";
$ewaycurrency = strtoupper($ashopcurrency);
if ($ashopcurrency != "nzd") $ewaysubdomain = "payment";
else $ewaysubdomain = "nz";
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$username = @mysqli_result($result, 0, "transactionkey");
$testmode = @mysqli_result($result, 0, "testmode");
$amount = number_format($amount,2,'.','');
if ($testmode) {
	$merchantid = "87654321";
	$username = "TestAccount";
}

if (isset($AccessPaymentCode)) {
	$ewayquerystring = "CustomerID=$merchantid&UserName=$username&AccessPaymentCode=$AccessPaymentCode";
	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt($ch, CURLOPT_URL,"https://$ewaysubdomain.ewaygateway.com/Result/?$ewayquerystring");
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$xmlresult = curl_exec($ch);
	$curlerror = curl_error($ch);
	curl_close ($ch);
	if (strpos($xmlresult,"<ResponseCode>") && strpos($xmlresult,"</ResponseCode>")) {
		$responsecode = substr($xmlresult,strpos($xmlresult,"<ResponseCode>")+14);
		$responsecode = substr($responsecode,0,strpos($responsecode,"</ResponseCode>"));
	}
	if (strpos($xmlresult,"<ReturnAmount>") && strpos($xmlresult,"</ReturnAmount>")) {
		$returnamount = substr($xmlresult,strpos($xmlresult,"<ReturnAmount>")+14);
		$returnamount = substr($returnamount,0,strpos($returnamount,"</ReturnAmount>"));
	}
	if (strpos($xmlresult,"<TrxnNumber>") && strpos($xmlresult,"</TrxnNumber>")) {
		$trxnnumber = substr($xmlresult,strpos($xmlresult,"<TrxnNumber>")+12);
		$trxnnumber = substr($trxnnumber,0,strpos($trxnnumber,"</TrxnNumber>"));
	}
	if (strpos($xmlresult,"<MerchantReference>") && strpos($xmlresult,"</MerchantReference>")) {
		$merchantreference = substr($xmlresult,strpos($xmlresult,"<MerchantReference>")+19);
		$merchantreference = substr($merchantreference,0,strpos($merchantreference,"</MerchantReference>"));
	}
	if (strpos($xmlresult,"<ErrorMessage>") && strpos($xmlresult,"</ErrorMessage>")) {
		$errormessage = substr($xmlresult,strpos($xmlresult,"<ErrorMessage>")+14);
		$errormessage = substr($errormessage,0,strpos($errormessage,"</ErrorMessage>"));
	}
	if (strpos($xmlresult,"<TrxnStatus>") && strpos($xmlresult,"</TrxnStatus>")) {
		$trxnstatus = substr($xmlresult,strpos($xmlresult,"<TrxnStatus>")+12);
		$trxnstatus = substr($trxnstatus,0,strpos($trxnstatus,"</TrxnStatus>"));
	}

	$sql = "SELECT * FROM pendingorders WHERE orderid='$merchantreference'";
	$result = @mysqli_query($db, "$sql");
	$row = @mysqli_fetch_array($result);
	$email = $row["email"];
	$firstname = $row["firstname"];
	$lastname = $row["lastname"];
	$address = $row["address"];
	$city = $row["city"];
	$zip = $row["zip"];
	$state = $row["state"];
	$country = $row["country"];
	$phone = $row["phone"];
	$amount = number_format($row["amount"],2,'.','');
	$products = $row["products"];
	$description = $row["description"];
	$affiliate = $row["affiliate"];
	$remoteorderid=$trxnnumber;
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");
	@mysqli_close($db);
	if (!empty($responsecode) && ($responsecode == "00" || $responsecode == "08" || $responsecode == "11" || $responsecode == "16") && $trxnstatus == "true" && $returnamount == $amount) {
		$responsemsg = "Success";
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$merchantreference&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
		if (strpos($ashopurl, "/", 8)) {
			$urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
			$urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
		} else {
			$urlpath = "/";
			$urldomain = $ashopurl;
		}
		if ($urlpath == "/") $scriptpath = "order.php";
		else $scriptpath = "/order.php";
		$urldomain = str_replace("http://", "", $urldomain);
		$header .= "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
		$fp = @fsockopen ("$urldomain", 80, $errno, $errstr, 10);
		$response = @fwrite ($fp, $header . $querystring);
		@fclose ($fp);
	}
	header("Location: ../order.php?payopt=$payoption&ofinv=$merchantreference");

} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	// Initiate eWay transaction...
	if (function_exists('curl_version')) {
		$curlversion = curl_version();
		if (strstr($curlversion, "SSL") || (is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS")))) {
			$ewayquerystring = "CustomerID=$merchantid&UserName=$username&Amount=$amount&Currency=$ewaycurrency&PageTitle=".urlencode("$ashopname")."&Language=$lang&CompanyName=".urlencode("$ashopname")."&CustomerFirstName=".urlencode("$firstname")."&CustomerLastName=".urlencode("$lastname")."&CustomerAddress=".urlencode("$address")."&CustomerCity=".urlencode("$city")."&CustomerState=".urlencode("$state")."&CustomerPostCode=".urlencode("$zip")."&CustomerCountry=".urlencode("$country")."&CustomerEmail=$email&CustomerPhone=".urlencode("$phone")."&InvoiceDescription=".urlencode("$description")."&CancelURL=$ashopurl&ReturnUrl=$ashopsurl/admin/eway.php&MerchantReference=$invoice&MerchantInvoice=$invoice&MerchantOption1=$affiliate&ModifiableCustomerDetails=false";
			$ch = curl_init();
			if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
			curl_setopt($ch, CURLOPT_URL,"https://$ewaysubdomain.ewaygateway.com/Request/?$ewayquerystring");
			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$xmlresult = curl_exec($ch);
			$curlerror = curl_error($ch);
			curl_close ($ch);
			if (strpos($xmlresult,"<URI>") && strpos($xmlresult,"</URI>")) {
				$redirecturl = substr($xmlresult,strpos($xmlresult,"<URI>")+5);
				$redirecturl = substr($redirecturl,0,strpos($redirecturl,"</URI>"));
			} else {
				if ($curlerror) echo "Error! Could not connect to payment server! Description: $curlerror";
				else echo "ERROR! The eWay server said: $xmlresult";
				exit;
			}
		}
	}

	if ($redirecturl) header ("Location: $redirecturl");
}
?>