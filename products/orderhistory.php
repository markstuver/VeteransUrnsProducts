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
include "admin/ashopconstants.inc.php";
include "admin/ashopfunc.inc.php";

// Validate variables...
if (!ashop_is_md5($_COOKIE["customersessionid"])) $_COOKIE["customersessionid"] = "";

if (empty($_COOKIE["customersessionid"]) && empty($_COOKIE["wssessionid"])) {
	header("Location: signupform.php");
	exit;
}

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none") include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/orderhistory.inc.php";

// Get member template path if no theme is used...
if ($shop && $shop != "1" && $usethemetemplates != "true" && file_exists("$ashoppath/members/files/$ashopuser/catalogue.html")) $templatepath = "/members/files/$ashopuser";

// Read wholesale session cookie if this is a wholesale customer...
if (!empty($_COOKIE["wssessionid"])) $_COOKIE["customersessionid"] = $_COOKIE["wssessionid"];

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get customer information from database...
$sql="SELECT * FROM customer WHERE sessionid='{$_COOKIE["customersessionid"]}'";
$result = @mysqli_query($db,"$sql");
if (@mysqli_num_rows($result) == 0) {
	header("Location: signupform.php");
	exit;
}

// Store customer information in variables...
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$customerid = @mysqli_result($result, 0, "customerid");
$email = @mysqli_result($result, 0, "email");

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/customer-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/customer.html");

echo "<br><table class=\"ashopcustomerhistoryframe\">
  <tr><td align=\"center\"><p><span class=\"ashopcustomerhistoryheader\">".ORDERHISTORY." $firstname $lastname, ".CUSTOMERID." $customerid</span></p>
<p class=\"ashopcustomerhistoryheader\"><a href=\"customerprofile.php";
if (!empty($shop) && $shop > 1) echo "?shop=$shop";
echo "\">".VIEWPROFILE."</a></p>
<p><table class=\"ashopcustomerhistorybox\" border=\"0\" cellspacing=\"2\" cellpadding=\"1\" align=\"center\">
	<tr class=\"ashopcustomerhistoryrow\"><td align=\"left\" width=\"60\"><span class=\"ashopcustomerhistorytext1\">&nbsp;".REFERENCE."</span></td><td align=\"left\" width=\"70\"><span class=\"ashopcustomerhistorytext1\">&nbsp;".DATE."</span></td><td align=\"left\"><span class=\"ashopcustomerhistorytext1\">".DESCRIPTION."</span></td><td align=\"left\" width=\"80\"><span class=\"ashopcustomerhistorytext1\">".AMOUNT."</span></td><td align=\"left\" width=\"150\"><span class=\"ashopcustomerhistorytext1\">".STATUS."</span></td><td>&nbsp;</td></tr>";

// Get statistics from database...
$total = 0;
$sql="SELECT * FROM orders WHERE customerid='$customerid' AND date IS NOT NULL AND paid IS NOT NULL AND paid != '' ORDER BY date DESC";
$result = @mysqli_query($db,"$sql");
$order = @mysqli_num_rows($result);
if (@mysqli_num_rows($result) != 0) {
  for ($i = 0; $i < @mysqli_num_rows($result);$i++) {
	  $orderdate = @mysqli_result($result, $i, "date");
	  $orderdatearray = explode(" ",$orderdate);
	  $orderdate = $orderdatearray[0];
	  $realorderid = @mysqli_result($result, $i, "orderid");
	  $remoteorderid = @mysqli_result($result, $i, "remoteorderid");
	  $invoiceid = @mysqli_result($result, $i, "invoiceid");
	  if (!empty($invoiceid)) $displayorderid = $invoiceid;
	  else $displayorderid = $orderid;
	  $payoptionid = @mysqli_result($result, $i, "payoptionid");
	  $status = @mysqli_result($result, $i, "status");
	  $orderid = $realorderid;
	  $checkpayoptionresult = @mysqli_query($db,"SELECT gateway FROM payoptions WHERE payoptionid='$payoptionid'");
	  $payoptiongateway = @mysqli_result($checkpayoptionresult,0,"gateway");
	  if ($payoptiongateway == "googleco") $orderid = "GC ".$remoteorderid;
	  $reference = @mysqli_result($result, $i, "reference");
	  $price = @mysqli_result($result, $i, "price");
	  $total += $price;
	  if ($price < 0) $orderid = "<font color=\"red\">$reference</font>";
	  $description = @mysqli_result($result, $i, "description");
	  $password = @mysqli_result($result, $i, "password");
	  $orderinfo = "";
	  $links = "";
	  $linknumber = 1;
	  $orderdownloads = FALSE;
	  if (!empty($password) && $price >= 0) {
		  $products = @mysqli_result($result, $i, "products");
		  $productsincart = ashop_parseproductstring($db, $products);
		  if (is_array($productsincart)) foreach($productsincart as $productnumber => $thisproduct) {
			  if ($thisproduct["filename"]) $orderdownloads = TRUE;
			  if ($thisproduct["protectedurl"]) {
				  $links .= "
				  <a href=\"{$thisproduct["protectedurl"]}\" target=\"_blank\">".THEWORDLINK." $linknumber</a>, password = $password";
				  $linknumber++;
			  }
		  }
		  if ($orderdownloads) $orderinfo .= "
		  <form action=\"deliver.php\" name=\"downloadform{$realorderid}\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"email\" value=\"$email\"><input type=\"hidden\" name=\"password\" value=\"$password\"><a href=\"javascript:document.downloadform{$realorderid}.submit();\">".DOWNLOAD."</a></form>";
		  if ($links) $orderinfo .= $links;
	  }

	  echo "<tr>
	  <td align=\"left\"><span class=\"ashopcustomertext3\">$displayorderid</span></td><td><span class=\"ashopcustomertext3\">$orderdate</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">$description</span></td><td align=\"left\"><span class=\"ashopcustomertext6\">";
	  if ($price < 0) echo "<font color=\"red\">";
	  echo $currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"];
	  if ($price < 0) echo "</font>";
	  echo "</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">$status</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">$orderinfo</span></td></tr>";
  }
}
echo "<tr><td colspan=\"3\" style=\"background-color:$categorycolor;\" align=\"right\"><span class=\"ashopcustomerhistorytext1\">".TOTAL.":</span></td><td align=\"left\"><span class=\"ashopcustomertext3\">".$currencysymbols[$ashopcurrency]["pre"].number_format($total,$showdecimals,$decimalchar,$thousandchar)." ".$currencysymbols[$ashopcurrency]["post"]."</span></td><td style=\"background-color:$categorycolor;\" colspan=\"2\">&nbsp;</td></tr></table></p></td></tr></table>";

// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/customer-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/customer-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/customer.html");

// Close database...
@mysqli_close($db);
?>