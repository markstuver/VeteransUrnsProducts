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

// License viewer

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

// Apply selected theme...
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/license.inc.php";

echo "<html><head><title>$ashopname".LICENSEAGREE1." $name</title>\n".CHARSET."<link rel=\"stylesheet\" href=\"includes/ashopcss.inc.php\" type=\"text/css\"></head>
      <body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\">";

if (!empty($_SERVER["QUERY_STRING"]) && is_numeric($_SERVER["QUERY_STRING"])) {
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
	$sql="SELECT name, licensetext FROM product WHERE productid = '$QUERY_STRING'";
	$result = @mysqli_query($db,$sql);
	$licensetext = @mysqli_result($result, 0, "licensetext");
	$licensetext = str_replace("\n","<br>",$licensetext);
	$name = @mysqli_result($result, 0, "name");
	@mysqli_close($db);
	echo "<p><span class=\"ashopproductagreementheader\">".LICENSEAGREE2." $name</span></p>";
} else if (file_exists("$ashoppath/agreement.txt")) {
	$licensetext = "";
	$fp = fopen ("$ashoppath/agreement.txt","r");
	if ($fp) {
		while (!feof ($fp)) $licensetext .= fgets($fp, 4096);
		fclose($fp);
	}
	echo "<p align=\"center\"><span class=\"ashopcheckoutagreement\"><b>".PURCHASEAGREEMENT."</b></span></p>";
}
echo "	  
	  <span class=\"ashopproductagreement\">$licensetext<br><br><br><center>
	  <a href=\"javascript:this.close()\">".WINDOWCLOSE."</a></span></center></body></html>";
?>