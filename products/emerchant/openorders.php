<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Open Orders";
include "template.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Open Orders");
echo "<table width=\"650\" border=\"0\" cellpadding=\"5\" align=\"center\">
        <tr> 
          <td height=\"172\" align=\"center\">";
// Get all open orders from the database and print them ordered by vendor...
unset($openorders);
unset($totalcost);
$noorders = 1;
if ($vendor == "all") $vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");
else $vendorresult = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
while ($vendorrow = @mysqli_fetch_array($vendorresult)) {
	unset($openorderstring);
	unset($vendorcost);
	$vendorid = $vendorrow["vendorid"];
	$vendorname = $vendorrow["name"];

	$ordersresult = @mysqli_query($db, "SELECT * FROM orders WHERE vendors LIKE '%|$vendorid|%' AND orders.userid LIKE '%|1|%' AND orders.paid != '' AND (shipped = '' OR shipped IS NULL) AND (reference = '' OR reference IS NULL) AND price >= 0 ORDER BY date ASC");
	
	if (@mysqli_num_rows($ordersresult)) while ($orderrow = @mysqli_fetch_array($ordersresult)) {
		$orderid = $orderrow["orderid"];
		$wholesale = $orderrow["wholesale"];
		if ($wholesale) $ws = " W";
		else $ws = "";
		$orderproducts = $orderrow["products"];
		unset($productsincart);
		$productsincart = ashop_parseproductstring($db, $orderproducts);
		$checkreturn = @mysqli_query($db, "SELECT * FROM orders WHERE reference='$orderid' AND price<0");
		if (!@mysqli_num_rows($checkreturn)) {
			$date = explode(" ",$orderrow["date"]);
			$customerid = $orderrow["customerid"];

			$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
			$customer = @mysqli_result($customerresult,0,"firstname")." ".@mysqli_result($customerresult,0,"lastname");
			
			$checkporesult = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE orderid='$orderid' AND vendorid='$vendorid'");

			if (!@mysqli_num_rows($checkporesult) && (!is_array($openorders[$vendorid]) || !in_array($orderid,$openorders[$vendorid]))) {
				$openorders[$vendorid][] = $orderid;
				if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
					$thisproductid = $thisproduct["productid"];
					$productsresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$thisproductid' AND vendorid='$vendorid'");
					$cost = @mysqli_result($productsresult,0,"cost");
					$vendorcost += $thisproduct["quantity"]*$cost;
					$totalcost += $thisproduct["quantity"]*$cost;
				}
				$openorderstring .= "<tr><td width=\"100\"><p>{$date[0]}</p></td>
				    <td width=\"100\"><p>$orderid$ws</p></td>
					<td width=\"100\"><p>$customer</p></td>
					<td width=\"150\"><p><a href=\"purchaseorder.php?vendor=$vendorid&order=$orderid\"><img src=\"images/button_po.gif\" border=\"0\" alt=\"Create Purchase Order\"></a></p></td></tr>";
			}
		}
	}
	if ($openorderstring) {
		$noorders = 0;
		echo "<table width=\"600\" border=\"0\" cellpadding=\"0\"><tr><td>
			<p>Open orders for vendor <span class=\"formlabel\"><i>$vendorname</i></span>...</p></td></tr></table>
			<table width=\"600\" border=\"0\" cellpadding=\"0\">
			<tr><td width=\"150\"><p><b>Date</b></p></td>
			<td width=\"150\"><p><b>Orderid</b></p></td>
			<td width=\"150\"><p><b>Customer</b></p>
			<td width=\"150\">&nbsp;</td>
			</td></tr>
			$openorderstring
			</table><div class=\"text\"><b>Cost:</b> ".number_format($vendorcost,2,'.','')."</div><br><br>";
	}
}
if ($noorders) echo "<span class=\"heading2\">There are no open orders for the selected vendor(s).</span>";
echo "<div class=\"text\"><b>Total cost:</b> ".number_format($totalcost,2,'.','')."</div><br><br></td></tr></table></td></tr><tr><td align=\"center\" colspan=\"2\"></td></tr></table>$footer";
?>