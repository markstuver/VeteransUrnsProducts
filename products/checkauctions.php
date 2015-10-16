#!/usr/bin/php -q
<?php
####################################################################################
##                                                                                ##
##                        AShop Subscription Check for AShop                      ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $ashoppath variable to the correct path             ##
##                 to your AShop on the server.                                   ##
##              2. Add a Cron job that runs this script once every ten minutes.   ##
##                                                                                ##
####################################################################################

$ashoppath = "/home/domain/public_html/ashop";

// Keep this script running for a maximum of...
$timeoutseconds = 300; // 5 minutes. Just in case.

####################################################################################
##                                                                                ##
##                           Do not edit below this.                              ##
##                                                                                ##
####################################################################################

include "$ashoppath/admin/config.inc.php";
include "$ashoppath/admin/ashopfunc.inc.php";

// Open database...
$ub_db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$ub_db) $ub_error = 1;

// Function for extracting shipping and tax info...
if (!function_exists(ub_gethandlingcost)) {
	function ub_gethandlingcost($productstring) {
		$items = explode("a", $productstring);
		$arraycount = 1;
		if ($items[0] && count($items)==1) $arraycount = 0;
		for ($i = 0; $i < count($items)-$arraycount; $i++) {
			$thisitem = explode("b", $items[$i]);
			if ($thisitem[0] == "sh") $handlinginfo["shipping"] = $thisitem[1];
			if ($thisitem[0] == "st") $handlinginfo["salestax"] = $thisitem[1];
			if (strstr($thisitem[0], "so")) $handlinginfo[$thisitem[0]] = str_replace("so", "", $thisitem[0]);
			if ($thisitem[0] == "sd") $handlinginfo["discount"] = $thisitem[1];
		}
		return $handlinginfo;
	}
}

// Function for sending email...
if(!function_exists('ub_mail')) {
	function ub_mail($recipient, $subject, $message, $headers) {
		global $ashoppath, $ashopname, $ashopemail, $mailertype, $mailerserver, $mailerport, $maileruser, $mailerpass;
		if ($mailertype == "smtp") {
			require_once "$ashoppath/includes/class.phpmailer.php";
			$mail = new PHPMailer();
			$mail->Body = $message;
			if ($mailerserver && $maileruser && $mailerpass) {
				$headersarray = explode("\n",$headers);
				if (!empty($headersarray) && is_array($headersarray)) {
					foreach ($headersarray as $header) {
						$thisheaderarray = explode(": ",$header);
						if (isset($thisheaderarray[0]) && $thisheaderarray[0] == "From") {
							$fullsender = $thisheaderarray[1];
							$senderarray = explode ("<",$fullsender);
							if (isset($senderarray[1])) {
								$sendername = $senderarray[0];
								$senderemail = str_replace(">","",$senderarray[1]);
							} else $senderemail = $fullsender;
						}
					}
				}
				if (!$senderemail) $senderemail = $ashopemail;
				if (!$sendername) $sendername = $ashopname;
				if (!$mailerport) $mailerport = "25";
				$mail->Port = $mailerport;
				$mail->IsSMTP();
				//$mail->SMTPDebug = 2;
				$mail->Host = $mailerserver;
				$mail->SMTPAuth = true;
				$mail->Username = $maileruser;
				$mail->Password = $mailerpass;
				$mail->From = $senderemail;
				$mail->FromName = $sendername;
				$mail->Subject = $subject;
				$mail->AddAddress($recipient, $recipientname);
				$result = $mail->Send();
				if (!$result) $result = $mail->ErrorInfo;
			} else $result = "SMTP Configuration Error";
		} else $result = @mail($recipient, $subject, $message, $headers);
		if ($openeddb) @mysqli_close($db);
		return $result;
	}
}

// Set starttime for timeout...
$starttime = time();

