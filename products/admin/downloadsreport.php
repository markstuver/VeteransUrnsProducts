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

@set_time_limit(0);
include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";
include "ashopconstants.inc.php";

// Get the correct userid...
if ($userid == "1" && $memberid > 1) $user = $memberid;
else $user = $userid;

// Get context help for this page...
$contexthelppage = "salesreport";
include "help.inc.php";

// Validate orderid...
if (!empty($orderid)) {
	$checkorderid = str_replace("ws","",$orderid);
	if (!is_numeric($checkorderid)) $orderid = "";
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Show report in browser...	
if (!empty($orderid)) {
	// Get customer ID for this order..
	$result = @mysqli_query($db, "SELECT customerid, invoiceid FROM orders WHERE orderid='$orderid'");
	$customerid = @mysqli_result($result,0,"customerid");
	$invoiceid = @mysqli_result($result,0,"invoiceid");
	$sql="SELECT * FROM customer WHERE customerid='$customerid'";
	$result = @mysqli_query($db, "$sql");
	$customername = @mysqli_result($result, 0, "firstname")." ".@mysqli_result($result, 0, "lastname");
	$customeremail = @mysqli_result($result, 0, "email");
	$customerstring = "$customername, ".CUSTOMERID." $customerid ";
	$customerlevel = @mysqli_result($result,0, "level");
	if ($customerlevel > 0) $editcustomer = "edituser";
	else $editcustomer = "editcustomer";

	echo "$header
	<div class=\"heading\">".DOWNLOADSREPORT;
	if ($userid == "1") echo " <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a>";
	echo "</div><center>";
	echo "<span class=\"heading\"><font size=\"2\">".DOWNLOADSFOR." <a href=\"salesreport.php?showresult=true&reporttype=paid&generate=View&orderid=$orderid\">$invoiceid</a>, ".PLACEDBY." $customerstring <a href=\"$editcustomer.php?customerid=$customerid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>";
	if ($userid == "1") echo "&nbsp;<a href=\"$editcustomer.php?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a>";
	echo "</font></span><br /><br />";

	// Get order information from database...
	$sql = "SELECT DISTINCT(fileid) FROM downloadslog WHERE orderid='$orderid'";
	$result = @mysqli_query($db, "$sql");
	$rowcolor = "#E0E0E0";
	$affiliatecommission = 0.00;
	$paidaffiliatecommission = 0.00;
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$fileid = @mysqli_result($result, $i, "fileid");
		$downloadsresult = @mysqli_query($db, "SELECT downloads FROM orderdownloads WHERE fileid='$fileid' AND orderid='$orderid'");
		$downloads = @mysqli_result($downloadsresult, 0, "downloads");
		if (empty($downloads)) $downloads = 0;
		$subresult = @mysqli_query($db, "SELECT * FROM downloadslog WHERE orderid='$orderid' AND fileid='$fileid'");
		$downloadattempts = @mysqli_num_rows($subresult);
		if ($downloadattempts == 0 && $downloads > 0) $downloadattempts = UNKNOWN;
		$fileresult = @mysqli_query($db, "SELECT filename FROM productfiles WHERE fileid='$fileid'");
		$filename = @mysqli_result($fileresult,0,"filename");
		$rowcolor = "#C0C0C0";
		echo "<p><b>$filename</b> ".DOWNLOADED." <font size=\"3\">$downloads</font> ".TIMESTOTALATTEMPTS.": <font size=\"3\">$downloadattempts</font></p>";
		if (!empty($downloadattempts) && $downloadattempts != UNKNOWN) {
			echo "<table width=\"30%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\" align=\"center\" bgcolor=\"#C0C0C0\">
			<tr class=\"reporthead\"><td nowrap align=\"left\">".THEWORDDATE."</td>
			<td nowrap align=\"left\">".IPNUMBER."</td></tr>";
			for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
				$downloaderip = @mysqli_result($subresult, $j, "ip");
				$downloaddate = @mysqli_result($subresult, $j, "date");
				echo "<tr class=\"reportlinesm\" valign=\"top\" bgcolor=\"$rowcolor\"><td nowrap align=\"left\">$downloaddate</td><td align=\"left\">$downloaderip</td></tr>\n";
				if ($rowcolor == "#C0C0C0") $rowcolor = "#E0E0E0";
				else $rowcolor = "#C0C0C0";
			}
			echo "</table><br />";
		}
	}

	echo "<br></center>$footer";

} else header ("Location: salesadmin.php");
?>