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

if ($userid != "1") {
	header("Location: index.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store updated data...
if ($update || $newlevel) {
	// Avoid duplicate email addresses...
	$result = @mysqli_query($db,"SELECT * FROM customer WHERE email='$email'");
	if (@mysqli_num_rows($result)) $errormsg = EMAILINUSE;
	else {
		if ($allowemail == "on") $allowemail = 1;
		else $allowemail = 0;
		if ($affiliateid == "0") $affiliateid = "";
		if ($newlevel) $newlevel = 1;
		// Convert money format...
		$virtualcash = str_replace($thousandchar,"",$virtualcash);
		$virtualcash = str_replace($decimalchar,".",$virtualcash);
		if (!empty($addvirtualcash)) {
			$addvirtualcash = str_replace($thousandchar,"",$addvirtualcash);
			$addvirtualcash = str_replace($decimalchar,".",$addvirtualcash);
			$virtualcash += $addvirtualcash;
		}
		// Encrypt password if encryption key is available...
		$password = trim($password);
		if (!empty($customerencryptionkey)) $password = ashop_encrypt($password, $customerencryptionkey);
		$sql="INSERT INTO customer (username, password, firstname, lastname, email, address, state, zip, city, country, phone, allowemail, extrainfo, affiliateid, level, virtualcash) VALUES ('$nusername', '$password', '$firstname', '$lastname', '$email', '$address', '$state', '$zip', '$city', '$country', '$phone', '$allowemail', '$extrainfo', '$affiliateid', '$newlevel', '$virtualcash')";
		$result = @mysqli_query($db,"$sql");
		$customerid = @mysqli_insert_id($db);

		$sql="INSERT INTO shipping (shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingzip, shippingcity, shippingstate, shippingcountry, vat, customerid) VALUES ('$shippingbusiness', '$shippingfirstname', '$shippinglastname', '$shippingaddress', '$shippingaddress2', '$shippingzip', '$shippingcity', '$shippingstate', '$shippingcountry', '$vat', '$customerid')";
		$result = mysqli_query($db,"$sql");

		header("Location: salesadmin.php"); 
		exit;
	}
}

// Close database...
@mysqli_close($db);


// Show customer page in browser...
	if (strpos($header, "title") != 0) {
	    $newheader = substr($header,1,strpos($header, "title")+5);
	    $newheader .= ADDNEWCUSTOMER." - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= ADDNEWCUSTOMER." - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$newheader
<div class=\"heading\">".ADDNEWCUSTOMER;
echo "</div><center>";
echo "<form action=\"addcustomer.php\" method=\"post\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\">";
if ($errormsg) echo "<p align=\"center\" class=\"notconfirm\">$errormsg</p>";
echo "
    <table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".USERNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"nusername\" value=\"$nusername\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PASSWORD.":</font></td>
    <td align=\"left\"><input type=text name=\"password\" value=\"$password\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".REFERREDBY.":</font></td>
    <td align=\"left\"><input type=text name=\"affiliateid\" value=\"$affiliateid\" size=4></td></tr>
	<tr><td align=\"right\">&nbsp;</td>
	<tr><td colspan=\"2\" align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>".BILLINGINFO."</b></font></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BUSINESSNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"shippingbusiness\" value=\"$shippingbusiness\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"firstname\" value=\"$firstname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"lastname\" value=\"$lastname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".EMAIL.":</font></td>
    <td align=\"left\"><input type=text name=\"email\" value=\"$email\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS.":</font></td>
    <td align=\"left\"><input type=text name=\"address\" value=\"$address\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CITY.":</font></td>
    <td align=\"left\"><input type=text name=\"city\" value=\"$city\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".STATEPROVINCE.":</font></td>
    <td align=\"left\"><input type=text name=\"state\" value=\"$state\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ZIP.":</font></td>
    <td align=\"left\"><input type=text name=\"zip\" value=\"$zip\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COUNTRY.":</font></td>
    <td align=\"left\"><input type=text name=\"country\" value=\"$country\" size=40></td></tr>
	<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PHONE.":</font></td>
    <td align=\"left\"><input type=text name=\"phone\" value=\"$phone\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">";
	if ($requestabn) echo ABN;
	else echo VAT;
	echo " ".NUMBER.":</font></td>
    <td align=\"left\"><input type=text name=\"vat\" value=\"$vat\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDITIONAL." ".INFORMATION.":</font></td>
    <td align=\"left\"><textarea name=\"extrainfo\" cols=\"30\" rows=\"5\">$extrainfo</textarea></td></tr>
	<tr><td colspan=\"2\" align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>".SHIPPINGINFO."</b></font></td></tr>
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
    <td align=\"left\"><input type=text name=\"shippingcountry\" value=\"$shippingcountry\" size=40></td></tr><tr><td colspan=\"2\" align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><br>".SENDINGEMAILALLOWED.": <input type=\"checkbox\" name=\"allowemail\""; if ($allowemail == "1") echo "checked"; echo "></td></tr>";
if ($userid == "1") echo "<tr><td></td><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\" name=\"update\"></td></tr></table></form>";
else echo "</table>";
echo "</font></center>$footer";
?>