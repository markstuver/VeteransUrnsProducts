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
include "ashopconstants.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/customers.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$orderid || !$action) {
	header("Location: salesreport.php?error=noorderselected");
	exit;
}

// Get order information from database...
$sql="SELECT * FROM orders WHERE orderid='$orderid' AND (date ='' OR date IS NULL)";
$result = @mysqli_query($db, "$sql");
if (!@mysqli_num_rows($result)) {
	echo "$header
	<div class=\"heading\">".INCOMPLETEORDERS."</div>
	<center><p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\" color=\"#FF0000\"><b>".ORDER.": $orderid ".COULDNOTBEFOUND."</b></font></p><p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"salesreport.php\">".BACKTOINCOMPLETELIST."</a></font></p>
	</center>
	$footer";
	exit;
}
$customerid = @mysqli_result($result, 0, "customerid");
$products = @mysqli_result($result, 0, "products");
$descriptionstring = @mysqli_result($result, 0, "description");
if (!$descriptionstring) {
	$productsincart = ashop_parseproductstring($db, @mysqli_result($result, $i, "products"));
	if ($productsincart) foreach($productsincart as $productnumber => $thisproduct) {
		$descriptionstring .= $thisproduct["quantity"].": ".$thisproduct["name"].$thisproduct["parameters"];
		if (count($productsincart) > 1 && $productnumber < count($productsincart)-1) $descriptionstring .= ", ";
	}
}
$displaydescr = str_replace(",","<br>",$descriptionstring);
$price = @mysqli_result($result, 0, "price");
$ip = @mysqli_result($result, 0, "ip");

$sql="SELECT * FROM shipping WHERE shippingid='$customerid'";
$result = @mysqli_query($db, "$sql");
$realcustomerid = @mysqli_result($result, 0, "customerid");
if ($realcustomerid) {
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$realcustomerid'");
	$firstname = @mysqli_result($result, 0, "firstname");
	$lastname = @mysqli_result($result, 0, "lastname");
	$address = @mysqli_result($result, 0, "address");
	$address2 = "";
	$email = @mysqli_result($result, 0, "shippingemail");
	$zip = @mysqli_result($result, 0, "zip");
	$city = @mysqli_result($result, 0, "city");
	$state = @mysqli_result($result, 0, "state");
	$country = @mysqli_result($result, 0, "country");
	$phone = @mysqli_result($result, 0, "phone");
} else {
	$business = @mysqli_result($result, 0, "shippingbusiness");
	$firstname = @mysqli_result($result, 0, "shippingfirstname");
	$lastname = @mysqli_result($result, 0, "shippinglastname");
	$address = @mysqli_result($result, 0, "shippingaddress");
	$address2 = @mysqli_result($result, 0, "shippingaddress2");
	$email = @mysqli_result($result, 0, "shippingemail");
	$zip = @mysqli_result($result, 0, "shippingzip");
	$city = @mysqli_result($result, 0, "shippingcity");
	$state = @mysqli_result($result, 0, "shippingstate");
	$country = @mysqli_result($result, 0, "shippingcountry");
	$phone = @mysqli_result($result, 0, "shippingphone");
}

if (!$firstname && !$lastname) $customername = UNKNOWN;
else $customername = "$firstname $lastname";

// Delete a preliminary order...
if ($action == "delete") {
	if ($yes) {
       $sql="DELETE FROM orders WHERE orderid='$orderid'";
       $result = @mysqli_query($db, $sql);
	   header("Location: salesreport.php?msg=deleted");
	   exit;
    } elseif ($no) {
	   header("Location: salesreport.php");
	   exit;
	} else {
		echo "$header
		<div class=\"heading\">".DELETEANORDER."</div>
        <center><p>".AREYOUSUREDELETEINCOMPLETE."</p>
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderid</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customername</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".AMOUNT.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".$currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>
		</table>	    
		<form action=\"editpreliminary.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		<input type=\"hidden\" name=\"action\" value=\"delete\"></form>
		</center>
        $footer";
		exit;
	}
} 

