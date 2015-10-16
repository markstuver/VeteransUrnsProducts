<?php
@set_time_limit(0);
####################################################################################
##                                                                                ##
##					 Deliver Key Code From Repository Product			          ##
##                                                                                ##
####################################################################################

$keycodepass = "secretpassword";

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "../admin/keycodes.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Handle unlock keys...
$unlockkeystring = "";
$adminunlockkeystring = "";
if ($keycodepass == $keycodepassword) {
	$sql="SELECT name FROM product WHERE productid='$repositoryproduct'";
	$result = @mysqli_query($db, "$sql");
	$thisproductname = @mysqli_result($result,0,"name");
	$sql="SELECT * FROM unlockkeys WHERE productid='$repositoryproduct' AND orderid IS NULL";
	$result = @mysqli_query($db, "$sql");
	$numberofkeys = @mysqli_num_rows($result)-1;
	if ($randomkeycodes == "1") {
		// Select a random unused unlock key for this product...
		list($usec, $sec) = explode(' ', microtime());
		$make_seed = (float) $sec + ((float) $usec * 100000);
		mt_srand($make_seed);
		$randval = mt_rand(0, $numberofkeys);
		$keytext = @mysqli_result($result,$randval,"keytext");
		$keyid = @mysqli_result($result,$randval,"keyid");
	} else {
		$keytext = @mysqli_result($result,0,"keytext");
		$keyid = @mysqli_result($result,0,"keyid");
	}
	if (!empty($keycodeencryptionkey) && !empty($keytext)) {
		$keytext = trim($keytext);
		$keytext = ashop_decrypt($keytext, $keycodeencryptionkey);
	}
	if (!$keytext && !$thisnoticesent) {
		if ($unlockkeystring) $unlockkeystring .= "\n";
		$unlockkeystring .= "Your unlock key for $thisproductname will soon be sent to you by email.\n";
		$adminunlockkeystring.="<p>A customer has purchased <b>$thisproductname</b> but there was no unused unlock key available in the database for the shop $ashopname! Send an unlock key by email to <a href=\"mailto:$email\">".stripslashes($firstname)." ".stripslashes($lastname)."</a>. You should also <a href=\"$ashopurl/admin\">click here</a> to login to the administration area for your shop. From there you will be able to refill the unlock keys by editing the product in your catalogue.</p>";
	} else {
		if ($receiptformat == "html") $unlockkeystring="<tr><td colspan=\"2\"><table width=\"100%\" cellpadding=\"5\"><tr><td bgcolor=\"#ffffff\" align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$productname: <b>$keytext</b></font></td></tr></table></td></tr>";
		else $unlockkeystring = "$productname: $keytext\r\n";
	}
	
	// Assign this unlock key to the customer...
	$sql="UPDATE unlockkeys SET orderid='$orderid' WHERE keyid='$keyid'";
	$result = @mysqli_query($db, "$sql");

	// Alert shop administrator if the database is running low on available keys...
	if ($keytext && $numberofkeys < 5) {
		$adminunlockkeystring.="<p>The number of unused unlock keys for <b>$thisproductname</b> in the shop $ashopname is low! <a href=\"$ashopurl\admin\">Click here</a> to login to the administration area for your shop. From there you will be able to refill the unlock keys by editing the product in your catalogue.</p>";
	}

	if ($adminunlockkeystring) {
		$message="<html><head><title>$ashopname - Unlock Key Notice</title></head><body><font face=\"$font\">";
		if ($adminunlockkeystring) $message.= "$adminunlockkeystring";	
		$message.="</font></body></html>";
		$headers = "From: ".un_html($ashopname,1)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname,1)." - Unlock Key Notice: $orderid","$message","$headers");
	}


	if ($unlockkeystring) echo $unlockkeystring;
}
?>