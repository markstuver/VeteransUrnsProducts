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
include "ashopconstants.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

// Get payment option settings...
$result = @mysqli_query($db, "SELECT * FROM payoptions WHERE gateway='psbill'");
$row = @mysqli_fetch_array($result);
$transactionkey = $row["transactionkey"];
$merchantid = $row["merchantid"];
$payoption = $row["payoptionid"];

if(strcmp($_SERVER['REQUEST_METHOD'],"GET")==0){
    $action = $_GET['act'];
    $data = $_GET['data'];
} else {
    $action = $_POST['act'];
    $data = $_POST['data'];
}

if (get_magic_quotes_gpc()) {
    $data = stripslashes($data);
}

class rc4crypt {
    function encrypt ($pwd, $data_e){
        $key[] = '';
        $box[] = '';
        $cipher ='';
        $pwd_length = strlen($pwd);
        $data_length = strlen($data_e);
        for ($i = 0; $i < 256; $i++){
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++){
            $j = ($j + $box[$i] + ord($pwd[$i % $pwd_length])) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++){
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data_e[$i]) ^ $k );
        }
        return $cipher;
    }
    function decrypt ($pwd, $data_e){
        return $this->encrypt($pwd, $data_e);
    }
}

function parseaction($key, $data) {
	$rc4 = new rc4crypt();
	$data_e = $rc4->decrypt($key, $data);
	$data = $data_e;
	$data = preg_replace( "/\/0/", '', $data);
	return $data;
}

if ($action) {
	echo "Ok001";
	$parseddata = parseaction($transactionkey, $data);
	$dataarray = explode("&", $parseddata);
	foreach ($dataarray as $part) {
		$partarray = explode("=", $part);
		eval("\$$partarray[0] = $partarray[1];");
	}
	$remoteorderid = $orderId;
	$securitycheck = md5("$remoteorderid$transactionkey");
	$responsemsg = "Success";
	$result = @mysqli_query($db, "SELECT * FROM pendingorders WHERE orderid='$var'");
	$row = @mysqli_fetch_array($result);
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
	@mysqli_query($db, "DELETE FROM pendingorders WHERE orderid='$orderid'");
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
	@mysqli_close($db);
	exit;
} else {
	// Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

	// Store preliminary customer info...
	@mysqli_query($db, "INSERT INTO pendingorders (orderid, products, date, amount, description, firstname, lastname, email, address, zip, city, state, country, phone, affiliateid) VALUES ('$invoice', '$products', '$date', '$amount', '$description', '$firstname', '$lastname', '$email', '$address', '$zip', '$city', '$state', '$country', '$phone', '$affiliate')");
	@mysqli_close($db);

	// Convert country to 2-digit code...
	if ($country) {
		reset($countries);
		while ($thiscountry = current($countries)) {
			if ($thiscountry == $country) {
				$pscountry = key($countries);
				break;
			}
			next($countries);
		}
	}

	if ($state == "other") $state = "NA";

	// Include language file...
	if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
	include "../language/$lang/checkout.inc.php";

	// Make sure the page isn't stored in the browsers cache...
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	echo "<html><head><title>".REDIRECTSERV."</title>\n".CHARSET."</head><body onload=\"document.forms[0].submit()\">
	<form method=\"POST\" action=\"https://www.psbill.biz/purchase.php4\">
	<input type=\"hidden\" name=\"var\" value=\"$invoice\">
	<input type=\"hidden\" name=\"price\" value=\"".number_format($amount,2,'.','')."\">
	<input type=\"hidden\" name=\"description\" value=\"$description\">
	<input type=\"hidden\" name=\"descriptionlong\" value=\"$description\">
	<input type=\"hidden\" name=\"sign\" value=\"".md5("$merchantid ".number_format($amount,2,'.','')." $transactionkey")."\">
	<input type=\"hidden\" name=\"s\" value=\"$merchantid\">
	<input type=\"hidden\" name=\"return_url\" value=\"$ashopurl/order.php?payopt=$payoption\">
	<input type=\"hidden\" name=\"cancel_url\" value=\"$ashopurl\">
	<input type=\"hidden\" name=\"firstname\" value=\"$firstname\">
	<input type=\"hidden\" name=\"lastname\" value=\"$lastname\">
	<input type=\"hidden\" name=\"address\" value=\"$address\">
	<input type=\"hidden\" name=\"city\" value=\"$city\">
	<input type=\"hidden\" name=\"zip\" value=\"$zip\">
	<input type=\"hidden\" name=\"state\" value=\"$state\">
	<input type=\"hidden\" name=\"email\" value=\"$email\">
	<input type=\"hidden\" name=\"phone\" value=\"$phone\">
	<input type=\"hidden\" name=\"country\" value=\"$pscountry\">
	</form></body></html>";
	exit;
}
?>