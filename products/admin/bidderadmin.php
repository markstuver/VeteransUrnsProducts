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

error_reporting(E_ALL ^ E_NOTICE);

include "config.inc.php";
include "ashopfunc.inc.php";
if ($noinactivitycheck == "false") {
	if ($msg) $noinactivitycheck = "true";
	else $noinactivitycheck = "false";
}

include "checklogin.inc.php";
include "ashopconstants.inc.php";

// Validate variables...
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($admindisplayitems)) unset($admindisplayitems);
else {
	$c_admindisplayitems = $admindisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_admindisplayitems","$admindisplayitems");
}
if (!is_numeric($c_admindisplayitems)) unset($c_admindisplayitems);
$namefilter = str_replace("<","",$namefilter);
$namefilter = str_replace(">","",$namefilter);
$emailfilter = str_replace("<","",$emailfilter);
$emailfilter = str_replace(">","",$emailfilter);

if ($userid != "1") {
	header("Location: salesreport.php");
	exit;
}

include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";

// Get context help for this page...
$contexthelppage = "salesadmin";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if penny auctions are used...
$bidderresult = @mysqli_query($db, "SELECT productid FROM floatingprice LIMIT 1");
if (!@mysqli_num_rows($bidderresult)) {
	header("Location: salesadmin.php");
	exit;
}

echo "$header
<div class=\"heading\">".CUSTOMERSANDMESSAGING." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><center>";
if ($wholesalecatalog) {
	if (file_exists("$ashoppath/emerchant/quote.php")) {
		echo "<span class=\"formtitle\">[<a href=\"salesadmin.php\" class=\"sm\">".ALLCUSTOMERS."</a>] [<a href=\"wssalesadmin.php\" class=\"sm\">".WHOLESALECUSTOMERS."</a>] [<a href=\"salesadmin.php?recurring=true\" class=\"sm\">".RECURRINGBILLINGCUSTOMERS."</a>] ".AUCTIONBIDDERS."</span><br>";
	} else echo "<span class=\"formtitle\">[<a href=\"salesadmin.php\" class=\"sm\">".ALLCUSTOMERS."</a>] [<a href=\"wssalesadmin.php\" class=\"sm\">".WHOLESALECUSTOMERS."</a>] ".AUCTIONBIDDERS."</span><br>";
} else {
	if (file_exists("$ashoppath/emerchant/quote.php")) {
		echo "<span class=\"formtitle\">[<a href=\"salesadmin.php\" class=\"sm\">".ALLCUSTOMERS."</a>] [<a href=\"salesadmin.php?recurring=true\" class=\"sm\">".RECURRINGBILLINGCUSTOMERS."</a>] ".AUCTIONBIDDERS."</span><br>";
	} else echo "<br>";
}
echo "<br>

<table width=\"80%\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\"><td align=\"left\" width=\"27%\">".IDNAME."</td><td align=\"left\" width=\"27%\">".EMAIL."</td><td align=\"left\" width=\"27%\">".SCREENNAME."</td><td align=\"center\">".BIDS."</td><td width=\"70\" align=\"center\">".ACTION."</td></tr>";

// Get bidder information from database...
$sql = "SELECT * FROM pricebidder";
$result = @mysqli_query($db, $sql);
$numberofrows = intval(@mysqli_num_rows($result));
if (!$admindisplayitems) {
	if ($c_admindisplayitems) $admindisplayitems = $c_admindisplayitems;
	else $admindisplayitems = 10;
}
$numberofpages = ceil($numberofrows/$admindisplayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $admindisplayitems;
else {
	$resultpage = 1;
	$startrow = 0;
}
$startpage = $resultpage - 9;
if ($numberofpages - $resultpage < 10) {
	$pagesleft = $numberofpages - $resultpage;
	$startpage = $startpage - (10 - $pagesleft);
}
if ($startpage < 1) $startpage = 1;
$stoprow = $startrow + $admindisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$customerid = $row["customerid"];
	$screenname = $row["screenname"];
	if (!$screenname) $screenname = "Unknown";
	$bids = $row["numberofbids"];
	$bidderid = $row["bidderid"];
	if (!$bids) $bids = 0;
	if ($customerid) {
		$customerresult = @mysqli_query($db, "SELECT firstname,lastname,email FROM customer WHERE customerid='$customerid'");
		$customerrow = @mysqli_fetch_array($customerresult);
		$firstname = $customerrow["firstname"];
		$lastname = $customerrow["lastname"];
		$email = $customerrow["email"];
	} else {
		$firstname = "";
		$lastname = "";
		$email = "";
	}
	$thisrow++;
	echo "<tr class=\"reportline\">
	<td nowrap align=\"left\">";
	if ($customerid && $email) echo "<a href=\"editcustomer.php?customerid=$customerid\">$customerid</a>, $firstname $lastname";
	else echo "&nbsp;";
	echo "</td><td align=\"left\"><a href=\"mailto:$email\">$email</a>";
	echo "</td><td align=\"left\">$screenname</td><td align=\"center\">$bids</a><td width=\"70\" nowrap align=\"center\"><a href=\"editbidder.php?bidderid=$bidderid\"><img src=\"images/icon_profile.gif\" alt=\"".EDITBIDDER." $bidderid\" title=\"".EDITBIDDER." $bidderid\" border=\"0\"></a>&nbsp;<a href=\"editbidder.php?bidderid=$bidderid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEBIDDER." $bidderid ".FROMDB."\" title=\"".DELETEBIDDER." $bidderid ".FROMDB."\" border=\"0\"></a>";
	echo "</td></tr>";
}

echo "</table>\n";
if ($numberofrows > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"bidderadmin.php?resultpage=$previouspage&admindisplayitems=$admindisplayitems\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"bidderadmin.php?resultpage=$i&admindisplayitems=$admindisplayitems\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"bidderadmin.php?resultpage=$nextpage&admindisplayitems=$admindisplayitems\"><b>".NEXTPAGE."</b></a>>>";
		}
	}
	echo " ".DISPLAY.": <select name=\"admindisplayitems\" onChange=\"document.location.href='bidderadmin.php?resultpage=$resultpage&admindisplayitems='+document.admindisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$numberofrows\">".ALL."</option></select> ".CUSTOMERS2."</td></tr></table>
	";
}
	
echo "</p></td></tr></table></center>$footer";
?>