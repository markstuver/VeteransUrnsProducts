<?php
####################################################################################
##                                                                                ##
##       Signup Customer With Wishlist Member Site - Automation Example		      ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $ashoppath variable to the path where AShop is      ##
##                 installed on the server.										  ##
##              2. Change the $wlmemberurl variable to your WL Member URL		  ##
##              3. Change the $level variable to your membership level SKU		  ##
##              4. Change the $secretkey variable to your WL Member secret word	  ##
##              5. Change the $password variable to a personal password.		  ##
##                                                                                ##
####################################################################################

$ashoppath = "/var/www/vhosts/ashopsoftware.com/subdomains/demoshop/httpdocs";
$wlmemberurl = "http://demoshop.ashopsoftware.com";
$level = "1256680957";
$secretkey = "IKMOgpq7BCIajmst";
$password = "98cmdoiwe8";

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

// Check for spam injection...
$firstname = ashop_mailsafe($_POST["firstname"]);
$lastname = ashop_mailsafe($_POST["lastname"]);
$email = ashop_mailsafe($_POST["email"]);

// Check if all fields were filled in...
if ($firstname=="" || $lastname=="" || $email=="" || $orderid=="") {
	echo "Membership signup error: requried field missing!";
	exit;
}

// Prepare and send the registration data...
$data = array ();
$data['cmd'] = 'CREATE';
$data['transaction_id'] = $orderid;
$data['lastname'] = $lastname;
$data['firstname'] = $firstname;
$data['email'] = $email;
$data['level'] = $level;
$delimiteddata = strtoupper (implode ('|', $data));
$hash = md5 ($data['cmd'] . '__' . $secretkey . '__' . $delimiteddata);
$data['hash'] = $hash;
$ch = curl_init ($wlmemberurl);
curl_setopt ($ch, CURLOPT_POST, true);
curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec ($ch);

// Process return value...
list ($cmd, $url) = explode ("\n", $res);

// Check the result...
if ($cmd == 'CREATE') {
	// Return message with URL...
		if ($receiptformat == "html") $message="<tr><td colspan=\"2\"><table width=\"100%\" cellpadding=\"5\"><tr><td bgcolor=\"#ffffff\" align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"$url\">Click here</a> to activate your membership at: http://demoshop.ashopsoftware.com</font></td></tr></table></td></tr>";
		else $message = "
		Go to: $url to activate your membership.
		";
		echo $message;
	exit;
} else {
	echo "The membership signup was unsuccessful! Call the shop owner to get your membership activated manually.";
	exit;
}
?>