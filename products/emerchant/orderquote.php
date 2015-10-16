<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
if ($bill || $action == "Send Bill") $pagetitle = "Convert Quote to Payable Bill";
else $pagetitle = "Convert Quote to Order";
include "template.inc.php";
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
$shortdate = date("Y-m-d", time()+$timezoneoffset);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$reminderdays) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='defaultreminderdays'");
	$reminderdays = @mysqli_result($result,0,"confvalue");
}
if (!$pastduedays) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='defaultpastduedays'");
	$pastduedays = @mysqli_result($result,0,"confvalue");
}
if (!isset($sendbilldays)) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='defaultsendbilldays'");
	$sendbilldays = @mysqli_result($result,0,"confvalue");
}
if (!isset($recurringtimes)) $recurringtimes = 0;
if (!$duedate) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='defaultduedays'");
	$defaultduedays = @mysqli_result($result,0,"confvalue");
	$duedatetimestamp = time();
	$duedatetimestamp += $defaultduedays*86400;
	$duedate = date("Y-m-d", $duedatetimestamp+$timezoneoffset);
}
$duedatearray = explode("-",$duedate);
$remindertimestamp = mktime(0,0,0,$duedatearray[1],$duedatearray[2]-$reminderdays,$duedatearray[0]);
$reminderdate = date("Y-m-d", $remindertimestamp);
$pastduetimestamp = mktime(0,0,0,$duedatearray[1],$duedatearray[2]+$pastduedays,$duedatearray[0]);
$pastduedate = date("Y-m-d", $pastduetimestamp);
if (!isset($startdate)) $startdate = $shortdate;
if (!isset($enddate)) $enddate = $duedate;

// Check if this is a bill...
if ($bill) {
	$isbill = TRUE;
	$quote = $bill;
} else $isbill = FALSE;

// Get quote information...
$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$quote'");
$quoterow = @mysqli_fetch_array($result);
$products = $quoterow["products"];
$comments = $quoterow["comments"];
$commentprices = explode("|",$quoterow["commentprices"]);
$commenttaxablearray = explode("|",substr($quoterow["commenttaxable"],0,-1));
$commentprice = 0;
$commenttax = 0;
$commenttaxnumber = 0;
if ($commentprices) foreach ($commentprices as $commentpricenumber=>$thiscommentprice) {
	$commentprice += $thiscommentprice;
	if ($commenttaxablearray["$commenttaxnumber"]) $commenttax += $thiscommentprice;
	$commenttaxnumber++;
}
$customer = $quoterow["customerid"];
$shipping = $quoterow["shipping"];
$productprices = $quoterow["productprices"];
$orderreference = "em".sprintf("%06d",$quote);
$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
$customerrow = @mysqli_fetch_array($result);
$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
$shippingrow = @mysqli_fetch_array($result);

// Calculate product cost and check product owners...
$subtotal = 0;
$membershops = array();
$productsincart = ashop_parseproductstring($db, $products);
if($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
	$productid = $thisproduct["productid"];
	$quantity = $thisproduct["quantity"];
	$discounted = $thisproduct["discounted"];
	if ($discounted) {
		// Check if there is a sale...
		$result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' AND onetime='0' AND (code='' OR code IS NULL)");
		if (@mysqli_num_rows($result2)) {
			$discounttype = @mysqli_result($result2,0,"type");
			$discountvalue = @mysqli_result($result2,0,"value");
			$regprice = $productrow["price"];
			if ($discounttype == "%") $thisproduct["price"] = $thisproduct["price"] - ($thisproduct["price"] * ($discountvalue/100));
			else if ($discounttype == "$") $thisproduct["price"] -= $discountvalue;
		}
	}
	$price = $thisproduct["price"];
	$name = $thisproduct["name"];
	$parameters = $thisproduct["parameters"];
	$thistotal = $thisproduct["price"] * $thisproduct["quantity"];
	$subtotal += $thistotal;
	$descriptionstring .= $thisproduct["quantity"].": ".$thisproduct["name"].$thisproduct["parameters"];
	if (count($productsincart) > 1 && $productnumber < count($productsincart)-1) $descriptionstring .= ", ";
	if (!is_array($membershops) || !in_array($thisproduct["userid"], $membershops)) $membershops[] = $thisproduct["userid"];
}
$displaydescr = str_replace(",","<br>",$descriptionstring);

