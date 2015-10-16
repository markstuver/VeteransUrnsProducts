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
include "language/$adminlang/customers.inc.php";

if ($userid != "1") {
	header("Location: $ashopurl/admin/index.php");
	exit;
}

// Get order info...
if ($ws == "1") {
	$orderid = substr($orderid, 2);
	$paymentinfotable = "wholesalepaymentinfo";
} else $paymentinfotable = "paymentinfo";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get customer information from database...
$orderresult = @mysqli_query($db, "SELECT $paymentinfotable.orderid FROM $paymentinfotable, orders WHERE $paymentinfotable.orderid=orders.orderid AND orders.customerid='$customerid' ORDER BY $paymentinfotable.orderid DESC");
$numberoforders = @mysqli_num_rows($orderresult);
$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");

echo "$header
<div class=\"heading\">".CREDITCARDORDERSBY." $firstname $lastname, ";
if ($ws == "1") echo WHOLESALE." ";
echo CUSTOMERID." $customerid <a href=\"salesreport.php?customerid=$customerid&generate=true";
if ($ws == "1") echo "&reporttype=wholesale";
echo "\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $customerid\" title=\"".SALESHISTORYFOR." $customerid\" border=\"0\"></a>&nbsp;<a href=\"";
if ($ws == "1") echo "edituser.php";
else echo "editcustomer.php";
echo "?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a></div>
<table width=\"70%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\" align=\"center\" bgcolor=\"#C0C0C0\">
<tr class=\"reportheadsm\"><td width=\"70\" nowrap>".THEWORDDATE."</td><td width=\"70\" nowrap>".ORDERID."</td><td>".PRODUCTS."</td><td width=\"70\" align=\"center\">".AMOUNT."</td><td width=\"30\" align=\"center\" nowrap></td></tr>";
while ($row = @mysqli_fetch_array($orderresult)) {
	$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='{$row['orderid']}'");
	$orderrow = @mysqli_fetch_array($result);
	$displaydescr = str_replace(",", "<br>", $orderrow['description']);
	$date = explode(" ",$orderrow['date']);
	$orderid = $orderrow['orderid'];
	echo "<tr class=\"reportlinesm\" valign=\"top\"><td width=\"70\" nowrap>{$date[0]}</td><td width=\"70\">$orderid</td><td>$displaydescr</td><td width=\"70\"  align=\"right\">".number_format($orderrow['price'],$showdecimals,$decimalchar,$thousandchar)."</td><td width=\"30\" align=\"center\"><a href=\"$ashopsurl/admin/process.php?sesid=$sesid&orderid=";
	if ($ws == "1") echo "ws";
	echo "$orderid\"><img src=\"images/icon_process.gif\" alt=\"".VIEWCREDITCARDANDACTIVATE."\" title=\"".VIEWCREDITCARDANDACTIVATE."\" border=\"0\"></a></td></tr>";
}
echo "</table></font></center>$footer";
// Close database...
@mysqli_close($db);
?>