// Get product information...
$ub_now = time();
$ub_result = @mysqli_query($ub_db, "SELECT * FROM floatingprice WHERE starttime+length < '$ub_now' AND (endprice='' OR endprice IS NULL)");
while ($ub_row = @mysqli_fetch_array($ub_result)) {
	if  (time()-$starttime > $timeoutseconds) exit;
	$ub_startprice = $ub_row["startprice"];
	$ub_priceincrement = $ub_row["priceincrement"];
	$ub_bids = $ub_row["bids"];
	$ub_bidderid = $ub_row["bidderid"];
	$ub_productid = $ub_row["productid"];
	$ub_auctiontype = $ub_row["type"];

	// Calculate the current bidding price...
	$ub_currentprice = number_format($ub_startprice + ($ub_priceincrement*$ub_bids),2,'.','');
	$ub_winningprice = $ub_currentprice;
	$ub_productresult = @mysqli_query($ub_db, "SELECT * FROM product WHERE productid='$ub_productid'");
	@mysqli_query($ub_db, "UPDATE floatingprice SET startprice=originalstartprice, endprice='$ub_currentprice' WHERE productid='$ub_productid'");
	@mysqli_query($ub_db, "UPDATE product SET price='$ub_currentprice' WHERE productid='$ub_productid'");

	// Check if we have the winner's email on file...
	if (!empty($ub_bidderid) && is_numeric($ub_bidderid)) {
		$ub_winnerresult = @mysqli_query($ub_db, "SELECT customerid FROM pricebidder WHERE bidderid='$ub_bidderid'");
		$ub_winnerid = @mysqli_result($ub_winnerresult,0,"customerid");
		$ub_customerresult = @mysqli_query($ub_db, "SELECT * FROM customer WHERE customerid='$ub_winnerid'");
		$ub_customerrow = @mysqli_fetch_array($ub_customerresult);
		$ub_firstname = $ub_customerrow["firstname"];
		$ub_lastname = $ub_customerrow["lastname"];
		$ub_email = $ub_customerrow["email"];
	} else $ub_email = "";

	// Contact the winner by email...
	if (!empty($ub_email)) {
		$ub_productname = @mysqli_result($ub_productresult,0,"name");
		$ub_producturl = @mysqli_result($ub_productresult,0,"detailsurl");
		$ub_productowner = @mysqli_result($ub_productresult,0,"userid");
		// Include language file...
		if (!$ub_lang) $ub_lang = $defaultlanguage;
		include "$ashoppath/language/$ub_lang/bidregister.inc.php";
		$ub_messagefile = "";
		$ub_messagetemplate = "";
		if (file_exists("$ashoppath/templates/messages/winningbidmessage-$ub_lang.html")) $ub_messagefile = "$ashoppath/templates/messages/winningbidmessage-$ub_lang.html";
		else if (file_exists("$ashoppath/templates/messages/winningbidmessage.html")) $ub_messagefile = "$ashoppath/templates/messages/winningbidmessage.html";
		if (!empty($ub_messagefile)) {
			$ub_fp = @fopen("$ub_messagefile","r");
			if ($ub_fp) {
				while (!feof ($ub_fp)) $ub_messagetemplate .= fgets($ub_fp, 4096);
				fclose($ub_fp);
			}
			if (!empty($ub_messagetemplate)) {
				$ub_date = date("Y-m-d H:i:s", time()+$timezoneoffset);
				$ub_currentprice = $currencysymbols[$ashopcurrency]["pre"].$ub_currentprice.$currencysymbols[$ashopcurrency]["post"];
				$ub_message = str_replace("%ashopname%",$ashopname,$ub_messagetemplate);
				if (!empty($ub_producturl)) $ub_message = str_replace("%itemurl%",$ub_producturl,$ub_message);
				else $ub_message = str_replace("%itemurl%","$ashopurl/product.php?product=$ub_productid",$ub_message);
				$ub_message = str_replace("%firstname%",$ub_firstname,$ub_message);
				$ub_message = str_replace("%lastname%",$ub_lastname,$ub_message);
				$ub_message = str_replace("%email%",$ub_email,$ub_message);
				$ub_message = str_replace("%date%",$ub_date,$ub_message);
				$ub_message = str_replace("%winningbid%",$ub_currentprice,$ub_message);
				$ub_message = str_replace("%itemname%",$ub_productname,$ub_message);
				$ub_ashopname = str_replace(",","",$ashopname);
				$ub_ashopname = str_replace(".","",$ub_ashopname);
				$ub_transtable = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
				$ub_transtable = array_flip($ub_transtable);
				$ub_ashopname = strtr($ub_ashopname, $ub_transtable);
				$ub_headers = "From: $ub_ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				@ub_mail("$ub_email","$ub_ashopname ".WINNINGBIDSUBJECT,"$ub_message","$ub_headers");

				// The customer has been informed, it is safe to hide the product...
				@mysqli_query($ub_db, "UPDATE product SET active='0' WHERE productid='$ub_productid'");
			}
			// Create and send an invoice...
			if ($ub_auctiontype == "standard") {
				// Create product string...
				$ub_products = "1b{$ub_productid}a";

				// Get shipping and handling cost...
				$ub_customerid = $ub_winnerid;
				$ub_productshipping = @mysqli_result($ub_productresult,0,"shipping");
				$ub_producttaxable = @mysqli_result($ub_productresult,0,"taxable");
				if ($ub_productshipping || $ub_producttaxable) $ub_calculateshipping = TRUE;
				else $ub_calculateshipping = FALSE;
				if($ub_calculateshipping) {
					$ub_shippingresult = @mysqli_query($ub_db, "SELECT * FROM shipping WHERE customerid='$ub_winnerid'");
					if (@mysqli_num_rows($ub_shippingresult)) {
						$ub_shippingrow = @mysqli_fetch_array($ub_shippingresult);
						$ub_customerid = $ub_shippingrow["shippingid"];
						$ub_shippingfirstname = $ub_shippingrow["shippingfirstname"];
						$ub_shippinglastname = $ub_shippingrow["shippinglastname"];
						$ub_shippingaddress = $ub_shippingrow["shippingaddress"];
						$ub_shippingcity = $ub_shippingrow["shippingcity"];
						$ub_shippingzip = $ub_shippingrow["shippingzip"];
						$ub_shippingcountry = $ub_shippingrow["shippingcountry"];
						$ub_shippingstate = $ub_shippingrow["shippingstate"];
					}
					if (!$ub_shippingfirstname || !$ub_shippinglastname || !$ub_shippingaddress || !$ub_shippingcity || !$ub_shippingzip || !$ub_shippingcountry) {
						$ub_shippingfirstname = $ub_customerrow["firstname"];
						$ub_shippinglastname = $ub_customerrow["lastname"];
						$ub_shippingaddress = $ub_customerrow["address"];
						$ub_shippingcity = $ub_customerrow["city"];
						$ub_shippingzip = $ub_customerrow["zip"];
						$ub_shippingcountry = $ub_customerrow["country"];
						$ub_shippingstate = $ub_customerrow["state"];
					}
					$ub_querystring = "quote=$ub_products&destfirstname={$ub_shippingfirstname}&destlastname={$ub_shippinglastname}&destaddress={$ub_shippingaddress}&destcity={$ub_shippingcity}&destzip={$ub_shippingzip}&destcntry={$ub_shippingcountry}&deststate={$ub_shippingstate}";
					if (strpos($ashopurl, "/", 8)) {
						$ub_urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
						$ub_urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
					} else {
						$ub_urlpath = "/";
						$ub_urldomain = $ashopurl;
					}
					if ($ub_urlpath == "/") $ub_scriptpath = "shipping.php";
					else $ub_scriptpath = "/shipping.php";
					$ub_urldomain = str_replace("http://", "", $ub_urldomain);
					$ub_postheader = "POST $ub_urlpath$ub_scriptpath HTTP/1.0\r\nHost: $ub_urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($ub_querystring)."\r\n\r\n";
					$ub_fp = @fsockopen ($ub_urldomain, 80, $ub_errno, $ub_errstr, 10);
					if ($ub_fp) {
						fputs ($ub_fp, $ub_postheader.$ub_querystring);
						unset($ub_res);
						while (!feof($ub_fp)) $ub_res .= fgets ($ub_fp, 1024);
						fclose ($ub_fp);
						$ub_handlingcostarray = explode("\n",$ub_res);
						$ub_handlingcoststring = $ub_handlingcostarray[count($ub_handlingcostarray)-1];
					}
					if ($ub_handlingcoststring) $ub_handlingcosts = ub_gethandlingcost($ub_handlingcoststring);
					$ub_thisshipping = $ub_handlingcosts["shipping"];
					$ub_thissalestax = $ub_handlingcosts["salestax"];
					$ub_totalcost = $ub_winningprice+$ub_thisshipping+$ub_thissalestax;
				} else $ub_totalcost = $ub_winningprice;

				// Calculate due date...
				$ub_result = @mysqli_query($ub_db, "SELECT * FROM emerchant_configuration WHERE confname='defaultduedays'");
				$ub_defaultduedays = @mysqli_result($ub_result,0,"confvalue");
				$ub_duedatetimestamp = time();
				$ub_duedatetimestamp += $ub_defaultduedays*86400;
				$ub_duedate = date("Y-m-d", $ub_duedatetimestamp+$timezoneoffset);

				// Set bill date to today...
				$ub_billdate = date("Y-m-d", time()+$timezoneoffset);

				// Add orders row...
				$ub_result = @mysqli_query($ub_db, "INSERT INTO orders (userid, customerid, products, productprices, price, ip, billdate, duedate, affiliateid) VALUES ('|{$ub_productowner}|','{$ub_customerid}','$ub_products$ub_handlingcoststring','{$ub_productid}:{$ub_winningprice}','$ub_totalcost','{$_SERVER["REMOTE_ADDR"]}', '$ub_billdate', '$ub_duedate', '$affiliate')");
				$ub_orderid = @mysqli_insert_id($ub_db);

				// Add invoice row...
				@mysqli_query($ub_db, "INSERT INTO emerchant_tempinvoices (orderid, products, productprices, itemorder, price, shipping) VALUES ('$ub_orderid','$ub_products','{$ub_productid}:{$ub_winningprice}','p','$ub_totalcost','$ub_thisshipping')");

				// Set the admin key for order processing...
				$ub_adminkey = md5("$databasepasswd$ashoppath"."prelcomplete");

				// Post to order.php to process and send the invoice...
				$ub_querystring = "email={$ub_customerrow["email"]}&firstname={$ub_customerrow["firstname"]}&lastname={$ub_customerrow["lastname"]}&address={$ub_customerrow["address"]}&city={$ub_customerrow["city"]}&zip={$ub_customerrow["zip"]}&state={$ub_customerrow["state"]}&country={$ub_customerrow["country"]}&phone={$ub_customerrow["phone"]}&invoice=$ub_orderid&adminkey=$ub_adminkey&amount=$ub_totalcost&products=0ashoporderstring{$ub_products}{$ub_handlingcoststring}&orderreference=au$ub_orderid&emerchantquote=$ub_orderid&quoteprices={$ub_productid}:{$ub_winningprice}";
				if (strpos($ashopurl, "/", 8)) {
					$ub_urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
					$ub_urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
				} else {
					$ub_urlpath = "/";
					$ub_urldomain = $ashopurl;
				}
				if ($ub_urlpath == "/") $ub_scriptpath = "order.php";
				else $ub_scriptpath = "/order.php";
				$ub_urldomain = str_replace("http://", "", $ub_urldomain);
				$ub_postheader = "POST $ub_urlpath$ub_scriptpath HTTP/1.0\r\nHost: $ub_urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($ub_querystring)."\r\n\r\n";
				$ub_fp = fsockopen ("$ub_urldomain", 80);
				unset($ub_res);
				if ($ub_fp) {
					$ub_response = fwrite ($ub_fp, $ub_postheader . $ub_querystring);
					while (!feof($ub_fp)) $ub_res .= fgets ($ub_fp, 1024);
					fclose ($ub_fp);
				}
				@mysqli_query($ub_db, "UPDATE orders SET source='Auction' WHERE orderid='$ub_orderid'");
				@mysqli_query($ub_db, "UPDATE memberorders SET auction='1' WHERE orderid='$ub_orderid'");
			}
		}
	}
}
@mysqli_close($ub_db);
?>