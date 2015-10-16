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

if(!$orderid || !@file_exists("$ashoppath/admin/receipts/$orderid")) exit;

// Read receipt...
$receipt = "";
$fp = @fopen ("$ashoppath/admin/receipts/$orderid","r");
if ($fp) {
	while (!feof ($fp)) $receipt .= fgets($fp, 4096);
	fclose($fp);
}

// Check the format of the receipt...
if (!strpos($receipt,"<html>") && !strpos($receipt,"<HTML>") && !strpos($receipt,"<br>") && !strpos($receipt,"<BR>") && !strpos($receipt,"<br />") && !strpos($receipt,"<BR />") && !strpos($receipt,"<p>") && !strpos($receipt,"<P>")) echo "<html><body><pre>$receipt</pre>";
else echo $receipt;
?>