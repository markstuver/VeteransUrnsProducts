<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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
include "language/$adminlang/editproduct.inc.php";
// Get context help for this page...
$contexthelppage = "editproduct";
include "help.inc.php";

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid = '$productid'";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productcopyof = @mysqli_result($result, 0, "copyof");
$productfeatured = @mysqli_result($result, 0, "featured");
$productowner = @mysqli_result($result, 0, "userid");
$productname = str_replace("\"", "&quot;", $productname);
$productreceipttext = @mysqli_result($result, 0, "receipttext");
$productebayid = @mysqli_result($result, 0, "ebayid");
$productprice = @mysqli_result($result, 0, "price");
$productpricelevels = @mysqli_result($result, 0, "wspricelevels");
$productpricelevels = explode("|",$productpricelevels);
$productdescr = @mysqli_result($result, 0, "description");
$productlicense = @mysqli_result($result, 0, "licensetext");
$productaffcom = explode("a",@mysqli_result($result, 0, "affiliatecom"));
$productaffwscom = explode("a",@mysqli_result($result, 0, "affiliatewscom"));
$productaffcom2 = explode("a",@mysqli_result($result, 0, "affiliatecom2"));
$producttieraffcoms = explode("|",@mysqli_result($result, 0, "affiliatetiercom"));
if (!empty($producttieraffcoms) && is_array($producttieraffcoms)) {
	$producttier2affcom = explode("a",$producttieraffcoms[0]);
	$producttier2affcom2 = explode("a",$producttieraffcoms[1]);
	$producttierlowerby = $producttieraffcoms[2];
}
if (empty($producttierlowerby)) $producttierlowerby = 0;
$productaffrepeat = @mysqli_result($result, 0, "affiliaterepeatcommission");
$productlmgroup = @mysqli_result($result, 0, "listmessengergroup");
$productlmpgroup = @mysqli_result($result, 0, "listmaillist");
$productphpbbgroup = @mysqli_result($result, 0, "phpbbgroup");
$productarpresponder = @mysqli_result($result, 0, "arpresponder");
$productarpreachresponder = @mysqli_result($result, 0, "arpreachresponder");
$productresponder = @mysqli_result($result, 0, "autoresponder");
$productresponderoff = @mysqli_result($result, 0, "autoresponderoff");
$productinfresponder = @mysqli_result($result, 0, "infresponder");
$productinfresponderoff = @mysqli_result($result, 0, "infresponderoff");
$productiemlist = @mysqli_result($result, 0, "iemlist");
$productmcgroup = @mysqli_result($result, 0, "mailchimplist");
$productstatus = @mysqli_result($result, 0, "active");
if ($productstatus == 1) $productstatus = " checked";
else $productstatus = "";
$productwsstatus = @mysqli_result($result, 0, "wholesaleactive");
if ($productwsstatus == 1) $productwsstatus = " checked";
else $productwsstatus = "";
$productwsprice = @mysqli_result($result, 0, "wholesaleprice");
$productbilltemplate = @mysqli_result($result, 0, "billtemplate");
$taxable = @mysqli_result($result, 0, "taxable");
// Get eMerchant vendor settings for this product...
$productvendor = @mysqli_result($result, 0, "vendorid");
$productcost = @mysqli_result($result, 0, "cost");
$productrecurringprice = @mysqli_result($result, 0, "recurringprice");
$productrecurringperiod = @mysqli_result($result, 0, "recurringperiod");
if (!empty($productrecurringperiod) && strstr($productrecurringperiod,"|")) {
	$recurringperiodarray = explode("|",$productrecurringperiod);
	$recurringperiod = $recurringperiodarray[0];
	$recurringperiodunits = $recurringperiodarray[1];
} else {
	$recurringperiod = "";
	$recurringperiodunits = "";
}
$productavail = @mysqli_result($result, 0, "avail");
$productinmainshop = @mysqli_result($result, 0, "inmainshop");
if ($productinmainshop) $inmainshop = " checked";
else $inmainshop = "";

// Generate Digital Mall member list if needed...
if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
	$memberlist = "<select name=\"memberid\"><option value=\"1\"";
	if ($productowner == "1") $memberlist .= " selected";
	$memberlist .= ">".ADMINISTRATOR;
	$result = @mysqli_query($db, "SELECT * FROM user WHERE userid>1 ORDER BY shopname");
	while ($row = @mysqli_fetch_array($result)) {
		$memberlist .= "<option value=\"{$row["userid"]}\"";
		if ($productowner == $row["userid"]) $memberlist .= " selected";
		$memberlist .= ">{$row["shopname"]}";
	}
	$memberlist .= "</select>";
}

// Check for floating price...
$result = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$productid'");
if (@mysqli_num_rows($result)) {
	$row = @mysqli_fetch_array($result);
	$fp_length = $row["length"];
	$fp_lengthseconds = $fp_length;
	$fp_lengthdays = floor($fp_lengthseconds/86400);
	$fp_lengthseconds -= $fp_lengthdays*86400;
	$fp_lengthhours = floor($fp_lengthseconds/3600);
	$fp_lengthseconds -= $fp_lengthhours*3600;
	$fp_lengthminutes = floor($fp_lengthseconds/60);
	$fp_lengthseconds -= $fp_lengthminutes*60;
	$fp_activate = $row["activatetime"];
	$productprice = $row["originalstartprice"];
	$fp_priceincr = $row["priceincrement"];
	$fp_type = $row["type"];
}

// Generate floating price activation time settings...
if (@mysqli_num_rows($result) || $addfloatingprice) {
	if (!$fp_activate) $fp_activate = time();
	$fp_activatemonth = date("m",$fp_activate);
	$fp_activateday = date("d",$fp_activate);
	$fp_activatehour = date("H",$fp_activate);
	$fp_activateminute = date("i",$fp_activate);
	$fp_activatestring = " <select name=\"activatemonth\"><option value=\"01\"";
	if ($fp_activatemonth == "01") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".JAN."</option><option value=\"02\"";
	if ($fp_activatemonth == "02") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".FEB."</option><option value=\"03\"";
	if ($fp_activatemonth == "03") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".MAR."</option><option value=\"04\"";
	if ($fp_activatemonth == "04") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".APR."</option><option value=\"05\"";
	if ($fp_activatemonth == "05") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".MAY."</option><option value=\"06\"";
	if ($fp_activatemonth == "06") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".JUN."</option><option value=\"07\"";
	if ($fp_activatemonth == "07") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".JUL."</option><option value=\"08\"";
	if ($fp_activatemonth == "08") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".AUG."</option><option value=\"09\"";
	if ($fp_activatemonth == "09") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".SEP."</option><option value=\"10\"";
	if ($fp_activatemonth == "10") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".OCT."</option><option value=\"11\"";
	if ($fp_activatemonth == "11") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".NOV."</option><option value=\"12\"";
	if ($fp_activatemonth == "12") $fp_activatestring .= " selected";
	$fp_activatestring .= ">".DEC."</option></select>";
	$fp_activatestring .= " <input type=\"text\" size=\"2\" name=\"activateday\" value=\"$fp_activateday\">";
	$fp_activatestring .= " ".HOUR.": <input type=\"text\" size=\"2\" name=\"activatehour\" value=\"$fp_activatehour\">";
	$fp_activatestring .= " ".MINUTE.": <input type=\"text\" size=\"2\" name=\"activateminute\" value=\"$fp_activateminute\">";
}

