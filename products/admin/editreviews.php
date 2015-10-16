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
$contexthelppage = "editreviews";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get product information...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");

// Print shipping option form...
if (!$delete) {
  echo "$header
        <div class=\"heading\">".PRODUCTREVIEWS."</div> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td><span class=\"formtitle\">$productname</span><br>
		";

    $sql = "SELECT * FROM reviews WHERE productid='$productid' ORDER BY time";
	$result = @mysqli_query($db, $sql);
	if (@mysqli_num_rows($result)) {
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$reviewid = @mysqli_result($result, $i, "reviewid");
			$rating = @mysqli_result($result, $i, "rating");
			$comment = @mysqli_result($result, $i, "comment");
			$customerid = @mysqli_result($result, $i, "customerid");
			$subresult = @mysqli_query($db, "SELECT firstname,lastname FROM customer WHERE customerid='$customerid'");
			$firstname = @mysqli_result($subresult, 0, "firstname");
			$lastname = @mysqli_result($subresult, 0, "lastname");
			echo "<table width=\"440\" style=\"border: 1px solid #000;\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\" align=\"center\"><tr><td width=\"100%\">
			<form action=\"editreviews.php\" method=\"post\" style=\"margin-bottom: 0px;\">
			<input type=\"hidden\" name=\"reviewid\" value=\"$reviewid\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">
			<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
			<tr><td class=\"formlabel\" width=\"20\">".BY.":</td><td class=\"formlabel\"><a href=\"editcustomer.php?customerid=$customerid\"><img src=\"images/icon_profile.gif\" border=\"0\" alt=\"$customerid\" title=\"$customerid\"></a> $firstname $lastname</td></tr>
			<tr><td class=\"formlabel\">".RATING.":</td><td class=\"formlabel\">$rating</td></tr>
			<tr><td class=\"formlabel\">".REVIEW.":</td><td class=\"formlabel\">$comment</td></tr>
			<tr><td>&nbsp;</td><td class=\"formtitle\" align=\"right\"><input type=\"submit\" name=\"delete\" value=\"".REMOVE."\"></td></tr>
			</table>
			</form>
			</td></tr></table><table width=\"540\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\"><tr><td bgcolor=\"#FFFFFF\"><img src=\"images/invisible.gif\" height=\"4\" width=\"2\"></td></tr></table>";
		}

		echo "<table width=\"440\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\" align=\"center\"><tr><td align=\"right\"><br><input type=\"button\" value=\"".FINISH."\" onClick=\"document.location.href='pagegenerator.php?productid=$productid&cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"></td></tr></table>";
	}
	echo "</td></tr></table>$footer";


// Store data in database...
} else if ($delete) {
	$sql = "DELETE FROM reviews WHERE reviewid='$reviewid'";
	$result = @mysqli_query($db, $sql);
	header ("Location: editreviews.php?cat=$cat&pid=$pid&search=$search&productid=$productid&resultpage=$resultpage");
}
?>