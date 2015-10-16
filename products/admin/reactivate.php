<?php
// AShop
// Copyright 2014 - AShop Software - http://www.ashopsoftware.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, see: http://www.gnu.org/licenses/.

include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";
include "ashopconstants.inc.php";
include "keycodes.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$orderid) {
	header("Location: salesreport.php?error=noorderselected");
	exit;
}

// Get order information...
$sql="SELECT * FROM orders WHERE orderid='$orderid'";
$result = @mysqli_query($db, "$sql");
$parsed_invoice = $orderid;
$customerid = @mysqli_result($result, 0, "customerid");
$user = @mysqli_result($result, 0, "userid");
$customerid = @mysqli_result($result, 0, "customerid");
$payoptionid = @mysqli_result($result, 0, "payoptionid");
$remoteorderid = @mysqli_result($result, 0, "remoteorderid");
$parsed_products = @mysqli_result($result, 0, "products");
$productprices = explode("|",@mysqli_result($result, 0, "productprices"));
$description = @mysqli_result($result, 0, "description");
$displaydescr = str_replace(",","<br>",$description);
$displaydescr = str_replace("\r\n","<br>",$description);
$date = @mysqli_result($result, 0, "date");
$datearray = explode(" ", $date);
$dateshort = $datearray[0];
$paid = @mysqli_result($result, 0, "paid");
$parsed_price = @mysqli_result($result, 0, "price");
$salestax = @mysqli_result($result, 0, "tax");
$shippingcost = @mysqli_result($result, 0, "shipping");
$ip = @mysqli_result($result, 0, "ip");
$password = @mysqli_result($result, 0, "password");
$totaldiscount = @mysqli_result($result, 0, "discount");
$rsubtotal = $parsed_price - $salestax - $shippingcost + $totaldiscount;
if ($shop != "1") {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$shop'");
	$shopuser = @mysqli_result($result,0,"username");
	$shopname = stripslashes(@mysqli_result($result,0,"shopname"));
	$shopaddress = stripslashes(@mysqli_result($result,0,"address"))."<br>".stripslashes(@mysqli_result($result,0,"city").", ".@mysqli_result($result,0,"state")." ".@mysqli_result($result,0,"zip"))."<br>".stripslashes(@mysqli_result($result,0,"country"))."<br>";
	$shopemail = stripslashes(@mysqli_result($result,0,"email"));
	$shopphone = stripslashes(@mysqli_result($result,0,"phone"));
}

// Get customer information...
$sql="SELECT * FROM customer WHERE customerid='$customerid'";
$result = @mysqli_query($db, "$sql");
$parsed_firstname = @mysqli_result($result, 0, "firstname");
$parsed_lastname = @mysqli_result($result, 0, "lastname");
$parsed_address = @mysqli_result($result, 0, "address");
$parsed_zip = @mysqli_result($result, 0, "zip");
$parsed_city = @mysqli_result($result, 0, "city");
$parsed_state = @mysqli_result($result, 0, "state");
$parsed_country = @mysqli_result($result, 0, "country");
$parsed_phone = @mysqli_result($result, 0, "phone");
$parsed_email = @mysqli_result($result, 0, "email");
$extrainfo = @mysqli_result($result, 0, "extrainfo");
$lang = @mysqli_result($result, 0, "preflanguage");
if (!$lang) $lang = $defaultlanguage;
include "../language/$lang/ad_reactivate.inc.php";

