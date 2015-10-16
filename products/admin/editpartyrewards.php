<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software
// is a violation U.S. and international copyright laws.

include "checklicense.inc.php";
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/partyplanner.inc.php";

// Get context help for this page...
$contexthelppage = "partyplanner";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Enable/disable party planner...
if (!empty($updatepartyplanner) && $updatepartyplanner == "true") {
	if ($nenablepartyplanner == "on") $nenablepartyplanner = "1";
	else $nenablepartyplanner = "";
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nenablepartyplanner' WHERE prefname='enablepartyplanner'");
	header("Location: settings.php");
	exit;
}

echo "$header
        <div class=\"heading\">".MANAGEPARTYREWARDS."</div><table cellpadding=\"10\" align=\"center\" width=\"650\"><tr><td>
		<form action=\"editpartyrewards.php\" method=\"post\" name=\"rewardform\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
			<tr><td colspan=\"2\" class=\"formtitle\">".PARTYPLANNERENABLED." <input type=\"checkbox\" name=\"nenablepartyplanner\""; if ($enablepartyplanner == "1") echo " checked"; echo "> <input type=\"hidden\" name=\"updatepartyplanner\" value=\"true\" /><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"></td></tr>
		</table></form><br>
        <form action=\"editpartyrewards.php\" method=\"post\" name=\"rewardform\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
		      <tr><td colspan=\"2\" class=\"formtitle\">".ADDPARTYREWARD."</td></tr>
		      <tr><td width=\"150\" align=\"right\"class=\"formlabel\">".ONRESULTABOVE.":</td><td>";
if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
echo "<input type=\"text\" name=\"partyrewardresult\" id=\"partyrewardresult\" size=\"10\">";
if ($currencysymbols[$ashopcurrency]["post"]) echo " ".$currencysymbols[$ashopcurrency]["post"];
echo "<script language=\"JavaScript\">document.getElementById('partyrewardresult').focus();</script>
			  </td></tr>
			  <tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\"><input type=\"text\" name=\"value\" size=\"10\"> % ".OFPARTYTOTAL.".</td></tr>
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"updaterewardid\" value=\"new\"><td align=\"right\"><input type=\"submit\" value=\"".ADD."\"></td></tr></table></form><br>";

// Update selected party reward...
if ($updaterewardid && !$delete) {
	if ($updaterewardid == "new") {
		@mysqli_query($db, "DELETE FROM partyrewards WHERE result='$partyrewardresult'");
		@mysqli_query($db, "INSERT INTO partyrewards (result, value) VALUES ('$partyrewardresult', '$value')");
	} else {
		@mysqli_query($db, "DELETE FROM partyrewards WHERE result='$partyrewardresult' AND rewardid!='$updaterewardid'");
		@mysqli_query($db, "UPDATE partyrewards SET result='$partyrewardresult', value='$value' WHERE rewardid='$updaterewardid'");
	}
} else if ($updaterewardid && $delete) {
	@mysqli_query($db, "DELETE FROM partyrewards WHERE rewardid='$updaterewardid'");
}

// Display current party rewards...
$result = @mysqli_query($db, "SELECT * FROM partyrewards ORDER BY result");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$rewardid = @mysqli_result($result, $i, "rewardid");
	$partyrewardresult = @mysqli_result($result, $i, "result");
	$discountvalue = @mysqli_result($result, $i, "value");
	echo "<form action=\"editpartyrewards.php\" method=\"post\" name=\"rewardform$i\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
		    <tr><td align=\"right\" class=\"formlabel\" width=\"150\">".ONRESULTABOVE.":</td><td class=\"formlabel\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			echo "<input type=\"text\" name=\"partyrewardresult\" value=\"$partyrewardresult\" size=\"10\">";
			if ($currencysymbols[$ashopcurrency]["post"]) echo " ".$currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\"><input type=\"text\" name=\"value\" value=\"$discountvalue\" size=\"10\"> % ".OFPARTYTOTAL.".</td></tr>
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"updaterewardid\" value=\"$rewardid\"><td align=\"right\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".REMOVEBUTTON."\"></td></tr></table></form><br>";
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>