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
include "ashopconstants.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";
// Get context help for this page...
$contexthelppage = "editproduct";
include "help.inc.php";

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productcopyof = @mysqli_result($result, 0, "copyof");
$productname = str_replace("\"", "&quot;", $productname);

if (!$edited) {
	$subscriptiondir = @mysqli_result($result, 0, "subscriptiondir");
	$protectedurl = @mysqli_result($result, 0, "protectedurl");
	$length = @mysqli_result($result, 0, "length");
} else {
	// Check if the submitted directory is writeable and add protection...
	$subscriptiondir = str_replace(".","",$subscriptiondir);
	if (!empty($subscriptiondir)) {
		if (!empty($probotpath) && file_exists("$probotpath/data/groups/$subscriptiondir/pass.txt")) $editerror = "";
		else if (!is_writeable("$ashoppath/$subscriptiondir")) {
			$editerror = "notwriteable";
			$edited = "";
		} else {
			if (!file_exists("$ashoppath/$subscriptiondir/.htaccess") && !file_exists("$ashoppath/$subscriptiondir/.htpasswd")) {
				$editerror = "";
				$fp = @fopen ("$ashoppath/$subscriptiondir/.htaccess", "w");
				if ($fp) {
					fwrite($fp, "AuthUserFile $ashoppath/$subscriptiondir/.htpasswd\n");
					fwrite($fp, "AuthName \"Member's Area\"\n");
					fwrite($fp, "AuthType Basic\n");
					fwrite($fp, "\n");
					fwrite($fp, "require valid-user\n");
					fwrite($fp, "ErrorDocument 401 \"Authorisation Required\"");
					fclose ($fp);
					$fp = @fopen ("$ashoppath/$subscriptiondir/.htpasswd", "w");
					fclose ($fp);
					@chmod("$ashoppath/$subscriptiondir/.htpasswd", 0777);
				} else $editerror = "nohtaccess";
			}
			if (!file_exists("$ashoppath/$subscriptiondir/track.php")) {
				$fp = @fopen ("$ashoppath/$subscriptiondir/track.php", "w");
				if ($fp) {
					fwrite($fp, "<?php\n");
					fwrite($fp, "\$ashoppath = \"$ashoppath\";\n");
					fwrite($fp, "include \"\$ashoppath/admin/tracksubscr.php\";\n");
					fwrite($fp, "?>");
					fclose ($fp);
					@chmod("$ashoppath/$subscriptiondir/track.php", 0755);
				}
			}
		}
	}
}

// Handle editing of the product...
if ($edit && $productid) {

  // Make sure special characters are handled properly...
  $name = str_replace("\"", "&quot;", $name);

  // Show edit form...
  if (!$edited) {

	  echo "$header
        <div class=\"heading\">".EDITMEMBERSHIP." </div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>";
	  if ($editerror) {
		  echo "<p class=\"error\">".ERROR."<br>";
		  if ($editerror=="notwriteable") echo PROTECTEDDIRNOTWRITEABLE."</p>";
		  else if ($editerror=="nohtaccess") echo COULDNOTCREATEHTFILES."</p>";
	  }
	  echo "
	  <form action=\"editsubscr.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">
	  <table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
	  <tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11a\" align=\"absmiddle\" onclick=\"return overlib('$tip11a');\" onmouseout=\"return nd();\"></a> <a href=\"$help11a\" class=\"helpnav2\" target=\"_blank\">".PROTECTEDDIRECTORY.":</td><td align=\"left\"><input type=\"text\" name=\"subscriptiondir\" value=\"$subscriptiondir\"></td></tr>
	  <tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11b','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11b\" align=\"absmiddle\" onclick=\"return overlib('$tip11b');\" onmouseout=\"return nd();\"></a> <a href=\"$help11b\" class=\"helpnav2\" target=\"_blank\">".PROTECTEDURL.":</a></td><td align=\"left\"><input type=\"text\" name=\"protectedurl\" value=\"$protectedurl\"></td></tr>
	  <tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12a\" align=\"absmiddle\" onclick=\"return overlib('$tip12a');\" onmouseout=\"return nd();\"></a> <a href=\"$help12a\" class=\"helpnav2\" target=\"_blank\">".SUBSCRIPTIONLENGTH.":</a></td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"length\" size=\"10\" value=\"$length\"> ".DAYS." <span class=\"sm\">".ZEROUNLIMITED."</span></td></tr>
	  <tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"hidden\" name=\"copyof\" value=\"$productcopyof\"><input type=\"button\" value=\"".CANCEL."\" onClick=\"document.location.href='editcatalogue.php?cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"> <input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></td></tr></table>$footer";
  }
  else {
	if (!$subscriptiondir) $sql = "UPDATE product SET prodtype='', subscriptiondir='', protectedurl='', length='$length'";
	else {
		$sql = "UPDATE product SET prodtype='subscription', ";
		if ($length) $sql .= "length='$length', ";
		else $sql .= "length='', ";
		if ($protectedurl) $sql .= "protectedurl='$protectedurl', ";
		else $sql .= "protectedurl='', ";
		$sql .= "subscriptiondir='$subscriptiondir'";
	}
	if ($copyof) $sql.=" WHERE productid='$productid' OR copyof='$productid' OR productid='$copyof' OR copyof='$copyof'";
	else $sql.=" WHERE productid='$productid' OR copyof='$productid'";
    $result = @mysqli_query($db, $sql);

    if ($error) header ("Location: editcatalogue.php?cat=$cat&error=$error");
    else header("Location: editcatalogue.php?cat=$cat");
  }
}
?>