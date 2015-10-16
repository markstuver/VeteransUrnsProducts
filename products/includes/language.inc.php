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

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";

$redirect = str_replace("|","&",$redirect);

// Apply selected theme...
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";

$langlist = "";
$findfile = opendir("$ashoppath/language");
while ($foundfile = readdir($findfile)) {
	if(is_dir("$ashoppath/language/$foundfile") && strlen($foundfile) == 2 && $foundfile != ".." && ((is_array($themelanguages) && in_array("$foundfile",$themelanguages)) || !is_array($themelanguages)) && file_exists("$ashoppath/language/$foundfile/lang.cfg.php")) {
		$fp = fopen ("$ashoppath/language/$foundfile/lang.cfg.php","r");
		while (!feof ($fp)) {
			$fileline = fgets($fp, 4096);
			if (strstr($fileline,"\$langname")) $langnamestring = $fileline;
		}
		fclose($fp);
		eval ($langnamestring);
		if ($lang == $foundfile) {
			$langlist .= "<option value=\"$foundfile\" selected=\"selected\">$langname</option>";
			$selectedname = $langname;
		} else $langlist .= "<option value=\"$foundfile\">$langname</option>";
	}
}

if ($langlist) {
	if (strstr($_SERVER["REQUEST_URI"],"/affiliate/") || strstr($_SERVER["REQUEST_URI"],"/members/")) {
		echo "<form name=\"languageselect\" action=\"$ashopurl/language.php\"><select name=\"language\" onchange=\"languageselect.submit();\">$langlist</select> <img src=\"../language/$lang/flag.gif\" alt=\"$langname\" style=\"vertical-align: text-bottom;\" /><input type=\"hidden\" name=\"redirect\" value=\"$redirect\" />";
		if (!empty($shop) && $shop != 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
		echo "
		</form>";
	} else {
		echo "<form name=\"languageselect\" action=\"$ashopurl/language.php\"><select name=\"language\" onchange=\"languageselect.submit();\">$langlist</select> <img src=\"language/$lang/flag.gif\" alt=\"$langname\" style=\"vertical-align: text-bottom;\" /><input type=\"hidden\" name=\"redirect\" value=\"$redirect\" />";
		if (!empty($shop) && $shop != 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
		echo "
		</form>";
	}
}
?>