// Detach copy from its original...
if ($detach && $productid) {

	// Make a complete copy of the product...
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) $row = @mysqli_fetch_array($result, MYSQL_ASSOC);
	if (is_array($row)) {
		$sql = "UPDATE product SET ";
		foreach ($row as $fieldname=>$fieldvalue) {
			if (!get_magic_quotes_runtime()) $fieldvalue = addslashes($fieldvalue);
			if ($fieldname != "productid" && $fieldname != "ordernumber") $sql .= "$fieldname = '$fieldvalue', ";
		}
		$sql = substr($sql,0,-2);
		$sql .= " WHERE productid='$productid'";
		@mysqli_query($db, $sql);

		// Assign a new details URL to this product...
		$olddetailsurl = $row["detailsurl"];
		if (!empty($olddetailsurl)) {
			if (strstr($olddetailsurl,"product=")) $newdetailsurl = str_replace("$productcopyof","$productid",$olddetailsurl);
			else {
				$olddetailsurl = str_replace(".html","",$olddetailsurl);
				$pagenumber = 1;
				$newdetailsurl = $olddetailsurl."_{$pagenumber}.html";
				$checkdetailsurls = @mysqli_query($db, "SELECT productid FROM product WHERE detailsurl='$newdetailsurl'");
				if (@mysqli_num_rows($checkdetailsurls)) {
					$newdetailsurlused = TRUE;
					while ($newdetailsurlused == TRUE) {
						$pagenumber++;
						$newdetailsurl = $olddetailsurl."_$pagenumber";
						$checkdetailsurls = @mysqli_query($db, "SELECT productid FROM product WHERE detailsurl='$newdetailsurl'");
						if (!@mysqli_num_rows($checkdetailsurls)) $newdetailsurlused = FALSE;
					}
				}
			}
			@mysqli_query($db, "UPDATE product SET detailsurl='$newdetailsurl' WHERE productid='$productid'");
		}
	}

	// Copy quantity price settings...
	@mysqli_query($db, "DELETE FROM qtypricelevels WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		$row = @mysqli_fetch_array($result);
		@mysqli_query($db, "INSERT INTO qtypricelevels (levelprice, levelquantity, productid) VALUES ('{$row["levelprice"]}', '{$row["levelquantity"]}', '$productid')");
	}

	// Copy product inventory...
	@mysqli_query($db, "DELETE FROM productinventory WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		$row = @mysqli_fetch_array($result);
		@mysqli_query($db, "INSERT INTO productinventory (type, skucode, inventory, productid) VALUES ('{$row["type"]}', '{$row["skucode"]}', '{$row["inventory"]}', '$productid')");
	}

	// Copy product file records...
	@mysqli_query($db, "DELETE FROM productfiles WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO productfiles (fileid, filename, url, productid) VALUES ('{$row["fileid"]}', '{$row["filename"]}', '{$row["url"]}', '$productid')");
			$newfileid = @mysqli_insert_id($db);
			@mysqli_query($db, "UPDATE productfiles SET ordernumber='$newfileid' WHERE id='$newfileid'");
		}
	}

	// Copy product discount...
	@mysqli_query($db, "DELETE FROM discount WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO discount (code, value, type, onetime, productid, affiliate, customerid) VALUES ('{$row["code"]}', '{$row["value"]}', '{$row["type"]}', '{$row["onetime"]}', '$productid', '{$row["affiliate"]}', '{$row["customerid"]}')");
		}
	}

	// Copy product shipping packages...
	@mysqli_query($db, "DELETE FROM packages WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM packages WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO packages (originzip, origincountry, originstate, weight, freightclass, productid) VALUES ('{$row["originzip"]}', '{$row["origincountry"]}', '{$row["originstate"]}', '{$row["weight"]}', '{$row["freightclass"]}', '$productid')");
		}
	}

	// Copy product shipping zone rates...
	@mysqli_query($db, "DELETE FROM zonerates WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM zonerates WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO zonerates (zone, rate, productid) VALUES ('{$row["zone"]}', '{$row["rate"]}', '$productid')");
		}
	}

	// Copy product quantity price rates...
	@mysqli_query($db, "DELETE FROM quantityrates WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM quantityrates WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO quantityrates (quantity, rate, productid) VALUES ('{$row["quantity"]}', '{$row["rate"]}', '$productid')");
		}
	}

	// Copy product attributes and options...
	$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			$parameterid = $row["parameterid"];
			@mysqli_query($db, "DELETE FROM parametervalues WHERE parameterid='$parameterid'");
			@mysqli_query($db, "DELETE FROM customparametervalues WHERE parameterid='$parameterid'");
		}
	}
	@mysqli_query($db, "DELETE FROM parameters WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO parameters (caption, buybuttons, productid) VALUES ('{$row["caption"]}', '{$row["buybuttons"]}', '$productid')");
			$newparameterid = @mysqli_insert_id($db);
			$result2 = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='{$row["parameterid"]}' ORDER BY valueid ASC");
			while ($row2 = @mysqli_fetch_array($result2)) {
				if ($row2["price"] == NULL) $price = "NULL";
				else $price = "'{$row2["price"]}'";
				@mysqli_query($db, "INSERT INTO parametervalues (parameterid, download, noshipping, notax, nofulfilment, price, value) VALUES ('$newparameterid', '{$row2["download"]}', '{$row2["noshipping"]}', '{$row2["notax"]}', '{$row2["nofulfilment"]}', $price, '{$row2["value"]}')");
				$newvalueid = @mysqli_insert_id($db);
				$oldvalueid = $row2["valueid"];
				$parametervalues["$oldvalueid"] = $newvalueid;
			}
		}
	}

	// Copy product floating price...
	@mysqli_query($db, "DELETE FROM floatingprice WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		$row = @mysqli_fetch_array($result);
		@mysqli_query($db, "INSERT INTO floatingprice (length, activatetime, starttime, startprice, originalstartprice, priceincrement, productid, type) VALUES ('{$row["length"]}', '{$row["activatetime"]}', '{$row["starttime"]}', '{$row["startprice"]}', '{$row["startprice"]}', '{$row["priceincrement"]}', '$productid', '{$row["type"]}')");
	}

	// Copy related products...
	@mysqli_query($db, "DELETE FROM relatedproducts WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM relatedproducts WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO relatedproducts (relatedproductid, priority, productid) VALUES ('{$row["relatedproductid"]}', '{$row["priority"]}', '$productid')");
		}
	}

	// Copy flags...
	@mysqli_query($db, "DELETE FROM flagvalues WHERE productid='$productid'");
	$result = @mysqli_query($db, "SELECT * FROM flagvalues WHERE productid='$productcopyof'");
	if (@mysqli_num_rows($result)) {
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "INSERT INTO flagvalues (flagid, productid) VALUES ('{$row["flagid"]}', '$productid')");
		}
	}

	// Copy image files...
	ashop_copydir("$ashoppath/prodimg/$productcopyof","$ashoppath/prodimg/$productid");

	@mysqli_query($db, "UPDATE product SET copyof=NULL WHERE productid='$productid'");
	@mysqli_query($db, "DELETE FROM productinventory WHERE productid='$productid'");
	$edited = "";
	$productcopyof = 0;
}

// Handle removal of the product...
if ($remove && $productid) {
	if ($yes) {
       $sql="DELETE FROM product WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM productcategory WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM discount WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM unlockkeys WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM packages WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM zonerates WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM quantityrates WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM flagvalues WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM qtypricelevels WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
       $sql="DELETE FROM productinventory WHERE productid=$productid";
       $result = @mysqli_query($db, $sql);
	   $result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid'");
	   while ($row = @mysqli_fetch_array($result)) {
		   $parameterid = $row["parameterid"];
		   @mysqli_query($db, "DELETE FROM parametervalues WHERE parameterid='$parameterid'");
		   @mysqli_query($db, "DELETE FROM customparametervalues WHERE parameterid='$parameterid'");
	   }
	   @mysqli_query($db, "DELETE FROM parameters WHERE productid='$productid'");
	   ashop_deleteimages("$ashoppath/prodimg/$productid");
	   if (is_dir("$ashoppath/previews/$productid")) {
		   $findfile = opendir("$ashoppath/previews/$productid");
		   while (false !== ($foundfile = readdir($findfile))) { 
			   if($foundfile && $foundfile != "." && $foundfile != "..") unlink("$ashoppath/previews/$productid/$foundfile");
			   unset($foundfile);
		   }
		   closedir($findfile);
		   rmdir("$ashoppath/previews/$productid");
		   unset($findfile);
	   }
	   $result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
	   while ($row = @mysqli_fetch_array($result)) {
		   $result2 = @mysqli_query($db, "SELECT * FROM productfiles WHERE fileid='{$row["fileid"]}' AND productid!='$productid'");
		   if (!@mysqli_num_rows($result2)) {
			   if (file_exists("$ashopspath/products/{$row["fileid"]}")) unlink("$ashopspath/products/{$row["fileid"]}");
		   }
		   @mysqli_query($db, "DELETE FROM productfiles WHERE productid='$productid'");
	   }
	   $result = @mysqli_query($db, "SELECT * FROM updates WHERE productid='$productid'");
	   while ($row = @mysqli_fetch_array($result)) if (file_exists("$ashopspath/updates/$productid")) unlink("$ashopspath/updates/$productid");
	   @mysqli_query($db, "DELETE FROM updates WHERE productid='$productid'");

	   // If there are copies of this product, the first copy should become the original...
	   $result = @mysqli_query($db, "SELECT * FROM product WHERE copyof='$productid' ORDER BY productid ASC LIMIT 1");
	   $firstcopy = @mysqli_result($result, 0, "productid");
	   @mysqli_query($db, "UPDATE product SET copyof=NULL WHERE productid='$firstcopy'");
	   @mysqli_query($db, "UPDATE product SET copyof='$firstcopy' WHERE copyof='$productid'");

	   header("Location: editcatalogue.php?cat=$cat&search=$search&resultpage=$resultpage&pid=$pid");
    }
	elseif ($no) header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
	else echo "$header
        <div class=\"heading\">".REMOVEAPRODUCT."</div><table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\">
        <p class=\"warning\">".THISWILLCOMPLETELYDELETE." <a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a> ".FROMTHECATALOG."</p>
		<form action=\"editproduct.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form>
		</td></tr></table>
        $footer";
} 

