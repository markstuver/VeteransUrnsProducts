<?php
include "../admin/ashopconstants.inc.php";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
$pagetitle = "Purchase Order";
include "template.inc.php";
// Get context help for this page...
$contexthelppage = "purchaseorder";
include "emhelp.inc.php";

if ($openorders_x) {
	header("Location: openorders.php?vendor=$vendor");
	exit;
}

if ($unshipped_x) {
	header("Location: polist.php?vendor=$vendor&report=unshipped");
	exit;
}

if ($shipped_x) {
	header("Location: polist.php?vendor=$vendor&report=shipped");
	exit;
}

if ($closed_x) {
	header("Location: polist.php?vendor=$vendor&report=closed");
	exit;
}

if ($vendor == "all") {
	if ($newpurchaseorder_x) {
		echo $header;
		emerchant_sidebar();
		echo "<td valign=\"top\">";
		emerchant_topbar("Purchase Order");
		echo "<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
		<tr><td><div align=\"center\" class=\"heading3\"><br>Error! Please select a vendor!</td></tr></table></td></tr></table>";
	} else header("Location: orderstatus.php?vendor=$vendor&order=$order");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$edit && $order && $vendor) {
	$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE vendorid='$vendor' AND orderid='$order'");
	if (@mysqli_num_rows($result)) {
		$edit = @mysqli_result($result, 0, "purchaseorderid");
		$voided = @mysqli_result($result, 0, "closed");
	}
}

$shipping = $nshipping;

// Get po information for editing...
if ($edit) {
	if (!isset($products) && !$comments) {
		if (strstr("$edit","em")) {
			$edit = str_replace("em","",$edit);
			$edit = number_format($edit,0);
		}
		$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE purchaseorderid='$edit'");
		$row = @mysqli_fetch_array($result);
		$products = $row["products"];
		$comments = $row["comments"];
		$customer = $row["customerid"];
		$modifieddate = $row["date"];
		$vendor = $row["vendorid"];
		$order = $row["orderid"];
		$commentprices = $row["commentprices"];
		$cost = $row["cost"];
		if (!isset($nshipping)) $shipping = $row["shipping"];
		$itemorder = $row["itemorder"];
		$billdate = $row["billdate"];
		$shipdate = $row["shipdate"];
		$sent = $row["sent"];
	}
	$po = $edit;
}

if (!$vendor) {
	echo $header;
	emerchant_sidebar();
	echo "<td valign=\"top\">";
	emerchant_topbar("Purchase Order");
	echo "<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
	<tr><td><div align=\"center\" class=\"heading3\"><br>";
	if ($edit) echo "There is no purchase order with that number!";
	else echo "Error! No vendor selected!";
	echo "</td></tr></table></td></tr></table>";
	exit;
}

if (!$modifieddate) $modifieddate = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Save purchase order...
if ($save == "true" && $vendor) {
	if ($nshipping) $shipping = $nshipping;
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

	// Remove PO from open orders...
	$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$orderid'");
	$vendorlist = @mysqli_result($result, 0, "vendors");
	$vendors = explode("||",$vendors);
	$newvendorlist = "";
	if (is_array($vendors)) foreach($vendors as $thisvendorid) {
		$thisvendorid = str_replace("|","",$thisvendorid);
		if ($thisvendorid != $vendor) $newvendorlist .= "|$thisvendorid|";
	}
	@mysqli_query($db, "UPDATE orders SET vendors='$newvendorilist' WHERE orderid='$orderid'");

	if ($po) @mysqli_query($db, "UPDATE emerchant_purchaseorder SET vendorid='$vendor', orderid='$order', customerid='$customer', date='$modifieddate', products='$products', productprices='$productprices', comments='$comments', commentprices='$commentprices', itemorder='$itemorder', cost='$cost', shipping='$shipping' WHERE purchaseorderid='$po'");
	else $result = @mysqli_query($db, "INSERT INTO emerchant_purchaseorder (user, vendorid, orderid, customerid, date, products, productprices, comments, commentprices, itemorder, cost, shipping) VALUES ('$emerchant_user', '$vendor', '$order', '$customer', '$modifieddate', '$products', '$productprices', '$comments', '$commentprices', '$itemorder', '$cost', '$shipping')");
	if (@mysqli_affected_rows() == 1) {
		if (!$po) $po = @mysqli_insert_id($db);
		$saved = "true";
		$result = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE purchaseorderid='$po'");
		$row = @mysqli_fetch_array($result);
		$billdate = $row["billdate"];
		$shipdate = $row["shipdate"];
		$sent = $row["sent"];
	}
	if ($send_x) header("Location: emailpo.php?po=$po");
	if ($close == "true") header("Location: editpostatus.php?close=$po");
}

