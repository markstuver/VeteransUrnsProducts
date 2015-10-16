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

include "admin/config.inc.php";
include "admin/ashopconstants.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/customers.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/customer.inc.php";

// Read wholesale session cookie if this is a wholesale customer...
if (!empty($_COOKIE["wssessionid"])) $_COOKIE["customersessionid"] = $_COOKIE["wssessionid"];

// Validate variables...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

if (empty($_COOKIE["customersessionid"]) && empty($_COOKIE["wssessionid"])) {
	header("Location: signupform.php");
	exit;
}

// Store updated data...
if ($update_x || $update) {
	$error = FALSE;
	// Avoid duplicate email addresses...
	$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email' AND sessionid!='{$_COOKIE["customersessionid"]}'");
	if (@mysqli_num_rows($result)) $errormsg = EMAILINUSE;
	else {
		// Get current details for this customer...
		$result = @mysqli_query($db, "SELECT customerid, password, email, firstname, lastname, alternativeemails FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
		$customerid = @mysqli_result($result, 0, "customerid");
		$correctpasswd = @mysqli_result($result, 0, "password");
		// Decrypt password if encryption key is available...
		if (!empty($customerencryptionkey) && !empty($correctpasswd)) $correctpasswd = ashop_decrypt($correctpasswd, $customerencryptionkey);
		$prevfirstname = @mysqli_result($result, 0, "firstname");
		$prevlastname = @mysqli_result($result, 0, "lastname");
		$prevemail = @mysqli_result($result, 0, "email");
		$alternativeemails = @mysqli_result($result, 0, "alternativeemails");

		// Update password...
		if (!empty($newpassword1) && !empty($newpassword2) && !empty($oldpassword)) {
			if (($newpassword1 == $newpassword2) && ($oldpassword == $correctpasswd)) {
				// Encrypt password if encryption key is available...
				$newpassword1 = trim($newpassword1);
				if (!empty($customerencryptionkey)) $customerpassword = ashop_encrypt($newpassword1, $customerencryptionkey);
				else $customerpassword = $newpassword1;
				$result = @mysqli_query($db, "UPDATE customer SET password='$customerpassword' WHERE sessionid='{$_COOKIE["customersessionid"]}'");

				// Send password change confirmation message to customer...
				if (file_exists("$ashoppath/templates/messages/changepassword-$lang.html")) $messagefile = "$ashoppath/templates/messages/changepassword-$lang.html";
				else $messagefile = "$ashoppath/templates/messages/changepassword.html";
				$fp = @fopen("$messagefile","r");
				if ($fp) {
					while (!feof ($fp)) $messagetemplate .= fgets($fp, 4096);
					fclose($fp);
				} else {
					$messagetemplate="<html><head><title>".PASSWORDCHANGE1." $ashopname ".PASSWORDCHANGE2."</title></head><body><font face=\"$font\"><p>".PASSWORDCHANGE1." $ashopname ".PASSWORDCHANGE2."</p><p>".YOURUSERNAMEIS." <b>$email</b>".ANDYOURPASSWORD." <b>$password</b></p><p>".IFNOTCHANGED."</p></font></body></html>";
				}
				$message = str_replace("%ashopname%",$ashopname,$messagetemplate);
				$message = str_replace("%username%",$email,$message);
				$message = str_replace("%firstname%",$prevfirstname,$message);
				$message = str_replace("%lastname%",$prevlastname,$message);
				$message = str_replace("%email%",$prevemail,$message);
				$message = str_replace("%password%",$password,$message);
				// Get current date and time...
				$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
				$message = str_replace("%date%",$date,$message);

				$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
				@ashop_mail("$email","$ashopname - ".PASSWORDCHANGESUBJECT,"$message","$headers");
			} else if ($newpassword1 != $newpassword2) {
				$error = TRUE;
				$msg = DIDNOTMATCH;
			} else if ($oldpassword != $correctpasswd) {
				$msg = INCORRECTPASS;
				$error = TRUE;
			}
		}

		// Update customer profile...
		if (!$error) {
			if (!empty($province)) $state = $province;
			if (!empty($shippingprovince)) $shippingstate = $shippingprovince;
			if ($allowemail == "on") $allowemail = 1;
			else $allowemail = 0;
			if ($prevemail != $email) $alternativeemails .= ", $prevemail";
			// Check if this is a wholesale customer...
			$levelcheckresult = @mysqli_query($db, "SELECT level FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'");
			$customerlevel = @mysqli_result($levelcheckresult,0,"level");
			if (empty($customerlevel) || $customerlevel == 0) $sql="UPDATE customer SET firstname='$firstname', lastname='$lastname', email='$email', alternativeemails='$alternativeemails', address='$address', state='$state', zip='$zip', city='$city', country='$country', phone='$phone', allowemail='$allowemail', extrainfo='$extrainfo' WHERE sessionid='{$_COOKIE["customersessionid"]}'";
			else $sql="UPDATE customer SET firstname='$firstname', lastname='$lastname', email='$email', alternativeemails='$alternativeemails', address='$address', state='$state', zip='$zip', city='$city', country='$country', phone='$phone', allowemail='$allowemail', extrainfo='$extrainfo' WHERE sessionid='{$_COOKIE["wssessionid"]}'";
			$result = @mysqli_query($db, "$sql");

			$checkshippingresult = @mysqli_query($db, "SELECT * FROM shipping WHERE customerid='$customerid'");
			if (!@mysqli_num_rows($checkshippingresult)) $sql = "INSERT INTO shipping (shippingbusiness, shippingfirstname, shippinglastname, shippingaddress, shippingaddress2, shippingzip, shippingcity, shippingstate, shippingcountry, vat, customerid) VALUES ('$shippingbusiness', '$shippingfirstname', '$shippinglastname', '$shippingaddress', '$shippingaddress2', '$shippingzip', '$shippingcity', '$shippingstate', '$shippingcountry', '$vat', '$customerid')";
			else $sql="UPDATE shipping SET shippingbusiness='$shippingbusiness', shippingfirstname='$shippingfirstname', shippinglastname='$shippinglastname', shippingaddress='$shippingaddress', shippingaddress2='$shippingaddress2', shippingzip='$shippingzip', shippingcity='$shippingcity', shippingstate='$shippingstate', shippingcountry='$shippingcountry', vat='$vat' WHERE customerid='$customerid'";
			$result = mysqli_query($db, "$sql");
			$msg = UPDATED;
		}
	}
}

// Get customer information from database...
$sql="SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'";
$result = @mysqli_query($db, "$sql");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");
$allowemail = @mysqli_result($result, 0, "allowemail");
$address = @mysqli_result($result, 0, "address");
$state = @mysqli_result($result, 0, "state");
$zip = @mysqli_result($result, 0, "zip");
$city = @mysqli_result($result, 0, "city");
$country = @mysqli_result($result, 0, "country");
$phone = @mysqli_result($result, 0, "phone");
$extrainfo = @mysqli_result($result, 0, "extrainfo");
$customerid = @mysqli_result($result, 0, "customerid");
$virtualcash = @mysqli_result($result, 0, "virtualcash");
$affiliateid = @mysqli_result($result, 0, "affiliateid");

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

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Show header using template signup.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/customer-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/customer.html");
echo "
<script language=\"JavaScript\" src=\"includes/switchstates.js.php\" type=\"text/javascript\"></script>
<br><table class=\"ashopsignupframe\">
  <tr><td align=\"center\"><p><span class=\"ashopsignupheader\">".PROFILEOF." $firstname $lastname, ".CUSTOMERID." $customerid</span></p>
  <p class=\"ashopsignupheader\"><a href=\"orderhistory.php";
  if (!empty($shop) && $shop > 1) echo "?shop=$shop";
  echo "\"";
  if ($device == "mobile") echo " data-ajax=\"false\"";
  echo ">".VIEWORDERHISTORY."</a></p>";
  if (!empty($affiliateid) && file_exists("$ashoppath/customerparties.php")) {
	  echo "<p class=\"ashopsignupheader\"><a href=\"customerparties.php";
	  if (!empty($shop) && $shop > 1) echo "?shop=$shop";
	  echo "\"";
	  if ($device == "mobile") echo " data-ajax=\"false\"";
	  echo ">".MANAGEPARTIES."</a></p>";
  }
if ($errormsg) echo "<p class=\"ashopalert\">$errormsg</p>";
if ($msg) {
	if ($msg == UPDATED) echo "<p class=\"ashopcustomeralert2\">$msg</p>";
	else echo "<p class=\"ashopcustomeralert\">$msg</p>";
}

// Check for unpaid auctions that this customer has won...
$auctionresult = @mysqli_query($db, "SELECT * FROM orders WHERE (paid='' OR paid IS NULL) AND source='Auction' AND customerid='$customerid'");
if (@mysqli_num_rows($auctionresult)) {
	echo "<table class=\"ashopsignupbox\">
	<tr align=\"center\"> 
	<td><span class=\"ashopcustomertext1\">".UNPAIDAUCTIONS."</span></td>
	</tr>";
	while ($auctionrow = @mysqli_fetch_array($auctionresult)) {
		$auctionproductname = $auctionrow["description"];
		$auctionproductprice = $auctionrow["price"];
		$auctioninvoice = $auctionrow["orderid"];
		if (empty($auctionproductname)) {
			$auctionproduct = $auctionrow["products"];
			$auctionproduct = str_replace("1b","",$auctionproduct);
			$auctionproduct = str_replace("a","",$auctionproduct);
			if (is_numeric($auctionproduct)) {
				$auctionproductresult = @mysqli_query($db, "SELECT name FROM product WHERE productid='$auctionproduct'");
				$auctionproductname = @mysqli_result($auctionproductresult,0,"name");
			}
		}
		echo "<tr align=\"center\">
		<td><span class=\"ashopcustomertext3\">$auctionproductname - ".YOURBID.": <b>".$currencysymbols[$ashopcurrency]["pre"]."$auctionproductprice".$currencysymbols[$ashopcurrency]["post"]."</b>, <a href=\"payment.php?invoice=$auctioninvoice\"";
		if ($device == "mobile") echo " daja-ajax=\"false\"";
		echo ">".PAY."</a></span></td>
		";
	}
	echo "
	</table><br />";
} else {
	$bidderresult = @mysqli_query($db, "SELECT bidderid FROM pricebidder WHERE customerid='$customerid'");
	$bidderid = @mysqli_result($bidderresult,0,"bidderid");
	if (!empty($bidderid)) $auctionresult =@mysqli_query($db, "SELECT * FROM floatingprice WHERE bidderid='$bidderid' AND endprice IS NOT NULL AND endprice != ''");
	if (@mysqli_num_rows($auctionresult)) {
		echo "<table class=\"ashopsignupbox\">
		<tr align=\"center\"> 
		<td><span class=\"ashopcustomertext1\">".UNPAIDAUCTIONS."</span></td>
		</tr>";
		while ($auctionrow = @mysqli_fetch_array($auctionresult)) {
			$auctionproductprice = $auctionrow["endprice"];
			$auctionproduct = $auctionrow["productid"];
			if (is_numeric($auctionproduct)) {
				$auctionproductresult = @mysqli_query($db, "SELECT name FROM product WHERE productid='$auctionproduct'");
				$auctionproductname = @mysqli_result($auctionproductresult,0,"name");
			}
		}
		echo "<tr align=\"center\">
		<td><span class=\"ashopcustomertext3\">$auctionproductname - ".YOURBID.": <b>".$currencysymbols[$ashopcurrency]["pre"]."$auctionproductprice".$currencysymbols[$ashopcurrency]["post"]."</b>, <a href=\"index.php?product=$auctionproduct\"";
		if ($device == "mobile") echo " daja-ajax=\"false\"";
		echo ">".PAY."</a></span></td>
		";
	}
	echo "
	</table><br />";
}

// Show virtual cash...
if (!empty($virtualcash)) {
	echo "<table class=\"ashopsignupbox\">
	<tr align=\"center\"> 
	<td><span class=\"ashopcustomertext1\">".VIRTUALCASH."</span></td>
	</tr>
	<tr align=\"center\">
		<td><span class=\"ashopcustomertext3\">".CURRENTLYHAVE.": <b>".$currencysymbols[$ashopcurrency]["pre"].number_format($virtualcash,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</b></span></td>
	</tr></table><br />";
}

echo "<table class=\"ashopsignupbox\">
  <tr align=\"center\"> 
  <td> 
<form action=\"customerprofile.php\" method=\"post\" name=\"customerprofileform\"";
if ($device == "mobile") echo " data-ajax=\"false\"";
echo ">
    <table width=\"540\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
	<tr><td colspan=\"2\" align=\"center\"><span class=\"ashopsignupheader2\">".CHANGEPASSWORD."</span></td></tr>
    <tr><td align=\"right\" width=\"30%\"><span class=\"ashopcustomertext3\">".CURRENTPASSWORD.":</span></td>
	<td width=\"70%\" align=\"left\"><input type=password name=\"oldpassword\" size=20></td></tr>
    <tr><td align=\"right\" width=\"30%\"><span class=\"ashopcustomertext3\">".NEWPASSWORD.":</span><span class=\"ashopcustomertext8\"><br />&nbsp;</span></td>
	<td width=\"70%\" align=\"left\"><input type=password name=\"newpassword1\" size=20><br /><span class=\"ashopcustomertext8\">".LEAVEBLANKTOKEEP."</span></td></tr>
    <tr><td align=\"right\" width=\"30%\"><span class=\"ashopcustomertext3\">".CONFIRM.":</span></td>
	<td width=\"70%\" align=\"left\"><input type=password name=\"newpassword2\" size=20></td></tr>
	<tr><td colspan=\"2\" align=\"center\"><span class=\"ashopsignupheader2\">".BILLINGINFORMATION."</span></td></tr>
    <tr><td align=\"right\" width=\"30%\"><span class=\"ashopcustomertext3\">".BUSINESS.":</span></td>
    <td width=\"70%\" align=\"left\"><input type=text name=\"shippingbusiness\" value=\"$shippingbusiness\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".FIRSTNAME.":</span></td>
    <td align=\"left\"><input type=text name=\"firstname\" value=\"$firstname\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".LASTNAME.":</span></td>
    <td align=\"left\"><input type=text name=\"lastname\" value=\"$lastname\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".EMAIL.":</span></td>
    <td align=\"left\"><input type=text name=\"email\" value=\"$email\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".ADDRESS.":</span></td>
    <td align=\"left\"><input type=text name=\"address\" value=\"$address\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".CITY.":</span></td>
    <td align=\"left\"><input type=text name=\"city\" value=\"$city\" size=20></td></tr>
	<tr><td align=\"right\"><span class=\"ashopcustomertext3\">".ZIPCODE.":</span></td>
    <td align=\"left\"><input type=text name=\"zip\" value=\"$zip\" size=10></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".COUNTRY.":</span></td>
    <td align=\"left\"><select name=\"country\" onChange=\"switchStates(document.getElementById('state'),document.customerprofileform.province,document.customerprofileform.country.value);\">";
	foreach ($countries as $shortcountry => $longcountry) {
		if (strlen($longcountry) > 30) $slongcountry = substr($longcountry,0,27)."...";
		else $slongcountry = $longcountry;
		echo "<option value=\"$longcountry\"";
		if ($country == $longcountry || $country == $shortcountry) echo " selected";
		echo ">$slongcountry\n";
	}
	echo "</select></td></tr>
    <tr id=\"stateselector\"";
	if (!$state || !in_array($country,$countrieswithstates)) echo " style=\"display:none\"";
	echo "><td align=\"right\"><span class=\"ashopcustomertext3\">".STATE.":</span></td>
    <td align=\"left\"><select name=\"state\" id=\"state\"><option value=\"none\">".NOTUSACAN."</option>";

	if ($country == "US" || $country == $countries["US"]) $states = $americanstates;
	else if ($country == "CA" || $country == $countries["CA"]) $states = $canprovinces;
	else if ($country == "AU" || $country == $countries["AU"]) $states = $australianstates;
	else if ($country == "AT" || $country == $countries["AT"]) $states = $austriastates;
	else if ($country == "BE" || $country == $countries["BE"]) $states = $belgiumstates;
	else if ($country == "DE" || $country == $countries["DE"]) $states = $germanystates;
	else if ($country == "ES" || $country == $countries["ES"]) $states = $spainstates;
	else if ($country == "FR" || $country == $countries["FR"]) $states = $francestates;
	else if ($country == "GB" || $country == $countries["GB"]) $states = $ukstates;
	else if ($country == "IT" || $country == $countries["IT"]) $states = $italystates;
	else if ($country == "LU" || $country == $countries["LU"]) $states = $luxembourgstates;
	else if ($country == "NL" || $country == $countries["NL"]) $states = $netherlandsstates;
	else $states = $uscanstates;
	if (!in_array($state, $states) && !array_key_exists($state, $states)) {
		$province = $state;
		$state = "";
	}
	foreach ($states as $longstate => $shortstate) {
		echo "<option value=\"$shortstate\"";
		if ($state == $longstate || $state == $shortstate) echo " selected";
		echo ">$longstate\n";
	}
	echo "</select></td></tr>
    <tr id=\"regionrow\"";
	if (!$province || in_array($country,$countrieswithstates)) echo " style=\"display:none\"";
	echo "><td align=\"right\"><span class=\"ashopcustomertext3\">".PROVINCE.":</span></td>
    <td align=\"left\"><input id=\"province\" type=text name=\"province\" value=\"$province\" size=20></td></tr>
	<tr><td align=\"right\"><span class=\"ashopcustomertext3\">".PHONE.":</span></td>
    <td align=\"left\"><input type=text name=\"phone\" value=\"$phone\" size=20></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">";
	if ($requestabn) echo "ABN";
	else echo "VAT";
	echo " number:</font></td>
    <td align=\"left\"><input type=text name=\"vat\" value=\"$vat\" size=20></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".ADDITIONALINFORMATION.":</span></td>
    <td align=\"left\"><textarea name=\"extrainfo\" cols=\"30\" rows=\"5\">$extrainfo</textarea></td></tr>
	<tr><td colspan=\"2\" align=\"center\"><span class=\"ashopsignupheader2\">".SHIPPINGINFORMATION."</span></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".FIRSTNAME.":</span></td>
    <td align=\"left\"><input type=text name=\"shippingfirstname\" value=\"$shippingfirstname\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".LASTNAME.":</span></td>
    <td align=\"left\"><input type=text name=\"shippinglastname\" value=\"$shippinglastname\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".ADDRESS1.":</span></td>
    <td align=\"left\"><input type=text name=\"shippingaddress\" value=\"$shippingaddress\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".ADDRESS2.":</span></td>
    <td align=\"left\"><input type=text name=\"shippingaddress2\" value=\"$shippingaddress2\" size=30></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".CITY.":</span></td>
    <td align=\"left\"><input type=text name=\"shippingcity\" value=\"$shippingcity\" size=20></td></tr>
	<tr><td align=\"right\"><span class=\"ashopcustomertext3\">".ZIPCODE.":</span></td>
    <td align=\"left\"><input type=text name=\"shippingzip\" value=\"$shippingzip\" size=10></td></tr>
    <tr><td align=\"right\"><span class=\"ashopcustomertext3\">".COUNTRY.":</span></td>
    <td align=\"left\"><select name=\"shippingcountry\" onChange=\"switchStates2(document.customerprofileform.shippingstate,document.customerprofileform.shippingprovince,document.customerprofileform.shippingcountry.value);\">";

	// Convert multiple origin countries to an array...
	$shipfromcountries = explode("-", $shipfromcountry);
	
	if ($shipfromcountries) foreach ($shipfromcountries as $thiscountry) {
		echo "<option value=$thiscountry";
		if ($thiscountry == $shippingcountry) echo " selected";
		echo ">$countries[$thiscountry]";
	}
	
	if (!$localshipping) foreach ($countries as $shortcountry => $longcountry) {
		if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
		if (!in_array($shortcountry, $shipfromcountries)) {
			echo "<option value=$shortcountry";
			if ($shortcountry == $shippingcountry) echo " selected";
			echo ">$longcountry\n";
		}
	}
	
	echo "</select></td></tr>
    <tr id=\"stateselector2\"";
	if (!$shippingstate || !in_array($shippingcountry,$countrieswithstates)) echo " style=\"display:none\"";
	echo "><td align=\"right\"><span class=\"ashopcustomertext3\">".STATE.":</span></td>
    <td align=\"left\"><select id=\"shippingstate\" name=\"shippingstate\"><option value=\"none\">".NOTUSACAN."</option>";
	if ($shippingcountry == "US" || $shippingcountry == $countries["US"]) $states = $americanstates;
	else if ($shippingcountry == "CA" || $shippingcountry == $countries["CA"]) $states = $canprovinces;
	else if ($shippingcountry == "AU" || $shippingcountry == $countries["AU"]) $states = $australianstates;
	else if ($shippingcountry == "AT" || $shippingcountry == $countries["AT"]) $states = $austriastates;
	else if ($shippingcountry == "BE" || $shippingcountry == $countries["BE"]) $states = $belgiumstates;
	else if ($shippingcountry == "DE" || $shippingcountry == $countries["DE"]) $states = $germanystates;
	else if ($shippingcountry == "ES" || $shippingcountry == $countries["ES"]) $states = $spainstates;
	else if ($shippingcountry == "FR" || $shippingcountry == $countries["FR"]) $states = $francestates;
	else if ($shippingcountry == "GB" || $shippingcountry == $countries["GB"]) $states = $ukstates;
	else if ($shippingcountry == "IT" || $shippingcountry == $countries["IT"]) $states = $italystates;
	else if ($shippingcountry == "LU" || $shippingcountry == $countries["LU"]) $states = $luxembourgstates;
	else if ($shippingcountry == "NL" || $shippingcountry == $countries["NL"]) $states = $netherlandsstates;
	else $states = $uscanstates;
	if (!empty($shippingstate) && !in_array($shippingstate, $states) && !array_key_exists($shippingstate, $states)) {
		$shippingprovince = $shippingstate;
		$shippingstate = "";
	}
	foreach ($states as $longstate => $shortstate) {
		echo "<option value=\"$shortstate\"";
		if ($shippingstate == $longstate || $shippingstate == $shortstate) echo " selected";
		echo ">$longstate\n";
	}
	echo "</select></td></tr>
    <tr id=\"regionrow2\"";
	if (!$shippingprovince || in_array($shippingcountry,$countrieswithstates)) echo " style=\"display:none\"";
	echo "><td align=\"right\"><span class=\"ashopcustomertext3\">".PROVINCE.":</span></td>
    <td align=\"left\"><input id=\"shippingprovince\" type=text name=\"shippingprovince\" value=\"$shippingprovince\" size=\"20\" /></td></tr>
	<tr><td>&nbsp;</td><td align=\"left\"><span class=\"ashopcustomertext3\">";
	if ($device == "mobile") {
		echo "<fieldset data-role=\"controlgroup\"><input type=checkbox name=\"allowemail\" id=\"allowemail\""; if ($allowemail == "1") echo "checked"; echo " /><label for=\"allowemail\">".YESEMAILME."</label></fieldset>";
	} else {
		echo "<br /><input type=\"checkbox\" name=\"allowemail\""; if ($allowemail == "1") echo "checked"; echo " /> ".YESEMAILME;
	}
	echo "</span></td></tr><tr><td colspan=\"2\" align=\"center\">";
	if ($device == "mobile") echo "<input type=\"submit\" name=\"update\" data-role=\"button\" value=\"".UPDATE."\" />";
	else echo "<br><input type=\"image\" src=\"images/submit-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".UPDATE."\" name=\"update\" /><br />";
	echo "</td></tr></table>";
	if (!empty($shop) && $shop > 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\">";
	echo "
	</form>
	</td></tr></table><br><br></td></tr></table>";

// Show footer using template customer.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/customer-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/customer.html");

// Close database...
@mysqli_close($db);
?>