// Handle editing of the product...
elseif ($edit && $productid) {

	// Get listmessenger groups if applicable...
	function parselmconfigstring($lmconfigstring) {
		$returnstring = "";
		$returnstring = substr($lmconfigstring, strpos($lmconfigstring, "\"")+1);
		$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
		$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
		$returnstring = substr($returnstring, 0, strpos($returnstring, "\""));
		return $returnstring;
	}
	if ($listmessengerpath && !file_exists("$listmessengerpath/config.inc.php") && file_exists("$listmessengerpath/includes/config.inc.php")) {
		$listmessengerversion = "pro";
		$listmessengerpath .= "/includes";
	}
	if ($listmessengerpath && file_exists("$listmessengerpath/config.inc.php")) {
		@mysqli_close($db);
		$fp = fopen ("$listmessengerpath/config.inc.php", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "DATABASE_HOST")) {
				$lmhost = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "DATABASE_NAME")) {
				$lmname = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "DATABASE_USER")) {
				$lmuser = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "DATABASE_PASS")) {
				$lmpass = parselmconfigstring($buffer);
			}
			if (strpos($buffer, "TABLES_PREFIX")) {
				$lmprefix = parselmconfigstring($buffer);
			}
		}
		fclose ($fp);
		$lmdb = @mysqli_connect("$lmhost", "$lmuser", "$lmpass", "$lmname");
		if ($listmessengerversion == "pro") $sql = "SELECT * FROM {$lmprefix}groups";
		else $sql = "SELECT * FROM {$lmprefix}user_groups";
		$result = @mysqli_query($lmdb, $sql);
		$lmselectstring = "";
		if (@mysqli_num_rows($result)) {
			$lmselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".LISTMESSENGERGROUP.":</td><td class=\"formlabel\"><select name=\"lmgroup\"><option value=\"0\">".NONE."</option>";
			for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
				if ($listmessengerversion == "pro") $lmgroupid = @mysqli_result($result, $i, "groups_id");
				else $lmgroupid = @mysqli_result($result, $i, "group_id");
				$lmgroupname = @mysqli_result($result, $i, "group_name");
				$lmselectstring .= "<option value=\"$lmgroupid\"";
				if ($lmgroupid == $productlmgroup) $lmselectstring .= " selected";
				$lmselectstring .= ">$lmgroupname</option>";
			}
			$lmselectstring .= "</select></td></tr>";
		}
		@mysqli_close($lmdb);
		$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
	}

	// Get MailChimp lists if applicable...
	if ($mailchimpapikey) {
		require_once "../includes/MCAPI.class.php";
		$api = new MCAPI($mailchimpapikey);
		$retval = $api->lists();
		if (!$api->errorCode){
			$mcselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".MAILCHIMPLIST.":</td><td class=\"formlabel\"><select name=\"mclist\"><option value=\"0\">".NONE."</option>";
			foreach ($retval['data'] as $list){
				$mcselectstring .= "<option value=\"".$list['id']."\"";
				if ($list['id'] == $productmcgroup) $mcselectstring .= " selected";
				$mcselectstring .= ">".$list['name']."</option>";
			}
			$mcselectstring .= "</select></td></tr>";
		}
	}

	// Get punbb groups if applicable...
	if ($phpbbpath && file_exists("$phpbbpath/config.php")) {
		$fp = fopen ("$phpbbpath/config.php", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "\$db_host") == 0 && is_integer(strpos($buffer, "\$db_host"))) {
				$phpbbhost = substr($buffer, strpos($buffer, "'")+1);
				$phpbbhost = substr($phpbbhost, 0, strpos($phpbbhost, "'"));
			}
			if (strpos($buffer, "\$db_name") == 0 && is_integer(strpos($buffer, "\$db_name"))) {
				$phpbbname = substr($buffer, strpos($buffer, "'")+1);
				$phpbbname = substr($phpbbname, 0, strpos($phpbbname, "'"));
			}
			if (strpos($buffer, "\$db_username") == 0 && is_integer(strpos($buffer, "\$db_username"))) {
				$phpbbuser = substr($buffer, strpos($buffer, "'")+1);
				$phpbbuser = substr($phpbbuser, 0, strpos($phpbbuser, "'"));
			}
			if (strpos($buffer, "\$db_password") == 0 && is_integer(strpos($buffer, "\$db_password"))) {
				$phpbbpass = substr($buffer, strpos($buffer, "'")+1);
				$phpbbpass = substr($phpbbpass, 0, strpos($phpbbpass, "'"));
			}
			if (strpos($buffer, "\$db_prefix") == 0 && is_integer(strpos($buffer, "\$db_prefix"))) {
				$phpbbtablepref = substr($buffer, strpos($buffer, "'")+1);
				$phpbbtablepref = substr($phpbbtablepref, 0, strpos($phpbbtablepref, "'"));
			}
			if (strpos($buffer, "\$db_type") == 0 && is_integer(strpos($buffer, "\$db_type"))) {
				$phpbbdbms = substr($buffer, strpos($buffer, "'")+1);
				$phpbbdbms = substr($phpbbdbms, 0, strpos($phpbbdbms, "'"));
			}
		}
		fclose ($fp);
		if (stristr($phpbbdbms, "mysql")) {
			@mysqli_close($db);
			$phpbbdb = @mysqli_connect("$phpbbhost", "$phpbbuser", "$phpbbpass", "$phpbbname");
			$sql = "SELECT * FROM $phpbbtablepref"."groups";
			$result = @mysqli_query($phpbbdb, $sql);
			$phpbbselectstring = "";
			if (@mysqli_num_rows($result)) {
				$phpbbselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a> ".PHPBBUSERGROUP.":</td><td class=\"formlabel\"><select name=\"phpbbgroup\"><option value=\"0\">".NONE."</option>";
				for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
					$phpbbgroupid = @mysqli_result($result, $i, "g_id");
					$phpbbgroupname = @mysqli_result($result, $i, "g_title");
					$phpbbselectstring .= "<option value=\"$phpbbgroupid\"";
					if ($phpbbgroupid == $productphpbbgroup) $phpbbselectstring .= " selected";
					$phpbbselectstring .= ">$phpbbgroupname</option>";
				}
				$phpbbselectstring .= "</select></td></tr>";
			}
			@mysqli_close($phpbbdb);
			$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
		}
    }

    // Get ARP3 autoresponders if applicable...
	if ($arpluspath && file_exists("$arpluspath/arp3-config.pl")) {
		$fp = fopen ("$arpluspath/arp3-config.pl", "r");
		while (!feof($fp)) {
			$buffer = fgets($fp,128);
			if (strpos($buffer, "\$db_host") == 0 && is_integer(strpos($buffer, "\$db_host"))) {
				$arphost = substr($buffer, strpos($buffer, "\"")+1);
				$arphost = substr($arphost, 0, strpos($arphost, "\""));
			}
			if (strpos($buffer, "\$db_name") == 0 && is_integer(strpos($buffer, "\$db_name"))) {
				$arpname = substr($buffer, strpos($buffer, "\"")+1);
				$arpname = substr($arpname, 0, strpos($arpname, "\""));
			}
			if (strpos($buffer, "\$db_login") == 0 && is_integer(strpos($buffer, "\$db_login"))) {
				$arpuser = substr($buffer, strpos($buffer, "\"")+1);
				$arpuser = substr($arpuser, 0, strpos($arpuser, "\""));
			}
			if (strpos($buffer, "\$db_password") == 0 && is_integer(strpos($buffer, "\$db_password"))) {
				$arppass = substr($buffer, strpos($buffer, "\"")+1);
				$arppass = substr($arppass, 0, strpos($arppass, "\""));
			}
			if (strpos($buffer, "\$db_table_AUT") == 0 && is_integer(strpos($buffer, "\$db_table_AUT"))) {
				$arptable = substr($buffer, strpos($buffer, "'")+1);
				$arptable = substr($arptable, 0, strpos($arptable, "'"));
			}
			if (strpos($buffer, "\$_your_domain_name") == 0 && is_integer(strpos($buffer, "\$_your_domain_name"))) {
				$arpdomain = substr($buffer, strpos($buffer, "\"")+1);
				$arpdomain = substr($arpdomain, 0, strpos($arpdomain, "\""));
			}
		}
		fclose ($fp);
		$arpdb = @mysqli_connect("$arphost", "$arpuser", "$arppass", "$arpname");
		$sql = "SELECT * FROM $arptable";
		$result = @mysqli_query($arpdb, $sql);
		$arpselectstring = "";
		if (@mysqli_num_rows($result)) {
			$arpselectstring = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3a\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> ".ARP3AUTORESPONDER.":</td><td class=\"formlabel\"><select name=\"arpresponder\"><option value=\"0\">".NONE."</option>";
			for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
				$arprespid = @mysqli_result($result, $i, "id");
				$arprespname = @mysqli_result($result, $i, "listens_on")."@$arpdomain";
				$arpselectstring .= "<option value=\"$arprespid\"";
				if ($arprespid == $productarpresponder) $arpselectstring .= " selected";
				$arpselectstring .= ">$arprespname</option>";
			}
			$arpselectstring .= "</select></td></tr>";
		}
		@mysqli_close($arpdb);
		$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
}

