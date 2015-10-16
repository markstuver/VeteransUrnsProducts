<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
if ($bill) $pagetitle = "Vendor Bill";
else $pagetitle = "Purchase Order";
include "template.inc.php";
$date = date("Y-m-d", time()+$timezoneoffset);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get current date for setting shipdate...
$date = explode("-",date("Y-m-d", time()+$timezoneoffset));
$year = $date[0];
$month = $date[1];
$day = $date[2];

// Handle closing of all related POs...
if ($close) {
	if ($yes) {
	   $result = @mysqli_query($db, "UPDATE emerchant_purchaseorder SET closed='$year-$month-$day' WHERE purchaseorderid='$close'");
	   header("Location: vendor.php?notice=PO Successfully Voided");
	   exit;
    }
	elseif ($no) header("Location: vendor.php");
	else {
		echo $header;
		emerchant_sidebar();
		echo "<td valign=\"top\">";
		emerchant_topbar($pagetitle);
		echo "<p class=\"heading3\" align=\"center\">Void Purchase Order: $close</p>
        <p class=\"warning\" align=\"center\">This will mark the purchase order as voided and remove it from any reports! Are you sure?</p>
		<form action=\"editpostatus.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"Yes\">
		<input type=\"submit\" name=\"no\" value=\"No \"></td>
		</tr></table><input type=\"hidden\" name=\"close\" value=\"$close\"></form>
		</td></tr></table>
		$footer";
		exit;
	}
} 

// Update purchase order...
if ($shipyear && $shipmonth && $shipday) {
	if (substr($shipyear,0,1) != substr(date("Y",time()),0,1)) {
		while (substr($shipyear,0,1) == "0") $shipyear = substr($shipyear,1);
		$shipyear = date("Y", mktime (0,0,0,1,1,$shipyear));
	}
	while (substr($shipday,0,1) == "0") $shipday = substr($shipday,1);
	if (strlen($shipday) != 2) $shipday = "0$shipday";
	if ("$shipyear-$shipmonth-$shipday" > "$year-$month-$day") $shipdate = "$year-$month-$day";
	else $shipdate = "$shipyear-$shipmonth-$shipday";
	@mysqli_query($db, "UPDATE emerchant_purchaseorder SET shipdate='$shipdate' WHERE purchaseorderid='$po'");
}

if ($billyear && $billmonth && $billday) {
	if (substr($billyear,0,1) != substr(date("Y",time()),0,1)) {
		while (substr($billyear,0,1) == "0") $billyear = substr($billyear,1);
		$billyear = date("Y", mktime (0,0,0,1,1,$billyear));
	}
	while (substr($billday,0,1) == "0") $billday = substr($billday,1);
	if (strlen($billday) != 2) $billday = "0$billday";
	if ("$billyear-$billmonth-$billday" > "$year-$month-$day") $billdate = "$year-$month-$day";
	else $billdate = "$billyear-$billmonth-$billday";
	@mysqli_query($db, "UPDATE emerchant_purchaseorder SET billdate='$billdate', shipping='".number_format($shipping,2,'.','')."', discount='".number_format($discount,2,'.','')."', cost='".number_format($subtotal,2,'.','')."', billtotal='".number_format($billtotal,2,'.','')."', tax='".number_format($tax,2,'.','')."' WHERE purchaseorderid='$po'");
	if (!$bill) {
		header("Location: polist.php?vendor=$vendor&report=shipped");
		exit;
	}
}

// Get purchase order information...
$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE purchaseorderid='$po'");
$porow = @mysqli_fetch_array($result);
$shipping = $porow["shipping"];
$cost = $porow["cost"];
$billdate = $porow["billdate"];
if ($billdate) {
	$date = explode("-",$billdate);
	$year = $date[0];
	$month = $date[1];
	$day = $date[2];
}
$shipdate = $porow["shipdate"];
$sent = $porow["sent"];
$discount = $porow["discount"];
$tax = $porow["tax"];
$billtotal = $porow["billtotal"];
if (!$billtotal) $billtotal = $cost + $shipping + $tax - $discount;

if (!$shipdate && !$sent && !$billdate) $status = "New";
else if (!$shipdate && $sent && !$billdate) $status = "Sent";
else if ($shipdate && !$billdate) $status = "Shipped";
else if ($billdate) $status = "Closed";

echo $header;
emerchant_sidebar();
if ($status == "Shipped" || $status == "Closed") echo "
<script language=\"JavaScript\">
function updatebilltotal()
{
	newbilltotal = parseFloat(document.statusform.subtotal.value)+parseFloat(document.statusform.shipping.value)+parseFloat(document.statusform.tax.value)-parseFloat(document.statusform.discount.value);
	newbilltotal = (newbilltotal + 0.001) + '';
	document.statusform.billtotal.value = newbilltotal.substring(0, newbilltotal.indexOf('.') + 3);
}
</script>";
echo "<td valign=\"top\">";
emerchant_topbar($pagetitle);
echo "<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
        <tr>
          <td>
            <div align=\"center\" class=\"heading3\"><br>";
if ($bill) echo "Edit Vendor Bill";
else echo "Edit Purchase Order Status";
echo "</div>
			<p class=\"sm\">Purchase Order Number: <b>$po</b> [<a href=\"purchaseorder.php?edit=$po\">Edit</a>]<br>Current Status: <b>$status</b></p>";
			