// Get customer information from database...
$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$order'");
$orderrow = @mysqli_fetch_array($result);
$wholesale = $orderrow["wholesale"];
if (!$customer) $customer = $orderrow["customerid"];
if (!isset($nshipping) && !isset($shipping)) $shipping = $orderrow["shipping"];
$orderreference = $orderrow["orderid"];
if (!isset($products)) {
	$products = emerchant_vendorproductstring($db, $orderrow["products"], $vendor);
	if ($products && !$itemorder) {
		if (substr($products, -1) == "a") $productsarray = explode("a",substr($products,0,-1));
		else $productsarray = explode("a",$products);
		$numberofproducts = count($productsarray);
		for ($i = 0; $i < $numberofproducts; $i++) $itemorder .= "p";
	}
}
if ($customer) $result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customer'");
$customerrow = @mysqli_fetch_array($result);
if ($customer) $result = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customer'");
if (@mysqli_num_rows($result)) $shippingrow = @mysqli_fetch_array($result);
else if (is_array($customerrow)) {
	@mysqli_query($db, "INSERT INTO shipping (shippingfirstname, shippinglastname, shippingaddress, shippingzip, shippingcity, shippingstate, shippingcountry, customerid) VALUES ('{$customerrow["firstname"]}', '{$customerrow["lastname"]}', '{$customerrow["address"]}', '{$customerrow["zip"]}', '{$customerrow["city"]}', '{$customerrow["state"]}', '{$customerrow["country"]}', '{$customerrow["customerid"]}')");
	$shippingrow["shippingfirstname"] = $customerrow["firstname"];
	$shippingrow["shippinglastname"] = $customerrow["lastname"];
	$shippingrow["shippingaddress"] = $customerrow["address"];
	$shippingrow["shippingcity"] = $customerrow["city"];
	$shippingrow["shippingstate"] = $customerrow["state"];
	$shippingrow["shippingzip"] = $customerrow["zip"];
	$shippingrow["shippingcountry"] = $customerrow["country"];
}

$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$vendor'");
$vendorrow = @mysqli_fetch_array($result);

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
	if (!empty($shop)) $productsresult = @mysqli_query($db, "SELECT * FROM product, productcategory WHERE productcategory.categoryid='$cat' AND product.productid=productcategory.productid AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.vendorid='$vendor' AND userid='$shop' ORDER BY product.ordernumber");
	else $productsresult = @mysqli_query($db, "SELECT * FROM product, productcategory WHERE productcategory.categoryid='$cat' AND product.productid=productcategory.productid AND (product.prodtype!='content' OR product.prodtype IS NULL) AND product.vendorid='$vendor' ORDER BY product.ordernumber");
}

if (!$product) 	@mysqli_data_seek($productsresult,0);

if (!$quantity) $quantity = 1;
$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$product'", $db);
$productrow = @mysqli_fetch_array($result);
$amount = $quantity * $productrow["cost"];
if ($addproduct_x) {
	$products .= "{$quantity}b{$product}a";
	$itemorder .= "p";
	$quantity = 1;
}

