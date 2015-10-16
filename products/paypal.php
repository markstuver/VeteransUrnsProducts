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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

if (!$token && !$invoice) {
	header("Location: $ashopurl/checkout.php");
	exit;
}

// Initialize variables...
if (!isset($shop)) $shop = 1;
if (!empty($shop) && !is_numeric($shop)) $shop = 1;

$ashopcurrency = strtoupper($ashopcurrency);

if ($_SERVER['HTTPS'] == "on") $secureconnection = TRUE;
else $secureconnection = FALSE;

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/paypal.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/cart.html")) $templatepath = "/members/files/$ashopuser";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get amounts...
if ($invoice) {
	$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$invoice'");
	$row = @mysqli_fetch_array($result);
	$amount = $row["price"];
	$customerid = $row["customerid"];
	$parsed_products = $row["products"];
	$description = $row["description"];
	if($customerid != "0") {
		$checkcustomerresult = @mysqli_query($db, "SELECT level FROM customer WHERE customerid='$customerid'");
		$pricelevel = @mysqli_result($checkcustomerresult,0,"level");
		if (!$pricelevel) $pricelevel = 0;
		$result = @mysqli_query($db, "SELECT * FROM shipping WHERE shippingid='$customerid'");
		$row = @mysqli_fetch_array($result);
		$shippingfirstname = $row["shippingfirstname"];
		$shippinglastname = $row["shippinglastname"];
		$shippingaddress = $row["shippingaddress"];
		$shippingaddress2 = $row["shippingaddress2"];
		$shippingcity = $row["shippingcity"];
		$shippingzip = $row["shippingzip"];
		$shippingstate = $row["shippingstate"];
		$shippingcountry = $row["shippingcountry"];
		$shippingphone = $row["shippingphone"];
	}
	// Create separate payments for Digital Mall member shops...
	$members = explode("|", $row["userid"]);
	$thismember = 1;
	$totalqty = ashop_totalqty($parsed_products);
	foreach ($members as $membernumber=>$memberid) {
		if ($memberid > 1) {
			$thismembertotal = 0;
			$thismemberdescr = "";
			$thismemberproducts = ashop_memberproductstring($db, $parsed_products, $memberid);
			$productsincart = ashop_parseproductstring($db, $thismemberproducts);
			if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
				$thisproductid = $thisproduct["productid"];
				$thisquantity = $thisproduct["quantity"];
				$thisproductname = $thisproduct["name"];
				if ($pricelevel < 1) $thisprice = $thisproduct["price"];
				else if ($pricelevel == 1) $thisprice = $thisproduct["wholesaleprice"];
				else {
					$pricelevels = $thisproduct["wspricelevels"];
					$thisprice = $pricelevels[$pricelevel-2];
				}
				if (!$thisproduct["qtytype"] || $thisproduct["qtytype"] == "1" || $thisproduct["qtytype"] == "3") $subtotalqty = $thisquantity;
				else {
					if (!$thisproduct["qtycategory"]) $subtotalqty = $totalqty;
					else $subtotalqty = ashop_categoryqty($db, $parsed_products, $thisproduct["qtycategory"]);
				}
				// Check if this product has been sold at a discounted price...
				if ($thisproduct["discounted"] == "true") {
					if ($thisproduct["storewidediscount"] != "true") {
						$thisdiscountid = $productdiscounts["$thisproductid"];
						$sql="SELECT * FROM discount WHERE productid='$thisproductid' AND discountid='$thisdiscountid'";
						$result = @mysqli_query($db, "$sql");
						if (@mysqli_num_rows($result)) {
							$discounttype = @mysqli_result($result, 0, "type");
							$discountvalue = @mysqli_result($result, 0, "value");
							$discountcode = @mysqli_result($result, 0, "code");
							$thisproductdiscount = md5($thisproductid.$discountcode."ashopdiscounts");
							if ($discounttype == "%") {
								$totaldiscount += $thisprice * ($discountvalue/100);
								if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $thisprice * ($discountvalue/100);
							} else if ($discounttype == "$") {
								$totaldiscount += $discountvalue;
								if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $discountvalue;
							}
						} else {
							$sql="SELECT * FROM storediscounts WHERE discountid='$thisdiscountid' AND categoryid!='' AND categoryid IS NOT NULL";
							$result = @mysqli_query($db, "$sql");
							if (@mysqli_num_rows($result)) {
								$discountcategory = @mysqli_result($result, 0, "categoryid");
								$result2 = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$thisproductid' AND categoryid='$discountcategory'");
								if (@mysqli_num_rows($result2)) {
									$discounttype = @mysqli_result($result, 0, "type");
									$discountvalue = @mysqli_result($result, 0, "value");
									$discountcode = @mysqli_result($result, 0, "code");
									$thisproductdiscount = md5($thisproductid.$discountcode."ashopdiscounts");
									if ($discounttype == "%") {
										$totaldiscount += $thisprice * ($discountvalue/100);
										if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $thisprice * ($discountvalue/100);
									} else if ($discounttype == "$") {
										$totaldiscount += $discountvalue;
										if ($thisproductowner > 1) $memberdiscount[$thisproductowner] += $discountvalue;
									}
								}
							}
						}
					}
					$thisproductname .= " (discounted)";
				}
				$thissubtotal = ashop_subtotal($db, $thisproductid, $subtotalqty, $thisquantity, $thisproductdiscount, $thisprice, $thisproduct["qtytype"]);
				$amounts[$thismember] += $thissubtotal;
				$descriptions[$thismember] .= "$thisquantity: $thisproductname";
			}
			// Get the member's PayPal ID and commission level...
			if ($amounts[$thismember]) {
				$sql="SELECT * FROM user WHERE userid='$memberid'";
				$result = @mysqli_query($db, "$sql");
				$paymentdetails = @mysqli_result($result, 0, "paymentdetails");
				if (strstr($paymentdetails,"PayPal")) {
					$paypalid = str_replace("My PayPal ID is:","",$paymentdetails);
					$paypalid = str_replace("PayPal","",$paypalid);
					$paypalid = trim($paypalid);
					$vendors[$thismember] = $paypalid;
					$commissionlevel = @mysqli_result($result, 0, "commissionlevel");
					if (!$commissionlevel) $commissionlevel = $memberpercent;
					$amounts[$thismember] = $amounts[$thismember] * ($commissionlevel/100);
					$thismember++;
				} else {
					unset($amounts[$thismember]);
					unset($descriptions[$thismember]);
				}
			}
		}
	}
}

