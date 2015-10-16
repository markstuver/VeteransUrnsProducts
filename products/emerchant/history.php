<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Customer History";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

unset($history);

$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
$customerrow = @mysqli_fetch_array($result);

$result = @mysqli_query($db, "SELECT * FROM emerchant_messages WHERE customerid='$customer' ORDER BY date,id DESC");
while ($row = @mysqli_fetch_array($result)) {
	if ($mailservertype == "imap" && $row["uid"]) $readmessagescript = "readmessageimap.php";
	else $readmessagescript = "readmessage.php";
	if (!$row["replyto"]) {
		$utilitycolumn = "<td width=\"80\" class=\"sm\"><a href=\"javascript:newWindow('"; if ($mailservertype == "imap" && $row["uid"]) $utilitycolumn .= "composemessageimap.php"; else $utilitycolumn .= "composemessage.php"; $utilitycolumn .= "?reply={$row["id"]}&mailbox=archive&history=$customer')\"><img src=\"images/icon_reply.gif\" alt=\"Reply to this message.\" border=\"0\"></a>&nbsp;<a href=\"forward.php?id={$row["id"]}&mailbox=archive&history=$customer\"><img src=\"images/icon_forward.gif\" alt=\"Forward this message.\" border=\"0\"></a>&nbsp;<a href=\"messages.php?delete={$row["id"]}&history=$customer\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a>";
		$messagetype = "Inquiry";
	} else {
		$byuser = $row["user"];
		$messagetype = "Reply by $byuser";
		$utilitycolumn = "<td width=\"80\" class=\"sm\"><img src=\"images/pixel.gif\" width=\"23\" height=\"15\">&nbsp;<img src=\"images/pixel.gif\" width=\"23\" height=\"15\">&nbsp;<a href=\"messages.php?delete={$row["id"]}&history=$customer\"><img src=\"images/icon_trash.gif\" width=\"15\" height=\"15\" alt=\"Delete this message.\" border=\"0\"></a></td>";
	}
	$history[$row["date"]][] = "<td class=\"sm\">$messagetype: <a href=\"javascript:newWindow('$readmessagescript?id={$row["id"]}&mailbox=archive')\">{$row["subject"]}</a></td>$utilitycolumn";
}

$result = @mysqli_query($db, "SELECT * FROM emerchant_comments WHERE customerid='$customer' ORDER BY date,id DESC");
while ($row = @mysqli_fetch_array($result)) $history[$row["date"]][] = "<td class=\"sm\">Comment: <a href=\"javascript:newWindow('customernote.php?id={$row["id"]}')\">{$row["subject"]}</a></td><td width=\"60\" class=\"sm\">&nbsp;</td>";

$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE customerid='$customer' ORDER BY date,id DESC");
while ($row = @mysqli_fetch_array($result)) $history[$row["date"]][] = "<td class=\"sm\">Quote: {$row["id"]} - <a href=\"quote.php?edit={$row["id"]}\">Edit</a></td><td width=\"80\" class=\"sm\">&nbsp;</td>";

