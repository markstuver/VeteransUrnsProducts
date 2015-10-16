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

if (!$saasuwsaccesskey || !$saasufileid) header("Location: index.php");

if ($checkprogress == "true") {
	// Open a database connection...
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

	// Get current number of items to export...
	$result = @mysqli_query($db, "SELECT DISTINCT skucode FROM product WHERE skucode != '' AND skucode IS NOT NULL AND (exportedtosaasu = '0' OR exportedtosaasu = '' OR exportedtosaasu IS NULL) AND (copyof IS NULL OR copyof ='')");
	$numberofitemsleft = @mysqli_num_rows($result);
	$result = @mysqli_query($db, "SELECT productinventory.skucode FROM product, productinventory WHERE productinventory.skucode != '' AND productinventory.skucode IS NOT NULL AND (productinventory.exportedtosaasu = '0' OR productinventory.exportedtosaasu = '' OR productinventory.exportedtosaasu IS NULL) AND product.productid=productinventory.productid AND (product.copyof IS NULL OR product.copyof = '')");
	$numberofitemsleft += @mysqli_num_rows($result);

	// Get original total number of items to export...
	$result = @mysqli_query($db, "SELECT numberofitems FROM export");
	$totalnumberofitems = @mysqli_result($result, 0, "numberofitems");

	// Get number of items already exported...
	$numberofitems = $totalnumberofitems-$numberofitemsleft;

	if ($totalnumberofitems && $numberofitems == $totalnumberofitems) {
		@mysqli_query($db, "DELETE FROM export");
		echo "-1|-1";
	} else echo "$numberofitems|$totalnumberofitems";
	exit;
}

if (!$startexport || !$saasuassetaccount || !$saasupurchasetaxcode || !$saasuincomeaccount || !$saasusalestaxcode || !$saasucosaccount) {
	echo "$header<table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td><font face=\"Arial, Helvetica, sans-serif\" color=\"ffffff\" size=\"4\"><b>Administration Panel</b></td></tr></table><table align=\"center\" cellpadding=\"10\">
	<tr><td><center><p class=\"heading\">Export Product List to SAASU</p></center>
	<form action=\"saasuexport.php\" method=\"post\" name=\"saasuexportform\">
	<table width=\"500\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">";
	$saasuassetaccounts = ashop_saasu_getaccounts("Asset");
	$saasuincomeaccounts = ashop_saasu_getaccounts("Income");
	$saasutaxcodes = ashop_saasu_gettaxcodes();
	$saasucosaccounts = ashop_saasu_getaccounts("Cost of Sales");
	if ($saasuassetaccounts) echo "
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\" width=\"250\">Inventory all items to this Asset Account:</td><td><select name=\"saasuassetaccount\">$saasuassetaccounts</select></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">Purchase Tax Code:</td><td><select name=\"saasupurchasetaxcode\">$saasutaxcodes</select></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">Income Account:</td><td><select name=\"saasuincomeaccount\">$saasuincomeaccounts</select></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">Sales Tax Code:</td><td><select name=\"saasusalestaxcode\">$saasutaxcodes</select></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">Cost of Sales Account:</td><td><select name=\"saasucosaccount\">$saasucosaccounts</select></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">&nbsp;</td><td><input type=\"submit\" value=\"Start Export\" name=\"startexport\"></td></tr>
	</table></form></td></tr></table></body></html>";
} else {
	// Open a database connection...
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

	// Get number of items to export...
	$result = @mysqli_query($db, "SELECT DISTINCT skucode FROM product WHERE skucode != '' AND skucode IS NOT NULL AND (exportedtosaasu = '0' OR exportedtosaasu = '' OR exportedtosaasu IS NULL) AND (copyof IS NULL OR copyof ='')");
	$numberofitems = @mysqli_num_rows($result);
	$result = @mysqli_query($db, "SELECT productinventory.skucode FROM product, productinventory WHERE productinventory.skucode != '' AND productinventory.skucode IS NOT NULL AND (productinventory.exportedtosaasu = '0' OR productinventory.exportedtosaasu = '' OR productinventory.exportedtosaasu IS NULL) AND product.productid=productinventory.productid AND (product.copyof IS NULL OR product.copyof = '')");
	$numberofitems += @mysqli_num_rows($result);

	// Store export settings...
	$result = @mysqli_query($db, "SELECT * FROM export");
	if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO export (saasuassetaccount, saasuincomeaccount, saasucosaccount, saasupurchasetaxcode, saasusalestaxcode, numberofitems) VALUES ('$saasuassetaccount', '$saasuincomeaccount', '$saasucosaccount', '$saasupurchasetaxcode', '$saasusalestaxcode', '$numberofitems')");
	
	echo "$header<table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td><font face=\"Arial, Helvetica, sans-serif\" color=\"ffffff\" size=\"4\"><b>Administration Panel</b></td></tr></table><table align=\"center\" cellpadding=\"10\">
	<tr><td><center><p class=\"heading\">Export Product List to SAASU</p><br>
	
<script language=\"JavaScript\" src=\"../includes/prototype.js\" type=\"text/javascript\"></script>
<script language=\"JavaScript\" type=\"text/javascript\">
function reportprogress(ajaxRequest) {
	parameters = ajaxRequest.responseText;
	parametersarray = parameters.split('|');
	exported = parseInt(parametersarray[0]);
	total = parseInt(parametersarray[1]);
	exporteditms = exported;
	totalitms = total;
	if (exported == -1) {
		$('exportprogress').update('Export completed!');
		clearInterval ( unstallIntervalID );
		clearInterval ( progressIntervalID );
	}
	$('exporteditems').update(exported);
	$('totalitems').update(total);
}

function checkprogress() {
	var myAjax = new Ajax.Request(
		'saasuexport.php', 
		{
			method: 'get',
			parameters: 'checkprogress=true&dummy='+ new Date().getTime(), 
			onSuccess: reportprogress
		}
	);
}

function startexport() {
	var myAjax = new Ajax.Request(
		'exportsaasu.php', 
		{
			method: 'get',
			parameters: 'dummy='+ new Date().getTime()
		}
	);
}
var progressIntervalID = 0;
progressIntervalID = window.setInterval(\"checkprogress()\",3000);
</script>

	<div id=\"exportprogress\" class=\"confirm\">Exported: <span id=\"exporteditems\">0</span> of: <span id=\"totalitems\">$numberofitems</span> items.</div>
<script language=\"JavaScript\" type=\"text/javascript\">
var totalitms = 0;
var checkitms = 0;
var unstallIntervalID = 0;
checkprogress();
startexport();
function unstall() {
	if (totalitms == checkitms && totalitms != -1) startexport();
	else checkitms = totalitms;
}
unstallIntervalID = window.setInterval(\"unstall()\",15000);
</script>
	
	</center></td></tr></table></body></html>";
}
?>