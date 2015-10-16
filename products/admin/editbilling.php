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
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/salesoffice.inc.php";
include "ashopconstants.inc.php";

// Get context help for this page...
$contexthelppage = "editbilling";
include "help.inc.php";

echo "$header
<div class=\"heading\">".BILLINGTEMPLATES."</div>
<table align=\"center\" width=\"600\" cellpadding=\"10\"><tr><td>
	<form action=\"editbilling.php\" method=\"post\" name=\"billingtemplateform$i\">
		<table width=\"100%\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td width=\"32%\" colspan=\"2\"><a href=\"$help1\" class=\"helpnav\" target=\"_blank\">".ADDNEWBILLINGTEMPLATE."</a></td></tr><tr><td class=\"formlabel\" align=\"right\" width=\"32%\">".BILLTEMPLATETYPE.":</td><td><select name=\"billtemplatetype\"><option value=\"authnetarb\">".AUTHNETARB."<option value=\"autobill\">".AUTOBILL."</select></td></tr><tr><td class=\"formlabel\" align=\"right\" width=\"32%\">".ENTERANAME.":</td><td><input type=\"text\" size=\"35\" name=\"billtemplatename\"><input type=\"hidden\" name=\"updatebilltemplate\" value=\"new\"> <input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form><br>";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Update selected bill template...
if ($updatebilltemplate && !$delete) {
	if ($updatebilltemplate == "new") {
		if ($billtemplatetype == "autobill") {
			$remindermessage = DEFAULTREMINDERMSG."$ashopurl/payment.php?invoice=%orderid%";
			$pastduemessage = DEFAULTPASTDUEMSG."$ashopurl/payment.php?invoice=%orderid%";
		}
		$sql="INSERT INTO emerchant_billtemplates (name, duedays, reminderdays, remindermessage, pastduedays, pastduemessage, recurring, recurringtimes, templatetype) VALUES ('$billtemplatename', '30', '3', '$remindermessage', '3', '$pastduemessage', '', '0', '$billtemplatetype')";
	} else $sql="UPDATE emerchant_billtemplates SET name='$nname', duedays='$nbilltemplateduedays', reminderdays='$nbilltemplatereminderdays', remindermessage='$nbilltemplateremindermessage', pastduedays='$nbilltemplatepastduedays', pastduemessage='$nbilltemplatepastduemessage', recurring='$nbilltemplaterecurring', recurringtimes='$nbilltemplaterecurringtimes', sendbilldays='$nbilltemplatesendbilldays' WHERE billtemplateid=$updatebilltemplate";
	$result = @mysqli_query($db, "$sql");
} else if ($updatebilltemplate && $delete) {
	$sql="DELETE FROM emerchant_billtemplates WHERE billtemplateid=$updatebilltemplate";
	$result = @mysqli_query($db, "$sql");
}

// Display current bill templates...
$sql="SELECT * FROM emerchant_billtemplates ORDER BY billtemplateid DESC";
$result = @mysqli_query($db, "$sql");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$billtemplateid = @mysqli_result($result, $i, "billtemplateid");
	$billtemplatename = @mysqli_result($result, $i, "name");
	$thistemplatetype = @mysqli_result($result, $i, "templatetype");
	if ($thistemplatetype == "autobill") $billtemplatetype = "AutoBill";
	else if ($thistemplatetype == "authnetarb") $billtemplatetype = "Authorize.Net ARB";
	$billtemplateduedays = @mysqli_result($result, $i, "duedays");
	$billtemplatereminderdays = @mysqli_result($result, $i, "reminderdays");
	$billtemplateremindermessage = @mysqli_result($result, $i, "remindermessage");
	$billtemplatepastduedays = @mysqli_result($result, $i, "pastduedays");
	$billtemplatepastduemessage = @mysqli_result($result, $i, "pastduemessage");
	$billtemplaterecurring = @mysqli_result($result, $i, "recurring");
	$billtemplaterecurringtimes = @mysqli_result($result, $i, "recurringtimes");
	$billtemplatesendbilldays = @mysqli_result($result, $i, "sendbilldays");

	echo "<form action=\"editbilling.php\" method=\"post\" name=\"billingtemplateform$i\">
	<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
	<tr><td align=\"right\" class=\"formlabel\">".BILLTEMPLATETYPE.":</td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$billtemplatetype</font></td></tr>
	<tr><td align=\"right\" class=\"formlabel\">".TEMPLATENAME.":</td><td><input type=\"text\" name=\"nname\" size=\"35\" value=\"$billtemplatename\"></td></tr>";
	if ($thistemplatetype == "autobill") echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".DUE.":</font></td><td><input type=\"text\" size=\"5\" name=\"nbilltemplateduedays\" value=\"$billtemplateduedays\"> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".DAYSAFTERORDERDATE."</font></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"checkbox\" name=\"reminder\" checked> ".REMIND.":</font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" size=\"5\" name=\"nbilltemplatereminderdays\" value=\"$billtemplatereminderdays\"> ".DAYSBEFOREDUEIFNOTPAID."</font></td></tr>
