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

if (empty($referer)) $referer = $_SERVER["HTTP_REFERER"];
if (!ashop_is_url($referer)) $referer = $_SERVER["HTTP_REFERER"];
$referer = str_replace($ashopurl,"",$referer);
$referer = str_ireplace("http://","",$referer);
$referer = str_ireplace("https://","",$referer);
if (empty($referer)) {
	if (empty($shop) || !is_numeric($shop)) $referer = "index.php";
	else $referer = "index.php?shop";
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check Facebook login, if activated...
$facebookactivated = FALSE;
$facebookid = "";
$facebookerror = FALSE;
if (!empty($facebookappid) && !empty($facebooksecret)) {
	include "includes/facebook/facebook.php";
	$facebook = new Facebook(array('appId'  => $facebookappid,'secret' => $facebooksecret));
	$accesstoken = $facebook->getAccessToken();
	$facebookactivated = TRUE;
	$facebookuser = get_facebook_user($accesstoken);
	$facebookid = $facebookuser->id;
	if (!empty($facebookid)) {
		$result = @mysqli_query($db,"SELECT * FROM customer WHERE facebookid='$facebookid'");
		if (@mysqli_num_rows($result)) {
			$username = @mysqli_result($result,0,"username");
			$password = @mysqli_result($result,0,"password");
		} else {
			header("Location: signup.php");
			exit;
		}
	}
}

if ($QUERY_STRING == "logout") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	SetCookie("customersessionid");
	SetCookie("bidderhash");
	SetCookie("shipid");
	SetCookie("discountall");
	if (!empty($_COOKIE) && is_array($_COOKIE)) foreach ($_COOKIE as $cookiename=>$cookievalue) {
		if (strstr($cookiename,"discount")) SetCookie($cookiename);
	}

	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}
	session_destroy();

	if (empty($facebookid)) {
		header("Location: login.php?loggedout");
		exit;
	} else {
		$redirecturl = urlencode("$ashopurl/login.php?loggedout");
		header("Location: https://www.facebook.com/logout.php?next=$redirecturl&access_token=$accesstoken");
		exit;
	}
}

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
include "language/$lang/login.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/customer.html")) $templatepath = "/members/files/$ashopuser";

// Check if a mobile device is being used...
$device = ashop_mobile();

