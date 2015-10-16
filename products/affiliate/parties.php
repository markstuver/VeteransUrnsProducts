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

include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "../admin/ashopconstants.inc.php";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/af_parties.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get affiliate information from database...
$sql="SELECT * FROM affiliate WHERE sessionid='$affiliatesesid'";
$result = @mysqli_query($db, "$sql");

// Store affiliate information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$affiliateid = @mysqli_result($result, 0, "affiliateid");
$affiliateuser = @mysqli_result($result, 0, "user");

// Approve a party...
if (!empty($approve) && is_numeric($approve)) {
	$checkowner = @mysqli_query($db, "SELECT affiliateid, approved FROM party WHERE partyid='$approve'");
	$owneraffiliateid = @mysqli_result($checkowner, 0, "affiliateid");
	$alreadyapproved = @mysqli_result($checkowner, 0, "approved");
	if ($owneraffiliateid == $affiliateid && $alreadyapproved != "1") @mysqli_query($db, "UPDATE party SET approved='1' WHERE partyid='$approve'");
}

// End a party...
if (!empty($end) && is_numeric($end)) {
	$checkowner = @mysqli_query($db, "SELECT * FROM party WHERE partyid='$end'");
	$owneraffiliateid = @mysqli_result($checkowner, 0, "affiliateid");
	$approved = @mysqli_result($checkowner, 0, "approved");
	$alreadyended = @mysqli_result($checkowner, 0, "ended");
	if ($owneraffiliateid == $affiliateid && $approved == "1" && $alreadyended != "1") {
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
			$partycustomerid = @mysqli_result($checkowner, 0, "customerid");
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
				$message = str_replace("%partylocation%",@mysqli_result($checkowner, 0, "location"),$message);
				$message = str_replace("%partydate%",@mysqli_result($checkowner, 0, "date"),$message);
				$subject="$ashopname - ".PARTYREWARD;
				$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				@ashop_mail($partycustomerrow["email"],"$subject","$message","$headers");
				$msg = PARTYREWARDSENT;
			}
		}
	}
}

// Get statistics from database...
$selectparties = "<table class=\"ashopaffiliatepartiesbox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
	<tr class=\"ashopaffiliatepartiesrow\"><td align=\"left\" width=\"80\"><span class=\"ashopaffiliatepartiestext1\">&nbsp;".DATETIME."</span></td><td align=\"left\"><span class=\"ashopaffiliatehistorytext1\">&nbsp;".CUSTOMER."</span></td><td align=\"left\"><span class=\"ashopaffiliatepartiestext1\">&nbsp;".LOCATION."</span></td><td align=\"left\"><span class=\"ashopaffiliatepartiestext1\">&nbsp;".COMMENTS."</span></td><td align=\"center\" width=\"60\"><span class=\"ashopaffiliatepartiestext1\">".COMMISSION."</span></td><td width=\"50\">&nbsp;</td></tr>";

