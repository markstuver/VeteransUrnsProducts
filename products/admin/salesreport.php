<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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

@set_time_limit(0);
include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";
include "ashopconstants.inc.php";

// Convert translated buttons...
if ($generate == "Redigera") $generate = "Edit";
if ($generate == "Visa") $generate = "View";
if ($generate == "Ladda ner") $generate = "Download";

// Only ashopadmin should be allowed to edit orders...
if ($generate == "View" && $userid == "1") $generate = "Edit";

// Convert double quote enclosure for CSV...
if ($defaultenclosure == "&quot;") $defaultenclosure = "\"";

// Get the correct userid...
if ($userid == "1" && $memberid > 1) $user = $memberid;
else $user = $userid;

// Get context help for this page...
		$contexthelppage = "salesreport";
		include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Show report options...
if (!$generate) {
	
	// Get the oldest and newest order...
	$maxdate = date("Y-m-d H:i:s", time()+$timezoneoffset);
	$sql = "SELECT date FROM orders WHERE date != '' AND userid LIKE '%|$user|%' ORDER BY date LIMIT 1";
	$result = @mysqli_query($db, "$sql");
	$mindate = @mysqli_result($result, 0, "date");
	if($wholesalecatalog && $userid == "1") {
		$sql = "SELECT date FROM orders WHERE date != '' AND wholesale='1' ORDER BY date LIMIT 1";
		$result = @mysqli_query($db, "$sql");
		$wsmindate = @mysqli_result($result, 0, "date");
		if($wsmindate && $wsmindate < $mindate) $mindate = $wsmindate;
	}
	$oldestarray = explode("-", $mindate);
	$oldest = $oldestarray[0];
	$newestarray = explode("-", $maxdate);
	$newest = $newestarray[0];
	$fromyears = "";
	$toyears = "";
	for ($thisyear = $oldest; $thisyear<=$newest; $thisyear++) {
		$toyears .= "<option value=\"$thisyear\"";
		if ($thisyear==$newest) $toyears.= " selected";
		$toyears .= ">$thisyear</option>";
		$fromyears .= "<option value=\"$thisyear\">$thisyear</option>";
	}

	// Get the current month and day...
	$currentmonth = date("m", time()+$timezoneoffset);
	$currentday = date("d", time()+$timezoneoffset);

	echo "$header
	";
	if ($msg == "deleted") echo "<p align=\"center\" class=\"confirm\">".ORDERDELETED."</p>";
	if ($msg == "activated") echo "<p align=\"center\" class=\"confirm\">".ORDERACTIVATIONSENT."</p>";
	if ($msg == "remindersent") echo "<p align=\"center\" class=\"confirm\">".REMINDERSENT."</p>";
	echo "<div class=\"heading\">".SALESREPORT;
	if ($userid == "1") echo " <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a>";
	echo "</div><center><form action=\"salesreport.php\" method=\"post\" name=\"salesreportform\"><table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"border-bottom: 2px solid #999;\"><tr class=\"formtitle\"><td align=\"center\"><p>".TRANSACTIONTYPE.": <select name=\"transtype\">";
	if ($userid == "1" && $wholesalecatalog) echo "<option value=\"retail\">".RETAIL."</option><option value=\"wholesale\">".WHOLESALE."</option><option value=\"auction\">".AUCTION."</option><option value=\"all\">".ALL."</option>";
	else echo "<option value=\"retail\">".RETAIL."</option><option value=\"auction\">".AUCTION."</option>";
	echo "</select> ".PAYMENTSTATUS.": <select name=\"reporttype\">";
	if ($userid == "1") echo "<option value=\"paid\" selected>".PAID."</option><option value=\"unpaid\">".UNPAID."</option><option value=\"chargebacks\">".CHARGEBACKS."</option><option value=\"all\">".ALL."</option>";
	else echo "<option value=\"paid\" selected>".PAIDBYCUSTOMER."</option><option value=\"unpaid\">".UNPAIDBYCUSTOMER."</option><option value=\"chargebacks\">".CHARGEBACKS."</option>";
	echo "</select></p><p>".SHIPPINGSTATUS.": <select name=\"shippingstatus\"><option value=\"shipped\" selected>".SHIPPED."</option><option value=\"unshipped\">".UNSHIPPED."</option><option value=\"all\" selected>".ALL."</option></select></p><p>".FROM.":   
	
	<select name=\"startyear\">$fromyears</select>
	
	<select name=\"startmonth\"><option value=\"01\" selected>".JAN."</option><option value=\"02\">".FEB."</option><option value=\"03\">".MAR."</option><option value=\"04\">".APR."</option><option value=\"05\">".MAY."</option><option value=\"06\">".JUN."</option><option value=\"07\">".JUL."</option><option value=\"08\">".AUG."</option><option value=\"09\">".SEP."</option><option value=\"10\">".OCT."</option><option value=\"11\">".NOV."</option><option value=\"12\">".DEC."</option></select>

	<select name=\"startday\"><option value=\"01\" selected>1</option>";
	
	for ($i = 2; $i < 32; $i++) {
		echo "<option value=\"";
		if ($i < 10) echo "0";
		echo "$i\">$i</option>";
	}
    echo "</select>

	&nbsp;To:   
	
	<select name=\"toyear\">$toyears</select>
	
	<select name=\"tomonth\">";

	echo "<option value=\"01\""; if ($currentmonth == 1) echo "selected"; echo">".JAN."</option>";
	echo "<option value=\"02\""; if ($currentmonth == 2) echo "selected"; echo">".FEB."</option>";
	echo "<option value=\"03\""; if ($currentmonth == 3) echo "selected"; echo">".MAR."</option>";
	echo "<option value=\"04\""; if ($currentmonth == 4) echo "selected"; echo">".APR."</option>";
	echo "<option value=\"05\""; if ($currentmonth == 5) echo "selected"; echo">".MAY."</option>";
	echo "<option value=\"06\""; if ($currentmonth == 6) echo "selected"; echo">".JUN."</option>";
	echo "<option value=\"07\""; if ($currentmonth == 7) echo "selected"; echo">".JUL."</option>";
	echo "<option value=\"08\""; if ($currentmonth == 8) echo "selected"; echo">".AUG."</option>";
	echo "<option value=\"09\""; if ($currentmonth == 9) echo "selected"; echo">".SEP."</option>";
	echo "<option value=\"10\""; if ($currentmonth == 10) echo "selected"; echo">".OCT."</option>";
	echo "<option value=\"11\""; if ($currentmonth == 11) echo "selected"; echo">".NOV."</option>";
	echo "<option value=\"12\""; if ($currentmonth == 12) echo "selected"; echo">".DEC."</option>";

    echo "</select><select name=\"today\">";
	
	for ($i = 1; $i < 32; $i++) {
		echo "<option value=\"";
		if ($i < 10) echo "0";
		echo "$i\"";
		if ($i == $currentday) echo " selected";
		echo ">$i</option>";
	}
    echo "</select>	
	
    </p><p>".ORDERBY.": <select name=\"orderby\"><option value=\"date\" selected>".THEWORDDATE."</option><option value=\"price\">".AMOUNT."</option><option value=\"customerid\">".CUSTOMERID."</option><option value=\"orderid\">".ORDERID."</option><option value=\"affiliate\">".AFFILIATE."</option>";
	if ($userid == "1") echo "<option value=\"productid\">".PRODUCT."</option><option value=\"dproductid\">".DOWNLOADABLEPRODUCT."</option><option value=\"pproductid\">".PHYSICALPRODUCT."</option>";
	echo "</select> <select name=\"ascdesc\"><option value=\"asc\" selected>".ASCENDING."</option><option value=\"desc\">".DESCENDING."</option></select>

	</p><p><input type=\"submit\" name=\"generate\" value=\"".VIEW."\"> <input type=\"submit\" name=\"generate\" value=\"".DOWNLOAD."\">";
	//if ($userid == "1") echo " <input type=\"submit\" name=\"generate\" value=\"".EDIT."\">";
	echo "</p></td></tr></table></form>";
		
	if ($userid == "1") echo "
	<p class=\"formtitle\">".LOOKUPORDER."</p><form action=\"salesreport.php\" method=\"post\" name=\"lookupform\"><table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"border-bottom: 1px solid #999;\"><tr><td align=\"center\"><p><b>".ORDERID.":</b> <input type=\"text\" name=\"orderid\" size=\"10\"> <input type=\"submit\" name=\"submit\" value=\"".SEARCH."\"><input type=\"hidden\" name=\"generate\" value=\"Edit\"><input type=\"hidden\" name=\"lookup\" value=\"true\"></p></td></tr></table></form>
	<p class=\"formtitle\">".LOOKUPINCOMPLETEORDERS."</p><form action=\"editpreliminary.php\" method=\"post\" name=\"incompleteform\"><table width=\"600\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"center\"><p><b>".ORDERID.":</b> <input type=\"text\" name=\"orderid\" size=\"10\"> <input type=\"submit\" name=\"generate\" value=\"".SEARCH."\"><input type=\"hidden\" name=\"action\" value=\"complete\"></p></td></tr></table></form>";
	echo "</center>$footer";

// Generate downloadable reports...
} else if ($generate == "Download") {
	$ordertable = "orders";
	$customertable = "customer";
	$customeridfield = "customerid";
	$paidcheck = " AND paid != ''";
	if ($transtype == "wholesale") $ordertypesql = " AND wholesale = '1'";
	else if ($transtype == "auction") $ordertypesql = " AND source = 'Auction'";
	else if ($transtype == "retail") $ordertypesql = " AND (wholesale IS NULL OR wholesale != '1')";
	if (($memberid > 1 && $userid == "1") || $user > 1) {
		$ordertable = "memberorders";
		$usercheck = "=$user";
	} else $usercheck = "LIKE '%|$user|%'";
	$startdate = "$startyear-$startmonth-$startday 00:00:00";
	$todate = "$toyear-$tomonth-$today 23:59:59";
	if ($reporttype == "chargebacks") {
		$filename = "chargebacks";
		$orderafftable = "orderaffiliate";
	} else if ($reporttype == "unpaid") {
		$filename = "unpaid";
		$orderafftable = "pendingorderaff";
	} else if ($reporttype == "paid") {
		$filename = "sales";
		$orderafftable = "orderaffiliate";
	} else $filename = "transactions";
	header ("Content-Type: application/octet-stream");
	header ("Content-Disposition: attachment; filename=$filename.csv");
	if ($orderby == "productid") echo ORDERID2."{$defaultdelimiter}".THEWORDDATE."{$defaultdelimiter}".THEWORDTIME."{$defaultdelimiter}".QUANTITY."{$defaultdelimiter}".PRICE."{$defaultdelimiter}".PRODUCT."{$defaultdelimiter}".CUSTOMER."{$defaultdelimiter}";
	else echo ORDERID2."{$defaultdelimiter}".TRANSID."{$defaultdelimiter}".PAYMENTMETHOD."{$defaultdelimiter}".THEWORDDATE."{$defaultdelimiter}".THEWORDTIME."{$defaultdelimiter}".PRODUCTS."{$defaultdelimiter}".SUBTOTAL."{$defaultdelimiter}".SHIPPING."{$defaultdelimiter}".TAX."{$defaultdelimiter}".TOTAL."{$defaultdelimiter}".CUSTOMERID."{$defaultdelimiter}".FIRSTNAME."{$defaultdelimiter}".LASTNAME."{$defaultdelimiter}".EMAIL."{$defaultdelimiter}".ADDRESS."{$defaultdelimiter}".CITY."{$defaultdelimiter}".ZIP."{$defaultdelimiter}".STATE."{$defaultdelimiter}".COUNTRY."{$defaultdelimiter}".PHONE."{$defaultdelimiter}".IP."{$defaultdelimiter}";
	if ($reporttype == "chargebacks") echo COMMENT."\r\n";
	else echo REFERRED."\r\n";

	// Report ordered by product...
	if ($orderby == "productid" || $orderby == "dproductid" || $orderby == "pproductid") {
		if ($orderby == "productid" || $orderby == "pproductid") {
			if (($memberid > 1 && $userid == "1") || $user > 1) $result = @mysqli_query($db, "SELECT * FROM product WHERE userid='$user' ORDER BY name $ascdesc");
			else $result = @mysqli_query($db, "SELECT * FROM product ORDER BY name $ascdesc");
		} else if ($orderby == "dproductid") {
			if (($memberid > 1 && $userid == "1") || $user > 1) $result = @mysqli_query($db, "SELECT DISTINCT product.* FROM product, productfiles WHERE product.userid='$user' AND product.productid=productfiles.productid ORDER BY product.name $ascdesc");
			else $result = @mysqli_query($db, "SELECT DISTINCT product.* FROM product, productfiles WHERE product.productid=productfiles.productid ORDER BY product.name $ascdesc");
		}
		while($row=@mysqli_fetch_array($result)) {
			$productid=$row["productid"];
			$productname=trim($row["name"]);
			$productname = un_html($productname);
			if ($defaultenclosure == "\"" && strstr($productname,"\"")) $productname = str_replace("\"","\"\"",$productname);
			if ($orderby == "pproductid") {
				$checkfiles = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
				if (@mysqli_num_rows($checkfiles)) $failedfilescheck = TRUE;
				else $failedfilescheck = FALSE;
			}
			if($productid && $productname && !$failedfilescheck) {
				if ($reporttype == "paid") $paidstring = "$ordertable.paid != ''";
				else if ($reporttype == "unpaid") $paidstring = "$ordertable.paid = ''";
				$sql = "SELECT $customertable.*, $ordertable.* FROM $customertable, $ordertable WHERE ($ordertable.products LIKE '%b$productid"."a%' OR $ordertable.products LIKE '%b$productid"."d%') AND $ordertable.$customeridfield = $customertable.$customeridfield AND $ordertable.date >= '$startdate' AND $ordertable.date <= '$todate'";
				if ($paidcheck && $paidstring) $sql .= " AND $paidstring";
				if ($ordertypesql) $sql .= $ordertypesql;
				if ($reporttype == "chargebacks") $sql .= " AND reference != '' AND reference IS NOT NULL AND $ordertable.price < 0";
				$result2 = @mysqli_query($db, $sql);
				if(@mysqli_num_rows($result2)) {
					$subtotal = 0;
					$totalqty = 0;
					while($row2=@mysqli_fetch_array($result2)) if ($thisquantity=ashop_checkproduct($productid,$row2["products"])) {
						$productprices = explode("|",$row2["paidproductprices"]);
						if ($row2["paidproductprices"] && is_array($productprices)) foreach ($productprices as $pricepart) {
							$thisproductprice = explode(":",$pricepart);
							if ($thisproductprice[0] == $productid) $productprice = $thisproductprice[1];
						} else if ($row2["wholesale"]) {
							$wspriceresult = @mysqli_query($db, "SELECT wholesaleprice FROM product WHERE productid='$productid'");
							$productprice = @mysqli_result($wspriceresult, 0, "wholesaleprice");
						} else $productprice = 0;
						$thisprice = $productprice;
						$totalqty += $thisquantity;
						$subtotal += $thisprice;
						$totalitems += $thisquantity;
						$totalamount += $thisprice;
						$orderid = $row2["orderid"];
						$invoiceid = $row2["invoiceid"];
						$paid = $row2["paid"];
						$processresult = @mysqli_query($db, "SELECT * FROM paymentinfo WHERE orderid='$orderid'");
						if (@mysqli_num_rows($processresult) && $userid == "1") $processlink = TRUE;
						else $processlink = FALSE;
						$timedate = explode(" ", $row2["date"]);
						$date = $timedate[0];
						$time = explode(":",$timedate[1]);
						$comment = trim($row2["comment"]);
						$comment = un_html($comment);
						if ($defaultenclosure == "\"" && strstr($comment,"\"")) $comment = str_replace("\"","\"\"",$comment);
						$thiscustomerid = $row2["$customeridfield"];
						$customername = $row2["firstname"]." ".$row2["lastname"];
						$customername = trim($customername);
						$customeremail = trim($row2["email"]);
						$sql = "SELECT affiliate.affiliateid, affiliate.firstname, affiliate.lastname, affiliate.email FROM $orderafftable, affiliate WHERE $orderafftable.orderid=$orderid AND $orderafftable.affiliateid=affiliate.affiliateid AND ($orderafftable.secondtier=0 OR $orderafftable.secondtier IS NULL)";
						$result3 = @mysqli_query($db, "$sql");
						$affiliatename = @mysqli_result($result3, 0, "firstname")." ".@mysqli_result($result3, 0, "lastname");
						$affiliatename = trim($affiliatename);
						$affiliateemail = @mysqli_result($result3, 0, "email");
						$affiliateid = @mysqli_result($result3, 0, "affiliateid");
						if ($row2["wholesale"]) $ws = " W";
						else if (!$paid && $reporttype != "unpaid") $ws = " U";
						else $ws = "";
						if (!empty($invoiceid)) $orderid = $invoiceid;
						echo "$orderid$ws{$defaultdelimiter}$date{$defaultdelimiter}{$defaultenclosure}$time[0]:$time[1]{$defaultenclosure}{$defaultdelimiter}$thisquantity{$defaultdelimiter}{$defaultenclosure}".number_format($thisprice,$showdecimals,$decimalchar,$thousandchar)."{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$productid:$productname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$thiscustomerid:$customername [$customeremail]{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}";
						if ($reporttype != "chargebacks" && $affiliateid) echo "$affiliateid:$affiliatename";
						else echo "$comment";
						echo "{$defaultenclosure}\r\n";
					}
				}
			}
		}

	// Other downloadable report...
	} else {
		if ($reporttype == "paid") $paidstring = "paid != ''";
		else if ($reporttype == "unpaid") $paidstring = "paid = ''";
		$sql="SELECT * FROM $ordertable WHERE";
		if ($transtype != "wholesale" && $user > 1) $sql .= " userid $usercheck AND";
		$sql .= " date >= '$startdate' AND date <= '$todate'";
		if ($ordertypesql && $ordertable != "memberorders") $sql .= $ordertypesql;
		if ($paidcheck && $paidstring) $sql .= " AND $paidstring";
		if ($reporttype == "chargebacks") $sql .= " AND reference != '' AND reference IS NOT NULL AND price < 0";
		$sql .= " ORDER BY $orderby $ascdesc";
		$result = @mysqli_query($db, "$sql");
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$orderid = @mysqli_result($result, $i, "orderid");
			$invoiceid = @mysqli_result($result, $i, "invoiceid");
			$ipnumber = @mysqli_result($result, $i, "ip");
			$transactionid = @mysqli_result($result, $i, "remoteorderid");
			$payoptionid = @mysqli_result($result, $i, "payoptionid");
			$price = @mysqli_result($result, $i, "price");
			$discount = @mysqli_result($result, $i, "discount");
			$virtualcash = @mysqli_result($result, $i, "virtualcash");
			$discount += $virtualcash;
			$tax = @mysqli_result($result, $i, "tax");
			$wholesale = @mysqli_result($result, $i, "wholesale");
			$paid = @mysqli_result($result, $i, "paid");
			if (substr($tax,0,1) == "c") {
				$taxarray = explode("|",$tax);
				$gst = $taxarray[1];
				$pst = $taxarray[2];
				$tax = "";
			} else {
				$gst = "";
				$pst = "";
			}
			$shipping = @mysqli_result($result, $i, "shipping");
			if (empty($shipping)) $shipping = 0.00;
			$reference = @mysqli_result($result, $i, "reference");
			$result2 = @mysqli_query($db, "SELECT * FROM paymentinfo WHERE orderid='$orderid'");
			if (@mysqli_num_rows($result2) && $userid == "1") $processlink = TRUE;
			else $processlink = FALSE;
			$subtotal += $price;
			$timedate = explode(" ", @mysqli_result($result, $i, "date"));
			$date = $timedate[0];
			$time = explode(":",$timedate[1]);
			$descriptionstring = trim(@mysqli_result($result, $i, "description"));
			$descriptionstring = un_html($descriptionstring);
			$descriptionstring = str_replace("\r\n",",",$descriptionstring);
			$descriptionstring = str_replace("\n",",",$descriptionstring);
			if ($defaultenclosure == "\"" && strstr($descriptionstring,"\"")) $descriptionstring = str_replace("\"","\"\"",$descriptionstring);
			if ($reference && $price < 0) {
				$referenceresult = @mysqli_query($db, "SELECT invoiceid FROM orders WHERE orderid='$reference'");
				$referenceid = @mysqli_result($referenceresult, 0, "invoiceid");
				if (empty($referenceid)) $referenceid = $reference;
				$descriptionstring = CHARGEBACKFOR." $referenceid, $descriptionstring";
			}
			$handlingarray = ashop_gethandlingcost(@mysqli_result($result, $i, "products"));
			$totalhandling = $handlingarray["shipping"] + $handlingarray["salestax"] - $handlingarray["discount"];
			$comment = @mysqli_result($result, $i, "comment");
			$thiscustomerid = @mysqli_result($result, $i, "$customeridfield");
			$sql = "SELECT * FROM $customertable WHERE $customeridfield=$thiscustomerid";
			$result3 = @mysqli_query($db, "$sql");
			$customerfirstname = trim(@mysqli_result($result3, 0, "firstname"));
			$customerlastname = trim(@mysqli_result($result3, 0, "lastname"));
			$customeremail = trim(@mysqli_result($result3, 0, "email"));
			$customeraddress = trim(@mysqli_result($result3, 0, "address"));
			$customeraddress = un_html($customeraddress);
			$customeraddress = str_replace("\r\n",",",$customeraddress);
			$customeraddress = str_replace("\n",",",$customeraddress);
			if ($defaultenclosure == "\"" && strstr($customeraddress,"\"")) $customeraddress = str_replace("\"","\"\"",$customeraddress);
			$customercity = trim(@mysqli_result($result3, 0, "city"));
			$customerzip = trim(@mysqli_result($result3, 0, "zip"));
			$customerstate = trim(@mysqli_result($result3, 0, "state"));
			$customercountry = trim(@mysqli_result($result3, 0, "country"));
			$customerphone = trim(@mysqli_result($result3, 0, "phone"));
			$sql = "SELECT affiliate.affiliateid, affiliate.firstname, affiliate.lastname, affiliate.email FROM $orderafftable, affiliate WHERE $orderafftable.orderid=$orderid AND $orderafftable.affiliateid=affiliate.affiliateid AND ($orderafftable.secondtier=0 OR $orderafftable.secondtier IS NULL)";
			$result4 = @mysqli_query($db, "$sql");
			$affiliatename = @mysqli_result($result4, 0, "firstname")." ".@mysqli_result($result4, 0, "lastname");
			$affiliatename = trim($affiliatename);
			$affiliateemail = trim(@mysqli_result($result4, 0, "email"));
			$affiliateid = @mysqli_result($result4, 0, "affiliateid");
			$result5 = @mysqli_query($db, "SELECT name FROM payoptions WHERE payoptionid='$payoptionid'");
			$paymentmethod = trim(@mysqli_result($result5, 0, "name"));
			if ($wholesale) $ws = " W";
			else if (!$paid && $reporttype != "unpaid") $ws = " U";
			else $ws = "";
			if (!empty($invoiceid)) $orderid = $invoiceid;
			echo "$orderid$ws{$defaultdelimiter}{$defaultenclosure}$transactionid{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$paymentmethod{$defaultenclosure}{$defaultdelimiter}$date{$defaultdelimiter}{$defaultenclosure}$time[0]:$time[1]{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$descriptionstring{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}".number_format($price+$discount-$shipping-$tax-$gst-$pst,$showdecimals,$decimalchar,$thousandchar).$defaultenclosure.$defaultdelimiter.$defaultenclosure.number_format($shipping,$showdecimals,$decimalchar,$thousandchar).$defaultenclosure.$defaultdelimiter.$defaultenclosure;
			if ($tax) echo number_format($tax,$showdecimals,$decimalchar,$thousandchar);
			else if ($gst || $pst) {
				if ($gst) echo number_format($gst,$showdecimals,$decimalchar,$thousandchar)." GST";
				if ($pst) echo " ".number_format($pst,$showdecimals,$decimalchar,$thousandchar)." PST";
			} else echo "0{$decimalchar}00";
			echo $defaultenclosure.$defaultdelimiter.$defaultenclosure.number_format($price,$showdecimals,$decimalchar,$thousandchar)."{$defaultenclosure}{$defaultdelimiter}$thiscustomerid{$defaultdelimiter}{$defaultenclosure}$customerfirstname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customerlastname{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customeremail{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customeraddress{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customercity{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customerzip{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customerstate{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customercountry{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$customerphone{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}$ipnumber{$defaultenclosure}{$defaultdelimiter}{$defaultenclosure}";
			if ($reporttype != "chargebacks" && $affiliateid) echo "$affiliateid: $affiliatename";
			else echo "$comment";
			echo "{$defaultenclosure}\r\n";
		}
	}

