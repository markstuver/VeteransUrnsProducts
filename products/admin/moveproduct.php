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

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
   $sql="SELECT * FROM product WHERE productid='$productid'";
   $result = @mysqli_query($db, $sql);
   $productname = @mysqli_result($result, 0, "name");
   $productowner = @mysqli_result($result, 0, "userid");
   $productcopyof = @mysqli_result($result,0 , "copyof");
   if (!empty($productcopyof)) $result = @mysqli_query($db, "SELECT productcategory.categoryid FROM productcategory,product WHERE (product.productid='$productid' OR product.copyof='$productcopyof') AND product.productid=productcategory.productid");
   else $result = @mysqli_query($db, "SELECT productcategory.categoryid FROM productcategory,product WHERE (product.productid='$productid' OR product.copyof='$productid') AND product.productid=productcategory.productid");
   $numberofcategories = @mysqli_num_rows($result);
   $incategories = array();
   while ($row = @mysqli_fetch_array($result)) $incategories[] = $row['categoryid'];
   if ($pid || $search) $cat = $incategories[0];
   if (!empty($productcopyof)) {
	   $result = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$productcopyof'");
	   $originalcategoryid = @mysqli_result($result,0,"categoryid");
   } else {
	   $result = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$productid'");
	   $originalcategoryid = @mysqli_result($result,0,"categoryid");
   }
   
   if (!$memberprodmanage) {
	   if ($userid > 1) {
		   header("Location: index.php");
		   exit;
	   } else $catuser = "%";
   } else {
	   if ($userid == 1) {
		   if ($_COOKIE["catmemberid"]) $catuser ="1";
		   else $catuser = "%";
	   } else {
		   if (!$membershops) $catuser = "%";
		   else $catuser = $userid;
	   }
   }