if (!$username || !$password) {
	// Print header from template...
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/customer-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/customer.html");
	echo "<br />";
	if ($device != "mobile") echo "
		<table class=\"ashopcustomerloginframe\"><tr><td align=\"left\">";
	echo "<span class=\"ashopcustomerheader\">".LOGINMESSAGE."</span>";
	if ($QUERY_STRING == "loggedout") echo "<p><span class=\"ashopcustomeralert2\">".LOGGEDOUT."</span></p>";
	if ($newregistered) echo "<p><span class=\"ashopcustomertext2\">".SIGNUPMESSAGE1."</span></p>";
	else echo "<p><span class=\"ashopcustomertext2\">".SIGNUPMESSAGE2."</span></p>";
	if ($QUERY_STRING == "retrylogin") echo "<p><span class=\"ashopcustomeralert\">".WRONGPASS."<br />".TRYAGAIN."</span></p>";
	echo "<form action=\"login.php\" method=\"post\"";
	if ($device == "mobile") echo " data-ajax=\"false\"";
	echo ">";
	if ($device != "mobile") {
		$tdwidth = 220;
		$fieldwidth = $tdwidth - 20;
		echo "
		<table width=\"400\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
	}
	if ($device == "mobile") {
		echo "<div data-role=\"fieldcontain\"><label for=\"username\">"; if ($ws == "1") echo USERNAME; else echo USER; echo ":</label><input type=\"text\" name=\"username\" id=\"username\" size=\"30\" /></div>";
	} else {
		echo "
		  <tr>
		    <td align=\"right\"><span class=\"ashopcustomertext6\">"; if ($ws == "1") echo USERNAME; else echo USER; echo ":</span></td>
			<td align=\"left\" width=\"$tdwidth\" style=\"width: {$tdwidth}px;\">&nbsp;<input type=\"text\" name=\"username\" size=\"25\" style=\"width: {$fieldwidth}px;\" />
			<script language=\"JavaScript\" type=\"text/javascript\">document.getElementsByName('username')[0].focus();</script></td>
			<td>&nbsp;</td>
		  </tr>";
	}
	if ($device == "mobile") {
		echo "<div data-role=\"fieldcontain\"><label for=\"password\">".PASSWORD.":</label><input type=\"password\" name=\"password\" id=\"password\" size=\"30\" /></div>
		<input type=\"submit\" name=\"Submit\" data-role=\"button\" value=\"".LOGIN."\" />";
	} else {
		echo "
		  <tr>
		    <td align=\"right\"><span class=\"ashopcustomertext6\">".PASSWORD.":</span></td>
			<td align=\"left\" style=\"width: {$tdwidth}px;\">&nbsp;<input type=\"password\" name=\"password\" size=\"25\" style=\"width: {$fieldwidth}px;\" /></td>
			<td><input type=\"image\" src=\"{$buttonpath}images/login-$lang.png\" class=\"ashopbutton\" style=\"border: none;\" alt=\"".LOGIN."\" name=\"Submit\" /></td>
		  </tr>
		</table>";
	}

	if (!empty($shop) && $shop > 1) echo "<input type=\"hidden\" name=\"shop\" value=\"$shop\" />";
	if (!empty($redirect) && $redirect == "hostparty") echo "<input type=\"hidden\" name=\"redirect\" value=\"hostparty\" />";
	echo "
		</form>";
	if ($facebookactivated) {
		echo "<p><input type=\"image\"";
		if ($device == "mobile") echo " data-role=\"none\"";
		echo " src=\"http://developers.facebook.com/images/devsite/login-button.png\" onclick=\"fblogin();\" /></p>
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
		    <p><span class=\"ashopcustomertext2\"><a href=\"sendpass.php";
	if (!empty($shop) && $shop > 1) echo "?shop=$shop";
	echo "\"";
	if ($device == "mobile") echo " data-ajax=\"false\"";
	echo ">".FORGOTPASS."</a><br /><a href=\"";
	if ($ws == "1") echo "wholesale/";
	echo "signupform.php";
	if (!empty($shop) && $shop > 1) echo "?shop=$shop";
	echo "\"";
	if ($device == "mobile") echo " data-ajax=\"false\"";
	echo ">".NEWCUSTOMER."</a></span></p>";
	if ($device != "mobile") echo "
		</td></tr></table>";

	// Print footer using template...
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/customer-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/customer.html");
	exit;
}

$date = date("Y/m/d H:i:s");
$username=strtolower($username);

$sql = "SELECT customerid, password FROM customer WHERE username = '$username'";
$result = @mysqli_query($db,$sql);
if (!@mysqli_num_rows($result)) {
	@mysqli_close($db);
    header("Location: login.php?retrylogin");
	exit;
} else {
	$correctpassword = @mysqli_result($result, 0, "password");
	// Decrypt password if encryption key is available...
	if (!empty($customerencryptionkey) && !empty($correctpassword)) $correctpassword = ashop_decrypt($correctpassword, $customerencryptionkey);
	if ($password != $correctpassword) {
		@mysqli_close($db);
		header("Location: login.php?retrylogin");
		exit;
	}
	$hash = md5($date.$username.$password."ashopisgreat");
    $sql = "UPDATE customer SET sessionid='$hash', activity='$date', ip='{$_SERVER["REMOTE_ADDR"]}' WHERE username='$username'";
    @mysqli_query($db,$sql);
	$customerid = @mysqli_result($result, 0, "customerid");
	$sql = "SELECT shippingid FROM shipping WHERE customerid='$customerid'";
	$result = @mysqli_query($db,$sql);
	$shippingid = @mysqli_result($result, 0, "shippingid");
    @mysqli_close($db);
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
    SetCookie("customersessionid", $hash);
	SetCookie("shipid", $shippingid);
	SetCookie("bidderhash");
	if ($redirect == "hostparty") header("Location: hostparty.php");
	else {
		if (!empty($shop) && $shop > 1) header("Location: index.php?shop=$shop");
		else header("Location: index.php");
	}
}
?>