// Get payment option information...
if ($memberpayoptions && !empty($shop) && $shop > 1) $result = @mysql_query("SELECT * FROM payoptions WHERE gateway='paypalec' AND userid='$shop'",$db);
else $result = @mysql_query("SELECT * FROM payoptions WHERE gateway='paypalec' AND userid='1'",$db);
if (!@mysqli_num_rows($result)) {
	header("Location: $ashopurl/checkout.php");
	exit;
}
$row = @mysqli_fetch_array($result);
$payoption = $row["payoptionid"];
$apiusername = $row["merchantid"];
$apipassword = $row["secret"];
$apisignature = $row["transactionkey"];
$paypalid = $row["paypalid"];
$gateway = $row["gateway"];
$fee = $row["fee"];
$amount += $fee;
$logourl = $row["logourl"];
if ($row["testmode"]) $testmode = "TRUE";
else unset($testmode);

// Calculate the site owner's fee for parallel payments...
if (count($amounts)) {
	$totalmembercommission = 0;
	foreach($amounts as $thisamount) $totalmembercommission += $thisamount;
	$amounts[0] = $amount-$totalmembercommission;
	$vendors[0] = $paypalid;
	$descriptions[0] = $description;
}

include "admin/paypalfunctions.php";

// PayPal Website Payment Pro Settings...
if ($testmode) {
	$environment = "Sandbox";
	$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
	$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
} else {
	$environment = "Live";
	$API_Endpoint = "https://api-3t.paypal.com/nvp";
	$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
}
$paymentType = "Sale";

