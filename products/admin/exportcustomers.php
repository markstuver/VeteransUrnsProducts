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

error_reporting(E_ALL ^ E_NOTICE);
@set_time_limit(0);
include "config.inc.php";
include "ashopfunc.inc.php";
$noinactivitycheck = "true";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";
include "ashopconstants.inc.php";

if (!$dmshowcustomers && $userid != "1") exit;

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Convert double quote enclosure for CSV...
if ($defaultenclosure == "&quot;") $defaultenclosure = "\"";

// Get customer information from database...
if ($userid > 1) {
	$sql = "SELECT DISTINCT customer.customerid, customer.* FROM customer, orders WHERE customer.firstname != '' AND customer.email != '' AND customer.allowemail='1' AND orders.customerid=customer.customerid AND orders.userid LIKE '%|$userid|%'";
	if ($namefilter || $emailfilter) $sql .= " AND ";
	if ($namefilter) {
		$sql .= "(firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
		if ($emailfilter) $sql .= " AND email LIKE '%$emailfilter%'";
	} else if ($emailfilter) $sql .= " email LIKE '%$emailfilter%'";
	if ($ws == "true") $sql .= " AND level>'0' AND level IS NOT NULL";
	$sql .= " ORDER BY customerid";
} else {
	if ($recurring) $sql = "SELECT DISTINCT customer.customerid, customer.* FROM customer, orders, emerchant_bills WHERE customer.firstname != '' AND customer.email != '' AND orders.customerid=customer.customerid AND orders.orderid=emerchant_bills.orderid AND emerchant_bills.recurring != ''";
	else $sql = "SELECT * FROM customer WHERE customer.firstname != '' AND customer.email != '' AND customer.password != '' AND customer.password IS NOT NULL";
	if ($namefilter || $emailfilter) $sql .= " AND ";
	if ($namefilter) {
		$sql .= "(firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
		if ($emailfilter) $sql .= " AND email LIKE '%$emailfilter%'";
	} else if ($emailfilter) $sql .= " email LIKE '%$emailfilter%'";
	if ($ws == "true") $sql .= " AND level>'0' AND level IS NOT NULL";
	$sql .= " ORDER BY customerid ASC";
}
$result = @mysqli_query($db, $sql);
if (@mysqli_num_rows($result)) {
	header ("Content-Type: application/octet-stream");
	if ($ws == "true") header ("Content-Disposition: attachment; filename=wholesalecustomers.csv");
	else header ("Content-Disposition: attachment; filename=customers.csv");
	echo CUSTOMERID."{$defaultdelimiter}".FIRSTNAME."{$defaultdelimiter}".LASTNAME."{$defaultdelimiter}".EMAIL."{$defaultdelimiter}".ADDRESS."{$defaultdelimiter}".CITY."{$defaultdelimiter}".STATEPROVINCE."{$defaultdelimiter}".ZIP."{$defaultdelimiter}".COUNTRY."{$defaultdelimiter}".PHONE."{$defaultdelimiter}".SHIPPINGADDRESS."{$defaultdelimiter}".SHIPPINGCITY."{$defaultdelimiter}".SHIPPINGSTATE."{$defaultdelimiter}".SHIPPINGZIP."{$defaultdelimiter}".SHIPPINGCOUNTRY."\r\n";
	while ($row = @mysqli_fetch_array($result)) {
		$firstname = $row["firstname"];
		if ($defaultenclosure == "\"" && strstr($firstname,"\"")) $firstname = str_replace("\"","\"\"",$firstname);
		$lastname = $row["lastname"];
		if ($defaultenclosure == "\"" && strstr($lastname,"\"")) $lastname = str_replace("\"","\"\"",$lastname);
		$customerid = $row["customerid"];
		$email = $row["email"];
		$address = $row["address"];
		if ($defaultenclosure == "\"" && strstr($address,"\"")) $address = str_replace("\"","\"\"",$address);
		$city = $row["city"];
		if ($defaultenclosure == "\"" && strstr($city,"\"")) $city = str_replace("\"","\"\"",$city);
		$state = $row["state"];
		$zip = $row["zip"];
		$country = $row["country"];
		$phone = $row["phone"];
		if ($defaultenclosure == "\"" && strstr($phone,"\"")) $phone = str_replace("\"","\"\"",$phone);
		$shippingresult = @mysqli_query($db, "SELECT * FROm shipping WHERE customerid='$customerid'");
		$shippingrow = @mysqli_fetch_array($shippingresult);
		$shippingaddress = $shippingrow["shippingaddress"];
		if ($defaultenclosure == "\"" && strstr($shippingaddress,"\"")) $shippingaddress = str_replace("\"","\"\"",$shippingaddress);
		$shippingcity = $shippingrow["shippingcity"];
		if ($defaultenclosure == "\"" && strstr($shippingcity,"\"")) $shippingcity = str_replace("\"","\"\"",$shippingcity);
		$shippingstate = $shippingrow["shippingstate"];
		$shippingzip = $shippingrow["shippingzip"];
		$shippingcountry = $shippingrow["shippingcountry"];
		echo "$customerid{$defaultdelimiter}{$defaultenclosure}$firstname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$lastname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$email{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$address{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$city{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$state{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$zip{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$country{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$phone{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$shippingaddress{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$shippingcity{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$shippingstate{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$shippingzip{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$shippingcountry{$defaultenclosure}\r\n";
	}
} else {
	if ($ws == "true") header("Location: wssalesadmin.php");
	else header("Location: salesadmin.php");
}
?>