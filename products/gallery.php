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

// Picture gallery...
include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

// Validate variables...
if (!empty($productid) && !is_numeric($productid)) exit;

// Get product image info...
$productimage = ashop_productimages($productid);

// Calculate height...
$imgnumber = 1;
$thumbnailheight = 0;
$imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["thumbnail"]}");
$thumbnailheight = $imagesize[1];
while (is_dir("$ashoppath/prodimg/$productid/$imgnumber")) {
	$thisproductimage = ashop_productimages($productid,$imgnumber);
	$imagesize = getimagesize("$ashoppath/prodimg/$productid/$imgnumber/{$thisproductimage["thumbnail"]}");
	$imageheight = $imagesize[1];
	if ($imageheight > $thumbnailheight) $thumbnailheight = $imageheight;
	$imgnumber++;
}

$galleryheight = 547;
$thumbnaillistheight = $thumbnailheight+22;

echo "<!DOCTYPE html>
<html lang=\"en\">
    <head>
        <meta charset=\"utf-8\">
 <title>Picture Gallery</title>
 <style type=\"text/css\">
 <!--
 #galleria{height:450px}
 -->
 </style>
<script type=\"text/javascript\" src=\"includes/jquery-1.7.1.min.js\"></script>
<script src=\"includes/galleria-1.2.5.min.js\"></script>
</head>
<body bgcolor=\"$itembgcolor\" topmargin=\"0\"  
marginheight=\"0\" leftmargin=\"0\" marginwidth=\"0\">
<div id=\"galleria\">
";

// Render main picture...
$imagesize = getimagesize("$ashoppath/prodimg/$productid/{$productimage["main"]}");
echo "<a href=\"prodimg/$productid/{$productimage["main"]}\">
<img src=\"prodimg/$productid/{$productimage["main"]}\" {$imagesize[3]} alt=\"$productid\" border=\"0\" align=\"top\"></a>";

$imgnumber = 1;
while (is_dir("$ashoppath/prodimg/$productid/$imgnumber")) {
	$thisproductimage = ashop_productimages($productid,$imgnumber);
	$imagesize = getimagesize("$ashoppath/prodimg/$productid/$imgnumber/{$thisproductimage["main"]}");
	echo "<a href=\"prodimg/$productid/$imgnumber/{$thisproductimage["main"]}\">
	<img src=\"prodimg/$productid/$imgnumber/{$thisproductimage["main"]}\" {$imagesize[3]} alt=\"$productid\" border=\"0\" align=\"top\"></a>
		";
	$imgnumber++;
}
echo "
</div>
    <script type=\"text/javascript\">

    // Load the classic theme
	Galleria.loadTheme('css/galleria.classic.min.js');

	// Initialize Galleria
	$('#galleria').galleria();

    </script>
</body> 
</html>";
?>