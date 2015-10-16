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

error_reporting(E_ALL ^ E_NOTICE);
// Make sure the page isn't stored in the browsers cache...
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "config.inc.php";
if ($noinactivitycheck == "false") {
	if ($msg) $noinactivitycheck = "true";
	else $noinactivitycheck = "false";
}

if ($userid != "1" && !$membershops) {
	header("Location: index.php");
	exit;
}
include "ashopfunc.inc.php";
include "ashopconstants.inc.php";
include "checklogin.inc.php";
// Get context help for this page...
$contexthelppage = "editcatalogue";
include "help.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editcatalog.inc.php";

// Validate variables...
$msg = str_replace("<","",$msg);
$msg = str_replace(">","",$msg);

// Set the owner filter...
if ($userid != "1") $shopsearch = $userid;
else {
	if (!empty($owner)) {
		if (is_numeric($owner)) $shopsearch = $owner;
		else if ($owner == "any") $shopsearch = "%";
	} else $shopsearch = "%";
}

// Set the language filter...
if (!empty($language)) {
	if ($language == "all") $langsearch = "%";
	else $langsearch = "$language";
} else $langsearch = "%";

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

echo "$header
	<div class=\"heading\">".EDITMENU."</div>";

// Make a top level item a subitem...
if ($itemmoveunder && !empty($thisitemid) && !empty($parentitemid)) @mysqli_query($db, "UPDATE menuitem SET parentitemid='$parentitemid' WHERE itemid='$thisitemid'");

// Move item up one step...
if ($itemmoveup) {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
	$movelock = @mysqli_num_rows($result);
	$starttime = time();
	while ($movelock && time()-$starttime < 180) {
		sleep(5);
		$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
		$movelock = @mysqli_num_rows($result);
	}

	if (!$movelock) {
		$sql="UPDATE menuitem SET ordernumber='$prevordno' WHERE itemid='$thisitemid'";
		$result = @mysqli_query($db, $sql);
		$sql="UPDATE menuitem SET ordernumber='$thisordno' WHERE itemid='$previtemid'";
		$result = @mysqli_query($db, $sql);
	} else $error = "movelock2";
}

// Check that the page is not being reloaded...
if ($itemmovetop) {
	$check = @mysqli_query($db, "SELECT * FROM menuitem WHERE itemid='$thisitemid' AND ordernumber='$thisordno' AND userid like '$shopsearch'");
	if (!@mysqli_num_rows($check)) $catmovetop = "";
	else {
		$check = @mysqli_query($db, "SELECT * FROM menuitem WHERE ordernumber='$topordno' AND itemid!='$thisitemid' AND userid like '$shopsearch'");
		if (!@mysqli_num_rows($check)) $catmovetop = "";
	}
	if ($thisordno == $topordno) $catmovetop = "";
}

if ($itemmovetop) {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
	$movelock = @mysqli_num_rows($result);
	$starttime = time();
	while ($movelock && time()-$starttime < 180) {
		sleep(5);
		$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
		$movelock = @mysqli_num_rows($result);
	}

	if (!$movelock) {
		$starttime = time();
		@mysqli_query($db, "UPDATE user SET movelock='1' WHERE userid='$userid'");
		while ($thisordno != $topordno) {
			$result = @mysqli_query($db, "SELECT * FROM menuitem WHERE ordernumber<'$thisordno' AND userid like '$shopsearch' ORDER BY ordernumber DESC LIMIT 1");
			$prevordno = @mysqli_result($result, 0, "ordernumber");
			$prevcatid = @mysqli_result($result, 0, "categoryid");
			$sql="UPDATE menuitem SET ordernumber=$prevordno WHERE itemid='$thisitemid'";
			$result = @mysqli_query($db, $sql);
			$sql="UPDATE menuitem SET ordernumber=$thisordno WHERE itemid='$previtemid'";
			$result = @mysqli_query($db, $sql);
			$thisordno = $prevordno;
			if (time()-$starttime >= 180) {
				$error = "movecrash";
				break;
			}
		}
		@mysqli_query($db, "UPDATE user SET movelock='0' WHERE userid='$userid'");
	} else $error = "movelock";
	unset($thisordno);
	unset($prevordno);
	unset($thisitemid);
	unset($previtemid);
	unset($topordno);
}

