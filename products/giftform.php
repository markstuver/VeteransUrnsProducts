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
include "admin/ashopconstants.inc.php";

// Validate variables...
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";
if (!empty($invoice) && !is_numeric($invoice)) $invoice = "";

if (!$invoice) header("Location: $ashopurl");
$payoption = "0";
// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/orderform.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/cart.html")) $templatepath = "/members/files/$ashopuser";

// Set the right redirect URL for the Continue Shopping button...
if ($returnurl) $redirecturl = $returnurl;
else $redirecturl = "index.php";

// Convert multiple origin countries to an array...
$shipfromcountries = explode("-", $shipfromcountry);

// Combine address fields...
if ($address && $address2) $address .= ", $address2";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$gateway = "manual";

$result = @mysqli_query($db, "SELECT * FROM orders WHERE orderid='$invoice'");
$row = @mysqli_fetch_array($result);
$shop = $row["userid"];
if (substr($shop,0,1) == "|") $shop = substr($shop,1);
if (substr($shop,-1) == "|") $shop = substr($shop,0,-1);
if ($shop && !strstr("|",$shop) && $shop != "1") {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$shop'");
	$shopname = stripslashes(@mysqli_result($result,0,"shopname"));
}
$amount = "0.00";
$products = $payoption."ashoporderstring".$row["products"];

include "admin/gateways/$gateway.gw";