$result = @mysqli_query($db, "SELECT * FROM orders WHERE date IS NOT NULL AND date != '' AND customerid='$customer' AND (wholesale IS NULL OR wholesale != '1') ORDER BY date,orderid DESC");
while ($row = @mysqli_fetch_array($result)) {
	if ($row["billdate"] && !$row["paid"]) {
		$today = date("Y-m-d", time()+$timezoneoffset);
		if ($today > $row["duedate"]) $paidtext = "Overdue";
		else $paidtext = "Unpaid";
		$billresult = @mysqli_query($db, "SELECT * FROM emerchant_bills WHERE orderid='{$row["orderid"]}'");
		if (@mysqli_num_rows($billresult) > 1) {
			$editbilltext = " [Edit: ";
			while($billrow = @mysqli_fetch_array($billresult)) $editbilltext .= "<a href=\"editinvoice.php?orderid={$row["orderid"]}&billnumber={$billrow["billnumber"]}\">{$billrow["billnumber"]}</a> ";
			$editbilltext .= "]";
		} else if (@mysqli_num_rows($billresult) == 1) {
			$billrow = @mysqli_fetch_array($billresult);
			if ($billrow["billnumber"]) $edittext = " - <a href=\"editinvoice.php?orderid={$row["orderid"]}&billnumber={$billrow["billnumber"]}\">Edit</a>";
			else $edittext = " - <a href=\"editinvoice.php?orderid={$row["orderid"]}\">Edit</a>";
		} else $edittext = "";
		if (is_dir("$ashoppath/emerchant/invoices") && file_exists("$ashoppath/emerchant/invoices/{$row["orderid"]}")) $orderidtext = "<a href=\"getinvoice.php?orderid={$row["orderid"]}\" target=\"_blank\">{$row["orderid"]}</a>$edittext";
		else $orderidtext = $row["orderid"].$edittext;
		$history[$row["date"]][] = "<td class=\"sm\">$paidtext Invoice $orderidtext<br>{$row["description"]}<br>Amount: ".number_format($row["price"],2,'.','')."</td><td width=\"80\" class=\"sm\">&nbsp;</td>";
	} else if ($row["reference"] && $row["price"] < 0) {
		$thistext = "Order {$row["orderid"]}<br><font color=\"#FF0000\">";
		if ($row["comment"]) $thistext .= "<a href=\"javascript: void(0)\" onMouseOver=\"window.status='{$row["comment"]}'; return true;\" onMouseOut=\"window.status=window.defaultStatus;\"><img src=\"../admin/images/icon_info.gif\" alt=\"{$row["comment"]}\" border=\"0\"></a> ";
		$thistext .= "Chargeback for {$row["reference"]}</font><br>{$row["description"]}<br>Amount: <font color=\"#FF0000\">".number_format($row["price"],2,'.','')."</font>";
		$history[$row["date"]][] = "<td class=\"sm\">$thistext</td><td width=\"80\" class=\"sm\">&nbsp;</td>";
	} else {
		if (is_dir("$ashoppath/admin/receipts") && file_exists("$ashoppath/admin/receipts/{$row["orderid"]}")) $orderidtext = "<a href=\"getreceipt.php?orderid={$row["orderid"]}\" target=\"_blank\">{$row["orderid"]}</a>";
		else $orderidtext = $row["orderid"];
		if ($row["vendors"]) $statustext = " [<a href=\"orderstatus.php?vendor=all&order={$row["orderid"]}\">Status</a>]";
		else $statustext = "";
		$billresult = @mysqli_query($db, "SELECT * FROM emerchant_bills WHERE orderid='{$row["orderid"]}' AND recurring != ''");
		if (@mysqli_num_rows($billresult) > 1) {
			$editbilltext = " [Recurring: ";
			while($billrow = @mysqli_fetch_array($billresult)) $editbilltext .= "<a href=\"editinvoice.php?orderid={$row["orderid"]}&billnumber={$billrow["billnumber"]}\">{$billrow["billnumber"]}</a> ";
			$editbilltext .= "]";
		} else if (@mysqli_num_rows($billresult) == 1) {
			$billrow = @mysqli_fetch_array($billresult);
			if ($billrow["billnumber"]) $editbilltext = " [<a href=\"editinvoice.php?orderid={$row["orderid"]}&billnumber={$billrow["billnumber"]}\">Recurring</a>]";
			else $editbilltext = " [<a href=\"editinvoice.php?orderid={$row["orderid"]}\">Recurring</a>]";
		} else $editbilltext = "";
		$history[$row["date"]][] = "<td class=\"sm\">Order $orderidtext$statustext$editbilltext<br>{$row["description"]}<br>Amount: ".number_format($row["price"],2,'.','')."</td><td width=\"80\" class=\"sm\">&nbsp;</td>";
	}
}