// Add and store new comments...
if ($addcomment_x && (($comment && $comment != "select") || $newcomment)) {
	if ($newcomment) {
		unset($comment);
		@mysqli_query($db, "INSERT INTO emerchant_vendornotes (note) VALUES ('$newcomment')");
		$comment = @mysqli_insert_id($db);
	}
	$comments .= "{$comment}|";
	if ($commentprice == "0.00") $commentprice = "0";
	$commentprices .= "{$commentprice}|";
	$itemorder .= "c";
	if ($taxable) $commenttaxable .= "1|";
	else $commenttaxable .= "0|";
}
$commentsresult = @mysqli_query($db, "SELECT * FROM emerchant_vendornotes WHERE reusable='1'");

echo $header;
emerchant_sidebar();
echo "<td valign=\"top\">";
emerchant_topbar("Purchase Order");

echo "
<script language=\"JavaScript\">
function setattributes(quantity, itemno, products)
{
	window.open(\"setattributes.php?quantity=\"+quantity+\"&item=\"+itemno+\"&products=\"+products,\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=400, height=400\");
}";

if ($print_x) echo "window.open(\"printpo.php?po=$po\",\"_blank\",\"toolbar=yes, location=no, scrollbars=yes, width=700, height=600\");";
echo "</script>";

echo "<br><font color=\"#000099\"><div align=\"center\" ID=\"notice\" class=\"heading3\">$notice</div></font>
<table width=\"650\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
        <tr>
          <td>
            <div align=\"center\" class=\"heading3\"><br>";
if (!$edit) {
	echo "<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> Create a ";
	if ($wholesale) echo "wholesale ";
	echo "purchase order...";
} else echo "<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over2.gif',1)\"><img src=\"images/contexthelpicon2.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> Edit an existing purchase order...";
echo "
<script language=\"JavaScript\">
function getShipping() {
	for (i = 1; i < document.forms.length-1; i++) {
		document.forms[i].nshipping.value = document.form1.nshipping.value;
	}
	return true;
}
</script>

<table width=\"650\" border=\"0\" cellpadding=\"0\">
                      <tr>
						<td width=\"20%\" align=\"left\" class=\"sm\">"; if ($vendorrow) echo "<a href=\"vendor.php?id=$vendor\"><img src=\"images/icon_profile.gif\" width=\"15\" height=\"15\" alt=\"Edit vendor information for ".$vendorrow["name"].".\" border=\"0\"></a> <a href=\"vendorhistory.php?vendor=$vendor\"><img src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$vendorrow["name"].".\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('vendornote.php?vendor=$vendor')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this vendor.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?vendor=$vendor')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a>"; else echo "&nbsp;"; echo "</td>
                        <td width=\"30%\" class=\"sm\" valign=\"bottom\">"; if ($po) echo "Purchase Order Number: $po"; else echo "&nbsp;"; echo "</td>
                        <td width=\"30%\" class=\"sm\" valign=\"bottom\">"; if ($po) echo "PO Date: $modifieddate"; else echo "&nbsp;"; echo "</td>
                        <td width=\"20%\" align=\"right\" class=\"sm\">"; if ($customerrow) echo "<a href=\"customer.php?id=$customer\"><img src=\"images/icon_profile.gif\" width=\"15\" height=\"15\" alt=\"Edit customer information for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a> <a href=\"history.php?customer=$customer\"><img src=\"images/icon_history.gif\" width=\"15\" height=\"15\" alt=\"View history for ".$customerrow["firstname"]." ".$customerrow["lastname"].".\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('customernote.php?customer=$customer')\"><img src=\"images/icon_customernote.gif\" width=\"15\" height=\"15\" alt=\"Create a note regarding this customer.\" border=\"0\"></a>&nbsp;<a href=\"javascript:newWindow('composemessage.php?customer=$customer')\"><img src=\"images/icon_mail.gif\" alt=\"Send mail.\" border=\"0\"></a>"; else echo "&nbsp;"; echo "</td>
                      </tr>
                    </table>
</div><form action=\"purchaseorder.php\" method=\"post\" name=\"productselection\" style=\"margin-top: 0px;\"><table width=\"100%\" border=\"1\" bordercolor=\"#CCCCCC\" cellpadding=\"1\" cellspacing=\"0\">
			    <tr valign=\"top\"> 
				  <td width=\"50%\"><p><b>Vendor:</b><br>
					<span class=\"sm\">{$vendorrow["name"]}<br>
					{$vendorrow["address"]}<br>
					{$vendorrow["city"]}, {$vendorrow["state"]}, {$vendorrow["zip"]}<br>
					eMail: {$vendorrow["email"]}<br>
					Phone: {$vendorrow["phone"]}</span></p>
				  </td>
				  <td width=\"50%\"> 
				    <p><b>Ship To:</b><br>
					<span class=\"sm\">";
if (is_array($shippingrow)) {
	if ($shippingrow["shippingbusiness"]) echo "{$shippingrow["shippingbusiness"]}<br>";
	echo "{$shippingrow["shippingfirstname"]} {$shippingrow["shippinglastname"]}<br>
					{$shippingrow["shippingaddress"]}<br>";
} else echo "Unknown<br>Customer ID: <input type=\"text\" name=\"customer\" size=\"5\"> <input type=\"image\" src=\"images/button_set.gif\" name=\"selectcustomer\" align=\"top\">";					
if ($shippingrow["shippingaddress2"]) echo "{$shippingrow["shippingaddress2"]}<br>";
if ($shippingrow["shippingcity"]) echo "{$shippingrow["shippingcity"]}, {$shippingrow["shippingstate"]},
					{$shippingrow["shippingzip"]}<br>
					{$shippingrow["shippingcountry"]}<br>";
if ($customerrow["phone"]) echo "Phone: {$customerrow["phone"]}</span></p>";
echo "</td>
				</tr>
              </table>";
if (!$voided) {
            echo "<table width=\"100%\" border=\"1\" bordercolor=\"#CCCCCC\" height=\"109\" cellpadding=\"1\" cellspacing=\"0\">
			<input type=\"hidden\" name=\"edit\" value=\"$edit\">
			<input type=\"hidden\" name=\"vendor\" value=\"$vendor\">
			<input type=\"hidden\" name=\"products\" value=\"$products\">";
if (is_array($shippingrow)) echo "<input type=\"hidden\" name=\"customer\" value=\"$customer\">";
echo "<input type=\"hidden\" name=\"order\" value=\"$order\">
			<input type=\"hidden\" name=\"comments\" value=\"$comments\">
			<input type=\"hidden\" name=\"commentprices\" value=\"$commentprices\">
			<input type=\"hidden\" name=\"itemorder\" value=\"$itemorder\">
			<input type=\"hidden\" name=\"nshipping\" value=\"$shipping\">
			<input type=\"hidden\" name=\"po\" value=\"$po\">
			<input type=\"hidden\" name=\"attributeset\" value=\"\">
              <tr> 
                <td colspan=\"7\" height=\"6\" bgcolor=\"#CCCCCC\"><font size=\"2\"><span class=\"sm\">Edit this PO by adding or deleting one row at a time.</span></font></td>
              </tr>
              <tr> 
                <td width=\"150\"> 
                  <p><b>Category</b></p>
                </td>
                <td width=\"300\"> 
                  <p><b>Product</b></p>
                </td>
                <td width=\"25\" align=\"center\"> 
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
				if (strlen($row["name"]) > 50) $displayname = substr($row["name"],0,47)."...";
				else $displayname = $row["name"];
				echo ">$displayname\n";
			}
			echo "</select>";
		}
		echo "
                </td>
                <td align=\"center\"> 
                  <p> 
                    <input type=\"text\" size=\"3\" name=\"quantity\" value=\"$quantity\"><input type=\"image\" src=\"images/button_calc.gif\" align=\"absmiddle\" alt=\"Recalculate amount\">
                  </p>
                </td>
                <td align=\"right\"> 
                  <p>".number_format($amount,2,'.','')."</p>
                </td>
                <td align=\"center\" width=\"99\"> <input type=\"image\" src=\"images/button_addproduct.gif\" width=\"98\" height=\"24\" name=\"addproduct\" alt=\"Add a product to the estimate below.\"></td>
              </tr>
              <tr> 
                <td colspan=\"5\" height=\"6\" bgcolor=\"#CCCCCC\"><font size=\"2\"><span class=\"sm\">Add comments or miscellaneous items...</span></font></td>
              </tr>
              <tr> 
                <td colspan=\"3\"> 
                  <select name=\"comment\" style=\"width: 420px\"><option value=\"select\" selected>Select from list.</option>";
 		while ($row = @mysqli_fetch_array($commentsresult)) echo "<option value=\"{$row["id"]}\">{$row["note"]}</option>\n";
		echo "</select> <span class=\"sm\">[ <a href=\"editvendornotes.php?vendor=$vendor&order=$order&edit=$edit\">Edit List</a> ]</span><br>
		<input type=\"text\" size=\"67\" name=\"newcomment\">
                </td>
				<td align=\"right\" valign=\"bottom\" class=\"sm\"><b>Amount</b><br><input type=\"text\" size=\"5\" name=\"commentprice\" style=\"text-align: right\" value=\"0.00\"></td>
                <td width=\"99\" align=\"center\" valign=\"bottom\"><input type=\"image\" name=\"addcomment\" src=\"images/button_addcomment.gif\" width=\"98\" height=\"24\" alt=\"Add a comment row to the estimate below.\"></td>
              </tr>
			  </form>
            </table><br>";
}
echo "<table width=\"100%\" border=\"0\" cellpadding=\"0\"><tr><td><p><b>Products:</b></p></td></tr></table>

