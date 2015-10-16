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
$contexthelppage = "editfulfilment";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Store edited data...
if ($fulfiloption) {
	$nffparamnames = "";
	if($nffattribute0) {
		$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid");
		$paramnumber = 0;
		while ($row = @mysqli_fetch_array($result)) {
			eval("\$nffparamnames .= \$nffattribute$paramnumber.\"|\";");
			$paramnumber++;
		}
	}
	$nffproductid = str_replace(" ","",$nffproductid);
	$nffproductid = str_replace(",","|",$nffproductid);
	$nffproductid = str_replace(";","|",$nffproductid);
	@mysqli_query($db, "UPDATE product SET fulfilment='$fulfiloption', ffproductid='$nffproductid', fflabelnumber='$nfflabelnumber', ffpackagenumber='$nffpackagenumber', ffparamnames='$nffparamnames' WHERE productid='$productid'");
} else if ($edit) @mysqli_query($db, "UPDATE product SET fulfilment='', ffproductid='', fflabelnumber='', ffpackagenumber='' WHERE productid='$productid'");

// Get product information...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productfulfilment = @mysqli_result($result, 0, "fulfilment");
$productffproductid = @mysqli_result($result, 0, "ffproductid");
$productfflabelnumber = @mysqli_result($result, 0, "fflabelnumber");
$productffpackagenumber = @mysqli_result($result, 0, "ffpackagenumber");
$productffparamnames = @mysqli_result($result, 0, "ffparamnames");
$paramnamesarray = explode("|",$productffparamnames);
if (!$productfulfilment && !$fulfilmentoption) {
	$fulfiloption = 0;
} else if (!$fulfilmentoption) $fulfiloption = $productfulfilment;
$fulfiloptionsstring = "";
$result = @mysqli_query($db, "SELECT * FROM fulfiloptions WHERE perorder!='1'");
while ($row = @mysqli_fetch_array($result)) {
	$fulfiloptionsstring .= "<option value=\"".$row["fulfiloptionid"]."\"";
	if ($fulfiloption == $row["fulfiloptionid"]) {
		$fulfiloptionsstring .= " selected";
		$showfulfiloptions = $row["method"];
	}
	$fulfiloptionsstring .= ">".$row["name"]."</option>";
}
if ($showfulfiloptions) include "fulfilment/$showfulfiloptions.ff";

// Print fulfilment option form...
if (!$edited) {
  echo "$header
        <div class=\"heading\">".FULFILMENTOPTIONS." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>
        <form action=\"editfulfilment.php\" method=\"post\" name=\"fulfiloptionform\">
        <table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"fofofo\">
        <tr><td align=\"right\" class=\"formlabel\">".SELECTOPTION.":</td><td width=\"50%\" class=\"formlabel\" align=\"left\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><select name=\"fulfiloption\" onChange=\"document.fulfiloptionform.submit()\">
		<option value=\"0\"";
		if ($fulfiloption == 0) echo " selected";
		echo ">".NONE."</option>
		$fulfiloptionsstring
		</select></td></tr>";
        if ($ff_prodparameters['productid'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".PRODUCTID.":</td><td width=\"50%\" class=\"formlabel\" align=\"left\"> <input name=\"nffproductid\" type=\"text\" size=\"25\" value=\"$productffproductid\"></font></td></tr>";
        if ($ff_prodparameters['productidlist'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".PRODUCTIDS.":</td><td width=\"50%\" class=\"formlabel\" align=\"left\"> <textarea name=\"nffproductid\"  cols=\"30\" rows=\"5\">$productffproductid</textarea></font></td></tr>";
		if ($ff_prodparameters['labelnumber'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".LABELNUMBER.":</td><td width=\"50%\" class=\"formlabel\" align=\"left\"> <input name=\"nfflabelnumber\" type=\"text\" size=\"10\" value=\"$productfflabelnumber\"></font></td></tr>";
		if ($ff_prodparameters['packagenumber'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".PACKAGENUMBER.":</td><td width=\"50%\" class=\"formlabel\" align=\"left\"> <input name=\"nffpackagenumber\" type=\"text\" size=\"10\" value=\"$productffpackagenumber\"></font></td></tr>";
		if ($ff_prodparameters['parameternames'] == "true") {
			$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid");
			$paramnumber = 0;
			while ($row = @mysqli_fetch_array($result)) {
				echo "<tr><td align=\"right\" class=\"formlabel\">\"{$row["caption"]}\" ".FIELDNAME.":</td><td width=\"50%\" class=\"formlabel\" align=\"left\"> <input name=\"nffattribute{$paramnumber}\" type=\"text\" size=\"10\" value=\"{$paramnamesarray["$paramnumber"]}\"></font></td></tr>";
				$paramnumber++;
			}
		}
		echo "</table><table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td align=\"right\"><input type=\"submit\" value=\"".SUBMIT."\" name=\"edited\"> <input type=\"button\" value=\"".CANCEL."\" onClick=\"document.location.href='editcatalogue.php?cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"><input type=\"hidden\" name=\"edit\" value=\"1\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></td></tr></table></form></td></tr></table>$footer";

// Store data in database...
} else header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
?>