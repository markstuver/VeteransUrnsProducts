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
include "customers.inc.php";

// Validate variables...
if (empty($customerid) || !is_numeric($customerid)) {
	header("Location: salesadmin.php");
	exit;
}

if ($userid != "1") {
	header("Location: index.php");
	exit;
}

// Validate variables...
if (empty($customerid) || !is_numeric($customerid)) {
	header("Location: wssalesadmin.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($remove && $customerid) {
	if ($yes) {
       $sql="DELETE FROM customer WHERE customerid='$customerid'";
       $result = @mysqli_query($db, $sql);
	   $sql="SELECT * FROM orders WHERE customerid='$customerid'";
	   $result = @mysqli_query($db, $sql);
	   for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		   $orderid = @mysqli_result($result,$i,"orderid");
		   $sql="DELETE FROM orderaffiliate WHERE orderid='$orderid'";
		   $result2 = @mysqli_query($db, $sql);
		   $sql="DELETE FROM pendingorderaff WHERE orderid='$orderid'";
		   $result2 = @mysqli_query($db, $sql);
	   }
	   $sql="DELETE FROM orders WHERE customerid='$customerid'";
	   $result = @mysqli_query($db, $sql);
	   $sql="DELETE FROM memberorders WHERE customerid='$customerid'";
	   $result = @mysqli_query($db, $sql);
	   $sql="DELETE FROM shipping WHERE customerid='$customerid'";
	   $result = @mysqli_query($db, $sql);
	   header("Location: wssalesadmin.php");
    }
	elseif ($no) header("Location: wssalesadmin.php");
	else {
		$sql="SELECT firstname, lastname FROM customer WHERE customerid='$customerid'";
		$result = @mysqli_query($db, $sql);
		$firstname = @mysqli_result($result,0,"firstname");
		$lastname = @mysqli_result($result,0,"lastname");
		echo "$header
<div class=\"heading\">".REMOVECUSTOMER."</div><center>
        <p>".AREYOUSURE." $customerid, $firstname $lastname?</font></p>
		<form action=\"edituser.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"customerid\" value=\"$customerid\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form>
		</center>
        $footer";
		exit;
	}
} 

// Store updated data...
if ($update || $newlevel) {
	/* Avoid duplicate email addresses...
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email' AND customerid!='$customerid'");
	if (@mysqli_num_rows($result)) $errormsg = "That email is already used by another customer profile!";
	else {*/
		if ($affiliateid == "0") $affiliateid = "";
		// Convert money format...
		$virtualcash = str_replace($thousandchar,"",$virtualcash);
		$virtualcash = str_replace($decimalchar,".",$virtualcash);
		if (!empty($addvirtualcash)) {
			$addvirtualcash = str_replace($thousandchar,"",$addvirtualcash);
			$addvirtualcash = str_replace($decimalchar,".",$addvirtualcash);
			$virtualcash += $addvirtualcash;
		}
		if ($newlevel) $level = 0;
		// Encrypt password if encryption key is available...
		$password = trim($password);
		if (!empty($customerencryptionkey)) $password = ashop_encrypt($password, $customerencryptionkey);
		$sql="UPDATE customer SET username='$nusername', password='$password', businessname='$businessname', businesstype='$businesstype', resellerid='$resellerid', firstname='$firstname', lastname='$lastname', email='$email', address='$address', state='$state', zip='$zip', city='$city', country='$country', phone='$phone', url='$url', extrainfo='$extrainfo', affiliateid='$affiliateid', level='$level', virtualcash='$virtualcash' WHERE customerid='$customerid'";
		$result = @mysqli_query($db, "$sql");

		$sql="UPDATE shipping SET shippingbusiness='$shippingbusiness', shippingfirstname='$shippingfirstname', shippinglastname='$shippinglastname', shippingaddress='$shippingaddress', shippingaddress2='$shippingaddress2', shippingzip='$shippingzip', shippingcity='$shippingcity', shippingstate='$shippingstate', shippingcountry='$shippingcountry', vat='$vat' WHERE customerid='$customerid'";
		$result = @mysqli_query($db, "$sql");

		if (!empty($auctionbids) && is_numeric($auctionbids) && $auctionbids > 0) {
			$sql="SELECT bidderid FROM pricebidder WHERE customerid='$customerid'";
			$result = @mysqli_query($db, "$sql");
			if (@mysqli_num_rows($result)) {
				$bidderid = @mysqli_result($result,0,"bidderid");
				@mysqli_query($db, "UPDATE pricebidder SET numberofbids='$auctionbids' WHERE bidderid='$bidderid'");
			} else @mysqli_query($db, "INSERT INTO pricebidder (numberofbids, customerid) VALUES ('$auctionbids', '$customerid')");
		}

		header("Location: wssalesadmin.php"); 
		exit;
	//}
}