// Show header using template catalogue.html...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
echo "
<script language=\"JavaScript\" src=\"includes/switchstates.js.php\" type=\"text/javascript\"></script>
<script language=\"JavaScript\">
	<!--
	function verifyform(orderform) {
		var allformfieldsfilled = 1;
		var emailmatch = 1;
		var emailvalid = 1;
		if (orderform.phone.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(PHONE)."'; }
		if (orderform.country.value == 'none') { allformfieldsfilled = 0; missedfield = '".strtolower(COUNTRY)."'; }
		if (orderform.zip.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ZIP)."'; }
		if (orderform.country.value == 'United States' && orderform.state.value == 'none') { allformfieldsfilled = 0; missedfield = '".strtolower(JUSTSTATE)."'; }
		if (orderform.country.value == 'United States' && orderform.state.value == 'other') { allformfieldsfilled = 0; missedfield = '".strtolower(JUSTSTATE)."'; }
		if (orderform.city.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(CITY)."'; }
		if (orderform.address.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(ADDRESS)."'; }
		if (orderform.email.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(EMAIL)."'; }
		if (orderform.confirmemail.value != orderform.email.value) emailmatch = 0; 
		if (orderform.lastname.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(LASTNAME)."'; }
        if (orderform.firstname.value == '') { allformfieldsfilled = 0; missedfield = '".strtolower(FIRSTNAME)."'; }
		if (orderform.email.value.indexOf('@') == -1 || orderform.email.value.indexOf('.') == -1) emailvalid = 0;
		if (allformfieldsfilled == 0) {
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".YOUFORGOT."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".FILLINALL." '+missedfield+'</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
		} else if (emailvalid == 0) {
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".EMAILINVALID."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".EMAILADDRESSINVALID."</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
	    } else if (emailmatch == 0) {
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=300, height=150\");
			w.document.write('<html><head><title>".EMAILDOESNOTMATCH."</title>".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px} .fontsize2 { font-size: {$fontsize2}px} .fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><center><font face=\"$font\" size=\"3\"><span class=\"fontsize3\">".EMAILADDRESSDOESNOTMATCH."</span><br><br><font size=\"2\"><span class=\"fontsize2\"><a href=\"javascript:this.close()\">".CLOSE."</a></span></font></font><br></center></body></html>');
			return false;
	    } else {
			document.getElementById('wait').innerHTML = '".PLEASEWAIT."'
			return true;
		}
    }
-->
</script>
<br><table class=\"ashoporderformframe\">
  <tr><td align=\"center\">
      <p><span class=\"ashoporderformheader\">".BILL."</span></p><p><a href=\"$ashopurl/checkout.php?id=$invoice&redirect=$redirecturl\"><img src=\"{$buttonpath}images/continue-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"Continue shopping!\"></a></p>
	  <p><span class=\"ashoporderformtext1\">".PRODUCTS."</span><br><span class=\"ashoporderformtext2\">".stripslashes($description)."</span><br></p>
      <p><table><tr><td align=\"left\"><span class=\"ashoporderformtext2\">$orderpagetext</span></td></tr></table></p>
      </td>
  </tr>
  <tr align=\"center\"> 
    <td> 
      <table class=\"ashoporderformbox\">
        <tr align=\"center\"> 
          <td> 
            <form action=\"$paymenturl2\" method=post name=\"orderform\" onSubmit=\"return verifyform(this)\">
              <table border=0 cellspacing=0 cellpadding=3 width=\"440\">
			  <tr>
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".FIRSTNAME.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"firstname\" value=\"$firstname\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".LASTNAME.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"lastname\" value=\"$lastname\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".EMAIL.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"email\" value=\"$email\" size=30>
                  </td>
                </tr>";
if (empty($email)) echo "
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".CONFIRMEMAIL.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"confirmemail\" value=\"$confirmemail\" size=30>
                  </td>
                </tr>";
if ($collectcustomerinfo) {
	echo "<tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".ADD1.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"address\" value=\"$address\" size=30>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".ADD2.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"address2\" value=\"$address2\" size=30>
                  </td>
                </tr>
                <tr>
                  <td align=\"right\" height=\"25\" width=\"159\"><span class=\"ashoporderformlabel\">".CITY.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"city\" value=\"$city\" size=20>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".ZIP.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <input type=text name=\"zip\" value=\"$zip\" size=10>
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".COUNTRY.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\"> 
                    <select name=\"country\" onChange=\"switchStates(document.getElementById('state'),document.orderform.province,document.orderform.country.value);\"><option  value=none>choose country";
					if (strlen($country) == 2) foreach ($countries as $shortcountry => $longcountry) if ($country == $shortcountry) $country = $longcountry;
					if ($shipfromcountries) foreach ($shipfromcountries as $thiscountry) {
						echo "<option value=\"$countries[$thiscountry]\"";
						if ($country == $countries[$thiscountry]) echo " selected";
						echo ">$countries[$thiscountry]";
					}
					foreach ($countries as $shortcountry => $longcountry) if (!in_array($shortcountry, $shipfromcountries)) {
						if (strlen($longcountry) > 30) $slongcountry = substr($longcountry,0,27)."...";
						else $slongcountry = $longcountry;
						echo "<option value=\"$longcountry\"";
						if ($country == $longcountry || $country == $shortcountry) echo " selected";
						echo ">$slongcountry\n";
					}
					echo "</select>
                  </td>
                </tr>";
				if (empty($state) || !in_array($country, $longcountrieswithstates)) echo "<tr id=\"stateselector\" style=\"display:none\">";
				else echo "<tr id=\"stateselector\">";
				echo "
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".STATE.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\">
				    <select name=\"state\" id=\"state\"><option value=none>".CHOOSESTATE."<option value=\"other\"";
					if ($address && !$state) echo " selected";
					else if (!in_array($state, $uscanstates)) echo " selected";
					echo ">".NOTUSACAN;
					foreach ($uscanstates as $longstate => $shortstate) {
						echo "<option  value=\"$shortstate\"";
						if ($shortstate == $state || $longstate == $state) {
							if ($shortstate == "WA" || $shortstate == "NT") {
								if ($country == "US" || $country == "United States") {
									if ($state == "WA" && $longstate == "Washington") echo " selected";
								} else if ($country == "AU" || $country == "Australia") {
									if ($state == "WA" && $longstate == "Western Australia") echo " selected";
									else if ($state == "NT" && $longstate == "Northern Territory") echo " selected";
								} else if ($country == "CA" || $country == "Canada") {
									if ($state == "NT" && $longstate == "Northwest Territories") echo " selected";
								}
							} else echo " selected";
						}
						echo ">$longstate\n";
					}
					echo "</select>
                  </td>
                </tr>";
				if (empty($state) || in_array($country, $longcountrieswithstates)) echo "<tr id=\"regionrow\" style=\"display:none\">";
				else echo "<tr id=\"regionrow\">";
				echo "
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".PROVINCE.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\">
				    <input type=text name=\"province\" size=20 value=\"";
					if (!in_array($country, $longcountrieswithstates)) echo $state;
					echo "\">
                  </td>
                </tr>
                <tr> 
                  <td align=\"right\" width=\"159\"><span class=\"ashoporderformlabel\">".PHONE.":</span></td>
                  <td width=\"269\" class=\"ashoporderformfield\">
                    <input type=text name=\"phone\" value=\"$phone\" size=20>
                  </td>
                </tr>";
} else echo "<input type=\"hidden\" name=\"address\" value=\"Unknown\"><input type=\"hidden\" name=\"address2\" value=\"Unknown\"><input type=\"hidden\" name=\"city\" value=\"Unknown\"><input type=\"hidden\" name=\"state\" value=\"Unknown\"><input type=\"hidden\" name=\"zip\" value=\"Unknown\"><input type=\"hidden\" name=\"country\" value=\"Unknown\"><input type=\"hidden\" name=\"phone\" value=\"Unknown\">";
$authkey = md5($ashoppath.$products."ashopkey0.00");
				echo "
                <tr> 
                  <td colspan=4 align=center> 
					<span class=\"ashopalert\"><div ID=\"wait\">&nbsp;</div></span>
                    <p>
					  <input type=\"hidden\" name=\"lang\" value=\"$lang\">
					  <input type=\"hidden\" name=\"invoice\" value=\"$invoice\">
					  <input type=\"hidden\" name=\"returnurl\" value=\"$returnurl\">
					  <input type=\"hidden\" name=\"products\" value=\"$products\">
				      <input type=\"hidden\" name=\"description\" value=\"$description\">
				      <input type=\"hidden\" name=\"payoption\" value=\"0\">
					  <input type=\"hidden\" name=\"amount\" value=\"0.00\">
					  <input type=\"hidden\" name=\"authkey\" value=\"$authkey\">";
					  if ($affiliate) echo "<input type=\"hidden\" name=\"affiliate\" value=\"$affiliate\">"; echo "<input type=\"image\" src=\"{$buttonpath}images/submit-$lang.png\" class=\"ashopbutton\" border=\"0\" alt=\"".SUBMIT."\" name=\"Submit\"></p>
                  </td>
                </tr>
              </table>
            </form>
      </table>
    </td>
  </tr>
</table>";
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");

// Close database...
@mysqli_close($db);
?>
