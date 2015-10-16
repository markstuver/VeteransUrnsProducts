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

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Delete alternative...
if ($deletealt) {
	$sql = "DELETE FROM parametervalues WHERE valueid='$deletealt'";
	$result = @mysqli_query($db, $sql);
	$edit = "TRUE";
}

// Get product information...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$defaultprice = @mysqli_result($result, 0, "price");
unset($productfiles);
$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
while ($row = @mysqli_fetch_array($result)) $productfiles[$row["fileid"]] = $row["filename"];

// Check if other parameter already uses separate buy buttons...
$result = @mysqli_query($db, "SELECT * FROM parameters WHERE buybuttons='1' AND productid='$productid' AND parameterid!='$paramid'");
$alreadybuybuttons = @mysqli_num_rows($result);

// Check if separate buy buttons is activated...
if ($activatebuybuttons == "on") $buybuttons = 1;
else if ($edited == "true" || $save) unset($buybuttons);
else {
	$result = @mysqli_query($db, "SELECT buybuttons FROM parameters WHERE parameterid='$paramid'");
	$buybuttons = @mysqli_result($result, 0, "buybuttons");
}

// Create the proper input fields...
if ($add) {
	if ($alternativescount) $alternatives = $alternativescount;
	if ($alternatives == 0) {
		$add = "";
		$save = "Save";
	} else {
		$inputstring = "";
		for ($i = 1; $i <= $alternatives; $i++) {
			$inputstring .= "<table width=\"";
			if ($buybuttons && $wholesalecatalog && $pricelevels > 1) $inputstring .= "700";
			else $inputstring .= "600";
			$inputstring .= "\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\"><tr><td align=\"left\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$i:</font></td>
			<td align=\"left\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" name=\"alternative$i\" value=\"$valuetext\" size=\"40\"><input type=\"hidden\" name=\"valueid$i\" value=\"$valueid\" size=\"35\"></font></td><td align=\"right\" valign=\"top\">";
			if ($buybuttons) {
				$inputstring .= "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PRICE.": <input type=\"text\" name=\"price{$i}[0]\" value=\"default\" size=\"10\">";
				if ($wholesalecatalog) {
					$inputstring .= "<br>".WHOLESALEPRICE.": <input type=\"text\" name=\"price{$i}[1]\" value=\"default\" size=\"10\">";
					if ($pricelevels > 1) for ($pricelevel = 2; $pricelevel <= $pricelevels; $pricelevel++) $inputstring .= "<br>".WHOLESALEPRICELEVEL." $pricelevel: <input type=\"text\" name=\"price{$i}[$pricelevel]\" value=\"default\" size=\"10\">";
				}
				$inputstring .= "</font>";
			} else $inputstring .= "&nbsp;";
			$inputstring .= "</td><td align=\"right\" valign=\"top\"><input type=\"button\" value=\"".THEWORDDELETE."\" onClick=\"document.alternativesform.deletealt.value='$valueid';document.alternativesform.submit();\"></td></tr>
			<tr><td>&nbsp;</td><td align=\"left\" colspan=\"3\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
			if ($productfiles) {
				$inputstring .= DOWNLOADS.": <select name=\"download$i\"";
				if (!$separatedownloads) $inputstring .= "disabled=\"true\"";
				$inputstring .= "><option value=\"all\">".THEWORDDEFAULT."<option value=\"none\">".NOFILES;
				foreach ($productfiles as $fileid=>$filename) $inputstring .= "<option value=\"$fileid\">$filename";
				$inputstring .= "</select>&nbsp;&nbsp;&nbsp;";
			}
			$inputstring .= "<input type=\"checkbox\" name=\"noshipping$i\"";
			if (!$separateshipping) $inputstring .= " disabled=\"true\"";
			$inputstring .= "> ".NOSHIPPING."&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"notax$i\"";
			if (!$separateshipping) $inputstring .= " disabled=\"true\"";
			$inputstring .= "> ".NOSALESTAX."&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"nofulfilment$i\"";
			if (!$separateshipping) $inputstring .= " disabled=\"true\"";
			$inputstring .= "> ".NOFULFILMENT."</font></td></tr></table><table width=\"540\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\"><tr><td bgcolor=\"#FFFFFF\"><img src=\"images/invisible.gif\" height=\"4\" width=\"2\"></td></tr></table>";
		}
		$paramname = $caption;
		$numberofalternatives = $alternatives;
	}
}

