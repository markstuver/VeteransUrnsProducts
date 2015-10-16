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
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/affiliates.inc.php";

// Validate variables...
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($partiesdisplayitems)) unset($partiesdisplayitems);
else {
	$c_partiesdisplayitems = $partiesdisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_partiesdisplayitems","$partiesdisplayitems");
}
if (!is_numeric($c_partiesdisplayitems)) unset($c_partiesdisplayitems);

// Open database...
$db = mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
if (!empty($affiliateid) && is_numeric($affiliateid)) {
	$sql="SELECT * FROM affiliate WHERE affiliateid='$affiliateid'";
	$result = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result) == 0) {
		echo "<html><head><title>".ERRORNOSUCHAFFILIATE."</title></head>
		<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	     <tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
 		 <tr align=\"center\"><td> <img src=\"../images/logo.gif\"><br><hr size=\"0\" noshade>
		 </td></tr></table><p><font face=\"Arial, Helvetica, sans-serif\" size=\"5\">".ERRORNOSUCHAFFILIATE."</p>
		 <p><a href=\"javascript:history.back()\">".TRYAGAIN."</a></p></font></td></tr></table></body></html>";
		exit;
	}
// Get customer information from database...
} else if (!empty($customerid) && is_numeric($customerid)) {
	$sql="SELECT * FROM customer WHERE customerid='$customerid'";
	$result = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result) == 0) {
		echo "<html><head><title>".ERRORNOSUCHCUSTOMER."</title></head>
		<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\" vlink=\"#000000\" alink=\"#000000\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	     <tr bordercolor=\"#000000\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
 		 <tr align=\"center\"><td> <img src=\"../images/logo.gif\"><br><hr size=\"0\" noshade>
		 </td></tr></table><p><font face=\"Arial, Helvetica, sans-serif\" size=\"5\">".ERRORNOSUCHCUSTOMER."</p>
		 <p><a href=\"javascript:history.back()\">".TRYAGAIN."</a></p></font></td></tr></table></body></html>";
		exit;
	}
}

// Store affiliate information in variables...
$firstname = mysqli_result($result, 0, "firstname");
$lastname = mysqli_result($result, 0, "lastname");

// Approve a party...
if (!empty($approve) && is_numeric($approve)) @mysqli_query($db, "UPDATE party SET approved='1' WHERE partyid='$approve'");

// End a party...
if (!empty($end) && is_numeric($end)) {
	$checkparty = @mysqli_query($db, "SELECT * FROM party WHERE partyid='$end'");
	$approved = @mysqli_result($checkparty, 0, "approved");
	$alreadyended = @mysqli_result($checkparty, 0, "ended");
	if ($approved == "1" && $alreadyended != "1") {
		@mysqli_query($db, "UPDATE party SET ended='1' WHERE partyid='$end'");
		$partyresult = 0;
		$ordersresult = @mysqli_query($db, "SELECT price FROM orders WHERE partyid='$end' AND paid!='' AND paid IS NOT NULL");
		while ($ordersrow = @mysqli_fetch_array($ordersresult)) {
			$partyresult += $ordersrow["price"];
			$partyresult -= $ordersrow["shipping"];
			$partyresult -= $ordersrow["tax"];
		}
		$partyrewardresult = @mysqli_query($db, "SELECT * FROM partyrewards WHERE result<='$partyresult' ORDER BY result DESC LIMIT 1");
		if (@mysqli_num_rows($partyrewardresult)) {
			function makeRandomcode() {
				$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
				srand ((double) microtime() * 1000000);
				$pwLength = "10"; // this sets the limit on how long the code is.
				for($i = 1; $i <=$pwLength; $i++) {
					$newPass .= $alphaNum[(rand(0,31))];
				}
				return ($newPass);
			}
			$code = makeRandomcode();
			$partycustomerid = @mysqli_result($checkparty, 0, "customerid");
			$partyrewardrow = @mysqli_fetch_array($partyrewardresult);
			$partyrewardpercent = $partyrewardrow["value"];
			$partyrewardmultiplier = $partyrewardpercent/100;
			$partyrewardvalue = $partyrewardmultiplier*$partyresult;
			@mysqli_query($db, "INSERT INTO storediscounts (code, value, type, customerid, giftcertificate) VALUES ('$code', '$partyrewardvalue', '$', '$partycustomerid', '1')");

			// Send the reward discount code to the hosting customer...
			$partycustomerresult = @mysqli_query($db, "SELECT firstname, lastname, email FROM customer WHERE customerid='$partycustomerid'");
			$partycustomerrow = @mysqli_fetch_array($partycustomerresult);
			if (file_exists("$ashoppath/templates/messages/partyreward-$lang.html")) $messagefile = "$ashoppath/templates/messages/partyreward-$lang.html";
			else $messagefile = "$ashoppath/templates/messages/partyreward.html";
			$fp = @fopen("$messagefile","r");
			if ($fp) {
				while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
				fclose($fp);
				$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
				$message = str_replace("%partyrewardcode%",$code,$message);
				$message = str_replace("%partyrewardamount%",$partyrewardrow["value"],$message);
				$message = str_replace("%hostfirstname%",$partycustomerrow["firstname"],$message);
				$message = str_replace("%hostlastname%",$partycustomerrow["lastname"],$message);
				$message = str_replace("%partylocation%",@mysqli_result($checkparty, 0, "location"),$message);
				$message = str_replace("%partydate%",@mysqli_result($checkparty, 0, "date"),$message);
				$subject="$ashopname - ".PARTYREWARD;
				$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				@ashop_mail($partycustomerrow["email"],"$subject","$message","$headers");
				$msg = PARTYREWARDSENT;
			}
		}
	}
}