// Get customer information from database...
$sql="SELECT * FROM customer WHERE customerid='$customerid'";
$result = @mysqli_query($db, "$sql");
$nusername = @mysqli_result($result, 0, "username");
$password = @mysqli_result($result, 0, "password");
$businessname = @mysqli_result($result, 0, "businessname");
$businesstype = @mysqli_result($result, 0, "businesstype");
$resellerid = @mysqli_result($result, 0, "resellerid");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");
$address = @mysqli_result($result, 0, "address");
$state = @mysqli_result($result, 0, "state");
$zip = @mysqli_result($result, 0, "zip");
$city = @mysqli_result($result, 0, "city");
$country = @mysqli_result($result, 0, "country");
$phone = @mysqli_result($result, 0, "phone");
$url = @mysqli_result($result, 0, "url");
$extrainfo = @mysqli_result($result, 0, "extrainfo");
$affiliateid = @mysqli_result($result, 0, "affiliateid");
$level = @mysqli_result($result, 0, "level");
$virtualcash = @mysqli_result($result, 0, "virtualcash");

// Decrypt password if encryption key is available...
if (!empty($password) && !empty($customerencryptionkey)) $password = ashop_decrypt($password, $customerencryptionkey);

if ($affiliateid) {
	$result = @mysqli_query($db, "SELECT firstname, lastname FROM affiliate WHERE affiliateid='$affiliateid'");
	$affiliatefirstname = @mysqli_result($result,0,"firstname");
	$affiliatelastname = @mysqli_result($result,0,"lastname");
}

$sql="SELECT * FROM shipping WHERE customerid='$customerid'";
$result = @mysqli_query($db, "$sql");
$shippingbusiness = @mysqli_result($result, 0, "shippingbusiness");
$shippingfirstname = @mysqli_result($result, 0, "shippingfirstname");
$shippinglastname = @mysqli_result($result, 0, "shippinglastname");
$shippingaddress = @mysqli_result($result, 0, "shippingaddress");
$shippingaddress2 = @mysqli_result($result, 0, "shippingaddress2");
$shippingzip = @mysqli_result($result, 0, "shippingzip");
$shippingcity = @mysqli_result($result, 0, "shippingcity");
$shippingstate = @mysqli_result($result, 0, "shippingstate");
$vat = @mysqli_result($result, 0, "vat");
$shippingcountry = @mysqli_result($result, 0, "shippingcountry");

$sql="SELECT bidderid FROM pricebidder WHERE customerid='$customerid'";
$result = @mysqli_query($db, "$sql");
$activatebids = @mysqli_num_rows($result);

if ($activatebids) {
	$sql="SELECT numberofbids FROM pricebidder WHERE customerid='$customerid'";
	$result = @mysqli_query($db, "$sql");
	$auctionbids = @mysqli_result($result, 0, "numberofbids");
}

// Check if credit card information is available...
$sql="SELECT wholesalepaymentinfo.orderid FROM wholesalepaymentinfo, orders WHERE wholesalepaymentinfo.orderid=orders.orderid AND orders.customerid='$customerid' ORDER BY wholesalepaymentinfo.orderid DESC";
$result = @mysqli_query($db, "$sql");
if (@mysqli_num_rows($result) && $userid == "1") {
	$processlink = "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CREDITCARD.":</font></td><td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><a href=\"selectcard.php?customerid=$customerid&ws=1\">".ACCESSCREDITCARDONFILE."</a></font></td></tr>";
}

// Close database...
@mysqli_close($db);