if ($move) {
	if (!$newcat) $newcat = $cat;
	echo "$header
        <div class=\"heading\">".MOVEORCOPYPRODUCT."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p>".SELECTCATEGORYTOMOVECOPY." <b>$productname</b> ".TODOTS."</font></p>
		<table width=\"400\" align=\"center\" cellspacing=\"0\" cellpadding=\"5\" bordercolor=\"#FFFFFF\">";
        if ($newcat) {
          $sql="SELECT * from category WHERE categoryid = '$newcat'";
          $result = @mysqli_query($db, $sql);
          $grandparent = @mysqli_result($result, 0, "grandparentcategoryid");
		  $parent = @mysqli_result($result, 0, "parentcategoryid");
        }
		if ($_COOKIE["catmemberid"]) echo "<tr><td bgcolor=\"#FFFFFF\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>".MAINSHOP.":</b></font></td></tr>";
		if (!$membershops && $userid > 1) $condition = "";
		else $condition = " OR memberclone='1'";
        $sql="SELECT categoryid, name FROM category WHERE (userid LIKE '$catuser'$condition) AND grandparentcategoryid = categoryid ORDER BY ordernumber";
        $result = @mysqli_query($db, $sql);
        for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
           $categoryname = @mysqli_result($result, $i, "name");
           $categoryid = @mysqli_result($result, $i, "categoryid");
	       if ($categoryid == $newcat) $cellcolor = "#F0F0F0";
	       else $cellcolor = "#D0D0D0";
           echo "<tr><td style=\"border: 1px solid #000;\" bgcolor=\"$cellcolor\"><table><tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"moveproduct.php?move=true&cat=$cat&newcat=$categoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\">$categoryname</a></font></td></tr><tr><td align=\"left\"><form action=\"moveproduct.php?cat=$cat&newcat=$categoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\" method=\"post\" style=\"margin-bottom: 0px;\">";
		   if ((!in_array($categoryid,$incategories) || $userid == "1") && $categoryid != $originalcategoryid) echo "<input type=\"submit\" name=\"moved\" value=\"".MOVEHERE."\"> <input type=\"submit\" name=\"copied\" value=\"".COPYHERE."\">";
		   if (in_array($categoryid,$incategories) && $categoryid != $originalcategoryid) echo " <input type=\"submit\" name=\"removed\" value=\"".REMOVEFROMHERE."\" class=\"widebutton\">";
		   echo "</form></td></tr></table></td></tr>";
           if (($categoryid == $newcat) || ($categoryid == $grandparent)) {
             $subsql="SELECT categoryid, name FROM category WHERE grandparentcategoryid = $categoryid AND categoryid != grandparentcategoryid AND categoryid = parentcategoryid AND (userid LIKE '$catuser'$condition) ORDER BY ordernumber";
             $subresult = @mysqli_query($db, $subsql);
             for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
                $subcategoryname = @mysqli_result($subresult, $j, "name");
                $subcategoryid = @mysqli_result($subresult, $j, "categoryid");
			    if ($subcategoryid == $newcat) $cellcolor = "#F0F0F0";
 		        else $cellcolor = "#D0D0D0";
                echo "<tr><td style=\"border: 1px solid #999; padding-left: 50px;\" bgcolor=\"$cellcolor\"><table><tr><td><li><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"moveproduct.php?move=true&cat=$cat&newcat=$subcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\">$subcategoryname</a></font></td></tr><tr><td align=\"center\"><form action=\"moveproduct.php?cat=$cat&newcat=$subcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\" method=\"post\" style=\"margin-bottom: 0px;\">";
				if ((!in_array($subcategoryid,$incategories) || $userid == "1") && $subcategoryid != $originalcategoryid) echo "<input type=\"submit\" name=\"moved\" value=\"".MOVEHERE."\"> <input type=\"submit\" name=\"copied\" value=\"".COPYHERE."\">";
				if (in_array($subcategoryid,$incategories) && $subcategoryid != $originalcategoryid) echo " <input type=\"submit\" name=\"removed\" value=\"".REMOVEFROMHERE."\" class=\"widebutton\">";
				echo "</form></td></tr></table></td></tr>";
				if (($subcategoryid == $newcat) || ($subcategoryid == $parent)) {
					$subsubsql="SELECT categoryid, name FROM category WHERE parentcategoryid = $subcategoryid AND categoryid != parentcategoryid AND (userid LIKE '$catuser'$condition) ORDER BY ordernumber";
					$subsubresult = @mysqli_query($db, $subsubsql);
					for ($k = 0; $k < @mysqli_num_rows($subsubresult); $k++) {
						$subsubcategoryname = @mysqli_result($subsubresult, $k, "name");
						$subsubcategoryid = @mysqli_result($subsubresult, $k, "categoryid");
						if ($subsubcategoryid == $newcat) $cellcolor = "#F0F0F0";
						else $cellcolor = "#D0D0D0";
						echo "<tr><td bgcolor=\"$cellcolor\"><table><tr><td width=\"20\">&nbsp;</td><td><li><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"moveproduct.php?move=true&cat=$cat&newcat=$subsubcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\">$subsubcategoryname</a></font></td></tr><tr><td>&nbsp;</td><td align=\"center\"><form action=\"moveproduct.php?cat=$cat&newcat=$subsubcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\" method=\"post\" style=\"margin-bottom: 0px;\">";
						if ((!in_array($subsubcategoryid,$incategories) || $userid == "1") && $subsubcategoryid != $originalcategoryid) echo "<input type=\"submit\" name=\"moved\" value=\"".MOVEHERE."\"> <input type=\"submit\" name=\"copied\" value=\"".COPYHERE."\">";
						if (in_array($subsubcategoryid,$incategories) && $subsubcategoryid != $originalcategoryid) echo " <input type=\"submit\" name=\"removed\" value=\"".REMOVEFROMHERE."\" class=\"widebutton\">";
						echo "</form></td></tr></table></td></tr>";
					}
				}
             }
           }
        }
		if (is_numeric($_COOKIE["catmemberid"])) {
			$catuser = $_COOKIE["catmemberid"];
			echo "<tr><td bgcolor=\"#FFFFFF\"><font face=\"Arial, Helvetica, sans-serif\" size=\"3\"><b>".MEMBERSHOP.":</b></font></td></tr>";
			$sql="SELECT categoryid, name FROM category WHERE userid LIKE '$catuser' AND grandparentcategoryid = categoryid ORDER BY ordernumber";
			$result = @mysqli_query($db, $sql);
			for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
				$categoryname = @mysqli_result($result, $i, "name");
				$categoryid = @mysqli_result($result, $i, "categoryid");
				if ($categoryid == $newcat) $cellcolor = "#F0F0F0";
				else $cellcolor = "#D0D0D0";
				echo "<tr><td style=\"border: 1px solid;\" bgcolor=\"$cellcolor\"><table><tr><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"moveproduct.php?move=true&cat=$cat&newcat=$categoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\">$categoryname</a></font></td></tr><tr><td align=\"left\"><form action=\"moveproduct.php?cat=$cat&newcat=$categoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\" method=\"post\" style=\"margin-bottom: 0px;\">";
				if ((!in_array($categoryid,$incategories) || $userid == "1") && $categoryid != $originalcategoryid) echo "<input type=\"submit\" name=\"moved\" value=\"".MOVEHERE."\"> <input type=\"submit\" name=\"copied\" value=\"".COPYHERE."\">";
				if (in_array($categoryid,$incategories) && $categoryid != $originalcategoryid) echo " <input type=\"submit\" name=\"removed\" value=\"".REMOVEFROMHERE."\" class=\"widebutton\">";
				echo "</form></td></tr></table></td></tr>";
				if ($categoryid == $newcat || $categoryid == $grandparent) {
					$subsql="SELECT categoryid, name FROM category WHERE grandparentcategoryid = $categoryid AND categoryid != grandparentcategoryid AND categoryid = parentcategoryid AND userid LIKE '$catuser' ORDER BY ordernumber";
					$subresult = @mysqli_query($db, $subsql);
					for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
						$subcategoryname = @mysqli_result($subresult, $j, "name");
						$subcategoryid = @mysqli_result($subresult, $j, "categoryid");
						if ($subcategoryid == $newcat) $cellcolor = "#F0F0F0";
						else $cellcolor = "#D0D0D0";
						echo "<tr><td bgcolor=\"$cellcolor\"><table><tr><td><li><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"moveproduct.php?move=true&cat=$cat&newcat=$subcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\">$subcategoryname</a></font></td></tr><tr><td align=\"center\"><form action=\"moveproduct.php?cat=$cat&newcat=$subcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\" method=\"post\" style=\"margin-bottom: 0px;\">";
						if ((!in_array($subcategoryid,$incategories) || $userid == "1") && $subcategoryid != $originalcategoryid) echo "<input type=\"submit\" name=\"moved\" value=\"".MOVEHERE."\"> <input type=\"submit\" name=\"copied\" value=\"".COPYHERE."\">";
						if (in_array($subcategoryid,$incategories) && $subcategoryid != $originalcategoryid) echo " <input type=\"submit\" name=\"removed\" value=\"".REMOVEFROMHERE."\" class=\"widebutton\">";
						echo "</form></td></tr></table></td></tr>";
						if (($subcategoryid == $newcat) || ($subcategoryid == $parent)) {
							$subsubsql="SELECT categoryid, name FROM category WHERE parentcategoryid = $subcategoryid AND categoryid != parentcategoryid AND userid LIKE '$catuser' ORDER BY ordernumber";
							$subsubresult = @mysqli_query($db, $subsubsql);
							for ($k = 0; $k < @mysqli_num_rows($subsubresult); $k++) {
								$subsubcategoryname = @mysqli_result($subsubresult, $k, "name");
								$subsubcategoryid = @mysqli_result($subsubresult, $k, "categoryid");
								if ($subsubcategoryid == $newcat) $cellcolor = "#F0F0F0";
								else $cellcolor = "#D0D0D0";
								echo "<tr><td bgcolor=\"$cellcolor\"><table><tr><td width=\"20\">&nbsp;</td><td><li><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><a href=\"moveproduct.php?move=true&cat=$cat&newcat=$subsubcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\">$subsubcategoryname</a></font></td></tr><tr><td>&nbsp;</td><td align=\"center\"><form action=\"moveproduct.php?cat=$cat&newcat=$subsubcategoryid&productid=$productid&pid=$pid&resultpage=$resultpage&search=$search\" method=\"post\" style=\"margin-bottom: 0px;\">";
								if ((!in_array($subsubcategoryid,$incategories) || $userid == "1") && $subsubcategoryid != $originalcategoryid) echo "<input type=\"submit\" name=\"moved\" value=\"".MOVEHERE."\"> <input type=\"submit\" name=\"copied\" value=\"".COPYHERE."\">";
								if (in_array($subsubcategoryid,$incategories) && $subsubcategoryid != $originalcategoryid) echo " <input type=\"submit\" name=\"removed\" value=\"".REMOVEFROMHERE."\" class=\"widebutton\">";
								echo "</form></td></tr></table></td></tr>";
							}
						}
					}
				}
			}
        }
        echo "<tr><td align=\"right\"><input type=\"button\" value=\"".CANCEL."\" onClick=\"document.location.href='editcatalogue.php?cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"></td></tr></table></p></td></tr></table>$footer";
}

