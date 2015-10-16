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
// Get language module...
include "language/$adminlang/editcatalog.inc.php";
// Get QR code generator library...
include "../includes/qrgen/qrlib.php";

if (empty($productid) || !is_numeric($productid)) exit;

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get product information...
$result = @mysqli_query($db, "SELECT detailsurl FROM product WHERE productid='$productid'");
$url = @mysqli_result($result, 0, "detailsurl");
if (empty($url)) $url = "$ashopurl/index.php?product=$productid";

// Generate and show the QR code...  
QRcode::png($url, "$ashoppath/prodqrimg/qr$productid.png", "Q", 3, 2);
echo "<html><head><title>".QRCODEIMAGE."</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\"><center><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".QRCODEIMAGE.":<br><br><img src=\"$ashopurl/prodqrimg/qr$productid.png\"><br><br>".URL.": <input type=\"text\" size=\"67\" value=\"$ashopurl/prodqrimg/qr$productid.png\"><br><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSETHISWINDOW."</a></font></center></body></html>";

    
?>