if ($edit) {
	if ($edited != "true") {
		$result = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid = '$paramid' AND download != 'all' AND download != ''");
		$separatedownloads = @mysqli_num_rows($result);
		$result = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid = '$paramid' AND (notax = 1 OR noshipping = 1 OR nofulfilment = 1)");
		$separateshipping = @mysqli_num_rows($result);
	} else {
		if ($activatedownloads == "on") $separatedownloads = 1;
		else $separatedownloads = 0;
		if ($activateshipping == "on") $separateshipping = 1;
		else $separateshipping = 0;
	}
	$sql="SELECT * FROM parametervalues WHERE parameterid = '$paramid' ORDER BY valueid";
	$result = @mysqli_query($db, $sql);
	if (@mysqli_num_rows($result)) {
		for ($i = 1; $i < @mysqli_num_rows($result)+1; $i++) {
			if ($edited == "true") {
				eval("\$valuetext = \$alternative$i;");
				eval("\$valueid = \$valueid$i;");
				eval("\$download = \$download$i;");
				eval("\$noshipping = \$noshipping$i;");
				eval("\$notax = \$notax$i;");
				eval("\$nofulfilment = \$nofulfilment$i;");
				if ($noshipping == "on") $noshipping = 1;
				else $noshipping = 0;
				if ($notax == "on") $notax = 1;
				else $notax = 0;
				if ($nofulfilment == "on") $nofulfilment = 1;
				else $nofulfilment = 0;
			} else {
				$valuetext = str_replace("\"","&quot;",@mysqli_result($result, $i-1, "value"));
				$valueid = @mysqli_result($result, $i-1, "valueid");
				$download = @mysqli_result($result, $i-1, "download");
				$noshipping = @mysqli_result($result, $i-1, "noshipping");
				$notax = @mysqli_result($result, $i-1, "notax");
				$nofulfilment = @mysqli_result($result, $i-1, "nofulfilment");
			}
			if ($buybuttons) {
				$attributeprice = @mysqli_result($result, $i-1, "price");
				if (strstr($attributeprice,"|")) {
					$attributeprices = explode("|",$attributeprice);
					$attributeprice = $attributeprices[0];
					$attributewsprice = $attributeprices[1];
				}
				if (!$attributeprice) $attributeprice = "default";
			}
			$inputstring .= "<table width=\"";
			if ($buybuttons && $wholesalecatalog && $pricelevels > 1) $inputstring .= "700";
			else $inputstring .= "600";
			$inputstring .= "\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\"><tr><td align=\"left\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$i:</font></td>
			<td align=\"left\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\"><input type=\"text\" name=\"alternative$i\" value=\"$valuetext\" size=\"40\"> <a href=\"\" onclick=\"vieworderformlink('attribute{$paramid}=$valueid&item=$productid&quantity=1'); return false;\" target=\"_blank\"><img src=\"images/icon_link.gif\" border=\"0\"></a><input type=\"hidden\" name=\"valueid$i\" value=\"$valueid\" size=\"35\"></font></td><td align=\"right\" valign=\"top\">";
			if ($buybuttons) {
				$inputstring .= "<font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PRICE.": <input type=\"text\" name=\"price{$i}[0]\" value=\"";
				if ($attributeprice == "default") $inputstring .= "default";
				else $inputstring .= number_format($attributeprice,$showdecimals,$decimalchar,$thousandchar);
				$inputstring .= "\" size=\"10\">";
				if ($wholesalecatalog) {
					$inputstring .= "<br>".WHOLESALEPRICE.": <input type=\"text\" name=\"price{$i}[1]\" value=\"";
					if ($attributewsprice == "default" || empty($attributewsprice)) $inputstring .= "default";
					else $inputstring .= number_format($attributewsprice,$showdecimals,$decimalchar,$thousandchar);
					$inputstring .= "\" size=\"10\">";
					if ($pricelevels > 1) for ($pricelevel = 2; $pricelevel <= $pricelevels; $pricelevel++) {
						$inputstring .= "<br>".WHOLESALEPRICELEVEL." $pricelevel: <input type=\"text\" name=\"price{$i}[$pricelevel]\" value=\"";
						if ($attributeprices[$pricelevel] == "default") $inputstring .= "default";
						else $inputstring .= number_format($attributeprices[$pricelevel],$showdecimals,$decimalchar,$thousandchar);
						$inputstring .= "\" size=\"10\">";
					}
				}
				$inputstring .= "</font>";
			} else $inputstring .= "&nbsp;";
			$inputstring .= "</td><td align=\"right\" valign=\"top\"><input type=\"button\" value=\"".THEWORDDELETE."\" onClick=\"document.alternativesform.deletealt.value='$valueid';document.alternativesform.submit();\"></td></tr>
			<tr><td>&nbsp;</td><td align=\"left\" colspan=\"3\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
			if ($productfiles) {
				$inputstring .= DOWNLOADS.": <select name=\"download$i\"";
				if (!$separatedownloads) $inputstring .= "disabled=\"true\"";
				$inputstring .= "><option value=\"all\"";
				if ($download == "all") $inputstring .= " selected";
				$inputstring .= ">".THEWORDDEFAULT."<option value=\"none\"";
				if ($download == "none") $inputstring .= " selected";
				$inputstring .= ">".NOFILES;
				foreach ($productfiles as $fileid=>$filename) {
					$inputstring .= "<option value=\"$fileid\"";
					if ($download == $fileid) $inputstring .= " selected";
					$inputstring .= ">$filename";
				}
				$inputstring .= "</select>&nbsp;&nbsp;&nbsp;";
			}
			$inputstring .= "<input type=\"checkbox\" name=\"noshipping$i\"";
			if ($noshipping == 1) $inputstring .= " checked";
			if (!$separateshipping) $inputstring .= " disabled=\"true\"";
			$inputstring .= "> ".NOSHIPPING."&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"notax$i\"";
			if ($notax == 1) $inputstring .= " checked";
			if (!$separateshipping) $inputstring .= " disabled=\"true\"";
			$inputstring .= "> ".NOSALESTAX."&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"nofulfilment$i\"";
			if ($nofulfilment == 1) $inputstring .= "checked";
			if (!$separateshipping) $inputstring .= " disabled=\"true\"";
			$inputstring .= "> ".NOFULFILMENT."</font></td></tr></table><table width=\"540\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\"><tr><td bgcolor=\"#FFFFFF\"><img src=\"images/invisible.gif\" height=\"4\" width=\"2\"></td></tr></table>";
		}
	} else header ("Location: editparameters.php?productid=$productid&resultpage=$resultpage");
	
	$numberofalternatives = @mysqli_num_rows($result);
	$sql="SELECT caption FROM parameters WHERE parameterid=$paramid";
	$result2 = @mysqli_query($db, $sql);
	$paramname = @mysqli_result($result2, 0, "caption");
}

