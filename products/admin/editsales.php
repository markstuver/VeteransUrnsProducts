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

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$orderid || !$action) {
	header("Location: salesreport.php?error=noorderselected");
	exit;
}

// Get order information from user input or database...
$sql="SELECT * FROM orders WHERE orderid='$orderid'";
$result = @mysqli_query($db, "$sql");
$invoiceid = @mysqli_result($result, 0, "invoiceid");
$orderdate = @mysqli_result($result, 0, "date");
$customerid = @mysqli_result($result, 0, "customerid");
$payoptionid = @mysqli_result($result, 0, "payoptionid");
$remoteorderid = @mysqli_result($result, 0, "remoteorderid");
$products = @mysqli_result($result, 0, "products");
$status = @mysqli_result($result, 0, "status");
$productarray = explode("a",$products);
$downloads = @mysqli_result($result, 0, "downloads");
$description = trim(@mysqli_result($result, 0, "description"));
$description = stripslashes($description);
$productprices = @mysqli_result($result, 0, "productprices");
$productpricesarray = explode("|",$productprices);
$displaydescr = "";
$displaydescrarray1 = explode("\r\n",$description);
$newproductstring = "";
$newdescription = "";
if(is_array($displaydescrarray1)) foreach ($displaydescrarray1 as $itemnumber=>$part) {
	$displaydescrarray2 = array();
	$displaydescrarray2[0] = substr($part,0,strpos($part,":"));
	$displaydescrarray2[1] = substr($part,strpos($part,":")+1);
	unset($thisquantity);
	eval("\$thisquantity = \$quantity$itemnumber;");
	$thisproductarray = explode("b",$productarray[$itemnumber]);
	$thisproductid = $thisproductarray[count($thisproductarray)-1];
	if ($thisquantity) {
		$newproductstring .= $thisquantity."b".substr($productarray[$itemnumber],strpos($productarray[$itemnumber],"b")+1)."a";
		$newdescription .= "$thisquantity: $displaydescrarray2[1]";
		if ($itemnumber < count($displaydescrarray1)-1) $newdescription .= "\r\n";
	}
	if ($action == "delete") $displaydescr .= "{$displaydescrarray2[0]}: {$displaydescrarray2[1]}<br>";
	else {
		if (is_array($productpricesarray)) foreach ($productpricesarray as $pricepart) {
			$thisproductpricearray = explode(":",$pricepart);
			if ($thisproductpricearray[0] == $thisproductid) $thisproductprice = $thisproductpricearray[1];
		}
		$displaydescr .= "<input type=\"text\" name=\"quantity$itemnumber\" value=\"$displaydescrarray2[0]\" size=\"5\"> $displaydescrarray2[1] (".PRICEPERITEM.": $thisproductprice)<br>";
	}
}
if ($newproductstring) $products = $newproductstring;
if ($newdescription) $description = trim($newdescription);
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
$shortdate = date("Y-m-d", time()+$timezoneoffset);
$paid = @mysqli_result($result, 0, "paid");
if ($_POST["price"]) $price = $_POST["price"];
else $price = @mysqli_result($result, 0, "price");
if ($_POST["tax"]) {
	$tax = $_POST["tax"];
	if ($action == "chargeback") $tax -= $tax*2;
	$taxstring = number_format($tax,2,'.','');
} else if ($_POST["gst"] || $_POST["pst"]) {
	if ($action == "chargeback") $gst -= $gst*2;
	if ($action == "chargeback") $pst -= $pst*2;
	$taxstring = "c|".number_format($gst,2,'.','')."|".number_format($pst,2,'.','');
	$tax = $taxstring;
} else {
	$tax = @mysqli_result($result, 0, "tax");
	$taxstring = "0.00";
}
if (substr($tax,0,1) == "c") {
	$taxarray = explode("|",$tax);
	$gst = $taxarray[1];
	$pst = $taxarray[2];
	$tax = "";
} else {
	$gst = "";
	$pst = "";
}
if ($_POST["shipping"]) {
	$shipping = $_POST["shipping"];
	if ($action == "chargeback") $shipping -= $shipping*2;
} else $shipping = @mysqli_result($result, 0, "shipping");
$ip = @mysqli_result($result, 0, "ip");
$password = @mysqli_result($result, 0, "password");
$thisuserid = @mysqli_result($result, 0, "userid");
if ($_POST["discount"]) $discount = $_POST["discount"];
else $discount = @mysqli_result($result, 0, "discount");
$language = @mysqli_result($result, 0, "language");
if ($shipping) $newproductstring .= "shb$shipping"."a";
if ($tax) $newproductstring .= "stb$tax"."a";
if ($discount) $newproductstring .= "sdb$discount"."a";

