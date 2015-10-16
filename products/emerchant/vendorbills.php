<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Vendor Bills";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Calculate total amount of selected bills...
$totalselectedamount = 0.00;
if ($calculate) foreach ($_POST as $key => $value) {
	if (strstr($key, "pay") && $value == "1") {
		$po = str_replace("pay","",$key);
		$calcresult = @mysqli_query($db, "SELECT billtotal FROM emerchant_purchaseorder WHERE purchaseorderid='$po'");
		$thisselectedamount = @mysqli_result($calcresult, 0, "billtotal");
		$totalselectedamount += $thisselectedamount;
	}
}

// Mark selected bills as paid...
else if ($year && $month && $day) foreach ($_POST as $key => $value) {
	if (strstr($key, "pay") && $value == "1") {
		$po = str_replace("pay","",$key);
		@mysqli_query($db, "UPDATE emerchant_purchaseorder SET paiddate='$year-$month-$day' WHERE purchaseorderid='$po'");
	}
}

// Set date range...
$startdate = "$startyear-$startmonth-$startday 00:00:00";
$todate = "$toyear-$tomonth-$today 23:59:59";

if ($action == "download") {
	$orderby = $datetype;
	header ("Content-Type: application/octet-stream");
	header ("Content-Disposition: attachment; filename=vendorbills.csv");
	echo "Bill Date;PO Number;Subtotal;Discount;Shipping;Tax;Bill Total;Paid Date\n";
} else {
	echo $header;
	emerchant_sidebar();
	echo "<td valign=\"top\">";
	emerchant_topbar("Vendor Bills");
	echo "<table width=\"700\" border=\"0\" cellpadding=\"5\" align=\"center\"><tr><td height=\"172\" align=\"center\"><form action=\"vendorbills.php\" method=\"post\"><br>";
}

if ($orderby == "vendorid") {
	$vendorsresult = @mysqli_query($db, "SELECT vendorid FROM emerchant_vendor ORDER BY name");
	while ($row = @mysqli_fetch_array($vendorsresult)) $vendors[] = $row["vendorid"];
} else {
	if ($vendor != "all") $vendors[0] = $vendor;
	else $vendors[0] = "all";
}

$totalsubtotal = 0;
$totaldiscount = 0;
$totalshipping = 0;
$totaltax = 0;
$totalbilltotal = 0;
$unpaid = 0;
$nobills = 1;