// Get ARP Reach autoresponders if applicable...
if ($arpreachpath && file_exists("$arpreachpath/config.php")) {
	$fp = fopen ("$arpreachpath/config.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$config['database_host']") == 0 && is_integer(strpos($buffer, "\$config['database_host']"))) {
			$arpreachhost = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachhost = substr($arpreachhost, 0, strpos($arpreachhost, "'"));
		}
		if (strpos($buffer, "\$config['database_name']") == 0 && is_integer(strpos($buffer, "\$config['database_name']"))) {
			$arpreachname = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachname = substr($arpreachname, 0, strpos($arpreachname, "'"));
		}
		if (strpos($buffer, "\$config['database_username']") == 0 && is_integer(strpos($buffer, "\$config['database_username']"))) {
			$arpreachuser = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachuser = substr($arpreachuser, 0, strpos($arpreachuser, "'"));
		}
		if (strpos($buffer, "\$config['database_password']") == 0 && is_integer(strpos($buffer, "\$config['database_password']"))) {
			$arpreachpass = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachpass = substr($arpreachpass, 0, strpos($arpreachpass, "'"));
		}
		if (strpos($buffer, "\$config['database_table_prefix']") == 0 && is_integer(strpos($buffer, "\$config['database_table_prefix']"))) {
			$arpreachtable = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachtable = substr($arpreachtable, 0, strpos($arpreachtable, "'"));
		}
		if (strpos($buffer, "\$config['application_url']") == 0 && is_integer(strpos($buffer, "\$config['application_url']"))) {
			$arpreachurl = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachurl = substr($arpreachurl, 0, strpos($arpreachurl, "'"));
		}
	}
	fclose ($fp);
	$arpreachdb = @mysqli_connect("$arpreachhost", "$arpreachuser", "$arpreachpass", "$arpreachname");
	$sql = "SELECT id, name FROM {$arpreachtable}actions WHERE event_type='6' AND enabled='1'";
	$result = @mysqli_query($arpreachdb, $sql);
	$arpreachselectstring = "";
	if (@mysqli_num_rows($result)) {
		$arpreachselectstring = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3a\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> ARP Reach Action:</td><td class=\"formlabel\"><select name=\"arpreachresponder\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$arpreachrespid = @mysqli_result($result, $i, "id");
			$arpreachrespname = @mysqli_result($result, $i, "name");
			$arpreachselectstring .= "<option value=\"$arpreachrespid\"";
			if ($arpreachrespid == $productarpreachresponder) $arpreachselectstring .= " selected";
			$arpreachselectstring .= ">$arpreachrespname</option>";
		}
		$arpreachselectstring .= "</select></td></tr>";
	}
	@mysqli_close($arpreachdb);
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
}

// Get Infinity Responder autoresponders if applicable...
if ($infinitypath && file_exists("$infinitypath/config.php")) {
	$fp = fopen ("$infinitypath/config.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$MySQL_server") == 0 && is_integer(strpos($buffer, "\$MySQL_server"))) {
			$infinityhost = substr($buffer, strpos($buffer, "'")+1);
			$infinityhost = substr($infinityhost, 0, strpos($infinityhost, "\""));
		}
		if (strpos($buffer, "\$MySQL_database") == 0 && is_integer(strpos($buffer, "\$MySQL_database"))) {
			$infinityname = substr($buffer, strpos($buffer, "'")+1);
			$infinityname = substr($infinityname, 0, strpos($infinityname, "'"));
		}
		if (strpos($buffer, "\$MySQL_user") == 0 && is_integer(strpos($buffer, "\$MySQL_user"))) {
			$infinityuser = substr($buffer, strpos($buffer, "'")+1);
			$infinityuser = substr($infinityuser, 0, strpos($infinityuser, "'"));
		}
		if (strpos($buffer, "\$MySQL_password") == 0 && is_integer(strpos($buffer, "\$MySQL_password"))) {
			$infinitypass = substr($buffer, strpos($buffer, "'")+1);
			$infinitypass = substr($infinitypass, 0, strpos($infinitypass, "'"));
		}
	}
	fclose ($fp);
	$infinitydb = @mysqli_connect("$infinityhost", "$infinityuser", "$infinitypass", "$infinityname");
	$sql = "SELECT * FROM InfResp_responders ORDER BY Name";
	$result = @mysqli_query($infinitydb, $sql);
	$infinityselectstring = "";
	if (@mysqli_num_rows($result)) {
		$infinityselectstring1 = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3b','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3b\" align=\"absmiddle\" onclick=\"return overlib('$tip3b');\" onmouseout=\"return nd();\"></a> ".ADDTOINFINITYRESPONDER.":</td><td class=\"formlabel\"><select name=\"infinityresponder\"><option value=\"0\">".NONE."</option>";
		$infinityselectstring2 = "<tr><td class=\"formlabel\" align=\"right\">".REMOVEFROMINFINITYRESPONDER.":</td><td class=\"formlabel\"><select name=\"infinityresponderoff\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$infinityrespid = @mysqli_result($result, $i, "ResponderID");
			$infinityrespname = @mysqli_result($result, $i, "Name");
			$infinityselectstring1 .= "<option value=\"$infinityrespid\"";
			$infinityselectstring2 .= "<option value=\"$infinityrespid\"";
			if ($infinityrespid == $productinfresponder) $infinityselectstring1 .= " selected";
			if ($infinityrespid == $productinfresponderoff) $infinityselectstring2 .= " selected";
			$infinityselectstring1 .= ">$infinityrespname</option>";
			$infinityselectstring2 .= ">$infinityrespname</option>";
		}
		$infinityselectstring1 .= "</select></td></tr>";
		$infinityselectstring2 .= "</select></td></tr>";
	}
	@mysqli_close($infinitydb);
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
}