$sql="SELECT * FROM customer WHERE customerid='$customerid'";
$result = @mysqli_query($db, "$sql");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");

$sql="SELECT * FROM payoptions WHERE payoptionid='$payoptionid'";
$result = @mysqli_query($db, "$sql");
$payoption = @mysqli_result($result, 0, "name");
$gateway = @mysqli_result($result, 0, "gateway");
$merchantid = @mysqli_result($result, 0, "merchantid");
$securitysecret = @mysqli_result($result, 0, "secret");
$testmode = @mysqli_result($result, 0, "testmode");

// Get Digital Mall member orders...
$moselectionstring = "";
$sql="SELECT * FROM memberorders WHERE orderid='$orderid' ORDER BY userid";
$result = @mysqli_query($db, "$sql");
while ($row = @mysqli_fetch_array($result)) {
	$thismoid = $row["memberorderid"];
	$thismouserid = $row["userid"];
	$thismoprice = number_format($row["price"],2,'.','');
	$thismodescription = $row["description"];
	$result2 = @mysqli_query($db, "SELECT * FROM user WHERE userid='$thismouserid'");
	$memberrow = @mysqli_fetch_array($result2);
	$membername = $memberrow["shopname"];
	$moselectionstring .= AMOUNT.": <input type=\"text\" name=\"mo$thismoid\" value=\"$thismoprice\" size=\"8\"> ".TO.": $membername<br>";
}

// Get POs...
$poselectionstring = "";
$sql="SELECT * FROM emerchant_purchaseorder WHERE orderid='$orderid' AND (billdate='' OR billdate IS NULL) ORDER BY date";
$result = @mysqli_query($db, "$sql");
while ($row = @mysqli_fetch_array($result)) {
	$thispoid = $row["purchaseorderid"];
	$thisvendorid = $row["vendorid"];
	$thispodatearray = explode(" ",$row["date"]);
	$thispodate = $thispodatearray[0];
	$thispocost = $row["cost"];
	$thisposhipping = $row["shipping"];
	$thispodiscount = $row["discount"];
	$thispotax = $row["tax"];
	$thistotal = $thispocost+$thisposhipping+$thispotax-$thispodiscount;
	$result2 = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$thisvendorid'");
	$vendorrow = @mysqli_fetch_array($result2);
	$vendorname = $vendorrow["name"];
	$vendorid = $vendorrow["vendorid"];
	$poselectionstring .= "<input type=\"checkbox\" name=\"po$thispoid\" value=\"1\"> <b>$thispoid:</b> $thispodate ".VENDOR.": $vendorname, ".AMOUNT.": $thistotal<br>";
}

// Get bills...
$billselectionstring = "";
$sql="SELECT * FROM emerchant_purchaseorder WHERE orderid='$orderid' AND billdate!='' AND billdate IS NOT NULL ORDER BY billdate";
$result = @mysqli_query($db, "$sql");
while ($row = @mysqli_fetch_array($result)) {
	$thisbillid = $row["purchaseorderid"];
	$thisvendorid = $row["vendorid"];
	$thisbilled = $row["billdate"];
	$thisbilltotal = $row["cost"];
	$result2 = @mysqli_query($db, "SELECT * FROM emerchant_vendor WHERE vendorid='$thisvendorid'");
	$vendorrow = @mysqli_fetch_array($result2);
	$vendorname = $vendorrow["name"];
	$vendorid = $vendorrow["vendorid"];
	$billselectionstring .= "<input type=\"checkbox\" name=\"po$thisbillid\" value=\"1\"> <b>$thisbillid:</b> $thisbilled ".VENDOR.": $vendorname, ".AMOUNT.": $thisbilltotal<br>";
}