foreach ($vendors as $vendornumber => $vendorid) {

	if ($vendorid != "all") {
		$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendorid'");
		$row = @mysqli_fetch_array($vendorresult);
	}

	$thistotalsubtotal = 0;
	$thistotaldiscount = 0;
	$thistotalshipping = 0;
	$thistotaltax = 0;
	$thistotalbilltotal = 0;

	// Get all bills from the database and print them ordered by vendor...
	if ($paidstatus=="paid") $paidstring = " AND paiddate != ''";
	else if ($paidstatus=="unpaid") $paidstring = " AND (paiddate = '' OR paiddate IS NULL)";
	if ($vendorid == "all") $billsresult = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE $datetype >= '$startdate' AND $datetype <= '$todate'$paidstring ORDER BY $datetype, purchaseorderid");
	else $billsresult = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE $datetype >= '$startdate' AND $datetype <= '$todate' AND vendorid='$vendorid'$paidstring ORDER BY $datetype, purchaseorderid");
	if (@mysqli_num_rows($billsresult)) {
		if ($vendorid == "all") echo "<p>Bills for all vendors</p>";
		else echo "<p>Bills for vendor <span class=\"formlabel\"><i>{$row["name"]}</i>&nbsp;<a href=\"vendorhistory.php?vendor={$row["vendorid"]}\"><img src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$row["name"].".\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendor={$row["vendorid"]}')\"><img src=\"images/icon_vendornote.gif\" width=\"18\" height=\"18\" alt=\"Create a note regarding this vendor.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor={$row["vendorid"]}')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a></span></p>";
		$nobills = 0;
		if ($action != "download") echo "<table width=\"99%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
		<tr bgcolor=\"#808080\"><td width=\"13%\" class=\"heading3_wht\"><p><b>Bill Date</b></p></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>PO Number</b></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>Subtotal</b></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>Discount</b></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>Shipping</b></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>Tax</b></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>Bill Total</b></td>
		<td width=\"12%\" class=\"heading3_wht\"><b>Paid Date</b></td>
		<td width=\"3%\" class=\"heading3_wht\">&nbsp;</td></tr>";
		while ($billsrow = @mysqli_fetch_array($billsresult)) {
			$reference = $billsrow["reference"];
			$totalsubtotal += $billsrow["cost"];
			$totaldiscount += $billsrow["discount"];
			$totalshipping += $billsrow["shipping"];
			$totaltax += $billsrow["tax"];
			$totalbilltotal += $billsrow["billtotal"];
			$thistotalsubtotal += $billsrow["cost"];
			$thistotaldiscount += $billsrow["discount"];
			$thistotalshipping += $billsrow["shipping"];
			$thistotaltax += $billsrow["tax"];
			$thistotalbilltotal += $billsrow["billtotal"];
			$billdate = explode(" ",$billsrow["billdate"]);
			if ($billsrow["paiddate"]) $paiddate = explode(" ",$billsrow["paiddate"]);
			else {
				$paiddate[0] = "Unpaid";
				$unpaid = 1;
			}
			if ($action == "download") echo "{$billdate[0]};{$billsrow["purchaseorderid"]};{$billsrow["cost"]};{$billsrow["discount"]};{$billsrow["shipping"]};{$billsrow["tax"]};{$billsrow["billtotal"]};{$paiddate[0]}\n";
			else {
				if ($reference && $billsrow["cost"] < 0) {
					echo "<tr bgcolor=\"#e0e0e0\"><td class=\"sm\">{$billdate[0]}</td>
					<td class=\"sm\">{$billsrow["purchaseorderid"]}<br><font color=\"#FF0000\">Return for: $reference</font></td>
					<td class=\"sm\"><font color=\"#FF0000\">{$billsrow["cost"]}</font></td>
					<td class=\"sm\"><font color=\"#FF0000\">{$billsrow["discount"]}</font></td>
					<td class=\"sm\"><font color=\"#FF0000\">{$billsrow["shipping"]}</font></td>
					<td class=\"sm\"><font color=\"#FF0000\">{$billsrow["tax"]}</font></td>
					<td class=\"sm\"><font color=\"#FF0000\">{$billsrow["billtotal"]}</font></td>
					<td class=\"sm\"><font color=\"#FF0000\">{$paiddate[0]}</font></td>
					<td class=\"sm\">";
					if ($paiddate[0] == "Unpaid") {
						echo "<input type=\"checkbox\" name=\"pay{$billsrow["purchaseorderid"]}\" value=\"1\"";
						$thispayselected = 0;
						$thispayid = $billsrow["purchaseorderid"];
						eval ("if (\$pay$thispayid == \"1\") echo \"checked\";");
						echo ">";
					} else echo "&nbsp;";
					echo "</td></tr>";
				}
				else {
					echo "<tr bgcolor=\"#e0e0e0\"><td class=\"sm\">{$billdate[0]}</td>
					<td class=\"sm\">{$billsrow["purchaseorderid"]} [<a href=\"editpostatus.php?po={$billsrow["purchaseorderid"]}&bill=true\">Edit</a>]</td>
					<td class=\"sm\">{$billsrow["cost"]}</td>
					<td class=\"sm\">{$billsrow["discount"]}</td>
					<td class=\"sm\">{$billsrow["shipping"]}</td>
					<td class=\"sm\">{$billsrow["tax"]}</td>
					<td class=\"sm\">{$billsrow["billtotal"]}</td>
					<td class=\"sm\">{$paiddate[0]}</td>
					<td class=\"sm\">";
					if ($paiddate[0] == "Unpaid") {
						echo "<input type=\"checkbox\" name=\"pay{$billsrow["purchaseorderid"]}\" value=\"1\"";
						$thispayselected = 0;
						$thispayid = $billsrow["purchaseorderid"];
						eval ("if (\$pay$thispayid == \"1\") echo \"checked\";");
						echo ">";
					} else echo "&nbsp;";
					echo "</td></tr>";
				}
			}
		}
		if ($action != "download") {
			echo "<tr bgcolor=\"#A0A0A0\">
			      <td class=\"smwht\" colspan=\"2\"><b>Total:</b></td>
				  <td class=\"smwht\"><b>".number_format($thistotalsubtotal,2,'.','')."</b></td>
				  <td class=\"smwht\"><b>".number_format($thistotaldiscount,2,'.','')."</b></td>
				  <td class=\"smwht\"><b>".number_format($thistotalshipping,2,'.','')."</b></td>
				  <td class=\"smwht\"><b>".number_format($thistotaltax,2,'.','')."</b></td>
				  <td class=\"smwht\"><b>".number_format($thistotalbilltotal,2,'.','')."</b></td>
				  <td class=\"smwht\">&nbsp;</td>
				  <td class=\"smwht\">&nbsp;</td></tr></table>";
		}
	}
}

