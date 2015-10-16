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
include "language/$adminlang/affiliates.inc.php";
include "ashopconstants.inc.php";

if ($userid != "1") exit;

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Convert double quote enclosure for CSV...
if ($defaultenclosure == "&quot;") $defaultenclosure = "\"";

// Get affiliate information from database...
$sql = "SELECT * FROM affiliate WHERE firstname != '' AND email != ''";
if ($namefilter || $urlfilter) $sql .= " AND ";
if ($namefilter) {
	$sql .= "(firstname  LIKE '%$namefilter%' OR lastname LIKE '%$namefilter%')";
	if ($urlfilter) $sql .= " AND url LIKE '%$urlfilter%'";
} else if ($urlfilter) $sql .= " url LIKE '%$urlfilter%'";
$sql .= " ORDER BY affiliateid ASC";
$result = @mysqli_query($db, $sql);
if (@mysqli_num_rows($result)) {
	header ("Content-Type: application/octet-stream");
	header ("Content-Disposition: attachment; filename=affiliates.csv");
	echo AFFILIATEID."{$defaultdelimiter}".FIRSTNAME."{$defaultdelimiter}".LASTNAME."{$defaultdelimiter}".EMAIL."{$defaultdelimiter}".ADDRESS."{$defaultdelimiter}".CITY."{$defaultdelimiter}".STATEPROVINCE."{$defaultdelimiter}".ZIP."{$defaultdelimiter}".COUNTRY."{$defaultdelimiter}".PHONE."{$defaultdelimiter}".URL."{$defaultdelimiter}".PAYPALID."\r\n";
	while ($row = @mysqli_fetch_array($result)) {
		$firstname = $row["firstname"];
		if ($defaultenclosure == "\"" && strstr($firstname,"\"")) $firstname = str_replace("\"","\"\"",$firstname);
		$lastname = $row["lastname"];
		if ($defaultenclosure == "\"" && strstr($lastname,"\"")) $lastname = str_replace("\"","\"\"",$lastname);
		$affiliateid = $row["affiliateid"];
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
		$url = $row["url"];
		$paypalid = $row["paypalid"];
		echo "$affiliateid{$defaultdelimiter}{$defaultenclosure}$firstname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$lastname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$email{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$address{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$city{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$state{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$zip{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$country{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$phone{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$url{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$paypalid{$defaultenclosure}\r\n";
	}
} else {
	header("Location: affiliateadmin.php");
}
?>