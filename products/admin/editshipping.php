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

include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";
include "ashopconstants.inc.php";
// Get context help for this page...
$contexthelppage = "editshipping";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store edited data...
if ($shipoption) {
   if (!$shipping || $shipping == "0" || $shipping == "0.00" || $shipping == "0.0") $shipping = "0.00";
   if ($shipoption == "flatrate") {
	   $dbcountryshipping = "";
	   if (empty($intshipping)) $intshipping = $shipping;
	   if (!empty($ratetocountry) && is_array($ratetocountry)) {
		   foreach ($ratetocountry as $thisratetocountrynumber=>$thisratetocountry) {
			   $thiscountryrate = floatval($countryshipping[$thisratetocountrynumber]);
			   if ($thisratetocountry != "none") $dbcountryshipping .= $thisratetocountry.":".number_format($thiscountryrate,2,'.','')."|";
		   }
	   }
	   $dbcountryshipping = substr($dbcountryshipping,0,-1);
	   $sql = "UPDATE product SET shipping='".number_format($shipping,2,'.','')."', intshipping='".number_format($intshipping,2,'.','')."', countryshipping='$dbcountryshipping' WHERE productid='$productid'";
   }
   else if ($shipoption == "none") $sql = "UPDATE product SET shipping=NULL WHERE productid='$productid'";
   else if ($shipoption == "usps") $sql = "UPDATE product SET shipping='usps' WHERE productid='$productid'";
   else if ($shipoption == "ups") $sql = "UPDATE product SET shipping='ups' WHERE productid='$productid'";
   else if ($shipoption == "fedex") $sql = "UPDATE product SET shipping='fedex' WHERE productid='$productid'";
   else if ($shipoption == "wml") $sql = "UPDATE product SET shipping='wml' WHERE productid='$productid'";
   else if ($shipoption == "zone") $sql = "UPDATE product SET shipping='zone$zipzonename' WHERE productid='$productid'";
   else if ($shipoption == "quantity") $sql = "UPDATE product SET shipping='quantity' WHERE productid='$productid'";
   else if ($shipoption == "storewide") $sql = "UPDATE product SET shipping='storewide' WHERE productid='$productid'";
   $result = @mysqli_query($db, $sql);
   if ($shipoption == "storewide" && (isset($nproductweightpounds) || isset($nproductweightounces))) {
	   if ($nproductweightounces == 0 && $nproductweightpounds == 0) $nproductweight = 0;
	   else {
		   $totalounces = ($nproductweightpounds*16)+$nproductweightounces;
		   $nproductweight = $totalounces/16;
	   }
	   @mysqli_query($db, "UPDATE product SET weight='".number_format($nproductweight,2,'.','')."' WHERE productid='$productid'");
   }
   if ($shipoption == "zone") {
	   for ($i = 2; $i <= 8; $i++) {
		   $zonerate = 0;
		   eval(" if (\$edzone$i) \$zonerate = \$edzone$i; else \$zonerate = 0;");
           $sql = "SELECT count(productid) as productcount FROM zonerates WHERE productid=$productid AND zone=$i";
           $result = @mysqli_query($db, $sql);
           $productcount = @mysqli_result($result, 0, "productcount");
           if (!$productcount > 0) $sql = "INSERT INTO zonerates(productid, zone, rate) VALUES($productid, $i,$zonerate)";
           else $sql = "UPDATE zonerates SET rate=$zonerate WHERE productid=$productid AND zone = $i";
           $result = @mysqli_query($db, $sql);
	   }
   } else {
       $sql="DELETE FROM zonerates WHERE productid=$productid";     
       $result = @mysqli_query($db, $sql);
   }

   $sql="DELETE FROM packages WHERE productid=$productid";
   $result = @mysqli_query($db, $sql);
   if ($numberofpackages) {
	   for ($i = 1; $i <= $numberofpackages; $i++) {
		   $origzip = 0;
		   $weight = 0;
		   eval ("if (\$origzip$i) \$origzip = \"\$origzip$i\";");
		   eval ("if (\$weight$i) \$weight = \$weight$i;");
		   eval ("if (\$class$i) \$class = \$class$i;");
		   eval ("if (\$origcountry$i) \$origcountry = \"\$origcountry$i\";");
		   eval ("if (\$origstate$i) \$origstate = \"\$origstate$i\";");
		   if ($origzip && $weight) {
			   $sql="INSERT INTO packages (productid,originzip,origincountry,originstate,weight,freightclass) VALUES ($productid,'$origzip','$origcountry','$origstate','$weight','$class')";
			   $result = mysqli_query($db, $sql);
		   }
	   }
   }
}

