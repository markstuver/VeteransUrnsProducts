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

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
$productname = @mysqli_result($result, 0, "name");
$result = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE customerid='$customerid'");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");

// Show log for this member...
if ($productid && $customerid) {
	echo "$header
        <div class=\"heading\">".MEMBERSHIPLOGFOR.": <b>$firstname $lastname</b>, product: <b><a href=\"listsubscr.php?productid=$productid\">$productname</a></b>...</div>
		<table width=\"600\" border=\"0\" cellspacing=\"1\" cellpadding=\"2\" align=\"center\" bgcolor=\"#D0D0D0\">
		<tr class=\"reporthead\"><td align=\"center\">".THEWORDDATE."</td><td align=\"center\">".IPNUMBER."</td><td align=\"center\">".ORDERID."</td><td align=\"center\">".REMAININGDAYS."</td></tr>";

	$result = @mysqli_query($db, "SELECT * FROM membershiplog WHERE customerid='$customerid' AND productid='$productid'");
	while ($row = @mysqli_fetch_array($result)) {
		$logindate = $row["logindate"];
		$ipnumber = $row["ipnumber"];
		$remainingdays = $row["remainingdays"];
		$password = $row["password"];
		$orderresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE (products LIKE '%b$productid"."a%' OR products LIKE '%b$productid"."d%') AND password='$password'");
		$orderid = @mysqli_result($orderresult,0,"orderid");
		echo "<tr class=\"reportline\"><td align=\"left\">$logindate</td><td align=\"left\">$ipnumber</td>
		<td align=\"left\"><a href=\"getreceipt.php?orderid=$orderid\" target=\"_blank\">$orderid</a></td><td align=\"center\">$remainingdays</td></tr>
		</table></td></tr></table><br>$footer";
	}
} else header ("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage");
?>