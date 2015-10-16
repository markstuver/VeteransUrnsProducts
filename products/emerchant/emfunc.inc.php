<?php
// eMerchant functions...

// Calculate sales tax...
if(!is_array($ecmembers)) include "../admin/ashopconstants.inc.php";
function emerchant_tax($amount, $shippingzip, $shippingcountry, $shippingstate, $shippingvat) {
	global $salestaxtype, $taxstate, $hstprovinces, $ecmembers, $vatorigincountry, $taxpercentage;
	$dotaxcalculation = "false";
	$dopstcalculation = "false";
	switch ($salestaxtype) {
		case "ussalestax":
			if($shippingstate == $taxstate && $shippingcountry == "US") $dotaxcalculation = "true";
		break;
		case "cancstpst":
			if($shippingcountry == "CA") $dotaxcalculation = "true";
			if($shippingcountry == "CA" && ($shippingstate == $taxstate || in_array($shippingstate, $hstprovinces))) $dopstcalculation = "true";
		break;
		case "euvat":
			if((in_array($shippingcountry, $ecmembers) && !$shippingvat) || (in_array($shippingcountry, $ecmembers) && $shippingcountry == $vatorigincountry)) $dotaxcalculation = "true";
		break;
	}
	if ($dotaxcalculation == "true") {
		$taxmultiplier = ($taxpercentage / 100);
		$tax = $amount * $taxmultiplier;
		$tax = round((($tax*100)/100)+0.0001, 3);
	}
	if ($dopstcalculation == "true") {
		$taxmultiplier = ($pstpercentage / 100);
		$pst = $amount * $taxmultiplier;
		$tax += round((($pst*100)/100)+0.0001, 3);
	}
	return $tax;
}


