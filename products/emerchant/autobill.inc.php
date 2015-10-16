<?php
if (!$databaseserver) include "$ashoppath/admin/config.inc.php";
include "$ashoppath/admin/ashopfunc.inc.php";
$datetime = date("Y-m-d H:i:s", time()+$timezoneoffset);
$servertime = date("Y-m-d H:i:s", time());
$date = date("Y-m-d", time()+$timezoneoffset);
$datelong = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Get path to php executable for creating new bills...
$phppath = "";
$fp = fopen("$ashoppath/emerchant/autobill.php", "r");
if ($fp) {
	while (!feof($fp)) {
		$line = fread($fp, 8192);
		if (strstr($line, "#!")) {
			$phppath = str_replace("#!","",$line);
			$phppath = explode(" ",$phppath);
			$phppath = trim($phppath[0]);
		}
	}
	fclose($fp);
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Set mail parameters...
$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='customeremail'");
$ashopemail = @mysqli_result($result, 0, "confvalue");
$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";

// DEBUG: ashop_mail("$ashopemail","AutoBill Cron Job Report","The AutoBill cron job was run at $servertime server time","");

// Select bills...
$result = @mysqli_query($db, "SELECT emerchant_bills.*, orders.duedate, orders.paid FROM emerchant_bills, orders WHERE emerchant_bills.orderid=orders.orderid");
$iterationcheck = 1;
$recurringreport = "";
$reminderreport = "";
$pastduereport = "";
while ($row = @mysqli_fetch_array($result)) {

	// Get bill information...
	$orderid = $row["orderid"];
	$billnumber = $row["billnumber"];
	if (!$billnumber) $billnumber = 0;
	$paid = $row["paid"];
	$thisduedate = $row["duedate"];
	$reminderdate = $row["reminderdate"];
	$remindermessage = $row["remindermessage"];
	$remindersent = $row["remindersent"];
	$pastduedate = $row["pastduedate"];
	$pastduemessage = $row["pastduemessage"];
	$pastduesent = $row["pastduesent"];
	$recurring = $row["recurring"];
	$recurringtimes = $row["recurringtimes"];
	$sendbilldays = $row["sendbilldays"];
	if (!$sendbilldays) $sendbilldays = 0;
	$startdate = $row["startdate"];
	$enddate = $row["enddate"];
	$billcomment = $row["billcomment"]; 
	
	// Get order information...
	$orderresult = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$orderid'");
	$orderrow = @mysqli_fetch_array($orderresult);
	$customerid = $orderrow["customerid"];
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
	$customerrow = @mysqli_fetch_array($customerresult);
	$firstname = $customerrow["firstname"];
	$lastname = $customerrow["lastname"];
	$email = $customerrow["email"];
	$shippingresult = @mysqli_query($db, "SELECT shippingid FROM shipping WHERE customerid='$customerid'");
	$shippingid = @mysqli_result($shippingresult, 0, "shippingid");
	
	// Get due date for automatically created subscription bills...
	if (!$thisduedate) {
		if (!$billnumber) $sql = "SELECT duedate FROM emerchant_tempinvoices WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
		else $sql = "SELECT duedate FROM emerchant_tempinvoices WHERE orderid='$orderid' AND billnumber='$billnumber'";
		$tempinvoiceresult = @mysqli_query($db, $sql);
		$thisduedate = @mysqli_result($tempinvoiceresult,0,"duedate");
	}

	// Handle recurring bills...
	if ($recurring) {
		// Check if a new bill should be created...
		$duedatearray = explode("-",$thisduedate);
		$thisduedatetimestamp = mktime(0,0,0,$duedatearray[1],$duedatearray[2],$duedatearray[0]);
		$lastdaytobill = $thisduedatetimestamp - ($sendbilldays*86400);
		$reminderdatearray = explode("-",$reminderdate);
		$pastduedatearray = explode("-",$pastduedate);
		$startdatearray = explode("-",$startdate);
		$enddatearray = explode("-",$enddate);
		if ($recurring == "weekly") {
			if (is_array($duedatearray)) $nextduedatetimestamp = mktime(0,0,0,$duedatearray[1],$duedatearray[2]+7,$duedatearray[0]);
			$nextbilltimestamp = $nextduedatetimestamp - ($sendbilldays*86400);
			if ($reminderdate) $nextremindertimestamp = mktime(0,0,0,$reminderdatearray[1],$reminderdatearray[2]+7,$reminderdatearray[0]);
			if ($pastduedate) $nextpastduetimestamp = mktime(0,0,0,$pastduedatearray[1],$pastduedatearray[2]+7,$pastduedatearray[0]);
			if ($startdate) $nextstartdatetimestamp = mktime(0,0,0,$startdatearray[1],$startdatearray[2]+7,$startdatearray[0]);
			if ($enddate) $nextenddatetimestamp = mktime(0,0,0,$enddatearray[1],$enddatearray[2]+7,$enddatearray[0]);
		} else if ($recurring == "monthly") {
			if (is_array($duedatearray)) $nextduedatetimestamp = mktime(0,0,0,$duedatearray[1]+1,$duedatearray[2],$duedatearray[0]);
			$nextbilltimestamp = $nextduedatetimestamp - ($sendbilldays*86400);
			if ($reminderdate) $nextremindertimestamp = mktime(0,0,0,$reminderdatearray[1]+1,$reminderdatearray[2],$reminderdatearray[0]);
			if ($pastduedate) $nextpastduetimestamp = mktime(0,0,0,$pastduedatearray[1]+1,$pastduedatearray[2],$pastduedatearray[0]);
			if ($startdate) $nextstartdatetimestamp = mktime(0,0,0,$startdatearray[1]+1,$startdatearray[2],$startdatearray[0]);
			if ($enddate) $nextenddatetimestamp = mktime(0,0,0,$enddatearray[1]+1,$enddatearray[2],$enddatearray[0]);
		} else if ($recurring == "quarterly") {
			if (is_array($duedatearray)) $nextduedatetimestamp = mktime(0,0,0,$duedatearray[1]+3,$duedatearray[2],$duedatearray[0]);
			$nextbilltimestamp = $nextduedatetimestamp - ($sendbilldays*86400);
			if ($reminderdate) $nextremindertimestamp = mktime(0,0,0,$reminderdatearray[1]+3,$reminderdatearray[2],$reminderdatearray[0]);
			if ($pastduedate) $nextpastduetimestamp = mktime(0,0,0,$pastduedatearray[1]+3,$pastduedatearray[2],$pastduedatearray[0]);
			if ($startdate) $nextstartdatetimestamp = mktime(0,0,0,$startdatearray[1]+3,$startdatearray[2],$startdatearray[0]);
			if ($enddate) $nextenddatetimestamp = mktime(0,0,0,$enddatearray[1]+3,$enddatearray[2],$enddatearray[0]);
		} else if ($recurring == "semiannually") {
			if (is_array($duedatearray)) $nextduedatetimestamp = mktime(0,0,0,$duedatearray[1]+6,$duedatearray[2],$duedatearray[0]);
			$nextbilltimestamp = $nextduedatetimestamp - ($sendbilldays*86400);
			if ($reminderdate) $nextremindertimestamp = mktime(0,0,0,$reminderdatearray[1]+6,$reminderdatearray[2],$reminderdatearray[0]);
			if ($pastduedate) $nextpastduetimestamp = mktime(0,0,0,$pastduedatearray[1]+6,$pastduedatearray[2],$pastduedatearray[0]);
			if ($startdate) $nextstartdatetimestamp = mktime(0,0,0,$startdatearray[1]+6,$startdatearray[2],$startdatearray[0]);
			if ($enddate) $nextenddatetimestamp = mktime(0,0,0,$enddatearray[1]+6,$enddatearray[2],$enddatearray[0]);
		} else if ($recurring == "annually") {
			if (is_array($duedatearray)) $nextduedatetimestamp = mktime(0,0,0,$duedatearray[1],$duedatearray[2],$duedatearray[0]+1);
			$nextbilltimestamp = $nextduedatetimestamp - ($sendbilldays*86400);
			if ($reminderdate) $nextremindertimestamp = mktime(0,0,0,$reminderdatearray[1],$reminderdatearray[2],$reminderdatearray[0]+1);
			if ($pastduedate) $nextpastduetimestamp = mktime(0,0,0,$pastduedatearray[1],$pastduedatearray[2],$pastduedatearray[0]+1);
			if ($startdate) $nextstartdatetimestamp = mktime(0,0,0,$startdatearray[1],$startdatearray[2],$startdatearray[0]+1);
			if ($enddate) $nextenddatetimestamp = mktime(0,0,0,$enddatearray[1],$enddatearray[2],$enddatearray[0]+1);
		}

		$nextbilldate = date("Y-m-d",$nextbilltimestamp);

		if ($date >= $nextbilldate) {
			if ($recurringtimes) {
				if ($recurringtimes == "1") $recurring = "";
				$recurringtimes--;
			}				
			$duedate = date("Y-m-d",$nextduedatetimestamp);
			$newreminderdate = date("Y-m-d",$nextremindertimestamp);
			$newpastduedate = date("Y-m-d",$nextpastduetimestamp);
			if (!$startdate) $newstartdate = "";
			else $newstartdate = date("Y-m-d",$nextstartdatetimestamp);
			if (!$enddate) $newenddate = "";
			else $newenddate = date("Y-m-d",$nextenddatetimestamp);
			if (!$billnumber) $sql = "SELECT * FROM emerchant_tempinvoices WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
			else $sql = "SELECT * FROM emerchant_tempinvoices WHERE orderid='$orderid' AND billnumber='$billnumber'";
			$invoiceresult = @mysqli_query($db, $sql);
			$invoicerow = @mysqli_fetch_array($invoiceresult);
			@mysqli_query($db, "INSERT INTO orders (userid, customerid, products, price, ip, billdate, duedate) VALUES ('|1|','$shippingid','{$invoicerow["products"]}','{$invoicerow["price"]}','{$orderrow["ip"]}', '$datelong', '$duedate')");
			$neworderid = @mysqli_insert_id($db);
			$orderreference = "em".sprintf("%06d",$neworderid);
			$sql = "INSERT INTO emerchant_tempinvoices (orderid, billnumber, duedate, products, productprices, comments, commentprices, itemorder, commenttaxable, price, shipping) VALUES ('$neworderid', '$billnumber', '$duedate', '{$invoicerow["products"]}', '{$invoicerow["productprices"]}', '{$invoicerow["comments"]}', '{$invoicerow["commentprices"]}', '{$invoicerow["itemorder"]}', '{$invoicerow["commenttaxable"]}', '{$invoicerow["price"]}', '{$invoicerow["shipping"]}')";
			@mysqli_query($db, $sql);
			$sql = "INSERT INTO emerchant_bills (orderid, billnumber, reminderdate, remindermessage, pastduedate, pastduemessage, recurring, recurringtimes, sendbilldays, startdate, enddate, billcomment) VALUES ('$neworderid','$billnumber','$newreminderdate','".@mysqli_escape_string($db, $remindermessage)."','$newpastduedate','".@mysqli_escape_string($db, $pastduemessage)."','$recurring','$recurringtimes','$sendbilldays','$newstartdate','$newenddate','$billcomment')";
			@mysqli_query($db, $sql);
			if (!$billnumber) $sql = "UPDATE emerchant_bills SET recurring='' WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
			else $sql = "UPDATE emerchant_bills SET recurring='' WHERE orderid='$orderid' AND billnumber='$billnumber'";
			@mysqli_query($db, $sql);
			if ($billcomment) {
				$emerchantcomment = $billcomment;
				$emerchantcomment = str_replace("%orderid%",$neworderid,$emerchantcomment);
				$emerchantcomment = str_replace("%duedate%",$duedate,$emerchantcomment);
				$emerchantcomment = str_replace("%startdate%",$newstartdate,$emerchantcomment);
				$emerchantcomment = str_replace("%enddate%",$newenddate,$emerchantcomment);
				$emerchantcomment = urlencode($emerchantcomment);
			}
			$adminkey = md5("$databasepasswd$ashoppath"."prelcomplete");
			$querystring = "&ashoppath=$ashoppath&email={$customerrow["email"]}&firstname={$customerrow["firstname"]}&lastname={$customerrow["lastname"]}&address={$customerrow["address"]}&city={$customerrow["city"]}&zip={$customerrow["zip"]}&state={$customerrow["state"]}&country={$customerrow["country"]}&phone={$customerrow["phone"]}&invoice=$neworderid&adminkey=$adminkey&amount={$invoicerow["price"]}&products=0ashoporderstring{$invoicerow["products"]}{$invoicerow["shipping"]}&orderreference=$orderreference&quoteprices={$invoicerow["productprices"]}&emerchantcomment=$emerchantcomment";

			@mysqli_query($db, "INSERT INTO emerchant_autobillreport (orderid, previousorderid, customerid, event, date) VALUES ('$neworderid', '$orderid', '$customerid', 'recurring', '$datetime')");

			// Experimental replacement for billwrap...
			$billresult = ashop_simulatepost("$ashoppath/emerchant/billwrap.php",$querystring);
			if ($billresult != "SUCCESS") @ashop_mail("$ashopemail","AutoBill Cron Script Error in $ashopname","A problem occurred when creating a new AutoBill. The order processing script could not be run for invoice number: $neworderid!","");
			else {
				$recurringreport .= "Created new invoice: $neworderid preceeded by: $orderid and sent it to customer $customerid: $firstname $lastname\n";
				if ($paid) {
					if (!$billnumber) $sql = "DELETE FROM emerchant_bills WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
					else $sql = "DELETE FROM emerchant_bills WHERE orderid='$orderid' AND billnumber='$billnumber'";
					@mysqli_query($db, $sql);
				}
			}

			/*if ($phppath) {
				$billwrapresult = exec("$phppath $ashoppath/emerchant/billwrap.php \"$querystring\"");
				if (trim($billwrapresult) != "SUCCESS") @ashop_mail("$ashopemail","AutoBill Cron Script Error in $ashopname","A problem occurred when creating a new AutoBill. The order processing script could not be run for invoice number: $neworderid!","");
				else {
					$recurringreport .= "Created new invoice: $neworderid preceeded by: $orderid and sent it to customer $customerid: $firstname $lastname\n";
					if ($paid) {
						if (!$billnumber) $sql = "DELETE FROM emerchant_bills WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
						else $sql = "DELETE FROM emerchant_bills WHERE orderid='$orderid' AND billnumber='$billnumber'";
						@mysqli_query($db, $sql);
					}
				}
			} else @ashop_mail("$ashopemail","AutoBill Cron Script Error in $ashopname","A problem occurred when creating a new AutoBill. Could not find the path to command line php at the beginning of autobill.php.","");*/
		}
	}

	// Send past due messages...
	if ($pastduemessage && $pastduedate <= $date && !$pastduesent && !$paid) {
		$pastduereport .= "Sent past due notice for invoice $orderid to customer $customerid: $firstname $lastname\n";
		@mysqli_query($db, "INSERT INTO emerchant_autobillreport (orderid, previousorderid, customerid, event, date) VALUES ('$orderid', '', '$customerid', 'reminder', '$datetime')");
		$pastduemessage = str_replace("%orderid%",$orderid,$pastduemessage);
		$pastduemessage = str_replace("%duedate%",$thisduedate,$pastduemessage);
		$remindermessage = str_replace("%startdate%",$startdate,$remindermessage);
		$remindermessage = str_replace("%enddate%",$enddate,$remindermessage);
		@ashop_mail($email,"Invoice $orderid - Past Due!",$pastduemessage,$headers);

		// Save the past due message in the customer message log...
		@mysqli_query($db, "INSERT INTO emerchant_messages (customerid, user, replyto, date, subject) VALUES ('$customerid', '1', '0', '$date', 'Past Due Notice from $ashopname')");
		$messageid = @mysqli_insert_id($db);
		$fp = @fopen ("$ashoppath/emerchant/mail/cust-$messageid", "w");
		if ($fp) {
			fwrite($fp, $pastduemessage);
			fclose($fp);
		}

		// Past due notice has been sent, remove it from queue...
		$pastduesent = "1";
		if (!$billnumber) $sql = "UPDATE emerchant_bills SET pastduesent='1' WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
		else $sql = "UPDATE emerchant_bills SET pastduesent='1' WHERE orderid='$orderid' AND billnumber='$billnumber'";
		@mysqli_query($db, $sql);
	}

	// Send reminder messages...
	if ($remindermessage && $reminderdate <= $date && !$remindersent && !$pastduesent && !$paid) {
		$reminderreport .= "Sent reminder for invoice $orderid to customer $customerid: $firstname $lastname\n";
		@mysqli_query($db, "INSERT INTO emerchant_autobillreport (orderid, previousorderid, customerid, event, date) VALUES ('$orderid', '', '$customerid', 'reminder', '$datetime')");
		$remindermessage = str_replace("%orderid%",$orderid,$remindermessage);
		$remindermessage = str_replace("%duedate%",$thisduedate,$remindermessage);
		$remindermessage = str_replace("%startdate%",$startdate,$remindermessage);
		$remindermessage = str_replace("%enddate%",$enddate,$remindermessage);
		@ashop_mail($email,"Reminder To Pay Invoice $orderid!",$remindermessage,$headers);

		// Save the reminder message in the customer message log...
		@mysqli_query($db, "INSERT INTO emerchant_messages (customerid, user, replyto, date, subject) VALUES ('$customerid', '1', '0', '$date', 'Payment Reminder from $ashopname')");
		$messageid = @mysqli_insert_id($db);
		$fp = @fopen ("$ashoppath/emerchant/mail/cust-$messageid", "w");
		if ($fp) {
			fwrite($fp, $remindermessage);
			fclose($fp);
		}

		// Reminder has been sent, remove it from queue...
		$remindersent = "1";
		if (!$billnumber) $sql = "UPDATE emerchant_bills SET remindersent='1' WHERE orderid='$orderid' AND (billnumber='' OR billnumber IS NULL)";
		else $sql = "UPDATE emerchant_bills SET remindersent='1' WHERE orderid='$orderid' AND billnumber='$billnumber'";
		@mysqli_query($db, $sql);
	}

	$iterationcheck++;
}
if ($recurringreport || $reminderreport || $pastduereport) @ashop_mail("$ashopemail","AutoBill Activity Report $datetime","$recurringreport\n$reminderreport\n$pastduereport\n","");
?>