if ($error) {
	echo "<p class=\"error\">".ERROR."<br>";
	if ($error==1) echo USERNAMEORPASSINCORRECT;
	elseif ($error==2) echo DATABASENAMEINCORRECT;
	elseif ($error=="extension") echo PICTUREMUSTBEGIFORJPG;
	elseif ($error=="keycodes") echo COULDNOTWRITETOPRODUCTSDIR;
	elseif ($error=="import") echo UPLOADDOESNOTAPPEARTOBEPRODUCTLIST;
	elseif ($error=="movelock") {
		echo COULDNOTMOVELOCKED;
		if ($userid == "1") echo " <a href=\"editcatalogue.php?unlock=movetotop\">".CLICKHERE."</a> ".TOUNLOCKIT;
	}
	elseif ($error=="moveup") echo COULDNOTMOVESORTORDERINCORRECT;
	elseif ($error=="movelock2") {
		echo COULDNOTMOVELOCKED2;
		if ($userid == "1") echo " <a href=\"editcatalogue.php?unlock=movetotop\">".CLICKHERE."</a> ".TOUNLOCKIT;
	}
	elseif ($error=="movecrash") echo COULDNOTMOVETIMEOUT;
	elseif ($error=="sortcrash") echo COULDNOTREORDERTIMEOUT;
	elseif ($error=="sortlock") {
		echo COULDNOTREORDERLOCKED;
		if ($userid == "1") echo " <a href=\"editcatalogue.php?unlock=movetotop\">".CLICKHERE."</a> ".TOUNLOCKIT;
	}
	echo "</p>";
}

// Generate Shopping Mall member list if needed...
if ($membershops && $userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
	$memberlist = "<option value=\"1\"";
	if ($owner == "1") $memberlist .= " selected";
	$memberlist .= ">".MAINSHOP;
	$result = @mysqli_query($db, "SELECT * FROM user WHERE userid>1 ORDER BY shopname");
	while ($row = @mysqli_fetch_array($result)) {
		$memberlist .= "<option value=\"{$row["userid"]}\"";
		if ($owner == $row["userid"]) $memberlist .= " selected";
		$memberlist .= ">{$row["shopname"]}";
	}
} else $memberlist = "";

// Generate language list...
$languagelist = "<select name=\"language\" onChange=\"document.langfilterform.submit()\"><option value=\"all\"";
if ($language == "all") $languagelist .= " selected";
$languagelist .= ">".ALL;
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
		if ($langmodule == $language) $languagelist .= " selected";
		$languagelist .= ">$langname</option>";
	}
}

// Show category name and description...
echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td width=\"180\">&nbsp;</td><td><table width=\"600\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" style=\"border: 1px solid #000000;\"><tr>";
if ($userid == "1") echo "
<td class=\"formtitle\" nowrap width=\"200\" align=\"center\"><form action=\"editmenu.php\" method=\"post\" style=\"margin-bottom: 0px;\" name=\"shopfilterform\">".OWNEDBY.": <select name=\"owner\" onChange=\"document.shopfilterform.submit()\" style=\"width: 109px\"><option value=\"any\""; if (!$owner || $owner == "all") echo " selected"; echo ">".ANYONE."</option>$memberlist</select><input type=\"hidden\" name=\"language\" value=\"$language\"></form></td>";
echo "<td class=\"formtitle\" nowrap width=\"200\" align=\"center\"><form action=\"editmenu.php\" method=\"post\" style=\"margin-bottom: 0px;\" name=\"langfilterform\">".LANGUAGE.": $languagelist<input type=\"hidden\" name=\"owner\" value=\"$owner\"></form></td>
<td align=\"right\"><form action=\"addmenuitem.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"owner\" value=\"$owner\"><input type=\"hidden\" name=\"language\" value=\"$language\"><input type=\"submit\" value=\"".NEWITEM."\" class=\"widebutton\"></form></td></tr></table>
<table class=\"categorytable\" width=\"600\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\"><tr><td>";

// Make sure the correct top menu item ID is used...
$result = @mysqli_query($db, "SELECT * FROM menuitem WHERE parentitemid = itemid AND (userid LIKE '$shopsearch'$condition) ORDER BY ordernumber LIMIT 1");
$topitemid = @mysqli_result($result, 0, "itemid");
$topitemordernumber = @mysqli_result($result, 0, "ordernumber");

