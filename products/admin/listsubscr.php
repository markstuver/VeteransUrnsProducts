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
if ($clear) $productid = $clear;
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$subscriptiondir = @mysqli_result($result, 0, "subscriptiondir");

// Handle removal of all members...
if ($clear) {
	if ($yes) {
		unlink("$ashoppath/$subscriptiondir/.htpasswd");
		$fp = @fopen ("$ashoppath/$subscriptiondir/.htpasswd", "w");
		fclose ($fp);
		@chmod("$ashoppath/$subscriptiondir/.htpasswd", 0777);
		exit;
    }
	else if ($no) header("Location: listsubscr.php?cat=$cat&productid=$clear&resultpage=$resultpage");
	else echo "$header
        <div class=\"heading\">".REMOVEALLMEMBERS.": $productname</div>
        <p class=\"warning\">".AREYOUSURECLEARMEMBERS."</p><table cellpadding=\"10\" align=\"center\"><tr><td>
		<form action=\"listsubscr.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"clear\" value=\"$clear\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"keyid\" value=\"$keyid\">
		<input type=\"hidden\" name=\"remove\" value=\"true\"></form>
        $footer";
	exit;
}

// Handle removal of a single member...
if ($remove && $memberid) {
	if ($yes) {
		$tempfp = fopen("$ashopspath/updates/.temppasswd","w");
		$fp = fopen("$ashoppath/$subscriptiondir/.htpasswd", "r");
		if ($fp) {
			$starttime = time();
			$linenumber = 1;
			while (!feof($fp)) {
				if (time()-$starttime > 300) exit;
				$line = fgets($fp, 4096);
				if ($memberid != $linenumber) fwrite($tempfp, $line);
				$linenumber++;
			}
			fclose($fp);
			fclose($tempfp);
			copy ("$ashopspath/updates/.temppasswd", "$ashoppath/$subscriptiondir/.htpasswd");
			unlink ("$ashopspath/updates/.temppasswd");
		}
		header("Location: listsubscr.php?cat=$cat&productid=$productid&msg=deleted&resultpage=$resultpage");
		exit;
    }
	else if ($no) header("Location: listsubscr.php?cat=$cat&productid=$productid&resultpage=$resultpage");
	else echo "$header    
        <div class=\"heading\">".REMOVEAMEMBERSHIP.": $productname</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p class=\"warning\">".AREYOUSUREDELETEMEMBERSHIP."</p>
		<form action=\"listsubscr.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"memberid\" value=\"$memberid\">
		<input type=\"hidden\" name=\"remove\" value=\"true\"></form></td></tr></table>
        $footer";
	exit;
} 

