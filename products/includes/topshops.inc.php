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
// --------------------------------------------------------------------
// Module: topshops.inc.php
// Input variables: items = number of items to show, redirect = where to link to,
// layout = 1 : format the output as a table
// layout = 2 : format the output as an unordered list

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/topjs.inc.php";
if (!empty($defaultlanguage) && $lang != $defaultlanguage) $toplistlang = $lang;
else $toplistlang = "";

// Validate $layout...
if (isset($layout) && !is_numeric($layout)) unset($layout);
if (isset($layout) && ($layout > 2 || $layout < 1)) unset($layout);
if (!isset($layout)) $layout = 1;

if (!$redirect) {
	$redirect = "index.php";
}

if (!$items || !is_numeric($items)) $items = 10;

// Show top sellers...
if ($layout == 1) {
	// Check if there is a cached top list available...
	$date = date("Ymd", time()+$timezoneoffset);
	if (file_exists("$ashoppath/updates/topshops{$toplistlang}{$layout}{$date}")) {
		$fp = fopen ("$ashoppath/updates/topshops{$toplistlang}{$layout}{$date}","r");
		if ($fp) {
			while (!feof ($fp)) echo fgets($fp, 4096);
			fclose($fp);
		}
	} else {
		if (is_dir("$ashoppath/updates") && is_writable("$ashoppath/updates")) {
			// Remove old top lists...
			$findfile = opendir("$ashoppath/updates");
			if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
				$checklen = strlen("topshops{$toplistlang}{$layout}");
				if (substr($foundfile,0,$checklen) == "topshops{$toplistlang}{$layout}" && !is_dir("$ashoppath/updates/$foundfile")) unlink("$ashoppath/updates/$foundfile");
			}
			// Create new top list...
			$fp = @fopen("$ashoppath/updates/topshops{$toplistlang}{$layout}{$date}", "w");
		}

		$result = @mysqli_query($db, "SELECT userid, shopname FROM user WHERE userid>'1'");

		if (@mysqli_num_rows($result)) {

			if ($mode == "list") {
				echo "<ul>\n";
				if ($fp) @fwrite($fp, "<ul>\n");
			}

			while ($row = @mysqli_fetch_array($result)) {
				$shop = $row["userid"];
				$shopname = $row["shopname"];
				$shops["$shop"] = $shopname;
				$subresult = @mysqli_query($db, "SELECT count(orderid) AS numberoforders FROM orders WHERE paid != '' AND userid LIKE '%|{$shop}|%'");
				$numberoforders = @mysqli_result($subresult,0,"numberoforders");
				$orders["$shop"] = $numberoforders;
			}
			$shopnumber = 1;
			if ($orders) {
				@arsort($orders);
				foreach ($orders as $shop=>$numberoforders) {
					$shopname = $shops["$shop"];
					$shopname = addslashes($shopname);
					if ($layout == 1) {
						echo "$shopnumber. <a href=\"$redirect?shop=$shop\">$shopname</a><br />\n";
						if ($fp) @fwrite($fp, "$shopnumber. <a href=\"$redirect?shop=$shop\">$shopname</a><br />\n");
					} else if ($layout == 2) {
						echo "<li>$shopnumber. <a href=\"$redirect?shop=$shop\">$shopname</a></li>\n";
						if ($fp) @fwrite($fp, "<li>$shopnumber. <a href=\"$redirect?product=$productid\">$shopname</a></li>\n");
					} 
					$shopnumber++;
					if ($shopnumber > $items) break;
				}
			}
			if ($mode == "list") {
				echo "</ul>";
				if ($fp) @fwrite($fp, "</ul>\n");
			}
		}
		fclose($fp);
	}
}

$layout = "";
?>