<table width=\"100%\" border=\"1\" bordercolor=\"#CCCCCC\" cellpadding=\"1\" cellspacing=\"0\">";

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
	$name = $thisproduct["name"];
	$parameters = $thisproduct["parameters"];
	if (strstr($parameters,"unset")) $parameters = "[<a href=\"javascript:setattributes($quantity, $productid, '$deletestring')\">Set Attributes</a>]";
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	$thiscost = @mysqli_result($result,0,"cost");
	$thistotal = $thiscost * $thisproduct["quantity"];
	$subtotal += $thistotal;
	$available = @mysqli_result($result,0,"inventory");
	if (@mysqli_result($result,0,"shipping")) $calculateshipping = TRUE;
	$productlist[] = "<tr><td><p class=\"sm\">$name $parameters</p></td>
	<td width=\"23\" align=\"center\" height=\"15\"><p class=\"sm\">$quantity</p></td>
	<td height=\"15\" width=\"57\" align=\"center\"><p class=\"sm\">$available</p></td>
	<td width=\"81\" align=\"right\" height=\"15\"><p class=\"sm\">".number_format($thistotal,2,'.','')."</p></td>
	<td align=\"center\" width=\"46\" height=\"15\">
	<form action=\"purchaseorder.php\" method=\"post\" onSubmit=\"return getShipping()\" style=\"margin-bottom: 0px;\">
	<input type=\"hidden\" name=\"products\" value=\"$deletestring\">
	<input type=\"hidden\" name=\"customer\" value=\"$customer\">
	<input type=\"hidden\" name=\"vendor\" value=\"$vendor\">
	<input type=\"hidden\" name=\"order\" value=\"$order\">
	<input type=\"hidden\" name=\"po\" value=\"$po\">
	<input type=\"hidden\" name=\"comments\" value=\"$comments\">
	<input type=\"hidden\" name=\"commentprices\" value=\"$commentprices\">
	<input type=\"hidden\" name=\"commenttaxable\" value=\"$commenttaxable\">
	<input type=\"hidden\" name=\"itemorder\" value=\"%itemorder%\">
	<input type=\"hidden\" name=\"edit\" value=\"$edit\">
	<input type=\"hidden\" name=\"nshipping\" value=\"$shipping\">
	<input type=\"image\" name=\"deleterow\" src=\"images/button_trash.gif\" width=\"36\" height=\"24\" alt=\"Remove this row.\" border=\"0\">
	</form>
	</td></tr>";
}

