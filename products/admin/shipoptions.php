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
include "checklogin.inc.php";

// Set the country...
$selectedcountry = $_GET["country"];
if (!array_key_exists($selectedcountry,$countries)) $selectedcountry = "";

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Convert multiple origin countries to an array...
$shipfromcountries = explode("-", $shipfromcountry);

$shipoptiontype = "";
if (in_array($selectedcountry, $shipfromcountries)) $shipoptiontype = "local";
else if ($selectedcountry) $shipoptiontype = "international";

// Check for shipping options...
$upsservice = "00";
$fedexservice = "00";
if ($shipoptionstype == "custom" && $shippingmethod == "custom") {
	$result = @mysqli_query($db, "SELECT * FROM shipoptions");
	if (@mysqli_num_rows($result)) $activateshipoptions = TRUE;
	else $activateshipoptions = FALSE;

	// Get shipping options...
	$selectedshipoptions = "";
	$sql = "SELECT * FROM shipoptions";
	if ($shipoptiontype) $sql .= " WHERE shipped='$shipoptiontype' OR shipped='both'";
	else $sql .= " WHERE shipped='both'";
	$sql .= " ORDER BY shipoptionid DESC";
	$result = @mysqli_query($db, $sql);
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$shipoptionid = @mysqli_result($result, $i, "shipoptionid");
		$shipoptions["$shipoptionid"] = @mysqli_result($result, $i, "description");
		if (strstr($taxandshipping, "so$shipoptionid"."a")) $selectedshipoptions = "so$shipoptionid"."a";
	}
} else if ($shipoptionstype == "ups" && $shippingmethod == "ups") {
	$activateshipoptions = TRUE;
	if ($upscountry == "US") {
		if ($selectedcountry == "US") $upsshipoptions = $upsservicesusa;
		else if ($selectedcountry == "CA") $upsshipoptions = $upsservicestocan;
		else $upsshipoptions = $upsservicesworld;
	} else if ($upscountry == "CA") {
		if ($selectedcountry == "CA") $upsshipoptions = $upsservicescan;
		else if ($selectedcountry == "US") $upsshipoptions = $upsservicestousa;
		else $upsshipoptions = $upsservicesworld;
	}

	if ($shipoptiontype) foreach ($upsshipoptions as $shipoptionid=>$shipoptiondescription) {
		$shipoptions["$shipoptionid"] = $shipoptiondescription;
		if (strstr($taxandshipping, "so$shipoptionid"."a")) $selectedshipoptions = "so$shipoptionid"."a";
	}
} else if ($shipoptionstype == "fedex" && $shippingmethod == "fedex") {
	$activateshipoptions = TRUE;

	if ($selectedcountry == "US") foreach ($fedexservicesusa as $servicecode => $servicename) {
		if ($servicecode != "70" && $servicecode != "80" && $servicecode != "83") $fedexshipoptions["$servicecode"] = $servicename;
	} else foreach ($fedexservicesworld as $servicecode => $servicename) {
		if ($servicecode != "70" && $servicecode != "86") $fedexshipoptions["$servicecode"] = $servicename;
	}

	if ($shipoptiontype) foreach ($fedexshipoptions as $shipoptionid=>$shipoptiondescription) {
		$shipoptions["$shipoptionid"] = $shipoptiondescription;
		if (strstr($taxandshipping, "so$shipoptionid"."a")) $selectedshipoptions = "so$shipoptionid"."a";
	}
} else if ($shipoptionstype == "usps" && $shippingmethod == "usps") {
	$activateshipoptions = TRUE;

	if ($selectedcountry == "US") foreach ($uspsservicesusa as $servicecode => $servicename) $uspsshipoptions["$servicecode"] = $servicename;
	else foreach ($uspsservicesworld as $servicecode => $servicename) $uspsshipoptions["$servicecode"] = $servicename;

	if ($shipoptiontype) foreach ($uspsshipoptions as $shipoptionid=>$shipoptiondescription) {
		$shipoptions["$shipoptionid"] = $shipoptiondescription;
		if (strstr($taxandshipping, "so$shipoptionid"."a")) $selectedshipoptions = "so$shipoptionid"."a";
	}
}

$shipoptionnumber = 0;
if (!empty($shipoptions) && is_array($shipoptions)) foreach ($shipoptions as $shipoptionid => $shipoptiondescr) {
	$shipoptionnumber++;
	if (count($shipoptions) == 1) {
		echo "<input type=\"checkbox\" name=\"shipoption\"";
		if (strstr($taxandshipping, "so$shipoptionid")) echo "checked";
		echo "><span class=\"ashopshippingtext2\"> $shipoptiondescr</span>";
	} else {
		echo "<input type=\"radio\" name=\"shipoption\" value=\"$shipoptionid\" ";
		if (strstr($taxandshipping, "so$shipoptionid") || (count($shipoptions) > 1 && $shipoptionnumber == 1)) echo "checked";
		echo "><span class=\"ashopshippingtext2\"> $shipoptiondescr</span>";
		if ($shipoptionnumber < count($shipoptions)) echo "<br>";
	}
} else echo "<input type=\"hidden\" name=\"shipoption\" value=\"0\">";
?>