// Show list of members...
if ($productid) {
	echo "$header
        <div class=\"heading\">".MEMBERSHIPSFOR.": <b>$productname</b>...</div><table cellpadding=\"10\" align=\"center\"><tr><td>";
	if ($msg == "deleted") echo "<p align=\"center\" class=\"confirm\">".MEMBERSHIPSDELETED."</p>";
	echo "</td></tr></table>
		<table width=\"600\" border=\"0\" cellspacing=\"1\" cellpadding=\"2\" align=\"center\" bgcolor=\"#D0D0D0\">
		<tr class=\"reporthead\"><td align=\"center\" width=\"230\">".USERNAME."</td><td align=\"center\" width=\"180\">".PASSWORD."</td><td align=\"center\" width=\"130\">".ORDERID."</td><td width=\"60\">&nbsp;</td></tr>";

	if ($subscriptiondir && file_exists("$ashoppath/$subscriptiondir/.htpasswd")) {
		$linenumber = 1;
		$fp = fopen ("$ashoppath/$subscriptiondir/.htpasswd","r");
		$starttime = time();
		if ($fp) while (!feof ($fp)) {
			if (time()-$starttime > 120) exit;
			$fileline = fgets($fp, 4096);
			$fileline = trim($fileline);
			if (!empty($fileline)) {
				$linearray = explode(":",$fileline);
				$thisusername = $linearray[0];
				$thispassword = $linearray[1];
				$result = @mysqli_query($db, "SELECT firstname, lastname, customerid FROM customer WHERE email='$thisusername'");
				$customerid = @mysqli_result($result,0,"customerid");
				$firstname = @mysqli_result($result, 0, "firstname");
				$lastname = @mysqli_result($result, 0, "lastname");
				$thisorderid = "";
				// Check if the customer has changed his/her email since the order...
				if (empty($customerid)) {
					$result = @mysqli_query($db, "SELECT orderid, paid, password, customerid FROM orders WHERE products LIKE '%b$productid"."a%' OR products LIKE '%b$productid"."d%' ORDER BY date");
					while ($orderrow = @mysqli_fetch_array($result)) {
						$password = $orderrow["password"];
						$checkpassword = "zombie";//crypt("$password","As");
						if ($checkpassword == $thispassword) {
							$paiddate = $orderrow["paiddate"];
							$thisorderid = $orderrow["orderid"];
							$customerid = $orderrow["customerid"];
							$customerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE customerid='$customerid'");
							$firstname = @mysqli_result($customerresult, 0, "firstname");
							$lastname = @mysqli_result($customerresult, 0, "lastname");
							break;
						} else $password = "";
					}
				} else {
					$result = @mysqli_query($db, "SELECT orderid, paid, password FROM orders WHERE customerid='$customerid' AND (products LIKE '%b$productid"."a%' OR products LIKE '%b$productid"."d%') ORDER BY date DESC LIMIT 1");
					$password = @mysqli_result($result,0,"password");
					$paiddate = @mysqli_result($result,0,"paid");
					$thisorderid = @mysqli_result($result,0,"orderid");
				}
				if (substr($thisorderid, 0, 2) == "ws") {
					$wholesaleorder = TRUE;
					$thisorderid = substr($thisorderid, 2);
				} else $wholesaleorder = FALSE;
				$logresult = @mysqli_query($db, "SELECT loginitemid FROM membershiplog WHERE customerid='$customerid' AND productid='$productid' LIMIT 1");
				$logexists = @mysqli_num_rows($logresult);	
				echo "<tr class=\"reportline\"><td align=\"left\">$thisusername</td><td align=\"left\">$password</td>
				<td align=\"left\"><a href=\"getreceipt.php?orderid=$thisorderid\" target=\"_blank\">$thisorderid</a></td><td align=\"center\">";
				if ($logexists) echo "<a href=\"subscrlog.php?customerid=$customerid&productid=$productid\"><img src=\"images/icon_history.gif\" alt=\"".ACCESSLOGFOR." $customerid\" title=\"".ACCESSLOGFOR." $customerid\" border=\"0\"></a>&nbsp;";
				else echo "<img src=\"images/invisible.gif\" width=\"16\" height=\"1\" alt=\" \">&nbsp;";
				if (!empty($customerid)) {
					if (!$wholesaleorder) echo "<a href=\"editcustomer.php?customerid=$customerid\">";
					else echo "<a href=\"edituser.php?customerid=$customerid\">";
					echo "<img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>&nbsp;";
				} else echo "<img src=\"images/invisible.gif\" width=\"16\" height=\"1\" alt=\" \">&nbsp;";
				echo "<a href=\"listsubscr.php?remove=true&memberid=$linenumber&productid=$productid&cat=$cat&resultpage=$resultpage\"><img src=\"images/icon_trash.gif\" border=\"0\"></a></td></tr>";
				$linenumber++;
			}
		}
		fclose($fp);

		echo "</table><br><center><form method=\"post\" action=\"listsubscr.php\"><input type=\"submit\" class=\"widebutton\" value=\"".CLEARALLMEMBERS."\"><input type=\"hidden\" name=\"clear\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></center></td></tr></table><br>$footer";
	}
} else header ("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage");
?>