#!/usr/bin/php -q
<?php
####################################################################################
##                                                                                ##
##                    AShop Subscription Check for AShop V	                      ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $ashoppath variable to the correct path             ##
##                 to your AShop on the server.                                   ##
##              2. Change the message templates if you want to.					  ##
##              3. Increase the timeout if your customer list is huge.			  ##
##              4. Make this script executable: chmod 755 checksubscr.php.        ##
##              5. Add a Cron job that runs this script once every day.           ##
##                                                                                ##
####################################################################################

$ashoppath = "/home/domain/public_html/ashop";

// Template for message to be sent on expired subscriptions to protected directories:

$messagetemplate1 = "<html><head><title>Subscription has expired!</title></head><body><font face=\"Arial, Helvetica, sans-serif\">Dear %firstname% %lastname%, your subscription to <b>%protectedurl%</b> has expired and is now deactivated.</font></body></html>";

// Template for message to be sent on expired membership discount codes:

$messagetemplate2 = "<html><head><title>Subscription has expired!</title></head><body><font face=\"Arial, Helvetica, sans-serif\">Dear %firstname% %lastname%, your membership discount code <b>%discountcode%</b> has expired and is now deactivated.</font></body></html>";

// Keep this script running for a maximum of...
$timeoutseconds = 300; // 5 minutes. Just in case.

####################################################################################
##                                                                                ##
##                           Do not edit below this.                              ##
##                                                                                ##
####################################################################################

include "$ashoppath/admin/config.inc.php";
include "$ashoppath/admin/ashopfunc.inc.php";

// Open a database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Set starttime for timeout...
$starttime = time();

// Select and iterate through all protected directory subscribers...
$result = @mysqli_query($db, "SELECT * FROM product WHERE prodtype = 'subscription' AND (copyof='' OR copyof='0' OR copyof IS NULL)");
while ($row = @mysqli_fetch_array($result)) {
	if  (time()-$starttime > $timeoutseconds) exit;
	$subscriptiondir = $row["subscriptiondir"];
	$protectedurl = $row["protectedurl"];
	if (empty($protectedurl)) $protectedurl = $subscriptiondir;
	$productid = $row["productid"];
	$length = $row["length"];
	$firsttime = time()+$timezoneoffset - ($length * 86400);
	if ($subscriptiondir && file_exists("$ashopspath/$subscriptiondir/.htpasswd")) {
		$htpasswdupdated = FALSE;
		$tempfp = fopen("$ashopspath/updates/.temppasswd","w");
		$fp = fopen("$ashoppath/$subscriptiondir/.htpasswd", "r");
		if ($fp) {
			while (!feof($fp)) {
				if (time()-$starttime > $timeoutseconds) exit;
				$line = fgets($fp, 4096);
				$lineparts = explode(":",$line);
				$customeremail = trim($lineparts[0]);
				$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE email='$customeremail' OR alternativeemails LIKE '%$customeremail%'");
				$customerid = @mysqli_result($customerresult,0,"customerid");
				$firstname = @mysqli_result($customerresult, 0, "firstname");
				$lastname = @mysqli_result($customerresult, 0, "lastname");
				$orderresult = @mysqli_query($db, "SELECT paid FROM orders WHERE (products LIKE '%b$productid"."a%' OR products LIKE '%b$productid"."d%') AND customerid = '$customerid' ORDER BY paid DESC LIMIT 1");
				$paiddate = @mysqli_result($orderresult,0,"paid");

				// No orders for this customer. The password will be deleted...
				if (!$paiddate) $htpasswdupdated = TRUE;
				else {

					// Check if the subscription has expired...
					$orderdatearray = explode(" ",$paiddate);
					$orderdate = explode ("-",$orderdatearray[0]);
					$ordertime = explode (":",$orderdatearray[1]);
					$orderedtimestamp = mktime($ordertime[0],$ordertime[1],$ordertime[2],$orderdate[1],$orderdate[2],$orderdate[0]);
					if ($orderedtimestamp < $firsttime) {
						$htpasswdupdated = TRUE;
						
						// Send message about expiration to customer and administrator...
						$message = str_replace("%protectedurl%","$protectedurl",$messagetemplate1);
						$message = str_replace("%firstname%","$firstname",$message);
						$message = str_replace("%lastname%","$lastname",$message);
						$message = str_replace("%email%","$customeremail",$message);
						$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

						@ashop_mail("$customeremail","Your subscription at $ashopname has expired!","$message","$headers");
						
						$message="<html><head><title>Subscription has expired!</title></head><body><font face=\"$font\">Subscription to <b>$protectedurl</b> has expired for customer $customerid: $firstname $lastname, email: $customeremail.</font></body></html>";
						$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
						
						@ashop_mail("$ashopemail","Expired Subscription","$message","$headers");
					} else fwrite($tempfp, $line);
				}
			}
			fclose($fp);
			fclose($tempfp);
			if ($htpasswdupdated) copy ("$ashopspath/updates/.temppasswd", "$ashoppath/$subscriptiondir/.htpasswd");
			unlink ("$ashopspath/updates/.temppasswd");
		}
	}
}

