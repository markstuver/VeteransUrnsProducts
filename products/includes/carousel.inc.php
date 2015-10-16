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

if (!isset($cat)) $cat = 0;
if ($cat && !is_numeric($cat)) $cat = 0;

$carouseldiv = "";
$result = @mysqli_query($db, "SELECT productid, name FROM product WHERE featured != '' AND featured IS NOT NULL order by featured");
while ($row = @mysqli_fetch_array($result)) {
	$productid = $row["productid"];
	$name = $row["name"];
	$name = strip_tags($name);
	// Get product image info...
	$productimage = ashop_productimages($productid);
	if ($productimage["thumbnail"]) {
		$imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["thumbnail"]}");
		if (empty($carouseldiv)) $carouseldiv = "<script type=\"text/javascript\" src=\"includes/jquery-1.7.1.min.js\"></script>
		<script type=\"text/javascript\" src=\"includes/jcarousellite.js\"></script>
		<div align=\"center\">
		<div class=\"carousel\">
			<ul>";
		$carouseldiv .= "
		<li><a href=\"index.php?product=$productid\"><img src=\"prodimg/$productid/{$productimage["thumbnail"]}\" alt=\"{$productid}\" border=\"0\" {$imagesize[3]}></a><p><a href=\"index.php?product=$productid\">$name</a></p></li>";
	}
}
if (!empty($carouseldiv)) $carouseldiv .= "
</ul></div></div><script type=\"text/javascript\">
$(\".carousel\").jCarouselLite({ auto: 2000, speed: 2000, visible: 3 });
</script>";

echo $carouseldiv;
?>