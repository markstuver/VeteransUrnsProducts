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

include "../admin/config.inc.php";
include "../admin/ashopfunc.inc.php";
include "../admin/ashopconstants.inc.php";

if ($wssessionid) {
	header("Location: ../index.php");
	exit;
}

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
include "../language/$lang/ws_signupform.inc.php";

// Show header using template affiliate.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/wssignup-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/wssignup.html");

echo "
<script language=\"JavaScript\" src=\"../includes/switchstates.js.php\" type=\"text/javascript\"></script>
<br /><table class=\"ashopwholesalesignupframe\">
  <tr><td align=\"center\"> 

      <p><span class=\"ashopwholesalesignupheader\">".SIGNUP." $ashopname</span></p>
	  <p><span class=\"ashopwholesalesignuptext2\">".ALREADYSIGNEDUP." <a href=\"login.php\">".LOGINHERE."</a></span></p>
      <p align=\"left\"><span class=\"ashopwholesalesignuptext2\">".WHOLESALEMESSAGE."</span></p>
      </td>
  </tr>
  <tr align=\"center\"> 
    <td> 
      <table class=\"ashopwholesalesignupbox\">
        <tr align=\"center\"> 
          <td> 
            <form action=\"signup.php\" method=\"post\" name=\"signupform\">
              <table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"440\">
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".USERNAME.":</span></td>
                  <td class=\"ashopwholesalesignupfield\"> 
                    <input type=\"text\" name=\"wsuser\" size=\"15\" />
                    <span class=\"ashopwholesalesignupnotice\"> ".MAXCHARS."</span> </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".BUSINESS.":</span></td>
                  <td class=\"ashopwholesalesignupfield\"> 
                    <input type=\"text\" name=\"businessname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".BUSINESSTYPE.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <select name=\"businesstype\">
					<option value=\"none\">".SELECTONE."</option>
					<option value=\"soleproprietor\">".SOLEPROPRIETOR."</option>
					<option value=\"corporation\">".CORPORATION."</option>
					<option value=\"nonprofit\">".NONPROFIT."</option>
					</select>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".URL.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"url\" size=\"30\" />
					<span class=\"ashopwholesalesignupnotice\"> ".OPTIONAL."</span>
                  </td>
                </tr>";
				/* echo "
                <tr> 
                  <td align=\"right\" width=\"180\"><font size=\"2\"
			  face=\"$font\" color=\"$formstextcolor\">Reseller ID:</font></td>
                  <td width=\"360\" nowrap align=\"left\"> 
                    <input type=\"text\" name=\"resellerid\" size=\"30\">
					<font size=\"1\"
			  face=\"$font\" color=\"$formstextcolor\"> [optional]</font>
                  </td>
                </tr>"; */
			echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".FIRSTNAME.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"firstname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".LASTNAME.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"lastname\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".EMAIL.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"email\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".ADDRESS.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"address\" size=\"30\" />
                  </td>
                </tr>
                <tr>
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".CITY.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"city\" size=\"30\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".ZIP.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"zip\" size=\"10\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".COUNTRY.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <select name=\"country\" onchange=\"switchStates(document.getElementById('state'),document.signupform.province,document.signupform.country.value);\" onClick=\"if (typeof(countryinterval) != 'undefined') window.clearInterval(countryinterval);\"><option  value=\"none\">".CHOOSECOUNTRY."</option>";
					foreach ($countries as $shortcountry => $longcountry) {
						if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
						echo "<option value=\"$shortcountry\">$longcountry</option>\n";
					}
					echo "</select>
                  </td>
                </tr>
                <tr id=\"stateselector\" style=\"display:none\"> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".STATE.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <select name=\"state\" id=\"state\"><option value=\"none\">choose...</option></select>
                  </td>
                </tr>
                <tr id=\"regionrow\" style=\"display:none\"> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".PROVINCE.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"province\" size=\"20\" />
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".PHONE.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"phone\" size=\"20\" />
                  </td>
                </tr>";
				if ($requestvat) echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".VATNUMBER.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"vat\" size=\"20\" />
                  </td>
                </tr>";
				else if ($requestabn) echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".ABNNUMBER.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <input type=\"text\" name=\"vat\" size=\"20\" />
                  </td>
                </tr>";
				/* echo "<tr> 
                  <td align=\"left\" colspan=\"2\"><font face=\"$font\" size=\"2\" color=\"$formstextcolor\">Please provide a brief description of your business. All information submitted in this form is held strictly confidential.</font></td>
                </tr>" */
				echo "
                <tr> 
                  <td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".DESCRIPTION.":</span></td>
                  <td class=\"ashopwholesalesignupfield\">
                    <textarea name=\"extrainfo\" cols=\"30\" rows=\"5\"></textarea>
                  </td>
                </tr>";
				if ($activatesecuritycheck) {
					echo "<tr><td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".SECURITYCODE.":</span></td><td valign=\"top\" class=\"ashopwholesalesignupfield\"><img src=\"signup.php?action=generatecode&amp;random=$random\" border=\"1\" alt=\"Security Code\" title=\"Security Code\" /></td></tr><tr><td align=\"right\"><span class=\"ashopwholesalesignuptext3\">".TYPESECURITYCODE.":</span></td><td valign=\"top\" class=\"ashopmallsignupfield\"><input type=\"text\" name=\"securitycheck\" size=\"10\" /><input type=\"hidden\" name=\"random\" value=\"$random\" /></td></tr>";
			    }
			    echo "
                <tr>
                  <td colspan=\"2\" align=\"center\"><p><input type=\"image\" src=\"../{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" style=\"border: none;\" name=\"".SUBMIT."\" /></p></td>
                </tr>
              </table>
            </form>
			</td>
			</tr>
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

// Show footer using template signup.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/wssignup-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/wssignup-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/wssignup.html");
?>