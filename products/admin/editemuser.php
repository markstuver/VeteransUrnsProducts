<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "checklicense.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/salesoffice.inc.php";
include "ashopconstants.inc.php";

if ($userid != "1") {
	header("Location: index.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($remove && $username) {
	if ($yes) {
       $sql="DELETE FROM emerchant_user WHERE username='$emusername'";
       $result = @mysqli_query($db, $sql);
	   header("Location: emuseradmin.php");
    }
	elseif ($no) header("Location: emuseradmin.php");
	else {
		echo "$header
<div class=\"heading\">".REMOVEAUSER."</div><center>
        <p>".AREYOUSUREREMOVEUSER.": $emusername?</font></p>
		<form action=\"editemuser.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"emusername\" value=\"$emusername\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form>
		</center>
        $footer";
		exit;
	}
} 

// Store updated data...
if ($update) {
	if ($empassword != $empassword2) $error = "<CENTER><P><font size=\"3\" color=\"#FF0000\"><b>".PASSWORDSNOTMATCH."</b></font></P></CENTER>";
	else {
		// Initiate password hashing...
		include "$ashoppath/includes/PasswordHash.php";
		$passhasher = new PasswordHash(8, FALSE);

		// Generate password hash...
		$passhash = $passhasher->HashPassword($empassword);

		if ($new == "true") $sql="INSERT INTO emerchant_user (username,password) VALUES ('$newusername','$passhash')";
		else $sql="UPDATE emerchant_user SET username='$newusername', password='$passhash' WHERE username='$emusername'";
		$result = @mysqli_query($db, "$sql");
		if (!empty($email) && $new == "true") {
			$signedup = date("Y-m-d H:i:s", time()+$timezoneoffset);
			@mysqli_query($db, "INSERT INTO affiliate (user,password,firstname,lastname,email,commissionlevel,signedup) VALUES ('$newusername','$empassword','$firstname','$lastname','$email','1','$signedup')");
		}
		header("Location: emuseradmin.php"); 
		exit;
	}
}

// Get customer information from database...
$sql="SELECT * FROM emerchant_user WHERE username='$emusername'";
$result = @mysqli_query($db, "$sql");

// Close database...
@mysqli_close($db);

echo "$header
<div class=\"heading\">".SALESOFFICEUSERS."</div><center>$error
    <form action=\"editemuser.php\" method=\"post\">";
if ($new == "true") echo "<input type=\"hidden\" name=\"new\" value=\"true\">";
echo "
	<input type=\"hidden\" name=\"emusername\" value=\"$emusername\">
    <table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".USERNAME.":</font></td>
    <td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">";
	if ($emusername == "admin") echo "admin<input type=\"hidden\" name=\"newusername\" value=\"admin\">";
	else echo "<input type=text name=\"newusername\" value=\"$emusername\" size=15>";
	echo "</font></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PASSWORD.":</font></td>
    <td align=\"left\"><input type=\"password\" name=\"empassword\" value=\"$empassword\" size=15></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CONFIRM.":</font></td>
    <td align=\"left\"><input type=\"password\" name=\"empassword2\" value=\"\" size=15></td></tr>";
	if ($salesrep == "true") echo "
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"firstname\" value=\"$firstname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"lastname\" value=\"$lastname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".EMAIL.":</font></td>
    <td align=\"left\"><input type=text name=\"email\" value=\"$email\" size=40></td></tr>";
	echo "
    <tr><td align=\"left\"></td><td align=\"left\">&nbsp;&nbsp;&nbsp;<input type=\"submit\" value=\"".UPDATE."\" name=\"update\"></td></tr>
    </table></form>
	</font></center>
	$footer";
?>