// Select and iterate through all membership discount codes...
$result = @mysqli_query($db, "SELECT * FROM storediscounts WHERE customerid != '' AND customerid IS NOT NULL");
while ($row = @mysqli_fetch_array($result)) {
	if  (time()-$starttime > $timeoutseconds) exit;
	$customerid = $row["customerid"];
	$discountcode = $row["code"];
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
	$customerrow = @mysqli_fetch_array($customerresult);
	$firstname = $customerrow["firstname"];
	$lastname = $customerrow["lastname"];
	$email = $customerrow["email"];

	// Select and iterate through all discount code fulfilment options...
	$fulfilresult = @mysqli_query($db, "SELECT * FROM fulfiloptions WHERE method='discount'");
	$subscribed = FALSE;
	while ($fulfilrow = @mysqli_fetch_array($fulfilresult)) {
		if  (time()-$starttime > $timeoutseconds) exit;
		$fulfiloptionid = $fulfilrow["fulfiloptionid"];

		// Select and iterate through all products using this fulfilment option...
		$productresult = @mysqli_query($db, "SELECT * FROM product WHERE fulfilment='$fulfiloptionid' AND prodtype='subscription' AND (copyof='' OR copyof='0' OR copyof IS NULL)");
		if (@mysqli_num_rows($productresult)) {
			while ($productrow = @mysqli_fetch_array($productresult)) {
				if  (time()-$starttime > $timeoutseconds) exit;
				$productid = $productrow["productid"];
				$length = $productrow["length"];
				$firsttime = time()+$timezoneoffset - ($length * 86400);
				$orderresult = @mysqli_query($db, "SELECT paid FROM orders WHERE (products LIKE '%b$productid"."a%' OR products LIKE '%b$productid"."d%') AND customerid = '$customerid' ORDER BY paid DESC LIMIT 1");
				if (@mysqli_num_rows($orderresult)) {
					$paiddate = @mysqli_result($orderresult,0,"paid");
					if (!empty($paiddate)) {
						// Check if the subscription has expired...
						$orderdatearray = explode(" ",$paiddate);
						$orderdate = explode ("-",$orderdatearray[0]);
						$ordertime = explode (":",$orderdatearray[1]);
						$orderedtimestamp = mktime($ordertime[0],$ordertime[1],$ordertime[2],$orderdate[1],$orderdate[2],$orderdate[0]);
						if ($orderedtimestamp >= $firsttime) $subscribed = TRUE;
					}
				}
			}
		}
	}
	if (!$subscribed) {
		// Remove expired code...
		@mysqli_query($db, "DELETE FROM storediscounts WHERE code='$discountcode' AND customerid='$customerid'");

		// Send message about expiration to customer and administrator...
		$message = str_replace("%discountcode%","$discountcode",$messagetemplate2);
		$message = str_replace("%firstname%","$firstname",$message);
		$message = str_replace("%lastname%","$lastname",$message);
		$message = str_replace("%email%","$email",$message);
		$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

		@ashop_mail("$email","Your membership discount at $ashopname has expired!","$message","$headers");

		$message="<html><head><title>Membership discount has expired!</title></head><body><font face=\"$font\">The membership discount <b>$discountcode</b> has expired for customer $customerid: $firstname $lastname, email: $email.</font></body></html>";
		$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

		@ashop_mail("$ashopemail","Expired Membership Discount","$message","$headers");
	}
}
@mysqli_close($db);
?>