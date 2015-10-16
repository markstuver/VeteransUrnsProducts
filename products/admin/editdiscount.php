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
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editdiscount.inc.php";
// Get context help for this page...
$contexthelppage = "editdiscount";
include "help.inc.php";

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if ($productid) {
	$sql="SELECT * FROM product WHERE productid = $productid";
	$result = @mysqli_query($db, $sql);
	$productcopyof = @mysqli_result($result, 0, "copyof");
	if ($productcopyof) $result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productcopyof'");
	$productname = @mysqli_result($result, 0, "name");
	$productname = str_replace("\"", "&quot;", $productname);
} else {
	$noproducts = 0;
	if($cat != "all") {
		$result = @mysqli_query($db, "SELECT * FROM category WHERE categoryid = '$cat'");
		$categoryname = @mysqli_result($result, 0, "name");
		$categoryname = str_replace("\"", "&quot;", $categoryname);
		$result = @mysqli_query($db, "SELECT * FROM productcategory WHERE categoryid = '$cat'");
	} else $result = @mysqli_query($db, "SELECT * FROM product");
	if(!@mysqli_num_rows($result)) $noproducts = 1;
}

// Handle editing of the product discounts...
if (!$edited) {
	  echo "$header
        <div class=\"heading\">".MANAGEDISCOUNTCODES." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\" width=\"500\"><tr><td>
        <form action=\"editdiscount.php\" method=\"post\" name=\"discountform\">";
		echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		      <tr><td width=\"50%\" colspan=\"2\" class=\"formtitle\">".ADD;
		if(!$productid) echo " ".THEWORDOR;
		else echo ",";
		echo " ".REMOVE." ";
		if($productid) echo OREDIT." ";
		echo DISCOUNTFOR." ";
		if($productid) echo "<b>$productname</b>";
		else {
			echo ALLPRODUCTS;
			if ($cat != "all") echo " ".INCATEGORY.": <b>$categoryname</b>";
		}
		echo "</td></tr>
		      <tr><td align=\"right\" class=\"formlabel\">".DISCOUNTCODE.":</td><td><input type=\"text\" name=\"code\" size=\"10\" value=\"$discountcode\"><script language=\"JavaScript\">discountform.code.focus();</script> <span class=\"sm\">".LEAVEBLANKTOAPPLY."</span></td></tr>
			  <tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\"><input type=\"text\" name=\"value\" size=\"7\" value=\"$discountvalue\"><input type=\"radio\" name=\"type\" value=\"%\"";
		if ($discounttype=="%" || !$discounttype) echo "checked";
		echo ">% <input type=\"radio\" name=\"type\" value=\"$\"";
		if ($discounttype=="$") echo "checked";
		echo ">";
		if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
		else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
		echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".CUSTOMERID.":</td><td><input type=\"text\" name=\"customerid\" size=\"3\"> <span class=\"sm\">".MAKESITPERSONAL."</span></td></tr>
			<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"onetime\"";
			if ($discountonetime) echo " checked";
			echo "> ".ONETIMEDISCOUNT."
				  </td></tr>
			<tr><td align=\"right\"class=\"formlabel\">".SETAFFILIATEID.":</td><td><input type=\"text\" name=\"daffiliate\" size=\"7\"> <font size=\"1\" face=\"Arial, Helvetica, sans-serif\">".OPTIONAL."</font></td></tr>
			<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" value=\"";
		if($discountcode) echo EDIT;
		else echo ADD;
		echo "\">&nbsp;<input type=\"submit\" name=\"remove\" value=\"".REMOVEBUTTON."";
		if ($cat == "all") echo " ".ALLDISCOUNTS;
		echo "\"></td></tr></table></form>";

		if ($productid) {
			// List existing discounts...
			$sql="SELECT * FROM discount WHERE productid = $productid";
			$result = @mysqli_query($db, $sql);
			while ($row = @mysqli_fetch_array($result)) {
				$discountid = $row["discountid"];
				$discountcode = $row["code"];
				$discounttype = $row["type"];
				$discountvalue = $row["value"];
				$discountonetime = $row["onetime"];
				$discountcustomerid = $row["customerid"];
				$discountaffiliate = $row["affiliate"];
				// Get usage stats...
				$statsresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE paid!='' AND paid IS NOT NULL AND (productdiscounts LIKE '$productid:$discountid|%' OR productdiscounts LIKE '%|$productid:$discountid' OR productdiscounts LIKE '%|$productid:$discountid|%' OR productdiscounts = '$productid:$discountid')");
				$discountstats = @mysqli_num_rows($statsresult);
				if (empty($discountstats)) $discountstats = 0;
				echo "<form action=\"editdiscount.php\" method=\"post\" name=\"discountform\">
				<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
				<tr><td align=\"right\" class=\"formlabel\" width=\"118\">";
				if (!empty($discountcustomerid) && is_numeric($discountcustomerid)) echo PERSONALDISCOUNT.":";
				else echo DISCOUNTCODE.":";
				echo "</td><td class=\"formlabel\"><input type=\"text\" name=\"code\" size=\"10\" value=\"$discountcode\">";
				if ($discountcustomerid) echo " <a href=\"editcustomer.php?customerid=$discountcustomerid\"><img src=\"images/icon_profile.gif\" border=\"0\"></a>";
				echo " ".USED.": $discountstats ".TIMES."</td></tr>
				<tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\"><input type=\"text\" name=\"value\" size=\"7\" value=\"$discountvalue\"><input type=\"radio\" name=\"type\" value=\"%\"";
				if ($discounttype=="%" || !$discounttype) echo "checked";
				echo ">% <input type=\"radio\" name=\"type\" value=\"$\"";
				if ($discounttype=="$") echo "checked";
				echo ">";
				if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
				else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
				echo "</td></tr>
				<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"onetime\"";
				if ($discountonetime) echo " checked";
				echo "> ".ONETIMEDISCOUNT."
				</td></tr>
				<tr><td align=\"right\"class=\"formlabel\">".SETAFFILIATEID.":</td><td><input type=\"text\" name=\"daffiliate\" value=\"$discountaffiliate\" size=\"7\"></td></tr>
				<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"customerid\" value=\"$discountcustomerid\"><input type=\"hidden\" name=\"discountid\" value=\"$discountid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" value=\"".UPDATE."\">&nbsp;<input type=\"submit\" name=\"remove\" value=\"".REMOVEBUTTON."\"></td></tr></table></form>";
			}
		}
		echo "</td></tr></table>$footer";
} else {
	if ($onetime == "on" && $code) $onetime = 1;
	else unset($onetime);
	if ($discountid && !$remove) @mysqli_query($db, "UPDATE discount SET code='$code', value='$value', type='$type', onetime='$onetime', affiliate='$daffiliate', customerid='$customerid' WHERE discountid='$discountid'");
	else if ($value && !$remove) {
		if($productid) @mysqli_query($db, "INSERT INTO discount (productid, code, value, type, onetime, customerid, affiliate) VALUES ('$productid', '$code', '$value', '$type', '$onetime', '$customerid', '$daffiliate')");
		else {
			if ($cat != "all") {
				$categoriesresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE parentcategoryid='$cat' OR grandparentcategoryid='$cat' OR categoryid='$cat'");
				while ($categoryrow = @mysqli_fetch_array($categoriesresult)) {
					$categoryid = $categoryrow["categoryid"];
					$result = @mysqli_query($db, "SELECT productcategory.productid, product.copyof FROM productcategory, product WHERE categoryid='$categoryid' AND productcategory.productid=product.productid");
					while ($row = @mysqli_fetch_array($result)) {
						if ($customerid) @mysqli_query($db, "DELETE FROM discount WHERE customerid='$customerid' AND (productid='".$row["productid"]."' OR productid='".$row["copyof"]."')");
						else @mysqli_query($db, "DELETE FROM discount WHERE productid='".$row["productid"]."' OR productid='".$row["copyof"]."'");
						if (!empty($row["copyof"]) && is_numeric($row["copyof"])) $thisproductid = $row["copyof"];
						else $thisproductid = $row["productid"];
						@mysqli_query($db, "INSERT INTO discount (productid, code, value, type, onetime, customerid) VALUES ('$thisproductid', '$code', '$value', '$type', '$onetime', '$customerid')");
					}
				}
			} else {
				@mysqli_query($db, "DELETE FROM discount");
				$result = @mysqli_query($db, "SELECT * FROM product WHERE copyof IS NULL OR copyof='' OR copyof='0'");
				while ($row = @mysqli_fetch_array($result)) {
					@mysqli_query($db, "INSERT INTO discount (productid, code, value, type, onetime) VALUES (".$row["productid"].", '$code', '$value', '$type', '$onetime')");
				}
			}
		}
	} else if($remove) {
		if($discountid) {
			@mysqli_query($db, "DELETE FROM onetimediscounts WHERE discountid='$discountid'");
			@mysqli_query($db, "DELETE FROM discount WHERE discountid='$discountid'");
		} else if ($cat != "all") {
			$result = @mysqli_query($db, "SELECT productcategory.productid, product.copyof FROM productcategory, product WHERE categoryid='$cat' AND productcategory.productid=product.productid");
			while ($row = @mysqli_fetch_array($result)) {
				if (!empty($row["copyof"]) && is_numeric($row["copyof"])) $thisproductid = $row["copyof"];
				else $thisproductid = $row["productid"];
				$result2 = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$thisproductid'");
				$discountid = @mysqli_result($result2,0,"discountid");
				@mysqli_query($db, "DELETE FROM onetimediscounts WHERE discountid='$discountid'");
				@mysqli_query($db, "DELETE FROM discount WHERE productid='$thisproductid'");
			}
		} else {
			@mysqli_query($db, "DELETE FROM discount");
			@mysqli_query($db, "DELETE FROM onetimediscounts");
		}
	}

	if ($cat == "all") $cat = "";

	if ($error) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
	else if ($customerid && !$discountid) {
		$discountid = @mysqli_insert_id($db);
		header ("Location: salesadmin.php?pdiscount=$discountid");
	} else if (!$productid && $cat) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
	else header ("Location: editdiscount.php?productid=$productid&cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
}
?>