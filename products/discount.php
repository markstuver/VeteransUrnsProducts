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

// Initialize variables...
if (!isset($cat)) $cat = 0;
if (!isset($exp)) $exp = 0;
if (!isset($shop)) $shop = 0;
if (!isset($lang)) $lang = "";
if (!isset($usethemebuttons)) $usethemebuttons = "";
if (!isset($usethemetemplates)) $usethemetemplates = "";
if (!isset($themelanguages)) $themelanguages = "";
if (!isset($returnurl)) $returnurl = "";
if (!isset($error)) $error = 0;
if (!isset($id)) $id = 0;
if (!isset($discountcode)) $discountcode = "";
if (!isset($taxandshipping)) $taxandshipping = "";
if (!isset($payoption)) $payoption = 0;
if (!isset($shipid)) $shipid = 0;
if (!isset($taxandshippingcost)) $taxandshippingcost = "";
if (!isset($shippingfirstname)) $shippingfirstname = "";
if (!isset($shippinglastname)) $shippinglastname = "";
if (!isset($shippingaddress)) $shippingaddress = "";
if (!isset($shippingcity)) $shippingcity = "";
if (!isset($shippingzip)) $shippingzip = "";
if (!isset($shippingstate)) $shippingstate = "";
if (!isset($shippingcountry)) $shippingcountry = "";
if (!isset($shippingphone)) $shippingphone = "";
if (!isset($shippingemail)) $shippingemail = "";
if (!isset($affiliate)) $affiliate = 0;
if (empty($papurl) && !is_numeric($affiliate)) $affiliate = 0;
if (!isset($upsellitems)) $upsellitems = 0;
if (!isset($upsold)) $upsold = 0;
$tempcookie = array();
if (!empty($returnurl) && !ashop_is_url($returnurl)) $returnurl = "";

// Use relative paths and escape &-characters in returnurl...
$returnurl = str_replace("$ashopurl/","",$returnurl);
$returnurl = str_replace("$ashopsurl/","",$returnurl);
$returnurl = str_replace("&","|",$returnurl);

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/checkout.inc.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Get member template path if no theme is used...
if (!$shop) $shop = 1;
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

// Check for storewide discounts...
$result = @mysqli_query($db, "SELECT * FROM storediscounts LIMIT 1");
if (@mysqli_num_rows($result)) $storediscounts = TRUE;
else $storediscounts = FALSE;

// Check for per product discounts...
$result = @mysqli_query($db, "SELECT * FROM discount LIMIT 1");
if (@mysqli_num_rows($result)) $perproductdiscounts = TRUE;
else $perproductdiscounts = FALSE;

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/cart.html");
$catalogscript = "index.php";
?>
    <table class="ashopcheckoutframe">
      
  <tr align="center"> 
    <td><br>
	  <table border="0" cellspacing="0" cellpadding="2" align="center">
	  <tr><td align="right" valign="top">
            <a href="<?php if ($returnurl) echo "$returnurl"; else { echo $catalogscript; if ($cat) echo "?cat=$cat"; if ($shop && $shop != "1") echo "|shop=$shop"; } ?>"><img src="<?php echo $buttonpath."images/continue-$lang.png"; ?>" class="ashopbutton" border="0" alt="<?php echo CONTINUESHOPPING; ?>"></a>
			</td>
			
<?php
if ($shoppingcart) {
	echo "<td align=\"left\" valign=\"top\"><a href=\"basket.php";
	if (!$returnurl) $returnurl = $catalogscript;
	if ($returnurl) echo "?returnurl=$returnurl|sid=$sid";
	else echo "?cat=$cat|shop=$shop|sid=$sid";
	echo "\"><img src=\"{$buttonpath}images/viewcart-$lang.png\" class=\"ashopbutton\" alt=\"".VIEWCART."\" border=\"0\"></a></td>";
}
?>
<?php
	  if ($shipid && $taxandshipping) {
		  echo "<td align=\"left\" valign=\"top\"><a href=\"";
		  if ($returnurl) echo "shipping.php?changeshipping=true&action=checkout&returnurl=$returnurl";
		  else echo "shipping.php?changeshipping=true&action=checkout&cat=$cat&shop=$shop";
		  echo "\"><img src=\"{$buttonpath}images/shipping-$lang.png\" class=\"ashopbutton\" border=\"0\"></a></td>";
	  }
?>
</tr></table><br><br>
	  
<?php
if ($storediscounts || $perproductdiscounts) echo "\n\n<form name=\"discountform\" method=\"post\" action=\"checkout.php\">\n<table class=\"ashopdiscounttable\"><tr><td align=\"center\"><span class=\"ashopdiscounttext\">$discountmessage</span><br><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">$welcomemessage<tr><td width=\"30\">&nbsp;</td><td align=\"left\" valign=\"top\"><span class=\"ashopdiscounttext\">".ENTERCODE." <input type=\"text\" name=\"discountcode\" size=\"28\"></span></td><td width=\"80\" align=\"right\" valign=\"bottom\"><input type=\"image\" border=\"0\" src=\"{$buttonpath}images/apply-$lang.png\" class=\"ashopbutton\" alt=\"Apply\"></td></tr></table></td></tr></table></form></p>
</td></tr></table>";

// Close database...

@mysqli_close($db);

// Print footer using template...

if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/cart-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/cart-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/cart.html");
?>