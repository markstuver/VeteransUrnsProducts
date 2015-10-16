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
include "checklogin.inc.php";
include "template.inc.php";

if (!$saasuwsaccesskey || !$saasufileid) exit;

// Open a database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$result = @mysqli_query($db, "SELECT * FROM export");
$row = @mysqli_fetch_array($result);
$saasuassetaccount = $row["saasuassetaccount"];
$saasupurchasetaxcode = $row["saasupurchasetaxcode"];
$saasuincomeaccount = $row["saasuincomeaccount"];
$saasusalestaxcode = $row["saasusalestaxcode"];
$saasucosaccount = $row["saasucosaccount"];

if (!$saasuassetaccount || !$saasupurchasetaxcode || !$saasuincomeaccount || !$saasusalestaxcode || !$saasucosaccount) exit;
else {
	// Select products to export...
	$result = @mysqli_query($db, "SELECT * FROM product WHERE skucode != '' AND skucode IS NOT NULL AND (exportedtosaasu = '0' OR exportedtosaasu = '' OR exportedtosaasu IS NULL) AND (copyof IS NULL OR copyof ='')");
	
	while ($row = @mysqli_fetch_array($result)) {
		$productid = $row["productid"];
		$name = $row["name"];
		$price = $row["price"];
		$sku = $row["skucode"];
		$inventory = $row["inventory"];
		$cost = $row["cost"];

		// Check if this product has any variations...
		$checkvariations = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid'");

		// Check if the product exists in SAASU...
		if (!@mysqli_num_rows($checkvariations)) {
			$existinguid = ashop_saasu_getitemuid($sku);
			if (!is_numeric($existinguid)) {
				// The product does not exist, export it...
				$exportcheck = ashop_saasu_additem($name, $price, $inventory, $sku, $cost, $saasuassetaccount, $saasuincomeaccount, $saasucosaccount, $saasupurchasetaxcode, $saasusalestaxcode);
			} else {
				// The product is already in SAASU, make sure the inventory is set...
				if ($inventory) {
					$checkinventory = ashop_saasu_getinventory($sku);
					if (!$checkinventory || $checkinventory == "nodata") $exportcheck = ashop_saasu_adjustinventory($name, $inventory, $existinguid, $saasuassetaccount);
				}
			}
			@mysqli_query($db, "UPDATE product SET exportedtosaasu = '1' WHERE productid='$productid'");
		} else {
			@mysqli_query($db, "UPDATE product SET exportedtosaasu = '1' WHERE productid='$productid'");
			
			// Export all variations of this product...
			$typestring = array();
			$typevalues = array();
			ashop_gettypes($productid);

			if (!empty($typevalues)) {
				foreach ($typevalues as $typenumber=>$type) {
					$thistypestring = $typestring[$typenumber];
					$typeresult = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid' AND type='$thistypestring'");
					$typerow = @mysqli_fetch_array($typeresult);
					$typesku = $typerow["skucode"];
					$typeinventory = $typerow["inventory"];
					$typeproductname = $name." - $type";
					$typeprice = $price;
					$parametervalues = explode("|",$thistypestring);
					foreach ($parametervalues as $valuenumber=>$valueid) {
						$checkparameterprice = @mysqli_query($db, "SELECT price FROM parametervalues WHERE valueid='$valueid' AND price IS NOT NULL AND price != ''");
						if (@mysqli_num_rows($checkparameterprice)) $typeprice = @mysqli_result($checkparameterprice, 0, "price");
					}
					
					// Check if the product variation exists in SAASU...
					$existinguid = ashop_saasu_getitemuid($typesku);
					if (!is_numeric($existinguid)) {
						// The product variation does not exist, export it...
						$exportcheck = ashop_saasu_additem($typeproductname, $typeprice, $typeinventory, $typesku, $cost, $saasuassetaccount, $saasuincomeaccount, $saasucosaccount, $saasupurchasetaxcode, $saasusalestaxcode);
					} else {
						// The product is already in SAASU, make sure the inventory is set...
						if ($typeinventory) {
							$checkinventory = ashop_saasu_getinventory($typesku);
							if (!$checkinventory || $checkinventory == "nodata") $exportcheck = ashop_saasu_adjustinventory($typeproductname, $typeinventory, $existinguid, $saasuassetaccount);
						}
					}
					@mysqli_query($db, "UPDATE productinventory SET exportedtosaasu = '1' WHERE productid='$productid' AND type='$thistypestring'");
				}
			}
		}
	}
}
?>