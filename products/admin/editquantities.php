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
include "ashopconstants.inc.php";

if ($update) $action = "update";
if ($delete) $action = "delete";
$inrate = $rate;

$rate = preg_replace ('/\$/', "", $inrate);
$rate = preg_replace ('/,/', "", $rate);
$rate = round($rate,2);

if ($action == "updatefirst") {
	$action= "update";
	$origquantity= 1;
	$quantity= 1;
}
if ($action == "addfirstrate") {
	$action= "add";
	$quantity= 1;
}
if (empty($rate)) $rate = 0;
if (intval($quantity) <= 0) {
	header ("Location: editshipping.php?productid=$productid");
	exit;
}

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Add Quantity Entry for a product
if ($action == "add" && is_numeric($productid) && is_numeric($quantity) && is_numeric($rate)) {
	//check if quantity is already added for the record
	$result = @mysqli_query($db, "SELECT rate FROM quantityrates WHERE productid='$productid' AND quantity='$quantity'");
	if (@mysqli_fetch_array($result)) $already_exists = true;		
	if (!$already_exists) $result = @mysqli_query($db, "INSERT INTO quantityrates SET productid='$productid', quantity='$quantity', rate='$rate'");
}

// Delete Quantity Entry for a product
if ($action == "delete" && is_numeric($productid) && is_numeric($quantity)) {
	if (intval($quantity) > 1) $result = @mysqli_query($db, "DELETE FROM quantityrates WHERE productid='$productid' AND quantity='$quantity'");
}

// Update Quantity Entry for a product
if ($action == "update" && is_numeric($productid) && is_numeric($origquantity) && is_numeric($quantity) && is_numeric($rate)) {
	//check if quantity is already added for the record
	if ($quantity != $origquantity) $result = @mysqli_query($db, "SELECT rate FROM quantityrates WHERE productid='$productid' AND quantity='$quantity'");
	if (@mysqli_fetch_array($result)) $already_exists = true;		
	if (!$already_exists) $result = @mysqli_query($db, "UPDATE quantityrates SET quantity='$quantity',rate='$rate' WHERE productid='$productid' AND quantity='$origquantity'");
}

if ($already_exists) header ("Location: editshipping.php?quantity_exists=$already_exists&productid=$productid");
else header ("Location: editshipping.php?msg=updated&productid=$productid");
?>