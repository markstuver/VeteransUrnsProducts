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

// Validate variables...
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";
if (!empty($invoice) && !is_numeric(str_replace("em","",$invoice))) $invoice = "";
if (!empty($payoption) && !is_numeric($payoption)) unset($payoption);
if (!empty($emerchantquote) && !is_numeric($emerchantquote)) $emerchantquote = "";
if (!empty($description)) {
	$description = strip_tags($description);
	$description = str_replace("<","",$description);
	$description = str_replace(">","",$description);
}
if (empty($shop)) $shop = 1;
if (!empty($shop) && !is_numeric($shop)) $shop = 1;

if (!isset($payoption) || !$invoice) header("Location: $ashopurl");
if (!isset($upsold)) $upsold = 0;

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
include "language/$lang/orderform.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/checkout.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Set the right redirect URL for the Continue Shopping button...
if ($returnurl) {
	$redirecturl = str_replace($ashopurl,"",$returnurl);
	$redirecturl = str_replace($ashopsurl,"",$returnurl);
} else {
	$redirecturl = "index.php";
	if ($shop && $shop != "1") $redirecturl .= "?shop=$shop";
}

// Convert multiple origin countries to an array...
$shipfromcountries = explode("-", $shipfromcountry);

// Combine address fields...
if ($address && $address2) $address .= ", $address2";

// Get payment option information...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if ($payoption > 0) {
	$result = @mysqli_query($db,"SELECT * FROM payoptions WHERE payoptionid='$payoption'");
	$row = @mysqli_fetch_array($result);
	$orderpagetext = $row["orderpagetext"];
	$user = $row["merchantid"];
	if ($row["gateway"] == "authorizenetaim" || $row["gateway"] == "authnetecheckaim" || $row["gateway"] == "authnetaimdelayed" || $row["gateway"] == "securenet") $password = $row["transactionkey"];
	else $password = $row["secret"];
	$apisignature = $row["transactionkey"];
	$vspartner = $row["vspartner"];
	$gateway = $row["gateway"];
	$fee = $row["fee"];
	$logourl = $row["logourl"];
	$telesignactivated = $row["telesign"];
	if ($row["testmode"]) $testmode = "TRUE";
	else unset($testmode);
	if ($gateway == "ideal") {
		require_once 'admin/ideal.class.php';
		$iDEAL = new Mollie_iDEAL_Payment ($user);
		if ($testmode == "TRUE") $iDEAL->setTestmode(true);
		$bank_array = $iDEAL->getBanks();
		if ($bank_array == false) {
			$idealerror = '<p>Er is een fout opgetreden bij het ophalen van de banklijst: '. $iDEAL->getErrorMessage(). '</p>';
		} else {
			$idealbanks = "<select name=\"bank_id\">
			<option value=''>Kies uw bank</option>";
			foreach ($bank_array as $bank_id => $bank_name) $idealbanks .= "<option value=\"".htmlspecialchars($bank_id)."\">".htmlspecialchars($bank_name)."</option>";
			$idealbanks .= "</select>";
		}
	}
} else $gateway = "manual";

// Store upsold item...
if ($upsellitems > 0 && $added) {
	$productsarray = explode("ashoporderstring", $products);
	@mysqli_query($db,"UPDATE orders SET products='{$productsarray[1]}', price='$amount', description='$description' WHERE orderid='$invoice'");
}

if ($emerchantquote) $result = @mysqli_query($db,"SELECT * FROM emerchant_quotes WHERE id='$emerchantquote'");
else $result = @mysqli_query($db,"SELECT * FROM orders WHERE orderid='$invoice'");
$row = @mysqli_fetch_array($result);
$telesigncode = $row["telesigncode"];
if ($emerchantquote && !@mysqli_num_rows($result)) unset($emerchantquote);
if (!$emerchantquote || ($emerchantquote && !$amount)) $amount = $row["price"]+$fee;
$products = $payoption."ashoporderstring".$row["products"];

// Get gateway path for the current currency...
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";

// Handle secure payments...
include "admin/gateways$pathprefix/$gateway.gw";

if (strstr($paymenturl,"https")) $secureconnection = TRUE;
else $secureconnection = FALSE;

if ($expmonth && $expyear && $ccnumber) $fullccpaymentinfo = TRUE;
else $fullccpaymentinfo = FALSE;
if ($bankname && $routingnumber && $accountnumber && $checknumber && $accountname) $fullecheckinfo = TRUE;
else $fullecheckinfo = FALSE;

