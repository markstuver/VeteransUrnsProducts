<?php
####################################################################################
##                                                                                ##
##       Signup Affiliate on Purchase - Automation Example for AShop Deluxe       ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $ashoppath variable to the path where AShop is      ##
##                 installed on the server.										  ##
##              2. Change the $password variable to a secret password that will   ##
##                 be used to securely add new information to the database.       ##
##                                                                                ##
####################################################################################

$ashoppath = '/home/httpd/vhosts/ashopsoftware.com/httpdocs/ashopdelus';
$password = "mypassword";

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
$address = ashop_mailsafe($_POST["address"]);
$state = ashop_mailsafe($_POST["state"]);
$zip = ashop_mailsafe($_POST["zip"]);
$city = ashop_mailsafe($_POST["city"]);
$country = ashop_mailsafe($_POST["country"]);
$phone = ashop_mailsafe($_POST["phone"]);
$paypalid = ashop_mailsafe($_POST["paypalid"]);

// Check if all fields were filled in...
if (($firstname=="") || ($lastname=="")
|| ($email=="") || ($address=="") || ($zip=="") || ($city=="") || ($country=="")) {
	echo "Affiliate signup error: requried field missing!";
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if affiliate is signed up already...
$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE email='$email'");
if (@mysqli_num_rows($result)) exit;

// Generate a unique password...
function makePassword() {
   $alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
   srand ((double) microtime() * 1000000);
   $pwLength = "7"; // this sets the limit on how long the password is.
   for($i = 1; $i <=$pwLength; $i++) {
      $newPass .= $alphaNum[(rand(0,31))];
   }
   return ($newPass);
}
$affpassword = makePassword();
$unique = 0;
while (!$unique) {
   $sql="SELECT password FROM affiliate WHERE password='$affpassword'";
   $result = @mysqli_query($db, "$sql");
   if (@mysqli_num_rows($result) == 0) $unique = 1;
   else $affpassword = makePassword();
}

// Generate a unique user name...
$username = trim(strtolower($lastname));
$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE user='$username'");
if (@mysqli_num_rows($result)) {
	$usernumber = 1;
	$unique = 0;
	while(!$unique && $usernumber < 1000) {
		$newusername = $username.$usernumber;
		$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE user='$newusername'");
		if (@mysqli_num_rows($result)) $usernumber++;
		else {
			$unique = 1;
			$affuser = $newusername;
		}
	}
} else $affuser = $username;

// Generate a unique referral code for manual referral...
$referralcode = substr(strtolower($firstname),0,2).substr(strtolower($lastname),0,3);
$referralcode .= str_repeat("0",5-strlen($referralcode));
$refnumber = 1;
$newreferralcode = $referralcode;
$referralcodenumber = $referralcode.sprintf("%03d",$refnumber);
$unique = 0;
$n = 0;
$m = ord("a");
while(!$unique) {
	while(!$unique && $refnumber < 1000) {
		$result = @mysqli_query($db, "SELECT * FROM affiliate WHERE referralcode='$referralcodenumber' OR user='$referralcodenumber'");
		if(@mysqli_num_rows($result)) {
			$refnumber++;
			$referralcodenumber = $newreferralcode.sprintf("%03d",$refnumber);
		} else $unique = 1;
	} if(!$unique) {
		$refnumber = 1;
		$newreferralcode = substr_replace($referralcode, chr($m), $n, 1);
		$referralcodenumber = $newreferralcode.sprintf("%03d",$refnumber);
		if($m == ord("z")) {
			$n++;
			$m = ord("a");
		} else $m++;
	}
}

// Set current date and time...
$date = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Store affiliate data...
if ($paypalid) $sql = "INSERT INTO affiliate (user, password, firstname, lastname,
email, address, state, zip, city, country, phone, url, affiliateid, paypalid, signedup, updated, referralcode, commissionlevel) VALUES ('$affuser', '$affpassword', '$firstname', '$lastname', '$email', '$address', '$state', '$zip', '$city', '$country', '$phone', '$url', 0, '$paypalid', '$date', '$date', '$referralcodenumber', 1)";
else $sql = "INSERT INTO affiliate (user, password, firstname, lastname,
email, address, state, zip, city, country, phone, url, affiliateid, signedup, updated, referralcode, commissionlevel) VALUES ('$affuser', '$affpassword', '$firstname', '$lastname', '$email', '$address', '$state', '$zip', '$city', '$country', '$phone', '$url', 0, '$date', '$date', '$referralcodenumber', 1)";
$result = @mysqli_query($db, "$sql");
$affiliateid = @mysqli_insert_id($db);

// Close database...

@mysqli_close($db);

// Return message with password to affiliate...
if ($receiptformat == "html") $message="<tr><td colspan=\"2\"><table width=\"100%\" cellpadding=\"5\"><tr><td bgcolor=\"#ffffff\" align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">You have been signed up with the $ashopname affiliate program!<br>Your user name is: <b>$affuser</b>, and your password: <b>$affpassword</b><br>To manually refer customers use this referralcode: <b>$referralcodenumber</b><br>Login to get your link code and check your stats at <b><a href=\"$ashopurl/affiliate/login.php\">$ashopurl/affiliate/login.php</a></b></font></td></tr></table></td></tr>";
else $message = "
You have been signed up with the $ashopname affiliate program!
Your user name is: $affuser, and your password: $affpassword
To manually refer customers use this referralcode: $referralcodenumber
Login to get your link code and check your stats at: $ashopurl/affiliate/login.php
";

echo $message;

exit;
?>