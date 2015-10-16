<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Order Status";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get vendors for this order...
$result = @mysqli_query($db, "SELECT * FROM orders WHERE (orderid='$order' OR remoteorderid='$order') AND (reference = '' OR reference IS NULL) AND price >= 0 AND (shipped IS NULL OR shipped='') ORDER BY paid");
$orderrow = @mysqli_fetch_array($result);
$productsincart = ashop_parseproductstring($db, $orderrow["products"]);
unset($vendorlist);
if ($productsincart) foreach ($productsincart as $productnumber => $thisproduct) {
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='{$thisproduct["productid"]}'");
	$productrow = @mysqli_fetch_array($result);
	if ($productrow["vendorid"] && (!is_array($vendorlist) || !in_array($productrow["vendorid"],$vendorlist))) $vendorlist[] = $productrow["vendorid"];
}

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Order Status");
echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\">
		  <table width=\"600\" border=\"0\" cellpadding=\"0\"><tr><td>
			<p>Order status for order <span class=\"formlabel\">$order</span>...</p></td></tr></table>
			<table width=\"450\" border=\"0\" cellpadding=\"0\">
			<tr><td width=\"150\"><p><b>Vendor</b></p></td>
			<td width=\"150\"><p><b>Status</b></p></td>
			<td width=\"150\">&nbsp;</td>
			</td></tr>";

// Get order status and generate a report...
if (is_array($vendorlist)) foreach ($vendorlist as $vendornumber => $vendorid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendorid'");
	$vendorrow = @mysqli_fetch_array($result);
	$poresult = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE vendorid='$vendorid' AND orderid='$order'");
	$porow = @mysqli_fetch_array($poresult);
	if (@mysqli_num_rows($poresult)) {
		if (!$orderrow["paid"]) $status = "Incomplete";
		else if (!$porow["shipdate"] && !$porow["sent"] && !$porow["billdate"]) $status = "New";
		else if (!$porow["shipdate"] && $porow["sent"] && !$porow["billdate"]) $status = "Sent";
		else if ($porow["shipdate"] && !$porow["billdate"]) $status = "Shipped {$porow["shipdate"]}";
		else if ($porow["billdate"]) $status = "Closed";
		if ($porow["closed"]) $status = "Voided";
	} else {
		if (!$orderrow["paid"]) $status = "Incomplete";
		else $status = "Open Order";
	}

	if ($status == "Incomplete") echo "<tr><td width=\"100\"><p>{$vendorrow["name"]}</p></td><td><p>$status</p><td width=\"150\">&nbsp;</td></tr>";
	else echo "<tr><td width=\"100\"><p>{$vendorrow["name"]}</p></td><td><p>$status</p><td width=\"150\"><p><a href=\"purchaseorder.php?vendor=$vendorid&order=$order\"><img src=\"images/button_po.gif\" border=\"0\" alt=\"Create Purchase Order\"></a></p></td></tr>";
}

echo "</table><br><br></td></tr></table></td></tr><tr><td align=\"center\" colspan=\"2\"></td></tr></table>$footer";
?>