$sBNCode = "AShopSoftware_ShoppingCart_EC_US";

// Initialize payment...
if (!$token) {
	$amount = number_format($amount,2,'.','');

	$paypaldescription = substr($description, 0, 127);

	$resArray = CallMarkExpressCheckout( $amount, $paypaldescription, $invoice, $ashopcurrency, $paymentType, "$ashopsurl/paypal.php", $ashopurl, $logourl, "$shippingfirstname $shippinglastname", $shippingaddress, $shippingcity, $shippingstate, $shippingcountry, $shippingzip, $shippingaddress2, $shippingphone, $affiliate, $vendors, $amounts, $descriptions );
	$ack = strtoupper($resArray["ACK"]);
	if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING") {
		RedirectToPayPal ( $resArray["TOKEN"] );
		exit;
	} else {
		$error = urldecode($resArray["L_LONGMESSAGE0"]);
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		}
		echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$error<br><br><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></font></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}
} 

// Get payment details and ask customer to confirm...
else {

	$resArray = GetShippingDetails( $token );
	$ack = strtoupper($resArray["ACK"]);
	if( $ack == "SUCCESS" || $ack == "SUCESSWITHWARNING") {
		$affiliate = $resArray["CUSTOM"];
		$firstname = $resArray["FIRSTNAME"];
		$lastname = $resArray["LASTNAME"];
		$email = urlencode($resArray["EMAIL"]);
		$payerid = $resArray["PAYERID"];
		$invoice = $resArray["INVNUM"];
		$address = $resArray["PAYMENTREQUEST_0_SHIPTOSTREET"];
		$city = $resArray["PAYMENTREQUEST_0_SHIPTOCITY"];
		$zip = $resArray["PAYMENTREQUEST_0_SHIPTOZIP"];
		$state = $resArray["PAYMENTREQUEST_0_SHIPTOSTATE"];
		$country = $resArray["COUNTRYCODE"];
		$phone = $resArray["PHONENUM"];
		for ($paymentnumber = 0; $paymentnumber <= 9; $paymentnumber++) {
			if ($resArray["PAYMENTREQUEST_{$paymentnumber}_PAYMENTREQUESTID"]) {
				$paymentrequests[$paymentnumber] = $resArray["PAYMENTREQUEST_{$paymentnumber}_PAYMENTREQUESTID"];
				$amounts[$paymentnumber] = $resArray["PAYMENTREQUEST_{$paymentnumber}_AMT"];
				$vendors[$paymentnumber] = $resArray["PAYMENTREQUEST_{$paymentnumber}_SELLERPAYPALACCOUNTID"];
			}
		}
	} else {
		$error = urldecode($resArray["L_LONGMESSAGE0"]);
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		}
		echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$error<br><br><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></font></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}

	// Verify the customer's IP number with minFraud...
	if (!empty($minfraudgeoipkey) && !empty($country)) {
		$ipnumber = $_SERVER["REMOTE_ADDR"];
		if (ashop_minfraudproxycheck($ipnumber) != "0.00") {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/checkout-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/checkout.html");
			echo "<table class=\"ashopmessagetable\">
			<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
			<p><span class=\"ashopmessage\">".PROXYDETECTED."</span></p>
			<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/checkout-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/checkout.html");
			exit;
		}

		if (!ashop_minfraudgeoip($ipaddress,$country)) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/checkout-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/checkout.html");
			echo "<table class=\"ashopmessagetable\">
			<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
			<p><span class=\"ashopmessage\">".IPCHECKFAILED."</span></p>
			<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/checkout-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/checkout.html");
			exit;
		}
	}

	// Get amount and productstring...
	$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$invoice'");
	$row = @mysqli_fetch_array($result);
	$amount = $row["price"];
	$products = $payoption."ashoporderstring".$row["products"];
	$description = $row["description"];
	$members = explode("|", $row["userid"]);

	if ($confirm_x) {
		$amount = number_format($amount,2,'.','');

		$resArray = ConfirmPayment ( $amount, $paymentrequests, $amounts, $vendors );

		$ack = strtoupper($resArray["ACK"]);
		if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" ) {
			$remoteorderid = $resArray["PAYMENTINFO_0_TRANSACTIONID"];
			$paymentstatus = $resArray["PAYMENTINFO_0_PAYMENTSTATUS"];
		} else {
			if ($resArray["L_ERRORCODE0"] == "10486") {
				RedirectToPayPal ( $token );
				exit;
			}
			$error = urldecode($resArray["L_LONGMESSAGE0"]);
			if ($secureconnection) {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
				else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
			} else {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
			}
			echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$error<br><br><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></font></p>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
			exit;
		}

		if ($remoteorderid && ($paymentstatus == "Completed" || $paymentstatus == "Pending" || $paymentstatus == "Processed")) {
			// Register parallel shopping mall vendor payments as paid...
			$paiddate = date("Y-m-d H:i:s", time()+$timezoneoffset);
			for ($paymentnumber = 1; $paymentnumber <= 9; $paymentnumber++) {
				if ($resArray["PAYMENTINFO_{$paymentnumber}_PAYMENTSTATUS"] == "Completed" || $resArray["PAYMENTINFO_{$paymentnumber}_PAYMENTSTATUS"] == "Pending" || $resArray["PAYMENTINFO_{$paymentnumber}_PAYMENTSTATUS"] == "Processed") {
					$thisuserid = $members[$paymentnumber];
					if ($thisuserid) @mysqli_query($db, "INSERT INTO memberorders (orderid, userid, date, paid, paidtoshop) VALUES ('$invoice', '$thisuserid', '$paiddate', '$paiddate', '$paiddate')");
				}
			}
			$securitycheck = md5("$remoteorderid$apipassword");
			$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$paymentstatus&invoice=$invoice&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
			if ($paymentstatus == "Pending") $querystring .= "&pendingpayment=true";
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
			$header = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
			$fp = @fsockopen ("$urldomain", 80);
			if ($fp) {
				$response = fwrite ($fp, $header . $querystring);
				fclose ($fp);
			}
			header ("Location: $ashopsurl/order.php?payopt=$payoption&ofinv=$invoice");
			exit;
		} else {
			if ($secureconnection) {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
				else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
			} else {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
			}
			echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".PAYMENTDECLINED."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">".REASON.": $paymentstatus<br><br><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></font></p>";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
			exit;
		}
	} else {
		// Show header using template catalogue.html...
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		}

		echo "<br><br><center><font face=\"$font\" size=\"2\" color=\"$formstextcolor\"><span class=\"fontsize2\">".CONFIRMPAYMENT." ".$currencysymbols[strtolower($ashopcurrency)]["pre"].number_format($amount,2,'.','').$currencysymbols[strtolower($ashopcurrency)]["post"]."</span><br><br><table width=\"50%\" cellpadding=\"5\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" valign=\"top\"><font face=\"$font\" size=\"2\" color=\"$formstextcolor\"><span class=\"fontsize2\"><b>".SHIPPINGADDRESS."</b></span></font></td><td><font face=\"$font\" size=\"2\" color=\"$formstextcolor\"><span class=\"fontsize2\">$firstname $lastname<br>$address<br>$city, $state $zip<br>$country</span></font></td></tr><tr><td valing=\"top\" align=\"right\"><font face=\"$font\" size=\"2\" color=\"$formstextcolor\"><span class=\"fontsize2\"><b>".ORDERDETAILS."</b></span></font></td><td><font face=\"$font\" size=\"2\" color=\"$formstextcolor\"><span class=\"fontsize2\">$description</span></font></td></tr></table><br></font><form action=\"paypal.php\" method=\"post\"><input type=\"hidden\" name=\"token\" value=\"$token\"><input type=\"image\" src=\"{$buttonpath}images/pay-$lang.png\" class=\"ashopbutton\" name=\"confirm\"></form>";

		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	}
}
chdir("$ashoppath");
?>