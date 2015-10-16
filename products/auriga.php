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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

// Kontrollera att invariablerna finns...
if (!isset($Status) || !isset($Status_code)) {
	header("Location: $ashopurl/checkout.php");
	exit;
}

if ($_SERVER['HTTPS'] == "on") $secureconnection = TRUE;
else $secureconnection = FALSE;

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/auriga.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/cart.html")) $templatepath = "/members/files/$ashopuser";

// Hantera felkoder...
$errmsg = "";
$paymentstatus == "";
if ($Status != "A") {
	switch ($Status_code) {
		case 3:
			$errmsg = "Ett obligatoriskt fält saknas eller har felaktigt format.";
			break;
		case 4:
			$errmsg = "Felaktigt Butiksid.";
			break;
		case 6:
			$errmsg = "Felaktigt Belopp.";
			break;
		case 7:
			$errmsg = "Problem vid Auktorisation.";
			break;
		case 11:
			$errmsg = "Gick ej att kontakta banken.";
			break;
		case 12:
			$errmsg = "Fel vid validering av mottagen MAC.";
			break;
		case 13:
			$errmsg = "För stort belopp.";
			break;
		case 14:
			$errmsg = "Fel format på datum/tid.";
			break;
		case 15:
			$errmsg = "Felaktigt Inköpsdatum.";
			break;
		case 17:
			$errmsg = "Otillåtet betalsätt. Betalsätt finns inte konfigurerat för Butiken.";
			break;
		case 18:
			$errmsg = "Kortbetalning: Nekad pga fel vid auktorisation eller ingen kontakt med bank.";
			break;
		case 19:
			$errmsg = "Kortbetalning: Köp nekat hos bank (kortets giltighetstid har gått ut), kontakta bank.";
			break;
		case 20:
			$errmsg = "Kortbetalning: Köp nekat hos bank, kontakta bank.";
			break;
		case 21:
			$errmsg = "Land för kortutgivande bank tillåts ej.";
			break;
		case 22:
			$errmsg = "Transaktionens riskbedömning överskrider tillåtet värde.";
			break;
		case 23:
			$errmsg = "Kortbetalning: kortet spärrat/inaktiverat hos kortutgivande bank.";
			break;
		case 25:
			$errmsg = "För högt belopp: inte tillräckligt med saldo, kortutgivande bank tillåter inte detta belopp på detta kort.";
			break;
		case 26:
			$errmsg = "Misstänkt bedrägeri.";
			break;
		case 27:
			$errmsg = "Köpesumman måste vara större än noll.";
			break;
		case 28:
			$errmsg = "Nekad pga för många betalningsförsök.";
			break;
		case 30:
			$errmsg = "Nekad pga time-out, inget svar från bank.";
			break;
		case 31:
			$errmsg = "Köpet avbrutet.";
			break;
		case 32:
			$errmsg = "Fel vid anrop, transaktion redan registrerad och betald.";
			break;
		case 41:
			$errmsg = "Butiken ej ansluten till tjänsten.";
			break;
		case 50:
			$errmsg = "Butik inte konfigurerad för valuta/korttyp kombination.";
			break;
		case 51:
			$errmsg = "Ogiltigt utgångsdaturm.";
			break;
		case 52:
			$errmsg = "Ogiltigt kortnummer.";
			break;
		case 53:
			$errmsg = "Felaktigt format på kortets kontrollsiffra.";
			break;
		case 56:
			$errmsg = "Köpet har avbrutits av köparen eller nekats av banken.";
			break;
		case 65:
			$errmsg = "Transaktion redan registrerad och väntar på svar från banken.";
			break;
		case 69:
			$errmsg = "Tekniskt fel Betalväxeln.";
			break;
		case 71:
			$errmsg = "Fel format eller storlek på SvarsURL:en.";
			break;
		case 76:
			$errmsg = "Felaktig varubeskrivning.";
			break;
		case 79:
			$errmsg = "Kunde inte göra inlösen.";
			break;
		case 81:
			$errmsg = "Misslyckad 3-D Secure-identifiering.";
			break;
		case 82:
			$errmsg = "3-D Secure-identifiering nekad pga. timeout.";
			break;
	}
	if ($secureconnection) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
		else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	}
	echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$errmsg<br><br><a href=\"checkout.php\">".TRYAGAIN."</a></span></font></p>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	exit;
} else {
	switch ($Status_code) {
		case 1:
			$errmsg = "Betalningen har nekats eller avbrutits.";
			break;
		case 3:
			$errmsg = "Kortbetalningen har makulerats av Butiken, innan inlösen.";
			break;
		case 7:
			$errmsg = "Inlösen misslyckad. Det gick inte att lösa in kortbetalningen.";
			break;
		case 2:
		case 4:
		case 6:
		case 8:
		case 9:
		case 11:
			$paymentstatus == "Pending";
			break;
	}
	if (!empty($errmsg)) {
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
		}
		echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$errmsg<br><br><a href=\"checkout.php\">".TRYAGAIN."</a></span></font></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
		exit;
	}
}

