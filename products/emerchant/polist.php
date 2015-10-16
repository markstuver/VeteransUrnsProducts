<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
if ($report == "unshipped") $pagetitle = "Unshipped Purchase Orders";
if ($report == "shipped") $pagetitle = "Shipped Purchase Orders";
if ($report == "closed") $pagetitle = "Closed Purchase Orders";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
if ($report == "unshipped") emerchant_topbar("Unshipped Purchase Orders");
if ($report == "shipped") emerchant_topbar("Shipped Purchase Orders");
if ($report == "closed") emerchant_topbar("Closed Purchase Orders");
echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\">";
// Get all open orders from the database and print them ordered by vendor...
$noorders = 1;
if ($vendor == "all") $vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");
else $vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
while ($vendorrow = @mysqli_fetch_array($vendorresult)) {
	$vendorid = $vendorrow["vendorid"];
	$vendorname = $vendorrow["name"];

		unset($shiporderstring);
		unset($ordersresult);
		unset($orderrow);
		unset($totalcost);

		if ($report == "unshipped") $shipquerystring = "(shipdate='' OR shipdate IS NULL)";
		if ($report == "shipped") $shipquerystring = "(shipdate<>'' AND shipdate IS NOT NULL) AND (billdate='' OR billdate IS NULL)";
		if ($report == "closed") $shipquerystring = "(shipdate<>'' AND shipdate IS NOT NULL) AND (billdate<>'' AND billdate IS NOT NULL)";

		$ordersresult = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE vendorid='$vendorid' AND (closed IS NULL OR closed='') AND $shipquerystring ORDER BY date DESC");

		while ($orderrow = @mysqli_fetch_array($ordersresult)) {
			$purchaseorderid = $orderrow["purchaseorderid"];
			$orderid = $orderrow["orderid"];
			$date = explode(" ",$orderrow["date"]);
			$customerid = $orderrow["customerid"];
			$cost = number_format($orderrow["cost"]+$orderrow["shipping"],2,'.','');
			$totalcost += $cost;
			$reference = $orderrow["reference"];

			$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
			$customer = @mysqli_result($customerresult,0,"firstname")." ".@mysqli_result($customerresult,0,"lastname");

			if ($cost < 0 || $reference) {
				if ($emerchant_user != "admin") {
					$shiporderstring .= "<tr><td width=\"200\"><p>{$date[0]}</p></td>
					<td width=\"200\"><p>$purchaseorderid, <font color=\"#FF0000\">Return for: $reference</font></p></td>
					<td nowrap>";
					if ($customerid) $shiporderstring .= "<p>$customer <a href=\"customer.php?id=$customerid\"><img src=\"images/icon_profile.gif\" border=\"0\" alt=\"Edit customer information for $customer\"></a><a href=\"history.php?customer=$customerid\"><img src=\"images/icon_history.gif\" border=\"0\" alt=\"View history for $customer\"></a></p>";
					else $shiporderstring .= "&nbsp;";
					$shiporderstring .= "</td></tr>";
				} else {
					$shiporderstring .= "<tr><td width=\"150\"><p>{$date[0]}</p></td>
					<td width=\"150\"><p>$cost</p></td>
					<td width=\"150\"><p>$purchaseorderid, <font color=\"#FF0000\">Return for: $reference</font></p></td>
					<td nowrap>";
					if ($customerid) $shiporderstring .= "<p>$customer <a href=\"customer.php?id=$customerid\"><img src=\"images/icon_profile.gif\" border=\"0\" alt=\"Edit customer information for $customer\"></a><a href=\"history.php?customer=$customerid\"><img src=\"images/icon_history.gif\" border=\"0\" alt=\"View history for $customer\"></a></p>";
					else $shiporderstring .= "&nbsp;";
					$shiporderstring .= "</td></tr>";
				}
			} else {
				if ($emerchant_user != "admin") {
					$shiporderstring .= "<tr><td width=\"200\"><p>{$date[0]}</p></td>
				    <td width=\"200\"><p>$purchaseorderid [<a href=\"purchaseorder.php?edit=$purchaseorderid\">Edit</a>]</p></td>
					<td nowrap>";
					if ($customerid) $shiporderstring .= "<p>$customer <a href=\"customer.php?id=$customerid\"><img src=\"images/icon_profile.gif\" border=\"0\" alt=\"Edit customer information for $customer\"></a><a href=\"history.php?customer=$customerid\"><img src=\"images/icon_history.gif\" border=\"0\" alt=\"View history for $customer\"></a></p>";
					else $shiporderstring .= "&nbsp;";
					$shiporderstring .= "</td></tr>";
				} else {
					$shiporderstring .= "<tr><td width=\"150\"><p>{$date[0]}</p></td>
					<td width=\"40\" align=\"right\"><p>$cost</p></td><td width=\"110\">&nbsp;</td>
				    <td width=\"150\"><p>$purchaseorderid [<a href=\"purchaseorder.php?edit=$purchaseorderid\">Edit</a>]</p></td>
					<td nowrap>";
					if ($customerid) $shiporderstring .= "<p>$customer <a href=\"customer.php?id=$customerid\"><img src=\"images/icon_profile.gif\" border=\"0\" alt=\"Edit customer information for $customer\"></a><a href=\"history.php?customer=$customerid\"><img src=\"images/icon_history.gif\" border=\"0\" alt=\"View history for $customer\"></a></p>";
					else $shiporderstring .= "&nbsp;";
					$shiporderstring .= "</td></tr>";
				}
			}
		}
	if ($shiporderstring) {
		$noorders = 0;
		echo "<table width=\"600\" border=\"0\" cellpadding=\"0\"><tr><td><p>";
		if ($report == "unshipped") echo "Unshipped";
		if ($report == "shipped") echo "Shipped";
		if ($report == "closed") echo "Closed";
		echo " purchase orders for vendor <span class=\"formlabel\"><i>$vendorname</i> <a href=\"vendor.php?id=$vendorid\"><img src=\"images/icon_profile.gif\" border=\"0\" alt=\"Edit vendor information for $vendorname\"></a></span>...</p></td></tr></table>
			<table width=\"600\" border=\"0\" cellpadding=\"0\">";
		if ($emerchant_user != "admin") echo "
			<tr><td width=\"200\"><p><b>Date</b></p></td>
			<td width=\"200\"><p><b>Purchase Order ID</b></p></td>
			<td width=\"200\"><p><b>Customer</b></p></td></tr>
			$shiporderstring
			</table><br><br>";
		else echo "
			<tr><td width=\"150\"><p><b>Date</b></p></td>
			<td width=\"40\" align=\"right\"><p><b>Cost</b></p></td><td width=\"110\">&nbsp;</td>
			<td width=\"150\"><p><b>Purchase Order ID</b></p></td>
			<td width=\"150\"><p><b>Customer</b></p></td></tr>
			$shiporderstring
			<tr><td colspan=\"5\"><hr></td></tr>
			<tr><td><p><b>Total:</b></p></td><td width=\"40\" align=\"right\"><p>".number_format($totalcost,2,'.','')."</p></td><td colspan=\"3\">&nbsp;</td></tr>
			</table><br><br>";
	}
}
if ($noorders) echo "<span class=\"heading2\">There are no $report purchase orders for the selected vendor(s).</span>";
echo "</td></tr></table></td></tr><tr><td align=\"center\" colspan=\"2\"></td></tr></table>$footer";
?>