// Get parties list from database...
$selectparties = "	<p><table width=\"60%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#D0D0D0\">
	<tr class=\"reporthead\"><td align=\"center\">".STATUS."</td><td align=\"center\">".DATETIME."</td><td align=\"center\">";
	if (!empty($affiliateid)) $selectparties .= CUSTOMER;
	else $selectparties .= AFFILIATE;
	$selectparties .= "</td><td align=\"center\">".LOCATION."</td><td align=\"center\">".COMMENTS."</td>";
	if (!empty($affiliateid)) $selectparties .= "<td align=\"center\">".COMMISSION."</td>";
	$selectparties .= "<td width=\"50\"></td></tr>";

$totalprovision = 0;
$totalourdebt = 0;
$date = date("Y-m-d h:i A", time()+$timezoneoffset);
if (!empty($affiliateid) && is_numeric($affiliateid)) $sql="SELECT * FROM party WHERE affiliateid='$affiliateid' ORDER BY date DESC";
else if (!empty($customerid) && is_numeric($customerid)) $sql="SELECT * FROM party WHERE customerid='$customerid' ORDER BY date DESC";
$result = @mysqli_query($db, "$sql");
$order = intval(@mysqli_num_rows($result));
if (!$partiesdisplayitems) {
	if ($c_partiesdisplayitems) $partiesdisplayitems = $c_partiesdisplayitems;
	else $partiesdisplayitems = 10;
}
$numberofpages = ceil($order/$partiesdisplayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $partiesdisplayitems;
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
$stoprow = $startrow + $partiesdisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$thisrow++;
	$partydate = $row["date"];
	$partylocation = $row["location"];
	$partycomment = $row["description"];
	$partycustomer = $row["customerid"];
	$partyaffiliate = $row["affiliateid"];
	$partyid = $row["partyid"];
	$partyapproved = $row["approved"];
	$partyended = $row["ended"];
	$partycustomerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE customerid='$partycustomer'");
	$partycustomername = @mysqli_result($partycustomerresult, 0, "firstname")." ".@mysqli_result($partycustomerresult, 0, "lastname");
	$partyaffiliateresult = @mysqli_query($db, "SELECT firstname, lastname FROM affiliate WHERE affiliateid='$partyaffiliate'");
	$partyaffiliatename = @mysqli_result($partyaffiliateresult, 0, "firstname")." ".@mysqli_result($partyaffiliateresult, 0, "lastname");
	if (!empty($affiliateid)) {
		if ($partydate >= $date) $partyresult = PENDING;
		else {
			$partyresult = 0;
			$ordersresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE partyid='$partyid' AND paid!='' AND paid IS NOT NULL");
			while ($ordersrow = @mysqli_fetch_array($ordersresult)) {
				$commissionresult = @mysqli_query($db, "SELECT commission FROM orderaffiliate WHERE orderid='{$ordersrow["orderid"]}' AND affiliateid='$affiliateid'");
				while ($commissionrow = @mysqli_fetch_array($commissionresult)) $partyresult += $commissionrow["commission"];
			}
		}
		if ($partydate < $date) {
			$total += $partyresult;
			$partyresult = $currencysymbols[$ashopcurrency]["pre"].number_format($partyresult,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
		}
	}

	$selectparties .= "<tr class=\"reportline\"><td align=\"left\">";
	if ($partyended == "1") $selectparties .= ENDED;
	else if ($partyapproved == "1") $selectparties .= APPROVED;
	else $selectparties .= PENDING;
	$selectparties .= "</td><td align=\"left\">$partydate</td>";
	if (!empty($affiliateid)) $selectparties .= "<td align=\"left\"><a href=\"editcustomer.php?customerid=$partycustomer\">$partycustomername</a></td>";
	else $selectparties .= "<td align=\"left\"><a href=\"editaffiliate.php?affiliateid=$partyaffiliate\">$partyaffiliatename</a></td>";
	$selectparties .= "
	  <td align=\"left\">$partylocation</td>
	  <td align=\"left\">$partycomment</td>";
	if (!empty($affiliateid)) $selectparties .= "
	  <td align=\"right\">$partyresult</td>";
	$selectparties .= "
	  <td align=\"center\">";
	if ($partyapproved != "1") $selectparties .= "<a href=\"viewparties.php?approve=$partyid&affiliateid=$affiliateid\"><img src=\"images/icon_activatem.gif\" border=\"0\" width=\"16\" height=\"16\" alt=\"Approve\" title=\"Approve\" /></a>";
	else if ($partyended != "1" && $partydate <= $date) $selectparties .= "<a href=\"viewparties.php?end=$partyid&affiliateid=$affiliateid\"><img src=\"images/icon_history.gif\" border=\"0\" width=\"16\" height=\"16\" alt=\"Mark as Ended\" title=\"Mark as Ended\" /></a>";
	else $selectparties .= "<img src=\"images/invisible.gif\" width=\"16\" height=\"16\" alt=\"\" />";
	$selectparties .= " <a href=\"editparty.php?partyid=$partyid"; if (!empty($affiliateid) && is_numeric($affiliateid)) $selectparties .= "&affiliateid=$affiliateid"; else if (!empty($customerid) && is_numeric($customerid)) $selectparties .= "&customerid=$customerid\""; $selectparties .= "\"><img src=\"images/icon_edit.gif\" border=\"0\" width=\"16\" height=\"16\" alt=\"Edit\" title=\"Edit\" /></a>";
	$selectparties .= "</td>
	</tr>
	";

}
$selectparties .= "</table></p>";


// Show affiliate stats in browser...
	if (strpos($header, "title") != 0) {
		$newheader = substr($header,1,strpos($header, "title")+5);
		$newheader .= PARTIESFOR.": $firstname $lastname - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= PARTIESFOR.": $firstname $lastname - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$newheader";
if (!empty($affiliateid) && is_numeric($affiliateid)) {
	echo "
	<div class=\"heading\">".PARTIESFOR." $firstname $lastname, ".AFFILIATEID." $affiliateid\n <a href=\"editaffiliate.php?affiliateid=$affiliateid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFORAFFILIATE." $affiliateid\" title=\"".PROFILEFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"referraldiscounts.php?affiliateid=$affiliateid\"><img src=\"images/icon_discount.gif\" alt=\"".REFERRALDISCOUNTSFORAFFILIATE." $affiliateid\" title=\"".REFERRALDISCOUNTSFORAFFILIATE." $affiliateid\" border=\"0\"></a>&nbsp;<a href=\"editaffiliate.php?affiliateid=$affiliateid&remove=True&fromstats=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" title=\"".DELETEAFFILIATE." $affiliateid ".FROMTHEDATABASE."\" border=\"0\"></a></div><center>";
} else if (!empty($customerid) && is_numeric($customerid)) {
	echo "
	<div class=\"heading\">".PARTIESFOR." $firstname $lastname, ".CUSTOMERID." $customerid <a href=\"salesreport.php?customerid=$customerid&generate=true\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $customerid\" title=\"".SALESHISTORYFOR." $customerid\" border=\"0\"></a> <a href=\"editstorediscounts.php?customerid=$customerid\"><img src=\"images/icon_discount.gif\" alt=\"".PERSONALDISCOUNTSFOR." $customerid\" title=\"".PERSONALDISCOUNTSFOR." $customerid\" border=\"0\"></a>";
	if (file_exists("$ashoppath/emerchant/quote.php") && $userid == 1) echo " <a href=\"../emerchant/history.php?customer=$customerid\" target=\"_blank\"><img src=\"images/icon_emerchant.gif\" alt=\"".SALESOFFICEHISTORY." $customerid\" title=\"".SALESOFFICEHISTORY." $customerid\" border=\"0\"></a>";
	if ($userid == "1") echo "&nbsp;<a href=\"editcustomer.php?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a>";
	echo "</div><center>";
}
echo "
	$selectparties";
if ($order > 5) {
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"viewparties.php?resultpage=$previouspage&partiesdisplayitems=$partiesdisplayitems&affiliateid=$affiliateid\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"viewparties.php?resultpage=$i&partiesdisplayitems=$partiesdisplayitems&affiliateid=$affiliateid\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"viewparties.php?resultpage=$nextpage&partiesdisplayitems=$partiesdisplayitems&affiliateid=$affiliateid\"><b>".NEXTPAGE."</b></a>>>";
		}
	}
	echo " ".DISPLAY.": <select name=\"partiesdisplayitems\" id=\"partiesdisplayitems\" onChange=\"document.location.href='viewparties.php?resultpage=$resultpage&affiliateid=$affiliateid&partiesdisplayitems='+document.getElementById('partiesdisplayitems').value;\"><option value=\"$order\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$order\">".ALL."</option></select> ".PARTIES2."</form></td></tr></table>
	";
}
echo "</center>$footer";

// Close database...
@mysqli_close($db);
?>