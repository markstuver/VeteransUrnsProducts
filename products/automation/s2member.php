<?php
####################################################################################
##                                                                                ##
##			Signup Customer With S2 Member Site - Automation Example		      ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $ashoppath variable to the path where AShop is      ##
##                 installed on the server.										  ##
##              2. Change the $password variable to a personal password.		  ##
##                                                                                ##
####################################################################################

$ashoppath = "/path/to/your/ashop";
$password = "yourownpassword";

####################################################################################
##                                                                                ##
##                           Do not edit below this.                              ##
##                                                                                ##
####################################################################################

if ($_POST["password"] != $password) exit;

include "$ashoppath/admin/config.inc.php";
include "$ashoppath/admin/ashopfunc.inc.php";

// Validate variables...
foreach($_POST as $variable=>$value) {
	$value = stripslashes($value);
	$value = @mysqli_real_escape_string($db, $value);
	$value = str_replace("\'","",$value);
	$value = str_replace("\"","",$value);
	$value = str_replace("/","",$value);
	$value = str_replace("\n","",$value);
	$value = str_replace(";","",$value);
}

// Check if all fields were filled in...
if ($level=="" || $s2memberurl=="" || $secretkey == "" || $orderid=="") {
	echo "Membership signup error: requried field missing!";
	exit;
}

// Prepare and send the registration data...
$reg = md5($orderid.$level.$secretkey);
$data = "make_registrationlink=1&orderid=$orderid&item=$level&reg=$reg";
$ch = curl_init ($s2memberurl);
curl_setopt ($ch, CURLOPT_POST, true);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec ($ch);

// Process return value...
$res = explode("<!-- StartAShopS2Member-",$res);
$res = explode("-EndAShopS2Member -->",$res[1]);
$registrationurl = $res[0];

// Check the result...
if (!empty($registrationurl) && (strstr($registrationurl,"http://tinyurl.com") || strstr($registrationurl,$s2memberurl))) {
	// Return message with URL...
		if ($receiptformat == "html") $message="<tr><td colspan=\"2\"><table width=\"100%\" cellpadding=\"5\"><tr><td bgcolor=\"#ffffff\" align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"$registrationurl\">Click here</a> to activate your membership at: http://demoshop.ashopsoftware.com</font></td></tr></table></td></tr>";
		else $message = "
		Go to: $registrationurl to activate your membership.
		";
		echo $message;
	exit;
} else {
	echo "The membership signup was unsuccessful! Call the shop owner to get your membership activated manually.";
	exit;
}
?>