<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

// Check for GD...
ob_start(); 
phpinfo(8); 
$phpinfo=ob_get_contents(); 
ob_end_clean();
$phpinfo=strip_tags($phpinfo); 
$phpinfo=stristr($phpinfo,"gd version");
$phpinfo=stristr($phpinfo,"version"); 
$end=strpos($phpinfo,"\n"); 
$phpinfo=substr($phpinfo,0,$end);
preg_match ("/[0-9]/", $phpinfo, $version);
if(isset($version[0]) && $version[0]>1) $gdversion = 2;
else $gdversion = 0;

unset($shop);
include "../admin/checklicense.inc.php";

// Validate input variables...
if (!empty($email) && !ashop_is_email(strtolower($email))) $email = "";
if (!empty($url) && substr($url,0,7) != "http://" && substr($url,0,8) != "https://") $url = "http://".$url;
if (!empty($url) && !ashop_is_url($url)) $url = "";
$shopuser = ashop_mailsafe($shopuser);
$shopuser = ashop_cleanfield($shopuser);
if (strlen($shopuser) < 2) $shopuser = "";
$shopname = ashop_mailsafe($shopname);
$shopname = ashop_cleanfield($shopname);
if (strlen($shopname) < 2) $shopname = "";
$businesstype = ashop_mailsafe($businesstype);
$businesstype = ashop_cleanfield($businesstype);
if (strlen($businesstype) < 2) $businesstype = "";
$firstname = ashop_mailsafe($firstname);
$firstname = ashop_cleanfield($firstname);
if (strlen($firstname) < 2) $firstname = "";
$lastname = ashop_mailsafe($lastname);
$lastname = ashop_cleanfield($lastname);
if (strlen($lastname) < 2) $lastname = "";
$email = ashop_mailsafe($email);
$email = ashop_cleanfield($email);
if (strlen($email) < 2) $email = "";
$address = ashop_mailsafe($address);
$address = ashop_cleanfield($address);
if (strlen($address) < 2) $address = "";
$state = ashop_mailsafe($state);
$state = ashop_cleanfield($state);
if (strlen($state) < 2) $state = "";
$province = ashop_mailsafe($province);
$province = ashop_cleanfield($province);
if (strlen($province) < 2) $province = "";
if (empty($state) && !empty($province)) $state = $province;
$zip = ashop_mailsafe($zip);
$zip = ashop_cleanfield($zip);
if (strlen($zip) < 2) $zip = "";
$city = ashop_mailsafe($city);
$city = ashop_cleanfield($city);
if (strlen($city) < 2) $city = "";
$country = ashop_mailsafe($country);
$country = ashop_cleanfield($country);
if (strlen($country) < 2) $country = "";
$phone = ashop_mailsafe($phone);
$phone = ashop_cleanfield($phone);
if (strlen($phone) < 2) $phone = "";

// If GD is available generate random code for security check...
if (function_exists('imagecreatefromjpeg') && function_exists('imagecreatefromgif') && function_exists('imagecreatetruecolor') && $gdversion == 2) {
	$activatesecuritycheck = TRUE;
	// Generate new random code...
	mt_srand ((double)microtime()*1000000);
	$maxrandom = 1000000;
	$random = mt_rand(0, $maxrandom);
} else $activatesecuritycheck = FALSE;

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "../themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "../language/$lang/dm_signupform.inc.php";

include "../admin/ashopconstants.inc.php";

