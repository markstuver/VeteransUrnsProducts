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

if ($_GET["sesid"]) $sesid = $_GET["sesid"];
if ($_POST["sesid"]) $sesid = $_POST["sesid"];
if ($sesid && !isset($_COOKIE['sesid'])) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("sesid", $sesid);
}

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

if ($_SERVER['HTTPS'] != "on" && $_SERVER['HTTPS'] != "1") {
	echo "<html><head><title>".NOTASECURESERVER."</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\"><table width=\"100%\" border=\"0\" height=\"100%\"><tr><td align=\"center\" valign=\"middle\"><p><font face=\"Arial, Helvetica, sans-serif\" size=\"4\">".THISISNOTSECURE."</font></p><p><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".ONLYSSL."</font></p></center><br><br><br><br><br><br></td></tr></table></body></html>";
	exit;
}

// Get order info...
if (substr($orderid, 0, 2) == "ws") {
	$ws = "1";
	$orderid = substr($orderid, 2);
	$paymentinfotable = "wholesalepaymentinfo";
} else {
	$ws = "";
	$paymentinfotable = "paymentinfo";
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($activate && $orderid) {
	@mysqli_query($db, "DELETE FROM paymentinfo WHERE orderid=$orderid");
	@mysqli_close($db);
	if ($wholesale == "true") $orderid = "ws$orderid";
	if ($salesreport) header ("Location: activate.php?orderid=$orderid&salesreport=$salesreport");
	else if ($processmore == "true") header ("Location: activate.php?orderid=$orderid&processmore=true");
	else if (!$sw) header ("Location: activate.php?orderid=$orderid&tocustomer=true");
	else header ("Location: activate.php?orderid=$orderid&towscustomer=true");
	exit;
}

// Get customer information from database...
$result = @mysqli_query($db, "SELECT $paymentinfotable.orderid FROM $paymentinfotable, orders WHERE $paymentinfotable.orderid=orders.orderid AND orders.customerid='$customerid' ORDER BY $paymentinfotable.orderid DESC");
$numberoforders = @mysqli_num_rows($result);
$result = @mysqli_query($db, "SELECT * FROM $paymentinfotable WHERE orderid='$orderid'");
if ($ws) {
	$firstname = @mysqli_result($result, 0, "firstname");
	$lastname = @mysqli_result($result, 0, "lastname");
	$address = @mysqli_result($result, 0, "address");
	$zip = @mysqli_result($result, 0, "zip");
	$city = @mysqli_result($result, 0, "city");
	$state = @mysqli_result($result, 0, "state");
	$country = @mysqli_result($result, 0, "country");
}
$payoptionid = @mysqli_result($result, 0, "payoptionid");
$cardtype = @mysqli_result($result, 0, "cardtype");
if($cardtype == "VISA") $cardtype="VISA";
if($cardtype == "MASTERCARD") $cardtype="MasterCard";
if($cardtype == "AMEX") $cardtype="American Express";
if($cardtype == "DISCOVER") $cardtype="Discover";
$result = @mysqli_query($db, "SELECT secret FROM payoptions WHERE payoptionid='$payoptionid'");
$secret = @mysqli_result($result, 0, "secret");
$result = @mysqli_query($db, "SELECT DECODE(cardnumber,'$secret') AS ccnumber, DECODE(expdate,'$secret') AS ccexpdate, DECODE(seccode,'$secret') AS seccode FROM $paymentinfotable WHERE orderid='$orderid'");
$cardnumber = @mysqli_result($result, 0, "ccnumber");
$seccode = @mysqli_result($result, 0, "seccode");
$expdate = @mysqli_result($result, 0, "ccexpdate");
$result = @mysqli_query($db, "SELECT customerid FROM orders WHERE orderid='$orderid'");
$customerid = @mysqli_result($result, 0, "customerid");
$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
if (!$ws) {
	$firstname = @mysqli_result($result, 0, "firstname");
	$lastname = @mysqli_result($result, 0, "lastname");
	$address = @mysqli_result($result, 0, "address");
	$state = @mysqli_result($result, 0, "state");
	$zip = @mysqli_result($result, 0, "zip");
	$city = @mysqli_result($result, 0, "city");
	$country = @mysqli_result($result, 0, "country");
}
$email = @mysqli_result($result, 0, "email");
$phone = @mysqli_result($result, 0, "phone");

// Close database...
@mysqli_close($db);

echo "$header
<table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td colspan=\"2\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#ffffff\" size=\"4\"><b>".PROCESSPAYMENT."</b></td></tr>
</table>
</td></tr></table>
<center>";
if ($activated) echo "<p><font face=\"Arial, Helvetica, sans-serif\" color=\"#009000\"><b>".ORDER.": $activated ".HASBEENACTIVATED."</b></font></p>";
echo "
<p class=\"heading\" align=\"center\">".BILLINGINFOFORORDER.": <a href=\"$ashopurl/admin/salesreport.php?generate=true&orderid=$orderid\">$orderid</a></p>
    <form action=\"process.php\" method=\"post\"><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
    <table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CREDITCARDTYPE.":</font></td>
    <td><input type=text value=\"$cardtype\" size=40 readonly></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CREDITCARDNUMBER.":</font></td>
    <td><input type=text value=\"$cardnumber\" ";
	if ($seccode) echo "size=30";
	else echo "size=40";
	echo " readonly>";
	if ($seccode) echo " &nbsp;<input type=text value=\"$seccode\" size=7>";
	echo "</td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".EXPDATE.":</font></td>
    <td><input type=text value=\"$expdate\" size=40 readonly></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td><input type=text name=\"firstname\" value=\"$firstname\" size=40 readonly></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td><input type=text name=\"lastname\" value=\"$lastname\" size=40 readonly></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS.":</font></td>
    <td><input type=text name=\"address\" value=\"$address\" size=40 readonly></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CITY.":</font></td>
    <td><input type=text name=\"city\" value=\"$city\" size=40 readonly></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".STATEPROVINCE.":</font></td>
    <td><input type=text name=\"state\" value=\"$state\" size=40 readonly></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ZIP.":</font></td>
    <td><input type=text name=\"zip\" value=\"$zip\" size=40 readonly></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COUNTRY.":</font></td>
    <td><input type=text name=\"country\" value=\"$country\" size=40 readonly></td></tr>
    <tr><td></td><td align=\"right\">";
	if ($numberoforders > 1) echo "<input type=\"hidden\" name=\"processmore\" value=\"true\">";
	if ($salesreport) echo "<input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\">";
	if ($ws) echo "<input type=\"hidden\" name=\"wholesale\" value=\"true\">";
	echo "<input type=\"submit\" value=\"".ACTIVATE."\" name=\"activate\"></td></tr>
    </table></form>
	</font></center>
	$footer";
?>