// Get shipping and handling cost...
if($calculateshipping && ($addproduct_x || $deleterow_x)) {
	$querystring = "quote=$products&destbusiness={$shippingrow["shippingbusiness"]}&destfirstname={$shippingrow["shippingfirstname"]}&destlastname={$shippingrow["shippinglastname"]}&destaddress={$shippingrow["shippingaddress"]}&destcity={$shippingrow["shippingcity"]}&destzip={$shippingrow["shippingzip"]}&destcountry={$shippingrow["shippingcountry"]}&deststate={$shippingrow["shippingstate"]}";
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
	if ($handlingcoststring) $handlingcosts = ashop_gethandlingcost($handlingcoststring);
}

// Show selected comments...
unset($commentlist);
unset($commenttax);
$commentsarray = explode("|",$comments);
$commentpricesarray = explode("|",$commentprices);
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
	$result = @mysqli_query($db, "SELECT * FROM emerchant_vendornotes WHERE id='$thiscommentid'");
	$row = @mysqli_fetch_array($result);
	$thisnote = $row["note"];
	if ($thisnote) {
		$addtocommentlist = "<tr><td colspan=\"2\" height=\"25\"><p class=\"sm\">$thisnote</p></td><td width=\"32\" height=\"15\" align=\"center\" class=\"sm\">";
		if ($commenttaxablearray["$commentnumber"]) {
			$addtocommentlist .= "txbl";
			$commenttax += $commentpricesarray["$commentnumber"];
		}
		else $addtocommentlist .= "&nbsp;";
		$addtocommentlist .= "<td width=\"81\" align=\"right\" height=\"15\"><p class=\"sm\">";
		if ($commentpricesarray["$commentnumber"]) $addtocommentlist .= number_format($commentpricesarray["$commentnumber"],2,'.','');
		else $addtocommentlist .= "&nbsp;";
		$addtocommentlist .= "</p></td><td align=\"center\" width=\"46\" height=\"25\">
		<form action=\"purchaseorder.php\" method=\"post\" onSubmit=\"return getShipping()\" style=\"margin-bottom: 0px;\">
		<input type=\"hidden\" name=\"vendor\" value=\"$vendor\">
		<input type=\"hidden\" name=\"products\" value=\"$products\">
		<input type=\"hidden\" name=\"customer\" value=\"$customer\">
		<input type=\"hidden\" name=\"order\" value=\"$order\">
		<input type=\"hidden\" name=\"comments\" value=\"$deletestring\">
		<input type=\"hidden\" name=\"commentprices\" value=\"$deletepricesstring\">
		<input type=\"hidden\" name=\"itemorder\" value=\"%itemorder%\">
		<input type=\"hidden\" name=\"edit\" value=\"$edit\">
		<input type=\"hidden\" name=\"po\" value=\"$po\">
		<input type=\"hidden\" name=\"nshipping\" value=\"$shipping\">
		<input type=\"image\" name=\"deleterow\" src=\"images/button_trash.gif\" width=\"36\" height=\"24\" alt=\"Remove this row.\" border=\"0\"></form></td></tr>";
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

	if (substr($itemorder,$ch,1) == "p") {
		echo str_replace("%itemorder%","$deletestring",current($productlist));
		next($productlist);
	} else if (substr($itemorder,$ch,1) == "c") {
		echo str_replace("%itemorder%","$deletestring",current($commentlist));
		next($commentlist);
	}
}

