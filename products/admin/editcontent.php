<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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
include "language/$adminlang/editcontent.inc.php";
// Get context help for this page...
$contexthelppage = "editcontent";
include "help.inc.php";

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productowner = @mysqli_result($result, 0, "userid");
$productdescr = @mysqli_result($result, 0, "description");
$productstatus = @mysqli_result($result, 0, "active");
$productlink = @mysqli_result($result, 0, "detailsurl");
$productlanguage = @mysqli_result($result, 0, "language");
if ($productstatus == 1) $productstatus = " checked";
else $productstatus = "";
$productwsstatus = @mysqli_result($result, 0, "wholesaleactive");
if ($productwsstatus == 1) $productwsstatus = " checked";
else $productwsstatus = "";
$productinmainshop = @mysqli_result($result, 0, "inmainshop");
if ($productinmainshop) $inmainshop = " checked";
else $inmainshop = "";

// Handle removal of the product...
if ($remove && $productid) {
	if ($yes) {
       $sql="DELETE FROM product WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM productcategory WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
	   header("Location: editcatalogue.php?cat=$cat&search=$search&resultpage=$resultpage&pid=$pid");
    }
	elseif ($no) header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
	else echo "$header
        <table cellpadding=\"10\" align=\"center\"><tr><td><p class=\"heading\" align=\"center\">".REMOVECONTENT."</p>
        <p class=\"warning\">".THISWILLCOMPLETELYREMOVE."</p>
		<form action=\"editcontent.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form></td></tr></table>
        $footer";
} 

// Handle editing of the product...
elseif ($edit && $productid) {
  // Show edit form...
  if (!$edited) {
	  echo "$header";
	  if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js") && (empty($productlink) || !empty($productdescr))) {
		  echo "
<script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>
";
	  }
	echo "
        <div class=\"heading\">".EDIT." ";
		if ($productname == "AShopFirstPage") echo WELCOMEMESSAGE;
		else if ($productname == "AShopFirstPageMobile") echo WELCOMEMESSAGEMOBILE;
		else if ($productname == "AShopAboutPage") echo ABOUTPAGE;
		else if ($productname == "AShopTermsPage") echo TERMSPAGE;
		else if ($productname == "AShopPrivacyPage") echo PRIVACYPAGE;
		else if ($productlink && empty($productdescr)) echo EXTERNALLINK;
		else echo CONTENT;
		echo "</div>
		<table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\">
        <form action=\"editcontent.php\" method=\"post\" name=\"productform\">
		<table width=\"700\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">";
		if ($cat != "pages") {
			echo "<tr><td align=\"right\" class=\"formlabel\" width=\"120\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".CATALOGSTATUS.":</td><td class=\"formlabel\" align=\"left\" width=\"580\">";
			if ($userid == "1") echo "<input type=\"checkbox\" name=\"active\"$productstatus> ".ACTIVE;
			else {
				if ($productstatus) echo ACTIVE;
				else echo INACTIVE;
			}
			if($wholesalecatalog && $userid == "1") echo " (".RETAIL.") <input type=\"checkbox\" name=\"wholesale\"$productwsstatus> ".WHOLESALE;
			echo "</td></tr>";
		} else {
		   // Generate Digital Mall member list if needed...
		   if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			   $memberlist = "<select name=\"memberid\"><option value=\"1\">".ADMINISTRATOR;
			   $result = @mysqli_query($db, "SELECT * FROM user WHERE userid>1 ORDER BY shopname");
			   while ($row = @mysqli_fetch_array($result)) {
				   $memberlist .= "<option value=\"{$row["userid"]}\"";
				   if ($row["userid"] == $productowner) $memberlist .= " selected";
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
				   if ($productlanguage == $langmodule) $languagelist .= " selected";
				   $languagelist .= ">$langname</option>";
			   }
		   }
		   if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") echo "<tr><td align=\"right\" class=\"formlabel\" width=\"120\">".OWNER.":</td><td align=\"left\" width=\"580\">$memberlist</td></tr>";
		   echo "<tr><td align=\"right\" class=\"formlabel\" width=\"120\">".LANGUAGE.":</td><td align=\"left\" width=\"580\">$languagelist</td></tr>";
		   if ($productname != "AShopFirstPage" && $productname != "AShopFirstPageMobile" && $productname != "AShopAboutPage" && $productname != "AShopTermsPage" && $productname != "AShopPrivacyPage") echo "
		   <tr id=\"caption\"><td align=\"right\" class=\"formlabel\" width=\"120\">".CAPTION.":</td><td align=\"left\" width=\"580\"><input type=\"text\" name=\"ncaption\" size=\"70\" value=\"$productname\"></td></tr>";
		   if ($productlink && empty($productdescr)) echo "
		   <tr><td align=\"right\" class=\"formlabel\" width=\"120\">".URL.":</td><td align=\"left\" width=\"580\"><input type=\"text\" name=\"nurl\" size=\"70\" value=\"$productlink\"></td></tr>	   
		   ";
		}
		if (empty($productlink) || !empty($productdescr)) {
			echo "<tr>";
			if (!is_dir("$ashoppath/admin/ckeditor") || !file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) echo "<td align=\"right\" class=\"formlabel\" valign=\"top\"><br><br><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> ".DESCRIPTION.":</td><td align=\"left\"><br>";
			else echo "<td colspan=\"2\" align=\"center\"><br>";
			echo "<textarea name=\"description\" class=\"ckeditor\" cols=\"";
			if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) echo "80";
			else echo "67";
			echo "\" rows=\"23\">".htmlentities(stripslashes($productdescr), ENT_QUOTES)."</textarea></td></tr>";
		}
		echo "
		<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" value=\"".SUBMIT."\">&nbsp;&nbsp;&nbsp;&nbsp;</td></tr></table></form>";
		echo "</td></tr></table>$footer";
  }
  else {
	$sql="UPDATE product SET description='$description'";
    if ($userid == "1") {
		if ($active == "on") $sql.=", active=1";
		else $sql.=", active=0";
		if ($wholesale == "on") $sql.=", wholesaleactive=1";
		else $sql.=", wholesaleactive=0";
	}
	if (!empty($nurl)) $sql .= ", detailsurl='$nurl'";
	if (!empty($ncaption)) $sql .= ", name='$ncaption'";
	if (!isset($memberid) || !is_numeric($memberid)) $memberid = $userid;
	$sql .= ", userid='$memberid', language='$nlanguage' WHERE productid=$productid";
    $result = @mysqli_query($db, $sql);
	
	if ($error) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
    else header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>