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
$sql = "SELECT * FROM payoptions WHERE gateway='payson'";
$result = @mysqli_query($db, "$sql");
$gateway = "payson";
include "gatewayssek/payson.gw";
$payoption = @mysqli_result($result, 0, "payoptionid");
$merchantid = @mysqli_result($result, 0, "merchantid");
$payoptionsecret = @mysqli_result($result, 0, "secret");
$transactionkey = @mysqli_result($result, 0, "transactionkey");
$testmode = @mysqli_result($result, 0, "testmode");

if (isset($_GET["Paysonref"]) && isset($_GET["RefNr"]) && isset($_GET["MD5"])) {
	$sql = "SELECT * FROM pendingorders WHERE orderid='$RefNr'";
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
	$amount = $row["amount"];
	$products = $row["products"];
	$description = $row["description"];
	$affiliate = $row["affiliate"];
	$remoteorderid=$Paysonref;
	$securitycheck = md5("$remoteorderid$payoptionsecret");
	$sql = "DELETE FROM pendingorders WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");

	if ($MD5) {
		$newchecksum = md5($OkURL.$Paysonref.$transactionkey);
		if ($newchecksum == $MD5) {
			$responsemsg = "Success";
			$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$RefNr&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
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
			$fp = @fsockopen ("$urldomain", 80, $errno, $errstr, 10);
			$response = @fwrite ($fp, $header . $querystring);
			@fclose ($fp);
			@mysqli_close($db);
		}
	}
	header ("Location: $postbackurl?payopt=$payoption&ofinv=$RefNr");
	exit;
} else if ($_GET["RefNr"]) {
	header ("Location: $postbackurl?payopt=$payoption&ofinv=$RefNr");
	exit;	
} else if ($invoice) {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	if ($address2) $address .= ", $address2";

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	$amount = number_format($amount,2,',','');
	if ($testmode) $paysonurl = "https://www.payson.se/testagent/default.aspx";
	else $paysonurl = "https://www.payson.se/merchant/default.aspx";

	$hashstring = "$merchantid:$amount:0:$ashopurl/admin/payson.php:0$transactionkey";
	$md5hash = md5($hashstring);

	echo "<html>
	<head>
	<title>&Ouml;ppnar Payson f&ouml;r betalning...</title>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />
	<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />
	<meta http-equiv=\"Content-Style-Type\" content=\"text/css\" />
	<script type=\"text/javascript\">
	<!--
	window.onload = function (evt) { document.forms[0].submit(); }
	//-->
	</script>
	</head>
	<body>
	<form name=\"payform\" method=\"post\" action=\"$paysonurl\">
	<input type=\"hidden\" name=\"AgentId\" value=\"$payoptionsecret\" />
	<input type=\"hidden\" name=\"SellerEmail\" value=\"$merchantid\" />
	<input type=\"hidden\" name=\"BuyerEmail\" value=\"$email\" />
	<input type=\"hidden\" name=\"RefNr\" value=\"$invoice\" />
	<input type=\"hidden\" name=\"Description\" value=\"$description\" />
	<input type=\"hidden\" name=\"Cost\" value=\"$amount\" />
	<input type=\"hidden\" name=\"MD5\" value=\"$md5hash\" />
	<input type=\"hidden\" name=\"OkUrl\" value=\"$ashopurl/admin/payson.php\" />
	<input type=\"hidden\" name=\"GuaranteeOffered\" value=\"0\" />
	<input type=\"hidden\" name=\"ExtraCost\" value=\"0\" />
	<input type=\"hidden\" name=\"PurchaseUrl\" value=\"$ashopurl\" />
	<input type=\"hidden\" name=\"BuyerFirstName\" value=\"$firstname\" />
	<input type=\"hidden\" name=\"BuyerLastName\" value=\"$lastname\" />
	</form>
	</body>
	</html>";
}
?>