<?php
// AShop
// Copyright 2012 - AShop Software - http://www.ashopsoftware.com
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

if (!$databaseserver || !$databaseuser) include "../admin/config.inc.php";
if (!function_exists('ashop_opendatabase')) include "../admin/ashopfunc.inc.php";

// Validate variables...
$basket = urldecode($basket);
$basket = html_entity_decode($basket);
$basket = str_replace("<","",$basket);
$basket = str_replace(">","",$basket);

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Combine the same products in the basket cookie...
$basket = ashop_combineproducts($basket);

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/checkout.inc.php";

// Parse shopping cart string...
$productsincart = ashop_parseproductstring($db, $basket);

if ($productsincart) {
	foreach($productsincart as $productnumber => $thisproduct) {
		if ($thisproduct["useinventory"] && $thisproduct["quantity"] > $thisproduct["inventory"]) {
			echo NOTENOUGHINSTOCK1. " ".$thisproduct["name"];
			if ($thisproduct["parameters"]) echo $thisproduct["parameters"];
			echo " ".NOTENOUGHINSTOCK2;
			exit;
		}
	}
	if ($mode == "js") echo 1;
}
?>