if (($fullccpaymentinfo || $fullecheckinfo) && (!$telesignid || $telesigncheck == $telesigncode || !$telesignactivated) && $email && $email == $confirmemail) {

	// Handle up selling...
	if ($upsellitems > 0 && $upsold < $upsellitems) {
		// Determine which product to show...
		$productsincart = ashop_parseproductstring($db, $row["products"]);
		$upsellproducts = array();
		if ($productsincart) {
			foreach($productsincart as $productnumber => $thisproduct) {
				$upsellarray = $thisproduct["upsell1"];
				if (is_array($upsellarray)) {
					$upsellproductid = $upsellarray["productid"];
					$upsellpriority = $upsellarray["priority"];
					if (!is_array($upsellproducts["$upsellpriority"]) || !in_array($upsellproductid, $upsellproducts["$upsellpriority"])) $upsellproducts["$upsellpriority"][] = $upsellproductid;
				}
				$upsellarray = $thisproduct["upsell2"];
				if (is_array($upsellarray)) {
					$upsellproductid = $upsellarray["productid"];
					$upsellpriority = $upsellarray["priority"];
					if (!is_array($upsellproducts["$upsellpriority"]) || !in_array($upsellproductid, $upsellproducts["$upsellpriority"])) $upsellproducts["$upsellpriority"][] = $upsellproductid;
				}
			}
			$product = 0;
			$checkproduct = 0;
			$prioritylevel = 9;
			while (!$product && $prioritylevel >= 0) {
				if (isset($upsellproducts["$prioritylevel"])) {
					$checkprioritylevel = array();
					while (count($checkprioritylevel) < count($upsellproducts["$prioritylevel"])) {
						srand ((double) microtime() * 1000000);
						$selectrandom = rand(0,count($upsellproducts["$prioritylevel"])-1);
						$checkproduct = $upsellproducts["$prioritylevel"][$selectrandom];
						$checkprioritylevel[] = $checkproduct;
						if ($checkproduct != $added && $checkproduct != $notadded && !ashop_checkproduct($checkproduct, $basket)) $product = $checkproduct;
					}
				}
				$prioritylevel--;
			}
		}

		// If there is a product to up sell, show it...
		if ($product) {
			if ($secureconnection) {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/upsell-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/upsell-$lang.html",$logourl);
				else ashop_showtemplateheaderssl("$ashoppath$templatepath/upsell.html",$logourl);
			} else {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/upsell-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/upsell-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/upsell.html");
			}
			$upsellresult = @mysqli_query($db,"SELECT * FROM product WHERE productid='$product'");
			$upsellrow = @mysqli_fetch_array($upsellresult);
			$upsellname = $upsellrow["name"];
			$upselldescription = $upsellrow["description"];
			$upsellprice = $upsellrow["price"];
			$upselldisplayprice = $currencysymbols[$ashopcurrency]["pre"].number_format($upsellprice,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
			echo "<div class=\"ashoppageheadertext1\">".ADDTOORDER."</div><br>";
			if (file_exists("$ashoppath/prodimg/$product.gif")) echo "<img src=\"prodimg/$product.gif\" width=\"$thumbnailwidth\" alt=\"thumbnail\"><br>";
			else if (file_exists("$ashoppath/prodimg/$product.jpg")) echo "<img src=\"prodimg/$product.jpg\" width=\"$thumbnailwidth\" alt=\"thumbnail\"><br>";
			echo "
			<div class=\"ashopproductname\">$upsellname</div>
			<div class=\"ashopproducttext\">$upselldescription</div><br>
			<span class=\"ashopproductlabel\">".PRICE.":</span><span class=\"ashopproducttext\"> $upselldisplayprice</span><br><br>
			<table width=\"236\" cellpadding=\"3\" border=\"0\" cellspacing=\"0\"><tr>
			<td><form method=\"post\" action=\"orderform.php\">";
			foreach($_POST as $key=>$value) {
				if ($key == "products") $value .= "1b{$product}a";
				if ($key == "description") $value .= ", 1: $upsellname";
				if ($key == "amount") $value += $upsellprice;
				if ($key != "notadded" && $key != "added" && $key != "upsold") echo "<input type=\"hidden\" name=\"$key\" value=\"$value\">
				";
			}
			echo "<input type=\"hidden\" name=\"added\" value=\"$product\">
			<input type=\"hidden\" name=\"upsold\" value=\"";
			echo $upsold+1;
			echo "\"><input name=\"yes\" type=\"image\" src=\"images/yes-$lang.png\" class=\"ashopbutton\" alt=\"".YESPLEASE."\"></form></td>
			<td><form method=\"post\" action=\"orderform.php\">";
			foreach($_POST as $key=>$value) if ($key != "notadded" && $key != "added" && $key != "upsold") echo "<input type=\"hidden\" name=\"$key\" value=\"$value\">
			";
			echo "<input type=\"hidden\" name=\"notadded\" value=\"$product\">
			<input type=\"hidden\" name=\"upsold\" value=\"".$upsellitems."\"><input name=\"no\" type=\"image\" src=\"images/no-$lang.png\" class=\"ashopbutton\" alt=\"".NOTHANKYOU."\"></form></td>
			";
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/upsell-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/upsell-$lang.html");
			else ashop_showtemplatefooter("$ashoppath$templatepath/upsell.html");
			exit;
		}
	}

	if ($fullccpaymentinfo) {

		// Verify the customer's IP number with minFraud...
		if (!empty($minfraudgeoipkey) && !empty($country)) {
			$ipnumber = $_SERVER["REMOTE_ADDR"];
			if (ashop_minfraudproxycheck($ipnumber) != "0.00") {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/checkout-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/checkout.html");
				echo "<table class=\"ashopmessagetable\">
				<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
				<p><span class=\"ashopmessage\">".PROXYDETECTED."</span></p>
				<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/checkout-$lang.html");
				else ashop_showtemplatefooter("$ashoppath$templatepath/checkout.html");
				exit;
			}

			if (!ashop_minfraudgeoip($ipaddress,$country)) {
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/checkout-$lang.html");
				else ashop_showtemplateheader("$ashoppath$templatepath/checkout.html");
				echo "<table class=\"ashopmessagetable\">
				<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".SORRY."</span></p>
				<p><span class=\"ashopmessage\">".IPCHECKFAILED."</span></p>
				<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
				if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/checkout-$lang.html");
				else ashop_showtemplatefooter("$ashoppath$templatepath/checkout.html");
				exit;
			}
		}

		if ($gateway == "virtualmerchant") {
			$address = substr($address, 0, 20);
			$zip = substr($zip, 0, 9);
		}
		$expdate = $expmonth.$expyear;
		$ccnumber = str_replace(" ", "", $ccnumber);
		$ccnumber = str_replace("-", "", $ccnumber);
		$ccnumber = str_replace(".", "", $ccnumber);
		if (!empty($province)) $state = $province;
		$result = process_payment($user, $password, $vspartner, $cardtype, $ccnumber, $seccode, $expdate, $amount, $invoice, $firstname, $lastname, $address, $city, $zip, $state, $country, $phone, $email, $testmode, $description);
	} else if ($fullecheckinfo) {
		$result = process_payment($user, $password, $bankname, $routingnumber, $accountnumber, $checknumber, $accountname, $amount, $invoice, $firstname, $lastname, $address, $city, $zip, $state, $country, $phone, $email, $testmode, $description);
	}
	if ($result["RESULT"] == "success") {
		// Parse any additional customer information fields...
		$customerinfo = "";
		foreach($_POST as $key => $value) {
			if ($key != "ccnumber" && $key != "seccode" && $key != "expdate" && $key != "amount" && $key != "invoice" && $key != "firstname" && $key != "lastname" && $key != "address" && $key != "city" && $key != "zip" && $key != "state" && $key != "country" && $key != "phone" && $key != "email" && $key != "returnurl" && $key != "payoption" && $key != "products" && $key != "localprocessing" && $key != "description" && $key != "emerchantquote" && $key != "affiliate" && $key != "confirmemail" && $key != "cardtype" && $key != "expmonth" && $key != "expyear" && $key != "address2" && $key != "Submit" && $key != "bankname" && $key != "routingnumber" && $key != "accountnumber" && $key != "checknumber" && $key != "accountname") $customerinfo .= "$key:$value|";
		}
		if ($customerinfo) $customerinfo = substr($customerinfo,0,-1);
		$remoteorderid = $result["REMOTEORDERID"];
		$securitycheck = md5("$remoteorderid$password");
		$responsemsg = $result["RESPONSEMSG"];
		$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$address&city=$city&zip=$zip&state=$state&country=$country&phone=$phone&remoteorderid=$remoteorderid&responsemsg=$responsemsg&invoice=$invoice&scode=$securitycheck&amount=$amount&products=$products&description=$description&affiliate=$affiliate&customerinfo=$customerinfo";
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
		$fp = fsockopen ("$urldomain", 80);
		if ($fp) {
			fputs ($fp, $header . $querystring);
			$response = "";
			while (!feof($fp)) $response .= fread ($fp, 8192);
			fclose ($fp);
		}

		// Store validated encrypted credit card info for offline processing...
		if ($gateway == "offline") {
			$sql = "INSERT INTO paymentinfo (orderid, payoptionid, cardtype, cardnumber, expdate";
			if ($ccsecuritycode) $sql .= ", seccode";
			$sql .= ") VALUES ('$invoice', '$payoption', '$cardtype', ENCODE('$ccnumber','$password'), ENCODE('$expdate','$password')";
			if ($ccsecuritycode) $sql .= ", ENCODE('$seccode','$password')";
			$sql .=")";
			@mysqli_query($db,$sql);
		}
		
		// Initiate Authorize.Net ARB if needed....
		if ($gateway == "authorizenetaim") {
			$arbexpdate = "20".$expyear."-".$expmonth;
			if(isset($productsincart)) unset($productsincart);
			$productsincart = ashop_parseproductstring($db, $row["products"]);
			if (is_array($productsincart)) foreach($productsincart as $productnumber => $thisproduct) {
				if ($thisproduct["billtemplate"]) {
					$billtemplateresult = @mysqli_query($db,"SELECT * FROM emerchant_billtemplates WHERE billtemplateid='{$thisproduct["billtemplate"]}'");
					$billtemplaterow = @mysqli_fetch_array($billtemplateresult);
					if (is_array($billtemplaterow)) $arbresult = authnetarb($user, $password, $thisproduct["name"], $thisproduct["price"], $billtemplaterow["recurring"], $billtemplaterow["duedays"], $billtemplaterow["recurringtimes"], $ccnumber, $arbexpdate, $invoice, $firstname, $lastname, $address, $city, $zip, $state, $country, $email, $phone, $testmode);
					// Check for ARB errors...
					$arbresultcode = explode("<resultCode>", $arbresult);
					$arbresultcode = explode("</resultCode>", $arbresultcode[1]);
					$arbresultcode = $arbresultcode[0];
					if ($arbresultcode == "Error") {
						$arberrortext = explode("<text>", $arbresult);
						$arberrortext = explode("</text>", $arberrortext[1]);
						$arberrortext = $arberrortext[0];
						$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
						@ashop_mail("$ashopemail", un_html($ashopname,1)." - ARB Error", "AShop could not activate ARB on order ID $invoice. Authorize.Net returned the following error message: $arberrortext","$headers");
					}
				}
			}			
		}

		if ($ccnumber) $newashopurl = "$ashopsurl/order.php";
		else $newashopurl = $postbackurl;

		header ("Location: $newashopurl?payopt=$payoption&ofinv=$invoice&returnurl=$returnurl&fromshop=$shop");
		exit;
	} else {
		if ($secureconnection) {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/checkout-$lang.html",$logourl);
			else ashop_showtemplateheaderssl("$ashoppath$templatepath/checkout.html",$logourl);
		} else {
			if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/checkout-$lang.html");
			else ashop_showtemplateheader("$ashoppath$templatepath/checkout.html");
		}
		if ($telesignid && $telesignactivated) {
			if ($secureconnection) $relay = "$ashopsurl/orderform.php";
			else $relay = "$ashopurl/orderform.php";
			echo "<form method=\"POST\" action=\"$relay\">";
			foreach($_POST as $fieldname=>$fieldvalue) if($fieldname != "expmonth" && $fieldname != "ccnumber" && $fieldname != "expyear") echo "<input type=\"hidden\" name=\"$fieldname\" value=\"$fieldvalue\">";
			echo "</form>";
		}
		if ($gateway == "authnetecheckaim") echo "<p align=\"center\"><br><br><span class=\"ashopmessageheader\">".ECHECKINVALID."</span></p><p align=\"center\"><span class=\"ashopmessage\">{$result["ERROR"]}<br><br><a href=\"";
		else echo "<p align=\"center\"><br><br><span class=\"ashopmessageheader\">".CARDINVALID."</span></p><p align=\"center\"><span class=\"ashopmessage\">{$result["ERROR"]}<br><br><a href=\"";
		if ($gateway == "eselect") echo "checkout.php";
		else {
			echo "javascript:";
			if ($telesignid && $telesignactivated) echo "document.forms[0].submit()";
			else echo "history.back()";
		}
		echo "\">".TRYAGAIN."</a></span></p>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/checkout-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/checkout.html");
		exit;
	}
}