// Complete a preliminary order...
if ($action == "complete") {
	if ($step1) {
		if ($naddress2) $naddress .= ", $naddress2";
		$adminkey = md5("$databasepasswd$ashoppath"."prelcomplete");
		$querystring = "email=$nemail&firstname=$nfirstname&lastname=$nlastname&address=$naddress&city=$ncity&zip=$nzip&state=$nstate&country=$ncountry&phone=$nphone&invoice=$orderid&adminkey=$adminkey&amount=$price&products=$payoption"."ashoporderstring$products";
		if (strpos($ashopurl, "/", 8)) {
			$urlpath = "/".substr($ashopurl, strpos($ashopurl, "/", 8)+1);
			$urldomain = substr($ashopurl, 0, strpos($ashopurl, "/", 8));
		} else {
			$urlpath = "/";
			$urldomain = $ashopurl;
		}
		if ($urlpath == "/") $scriptpath = "order.php";
		else $scriptpath = "/order.php";
		$urldomain = str_replace("http://", "", $urldomain);
		$postheader = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
		$fp = fsockopen ("$urldomain", 80);
		if ($fp) {
			fputs ($fp, $postheader . $querystring);
			$response = "";
			while (!feof($fp)) $response .= fread ($fp, 8192);
			fclose ($fp);
		}
		echo "$header
		<div class=\"heading\">".INCOMPLETEORDERS."</div>
		<center><p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\" color=\"#009000\"><b>".ORDER.": $orderid ".HASBEENPROCESSED."</b></font></p><p align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"salesreport.php\">".BACKTOINCOMPLETELIST."</a></font></p>
		</center>
		$footer";
	} else {
		$payoptionsstring = "";
		$result = @mysqli_query($db, "SELECT * FROM payoptions");
		while($row = @mysqli_fetch_array($result)) $payoptionsstring .= "<option value=\"".$row["payoptionid"]."\">".$row["name"]."</option>";
		echo "$header
		<div class=\"heading\">".INCOMPLETEORDERS." <a href=\"editpreliminary.php?orderid=$orderid&action=delete\"><img src=\"images/icon_trash.gif\" alt=\"DELETEORDER\" title=\"".DELETEORDER."\" /></a></div>
        <center><p>".COMPLETEPRELIMINARYORDER."</p>
		<form action=\"editpreliminary.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ORDERID.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$orderid</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMER.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$customername</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".AMOUNT.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".$currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PRODUCTS.":</b></font></td><td><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$displaydescr</font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".PAYMENTOPTION.":</b></font></td><td><select name=\"payoption\">$payoptionsstring</select></td></tr>
	    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CUSTOMEREMAIL.":</b></font></td><td><input type=text name=\"nemail\" size=40 value=\"$email\"></font></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".FIRSTNAME.":</b></font></td>
		<td><input type=text name=\"nfirstname\" value=\"$firstname\" size=40></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".LASTNAME.":</b></font></td>
		<td><input type=text name=\"nlastname\" value=\"$lastname\" size=40></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ADDRESS.":</b></font></td>
		<td><input type=text name=\"naddress\" value=\"$address\" size=40></td></tr>";
		if ($address2) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ADDRESSLINE2.":</b></font></td>
		<td><input type=text name=\"naddress2\" value=\"$address2\" size=40></td></tr>";
		echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".CITY.":</b></font></td>
		<td><input type=text name=\"ncity\" value=\"$city\" size=40></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".STATEPROVINCE.":</b></font></td>
		<td><input type=text name=\"nstate\" value=\"$state\" size=40></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".ZIP.":</b></font></td>
		<td><input type=text name=\"nzip\" value=\"$zip\" size=40></td></tr>
		<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><b>".COUNTRY.":</b></font></td>
		<td><input type=text name=\"ncountry\" value=\"$country\" size=40></td></tr>
		<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><b>".PHONE.":</b></font></td>
		<td><input type=text name=\"nphone\" value=\"$phone\" size=40></td></tr>
		</table>
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
		<td width=\"100%\" align=\"right\" valign=\"top\"><input type=\"button\" name=\"cancel\" value=\"".CANCEL."\" onClick=\"javascript:history.back()\"> <input type=\"submit\" name=\"step1\" value=\"".SUBMIT."\"></td>
		</tr></table><input type=\"hidden\" name=\"orderid\" value=\"$orderid\">
		<input type=\"hidden\" name=\"action\" value=\"complete\"></form>
		</center>
		$footer";
	}
}

// Close database...
@mysqli_close($db);

?>