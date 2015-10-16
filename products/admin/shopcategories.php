<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "checklicense.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/configure.inc.php";
// Get context help for this page...
$contexthelppage = "editflags";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Print product flags form...
if (!$add && !$shopcategories) {
  echo "$header
        <div class=\"heading\">".SHOPPINGMALLCATEGORIES."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <form action=\"shopcategories.php\" method=\"post\" name=\"newcategoryform\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"productid\" value=\"$productid\">
        <table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\" align=\"center\">
        <tr><td colspan=\"2\" class=\"formtitle\">".ADDNEWCATEGORY."</td></tr><tr><td class=\"formlabel\">".CAPTION.":</td><td class=\"formlabel\"><input type=\"text\" name=\"caption\" size=\"40\"></td>
		<td class=\"formlabel\"><input type=\"submit\" name=\"add\" value=\"".ADDCATEGORY."\"></td></tr></table></form>";


		$categoriesstring = "";
		$categories = @mysqli_query($db, "SELECT * FROM shopcategory ORDER BY name");
		while ($row = @mysqli_fetch_array($categories)) $categoriesstring .= "<option value=\"{$row["categoryid"]}\">{$row["name"]}";

		if (@mysqli_num_rows($categories)) echo "<form action=\"shopcategories.php\" method=\"post\"><table width=\"480\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\"><tr><td class=\"formtitle\" colspan=\"2\">".EXISTINGCATEGORIES.":</td></tr><tr><td width=\"20%\"><select name=\"shopcategories[]\" size=\"10\" multiple>$categoriesstring</select></td><td valign=\"top\"><input type=\"submit\" value=\"".DELETESELECTED."\" class=\"widebutton\"></td></tr></table></form>";
		
		echo "</td></tr></table>$footer";

// Store data in database...
} else if ($add) {
	$sql = "INSERT INTO shopcategory (name) VALUES ('$caption')";
	$result = @mysqli_query($db, $sql);
	header ("Location: shopcategories.php");
} else {
	if ($shopcategories) foreach ($shopcategories as $key => $value) {
		$sql = "DELETE FROM shopcategory WHERE categoryid='$value'";
		$result = @mysqli_query($db, $sql);
		$sql = "DELETE FROM membercategory WHERE categoryid='$value'";
		$result = @mysqli_query($db, $sql);
	}
	header ("Location: shopcategories.php");
}
?>