// Get product information...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productshipping = @mysqli_result($result, 0, "shipping");
$productintshipping = @mysqli_result($result, 0, "intshipping");
$productcountryshipping = @mysqli_result($result, 0, "countryshipping");
$productweight = @mysqli_result($result, 0, "weight");
$totalounces = $productweight*16;
$productweightpounds = floor($productweight);
$productweightounces = ($totalounces-($productweightpounds*16));
$productweightounces = number_format($productweightounces,1,'.','');
if (!$productshipping && !$shipoption) {
	$productshipping = "0.00";
	$shipoption = "none";
} else if ($productshipping == "usps" && !$shipoption) $shipoption = "usps";
else if ($productshipping == "ups" && !$shipoption) $shipoption = "ups";
else if ($productshipping == "fedex" && !$shipoption) $shipoption = "fedex";
else if ($productshipping == "wml" && !$shipoption) $shipoption = "wml";
else if ($productshipping == "quantity" && !$shipoption) $shipoption = "quantity";
else if (strstr($productshipping,"zone") && !$shipoption) { 
	$zipzonename = $productshipping;
	$zipzonename = str_replace("zone", "", $zipzonename);
	$shipoption = "zone"; 
} else if ($productshipping == "storewide" && !$shipoption) $shipoption = "storewide";
else if ($productshipping && !$shipoption) $shipoption = "flatrate";

// Load zipzone amounts
if ($shipoption == "zone") {
	$sql="SELECT DISTINCT zonename FROM zipzones";
	$result = @mysqli_query($db, $sql);
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) $zipzonenames[$i] = @mysqli_result($result, $i, "zonename");
	$sql="SELECT * FROM zonerates WHERE productid = $productid ORDER BY zone";
	$result = @mysqli_query($db, $sql);
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$zipzone = @mysqli_result($result, $i, "zone");
		$zoneamounts[$zipzone] = @mysqli_result($result, $i, "rate");
	}
}

