<?php
include "../admin/checklicense.inc.php";
include "emfunc.inc.php";
include "checklogin.inc.php";
$date = date("Y-m-d", time()+$timezoneoffset);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get purchase order information...
$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE purchaseorderid='$po'");
$porow = @mysqli_fetch_array($result);
$orderid = $porow["orderid"];
$products = $porow["products"];
$vendor = $porow["vendorid"];
$comments = $porow["comments"];
$commentprices = $porow["commentprices"];
$itemorder = $porow["itemorder"];
$customer = $porow["customerid"];
$shipping = $porow["shipping"];
if ($customer) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$customerrow = @mysqli_fetch_array($result);
	$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
	$shippingrow = @mysqli_fetch_array($result);
}
$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
$vendorrow = @mysqli_fetch_array($result);
$result = @mysqli_query($db, "SELECT * FROM order WHERE orderid='$orderid'");
$norderid = @mysqli_result($result, 0, "remoteorderid");
if ($norderid) $orderid = $norderid;
echo "
<html>
<head>
<title>Purchase Order $po";
if (is_array($customerrow)) echo ", {$customerrow["firstname"]} {$customerrow["lastname"]}";
echo "</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
</head>
<body bgcolor=\"#FFFFFF\" text=\"#000000\">
<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\" align=\"center\">
  <tr> 
    <td colspan=\"4\" class=\"subtitle\">Purchase Order $po";
if (is_array($customerrow)) echo ", {$customerrow["firstname"]} {$customerrow["lastname"]}";
echo "</td>
    <td class=\"sm\" align=\"right\">$date</td>
  </tr>
  <tr>
    <td colspan=\"5\" class=\"sm\">$ashopname<br>$ashopaddress<br>Phone: $ashopphone<br>eMail: $ashopemail</td>
  </tr>
  <tr> 
    <td colspan=\"5\">";
if ($ashoppath && file_exists("$ashoppath/emerchant/poheader.inc.php")) include "poheader.inc.php";
echo "</td>
  </tr>
  <tr> 
    <td colspan=\"5\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td colspan=\"5\" class=\"sm\"><b>Vendor:</b><br>
	{$vendorrow["name"]}<br>
	{$vendorrow["address"]}<br>
	{$vendorrow["city"]}, {$vendorrow["state"]}, {$vendorrow["zip"]}<br>
	Phone: {$vendorrow["phone"]}</td>
  </tr>
  <tr> 
    <td colspan=\"5\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>";
if (is_array($shippingrow)) {
	echo "<tr> 
    <td colspan=\"5\" class=\"sm\"><b>Ship to:</b><br>";
	if ($shippingrow["shippingbusiness"]) echo $shippingrow["shippingbusiness"]."<br>";
	echo "{$shippingrow["shippingfirstname"]} {$shippingrow["shippinglastname"]}<br>
	{$shippingrow["shippingaddress"]}<br>";
	if ($shippingrow["shippingaddress2"]) echo "{$shippingrow["shippingaddress2"]}<br>";
	echo "{$shippingrow["shippingcity"]}, {$shippingrow["shippingstate"]}, {$shippingrow["shippingzip"]}<br>
	Phone: {$customerrow["phone"]}</td>
	</tr>";
}
echo "
  <tr> 
    <td colspan=\"5\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr>
	<td width=\"80\" align=\"left\" class=\"sm\"><b>SKU</b></td>
    <td width=\"320\" class=\"sm\"><b>Item</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\"><b>Price</b></td>
    <td align=\"center\" width=\"60\" class=\"sm\"><b>Qty</b></td>
    <td align=\"right\" width=\"60\" class=\"sm\"><b>Amount</b></td>
  </tr>";

// List products in purchase order...
$subtotal = 0;
$calculateshipping = FALSE;
unset($productlist);
$productsincart = ashop_parseproductstring($db, $products);
if($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
	$sku = $thisproduct["sku"];
	$productid = $thisproduct["productid"];
	$quantity = $thisproduct["quantity"];
	$price = $thisproduct["price"];
	$name = $thisproduct["name"];
	$parameters = $thisproduct["parameters"];
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	$thiscost = @mysqli_result($result,0,"cost");
	if (!$thiscost) $thiscost = 0.00;
	$thistotal = $thiscost * $thisproduct["quantity"];
	$subtotal += $thistotal;
	if (@mysqli_result($result,0,"inventory")) $available = "In Stock";
	else $available = "";
	$productlist[] = "<tr>
	<td width=\"80\" align=\"left\" class=\"sm\">$sku</td>
	<td width=\"320\" class=\"sm\">$name $parameters</td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($thiscost,2,'.','')."</td>
    <td width=\"60\" align=\"right\" class=\"sm\">$quantity</td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($thistotal,2,'.','')."</td>
  </tr>";
}

// Include selected comments...
$commentprice = 0;
$commentsarray = explode("|",substr($comments,0,-1));
$commentpricesarray = explode("|",substr($commentprices,0,-1));
unset($commentlist);
if ($commentsarray) foreach ($commentsarray as $commentnumber=>$thiscommentid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendornotes WHERE id='$thiscommentid'");
	$row = @mysqli_fetch_array($result);
	$thiscomment = $row["note"];
	$commentprice += $commentpricesarray["$commentnumber"];
	if ($thiscomment && $commentpricesarray["$commentnumber"]) $commentlist[] = "<tr><td width=\"80\" align=\"right\" class=\"sm\">&nbsp;</td><td width=\"320\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td></tr>";
	else if ($thiscomment) $commentlist[] = "<tr><td width=\"400\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">&nbsp;</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">&nbsp;</td></tr>";
}

// Calculate total cost...
$subtotal += $commentprice;

$totalcost = $subtotal + $shipping;

// Display items...
if (is_array($productlist)) reset($productlist);
if (is_array($commentlist)) reset($commentlist);
for ($ch = 0; $ch < strlen($itemorder); $ch++) {
	if (is_array($productlist) && substr($itemorder,$ch,1) == "p") {
		echo current($productlist);
		next($productlist);
	} else if (is_array($commentlist) && substr($itemorder,$ch,1) == "c") {
		echo current($commentlist);
		next($commentlist);
	}
}

echo "<tr> 
    <td colspan=\"5\" height=\"12\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td colspan=\"4\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Subtotal:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">".number_format($subtotal,2,'.','')."</td>
  </tr>
  <tr> 
    <td colspan=\"4\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Shipping:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">".number_format($shipping,2,'.','')."</td>
  </tr>
  <tr> 
    <td colspan=\"4\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Total 
      Amount:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\"><b>".number_format($totalcost,2,'.','')."</b></td>
  </tr>
  <tr> 
    <td colspan=\"4\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Order 
      Reference Number:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">$orderid</td>
  </tr>
  <tr> 
    <td colspan=\"6\">";
if ($ashoppath && file_exists("$ashoppath/emerchant/pofooter.inc.php")) include "footer.inc.php";
echo "</td>
  </tr>
</table>
</body>
</html>";
@mysqli_close($db);
?>