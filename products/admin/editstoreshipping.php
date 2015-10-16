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
include "ashopconstants.inc.php";
// Get context help for this page...
		$contexthelppage = "editshipping";
		include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store edited data...
if ($shipoption) {
   if ($shipoption == "none") $sql = "UPDATE preferences SET prefvalue='none' WHERE prefname='storeshippingmethod'";
   else if ($shipoption == "ups") $sql = "UPDATE preferences SET prefvalue='ups' WHERE prefname='storeshippingmethod'";
   else if ($shipoption == "fedex") $sql = "UPDATE preferences SET prefvalue='fedex' WHERE prefname='storeshippingmethod'";
   else if ($shipoption == "perpound") $sql = "UPDATE preferences SET prefvalue='perpound' WHERE prefname='storeshippingmethod'";
   $storeshippingmethod = $shipoption;
   @mysqli_query($db, $sql);
   if (isset($nstoreshippingmaxpounds) || isset($nstoreshippingmaxounces)) {
	   $totalounces = ($nstoreshippingmaxpounds*16)+$nstoreshippingmaxounces;
	   $nstoreshippingmaxweight = $totalounces/16;
	   @mysqli_query($db, "UPDATE preferences SET prefvalue='".number_format($nstoreshippingmaxweight,2,'.','')."' WHERE prefname='storeshippingmaxweight'");
   }
   if (isset($nstoreshippingfromzip)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingfromzip' WHERE prefname='storeshippingfromzip'");
   if (isset($nstoreshippingfromstate)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingfromstate' WHERE prefname='storeshippingfromstate'");
   if (isset($nstoreshippingbasecharge)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingbasecharge' WHERE prefname='storeshippingbasecharge'");
   if (isset($nstoreshippingperpound)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingperpound' WHERE prefname='storeshippingperpound'");
}

// Print shipping option form...
if (!$edited || $addpackage) {
  $totalounces = $storeshippingmaxweight*16;
  $storeshippingmaxpounds = floor($storeshippingmaxweight);
  $storeshippingmaxounces = ($totalounces-($storeshippingmaxpounds*16));
  $storeshippingmaxounces = number_format($storeshippingmaxounces,1,'.','');
  echo "$header
        <table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td colspan=\"6\" class=\"heading1\">Store Configuration</td></tr>
  <tr align=\"center\">  
    <td class=\"nav\" width=\"17%\" nowrap><a href=\"configure.php?param=shop\" class=\"nav\">Shop Parameters</a></td>
    <td class=\"nav\" width=\"12%\" nowrap><a href=\"configure.php?param=layout\" class=\"nav\">Layout</a></td>
    <td class=\"nav\" width=\"17%\" nowrap><a href=\"configure.php?param=affiliate\" class=\"nav\">Affiliate Program</a></td>
    <td class=\"nav\" width=\"14%\" nowrap><a href=\"payoptions.php\" class=\"nav\">Payment</a></td>
    <td class=\"nav\" width=\"14%\" nowrap><a href=\"fulfiloptions.php\" class=\"nav\">Fulfilment</a></td>
    <td class=\"nav\" width=\"14%\" nowrap><a href=\"configure.php?param=shipping\" class=\"nav\">Shipping</a></td>
	<td class=\"nav\" width=\"12%\" nowrap><a href=\"configure.php?param=taxes\" class=\"nav\">Taxes</a></td>
<tr>
</table>
<table align=\"center\" cellpadding=\"10\"><tr><td><center><p class=\"heading\">Storewide Shipping</p>
        <form action=\"editstoreshipping.php\" method=\"post\" name=\"shipoptionform\">
        <table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
        <tr><td align=\"center\" class=\"formlabel\">Shipping Calculation Method: <input type=\"hidden\" name=\"productid\" value=\"$productid\"><select name=\"shipoption\" onChange=\"document.shipoptionform.submit()\">
		<option value=\"none\"";
		if ($storeshippingmethod == "none") echo " selected";
		echo ">None</option>
		<option value=\"ups\"";
		if ($storeshippingmethod == "ups") echo " selected";
		echo ">UPS</option>
		<option value=\"fedex\"";
		if ($storeshippingmethod == "fedex") echo " selected";
		echo ">FedEx</option>
		<option value=\"perpound\"";		
		if ($storeshippingmethod == "perpound") echo " selected";
		echo ">Per Pound</option>
		</select></td></tr></table>";
		if ($storeshippingmethod == "ups" || $storeshippingmethod == "fedex") {
			echo "<table width=\"100%\" bgcolor=\"#F0F0F0\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" class=\"formlabel\">Origin State: </td><td align=\"left\"><SELECT NAME=\"nstoreshippingfromstate\"><option value=none>choose state";
			foreach ($americanstates as $longstate => $shortstate) {
				echo "<option  value=$shortstate";
				if ($shortstate == $storeshippingfromstate) echo " selected";
				echo ">$longstate\n";
			}
			echo "</SELECT></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">Origin Zip: </td><td><input type=\"text\" name=\"nstoreshippingfromzip\" size=\"10\" value=\"$storeshippingfromzip\"></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">Max Weight Per Package: </td><td align=\"left\" class=\"formlabel\"><input type=\"text\" name=\"nstoreshippingmaxpounds\" value=\"$storeshippingmaxpounds\" size=\"4\"> lb <input type=\"text\" name=\"nstoreshippingmaxounces\" value=\"$storeshippingmaxounces\" size=\"4\"> oz</td></tr></table>
			<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#F0F0F0\"><tr><td>&nbsp;</td><td align=\"right\"><input type=\"submit\" value=\"Submit\" name=\"edited\">";
		} else if ($storeshippingmethod == "perpound") {
			echo "<table width=\"100%\" bgcolor=\"#F0F0F0\" cellpadding=\"3\" cellspacing=\"0\" border=\"0\"><tr><td align=\"right\" class=\"formlabel\">Base charge: </td><td><input type=\"text\" name=\"nstoreshippingbasecharge\" size=\"6\" value=\"$storeshippingbasecharge\"></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">Rate Per Pound: </td><td align=\"left\" class=\"formlabel\"><input type=\"text\" name=\"nstoreshippingperpound\" value=\"$storeshippingperpound\" size=\"6\"></td></tr></table>
			<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#F0F0F0\"><tr><td>&nbsp;</td><td align=\"right\"><input type=\"submit\" value=\"Submit\" name=\"edited\">";			
		} else if($storeshippingmethod == "none") echo "<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"#F0F0F0\"><tr><td align=\"right\"><input type=\"submit\" value=\"Submit\" name=\"edited\">";
		echo "</td></tr></table></form></td></tr></table>$footer";

// Store data in database...
} else header ("Location: configure.php?param=shipping");
?>