// Handle ups packages...
$sql="SELECT * FROM packages WHERE productid = $productid ORDER BY packageid";
$result = @mysqli_query($db, $sql);
$storedpackages = @mysqli_num_rows($result);
$packages = $numberofpackages;
if (!$numberofpackages) $packages = 1;
if ($storedpackages) {
	$storedpackagesstring = "";
 	$totalweight = 0;
	for ($i = 0; $i < $storedpackages; $i++) {
		$storigincountry = @mysqli_result($result, $i, "origincountry");
		$storiginstate = @mysqli_result($result, $i, "originstate");
		$storiginzip = @mysqli_result($result, $i, "originzip");
		$stweight = @mysqli_result($result, $i, "weight");
		$stclass = @mysqli_result($result, $i, "freightclass");
		$totalweight += $stweight;
		$packagenumber = $i + 1;
		$storedpackagesstring .= "\n<br><table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" bgcolor=\"#D0D0D0\"><tr><td valign=\"top\" class=\"formtitle\">".PACKAGE." $packagenumber:</td><td width=\"75%\" valign=\"top\">"; /* I'll leave this for now.. uncomment to activate multiple origin countries... <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"left\" width=\"120\" class=\"formlabel\">Origin country:</td><td><SELECT NAME=\"origcountry$packagenumber\"><option  value=none>choose country";

		foreach ($countries as $shortcountry => $longcountry) {
			$storedpackagesstring .= "<option  value=$shortcountry";
			if ($shortcountry == $storigincountry) $storedpackagesstring .= " selected";
			$storedpackagesstring .= ">$longcountry\n";
		}
		
		$storedpackagesstring .= "</SELECT></td></tr></table></td></tr><tr><td></td><td>"; */
		$storedpackagesstring .= "</td></tr><tr><td></td><td><input type=\"hidden\" name=\"origcountry$packagenumber\" value=\"US\">";

		
		if ($storigincountry == "US" && $shipoption != "wml") {
			$storedpackagesstring .= "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" width=\"120\" class=\"formlabel\">".ORIGINSTATE.":</td><td align=\"left\"><SELECT NAME=\"origstate$packagenumber\"><option value=\"none\">".CHOOSESTATE;

			foreach ($uscanstates as $longstate => $shortstate) {
				$storedpackagesstring .= "<option value=\"$shortstate\"";
				if ($shortstate == $storiginstate) $storedpackagesstring .= " selected";
				$storedpackagesstring .= ">$longstate\n";
			}

			$storedpackagesstring .= "</SELECT></td></tr></table>";
		}
		$storedpackagesstring .= "</td></tr><tr><td></td><td><table width=\"100%\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" width=\"120\" class=\"formlabel\">".ORIGINZIP.":</td><td><input type=\"text\" name=\"origzip$packagenumber\" size=\"10\" value=\"$storiginzip\"></td>";
		if ($shipoption == "wml") $storedpackagesstring .= "<td align=\"right\" class=\"formlabel\">".SHIPPINGCLASS.": </td><td align=\"left\"><input type=\"text\" name=\"class$packagenumber\" size=\"5\" value=\"$stclass\"></td>";
		$storedpackagesstring .= "<td align=\"right\" class=\"formlabel\">".WEIGHT.": </td><td align=\"left\"><input type=\"text\" name=\"weight$packagenumber\" size=\"5\" value=\"$stweight\"></td></tr></table></td></tr></table>\n";
	}
	$packages = 0;
}
if ($addpackage) $packages = $packages + 1;


