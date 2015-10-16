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
// Get context help for this page...
$contexthelppage = "editinventory";
include "help.inc.php";

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid='$productid'";
$result = @mysqli_query($db, $sql);
$productcopyof = @mysqli_result($result, 0, "copyof");
$productname = @mysqli_result($result, 0, "name");
$productowner = @mysqli_result($result, 0, "userid");
$productname = str_replace("\"", "&quot;", $productname);
$productsku = @mysqli_result($result, 0, "skucode");
$productqtylimit = @mysqli_result($result, 0, "qtylimit");
if (empty($productqtylimit)) $productqtylimit = 0;
$productqtytlimit = @mysqli_result($result, 0, "qtytlimit");
if (empty($productqtytlimit)) $productqtytlimit = 0;
$checksaasuinventory = "";
if ($saasuwsaccesskey && $saasufileid) {
	$checksaasuinventory = ashop_saasu_getinventory($productsku);
	if ($checksaasuinventory != "nodata") {
		$productinventory = $checksaasuinventory;
		@mysqli_query($db, "UPDATE product SET inventory = '$productinventory' WHERE productid='$productid'");
	} else $productinventory = @mysqli_result($result, 0, "inventory");
} else $productinventory = @mysqli_result($result, 0, "inventory");
$productuseinventory = @mysqli_result($result, 0, "useinventory");
$productlowlimit = @mysqli_result($result, 0, "lowlimit");

// Get eMerchant vendor settings for this product...
$productvendor = @mysqli_result($result, 0, "vendorid");
$productcost = @mysqli_result($result, 0, "cost");

// Check if there are variations of this product...
$typestring = array();
$typevalues = array();
ashop_gettypes($productid);

// Check if separate inventory should be kept..
$separateinventory = TRUE;
if (!empty($productsku)) {
	$result = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid'");
	if (@mysqli_num_rows($result)) {
		$result = @mysqli_query($db, "SELECT * FROM productinventory WHERE skucode!='$productsku' AND productid='$productid'");
		if (!@mysqli_num_rows($result)) $separateinventory = FALSE;
	}
}

// Check the total inventory for this product...
if (!empty($typevalues) && $separateinventory) {
	$result = @mysqli_query($db, "SELECT SUM(inventory) AS totalinventory FROM productinventory WHERE productid='$productid'");
	$productinventory = @mysqli_result($result,0,"totalinventory");
}

// Generate owner member name...
if ($productowner == "1") $productowner = ADMINISTRATOR;
else {
	$result = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='$productowner'");
	$productowner = @mysqli_result($result, 0, "shopname");
}

