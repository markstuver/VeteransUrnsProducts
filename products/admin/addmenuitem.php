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
include "language/$adminlang/editmenuitem.inc.php";
// Get context help for this page...
$contexthelppage = "addcategory";
include "help.inc.php";

// Block unauthorized access...
if ($userid != "1" && !$membershops) {
	header("Location: index.php");
	exit;
}

// Set the right member user ID...
if ($userid == 1) $user = 1;
else {
	if (!$membershops) {
		header("Location: index.php");
		exit;
	} else $user = $userid;
}

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

   // Generate Shopping Mall member list if needed...
   if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
	   $memberlist = "<select name=\"memberid\" onChange=\"javascript: if(menuitemform.memberid.value != '1') { menuitemform.memberclone.checked=false; menuitemform.memberclone.disabled=true; } else menuitemform.memberclone.disabled=false;\"><option value=\"1\">".ADMINISTRATOR;
	   $result = @mysqli_query($db,"SELECT * FROM user WHERE userid>1 ORDER BY shopname");
	   while ($row = @mysqli_fetch_array($result)) {
		   $memberlist .= "<option value=\"{$row["userid"]}\"";
		   if ($row["userid"] == $owner) $memberlist .= " selected";
		   $memberlist .= ">{$row["shopname"]}
		   ";
	   }
	   $memberlist .= "</select>
	   ";
   }

   	// Generate language list...
	$languagelist = "<select name=\"nlanguage\"><option value=\"any\">".ANY;
	$findfile = opendir("$ashoppath/language");
	while ($foundfile = readdir($findfile)) {
		if($foundfile && $foundfile != "." && $foundfile != ".." && is_dir("$ashoppath/language/$foundfile") && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_" && file_exists("$ashoppath/language/$foundfile/lang.cfg.php")) {
			$fp = fopen ("$ashoppath/language/$foundfile/lang.cfg.php","r");
			while (!feof ($fp)) {
				$fileline = fgets($fp, 4096);
				if (strstr($fileline,"\$langname")) $langnamestring = $fileline;
			}
			fclose($fp);
			eval ($langnamestring);
			$languages["$foundfile"] = $langname;
		}
	}
	if (is_array($languages)) {
		natcasesort($languages);
		foreach ($languages as $langmodule=>$langname) {
			$languagelist .= "<option value=\"$langmodule\"";
			if ($language == $langmodule) $languagelist .= " selected";
			$languagelist .= ">$langname</option>";
		}
	}


if (!$name && !$url) {
        echo "$header
        <div class=\"heading\">".ADDMENUITEM." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <form action=\"addmenuitem.php\" method=\"post\" name=\"menuitemform\" enctype=\"multipart/form-data\">
		<table width=\"520\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
        <tr><td align=\"right\" class=\"formlabel\">".CAPTION.":</td><td><input type=\"text\" name=\"caption\" size=\"35\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".URL.":</td><td><input type=\"text\" name=\"url\" size=\"35\"></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".OWNER.":</td><td>$memberlist</td></tr>
			<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"memberclone\"> ".MAKETHISITEMAVAILABLE;
		}
		echo "<tr><td align=\"right\" class=\"formlabel\"><font face=\"Arial, Helvetica, sans-serif\">".LANGUAGE.":</font></td><td>$languagelist</td></tr>
        <tr><td>&nbsp;</td><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table><input type=\"hidden\" name=\"language\" value=\"$language\"><input type=\"hidden\" name=\"owner\" value=\"$owner\"></form></td></tr></table>";
		echo $footer;
} else {
   if ($memberid) $menuuser = $memberid;
   else $menuuser = $user;
   if ($memberclone == "on") $memberclone = 1;
   $sql="INSERT INTO menuitem (caption,url,userid,language,memberclone) VALUES ('$caption','$url','$menuuser','$nlanguage','$memberclone')";
   $result = @mysqli_query($db,$sql);
   $itemid = @mysqli_insert_id($db);
   $sql="UPDATE menuitem SET parentitemid='$itemid', ordernumber='$itemid' WHERE itemid='$itemid'";
   $result = @mysqli_query($db,$sql);
   header("Location: editmenu.php?language=$language&owner=$owner");
}
?>