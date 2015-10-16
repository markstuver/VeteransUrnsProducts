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
include "gateways/daopay.gw";
if (!in_array($_SERVER["REMOTE_ADDR"], $gatewayip)) exit;
if (!$_GET["tid"] || $_GET["stat"] != "ok") exit;

$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get payment option information...
$result = @mysqli_query($db, "SELECT * FROM payoptions WHERE gateway='daopay'");
$appcode = @mysqli_result($result,0,"merchantid");
if ($appcode != substr($_GET["tid"],0,strlen($appcode))) exit;
else $orderid = substr($_GET["tid"],strlen($appcode));

// Get order details...
$result = @mysqli_query($db, "SELECT * FROM orders WHERE remoteorderid='$remoteorderid'");
if (!@mysqli_num_rows($result)) exit;
else {
	$price = number_format(@mysqli_result($result,0,"price"),2,'.','');
	if ($_GET["paid"] == $price) {
		// Activate order...
		$secret = md5("{$ashoppath}daopayactivation{$orderid}");
		$querystring = "orderid=$orderid&secret=$secret";
		if (strpos($ashopurl, "/", 8)) {
			$urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
			$urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
		} else {
			$urlpath = "/";
			$urldomain = $ashopurl;
		}
		if ($urlpath == "/") $scriptpath = "admin/activate.php";
		else $scriptpath = "/admin/activate.php";
		$urldomain = str_replace("http://", "", $urldomain);
		$postheader = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
		$fp = @fsockopen ("$urldomain", 80);
		unset($response);
		if ($fp) {
			fputs ($fp, $postheader.$querystring);
			while (!feof($fp)) $response .= fgets ($fp, 1024);
			fclose ($fp);
		}
		echo "ORDER COMPLETED";
	}
}
@mysqli_close($db);
?>