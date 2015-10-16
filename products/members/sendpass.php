<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "../admin/checklicense.inc.php";

// Apply selected theme...
$templatepath = "";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

if ($user != "") {

  // Open database...
  $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

  // Get password and email from database...
  $sql="SELECT password,email FROM user WHERE username='$user'";
  $result = @mysqli_query($db, "$sql");
  if (@mysqli_num_rows($result) != 0) {

    // Store in variables...
    $password = @mysqli_result($result, 0, "password");
	$email = @mysqli_result($result, 0, "email");
  } else {
	unset($password);
	unset($email);
  }

  // Close database...

  @mysqli_close($db);


  if ($user && $email) {

    // Send message with password...

    $subject="$ashopname - Shopping Mall";
	$message="<html><head><title>Your Shopping Mall Password</title></head><body><font face=\"$font\"><p>Your shopping mall password for $ashopname is: $password</p></font></body></html>";
	$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
    @ashop_mail("$email","$subject","$message","$headers");
  }

  // Tell shopping mall user that the password has been sent...

  if ($password) {
	  echo "
		  <HTML><title>$ashopname - Shopping Mall</title>
		    <BODY bgcolor=\"$bgcolor\" text=\"$textcolor\">
	        <CENTER>
				<a href=\"$ashopurl\"><img src=\"../images/logo.gif\" border=\"0\" alt=\"AShop\"></a><br>
				<H1><font face=\"Arial, Helvetica, sans-serif\" size=\"4\">Your
				password has been sent</font></H1>
				<font face=\"Arial, Helvetica, sans-serif\"><p>Your password has
				been sent to you by email</p><p><a href=\"../admin/login.php\">Login to your Shopping Mall Admin Panel</a></p></font>
			</CENTER>
		    </BODY>
		</HTML>";
	exit;
  } else {
	  echo "
		  <HTML><title>$ashopname - Shopping Mall</title>
		    <BODY bgcolor=\"$bgcolor\" text=\"$textcolor\">
	        <CENTER>
				<H1><a href=\"../index.php\"><img src=\"../images/logo.gif\" border=\"0\" alt=\"$ashopname\"></a><br></H1>
				<H1><font face=\"Arial, Helvetica, sans-serif\" size=\"4\">You are not registered as a shopping mall user</font></H1>
				<p><font face=\"Arial, Helvetica, sans-serif\">
				<a href=\"javascript:history.back()\">Try again!</a></font></p>
			</CENTER>
		    </BODY>
		</HTML>";
	exit;
  }
}


// Show send password page in browser...
if (file_exists("$ashoppath$templatepath/membersignup.html")) {
   $ended = 0;
   $fp = fopen ("$ashoppath$templatepath/membersignup.html","r");
   while (!feof ($fp)) {
      $templateline = fgets($fp, 4096);
	  if (strstr($templateline,"<!-- AShopstart -->")) $ended = 1;
	  if (!$ended) echo $templateline;
   }
   fclose($fp);
   if (!$ended) echo "<p><font face=\"$font\" size=\"2\" color=\"#900000\"><b>Error! Incorrectly formatted template file!</b></font></p>";
}
else echo "<html><head><title>$ashopname - Shopping Mall</title></head>
           <body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\">
           <center>
           <p><img src=\"../images/logo.gif\"></p>";


echo "<table width=\"80%\" cellpadding=\"20\" border=\"0\" align=\"center\"><tr><td>
  <font face=\"Arial, Helvetica, sans-serif\" size=\"4\">Forgot
your password?</font>
  <p align=\"left\"><font face=\"Arial, Helvetica, sans-serif\">Enter your user name
and we will send you the password to the email address you entered
when you signed up as a shopping mall user...</font></p>
<form method=\"post\" action=\"sendpass.php\">
    <table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
      <tr> 
        <td align=\"right\"><font face=\"Arial, Helvetica,
sans-serif\">User name:</font></td>
        <td><font face=\"Arial, Helvetica, sans-serif\"> 
          <input type=\"text\" name=\"user\">
          </font></td>
        <td><font face=\"Arial, Helvetica, sans-serif\"> 
          <input type=\"submit\" value=\"Submit\">
          </font></td>
      </tr>
    </table>
  </form></td></tr></table>";

if (file_exists("$ashoppath$templatepath/membersignup.html")) {
   $ended = 0;
   $fp = fopen ("$ashoppath$templatepath/membersignup.html","r");
   while (!feof ($fp)) {
	  $templateline = fgets($fp, 4096);
      if ($started) echo $templateline;
	  if (strstr($templateline, "<!-- AShopend -->")) $started = 1;
   }
   fclose($fp);
   if (!$started) echo "<p><font face=\"$font\" size=\"2\" color=\"#900000\"><b>Error! Incorrectly formatted template file!</b></font></p>";
}
else echo "</center></body></html>";

?>