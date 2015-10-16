<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Email Purchase Order";
include "template.inc.php";
$date = date("Y-m-d", time()+$timezoneoffset);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get sender mail address...
$result = @mysqli_query($db, "SELECT * FROM emerchant_configuration WHERE confname='vendoremail'");
$ashopemail = @mysqli_result($result, 0, "confvalue");

// Get purchase order information...
$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE purchaseorderid='$po'");
$porow = @mysqli_fetch_array($result);
$vendor = $porow["vendorid"];
$products = $porow["products"];
$comments = $porow["comments"];
$commentprices = $porow["commentprices"];
$commenttaxable = $porow["commenttaxable"];
$itemorder = $porow["itemorder"];
$customer = $porow["customerid"];
$shipping = $porow["shipping"];
$orderreference = "em".sprintf("%06d",$quote);
if ($customer) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$customerrow = @mysqli_fetch_array($result);
	$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
	$shippingrow = @mysqli_fetch_array($result);
}
$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
$vendorrow = @mysqli_fetch_array($result);
$vendcontactsresult = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendorid='$vendor' ORDER BY lastname, firstname");
if ($vendorcontact) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendcontact WHERE vendcontactid='$vendorcontact'");
	$vendcontactrow = @mysqli_fetch_array($result);
	$sendtoemail = $vendcontactrow["email"];
} else $sendtoemail = $vendorrow["email"];

// Get message header...
$messageheader = "";
if ($ashoppath && file_exists("$ashoppath/emerchant/poheader.inc.php")) {
	$fp = fopen ("$ashoppath/emerchant/poheader.inc.php", "r");
	while (!feof($fp)) $messageheader .= fgets($fp,128);
}

// Get message footer...
$messagefooter = "";
if ($ashoppath && file_exists("$ashoppath/emerchant/pofooter.inc.php")) {
	$fp = fopen ("$ashoppath/emerchant/pofooter.inc.php", "r");
	while (!feof($fp)) $messagefooter .= fgets($fp,128);
}

$pomessage = "<html>
<head>
<title>Purchase Order $po";
if (is_array($customerrow)) $pomessage .", {$customerrow["firstname"]} {$customerrow["lastname"]}";
$pomessage .= "</title>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
<style type=\"text/css\">
<!--
.subtitle {  font-family: Arial, Helvetica, sans-serif; font-size: 11pt; font-weight: bold; color: #003399 }
.sm {  font-family: Arial, Helvetica, sans-serif; font-size: 9pt}
-->
</style>
</head>
<body bgcolor=\"#FFFFFF\" text=\"#000000\">
<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\" align=\"center\">
  <tr> 
    <td colspan=\"4\" class=\"subtitle\">Purchase Order $po";
	if (is_array($customerrow)) $pomessage .= ", {$customerrow["firstname"]} {$customerrow["lastname"]}";
	$pomessage .= "</td>
    <td class=\"sm\" align=\"right\">$date</td>
  </tr>
  <tr>
    <td colspan=\"5\" class=\"sm\">$ashopname<br>$ashopaddress<br>Phone: $ashopphone<br>eMail: $ashopemail</td>
  </tr>
  <tr> 
    <td colspan=\"5\">
	$messageheader
	</td>
  </tr>
  <tr> 
    <td colspan=\"5\"><img src=\"$ashopurl/emerchant/images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td colspan=\"5\" class=\"sm\"><b>Vendor:</b><br>
	{$vendorrow["name"]}<br>
	{$vendorrow["address"]}<br>
	{$vendorrow["city"]}, {$vendorrow["state"]}, {$vendorrow["zip"]}<br>
	Phone: {$vendorrow["phone"]}</td>
  </tr>
  <tr> 
    <td colspan=\"5\"><img src=\"$ashopurl/emerchant/images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>";
if (is_array($shippingrow)) {
	$pomessage .= "
  <tr> 
    <td colspan=\"5\" class=\"sm\"><b>Ship to:</b><br>";
  if ($shippingrow["shippingbusiness"]) $pomessage .= $shippingrow["shippingbusiness"]."<br>";
  $pomessage .= "{$shippingrow["shippingfirstname"]} {$shippingrow["shippinglastname"]}<br>
	{$shippingrow["shippingaddress"]}<br>";
  if ($shippingrow["shippingaddress2"]) $pomessage .= "{$shippingrow["shippingaddress2"]}<br>";
  $pomessage .= "{$shippingrow["shippingcity"]}, {$shippingrow["shippingstate"]}, {$shippingrow["shippingzip"]}<br>
	Phone: {$customerrow["phone"]}</td>
  </tr>";
}
  $pomessage .= "
  <tr> 
    <td colspan=\"5\"><img src=\"$ashopurl/emerchant/images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr>
	<td width=\"80\" align=\"left\" class=\"sm\"><b>SKU</b></td>
    <td width=\"320\" class=\"sm\"><b>Item</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\"><b>Price</b></td>
    <td align=\"center\" width=\"60\" class=\"sm\"><b>Qty</b></td>
    <td align=\"right\" width=\"60\" class=\"sm\"><b>Amount</b></td>
  </tr>";

// Create a list of products in quote...
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
	$productlist[] .= "<tr>
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
	if ($thiscomment && $commentpricesarray["$commentnumber"]) $commentlist[] .= "<tr><td width=\"80\" align=\"right\" class=\"sm\">&nbsp;</td><td width=\"320\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td></tr>";
	else if ($thiscomment) $commentlist[] .= "<tr><td width=\"60\" align=\"right\" class=\"sm\">&nbsp;</td><td width=\"400\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td></tr>";
	else if ($thiscomment) $commentlist[] = "<tr><td width=\"60\" align=\"right\" class=\"sm\">&nbsp;</td><td width=\"400\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">&nbsp;</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">&nbsp;</td></tr>";
}