// Show customer page in browser...
	if (strpos($header, "title") != 0) {
	    $newheader = substr($header,1,strpos($header, "title")+5);
	    $newheader .= CUSTOMERDATAFOR.": $firstname $lastname - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= CUSTOMERDATAFOR.": $firstname $lastname - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$newheader
<div class=\"heading\">".PROFILEOF." $firstname $lastname, ".WHOLESALECUSTOMER." $customerid <a href=\"salesreport.php?customerid=$customerid&generate=true&reporttype=wholesale\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $customerid\" title=\"".SALESHISTORYFOR." $customerid\" border=\"0\"></a>&nbsp;<a href=\"edituser.php?customerid=$customerid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETECUSTOMER." $customerid ".FROMDB."\" title=\"".DELETECUSTOMER." $customerid ".FROMDB."\" border=\"0\"></a></div><center>";
if ($errormsg) echo "<p align=\"center\" class=\"notconfirm\">$errormsg</p>";
if ($activate == "true") echo "<span class=\"confirm\">".ORDERACTIVATIONCOMPLETED."</span><br>";
echo "<form action=\"edituser.php\" method=\"post\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\">
    <table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">$processlink
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".USERNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"nusername\" value=\"$nusername\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PASSWORD.":</font></td>
    <td align=\"left\"><input type=text name=\"password\" value=\"$password\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".REFERREDBY.":</font></td>
    <td align=\"left\"><input type=text name=\"affiliateid\" value=\"$affiliateid\" size=4><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"> <a href=\"editaffiliate.php?affiliateid=$affiliateid\">$affiliatefirstname $affiliatelastname</a></font></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".VIRTUALCASH.":</font></td>
    <td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=text name=\"virtualcash\" value=\"".number_format($virtualcash,$showdecimals,$decimalchar,$thousandchar)."\" size=10> ".$currencysymbols[$ashopcurrency]["post"]."<span style=\"margin-left: 20px;\"> ".ADD.": ".$currencysymbols[$ashopcurrency]["pre"]." <input type=text name=\"addvirtualcash\" value=\"\" size=5> ".$currencysymbols[$ashopcurrency]["post"]."</span></font></td></tr>";
if ($activatebids) echo "
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".AUCTIONBIDS.":</font></td>
    <td align=\"left\"><input type=text name=\"auctionbids\" value=\"$auctionbids\" size=10></td></tr>";
echo "
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PRICELEVEL.":</font></td>
    <td align=\"left\"><select name=\"level\"><option value=\"1\""; if ($level == "1") echo " selected"; echo ">".DEFAULTLEVEL."</option>";
	if ($pricelevels > 1) {
		for ($thislevel = 2; $thislevel <= $pricelevels; $thislevel++) {
			echo "<option value=\"$thislevel\"";
			if ($level == $thislevel) echo " selected";
			echo ">$thislevel</option>";
		}
	}
	echo "
	</select></td></tr>
	<tr><td align=\"right\">&nbsp;</td>
    <td align=\"left\"><input type=\"submit\" class=\"widebutton\" name=\"newlevel\" value=\"".REVERTTORETAIL."\"></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BUSINESSTYPE.":</font></td>
    <td align=\"left\"><select name=\"businesstype\"><option value=\"none\""; 
	if ($businesstype == "none") echo " selected";
	echo ">".SELECTONE."</option><option value=\"soleproprietor\"";
	if ($businesstype == "soleproprietor") echo " selected";
	echo ">".SOLEPROPRIETOR."</option><option value=\"corporation\"";
	if ($businesstype == "corporation") echo " selected";
	echo ">".CORPORATION."</option><option value=\"nonprofit\"";
	if ($businesstype == "nonprofit") echo " selected";
	echo ">".NONPROFIT."</option></select></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".WEBSITEURL.":</font></td>
    <td align=\"left\"><input type=text name=\"url\" value=\"$url\" size=40></td></tr>";
	if ($resellerid) echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".RESELLERID.":</font></td>
    <td><input type=text name=\"resellerid\" value=\"$resellerid\" size=40></td></tr>";
echo "
	<tr><td colspan=\"2\" align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>".BILLINGINFO."</b></font></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BUSINESSNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"businessname\" value=\"$businessname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"firstname\" value=\"$firstname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"lastname\" value=\"$lastname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".EMAIL.":</font></td>
    <td align=\"left\"><input type=text name=\"email\" value=\"$email\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS.":</font></td>
    <td align=\"left\"><input type=text name=\"address\" value=\"$address\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".STATEPROVINCE.":</font></td>
    <td align=\"left\"><input type=text name=\"state\" value=\"$state\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ZIP.":</font></td>
    <td align=\"left\"><input type=text name=\"zip\" value=\"$zip\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CITY.":</font></td>
    <td align=\"left\"><input type=text name=\"city\" value=\"$city\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COUNTRY.":</font></td>
    <td align=\"left\"><select name=\"country\"><option  value=none>".CHOOSECOUNTRY;

	foreach ($countries as $shortcountry => $longcountry) {
		if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
		echo "<option  value=$shortcountry";
		if ($country == $shortcountry) echo " selected";
		echo ">$longcountry\n"; 
	}

	echo "</select></td></tr>
	<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PHONE.":</font></td>
    <td align=\"left\"><input type=text name=\"phone\" value=\"$phone\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">";
	if ($requestabn) echo ABN;
	else echo VAT;
	echo " ".NUMBER.":</font></td>
    <td align=\"left\"><input type=text name=\"vat\" value=\"$vat\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDITIONAL."<br>".INFORMATION.":</font></td>
    <td align=\"left\"><textarea name=\"extrainfo\" cols=\"30\" rows=\"5\">$extrainfo</textarea></td></tr>
	<td align=\"center\" colspan=\"2\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>".SHIPPINGINFO."</b></font></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingfirstname\" value=\"$shippingfirstname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"shippinglastname\" value=\"$shippinglastname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingaddress\" value=\"$shippingaddress\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS2.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingaddress2\" value=\"$shippingaddress2\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CITY.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingcity\" value=\"$shippingcity\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".STATEPROVINCE.":</font></td>
    <td align=\"left\"><SELECT NAME=\"shippingstate\"><option  value=none>".CHOOSESTATE;
		foreach ($americanstates as $longstate => $shortstate) {
			echo "<option  value=$shortstate";
			if ($shortstate == $shippingstate) echo " selected";
			echo ">$longstate\n";
		}

	echo "</SELECT></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ZIP.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingzip\" value=\"$shippingzip\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COUNTRY.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingcountry\" value=\"$shippingcountry\" size=40></td></tr>
    <tr><td></td><td align=\"right\"><input type=\"submit\" value=\"".UPDATE."\" name=\"update\"></td></tr>
    </table></form>
	</font></center>
	$footer";
?>