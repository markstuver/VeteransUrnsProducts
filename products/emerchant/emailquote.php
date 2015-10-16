<?php
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Email Quote";
include "template.inc.php";
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
if($customer) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$customerrow = @mysqli_fetch_array($result);
	$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
	$shippingrow = @mysqli_fetch_array($result);
} else {
	$shippingrow["shippingzip"] = $quoterow["qqzip"];
	$shippingrow["shippingstate"] = $quoterow["qqstate"];
}

// Get message header...
$messageheader = "";
if ($ashoppath && file_exists("$ashoppath/emerchant/header.inc.php")) {
	$fp = fopen ("$ashoppath/emerchant/header.inc.php", "r");
	while (!feof($fp)) $messageheader .= fgets($fp,128);
}

// Get message footer...
$messagefooter = "";
if ($ashoppath && file_exists("$ashoppath/emerchant/footer.inc.php")) {
	$fp = fopen ("$ashoppath/emerchant/footer.inc.php", "r");
	while (!feof($fp)) $messagefooter .= fgets($fp,128);
}

$quotemessage = "<p>Hi {$customerrow["firstname"]},</p><p>$messageheader</p><table width=\"600\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\"><tr><td colspan=\"5\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td width=\"400\" class=\"sm\"><b>Item</b></td>
    <td width=\"60\" align=\"right\" class=\"sm\"><b>Price</b></td>
    <td align=\"center\" width=\"60\" class=\"sm\"><b>Qty</b></td>
    <td align=\"right\" width=\"60\" class=\"sm\"><b>Amount</b></td>
    <td align=\"center\" width=\"70\" class=\"sm\"><b>Available</b></td>
  </tr>";

// Create a list of products in quote...
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
	$productlist[] .= "<tr><td width=\"400\" class=\"sm\">$name</td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($price,2,'.','')."</td>
    <td width=\"60\" align=\"right\" class=\"sm\">$quantity</td>
    <td width=\"60\" align=\"right\" class=\"sm\">".number_format($thistotal,2,'.','')."</td>
    <td width=\"70\" align=\"center\" class=\"sm\">$available</td>
  </tr>";
}
if ($shipping) $handlingcosts = ashop_gethandlingcost($shipping);

// Include selected comments...
$commentprice = 0;
$commenttax = 0;
$commentsarray = explode("|",substr($comments,0,-1));
$commentpricesarray = explode("|",substr($commentprices,0,-1));
$commenttaxablearray = explode("|",substr($commenttaxable,0,-1));
unset($commentlist);
if ($commentsarray) foreach ($commentsarray as $commentnumber=>$thiscommentid) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_notes WHERE id='$thiscommentid'");
	$row = @mysqli_fetch_array($result);
	$thiscomment = $row["note"];
	$commentprice += $commentpricesarray["$commentnumber"];
	if ($thiscomment) $commentlist[] .= "<tr><td width=\"400\" class=\"sm\">$thiscomment</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"60\">&nbsp;</td><td width=\"60\" class=\"sm\" align=\"right\">".number_format($commentpricesarray["$commentnumber"],2,'.','')."</td><td width=\"70\">&nbsp;</td></tr>";
}
$subtotal += $commentprice;

// Calculate total cost...
$totalcost = $subtotal + $handlingcosts["salestax"] + $handlingcosts["shipping"];

// Display items...
if (is_array($productlist)) reset($productlist);
if (is_array($commentlist)) reset($commentlist);
for ($ch = 0; $ch < strlen($itemorder); $ch++) {
	if (substr($itemorder,$ch,1) == "p") {
		$quotemessage .= current($productlist);
		next($productlist);
	} else if (substr($itemorder,$ch,1) == "c") {
		$quotemessage .= current($commentlist);
		next($commentlist);
	}
}

$quotemessage .= "</td>
  </tr>
  <tr> 
    <td colspan=\"5\" height=\"12\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Subtotal:</font></b></td>
    <td width=\"70\" align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($subtotal,2,'.','')."</font></td>
    <td width=\"80\" align=\"center\">&nbsp;</td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Sales 
      Tax:</font></b></td>
    <td width=\"70\" align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($handlingcosts["salestax"],2,'.','')."</font></td>
    <td width=\"80\" align=\"center\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">{$shippingrow["shippingstate"]} 
      </font></b></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Shipping 
      to zip {$shippingrow["shippingzip"]}:</font></b></td>
    <td width=\"70\" align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($handlingcosts["shipping"],2,'.','')."</font></td>
    <td width=\"80\" align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"></font></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Total 
      Amount:</font></b></td>
    <td width=\"70\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".number_format($totalcost,2,'.','')."</font></b></td>
    <td width=\"80\" align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"></font></td>
  </tr>
  <tr> 
    <td colspan=\"3\" align=\"right\"><b><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">Order 
      Reference Number:</font></b></td>
    <td width=\"70\" align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$orderreference</font></td>
    <td width=\"80\" align=\"center\"></td>
  </tr>";
		
$quotemessage .= "</table><p>$messagefooter</p>";

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Email Quote");
echo "<form action=\"emailquote.php\" method=\"post\"><table width=\"700\" border=\"0\" cellpadding=\"0\" align=\"center\">";

if ($send == "true" && $subject && $sendtoemail) {
	$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

	@ashop_mail("$sendtoemail",$subject,"<html><body>$quotemessage</body></html>","$headers");

	echo "<tr><td class=\"heading3\">Quote number $quote has been sent!</td></tr>";
} else echo "<tr> 
          <td class=\"heading3\"><br><br>Review and edit the message to be sent with the 
            quote...<br><br></td>
        </tr>
        <tr> 
          <td height=\"36\"> 
            <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td width=\"50\" class=\"regular\" align=\"left\">To:</td><td> 
              <input type=\"text\" name=\"sendtoemail\" value=\"{$customerrow["email"]}\" size=\"30\">
              </td></tr>
			  <tr>
			  <td class=\"regular\" align=\"left\">Subject:</td><td>
              <input type=\"text\" name=\"subject\" value=\"Quote from $ashopname, $date\" size=\"50\">
			  </td></tr></table><br>
          </td>
        </tr>
        <tr> 
          <td height=\"2\"><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
        </tr>
        <tr>
          <td>$quotemessage</td>
        </tr>
        <tr>
          <td><img src=\"images/1pixdkblu.gif\" width=\"100%\" height=\"1\"></td>
        </tr>
        <tr>
          <td align=\"center\"><table width=\"130\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td><input type=\"button\" value=\"Revise\" onClick=\"document.location.href='quote.php?edit=$quote'\"></td><td><input type=\"hidden\" name=\"quote\" value=\"$quote\"><input type=\"hidden\" name=\"send\" value=\"true\"><input type=\"submit\" value=\"Send\"></td></tr></table></td>
        </tr>";
echo "</table></form>
      </td>
  </tr>
  <tr> 
    <td align=\"center\" colspan=\"2\"></td>
  </tr>
</table>
$footer";
?>