// Delete an order...
if ($action == "delete") {
	if ($yes) {
       $sql="DELETE FROM orders WHERE orderid=$orderid";
       $result = @mysqli_query($db, $sql);
	   $sql="UPDATE unlockkeys SET orderid=NULL WHERE orderid='$orderid'";
	   $result = @mysqli_query($db, $sql);
	   if ($salesreport) {
		   $reportfields = explode("|", $salesreport);
		   $reporttype = $reportfields[0];
		   $startyear = $reportfields[1];
		   $startmonth = $reportfields[2];
		   $startday = $reportfields[3];
		   $toyear = $reportfields[4];
		   $tomonth = $reportfields[5];
		   $today = $reportfields[6];
		   $orderby = $reportfields[7];
		   $ascdesc = $reportfields[8];
		   $generate = $reportfields[9];
		   if (strstr($SERVER_SOFTWARE, "IIS")) {
			   echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=salesreport.php?msg=deleted&reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate\"></head></html>";
			   exit;
		   } else header("Location: salesreport.php?msg=deleted&reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
	   } else {
		   header("Location: salesreport.php?msg=deleted");
		   exit;
	   }
    } elseif ($no) {
	   header("Location: salesreport.php");
	   exit;
	} else {
		if (empty($invoiceid)) $invoiceid = $orderid;
		echo "$header
		 <div class=\"heading\">".DELETEANORDER."</div><center>
        <p>".AREYOUSUREDELETEORDER."</p>
		<table width=\"540\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$invoiceid</font></td></tr>";
		if ($remoteorderid) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".GATEWAYORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$remoteorderid</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DATEOFSALE.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderdate</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customerid, $firstname $lastname</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".AMOUNT.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".$currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</font></td></tr>";
		if ($payoption) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PAYMENTOPTION.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$payoption</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>";
		if ($comment) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".YOURCOMMENT.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$comment</font></td></tr>";
		echo "
		</table>	    
		<form action=\"editsales.php\" method=\"post\">
		<table width=\"540\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		<input type=\"hidden\" name=\"action\" value=\"delete\"><input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\"></form>
		</center>
        $footer";
		exit;
	}
} 


// Credit an order...
if ($action == "chargeback") {
	if ($yes) {
	   // Submit a refund to Klarna if they were used for payment...
	   if ($gateway == "klarna") {

		   require_once "$ashoppath/payment/Klarna/Klarna.php";
		   require_once "$ashoppath/payment/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc";
		   require_once "$ashoppath/payment/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc";

		   $k = new Klarna();

		   if ($testmode == "1") $k->config(
			   $merchantid,		
			   "$securitysecret",  
			   KlarnaCountry::SE,	// Purchase country
			   KlarnaLanguage::SV,	// Purchase language
			   KlarnaCurrency::SEK,// Purchase currency
			   Klarna::BETA,		// Server
			   'json',				
				'./pclasses.json'     
			);
			else $k->config(
				$merchantid,		
				"$securitysecret",  
				KlarnaCountry::SE,	// Purchase country
				KlarnaLanguage::SV,	// Purchase language
				KlarnaCurrency::SEK,// Purchase currency
				Klarna::LIVE,		// Server
				'json',				
				'./pclasses.json'     
			);

			try {
				$result = $k->creditInvoice("$remoteorderid");
			} catch(Exception $e) {
				$klarnaerrormessage = $e->getMessage()." (#".$e->getCode().")";
				
				echo "$header
				<div class=\"heading\">".WILLREVERSEORDER."</div><center>
				<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td>
				<br><span class=\"error\">$klarnaerrormessage</span></br>
				</td></tr></table>
				</center>
				$footer";
				exit;
			}
	   }

	   // Reverse the order...
	   if ($npassword) @mysqli_query($db, "UPDATE orders SET password='$npassword' WHERE orderid='$orderid'");
	   else $npassword = $password;
	   $result = @mysqli_query($db, "SELECT * FROM orderaffiliate WHERE orderid='$orderid'");
	   while ($row = @mysqli_fetch_array($result)) {
		   $affiliateid = $row["affiliateid"];
		   $secondtier = $row["secondtier"];
		   $commission = $row["commission"];
		   $commission -= $commission*2;
		   @mysqli_query($db, "INSERT INTO orderaffiliate (affiliateid, orderid, secondtier, commission) VALUES ('$affiliateid', '$orderid', '$secondtier', '$commission')");
	   }
	   $result = @mysqli_query($db, "SELECT * FROM pendingorderaff WHERE orderid='$orderid'");
	   while ($row = @mysqli_fetch_array($result)) {
		   $affiliateid = $row["affiliateid"];
		   $secondtier = $row["secondtier"];
		   $commission = $row["commission"];
		   $commission -= $commission*2;
		   @mysqli_query($db, "INSERT INTO pendingorderaff (affiliateid, orderid, secondtier, commission) VALUES ('$affiliateid', '$orderid', '$secondtier', '$commission')");
	   }
	   $price -= $price*2;
	   $description = @mysqli_real_escape_string($db, $description);
	   $seqinvoiceidresult = @mysqli_query($db,"SELECT MAX(invoiceid) AS invoiceid FROM orders");
	   $invoiceid = @mysqli_result($seqinvoiceidresult,0,"invoiceid");
	   $invoiceid++;
	   $sql="INSERT INTO orders (customerid, reference, payoptionid, remoteorderid, products, productprices, description, date, paid, price, tax, shipping, discount, ip, password, comment, userid, language, invoiceid) VALUES ('$customerid', '$orderid', '$payoptionid', '$remoteorderid', '$newproductstring', '$productprices', '$description', '$date', '$paid', '$price', '$taxstring', '$shipping', '$discount', '$ip', '$npassword', '$chargebackcomment', '$thisuserid', '$language', '$invoiceid')";

	   $result = @mysqli_query($db, $sql);

	   $chargebackorderid = @mysqli_insert_id($db);

	   if (is_array($_POST)) foreach ($_POST as $key=>$value) {
		   if (strstr($key, "po") && $value == "1") {
			   $po = str_replace("po","",$key);
			   $poresult = @mysqli_query($db, "SELECT * FROM emerchant_purchaseorder WHERE purchaseorderid='$po'");
			   $porow = @mysqli_fetch_array($poresult);
			   $vendorid = $porow["vendorid"];
			   $sent = $porow["sent"];
			   $billdate = $porow["billdate"];
			   if (!$billdate) $billdate = $shortdate;
			   $poproducts = $porow["products"];
			   $poproductprices = $porow["productprices"];
			   $comments = $porow["comments"];
			   $commentprices = $porow["commentprices"];
			   $itemorder = $porow["itemorder"];
			   $cost = $porow["cost"];
			   $poshipping = $porow["shipping"];
			   $podiscount = $porow["discount"];
			   $potax = $porow["tax"];
			   $billtotal = $porow["billtotal"];
			   $shipdate = $porow["shipdate"];
			   if (!$shipdate && !$billtotal) {
				   $shipdate = $shortdate;
				   @mysqli_query($db, "UPDATE emerchant_purchaseorder SET billdate='$billdate', paiddate='$billdate', billtotal='0.00', shipdate='$shipdate' WHERE purchaseorderid='$po'");
			   } else {
				   $cost -= $cost*2;
				   $poshipping -= $poshipping*2;
				   $potax -= $potax*2;
				   $billtotal -= $billtotal*2;
				   @mysqli_query($db, "INSERT INTO emerchant_purchaseorder (reference, orderid, vendorid, customerid, date, sent, billdate, products, productprices, comments, commentprices, itemorder, cost, shipping, discount, tax, billtotal, shipdate) VALUES ('$po', '$orderid', '$vendorid', '$customerid', '$date', '$sent', '$billdate', '$poproducts', '$poproductprices', '$comments', '$commentprices', '$itemorder', '$cost', '$poshipping', '$podiscount', '$potax', '$billtotal', '$shipdate')");
			   }
		   } else if (substr($key,0,2) == "mo" && $value) {
			   $morderid = substr($key,2);
			   unset($moprice);
			   $moprice -= $value;
			   $moresult = @mysqli_query($db, "SELECT userid FROM memberorders WHERE memberorderid='$morderid'");
			   $mouserid = @mysqli_result($moresult,0,"userid");
			   if($mouserid > 1) @mysqli_query($db, "INSERT INTO memberorders (customerid, orderid, userid, description, date, paid, price, reference) VALUES ('$customerid', '$chargebackorderid', '$mouserid', '', '$date', '$date', '$moprice', '$orderid')");
		   }
	   }

	   if($salesreport) {
		   $reportfields = explode("|", $salesreport);
		   $reporttype = $reportfields[0];
		   $startyear = $reportfields[1];
		   $startmonth = $reportfields[2];
		   $startday = $reportfields[3];
		   $toyear = $reportfields[4];
		   $tomonth = $reportfields[5];
		   $today = $reportfields[6];
		   $orderby = $reportfields[7];
		   $ascdesc = $reportfields[8];
		   $generate = $reportfields[9];
		   header("Location: salesreport.php?msg=chargeback&reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
		   exit;
	   } else {
		   header("Location: salesreport.php?msg=chargeback");
		   exit;
	   }
    } elseif ($no) {
		if($salesreport) {
			$reportfields = explode("|", $salesreport);
			$reporttype = $reportfields[0];
			$startyear = $reportfields[1];
			$startmonth = $reportfields[2];
			$startday = $reportfields[3];
			$toyear = $reportfields[4];
			$tomonth = $reportfields[5];
			$today = $reportfields[6];
			$orderby = $reportfields[7];
			$ascdesc = $reportfields[8];
			$generate = $reportfields[9];
			header("Location: salesreport.php?reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
			exit;
		} else {
			header("Location: salesreport.php");
			exit;
		}
	} else {
		if (empty($invoiceid)) $invoiceid = $orderid;
	   	echo "$header
		<div class=\"heading\">".WILLREVERSEORDER;
		if ($gateway != "klarna") echo " ".NOTREFUND;
		echo "</div><center>
		<form action=\"editsales.php\" method=\"post\">
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$invoiceid</font></td></tr>";
		if ($remoteorderid) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".GATEWAYORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$remoteorderid</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DATEOFSALE.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderdate</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customerid, $firstname $lastname</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".TOTALAMOUNTREPAID.":</b></font></td><td><input type=\"text\" name=\"price\" value=\"$price\" size=\"8\"></td></tr>";
		if ($gst || $pst) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".TAXREPAID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".GSTHST.": <input type=\"text\" name=\"gst\" value=\"$gst\" size=\"8\"> ".PST.": <input type=\"text\" name=\"pst\" value=\"$pst\" size=\"8\"></font></td></tr>";
		else if ($tax) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".TAXREPAID.":</b></font></td><td><input type=\"text\" name=\"tax\" value=\"$tax\" size=\"8\"></td></tr>";
		if ($shipping) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".SHIPPINGREPAID.":</b></font></td><td><input type=\"text\" name=\"shipping\" value=\"$shipping\" size=\"8\"></td></tr>";
		if ($discount) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DISCOUNTRECLAIMED.":</b></font></td><td><input type=\"text\" name=\"discount\" value=\"$discount\" size=\"8\"></td></tr>";
		if ($payoption) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PAIDBY.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$payoption</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".RETURNEDPRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PASSWORD.":</b></font></td><td><input type=\"text\" name=\"npassword\" value=\"$password\" size=\"10\"><font size=\"1\" face=\"Arial, Helvetica, sans-serif\"> ".CHANGETODISABLEDOWNLOADS."</font></td></tr>";
		if ($moselectionstring) echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CREDITSHOPPINGMALLMEMBER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$moselectionstring</font></td></tr>";
		if ($poselectionstring) echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CREDITPURCHASEORDERS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$poselectionstring</font></td></tr>";
		if ($billselectionstring) echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CREDITVENDORBILLS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$billselectionstring</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".YOURCOMMENT.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><textarea name=\"chargebackcomment\" cols=\"30\" rows=\"5\"></textarea></font></td></tr>
		</table>
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".SUBMIT."\">
		<input type=\"button\" name=\"no\" value=\"".CANCEL."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		<input type=\"hidden\" name=\"action\" value=\"chargeback\"><input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\"></form>
		</center>
        $footer";
		exit;
	}
}

// Edit an order...
if ($action == "modify") {
	$result = @mysqli_query($db, "SELECT * FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='0'");
	if (@mysqli_num_rows($result)) {
		$row = @mysqli_fetch_array($result);
		$first_affiliateid = $row["affiliateid"];
		$first_affcommission = $row["commission"];
	}
	$result = @mysqli_query($db, "SELECT * FROM pendingorderaff WHERE orderid='$orderid' AND secondtier='1'");
	if (@mysqli_num_rows($result)) {
		$row = @mysqli_fetch_array($result);
		$second_affiliateid = $row["affiliateid"];
		$second_affcommission = $row["commission"];
	}
	if ($yes) {
	   $description = @mysqli_real_escape_string($db, $description);
	   @mysqli_query($db, "UPDATE orders SET password='$npassword', products='$newproductstring', productprices='$productprices', description='$description', price='$price', tax='$taxstring', shipping='$shipping', discount='$discount' WHERE orderid='$orderid'");
	   if ($firsttiercommission) @mysqli_query($db, "UPDATE pendingorderaff SET commission='$firsttiercommission' WHERE orderid='$orderid' AND secondtier='0'");
	   if ($secondtiercommission) @mysqli_query($db, "UPDATE pendingorderaff SET commission='$secondtiercommission' WHERE orderid='$orderid' AND secondtier='1'");

	   if (is_array($_POST)) foreach ($_POST as $key=>$value) {
		   if (substr($key,0,2) == "mo" && $value) {
			   $morderid = substr($key,2);
			   unset($moprice);
			   $moprice = $value;
			   if ($morderid) @mysqli_query($db, "UPDATE memberorders SET price='$moprice' WHERE memberorderid='$morderid'");
		   }
	   }

	   if($salesreport) {
		   $reportfields = explode("|", $salesreport);
		   $reporttype = $reportfields[0];
		   $startyear = $reportfields[1];
		   $startmonth = $reportfields[2];
		   $startday = $reportfields[3];
		   $toyear = $reportfields[4];
		   $tomonth = $reportfields[5];
		   $today = $reportfields[6];
		   $orderby = $reportfields[7];
		   $ascdesc = $reportfields[8];
		   $generate = $reportfields[9];
		   header("Location: salesreport.php?msg=modify&reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
		   exit;
	   } else {
		   header("Location: salesreport.php?msg=modify");
		   exit;
	   }
    } elseif ($no) {
		if($salesreport) {
			$reportfields = explode("|", $salesreport);
			$reporttype = $reportfields[0];
			$startyear = $reportfields[1];
			$startmonth = $reportfields[2];
			$startday = $reportfields[3];
			$toyear = $reportfields[4];
			$tomonth = $reportfields[5];
			$today = $reportfields[6];
			$orderby = $reportfields[7];
			$ascdesc = $reportfields[8];
			$generate = $reportfields[9];
			header("Location: salesreport.php?reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
			exit;
		} else {
			header("Location: salesreport.php");
			exit;
		}
	} else {
		if (!empty($shippingstatusid) && is_numeric($shippingstatusid)) {
			$shippingstatusresult = @mysqli_query($db, "SELECT * FROM shippingstatus WHERE shippingstatusid='$shippingstatusid' AND status='0'");
			if (@mysqli_num_rows($shippingstatusresult)) {
				$shippingstatusrow = @mysqli_fetch_array($shippingstatusresult);
				$oldquantity = $shippingstatusrow["quantity"];
				$shippingsku = $shippingstatusrow["skucode"];
				if ($unshippedquantity > 0) {
					$subtractquantity = $oldquantity - $unshippedquantity;
					@mysqli_query($db, "UPDATE shippingstatus SET quantity='$unshippedquantity' WHERE shippingstatusid='$shippingstatusid' AND status='0'");
				} else {
					$subtractquantity = $oldquantity;
					@mysqli_query($db, "DELETE FROM shippingstatus WHERE shippingstatusid='$shippingstatusid'");
				}
				@mysqli_query($db, "INSERT INTO shippingstatus (orderid, productname, quantity, status, date) VALUES ('$orderid', '{$shippingstatusrow["productname"]}', '$subtractquantity', '1', '$date')");

				// Submit a partial activation to Klarna if they were used for payment...
				if ($gateway == "klarna" && $subtractquantity > 0) {

					require_once "$ashoppath/payment/Klarna/Klarna.php";
					require_once "$ashoppath/payment/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc.inc";
					require_once "$ashoppath/payment/Klarna/transport/xmlrpc-3.0.0.beta/lib/xmlrpc_wrappers.inc";

					$k = new Klarna();

					if ($testmode == "1") $k->config(
						$merchantid,		
						"$securitysecret",  
						KlarnaCountry::SE,	// Purchase country
						KlarnaLanguage::SV,	// Purchase language
						KlarnaCurrency::SEK,// Purchase currency
						Klarna::BETA,		// Server
						'json',				
						'./pclasses.json'     
					);
					else $k->config(
						$merchantid,		
						"$securitysecret",  
						KlarnaCountry::SE,	// Purchase country
						KlarnaLanguage::SV,	// Purchase language
						KlarnaCurrency::SEK,// Purchase currency
						Klarna::LIVE,		// Server
						'json',				
						'./pclasses.json'     
					);

					try {
						$k->addArtNo($subtractquantity, "$shippingsku");
						$result = $k->activate("$remoteorderid", null);

						$risk = $result[0];  // "ok" or "no_risk"
						$invNo = $result[1]; // "9876451"
					} catch(Exception $e) {
						$klarnaerrormessage = $e->getMessage()." (#".$e->getCode().")";

						echo "$header
						<div class=\"heading\">".MODIFYUNPAIDORDERS."</div><center>
						<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td>
						<br><span class=\"error\">$klarnaerrormessage</span></br>
						</td></tr></table>
						</center>
						$footer";
						exit;
					}
				}

			}
		}
		if (empty($invoiceid)) $invoiceid = $orderid;
	   	echo "$header
		<div class=\"heading\">".MODIFYUNPAIDORDERS;
		if ($gateway != "klarna") echo " ".NOTPOSTPAYMENTS;
		echo "</div><center>
		<form action=\"editsales.php\" method=\"post\">
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\" width=\"150\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$invoiceid</font></td></tr>";
		if ($remoteorderid) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".GATEWAYORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$remoteorderid</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DATEOFSALE.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderdate</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customerid, $firstname $lastname</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".TOTALAMOUNT.":</b></font></td><td><input type=\"text\" name=\"price\" value=\"$price\" size=\"8\"></td></tr>";
		if ($gst || $pst) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".TAX.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".GST.": <input type=\"text\" name=\"gst\" value=\"$gst\" size=\"8\"> ".PST.": <input type=\"text\" name=\"pst\" value=\"$pst\" size=\"8\"></font></td></tr>";
		else if ($tax) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".TAX.":</b></font></td><td><input type=\"text\" name=\"tax\" value=\"$tax\" size=\"8\"></td></tr>";
		if ($shipping) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".SHIPPING.":</b></font></td><td><input type=\"text\" name=\"shipping\" value=\"$shipping\" size=\"8\"></td></tr>";
		if ($discount) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DISCOUNT.":</b></font></td><td><input type=\"text\" name=\"discount\" value=\"$discount\" size=\"8\"></td></tr>";
		if ($first_affcommission) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".AFFILIATECOMMISSION.":</b></font></td><td><input type=\"text\" name=\"firsttiercommission\" value=\"$first_affcommission\" size=\"8\"></td></tr>";
		if ($second_affcommission) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".SECONDTIERCOMMISSION.":</b></font></td><td><input type=\"text\" name=\"secondtiercommission\" value=\"$second_affcommission\" size=\"8\"></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PASSWORD.":</b></font></td><td><input type=\"text\" name=\"npassword\" value=\"$password\" size=\"10\"></td></tr>";
		if ($moselectionstring) echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".SHOPPINGMALLMEMBERS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$moselectionstring</font></td></tr>";
		echo "
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".STATUS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><select name=\"orderstatus\"><option value=\"\">".SELECT."</option><option value=\"".CHECKINGORDER."\">".CHECKINGORDER."</option><option value=\"".PREPARINGORDER."\">".PREPARINGORDER."</option><option value=\"".SHIPPINGORDER."\">".SHIPPINGORDER."</option><option value=\"".ORDERCOMPLETED."\">".ORDERCOMPLETED."</option></select></font></td></tr>
		<tr><td align=\"right\">&nbsp;</td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" name=\"orderstatustext\" value=\"$status\" size=\"70\" /></font></td></tr>
		</table>
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".SUBMIT."\">
		<input type=\"button\" name=\"no\" value=\"".CANCEL."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		<input type=\"hidden\" name=\"action\" value=\"modify\"><input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\"></form>";
		$unshippedproductsresult = @mysqli_query($db, "SELECT * FROM shippingstatus WHERE orderid='$orderid' AND status='0'");
		if (@mysqli_num_rows($unshippedproductsresult)) {
			echo "<br /><table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
			<tr><td colspan=\"2\" align=\"center\"><span class=\"heading\">".UNSHIPPEDPRODUCTS.":</span></td></tr>";
			while ($row = @mysqli_fetch_array($unshippedproductsresult)) {
				echo "<form action=\"editsales.php\" method=\"post\"><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>{$row["productname"]}:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" name=\"unshippedquantity\" value=\"{$row["quantity"]}\" size=\"10\" /> <input type=\"submit\" value=\"".UPDATE."\"></font></td></tr>
				<input type=\"hidden\" name=\"shippingstatusid\" value=\"{$row["shippingstatusid"]}\">
				<input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
				<input type=\"hidden\" name=\"action\" value=\"modify\">
				<input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\"></form>";
			}
			echo "</table>";
		}
		echo "
		</center>
        $footer";
		exit;
	}
}

// Update the status of an order...
if ($action == "updatestatus") {
	if ($yes) {
	   if (empty($orderstatus) && !empty($orderstatustext)) $orderstatus = $orderstatustext;
	   $orderstatus = @mysqli_real_escape_string($db, $orderstatus);
	   @mysqli_query($db, "UPDATE orders SET status='$orderstatus' WHERE orderid='$orderid'");

	   if($salesreport) {
		   $reportfields = explode("|", $salesreport);
		   $reporttype = $reportfields[0];
		   $startyear = $reportfields[1];
		   $startmonth = $reportfields[2];
		   $startday = $reportfields[3];
		   $toyear = $reportfields[4];
		   $tomonth = $reportfields[5];
		   $today = $reportfields[6];
		   $orderby = $reportfields[7];
		   $ascdesc = $reportfields[8];
		   $generate = $reportfields[9];
		   header("Location: salesreport.php?msg=updatedstatus&reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
		   exit;
	   } else {
		   header("Location: salesreport.php?msg=updatedstatus");
		   exit;
	   }
    } elseif ($no) {
		if($salesreport) {
			$reportfields = explode("|", $salesreport);
			$reporttype = $reportfields[0];
			$startyear = $reportfields[1];
			$startmonth = $reportfields[2];
			$startday = $reportfields[3];
			$toyear = $reportfields[4];
			$tomonth = $reportfields[5];
			$today = $reportfields[6];
			$orderby = $reportfields[7];
			$ascdesc = $reportfields[8];
			$generate = $reportfields[9];
			header("Location: salesreport.php?reporttype=$reporttype&startyear=$startyear&startmonth=$startmonth&startday=$startday&toyear=$toyear&tomonth=$tomonth&today=$today&orderby=$orderby&ascdesc=$ascdesc&generate=$generate");
			exit;
		} else {
			header("Location: salesreport.php");
			exit;
		}
	} else {
		if (!empty($shippingstatusid) && is_numeric($shippingstatusid)) {
			$shippingstatusresult = @mysqli_query($db, "SELECT * FROM shippingstatus WHERE shippingstatusid='$shippingstatusid' AND status='0'");
			if (@mysqli_num_rows($shippingstatusresult)) {
				$shippingstatusrow = @mysqli_fetch_array($shippingstatusresult);
				$oldquantity = $shippingstatusrow["quantity"];
				if ($unshippedquantity > 0) {
					$subtractquantity = $oldquantity - $unshippedquantity;
					@mysqli_query($db, "UPDATE shippingstatus SET quantity='$unshippedquantity' WHERE shippingstatusid='$shippingstatusid' AND status='0'");
				} else {
					$subtractquantity = $oldquantity;
					@mysqli_query($db, "DELETE FROM shippingstatus WHERE shippingstatusid='$shippingstatusid'");
				}
				@mysqli_query($db, "INSERT INTO shippingstatus (orderid, productname, quantity, status, date) VALUES ('$orderid', '{$shippingstatusrow["productname"]}', '$subtractquantity', '1', '$date')");
			}
		}
		if (empty($invoiceid)) $invoiceid = $orderid;
	   	echo "$header
		<div class=\"heading\">".UPDATEORDERSTATUS."</div><center>
		<form action=\"editsales.php\" method=\"post\">
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\" width=\"150\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$invoiceid</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".DATEOFSALE.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderdate</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customerid, $firstname $lastname</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$description</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".STATUS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><select name=\"orderstatus\"><option value=\"\">".SELECT."</option><option value=\"".CHECKINGORDER."\">".CHECKINGORDER."</option><option value=\"".PREPARINGORDER."\">".PREPARINGORDER."</option><option value=\"".SHIPPINGORDER."\">".SHIPPINGORDER."</option><option value=\"".ORDERCOMPLETED."\">".ORDERCOMPLETED."</option></select></font></td></tr>
		<tr><td align=\"right\">&nbsp;</td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" name=\"orderstatustext\" value=\"$status\" size=\"70\" /></font></td></tr>
		</table>
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".SUBMIT."\">
		<input type=\"button\" name=\"no\" value=\"".CANCEL."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		<input type=\"hidden\" name=\"action\" value=\"updatestatus\"><input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\"></form>";
		$unshippedproductsresult = @mysqli_query($db, "SELECT * FROM shippingstatus WHERE orderid='$orderid' AND status='0'");
		if (@mysqli_num_rows($unshippedproductsresult)) {
			echo "<br /><table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
			<tr><td colspan=\"2\" align=\"center\"><span class=\"heading\">".UNSHIPPEDPRODUCTS.":</span></td></tr>";
			while ($row = @mysqli_fetch_array($unshippedproductsresult)) {
				echo "<form action=\"editsales.php\" method=\"post\"><tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>{$row["productname"]}:</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" name=\"unshippedquantity\" value=\"{$row["quantity"]}\" size=\"10\" /> <input type=\"submit\" value=\"".UPDATE."\"></font></td></tr>
				<input type=\"hidden\" name=\"shippingstatusid\" value=\"{$row["shippingstatusid"]}\">
				<input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
				<input type=\"hidden\" name=\"action\" value=\"updatestatus\">
				<input type=\"hidden\" name=\"salesreport\" value=\"$salesreport\"></form>";
			}
			echo "</table>";
		}
		echo "
		</center>
        $footer";
		exit;
	}
}

// Close database...
@mysqli_close($db);

?>