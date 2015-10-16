<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Create Quote or Order";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "quote";
include "emhelp.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Update quick quote...
if ($quickquote && $customer) {
	$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$qqresult = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$quickquote'");
	if (!@mysqli_num_rows($customerresult)) {
		header("Location: customer.php?errormessage=No such customer!");
		exit;
	} else if (!@mysqli_num_rows($qqresult)) {
		header("Location: customer.php?errormessage=No such quote!");
		exit;
	}
	@mysqli_query($db, "UPDATE emerchant_quotes SET customerid='$customer', qqzip='', qqstate='' WHERE id='$quickquote'");
	header("Location: quote.php?edit=$quickquote");
	exit;
}

// Get quote information for editing...
if ($edit) {
	if (!$products && !$customer) {
		if (strstr("$edit","em")) {
			$edit = str_replace("em","",$edit);
			$edit = number_format($edit,0);
		}
		$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$edit'");
		$row = @mysqli_fetch_array($result);
		$products = $row["products"];
		$comments = $row["comments"];
		$customer = $row["customerid"];
		$modifieddate = $row["date"];
		$destzip = $row["qqzip"];
		$deststate = $row["qqstate"];
		$commentprices = $row["commentprices"];
		$commenttaxable = $row["commenttaxable"];
		$itemorder = $row["itemorder"];
		$shipping = $row["shipping"];
	}
	if ($save != "true" && !$addproduct_x && !$addcomment_x && !$addcredit_x && !$deleterow_x && !$attributeset) {
		$result = @mysqli_query($db, "SELECT * FROM emerchant_quotes WHERE id='$edit'");
		$row = @mysqli_fetch_array($result);
		$handlingcosts = ashop_gethandlingcost($row["shipping"]);
	}
	$quote = $edit;
	if ($destzip && $deststate) $quickquote = "true";
}

if (!$destzip || !$deststate) $quickquote = "";

if (!$customer && $quickquote != "true") {
	echo $header;
	emerchant_sidebar();
	echo "<td valign=\"top\">";
	if (!$edit) emerchant_topbar("New Quote");
	else emerchant_topbar("Edit Quote");
	echo "<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	<tr><td><div align=\"center\" class=\"heading3\"><br>";
	if ($edit) echo "There is no quote with that number!";
	else echo "Error! No customer selected!";
	echo "</td></tr></table></td></tr></table>";
	exit;
}

if (!$modifieddate) $modifieddate = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Save quote...
if ($save == "true" && ($products || $comments) && ($customer || $quickquote == "true")) {

	// Store current prices for future reference...
	unset($pricesarray);
	$productsincart = ashop_parseproductstring($db, $products);
	if($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
		$thisproductid = $thisproduct["productid"];
		$thisprice = $thisproduct["price"];
		$pricesarray["$thisproductid"] = $thisprice;
	}
	if ($pricesarray) foreach($pricesarray as $productid => $price) $productprices .= "$productid:$price|";
	$productprices = substr($productprices,0,-1);

	if ($quote) @mysqli_query($db, "UPDATE emerchant_quotes SET customerid='$customer', date='$modifieddate', products='$products', comments='$comments', commentprices='$commentprices', commenttaxable='$commenttaxable', itemorder='$itemorder', shipping='$shipping', productprices='$productprices', qqzip='$destzip', qqstate='$deststate' WHERE id='$quote'");
	else $result = @mysqli_query($db, "INSERT INTO emerchant_quotes (user, customerid, date, products, comments, commentprices, commenttaxable, itemorder, shipping, productprices, qqzip, qqstate) VALUES ('$emerchant_user', '$customer', '$modifieddate', '$products', '$comments', '$commentprices', '$commenttaxable', '$itemorder', '$shipping', '$productprices', '$destzip', '$deststate')");
	if (@mysqli_affected_rows() == 1) {
		if (!$quote) $quote = @mysqli_insert_id($db);
		$saved = "true";
	}
	if ($send_x) header("Location: emailquote.php?quote=$quote");
	if ($order_x) header("Location: orderquote.php?quote=$quote");
	if ($bill_x) header("Location: orderquote.php?bill=$quote");
}
if ($quote) $orderreference = "em".sprintf("%06d",$quote);

// Set variables for quick quotes...
if ($quickquote == "true" && $destzip && $deststate) {
	$shippingrow["shippingzip"] = $destzip;
	$shippingrow["shippingstate"] = $deststate;
	$shippingrow["shippingcountry"] = "US";
	$shippingrow["shippingfirstname"] = "Unknown";
	$shippingrow["shippinglastname"] = "Unknown";
	$shippingrow["shippingaddress"] = "Unknown";
	$shippingrow["shippingcity"] = "Unknown";

// Get customer information from database if this is no quick quote...
} else {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
	$customerrow = @mysqli_fetch_array($result);
	$result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
	$shippingrow = @mysqli_fetch_array($result);
}

