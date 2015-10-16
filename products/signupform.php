<?php
// AShop
// Copyright 2012 - AShop Software - http://www.ashopsoftware.com
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

// Check for GD...
$checkgd = TRUE;
include "includes/captcha.inc.php";

// If GD is available generate random code for security check...
if ($gdversion == 2) {
	$activatesecuritycheck = TRUE;
	// Generate new random code...
	mt_srand ((double)microtime()*1000000);
	$maxrandom = 1000000;
	$random = mt_rand(0, $maxrandom);
} else $activatesecuritycheck = FALSE;

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
include "language/$lang/signupform.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

// Check Facebook login, if activated...
$facebookactivated = FALSE;
if (!empty($facebookappid) && !empty($facebooksecret)) {
	include "includes/facebook/facebook.php";
	$facebook = new Facebook(array('appId'  => $facebookappid,'secret' => $facebooksecret));
	$accesstoken = $facebook->getAccessToken();
	$facebookactivated = TRUE;
	$facebookuser = get_facebook_user($accesstoken);
	$firstname = $facebookuser->first_name;
	if (!empty($firstname)) {
		header("Location: signup.php");
		exit;
	}
}

// Show header using template signup.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/signup-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/signup.html");

echo "
<br /><table class=\"ashopsignupframe\">
  <tr><td align=\"center\"> 

      <p><span class=\"ashopsignupheader\">".CREATEPROFILE."</span></p>
	  <p><span class=\"ashopcustomertext2\">".ALREADYSIGNEDUP." <a href=\"login.php";
if (!empty($shop) && $shop > 1) echo "?shop=$shop";
echo "\"";
if ($device == "mobile") echo " data-ajax=\"false\"";
echo ">".LOGINHERE."</a></span></p>";
if ($facebookactivated) {
	if (empty($firstname)) {
		echo "<p><input type=\"image\"";
		if ($device == "mobile") echo " data-role=\"none\"";
		echo " src=\"http://developers.facebook.com/images/devsite/login-button.png\" onclick=\"fblogin();\" /></p>";
	}
	echo "
	<div id=\"fb-root\"></div>
	<script language=\"JavaScript\" type=\"text/javascript\">

	function fblogin() {
		FB.login(function(response) {
			if (response.authResponse) {
				document.location.href='$ashopurl/signup.php';
			} 
		}, {scope:'email'});
	}

	window.fbAsyncInit = function() {
		FB.init({appId: '$facebookappid', status: true, cookie: true, xfbml: true, oauth : true});
	};

	(function(d){
		var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
		js = d.createElement('script'); js.id = id; js.async = true;
		js.src = \"//connect.facebook.net/en_US/all.js\";
		d.getElementsByTagName('head')[0].appendChild(js);
	}(document));

    </script>";
}
echo "
      <p align=\"left\"><span class=\"ashopcustomertext2\">".SIGNUPMESSAGE."$ashopname...</span></p>
      </td>
  </tr>
  <tr align=\"center\"> 
    <td>";
echo "
      <table class=\"ashopsignupbox\">
        <tr align=\"center\"> 
          <td>
            <form action=\"signup.php\" method=\"post\" name=\"orderform\"";
if ($device == "mobile") echo " data-ajax=\"false\"";			
			echo ">";
if ($device == "mobile") echo "
			<div data-role=\"fieldcontain\"><label for=\"firstname\">".FIRSTNAME.":</label><input type=\"text\" name=\"firstname\" id=\"firstname\" size=\"30\" /></div>
			<div data-role=\"fieldcontain\"><label for=\"lastname\">".LASTNAME.":</label><input type=\"text\" name=\"lastname\" id=\"lastname\" size=\"30\" /></div>
			<div data-role=\"fieldcontain\"><label for=\"email\">".EMAIL.":</label><input type=\"text\" name=\"email\" id=\"email\" size=\"30\" /></div>
			<div data-role=\"fieldcontain\"><label for=\"password\">".PASSWORD.":</label><input type=\"password\" name=\"password\" id=\"password\" size=\"15\" /></div>
			<div data-role=\"fieldcontain\"><label for=\"confirmpassword\">".CONFIRMPASSWORD.":</label><input type=\"password\" name=\"confirmpassword\" id=\"confirmpassword\" size=\"15\" /></div>
";
else echo "
              <table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"540\">
                <tr> 
                  <td align=\"right\" width=\"30%\"><span class=\"ashopcustomertext3\">".FIRSTNAME.":</span></td>
                  <td width=\"70%\" align=\"left\"> 
                    <input type=\"text\" name=\"firstname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".LASTNAME.":</span></td>
                  <td align=\"left\"> 
                    <input type=\"text\" name=\"lastname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".EMAIL.":</span></td>
                  <td align=\"left\"> 
                    <input type=\"text\" name=\"email\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".PASSWORD.":</span></td>
                  <td align=\"left\"> 
                    <input type=\"password\" name=\"password\" size=\"15\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopcustomertext3\">".CONFIRMPASSWORD.":</span></td>
                  <td align=\"left\"> 
                    <input type=\"password\" name=\"confirmpassword\" size=\"15\" />
                  </td>
                </tr>";
if ($activatesecuritycheck) {
	if ($device == "mobile") echo "<div data-role=\"fieldcontain\"><label for=\"securitycode\">".SECURITYCODE.":</label> <img src=\"includes/captcha.inc.php?action=generatecode&amp;random=$random\" border=\"1\" id=\"securitycode\" alt=\"Security Code\" title=\"Security Code\" /></div>
	<div data-role=\"fieldcontain\"><label for=\"securitycheck\">".TYPESECURITYCODE.":</label><input type=\"text\" name=\"securitycheck\" id=\"securitycheck\" size=\"10\" /><input type=\"hidden\" name=\"random\" value=\"$random\" /></div>";
	else echo "<tr><td align=\"right\"><span class=\"ashopcustomertext3\">".SECURITYCODE.":</span></td><td valign=\"top\" align=\"left\"><img src=\"includes/captcha.inc.php?action=generatecode&amp;random=$random\" border=\"1\" alt=\"Security Code\" title=\"Security Code\" /></td></tr><tr><td align=\"right\"><span class=\"ashopcustomertext3\">".TYPESECURITYCODE.":</span></td><td valign=\"top\" align=\"left\"><input type=\"text\" name=\"securitycheck\" size=\"10\" /><input type=\"hidden\" name=\"random\" value=\"$random\" /></td></tr>";
}
if ($device == "mobile") echo "<fieldset data-role=\"controlgroup\"><input type=\"checkbox\" id=\"allowemail\" name=\"allowemail\" /><label for=\"allowemail\">".YESEMAILME."</label></fieldset>";
else echo "<tr>
                  <td align=\"right\">&nbsp;</td>
                  <td align=\"left\"><span class=\"ashopcustomertext3\"> 
                    <input type=\"checkbox\" name=\"allowemail\" /> ".YESEMAILME."</span>
                  </td>
		   </tr>";
if ($device == "mobile") echo "<input type=\"submit\" name=\"Submit\" data-role=\"button\" value=\"".REGISTER."\" />";
else echo "<tr> 
                  <td colspan=\"2\" align=\"center\"><br /><input type=\"image\" src=\"{$buttonpath}images/register-$lang.png\" class=\"ashopbutton\" style=\"border: none;\" alt=\"".REGISTER."\" name=\"Submit\" /></td>
                </tr>
              </table>
";
if (!empty($shop) && $shop > 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
echo "
            </form>
			</td>
			</tr>
      </table>
    </td>
  </tr>
</table>";

// Show footer using template signup.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/signup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/signup-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/signup.html");
?>