// Calculate total cost...
if ($addproduct_x || $deleterow_x) $shipping = $handlingcosts["shipping"];
else if (isset($nshipping)) $shipping = $nshipping;
$totalcost = $subtotal + $shipping;

echo "</table>
	<form action=\"purchaseorder.php\" name=\"form1\" method=\"post\" style=\"margin-bottom: 0px;\">
	<input type=\"hidden\" name=\"vendor\" value=\"$vendor\">
	<input type=\"hidden\" name=\"products\" value=\"$products\">
	<input type=\"hidden\" name=\"order\" value=\"$order\">
	<input type=\"hidden\" name=\"customer\" value=\"$customer\">
	<input type=\"hidden\" name=\"comments\" value=\"$comments\">
	<input type=\"hidden\" name=\"commentprices\" value=\"$commentprices\">
	<input type=\"hidden\" name=\"itemorder\" value=\"$itemorder\">
	<input type=\"hidden\" name=\"edit\" value=\"$edit\">
	<input type=\"hidden\" name=\"po\" value=\"$po\">
	<input type=\"hidden\" name=\"save\" value=\"true\">
	<input type=\"hidden\" name=\"cost\" value=\"".number_format($subtotal,2,'.','')."\">
    <table width=\"100%\" border=\"0\" cellpadding=\"0\">
              <tr> 
                <td align=\"center\" valign=\"middle\"> 
                  <table width=\"100%\" border=\"0\" cellpadding=\"0\">
                    <tr> 
                      <td colspan=\"2\" align=\"center\" class=\"heading3\">"; if (!$voided) echo " Process"; else echo "&nbsp;"; echo "</td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>Subtotal:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p>".number_format($subtotal,2,'.','')."</p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>
                    <tr> 
                      <td width=\"103\" align=\"center\"><b>";
					  if (!$voided) echo "<input type=\"image\" src=\"images/button_save.gif\" width=\"64\" height=\"24\" alt=\"Save this purchase order\" border=\"0\">"; else echo "&nbsp;"; echo "</b></td><td width=\"103\" align=\"center\"><b>"; if (!$voided) echo "<input type=\"image\" src=\"images/button_send.gif\" name=\"send\" width=\"64\" height=\"24\" alt=\"Send purchase order in e-mail.\" border=\"0\">"; else echo "&nbsp;"; echo "</b></td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>Shipping:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p>";
						if (!$voided) {
							if (!$shipping) $shipping = 0.00;
							echo "<input type=\"text\" name=\"nshipping\" size=\"5\" value=\"".number_format($shipping,2,'.','')."\"";
							if (is_array($handlingcosts)) echo " style=\"color: #FF0000\"";
							echo ">";
						} else echo number_format($shipping,2,'.','');
						echo "</p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>
                    <tr> 
                      <td width=\"103\" align=\"center\"><b>"; if (!$voided) echo "<a href=\"purchaseorder.php?vendor=$vendor&order=$order\"><img src=\"images/button_clear.gif\" width=\"64\" height=\"24\" border=\"0\" alt=\"Clear all items.\"></a>"; else echo "&nbsp;"; echo "</b></td>
                      <td width=\"103\" align=\"center\"><b>"; if (!$voided) echo "<input type=\"image\" src=\"images/button_print.gif\" name=\"print\" width=\"64\" height=\"24\" alt=\"View printable purchase order.\" border=\"0\">"; else echo "&nbsp;"; echo "</b></td>
                      <td width=\"300\" align=\"right\"> 
                        <p><b>Total:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\"> 
                        <p>".number_format($totalcost,2,'.','')."</p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>
					<tr>
					  <td width=\"206\" colspan=\"2\">&nbsp;</td>
					  <td width=\"300\" align=\"right\" height=\"2\"> 
                        <p><b>Order Reference:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\" height=\"2\"> 
                        <p>$orderreference";
if ($po && !$voided) echo " [<a href=\"editpostatus.php?close=$po\">Void</a>]";
echo "</p>
                      </td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>
					<tr> 
                      <td width=\"208\" align=\"center\" colspan=\"2\" height=\"2\">&nbsp;</td>
                      <td width=\"300\" align=\"right\" height=\"2\"> 
                        <p><b>Status:</b></p>
                      </td>
                      <td width=\"113\" align=\"right\" height=\"2\"><p>";
if ($voided) echo "Voided";
else if ($po && !$shipdate && !$sent && !$billdate) echo "New";
else if ($po && !$shipdate && $sent && !$billdate) echo "Sent";
else if ($po && $shipdate && !$billdate) echo "Shipped";
else if ($po && $billdate) echo "Closed";
else echo "Unsaved";
if (!$voided) echo " [<a href=\"editpostatus.php?po=$po\">Edit</a>]";
echo "</p></td>
					  <td width=\"20\">&nbsp;</td>
                    </tr>
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