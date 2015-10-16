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

// Validate $language...
if (strlen($language) > 2) $language = "";
if (!preg_match("/^[a-zA-ZÀ-ÿ]*$/", $language)) $language = "";
$language = strtolower($language);

// Apply selected theme...
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";

$langlist = "";
$langfound = FALSE;
$findfile = opendir("$ashoppath/language");
while ($foundfile = readdir($findfile)) {
	if(is_dir("$ashoppath/language/$foundfile") && strlen($foundfile) == 2 && $foundfile != ".." && ((is_array($themelanguages) && in_array("$foundfile",$themelanguages)) || !is_array($themelanguages)) && file_exists("$ashoppath/language/$foundfile/lang.cfg.php")) {
		$fp = fopen ("$ashoppath/language/$foundfile/lang.cfg.php","r");
		while (!feof ($fp)) {
			$fileline = fgets($fp, 4096);
			if (strstr($fileline,"\$langname")) $langnamestring = $fileline;
			if (strstr($fileline,"\$langredirect")) $langredirectstring = $fileline;
		}
		fclose($fp);
		eval ($langnamestring);
		if ($language == $foundfile) {
			eval ($langredirectstring);
			$langfound = TRUE;
		}
		$langlist .= "<a href=\"language.php?language=$foundfile\"><img src=\"$ashopurl/language/$foundfile/flag.gif\" border=\"0\" alt=\"$langname\"></a>&nbsp;";
	}
}

if (!$langfound) $language = "";

if (!$language) {
	echo "<HTML></HEAD><title>$ashopname - Choose Language</title><link rel=\"stylesheet\" href=\"includes/ashopcss.inc.php\" type=\"text/css\"></HEAD>
	<BODY bgcolor=\"$bgcolor\" text=\"$textcolor\">
		<table width=\"100%\" height=\"100%\"><tr><td align=\"center\">
		<table class=\"ashoplanguageselectionbox\"><tr><td align=\"center\">
		<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
		<tr><td align=\"center\"><br><img src=\"images/logo.gif\" border=\"0\" alt=\"$ashopname\"></a><br>
		<br><span class=\"ashoplanguageselection\">Choose your language:</span>
		<br><br>$langlist<br><br></td></tr></table></td></tr></table></td></tr></table></body></html>";
} else {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("lang",$language);
	if (!empty($shop) && is_numeric($shop)) {
		if (strstr($redirect,"?") || strstr($langredirect,"?")) $shopurlpart = "&shop=$shop";
		else $shopurlpart = "?shop=$shop";
	} else $shopurlpart = "";
	if ($langredirect) header("Location: $langredirect$shopurlpart");
	else if ($redirect) header("Location: $redirect$shopurlpart");
	else header("Location: index.php$shopurlpart");
}
?>