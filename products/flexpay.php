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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check through AJAX from the thank you page if an order has been processed...
if (!empty($checkorder) && is_numeric($checkorder)) {
	$result = @mysqli_query($db, "SELECT orderid FROM orders WHERE orderid='$checkorder' AND paid IS NOT NULL and paid != ''");
	if (@mysqli_num_rows($result)) echo "1";
	else echo "0";
	exit;
}

// Check that the required variables are set...
$ashopcurrency = strtoupper($ashopcurrency);
if (!isset($priceAmount) || !isset($priceCurrency) || !isset($referenceID) || !isset($saleID) || !isset($shopID) || !isset($signature) || !is_numeric($shopID) || $priceCurrency != $ashopcurrency) exit;

foreach ($_GET as $key=>$value) $debug .= "$key = $value\n";

// Get payment option information...
$result = @mysqli_query($db, "SELECT payoptionid,secret FROM payoptions WHERE gateway='verotelflexpay' AND merchantid='$shopID'");
if (!@mysqli_num_rows($result)) exit;
$row = @mysqli_fetch_array($result);
$payoption = $row["payoptionid"];
$secret = $row["secret"];

// Verify the signature hash...
$correctsignature = sha1("$secret:priceAmount=$priceAmount:priceCurrency=$priceCurrency:referenceID=$referenceID:saleID=$saleID:shopID=$shopID");
if ($signature != $correctsignature) exit;

// Show thank you page...
if (!empty($ty) && $ty == "1") {

	// Apply selected theme...
	$templatepath = "/templates";
	if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
	if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

	// Get language module...
	$lang = $defaultlanguage;
	include "language/$lang/order.inc.php";

	// Check if a mobile device is being used...
	$device = ashop_mobile();

	if($resultpagessl == "TRUE") ashop_showtemplateheaderssl("$ashoppath$templatepath/thankyou.html",$logourl);
	else ashop_showtemplateheader("$ashoppath$templatepath/thankyou.html");

	// Display "please wait" message...
	echo "<script type=\"text/javascript\" src=\"includes/prototype.js\"></script>
	<script language=\"JavaScript\" type=\"text/javascript\">
	/* <![CDATA[ */
	function updatestatus(ajaxRequest) {
		result = ajaxRequest.responseText;
		if (result == '1') window.location.replace(\"order.php?payopt=$payoption&ofinv=$referenceID\");
	}

	function checkorder() {
		var myAjax = new Ajax.Request(
			'flexpay.php', 
			{
				method: 'get', 
				parameters: 'checkorder=$referenceID&dummy='+ new Date().getTime(), 
				onSuccess: updatestatus
			}
		);
		return false;
	}
	window.setInterval(\"checkorder()\",3000);
	/* ]]> */
	</script><br /><br />
	<table class=\"ashopthankyouframe\">
		<tr align=\"center\"><td><span class=\"ashopthankyouheader\">Thank you for your order!</span></td></tr>
		<tr><td align=\"center\"><br /><span class=\"ashopthankyoutext2\">Please wait a few seconds while we process your payment...</span></td></tr>
	</table>";

	ashop_showtemplatefooter("$ashoppath$templatepath/thankyou.html");
	exit;

}

// Check the status of this order and get order details...
$verotelresult = "";
if (function_exists('curl_version')) {
	$curlversion = curl_version();
	if ((is_array($curlversion) && (strstr($curlversion["ssl_version"], "SSL") || strstr($curlversion["ssl_version"], "NSS"))) || strstr($curlversion, "SSL")) {
		$statussignature = sha1("$secret:saleID=$saleID:shopID=$shopID:version=1");
		$ch = curl_init();
		if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
		curl_setopt($ch, CURLOPT_URL,"https://secure.verotel.com/v3/en/salestatus?shopID=$shopID&version=1&saleID=$saleID&signature=$statussignature");
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$verotelresult=curl_exec ($ch);
		$curlerror = curl_error($ch);
		curl_close ($ch);
	}
}
if (empty($verotelresult)) exit;
$verotelresponse = explode("\n",$verotelresult);
$verotelvars = array();
foreach ($verotelresponse as $responsepart) {
	$responsearray = explode(": ",$responsepart);
	$verotelvars[$responsearray[0]] = $responsearray[1];
}
if ($verotelvars["response"] != "FOUND") exit;

// Process order...
$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$referenceID'");
$row = @mysqli_fetch_array($result);
$affiliate = $row["affiliateid"];
if (!empty($verotelvars["billingAddr_fullName"])) $name = $verotelvars["billingAddr_fullName"];
else $name = $verotelvars["name"];
$names = explode(" ",$name);
$firstname = $names[0];
$lastname = $names[1];
$email = $verotelvars["email"];
$address = $customerrow["billingAddr_addressLine1"];
$city = $customerrow["billingAddr_city"];
$zip = $customerrow["billingAddr_zip"];
$state = $customerrow["billingAddr_state"];
$country = $customerrow["billingAddr_country"];
$products = $payoption."ashoporderstring".$row["products"];
$description = $row["description"];
$securitycheck = md5("$saleID$secret");
$querystring = "email=".$verotelvars["email"]."&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$saleID&responsemsg=".$verotelvars["saleResult"]."&invoice=$referenceID&scode=$securitycheck&amount=$priceAmount&products=$products&description=".$verotelvars["description"]."&affiliate=$affiliate";
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
$header = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
$fp = @fsockopen ("$urldomain", 80);
$response = "";
if ($fp) {
	fputs ($fp, $header . $querystring);
	while (!feof($fp)) $response .= fread ($fp, 8192);
	fclose ($fp);
}
echo "OK";
?>