// Print alternatives form...
if ($add || $edit) {
  echo "$header
  <script language=\"JavaScript\">
	  <!--
	  function vieworderformlink(query) 
		{
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=600, height=100\");
			w.document.write('<html><head><title>".DIRECTLINK."</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\"><center><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".DIRECTLINKURL.":<br><textarea name=\"description\" cols=\"65\" rows=\"2\">$ashopurl/buy.php?'+query+'&redirect=basket.php</textarea><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSEWINDOW."</a></font></font><br></center></body></html>');
			return false;
	  }
	  function toggledownloads(altform) {
	    if (altform.activatedownloads.checked == false) {
			";
  for ($i = 1; $i <= $numberofalternatives; $i++) echo "altform.download$i.disabled=true;
  altform.download$i.value='all';
  ";

  echo "} else {\n";
  for ($i = 1; $i <= $numberofalternatives; $i++) echo "altform.download$i.disabled=false;
  ";
  echo "}
		}
	  function toggleshipping(altform) {
	    if (altform.activateshipping.checked == false) {
			";
  for ($i = 1; $i <= $numberofalternatives; $i++) echo "altform.noshipping$i.disabled=true;
  altform.noshipping$i.checked=false;
  altform.notax$i.disabled=true;
  altform.notax$i.checked=false;
  altform.nofulfilment$i.disabled=true;
  altform.nofulfilment$i.checked=false;
  ";

  echo "} else {\n";
  for ($i = 1; $i <= $numberofalternatives; $i++) echo "altform.noshipping$i.disabled=false;
  altform.notax$i.disabled=false;altform.nofulfilment$i.disabled=false;
  ";
  echo "}
		}
	  -->
	  </script>		
        <div class=\"heading\">".EDITATTRIBUTESFOR." <a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></div><table cellpadding=\"10\" align=\"center\"><tr><td><span class=\"subheader\">".EDITALTERNATIVES.": <b>$paramname</b>...</span><br><br><table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td width=\"75%\"><form action=\"editalternatives.php\" method=\"post\" name=\"alternativesform\"><input type=\"hidden\" name=\"paramid\" value=\"$paramid\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"caption\" value=\"$paramname\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"alternativescount\" value=\"$numberofalternatives\"><input type=\"hidden\" name=\"deletealt\" value=\"\"><input type=\"hidden\" name=\"add\" value=\"\"><input type=\"hidden\" name=\"edit\" value=\"\"><input type=\"hidden\" name=\"edited\" value=\"\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"alternatives\" value=\"$alternatives\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td colspan=\"2\">
		$inputstring
		</td></tr>
		<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"1\">".NOTEONLYONEATTRIBUTE."</font></td><td align=\"right\">&nbsp;</td></tr>";
		if (!$alreadybuybuttons) {
			echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"checkbox\" name=\"activatebuybuttons\"";
			if ($buybuttons) echo " checked";
			echo " onClick=\"document.alternativesform.save.disabled=true;document.alternativesform.edited.value='true';document.alternativesform.edit.value='$edit';document.alternativesform.add.value='$add';document.alternativesform.submit();\"> ".ACTIVATESEPARATEBUYBUTTONS."</font></td><td align=\"right\">&nbsp;</td></tr>";
		}
		if ($productfiles) {
			echo "<tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"checkbox\" name=\"activatedownloads\"";
			if ($separatedownloads) echo " checked";
			echo " onClick=\"toggledownloads(document.alternativesform);\"> ".ACTIVATESEPARATEDOWNLOAD."</font></td><td align=\"right\">&nbsp;</td></tr>";
		}
		echo "<tr><td width=\"400\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"checkbox\" name=\"activateshipping\"";
		if ($separateshipping) echo " checked";
		echo " onClick=\"toggleshipping(document.alternativesform);\"> ".ACTIVATESEPARATESHIPPING."</font></td><td align=\"right\"><input type=\"submit\" name=\"save\" value=\"".SAVE."\"> <input type=\"button\" value=\"".CANCEL."\" onClick=\"document.location.href='editparameters.php?productid=$productid&cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"></td></tr>	
		</table></form></td></tr></table></td></tr></table>$footer";
}

// Store data in database...
if ($save) {
	if (!$paramid) {
		$sql = "INSERT INTO parameters (productid, caption) VALUES ($productid, '$caption')";
		$result = @mysqli_query($db, $sql);
		$paramid = @mysqli_insert_id($db);
	}
	$changeattributeprice = FALSE;
	$alldefault = TRUE;
	$changedownload = FALSE;
	$changeshipping = FALSE;
	if ($alternativescount) for ($i = 1; $i <= $alternativescount; $i++) {
		eval ("\$thisvaluetext = \"\$alternative$i\";");
		eval ("\$thisvalueid = \"\$valueid$i\";");
		eval ("\$thisdownload = \"\$download$i\";");
		eval ("\$thisnoshipping = \"\$noshipping$i\";");
		eval ("\$thisnotax = \"\$notax$i\";");
		eval ("\$thisnofulfilment = \"\$nofulfilment$i\";");
		$thisvaluetext = str_replace("'","&#39;",$thisvaluetext);
		$thisvaluetext = str_replace("\"","&quot;",$thisvaluetext);
		if ($thisprice == "default" || !$buybuttons) $thisprice = $defaultprice;
		else $alldefault = FALSE;
		if ($_POST["price{$i}"]) {
			$changeattributeprice = TRUE;
			$newthisprice = "";
			foreach ($_POST["price{$i}"] as $pricepart) {
				if ($pricepart == "default" || !$buybuttons) $pricepart = $defaultprice;
				else $alldefault = FALSE;
				$pricepart = str_replace($thousandchar,"",$pricepart);
				$pricepart = str_replace($decimalchar,".",$pricepart);
				$pricepart = number_format($pricepart,2,".","");
				$newthisprice .= $pricepart."|";
			}
			$thisprice = substr($newthisprice,0,-1);
		}
		if ($thisnoshipping == "on") $thisnoshipping = 1;
		else $thisnoshipping = 0;
		if ($thisnotax == "on") $thisnotax = 1;
		else $thisnotax = 0;
		if ($thisnofulfilment == "on") $thisnofulfilment = 1;
		else $thisnofulfilment = 0;
		if (!$thisdownload) $thisdownload = "all";
		if ($thisdownload != "all") $changedownload = TRUE;
		if ($thisnoshipping || $thisnotax || $thisnofulfilment) $changeshipping = TRUE;
		if ($thisvalueid) $sql = "UPDATE parametervalues SET value='$thisvaluetext', download='$thisdownload', noshipping='$thisnoshipping', notax='$thisnotax', nofulfilment='$thisnofulfilment', price='$thisprice' WHERE valueid=$thisvalueid";
		else $sql = "INSERT INTO parametervalues (parameterid, value, download, noshipping, notax, nofulfilment, price) VALUES ($paramid, '$thisvaluetext', '$thisdownload', '$thisnoshipping', '$thisnotax', '$thisnofulfilment', '$thisprice')";
		$result = @mysqli_query($db, $sql);
	}
	if ($changedownload) {
		$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' AND parameterid!='$paramid'");
		while ($row = @mysqli_fetch_array($result)) @mysqli_query($db, "UPDATE parametervalues SET download='all' WHERE parameterid='{$row["parameterid"]}'");
	}
	if ($changeshipping) {
		$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' AND parameterid!='$paramid'");
		while ($row = @mysqli_fetch_array($result)) @mysqli_query($db, "UPDATE parametervalues SET noshipping='0', notax='0' WHERE parameterid='{$row["parameterid"]}'");
	}
	if ($alldefault) @mysqli_query($db, "UPDATE parametervalues SET price=NULL WHERE parameterid='$paramid'");
	if ($changeattributeprice) @mysqli_query($db, "UPDATE parametervalues SET price=NULL WHERE productid='$productid' AND parameterid != '$paramid'");
	if ($buybuttons) {
		@mysqli_query($db, "UPDATE parameters SET buybuttons = NULL WHERE productid='$productid'");
		@mysqli_query($db, "UPDATE parameters SET buybuttons = 1 WHERE parameterid='$paramid'");
	} else @mysqli_query($db, "UPDATE parameters SET buybuttons = NULL WHERE parameterid='$paramid'");
    header ("Location: editparameters.php?cat=$cat&productid=$productid&resultpage=$resultpage");
}
?>