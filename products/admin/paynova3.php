<?php
// AShop
// Copyright 2011 - AShop Software - http://www.ashopsoftware.com
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
include "ashopconstants.inc.php";

// Connect to database...
$db = @mysql_connect("$databaseserver", "$databaseuser", "$databasepasswd");
@mysql_select_db("$databasename",$db);

if (!$lang) $lang = $defaultlanguage;

// Get payment option information...
$splitorderstring = explode("ashoporderstring", $products);
$payoption = $splitorderstring[0];
if ($payoption) $sql = "SELECT * FROM payoptions WHERE payoptionid=$payoption";
else $sql = "SELECT * FROM payoptions WHERE gateway='paynova3'";
$result = @mysql_query("$sql",$db);
$gateway = @mysql_result($result, 0, "gateway");
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
include "gateways$pathprefix/$gateway.gw";
$merchantid = @mysql_result($result, 0, "merchantid");
$payoptionsecret = @mysql_result($result, 0, "secret");
$testmode = @mysql_result($result, 0, "testmode");

if (isset($paynova_transid) && isset($merchant_orderid)) {
	$sql = "SELECT * FROM pendingorders WHERE orderid='$merchant_orderid'";
	$result = @mysql_query("$sql",$db);
	$row = @mysql_fetch_array($result);
	$orderid = $row["orderid"];
	$email = $row["email"];
	$firstname = $row["firstname"];
	$lastname = $row["lastname"];
	$address = $row["address"];
	$city = $row["city"];
	$zip = $row["zip"];
	$state = $row["state"];
	$country = $row["country"];
	$phone = $row["phone"];
	$amount = $row["amount"];
	$products = $row["products"];
	$description = $row["description"];
	$affiliate = $row["affiliate"];
	$remoteorderid=$paynova_transid;
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysql_query("$sql",$db);

	$paynovachecksum=$checksum;
	$checksum = md5($paynova_status.$paynova_statusmessage.$merchant_orderid.$paynova_transid.$payoptionsecret);
	if ($checksum == $paynovachecksum) {
		if ($paynova_status=="1") {
			$newchecksum = md5("1OK".$merchant_orderid.$payoptionsecret);
			$response = "<?xml version=\"1.0\" encoding=\"utf-8\"?><responsemessage><status>1</status><statusmessage>OK</statusmessage><neworderid>$merchant_orderid</neworderid><batchid></batchid><checksum>$newchecksum</checksum></responsemessage>";
			echo $response;
			$responsemsg = "Success";
			$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$orderid&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
			@mysql_close($db);
		} else {
			$newchecksum = md5("-1Avbryt".$merchant_orderid.$payoptionsecret);
			$response = "<?xml version=\"1.0\" encoding=\"utf-8\"?><responsemessage><status>-1</status><statusmessage>Avbryt</statusmessage><neworderid>$merchant_orderid</neworderid><batchid></batchid><checksum>$newchecksum</checksum></responsemessage>";
			echo $response;
		}
	} else {
		$newchecksum = md5("-1Avbryt".$merchant_orderid.$payoptionsecret);
		$response = "<?xml version=\"1.0\" encoding=\"utf-8\"?><responsemessage><status>-1</status><statusmessage>Avbryt</statusmessage><neworderid>$merchant_orderid</neworderid><batchid></batchid><checksum>$newchecksum</checksum></responsemessage>";
		echo $response;
	}
	exit;
} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	// Store preliminary customer info...
	@mysql_query("INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysql_close($db);

	$amount = number_format($amount,2,".","");
	$amount = str_replace(".","",$amount);

	// Close database...
	@mysql_close($db);

	if ($lang == "en") $pnlang = "ENG";
	if ($lang == "sv") $pnlang = "SWE";
	if (!$lang) $pnlang = "ENG";

	foreach ($countries as $shortcountry => $longcountry) if ($country == $longcountry) $pncountry = $shortcountry;

	$sessionkey = getpaynovasessionstring($merchantid, $email, $firstname, $lastname, $address, $zip, $city, $state, $amount, $invoice, $description, $payoptionsecret, $ashopurl, $products, $payoption, $testmode, $pncountry, $pnlang);

	if ($sessionkey && !strstr($sessionkey,"ERROR")) {
		echo "<html><head><title>PayNova Betalning</title></head><body><center><br><br><br><br>
		<IFRAME NAME=\"iframe\" width=\"400\" height=\"400\" SRC=\"";
		if($testmode) echo "https://testpaygate.paynova.com/paynova3/";
		else echo "https://paygate.paynova.com/paynova3/";
		echo "?sessionkey=$sessionkey\" scrolling=\"No\" marginwidth=\"0\" marginheight=\"0\" frameborder=\"No\"></IFRAME></center></body></html>";
	}
	else echo "An error occurred. Please contact the webshop. Error message: ".str_replace("ERROR: ","",$sessionkey);
} else {
	echo "<html><head><title>Payment Canceled</title></head>
         <body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" vlink=\"$linkcolor\" alink=\"$linkcolor\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	     <tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
 		 <tr align=\"center\"><td> <img src=\"../images/logo.gif\"><br><hr size=\"0\" noshade>
		 </td></tr></table><p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>Payment Canceled</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$StatusNotify<br><br><a href=\"$ashopurl/checkout.php\">Try again!</a></span></font></p></td></tr></table></body></html>";
	exit;
}	
?>