elseif ($moved) {
	$result = @mysqli_query($db, "SELECT * FROM productcategory WHERE productid='$productid' AND categoryid='$newcat'");
	if (!@mysqli_num_rows($result)) @mysqli_query($db, "UPDATE productcategory SET categoryid='$newcat' WHERE productid='$productid' AND categoryid='$cat'");
    header ("Location: editcatalogue.php?cat=$newcat&pid=$pid&resultpage=$resultpage&search=$search");
}

else if ($copied) {

	// Make a complete copy of the product...
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	if (@mysqli_num_rows($result)) $row = @mysqli_fetch_array($result, MYSQL_ASSOC);
	if (is_array($row)) {
		$copyof = $row["copyof"];
		if (!$copyof) $copyof = $productid;
		$sql = "INSERT INTO product ";
		$fieldlist = "(copyof, ";
		$valuelist = "('$copyof', ";
		foreach ($row as $fieldname=>$fieldvalue) {
			if (!get_magic_quotes_runtime()) $fieldvalue = addslashes($fieldvalue);
			if ($fieldname != "productid" && $fieldname != "copyof") {
				$fieldlist .= "$fieldname, ";
				$valuelist .= "'$fieldvalue', ";
			}
		}
		$fieldlist = substr($fieldlist,0,-2);
		$valuelist = substr($valuelist,0,-2);
		$fieldlist .= ")";
		$valuelist .= ")";
		$sql .= "$fieldlist VALUES $valuelist";
		@mysqli_query($db, $sql);
		$newproductid = @mysqli_insert_id($db);
		@mysqli_query($db, "UPDATE product SET ordernumber='$newproductid' WHERE productid='$newproductid'");
	}

	if ($newproductid) {
		// Add the copied product to the category and make sure it will be displayed there...
		@mysqli_query($db, "INSERT INTO productcategory (productid, categoryid) VALUES ('$newproductid','$newcat')");
		$result = @mysqli_query($db, "SELECT userid FROM category WHERE categoryid='$newcat'");
		$newcatowner = @mysqli_result($result, 0, "userid");
		if ($newcatowner == "1") @mysqli_query($db, "UPDATE product SET inmainshop='1' WHERE productid='$newproductid' OR productid='$productid'");

		// Copy quantity price settings...
		$result = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			$row = @mysqli_fetch_array($result);
			@mysqli_query($db, "INSERT INTO qtypricelevels (levelprice, levelquantity, productid) VALUES ('{$row["levelprice"]}', '{$row["levelquantity"]}', '$newproductid')");
		}

		// Copy product inventory...
		$result = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			$row = @mysqli_fetch_array($result);
			@mysqli_query($db, "INSERT INTO productinventory (type, skucode, inventory, productid) VALUES ('{$row["type"]}', '{$row["skucode"]}', '{$row["inventory"]}', '$newproductid')");
		}

		// Copy product file records...
		$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO productfiles (fileid, filename, url, productid) VALUES ('{$row["fileid"]}', '{$row["filename"]}', '{$row["url"]}', '$newproductid')");
				$newfileid = @mysqli_insert_id($db);
				@mysqli_query($db, "UPDATE productfiles SET ordernumber='$newfileid' WHERE id='$newfileid'");
			}
		}

		// Copy product discount...
		$result = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO discount (code, value, type, onetime, productid, affiliate, customerid) VALUES ('{$row["code"]}', '{$row["value"]}', '{$row["type"]}', '{$row["onetime"]}', '$newproductid', '{$row["affiliate"]}', '{$row["customerid"]}')");
			}
		}

		// Copy product shipping packages...
		$result = @mysqli_query($db, "SELECT * FROM packages WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO packages (originzip, origincountry, originstate, weight, freightclass, productid) VALUES ('{$row["originzip"]}', '{$row["origincountry"]}', '{$row["originstate"]}', '{$row["weight"]}', '{$row["freightclass"]}', '$newproductid')");
			}
		}

		// Copy product shipping zone rates...
		$result = @mysqli_query($db, "SELECT * FROM zonerates WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO zonerates (zone, rate, productid) VALUES ('{$row["zone"]}', '{$row["rate"]}', '$newproductid')");
			}
		}

		// Copy product quantity price rates...
		$result = @mysqli_query($db, "SELECT * FROM quantityrates WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO quantityrates (quantity, rate, productid) VALUES ('{$row["quantity"]}', '{$row["rate"]}', '$newproductid')");
			}
		}

		// Copy product attributes and options...
		$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO parameters (caption, buybuttons, productid) VALUES ('{$row["caption"]}', '{$row["buybuttons"]}', '$newproductid')");
				$newparameterid = @mysqli_insert_id($db);
				$result2 = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='{$row["parameterid"]}'");
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

		// Copy attribute specific inventory...
		$result = @mysqli_query($db, "SELECT * FROM productinventory WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				$oldtype = $row["type"];
				$newtype = "";
				if (strpos($oldtype, "|")) {
					$maptypes = explode("|",$oldtype);
					foreach($maptypes as $valuenumber=>$valueid) $newtype .= $parametervalues["$valueid"]."|";
					$newtype = substr($newtype,0,-1);
				} else $newtype = $parametervalues["$oldtype"];
				@mysqli_query($db, "INSERT INTO productinventory (productid, type, skucode, inventory) VALUES ('$newproductid', '$newtype', '{$row["skucode"]}', '{$row["inventory"]}')");
			}
		}

		// Copy product floating price...
		$result = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			$row = @mysqli_fetch_array($result);
			@mysqli_query($db, "INSERT INTO floatingprice (length, activatetime, starttime, startprice, priceincrement, productid) VALUES ('{$row["length"]}', '{$row["activatetime"]}', '{$row["starttime"]}', '{$row["startprice"]}', '{$row["priceincrement"]}', '$newproductid')");
		}

		// Copy related products...
		$result = @mysqli_query($db, "SELECT * FROM relatedproducts WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO relatedproducts (relatedproductid, priority, productid) VALUES ('{$row["relatedproductid"]}', '{$row["priority"]}', '$newproductid')");
			}
		}

		// Copy flags...
		$result = @mysqli_query($db, "SELECT * FROM flagvalues WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) {
			while ($row = @mysqli_fetch_array($result)) {
				@mysqli_query($db, "INSERT INTO flagvalues (flagid, productid) VALUES ('{$row["flagid"]}', '$newproductid')");
			}
		}

		// Copy image files...
		if (file_exists("$ashoppath/prodimg/$productid.gif")) copy ("$ashoppath/prodimg/$productid.gif","$ashoppath/prodimg/$newproductid.gif");
		if (file_exists("$ashoppath/prodimg/$productid.jpg")) copy ("$ashoppath/prodimg/$productid.jpg","$ashoppath/prodimg/$newproductid.jpg");
		if (file_exists("$ashoppath/prodimg/b$productid.gif")) copy ("$ashoppath/prodimg/b$productid.gif","$ashoppath/prodimg/b$newproductid.gif");
		if (file_exists("$ashoppath/prodimg/b$productid.jpg")) copy ("$ashoppath/prodimg/b$productid.jpg","$ashoppath/prodimg/b$newproductid.jpg");
		$lastimage = FALSE;
		$imagenumber = 1;
		while (!$lastimage) {
			if (file_exists("$ashoppath/prodimg/{$productid}_$imagenumber.gif")) {
				copy ("$ashoppath/prodimg/{$productid}_$imagenumber.gif","$ashoppath/prodimg/{$newproductid}_$imagenumber.gif");
				if (file_exists("$ashoppath/prodimg/b{$productid}_$imagenumber.gif")) copy ("$ashoppath/prodimg/b{$productid}_$imagenumber.gif","$ashoppath/prodimg/b{$newproductid}_$imagenumber.gif");
				$imagenumber++;
			} else if (file_exists("$ashoppath/prodimg/{$productid}_$imagenumber.jpg")) {
				copy ("$ashoppath/prodimg/{$productid}_$imagenumber.jpg","$ashoppath/prodimg/{$newproductid}_$imagenumber.jpg");
				if (file_exists("$ashoppath/prodimg/b{$productid}_$imagenumber.jpg")) copy ("$ashoppath/prodimg/b{$productid}_$imagenumber.jpg","$ashoppath/prodimg/b{$newproductid}_$imagenumber.jpg");
				$imagenumber++;
			} else $lastimage = TRUE;
		}
	}
	header ("Location: editcatalogue.php?cat=$newcat&resultpage=$resultpage");
}

elseif ($removed) {
	$result = @mysqli_query($db, "SELECT productid FROM product WHERE productid='$productid' OR copyof='$productcopyof' OR copyof='$productid'");
	while ($row = @mysqli_fetch_array($result)) if (@mysqli_num_rows($result)) @mysqli_query($db, "DELETE FROM productcategory WHERE productid='{$row["productid"]}' AND categoryid='$newcat'");
    header ("Location: editcatalogue.php?cat=$newcat&resultpage=$resultpage");
}
?>