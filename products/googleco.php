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

// Initialize variables...
if (!isset($shop)) $shop = 1;
if (!empty($shop) && !is_numeric($shop)) $shop = 1;

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if Google Checkout is available...
$gcocheckresult = @mysqli_query($db, "SELECT * FROM payoptions WHERE gateway='googleco' AND userid='$shop'");
$gcoid = @mysqli_result($gcocheckresult,0,"merchantid");
$gcokey = @mysqli_result($gcocheckresult,0,"secret");
$gcotest = @mysqli_result($gcocheckresult,0,"testmode");
$payoptionid = @mysqli_result($gcocheckresult,0,"payoptionid");
$fullproductstring = $payoptionid."ashoporderstring";
$productstring = "";
$description;
$items = array();

if (empty($gcoid)) {
	header("Location: $ashopurl");
	exit;
}

// Retrieve notification...
$serialnumber = $_POST["serial-number"];
if (!empty($serialnumber)) $notification = ashop_googlegetnotification($serialnumber,$gcoid,$gcokey,$gcotest);
else {
	header("Location: $ashopurl");
	exit;
}
// Acknowledge notification...
if (!empty($notification)) echo "<notification-acknowledgment xmlns=\"http://checkout.google.com/schema/2\" serial-number=\"$serialnumber\"/>";

// Parse notification...
if (!empty($notification)) {
	$notificationvars = explode("&",$notification);
	foreach($notificationvars as $notificationpair) {
		$notificationvar = explode("=",$notificationpair);
		if ($notificationvar[0] == "_type") $notificationvar[0] = "type";
		$notificationvar[0] = str_replace(".","_",$notificationvar[0]);
		$notificationvar[0] = str_replace("-","_",$notificationvar[0]);
		$$notificationvar[0] = urldecode($notificationvar[1]);
		if (substr($notificationvar[0],0,25) == "shopping_cart_items_item_") {
			$itemvar = str_replace("shopping_cart_items_item_","",$notificationvar[0]);
			$itemnumber = substr($itemvar,0,strpos($itemvar,"_"));
			$itemdetail = substr($itemvar,strpos($itemvar,"_")+1);
			$items[$itemnumber]["$itemdetail"] = urldecode($notificationvar[1]);
		}
	}
	if (!empty($shopping_cart_merchant_private_data)) {
		$ipnumber = str_replace("<merchant-note>","",$shopping_cart_merchant_private_data);
		$ipnumber = str_replace("</merchant-note>","",$ipnumber);
	}
} else {
	header("Location: $ashopurl");
	exit;
}

// Update the status of an existing order, if it has been authorized for charge by Google...
if ($type == "authorization-amount-notification" && !empty($google_order_number)) @mysqli_query($db, "UPDATE orders SET status='GOOGLEAUTH' WHERE remoteorderid='$google_order_number'");

// Register risk information on existing order...
if ($type == "risk-information-notification" && !empty($google_order_number) && !empty($risk_information_ip_address)) @mysqli_query($db, "UPDATE orders SET ip='$risk_information_ip_address' WHERE remoteorderid='$google_order_number'");

// Handle cancellations...
if ($type == "order-state-change-notification" && !empty($new_financial_order_state) && ($new_financial_order_state == "CANCELLED" || $new_financial_order_state == "CANCELLED_BY_GOOGLE")) {
	$result = @mysqli_query($db, "SELECT orderid, paid FROM orders WHERE remoteorderid='$google_order_number'");
	if (@mysqli_num_rows($result)) {
		$orderid = @mysqli_result($result,0,"orderid");
		$paid = @mysqli_result($result,0,"paid");
		if (!empty($orderid)) {
			if (empty($paid)) {
				@mysqli_query($db, "DELETE FROM orders WHERE orderid='$orderid'");
				@mysqli_query($db, "DELETE FROM orderaffiliate WHERE orderid='$orderid'");
				@mysqli_query($db, "DELETE FROM pendingorderaff WHERE orderid='$orderid'");
				@mysqli_query($db, "UPDATE unlockkeys SET orderid=NULL WHERE orderid='$orderid'");
			} else @mysqli_query($db, "UPDATE orders SET status='GOOGLECANCELLED' WHERE orderid='$orderid'");
		}
	}
}

