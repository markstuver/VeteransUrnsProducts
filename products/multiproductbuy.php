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

include "admin/config.inc.php";

// Get return URL...
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";
if (empty($returnurl)) $returnurl = $_SERVER["HTTP_REFERER"];

// Validate variables...
if ($action != "basket" && $action != "checkout") unset($action);
$basket = str_replace("<","",$basket);
$basket = str_replace(">","",$basket);
$returnurl = str_replace("<","",$returnurl);
$returnurl = str_replace(">","",$returnurl);
$returnurl = str_replace($ashopurl,"",$returnurl);
$returnurl = str_replace($ashopsurl,"",$returnurl);
if (substr($returnurl,0,1) == "/") $returnurl = substr($returnurl,1);

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Set basket variable from product array...
if (is_array($products) && is_array($quantity)) foreach ($products as $productnumber=>$productid) {
	if (!empty($quantity[$productnumber]) && $quantity[$productnumber] > 0 && (!empty($buy[$productid]) || !empty($buyall_x))) {
		$basket .= $quantity[$productnumber]."b";
		$checkattribute = str_replace("b","",$attribute[$productnumber]);
		if (!is_numeric($checkattribute)) $attribute[$productnumber] = "";
		if (!empty($attribute[$productnumber])) $basket .= $attribute[$productnumber];
		$basket .= "b".$productid."a";
	}
}

if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$p3psent = TRUE;
setcookie("basket","$basket");
if (!isset($action)) $action = "checkout";

if (!strstr($HTTP_REFERER, "basket.php") && !strstr($HTTP_REFERER, "checkout.php") && !strstr($HTTP_REFERER, "shipping.php")) { header ("Location: $action.php?returnurl=$returnurl"); exit; }
else if ($returnurl) { header ("Location: $action.php?returnurl=$returnurl"); exit; }
?>