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

@set_time_limit(0);
include "admin/config.inc.php";
if ($_POST["apc_3"] == "r") $ipnurl = "$ashopurl/order.php";
else if ($_POST["apc_3"] == "w") $ipnurl = "$ashopurl/order.php";
$alertpayipn = "";
foreach ($_POST as $key=>$value) $alertpayipn .= "{$key}={$value}&";
$alertpayipn = substr($alertpayipn,0,-1);
if (function_exists('curl_version')) {
	$ch = curl_init();
	if (file_exists("$ashoppath/admin/curl.inc.php")) include "$ashoppath/admin/curl.inc.php";
	curl_setopt($ch, CURLOPT_URL,$ipnurl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $alertpayipn);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$curlresult = curl_exec ($ch);
	$curlerror = curl_error($ch);
	curl_close ($ch);
}
?>