// Reactivate an order...
if (!$resend && !$reactivatedownloads && !$receiptadmin) {
	echo "$header
		<div class=\"heading\">".REACTIVATERESENDORDER.":</div><center>
		<form action=\"reactivate.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderid</font></td></tr>";
	if ($remoteorderid) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".GATEWAYORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$remoteorderid</font></td></tr>";
	echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DATEOFSALE.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$date</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customerid, $parsed_firstname $parsed_lastname</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".AMOUNTPAID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".$currencysymbols[$ashopcurrency]["pre"].number_format($parsed_price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".RESENDRECEIPT.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"checkbox\" name=\"resend\" checked> <input type=\"text\" name=\"resendemail\" value=\"$parsed_email\" size=\"40\"></font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".COPYTOADMIN.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"checkbox\" name=\"receiptadmin\" checked></font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".REACTIVATEDOWNLOADS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"checkbox\" name=\"reactivatedownloads\" checked></font></td></tr>
		</table>
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"right\" valign=\"top\"><input type=\"submit\" value=\"".SUBMIT."\">
		<input type=\"button\" name=\"cancel\" value=\"".CANCEL."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		</form></center>
        $footer";
	exit;
} else {
	if ($resend || $receiptadmin) {
		// Get product information from product string...
		$downloadgoods = 0;
		$totaldiscount = 0;
		$tangiblegoods = 0;
		$rdescriptionstring = "";
		$subscriptiongoods = 0;
		$subscriptionlinks = "";
		$unlockkeystring = "";
		$productsincart = ashop_parseproductstring($db, $parsed_products);
		if (is_array($productsincart)) foreach($productsincart as $productnumber => $thisproduct) {
			$thisproductid = $thisproduct["productid"];
			$thisproductname = $thisproduct["name"];
			if (is_array($productprices)) foreach ($productprices as $pricepart) {
				$thisproductprice = explode(":",$pricepart);
				if ($thisproductprice[0] == $thisproductid) $thisprice = $thisproductprice[1];
			} else $thisprice = $thisproduct["price"];
			$thisquantity = $thisproduct["quantity"];
			$filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$thisproductid'");
			$files = @mysqli_num_rows($filesresult);
			$sql="SELECT * FROM product WHERE productid=$thisproductid";
			$result = @mysqli_query($db, "$sql");
			$subscriptiondir = @mysqli_result($result,0,"subscriptiondir");
			$producttype = @mysqli_result($result,0,"prodtype");
			$shipping = @mysqli_result($result,0,"shipping");
			$rdescriptionstring .= "<tr bgcolor=\"#ffffff\"><td align=\"middle\" width=\"30\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisquantity</font></td><td width=\"433\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$thisproductname".$thisproduct["parameters"]."</font></td><td align=\"right\" width=\"60\"></td><td align=\"right\" width=\"60\"></td></tr>\r\n";
			if ($shipping) $tangiblegoods = 1;
			if ($files && $thisproduct["download"] != "none") $downloadgoods = 1;
			if ($subscriptiondir && $producttype == "subscription") {
				$subscriptiongoods = 1;
				$subscriptionurl = @mysqli_result($result,0,"protectedurl");
				if ($probotpath && file_exists("$probotpath/data/groups/$subscriptiondir/pass.txt")) {
					if ($subscriptionurl) {
						if ($receitpformat == "html") $subscriptionlinks .= "<a href=\"$subscriptionurl\">$thisproductname</a><br>";
						else $subscriptionlinks .= "$subscriptionurl";
					} else $subscriptionlinks .= "$thisproductname<br>";
				} else if ($papluspath && file_exists("$papluspath/$subscriptiondir/d_pass.txt") && file_exists("$papluspath/$subscriptiondir/d_active.txt")) {
					if ($subscriptionurl) {
						if ($receitpformat == "html") $subscriptionlinks .= "<a href=\"$subscriptionurl\">$thisproductname</a><br>";
						else $subscriptionlinks .= "$subscriptionurl";
					} else $subscriptionlinks .= "$thisproductname<br>";
				} else {
					if ($subscriptionurl) {
						if ($receiptformat == "html") $subscriptionlinks .= "<a href=\"$subscriptionurl\">$thisproductname</a><br>";
						else $subscriptionlinks .= "$subscriptionurl";
					} else {
						if ($receiptformat == "html") $subscriptionlinks .= "<a href=\"$ashopurl/$subscriptiondir\">$thisproductname</a><br>";
						else $subscriptionlinks .= "$ashopurl/$subscriptiondir";
					}
				}
			}

			// Get unlock keys that have been assigned to this order...
			$result = @mysqli_query($db, "SELECT * FROM unlockkeys WHERE productid='$thisproductid' AND orderid='$parsed_invoice'");
			while ($row = @mysqli_fetch_array($result)) {
				$keytext = trim($row["keytext"]);
				if (!empty($keycodeencryptionkey) && !empty($keytext)) $keytext = ashop_decrypt($keytext, $keycodeencryptionkey);
				if ($keytext) $unlockkeystring .= "Your unlock key for $thisproductname is: <b>$keytext</b><br>";
			}


		}

		// Get selected shipping options...
		$handlingcosts = ashop_gethandlingcost($parsed_products);
		$shippingdiscount = $handlingcosts["discount"];
		$selectedshipoptions = "";
		if (is_array($handlingcosts)) foreach($handlingcosts as $handlingname => $value) {
			if (strstr($handlingname, "so")) {
				$result = @mysqli_query($db, "SELECT * FROM shipoptions WHERE shipoptionid='$value'");
				if ($selectedshipoptions) $selectedshipoptions .= ", ";
				$selectedshipoptions .= @mysqli_result($result, 0, "description");
			}
		}

		// Get the shipping info...
		$shipto = "";
		$sql="SELECT * FROM shipping WHERE customerid='$customerid'";
		$result = @mysqli_query($db, "$sql");
		$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
		$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
		$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
		$shippingaddress2 = @mysqli_result($result, 0, "shippingaddress2");
		$shippingcity = @mysqli_result($result, 0, "shippingcity");
		$shippingstate = @mysqli_result($result, 0, "shippingstate");
		$shippingzip = @mysqli_result($result, 0, "shippingzip");
		$shippingcountry = @mysqli_result($result, 0, "shippingcountry");
		$shipto = "$shippingfirstname $shippinglastname<br>\r\n";
		$shipto .= "$shippingaddress<br>\r\n";
		if ($shippingaddress2) $shipto .= "$shippingaddress2<br>\r\n";
		$shipto .= "$shippingcity, $shippingstate $shippingzip<br>\r\n";
		$shipto .= $countries["$shippingcountry"];

		$sql="SELECT name FROM payoptions WHERE payoptionid='$payoptionid'";
		$result = @mysqli_query($db, "$sql");
		$payoptionname = @mysqli_result($result, 0, "name");

		// Read receipt file if available...
		if (file_exists("$ashoppath/admin/receipts/$parsed_invoice")) {
			$fp = fopen ("$ashoppath/admin/receipts/$parsed_invoice","r");
			while (!feof ($fp)) $receipt .= fgets($fp, 4096);
			fclose ($fp);
			if (strstr($receipt, "<html>")) $receiptformat = "html";
		} else {

			// Read receipt template...
			if (file_exists("$ashoppath/templates/messages/receipt-$lang.{$receiptformat}")) $receiptfile = "$ashoppath/templates/messages/receipt-$lang.$receiptformat";
			else $receiptfile = "$ashoppath/templates/messages/receipt.{$receiptformat}";
			$fp = fopen ("$receiptfile","r");
			while (!feof ($fp)) $receipttemplate .= fgets($fp, 4096);
			fclose($fp);
		$receipt = str_replace("%ashopname%",$ashopname,$receipttemplate);
		$receipt = str_replace("%ashopemail%",$ashopemail,$receipt);
		$receipt = str_replace("%dateshort%",$dateshort,$receipt);
		$receipt = str_replace("%invoice%",$parsed_invoice,$receipt);
		$receipt = str_replace("%customer_firstname%",stripslashes($parsed_firstname),$receipt);
		$receipt = str_replace("%customer_lastname%",stripslashes($parsed_lastname),$receipt);
		$receipt = str_replace("%customer_address%",stripslashes($parsed_address),$receipt);
		$receipt = str_replace("%customer_city%",stripslashes($parsed_city),$receipt);
		$receipt = str_replace("%customer_state%",stripslashes($parsed_state),$receipt);
		$receipt = str_replace("%customer_zip%",stripslashes($parsed_zip),$receipt);
		$receipt = str_replace("%customer_country%",stripslashes($parsed_country),$receipt);
		$receipt = str_replace("%customer_email%",$parsed_email,$receipt);
		$receipt = str_replace("%customer_phone%",$parsed_phone,$receipt);
		$receipt = str_replace("%customer_info%","$extrainfo",$receipt);
		$receipt = str_replace("%receipt_description%",$rdescriptionstring,$receipt);

		$receipt = str_replace("%payoption%",$payoptionname,$receipt);
		if ($payoptionfee) $payoptionstring = "(".$currencysymbols[$ashopcurrency]["pre"]."$payoptionfee".$currencysymbols[$ashopcurrency]["post"]." fee)";
		else $payoptionstring = "";
		$receipt = str_replace("%payoptionfee%",$payoptionstring,$receipt);

		$receipt = str_replace("%subtotal%",number_format($rsubtotal,$showdecimals,$decimalchar,$thousandchar),$receipt);
		$receipt = str_replace("%salestax%",number_format($salestax,$showdecimals,$decimalchar,$thousandchar),$receipt);
		$receipt = str_replace("%shipping%",number_format($shippingcost,$showdecimals,$decimalchar,$thousandchar),$receipt);

		$splitreceipt1 = explode("<!-- Newcustomerpassword -->", $receipt);
		$splitreceipt2 = explode("<!-- /Newcustomerpassword -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");

		if ($shippingdiscount) {
			$receipt = str_replace("%shippingdiscount%",number_format($shippingdiscount,$showdecimals,$decimalchar,$thousandchar),$receipt);
			str_replace("\n<!-- Shippingdiscount -->\n","",$receipt);
			str_replace("\n<!-- /Shippingdiscount -->\n","\n",$receipt);
			str_replace("<!-- Shippingdiscount -->","",$receipt);
			str_replace("<!-- /Shippingdiscount -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Shippingdiscount -->", $receipt);
			$splitreceipt2 = explode("<!-- /Shippingdiscount -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}

		$splitreceipt1 = explode("<!-- Virtualcash -->", $receipt);
		$splitreceipt2 = explode("<!-- /Virtualcash -->", $splitreceipt1[1]);
		$receipt = rtrim($splitreceipt1[0],"\n")."\n".ltrim($splitreceipt2[1],"\n");

		$receipt = str_replace("%amount%",$currencysymbols[$ashopcurrency]["pre"]."$parsed_price ".$currencysymbols[$ashopcurrency]["post"],$receipt);

		// Add special instructions...
		if(($tangiblegoods && $shipto) ||
			$downloadgoods || 
			$unlockkeystring || 
			$subscriptiongoods) {
			if ($tangiblegoods && $shipto) {
				$receipt = str_replace("%customer_shippingaddress%",$shipto,$receipt);
				str_replace("\n<!-- Shippingaddress -->\n","",$receipt);
				str_replace("\n<!-- /Shippingaddress -->\n","\n",$receipt);
				str_replace("<!-- Shippingaddress -->","",$receipt);
				str_replace("<!-- /Shippingaddress -->","",$receipt);
				if ($selectedshipoptions) {
					$receipt = str_replace("%shipoptions%",$selectedshipoptions,$receipt);
					str_replace("\n<!-- Shippingoption -->\n","",$receipt);
					str_replace("\n<!-- /Shippingoption -->\n","\n",$receipt);
					str_replace("<!-- Shippingoption -->","",$receipt);
					str_replace("<!-- /Shippingoption -->","",$receipt);
				} else {
					$splitreceipt1 = explode("<!-- Shippingoption -->", $receipt);
					$splitreceipt2 = explode("<!-- /Shippingoption -->", $splitreceipt1[1]);
					$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
				}
			} else {
				$splitreceipt1 = explode("<!-- Shippingaddress -->", $receipt);
				$splitreceipt2 = explode("<!-- /Shippingaddress -->", $splitreceipt1[1]);
				$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
			}

			if ($downloadgoods) {
				$limiteddays = "$alloweddownloaddays";
				$unlimiteddays = UNLIMITED;
				if ($downloaddays = ( $alloweddownloaddays > 0 ? $limiteddays : $unlimiteddays ));
				$limiteddownloads = "$alloweddownloads";
				$unlimiteddownloads = UNLIMITED;
				if ($downloadtimes = ( $alloweddownloads > 0 ? $limiteddownloads : $unlimiteddownloads ));

				$receipt = str_replace("%ashopurl%",$ashopurl,$receipt);
				$receipt = str_replace("%customer_email%",$parsed_email,$receipt);
				$receipt = str_replace("%password%",$password,$receipt);
				$receipt = str_replace("%downloadtimes%",$downloadtimes,$receipt);
				$receipt = str_replace("%downloaddays%",$downloaddays,$receipt);
				str_replace("\n<!-- Downloads -->\n","",$receipt);
				str_replace("\n<!-- /Downloads -->\n","\n",$receipt);
				str_replace("<!-- Downloads -->","",$receipt);
				str_replace("<!-- /Downloads -->","",$receipt);
			} else {
				$splitreceipt1 = explode("<!-- Downloads -->", $receipt);
				$splitreceipt2 = explode("<!-- /Downloads -->", $splitreceipt1[1]);
				$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
			}

			if ($unlockkeystring) {
				$receipt = str_replace("%unlockkeys%",$unlockkeystring,$receipt);
				str_replace("\n<!-- Unlockkeys -->\n","",$receipt);
				str_replace("\n<!-- /Unlockkeys -->\n","\n",$receipt);
				str_replace("<!-- Unlockkeys -->","",$receipt);
				str_replace("<!-- /Unlockkeys -->","",$receipt);
			} else {
				$splitreceipt1 = explode("<!-- Unlockkeys -->", $receipt);
				$splitreceipt2 = explode("<!-- /Unlockkeys -->", $splitreceipt1[1]);
				$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
			}

			if ($subscriptiongoods) {
				$receipt = str_replace("%password%",$password,$receipt);
				$receipt = str_replace("%subscriptionlinks%",$subscriptionlinks,$receipt);
				str_replace("\n<!-- Subscriptions -->\n","",$receipt);
				str_replace("\n<!-- /Subscriptions -->\n","\n",$receipt);
				str_replace("<!-- Subscriptions -->","",$receipt);
				str_replace("<!-- /Subscriptions -->","",$receipt);
			} else {
				$splitreceipt1 = explode("<!-- Subscriptions -->", $receipt);
				$splitreceipt2 = explode("<!-- /Subscriptions -->", $splitreceipt1[1]);
				$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
			}
			$splitreceipt1 = explode("<!-- Manualpayment -->", $receipt);
			$splitreceipt2 = explode("<!-- /Manualpayment -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
			str_replace("\n<!-- Specialinstructions -->\n","",$receipt);
			str_replace("\n<!-- /Specialinstructions -->\n","\n",$receipt);
			str_replace("<!-- Specialinstructions -->","",$receipt);
			str_replace("<!-- /Specialinstructions -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Specialinstructions -->", $receipt);
			$splitreceipt2 = explode("<!-- /Specialinstructions -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}

		$receipt = str_replace("%ashopname%",$ashopname,$receipt);
		$receipt = str_replace("%ashopaddress%",$ashopaddress,$receipt);
		$receipt = str_replace("%ashopemail%",$ashopemail,$receipt);
		$receipt = str_replace("%ashopphone%",$ashopphone,$receipt);

		if ($shop != "1" && $shopuser && $shopname) {
			$receipt = str_replace("%membershop%","<a href=\"$ashopurl/index.php?shop=$shop\">$shopname</a>",$receipt);
			str_replace("\n<!-- Membershop -->\n","",$receipt);
			str_replace("\n<!-- /Membershop -->\n","\n",$receipt);
			str_replace("<!-- Membershop -->","",$receipt);
			str_replace("<!-- /Membershop -->","",$receipt);
		} else {
			$splitreceipt1 = explode("<!-- Membershop -->", $receipt);
			$splitreceipt2 = explode("<!-- /Membershop -->", $splitreceipt1[1]);
			$receipt = rtrim($splitreceipt1[0],"\n").ltrim($splitreceipt2[1],"\n");
		}
		}

		if ($receiptformat == "html") $headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		else {
			$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";
			$receipt = strip_tags($receipt);
		}
		if ($resend) @ashop_mail("$resendemail",un_html($ashopname)." - ".RECEIPT,"$receipt","$headers");
		if ($receiptadmin) @ashop_mail("$ashopemail",un_html($ashopname)." - RECEIPT","$receipt","$headers");
	}

	// Reset paid date to reactivate downloads for the allowed number of days...
	if ($reactivatedownloads) {
		$today = date("Y-m-d H:i:s", time()+$timezoneoffset);
		$todayarray = explode(" ", $today);
		@mysqli_query($db, "UPDATE orders SET paid='$today' WHERE orderid='$orderid'");
		@mysqli_query($db, "DELETE FROM orderdownloads WHERE orderid='$orderid'");
	}
	echo "$header
		<div class=\"heading\">".REACTIVATERESENDORDER.":</div><center>";
	if ($resend) echo "<p>".ARECEIPTFORORDER.": <b>$orderid</b> ".HASBEENSENTTO." $resendemail.</p>";
	else if ($receiptadmin) echo "<p>".ARECEIPTFORORDER.": <b>$orderid</b> ".HASBEENSENTTOADMIN."</p>";
	if ($reactivatedownloads) {
		if ($alloweddownloaddays) echo "<p>".DATEOFPAYMENTFORORDER.": <b>$orderid</b> ".HASBEENSETTO." <b>".$todayarray[0]."</b><br>".TOREACTIVATEDOWNLOADSFOR." <b>$alloweddownloaddays ".DAYS."</b>.</p>";
		if ($alloweddownloads) echo "<p>".THENUMBEROFDOWNLOADS." <b>$alloweddownloads ".DOWNLOADSPERPRODUCT."</b>.</p>";
	}
	echo "</center>$footer";
}

// Close database...
@mysqli_close($db);

?>