// Calculate total cost...
$subtotal += $commentprice;

$totalcost = $subtotal + $shipping;

// Display items...
if (is_array($productlist)) reset($productlist);
if (is_array($commentlist)) reset($commentlist);
for ($ch = 0; $ch < strlen($itemorder); $ch++) {
	if (is_array($productlist) && substr($itemorder,$ch,1) == "p") {
		$pomessage .= current($productlist);
		next($productlist);
	} else if (is_array($commentlist) && substr($itemorder,$ch,1) == "c") {
		$pomessage .= current($commentlist);
		next($commentlist);
	}
}

$pomessage .= "<tr> 
    <td colspan=\"5\" height=\"12\"><img src=\"$ashopurl/emerchant/images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td colspan=\"4\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Subtotal:</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($subtotal,2,'.','')."</td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Shipping:</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($shipping,2,'.','')."</td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\" class=\"sm\" colspan=\"3\"><b>Total 
      Amount:</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\"><b>".number_format($totalcost,2,'.','')."</b></td>
  </tr>
  <tr> 
    <td colspan=\"6\">
	$messagefooter
	</td>
  </tr>
</table>
</body>
</html>";

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Email Purchase Order");
echo "<form action=\"emailpo.php\" method=\"post\" name=\"emailform\"><table width=\"100%\" border=\"0\" cellpadding=\"0\">";

if ($send == "Send" && $subject && ($sendtoemail || $vendorcontact == "none")) {
	if ($vendorcontact != "none") {
		$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

		@ashop_mail("$sendtoemail","$subject","$pomessage","$headers");
	}

	@mysqli_query($db, "UPDATE emerchant_purchaseorder SET sent='$date' WHERE purchaseorderid='$po'");

	echo "<tr><td class=\"heading3\">Purchase order number $po has been sent!</td></tr>";
} else {
	echo "<tr> 
          <td class=\"heading3\">Review and edit the message to be sent with the 
            purchase order... </td>
        </tr>
        <tr> 
          <td height=\"36\"> 
            <p>To: <select name=\"vendorcontact\" onChange=\"emailform.submit()\"><option value=\"0\"";
	if (!$vendorcontact) echo " selected";
	echo ">Main eMail</option><option value=\"none\""; if ($vendorcontact == "none") echo " selected"; echo ">No recipient</option>";
	while ($contactrow = @mysqli_fetch_array($vendcontactsresult)) {
		echo "<option value=\"{$contactrow["vendcontactid"]}\"";
		if ($vendorcontact == $contactrow["vendcontactid"]) echo " selected";
		echo ">{$contactrow["firstname"]} {$contactrow["lastname"]}</option>";
	}

	echo "</select><br>
              Subject: 
              <input type=\"text\" name=\"subject\" value=\"Purchase Order: $po, {$customerrow["firstname"]}&nbsp;{$customerrow["lastname"]}\" size=\"50\">
            </p>
          </td>
        </tr>
        <tr> 
          <td height=\"2\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
        </tr>
        <tr>
          <td><blockquote><font face=\"Courier New, Courier, mono\" size=\"2\">$pomessage</font></blockquote></td>
        </tr>
        <tr>
          <td><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
        </tr>
        <tr>
          <td align=\"center\"><table width=\"130\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td><input type=\"button\" value=\"Revise\" onClick=\"document.location.href='purchaseorder.php?edit=$po'\"></td><td><input type=\"hidden\" name=\"po\" value=\"$po\"><input type=\"submit\" name=\"send\" value=\"Send\"></td></tr></table></td>
        </tr>";
}
echo "</table></form>
      </td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>
$footer";
@mysqli_close($db);
?>