// Generate sidebar...
function emerchant_sidebar() {
	global $db, $emerchant_user, $ashopname, $ashopurl, $ashopaddress, $ashopemail, $ashopphone, $sesid, $affiliateid;
	echo "<td valign=\"top\" width=\"243\" bgcolor=\"#7589e7\"><table width=\"95%\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\" align=\"center\"><tr><td class=\"heading_wht\">$ashopname</td></tr><tr><td><img src=\"$ashopurl/emerchant/images/pixel.gif\" width=\"100%\" height=\"1\"></td></tr><tr><td class=heading3_wht><img src=\"images/openitems.gif\" alt=\"Open Items\"></td></tr><tr><td><table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\" background=\"images/panelbg.gif\" style=\"background-repeat: repeat-y;\">";
	
	// Stats section...
	if (!empty($affiliateid)) $result = @mysqli_query($db, "SELECT emerchant_inbox.* FROM emerchant_inbox, customer WHERE emerchant_inbox.email=customer.email AND customer.affiliateid='$affiliateid'");
	else $result = @mysqli_query($db, "SELECT * FROM emerchant_inbox", $db);
	echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;Customer Mail: ".@mysqli_num_rows($result)." [<a href=\"inquiries.php\">View</a>] [<a href=\"inquiries.php?checkmail=true\">Check Mail</a>]</td></tr>";
	$result = @mysqli_query($db, "SELECT * FROM emerchant_inbox2", $db);
	echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;Vendor Mail: ".@mysqli_num_rows($result)." [<a href=\"vendormail.php\">View</a>] [<a href=\"vendormail.php?checkmail=true\">Check Mail</a>]</td></tr>";
	if (empty($affiliateid)) {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_spam", $db);
		echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;Spam: ".@mysqli_num_rows($result)." [<a href=\"spam.php\">View</a>] [<a href=\"spam.php?empty=true\">Empty</a>]</td></tr>";
	}
	if (!empty($affiliateid)) $result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE user='$emerchant_user'", $db);
	else $result = @mysqli_query($db, "SELECT * FROM emerchant_quotes", $db);
	echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;Unconverted Quotes: ".@mysqli_num_rows($result)." [<a href=\"viewquotes.php\">View</a>] [<a href=\"deletequotes.php\">Delete</a>]</td></tr>";

	echo "</table></td></tr><tr><td><img src=\"$ashopurl/emerchant/images/pixel.gif\" width=\"100%\" height=\"3\"></td></tr><tr><td class=heading3_wht><img src=\"images/announcements.gif\" alt=\"Announcements\"></td></tr><tr><td><table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\" background=\"images/panelbg.gif\" style=\"background-repeat: repeat-y;\">";

	// Notes and announcements...
	$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='announcement'");
	$announcement = @mysqli_result($result,0,"confvalue");
	if (!$announcement) $announcement = "&nbsp;";
	echo "<tr><td class=sm>&nbsp;&nbsp;&nbsp;$announcement</td></tr>";

	// Reminders section...
	echo "</table></td></tr><tr><td><img src=\"$ashopurl/emerchant/images/pixel.gif\" width=\"100%\" height=\"3\"></td></tr><tr><td class=heading3_wht><img src=\"images/reminders.gif\" alt=\"Reminders\"></td></tr><tr><td><table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\" background=\"images/panelbg.gif\" style=\"background-repeat: repeat-y;\"><tr><td class=\"sm\"><a href=\"javascript:newWindow('reminder.php')\"><b>Create New Reminder</b></a></td></tr>";

	if ($emerchant_user == "admin") $result = @mysqli_query($db, "SELECT * FROM emerchant_reminders ORDER BY duedate", $db);
	else $result = @mysqli_query($db, "SELECT * FROM emerchant_reminders WHERE username='$emerchant_user' OR username='' OR username IS NULL ORDER BY duedate", $db);
	while ($row = @mysqli_fetch_array($result)) {
		echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;<a href=\"javascript:newWindow('reminder.php?id=".$row["id"]."')\">";
		if ($row["duedate"] != "0000-00-00") echo $row["duedate"]." ";
		echo $row["subject"]."</a></td></tr>";
	}

	// Links and shortcuts...
	echo "</table></td></tr><tr><td><img src=\"$ashopurl/emerchant/images/pixel.gif\" width=\"100%\" height=\"3\"></td></tr><tr><td class=heading3_wht><img src=\"images/links.gif\" alt=\"Links\"></td></tr><tr><td><table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\" background=\"images/panelbg.gif\" style=\"background-repeat: repeat-y;\"><tr><td class=sm><a href=\"javascript:newWindow('links.php')\"><b>Add/Delete Links</b></a></td></tr>";

	echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;<a href=\"$ashopurl\" target=\"_blank\" class=\"sm\">Product Catalogue</a></td></tr>";
	//if ($emerchant_user == "admin") echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;<a href=\"$ashopurl/admin/index.php?emsesid=$sesid\" target=\"_blank\">Admin Panel</a></td></tr>";
	$result = @mysqli_query($db, "SELECT * FROM emerchant_links ORDER BY name", $db);
	while ($row = @mysqli_fetch_array($result)) {
		echo "<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;<a href=\"{$row["url"]}\" target=\"_blank\" class=\"sm\" bgcolor=\"#D6DFF7\">{$row["name"]}</a></td></tr>";
	}

	// Contact information...
	echo "</table></td></tr><tr><td><img src=\"$ashopurl/emerchant/images/pixel.gif\" width=\"100%\" height=\"3\"></td></tr><tr><td class=heading3_wht><img src=\"images/contactinformation.gif\" alt=\"Contact Information\"></td></tr><tr><td><table width=\"100%\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\" background=\"images/panelbg.gif\" style=\"background-repeat: repeat-y;\">
	<tr><td class=\"sm\">&nbsp;&nbsp;&nbsp;<a href=\"mailto:$ashopemail\">$ashopemail</a><br>&nbsp;&nbsp;&nbsp;$ashopphone<br>&nbsp;&nbsp;&nbsp;$ashopaddress</td></tr>";

	echo "</table></td></tr></table></td>";
}

function emerchant_topbar($title) {
	global $emerchant_user;
	echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\" height=\"20\" bgcolor=\"#7589e7\">
        <tr> 
          <td width=\"33%\">&nbsp;</td>
          <td width=\"33%\" align=\"center\" class=\"heading_wht\">$title</td>
          <td align=\"right\" class=\"smwht\" width=\"33%\">login by: $emerchant_user&nbsp;</td>
        </tr>
		</table>
            <table width=\"100%\" border=\"0\" cellpadding=\"1\" bgcolor=\"#7589e7\">
              <tr align=\"center\"> 
                <td class=\"nav\" width=\"28%\"><a href=\"inquiries.php\" class=\"nav\">Messages</a></td>
                <td class=\"nav\" width=\"28%\"><a href=\"customer.php\" class=\"nav\">Customers</a></td>
			    <td class=\"nav\" width=\"28%\"><a href=\"vendor.php\" class=\"nav\">Vendors</a></td>
                <td class=\"nav\" width=\"16%\" align=\"right\"><a href=\"login.php?logout\" class=\"nav\">Log Out</a>&nbsp;</td>
              </tr>
            </table>";
}

// Extract a product string for one vendor...
function emerchant_vendorproductstring($databaseconnection, $productstring, $vendor) {
	$newproductstring = "";
	$items = explode("a", $productstring);
	$arraycount = 1;
	if ($items[0] && count($items)==1) $arraycount = 0;
	for ($i = 0; $i < count($items)-$arraycount; $i++) {
		$thisitem = explode("b", $items[$i]);
		$thisquantity = $thisitem[0];
		$prethisproductid = $thisitem[count($thisitem)-1];
		$thisproductid = explode("d", $prethisproductid);
		$thisproductid = $thisproductid[0];
		if ($thisitem[0] != "sh" && $thisitem[0] != "st" && !strstr($thisitem[0], "so") && $thisitem[0] != "sd") {
			$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$thisproductid' AND vendorid='$vendor'",$databaseconnection);
			if (@mysqli_num_rows($result)) $newproductstring .= $items[$i]."a";
		}
	}
	return $newproductstring;
}
?>