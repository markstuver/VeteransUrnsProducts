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
include "language/$adminlang/members.inc.php";

if ($userid != "1") {
	header("Location: index.php");
	exit;
}

if (!$paymethod && !$memberid) {
	header ("Location: memberadmin.php");	
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Set current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Get affiliate information from database...
$sql="SELECT * FROM user WHERE userid='$memberid'";
$result = @mysqli_query($db, "$sql");
$shopname = @mysqli_result($result, 0, "shopname");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$address = @mysqli_result($result, 0, "address");
$state = @mysqli_result($result, 0, "state");
$zip = @mysqli_result($result, 0, "zip");
$city = @mysqli_result($result, 0, "city");
$paymentdetails = @mysqli_result($result, 0, "paymentdetails");
$commissionlevel = @mysqli_result($result, 0, "commissionlevel");
if (!$commissionlevel) $commissionlevel = $memberpercent;

// Set member commission as paid...
$commission = 0;
$sql="SELECT * FROM memberorders WHERE userid='$memberid' AND (paidtoshop='' OR paidtoshop IS NULL) AND date!='' AND paid !=''";
$result = @mysqli_query($db, "$sql");
$updatepaid = "off";
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$orderid = @mysqli_result($result, $i, "orderid");
	unset($affiliatecommission);
	$affiliateresult = @mysqli_query($db, "SELECT * FROM orderaffiliate WHERE orderid='$orderid'");
	while($row = @mysqli_fetch_array($affiliateresult)) $affiliatecommission += $row["commission"];
	$price = @mysqli_result($result, $i, "price");
	$tax = @mysqli_result($result, $i, "tax");
	if (substr($tax,0,1) == "c") {
		$taxarray = explode("|",$tax);
		$gst = $taxarray[1];
		$pst = $taxarray[2];
		$tax = "";
	} else {
		$gst = "";
		$pst = "";
	}
	$shipping = @mysqli_result($result, $i, "shipping");
	$baseprice = $price - $shipping - $tax - $gst - $pst - $affiliatecommission;
	$membercommission = $baseprice * ($commissionlevel/100);
	$membercommission += $shipping + $tax + $gst + $pst;

    eval ("if (\$paid$orderid == \"on\") \$updatepaid = \"on\";");
    if ($updatepaid == "on") {
		$sql="UPDATE memberorders SET paidtoshop='$date' WHERE orderid='$orderid' AND userid='$memberid'";
		$subresult = @mysqli_query($db, "$sql");
		$updatepaid = "off";
		$totalcommission += $membercommission;
	}
}
@mysqli_query($db, "UPDATE user SET requestpayment='0' WHERE userid='$memberid'");

$item_name = urlencode(SALESCOMMISSIONFROM." $ashopname");
$return = "$ashopurl/admin/memberadmin.php";
$urlcommission = urlencode(number_format(round($totalcommission,2),2,'.',''));

// Send the shop administrator back to affiliate stats or to PayPal...
if ($paymethod == "Manual Payout") {
	echo "$header
	<table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td colspan=\"3\"><font face=\"Arial, Helvetica, sans-serif\" color=\"ffffff\" size=\"4\"><b>".MANAGEMEMBERS."</b></td></tr>
</table>
<center><p class=\"heading\">".PAYMENTTO." $shopname</p>
<table width=\"50%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".AMOUNTPAID.":</font></td><td><input type=\"text\" value=\"".number_format(round($totalcommission,2),$showdecimals,$decimalchar,$thousandchar)."\" readonly></td></tr>
<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PAIDTO.":</font></td><td><input type=\"text\" value=\"$firstname $lastname\" readonly></td></tr>
<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PAYMENTDETAILS.":</font></td><td><textarea rows=\"7\" readonly>";
if ($paymentdetails) echo $paymentdetails;
else echo SENDCHECKTO.":
$firstname $lastname
$address
$city
$state
$zip
$country";
echo "</textarea></td></tr></table></center>$footer";


} else if ($paymethod == "PayPal") {
	$paypalcurrency = "&currency_code=".strtoupper($ashopcurrency);
	header ("Location: https://www.paypal.com/cgi-bin/webscr?business=$paypalid&item_name=$item_name&cmd=_xclick&undefined_quantity=0&no_shipping=1&no_note=1$paypalcurrency&return=$return&amount=$urlcommission");
} else if ($ashopcurrency == "tec" && !empty($paymethod) && is_numeric($paymethod)) {
	$result = @mysqli_query($db, "SELECT * FROM payoptions WHERE payoptionid='$paymethod'");
	$gateway = @mysqli_result($result,0,"gateway");
	$merchantid = @mysqli_result($result,0,"merchantid");
	$secret = @mysqli_result($result, 0, "secret");
	$hash = md5($secret."ashopcommissionpayment".$urlcommission);
	if (file_exists("$ashoppath/admin/gatewaystec/$gateway.gw")) {
		$fp = fopen ("$ashoppath/admin/gatewaystec/$gateway.gw", "r");
		while (!feof($fp)) {
			$line = fgets($fp,4096);
			if (substr($line,1,10) == "paymenturl") {
				$gatewayurl = str_replace("\$paymenturl = ","",$line);
				$gatewayurl = str_replace("=","",$gatewayurl);
				$gatewayurl = str_replace(";","",$gatewayurl);
				$gatewayurl = str_replace("\"","",$gatewayurl);
				$gatewayurl = str_replace("transaction.php","send.php",$gatewayurl);
				$gatewayurl = trim($gatewayurl);
			}
		}
		fclose($fp);
	}
	if (!empty($gatewayurl)) {
		$gatewayurl .= "?tec_vendor=$merchantid&tec_hash=$hash&amount=$urlcommission&recipientemail=$recipientemail";
		header ("Location: $gatewayurl");
	}
}
?>