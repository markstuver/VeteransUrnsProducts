<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "admin/checklicense.inc.php";
include "admin/ashopconstants.inc.php";

if (empty($enablepartyplanner)) {
	header("Location: $ashopurl");
	exit;
}

// Validate variables...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

if (empty($_COOKIE["customersessionid"]) && empty($_COOKIE["wssessionid"])) {
	header("Location: signupform.php");
	exit;
}

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/customerparties.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Read wholesale session cookie if this is a wholesale customer...
if (!empty($_COOKIE["wssessionid"])) $_COOKIE["customersessionid"] = $_COOKIE["wssessionid"];

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get customer information from database...
$sql="SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) == 0) {
	header("Location: signupform.php");
	exit;
}

// Store customer information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$customerid = @mysqli_result($result, 0, "customerid");
$email = @mysqli_result($result, 0, "email");
$affiliateid = @mysqli_result($result, 0, "affiliateid");

// Make sure the customer is referred by an affiliate...
if (empty($affiliateid)) {
	header("Location: affiliate.php");
	exit;
}

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/partyplanner-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/partyplanner.html");

echo "<br><table class=\"ashopcustomerhistoryframe\">
  <tr><td align=\"center\">";

if (!empty($inviteemail) && !ashop_is_email($inviteemail)) $inviteemail = "";

// Register accepted invitation...
if (!empty($inviteyes) && is_numeric($inviteyes)) {
	@mysqli_query($db, "UPDATE partyinvitations SET response='yes' WHERE invitationid='$inviteyes'");

	$partyinvitationresult = @mysqli_query($db, "SELECT partyid FROM partyinvitations WHERE invitationid='$inviteyes'");
	$partyid = @mysqli_result($partyinvitationresult, 0, "partyid");
	$partyresult = @mysqli_query($db, "SELECT location, date FROM party WHERE partyid='$partyid'");
	$partylocation = @mysqli_result($partyresult, 0, "location");
	$partydate = @mysqli_result($partyresult, 0, "date");

	echo "<p class=\"ashopcustomerhistoryheader\">".THANKYOU."</p><p class=\"ashopcustomerhistoryheader\">".WELCOMETOTHEPARTY." $partylocation ".THEWORDON." $partydate.</p></td></tr></table>";

	// Print footer using template...
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner.html");
	exit;
}

// Register declined invitation
if (!empty($inviteno) && is_numeric($inviteno)) {
	@mysqli_query($db, "UPDATE partyinvitations SET response='no' WHERE invitationid='$inviteno'");

	echo "<p class=\"ashopcustomerhistoryheader\">".THANKYOUFORLETTINGUSKNOW."</p></td></tr></table>";

	// Print footer using template...
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner.html");
	exit;
}

