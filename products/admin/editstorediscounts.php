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

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get customer details for personal discounts...
if (!empty($customerid) && is_numeric($customerid)) {
	$customerresult = @mysqli_query($db, "SELECT firstname,lastname FROM customer WHERE customerid='$customerid'");
	$firstname = @mysqli_result($customerresult,0,"firstname");
	$lastname = @mysqli_result($customerresult,0,"lastname");
	if (!empty($firstname)) {
		$customername = $firstname;
		if (!empty($lastname)) $customername .= " $lastname";
	} else $customername = $lastname;
}

// Get category name for category wide discounts...
if (!empty($cat) && is_numeric($cat)) {
	$categoryresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$cat'");
	$categoryname = @mysqli_result($categoryresult,0,"name");
}

if (strpos($header, "body") != 0) {
	$newheader = substr($header,1,strpos($header, "body")+3);
	$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "body")+4,strlen($header));
} else {
	$newheader = substr($header,1,strpos($header, "BODY")+3);
	$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "BODY")+4,strlen($header));
}
echo "$newheader
	  	<script language=\"JavaScript\">
		function uploadmessage() 
		{
		  if (document.discountform.codelist.value != '') {
			  w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
		  }
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
        </script>
        <div class=\"heading\">";
if (!empty($customername)) echo PERSONALCODESFOR." $customername, ".CUSTOMERID." $customerid <a href=\"editcustomer.php?customerid=$customerid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>";
else echo MANAGEDISCOUNTCODES." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a>";
		echo "</div><table cellpadding=\"10\" align=\"center\" width=\"650\"><tr><td>
        <form action=\"editstorediscounts.php\" enctype=\"multipart/form-data\" method=\"post\" name=\"discountform\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
		      <tr><td colspan=\"2\" class=\"formtitle\">".ADDPERORDERDISCOUNTS."</td></tr>
		      <tr><td width=\"150\" align=\"right\"class=\"formlabel\">".DISCOUNTCODE.":</td><td><input type=\"text\" name=\"code\" id=\"code\" size=\"10\"><script language=\"JavaScript\">document.getElementById('code').focus();</script>
			  </td></tr>";
if (empty($customerid)) echo "
			  <tr><td align=\"right\" class=\"formlabel\">".ORIMPORTLIST.":</td><td><input type=\"file\" name=\"codelist\" size=\"20\">
			  </td></tr>";
if (!empty($categoryname) && empty($customerid)) echo "
			  <tr><td align=\"right\" class=\"formlabel\">".LIMITTOCATEGORY.":</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"categoryid\" value=\"$cat\"> $categoryname
			  </td></tr>";
			  echo "
			  <tr><td align=\"right\" class=\"formlabel\">".GIFTCERTIFICATE.":</td><td class=\"formlabel\"><select name=\"giftcertificate\"><option value=\"0\">".NO."</option><option value=\"1\">".YES."</option></select></td></tr>
			  <tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\"><input type=\"text\" name=\"value\" size=\"7\"><input type=\"radio\" name=\"type\" value=\"%\">% <input type=\"radio\" name=\"type\" value=\"$\">";
			  if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			  else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			  echo "<input type=\"radio\" name=\"type\" value=\"s\"> ".FREESHIPPING;
			  //echo " <input type=\"radio\" name=\"type\" value=\"i\"> ".ITEMSFREEPER." <input type=\"text\" size=\"4\" name=\"prerequisite\" value=\"$prerequisite\"> ".ITEMS;
			  echo "</td></tr>";
			  if ($digitalmall == "ON") echo  "<tr><td>&nbsp;</td><td valign=\"top\"><font size=\"1\" face=\"Arial, Helvetica, sans-serif\" color=\"#FF0000\">".WARNINGAMOUNTDISCOUNT."</font></td></tr>";
		if (!empty($pappath) && file_exists("$pappath/accounts/settings.php")) echo "
		<tr><td align=\"right\"class=\"formlabel\">".PAPAFFILIATEDISCOUNT.":</td><td><input type=\"checkbox\" name=\"daffiliate\"></td></tr>";
		else echo "
		<tr><td align=\"right\"class=\"formlabel\">".SETAFFILIATEID.":</td><td><input type=\"text\" name=\"daffiliate\" size=\"7\"> <font size=\"1\" face=\"Arial, Helvetica, sans-serif\">".OPTIONAL."</font></td></tr>";
		echo "
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"updatestorediscount\" value=\"new\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><td align=\"right\"><input type=\"submit\" value=\"".ADD."\" onClick=\"uploadmessage()\"></td></tr></table></form><br>";

// Update selected storewide discount...
if ($updatestorediscount && !$delete) {
	if (!empty($pappath) && file_exists("$pappath/accounts/settings.php")) {
		if ($daffiliate == "on") $daffiliate = "1";
		if ($daffiliate == "1") @mysqli_query($db, "UPDATE storediscount SET affiliate=''");
	}
	if ($updatestorediscount == "new") {
		if (is_uploaded_file($codelist)) {
			if (@move_uploaded_file($codelist, "$ashopspath/updates/discountcodelist.txt")) {
				$fp = fopen ("$ashopspath/updates/discountcodelist.txt","r");
				if ($fp) {
					while (!feof ($fp)) {
						$codetext = fgets($fp, 4096);
						if (strpos($codetext,",")) $thiscode = substr($codetext,0,strpos($codetext,","));
						else if (strpos($codetext,"|")) $thiscode = substr($codetext,0,strpos($codetext,"|"));
						else if (strpos($codetext,";")) $thiscode = substr($codetext,0,strpos($codetext,";"));
						if (trim($thiscode) && !strstr($codetext,'code') && !strstr($codetext,'firstname')) {
							@mysqli_query($db, "INSERT INTO storediscounts (code, value, type, giftcertificate, affiliate, categoryid) VALUES ('$thiscode', '$value', '$type', '$giftcertificate', '$daffiliate', '$categoryid')");
						}
					}
					fclose($fp);
					unlink ("$ashopspath/updates/discountcodelist.txt");
				}
			}
		} else {
			if (!empty($customerid) && is_numeric($customerid)) {
				@mysqli_query($db, "INSERT INTO storediscounts (code, value, type, prerequisite, affiliate, customerid, giftcertificate, categoryid) VALUES ('$code', '$value', '$type', '$prerequisite', '$daffiliate', '$customerid', '$giftcertificate', '$categoryid')");
				$discountid = @mysqli_insert_id($db);
				echo "<meta http-equiv=\"Refresh\" content=\"0; URL=salesadmin.php?discount=$discountid\">";
			} else @mysqli_query($db, "INSERT INTO storediscounts (code, value, type, prerequisite, affiliate, giftcertificate, categoryid) VALUES ('$code', '$value', '$type', '$prerequisite', '$daffiliate', '$giftcertificate', '$categoryid')");
		}
	} else @mysqli_query($db, "UPDATE storediscounts SET code='$code', value='$value', type='$type', prerequisite='$prerequisite', affiliate='$daffiliate', categoryid='$categoryid' WHERE discountid='$updatestorediscount'");
} else if ($updatestorediscount && $delete) {
	@mysqli_query($db, "DELETE FROM storediscounts WHERE discountid='$updatestorediscount'");
}

// Display current storewide discounts...
if ($customerid) $result = @mysqli_query($db, "SELECT * FROM storediscounts WHERE customerid='$customerid' ORDER BY code");
else $result = @mysqli_query($db, "SELECT * FROM storediscounts ORDER BY code");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$discountid = @mysqli_result($result, $i, "discountid");
	$discountcode = @mysqli_result($result, $i, "code");
	$discountvalue = @mysqli_result($result, $i, "value");
	$discounttype = @mysqli_result($result, $i, "type");
	if ($discounttype == "i") $discountvalue = intval($discountvalue);
	$discountprerequisite = @mysqli_result($result, $i, "prerequisite");
	$discountaffiliate = @mysqli_result($result, $i, "affiliate");
	$discountcustomer = @mysqli_result($result, $i, "customerid");
	$discountcategoryid = @mysqli_result($result, $i, "categoryid");
	if ($discountcategoryid) {
		$discountcategoryresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$discountcategoryid'");
		$discountcategory = @mysqli_result($discountcategoryresult,0,"name");
	} else $discountcategory = "";
	$discountgiftcertificate = @mysqli_result($result, $i, "giftcertificate");
	$discountused = @mysqli_result($result, $i, "used");
	if (empty($discountused)) $discountused = 0;
	echo "<form action=\"editstorediscounts.php\" method=\"post\" name=\"discountform$i\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
		    <tr><td align=\"right\" class=\"formlabel\" width=\"150\">";
			if ($discountcustomer) echo PERSONALDISCOUNT;
			else if ($discountgiftcertificate) echo GIFTCERTIFICATE;
			else echo DISCOUNT;
			echo CODE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"code\" value=\"$discountcode\" size=\"10\">";
			if ($discountcustomer && empty($customerid)) echo " <a href=\"editcustomer.php?customerid=$discountcustomer\"><img src=\"images/icon_profile.gif\" border=\"0\"></a>";
			echo " ".USED.": $discountused ".TIMES."</td></tr>";
			if ($discountcategory) echo "
			<tr><td align=\"right\" class=\"formlabel\">".LIMITTOCATEGORY.":</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"categoryid\" value=\"$discountcategoryid\" checked> $discountcategory</td></tr>";
			echo "
			<tr><td align=\"right\" class=\"formlabel\">";
			if ($discountgiftcertificate) echo "Value:";
			else echo DISCOUNT.":";
			echo "</td><td class=\"formlabel\">";
			if ($discountgiftcertificate && $currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			echo "<input type=\"text\" name=\"value\" value=\"$discountvalue\" size=\"7\"";
			if ($discounttype=="s") echo " disabled";
			echo ">";
			if (!$discountgiftcertificate) {
				echo "<input type=\"radio\" onClick=\"javascript: discountform$i.value.disabled=false;\" name=\"type\" value=\"%\"";
				if ($discounttype=="%" || !$discounttype) echo " checked";
				echo ">% <input type=\"radio\" onClick=\"javascript: discountform$i.value.disabled=false;\" name=\"type\" value=\"$\"";
				if ($discounttype=="$") echo "checked";
				echo ">";
			} else echo "<input type=\"hidden\" name=\"type\" value=\"$\" />";
			if (!$discountgiftcertificate && $currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			if (!$discountgiftcertificate) {
				echo "<input onClick=\"javascript: discountform$i.value.disabled=true;\" type=\"radio\" name=\"type\" value=\"s\""; if ($discounttype=="s") echo "checked"; echo "> ".FREESHIPPING;
				//echo " <input type=\"radio\" name=\"type\" value=\"i\""; if ($discounttype=="i") echo "checked"; echo "> ".ITEMSFREEPER." <input type=\"text\" size=\"4\" name=\"prerequisite\" value=\"$discountprerequisite\"> ".ITEMS;
				echo "</td></tr>";
				if (!empty($pappath) && file_exists("$pappath/accounts/settings.php")) {
					echo "
					<tr><td align=\"right\"class=\"formlabel\">".PAPAFFILIATEDISCOUNT.":</td><td><input type=\"checkbox\" name=\"daffiliate\"";
					if ($discountaffiliate == "1") echo " checked";
					echo "></td></tr>";
				} else echo "
				<tr><td align=\"right\"class=\"formlabel\">".SETAFFILIATEID.":</td><td><input type=\"text\" name=\"daffiliate\" value=\"$discountaffiliate\" size=\"7\"></td></tr>";
				if ($discounttype=="s") echo "<script language=\"JavaScript\">discountform$i.value.disabled=true;</script>";
			}
			echo "
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"updatestorediscount\" value=\"$discountid\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><td align=\"right\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".REMOVEBUTTON."\"></td></tr></table></form><br>";
}

// Display current personal per product discounts...
unset($result);
if ($customerid) $result = @mysqli_query($db, "SELECT * FROM discount WHERE customerid='$customerid' ORDER BY code");
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$discountid = @mysqli_result($result, $i, "discountid");
	$discountcode = @mysqli_result($result, $i, "code");
	$discountvalue = @mysqli_result($result, $i, "value");
	$discounttype = @mysqli_result($result, $i, "type");
	$discountcustomer = @mysqli_result($result, $i, "customerid");
	$discountonetime = @mysqli_result($result, $i, "onetime");
	$discountproductid = @mysqli_result($result, $i, "productid");
	// Get usage stats...
	$statsresult = @mysqli_query($db, "SELECT orderid FROM orders WHERE paid!='' AND paid IS NOT NULL AND (productdiscounts LIKE '$productid:$discountid|%' OR productdiscounts LIKE '%|$productid:$discountid' OR productdiscounts LIKE '%|$productid:$discountid|%' OR productdiscounts = '$productid:$discountid')");
	$discountstats = @mysqli_num_rows($statsresult);
	if (empty($discountstats)) $discountstats = 0;
	$productresult = @mysqli_query($db, "SELECT name FROM product WHERE productid='$discountproductid'");
	$productname = @mysqli_result($productresult,0,"name");
	echo "<form action=\"editdiscount.php\" method=\"post\" name=\"discountform$i\">
		<table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\" align=\"center\">
		    <tr><td align=\"right\" class=\"formlabel\" width=\"150\">".PERSONALDISCOUNT.CODE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"code\" value=\"$discountcode\" size=\"10\"> ".THEWORDFOR." <a href=\"editcatalogue.php?pid=$discountproductid\">$productname</a>
			 - ".USED.": $discountstats ".TIMES."</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.":</td><td class=\"formlabel\">";
	if ($discountgiftcertificate && $currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
	echo "<input type=\"text\" name=\"value\" value=\"$discountvalue\" size=\"7\">
	<input type=\"radio\" name=\"type\" value=\"%\"";
	if ($discounttype=="%" || !$discounttype) echo " checked";
	echo ">% <input type=\"radio\" name=\"type\" value=\"$\"";
	if ($discounttype=="$") echo "checked";
	echo ">";
	if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
	else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
	echo "</td></tr>
	<tr><td>&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"onetime\"";
	if ($discountonetime) echo " checked";
	echo "> ".ONETIMEDISCOUNT."</td></tr>
	<tr><td>&nbsp;</td>
	<input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$discountproductid\">
	<input type=\"hidden\" name=\"discountid\" value=\"$discountid\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\">
	<td align=\"right\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"remove\" value=\"".REMOVEBUTTON."\"></td></tr></table></form><br>";
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>