// Get Interspire Email Marketer lists if applicable...
if ($iemurl && $iemuser && $iemtoken) {
	$iemselectstring = "";
	$iemxml = "<xmlrequest><username>$iemuser</username><usertoken>$iemtoken</usertoken><requesttype>lists</requesttype><requestmethod>GetLists</requestmethod><details> </details></xmlrequest>";
	$iemch = @curl_init($iemurl);
	curl_setopt($iemch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($iemch, CURLOPT_POST, 1);
	curl_setopt($iemch, CURLOPT_POSTFIELDS, $iemxml);
	$iemresult = @curl_exec($iemch);
	if($iemresult === false) {}
	else {
		$iemselectstring .= "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3d','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3d\" align=\"absmiddle\" onclick=\"return overlib('$tip3d');\" onmouseout=\"return nd();\"></a> ".EMAILMARKETERLIST.":</td><td class=\"formlabel\"><select name=\"iemlist\"><option value=\"0\">".NONE."</option>";
		if (strpos($iemresult,"<item>")) {
			$iemresultarray = explode("<item>",$iemresult);
			foreach($iemresultarray as $iempartnumber=>$iemxmlpart) {
				$iemlistid = 0;
				$iemlistname = "";
				if (strpos($iemxmlpart,"<listid>")) {
					$iemsubresultarray = explode("<listid>",$iemxmlpart);
					$iemsubresultarray = explode("</listid>",$iemsubresultarray[1]);
					$iemlistid = $iemsubresultarray[0];
				}
				if (strpos($iemxmlpart,"<name>")) {
					$iemsubresultarray = explode("<name>",$iemxmlpart);
					$iemsubresultarray = explode("</name>",$iemsubresultarray[1]);
					$iemlistname = $iemsubresultarray[0];
				}
				if ($iemlistid && $iemlistname) {
					$iemselectstring .= "<option value=\"$iemlistid\"";
					if ($iemlistid == $productiemlist) $iemselectstring .= " selected";
					$iemselectstring .= ">$iemlistname</option>";
				}
			}
		}
		$iemselectstring .= "</select></td></tr>";
	}
}

  // Make sure special characters are handled properly...
  $name = str_replace("\"", "&quot;", $name);
  //$name = str_replace("'", "&#039;", $name);
  //$description = str_replace("'", "&#039;", $description);

  // Show edit form...
  if (!$edited || $addlicense || $addfloatingprice || $removefloatingprice || !$name || $price == '') {
	  echo "$header";
	  if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) {
		  echo "
<script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>
";
	  }
  echo "
        <div class=\"heading\">".EDITPRODUCT."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>
        <form action=\"editproduct.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">";
		echo "<table width=\"670\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\"><tr><td colspan=\"2\" class=\"formlabel\" align=\"center\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image0','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image0\" align=\"absmiddle\" onclick=\"return overlib('$tip0');\" onmouseout=\"return nd();\"></a> ".PRODUCTID.": $productid";
		if ($productcopyof) echo "<br>".COPYOFPRODUCT.": $productcopyof <input type=\"submit\" name=\"detach\" value=\"".DETACH."\">";
		echo "</td></tr>
		      <tr><td align=\"right\" class=\"formlabel\" width=\"200\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a> ".NAME.":</td><td align=\"left\"><input type=\"text\" name=\"name\" size=\"35\" value=\"$productname\"><script language=\"JavaScript\">document.productform.name.focus();</script></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".OWNERMEMBER.":</td><td align=\"left\">$memberlist</td></tr>";
			if ($productowner > 1) echo "<tr><td align=\"right\" class=\"formlabel\">".VISIBLEINMAINSHOP.":</td><td align=\"left\"><input type=\"checkbox\" name=\"activateinmainshop\"$inmainshop></td></tr>";
		}
		echo "
			  <tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".CATALOGSTATUS.":</td><td class=\"formlabel\" align=\"left\">";
		if ($userid == "1") echo "<input type=\"checkbox\" name=\"active\"$productstatus> ".ACTIVE;
		else {
			if ($productstatus) echo ACTIVE;
			else echo INACTIVE;
		}
		if($wholesalecatalog && $userid == "1" && (!$addfloatingprice && (!$fp_length || $removefloatingprice))) echo " ".RETAIL." <input type=\"checkbox\" name=\"wholesale\"$productwsstatus> ".WHOLESALE; echo "</td></tr>";

		if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".FEATUREDSPOT.":</td><td align=\"left\"><select name=\"featured\"><option value=\"0\""; if (!$productfeatured) echo " selected"; echo ">".NO."</option>";
			for ($featuredspot = 1; $featuredspot <= $numberoffeatures; $featuredspot++) {
				echo "<option value=\"$featuredspot\"";
				if ($productfeatured == $featuredspot) echo " selected";
				echo ">$featuredspot</option>";
			}
			echo "</select></td></tr>";
		}
		if ($userid == "1") echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a> <a href=\"$help6\" class=\"helpnav2\" target=\"_blank\">".EBAYID."</a>:</td><td align=\"left\"><input type=\"text\" name=\"ebayid\" size=\"10\" value=\"$productebayid\"><span class=\"sm\"> ".OPTIONAL."</span></td></tr>";

		// Show price parameters...        
		if ($addfloatingprice || ($fp_length && !$removefloatingprice)) {
			echo "<tr><td align=\"right\" class=\"formlabel\">".STARTPRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"price\" size=\"10\" value=\"".number_format($productprice,$showdecimals,$decimalchar,$thousandchar)."\"> ".$currencysymbols[$ashopcurrency]["post"]."<input type=\"hidden\" name=\"removefloatingprice\" value=\"\"><input type=\"button\" value=\"".FIXEDPRICE."\" onClick=\"document.productform.removefloatingprice.value='true';document.productform.submit();\"></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".BIDINCREMENT.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"priceincrement\" size=\"10\" value=\"".number_format($fp_priceincr,$showdecimals,$decimalchar,$thousandchar)."\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".LENGTH.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"bidlengthdays\" size=\"2\" value=\"$fp_lengthdays\"> ".DAYS.", <input type=\"text\" name=\"bidlengthhours\" size=\"2\" value=\"$fp_lengthhours\"> h, <input type=\"text\" name=\"bidlengthminutes\" size=\"2\" value=\"$fp_lengthminutes\"> ".MINUTES.", <input type=\"text\" name=\"bidlengthseconds\" size=\"2\" value=\"$fp_lengthseconds\"> ".SECONDS."</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".ACTIVATE.":</td><td class=\"formlabel\" align=\"left\">$fp_activatestring</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".AUCTIONTYPE.":</td><td class=\"formlabel\" align=\"left\"><select name=\"auctiontype\"><option value=\"standard\""; if($fp_type == "standard") echo " selected"; echo ">".STANDARD."</option><option value=\"penny\""; if($fp_type == "penny") echo " selected"; echo ">".PENNY."</option></td></tr>";
		} else {
			echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> ".PRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"price\" size=\"10\" value=\"".number_format($productprice,$showdecimals,$decimalchar,$thousandchar)."\"> ".$currencysymbols[$ashopcurrency]["post"]."
			<input type=\"hidden\" name=\"addfloatingprice\" value=\"\"><input type=\"button\" value=\"".AUCTION."\" onClick=\"document.productform.addfloatingprice.value='true';document.productform.submit();\">
			</td></tr>";
		}

		if ($wholesalecatalog && ($advancedmallmode == "1" || $userid == "1")) {
			echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip8');\" onmouseout=\"return nd();\"></a> ".WHOLESALEPRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"wholesaleprice\" size=\"10\" value=\"".number_format($productwsprice,$showdecimals,$decimalchar,$thousandchar)."\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			if ($pricelevels > 1) {
				for ($thislevel = 2; $thislevel <= $pricelevels; $thislevel++) {
					if (empty($productpricelevels[$thislevel-2])) $productpricelevels[$thislevel-2] = 0.00;
					echo "<tr><td align=\"right\" class=\"formlabel\">".WHOLESALEPRICELEVEL." $thislevel:</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"wholesaleprice$thislevel\" size=\"10\" value=\"".number_format($productpricelevels[$thislevel-2],$showdecimals,$decimalchar,$thousandchar)."\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
				}
			}
		}
		if ($userid == "1" && file_exists("$ashoppath/emerchant/quote.php")) $billtemplateresult = @mysqli_query($db, "SELECT * FROM emerchant_billtemplates ORDER BY name");
		$recurringresult = @mysqli_query($db, "SELECT * FROM payoptions WHERE gateway='ccbill' OR gateway='paypal' OR gateway='paypalsandbox' OR gateway='payza' OR gateway='netbillingrecurring'");
		if (@mysqli_num_rows($billtemplateresult) || @mysqli_num_rows($recurringresult)) {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".RECURRINGPRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"recurringprice\" size=\"10\" value=\"".number_format($productrecurringprice,$showdecimals,$decimalchar,$thousandchar)."\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			echo "<tr><td align=\"right\" class=\"formlabel\">".RECURRINGPERIOD.":</td><td><input type=\"text\" name=\"nrecurringperiod\" size=\"5\" value=\"$recurringperiod\"> <select name=\"nrecurringperiodunits\"><option value=\"D\""; if ($recurringperiodunits == "D") echo " selected"; echo ">".DAYS."</option><option value=\"W\""; if ($recurringperiodunits == "W") echo " selected"; echo ">".WEEKS."</option><option value=\"M\""; if ($recurringperiodunits == "M") echo " selected"; echo ">".MONTHS."</option><option value=\"Y\""; if ($recurringperiodunits == "Y") echo " selected"; echo ">".YEARS."</option></select></td></tr>";
		}
		if ($userid == "1" && @mysqli_num_rows($billtemplateresult)) {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".BILLINGTEMPLATE."</a>:</td><td class=\"formlabel\" align=\"left\"><select name=\"billtemplate\"><option value=\"none\"";
			if (!$productbilltemplate) echo " selected";
			echo ">".NONE;
			while ($billtemplaterow = @mysqli_fetch_array($billtemplateresult)) {
				$billtemplatename = $billtemplaterow["name"];
				$billtemplateid = $billtemplaterow["billtemplateid"];
				echo "<option value=\"$billtemplateid\"";
				if ($productbilltemplate == $billtemplateid) echo " selected";
				echo ">$billtemplatename";
			}
			echo "</select></td></tr>";
		}
		echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".RECEIPTTEXT.":</td><td align=\"left\"><textarea name=\"receipttext\" cols=\"30\" rows=\"2\">".htmlentities(stripslashes($productreceipttext), ENT_QUOTES)."</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> ".DESCRIPTION.":</td><td align=\"left\"><textarea name=\"description\" id=\"id_description\" class=\"ckeditor\" cols=\"30\" rows=\"15\">".htmlentities(stripslashes($productdescr), ENT_QUOTES)."</textarea>";
		if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) echo "<script type=\"text/javascript\">
		CKEDITOR.replace( 'id_description', {
			// Define the toolbar groups as it is a more accessible solution.
			toolbarGroups: [
				{\"name\":\"basicstyles\",\"groups\":[\"basicstyles\"]},
				{\"name\":\"links\",\"groups\":[\"links\"]},
				{\"name\":\"paragraph\",\"groups\":[\"list\",\"blocks\"]},
				{\"name\":\"document\",\"groups\":[\"mode\"]},
				{\"name\":\"insert\",\"groups\":[\"insert\"]},
				{\"name\":\"styles\",\"groups\":[\"styles\"]},
				{\"name\":\"colors\",\"groups\":[\"colors\"]}
			],
			// Remove the redundant buttons from toolbar groups defined above.
			removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar'
		} );
		</script>";
		echo "</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a> <a href=\"$help10\" class=\"helpnav2\" target=\"_blank\">".LICENSEAGREEMENT."</a>:</td><td align=\"left\">";
		if ($addlicense || $productlicense) echo "<textarea name=\"licensetext\" cols=\"30\" rows=\"5\">".htmlentities(stripslashes($productlicense), ENT_QUOTES)."</textarea>";
		else echo "<input type=\"hidden\" name=\"addlicense\" value=\"\"><input type=\"button\" class=\"widebutton\" value=\"".ADDLICENSEAGREEMENT."\" onClick=\"document.productform.addlicense.value='true';document.productform.submit();\">";
		echo "</td></tr>";
		echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image14','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image14\" align=\"absmiddle\" onclick=\"return overlib('$tip14');\" onmouseout=\"return nd();\"></a> <a href=\"$help14\" class=\"helpnav2\" target=\"_blank\">".AFFILIATECOMMISSION."</a>:</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatecom\" size=\"7\" value=\"";
		if ($productaffcom[1]) echo $productaffcom[0];
		else echo $affiliatepercent;
		echo "\"><input type=\"radio\" name=\"affcomtype\" value=\"percent\"";
		if ($productaffcom[1]=="percent" || !$productaffcom[1]) echo "checked";
		echo ">% <input type=\"radio\" name=\"affcomtype\" value=\"money\"";
		if ($productaffcom[1]=="money") echo "checked";
		echo ">";
		if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
		else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
		echo "</td></tr>\n";
		if ($wholesaleaffiliate == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".WSAFFILIATECOMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatewscom\" size=\"7\" value=\"";
			if ($productaffwscom[1]) echo $productaffwscom[0];
			else echo $wholesalepercent;
			echo "\"><input type=\"radio\" name=\"affwscomtype\" value=\"percent\"";
			if ($productaffwscom[1]=="percent" || !$productaffcom[1]) echo "checked";
			echo ">% <input type=\"radio\" name=\"affwscomtype\" value=\"money\"";
			if ($productaffwscom[1]=="money") echo "checked";
			echo ">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>\n";
		}

		if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".UPGRADEDAFFILIATECOMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatecom2\" size=\"7\" value=\"";
			if ($productaffcom2[1]) echo $productaffcom2[0];
			else echo $affiliatepercent2;
			echo "\"><input type=\"radio\" name=\"affcomtype2\" value=\"percent\"";
			if ($productaffcom2[1]=="percent" || !$productaffcom2[1]) echo "checked";
			echo ">% <input type=\"radio\" name=\"affcomtype2\" value=\"money\"";
			if ($productaffcom2[1]=="money") echo "checked";
			echo ">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".TIER2COMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatetier2com\" size=\"7\" value=\"";
			if ($producttier2affcom[1]) echo $producttier2affcom[0];
			else echo $secondtierpercent;
			echo "\"><input type=\"radio\" name=\"afftier2comtype\" value=\"percent\"";
			if ($producttier2affcom[1]=="percent" || !$producttier2affcom[1]) echo "checked";
			echo ">% <input type=\"radio\" name=\"afftier2comtype\" value=\"money\"";
			if ($producttier2affcom[1]=="money") echo "checked";
			echo ">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".UPGRADEDTIER2COMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatetier2com2\" size=\"7\" value=\"";
			if ($producttier2affcom2[1]) echo $producttier2affcom2[0];
			else echo $secondtierpercent2;
			echo "\"><input type=\"radio\" name=\"afftier2comtype2\" value=\"percent\"";
			if ($producttier2affcom2[1]=="percent" || !$producttier2affcom2[1]) echo "checked";
			echo ">% <input type=\"radio\" name=\"afftier2comtype2\" value=\"money\"";
			if ($producttier2affcom2[1]=="money") echo "checked";
			echo ">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".LOWERBY.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatetierlowerby\" size=\"7\" value=\"$producttierlowerby\"> ".ONEACHTIER." <span class=\"sm\"> ".DISABLEMULTITIER."</span></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".ONREPEATORDERS.":</td><td class=\"formlabel\" align=\"left\"><select name=\"affiliaterepeatcommission\"><option value=\"1\""; if ($productaffrepeat == "1") echo " selected"; echo ">Yes</option><option value=\"0\""; if ($productaffrepeat == "0") echo " selected"; echo ">No</option></select></td></tr>
			<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image15','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image15\" align=\"absmiddle\" onclick=\"return overlib('$tip15');\" onmouseout=\"return nd();\"></a> ".SALESTAX.":</td><td align=\"left\"><select name=\"istaxable\"><option value=\"0\""; if ($taxable == "0") echo " selected"; echo ">".NO."<option value=\"1\""; if ($taxable == "1") echo " selected"; echo ">".YES."<option value=\"2\""; if ($taxable == "2") echo " selected"; echo ">".LEVEL2."</select></td></tr>";
			if ($activateautoresponder == "1" && !empty($autoresponderid) && is_numeric($autoresponderid)) {
				$sql = "SELECT * FROM autoresponders ORDER BY name";
				$result = @mysqli_query($db, $sql);
				if (@mysqli_num_rows($result)) {
					echo "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3c','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3c\" align=\"absmiddle\" onclick=\"return overlib('$tip3c');\" onmouseout=\"return nd();\"></a> ".ADDTOAUTORESPONDER.":</td><td class=\"formlabel\" align=\"left\"><select name=\"autoresponder\"><option value=\"0\"";
					if ($productresponder == "0") echo " selected";
					echo ">".NONE."</option>";
					for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
						$responderid = @mysqli_result($result, $i, "responderid");
						$respondername = @mysqli_result($result, $i, "name");
						echo "<option value=\"$responderid\"";
						if ($productresponder == $responderid) echo " selected";
						echo ">$respondername</option>";
					}
					echo "</select></td></tr>
					<tr><td class=\"formlabel\" align=\"right\">".REMOVEFROMAUTORESPONDER.":</td><td class=\"formlabel\" align=\"left\"><select name=\"autoresponderoff\"><option value=\"0\"";
					if ($productresponderoff == "0") echo " selected";
					echo ">".NONE."</option>";
					for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
						$responderid = @mysqli_result($result, $i, "responderid");
						$respondername = @mysqli_result($result, $i, "name");
						echo "<option value=\"$responderid\"";
						if ($productresponderoff == $responderid) echo " selected";
						echo ">$respondername</option>";
					}
					echo "</select></td></tr>";
				}
			} else if (!empty($aweberauthcode)) {
				$sql = "SELECT * FROM autoresponders ORDER BY name";
				$result = @mysqli_query($db, $sql);
				if (@mysqli_num_rows($result)) {
					echo "<tr><td class=\"formlabel\" align=\"right\">AWeber List:</td><td class=\"formlabel\"><select name=\"autoresponder\"><option value=\"0\">".NONE."</option>";
					for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
						$responderid = @mysqli_result($result, $i, "responderid");
						$respondername = @mysqli_result($result, $i, "name");
						echo "<option value=\"$responderid\"";
						if ($productresponder == $responderid) echo " selected";
						echo ">$respondername</option>";
					}
					echo "</select></td></tr>";
				}
			}
			if ($infinityselectstring1) echo $infinityselectstring1;
			if ($infinityselectstring2) echo $infinityselectstring2;
			if ($lmselectstring) echo $lmselectstring;
			if ($mcselectstring) echo $mcselectstring;
			if ($iemselectstring) echo $iemselectstring;
			if ($listmailurl) echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ".LISTMAILPROID.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" size=\"5\" name=\"lmpgroup\" value=\"$productlmpgroup\"></td></tr>";
			if ($phpbbselectstring) echo $phpbbselectstring;
			if ($arpselectstring) echo $arpselectstring;
			if ($arpreachselectstring) echo $arpreachselectstring;
		}
		echo "<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"hidden\" name=\"copyof\" value=\"$productcopyof\"><input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></td></tr></table>";
		echo $footer;
  }
  else {
	// Reset the featured spot...
	if ($userid == "1" && $featured > 0) @mysqli_query($db, "UPDATE product SET featured='0' WHERE featured='$featured'");

	// Convert money format...
	$price = str_replace($thousandchar,"",$price);
	$price = str_replace($decimalchar,".",$price);
	$wholesaleprice = str_replace($thousandchar,"",$wholesaleprice);
	$wholesaleprice = str_replace($decimalchar,".",$wholesaleprice);
	$wspricelevels = "";
	if ($pricelevels > 1) {
		for ($thislevel = 2; $thislevel <= $pricelevels; $thislevel++) {
			$thislevelprice = $_POST["wholesaleprice$thislevel"];
			$thislevelprice = str_replace($thousandchar,"",$thislevelprice);
			$thislevelprice = str_replace($decimalchar,".",$thislevelprice);
			$wspricelevels .= $thislevelprice."|";
		}
		$wspricelevels = substr($wspricelevels,0,-1);
	}
	$priceincrement = str_replace($thousandchar,"",$priceincrement);
	$priceincrement = str_replace($decimalchar,".",$priceincrement);
	$recurringprice = str_replace($thousandchar,"",$recurringprice);
	$recurringprice = str_replace($decimalchar,".",$recurringprice);
	$affiliatecom = str_replace($thousandchar,"",$affiliatecom);
	$affiliatecom = str_replace($decimalchar,".",$affiliatecom);
	$affiliatewscom = str_replace($thousandchar,"",$affiliatewscom);
	$affiliatewscom = str_replace($decimalchar,".",$affiliatewscom);
	$affiliatecom2 = str_replace($thousandchar,"",$affiliatecom2);
	$affiliatecom2 = str_replace($decimalchar,".",$affiliatecom2);
	$affiliatetier2com = str_replace($thousandchar,"",$affiliatetier2com);
	$affiliatetier2com = str_replace($decimalchar,".",$affiliatetier2com);
	$affiliatetier2com2 = str_replace($thousandchar,"",$affiliatetier2com2);
	$affiliatetier2com2 = str_replace($decimalchar,".",$affiliatetier2com2);
	$affiliatetierlowerby = str_replace($thousandchar,"",$affiliatetierlowerby);
	$affiliatetierlowerby = str_replace($decimalchar,".",$affiliatetierlowerby);	

	// Check max affiliate commission for member products...
	if ($userid != "1") {
		if ($affiliatecom && $affcomtype == "money") {
			$memberresult = @mysqli_query($db, "SELECT commissionlevel FROM user WHERE userid='$userid'");
			$commissionlevel = @mysqli_result($memberresult, 0, "commissionlevel");
			if ($commissionlevel > 75) $commissionlevel = 75;
			$commissionlevel = $commissionlevel/100;
			$maxcommission = number_format($price*$commissionlevel,2,'.','');
			if ($affiliatecom > $maxcommission) $affiliatecom = $maxcommission;
		} else if ($affiliatecom > 75) $affiliatecom = 75;
		if ($affiliatewscom && $affwscomtype == "money") {
			$memberresult = @mysqli_query($db, "SELECT commissionlevel FROM user WHERE userid='$userid'");
			$commissionlevel = @mysqli_result($memberresult, 0, "commissionlevel");
			if ($commissionlevel > 75) $commissionlevel = 75;
			$commissionlevel = $commissionlevel/100;
			$maxcommission = number_format($price*$commissionlevel,2,'.','');
			if ($affiliatewscom > $maxcommission) $affiliatewscom = $maxcommission;
		} else if ($affiliatewscom > 75) $affiliatewscom = 75;
	}
	$sql="UPDATE product SET name='$name', price='$price', description='$description', receipttext='$receipttext'";
    if ($userid == "1") {
		if ($active == "on") $sql.=", active=1";
		else $sql.=", active=0";
		if ($wholesale == "on" && !$priceincrement) $sql.=", wholesaleactive=1";
		else $sql.=", wholesaleactive=0";
	}
    $sql.=", licensetext='$licensetext', ebayid='$ebayid'";
	if (isset($activateinmainshop)) {
		if (!empty($activateinmainshop)) $sql.=", inmainshop=1";
		else $sql.=", inmainshop=0";
	} else if ($userid == "1") $sql.=", inmainshop=0";
	if ($lmgroup) $sql.=", listmessengergroup=$lmgroup";
	else $sql.=", listmessengergroup=NULL";
	if ($lmpgroup) $sql.=", listmaillist='$lmpgroup'";
	else $sql.=", listmaillist='0'";
	if ($iemlist) $sql.=", iemlist='$iemlist'";
	else $sql.=", iemlist='0'";
	if ($mclist) $sql.=", mailchimplist='$mclist'";
	else $sql.=", mailchimplist=''";
	if ($phpbbgroup) $sql.=", phpbbgroup=$phpbbgroup";
	else $sql.=", phpbbgroup=NULL";
	if ($wholesaleprice) $sql.=", wholesaleprice='$wholesaleprice'";
	else $sql.=", wholesaleprice=NULL";
	if ($wspricelevels) $sql.=", wspricelevels='$wspricelevels'";
	else $sql.=", wspricelevels=NULL";
	if ($arpresponder) $sql.=", arpresponder='$arpresponder'";
	else $sql.=", arpresponder=NULL";
	if ($arpreachresponder) $sql.=", arpreachresponder='$arpreachresponder'";
	else $sql.=", arpreachresponder=NULL";
	if (isset($autoresponder)) $sql .= ", autoresponder='$autoresponder'";
	if (isset($autoresponderoff)) $sql .= ", autoresponderoff='$autoresponderoff'";
	if ($infinityresponder) $sql.=", infresponder=$infinityresponder";
	else $sql.=", infresponder=NULL";
	if ($infinityresponderoff) $sql.=", infresponderoff=$infinityresponderoff";
	else $sql.=", infresponderoff=NULL";	
    if ($istaxable == "0") $sql.=", taxable=NULL";
	else $sql.=", taxable='$istaxable'";
	if ($billtemplate && $billtemplate != "none") $sql.=", billtemplate='$billtemplate'";
	else $sql.=", billtemplate=NULL";
    if (!$affiliatecom) $affiliatecom = "0";
	$affiliatecomstring = $affiliatecom."a$affcomtype";
    $sql.=", affiliatecom='$affiliatecomstring'";
    if (!$affiliatewscom) $affiliatewscom = "0";
	$affiliatewscomstring = $affiliatewscom."a$affwscomtype";
    $sql.=", affiliatewscom='$affiliatewscomstring'";
    if (!$affiliatecom2) $affiliatecom2 = "0";
    $affiliatecom2string = $affiliatecom2."a$affcomtype2";
    $sql.=", affiliatecom2='$affiliatecom2string'";
	if (empty($affiliatetier2com)) $affiliatetier2com = 0;
	if (empty($affiliatetier2com2)) $affiliatetier2com2 = 0;
	if (empty($affiliatetierlowerby)) $affiliatetierlowerby = 0;
	if ($afftier2comtype != "percent" && $afftier2comtype != "money") $afftier2comtype = "percent";
	if ($afftier2comtype2 != "percent" && $afftier2comtype2 != "money") $afftier2comtype2 = "percent";
	$affiliatetiercom = $affiliatetier2com."a".$afftier2comtype."|".$affiliatetier2com2."a".$afftier2comtype2."|".$affiliatetierlowerby;
	$sql.=", affiliatetiercom='$affiliatetiercom'";
	if (empty($affiliaterepeatcommission) || $affiliaterepeatcommission != "1") $affiliaterepeatcommission = 0;
	$sql.=", affiliaterepeatcommission='$affiliaterepeatcommission'";
	if (isset($memberid)) $sql .= ", userid='$memberid'";
	if ($recurringprice) $sql .= ", recurringprice='$recurringprice'";
	else $sql .= ", recurringprice=NULL";
	if ($recurringprice && $nrecurringperiod) $sql .= ", recurringperiod='$nrecurringperiod|$nrecurringperiodunits'";
	else $sql .= ", recurringperiod=NULL";
	if ($copyof) $sql.=" WHERE productid='$productid' OR copyof='$productid' OR productid='$copyof' OR copyof='$copyof'";
	else $sql.=" WHERE productid='$productid' OR copyof='$productid'";
    $result = @mysqli_query($db, $sql);

	// Set featured to the original product if selected on a copy...
	if ($copyof) {
		@mysqli_query($db, "UPDATE product SET featured='$featured' WHERE productid='$copyof'");
		@mysqli_query($db, "UPDATE product SET featured='0' WHERE copyof='$copyof'");
	} else {
		@mysqli_query($db, "UPDATE product SET featured='$featured' WHERE productid='$productid'");
		@mysqli_query($db, "UPDATE product SET featured='0' WHERE copyof='$productid'");
	}
	
	// Update attribute specific price if needed...
	if ($price != $productprice) {
		$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid' OR productid='$copyof'");
		while ($row = @mysqli_fetch_array($result)) {
			$parameterid = $row["parameterid"];
			@mysqli_query($db, "UPDATE parametervalues SET price='$price' WHERE parameterid='$parameterid' AND price='$productprice'");
		}
	}

	// Update floating price...
	$bidlength = $bidlengthdays*86400;
	$bidlength += $bidlengthhours*3600;
	$bidlength += $bidlengthminutes*60;
	$bidlength += $bidlengthseconds;
	if ($price && $priceincrement && $bidlength) {
		$activatetimestamp = mktime($activatehour, $activateminute, 0, $activatemonth, $activateday, date("Y",time()));
		if (!$fp_length) {
			if ($auctiontype == "standard") {
				$starttimestamp = $activatetimestamp;
				@mysqli_query($db, "INSERT INTO floatingprice (productid, startprice, originalstartprice, length, priceincrement, activatetime, type, starttime) VALUES ('$productid', '$price', '$price', '$bidlength', '$priceincrement', '$activatetimestamp', '$auctiontype', '$starttimestamp')");
			} else @mysqli_query($db, "INSERT INTO floatingprice (productid, startprice, originalstartprice, length, priceincrement, activatetime, type) VALUES ('$productid', '$price', '$price', '$bidlength', '$priceincrement', '$activatetimestamp', '$auctiontype')");
		} else {
			if ($auctiontype == "standard") {
				$starttimestamp = $activatetimestamp;
				@mysqli_query($db, "UPDATE floatingprice SET startprice='$price', originalstartprice='$price', length='$bidlength', priceincrement='$priceincrement', activatetime='$activatetimestamp', type='$auctiontype', starttime='$starttimestamp' WHERE productid='$productid'");
			} else @mysqli_query($db, "UPDATE floatingprice SET startprice='$price', originalstartprice='$price', length='$bidlength', priceincrement='$priceincrement', activatetime='$activatetimestamp', type='$auctiontype' WHERE productid='$productid'");
		}
		@mysqli_query($db, "DELETE FROM qtypricelevels WHERE productid='$productid'");
		if ($copyof) $checkfppriceoncopies = @mysqli_query($db, "SELECT * FROM product WHERE copyof='$productid' OR productid='$copyof' OR copyof='$copyof'");
		else $checkfppriceoncopies = @mysqli_query($db, "SELECT * FROM product WHERE copyof='$productid'");
		while ($checkfppricerow = @mysqli_fetch_array($checkfppriceoncopies)) {
			$fpcopyproductid = $checkfppricerow["productid"];
			$checkfponthiscopy = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$fpcopyproductid'");
			if (@mysqli_num_rows($checkfponthiscopy)) @mysqli_query($db, "UPDATE floatingprice SET startprice='$price', originalstartprice='$price', length='$bidlength', priceincrement='$priceincrement', activatetime='$activatetimestamp', type='$auctiontype' WHERE productid='$fpcopyproductid'");
			else @mysqli_query($db, "INSERT INTO floatingprice (productid, startprice, originalstartprice, length, priceincrement, activatetime, type) VALUES ('$fpcopyproductid', '$price', '$price', '$bidlength', '$priceincrement', '$activatetimestamp', '$auctiontype')");
			@mysqli_query($db, "DELETE FROM qtypricelevels WHERE productid='$fpcopyproductid'");
		}
	} else {
		@mysqli_query($db, "DELETE FROM floatingprice WHERE productid='$productid'");
		if ($copyof) $checkfppriceoncopies = @mysqli_query($db, "SELECT * FROM product WHERE copyof='$productid' OR productid='$copyof' OR copyof='$copyof'");
		else $checkfppriceoncopies = @mysqli_query($db, "SELECT * FROM product WHERE copyof='$productid'");
		while ($checkfppricerow = @mysqli_fetch_array($checkfppriceoncopies)) {
			$fpcopyproductid = $checkfppricerow["productid"];
			@mysqli_query($db, "DELETE FROM floatingprice WHERE productid='$fpcopyproductid'");
		}
	}

	if ($error) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
    else header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>