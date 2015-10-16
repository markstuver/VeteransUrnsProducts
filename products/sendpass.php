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
include "admin/customers.inc.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/sendpass.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/customer.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

if ($email != "") {

  // Open database...
  $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");


  // Get password and email from database...
  $sql="SELECT password FROM customer WHERE username='$email' OR email='$email'";
  $result = @mysqli_query($db, "$sql");
  if (@mysqli_num_rows($result) != 0) {

    // Store in variables...
    $password = @mysqli_result($result, 0, "password");
  } else {
	  unset($password);
	  unset($email);
  }

  // Close database...
  @mysqli_close($db);


  if ($email != "" && $password != "") {

	// Decrypt password if encryption key is available...
	if (!empty($customerencryptionkey) && !empty($password)) $password = ashop_decrypt($password, $customerencryptionkey);

    // Send message with password...

    $subject="$ashopname - ".CUSTOMERPROFILE;
	$message="<html><head><title>".YOURPASSWORD."</title></head><body><font face=\"$font\"><p>".YOURPASSWORDFOR." $ashopname ".IS.": <b><i>$password</i></b></p></font></body></html>";
	$headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
    @ashop_mail("$email","$subject","$message","$headers");
  }

  // Tell the customer that the password has been sent...

  if ($password) {
	  if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/customer-$lang.html");
	  else ashop_showtemplateheader("$ashoppath$templatepath/customer.html");
	  echo "<table class=\"ashopmessagetable\" align=\"center\">
	  <tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".PASSWORDSENT."</span></p>
	  <p><span class=\"ashopmessage\">".PASSWORDSENTBYEMAIL."</span></p>
	  <p><span class=\"ashopmessage\"><a href=\"login.php\"";
	  if ($device == "mobile") echo " data-ajax=\"false\"";
	  echo ">".CUSTOMERLOGIN."</a></span></p></td></tr></table>";
	  if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/customer-$lang.html");
	  else ashop_showtemplatefooter("$ashoppath$templatepath/customer.html");
	  exit;
  } else {
	  if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/customer-$lang.html");
	  else ashop_showtemplateheader("$ashoppath$templatepath/customer.html");
	  echo "<table class=\"ashopmessagetable\" align=\"center\">
	  <tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".NOTREGISTERED."</span></p>
	  <p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\"";
	  if ($device == "mobile") echo " data-ajax=\"false\"";
	  echo ">".TRYAGAIN."</a></span></p></td></tr></table>";
	  if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/customer-$lang.html");
	  else ashop_showtemplatefooter("$ashoppath$templatepath/customer.html");
	  exit;
  }
}


// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/customer-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/customer.html");


echo "<br><table class=\"ashopcustomerloginframe\"><tr><td>
  <span class=\"ashopcustomerheader\">".FORGOTPASSWORD."</span>
  <p align=\"left\"><span class=\"ashopcustomertext2\">".ENTERUSERNAME."</span></p>
<form method=\"post\" action=\"sendpass.php\"";
if ($device == "mobile") echo " data-ajax=\"false\"";
echo ">";
if ($device != "mobile") {
	$tdwidth = 220;
	echo "
    <table width=\"400\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
}
if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"email\">".USERNAME.":</label><input type=\"text\" name=\"email\" id=\"email\" size=\"30\" /></div>
<input type=\"submit\" name=\"Submit\" data-role=\"button\" value=\"".SUBMIT."\" />";
else echo "
      <tr> 
        <td align=\"right\"><span class=\"ashopcustomertext2\">".USERNAME.":</span></td>
        <td width=\"$tdwidth\">&nbsp;<input type=\"text\" name=\"email\" size=\"30\"></td>
        <td><input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".SUBMIT."\" name=\"Submit\" /></td>
      </tr>
    </table>";
if (!empty($shop) && $shop > 1) echo "
    <input type=\"hidden\" name=\"shop\" value=\"$shop\">";
echo "
  </td></tr></table></form>";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/customer-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/customer.html");
?>