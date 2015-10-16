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
include "language/$adminlang/editproduct.inc.php";

// Get context help for this page...
$contexthelppage = "editupsell";
include "help.inc.php";

// Get information about the products from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if ($editrelation) {
	$result = @mysqli_query($db, "SELECT * FROM relatedproducts WHERE relationid='$editrelation'");
	$row = @mysqli_fetch_array($result);
	$productid = $row["productid"];
	$relatewith = $row["relatedproductid"];
	$priority = $row["priority"];
}
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid = $productid");
$productname = @mysqli_result($result, 0, "name");
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid = $relatewith");
$relatewithname = @mysqli_result($result, 0, "name");

// Handle editing of the relation...
if ($relatewith && $productid) {
  // Show edit form...
  if (!$edited) {
	  echo "$header
        <div class=\"heading\">".UPSELLPRODUCT."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p class=\"formlabel\">".OFFER.": <b>$relatewithname</b> ".TOCUSTOMERSWHOBUY.": <b>$productname</b></p>
		<form action=\"editupsell.php\" method=\"post\">
		<p class=\"formlabel\" align=\"center\">".PRIORITY.": <select name=\"npriority\">";
		for ($p = 0; $p < 10; $p++) {
			echo "<option value=\"$p\"";
			if ($priority == $p) echo " selected";
			echo ">$p";
		}
		echo "
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"relatewith\" value=\"$relatewith\"><input type=\"hidden\" name=\"editrelation\" value=\"$editrelation\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">
		<input type=\"hidden\" name=\"edited\" value=\"True\"></form></td></tr></table>
        $footer";
  }
  else {
	  if ($yes) {
		  if ($editrelation) $sql="UPDATE relatedproducts SET priority='$npriority' WHERE relationid='$editrelation'";
		  else $sql="INSERT INTO relatedproducts (productid, relatedproductid, priority) VALUES ('$productid','$relatewith','$npriority')";
		  $result = @mysqli_query($db, $sql);
	  } else if ($no && $editrelation) @mysqli_query($db, "DELETE FROM relatedproducts WHERE relationid='$editrelation'");

	  if ($error) header ("Location: editcatalogue.php?cancelrelate=true&cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
	  else header("Location: editcatalogue.php?cancelrelate=true&cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>