// Show "Please wait" page while completing the search...
} else if (!$showresult) {
	echo "$header<div class=\"heading\">".GENERATINGREPORT."</div>";
	foreach ($_POST as $field => $value) $getquerystring .= "&$field=$value";
	foreach ($_GET as $field => $value) $getquerystring .= "&$field=$value";
	echo "<meta http-equiv=\"Refresh\" content=\"0; URL=salesreport.php?showresult=true$getquerystring\"></table></center>$footer";
	exit;
}


// Show report in browser...	
else if ($orderby != "productid" && $orderby != "dproductid" && $orderby != "pproductid") {
	ob_start();
	$ordertable = "orders";
	$customertable = "customer";
	$customeridfield = "customerid";
	$paidcheck = " AND paid != ''";
	if ($transtype == "wholesale") $ordertypesql = " AND wholesale = '1' AND (source != 'Auction' OR source IS NULL)";
	else if ($transtype == "auction") $ordertypesql = " AND source = 'Auction'";
	else if ($transtype == "retail") $ordertypesql = " AND (wholesale IS NULL OR wholesale != '1') AND (source != 'Auction' OR source IS NULL)";
	if ($shippingstatus == "shipped") $shippingstatussql = " AND NOT EXISTS (SELECT * FROM shippingstatus WHERE shippingstatus.orderid=$ordertable.orderid AND status='0' LIMIT 1)";
	else if ($shippingstatus == "unshipped") $shippingstatussql = " AND EXISTS (SELECT * FROM shippingstatus WHERE shippingstatus.orderid=$ordertable.orderid AND status='0' LIMIT 1)";
	else $shippingstatussql = "";
	if ($customerid) {
		$sql="SELECT * FROM $customertable WHERE $customeridfield=$customerid";
		$result = @mysqli_query($db, "$sql");
		$customername = @mysqli_result($result, 0, "firstname")." ".@mysqli_result($result, 0, "lastname");
		$customeremail = @mysqli_result($result, 0, "email");
		$customerstring = " ".THEWORDFOR." $customername, ".CUSTOMERID." $customerid ";
	}
	if (($memberid > 1 && ($userid == "1" || $userid == $memberid)) || $user > 1) {
		$ordertable = "memberorders";
		$usercheck = "='$user'";
		$sql="SELECT * FROM user WHERE userid='$user'";
		$result = @mysqli_query($db, $sql);
		$membername = @mysqli_result($result, 0, "shopname");
		$memberemail = @mysqli_result($result, 0, "email");
		$memberstring = " ".THEWORDFOR." $membername, ".MEMBERID." $user";
		$commissionlevel = @mysqli_result($result, 0, "commissionlevel");
		if (!$commissionlevel) $commissionlevel = $memberpercent;
	} else $usercheck = "LIKE '%|$user|%'";
	if ($reporttype == "paid" || $reporttype == "chargebacks" || $reporttype == "all") $orderafftable = "orderaffiliate";
	else $orderafftable = "pendingorderaff";
	echo "$header
	<div class=\"heading\">".SALESREPORT;
	if ($userid == "1") echo " <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a>";
	echo "</div><center>";
	if ($customerstring) {
		if ($reporttype == "wholesale") $editcustomer = "edituser";
		else $editcustomer = "editcustomer";
		echo "<span class=\"heading\"><font size=\"2\">$customerstring <a href=\"$editcustomer.php?customerid=$customerid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>";
		if ($userid == "1") echo "&nbsp;<a href=\"$editcustomer.php?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a>";
		echo "</font></span><br>";
	} else if ($memberstring) {
		echo "<span class=\"heading\"><font size=\"2\">$memberstring <a href=\"editmember.php?memberid=$user\"><img src=\"images/icon_profile.gif\" alt=\"".MEMBERPROFILEFOR." $user\" title=\"".MEMBERPROFILEFOR." $user\" border=\"0\"></a>";
		if ($userid == "1") echo "&nbsp;<a href=\"editmember.php?memberid=$user&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEMEMBER." $user ".FROMDB."\" title=\"".DELETEMEMBER." $user ".FROMDB."\" border=\"0\"></a>";
		echo "</font></span><br>";
	}
	if ($msg == "deleted") echo "<p align=\"center\" class=\"confirm\">".ORDERDELETED."</p>";
	else if ($msg == "activated") echo "<p align=\"center\" class=\"confirm\">".ORDERACTIVATIONSENT."</p>";
	if ($msg == "remindersent") echo "<p align=\"center\" class=\"confirm\">".REMINDERSENT."</p>";
	else echo "<br>";
	echo "<span class=\"formtitle\">";
	if ($transtype == "wholesale") $ordertoptext = WHOLESALE." ";
	else $ordertoptext = "";
	if ($transtype == "auction") $ordertoptext2 = AUCTIONS;
	else $ordertoptext2 = ORDERS;
	if ($shippingstatus == "shipped") $ordertoptext = SHIPPED." {$ordertoptext}";
	else if ($shippingstatus == "unshipped") $ordertoptext = UNSHIPPED." {$ordertoptext}";
	if ($reporttype == "paid") echo PAID." {$ordertoptext}".$ordertoptext2;
	else if ($reporttype == "unpaid") echo UNPAID." {$ordertoptext}".$ordertoptext2;
	else if ($reporttype == "chargebacks") echo "{$ordertoptext}".CHARGEBACKS;
	else echo "{$ordertoptext}".TRANSACTIONS;
	$startdate = "$startyear-$startmonth-$startday 00:00:00";
	$todate = "$toyear-$tomonth-$today 23:59:59";
	$subtotal = 0;
	if (!$customerid && !$orderid && $memberid <= "1") echo " - ".SMALLFROM." $startdate ".TO." $todate, ".ORDEREDBY,": ";
	if ($orderby == "date") echo THEWORDDATE;
	else if ($orderby == "price") echo AMOUNT;
	else if ($orderby == "customerid") echo CUSTOMERID;
	else if ($orderby == "orderid") echo ORDERID;
	echo "</span> 
	<table width=\"96%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\" align=\"center\" bgcolor=\"#C0C0C0\">
	<tr class=\"reporthead\"><td nowrap align=\"left\">".THEWORDDATE."</td>
	<td nowrap align=\"left\">".ORDERID."</td>
	<td align=\"left\">".PRODUCTS."</td><td align=\"center\">".AMT."</td>";
	if (($memberid > 1 && $userid == "1") || $user > 1) echo "<td align=\"center\">".DISC."</td><td align=\"center\">".SHOPFEE."</td><td align=\"center\">".TOTAL."</td>";
	else echo "<td align=\"center\">".SHP."</td><td align=\"center\">".TAX1."</td><td align=\"center\">".TAX2."</td><td align=\"center\">".DISC."</td><td align=\"center\">".TOTAL."</td>";
	if (!$customerid) echo "<td align=\"left\">".CUSTOMER."</td>";
	if ($userid == "1" && ($transtype != "wholesale" || $wholesaleaffiliate) || ($transtype == "wholesale" && $generate == "Edit")) echo "<td align=\"center\" nowrap>";
	if ($generate != "Edit" && $userid == "1" && ($transtype != "wholesale" || $wholesaleaffiliate)) echo REFERRED;
	else echo "&nbsp;";
	if ($userid == "1" && ($transtype != "wholesale" || $wholesaleaffiliate) || ($transtype == "wholesale" && $generate == "Edit")) echo "</td>";
	if ($customerid && ($transtype != "wholesale" || $wholesaleaffiliate)) echo "<td>&nbsp;</td>";
	if (($reporttype == "unpaid" || $reporttype == "all") && $generate != "Edit") echo "<td>&nbsp;</td>";
	echo "</tr>";

	// Get order information from database...
	if ($customerid) {
		$sql="SELECT * FROM $ordertable WHERE $customeridfield='$customerid'";
		if ($transtype != "wholesale" && $user > 1) $sql .= " AND userid $usercheck";
		if ($ordertypesql) $sql .= $ordertypesql;
		if ($shippingstatussql) $sql .= $shippingstatussql;
		$sql .= " AND date != '' ORDER BY date";
	} else if ($orderid) {
		if ($lookup == "true") {
			if ($userid == "1") $sql="SELECT * FROM $ordertable WHERE invoiceid='$orderid'";
			else $sql= "SELECT * FROM $ordertable WHERE invoiceid='$orderid' AND userid $usercheck";
		} else {
			if ($userid == "1") $sql="SELECT * FROM $ordertable WHERE orderid='$orderid'";
			else $sql= "SELECT * FROM $ordertable WHERE orderid='$orderid' AND userid $usercheck";
		}
	}
	else if ($memberid > 1 && ($userid == "1" || $userid == $memberid)) $sql="SELECT * FROM $ordertable WHERE userid $usercheck AND date !=''$paidcheck ORDER BY date";
	else if ($orderby == "affiliate") {
		if ($reporttype == "paid") $paidstring = "$ordertable.paid != ''";
		else if ($reporttype == "unpaid") $paidstring = "$ordertable.paid = ''";
		$sql="SELECT * FROM $ordertable, $orderafftable, affiliate WHERE $ordertable.orderid=$orderafftable.orderid AND $orderafftable.affiliateid=affiliate.affiliateid AND";
		if ($transtype != "wholesale" && $user > 1) $sql .= " $ordertable.userid $usercheck AND";
		$sql .= " $ordertable.date >= '$startdate' AND $ordertable.date <= '$todate'";
		if ($paidcheck && $paidstring) $sql .= " AND $paidstring";
		if ($reporttype == "chargebacks") $sql .= " AND $ordertable.reference != '' AND $ordertable.reference IS NOT NULL AND $ordertable.price < 0";
		if ($ordertypesql && $userid == "1") $sql .= $ordertypesql;
		if ($shippingstatussql) $sql .= $shippingstatussql;
		if ($userid > "1") {
			if ($transtype == "auction") $sql .= " AND auction='1'";
			else $sql .= " AND (auction='0' OR auction IS NULL)";
		}
		$sql .= " ORDER BY affiliate.lastname, $ordertable.date, $ordertable.orderid $ascdesc";
	}
	else {
		if ($reporttype == "paid") $paidstring = "paid != ''";
		else if ($reporttype == "unpaid") $paidstring = "paid = ''";
		$sql="SELECT * FROM $ordertable WHERE";
		if ($transtype != "wholesale" && $user > 1) $sql .= " userid $usercheck AND";
		$sql .= " date >= '$startdate' AND date <= '$todate'";
		if ($paidcheck && $paidstring) $sql .= " AND $paidstring";
		if ($reporttype == "chargebacks") $sql .= " AND reference != '' AND reference IS NOT NULL AND price < 0";
		if ($ordertypesql && $userid == "1") $sql .= $ordertypesql;
		if ($shippingstatussql) $sql .= $shippingstatussql;
		if ($userid > "1") {
			if ($transtype == "auction") $sql .= " AND auction='1'";
			else $sql .= " AND (auction='0' OR auction IS NULL)";
		}
		$sql .= " ORDER BY $orderby $ascdesc";
	}
	$result = @mysqli_query($db, "$sql");
	$rowcolor = "#E0E0E0";
	$affiliatecommission = 0.00;
	$paidaffiliatecommission = 0.00;
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$orderid = @mysqli_result($result, $i, "orderid");
		$invoiceid = @mysqli_result($result, $i, "invoiceid");
		$source = @mysqli_result($result, $i, "source");
		$remoteorderid = @mysqli_result($result, $i, "remoteorderid");
		$paid = @mysqli_result($result, $i, "paid");
		$reference = @mysqli_result($result, $i, "reference");
		if ($paid && !$reference) $ordertype = "paid";
		else if (!$reference) $ordertype = "unpaid";
		else $ordertype = "chargebacks";
		$price = @mysqli_result($result, $i, "price");
		$tax = @mysqli_result($result, $i, "tax");
		$wholesale = @mysqli_result($result, $i, "wholesale");
		$purchaseorder = @mysqli_result($result, $i, "purchaseorder");
		$payoptionid = @mysqli_result($result, $i, "payoptionid");
		$checkpayoptionresult = @mysqli_query($db, "SELECT payoptionid FROM payoptions WHERE gateway='googleco' AND payoptionid='$payoptionid'");
		if (@mysqli_num_rows($checkpayoptionresult) && !empty($remoteorderid)) $gcorderid = "<br>Google Order:<br><a href=\"https://checkout.google.com/sell/multiOrder?order=$remoteorderid\" target=\"_blank\">$remoteorderid</a>";
		else $gcorderid = "";
		if (substr($tax,0,1) == "c") {
			$taxarray = explode("|",$tax);
			$gst = $taxarray[1];
			$pst = $taxarray[2];
			$tax = "";
		} else {
			$gst = "";
			$pst = "";
		}
		$discount = @mysqli_result($result, $i, "discount");
		$storediscount = @mysqli_result($result, $i, "storediscount");
		$storediscountresult = @mysqli_query($db, "SELECT code FROM storediscounts WHERE discountid='$storediscount'");
		$storediscountcode = @mysqli_result($storediscountresult, 0, "code");
		$virtualcash = @mysqli_result($result, $i, "virtualcash");
		$discount += $virtualcash;
		$discounttotal += $discount;
		$shipping = @mysqli_result($result, $i, "shipping");
		if ($memberid > 1 || $user > 1) {
			$baseprice = $price;
			$membercommission = $baseprice * ($commissionlevel/100);
			$totalcommission += $membercommission;
			$shopfee = $price - $membercommission;
			$totalshopfee += $shopfee;
			if (@mysqli_result($result, $i, "paidtoshop")) $totalpaidtoshop += $membercommission;
			else $totalowedtoshop += $membercommission;
		}
		if ($transtype == "wholesale") $result2 = @mysqli_query($db, "SELECT * FROM wholesalepaymentinfo WHERE orderid='$orderid'");
		else $result2 = @mysqli_query($db, "SELECT * FROM paymentinfo WHERE orderid='$orderid'");
		if (@mysqli_num_rows($result2) && $userid == "1") $processlink = TRUE;
		else $processlink = FALSE;
		$checkchargebackresult = @mysqli_query($db, "SELECT * FROM orders WHERE reference='$orderid' AND price < 0");
		if (@mysqli_num_rows($checkchargebackresult)) $checkchargeback = TRUE;
		else $checkchargeback = FALSE;
		$totalamt += $price+$discount-$tax-$gst-$pst-$shipping;
		$subtotal += $price;
		$tax1total += $tax;
		$tax1total += $gst;
		$tax2total += $pst;
		$shiptotal += $shipping;
		$timedate = explode(" ", @mysqli_result($result, $i, "date"));
		$date = $timedate[0];
		$time = explode(":",$timedate[1]);
		$descriptionstring = @mysqli_result($result, $i, "description");
		if ($reference) {
			$referenceresult = @mysqli_query($db, "SELECT invoiceid FROM orders WHERE orderid='$reference'");
			$referenceid = @mysqli_result($referenceresult, 0, "invoiceid");
			if (empty($referenceid)) $referenceid = $reference;
			$comment = str_replace("\r\n", " ", @mysqli_result($result, $i, "comment"));
			$comment = str_replace("\n", " ", $comment);
			$comment = str_replace("\r", " ", $comment);
			$comment = str_replace("'", "&#039;", $comment);
			if ($comment) $descriptionstring = "<a href=\"javascript: void(0)\" onMouseOver=\"window.status='$comment'; return true;\" onMouseOut=\"window.status=window.defaultStatus;\"><img src=\"images/icon_info.gif\" alt=\"$comment\" title=\"$comment\" border=\"0\"></a> <font color=\"#FF0000\">".CHARGEBACKFOR." $referenceid</font><br>$descriptionstring";
			else $descriptionstring = "<font color=\"#FF0000\">".CHARGEBACKFOR." $referenceid</font><br>$descriptionstring";
		}
		$displaydescr = "";
		$descrarray = array();
		if (strpos($descriptionstring,",")) $descrarray = explode(",", $descriptionstring);
		else if (strpos($descriptionstring,"\r\n")) $descrarray = explode("\r\n", $descriptionstring);
		else $displaydescr = $descriptionstring;
		if (is_array($descrarray)) foreach ($descrarray as $linenumber=>$line) {
			if (substr($line,0,1) == "-") $line = "<font color=\"#FF0000\">$line</font>";
			$displaydescr .= $line;
			if ($linenumber < count($descrarray)) $displaydescr .= "<br>";
		}
		$handlingarray = ashop_gethandlingcost(@mysqli_result($result, $i, "products"));
		$totalhandling = $handlingarray["shipping"] + $handlingarray["salestax"] - $handlingarray["discount"];
		$thiscustomerid = @mysqli_result($result, $i, "$customeridfield");
		$sql = "SELECT * FROM $customertable WHERE $customeridfield=$thiscustomerid";
		$result3 = @mysqli_query($db, "$sql");
		$customername = @mysqli_result($result3, 0, "firstname")." ".@mysqli_result($result3, 0, "lastname");
		$customeremail = @mysqli_result($result3, 0, "email");
		if ($generate != "Edit") {
			$sql = "SELECT affiliate.affiliateid, affiliate.firstname, affiliate.lastname, affiliate.email, $orderafftable.commission";
			if ($reporttype != "unpaid") $sql .= ", $orderafftable.paid";
			$sql .= " FROM $orderafftable, affiliate WHERE $orderafftable.orderid=$orderid AND $orderafftable.affiliateid=affiliate.affiliateid AND ($orderafftable.secondtier=0 OR $orderafftable.secondtier IS NULL)";
			$result4 = @mysqli_query($db, "$sql");
			$affiliatename = @mysqli_result($result4, 0, "firstname")." ".@mysqli_result($result4, 0, "lastname");
			$affiliateemail = @mysqli_result($result4, 0, "email");
			$affiliateid = @mysqli_result($result4, 0, "affiliateid");
			$sql = "SELECT commission";
			if ($reporttype != "unpaid") $sql .= ", paid";
			$sql .= " FROM $orderafftable WHERE orderid='$orderid'";
			$result4 = @mysqli_query($db, "$sql");
			while ($affiliatecommissionrow = @mysqli_fetch_array($result4)) {
				$affiliatecommission += $affiliatecommissionrow["commission"];
				if ($affiliatecommissionrow["paid"]) $paidaffiliatecommission += $affiliatecommissionrow["commission"];
			}
		}
		// Check download stats...
		$totaldownloads = 0;
		$downloadsresult = @mysqli_query($db, "SELECT SUM(downloads) AS totaldownloads FROM orderdownloads WHERE orderid='$orderid'");
		$downloadsresult2 = @mysqli_query($db, "SELECT fileid FROM downloadslog WHERE orderid='$orderid'");
		if (@mysqli_num_rows($downloadsresult)) $totaldownloads = @mysqli_result($downloadsresult,0,"totaldownloads");
		if (empty($totaldownloads)) $totaldownloads = 0;
		if ((!empty($totaldownloads) && $totaldownloads > 0) || @mysqli_num_rows($downloadsresult2)) {
			$downloadsstring = " <a href=\"downloadsreport.php?orderid=$orderid\"><img src=\"images/icon_downloadable.gif\" alt=\"$totaldownloads Downloads\" title=\"$totaldownloads Downloads\" style=\"vertical-align: middle;\" /></a>";
		} else $downloadsstring = "";

		echo "<tr class=\"reportlinesm\" valign=\"top\" bgcolor=\"$rowcolor\"><td nowrap align=\"left\">$date<br>{$time[0]}:{$time[1]}</td><td align=\"left\">";
		if ($wholesale) {
			$editcustomer = "edituser";
			$ws = "&nbsp;".WHOLESALELETTER;
			if ($purchaseorder) $ws .= "</a> <a href=\"javascript: void(0)\" onMouseOver=\"window.status='$purchaseorder'; return true;\" onMouseOut=\"window.status=window.defaultStatus;\"><img src=\"images/icon_info.gif\" alt=\"$purchaseorder\" title=\"$purchaseorder\" border=\"0\">";
		} else {
			$editcustomer = "editcustomer";
			$ws = "";
		}
		if (empty($invoiceid)) {
			if ($ordertable == "memberorders") {
				$invoiceidresult = @mysqli_query($db, "SELECT invoiceid FROM orders WHERE orderid='$orderid'");
				if (@mysqli_num_rows($invoiceidresult)) $invoiceid = @mysqli_result($invoiceidresult,0,"invoiceid");
				if (empty($invoiceid)) $invoiceid = $orderid;
			} else $invoiceid = $orderid;
		}
		if ($userid == "1" && file_exists("$ashoppath/emerchant/invoices/$orderid") && $source == "eM: Invoice") echo "<a href=\"getinvoice.php?orderid=$orderid\" target=\"_blank\">$invoiceid$ws</a>$gcorderid$downloadsstring";
		else if ($reporttype == "unpaid" && $userid == "1" && file_exists("$ashoppath/emerchant/invoices/$orderid") && $source == "Auction") echo "<a href=\"getinvoice.php?orderid=$orderid\" target=\"_blank\">$invoiceid$ws</a>$gcorderid$downloadsstring";
		else if ($userid == "1" && file_exists("$ashoppath/admin/receipts/$orderid")) echo "<a href=\"getreceipt.php?orderid=$orderid\" target=\"_blank\">$invoiceid$ws</a>$gcorderid$downloadsstring";
		else echo "$invoiceid$ws$gcorderid$downloadsstring";
		echo "</td><td align=\"left\">$displaydescr</td><td align=\"right\">";
		if ($price < 0) echo "<font color=\"#FF0000\">";
		if ($ordertable == "memberorders") echo number_format($price,$showdecimals,$decimalchar,$thousandchar);
		else echo number_format($price+$discount-$shipping-$tax-$gst-$pst,$showdecimals,$decimalchar,$thousandchar);
		if ($price < 0) echo "</font>";
		echo "</td>";
		if ($ordertable == "memberorders") {
			echo "<td align=\"right\">";
			if ($reference && $price < 0) echo "<font color=\"#FF0000\">";
			echo "-".number_format($discount,$showdecimals,$decimalchar,$thousandchar);
			if ($reference && $price < 0) echo "</font>";
			echo "</td><td align=\"right\">";
			if ($shopfee < 0 || ($reference && $price < 0)) echo "<font color=\"#FF0000\">-".number_format($shopfee,$showdecimals,$decimalchar,$thousandchar)."</font>";
			else echo "-".number_format($shopfee,$showdecimals,$decimalchar,$thousandchar);
			echo "</td><td align=\"right\">";
			if ($reference && $price < 0) echo "<font color=\"#FF0000\">".number_format($membercommission,$showdecimals,$decimalchar,$thousandchar)."</font>";
			else echo number_format($membercommission,$showdecimals,$decimalchar,$thousandchar);
			echo "</td>";
		} else {
			echo "<td align=\"right\">";
			if (empty($shipping)) $shipping = 0.00;
			if ($shipping < 0 || ($reference && $price < 0)) echo "<font color=\"#FF0000\">";
			echo number_format($shipping,$showdecimals,$decimalchar,$thousandchar);
			if ($shipping < 0 || ($reference && $price < 0)) echo "</font>";
			echo "</td><td align=\"right\">";
			if ($tax < 0 || $gst < 0 || ($reference && $price < 0)) echo "<font color=\"#FF0000\">";
			if ($tax) echo number_format($tax,$showdecimals,$decimalchar,$thousandchar);
			else if ($gst) echo number_format($gst,$showdecimals,$decimalchar,$thousandchar);
			else echo number_format(0,$showdecimals,$decimalchar,$thousandchar);
			if ($tax < 0 || $gst < 0 || ($reference && $price < 0)) echo "</font>";
			echo "</td><td align=\"right\">";
			if (empty($pst)) $pst = 0.00;
			if ($pst < 0 || ($reference && $price < 0)) echo "<font color=\"#FF0000\">";
			echo number_format($pst,$showdecimals,$decimalchar,$thousandchar);
			if ($pst < 0 || ($reference && $price < 0)) echo "</font>";
			echo "</td><td align=\"right\">";
			if ($reference && $price < 0) echo "<font color=\"#FF0000\">";
			if (!empty($storediscountcode)) echo "$storediscountcode:<br>";
			echo "-".number_format($discount,$showdecimals,$decimalchar,$thousandchar);
			if ($reference && $price < 0) echo "</font>";
			echo "</td><td align=\"right\">";
			if ($price < 0) echo "<font color=\"#FF0000\">".number_format($price,$showdecimals,$decimalchar,$thousandchar)."</font>";
			else echo number_format($price,$showdecimals,$decimalchar,$thousandchar);
			echo "</td>";
		}
		if (($userid == "1" || $dmshowcustomers) && !$customerid) echo "<td nowrap align=\"left\">$thiscustomerid: <a href=\"$editcustomer.php?customerid=$thiscustomerid\">$customername</a></td>";
		else if (!$customerid) echo "<td nowrap>$thiscustomerid: $customername</td>";
		if ($userid == "1" && ($transtype != "wholesale" || $wholesaleaffiliate) || ($transtype == "wholesale" && $generate == "Edit")) echo "<td align=\"center\">";
		if ($affiliateid && $userid == "1" && $generate != "Edit" && ($transtype != "wholesale" || $wholesaleaffiliate)) {
			echo "$affiliateid: <a href=\"affiliatedetail.php?affiliateid=$affiliateid\">$affiliatename";
		} else if ($userid == "1" && $generate == "Edit") {
			if ($ordertype == "paid" && !$reference) {
				echo "<a href=\"editsales.php?orderid=$orderid&action=updatestatus&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_edit.gif\" alt=\"".UPDATESTATUS."\" title=\"".UPDATESTATUS."\" border=\"0\"></a> <a href=\"editsales.php?orderid=$orderid&action=chargeback&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_chargeback.gif\" alt=\"".CHARGEBACKORDER."\" title=\"".CHARGEBACKORDER."\" border=\"0\"></a>&nbsp;<a href=\"reactivate.php?orderid=$orderid\"><img src=\"images/icon_reactivate.gif\" alt=\"".REACTIVATEORDER."\" title=\"".REACTIVATEORDER."\" border=\"0\"></a>";
			} else if ($ordertype == "unpaid") {
				if ($processlink) echo "<a href=\"$ashopsurl/admin/process.php?sesid=$sesid&orderid=$orderid&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_process.gif\" alt=\"".VIEWCREDITCARDANDACTIVATE."\" title=\"".VIEWCREDITCARDANDACTIVATE."\" border=\"0\"></a> <a href=\"editsales.php?action=delete&orderid=$orderid\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEORDER."\" title=\"".DELETEORDER."\" border=\"0\"></a>";
				else echo "<a href=\"editsales.php?orderid=$orderid&action=modify&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_edit.gif\" alt=\"".MODIFYORDER."\" title=\"".MODIFYORDER."\" border=\"0\"></a> <a href=\"activate.php?orderid=$orderid&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_activatem.gif\" alt=\"".RECORDPAYMENTANDACTIVATE."\" title=\"".RECORDPAYMENTANDACTIVATE."\" border=\"0\"></a> <a href=\"salesadmin.php?remind=$orderid&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_remind.gif\" alt=\"".REMINDCUSTOMER."\" title=\"".REMINDCUSTOMER."\" border=\"0\"></a> <a href=\"editsales.php?action=delete&orderid=$orderid&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEORDER."\" title=\"".DELETEORDER."\" border=\"0\"></a>";
			}
		} 
		if ($customerid && $paid) {
			echo "<td align=\"center\"><a href=\"editsales.php?orderid=$orderid&action=updatestatus&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_edit.gif\" alt=\"".UPDATESTATUS."\" title=\"".UPDATESTATUS."\" border=\"0\"></a> <a href=\"reactivate.php?orderid=$orderid\"><img src=\"images/icon_reactivate.gif\" alt=\"".REACTIVATEORDER."\" title=\"".REACTIVATEORDER."\" border=\"0\"></a></td>";
		} else if (!$paid && $generate != "Edit") echo "<td align=\"center\"><a href=\"activate.php?orderid=$orderid&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_activatem.gif\" alt=\"".RECORDPAYMENTANDACTIVATE."\" title=\"".RECORDPAYMENTANDACTIVATE."\" border=\"0\"></a></td>";
		else if ($reporttype == "all" && $generate != "Edit") echo "<td>&nbsp;</td>";

		if ($userid == "1") echo "</a></td>";
		echo "</tr>\n";
		if ($rowcolor == "#C0C0C0") $rowcolor = "#E0E0E0";
		else $rowcolor = "#C0C0C0";
	}

	echo "<tr class=\"reportheadsm\"><td colspan=\"3\" align=\"right\">".TOTALS.":</td>";
	if (($memberid > 1 && $user == "1") || $user > 1) {
		echo "<td align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($totalamt,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td nowrap align=\"right\">&nbsp;-".$currencysymbols[$ashopcurrency]["pre"].number_format($discounttotal,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td nowrap align=\"right\"> -".number_format($totalshopfee,$showdecimals,$decimalchar,$thousandchar)."</td>
		<td align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($totalcommission,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td>&nbsp;</td>";
		if (!$customerid && ($userid == "1" || $reporttype == "unpaid")) echo "<td>&nbsp;</td>";
	} else {
		echo "<td align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($totalamt,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td>
		<td align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($shiptotal,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($tax1total,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($tax2total,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td nowrap align=\"right\">&nbsp;-".$currencysymbols[$ashopcurrency]["pre"].number_format($discounttotal,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td><td nowrap align=\"right\">&nbsp;".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</td>";
		if (($transtype != "wholesale" || $wholesaleaffiliate) || !$customerid) echo "<td>&nbsp;</td>";
		if (($transtype != "wholesale" || $wholesaleaffiliate) || ($transtype == "wholesale" && $generate == "Edit")) echo "<td>&nbsp;</td>";
		if (($reporttype == "unpaid" || $reporttype == "all") && $generate != "Edit") echo "<td>&nbsp;</td>";
	}
	echo "</tr></table><br>";
	if ($userid > 1) {
		echo "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
		if ($totalpaidtoshop) echo TOTALPAIDTOYOU.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($totalpaidtoshop,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</b>";
		echo "<br>";
		if ($totalowedtoshop) echo TOTALOWEDTOYOU.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($totalowedtoshop,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</b>";
		echo "</font><br>";
	}
	if ($generate != "Edit") {
		echo "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".AFFILIATECOMMISSION.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($affiliatecommission,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</b>, ".PAIDSMALL.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($paidaffiliatecommission,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</b></font><br>";
	}
	echo "<br></center>$footer";


// Generate a sales report by product and show in browser...
} else if ($orderby == "productid" || $orderby == "dproductid" || $orderby == "pproductid") {
	ob_start();
	$ordertable = "orders";
	$customertable = "customer";
	$customeridfield = "customerid";
	$paidcheck = " AND paid != ''";
	if ($transtype == "wholesale") $ordertypesql = " AND orders.wholesale = '1' AND (orders.source != 'Auction' OR orders.source IS NULL)";
	else if ($transtype == "auction") $ordertypesql = " AND orders.source = 'Auction'";
	else if ($transtype == "retail") $ordertypesql = " AND (orders.wholesale IS NULL OR orders.wholesale != '1') AND (orders.source != 'Auction' OR orders.source IS NULL)";
	if ($shippingstatus == "shipped") $shippingstatussql = " AND NOT EXISTS (SELECT * FROM shippingstatus WHERE shippingstatus.orderid=$ordertable.orderid AND status='0' LIMIT 1)";
	else if ($shippingstatus == "unshipped") $shippingstatussql = " AND EXISTS (SELECT * FROM shippingstatus WHERE shippingstatus.orderid=$ordertable.orderid AND status='0' LIMIT 1)";
	else $shippingstatussql = "";
	if ($reporttype == "paid" || $reporttype == "chargebacks") $orderafftable = "orderaffiliate";
	else $orderafftable = "pendingorderaff";
	$startdate = "$startyear-$startmonth-$startday 00:00:00";
	$todate = "$toyear-$tomonth-$today 23:59:59";
	$totalamount = 0;
	$totalitems = 0;
	echo "$header
	<div class=\"heading\">".SALESREPORT;
	if ($userid == "1") echo " <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a>";
	echo "</div><center><span class=\"formtitle\">";
	if (!$productid) {
		if ($transtype == "wholesale") $ordertoptext = WHOLESALE." ";
		else $ordertoptext = "";
		if ($shippingstatus == "shipped") $ordertoptext = SHIPPED." {$ordertoptext}";
		else if ($shippingstatus == "unshipped") $ordertoptext = UNSHIPPED." {$ordertoptext}";
		if ($reporttype == "paid") echo PAID." {$ordertoptext}".ORDERS;
		else if ($reporttype == "unpaid") echo UNPAID." {$ordertoptext}".ORDERS;
		else if ($reporttype == "chargebacks") echo "{$ordertoptext}".CHARGEBACKS;
		else echo "{$ordertoptext}".TRANSACTIONS;
		echo " - ".SMALLFROM." $startdate ".TO." $todate, ".SUBTOTALEDBYPRODUCT."</span><br>";
		$productlimit = "";
	} else {
		if ($memberid > 1) {
			$sql="SELECT * FROM user WHERE userid='$memberid'";
			$result = @mysqli_query($db, $sql);
			$membername = @mysqli_result($result, 0, "shopname");
			$memberemail = @mysqli_result($result, 0, "email");
			$memberstring = " ".THEWORDFOR." $membername, ".MEMBERID." $user";
			echo "<p><span class=\"heading\"><font size=\"2\">$memberstring <a href=\"editmember.php?memberid=$user\"><img src=\"images/icon_profile.gif\" alt=\"".MEMBERPROFILEFOR." $user\" title=\"".MEMBERPROFILEFOR." $user\" border=\"0\"></a>";
			if ($userid == "1") echo "&nbsp;<a href=\"editmember.php?memberid=$user&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEMEMBER." $user ".FROMDB."\" title=\"".DELETEMEMBER." $user ".FROMDB."\" border=\"0\"></a>";
			echo "</font></span></p><span class=\"formtitle\">".PAIDORDERS."</span><br>";
		}
		$productlimit = " AND productid='$productid'";
	}
	if ($orderby == "productid" || $orderby == "pproductid") {
		if (($memberid > 1 && $userid == "1") || $user > 1) $result = @mysqli_query($db, "SELECT * FROM product WHERE userid='$user'$productlimit AND (prodtype!='content' OR prodtype IS NULL) ORDER BY name $ascdesc");
		else $result = @mysqli_query($db, "SELECT * FROM product WHERE (prodtype!='content' OR prodtype IS NULL)$productlimit ORDER BY name $ascdesc");
	} else if ($orderby == "dproductid") {
		if (($memberid > 1 && $userid == "1") || $user > 1) $result = @mysqli_query($db, "SELECT DISTINCT product.* FROM product, productfiles WHERE product.userid='$user' AND (product.prodtype!='content' OR prodtype IS NULL) AND product.productid=productfiles.productid ORDER BY product.name $ascdesc");
		else $result = @mysqli_query($db, "SELECT DISTINCT product.* FROM product, productfiles WHERE (product.prodtype!='content' OR prodtype IS NULL) AND product.productid=productfiles.productid ORDER BY product.name $ascdesc");
	}
	while($row=@mysqli_fetch_array($result)) {
		$productid=$row["productid"];
		$productname=$row["name"];
		if ($orderby == "pproductid") {
			$checkfiles = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
			if (@mysqli_num_rows($checkfiles)) $failedfilescheck = TRUE;
			else $failedfilescheck = FALSE;
		}
		if($productid && $productname && !$failedfilescheck) {
			if ($reporttype == "paid") $paidstring = "$ordertable.paid != ''";
			else if ($reporttype == "unpaid") $paidstring = "$ordertable.paid = ''";
			$sql = "SELECT $customertable.*, $ordertable.* FROM $customertable, $ordertable WHERE ($ordertable.products LIKE '%b$productid"."a%' OR $ordertable.products LIKE '%b$productid"."d%') AND $ordertable.$customeridfield = $customertable.$customeridfield";
			if (!$productlimit) $sql .= " AND $ordertable.date >= '$startdate' AND $ordertable.date <= '$todate'";
			if ($reporttype == "chargebacks") $sql .= " AND $ordertable.reference != '' AND $ordertable.reference AND $ordertable.price < 0";
			if ($paidcheck && $paidstring) $sql .= " AND $paidstring";
			$sql .= $ordertypesql;
			$sql .= $shippingstatussql;
			if ($userid > "1" && !$productid) {
				if ($transtype == "auction") $sql .= " AND auction='1'";
				else $sql .= " AND (auction='0' OR auction IS NULL)";
			}
			$sql .= " ORDER BY $ordertable.date";
			$result2 = @mysqli_query($db, $sql);
			if(@mysqli_num_rows($result2)) {
				$subtotal = 0;
				$totalqty = 0;
				echo "<table width=\"90%\" border=\"0\" cellspacing=\"1\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\"><tr class=\"reportheadsm\"><td width=\"70%\" align=\"left\">$productname</td><td align=\"right\" nowrap>".PRODUCTID.": <a href=\"editcatalogue.php?pid=$productid\"><font color=\"#FFFFFF\">$productid</font></a></td></tr></table>
				<table width=\"90%\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\" bgcolor=\"#d0d0d0\">
				<tr class=\"reportheadsm\"><td width=\"100\" nowrap align=\"left\">".DATETIME."</td><td width=\"70\" nowrap align=\"left\">".ORDERID."</td><td align=\"center\" width=\"50\" nowrap>".QUANTITY."</td><td width=\"60\" align=\"center\">".AMOUNT."</td><td width=\"240\" align=\"left\">".CUSTOMER."</td>";
				if ($generate != "Edit" && $transtype != "wholesale") echo "<td width=\"240\" align=\"center\">".REFERRED."</td>";
				if ($generate == "Edit") echo "<td width=\"33\" align=\"left\"></td>";
				echo "</tr>";
				$rowcolor = "#E0E0E0";
				while($row=@mysqli_fetch_array($result2)) if ($thisquantity=ashop_checkproduct($productid,$row["products"])) {
					$paid = $row["paid"];
					$reference = $row["reference"];
					if ($paid && !$reference) $ordertype = "paid";
					else if (!$reference) $ordertype = "unpaid";
					else $ordertype = "chargebacks";
					$productprices = explode("|",$row["paidproductprices"]);
					if ($row["paidproductprices"] && is_array($productprices)) foreach ($productprices as $pricepart) {
						$thisproductprice = explode(":",$pricepart);
						if ($thisproductprice[0] == $productid) $productprice = $thisproductprice[1];
					} else if ($row["wholesale"]) {
							$wspriceresult = @mysqli_query($db, "SELECT wholesaleprice FROM product WHERE productid='$productid'");
							$productprice = @mysqli_result($wspriceresult, 0, "wholesaleprice");
					} else $productprice = 0;
					$thisprice = $productprice;
					if ($row["reference"] && $row["price"] < 0) $thisprice -= $thisprice*2;
					$totalqty += $thisquantity;
					$subtotal += $thisprice;
					$totalitems += $thisquantity;
					$totalamount += $thisprice;
					$orderid = $row["orderid"];
					$invoiceid = $row["invoiceid"];
					$processresult = @mysqli_query($db, "SELECT * FROM paymentinfo WHERE orderid='$orderid'");
					if (@mysqli_num_rows($processresult) && $userid == "1") $processlink = TRUE;
					else $processlink = FALSE;
					$timedate = explode(" ", $row["date"]);
					$date = $timedate[0];
					$time = explode(":",$timedate[1]);
					$comment = $row["comment"];
					$thiscustomerid = $row["$customeridfield"];
					$customername = $row["firstname"]." ".$row["lastname"];
					$customeremail = $row["email"];
					if ($generate != "Edit") {
						$sql = "SELECT affiliate.affiliateid, affiliate.firstname, affiliate.lastname, affiliate.email FROM $orderafftable, affiliate WHERE $orderafftable.orderid=$orderid AND $orderafftable.affiliateid=affiliate.affiliateid AND ($orderafftable.secondtier=0 OR $orderafftable.secondtier IS NULL)";
						$result3 = @mysqli_query($db, "$sql");
						$affiliatename = @mysqli_result($result3, 0, "firstname")." ".@mysqli_result($result3, 0, "lastname");
						$affiliateemail = @mysqli_result($result3, 0, "email");
						$affiliateid = @mysqli_result($result3, 0, "affiliateid");
					}
					if ($row["wholesale"]) {
						$editcustomer = "edituser";
						$ws = "&nbsp;".WHOLESALELETTER;
						if ($row["purchaseorder"]) $ws .= "</a> <a href=\"javascript: void(0)\" onMouseOver=\"window.status='{$row["purchaseorder"]}'; return true;\" onMouseOut=\"window.status=window.defaultStatus;\"><img src=\"images/icon_info.gif\" alt=\"{$row["purchaseorder"]}\" title=\"{$row["purchaseorder"]}\" border=\"0\">";						
					} else {
						$editcustomer = "editcustomer";
						$ws = "";
					}
					if (empty($invoiceid)) $invoiceid = $orderid;
					echo "<tr class=\"reportlinesm\" bgcolor=\"$rowcolor\"><td nowrap align=\"left\">$date<br>{$time[0]}:{$time[1]}</td><td align=\"left\">";
					if($userid == "1" && file_exists("$ashoppath/admin/receipts/$orderid")) echo "<a href=\"getreceipt.php?orderid=$orderid\" target=\"_blank\">$invoiceid$ws</a>";
					else if ($userid == "1" && file_exists("$ashoppath/emerchant/invoices/$orderid")) echo "<a href=\"getinvoice.php?orderid=$orderid\" target=\"_blank\">$invoiceid$ws</a>";
					else echo "$invoiceid$ws";
					echo "</td><td align=\"center\">";
					if ($thisquantity < 0 || ($thisprice < 0 && $reference)) echo "<font color=\"#FF0000\">$thisquantity</font>";
					else echo $thisquantity;
					echo "</td><td align=\"right\">";
					if ($thisquantity < 0 || ($thisprice < 0 && $reference)) echo "<font color=\"#FF0000\">".number_format($thisprice,$showdecimals,$decimalchar,$thousandchar)."</font></td>";
					else echo number_format($thisprice,$showdecimals,$decimalchar,$thousandchar)."</td>";
					if (!$customerid) echo "<td align=\"left\">$thiscustomerid: <a href=\"$editcustomer.php?customerid=$thiscustomerid\">$customername</a></td>";
					if ($generate != "Edit" && $transtype != "wholesale") {
						echo "<td align=\"center\">";
						if ($affiliateid) echo "$affiliateid: <a href=\"affiliatedetail.php?affiliateid=$affiliateid\">$affiliatename";
						else echo "-";
						echo "</td>";
					} else if ($generate == "Edit") {
						echo "<td>";
						if ($ordertype == "paid") {
							echo "<a href=\"editsales.php?orderid=$orderid&action=updatestatus&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_edit.gif\" alt=\"".UPDATESTATUS."\" title=\"".UPDATESTATUS."\" border=\"0\"></a> <a href=\"editsales.php?orderid=$orderid&action=chargeback\"><img src=\"images/icon_chargeback.gif\" alt=\"".CHARGEBACKORDER."\" title=\"".CHARGEBACKORDER."\" border=\"0\"></a>&nbsp;<a href=\"reactivate.php?orderid=$orderid\"><img src=\"images/icon_reactivate.gif\" alt=\"".REACTIVATEORDER."\" title=\"".REACTIVATEORDER."\" border=\"0\"></a>";
						} else if ($ordertype == "unpaid") {
							if ($processlink) echo "<a href=\"$ashopsurl/admin/process.php?sesid=$sesid&orderid=$orderid&salesreport=$reporttype|$startyear|$startmonth|$startday|$toyear|$tomonth|$today|$orderby|$ascdesc|$generate\"><img src=\"images/icon_process.gif\" alt=\"".VIEWCREDITCARDANDACTIVATE."\" title=\"".VIEWCREDITCARDANDACTIVATE."\" border=\"0\"></a>";
							else echo "<a href=\"activate.php?orderid=$orderid\"><img src=\"images/icon_activatem.gif\" alt=\"".RECORDPAYMENTANDACTIVATE."\" title=\"".RECORDPAYMENTANDACTIVATE."\" border=\"0\"></a>";
						}
						echo "</td>";
					}
					echo "</tr>\n";
					if ($rowcolor == "#C0C0C0") $rowcolor = "#E0E0E0";
					else $rowcolor = "#C0C0C0";
				}
				echo "<tr class=\"reportheadsm\"><td align=\"right\">".TOTALS.":</td><td>&nbsp;</td><td align=\"center\">$totalqty</td><td align=\"right\">".$currencysymbols[$ashopcurrency]["pre"].number_format($subtotal,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</td><td>&nbsp;</td>";
				if ($generate == "Edit" || $transtype != "wholesale") echo "<td>&nbsp;</td>";
				echo "</tr></table><br>";
			}
		}
	}
	echo "<p>".TOTALITEMSSOLD.": $totalitems<br>".TOTALSALES.": ".$currencysymbols[$ashopcurrency]["pre"].number_format($totalamount,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</p><br><br></center>$footer";
}

ob_end_flush();
?>