// Validate variables...
if (!empty($shop) && !is_numeric($shop)) $shop = "1";
if (!empty($cat) && !is_numeric($cat)) $cat = "";
if (!empty($currentcat) && !is_numeric($currentcat)) $currentcat = "";
if (!$cat && $currentcat) $cat = $currentcat;
if ($cat == -1) $cat = "";

// Get information about selected category...
if (!empty($cat)) {
	$thiscatresult = @mysqli_query($db, "SELECT name, parentcategoryid, grandparentcategoryid FROM category WHERE categoryid='$cat'");
	$thiscatname = @mysqli_result($thiscatresult,0,"name");
	$thiscatparent = @mysqli_result($thiscatresult,0,"parentcategoryid");
	$thiscatgrandparent = @mysqli_result($thiscatresult,0,"grandparentcategoryid");
	if ($cat == $thiscatparent && $cat == $thiscatgrandparent) $categoryuplevel = -1;
	else if ($cat == $thiscatparent) {
		$thiscatparentresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$thiscatgrandparent'");
		$thiscatparentname = @mysqli_result($thiscatparentresult,0,"name");
		$thiscatname = "$thiscatparentname<br>->$thiscatname";
		$categoryuplevel = $thiscatgrandparent;
	} else {
		$thiscatparentresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$thiscatparent'");
		$thiscatparentname = @mysqli_result($thiscatparentresult,0,"name");
		$thiscatname = "$thiscatparentname<br>->$thiscatname";
		$categoryuplevel = $thiscatparent;
	}
}

// Get categories...
if (!empty($shop)) {
	if ($cat) {
		if ($cat == $thiscatparent && $cat == $thiscatgrandparent) $catresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE grandparentcategoryid='$thiscatgrandparent' AND parentcategoryid=categoryid AND categoryid!='$cat' AND (userid='$shop' OR (memberclone='1' AND memberclone IS NOT NULL)) ORDER BY ordernumber");
		else if ($cat == $thiscatparent) $catresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE parentcategoryid='$thiscatparent' AND categoryid!='$cat' AND (userid='$shop' OR (memberclone='1' AND memberclone IS NOT NULL)) ORDER BY ordernumber");
	} else $catresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE userid='$shop' OR (memberclone='1' AND memberclone IS NOT NULL) ORDER BY ordernumber");
} else {
	if ($cat) {
		if ($cat == $thiscatparent && $cat == $thiscatgrandparent) $catresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE grandparentcategoryid='$thiscatgrandparent' AND parentcategoryid=categoryid AND categoryid!='$cat' ORDER BY ordernumber");
		else if ($cat == $thiscatparent) $catresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE parentcategoryid='$thiscatparent' AND categoryid!='$cat' ORDER BY ordernumber");
	} else $catresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE categoryid=parentcategoryid AND categoryid=grandparentcategoryid ORDER BY ordernumber");
}

// Get products...
if (!empty($cat)) {
	if (!empty($shop)) $productsresult = @mysqli_query($db, "SELECT * FROM product, productcategory WHERE productcategory.categoryid='$cat' AND product.productid=productcategory.productid AND (product.prodtype!='content' OR product.prodtype IS NULL) AND userid='$shop' ORDER BY product.ordernumber");
	else $productsresult = @mysqli_query($db, "SELECT * FROM product, productcategory WHERE productcategory.categoryid='$cat' AND product.productid=productcategory.productid AND (product.prodtype!='content' OR product.prodtype IS NULL) ORDER BY product.ordernumber");
}

if (!$product) 	@mysqli_data_seek($productsresult,0);

if (!$quantity) $quantity = 1;
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$product'", $db);
$productrow = @mysqli_fetch_array($result);
// Check if there is a sale...
$result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$product' AND onetime='0' AND (code='' OR code IS NULL)");
if (@mysqli_num_rows($result2)) {
	$discounttype = @mysqli_result($result2,0,"type");
	$discountvalue = @mysqli_result($result2,0,"value");
	$regprice = $productrow["price"];
	if ($discounttype == "%") $productrow["price"] = $productrow["price"] - ($productrow["price"] * ($discountvalue/100));
	else if ($discounttype == "$") $productrow["price"] -= $discountvalue;
	$salediscount = TRUE;
} else $salediscount = FALSE;
$amount = $quantity * $productrow["price"];
if ($addproduct_x) {
	$products .= "{$quantity}b{$product}";
	if ($salediscount) $products .= "d";
	$products .= "a";
	$itemorder .= "p";
	$quantity = 1;
}

