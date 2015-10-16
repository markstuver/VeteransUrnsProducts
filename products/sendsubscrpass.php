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

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$email = ashop_mailsafe($email);

if ($subscription && $email) {

  // Get customerid from database...
  $result = @mysqli_query($db, "SELECT customerid FROM customer WHERE email='$email'");
  $customerid = @mysqli_result($result,0,"customerid");

  // Get product name and URL...
  $result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$subscription'");
  $subscriptionproductname = @mysqli_result($result,0,"name");
  $subscriptionurl = @mysqli_result($result,0,"protectedurl");

  // Get password...
  $result = @mysqli_query($db, "SELECT * FROM orders WHERE customerid='$customerid' AND paid != '' ORDER BY date DESC");
  while($row = @mysqli_fetch_array($result)) {
	  if (ashop_checkproduct($subscription, $row["products"])) {
		  $password = $row["password"];
		  break;
	  }
  }
	  
  if ($password) {

    // Send message with password...

    $subject="$ashopname - Subscription Password";
	$message="<html><head><title>Your Subscription Password</title></head><body><font face=\"$font\"><p>Your password for $subscriptionproductname is: $password</p></font></body></html>";
	$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
    @ashop_mail("$email","$subject","$message","$headers");

    echo "
		  <HTML><title>$ashopname - Subscription Password</title>
		    <BODY bgcolor=\"$bgcolor\" text=\"$textcolor\">
	        <CENTER>
				<a href=\"$ashopurl\"><img src=\"images/logo.gif\" border=\"0\" alt=\"AShop\"></a><br>
				<H1><font face=\"Arial, Helvetica, sans-serif\" size=\"4\">Your
				password has been sent</font></H1>
				<font face=\"Arial, Helvetica, sans-serif\"><p>Your password has
				been sent to you by email</p><p><a href=\"$subscriptionurl\">Login to $subscriptionproductname</a></p></font>
			</CENTER>
		    </BODY>
		</HTML>";
	exit;
  } else {
	  echo "
		  <HTML><title>$ashopname - Subscription Password</title>
		    <BODY bgcolor=\"$bgcolor\" text=\"$textcolor\">
	        <CENTER>
				<H1><a href=\"index.php\"><img src=\"images/logo.gif\" border=\"0\" alt=\"$ashopname\"></a><br></H1>
				<H1><font face=\"Arial, Helvetica, sans-serif\" size=\"4\">You have not bought access to this protected area</font></H1>
				<p><font face=\"Arial, Helvetica, sans-serif\">
				<a href=\"javascript:history.back()\">Try again!</a></font></p>
			</CENTER>
		    </BODY>
		</HTML>";
	exit;
  }
}

// Get list of subscription products...
$result = @mysqli_query($db, "SELECT * FROM product WHERE prodtype='subscription' AND active='1' ORDER BY ordernumber");
$subscriptionlist = "";
while($row = @mysqli_fetch_array($result)) $subscriptionlist .= "<option value=\"{$row["productid"]}\">{$row["name"]}</option>\n";

// Show send password page in browser...
// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");

echo "<table width=\"80%\" cellpadding=\"20\" border=\"0\" align=\"center\"><tr><td>
  <font face=\"Arial, Helvetica, sans-serif\" size=\"4\">Forgot
your password?</font>
  <p align=\"left\"><font face=\"Arial, Helvetica, sans-serif\">Select the protected area and enter your email address.<br>We will send you the password...</font></p>
<form method=\"post\" action=\"sendsubscrpass.php\">
    <table width=\"420\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
      <tr> 
        <td align=\"right\"><font face=\"Arial, Helvetica,
sans-serif\">Protected area:<br><br></font></td>
        <td><font face=\"Arial, Helvetica, sans-serif\"> 
          <select name=\"subscription\">$subscriptionlist</select><br><br>
          </font></td>
	   </tr>
	   <tr>
        <td align=\"right\"><font face=\"Arial, Helvetica,
sans-serif\">Email:<br><br></font></td>
        <td><font face=\"Arial, Helvetica, sans-serif\"> 
          <input type=\"text\" name=\"email\" size=\"30\"><br><br>
          </font></td>
		</tr>
		<tr>
		<td>&nbsp;</td>
        <td align=\"right\"><input type=\"submit\" value=\"Submit\"></td>
      </tr>
    </table>
  </form></td></tr></table>";

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");

@mysqli_close($db);
?>