// Check if PayPal Express Checkout is available...
$ppcheckresult = @mysqli_query($db,"SELECT * FROM payoptions WHERE gateway='paypalec' AND userid='$shop'");
$ppecid = @mysqli_result($ppcheckresult,0,"payoptionid");

// Show header using template catalogue.html...
if ($secureconnection) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheaderssl("$ashoppath$templatepath/checkout-$lang.html",$logourl);
	else ashop_showtemplateheaderssl("$ashoppath$templatepath/checkout.html",$logourl);
} else {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/checkout-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/checkout.html");
}
if ($expmonth && $expyear && $ccnumber) {
	if (!$telesigncheck) $telesigncallresult = ashop_telesigncall($telesigncode, $country, $phone);
	if ($telesigncallresult != "SUCCESS") {
		echo "<p align=\"center\"><br><br><span class=\"ashopmessageheader\">".TELESIGNERROR."</span></p><p align=\"center\"><span class=\"ashopmessage\">";
		if ($telesigncallresult == "1") echo TELESIGNERROR1;
		else if ($telesigncallresult == "2") echo TELESIGNERROR2;
		echo "<br><br><a href=\"";
		if ($gateway == "eselect") echo "checkout.php";
		else echo "javascript: history.back()";
		echo "\">".TRYAGAIN."</a></span></p>";
	} else {
		echo "<SCRIPT LANGUAGE=\"JavaScript\">
<!--

setTimeout (\"showmessage()\", 60000);

function showmessage()
{
   w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=350, height=200\");
   w.document.write('<html><head><title>".TELESIGN."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".TELESIGNMESSAGE."</span><br><br><center><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></center></font><br></body></html>');
}

//-->
</SCRIPT><br><br><center><span class=\"ashoporderformtext2\">".TELESIGNINFO2."</span><br><br><form action=\"orderform.php\" method=\"post\">";
		foreach ($_POST as $key => $value) echo "<input type=\"hidden\" name=\"$key\" value=\"$value\">";
		if ($telesigncheck && $telesigncheck != $telesigncode) echo "<span class=\"ashoporderformtext1\">".INCORRECTCODE."</span><br><br>";
		echo "<span class=\"ashoporderformtext2\">".TSCODE." <input type=\"text\" name=\"telesigncheck\" size=\"7\"> <input type=\"submit\" value=\"".SUBMIT."\"></span></center></form>";
	}
} else {
	echo "<script language=\"JavaScript\">
	<!--
	function verifyform(orderform) {
		var allformfieldsfilled = 1;
		var creditcardnumbervalid = 1;
		var creditcardnotexpired = 1;
		var emailmatch = 1;
		var emailvalid = 1;\n";
$thismonth = date("m",time()+$timezoneoffset);
$thisyear = date("y",time()+$timezoneoffset);
// Verify extra form fields for this payment option...
$fieldsresult = @mysqli_query($db,"SELECT * FROM formfields WHERE payoptionid='$payoption' ORDER BY formfieldid DESC");
$count = 1;
while ($fieldrow = @mysqli_fetch_array($fieldsresult)) {
	$fieldlabel = $fieldrow["label"];
	$fieldname = str_replace(" ", "_", $fieldrow["name"]);
	$fieldname = strtolower($fieldname);
	if ($fieldrow["required"]) {
		if ($gateway == "manual") echo "		if (orderform.customerinfos[$count].value == '') { allformfieldsfilled = 0; missedfield = '$fieldlabel'; }\n";
		else echo "		if (orderform.$fieldname.value == '') { allformfieldsfilled = 0; missedfield = '$fieldlabel'; }\n";
	}
	$count+=2;
}
if ($gateway == "ideal") echo "		if (orderform.bank_id.value == '') { allformfieldsfilled = 0; missedfield = 'Bank'; }\n";
echo "		if (orderform.phone.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(PHONE)."'; }
		if (orderform.country.value == 'none') { allformfieldsfilled = 0; missedfield = '".strtolower(COUNTRY)."'; }
		if (orderform.zip.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ZIP)."'; }
		if (orderform.country.value == 'United States' && orderform.state.value == 'none') { allformfieldsfilled = 0; missedfield = '".strtolower(JUSTSTATE)."'; }
		if (orderform.country.value == 'United States' && orderform.state.value == 'other') { allformfieldsfilled = 0; missedfield = '".strtolower(JUSTSTATE)."'; }
		if (orderform.city.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(CITY)."'; }
		if (orderform.address.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ADDRESS)."'; }
		if (orderform.email.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(EMAIL)."'; }
		if (orderform.confirmemail.value != orderform.email.value) emailmatch = 0; 
		if (orderform.lastname.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(LASTNAME)."'; }
        if (orderform.firstname.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(FIRSTNAME)."'; }
		if (orderform.email.value.indexOf('@') == -1 || orderform.email.value.indexOf('.') == -1) emailvalid = 0;";
if ($ccsecuritycode == "TRUE") echo "if (orderform.seccode.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(SECCODE)."'; }";
if ($localprocessing == "TRUE" && $gateway != "authnetecheckaim") echo "if (orderform.ccnumber.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(CARDNO)."'; }
		if (orderform.expyear.value+orderform.expmonth.value < '$thisyear$thismonth') creditcardnotexpired = 0;
		if ((orderform.ccnumber.value.substring(0,1) != 4) && (orderform.ccnumber.value.substring(0,1) != 5) && (orderform.ccnumber.value.substring(0,1) != 6) && (orderform.ccnumber.value.substring(0,1) != 3)) creditcardnumbervalid = 0;
		if ((orderform.ccnumber.value.substring(0,1) == 4 || orderform.ccnumber.value.substring(0,1) == 5 || orderform.ccnumber.value.substring(0,1) == 6) && orderform.ccnumber.value.length != 16) creditcardnumbervalid = 0;
		if (orderform.ccnumber.value.substring(0,1) == 3 && orderform.ccnumber.value.length != 15) creditcardnumbervalid = 0;\n";
else if ($localprocessing == "TRUE") echo "if (orderform.accountname.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ACCOUNTNAME)."'; }
		if (orderform.checknumber.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(CHECKNUMBER)."'; }
		if (orderform.accountnumber.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ACCOUNTNUMBER)."'; }
		if (orderform.routingnumber.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ROUTINGNUMBER)."'; }
		if (orderform.bankname.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(BANKNAME)."'; }";
echo "if (allformfieldsfilled == 0) {
	";
if ($device == "mobile") echo "
			alert('".FILLINALL." '+missedfield);
			return false;
			";
else echo "
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".YOUFORGOT."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".FILLINALL." '+missedfield+'</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
			";
echo "
		} else if (creditcardnumbervalid == 0) {
	";
if ($device == "mobile") echo "
			alert('".CARDNUMBERINVALID."');
			return false;
			";
else echo "
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".CARDINVALID."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".CARDNUMBERINVALID."</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
			";
echo "
		} else if (creditcardnotexpired == 0) {
	";
if ($device == "mobile") echo "
			alert('".CARDHASEXPIRED."');
			return false;
			";
else echo "
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".CARDEXPIRED."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".CARDHASEXPIRED."</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
			";
echo "
	    } else if (emailvalid == 0) {
	";
if ($device == "mobile") echo "
			alert('".EMAILADDRESSINVALID."');
			return false;
			";
else echo "
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".EMAILINVALID."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".EMAILADDRESSINVALID."</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
			";
echo "
	    } else if (emailmatch == 0) {
	";
if ($device == "mobile") echo "
			alert('".EMAILADDRESSDOESNOTMATCH."');
			return false;
			";
else echo "
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".EMAILDOESNOTMATCH."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".EMAILADDRESSDOESNOTMATCH."</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
			";
echo "
	    } else {
			document.getElementById('wait').innerHTML = '".PLEASEWAIT."';
			";
if ($gateway == "manual" && @mysqli_num_rows($fieldsresult)) echo "var count = 0;
	    for (var n = 0; n < document.orderform.customerinfos.length; n++) {
			document.orderform.customerinfo.value += document.orderform.customerinfos[n].value;
			if (count == 2) count = 0;
			if (count == 0) {
				document.orderform.customerinfo.value += ':';
				count++;
			}
			else if (count == 1 && n < document.orderform.customerinfos.length-1) {
				document.orderform.customerinfo.value +=  '|';
				count++;
			}
	    }
			";
echo "			return true;
		}
    }
	function aboutseccode() {
	";
if ($device == "mobile") echo "
			alert('".CODE2."');
			";
else echo "
		w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=250\");
		w.document.write('<html><head><title>".CODE1."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".CODE2."</span><br><br><image src=\"images/ccv.jpg\"><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
		";
echo "
    }
-->
</script>
<script language=\"JavaScript\" src=\"includes/switchstates.js.php\" type=\"text/javascript\"></script>
<br><table class=\"ashoporderformframe\">
  <tr><td align=\"center\">
      <p><span class=\"ashoporderformheader\">".BILL."</span></p><p><a href=\"$ashopurl/checkout.php?id=$invoice&redirect=$redirecturl\"";
if ($device == "mobile") echo " data-ajax=\"false\" data-role=\"button\"";
echo ">";
if ($device == "mobile") echo CONTINUESHOPPING;
else echo "<img src=\"{$buttonpath}images/continue-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"Continue shopping!\">";
echo "</a></p>
	  <p><span class=\"ashoporderformtext1\">".PRODUCTS."</span><br><span class=\"ashoporderformtext2\">".stripslashes($description)."</span><br><br>";

if ($taxandshippingcost) echo "<span class=\"ashoporderformtext1\">".PRICE.":</span><span class=\"ashoporderformtext2\"> {$currencysymbols[$ashopcurrency]["pre"]}".number_format($productcost,$showdecimals,$decimalchar,$thousandchar)."{$currencysymbols[$ashopcurrency]["post"]}, </span><span class=\"ashoporderformtext1\">".SHIPPINGHANDLING.":</span><span class=\"ashoporderformtext2\"> {$currencysymbols[$ashopcurrency]["pre"]}".number_format($taxandshippingcost,$showdecimals,$decimalchar,$thousandchar)."{$currencysymbols[$ashopcurrency]["post"]}</span><br>";

echo "<span class=\"ashoporderformtext1\">".AMOUNT."</span><span class=\"ashoporderformtext2\"> {$currencysymbols[$ashopcurrency]["pre"]}".number_format($amount,$showdecimals,$decimalchar,$thousandchar)."{$currencysymbols[$ashopcurrency]["post"]}</span></p>
      <p><table><tr><td align=\"left\"><span class=\"ashoporderformtext2\">$orderpagetext</span></td></tr></table></p>";
if ($idealerror) echo "<p align=\"center\"><span class=\"ashopmessage\">$idealerror</span></p>";
echo "
      </td>
  </tr>
  <tr align=\"center\"> 
    <td> 
      <table class=\"ashoporderformbox\">
        <tr align=\"center\"> 
          <td> 
            <form action=\"";
if ($localprocessing == "TRUE") echo "orderform.php";
else echo "$paymenturl2";
echo "\" method=post name=\"orderform\" onSubmit=\"return verifyform(this)\"";
if ($device == "mobile") echo " data-ajax=\"false\"";
echo ">
";

if ($device != "mobile") {
	echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"440\">";
	$tdwidth1 = 160;
	$tdwidth2 = 280;
}

if ($localprocessing == "TRUE" && $gateway != "authnetecheckaim") {
	$thisyear4 = date("Y", time());
	$thisyear2 = date("y", time());
	if($gw_cardtypes) {
		if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"cardtype\">".CARDTYPE."</label><select name=\"cardtype\" id=\"cardtype\">$gw_cardtypes</select></div>";
		else echo "<tr><td align=\"right\" width=\"$tdwidth1\"><span class=\"ashoporderformlabel\">".CARDTYPE."</span></td><td width=\"$tdwidth2\" class=\"ashoporderformfield\"><select name=\"cardtype\">$gw_cardtypes</select></td></tr>";
	}
	if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"ccnumber\">".CARDNO.":</label><input type=text name=\"ccnumber\" id=\"ccnumber\" size=20></div>";
    else echo "<tr><td align=\"right\" width=\"$tdwidth1\"><span class=\"ashoporderformlabel\">".CARDNO.":</span></td><td width=\"$tdwidth2\" class=\"ashoporderformfield\"><input type=text name=\"ccnumber\" size=20></td></tr>";
	if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"expmonth\">".EXPDATE."</label>";
	else echo "
	<tr><td align=\"right\"><span class=\"ashoporderformlabel\">".EXPDATE."</span></td><td class=\"ashoporderformfield\">";
	echo "<select name=\"expmonth\" id=\"expmonth\"><option value=\"01\">Jan</option><option value=\"02\">Feb</option><option value=\"03\">Mar</option><option value=\"04\">Apr</option><option value=\"05\">May</option><option value=\"06\">Jun</option><option value=\"07\">Jul</option><option value=\"08\">Aug</option><option value=\"09\">Sep</option><option value=\"10\">Oct</option><option value=\"11\">Nov</option><option value=\"12\">Dec</option></select>";
	if ($device == "mobile") echo "</div><div data-role=\"fieldcontain\"><label for=\"expyear\">".EXPDATE."</label>";
	echo "<select name=\"expyear\" id=\"expyear\">";
	for ($i = 0; $i < 10; $i++) {
		printf("<option value=\"%02d\">", $thisyear2);
		echo "$thisyear4</option>";
		$thisyear4++;
		$thisyear2++;
	}
	echo "</select>";
	if ($device == "mobile") echo "</div";
	else echo "</td></tr>";
	if ($ccsecuritycode == "TRUE") {
		if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"seccode\">".SECCODE.":</label><input type=text name=\"seccode\" id=\"seccode\" size=\"5\" maxsize=\"3\"></div>";
		else echo "<tr><td align=\"right\"><a href=\"javascript: aboutseccode();\"><span class=\"ashoporderformlabel\">".SECCODE."</span></a>:</td><td class=\"ashoporderformfield\"><input type=text name=\"seccode\" size=\"5\" maxsize=\"3\"></td></tr>";
	}
} else if ($localprocessing == "TRUE") {
	if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"bankname\">".BANKNAME.":</label><input type=\"text\" name=\"bankname\" id=\"bankname\" value=\"$bankname\" size=30></div>
	<div data-role=\"fieldcontain\"><label for=\"routingnumber\">".ROUTINGNUMBER.":</label><input type=\"text\" name=\"routingnumber\" id=\"routingnumber\" value=\"$routingnumber\" size=9></div>
	<div data-role=\"fieldcontain\"><label for=\"accountnumber\">".ACCOUNTNUMBER.":</label><input type=\"text\" name=\"accountnumber\" id=\"accountnumber\" value=\"$accountnumber\" size=30></div>
	<div data-role=\"fieldcontain\"><label for=\"checknumber\">".CHECKNUMBER.":</label><input type=\"text\" name=\"checknumber\" id=\"checknumber\" value=\"$checknumber\" size=9></div>
	<div data-role=\"fieldcontain\"><label for=\"accountname\">".ACCOUNTNAME.":</label><input type=\"text\" name=\"accountname\" id=\"accountname\" value=\"$accountname\" size=30></div>";
	else echo "<tr><td align=\"center\" colspan=\"2\"><img src=\"images/echeck.gif\" alt=\"eCheck Example\"></td></tr>
	<tr><td align=\"right\" width=\"$tdwidth1\"><span class=\"ashoporderformlabel\">".BANKNAME.":</span></td><td width=\"$tdwidth2\" class=\"ashoporderformfield\"><input type=text name=\"bankname\" value=\"$bankname\" size=30></td></tr>
	<tr><td align=\"right\" valign=\"top\"><span class=\"ashoporderformlabel\">".ROUTINGNUMBER.":</span></td><td class=\"ashoporderformfield\"><input type=text name=\"routingnumber\" value=\"$routingnumber\" size=9> <span class=\"ashoporderformnotice\">".ALWAYSNINEDIGITS."</span></td></tr>
	<tr><td align=\"right\"><span class=\"ashoporderformlabel\">".ACCOUNTNUMBER.":</span></td><td class=\"ashoporderformfield\"><input type=text name=\"accountnumber\" value=\"$accountnumber\" size=30></td></tr>
	<tr><td align=\"right\"><span class=\"ashoporderformlabel\">".CHECKNUMBER.":</span></td><td class=\"ashoporderformfield\"><input type=text name=\"checknumber\" value=\"$checknumber\" size=9></td></tr>
	<tr><td align=\"right\"><span class=\"ashoporderformlabel\">".ACCOUNTNAME.":</span></td><td class=\"ashoporderformfield\"><input type=text name=\"accountname\" value=\"$accountname\" size=30></td></tr>";
}

if (!empty($idealbanks)) {
	if ($device == "mobile") echo "
	<div data-role=\"fieldcontain\"><label for=\"bank_id\">Bank:</label>$idealbanks";
	else echo "
		<tr> 
			<td align=\"right\"><span class=\"ashoporderformlabel\">Bank:</span></td>
			<td class=\"ashoporderformfield\">$idealbanks</td></tr>";
}

if ($device == "mobile") {
	echo "
<div data-role=\"fieldcontain\"><label for=\"firstname\">".FIRSTNAME.":</label><input type=\"text\" name=\"firstname\" id=\"firstname\" value=\"$firstname\" size=30></div>
<div data-role=\"fieldcontain\"><label for=\"lastname\">".LASTNAME.":</label><input type=\"text\" name=\"lastname\" id=\"lastname\" value=\"$lastname\" size=30></div>
<div data-role=\"fieldcontain\"><label for=\"email\">".EMAIL.":</label><input type=\"text\" name=\"email\" id=\"email\" value=\"$email\" size=30></div>";
if (empty($email)) echo "
<div data-role=\"fieldcontain\"><label for=\"confirmemail\">".CONFIRMEMAIL.":</label><input type=\"text\" name=\"confirmemail\" id=\"confirmemail\" value=\"$confirmemail\" size=30></div>";
else echo "<input type=\"hidden\" name=\"confirmemail\" id=\"confirmemail\" value=\"$email\" />";
echo "
<div data-role=\"fieldcontain\"><label for=\"address\">".ADD1.":</label><input type=\"text\" name=\"address\" id=\"address\" value=\"$address\" size=30></div>
<div data-role=\"fieldcontain\"><label for=\"address2\">".ADD2.":</label><input type=\"text\" name=\"address2\" id=\"address2\" value=\"$address2\" size=30></div>
<div data-role=\"fieldcontain\"><label for=\"city\">".CITY.":</label><input type=\"text\" name=\"city\" id=\"city\" value=\"$city\" size=20></div>
<div data-role=\"fieldcontain\"><label for=\"zip\">".ZIP.":</label><input type=\"text\" name=\"zip\" id=\"zip\" value=\"$zip\" size=30></div>
";
} else {
	echo "<tr>
                  <td align=\"right\" width=\"$tdwidth1\"><span class=\"ashoporderformlabel\">".FIRSTNAME.":</span></td>
                  <td width=\"$tdwidth2\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"firstname\" value=\"$firstname\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".LASTNAME.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"lastname\" value=\"$lastname\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".EMAIL.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"email\" value=\"$email\" size=30>
                  </td>
                </tr>";
	if (empty($email)) echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".CONFIRMEMAIL.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"confirmemail\" value=\"$confirmemail\" size=30>
                  </td>
                </tr>";
	else echo "<input type=\"hidden\" name=\"confirmemail\" id=\"confirmemail\" value=\"$email\" />";
	echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".ADD1.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"address\" value=\"$address\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".ADD2.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"address2\" value=\"$address2\" size=30>
                  </td>
                </tr>
                <tr>
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".CITY.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"city\" value=\"$city\" size=20>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".ZIP.":</span></td>
                  <td class=\"ashoporderformfield\"> 
                    <input type=text name=\"zip\" value=\"$zip\" size=10>
                  </td>
                </tr>";
}
if ($device == "mobile") echo "
<div data-role=\"fieldcontain\"><label for=\"country\">".COUNTRY.":</label>";

else echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".COUNTRY.":</span></td>
                  <td class=\"ashoporderformfield\">";
echo "
                    <select name=\"country\" id=\"country\" onChange=\"switchStates(document.getElementById('state'),document.orderform.province,document.orderform.country.value);\" onClick=\"if (typeof(countryinterval) != 'undefined') window.clearInterval(countryinterval);\"><option  value=none>".CHOOSECOUNTRY;
					if (strlen($country) == 2) foreach ($countries as $shortcountry => $longcountry) if ($country == $shortcountry) $country = $longcountry;
					if ($shipfromcountries) foreach ($shipfromcountries as $thiscountry) {
						echo "<option value=\"$countries[$thiscountry]\"";
						if ($country == $countries[$thiscountry]) echo " selected";
						echo ">$countries[$thiscountry]";
					}
					foreach ($countries as $shortcountry => $longcountry) if (!in_array($shortcountry, $shipfromcountries)) {
						if (strlen($longcountry) > 30) $slongcountry = substr($longcountry,0,27)."...";
						else $slongcountry = $longcountry;
						echo "<option value=\"$longcountry\"";
						if ($country == $longcountry || $country == $shortcountry) echo " selected";
						echo ">$slongcountry\n";
					}
					echo "</select>";
if ($device == "mobile") echo "</div>";
else echo "
                  </td>
                </tr>";
if ($device == "mobile") {
	if (empty($state) || !in_array($country, $longcountrieswithstates)) echo "<div data-role=\"fieldcontain\" id=\"stateselector\" style=\"display:none\">";
	else echo "<div data-role=\"fieldcontain\" id=\"stateselector\">";
	echo "<label for=\"state\">".STATE.":</label>";
} else {
	if (empty($state) || !in_array($country, $longcountrieswithstates)) echo "<tr id=\"stateselector\" style=\"display:none\">";
	else echo "<tr id=\"stateselector\">";
	echo "
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".STATE.":</span></td>
                  <td class=\"ashoporderformfield\">";
}
echo "
				    <select name=\"state\" id=\"state\"><option value=\"none\">".CHOOSESTATE."<option value=\"other\"";
					if ($address && !$state) echo " selected";
					else if (!in_array($state, $uscanstates)) echo " selected";
					echo ">".NOTUSACAN;
					foreach ($uscanstates as $longstate => $shortstate) {
						echo "<option  value=\"$shortstate\"";
						if ($shortstate == $state || $longstate == $state) {
							if ($shortstate == "WA" || $shortstate == "NT") {
								if ($country == "US" || $country == "United States") {
									if ($state == "WA" && $longstate == "Washington") echo " selected";
								} else if ($country == "AU" || $country == "Australia") {
									if ($state == "WA" && $longstate == "Western Australia") echo " selected";
									else if ($state == "NT" && $longstate == "Northern Territory") echo " selected";
								} else if ($country == "CA" || $country == "Canada") {
									if ($state == "NT" && $longstate == "Northwest Territories") echo " selected";
								}
							} else echo " selected";
						}
						echo ">$longstate\n";
					}
					echo "</select>";
if ($device == "mobile") echo "</div>";
else echo "
                  </td>
                </tr>";

if ($device == "mobile") {
	if (empty($state) || in_array($country, $longcountrieswithstates)) echo "<div data-role=\"fieldcontain\" id=\"regionrow\" style=\"display:none\">";
	else echo "<div data-role=\"fieldcontain\" id=\"regionrow\">";
	echo "<label for=\"province\">".PROVINCE.":</label><input type=text name=\"province\" id=\"province\" size=\"20\" value=\"";
	if (!in_array($country, $longcountrieswithstates)) echo $state;
	echo "\" /></div>";
} else {
	if (empty($state) || in_array($country, $longcountrieswithstates)) echo "<tr id=\"regionrow\" style=\"display:none\">";
	else echo "<tr id=\"regionrow\">";
	echo "
				  <td align=\"right\"><span class=\"ashoporderformlabel\">".PROVINCE."</span></td>
				  <td class=\"ashoporderformfield\">
				    <input type=text name=\"province\" size=\"20\" value=\"";
					if (!in_array($country, $longcountrieswithstates)) echo $state;
					echo "\" />
				  </td>
				</tr>";
}
if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"phone\">".PHONE.":</label><input type=\"text\" name=\"phone\" id=\"phone\" value=\"$phone\" size=\"20\" /></div>";
else echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashoporderformlabel\">".PHONE.":</span></td>
                  <td class=\"ashoporderformfield\">
                    <input type=text name=\"phone\" value=\"$phone\" size=\"20\" />
                  </td>
                </tr>";

				// Make sure the correct state selector is always visible...
				if (!empty($country) && empty($state)) echo "<script language=\"javascript\">switchStates(document.orderform.state,document.orderform.province,document.orderform.country.value);</script>";
				// Display extra form fields for this payment option...
				if (@mysqli_num_rows($fieldsresult)) {
					@mysqli_data_seek($fieldsresult,0);
					while ($fieldrow = @mysqli_fetch_array($fieldsresult)) {
						$fieldname = str_replace(" ", "_", $fieldrow["name"]);
						$fieldname = strtolower($fieldname);
						$fieldname = urlencode($fieldname);
						if ($gateway == "manual") {
							if ($device == "mobile") {
								if ($fieldrow["rows"] == "1") echo "<div data-role=\"fieldcontain\"><label for=\"customerinfos\">{$fieldrow["label"]}:</label><input type=\"hidden\" name=\"customerinfos\" value=\"$fieldname\" /><input type=\"text\" name=\"customerinfos\" id=\"customerinfos\" value=\"\" size=\"{$fieldrow["size"]}\" /></div>";
								else echo "<div data-role=\"fieldcontain\"><label for=\"customerinfos\">{$fieldrow["label"]}:</span></label><input type=\"hidden\" name=\"customerinfos\" value=\"$fieldname\" /><textarea name=\"customerinfos\" id=\"customerinfos\" cols=\"{$fieldrow["size"]}\" rows=\"{$fieldrow["rows"]}\"></textarea></div>";
							} else {
								if ($fieldrow["rows"] == "1") echo "<tr><td align=\"right\"><span class=\"ashoporderformlabel\">{$fieldrow["label"]}:</span></td><td class=\"ashoporderformfield\"><input type=\"hidden\" name=\"customerinfos\" value=\"$fieldname\" /><input type=\"text\" name=\"customerinfos\" value=\"\" size=\"{$fieldrow["size"]}\" /></td></tr>";
								else echo "<tr><td align=\"right\"><span class=\"ashoporderformlabel\">{$fieldrow["label"]}:</span></td><td class=\"ashoporderformfield\"><input type=\"hidden\" name=\"customerinfos\" value=\"$fieldname\"><textarea name=\"customerinfos\" cols=\"{$fieldrow["size"]}\" rows=\"{$fieldrow["rows"]}\"></textarea></td></tr>";
							}
						} else {
							if ($device == "mobile") {
								if ($fieldrow["rows"] == "1") echo "<div data-role=\"fieldcontain\"><label for=\"$fieldname\">{$fieldrow["label"]}:</label><input type=\"text\" name=\"$fieldname\" id=\"$fieldname\" value=\"{$_POST["fieldname"]}\" size=\"{$fieldrow["size"]}\" /></div>";
								else echo "<div data-role=\"fieldcontain\"><label for=\"$fieldname\">{$fieldrow["label"]}:</span><textarea name=\"$fieldname\" id=\"$fieldname\" cols=\"{$fieldrow["size"]}\" rows=\"{$fieldrow["rows"]}\">{$_POST["fieldname"]}</textarea></div>";
							} else {
								if ($fieldrow["rows"] == "1") echo "<tr><td align=\"right\"><span class=\"ashoporderformlabel\">{$fieldrow["label"]}:</span></td><td class=\"ashoporderformfield\"><input type=\"text\" name=\"$fieldname\" value=\"{$_POST["fieldname"]}\" size=\"{$fieldrow["size"]}\" /></td></tr>";
								else echo "<tr><td align=\"right\"><span class=\"ashoporderformlabel\">{$fieldrow["label"]}:</span></td><td class=\"ashoporderformfield\"><textarea name=\"$fieldname\" cols=\"{$fieldrow["size"]}\" rows=\"{$fieldrow["rows"]}\">{$_POST["fieldname"]}</textarea></td></tr>";
							}
						}
					}
				}
				if ($device == "mobile") {
					if ($telesignid && $telesignactivated && $localprocessing == "TRUE") echo "<span class=\"ashoporderformlabel\">".TELESIGNINFO."</span>";
				} else {
					if ($telesignid && $telesignactivated && $localprocessing == "TRUE") echo "
					<tr> 
					<td align=\"right\">&nbsp;</td>
					<td class=\"ashoporderformfield\">
					<span class=\"ashoporderformlabel\">".TELESIGNINFO."</span>
					</td>
					</tr>";
					echo "
					</table>";
				}
				echo "
              <br>
			  <span class=\"ashopalert\"><div ID=\"wait\">&nbsp;</div></span>";
			  if ($device != "mobile") echo "
              <table>
                <tr> 
                  <td colspan=4 align=center> ";
			  echo "
                    <p>";
					if ($gateway == "manual" && @mysqli_num_rows($fieldsresult)) echo "<input type=\"hidden\" name=\"customerinfo\" value=\"\">";
					$md5amount = number_format($amount,2,'.','');
					$authkey = md5($ashoppath.$products."ashopkey$md5amount");
					echo "
					  <input type=\"hidden\" name=\"lang\" value=\"$lang\">
					  <input type=\"hidden\" name=\"invoice\" value=\"$invoice\">
					  <input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">
					  <input type=\"hidden\" name=\"payoption\" value=\"$payoption\">
					  <input type=\"hidden\" name=\"products\" value=\"$products\">
				      <input type=\"hidden\" name=\"localprocessing\" value=\"$localprocessing\">
				      <input type=\"hidden\" name=\"description\" value=\"$description\">
					  <input type=\"hidden\" name=\"emerchantquote\" value=\"$emerchantquote\">
					  <input type=\"hidden\" name=\"amount\" value=\"$amount\">";
					  if ($gateway != "transfirst") echo "<input type=\"hidden\" name=\"authkey\" value=\"$authkey\">";
					  if ($shop && $shop != "1") echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\">";
					  if ($affiliate) echo "<input type=\"hidden\" name=\"affiliate\" value=\"$affiliate\">";
					  if ($gateway == "networkmerchants" && $nmi_recurring) echo "<input type=\"hidden\" name=\"nmi_recurring\" value=\"$nmi_recurring\">";
					  if ($device == "mobile") {
						  echo "<input type=\"submit\" data-role=\"button\" value=\"";
						  if ($localprocessing == "TRUE") echo SUBMITORDERSECURE;
						  else echo SUBMITORDER; 
						  echo "\" name=\"Submit\">";
					  } else {
						  echo "<input type=\"image\" src=\"{$buttonpath}images/submitorder-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"";
						  if ($localprocessing == "TRUE") echo SUBMITORDERSECURE;
						  else echo SUBMITORDER; 
						  echo "\" name=\"Submit\">";
					  }
					  if ($ppecid && $gateway == "paypaldp") echo "<p><span class=\"ashoporderformlabel\">Or... <a href=\"$ashopurl/checkout.php?payoption=$ppecid\"><img border=\"0\" align=\"absmiddle\" src=\"images/btn_xpressCheckoutsm.gif\" alt=\"Place order\"></a></span></p>";
				  if ($device != "mobile") echo "</td>
                </tr>
              </table>";
			  echo "
            </form>
      </table>
    </td>
  </tr>
</table>";

echo "<script language=\"JavaScript\" type=\"text/javascript\">
/* <![CDATA[ */
	var currentcntry = document.orderform.country.value;
	function makechange() {
		if (document.orderform.country.value != window.currentcntry) {
			switchStates(document.getElementById('state'),document.orderform.province,document.orderform.country.value);
			window.currentcntry = document.orderform.country.value;
		}
	}
	var countryinterval = window.setInterval(\"makechange()\",1000);
/* ]]> */
</script>";

}
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/checkout-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/checkout-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/checkout.html");

// Close database...
@mysqli_close($db);
?>