if ($status == "New") echo "<a href=\"emailpo.php?po=$po\"><img src=\"images/button_send.gif\" width=\"64\" height=\"24\" alt=\"Send purchase order in e-mail.\" border=\"0\"></a>";
if ($status == "Sent") {
	echo "<form action=\"editpostatus.php\" method=\"post\" name=\"statusform\" style=\"margin-bottom: 0px;\"><div class=\"sm\">Shipped: <input type=\"text\" name=\"shipyear\" value=\"$year\" size=\"4\"> <select name=\"shipmonth\">
			<option value=\"01\""; if ($month == "01") echo " selected"; echo ">Jan</option>
			<option value=\"02\""; if ($month == "02") echo " selected"; echo ">Feb</option>
			<option value=\"03\""; if ($month == "03") echo " selected"; echo ">Mar</option>
			<option value=\"04\""; if ($month == "04") echo " selected"; echo ">Apr</option>
			<option value=\"05\""; if ($month == "05") echo " selected"; echo ">May</option>
			<option value=\"06\""; if ($month == "06") echo " selected"; echo ">Jun</option>
			<option value=\"07\""; if ($month == "07") echo " selected"; echo ">Jul</option>
			<option value=\"08\""; if ($month == "08") echo " selected"; echo ">Aug</option>
			<option value=\"09\""; if ($month == "09") echo " selected"; echo ">Sep</option>
			<option value=\"10\""; if ($month == "10") echo " selected"; echo ">Oct</option>
			<option value=\"11\""; if ($month == "11") echo " selected"; echo ">Nov</option>
			<option value=\"12\""; if ($month == "12") echo " selected"; echo ">Dec</option>
			</select> <input type=\"text\" name=\"shipday\" value=\"$day\" size=\"4\"> <input type=\"hidden\" name=\"po\" value=\"$po\"><input type=\"image\" src=\"images/button_set.gif\" alt=\"Set Shipping Date\" name=\"setshipdate\" align=\"top\"></div></form><br><br><hr><div class=\"formlabel\">Unshipped purchase orders: <a href=\"polist.php?vendor=all&report=unshipped\"><img src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" align=\"absmiddle\"></a></div>";
}
if ($status == "Shipped" || $status == "Closed") {
	echo "<form action=\"editpostatus.php\" method=\"post\" name=\"statusform\" style=\"margin-bottom: 0px;\"><div class=\"sm\">Billed: <input type=\"text\" name=\"billyear\" value=\"$year\" size=\"4\"> <select name=\"billmonth\">
			<option value=\"01\""; if ($month == "01") echo " selected"; echo ">Jan</option>
			<option value=\"02\""; if ($month == "02") echo " selected"; echo ">Feb</option>
			<option value=\"03\""; if ($month == "03") echo " selected"; echo ">Mar</option>
			<option value=\"04\""; if ($month == "04") echo " selected"; echo ">Apr</option>
			<option value=\"05\""; if ($month == "05") echo " selected"; echo ">May</option>
			<option value=\"06\""; if ($month == "06") echo " selected"; echo ">Jun</option>
			<option value=\"07\""; if ($month == "07") echo " selected"; echo ">Jul</option>
			<option value=\"08\""; if ($month == "08") echo " selected"; echo ">Aug</option>
			<option value=\"09\""; if ($month == "09") echo " selected"; echo ">Sep</option>
			<option value=\"10\""; if ($month == "10") echo " selected"; echo ">Oct</option>
			<option value=\"11\""; if ($month == "11") echo " selected"; echo ">Nov</option>
			<option value=\"12\""; if ($month == "12") echo " selected"; echo ">Dec</option>
			</select> <input type=\"text\" name=\"billday\" value=\"$day\" size=\"4\"><br>
			<table width=\"100\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
			<tr><td class=\"sm\">Subtotal:</td><td class=\"sm\"><input type=\"text\" name=\"subtotal\" value=\"$cost\" size=\"10\" onBlur=\"updatebilltotal()\"></td></tr>
			<tr><td class=\"sm\">Discount:</td><td class=\"sm\"><input type=\"text\" name=\"discount\" value=\"$discount\" size=\"10\" onBlur=\"updatebilltotal()\"></td></tr>
			<tr><td class=\"sm\">Shipping:</td><td class=\"sm\"><input type=\"text\" name=\"shipping\" value=\"$shipping\" size=\"10\" onBlur=\"updatebilltotal()\"></td></tr>
			<tr><td class=\"sm\">Tax:</td><td class=\"sm\"><input type=\"text\" name=\"tax\" value=\"$tax\" size=\"10\" onBlur=\"updatebilltotal()\"></td></tr>
			<tr><td class=\"sm\">Bill total:</td><td class=\"sm\"><input type=\"text\" name=\"billtotal\" value=\"$billtotal\" size=\"10\"></td></tr>
			<tr><td class=\"sm\">&nbsp;</td><td align=\"right\" class=\"sm\"><input type=\"hidden\" name=\"po\" value=\"$po\"><input type=\"hidden\" name=\"vendor\" value=\"$vendor\"><input type=\"hidden\" name=\"bill\" value=\"$bill\"><input type=\"image\" src=\"images/button_set.gif\" alt=\"Set Bill Date\" name=\"setbilldate\" align=\"top\"></td></tr></table></div></form>";
	if ($status == "Shipped") echo "<br><br><hr><div class=\"formlabel\">Shipped purchase orders: <a href=\"polist.php?vendor=all&report=shipped\"><img src=\"images/button_go.gif\" width=\"27\" height=\"24\" border=\"0\" align=\"absmiddle\"></a></div>";
}

echo "</td></tr></table>
$footer";
@mysqli_close($db);
?>