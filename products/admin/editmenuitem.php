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

// Block unauthorized access...
if ($userid != "1" && !$membershops) {
	header("Location: index.php");
	exit;
}

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
   $sql="SELECT * FROM menuitem WHERE itemid = '$item'";
   $result = @mysqli_query($db, $sql);
   $itemcaption = @mysqli_result($result, 0, "caption");
   $itemowner = @mysqli_result($result, 0, "userid");
   $itemlanguage = @mysqli_result($result, 0, "language");
   $itemurl = @mysqli_result($result, 0, "url");
   $parentitem = @mysqli_result($result, 0, "parentitemid");
   if ($parentitem == $item) $istopitem = TRUE;
   else {
	   $istopitem = FALSE;
	   $parentresult = @mysqli_query($db, "SELECT caption FROM menuitem WHERE itemid = '$parentitem'");
	   $parentitemcaption = @mysqli_result($parentresult, 0, "caption");
   }
   $itemmemberclone = @mysqli_result($result, 0, "memberclone");

if ($remove && $item) {
	if ($yes) {
		@mysqli_query($db, "DELETE FROM menuitem WHERE itemid='$item' OR parentitemid='$item'");
		header("Location: editmenu.php?language=$language&owner=$owner");
	}
    elseif ($no) header("Location: editmenu.php");
    else echo "$header
        <div class=\"heading\">".REMOVEANITEM."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p>".AREYOUSUREREMOVE." $itemcaption</p>
		<form action=\"editmenuitem.php\" method=\"post\">
		<table align=\"center\" width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"item\" value=\"$item\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"><input type=\"hidden\" name=\"owner\" value=\"$owner\"><input type=\"hidden\" name=\"language\" value=\"$language\"></form>
        </center></td></tr></table>$footer";
} 

elseif (!$caption) {
	// Generate Shopping Mall member list if needed...
	if ($membershops && $userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
		$memberlist = "<select name=\"memberid\"><option value=\"1\"";
		if ($itemowner == "1") $memberlist .= " selected";
		$memberlist .= ">".ADMINISTRATOR;
		$result = @mysqli_query($db, "SELECT * FROM user WHERE userid>1 ORDER BY shopname");
		while ($row = @mysqli_fetch_array($result)) {
			$memberlist .= "<option value=\"{$row["userid"]}\"";
			if ($itemowner == $row["userid"]) $memberlist .= " selected";
			$memberlist .= ">{$row["shopname"]}";
		}
		$memberlist .= "</select>";
	} else $memberlist = "";

	// Generate language list if needed...
	$languagelist = "<select name=\"nlanguage\"><option value=\"any\"";
	if ($itemlanguage == "any") $languagelist .= " selected";
	$languagelist .= ">".ANY;
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
			if ($langmodule == $itemlanguage) $languagelist .= " selected";
			$languagelist .= ">$langname</option>";
		}
	}

	echo "$header
        <div class=\"heading\">".EDITMENUITEM."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p>".EDITTHEITEM." $itemcaption ".BYENTERINGNEWDATA."</p>
        <form action=\"editmenuitem.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
        <tr><td align=\"right\" class=\"formlabel\">".CAPTION.":</td><td><input type=\"text\" name=\"caption\" size=\"35\" value=\"".htmlentities(stripslashes($itemcaption), ENT_QUOTES)."\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".URL.":</td><td><input type=\"text\" name=\"url\" value=\"$itemurl\" size=\"35\"></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			if ($membershops && $memberlist) echo "<tr><td align=\"right\" class=\"formlabel\">".OWNER.":</td><td>$memberlist</td></tr>";
			else echo "<input type=\"hidden\" name=\"memberid\" value=\"1\">";
			if ($itemowner == "1") {
				echo "<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"memberclone\"";
				if ($itemmemberclone) echo " checked";
				echo "> ".MAKETHISITEMAVAILABLE;
			}
		}
		echo "<tr><td align=\"right\" class=\"formlabel\">".LANGUAGE.":</td><td>$languagelist</td></tr>
        <tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"item\" value=\"$item\"><input type=\"hidden\" name=\"owner\" value=\"$owner\"><input type=\"hidden\" name=\"language\" value=\"$language\"><input type=\"submit\" value=\"".SUBMIT."\"></td></tr></form></table>
		</table></td></tr></table></center></td></tr></table>$footer";
} else {
   if ($memberid) $user = $memberid;
   else $user = $userid;
   if ($memberclone == "on") $memberclone = 1;
   else $memberclone = NULL;
   if (empty($layout) || !is_numeric($layout)) $layout = 0;
   $sql="UPDATE menuitem SET caption='$caption', url='$url', userid='$user', language='$nlanguage', memberclone='$memberclone' WHERE itemid='$item'";
   $result = @mysqli_query($db, $sql);

   // Change the owner of this item and all subitems it contains...
   if ($memberid && $itemowner != $memberid) {
	   $result = @mysqli_query($db, "SELECT * FROM menuitem WHERE parentitemid='$item'");
	   while ($row = @mysqli_fetch_array($result)) @mysqli_query($db, "UPDATE menuitem SET userid='$memberid' WHERE itemid='{$row["itemid"]}'");
   }

   header("Location: editmenu.php?language=$language&owner=$owner");
}
?>