// Handle editing of the product inventory...
if ($productid) {

// Get eMerchant vendors if any...
$result = @mysqli_query($db, "SELECT * FROM emerchant_vendor ORDER BY name");
if (@mysqli_num_rows($result)) {
	$vendorlist = "<select name=\"vendor\"><option value=\"0\">".NONE."</option>";
	while ($row = @mysqli_fetch_array($result)) {
		$vendorlist .= "<option value=\"{$row["vendorid"]}\"";
		if ($productvendor == $row["vendorid"]) $vendorlist .= " selected";
		$vendorlist .= ">{$row["name"]}</option>";
	}
	$vendorlist .= "</select>";
}

  // Show edit form...
  if (!$edited) {
	  echo "$header
        <div class=\"heading\">".EDITPRODUCTINVENTORY."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>
        <form action=\"editinventory.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">";
		echo "<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\"><tr><td colspan=\"2\" class=\"formlabel\" align=\"center\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image0','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image0\" align=\"absmiddle\" onclick=\"return overlib('$tip0');\" onmouseout=\"return nd();\"></a> ".PRODUCTID.": $productid</td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a> ".SKU.":</td><td align=\"left\"><input type=\"text\" name=\"skucode\" size=\"25\" value=\"$productsku\"><script language=\"JavaScript\">document.productform.skucode.focus();</script></td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".ACTIVATEINVENTORY.":</td><td class=\"formlabel\" align=\"left\"><input type=\"checkbox\" name=\"useinventory\""; if ($productuseinventory) echo " checked"; echo "></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".MAXIMUM.":</td><td align=\"left\" class=\"formlabel\"><input type=\"text\" name=\"qtylimit\" size=\"10\" value=\"$productqtylimit\"> ".ITEMSPERCUSTOMER." <span class=\"sm\">".ZEROUNLIMITED."</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".MAXIMUM.":</td><td align=\"left\" class=\"formlabel\"><input type=\"text\" name=\"qtytlimit\" size=\"10\" value=\"$productqtytlimit\"> ".ITEMSPERPURCHASE." <span class=\"sm\">".ZEROUNLIMITED."</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image18','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image18\" align=\"absmiddle\" onclick=\"return overlib('$tip18');\" onmouseout=\"return nd();\"></a>";
		if (!empty($typevalues)) echo " ".TOTALITEMSINSTOCK.":";
		else echo " ".ITEMSINSTOCK.":";
		echo "</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"inventory\" size=\"10\" value=\"$productinventory\"";
		if (!empty($checksaasuinventory) && $checksaasuinventory != "nodata") echo " disabled> ".FROMSAASU." <a href=\"http://www.saasu.com\" target=\"_blank\">SAASU</a></td></tr>";
		else echo "></td></tr>";
		echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image19','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image19\" align=\"absmiddle\" onclick=\"return overlib('$tip19');\" onmouseout=\"return nd();\"></a> ".LOWSTOCKLIMIT.":</td><td align=\"left\"><input type=\"text\" name=\"lowlimit\" size=\"10\" value=\"$productlowlimit\"></td></tr>";
		if ($vendorlist && $userid == "1") echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image20','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image20\" align=\"absmiddle\" onclick=\"return overlib('$tip20');\" onmouseout=\"return nd();\"></a> ".VENDOR.":</td><td align=\"left\">$vendorlist</td></tr><tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image21','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image21\" align=\"absmiddle\" onclick=\"return overlib('$tip21');\" onmouseout=\"return nd();\"></a> ".VENDORCOST.":</td><td align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"cost\" size=\"10\" value=\"$productcost\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
		if ($separateinventory && (empty($checksaasuinventory) || $checksaasuinventory == "nodata")) {
			echo "
			<script language=\"JavaScript\" type=\"text/javascript\">
			<!--
			function shareInventory()
			{
				for (var i = 0; i < document.productform.elements.length; i++) {
						if (document.productform.elements[i].name.substring(0,7) == 'skucode') document.productform.elements[i].value = document.productform.skucode.value;
						else if (document.productform.elements[i].name.substring(0,9) == 'inventory') document.productform.elements[i].value = document.productform.inventory.value;
				}
			}
			-->
			</script>			
			
			<tr><td align=\"right\" class=\"formlabel\">&nbsp;</td><td align=\"left\"><input type=\"button\" class=\"widebutton\" name=\"sharedinventory\" value=\"".SHAREDINVENTORY."\" onClick=\"shareInventory();\">";

		}
		if (!empty($typevalues)) {
			foreach ($typevalues as $typenumber=>$type) {
				$thistypestring = $typestring[$typenumber];
				if ($separateinventory) {
					$typeresult = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid' AND type='$thistypestring'");
					$typerow = @mysqli_fetch_array($typeresult);
				} else {
					$typerow["skucode"] = $productsku;
					$typerow["inventory"] = $productinventory;
				}
				$typeskucode = $typerow["skucode"];
				$checksaasutypeinventory = "";
				if ($saasuwsaccesskey && $saasufileid) {
					$checksaasutypeinventory = ashop_saasu_getinventory($typeskucode);
					if ($checksaasutypeinventory != "nodata") {
						$typeinventory = $checksaasutypeinventory;
						if ($typeinventory < $typerow["inventory"]) {
							$subtractfrominventory = $typerow["inventory"] - $typeinventory;
							@mysqli_query($db, "UPDATE productinventory SET inventory = '$typeinventory' WHERE productid='$productid' AND type='$thistypestring'");
							@mysqli_query($db, "UPDATE product SET inventory = inventory-'$subtractfrominventory' WHERE productid='$productid' AND type='$thistypestring'");
						} else if ($typeinventory > $typerow["inventory"]) {
							$addtoinventory = $typerow["inventory"] - $typeinventory;
							@mysqli_query($db, "UPDATE productinventory SET inventory = '$typeinventory' WHERE productid='$productid' AND type='$thistypestring'");
							@mysqli_query($db, "UPDATE product SET inventory = inventory+'$addtoinventory' WHERE productid='$productid'");
						}
					} else $typeinventory = $typerow["inventory"];
				} else $typeinventory = $typerow["inventory"];
				echo "<tr><td colspan=\"2\" class=\"formlabel\" align=\"center\"><hr>$type</td></tr>
				<tr><td align=\"right\" class=\"formlabel\">".SKU.":</td><td align=\"left\"><input type=\"text\" name=\"skucode$typenumber\" size=\"25\" value=\"$typeskucode\"></td></tr>";
				if ($separateinventory) {
					echo "<tr><td align=\"right\" class=\"formlabel\">".ITEMSINSTOCK.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"inventory$typenumber\" size=\"10\" value=\"$typeinventory\"";
					if ($checksaasutypeinventory && $checksaasutypeinventory != "nodata") echo " disabled> ".FROM." <a href=\"http://www.saasu.com\" target=\"_blank\">SAASU</a><input type=\"hidden\" name=\"typestring$typenumber\" value=\"$thistypestring\"></td></tr>";
					else echo "><input type=\"hidden\" name=\"typestring$typenumber\" value=\"$thistypestring\"></td></tr>";
				} else echo "<input type=\"hidden\" name=\"inventory$typenumber\" size=\"10\" value=\"$productinventory\"><input type=\"hidden\" name=\"typestring$typenumber\" value=\"$thistypestring\"></td></tr>";
			}
		}
		echo "<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"copyof\" value=\"$productcopyof\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></td></tr></table>$footer";
  }
  else {
	if ($useinventory == "on") $useinventory = "1";
	else $useinventory = "0";
	if (!empty($checksaasuinventory) && $checksaasuinventory != "nodata") $sql="UPDATE product SET skucode='$skucode', lowlimit='$lowlimit', qtylimit='$qtylimit', qtytlimit='$qtytlimit' useinventory='$useinventory'";
	else $sql="UPDATE product SET skucode='$skucode', lowlimit='$lowlimit', qtylimit='$qtylimit', qtytlimit='$qtytlimit', inventory='$inventory', useinventory='$useinventory'";
    if ($userid == "1") {
		if (isset($vendor)) $sql .= ", vendorid='$vendor'";
		if (isset($cost)) $sql .= ", cost='$cost'";	
	}
	if ($copyof) $sql.=" WHERE productid=$productid OR copyof='$productid' OR productid='$copyof' OR copyof='$copyof'";
	else $sql.=" WHERE productid=$productid OR copyof='$productid'";
    $result = @mysqli_query($db, $sql);

	// Check for variations...
	$typestrings = $typestring;
	$types = array();
	$totalinventory = 0;
	$separateinventory = FALSE;
	foreach ($_POST as $key=>$value) {
		if (strstr($key,"skucode")) $getnumber = trim(str_replace("skucode","",$key));
		if ((!empty($getnumber) || $getnumber == "0") && !in_array($getnumber,$types)) {
			$types[] = $getnumber;
			$thisskucode = $_POST["skucode{$getnumber}"];
			if ($thisskucode != $skucode) {
				$separateinventory = TRUE;
				$thisinventory = $_POST["inventory{$getnumber}"];
				$totalinventory += $thisinventory;
			} else $thisinventory = $inventory;
			$thistype = $_POST["typestring{$getnumber}"];
			foreach($typestrings as $key => $value) if ($value == $thistype) $thistypenumber = $key;
			$result = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid' AND type='$thistype'");
			if (@mysqli_num_rows($result)) {
				if ($saasuwsaccesskey && $saasufileid && !isset($thisinventory)) @mysqli_query($db, "UPDATE productinventory SET skucode='$thisskucode' WHERE productid='$productid' AND type='$thistype'");
				else @mysqli_query($db, "UPDATE productinventory SET skucode='$thisskucode', inventory='$thisinventory' WHERE productid='$productid' AND type='$thistype'");
				if ($copyof) $result = @mysqli_query($db, "SELECT productid FROM product WHERE copyof='$productid' OR productid='$copyof' OR copyof='$copyof'");
				else $result = @mysqli_query($db, "SELECT productid FROM product WHERE copyof='$productid'");
				while ($row = @mysqli_fetch_array($result)) {
					$typestring = array();
					$typevalues = array();
					ashop_gettypes($row["productid"]);
					$newthistype = $typestring["$thistypenumber"];
					if ($saasuwsaccesskey && $saasufileid && !isset($thisinventory)) @mysqli_query($db, "UPDATE productinventory SET skucode='$thisskucode' WHERE productid='{$row["productid"]}' AND type='$newthistype'");
					else @mysqli_query($db, "UPDATE productinventory SET skucode='$thisskucode', inventory='$thisinventory' WHERE productid='{$row["productid"]}' AND type='$newthistype'");
				}
			} else {
				@mysqli_query($db, "INSERT INTO productinventory (productid, type, skucode, inventory) VALUE ('$productid', '$thistype', '$thisskucode', '$thisinventory')");
				if ($copyof) $result = @mysqli_query($db, "SELECT productid FROM product WHERE copyof='$productid' OR productid='$copyof' OR copyof='$copyof'");
				else $result = @mysqli_query($db, "SELECT productid FROM product WHERE copyof='$productid'");
				while ($row = @mysqli_fetch_array($result)) {
					$typestring = array();
					$typevalues = array();
					ashop_gettypes($row["productid"]);
					$newthistype = $typestring["$thistypenumber"];
					@mysqli_query($db, "INSERT INTO productinventory (productid, type, skucode, inventory) VALUE ('{$row["productid"]}', '$newthistype', '$thisskucode', '$thisinventory')");
				}
			}
		}
	}
	if (!empty($types) && $separateinventory) {
		if ($copyof) @mysqli_query($db, "UPDATE product SET inventory='$totalinventory' WHERE productid='$productid' OR copyof='$productid' OR productid='$copyof' OR copyof='$copyof'");
		else @mysqli_query($db, "UPDATE product SET inventory='$totalinventory' WHERE productid='$productid' OR copyof='$productid'");
	}

	if ($error) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
    else header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>