// List menu items...
if (($membershops && $userid > 1) || $shopfilter == "member") $condition = " OR memberclone='1'";
else $condition = "";
$sql="SELECT * FROM menuitem WHERE parentitemid = itemid AND (userid LIKE '$shopsearch'$condition) AND (language LIKE '$langsearch' OR language='any') ORDER BY ordernumber";
$result = @mysqli_query($db, $sql);
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$menuitemcaption = @mysqli_result($result, $i, "caption");
	$menuitemid = @mysqli_result($result, $i, "itemid");
	$menuitemordernumber = @mysqli_result($result, $i, "ordernumber");
	$menuitemowner = @mysqli_result($result, $i, "userid");
	if ($menuitemowner != $userid && $userid != "1") $menuitemordernumber = "";
	$menuitemicon = "";
	if ($menuitemowner > "1") {
		$ownerresult = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='$menuitemowner'");
		$menuitemownername = @mysqli_result($ownerresult,0,"shopname");
		$menuitemicon = "<a href=\"editmember.php?memberid=$catowner\"><img src=\"images/icon_owner.gif\" alt=\"".OWNEDBY.": $catownername\" title=\"".OWNEDBY.": $catownername\" border=\"0\"></a>";
	}

	echo "<tr><td bgcolor=\"#F0F0F0\" valign=\"top\">$menuitemicon $menuitemcaption";
	$subsql="SELECT * FROM menuitem WHERE parentitemid = '$menuitemid' AND itemid != '$menuitemid' AND (userid LIKE '$shopsearch'$condition) ORDER BY ordernumber";
	$subresult = @mysqli_query($db, $subsql);
	if ($userid == "1" || $userid == $menuitemowner) {
		echo "<br><span class=\"smaller\"><a href=\"editmenuitem.php?item=$menuitemid&language=$language&owner=$owner\" class=\"smaller\">[".EDIT."]</a> <a href=\"editmenuitem.php?item=$menuitemid&remove=True&language=$language&owner=$owner\" class=\"smaller\">[".REMOVE."]</a>";
		if ($previousorderno || $menuitemordernumber != $topcatordernumber) echo "<br>";
		if ($previousorderno) {
			echo "<a href=\"editmenu.php?thisordno=$menuitemordernumber&prevordno=$previousorderno&thisitemid=$menuitemid&previtemid=$previousmenuitemid&itemmoveup=true&language=$language&owner=$owner\" class=\"smaller\">[".MOVEUP."]</a>";
			if (!@mysqli_num_rows($subresult)) echo " <a href=\"editmenu.php?thisitemid=$menuitemid&parentitemid=$previousmenuitemid&itemmoveunder=true&language=$language&owner=$owner\" class=\"smaller\">[".MOVEUNDER."]</a>";
		}
		//if ($menuitemordernumber != $topitemordernumber) echo " <a href=\"editmenu.php?thisordno=$menuitemordernumber&topordno=$topitemordernumber&thisitemid=$menuitemid&itemmovetop=true&language=$language&owner=$owner\" class=\"smaller\">[".MOVETOTOP."]</a>";
	}
	echo "</span></td></tr>";
	$previoussuborderno = "";
	for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
		$submenuitemcaption = @mysqli_result($subresult, $j, "caption");
		$submenuitemid = @mysqli_result($subresult, $j, "itemid");
		$submenuitemordernumber = @mysqli_result($subresult, $j, "ordernumber");
		$submenuitemowner = @mysqli_result($result, $i, "userid");
		echo "<tr><td bgcolor=\"#D0D0D0\"><img src=\"images/icon_subcategory.gif\" alt=\"".SUBITEMOF." $menuitemcaption\" title=\"".SUBCATEGORYOF." $menuitemcaption\">$submenuitemcaption";
		if ($userid == "1" || $userid == $submenuitemowner) {
			echo "<br><img src=\"images/10pxl.gif\"><a href=\"editmenuitem.php?item=$submenuitemid&language=$language&owner=$owner\" class=\"smaller\">[".EDIT."]</a> <a href=\"editmenuitem.php?item=$submenuitemid&remove=True&language=$language&owner=$owner\" class=\"smaller\">[".REMOVE."]";
			if ($previoussuborderno) echo "<br><img src=\"images/10pxl.gif\"><a href=\"editmenu.php?thisordno=$submenuitemordernumber&prevordno=$previoussuborderno&thisitemid=$submenuitemid&previtemid=$previoussubmenuitemid&itemmoveup=true&language=$language&owner=$owner\" class=\"smaller\">[".MOVEUP."]</a>";
		}
		echo "</span></td></tr>";
		$previoussuborderno = $submenuitemordernumber;
		$previoussubmenuitemid = $submenuitemid;
	}
	$previousorderno = $menuitemordernumber;
	$previousmenuitemid = $menuitemid;
}
echo "</table></td></tr></table>";

@mysqli_close($db);
echo "</td>
</tr>
</table><br><br>
$footer";
?>