$handlingcosts = ashop_gethandlingcost($shipping);

// Calculate total cost...
$totalcost = $subtotal + $handlingcosts["salestax"] + $handlingcosts["shipping"] + $commentprice;

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
if ($isbill || $action == "Send Bill") emerchant_topbar("Send Quote As Payable Bill");
else emerchant_topbar("Convert Quote To Order");
echo "<script language=\"JavaScript\">
function payquote(quote, payoption)
{
	window.open(\"payquote.php?quote=\"+quote+\"&payoptionid=\"+payoption,\"_blank\",\"toolbar=yes, location=yes, scrollbars=yes, width=990, height=600\");
}
</script>
<table width=\"100%\" border=\"0\" cellpadding=\"0\">";

if (!$products && !$comments) {
	echo "<tr><td class=\"heading3\" align=\"center\"><br><br>This quote has already been converted!</td></tr>";
	$action = "exit";
}

// Complete a preliminary order...
if ($action == "Convert" || $action == "Send Bill") {
	if ($action == "Send Bill") {
		$payoption = "0";
		$billdate = $date;
	} else $billdate = "";
	// Give the user credit for the sale if he/she is an affiliate...
	if ($emerchant_user != "admin") {
		$affiliateresult = @mysqli_query($db, "SELECT affiliateid FROM affiliate WHERE user='$emerchant_user'");
		if (@mysqli_num_rows($affiliateresult)) $affiliateid = @mysqli_result($affiliateresult,0,"affiliateid");
		else $affiliateid = "";
	} else $affiliateid = "";
	// Create a list of all membershops included in this order...
	$shops = "";
	if ($membershops) foreach ($membershops as $i => $membershopid) if ($membershopid) $shops .= "|$membershopid|";
	$shops = str_replace("||","|",$shops);
	$result = @mysqli_query($db, "INSERT INTO orders (userid, customerid, products, price, ip, billdate, duedate, affiliateid, partyid) VALUES ('$shops','{$shippingrow["shippingid"]}','$products$shipping','$totalcost','{$_SERVER["REMOTE_ADDR"]}', '$billdate', '$duedate', '$affiliateid', '$partyid')");
	$orderid = @mysqli_insert_id($db);
	if ($action == "Send Bill") {
		@mysqli_query($db, "INSERT INTO emerchant_tempinvoices (orderid, products, productprices, comments, commentprices, itemorder, commenttaxable, price, shipping) VALUES ('$orderid','{$quoterow["products"]}','{$quoterow["productprices"]}','{$quoterow["comments"]}','{$quoterow["commentprices"]}','{$quoterow["itemorder"]}', '{$quoterow["commenttaxable"]}','$totalcost','$shipping')");
		if (!$reminder) {
			$reminderdate = "";
			$remindermessage = "";
		}
		if (!$pastdue) {
			$pastduedate = "";
			$pastduemessage = "";
		}
		if ($recurring == "none") $recurring = "";
		@mysqli_query($db, "INSERT INTO emerchant_bills (orderid, reminderdate, remindermessage, pastduedate, pastduemessage, recurring, recurringtimes, sendbilldays, startdate, enddate, billcomment) VALUES ('$orderid','$reminderdate','$remindermessage','$pastduedate','$pastduemessage','$recurring','$recurringtimes','$sendbilldays','$startdate','$enddate','$billcomment')");
	}
	$adminkey = md5("$databasepasswd$ashoppath"."prelcomplete");
	$querystring = "email={$customerrow["email"]}&firstname={$customerrow["firstname"]}&lastname={$customerrow["lastname"]}&address={$customerrow["address"]}&city={$customerrow["city"]}&zip={$customerrow["zip"]}&state={$customerrow["state"]}&country={$customerrow["country"]}&phone={$customerrow["phone"]}&invoice=$orderid&adminkey=$adminkey&amount=$totalcost&products=$payoption"."ashoporderstring$products$shipping&orderreference=$orderreference&emerchantquote=$quote&quoteprices=$productprices";
	if (strpos($ashopurl, "/", 8)) {
		$urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
		$urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
	} else {
		$urlpath = "/";
		$urldomain = $ashopurl;
	}
	if ($urlpath == "/") $scriptpath = "order.php";
	else $scriptpath = "/order.php";
	$urldomain = str_replace("http://", "", $urldomain);
	$postheader = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
	$fp = fsockopen ("$urldomain", 80);
	if ($fp) {
		$response = fwrite ($fp, $postheader . $querystring);
		while (!feof($fp)) $res .= fgets ($fp, 1024);
		fclose ($fp);
		$result = @mysqli_query($db, "SELECT date FROM orders WHERE orderid='$orderid'");
	} else unset($result);
	if (@mysqli_result($result,0,"date")) {
		@mysqli_query($db, "DELETE FROM emerchant_quotes WHERE id='$quote'");
		if ($action == "Send Bill") echo "<tr><td class=\"heading3\" align=\"center\"><br><br>Quote number $quote has been converted to a payable bill and sent to the customer!<br>Invoice number: $orderid</td></tr>";
		else echo "<tr><td class=\"heading3\" align=\"center\"><br><br>Quote number $quote has been converted! Order ID: $orderid</td></tr>";
	} else {
		@mysqli_query($db, "DELETE FROM orders WHERE orderid='$orderid'");
		echo "<tr><td class=\"heading3\" align=\"center\"><br><br>Error! Quote number $quote could not be converted!</td></tr>";
	}
} else if ($action != "exit") {
	$payoptionsstring = "";
	$result = @mysqli_query($db, "SELECT * FROM payoptions WHERE (wholesaleonly = '' OR wholesaleonly IS NULL OR wholesaleonly = '0') AND (retailonly = '' OR retailonly IS NULL OR retailonly = '0') AND gateway!='manual' AND gateway!='offline' AND userid='1'");
	while($row = @mysqli_fetch_array($result)) $payoptionsstring .= "<option value=\"".$row["payoptionid"]."\">".$row["name"]."</option>";
	echo "<script language=\"JavaScript\">
	<!--
	function verifyform(orderform) {
		var reminderdisablemessage = 0;
		var remindertoosoonmessage = 0;
		if (orderform.recurring.value != \"none\" && orderform.sendbilldays.value < 2 && orderform.reminderdays.value > 0) reminderdisablemessage = 1;
		else if (orderform.recurring.value != \"none\" && orderform.sendbilldays.value <= orderform.reminderdays.value) remindertoosoonmessage = 1;
		if (reminderdisablemessage == 1) {
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>Reminder can not be enabled!</title><style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">A reminder message can not be used because the next bill will be sent after its&#039; reminder date. Please deactivate Remind.</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">Close this window</a></span></font></font><br></center></body></html>');
			return false;
		} else if (remindertoosoonmessage == 1) {
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>Reminder date too soon!</title><style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">Please set a lower value for Remind. The reminder date of the next bill will occur before the bill is sent.</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">Close this window</a></span></font></font><br></center></body></html>');
			return false;
		}
    }
	-->
	</script>
		<center><br><form action=\"orderquote.php\" name=\"orderform\" method=\"post\" onSubmit=\"return verifyform(this)\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>";
		if (!$isbill) echo "Order Reference:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderreference</font></td></tr>";
		echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Customer:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">{$customerrow["firstname"]} {$customerrow["lastname"]}</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Amount:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".number_format($totalcost,2,'.','')."</font></td></tr>
		<tr><td align=\"right\" valign=\"top\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Products:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>";

		// Check if there are any parties booked today...
		if (file_exists("$ashoppath/customerparties.php")) {
			$today = date("Y-m-d", time()+$timezoneoffset);
			$today .= " 00:01 AM";
			$tomorrow = date("Y-m-d", time()+$timezoneoffset+86400);
			$tomorrow .= " 00:01 AM";
			if ($affiliateid) $partiesresult = @mysqli_query($db, "SELECT * FROM party WHERE date<'$tomorrow' AND approved='1' AND approved IS NOT NULL AND (ended='0' OR ended IS NULL) AND affiliateid='$affiliateid' ORDER BY date ASC");
			else $partiesresult = @mysqli_query($db, "SELECT * FROM party WHERE date<'$tomorrow' AND approved='1' AND approved IS NOT NULL AND (ended='0' OR ended IS NULL) ORDER BY date ASC");
			if (@mysqli_num_rows($partiesresult)) {
				 echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Party:</b></font></td><td><select name=\"partyid\" id=\"partyid\" onchange=\"updateparty();\">\n<option value=\"0\">none</option>\n";
				while ($partiesrow = @mysqli_fetch_array($partiesresult)) {
					echo "<option value=\"".$partiesrow["partyid"]."\">".$partiesrow["date"].", ".$partiesrow["location"]."</option>\n";
				}
				echo "</select></td></tr>";
			}
		} else echo "<input type=\"hidden\" name=\"partyid\" value=\"\">";


		if (!$isbill) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Payment Option:</b></font></td><td><select name=\"payoption\">$payoptionsstring</select> <input type=\"button\" value=\"Process payment\" onClick=\"payquote($quote,document.orderform.payoption.value)\"></td></tr>";
		else {
			$remindermessage = "Dear {$customerrow["firstname"]} {$customerrow["lastname"]},\n\nThis is a reminder to pay invoice number %orderid%.\nDue date: %duedate%\n\nTo view invoice details and to pay the invoice, please use this link.\n$ashopurl/payment.php?invoice=%orderid%\n\nOrder description: $displaydescr";
			$pastduemessage = "Dear {$customerrow["firstname"]} {$customerrow["lastname"]},\n\nOur records show that we still haven't received payment for invoice number %orderid%.\nDue date: %duedate%\n\nTo view invoice details and to pay the invoice, please use this link.\n$ashopurl/payment.php?invoice=%orderid%\n\nOrder description: $displaydescr";
			$billcomment = "This invoice is for service from %startdate% to %enddate%";
			echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Due Date:</b></font></td><td><input type=\"text\" size=\"15\" name=\"duedate\" value=\"$duedate\"></td></tr>\n<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>For service from:</b></font></td><td><input type=\"text\" size=\"15\" name=\"startdate\" value=\"$startdate\"> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>to:</b> <input type=\"text\" size=\"15\" name=\"enddate\" value=\"$enddate\"></font> <font size=\"1\" face=\"Arial, Helvetica, sans-serif\">[Optional]</font></td></tr>\n<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Add Comment:</b></font></td>\n<td><textarea name=\"billcomment\" cols=\"55\" rows=\"2\">$billcomment</textarea></td></tr>\n<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><input type=\"checkbox\" name=\"reminder\" checked> Remind:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" size=\"5\" name=\"reminderdays\" value=\"$reminderdays\"> days before due date if not paid.</font></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Reminder Message:</b></font></td><td><textarea name=\"remindermessage\" cols=\"55\" rows=\"5\">$remindermessage</textarea></td></tr><tr><td align=\"right\" nowrap><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b><input type=\"checkbox\" name=\"pastdue\" checked> Send Past Due Message:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" size=\"5\" name=\"pastduedays\" value=\"$pastduedays\"> days after due date if not paid.</font></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Past Due Message:</b></font></td><td><textarea name=\"pastduemessage\" cols=\"55\" rows=\"5\">$pastduemessage</textarea></td></tr><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Recurring:</b></font></td><td><select name=\"recurring\"><option value=\"none\">None<option value=\"weekly\">Weekly<option value=\"monthly\">Monthly<option value=\"quarterly\">Quarterly<option value=\"semiannually\">Semiannually<option value=\"annually\">Annually</select> <font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>Repeat: <input type=\"text\" size=\"3\" name=\"recurringtimes\" value=\"$recurringtimes\"> number of times </b></font><font size=\"1\" face=\"Arial, Helvetica, sans-serif\">[0 = indefinitely]</font></td></tr><tr><td align=\"right\">&nbsp;</td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">Create and send next bill <input type=\"text\" size=\"5\" name=\"sendbilldays\" value=\"$sendbilldays\"> days before its' due date.</font></td></tr>";
		}
		echo "</table>
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td width=\"100%\" align=\"center\" valign=\"top\"><br><input type=\"button\" name=\"cancel\" value=\"Cancel\" onClick=\"javascript:history.back()\" style=\"width: 80px\"  style=\"\"> ";
		if (!$isbill) echo "<input type=\"submit\" name=\"action\" value=\"Convert\" style=\"width: 80px\">";
		else echo "<input type=\"submit\" name=\"action\" value=\"Send Bill\" style=\"width: 80px\">";
		echo "</td>
		</tr></table><input type=\"hidden\" name=\"quote\" value=\"$quote\">
		</form>
		</center>";
}

echo "</table>
      </td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>
$footer";
?>