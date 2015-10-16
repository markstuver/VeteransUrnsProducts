<?php
####################################################################################
##                                                                                ##
##            Signup With Aweber List - Automation Example for AShop V            ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $ashoppath variable to the path where AShop is      ##
##                 installed on the server.										  ##
##              2. Change the $password variable to a secret password that will   ##
##                 be used to securely add new information to the database.       ##
##              3. Change the $aweberemail variable to your Aweber parser email   ##
##                                                                                ##
####################################################################################

$ashoppath = '..';
$password = "yourpass";
$aweberemail = "yourlist@aweber.com";

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
if (($firstname=="") || ($lastname=="")
|| ($email=="")) {
	echo "Aweber signup error: requried field missing!";
	exit;
}

// Send a message to sign up customer with the list...
$headers = "From: $firstname $lastname<$email>\nX-Sender: <$email>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$email>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
@mail("$aweberemail","Signup through shopping cart"," ","$headers");
?>