// Print shipping option form...
if (!$edited || $addpackage) {
  echo "$header
        <div class=\"heading\">".SHIPPINGMETHOD." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>
        <form action=\"editshipping.php\" method=\"post\" name=\"shipoptionform\">
        <table width=\"700\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
        <tr><td align=\"center\" class=\"formlabel\">".SHIPPINGMETHOD.": <input type=\"hidden\" name=\"productid\" value=\"$productid\"><select name=\"shipoption\" onChange=\"document.shipoptionform.submit()\">
		<option value=\"none\"";
		if ($shipoption == "none") echo " selected";
		echo ">".NONE."</option>";
		if ($shippingmethod == "custom" || $shipoptionstype == "custom") {
			echo "<option value=\"flatrate\"";
			if ($shipoption == "flatrate") echo " selected";
			echo ">".FLATRATE."</option>
			<option value=\"wml\"";
			if ($shipoption == "wml") echo " selected";
			echo ">".WATKINSML."</option>
			<option value=\"zone\"";
			if ($shipoption == "zone") echo " selected";
			echo ">".ZIPZONE."</option>
			<option value=\"quantity\"";
			if ($shipoption == "quantity") echo " selected";
			echo ">".QUANTITY."</option>";
		}
		
		if ($shippingmethod == "usps") {
			echo "<option value=\"usps\"";
			if ($shipoption == "usps") echo " selected";
			echo ">".USPS."</option>";
		} else if ($shippingmethod == "ups") {
			echo "<option value=\"ups\"";
			if ($shipoption == "ups") echo " selected";
			echo ">".UPS."</option>";
		} else if ($shippingmethod == "fedex") {
			echo "<option value=\"fedex\"";
			if ($shipoption == "fedex") echo " selected";
			echo ">".FEDEX."</option>";
		}
		echo "<option value=\"storewide\"";
		if ($shipoption == "storewide") echo " selected";
		echo ">".STOREWIDE."</option></select></td></tr>";

		if ($shipoption == "zone") {
			echo "<tr><td class=\"formlabel\" align=\"center\">".ZIPZONELOOKUPTABLE.": <select name=\"zipzonename\">";

			for ($i = 0; $i < count($zipzonenames); $i++) {
				echo "<option value=\"$zipzonenames[$i]\"";
				if ($zipzonenames[$i] == $zipzonename) echo " selected ";
				echo ">".$zipzonenames[$i]."</option>";
			}
			echo "</select></td></tr><tr><td align=\"center\"><a href=\"editzones.php\" class=\"nav2\">".MANAGEZIPTABLES."</a></td></tr><tr><td align=\"center\" class=\"formlabel\">".ZONESHIPPINGAMOUNTS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> <a href=\"$help2\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></td></tr>";

			for ($i = 2; $i <= 8; $i++) {
				echo "<tr><td align=\"center\"><table width=\"180\" align=\"center\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\"><tr bgcolor=\"#D0D0D0\"><td align=\"left\" class=\"formlabel\">".ZONE." $i ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"edzone$i\" size=\"8\" value=\"$zoneamounts[$i]\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr><tr><td></td></tr></table>";
			}
		}

		if ($shipoption == "quantity") {
			$sql = "SELECT quantity,rate FROM quantityrates WHERE productid='$productid' ORDER BY quantity";
			$result = mysqli_query($db, $sql);

			echo "</tr>";
			if ($quantity_exists) echo "<tr><td align=\"center\"><p class=\"error\"><b>".ERROR."</b>: ".QUANTITYAMOUNTEXISTS."</p></td></tr>";
			else if ($msg=="updated") echo "<tr><td align=\"center\"><p class=\"confirm\">".SUCCESSFULLYUPDATED."</p></td></tr>";

			echo 	"</form><tr><td align=\"center\" class=\"formtitle\">".SHIPPINGAMOUNTBASEDONQTY." <a href=\"$help4\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></td></tr>
				<tr><td align=\"center\">
					<table bgcolor=\"#D0D0D0\" border=\"0\" width=\"400\" cellpadding=\"0\" cellspacing=\"5\" align=\"center\">
					<tr><td align=\"left\" class=\"formlabel\">".QUANTITY."</td><td align=\"left\" class=\"formlabel\">".SHIPPING."</td></tr>";
			$results = false;
			while ($row = @mysqli_fetch_array($result)) {
				$results = true;
				echo  "<tr>
					<form action=\"editquantities.php\" method=\"POST\">
					<input type=\"hidden\" name=\"productid\" value=\"$productid\">";

				if ($row["quantity"] != "1") echo "<input type=\"hidden\" name=\"origquantity\" value=\"$row[quantity]\">
					<td align=\"left\"><input type=\"text\" name=\"quantity\" value=\"$row[quantity]\" size=\"8\"></td><td align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"rate\" value=\"".number_format($row["rate"],2,'.','')."\" size=\"8\"> ".$currencysymbols[$ashopcurrency]["post"]."</td>\n<form action=\"editquantities.php\" method=\"POST\">
						<input type=\"hidden\" name=\"productid\" value=\"$productid\">
						<td align=\"right\"><table width=\"40\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td><input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\">&nbsp;</td><td><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"></td></tr></table></td></form>";
				else echo "<input type=\"hidden\" name=\"action\" value=\"updatefirst\">
					<td align=\"left\">$row[quantity]</td><td align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"rate\" value=\"".number_format($row["rate"],2,'.','')."\" size=\"8\"> ".$currencysymbols[$ashopcurrency]["post"]."</td><td align=\"right\"><input type=\"submit\" value=\"".UPDATE."\"></td></form><td>&nbsp;</td>";
				echo "</tr><tr><td colspan=\"3\"></td></tr>";
			}

			// no entries in database
			if (!$results){
				echo "<form action=\"editquantities.php\" method=\"POST\">
						<tr><td valign=\"middle\" align=\"left\">1</td>
						<td valign=\"middle\">
						<input type=\"hidden\" name=\"productid\" value=\"$productid\">
						<input type=\"hidden\" name=\"action\" value=\"addfirstrate\">
						".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" size=\"8\" name=\"rate\" value=\"0.00\"> ".$currencysymbols[$ashopcurrency]["post"]."</td>
						<td valign=\"middle\"><input type=\"submit\" value=\"".UPDATE."\"></td></form></tr></table></td></tr>
						<tr><td valign=\"middle\" colspan=\"2\" class=\"formlabel\">".ENTERSHIPPINGFORSINGLEPRODUCT."</td></tr>";
			}
			else echo "<tr><form action=\"editquantities.php\" method=\"POST\"><td align=\"left\">
						<input type=\"text\" name=\"quantity\" size=\"8\"></td>
						<input type=\"hidden\" name=\"productid\" value=\"$productid\">
						<input type=\"hidden\" name=\"action\" value=\"add\">
					<td align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"rate\" size=\"8\"> ".$currencysymbols[$ashopcurrency]["post"]."
					</td>
					<td align=\"right\">
						<input type=\"submit\" value=\"".ADDROW."\">
					</td></form>
					<td>&nbsp;</td>
				</tr>
				</table></td></tr>";
		}

       	if ($shipoption == "flatrate") {
			echo "<tr><td align=\"center\"><table  bgcolor=\"#dododo\" width=\"600\" style=\"padding-bottom: 10px;\"><tr><td align=\"right\" class=\"formlabel\">".FLATRATESHIPPING.":</td><td class=\"formlabel\"> ".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"shipping\" size=\"10\" value=\"$productshipping\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr><tr><td align=\"right\" class=\"formlabel\">".INTERNATIONALRATE.":</td><td class=\"formlabel\"> ".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"intshipping\" size=\"10\" value=\"$productintshipping\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			$ratetocountrynumber = 0;
			$alreadyusedcountries = array();
			if (!empty($productcountryshipping)) {
				$productcountryshippingarray = explode("|",$productcountryshipping);
				foreach ($productcountryshippingarray as $thisproductcountryshipping) {
					$thisproductcountryshippingarray = explode(":",$thisproductcountryshipping);
					echo "<tr style=\"margin-bottom: 10px;\"><td align=\"right\" class=\"formlabel\">".RATETO." <select name=\"ratetocountry[$ratetocountrynumber]\"><option value=\"none\">".CHOOSECOUNTRY."</option>\n";
					foreach ($countries as $shortcountry=>$longcountry) {
						if (!in_array($shortcountry,$alreadyusedcountries)) {
							if (strlen($longcountry) > 35) $longcountry = substr($longcountry,0,32)."...";
							echo "<option value=\"$shortcountry\"";
							if ($thisproductcountryshippingarray[0] == $shortcountry) {
								echo " selected";
								$alreadyusedcountries[] = $shortcountry;
							}
							echo ">$longcountry</option>\n";
						}
					}
					echo "</select>:</td><td class=\"formlabel\"> ".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"countryshipping[$ratetocountrynumber]\" size=\"10\" value=\"".$thisproductcountryshippingarray[1]."\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
					$ratetocountrynumber++;
				}
			}
			echo "<tr style=\"margin-bottom: 10px;\"><td align=\"right\" class=\"formlabel\">".RATETO." <select name=\"ratetocountry[$ratetocountrynumber]\"><option value=\"none\">".CHOOSECOUNTRY."</option>\n";
			foreach ($countries as $shortcountry=>$longcountry) {
				if (!in_array($shortcountry,$alreadyusedcountries)) {
					if (strlen($longcountry) > 35) $longcountry = substr($longcountry,0,32)."...";
					echo "<option value=\"$shortcountry\">$longcountry</option>\n";
				}
			}
			echo "</select>:</td><td class=\"formlabel\"> ".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"countryshipping[$ratetocountrynumber]\" size=\"10\" value=\"\"> ".$currencysymbols[$ashopcurrency]["post"]." <input type=\"submit\" name=\"addcountry\" value=\"Add\"></td></tr>";

		}
       	if ($shipoption == "storewide" && ($storeshippingmethod == "perpound" || $storeshippingmethod == "byweight" || $storeshippingmethod == "usps" || $storeshippingmethod == "ups" || $storeshippingmethod == "fedex")) echo "<tr><td align=\"center\"><table  bgcolor=\"#dododo\" width=\"300\"><tr><td align=\"right\" class=\"formlabel\">".WEIGHT.":</td><td class=\"formlabel\"> <input type=\"text\" name=\"nproductweightpounds\" size=\"5\" value=\"$productweightpounds\"> ".POUND." <input type=\"text\" name=\"nproductweightounces\" size=\"5\" value=\"$productweightounces\"> ".OUNCE."</td></tr>";
		echo "</td></tr></table>";
		if ($shipoption == "usps" || $shipoption == "ups" || $shipoption == "fedex" || $shipoption == "wml") {
			echo "<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td align=\"center\" class=\"formtitle\">".SINGLEORMULTIPLE."<br>".USGROUNDSHIPMENTS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a></td><td></td></tr></table>$storedpackagesstring";
			$totalpackages = $storedpackages + $packages;
			if ($packages > 0) for ($i = $storedpackages; $i < $totalpackages; $i++) {
				$thispackage = $i + 1;
				$shippingcountry = "US";
				echo "\n<br><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\" bgcolor=\"#D0D0D0\"><tr><td valign=\"top\" class=\"formtitle\">".PACKAGE." $thispackage:</td><td width=\"75%\" valign=\"top\">"; /* I'll leave this for now.. uncomment to activate multiple origin countries... <table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"left\" class=\"formlabel\">Origin country:</td><td><SELECT NAME=\"origcountry$thispackage\"><option  value=none>choose country";

				foreach ($countries as $shortcountry => $longcountry) {
					echo "<option  value=$shortcountry";
					if ($shortcountry == $shippingcountry) echo " selected";
					echo ">$longcountry\n";
				}

				echo "</SELECT></td></tr></table> */
				echo "</td></tr><tr><td></td><td><input type=\"hidden\" name=\"origcountry$thispackage\" value=\"US\">";

				if ($shippingcountry == "US" && $shipoption != "wml") {
					echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" width=\"120\" class=\"formlabel\">".ORIGINSTATE.":</td><td align=\"left\"><SELECT NAME=\"origstate$thispackage\"><option  value=none>".CHOOSESTATE;
					foreach ($uscanstates as $longstate => $shortstate) {
						echo "<option  value=$shortstate";
						if ($shortstate == $storiginstate) echo " selected";
						echo ">$longstate\n";
					}
					echo "</SELECT></td></tr></table>";
				}
				echo "</td></tr><tr><td></td><td><table width=\"100%\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" width=\"120\" class=\"formlabel\">".ORIGINZIP.":</td><td><input type=\"text\" name=\"origzip$thispackage\" size=\"10\" value=\"$storiginzip\"></td>";
				if ($shipoption == "wml") echo "<td align=\"right\"  class=\"formlabel\">".SHIPPINGCLASS.": </td><td align=\"left\"><input type=\"text\" name=\"class$thispackage\" size=\"5\"></td>";
				echo "<td align=\"right\"  class=\"formlabel\">".WEIGHT.": </td><td align=\"left\"><input type=\"text\" name=\"weight$thispackage\" size=\"5\"></td></tr></table></td></tr></table>\n";
			}
			echo "\n<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\"><tr><td align=\"right\"  class=\"formlabel\">".TOTALWEIGHT.": $totalweight</td></tr></table>\n<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td><input type=\"hidden\" name=\"numberofpackages\" value=\"$totalpackages\"><input type=\"submit\" name=\"addpackage\" value=\"".ADDPACKAGE."\"></td><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\" name=\"edited\">";
		}
		if($shipoption == "flatrate" || $shipoption == "none" || $shipoption == "zone" || $shipoption == "storewide") echo "<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\" name=\"edited\">";
		echo "<input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></td></tr></table></form></td></tr></table></td></tr></table>$footer";
} else header ("Location: editcatalogue.php?cat=$cat&pid=$pid&search=$search&resultpage=$resultpage");
?>