<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".REMINDERMESSAGE.":</font></td><td><textarea name=\"nbilltemplateremindermessage\" cols=\"45\" rows=\"5\">$billtemplateremindermessage</textarea></td></tr>
<tr><td align=\"right\" nowrap><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"checkbox\" name=\"pastdue\" checked> ".SENDPASTDUEMESSAGE.":</font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" size=\"5\" name=\"nbilltemplatepastduedays\" value=\"$billtemplatepastduedays\"> ".DAYSAFTERDUEIFNOTPAID."</font></td></tr>
<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PASTDUEMESSAGE.":</font></td><td><textarea name=\"nbilltemplatepastduemessage\" cols=\"45\" rows=\"5\">$billtemplatepastduemessage</textarea></td></tr>";
	else echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".START.":</font></td><td><input type=\"text\" size=\"5\" name=\"nbilltemplateduedays\" value=\"$billtemplateduedays\"> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".DAYSAFTERORDERDATE."</font></td></tr><input type=\"hidden\" name=\"nbilltemplatepastduedays\" value=\"0\"><input type=\"hidden\" name=\"nbilltemplatereminderdays\" value=\"0\"><input type=\"hidden\" name=\"nbilltemplatesendbilldays\" value=\"0\">";
	echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".RECURRING.":</font></td><td><select name=\"nbilltemplaterecurring\">";
	if ($thistemplatetype == "autobill") echo "<option value=\"none\">".NONE;
	echo "<option value=\"weekly\""; if ($billtemplaterecurring == "weekly") echo " selected"; echo ">".WEEKLY."<option value=\"monthly\""; if ($billtemplaterecurring == "monthly") echo " selected"; echo ">".MONTHLY."<option value=\"quarterly\""; if ($billtemplaterecurring == "quarterly") echo " selected"; echo ">".QUARTERLY."<option value=\"semiannually\""; if ($billtemplaterecurring == "semiannually") echo " selected"; echo ">".SEMIANNUALLY."<option value=\"annually\""; if ($billtemplaterecurring == "annually") echo " selected"; echo ">".ANNUALLY."</select></td></tr>
<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".REPEAT.":</font></td><td><input type=\"text\" size=\"3\" name=\"nbilltemplaterecurringtimes\" value=\"$billtemplaterecurringtimes\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"> ".NUMBEROFTIMES." </font><font size=\"1\" face=\"Arial, Helvetica, sans-serif\">".ZEROINDEFINITELY."</font></td></tr>";
if ($thistemplatetype == "autobill") echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".SENDNEXTBILLATLEAST."</font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" size=\"5\" name=\"nbilltemplatesendbilldays\" value=\"$billtemplatesendbilldays\"> ".DAYSBEFOREDUEDATE."</font></td></tr>";
	echo "<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updatebilltemplate\" value=\"$billtemplateid\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form><br>";
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>