// Show header using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/membersignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/membersignup-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/membersignup.html");

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (is_array($memberpayoutoptions)) {
	echo "
<script language=\"JavaScript\">
<!--
	function paymentTemplate(signupform) {
		if (signupform.paymentmethod.value=='0') signupform.paymentdetails.value='';";
foreach($memberpayoutoptions as $payoutnumber=>$payouttext) echo "
		else if (signupform.paymentmethod.value=='$payoutnumber') signupform.paymentdetails.value='$payouttext'";

echo "	}
-->
</script>";
}
echo "
<script language=\"JavaScript\" src=\"../includes/switchstates.js.php\" type=\"text/javascript\"></script>
  <br><table class=\"ashopmallsignupframe\">
  <tr><td align=\"center\"> 

      <p><span class=\"ashopmallsignupheader\">".APPLY." $ashopname</span></p>";
	  if (empty($cpanelapiuser) || empty($cpanelapipass)) echo "<p><span class=\"ashopmallsignuptext2\">".ALREADYSIGNEDUP." <a href=\"../admin/login.php\">".LOGINHERE."</a></span></p>";
if (!empty($shoppingmallinfo)) echo "
      <p><table><tr><td align=\"left\"><span class=\"ashopmallsignuptext2\">$shoppingmallinfo</span></td></tr></table></p>";
echo "
      </td>
  </tr>
  <tr align=\"center\"> 
    <td> 
      <table class=\"ashopmallsignupbox\">
        <tr align=\"center\"> 
          <td> 
            <form action=\"signup.php\" method=post name=\"signupform\">
              <table border=0 cellspacing=0 cellpadding=3 width=\"440\">
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".USERNAME.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"shopuser\" value=\"$shopuser\" size=20>
                    <span class=\"ashopmallsignupnotice\"> ".MAXCHARS."</span> </td>
                </tr>";
if ($membershops) {
	echo "<tr> 
                  <td align=\"right\" nowrap><span class=\"ashopmallsignuptext3\">".NAMEOFSHOP.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"shopname\" value=\"$shopname\" size=30>"; if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpaneldomain)) echo " <span class=\"ashopmallsignuptext3\">.$cpaneldomain</span>"; echo "
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" valign=\"top\"><span class=\"ashopmallsignuptext3\">".CATEGORIES.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <select name=\"shopcategories[]\" size=\"5\" multiple>";

   $categories = @mysqli_query($db, "SELECT * FROM shopcategory ORDER BY name");
   while ($row = @mysqli_fetch_array($categories)) {
	   echo "<option value=\"{$row["categoryid"]}\"";
	   if (!empty($shopcategories) && in_array($row["categoryid"],$shopcategories)) echo " selected";
	   echo ">{$row["name"]}";
   }

   echo "</select><br><span class=\"ashopmallsignupnotice\"> ".CTRLCLICK."</span>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" valign=\"top\" nowrap><span class=\"ashopmallsignuptext3\">".SHOPDESCRIPTION.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <textarea name=\"description\" cols=\"30\" rows=\"4\">$description</textarea>
                  </td>
                </tr>";
} else echo "<input type=\"hidden\" name=\"shopname\" value=\"$ashopname\">";
echo "<tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".BUSINESSTYPE.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <select name=\"businesstype\">
					<option value=\"none\""; if ($businesstype == "none") echo " selected"; echo ">".SELECTONE."</option>
					<option value=\"soleproprietor\""; if ($businesstype == "soleproprietor") echo " selected"; echo ">".SOLEPROPRIETOR."</option>
					<option value=\"corporation\""; if ($businesstype == "corporation") echo " selected"; echo ">".CORPORATION."</option>
					<option value=\"nonprofit\""; if ($businesstype == "nonprofit") echo " selected"; echo ">".NONPROFIT."</option>
					</select>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".FIRSTNAME.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"firstname\" value=\"$firstname\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".LASTNAME.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"lastname\" value=\"$lastname\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".EMAIL.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"email\" value=\"$email\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".ADDRESS.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"address\" value=\"$address\" size=30>
                  </td>
                </tr>
                <tr>
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".CITY.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"city\" value=\"$city\" size=20>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".ZIP.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"zip\" value=\"$zip\" size=10>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".COUNTRY.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <select name=\"country\" onChange=\"switchStates(document.getElementById('state'),document.signupform.province,document.signupform.country.value);\" onClick=\"if (typeof(countryinterval) != 'undefined') window.clearInterval(countryinterval);\"><option  value=none>".CHOOSECOUNTRY;
foreach ($countries as $shortcountry => $longcountry) {
	if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
	echo "<option value=\"$shortcountry\">$longcountry\n";
}
echo "</select>
                  </td>
                </tr>
                <tr id=\"stateselector\" style=\"display:none\"> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".STATE.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <select name=\"state\" id=\"state\"><option value=none>".CHOOSESTATE."</select>
                  </td>
                </tr>
                <tr id=\"regionrow\" style=\"display:none\"> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".PROVINCE.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"province\" value=\"$province\" size=20>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".PHONE.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"phone\" value=\"$phone\" size=20>
                  </td>
                </tr>";
				if (empty($cpanelapiuser) || empty($cpanelapipass)) echo "
				<tr> 
                  <td align=\"right\" nowrap><span class=\"ashopmallsignuptext3\">".URL.":</span></td>
                  <td class=\"ashopmallsignupfield\"> 
                    <input type=text name=\"url\" value=\"$url\" size=30>
                  </td>
                </tr>";
                if (is_array($memberpayoutnames) && is_array($memberpayoutoptions) && !$memberpayoptions && (!$cpanelapiuser || !$cpanelapipass || !$cpanelapiurl)) {
					echo "<tr> 
                  <td align=\"right\"><span class=\"ashopmallsignuptext3\">".PAYMENTDETAILS.":</span></td>
                  <td valign=\"top\" class=\"ashopmallsignupfield\">
                    <select name=\"paymentmethod\" onChange=\"paymentTemplate(signupform)\"><option value=\"0\">".CHOOSEMETHOD;
					foreach ($memberpayoutnames as $payoutnumber=>$payoutname) echo "<option value=\"$payoutnumber\">$payoutname";
					echo "</select>
                  </td>
                </tr>
				<tr>
				  <td></td><td class=\"ashopmallsignupfield\"><textarea name=\"paymentdetails\" cols=\"30\" rows=\"5\">$paymentdetails</textarea></td>
				</tr>";
				}
				if ($activatesecuritycheck) {
					echo "<tr><td align=\"right\"><span class=\"ashopmallsignuptext3\">".SECURITYCODE.":</span></td><td valign=\"top\" class=\"ashopmallsignupfield\"><img src='signup.php?action=generatecode&random=$random' border='1' alt='Security Code' title='Security Code'></td></tr><tr><td align=\"right\"><span class=\"ashopmallsignuptext3\">".TYPESECURITYCODE.":</span></td><td valign=\"top\" class=\"ashopmallsignupfield\"><input type=\"text\" name=\"securitycheck\" size=\"10\"><input type=\"hidden\" name=\"random\" value=\"$random\"></td></tr>";
			    }
			  echo "
                <tr> 
                  <td colspan=2 align=center><p><input type=\"image\" src=\"../{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".SUBMIT."\" name=\"Submit\"></p></td>
                </tr>
              </table>
            </form>
      </table>
    </td>
  </tr>
</table>";

echo "<script language=\"JavaScript\" type=\"text/javascript\">
/* <![CDATA[ */
	var currentcntry = document.signupform.country.value;
	function makechange() {
		if (document.signupform.country.value != window.currentcntry) {
			switchStates(document.getElementById('state'),document.signupform.province,document.signupform.country.value);
			window.currentcntry = document.signupform.country.value;
		}
	}
	var countryinterval = window.setInterval(\"makechange()\",1000);
/* ]]> */
</script>";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/membersignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/membersignup-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/membersignup.html");
?>