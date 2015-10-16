<?php
include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "emfunc.inc.php";
$pagetitle = "Modify Shipping or Sales Tax";
include "template.inc.php";
  
// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($_POST["shipping"] || $_POST["tax"]) {
	if (!$modifieddate) $modifieddate = date("Y-m-d H:i:s", time()+$timezoneoffset);
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

	$shippingstring = "shb{$shipping}astb{$tax}a";

	if ($quote) @mysqli_query($db, "UPDATE emerchant_quotes SET customerid='$customer', date='$modifieddate', products='$products', comments='$comments', commentprices='$commentprices', commenttaxable='$commenttaxable', itemorder='$itemorder', shipping='$shippingstring', productprices='$productprices', qqzip='$destzip', qqstate='$deststate' WHERE id='$quote'");
	else $result = @mysqli_query($db, "INSERT INTO emerchant_quotes (customerid, date, products, comments, commentprices, commenttaxable, itemorder, shipping, productprices, qqzip, qqstate) VALUES ('$customer', '$modifieddate', '$products', '$comments', '$commentprices', '$commenttaxable', '$itemorder', '$shippingstring', '$productprices', '$destzip', '$deststate')");
	if (@mysqli_affected_rows() == 1) {
		if (!$quote) $quote = @mysqli_insert_id($db);
		$saved = "true";
	}
	echo "<html><head>\n<script language=\"JavaScript\">
	opener.window.location.href='quote.php?edit=$quote';
	this.close();
	</script>\n</head></html>";
	@mysqli_close($db);
	exit;
}

    echo "
	<html>
	<head><title>";
	if ($edit == "shipping") echo "Modify Shipping";
	else if ($edit == "tax") echo "Modify Sales Tax";
	echo "</title>
	</head>
	<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\">
	<center>
	<br>
	<form action=\"editshipping.php\" method=\"post\">
	<script language=\"javascript\">
	   document.write('<input type=\"hidden\" name=\"products\" value=\"'+opener.document.quoteform.products.value+'\">');
	   document.write('<input type=\"hidden\" name=\"customer\" value=\"'+opener.document.quoteform.customer.value+'\">');
	   document.write('<input type=\"hidden\" name=\"comments\" value=\"'+opener.document.quoteform.comments.value+'\">');
	   document.write('<input type=\"hidden\" name=\"commentprices\" value=\"'+opener.document.quoteform.commentprices.value+'\">');
	   document.write('<input type=\"hidden\" name=\"commenttaxable\" value=\"'+opener.document.quoteform.commenttaxable.value+'\">');
	   document.write('<input type=\"hidden\" name=\"itemorder\" value=\"'+opener.document.quoteform.itemorder.value+'\">');
	   document.write('<input type=\"hidden\" name=\"edit\" value=\"'+opener.document.quoteform.edit.value+'\">');
	   document.write('<input type=\"hidden\" name=\"quickquote\" value=\"'+opener.document.quoteform.quickquote.value+'\">');
	   document.write('<input type=\"hidden\" name=\"destzip\" value=\"'+opener.document.quoteform.destzip.value+'\">');
	   document.write('<input type=\"hidden\" name=\"deststate\" value=\"'+opener.document.quoteform.deststate.value+'\">');
	   document.write('<input type=\"hidden\" name=\"shipping\" value=\"'+opener.document.quoteform.shipping.value+'\">');
	   document.write('<input type=\"hidden\" name=\"quote\" value=\"'+opener.document.quoteform.quote.value+'\">');
	   document.write('<input type=\"hidden\" name=\"save\" value=\"'+opener.document.quoteform.save.value+'\">');	
	</script>
    <center><font face=\"$font\" size=\"2\">";
	if ($edit == "shipping") echo "Shipping: <input type=\"text\" name=\"shipping\" size=\"5\" value=\"".number_format($shipping,2,'.','')."\"><input type=\"hidden\" name=\"tax\" value=\"".number_format($tax,2,'.','')."\">";
	else if ($edit == "tax") echo "Sales tax: <input type=\"text\" name=\"tax\" size=\"5\" value=\"".number_format($tax,2,'.','')."\"><input type=\"hidden\" name=\"shipping\" value=\"".number_format($shipping,2,'.','')."\">";
	echo "<input type=\"submit\" value=\"Submit\">
	<input type=\"button\" value=\"Cancel\" onClick=\"window.close()\"></center>
	</form>
	</font></center>
	</body>
	</html>";
@mysqli_close($db);
?>