if (!empty($invite) && is_numeric($invite)) {
	$partyresult = @mysqli_query($db, "SELECT * FROM party WHERE partyid='$invite'");
	if (@mysqli_num_rows($partyresult)) {
		$partyrow = @mysqli_fetch_array($partyresult);
		if ($partyrow["customerid"] == $customerid) {
			if (!empty($inviteemail)) {
				$checkalreadysent = @mysqli_query($db, "SELECT invitationid FROM partyinvitations WHERE email='$inviteemail' AND partyid='$invite'");
				if (!@mysqli_num_rows($checkalreadysent)) {
					if (file_exists("$ashoppath/templates/messages/partyinvitation-$lang.html")) $messagefile = "$ashoppath/templates/messages/partyinvitation-$lang.html";
					else $messagefile = "$ashoppath/templates/messages/partyinvitation.html";
					$fp = @fopen("$messagefile","r");
					if ($fp) {
						// Register invitation in the database...
						$today = date("Y-m-d H:i:s", time()+$timezoneoffset);
						@mysqli_query($db, "INSERT INTO partyinvitations (email, date, partyid) VALUES ('$email', '$today', '$invite')");
						$invitationid = @mysqli_insert_id($db);

						// Read message template and send invitation by email...
						while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
						fclose($fp);
						$partyaffiliateid = $partyrow["affiliateid"];
						$acceptpartyurl = $ashopurl."/affiliate.php?id=$partyaffiliateid&redirect=customerparties.php?inviteyes=$invitationid";
						$declinepartyurl = $ashopurl."/affiliate.php?id=$partyaffiliateid&redirect=customerparties.php?inviteno=$invitationid";
						$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
						$message = str_replace("%yeslink%",$acceptpartyurl,$message);
						$message = str_replace("%nolink%",$declinepartyurl,$message);
						$message = str_replace("%inviterfirstname%",$firstname,$message);
						$message = str_replace("%inviterlastname%",$lastname,$message);
						$message = str_replace("%partylocation%",$partyrow["location"],$message);
						$message = str_replace("%partydate%",$partyrow["date"],$message);
						$subject="$ashopname - ".PARTYINVITATION;
						$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
						@ashop_mail("$email","$subject","$message","$headers");
						$msg = INVITATIONSENT;
					}
				} else $msg = INVITATIONALREADYSENT;
			}

			echo "<p class=\"ashopcustomerhistoryheader\"><< <a href=\"customerparties.php";
			if (!empty($shop) && $shop > 1) echo "?shop=$shop";
			echo "\">".BACKTOPARTIES."</a></p>";
			if ($msg == INVITATIONSENT) echo "<p class=\"ashopcustomeralert2\">$msg</p>";
			else if ($msg == INVITATIONALREADYSENT) echo "<p class=\"ashopcustomeralert\">$msg</p>";
			echo "
			<br /><p class=\"ashoppartiestext2\">".INVITATIONTOPARTYON." <b>".$partyrow["date"]."</b> ".AT." <b>".$partyrow["location"]."</b>...</p>
			<p class=\"ashopcustomertext3\">
			<form method=\"post\" action=\"customerparties.php\"";
			if ($device == "mobile") echo " data-ajax=\"false\"";
			echo ">";
			if ($device != "mobile") {
				$tdwidth = 180;
				echo "
				<table width=\"340\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
			}
			if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"email\">".SENDTO.":</label><input type=\"text\" name=\"inviteemail\" id=\"email\" size=\"25\" /></div>
			<input type=\"submit\" name=\"Submit\" data-role=\"button\" value=\"".SUBMIT."\" />";
			else echo "
			<tr> 
				<td align=\"right\"><span class=\"ashopcustomertext2\">".SENDTO.":</span></td>
				<td width=\"$tdwidth\">&nbsp;<input type=\"text\" name=\"inviteemail\" size=\"25\"></td>
				<td><input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".SUBMIT."\" name=\"Submit\" /></td>
			</tr>
			</table>";
			if (!empty($shop) && $shop > 1) echo "
				<input type=\"hidden\" name=\"shop\" value=\"$shop\">";
			echo "
				<input type=\"hidden\" name=\"invite\" value=\"$invite\"></form>
				<p class=\"ashopcustomerhistoryheader\">".SENTINVITATIONS."</p>
				<p><table class=\"ashopcustomerhistorybox\" style=\"width: 400px;\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
				<tr class=\"ashopcustomerhistoryrow\"><td align=\"left\" width=\"120\"><span class=\"ashopcustomerhistorytext1\">&nbsp;".INVITATIONDATE."</span></td><td align=\"left\"><span class=\"ashopcustomerhistorytext1\">".EMAIL."<td align=\"left\"><span class=\"ashopcustomerhistorytext1\">".RESPONSE."</span></td></tr>";
			$invitationsresult = @mysqli_query($db, "SELECT * FROM partyinvitations WHERE partyid='$invite' ORDER BY date");
			while ($invitationrow = @mysqli_fetch_array($invitationsresult)) {
				if (empty($invitationrow["response"])) $invitationrow["response"] = PENDING;
				else if ($invitationrow["response"] == "yes") $invitationrow["response"] = ACCEPTED;
				else if ($invitationrow["response"] == "no") $invitationrow["response"] = DECLINED;
				echo "<tr>
				<td align=\"left\"><span class=\"ashopcustomertext3\">".$invitationrow["date"]."</span></td><td><span class=\"ashopcustomertext3\">".$invitationrow["email"]."</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">".$invitationrow["response"]."</span></td></tr>";
			}
			echo "</table></p></td></tr></table>";
			// Print footer using template...
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner.html");
			exit;
		}
	}
}

