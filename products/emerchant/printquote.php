<?php
include "../admin/checklicense.inc.php";
include "emfunc.inc.php";
include "checklogin.inc.php";
$date = date("Y-m-d", time()+$timezoneoffset);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get quote information...
$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$quote'");
$quoterow = @mysqli_fetch_array($result);
$products = $quoterow["products"];
$comments = $quoterow["comments"];
$commentprices = $quoterow["commentprices"];
$commenttaxable = $quoterow["commenttaxable"];
$itemorder = $quoterow["itemorder"];
$customer = $quoterow["customerid"];
$shipping = $quoterow["shipping"];
$orderreference = "em".sprintf("%06d",$quote);
if ($customer) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$customerrow = @mysqli_fetch_array($result);
	$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
	$shippingrow = @mysqli_fetch_array($result);
} else {
	$shippingrow["shippingzip"] = $quoterow["qqzip"];
	$shippingrow["shippingstate"] = $quoterow["qqstate"];
}

echo "
<html>
<head>
<title>Quote from $ashopname</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<link rel=\"stylesheet\" href=\"emerchant.css\" type=\"text/css\">
</head>
<body bgcolor=\"#FFFFFF\" text=\"#000000\">
<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\" align=\"center\">
  <tr> 
    <td colspan=\"3\" class=\"subtitle\">Quote from $ashopname</td>
    <td colspan=\"2\" class=\"sm\" align=\"right\">$date</td>
  </tr>
  <tr> 
    <td colspan=\"5\">";
include "header.inc.php";
echo "</td>
  </tr>
  <tr> 
    <td colspan=\"5\" class=\"sm\">To: {$customerrow["firstname"]} {$customerrow["lastname"]} {$customerrow["email"]} 
	{$customerrow["phone"]} {$customerrow["fax"]}</td>
  </tr>
  <tr> 
    <td colspan=\"5\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td width=\"400\" class=\"sm\"><b>Item</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\"><b>Price</b></td>
    <td align=\"center\" width=\"60\" class=\"sm\"><b>Qty</b></td>
    <td align=\"right\" width=\"60\" class=\"sm\"><b>Amount</b></td>
    <td align=\"center\" width=\"70\" class=\"sm\"><b>Available</b></td>
  </tr>";

// List products in quote...
$subtotal = 0;
$calculateshipping = FALSE;
unset($productlist);
$productsincart = ashop_parseproductstring($db, $products);
if($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
	$productid = $thisproduct["productid"];
	$quantity = $thisproduct["quantity"];
	$discounted = $thisproduct["discounted"];
	if ($discounted) {
		// Check if there is a sale...
		$result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' AND onetime='0' AND (code='' OR code IS NULL)");
		if (@mysqli_num_rows($result2)) {
			$discounttype = @mysqli_result($result2,0,"type");
			$discountvalue = @mysqli_result($result2,0,"value");
			$regprice = $productrow["price"];
			if ($discounttype == "%") $thisproduct["price"] = $thisproduct["price"] - ($thisproduct["price"] * ($discountvalue/100));
			else if ($discounttype == "$") $thisproduct["price"] -= $discountvalue;
		}
	}
	$price = $thisproduct["price"];
	$name = $thisproduct["name"];
	$parameters = $thisproduct["parameters"];
	if ($parameters) $name .= " ".$parameters;
	if ($discounted) $name .= " (discounted)";
	$thistotal = $thisproduct["price"] * $thisproduct["quantity"];
	$subtotal += $thistotal;
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	$available = @mysqli_result($result,0,"inventory");
	if (!$available) $available = "&nbsp;";
	else $available = "In Stock";
	$productlist[] = "<tr><td width=\"400\" class=\"sm\">$name</td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($price,2,'.','')."</td>
    <td width=\"60\" align=\"right\" class=\"sm\">$quantity</td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($thistotal,2,'.','')."</td>
    <td width=\"70\" align=\"center\" class=\"sm\">$available</td>
  </tr>";
}
if ($shipping) $handlingcosts = ashop_gethandlingcost($shipping);

// Include selected comments...
$commentprice = 0;
$commentsarray = explode("|",substr($comments,0,-1));
$commentpricesarray = explode("|",substr($commentprices,0,-1));
$commenttaxablearray = explode("|",substr($commenttaxable,0,-1));
unset($commentlist);
if ($commentsarray) foreach ($commentsarray as $commentnumber=>$thiscommentid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_notes WHERE id='$thiscommentid'");
	$row = @mysqli_fetch_array($result);
	$thiscomment = $row["note"];
	$commentprice += $commentpricesarray["$commentnumber"];
	if ($thiscomment) $commentlist[] = "<tr><td width=\"400\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"70\">&nbsp;</td></tr>";
}
$subtotal += $commentprice;

// Calculate total cost...
$totalcost = $subtotal + $handlingcosts["salestax"] + $handlingcosts["shipping"];

// Display items...
if (is_array($productlist)) reset($productlist);
if (is_array($commentlist)) reset($commentlist);
for ($ch = 0; $ch < strlen($itemorder); $ch++) {
	if (substr($itemorder,$ch,1) == "p") {
		echo current($productlist);
		next($productlist);
	} else if (substr($itemorder,$ch,1) == "c") {
		echo current($commentlist);
		next($commentlist);
	}
}

echo "<tr> 
    <td colspan=\"5\" height=\"12\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\" class=\"sm\"><b>Subtotal:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">".number_format($subtotal,2,'.','')."</td>
    <td width=\"80\" align=\"center\">&nbsp;</td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Sales 
      Tax:</font></b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">".number_format($handlingcosts["salestax"],2,'.','')."</td>
    <td width=\"80\" align=\"center\" class=\"sm\"><b>{$shippingrow["shippingstate"]}</b></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\" class=\"sm\"><b>Shipping 
      to zip {$shippingrow["shippingzip"]}:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">".number_format($handlingcosts["shipping"],2,'.','')."</td>
    <td width=\"80\" align=\"center\"></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\" class=\"sm\"><b>Total 
      Amount:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\"><b>".number_format($totalcost,2,'.','')."</b></td>
    <td width=\"80\" align=\"center\" class=\"sm\"></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\" class=\"sm\"><b>Order 
      Reference Number:</b></td>
    <td width=\"70\" align=\"right\" class=\"sm\">$orderreference</td>
    <td width=\"80\" align=\"center\"></td>
  </tr>
  <tr> 
    <td colspan=\"5\">";
include "footer.inc.php";
echo "</td>
  </tr>
</table>
</body>
</html>";
@mysqli_close($db);
?>