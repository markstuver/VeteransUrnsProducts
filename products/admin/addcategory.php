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
include "language/$adminlang/editcategory.inc.php";
// Get context help for this page...
$contexthelppage = "addcategory";
include "help.inc.php";

// Set the right member user ID...
if ($userid == 1) $user = 1;
else {
	if (!$membershops) {
		header("Location: index.php");
		exit;
	} else $user = $userid;
}

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

   if ($cat) {
	   $sql="SELECT * FROM category WHERE categoryid = '$cat'";
	   $result = @mysqli_query($db,$sql);
	   $selectedcategoryname = @mysqli_result($result,0,"name");
	   $parentcategory = @mysqli_result($result,0,"parentcategoryid");
	   $grandparentcategory = @mysqli_result($result,0,"grandparentcategoryid");
	   $parentowner = @mysqli_result($result,0,"userid");
   }

   // Generate Digital Mall member list if needed...
   if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
	   $memberlist = "<select name=\"memberid\" onChange=\"javascript: if(categoryform.memberid.value != '1') { categoryform.memberclone.checked=false; categoryform.memberclone.disabled=true; } else categoryform.memberclone.disabled=false;\"><option value=\"1\">".ADMINISTRATOR;
	   $result = @mysqli_query($db,"SELECT * FROM user WHERE userid>1 ORDER BY shopname");
	   while ($row = @mysqli_fetch_array($result)) {
		   $memberlist .= "<option value=\"{$row["userid"]}\"";
		   if ($row["userid"] == $parentowner) $memberlist .= " selected";
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
		foreach ($languages as $langmodule=>$langname) $languagelist .= "<option value=\"$langmodule\">$langname</option>";
	}


if (!$name && !$description) {
        echo "$header
        <div class=\"heading\">".ADDPRODUCTCATEGORY." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <form action=\"addcategory.php\" method=\"post\" name=\"categoryform\" enctype=\"multipart/form-data\">
		<table width=\"520\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">";
		if ($cat && ($parentcategory == $cat || $grandparentcategory == $cat)) echo "<tr><td align=\"right\" class=\"formlabel\">".TYPE.":</td><td class=\"formlabel\"><input type=\"radio\" name=\"cat\" value=\"0\" checked onClick=\"javascript:if(categoryform.cat.value='0') categoryform.nlanguage.disabled=false;\"> ".TOPCATEGORY." <input type=\"radio\" name=\"cat\" value=\"$cat\" onClick=\"javascript:if(categoryform.cat.value='$cat') categoryform.nlanguage.disabled=true;\"> ".SUBCATEGORYTO." $selectedcategoryname</td></tr>";
        echo "<tr><td align=\"right\" class=\"formlabel\">".NAME.":</td><td><input type=\"text\" name=\"name\" size=\"35\"></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".OWNER.":</td><td>$memberlist</td></tr>
			<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"memberclone\"> ".MAKETHISCATEGORYAVAILABLE;
		}
		echo "<tr><td align=\"right\" class=\"formlabel\"><font face=\"Arial, Helvetica, sans-serif\">".LANGUAGE.":</font></td><td>$languagelist</td></tr>";
		/*
		<tr><td align=\"right\" class=\"formlabel\"><font face=\"Arial, Helvetica, sans-serif\">".THUMBNAIL.":<br><span class=\"sm\">".GIFORJPG."</span></font></td><td><input type=\"file\" name=\"imgfile\"></td></tr>
		*/
		echo "
		<tr><td align=\"right\" class=\"formlabel\"><font face=\"Arial, Helvetica, sans-serif\">".PRODUCTLAYOUT.":</font></td><td>
		<select name=\"nlayout\"><option value=\"0\">".USEDEFAULT."</option><option value=\"1\">".STANDARD."</option><option value=\"2\">".CONDENSED."</option><option value=\"3\">".LINKS."</option></select>
		</td></tr>
        <tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".DESCRIPTION.":</td><td class=\"formlabel\"><textarea name=\"description\" cols=\"45\" rows=\"3\"></textarea></td></tr>
        <tr><td>&nbsp;</td><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></td></tr></table>";
		echo $footer;
} else {
   if ($memberid) $catuser = $memberid;
   else $catuser = $user;
   if ($memberclone == "on") $memberclone = 1;
   else $memberclone = 0;
   $sql="INSERT INTO category (name,description,userid,language,memberclone,productlayout) VALUES ('$name','$description','$catuser','$nlanguage','$memberclone','$nlayout')";
   $result = @mysqli_query($db,$sql);
   $categoryid = @mysqli_insert_id($db);
   if (!$cat) {
	   $topcat = $categoryid;
	   $midcat = $categoryid;
   } else if ($parentcategory == $cat && $grandparentcategory == $cat) {
	   $topcat = $cat;
	   $midcat = $categoryid;
   } else {
	   $topcat = $grandparentcategory;
	   $midcat = $cat;
   }
   if (empty($nlanguage)) {
	   $result = @mysqli_query($db,"SELECT language FROM category WHERE grandparentcategoryid='$topcat'");
	   $nlanguage = @mysqli_result($result,0,"language");
	   if (empty($nlanguage)) $nlanguage = "any";
	   $sql="UPDATE category SET grandparentcategoryid='$topcat', parentcategoryid='$midcat', ordernumber='$categoryid', userid='$catuser', language='$nlanguage' WHERE categoryid='$categoryid'";
   } else $sql="UPDATE category SET grandparentcategoryid='$topcat', parentcategoryid='$midcat', ordernumber='$categoryid', userid='$catuser' WHERE categoryid='$categoryid'";
   $result = @mysqli_query($db,$sql);
   header("Location: editcatalogue.php?cat=$categoryid");
}
?>