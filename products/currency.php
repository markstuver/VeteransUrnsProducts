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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

$redirect = str_replace("|","&",$redirect);
if (!ashop_is_url($redirect)) $redirect = "";
$redirect = str_ireplace("http://","",$redirect);
$redirect = str_ireplace("https://","",$redirect);

if (preg_match("/^[a-z]*$/", $currency) && strlen($currency) == 3) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("curr",$currency);
}
if (!empty($shop) && is_numeric($shop)) {
	if (strstr($redirect,"?")) $shopurlpart = "&shop=$shop";
	else $shopurlpart = "?shop=$shop";
} else $shopurlpart = "";
if ($redirect) header("Location: $redirect$shopurlpart");
else header("Location: index.php$shopurlpart");
?>