echo "<p><span class=\"ashopcustomerhistoryheader\">".MANAGEPARTIES."</span></p>
<p class=\"ashopcustomerhistoryheader\"><a href=\"customerprofile.php";
if (!empty($shop) && $shop > 1) echo "?shop=$shop";
echo "\">".VIEWPROFILE."</a></p>
<p class=\"ashopcustomerhistoryheader\"><a href=\"hostparty.php";
if (!empty($shop) && $shop > 1) echo "?shop=$shop";
echo "\">".HOSTAPARTY."</a></p>";

if ($msg) echo "<p class=\"ashopcustomeralert\">$msg</p>";

echo "
<p><table class=\"ashopcustomerhistorybox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
	<tr class=\"ashopcustomerhistoryrow\"><td align=\"left\" width=\"120\"><span class=\"ashopcustomerhistorytext1\">&nbsp;".WHEN."</span></td><td align=\"left\"><span class=\"ashopcustomerhistorytext1\">".WHERE."<td align=\"left\"><span class=\"ashopcustomerhistorytext1\">".COMMENT."</span></td><td align=\"left\" width=\"150\"><span class=\"ashopcustomerhistorytext1\">".RESULT."</span></td><td width=\"40\">&nbsp;</td></tr>";

// Get statistics from database...
$total = 0;
$date = date("Y-m-d h:i A", time()+$timezoneoffset);
$sql="SELECT * FROM party WHERE customerid='$customerid' ORDER BY date ASC";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) != 0) {
  for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
	  $partyid = @mysqli_result($result, $i, "partyid");
	  $partydate = @mysqli_result($result, $i, "date");
	  $location = @mysqli_result($result, $i, "location");
	  $comment = @mysqli_result($result, $i, "description");
	  $approved = @mysqli_result($result, $i, "approved");
	  $ended = @mysqli_result($result, $i, "ended");
	  if ($approved != "1") $partyresult = AWAITINGAPPROVAL;
	  else if ($partydate >= $date) $partyresult = PENDING;
	  else {
		  $partyresult = 0;
		  $ordersresult = @mysqli_query($db, "SELECT price FROM orders WHERE partyid='$partyid' AND paid!='' AND paid IS NOT NULL");
		  while ($ordersrow = @mysqli_fetch_array($ordersresult)) $partyresult += $ordersrow["price"];
	  }
	  if ($partydate < $date && $approved == "1") {
		  $total += $partyresult;
		  $partyresult = $currencysymbols[$ashopcurrency]["pre"].number_format($partyresult,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
	  }
	  echo "<tr>
	  <td align=\"left\"><span class=\"ashopcustomertext3\">$partydate</span></td><td><span class=\"ashopcustomertext3\">$location</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">$comment</span></td><td align=\"left\"><span class=\"ashopcustomertext6\">$partyresult</span></td><td>";

	  if ($ended != "1" && $partydate > $date) echo "<a href=\"hostparty.php?edit=$partyid\"><img src=\"images/icon_edit.png\" width=\"16\" height=\"16\" alt=\"".EDITPARTY."\" title=\"".EDITPARTY."\" /></a>";

	  if ($approved == "1" && $ended != "1" && $partydate >= $date) echo " <a href=\"customerparties.php?invite=$partyid\"><img src=\"images/icon_email.png\" width=\"16\" height=\"16\" alt=\"".INVITE."\" title=\"".INVITE."\" /></a>";
	  
	  echo "</td></tr>";
  }
}
echo "<tr><td colspan=\"3\" style=\"background-color:$categorycolor;\" align=\"right\"><span class=\"ashopcustomerhistorytext1\">".TOTAL.":</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">".$currencysymbols[$ashopcurrency]["pre"].number_format($total,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</span></td><td style=\"background-color:$categorycolor;\" colspan=\"2\">&nbsp;</td></tr></table></p></td></tr></table>";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/partyplanner-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/partyplanner.html");

// Close database...
@mysqli_close($db);
?>