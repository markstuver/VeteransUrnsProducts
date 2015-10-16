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

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo "$header
<script language=\"JavaScript\">
<!--
	function selectPayMethod(paymentform) {
		if (paymentform.paymethod.value=='0') return false;
		else {
			paymentform.action='paymember.php?memberid=$memberid&paymethod='+paymentform.paymethod.value;
			paymentform.submit();
		}
	}
-->
</script>
";

// Get Traffic Exchange payment options if needed...
if ($ashopcurrency == "tec") {
	$result = @mysqli_query($db, "SELECT * FROM payoptions ORDER BY name");
	$paymentoptions = "<select onChange=\"selectPayMethod(paymentform)\" name=\"paymethod\"><option value=\"0\"";
	if (empty($paymethod)) $paymentoptions .= " selected";
	$paymentoptions .= ">".CHOOSE."</option>";
	while ($row = @mysqli_fetch_array($result)) {
		$paymentoptions .= "<option value=\"{$row["payoptionid"]}\""; if ($paymethod==$row["payoptionid"]) $paymentoptions .= " selected"; $paymentoptions .= ">{$row["name"]}</option>";
	}
	$paymentoptions .= "</select>";
}

// Get member information from database...
$sql="SELECT * FROM user WHERE userid='$memberid'";
$result = @mysqli_query($db, "$sql");
$shopname = @mysqli_result($result, 0, "shopname");
$commissionlevel = @mysqli_result($result, 0, "commissionlevel");
if (!$commissionlevel) $commissionlevel = $memberpercent;
$paymentdetails = @mysqli_result($result, 0, "paymentdetails");
$email = @mysqli_result($result, 0, "email");
if ($paymethod == "PayPal") {
	$paypalid = str_replace("My PayPal ID is:","",$paymentdetails);
	$paypalid = str_replace("PayPal","",$paypalid);
	$paypalid = trim($paypalid);
}

echo  "<center><div class=\"heading\">".PAYMENTTO." $shopname, ".MEMBERID." $memberid <a href=\"editmember.php?memberid=$memberid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $memberid\" title=\"".PROFILEFOR." $memberid\" border=\"0\"></a>&nbsp;<a href=\"editmember.php?memberid=$memberid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEMEMBER." $memberid ".FROMDB."\" title=\"".DELETEMEMBER." $memberid ".FROMDB."\" border=\"0\"></a></div>";

if ($ashopcurrency == "tec") $selectorderids = "<table width=\"700\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#D0D0D0\">
	<tr bgcolor=\"#808080\"><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".ORDERID."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".THEWORDDATE."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".TRAFFICEXCHANGE."</b></font></td><td align=\"center\" width=\"50\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".AMOUNT."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".PAY."</b></font></td></tr>";

else $selectorderids = "<table width=\"600\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#D0D0D0\">
	<tr bgcolor=\"#808080\"><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".ORDERID."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".THEWORDDATE."</b></font></td><td align=\"center\" width=\"50\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".AMOUNT."</b></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".PAY."</b></font></td></tr>";


$totalcommission = 0;
if ($ashopcurrency == "tec") {
	if (!empty($paymethod) && is_numeric($paymethod)) $result = @mysqli_query($db, "SELECT memberorders.*,orders.payoptionid FROM memberorders, orders WHERE memberorders.userid='$memberid' AND (memberorders.paidtoshop='' OR memberorders.paidtoshop IS NULL) AND memberorders.date!='' AND memberorders.paid !='' AND memberorders.orderid=orders.orderid AND orders.payoptionid='$paymethod'");
	else $result = @mysqli_query($db, "SELECT memberorders.*,orders.payoptionid FROM memberorders, orders WHERE memberorders.userid='$memberid' AND (memberorders.paidtoshop='' OR memberorders.paidtoshop IS NULL) AND memberorders.date!='' AND memberorders.paid !='' AND memberorders.orderid=orders.orderid");
} else $result = @mysqli_query($db, "SELECT * FROM memberorders WHERE userid='$memberid' AND (paidtoshop='' OR paidtoshop IS NULL) AND date!='' AND paid !=''");
if (@mysqli_num_rows($result) != 0) {
  for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
	  $orderid = @mysqli_result($result, $i, "orderid");
	  //$affiliatecommission = @mysqli_result($result, $i, "affiliatecommission");
	  $price = @mysqli_result($result, $i, "price");
	  $baseprice = $price; // - $affiliatecommission;
	  $membercommission = $baseprice * ($commissionlevel/100);
	  $membercommission += $shipping + $tax + $gst + $pst;
	  $totalcommission += $membercommission;
	  $orderdate = @mysqli_result($result, $i, "date");
	  $orderid = @mysqli_result($result, $i, "orderid");
	  if ($ashopcurrency == "tec") $payoptionid = @mysqli_result($result, $i, "payoptionid");
	  if (!empty($payoptionid) && is_numeric($payoptionid)) {
		  $payoptionresult = @mysqli_query($db, "SELECT name FROM payoptions WHERE payoptionid='$payoptionid'");
		  $payoptionname = @mysqli_result($payoptionresult,0,"name");
	  }
	  $selectorderids .= "<tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"salesreport.php?generate=true&orderid=$orderid\">$orderid</a></font></td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$orderdate</font></td>";
	  if ($ashopcurrency == "tec") $selectorderids .= "<td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$payoptionname</font></td>";
	  $selectorderids .= "<td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".$currencysymbols[$ashopcurrency]["pre"].number_format($membercommission,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</font></td><td align=\"center\"><input type=\"checkbox\" name=\"paid$orderid\" checked></td></tr>";
  }
}
$selectorderids .= "</table>";

echo "<form method=\"post\" action=\"memberpay.php\" name=\"paymentform\">
<p><font face=\"Arial, Helvetica, sans-serif\" size=\"3\">".TOTALUNPAID.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($totalcommission,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</b></font></p>
<p>$selectorderids</p>
<table width=\"400\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
<tr><td width=\"120\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
if ($ashopcurrency == "tec") {
	echo PAYCREDITS.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$paymentoptions</font></td></tr>";
	if (!empty($paymethod) && is_numeric($paymethod)) echo "
	<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".RECIPIENTEMAIL.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"text\" name=\"recipientemail\" size=\"43\" value=\"$email\"></font></td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"memberid\" value=\"$memberid\"><input type=\"hidden\" name=\"paymethod\" value=\"$paymethod\"><input type=\"submit\" name=\"check\" value=\"".PAYNOW."\"></td></tr>";
} else {
	echo PAYSELECTEDBY.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><select onChange=\"selectPayMethod(paymentform)\" name=\"paymethod\"><option value=\"0\">".CHOOSE."<option value=\"PayPal\""; if ($paymethod=="PayPal") echo " selected"; echo ">".PAYPAL."<option value=\"Manual Payout\""; if ($paymethod=="Manual Payout") echo " selected"; echo ">".MANUALPAYOUT."</select></font></td></tr>";
}
if ($paymethod == "PayPal") echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PAYPALID.":</font></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"text\" name=\"paypalid\" size=\"43\" value=\"$paypalid\"></font></td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"memberid\" value=\"$memberid\"><input type=\"submit\" name=\"check\" value=\"".PAYNOW."\"></td></tr>";
else if ($paymethod == "Manual Payout") echo "<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"memberid\" value=\"$memberid\"><input type=\"submit\" name=\"check\" value=\"".MARKASPAID."\"></td></tr>";
echo "</table></form></center>$footer";
?>