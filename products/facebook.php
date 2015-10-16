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
include "admin/ashopfunc.inc.php";

if ($fb_sig_in_iframe != "1") {
	header("Location: $ashopurl");
	exit;
}

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<title>$ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />
<style type=\"text/css\">
<!--
body {
	font-family: \"lucida grande\",tahoma,verdana,arial,sans-serif;
	color: #808080;
	font-size:11px;
}
a {
	color: #3b5998;
	text-decoration: none;
	font-weight: bold;
}
a:hover {
	text-decoration: underline;
}
hr {
	background:#d9d9d9;
	border-width:0;
	color:#d9d9d9;
	height:1px
}
h1 {
	color:#1c2a47;
	font-size:16px
}
h2 {
	color:#1c2a47;
	font-size:13px
}
-->
</style>
</head>
<body>
<center>
<div style=\"width: 600px; text-align: left;\">
<table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\">
<tr><td width=\"100\"><img src=\"images/logo.gif\"></td>
<td><h1>$ashopname</h1>
<h2>Featured Products</h2></td></tr></table><hr>";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get featured products...
$result = @mysqli_query($db, "SELECT * FROM product WHERE featured!='' AND featured IS NOT NULL AND featured!='0' ORDER BY featured ASC");
while($ffeaturedproductrow = @mysqli_fetch_array($result)) {
	$ffeaturedproductid = $ffeaturedproductrow["productid"];
	$ffeaturedproducturl = $ffeaturedproductrow["detailsurl"];
	$ffeaturedproductname = $ffeaturedproductrow["name"];
	$ffeaturedproductdescr = strip_tags($ffeaturedproductrow["description"]);
	if (!$ffeaturedproducturl) {
		$ffeaturedproducturl = "$ashopurl/index.php?product=$ffeaturedproductid";
	}
	echo "<p>";
	$thumbnail = FALSE;
	if (file_exists("$ashoppath/prodimg/$ffeaturedproductid.jpg")) {
		echo "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\"><tr><td width=\"$thumbnailwidth\"><img src=\"prodimg/$ffeaturedproductid.jpg\" alt=\"$ffeaturedproductname\" width=\"$thumbnailwidth\" align=\"left\"></td><td align=\"left\" valign=\"top\">";
		$thumbnail = TRUE;
	} else if (file_exists("$ashoppath/prodimg/$ffeaturedproductid.gif")) {
		echo "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\"><tr><td width=\"$thumbnailwidth\"><img src=\"prodimg/$ffeaturedproductid.gif\" alt=\"$ffeaturedproductname\" width=\"$thumbnailwidth\" align=\"left\"></td><td align=\"left\" valign=\"top\">";
		$thumbnail = TRUE;
	}
	echo "<a href=\"$ffeaturedproducturl\" target=\"_blank\">$ffeaturedproductname</a></p>
	<p>$ffeaturedproductdescr</p>
	<p align=\"right\"><a href=\"$ffeaturedproducturl\" target=\"_blank\"><img src=\"images/facebookbuy.gif\" alt=\"Buy Now\" border=\"0\"></a></p>";
	if ($thumbnail) echo "</td></tr></table>";
	echo "<hr>";
}

echo "</div></center></body>
</html>";
?>