// Kontrollera att nödvändiga invariabler för köp finns och inte manipulerats...
$ashopcurrency = strtoupper($ashopcurrency);
if (!$Merchant_id || !is_numeric($Merchant_id) || !$Transaction_id || !$MAC || !$Customer_refno || !is_numeric($Customer_refno) || $Currency != $ashopcurrency) {
	header("Location: $ashopurl/checkout.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get payment option information...
$result = @mysqli_query($db, "SELECT payoptionid,secret FROM payoptions WHERE gateway='auriga' AND merchantid='$Merchant_id'");
if (!@mysqli_num_rows($result)) {
	header("Location: $ashopurl/checkout.php");
	exit;
}
$row = @mysqli_fetch_array($result);
$payoption = $row["payoptionid"];
$secret = $row["secret"];

// Verify the MAC...
$hashstring = $Merchant_id.$Version.$Customer_refno.$Transaction_id.$Status.$Status_code;
$hashstring .= $AuthCode.$_GET["3DSec"].$Batch_id.$Currency.$Payment_method.$Card_num.$Exp_date.$Card_type;
$hashstring .= $Risk_score.$Issuing_bank.$IP_country.$Issuing_country.$Authorized_amount.$Fee_amount;
$hashstring .= $secret;
$verifymac = md5($hashstring);
if ($verifymac != $MAC) {
	$errmsg = "Denna betalning kunde inte verifieras!";
	if ($secureconnection) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/cart-$lang.html",$logourl);
		else ashop_showtemplateheaderssl("$ashoppath$templatepath/cart.html",$logourl);
	} else {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
	}
	echo "<p align=\"center\"><br><br><font face=\"$font\" size=\"3\"><span class=\"fontsize3\"><b>".ERROR."</b></span></p><p align=\"center\"><font size=\"2\"><span class=\"fontsize2\">$errmsg<br><br><a href=\"checkout.php\">".TRYAGAIN."</a></span></font></p>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
	exit;
}

// Get payment details and ask customer to confirm...
$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$Customer_refno'");
$row = @mysqli_fetch_array($result);
$shippingid = $row["customerid"];
$shippingresult = @mysqli_query($db, "SELECT customerid FROM shipping WHERE shippingid='$shippingid'");
$customerid = @mysqli_result($shippingresult,0,"customerid");
$customerresult = @mysqli_query($db, "SELECT * FROM customer WHERE customerid='$customerid'");
$customerrow = @mysqli_fetch_array($customerresult);
$affiliate = $row["affiliateid"];
$invoice = $Customer_refno;
$firstname = $customerrow["firstname"];
$lastname = $customerrow["lastname"];
$email = $customerrow["email"];
$address = $customerrow["address"];
$city = $customerrow["city"];
$zip = $customerrow["zip"];
$state = $customerrow["state"];
$country = $customerrow["country"];
$phone = $customerrow["phone"];
$amount = $row["price"];
$products = $payoption."ashoporderstring".$row["products"];
$description = $row["description"];
$remoteorderid = $Transaction_id;
$securitycheck = md5("$remoteorderid$secret");
$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$Status&invoice=$invoice&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate";
if ($paymentstatus == "Pending") $querystring .= "&pendingpayment=true";
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
$header = "POST $urlpath$scriptpath HTTP/1.0\r\nHost: $urldomain\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
$fp = @fsockopen ("$urldomain", 80);
$response = "";
if ($fp) {
	fputs ($fp, $header . $querystring);
	while (!feof($fp)) $response .= fread ($fp, 8192);
	fclose ($fp);
}
if ($secureconnection) header ("Location: $ashopsurl/order.php?payopt=$payoption&ofinv=$invoice");
else header ("Location: $ashopurl/order.php?payopt=$payoption&ofinv=$invoice");
exit;
?>