// Add credited products...
if ($addcredit_x) {
	$quantity = -$quantity;
	$products .= "{$quantity}b{$product}a";
	$itemorder .= "p";
	$quantity = 1;
}

// Add and store new comments...
if ($addcomment_x && (($comment && $comment != "select") || $newcomment)) {
	if ($newcomment) {
		unset($comment);
		@mysqli_query($db, "INSERT INTO emerchant_notes (note) VALUES ('$newcomment')");
		$comment = @mysqli_insert_id($db);
	}
	$comments .= "{$comment}|";
	if ($commentprice == "0.00") $commentprice = "0";
	$commentprices .= "{$commentprice}|";
	$itemorder .= "c";
	if ($taxable) $commenttaxable .= "1|";
	else $commenttaxable .= "0|";
}
$commentsresult = @mysqli_query($db, "SELECT * FROM emerchant_notes WHERE reusable='1'");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
if (!$edit) emerchant_topbar("New Quote");
else emerchant_topbar("Edit Quote");

echo "
<script language=\"JavaScript\">
function setattributes(quantity, itemno, products)
{
	window.open(\"setattributes.php?quantity=\"+quantity+\"&item=\"+itemno+\"&products=\"+products,\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=400, height=450\");
}

function edittax(shipping, tax)
{
	window.open(\"editshipping.php?shipping=\"+shipping+\"&tax=\"+tax+\"&edit=tax\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=400, height=100\");
}

function changeshop()
{
	shop = document.getElementById('fromshop').value;
	location.href = 'quote.php?customer=$customer&shop='+shop;
}

function editshipping(shipping, tax)
{
	window.open(\"editshipping.php?shipping=\"+shipping+\"&tax=\"+tax+\"&edit=shipping\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=400, height=100\");
}";

if ($print_x) echo "window.open(\"printquote.php?quote=$quote\",\"_blank\",\"toolbar=yes, location=no, scrollbars=yes, width=700, height=600\");";

echo "</script>
<br><font color=\"#000099\"><div align=\"center\" ID=\"notice\" class=\"heading3\">$notice</div></font>
<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
        <tr>
          <td>
            <div align=\"center\" class=\"heading3\"><br>";
if (!$edit) echo "<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> Create a quote or order...";
else echo "<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> Edit an existing quote or convert to an order...";
echo "<table width=\"650\" border=\"0\" cellpadding=\"0\">
                      <tr> 
                        <td width=\"33%\" class=\"sm\" valign=\"bottom\">";
if ($quote) echo "Quote Number: $quote";
else {
	$memberresult = @mysqli_query($db, "SELECT userid,shopname FROM user WHERE userid!='1'");
	if (@mysqli_num_rows($memberresult)) {
		echo "From: ";
		if (!$products) {
			echo "<select id=\"fromshop\" onchange=\"changeshop()\"><option value=\"\""; if (empty($shop)) echo " selected"; echo ">All Shops</option><option value=\"1\""; if ($shop == "1") echo " selected"; echo ">Main Shop</option>";
			while ($memberrow = @mysqli_fetch_array($memberresult)) {
				echo "\n<option value=\"{$memberrow["userid"]}\"";
				if ($shop == $memberrow["userid"]) echo " selected";
				echo ">{$memberrow["shopname"]}</option>";
			}
			echo "</select>";
		} else while ($memberrow = @mysqli_fetch_array($memberresult)) if ($shop == $memberrow["userid"]) echo $memberrow["shopname"];
	} else echo "&nbsp;";
}	
echo "</td>
                        <td width=\"33%\" class=\"sm\" valign=\"bottom\">"; if ($quote) echo "Quote Date: $modifieddate"; else echo "&nbsp;"; echo "</td>
                        <td width=\"33%\" align=\"right\" class=\"sm\">"; if ($customerrow) echo "<a href=\"customer.php?id=$customer\"><img src=\"images/icon_profile.gif\" width=\"15\" height=\"15\" alt=\"Edit customer information for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a> <a href=\"history.php?customer=$customer\"><img src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('customernote.php?customer=$customer')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this customer.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?customer=$customer')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a>"; else if ($quote && $quickquote) echo "<a href=\"customer.php?quickquote=$quote\">Create/select customer profile</a>"; else echo "&nbsp;"; echo "</td>
                      </tr>
                    </table>
</div>";

if ($quickquote) echo "<table width=\"100%\" class=\"quotetable\" cellpadding=\"1\" cellspacing=\"0\">
              <tr> 
                <td width=\"50%\"><span class=\"formlabel\">Ship To State:</span> <span class=\"sm\">{$shippingrow["shippingstate"]}</span></td>
                <td width=\"50%\"><span class=\"formlabel\">Ship To Zip Code:</span> <span class=\"sm\">{$shippingrow["shippingzip"]}</span></td>
              </tr>
			</table>";
else {
	echo "
			  <table width=\"100%\" class=\"quotetable\" cellpadding=\"5\" cellspacing=\"0\">
			    <tr valign=\"top\"> 
				  <td width=\"50%\"><p><b>Bill To:</b><br>
					<span class=\"sm\">{$customerrow["firstname"]} {$customerrow["lastname"]}<br>
					{$customerrow["address"]}<br>
					{$customerrow["city"]}, {$customerrow["state"]}, {$customerrow["zip"]}<br>
					eMail: {$customerrow["email"]}<br>
					Phone: {$customerrow["phone"]}</span></p>
				  </td>
				  <td width=\"50%\"> 
				    <p><b>Ship To:</b><br>
					<span class=\"sm\">";
				if ($shippingrow["shippingbusiness"]) echo "{$shippingrow["shippingbusiness"]}<br>";
				echo "{$shippingrow["shippingfirstname"]} {$shippingrow["shippinglastname"]}<br>
					{$shippingrow["shippingaddress"]}<br>";
				if ($shippingrow["shippingaddress2"]) echo "{$shippingrow["shippingaddress2"]}<br>";
				echo "{$shippingrow["shippingcity"]}, {$shippingrow["shippingstate"]}, {$shippingrow["shippingzip"]}<br>
					{$shippingrow["shippingcountry"]}</span></p>
				  </td>
				</tr>
              </table>";
}
echo "
            <table width=\"100%\" class=\"quotetable\" height=\"109\" cellpadding=\"1\" cellspacing=\"0\">
			<form action=\"quote.php\" method=\"post\" name=\"productselection\">
			<input type=\"hidden\" name=\"edit\" value=\"$edit\">
			<input type=\"hidden\" name=\"shop\" value=\"$shop\">
			<input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
			<input type=\"hidden\" name=\"destzip\" value=\"$destzip\">
			<input type=\"hidden\" name=\"deststate\" value=\"$deststate\">
			<input type=\"hidden\" name=\"customer\" value=\"$customer\">
			<input type=\"hidden\" name=\"products\" value=\"$products\">
			<input type=\"hidden\" name=\"comments\" value=\"$comments\">
			<input type=\"hidden\" name=\"commentprices\" value=\"$commentprices\">
			<input type=\"hidden\" name=\"commenttaxable\" value=\"$commenttaxable\">
			<input type=\"hidden\" name=\"itemorder\" value=\"$itemorder\">
			<input type=\"hidden\" name=\"attributeset\" value=\"\">
			<input type=\"hidden\" name=\"quote\" value=\"$quote\">
              <tr> 
                <td colspan=\"7\" height=\"6\" bgcolor=\"#CCCCCC\"><font size=\"2\"><span class=\"sm\">Create a quote by adding 
                  one row at a time.</span></font></td>
              </tr>
              <tr> 
                <td width=\"150\"> 
                  <p><b>Category</b></p>
                </td>
                <td width=\"250\"> 
                  <p><b>Product</b></p>
                </td>
                <td width=\"75\" align=\"center\"> 
                  <p><b>Qty</b></p>
                </td>
                <td width=\"25\" align=\"right\"> 
                  <p><b>Amount</b></p>
                </td>
                <td align=\"center\" width=\"100\"> 
                  <p><b>New Row</b></p>
                </td>
              </tr>
              <tr> 
                <td>";
		if ($cat) echo "<p>$thiscatname</p><input type=\"hidden\" name=\"currentcat\" value=\"$cat\"><select name=\"cat\" onChange=\"productselection.product.value=''; productselection.submit()\"><option value=\"0\">Select a category...<option value=\"$categoryuplevel\"> &uarr; Up one level";
		else echo "<select name=\"cat\" onChange=\"productselection.submit()\"><option value=\"0\">Select a category...";
 		while ($row = @mysqli_fetch_array($catresult)) {
			$getname = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='{$row["categoryid"]}'");
			$categoryname = @mysqli_result($getname, 0, "name");
			echo "<option  value={$row["categoryid"]}";
			if ($cat == $row["categoryid"]) echo " selected";
			echo ">$categoryname\n";
		}
		echo "</select>
                </td>
				<td>";
		if (!$cat) echo "&nbsp;";
		else {
			echo "<select name=\"product\" onChange=\"productselection.submit()\"><option value=\"0\">Select a product...";
			while ($row = @mysqli_fetch_array($productsresult)) {
				echo "<option value={$row["productid"]}";
				if ($product == $row["productid"]) echo " selected";
				if (strlen($row["name"]) > 40) $displayname = substr($row["name"],0,37)."...";
				else $displayname = $row["name"];
				echo ">$displayname\n";
			}
			echo "</select>";
		}
		echo "
                </td>
                <td align=\"center\"> 
                  <p> 
                    <input type=\"text\" size=\"2\" name=\"quantity\" value=\"$quantity\"><input type=\"image\" src=\"images/button_calc.gif\" align=\"absmiddle\" alt=\"Recalculate amount\" title=\"Recalculate amount\">
                  </p>
                </td>
                <td align=\"right\"> 
                  <p>".number_format($amount,2,'.','')."</p>
                </td>
                <td align=\"center\" width=\"99\"><input type=\"image\" src=\"images/button_addproduct.gif\" width=\"98\" height=\"24\" name=\"addproduct\" alt=\"Add a product to the estimate below.\" title=\"Add a product to the estimate below.\"><br><input type=\"image\" src=\"images/button_addcredit.gif\" width=\"98\" height=\"24\" name=\"addcredit\" alt=\"Add a credit for this product to the estimate below.\" title=\"Add a credit for this product to the estimate below.\"></td>
              </tr>
              <tr> 
                <td colspan=\"3\"> 
                  <select name=\"comment\" style=\"width: 414px\"><option value=\"select\" selected>Select from list.</option>";
 		while ($row = @mysqli_fetch_array($commentsresult)) echo "<option value=\"{$row["id"]}\">{$row["note"]}</option>\n";
		echo "</select> <span class=\"sm\">[ <a href=\"editnotes.php?edit=";
		if ($edit) echo $edit;
		else if ($quote) echo $quote;
		echo "&customer=$customer\">Edit List</a> ]</span><br>
		<input type=\"text\" size=\"70\" name=\"newcomment\">
                </td>
				<td align=\"right\" valign=\"bottom\" class=\"sm\"><input type=\"text\" size=\"5\" name=\"commentprice\" style=\"text-align: right\" value=\"0.00\"><br>txbl:<input type=\"checkbox\" name=\"taxable\"></td>
                <td width=\"99\" align=\"center\" valign=\"bottom\"><input type=\"image\" name=\"addcomment\" src=\"images/button_addcomment.gif\" width=\"98\" height=\"24\" alt=\"Add a comment row to the estimate below.\" title=\"Add a comment row to the estimate below.\"></td>
              </tr>
			  </form>
            </table><br>";
if ($products) echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\"><tr><td><p><b>Selected Products:</b></p></td></tr></table>";

echo "<table width=\"100%\" class=\"quotetable\" cellpadding=\"1\" cellspacing=\"0\">";

// Show selected products...
unset($productlist);
$subtotal = 0;
$calculateshipping = FALSE;
$productsincart = ashop_parseproductstring($db, $products);
if($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
	$deletestring = substr($products,0,-1);
	$productsarray = explode("a",$deletestring);
	$deletestring = "";
	$deleteproductnumber = 0;
	if($productsarray) foreach($productsarray as $productpart) {
		if ($deleteproductnumber != $productnumber) $deletestring .= $productpart."a";
		$deleteproductnumber++;
	}
	$productid = $thisproduct["productid"];
	$quantity = $thisproduct["quantity"];
	$price = $thisproduct["price"];
	$discounted = $thisproduct["discounted"];
	if ($discounted) {
		// Check if there is a sale...
		$result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid' AND onetime='0' AND (code='' OR code IS NULL)");
		if (@mysqli_num_rows($result2)) {
			$discounttype = @mysqli_result($result2,0,"type");
			$discountvalue = @mysqli_result($result2,0,"value");
			$regprice = $productrow["price"];
			if ($discounttype == "%") $price = $price - ($price * ($discountvalue/100));
			else if ($discounttype == "$") $price -= $discountvalue;
			$thisproduct["price"] = $price;
		}
	}
	$name = $thisproduct["name"];
	$parameters = $thisproduct["parameters"];
	if (strstr($parameters,"unset")) $parameters = "[<a href=\"javascript:setattributes($quantity, $productid, '$deletestring')\">Set Attributes</a>]";
	$thistotal = $thisproduct["price"] * $thisproduct["quantity"];
	$subtotal += $thistotal;
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	$available = @mysqli_result($result,0,"inventory");
	if (!$available) $available = "&nbsp;";
	else $available = "In Stock";
	if (@mysqli_result($result,0,"taxable") == "1") {
		$calculateshipping = TRUE;
		$taxable = "txbl";
	} else $taxable = "&nbsp;";
	if (@mysqli_result($result,0,"shipping")) $calculateshipping = TRUE;
	if ($quantity < 0) {
		$quantity *= -1;
		$style = "smred";
	} else $style = "sm";
	$productlist[] = "<tr><td><p class=\"$style\">$name $parameters</p></td>
	<td width=\"23\" align=\"center\" height=\"15\"><p class=\"$style\">$quantity</p></td>
	<td height=\"15\" width=\"57\" align=\"center\"><p class=\"$style\">$available</p></td>
	<td width=\"32\" height=\"15\" align=\"center\"><p class=\"$style\">$taxable</p></td>
	<td width=\"81\" align=\"right\" height=\"15\"><p class=\"$style\">".number_format($thistotal,2,'.','')."</p></td>
	<td align=\"center\" width=\"46\" height=\"15\">
	<form action=\"quote.php\" method=\"post\" style=\"margin-bottom: 0px;\">
	<input type=\"hidden\" name=\"shop\" value=\"$shop\">
	<input type=\"hidden\" name=\"products\" value=\"$deletestring\">
	<input type=\"hidden\" name=\"customer\" value=\"$customer\">
	<input type=\"hidden\" name=\"comments\" value=\"$comments\">
	<input type=\"hidden\" name=\"commentprices\" value=\"$commentprices\">
	<input type=\"hidden\" name=\"commenttaxable\" value=\"$commenttaxable\">
	<input type=\"hidden\" name=\"itemorder\" value=\"%itemorder%\">
	<input type=\"hidden\" name=\"edit\" value=\"$edit\">
	<input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
	<input type=\"hidden\" name=\"destzip\" value=\"$destzip\">
	<input type=\"hidden\" name=\"deststate\" value=\"$deststate\">
	<input type=\"image\" name=\"deleterow\" src=\"images/button_trash.gif\" width=\"36\" height=\"24\" alt=\"Remove this row.\" title=\"Remove this row.\" border=\"0\">
	</form>
	</td></tr>";
}

// Cancel unwanted shipping calculation...
if (!$attributeset && !$addproduct_x && !$addcomment_x && !$addcredit_x && !$deleterow_x) $calculateshipping = FALSE;

// Get shipping and handling cost...
if($calculateshipping) {
	$querystring = "quote=$products&destfirstname={$shippingrow["shippingfirstname"]}&destlastname={$shippingrow["shippinglastname"]}&destaddress={$shippingrow["shippingaddress"]}&destcity={$shippingrow["shippingcity"]}&destzip={$shippingrow["shippingzip"]}&destcntry={$shippingrow["shippingcountry"]}&deststate={$shippingrow["shippingstate"]}";
	if (strpos($ashopurl, "/", 8)) {
		$urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
		$urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
	} else {
		$urlpath = "/";
		$urldomain = $ashopurl;
	}
	if ($urlpath == "/") $scriptpath = "shipping.php";
	else $scriptpath = "/shipping.php";
	$urldomain = str_replace("http://", "", $urldomain);
	$postheader = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
	$fp = @fsockopen ($urldomain, 80, $errno, $errstr, 10);
	if ($fp) {
		fputs ($fp, $postheader.$querystring);
		unset($res);
		while (!feof($fp)) $res .= fgets ($fp, 1024);
		fclose ($fp);
		$handlingcostarray = explode("\n",$res);
		$handlingcoststring = $handlingcostarray[count($handlingcostarray)-1];
	}
}

// Get the saved handling costs...
if (!$handlingcoststring && $shipping) $handlingcoststring = $shipping;
if ($handlingcoststring) $handlingcosts = ashop_gethandlingcost($handlingcoststring);

// Show selected comments...
unset($commentlist);
unset($commenttax);
$commentsarray = explode("|",$comments);
$commentpricesarray = explode("|",$commentprices);
$commenttaxablearray = explode("|",$commenttaxable);
if ($commentsarray) foreach ($commentsarray as $commentnumber => $thiscommentid) {
	// Create new comments value in case this comment is deleted...
	$deletestring = substr($comments,0,-1);
	$delcommentsarray = explode("|",$deletestring);
	$deletestring = "";
	$deletecommentnumber = 0;
	if($delcommentsarray) foreach($delcommentsarray as $commentpart) {
		if ($deletecommentnumber != $commentnumber) $deletestring .= $commentpart."|";
		$deletecommentnumber++;
	}
	// Create new commentprices value in case this comment is deleted...
	$deletepricesstring = substr($commentprices,0,-1);
	$delcommentpricesarray = explode("|",$deletepricesstring);
	$deletepricesstring = "";
	$deletecommentpricenumber = 0;
	if($delcommentpricesarray) foreach($delcommentpricesarray as $commentpricepart) {
		if ($deletecommentpricenumber != $commentnumber) $deletepricesstring .= $commentpricepart."|";
		$deletecommentpricenumber++;
	}
	// Create new commenttaxable value in case this comment is deleted...
	$deletetaxablestring = substr($commenttaxable,0,-1);
	$deltaxablearray = explode("|",$deletetaxablestring);
	$deletetaxablestring = "";
	$deletetaxablenumber = 0;
	if($deltaxablearray) foreach($deltaxablearray as $taxablepart) {
		if ($deletetaxablenumber != $commentnumber) $deletetaxablestring .= $taxablepart."|";
		$deletetaxablenumber++;
	}
	$result = @mysqli_query($db, "SELECT * FROM emerchant_notes WHERE id='$thiscommentid'");
	$row = @mysqli_fetch_array($result);
	$thisnote = $row["note"];
	if ($thisnote) {
		$addtocommentlist = "<tr><td colspan=\"3\" height=\"25\"><p class=\"sm\">$thisnote</p></td><td width=\"32\" height=\"15\" align=\"center\" class=\"sm\">";
		if ($commenttaxablearray["$commentnumber"]) {
			$addtocommentlist .= "txbl";
			$commenttax += $commentpricesarray["$commentnumber"];
		}
		else $addtocommentlist .= "&nbsp;";
		$addtocommentlist .= "<td width=\"81\" align=\"right\" height=\"15\"><p class=\"sm\">";
		if ($commentpricesarray["$commentnumber"]) $addtocommentlist .= number_format($commentpricesarray["$commentnumber"],2,'.','');
		else $addtocommentlist .= "&nbsp;";
		$addtocommentlist .= "</p></td><td align=\"center\" width=\"46\" height=\"25\">
		<form action=\"quote.php\" method=\"post\" style=\"margin-bottom: 0px;\">
		<input type=\"hidden\" name=\"shop\" value=\"$shop\">
		<input type=\"hidden\" name=\"products\" value=\"$products\">
		<input type=\"hidden\" name=\"customer\" value=\"$customer\">
		<input type=\"hidden\" name=\"comments\" value=\"$deletestring\">
		<input type=\"hidden\" name=\"commenttaxable\" value=\"$deletetaxablestring\">
		<input type=\"hidden\" name=\"commentprices\" value=\"$deletepricesstring\">
		<input type=\"hidden\" name=\"itemorder\" value=\"%itemorder%\">
		<input type=\"hidden\" name=\"edit\" value=\"$edit\">
		<input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
		<input type=\"hidden\" name=\"destzip\" value=\"$destzip\">
		<input type=\"hidden\" name=\"deststate\" value=\"$deststate\">
		<input type=\"image\" name=\"deleterow\" src=\"images/button_trash.gif\" width=\"36\" height=\"24\" alt=\"Remove this row.\" title=\"Remove this row.\" border=\"0\"></form></td></tr>";
		$commentlist[] = $addtocommentlist;
	}
	$subtotal += $commentpricesarray["$commentnumber"];
}

// Output items...
if (is_array($productlist)) reset($productlist);
if (is_array($commentlist)) reset($commentlist);
// Parse itemorder into an array...
unset($itemorderarray);
for ($ch = 0; $ch < strlen($itemorder); $ch++) $itemorderarray[] = substr($itemorder,$ch,1);
for ($ch = 0; $ch < strlen($itemorder); $ch++) {

	// Create new itemorder string in case this item is deleted...
	$deletestring = "";
	$deleteproductnumber = 0;
	if($itemorderarray) foreach($itemorderarray as $itemorderpart) {
		if ($deleteproductnumber != $ch) $deletestring .= $itemorderpart;
		$deleteproductnumber++;
	}

	if (substr($itemorder,$ch,1) == "p" && is_array($productlist)) {
		echo str_replace("%itemorder%","$deletestring",current($productlist));
		next($productlist);
	} else if (substr($itemorder,$ch,1) == "c" && is_array($commentlist)) {
		echo str_replace("%itemorder%","$deletestring",current($commentlist));
		next($commentlist);
	}
}

// Calculate misc items tax and add to shippingstring...
if ($addproduct_x || $addcomment_x || $addcredit_x || $deleterow_x) {
	$emerchanttax = emerchant_tax($commenttax,$shippingrow["shippingzip"],$shippingrow["shippingcountry"],$shippingrow["shippingstate"],$shippingrow["vat"]);
	$handlingcosts["salestax"] += $emerchanttax;
}

// Calculate total cost...
$totalcost = $subtotal + $handlingcosts["salestax"] + $handlingcosts["shipping"];
$handlingcosts["shipping"] = number_format($handlingcosts["shipping"],2,'.','');
$handlingcosts["salestax"] = number_format($handlingcosts["salestax"],2,'.','');
$handlingcostsstring = "shb".$handlingcosts["shipping"]."astb".$handlingcosts["salestax"]."a";

echo "</table>
	<form action=\"quote.php\" name=\"quoteform\" method=\"post\" style=\"margin-bottom: 0px;\">
	<input type=\"hidden\" name=\"shop\" value=\"$shop\">
	<input type=\"hidden\" name=\"products\" value=\"$products\">
	<input type=\"hidden\" name=\"customer\" value=\"$customer\">
	<input type=\"hidden\" name=\"comments\" value=\"$comments\">
	<input type=\"hidden\" name=\"commentprices\" value=\"$commentprices\">
	<input type=\"hidden\" name=\"commenttaxable\" value=\"$commenttaxable\">
	<input type=\"hidden\" name=\"itemorder\" value=\"$itemorder\">
	<input type=\"hidden\" name=\"edit\" value=\"$edit\">
	<input type=\"hidden\" name=\"quickquote\" value=\"$quickquote\">
	<input type=\"hidden\" name=\"destzip\" value=\"$destzip\">
	<input type=\"hidden\" name=\"deststate\" value=\"$deststate\">
	<input type=\"hidden\" name=\"shipping\" value=\"$handlingcostsstring\">
	<input type=\"hidden\" name=\"quote\" value=\"$quote\">
	<input type=\"hidden\" name=\"save\" value=\"true\">
    <table width=\"100%\" border=\"0\" cellpadding=\"0\">
              <tr> 
                <td align=\"center\" valign=\"middle\"> 
                  <table width=\"100%\" border=\"0\" cellpadding=\"0\">
                    <tr> 
                      <td colspan=\"2\" align=\"center\" class=\"heading3\"> Process</td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>Subtotal:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p>".number_format($subtotal,2,'.','')."</p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>
                    <tr> 
                      <td width=\"103\" align=\"center\"><b>
					  <input type=\"image\" src=\"images/button_save.gif\" width=\"64\" height=\"24\" alt=\"Save this quote\" title=\"Save this quote\" border=\"0\"></b></td><td width=\"103\" align=\"center\"><b><input type=\"image\" src=\"images/button_send.gif\" name=\"send\" width=\"64\" height=\"24\" alt=\"Send quote in e-mail.\" title=\"Send quote in e-mail.\" border=\"0\"></b></td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>State Sales Tax:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p>".number_format($handlingcosts["salestax"],2,'.','')."</p>
                      </td>
					  <td width=\"20\"><p>[<a href=\"javascript:edittax('{$handlingcosts["shipping"]}', '{$handlingcosts["salestax"]}')\">Edit</a>]</p></td>
                    </tr>
                    <tr> 
                      <td width=\"103\" align=\"center\"><b><input type=\"image\" src=\"images/button_bill.gif\" name=\"bill\" width=\"64\" height=\"24\" border=\"0\" alt=\"Send this quote as a payable bill.\" title=\"Send this quote as a payable bill.\"></a></b></td>
                      <td width=\"103\" align=\"center\"><b><input type=\"image\" src=\"images/button_print.gif\" name=\"print\" width=\"64\" height=\"24\" alt=\"View printable quote.\" title=\"View printable quote.\" border=\"0\"></b></td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>Shipping:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p>".number_format($handlingcosts["shipping"],2,'.','')."</p></td>
					  <td width=\"20\"><p>[<a href=\"javascript:editshipping('{$handlingcosts["shipping"]}', '{$handlingcosts["salestax"]}')\">Edit</a>]</p></td>
                    </tr>
                    <tr> 
                      <td width=\"103\" align=\"center\"><a href=\"quote.php?customer=$customer&quote=$quote&destzip=$destzip&deststate=$deststate&quickquote=$quickquote\"><img src=\"images/button_clear.gif\" width=\"64\" height=\"24\" border=\"0\" alt=\"Clear all items.\" title=\"Clear all items.\"></a></td>
                      <td width=\"103\" align=\"center\"><b>";
if (!$quickquote) echo "<input type=\"image\" src=\"images/button_order.gif\" name=\"order\" width=\"64\" height=\"24\" border=\"0\" alt=\"Apply payment and convert this quote to an invoice.\" title=\"Apply payment and convert this quote to an invoice.\"></b>";
else echo "&nbsp;";
echo "</td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>Quote Total:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p><b>".number_format($totalcost,2,'.','')."</b></p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>";
if ($orderreference) echo "<tr> 
                      <td width=\"208\" align=\"center\" colspan=\"2\" height=\"2\">&nbsp;</td>
                      <td width=\"300\" align=\"right\" height=\"2\"> 
                        <p><b>Order Reference:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\" height=\"2\"> 
                        <p>$orderreference</p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>";
echo "
                  </table>
                </td>
              </tr>
            </table></form>
      </td>
        </tr>
      </table>
      </td>
  </tr>
</table>
$footer";
?>