if ($action != "download") {
	if ($unpaid) {
		// Display calculated amount and button to calculate total billed amount...
		if ($totalselectedamount) echo "<p>Total amount of selected bills: ".number_format($totalselectedamount,2,'.','')."</p>";
		else echo "<br>";
		echo "<input type=\"submit\" name=\"calculate\" value=\"Calculate selected\"><br>";

		// Get the current month and day...
		$currentmonth = date("m", time()+$timezoneoffset);
		$currentday = date("d", time()+$timezoneoffset);
		$currentyear = date("Y", time()+$timezoneoffset);
		echo "<p>Selected bills paid: <input type=\"text\" name=\"year\" size=\"5\" value=\"$currentyear\"><select name=\"month\">";
		echo "<option value=\"01\""; if ($currentmonth == 1) echo "selected"; echo">Jan</option>";
		echo "<option value=\"02\""; if ($currentmonth == 2) echo "selected"; echo">Feb</option>";
		echo "<option value=\"03\""; if ($currentmonth == 3) echo "selected"; echo">Mar</option>";
		echo "<option value=\"04\""; if ($currentmonth == 4) echo "selected"; echo">Apr</option>";
		echo "<option value=\"05\""; if ($currentmonth == 5) echo "selected"; echo">May</option>";
		echo "<option value=\"06\""; if ($currentmonth == 6) echo "selected"; echo">Jun</option>";
		echo "<option value=\"07\""; if ($currentmonth == 7) echo "selected"; echo">Jul</option>";
		echo "<option value=\"08\""; if ($currentmonth == 8) echo "selected"; echo">Aug</option>";
		echo "<option value=\"09\""; if ($currentmonth == 9) echo "selected"; echo">Sep</option>";
		echo "<option value=\"10\""; if ($currentmonth == 10) echo "selected"; echo">Oct</option>";
		echo "<option value=\"11\""; if ($currentmonth == 11) echo "selected"; echo">Nov</option>";
		echo "<option value=\"12\""; if ($currentmonth == 12) echo "selected"; echo">Dec</option>";
		echo "</select><select name=\"day\">";

		for ($i = 1; $i < 32; $i++) {
			echo "<option value=\"";
			if ($i < 10) echo "0";
			echo "$i\"";
			if ($i == $currentday) echo " selected";
			echo ">$i</option>";
		}
		echo "</select><input type=\"hidden\" name=\"datetype\" value=\"$datetype\"><input type=\"hidden\" name=\"startyear\" value=\"$startyear\"><input type=\"hidden\" name=\"startmonth\" value=\"$startmonth\"><input type=\"hidden\" name=\"startday\" value=\"$startday\"><input type=\"hidden\" name=\"toyear\" value=\"$toyear\"><input type=\"hidden\" name=\"tomonth\" value=\"$tomonth\"><input type=\"hidden\" name=\"today\" value=\"$today\"><input type=\"hidden\" name=\"orderby\" value=\"$orderby\"><input type=\"hidden\" name=\"vendor\" value=\"$vendor\"><input type=\"hidden\" name=\"action\" value=\"display\"><input type=\"hidden\" name=\"paidstatus\" value=\"$paidstatus\"><input type=\"submit\" value=\"Submit\">";
	}
	if ($nobills) echo "<span class=\"heading2\">There are no bills for the selected vendor(s).</span>";
	echo "</form><br><br></td></tr></table></td></tr><tr><td align=\"center\" colspan=\"2\"></td></tr></table>$footer";
} 
?>