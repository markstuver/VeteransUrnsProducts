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

$redirect = str_replace("|","&",$redirect);

if (!empty($currencies)) {
	$currencies = str_replace("|",",",$currencies);
	$currencies = str_replace(";",",",$currencies);
	$currencies = str_replace(" ","",$currencies);
	$currenciesarray = explode(",",$currencies);
}

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

if (!isset($currencynames)) include "admin/ashopconstants.inc.php";

if (empty($curr)) $curr = $ashopcurrency;

$currencylist = "";
$currencyresult = @mysqli_query($db, "SELECT * FROM currencies ORDER BY currencycode");
if (!@mysqli_num_rows($currencyresult)) {
	if (!function_exists(ashop_mailsafe)) include "admin/ashopfunc.inc.php";
	getcurrencyrates();
	$currencyresult = @mysqli_query($db, "SELECT * FROM currencies ORDER BY currencycode");
}
while ($currencyrow = @mysqli_fetch_array($currencyresult)) {
	$currencycode = $currencyrow["currencycode"];
	if (empty($currenciesarray) || !is_array($currenciesarray) || in_array($currencycode,$currenciesarray)) {
		$currency = $currencynames["$currencycode"];
		$currencylist .= "<option value=\"$currencycode\"";
		if ($currencycode == $curr) $currencylist .= " selected=\"selected\"";
		$currencylist .= ">$currency</option>";
	}
}

if ($currencylist) {
	echo "<form name=\"currencyselect\" action=\"$ashopurl/currency.php\"><select name=\"currency\" onchange=\"currencyselect.submit();\">$currencylist</select><input type=\"hidden\" name=\"redirect\" value=\"$redirect\" />";
	if (!empty($shop) && $shop != 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
	echo "
	</form>";
}
?>