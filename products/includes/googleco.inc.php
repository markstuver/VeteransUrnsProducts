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

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Initialize variables...
if (!isset($shop)) $shop = 1;
if (!empty($shop) && !is_numeric($shop)) $shop = 1;

if (empty($button) || !is_numeric($button)) $button = 1;
if (!strstr($product,"b")) {
	if (empty($quantity) || !is_numeric($quantity)) $quantity = 1;
	$product = $quantity."b".$product."a";
}

// Get Google Checkout payment option details...
$gcocheckresult = @mysqli_query($db, "SELECT * FROM payoptions WHERE gateway='googleco' AND userid='$shop'");
echo mysqli_error();
if (@mysqli_num_rows($gcocheckresult)) {
	$gcoid = @mysqli_result($gcocheckresult,0,"merchantid");
	$gcokey = @mysqli_result($gcocheckresult,0,"secret");
	$gcotest = @mysqli_result($gcocheckresult,0,"testmode");
	ashop_googlecheckoutbutton($db, $product, $gcoid, $gcokey, $gcotest, $button);
}

$product = "";
$button = "";
?>