$result = @mysqli_query($db, "SELECT * FROM orders WHERE date IS NOT NULL AND date != '' AND customerid='$customer' AND wholesale='1' ORDER BY date,orderid DESC");
while ($row = @mysqli_fetch_array($result)) {
	if (is_dir("$ashoppath/admin/receipts") && file_exists("$ashoppath/admin/receipts/{$row["orderid"]}")) $orderidtext = "<a href=\"getreceipt.php?orderid={$row["orderid"]}\" target=\"_blank\">{$row["orderid"]} W</a>";
	else $orderidtext = $row["orderid"];
	if ($row["vendors"]) $statustext = " [<a href=\"orderstatus.php?vendor=all&order={$row["orderid"]}\">Status</a>]";
	else $statustext = "";
	$history[$row["date"]][] = "<td class=\"sm\">Wholesale Order $orderidtext$statustext<br>{$row["description"]}<br>Amount: ".number_format($row["price"],2,'.','')."</td><td width=\"80\" class=\"sm\">&nbsp;</td>";
}

$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE customerid='$customer' AND orderid=0 AND (closed IS NULL OR closed='') ORDER BY date,purchaseorderid DESC");
while ($row = @mysqli_fetch_array($result)) {
	if ($row["vendorid"]) {
		$vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='{$row["vendorid"]}'");
		$vendorname = @mysqli_result($vendorresult,0,"name");
	}
	$description = "";
	if ($row["products"]) {
		$productsincart = ashop_parseproductstring($db, $row["products"]);
		if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) $description .= "{$thisproduct["quantity"]}: {$thisproduct["name"]}, ";
	}
	if ($description) $description = substr_replace($description, "", -2);
	$history[$row["date"]][] = "<td class=\"sm\">Purchase Order {$row["purchaseorderid"]} [<a href=\"purchaseorder.php?edit={$row["purchaseorderid"]}\">Edit</a>]<br>$description<br>Vendor: $vendorname<br>Amount: ".number_format($row["cost"]+$row["shipping"],2,'.','')."</td><td width=\"80\" class=\"sm\">&nbsp;</td>";
}

if($history) krsort($history);

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Customer History");
if ($notice) echo "<div align=\"center\" class=\"heading3\"><br><font color=\"#000099\">$notice</font></div>";
echo "<div align=\"center\" class=\"heading3\"><br>
        <span class=\"heading2\">History for ".$customerrow["firstname"]." ".$customerrow["lastname"].", Customer ID 
        $customer</span><a href=\"customer.php?id=$customer\"><img src=\"images/icon_profile.gif\" width=\"15\" height=\"15\" alt=\"Edit customer information for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a>&nbsp;<a href=\"quote.php?customer=$customer\"><img src=\"images/icon_quote-order.gif\" width=\"18\" height=\"18\" alt=\"Create a new quote or order for this customer.\" border=\"0\"></a>&nbsp;<img src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\">&nbsp;<a href=\"javascript:newWindow('customernote.php?customer=$customer&refresh=true')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this customer.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?customer=$customer')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a></div><br>
      <table width=\"85%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" bgcolor=\"#d0d0d0\" align=\"center\">
        <tr bgcolor=\"#808080\"> 
          <td width=\"100\" class=\"heading3_wht\"><b class=\"heading3_wht\">Date</b></td>
          <td class=\"heading3_wht\"><b>Item</b></td>
		  <td width=\"80\" valign=\"top\" class=\"sm\">&nbsp;</td>
        </tr>";

if ($history) foreach ($history as $date=>$contentarray) {
	$dateandtime = explode(" ", $date);
	foreach ($contentarray as $contentnumber=>$content) {
		$pos = strpos($content, "Reply by ");
		if ($pos === false) echo "<tr bgcolor=\"#e0e0e0\">";
		else echo "<tr bgcolor=\"#c0c0c0\">";
		echo "
		<td width=\"100\" class=\"sm\">$dateandtime[0]</td>
			$content
		</tr>";
	}
}

echo "</table>
      </td>
  </tr>
</table>
$footer";
?>