// Register new order...
if ($type == "new-order-notification" && !empty($google_order_number)) {
	$checkduplicate = @mysqli_query($db, "SELECT * FROM orders WHERE remoteorderid='$google_order_number'");
	if (@mysqli_num_rows($checkduplicate)) exit;
	if(!empty($items)) foreach($items as $item) {
		$productidarray = explode("-",$item["merchant_item_id"]);
		if(!empty($productidarray[0])) $affiliate = $productidarray[0];
		$fullproductstring .= $productidarray[1];
		$productstring .= $productidarray[1];
		$quantity = $item["quantity"];
		$thisdescription = $item["item_name"];
		if (!empty($description)) $description .= ", ";
		$description .= $thisdescription;
	}
	$salestax = number_format($order_adjustment_total_tax,2,'.','');
	$fullproductstring .= "stb".$salestax."a";
	$productstring .= "stb".$salestax."a";
	$safemysqldescription = @mysqli_escape_string($db, $description);
	$securitycheck = md5("$google_order_number$gcokey");
	$fullname = $buyer_billing_address_contact_name;
	$names = explode(" ",$fullname);
	$firstname = $names[0];
	$lastname = $names[1];
	$email = $buyer_billing_address_email;
	if (empty($email)) {
		ashop_mail($ashopemail,"Google Checkout Error!","The order: $google_order_number from Google Checkout did not provide an email for the customer! It will need to be handled manually.");
		exit;
	}
	if ($buyer_marketing_preferences_email_allowed == "true") $allowemail = "1";
	$tempdate = date("Y-m-d H:i:s", time()+$timezoneoffset);
	$customerresult = @mysqli_query($db, "SELECT customerid FROM customer WHERE email='$email'");
	if (@mysqli_num_rows($customerresult)) {
		$customerid = @mysqli_result($customerresult,0,"customerid");
		$customerresult2 = @mysqli_query($db, "SELECT shippingid FROM shipping WHERE customerid='$customerid'");
		if (@mysqli_num_rows($customerresult)) $customerid = @mysqli_result($customerresult2,0,"shippingid");
		else {
			@mysqli_query($db, "INSERT INTO shipping (shippingfirstname, shippinglastname, customerid) VALUES ('$firstname', '$lastname', '$customerid')");
			$customerid = @mysqli_insert_id($db);
		}
	} else {
		@mysqli_query($db, "INSERT INTO customer (username, firstname, lastname, email, affiliateid, ip) VALUES ('$email', '$firstname', '$lastname', '$email', '$affiliate', '$ipnumber')");
		$customerid = @mysqli_insert_id($db);
		@mysqli_query($db, "INSERT INTO shipping (shippingfirstname, shippinglastname, customerid) VALUES ('$firstname', '$lastname', '$customerid')");
		$customerid = @mysqli_insert_id($db);
	}

	$sql = "INSERT INTO orders (remoteorderid, customerid, products, description, price, ip, userid, language, returnurl, allowemail, source, tempdate, affiliateid) VALUES ('$google_order_number','$customerid','$productstring','$safemysqldescription','$order_total', '$ipnumber', '|1|', '$defaultlanguage', '$ashopurl', '$allowemail', 'Shopping Cart', '$tempdate', '$affiliate')";
	$result = @mysqli_query($db, "$sql");
	$invoice = @mysqli_insert_id($db);

	$email = urlencode($email);

	$querystring = "email=$email&firstname=$firstname&lastname=$lastname&address=$buyer_billing_address_address1&city=$buyer_billing_address_city&zip=$buyer_billing_address_postal_code&state=$buyer_billing_address_region&country=$buyer_billing_address_country_code&phone=$buyer_billing_address_phone&remoteorderid=$google_order_number&responsemsg=NEW&invoice=$invoice&scode=$securitycheck&amount=$order_total&products=$fullproductstring&description=$description&affiliate=$affiliate";
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
	fputs ($fp, $header . $querystring);
	$response = "";
	while (!feof($fp)) $response .= fread ($fp, 8192);
	fclose ($fp);
}
?>