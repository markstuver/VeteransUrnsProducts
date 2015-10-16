<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Edit Billing Options";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get invoice information...
$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$orderid'");
$orderrow = @mysqli_fetch_array($result);
$customer = $orderrow["customerid"];
if ($billnumber) {
	$invoiceresult = @mysqli_query($db, "SELECT * FROM emerchant_tempinvoices WHERE orderid='$orderid' AND billnumber='$billnumber'");
	$invoicerow = @mysqli_fetch_array($invoiceresult);
	$totalcost = $invoicerow["price"];
	$duedate = $invoicerow["duedate"];
	$productsincart = ashop_parseproductstring($db,$invoicerow["products"]);
	$displaydescr = "";
	if ($productsincart) {
		foreach($productsincart as $productnumber => $thisproduct) {
			$quantity = $thisproduct["quantity"];
			$name = $thisproduct["name"];
			$parameters = $thisproduct["parameters"];
			$displaydescr .= "$quantity: $name";
			if ($parameters) $displaydescr .= " $parameters";
			$displaydescr .= ", ";
		}
	}
	$displaydescr = substr($displaydescr,0,-2);			
} else {
	$displaydescr = $orderrow["description"];
	$totalcost = $orderrow["price"];
	$duedate = $orderrow["duedate"];
}
$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
$customerrow = @mysqli_fetch_array($result);
if ($billnumber) $result = @mysqli_query($db, "SELECT * FROM emerchant_bills WHERE orderid='$orderid' AND billnumber='$billnumber'");
else $result = @mysqli_query($db, "SELECT * FROM emerchant_bills WHERE orderid='$orderid'");
$billrow = @mysqli_fetch_array($result);
$reminderdate = $billrow["reminderdate"];
$remindermessage = $billrow["remindermessage"];
$pastduedate = $billrow["pastduedate"];
$pastduemessage = $billrow["pastduemessage"];
$recurring = $billrow["recurring"];
$sendbilldays = $billrow["sendbilldays"];
$recurringtimes = $billrow["recurringtimes"];

// Store changes...
if ($action == "Update") {
	if (!$reminder) {
		$nreminderdate = "";
		$nremindermessage = "";
	}
	if (!$pastdue) {
		$npastduedate = "";
		$npastduemessage = "";
	}
	if ($nrecurring == "none") $nrecurring = "";
	if ($billnumber) @mysqli_query($db, "UPDATE emerchant_bills SET reminderdate='$nreminderdate', remindermessage='$nremindermessage', pastduedate='$npastduedate', pastduemessage='$npastduemessage', recurring='$nrecurring', recurringtimes='$nrecurringtimes', sendbilldays='$nsendbilldays' WHERE orderid='$orderid' AND billnumber='$billnumber'");
	else @mysqli_query($db, "UPDATE emerchant_bills SET reminderdate='$nreminderdate', remindermessage='$nremindermessage', pastduedate='$npastduedate', pastduemessage='$npastduemessage', recurring='$nrecurring', recurringtimes='$nrecurringtimes', sendbilldays='$nsendbilldays' WHERE orderid='$orderid'");
	header("Location: history.php?customer=$customer");
} else if ($action != "exit") {
	echo $header;
	emerchant_sidebar();
	echo "<td valign=\"top\">";
	emerchant_topbar("Edit Billing Options");
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\">
	<center><br><form action=\"editinvoice.php\" name=\"editform\" method=\"post\">
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Customer:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">{$customerrow["firstname"]} {$customerrow["lastname"]}</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Amount:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".number_format($totalcost,2,'.','')."</font></td></tr>
		<tr><td align=\"right\" valign=\"top\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Products:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Due Date:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$duedate</font></td></tr>\n<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><input type=\"checkbox\" name=\"reminder\""; if ($reminderdate) echo " checked"; echo "> Remind on Date:</b></font></td><td><input type=\"text\" size=\"15\" name=\"nreminderdate\" value=\"$reminderdate\"></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Reminder Message:</b></font></td><td><textarea name=\"nremindermessage\" cols=\"55\" rows=\"5\">$remindermessage</textarea></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><input type=\"checkbox\" name=\"pastdue\""; if ($pastduedate) echo " checked"; echo "> Past Due Message on:</b></font></td><td><input type=\"text\" size=\"15\" name=\"npastduedate\" value=\"$pastduedate\"></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Past Due Message:</b></font></td><td><textarea name=\"npastduemessage\" cols=\"55\" rows=\"5\">$pastduemessage</textarea></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Recurring:</b></font></td><td><select name=\"nrecurring\"><option value=\"none\""; if (!$recurring) echo " selected"; echo ">None<option value=\"weekly\""; if ($recurring == "weekly") echo " selected"; echo ">Weekly<option value=\"monthly\""; if ($recurring == "monthly") echo " selected"; echo ">Monthly<option value=\"quarterly\""; if ($recurring == "quarterly") echo " selected"; echo ">Quarterly<option value=\"semiannually\""; if ($recurring == "semiannually") echo " selected"; echo ">Semiannually<option value=\"annually\""; if ($recurring == "annually") echo " selected"; echo ">Annually</select> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Repeat: <input type=\"text\" size=\"3\" name=\"nrecurringtimes\" value=\"$recurringtimes\"> number of times </b></font><font size=\"1\" face=\"Arial, Helvetica, sans-serif\">[0 = indefinitely]</font></td></tr><tr><td align=\"right\">&nbsp;</td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">Create and send next bill <input type=\"text\" size=\"5\" name=\"nsendbilldays\" value=\"$sendbilldays\"> days before due date.</font></td></tr></table>
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
		<td width=\"100%\" align=\"center\" valign=\"top\"><br><input type=\"button\" name=\"cancel\" value=\"Cancel\" onClick=\"javascript:history.back()\" style=\"width: 80px\">
		<input type=\"submit\" name=\"action\" value=\"Update\" style=\"width: 80px\">
		</td></tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\"><input type=\"hidden\" name=\"billnumber\" value=\"$billnumber\">
		</form>
		</center>
		</table></td></tr><tr><td align=\"center\" colspan=\"2\"></td></tr></table>$footer";
}
?>