$total = 0;
$totalourdebt = 0;
$date = date("Y-m-d h:i A", time()+$timezoneoffset);
$datets = time()+$timezoneoffset;
$sql = "SELECT * FROM party WHERE affiliateid='$affiliateid' ORDER BY date ASC";
$result = @mysqli_query($db, "$sql");
$order = @mysqli_num_rows($result);
if (@mysqli_num_rows($result) != 0) {
  for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
	  $partydate = @mysqli_result($result, $i, "date");
	  $partydatets = strtotime($partydate);
	  $partydate = substr($partydate,0,strpos($partydate," "))."<br>".substr($partydate,strpos($partydate," ")+1);
	  $partylocation = @mysqli_result($result, $i, "location");
	  $partycomment = @mysqli_result($result, $i, "description");
	  $partycustomer = @mysqli_result($result, $i, "customerid");
	  $partyid = @mysqli_result($result, $i, "partyid");
	  $partyapproved = @mysqli_result($result, $i, "approved");
	  $partyended = @mysqli_result($result, $i, "ended");
	  $partycustomerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE customerid='$partycustomer'");
	  $partycustomername = @mysqli_result($partycustomerresult, 0, "firstname")." ".@mysqli_result($partycustomerresult, 0, "lastname");
	  if ($partydatets >= $datets) $partyresult = PENDING;
	  else {
		  $partyresult = 0;
		  $ordersresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE partyid='$partyid' AND paid!='' AND paid IS NOT NULL");
		  while ($ordersrow = @mysqli_fetch_array($ordersresult)) {
			  $commissionresult = @mysqli_query($db, "SELECT commission FROM orderaffiliate WHERE orderid='{$ordersrow["orderid"]}' AND affiliateid='$affiliateid'");
			  while ($commissionrow = @mysqli_fetch_array($commissionresult)) $partyresult += $commissionrow["commission"];
		  }
	  }
	  if ($partydatets < $datets) {
		  $total += $partyresult;
		  $partyresult = $currencysymbols[$ashopcurrency]["pre"].number_format($partyresult,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
	  }
	  if ($partyapproved != "1") $partytextclass = "ashopaffiliatepartiestext2";
	  else $partytextclass = "ashopaffiliatetext3";

	  $selectparties .= "<tr><td align=\"left\"><span class=\"$partytextclass\">$partydate</span></td>
	  <td align=\"left\"><span class=\"$partytextclass\">$partycustomername</span></td>
	  <td align=\"left\"><span class=\"$partytextclass\">$partylocation</span></td>
	  <td align=\"left\"><span class=\"$partytextclass\">$partycomment</span></td>
	  <td align=\"right\"><span class=\"$partytextclass\">$partyresult</span></td>
	  <td align=\"center\">";
	  if ($partyapproved != "1") $selectparties .= "<a href=\"parties.php?approve=$partyid\"><img src=\"../images/icon_approve.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Approve\" title=\"Approve\" /></a>";
	  else if ($partyended != "1" && $partydatets <= $datets) $selectparties .= "<a href=\"parties.php?end=$partyid\"><img src=\"../images/icon_clock.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Mark as Ended\" title=\"Mark as Ended\" /></a>";
	  else $selectparties .= "&nbsp;";
	  $selectparties .= "</td>
	  </tr><tr style=\"height: 2px; background-color: #ddd;\"><td colspan=\"6\"></td></tr>";

  }
}
$selectparties .= "<tr class=\"ashopaffiliatepartiesrow\"><td colspan=\"4\" align=\"right\"><span class=\"ashopaffiliatepartiestext1\">".TOTAL.":</span></td><td align=\"right\"><span class=\"ashopaffiliatepartiestext1\">".$currencysymbols[$ashopcurrency]["pre"].number_format($total,2,'.','')." ".$currencysymbols[$ashopcurrency]["post"]."</span></td><td>&nbsp;</td></tr></table></p>";

// Get number of unread PMs...
$sql="SELECT * FROM affiliatepm WHERE toaffiliateid='$affiliateid' AND (hasbeenread='' OR hasbeenread='0' OR hasbeenread IS NULL)";
$unreadresult = @mysqli_query($db, "$sql");
$unreadcount = @mysqli_num_rows($unreadresult);

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/affiliate.html");

echo "<br><table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr><td align=\"left\"><span class=\"ashopaffiliateheader\">".WELCOME." $firstname $lastname! ".AFFILIATEID.": $affiliateid</span></td>$salesreplink</tr></table>
	<table align=\"center\" width=\""; if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "560"; else echo "400"; echo "\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"affiliate.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".STATISTICS."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"affiliate.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".STATISTICS."\"></a></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changeprofile.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".VIEWPROFILE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"changepassword.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".CHANGEPASS."\"></a></td>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".PARTIES."\" disabled></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"login.php?logout\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"login.php?logout\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LOGOUT."\"></a></td>";
echo "</tr></table>
	<table align=\"center\" width=\"400\"><tr>";
if (file_exists("$ashoppath/customerparties.php") && $enablepartyplanner == "1") echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
else echo "<td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"linkcodes.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".LINKCODES."\"></a></td>";
echo "<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"orderhistory.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".ORDERHISTORY."\"></a></td>";
if ($activateleads) {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"leads.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".LEADS."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebuttonsmall\" href=\"inbox.php\"><input class=\"ashopaffiliatebuttonsmall\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
} else {
	echo "	
	<td align=\"center\"><a class=\"ashopaffiliatebuttonlarge\" href=\"downline.php\"><input class=\"ashopaffiliatebuttonlarge\" type=\"button\" value=\"".DOWNLINE."\"></a></td><td align=\"center\"><a class=\"ashopaffiliatebutton\" href=\"inbox.php\"><input class=\"ashopaffiliatebutton\" type=\"button\" value=\"".INBOX;
	if ($unreadcount) echo " ($unreadcount)";
	echo "\"></a></td>";
}
echo "
	</tr></table>
	<br /><table width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\"><tr><td width=\"50%\" align=\"left\"><span class=\"ashopaffiliateheader\">".YOURPARTIES."</span></td><td width=\"50%\" align=\"right\"><span class=\"ashopaffiliateheader\"><a href=\"addparty.php\">".ADDAPARTY."</a></span></td></tr></table>";
if (!empty($msg)) {
	if ($msg == "partyadded") echo "<span class=\"ashopaffiliatetext1\">".PARTYADDED."</span><br /><br />";
	else echo "<span class=\"ashopaffiliatetext1\">$msg</span><br /><br />";
}

echo $selectparties;

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/affiliate-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/affiliate-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/affiliate.html");

// Close database...
@mysqli_close($db);
?>