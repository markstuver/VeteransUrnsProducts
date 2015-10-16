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

@set_time_limit(0);
include "config.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";
include "ashopconstants.inc.php";

// Check for GD...
ob_start();
phpinfo(8);
$phpinfo=ob_get_contents();
ob_end_clean();
$phpinfo=strip_tags($phpinfo);
$phpinfo=stristr($phpinfo,"gd version");
$phpinfo=stristr($phpinfo,"version"); 
$end=strpos($phpinfo,"\n");
$phpinfo=substr($phpinfo,0,$end);
preg_match ("/[0-9]/", $phpinfo, $version);
if(isset($version[0]) && $version[0]>1) $gdversion = 2;
else $gdversion = 0;

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Report import status...
if ($action == "getprogress") {
	$result = @mysqli_query($db, "SELECT message FROM notification WHERE type='productimport'");
	echo @mysqli_result($result,0,"message");
	exit;
}

if ($userid == "1") {
	$result = @mysqli_query($db, "SELECT userid FROM category WHERE categoryid='$cat'");
	$memberid = @mysqli_result($result, 0, "userid");
	if (empty($memberid)) $memberid = "1";
} else $memberid = $userid;

// Import product data from file...
$fieldnr = array();
$numberofcategories = 0;
$numberofwslevels = 0;
$numberofproductfiles = 0;
$numberofalternatives = 0;
$numberofdownloadalternatives = 0;
$importfile = str_replace("\t","\\t",$importfile);
if (!empty($importfile) && is_uploaded_file($importfile)) {
	$importfilesize = filesize($importfile);
	$enclosure = stripslashes($enclosure);
	$delimiter = stripslashes($delimiter);
	$progresspercent = 0;
	if (@move_uploaded_file($importfile, "$ashopspath/products/import")) {
		$fp = fopen ("$ashopspath/products/import","r");
		if ($fp) {
			// Create notification...
			$checknotification = @mysqli_query($db, "SELECT * FROM notification WHERE type='productimport'");
			if (!@mysqli_num_rows($checknotification)) @mysqli_query($db, "INSERT INTO notification (type,message) VALUES ('productimport','Starting product import.')");
			else @mysqli_query($db, "UPDATE notification SET message='Starting product import.' WHERE type='productimport'");
			$csvline = 0;
			while (!feof ($fp)) {
				unset($productinfo);
				$productinfo = fgetcsv($fp, 4096, $delimiter, $enclosure);
				$measure = "";
				if (is_array($productinfo)) foreach ($productinfo as $productinfofield) $measure .= $productinfofield;
				$measure = strlen($measure);
				$progresspercent += ($measure/$importfilesize)*100;

				// Get field order from first line of CSV file...
				if ($csvline == 0) {
					unset($fieldnr["category"]);
					unset($fieldnr["wholesaleprice"]);
					unset($fieldnr["wholesaleprice1"]);
					unset($fieldnr["productfile"]);
					unset($fieldnr["skucode"]);
					unset($fieldnr["productname"]);
					unset($fieldnr["inventory"]);
					unset($fieldnr["description"]);
					unset($fieldnr["price"]);
					unset($fieldnr["weight"]);
					unset($fieldnr["shipping"]);
					unset($fieldnr["tax"]);
					unset($fieldnr["productimage"]);
					unset($fieldnr["watermark"]);
					unset($fieldnr["attribute"]);

					foreach ($productinfo as $pifieldnr=>$pifieldvalue) {
						$pifieldvalue = strtoupper($pifieldvalue);
						if (strstr($pifieldvalue,"CATEGORY") || strstr($pifieldvalue,"CATEGORY NAME") || strstr($pifieldvalue,"PRODUCT CATEGORY")) {
							$categorynumber = str_replace("CATEGORY NAME","",$pifieldvalue);
							$categorynumber = str_replace("PRODUCT CATEGORY","",$categorynumber);
							$categorynumber = str_replace("CATEGORY","",$categorynumber);
							$categorynumber = trim($categorynumber);
							if (is_numeric($categorynumber)) $fieldnr["category$categorynumber"] = $pifieldnr;
							else $fieldnr["category"] = $pifieldnr;
							$numberofcategories++;
						}
						if (strstr($pifieldvalue,"WHOLESALE") || strstr($pifieldvalue,"WHOLESALE PRICE") || strstr($pifieldvalue,"WHOLESALE LEVEL") || strstr($pifieldvalue,"WHOLESALE PRICE LEVEL")) {
							$wslevelnumber = str_replace("WHOLESALE PRICE LEVEL","",$pifieldvalue);
							$wslevelnumber = str_replace("WHOLESALE PRICE","",$wslevelnumber);
							$wslevelnumber = str_replace("WHOLESALE LEVEL","",$wslevelnumber);
							$wslevelnumber = str_replace("WHOLESALE","",$wslevelnumber);
							$wslevelnumber = trim($wslevelnumber);
							if (is_numeric($wslevelnumber)) $fieldnr["wholesaleprice$wslevelnumber"] = $pifieldnr;
							else $fieldnr["wholesaleprice"] = $pifieldnr;
							$numberofwslevels++;
						}
						if (strstr($pifieldvalue,"PRODUCT FILE") || strstr($pifieldvalue,"FILE") || strstr($pifieldvalue,"DOWNLOAD")) {
							$productfilenumber = str_replace("PRODUCT FILE","",$pifieldvalue);
							$productfilenumber = str_replace("FILE","",$productfilenumber);
							$productfilenumber = str_replace("DOWNLOAD","",$productfilenumber);
							$productfilenumber = trim($productfilenumber);
							if (is_numeric($productfilenumber)) $fieldnr["productfile$productfilenumber"] = $pifieldnr;
							else $fieldnr["productfile"] = $pifieldnr;
							$numberofproductfiles++;
						}
						if (strstr($pifieldvalue,"DOWNLOADABLE ALTERNATIVE") || strstr($pifieldvalue,"DOWNLOADABLE OPTION") || strstr($pifieldvalue,"FILE ALTERNATIVE") || strstr($pifieldvalue,"FILE OPTION")) {
							$alternativenumber = str_replace("DOWNLOADABLE ALTERNATIVE","",$pifieldvalue);
							$alternativenumber = str_replace("DOWNLOADABLE OPTION","",$alternativenumber);
							$alternativenumber = str_replace("FILE ALTERNATIVE","",$alternativenumber);
							$alternativenumber = str_replace("FILE OPTION","",$alternativenumber);
							$alternativenumber = trim($alternativenumber);
							if (is_numeric($alternativenumber)) {
								$fieldnr["downloadalternative$alternativenumber"] = $pifieldnr;
								$numberofdownloadalternatives++;
							}
						} else if ((strstr($pifieldvalue,"ALTERNATIVE") && !strstr($pifieldvalue,"ALTERNATIVE PRICE")) || strstr($pifieldvalue,"OPTION")) {
							$alternativenumber = str_replace("ALTERNATIVE","",$pifieldvalue);
							$alternativenumber = str_replace("OPTION","",$alternativenumber);
							$alternativenumber = trim($alternativenumber);
							if (is_numeric($alternativenumber)) {
								$fieldnr["alternative$alternativenumber"] = $pifieldnr;
								$numberofalternatives++;
							}
						}
						if (strstr($pifieldvalue,"ALTERNATIVE PRICE") || strstr($pifieldvalue,"OPTION PRICE")) {
							$alternativenumber = str_replace("ALTERNATIVE PRICE","",$pifieldvalue);
							$alternativenumber = str_replace("OPTION PRICE","",$alternativenumber);
							$alternativenumber = trim($alternativenumber);
							if (is_numeric($alternativenumber)) $fieldnr["alternativeprice$alternativenumber"] = $pifieldnr;
						}
						switch ($pifieldvalue) {
							case "SKU":
								$fieldnr["skucode"] = $pifieldnr;
								break;
							case "SKU CODE":
								$fieldnr["skucode"] = $pifieldnr;
								break;
							case "SKUCODE":
								$fieldnr["skucode"] = $pifieldnr;
								break;
							case "ART NR":
								$fieldnr["skucode"] = $pifieldnr;
								break;
							case "ARTNR":
								$fieldnr["skucode"] = $pifieldnr;
								break;
							case "NAME":
								$fieldnr["productname"] = $pifieldnr;
								break;
							case "PRODUCT":
								$fieldnr["productname"] = $pifieldnr;
								break;
							case "PRODUCT NAME":
								$fieldnr["productname"] = $pifieldnr;
								break;
							case "PRODUCTNAME":
								$fieldnr["productname"] = $pifieldnr;
								break;
							case "INVENTORY":
								$fieldnr["inventory"] = $pifieldnr;
								break;
							case "STOCK":
								$fieldnr["inventory"] = $pifieldnr;
								break;
							case "ITEMS":
								$fieldnr["inventory"] = $pifieldnr;
								break;
							case "DESCRIPTION":
								$fieldnr["description"] = $pifieldnr;
								break;
							case "PRICE":
								$fieldnr["price"] = $pifieldnr;
								break;
							case "RETAIL PRICE":
								$fieldnr["price"] = $pifieldnr;
								break;
							case "RETAIL":
								$fieldnr["price"] = $pifieldnr;
								break;
							case "BASE PRICE":
								$fieldnr["cost"] = $pifieldnr;
								break;
							case "COST":
								$fieldnr["cost"] = $pifieldnr;
								break;
							case "VENDOR COST":
								$fieldnr["cost"] = $pifieldnr;
								break;
							case "WEIGHT":
								$fieldnr["weight"] = $pifieldnr;
								break;
							case "SHIPPING":
								$fieldnr["shipping"] = $pifieldnr;
								break;
							case "SHIPPING COST":
								$fieldnr["shipping"] = $pifieldnr;
								break;
							case "SHIPPING FEE":
								$fieldnr["shipping"] = $pifieldnr;
								break;
							case "SHIPPING CHARGE":
								$fieldnr["shipping"] = $pifieldnr;
								break;
							case "TAX":
								$fieldnr["tax"] = $pifieldnr;
								break;
							case "SALES TAX":
								$fieldnr["tax"] = $pifieldnr;
								break;
							case "VAT":
								$fieldnr["tax"] = $pifieldnr;
								break;
							case "PRODUCT IMAGE":
								$fieldnr["productimage"] = $pifieldnr;
								break;
							case "THUMBNAIL":
								$fieldnr["productimage"] = $pifieldnr;
								break;
							case "IMAGE":
								$fieldnr["productimage"] = $pifieldnr;
								break;
							case "PICTURE":
								$fieldnr["productimage"] = $pifieldnr;
								break;
							case "WATERMARK":
								$fieldnr["watermark"] = $pifieldnr;
								break;
							case "ATTRIBUTE":
								$fieldnr["attribute"] = $pifieldnr;
								break;
							case "ATTRIBUTE NAME":
								$fieldnr["attribute"] = $pifieldnr;
								break;
							case "ZIP":
								$fieldnr["zip"] = $pifieldnr;
								break;
							case "ZIP FILE":
								$fieldnr["zip"] = $pifieldnr;
								break;
							case "ZIP FILES":
								$fieldnr["zip"] = $pifieldnr;
								break;
							case "ZIP PRODUCT FILE":
								$fieldnr["zip"] = $pifieldnr;
								break;
							case "ZIP PRODUCT FILES":
								$fieldnr["zip"] = $pifieldnr;
								break;
						}
					}
					$csvline++;
					continue;
				}

				// Skip empty lines...
				if (empty($productinfo)) continue;

				// Make sure the information can be stored in the database...
				foreach ($productinfo as $pifieldnr=>$pifieldvalue) $productinfo[$pifieldnr] = str_replace("'","\'",$pifieldvalue);

				// Store categories in an array...
				$categorynames = array();
				if ($numberofcategories == 1 && isset($fieldnr["category"])) $categorynames[0] = $productinfo[$fieldnr["category"]];
				else for ($categorynumber = 0; $categorynumber <= $numberofcategories; $categorynumber++) {
					$thiscategorynumber = $categorynumber+1;
					if (isset($fieldnr["category$thiscategorynumber"])) $categorynames[$categorynumber] = $productinfo[$fieldnr["category$thiscategorynumber"]];
					else unset($categorynames[$categorynumber]);
				}

				// Store product file names in an array...
				$productfilenames = array();
				if ($numberofproductfiles == 1 && isset($fieldnr["productfile"])) $productfilenames[0] = $productinfo[$fieldnr["productfile"]];
				else for ($productfilenumber = 0; $productfilenumber <= $numberofproductfiles; $productfilenumber++) {
					$thisproductfilenumber = $productfilenumber+1;
					if (isset($fieldnr["productfile$thisproductfilenumber"])) $productfilenames[$productfilenumber] = $productinfo[$fieldnr["productfile$thisproductfilenumber"]];
					else unset($productfilenames[$productfilenumber]);
				}

				// Get the SKU code, if any...
				if (isset($fieldnr["skucode"])) $skucode = substr($productinfo[$fieldnr["skucode"]],0,25);
				else $skucode = "";

				// Get the number of items in inventory, if any...
				if (isset($fieldnr["inventory"])) $inventory = $productinfo[$fieldnr["inventory"]];
				else $inventory = "";

				// Get the name of the product...
				if (isset($fieldnr["productname"])) $name = $productinfo[$fieldnr["productname"]];
				else $name = "";

				// Register notification...
				$notifypercent = round($progresspercent);
				if (!empty($name)) @mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: $name.' WHERE type='productimport'");

				// Get the product description...
				if (isset($fieldnr["description"])) $description = $productinfo[$fieldnr["description"]];
				else $description = "";

				// Get the vendor cost...
				if (isset($fieldnr["cost"])) $cost = $productinfo[$fieldnr["cost"]];
				else $cost = "";

				// Get the retail and wholesale price for the product...
				if (isset($fieldnr["price"])) {
					if (strstr($productinfo[$fieldnr["price"]],".")) $price = str_replace(",","",$productinfo[$fieldnr["price"]]);
					else $price = str_replace(",",".",$productinfo[$fieldnr["price"]]);
				} else $price = "";
				if ($wholesalecatalog) {
					if (isset($fieldnr["wholesaleprice"])) $wholesaleprice = $productinfo[$fieldnr["wholesaleprice"]];
					else if (isset($fieldnr["wholesaleprice1"])) $wholesaleprice = $productinfo[$fieldnr["wholesaleprice1"]];
					else $wholesaleprice = "";
					if (strstr($wholesaleprice,".")) $wholesaleprice = str_replace(",","",$wholesaleprice);
					else $wholesaleprice = str_replace(",",".",$wholesaleprice);
					$wspricelevels = "";
					if ($numberofwslevels > 1) for ($pricelevel = 2; $pricelevel<=$numberofwslevels; $pricelevel++) {
						$thispricelevel = $productinfo[$fieldnr["wholesaleprice$pricelevel"]];
						if (strstr($thispricelevel,".")) $thispricelevel = str_replace(",","",$thispricelevel);
						else $thispricelevel = str_replace(",",".",$thispricelevel);
						if (empty($thispricelevel)) $thispricelevel = $wholesaleprice;
						$wspricelevels .= $thispricelevel."|";
					}
					if (!empty($wspricelevels)) $wspricelevels = substr($wspricelevels,0,-1);
					$nextfield = $nextfield+$pricelevels;
					$wholesaleactive = "1";
				} else {
					$wholesaleprice = "";
					$wspricelevels = "";
					$wholesaleactive = "0";
				}
				if (isset($fieldnr["weight"])) $weight = str_replace(",",".",$productinfo[$fieldnr["weight"]]);
				else $weight = "";
				if (isset($fieldnr["shipping"])) $shipping = str_replace(",",".",$productinfo[$fieldnr["shipping"]]);
				else $shipping = "";
				if (isset($fieldnr["tax"])) $tax = $productinfo[$fieldnr["tax"]];
				else $tax = 0;
				if ($tax != "1" && $tax != "2") $tax = "0";
				if (isset($fieldnr["productfile"])) $prodfile = $productinfo[$fieldnr["productfile"]];
				else $prodfile = "";
				if (isset($fieldnr["productimage"])) $prodimg = $productinfo[$fieldnr["productimage"]];
				else $prodimg = "";
				if (isset($fieldnr["watermark"])) $watermark = $productinfo[$fieldnr["watermark"]];
				else $watermark = "";
				if (isset($fieldnr["attribute"])) $attribute = $productinfo[$fieldnr["attribute"]];
				else $attribute = "";
				if (isset($fieldnr["zip"])) $zip = $productinfo[$fieldnr["zip"]];
				else $zip = "";
				if (!empty($skucode)) {
					if (!empty($inventory) && is_numeric($inventory)) $useinventory = "'1'";
					else $useinventory = "NULL";
					$checkexists = @mysqli_query($db, "SELECT productid FROM product WHERE skucode='$skucode'");
				} else {
					$useinventory = "NULL";
					$checkexists = @mysqli_query($db, "SELECT productid FROM product WHERE name='$name' AND userid='$memberid'");
				}
				if (!@mysqli_num_rows($checkexists)) {
					$sql="INSERT INTO product (name, price, wholesaleprice, wspricelevels, description, userid, active, wholesaleactive, shipping, intshipping, taxable, useinventory, inventory, skucode, weight, cost) VALUES ('$name','$price','$wholesaleprice','$wspricelevels','$description','$memberid', '1', '$wholesaleactive', '$shipping', '$shipping', '$tax', $useinventory, '$inventory', '$skucode', '$weight', '$cost')";
					$result = @mysqli_query($db, $sql);
					$product_id = @mysqli_insert_id($db);
					@mysqli_query($db, "UPDATE product SET ordernumber='$product_id' WHERE productid='$product_id'");
					//if (!empty($weight)) @mysqli_query($db, "INSERT INTO packages (productid,weight) VALUES ('$product_id','$weight')");
				} else {
					$product_id = @mysqli_result($checkexists,0,"productid");
					if (!empty($skucode)) @mysqli_query($db, "UPDATE product SET name='$name', price='$price', wholesaleprice='$wholesaleprice', wspricelevels='$wspricelevels', description='$description', userid='$memberid', shipping='$shipping', taxable='$tax', inventory='$inventory', weight='$weight', cost='$cost' WHERE skucode='$skucode'");
					else @mysqli_query($db, "UPDATE product SET price='$price', wholesaleprice='$wholesaleprice', wspricelevels='$wspricelevels', description='$description', shipping='$shipping', taxable='$tax', inventory='$inventory', weight='$weight', skucode='$skucode', cost='$cost' WHERE name='$name' AND userid='$memberid'");
				}

				// Add the product to the selected categories...
				$catid = $cat;
				$addedtocategories = 0;
				if (!empty($categorynames) && is_array($categorynames)) {
					foreach ($categorynames as $categoryname) {
						$grandparentcategoryname = "";
						$parentcategoryname = "";
						if (!empty($categoryname)) {
							$categorytree = explode("->",$categoryname);
							if (!empty($categorytree[0])) {
								if (!empty($categorytree[1]) && !empty($categorytree[2])) $grandparentcategoryname = $categorytree[0];
								else if (!empty($categorytree[1])) $parentcategoryname = $categorytree[0];
								else $categoryname = $categorytree[0];
							}
							if (!empty($categorytree[1])) {
								if (!empty($categorytree[2])) $parentcategoryname = $categorytree[1];
								else $categoryname = $categorytree[1];
							}
							if (!empty($categorytree[2])) $categoryname = $categorytree[2];
							$grandparentcategoryid = "";
							$parentcategoryid = "";
							if (!empty($grandparentcategoryname)) {
								// Create or get the top level category...
								$categoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE name='$grandparentcategoryname' ORDER BY ordernumber ASC LIMIT 1");
								if (@mysqli_num_rows($categoryresult)) $grandparentcategoryid = @mysqli_result($categoryresult,0,"categoryid");
								else {
									@mysqli_query($db, "INSERT INTO category (userid, language, name) VALUES ('$memberid','any','$grandparentcategoryname')");
									$grandparentcategoryid = @mysqli_insert_id($db);
									@mysqli_query($db, "UPDATE category SET grandparentcategoryid='$grandparentcategoryid', parentcategoryid='$grandparentcategoryid', ordernumber='$grandparentcategoryid' WHERE categoryid='$grandparentcategoryid'");
								}
								// Create or get the second level category...
								$categoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE name='$parentcategoryname' AND grandparentcategoryid='$grandparentcategoryid' ORDER BY ordernumber ASC LIMIT 1");
								if (@mysqli_num_rows($categoryresult)) $parentcategoryid = @mysqli_result($categoryresult,0,"categoryid");
								else {
									@mysqli_query($db, "INSERT INTO category (userid, language, name) VALUES ('$memberid','any','$parentcategoryname')");
									$parentcategoryid = @mysqli_insert_id($db);
									@mysqli_query($db, "UPDATE category SET grandparentcategoryid='$grandparentcategoryid', parentcategoryid='$parentcategoryid', ordernumber='$parentcategoryid' WHERE categoryid='$parentcategoryid'");
								}
							} else if (!empty($parentcategoryname)) {
								// Create or get the top level category...
								$categoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE name='$parentcategoryname' ORDER BY ordernumber ASC LIMIT 1");
								if (@mysqli_num_rows($categoryresult)) $grandparentcategoryid = @mysqli_result($categoryresult,0,"categoryid");
								else {
									@mysqli_query($db, "INSERT INTO category (userid, language, name) VALUES ('$memberid','any','$parentcategoryname')");
									$grandparentcategoryid = @mysqli_insert_id($db);
									@mysqli_query($db, "UPDATE category SET grandparentcategoryid='$grandparentcategoryid', parentcategoryid='$grandparentcategoryid', ordernumber='$grandparentcategoryid' WHERE categoryid='$grandparentcategoryid'");
								}
							}

							// Create or get the category...
							if (!empty($categoryname)) {
								if (!empty($grandparentcategoryid)) {
									if (!empty($parentcategoryid)) $categoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE name='$categoryname' AND grandparentcategoryid='$grandparentcategoryid' AND parentcategoryid='$parentcategoryid' ORDER BY ordernumber ASC LIMIT 1");
									else $categoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE name='$categoryname' AND grandparentcategoryid='$grandparentcategoryid' AND categoryid=parentcategoryid ORDER BY ordernumber ASC LIMIT 1");
								} else $categoryresult = @mysqli_query($db, "SELECT categoryid FROM category WHERE name='$categoryname' ORDER BY ordernumber ASC LIMIT 1");
								if (@mysqli_num_rows($categoryresult)) $newcat = @mysqli_result($categoryresult,0,"categoryid");
								else {
									@mysqli_query($db, "INSERT INTO category (userid, language, name) VALUES ('$memberid','any','$categoryname')");
									$newcat = @mysqli_insert_id($db);
									if (empty($grandparentcategoryid)) $grandparentcategoryid=$newcat;
									if (empty($parentcategoryid)) $parentcategoryid=$newcat;
									@mysqli_query($db, "UPDATE category SET grandparentcategoryid='$grandparentcategoryid', parentcategoryid='$parentcategoryid', ordernumber='$newcat' WHERE categoryid='$newcat'");
								}
								if (!empty($newcat) && is_numeric($newcat)) $catid = $newcat;
								$checkprodcat = @mysqli_query($db, "SELECT categoryid FROM productcategory WHERE productid='$product_id' AND categoryid='$catid'");
								if (!@mysqli_num_rows($checkprodcat)) {
									$sql="INSERT INTO productcategory (productid,categoryid) VALUES ('$product_id','$catid')";
									$result = @mysqli_query($db, $sql);
								}
								$addedtocategories++;
							}
						}
					}
				}
				if (!$addedtocategories) {
					$checkprodcat = @mysqli_query($db, "SELECT categoryid FROM productcategory WHERE productid='$product_id' AND categoryid='$catid'");
					if (!@mysqli_num_rows($checkprodcat)) {
						$sql="INSERT INTO productcategory (productid,categoryid) VALUES ('$product_id','$catid')";
						$result = @mysqli_query($db, $sql);
					}
				}

				// Handle image file...
				if ($prodimg && file_exists("$ashoppath/prodimg/$prodimg")) {
					$imagefilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($prodimg)));
					$imagefilename = preg_replace("/%E5|%E4/","a",$imagefilename);
					$imagefilename = preg_replace("/%F6/","o",$imagefilename);
					$imagefilename = preg_replace("/%C5|%C4/","A",$imagefilename);
					$imagefilename = preg_replace("/%D6/","O",$imagefilename);
					$imagefilename = preg_replace("/\+\+\+|\+\+/","+",$imagefilename);
					$fileinfo = pathinfo("$prodimg");
					$extension = $fileinfo["extension"];
					$imagefilename = str_replace(".$extension","",$imagefilename);
					$extension = strtolower($extension);
					if ($extension == "jpeg") $extension = "jpg";
					if ($extension != "gif" && $extension != "jpg") $error = "extension";
					else {
						// Make sure the product has a subdirectory for its images...
						if (!file_exists("$ashoppath/prodimg/$product_id")) {
							@mkdir("$ashoppath/prodimg/$product_id");
							@chmod("$ashoppath/prodimg/$product_id", 0755);
						}

						// Check for existing files...
						$productimage = ashop_productimages($productid);
						if ($productimage["thumbnail"]) {
							$imagenumber = $productimage["additionalimages"]+1;
							$imagenumberpath = "$imagenumber/";
							if (!file_exists("$ashoppath/prodimg/$product_id/$imagenumber")) {
								@mkdir("$ashoppath/prodimg/$product_id/$imagenumber");
								@chmod("$ashoppath/prodimg/$product_id/$imagenumber", 0755);
							}
						} else $imagenumberpath = "";

						copy("$ashoppath/prodimg/$prodimg", "$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
						@chmod("$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension", 0666);
						copy ("$ashoppath/prodimg/$prodimg","$ashoppath/prodimg/$product_id/{$imagenumberpath}p-$imagefilename.$extension");
						@chmod("$ashoppath/prodimg/$product_id/{$imagenumberpath}p-$imagefilename.$extension", 0666);
						copy ("$ashoppath/prodimg/$prodimg","$ashoppath/prodimg/$product_id/{$imagenumberpath}m-$imagefilename.$extension");
						@chmod("$ashoppath/prodimg/$product_id/{$imagenumberpath}m-$imagefilename.$extension", 0666);
						copy ("$ashoppath/prodimg/$prodimg","$ashoppath/prodimg/$product_id/{$imagenumberpath}t-$imagefilename.$extension");
						@chmod("$ashoppath/prodimg/$product_id/{$imagenumberpath}t-$imagefilename.$extension", 0666);
						
						// Determine the new sizes...
						$imagesize = getimagesize("$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
						$imagesizeratio = $thumbnailwidth/$imagesize[0];
						$thumbnailheight = $imagesize[1]*$imagesizeratio;
						$imagesizeratio = $imagewidth/$imagesize[0];
						$imageheight = $imagewidth-50;
						if($imagesize[0] > $imagesize[1]) {
							$largewidth = 600;
							$imagesizeratio = $largewidth/$imagesize[0];
							$largeheight = $imagesize[1]*$imagesizeratio;
							$miniwidth = 45;
							$imagesizeratio = $miniwidth/$imagesize[0];
							$miniheight = $imagesize[1]*$imagesizeratio;
						} else {
							$largeheight = 500;
							$imagesizeratio = $largeheight/$imagesize[1];
							$largewidth = $imagesize[0]*$imagesizeratio;
							$miniheight = 45;
							$imagesizeratio = $miniheight/$imagesize[1];
							$miniwidth = $imagesize[0]*$imagesizeratio;
						}
						if ($imagesize[1] > $largeheight || $imagesize[0] > $largewidth) $resizeoriginal = TRUE;
						else $resizeoriginal = FALSE;
					}
					if ($watermark) {
						$wfileinfo = pathinfo("$watermark");
						$wextension = $wfileinfo["extension"];
						$wimagefilename = str_replace(".$wextension","",$watermark);
						$wextension = strtolower($wextension);
						if ($wextension == "jpeg") $wextension = "jpg";
						if ($wextension != "gif" && $wextension != "jpg" && $wextension != "png") $watermark = "";
						if (file_exists("$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.$wextension")) unlink ("$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.$wextension");
						copy("$ashoppath/prodimg/$watermark", "$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.$wextension");
					}

					// If GD is available resample the image to fit the size set in layout config...
					if (function_exists('imagecreatefromjpeg') && function_exists('imagecreatefromgif') && function_exists('imagecreatetruecolor') && $gdversion == 2) {
						// Register notification...
						@mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: processing product thumbnail image.' WHERE type='productimport'");
						// Give the server some time to copy the uploaded file to the right location...
						$resampleimage = "$ashoppath/prodimg/$product_id/{$imagenumberpath}t-$imagefilename.$extension";
						$starttime = date("s", time());
						while (!@getimagesize($resampleimage)) {
							$now = date("s", time());
							// Time out if this has taken more than 30 seconds to avoid eternal loops...
							if ($now - $starttime >=30) break;
						}
						if ($extension == "jpg") {
							$src_img = imagecreatefromjpeg($resampleimage);
						} else if ($extension == "gif") {
							$src_img = imagecreatefromgif($resampleimage);
						}
						$quality = 90;
						$src_width = imagesx($src_img);
						$src_height = imagesy($src_img);
						$dest_ar = $thumbnailwidth / $thumbnailheight;
						$src_ar = $src_width / $src_height;
						if ($src_ar < $dest_ar) {
							$dest_height = $thumbnailheight;
							$dest_width = ($thumbnailheight/$src_height) * $src_width;
						} else if ($src_ar > $dest_ar) {
							$dest_width = $thumbnailwidth;
							$dest_height = ($thumbnailwidth/$src_width) * $src_height;
						} else {
							$dest_width = $thumbnailwidth;
							$dest_height = $thumbnailheight;
						}
						$dest_img = imagecreatetruecolor($thumbnailwidth,$thumbnailheight);
						// Fill with the current background color...
						if (substr($itembgcolor, 0, 1) == "#") {
							$redcomponent = substr($bgcolor, 1, 2);
							$greencomponent = substr($bgcolor, 3, 2);
							$bluecomponent = substr($bgcolor, 5, 2);
						} else {
							$redcomponent = substr($bgcolor, 0, 2);
							$greencomponent = substr($bgcolor, 2, 2);
							$bluecomponent = substr($bgcolor, 4, 2);
						}
						$fillcolor = imagecolorallocate ($dest_img, hexdec($redcomponent), hexdec($greencomponent), hexdec($bluecomponent));
						imagefill ($dest_img, 0, 0, $fillcolor);
						imagecopyresampled($dest_img, $src_img, 0, 0, 0 ,0, $dest_width, $dest_height, $src_width, $src_height);
						if ($extension == "jpg") {
							imagejpeg($dest_img, $resampleimage, $quality);
						} else if ($extension == "gif") {
							if (function_exists("imagegif")) {
								imagetruecolortopalette($dest_img, TRUE, 256);
								imagegif($dest_img, $resampleimage);
							} else {
								imagejpeg($dest_img, $resampleimage, $quality);
								rename($resampleimage, "$ashoppath/prodimg/$product_id/{$imagenumberpath}t-$imagefilename.jpg");
							}
						}
						imagedestroy($src_img);
						imagedestroy($dest_img);
						$thumbnailautosized = TRUE;

						// Resize the main product image...
						// Register notification...
						@mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: processing product image.' WHERE type='productimport'");
						// Give the server some time to copy the uploaded file to the right location...
						$resampleimage = "$ashoppath/prodimg/$product_id/{$imagenumberpath}p-$imagefilename.$extension";
						$starttime = date("s", time());
						while (!@getimagesize($resampleimage)) {
							$now = date("s", time());
							// Time out if this has taken more than 30 seconds to avoid eternal loops...
							if ($now - $starttime >=30) break;
						}
						if ($extension == "jpg") {
							$src_img = imagecreatefromjpeg($resampleimage);
						} else if ($extension == "gif") {
							$src_img = imagecreatefromgif($resampleimage);
						}
						$quality = 90;
						$src_width = imagesx($src_img);
						$src_height = imagesy($src_img);
						$dest_ar = $imagewidth / $imageheight;
						$src_ar = $src_width / $src_height;
						if ($src_ar < $dest_ar) {
							$dest_height = $imageheight;
							$dest_width = ($imageheight/$src_height) * $src_width;
						} else if ($src_ar > $dest_ar) {
							$dest_width = $imagewidth;
							$dest_height = ($imagewidth/$src_width) * $src_height;
						} else {
							$dest_width = $imagewidth;
							$dest_height = $imageheight;
						}
						$dest_position = floor(($imagewidth-$dest_width)/2);
						$dest_img = imagecreatetruecolor($imagewidth,$imageheight);
						// Fill with the current background color...
						if (substr($itembgcolor, 0, 1) == "#") {
							$redcomponent = substr($itembgcolor, 1, 2);
							$greencomponent = substr($itembgcolor, 3, 2);
							$bluecomponent = substr($itembgcolor, 5, 2);
						} else {
							$redcomponent = substr($itembgcolor, 0, 2);
							$greencomponent = substr($itembgcolor, 2, 2);
							$bluecomponent = substr($itembgcolor, 4, 2);
						}
						$fillcolor = imagecolorallocate ($dest_img, hexdec($redcomponent), hexdec($greencomponent), hexdec($bluecomponent));
						imagefill ($dest_img, 0, 0, $fillcolor);
						imagecopyresampled($dest_img, $src_img, $dest_position, 0, 0 ,0, $dest_width, $dest_height, $src_width, $src_height);
						if ($extension == "jpg") {
							imagejpeg($dest_img, $resampleimage, $quality);
						} else if ($extension == "gif") {
							if (function_exists("imagegif")) {
								imagetruecolortopalette($dest_img, TRUE, 256);
								imagegif($dest_img, $resampleimage);
							} else {
								imagejpeg($dest_img, $resampleimage, $quality);
								rename($resampleimage, "$ashoppath/prodimg/$product_id/{$imagenumberpath}p-$imagefilename.jpg");
							}
						}
						imagedestroy($src_img);
						imagedestroy($dest_img);

						// Resize the mini thumbnail image...
						// Register notification...
						@mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: processing small thumbnail image.' WHERE type='productimport'");
						// Give the server some time to copy the uploaded file to the right location...
						$resampleimage = "$ashoppath/prodimg/$product_id/{$imagenumberpath}m-$imagefilename.$extension";
						$starttime = date("s", time());
						while (!@getimagesize($resampleimage)) {
							$now = date("s", time());
							// Time out if this has taken more than 30 seconds to avoid eternal loops...
							if ($now - $starttime >=30) break;
						}
						if ($extension == "jpg") {
							$src_img = imagecreatefromjpeg($resampleimage);
						} else if ($extension == "gif") {
							$src_img = imagecreatefromgif($resampleimage);
						}
						$quality = 90;
						$src_width = imagesx($src_img);
						$src_height = imagesy($src_img);
						$dest_ar = $miniwidth / $miniheight;
						$src_ar = $src_width / $src_height;
						if ($src_ar < $dest_ar) {
							$dest_height = $miniheight;
							$dest_width = ($miniheight/$src_height) * $src_width;
						} else if ($src_ar > $dest_ar) {
							$dest_width = $miniwidth;
							$dest_height = ($miniwidth/$src_width) * $src_height;
						} else {
							$dest_width = $miniwidth;
							$dest_height = $miniheight;
						}
						$dest_img = imagecreatetruecolor($miniwidth,$miniheight);
						// Fill with the current background color...
						if (substr($itembgcolor, 0, 1) == "#") {
							$redcomponent = substr($itembgcolor, 1, 2);
							$greencomponent = substr($itembgcolor, 3, 2);
							$bluecomponent = substr($itembgcolor, 5, 2);
						} else {
							$redcomponent = substr($itembgcolor, 0, 2);
							$greencomponent = substr($itembgcolor, 2, 2);
							$bluecomponent = substr($itembgcolor, 4, 2);
						}
						$fillcolor = imagecolorallocate ($dest_img, hexdec($redcomponent), hexdec($greencomponent), hexdec($bluecomponent));
						imagefill ($dest_img, 0, 0, $fillcolor);
						imagecopyresampled($dest_img, $src_img, 0, 0, 0 ,0, $dest_width, $dest_height, $src_width, $src_height);
						if ($extension == "jpg") {
							imagejpeg($dest_img, $resampleimage, $quality);
						} else if ($extension == "gif") {
							if (function_exists("imagegif")) {
								imagetruecolortopalette($dest_img, TRUE, 256);
								imagegif($dest_img, $resampleimage);
							} else {
								imagejpeg($dest_img, $resampleimage, $quality);
								rename($resampleimage, "$ashoppath/prodimg/$product_id/{$imagenumberpath}m-$imagefilename.jpg");
							}
						}
						imagedestroy($src_img);
						imagedestroy($dest_img);

						// Resize the large image...
						if ($resizeoriginal == TRUE) {
							// Register notification...
							@mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: processing large product image.' WHERE type='productimport'");
							// Give the server some time to copy the uploaded file to the right location...
							$resampleimage = "$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension";
							$starttime = date("s", time());
							while (!@getimagesize($resampleimage)) {
								$now = date("s", time());
								// Time out if this has taken more than 30 seconds to avoid eternal loops...
								if ($now - $starttime >=30) break;
							}
							if ($extension == "jpg") {
								$src_img = imagecreatefromjpeg($resampleimage);
							} else if ($extension == "gif") {
								$src_img = imagecreatefromgif($resampleimage);
							}
							$quality = 90;
							$src_width = imagesx($src_img);
							$src_height = imagesy($src_img);
							$dest_ar = $largewidth / $largeheight;
							$src_ar = $src_width / $src_height;
							if ($src_ar < $dest_ar) {
								$dest_height = $largeheight;
								$dest_width = ($largeheight/$src_height) * $src_width;
							} else if ($src_ar > $dest_ar) {
								$dest_width = $largewidth;
								$dest_height = ($largewidth/$src_width) * $src_height;
							} else {
								$dest_width = $largewidth;
								$dest_height = $largeheight;
							}
							$dest_img = imagecreatetruecolor($largewidth,$largeheight);
							// Fill with the current background color...
							$blackbackground = "#000000";
							if (substr($blackbackground, 0, 1) == "#") {
								$redcomponent = substr($blackbackground, 1, 2);
								$greencomponent = substr($blackbackground, 3, 2);
								$bluecomponent = substr($blackbackground, 5, 2);
							} else {
								$redcomponent = substr($blackbackground, 0, 2);
								$greencomponent = substr($blackbackground, 2, 2);
								$bluecomponent = substr($blackbackground, 4, 2);
							}
							$fillcolor = imagecolorallocate ($dest_img, hexdec($redcomponent), hexdec($greencomponent), hexdec($bluecomponent));
							imagefill ($dest_img, 0, 0, $fillcolor);
							imagecopyresampled($dest_img, $src_img, 0, 0, 0 ,0, $dest_width, $dest_height, $src_width, $src_height);
							if ($extension == "jpg") {
								imagejpeg($dest_img, $resampleimage, $quality);
							} else if ($extension == "gif") {
								if (function_exists("imagegif")) {
									imagetruecolortopalette($dest_img, TRUE, 256);
									imagegif($dest_img, $resampleimage);
								} else {
									imagejpeg($dest_img, $resampleimage, $quality);
									rename($resampleimage, "$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.jpg");
								}
							}
							imagedestroy($src_img);
							imagedestroy($dest_img);
						}

						// Add watermark to large image...
						if ($watermark) {
							if ($resizeoriginal != TRUE) {
								$largeheight = $imagesize[1];
								$largewidth = $imagesize[0];
							}
							// Resize watermark image to the same size as the large image...
							// Register notification...
							@mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: adding watermark.' WHERE type='productimport'");
							// Give the server some time to copy the uploaded file to the right location...
							$resampleimage = "$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.$wextension";
							$starttime = date("s", time());
							while (!@getimagesize($resampleimage)) {
								$now = date("s", time());
								// Time out if this has taken more than 30 seconds to avoid eternal loops...
								if ($now - $starttime >=30) break;
							}
							if ($wextension == "jpg") {
								$src_img = imagecreatefromjpeg($resampleimage);
							} else if ($wextension == "gif") {
								$src_img = imagecreatefromgif($resampleimage);
							} else if ($wextension == "png") {
								$src_img = imagecreatefrompng($resampleimage);
							}
							$quality = 90;
							$src_width = imagesx($src_img);
							$src_height = imagesy($src_img);
							$overlay_img = imagecreatetruecolor($largewidth,$largeheight);
							imagealphablending($overlay_img, false);
							imagesavealpha($overlay_img,true);
							imagecopyresampled($overlay_img, $src_img, 0, 0, 0 ,0, $largewidth, $largeheight, $src_width, $src_height);
							if ($wextension == "jpg") {
								imagejpeg($overlay_img, $resampleimage, $quality);
							} else if ($wextension == "gif") {
								if (function_exists("imagegif")) {
									imagetruecolortopalette($overlay_img, TRUE, 256);
									imagegif($overlay_img, $resampleimage);
								} else {
									imagejpeg($overlay_img, $resampleimage, $quality);
									rename($resampleimage, "$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.jpg");
								}
							} else if ($wextension == "png") {
								imagepng($overlay_img, $resampleimage);
							}
							imagedestroy($src_img);

							// Overlay resized watermark on large image...
							if ($extension == "jpg") {
								$large_img = imagecreatefromjpeg("$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
								imagealphablending($overlay_img, true);
								imagecopymerge_alpha($large_img, $overlay_img, 0, 0, 0, 0, $largewidth, $largeheight, 20);
								imagejpeg($large_img, "$ashoppath/prodimg/$product_id/{$imagenumberpath}watermarked.jpg", $quality);
								imagedestroy($large_img);
								imagedestroy($overlay_img);
								unlink("$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
								rename("$ashoppath/prodimg/$product_id/{$imagenumberpath}watermarked.jpg","$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
								unlink("$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.$wextension");
							} else if ($extension == "gif") {
								if (function_exists("imagegif")) {
									$large_img = imagecreatefromgif("$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
									imagecopymerge_alpha($large_img, $overlay_img, 0, 0, 0, 0, $largewidth, $largeheight, 20);
									imagetruecolortopalette($large_img, TRUE, 256);
									imagegif($large_img, "$ashoppath/prodimg/$product_id/{$imagenumberpath}watermarked.gif");
									imagedestroy($large_img);
									imagedestroy($overlay_img);
									unlink("$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
									rename("$ashoppath/prodimg/$product_id/{$imagenumberpath}watermarked.gif","$ashoppath/prodimg/$product_id/{$imagenumberpath}$imagefilename.$extension");
									unlink("$ashoppath/prodimg/$product_id/{$imagenumberpath}w-$wimagefilename.$wextension");
								}
							}
						}
					}
				}

				// Handle product files...
				$prodfileids = array();
				if (!empty($productfilenames) && is_array($productfilenames)) {
					// Register notification...
					@mysqli_query($db, "UPDATE notification SET message='{$notifypercent}% done. Importing product {$csvline}: processing product files.' WHERE type='productimport'");
					foreach ($productfilenames as $prodfilenumber=>$prodfile) {
						if ($prodfile && file_exists("$ashoppath/products/$prodfile")) {
							$productfilenames[$prodfilenumber] = preg_replace("/%28|%29|%2B/","",urlencode(basename($prodfile)));
							$productfilenames[$prodfilenumber] = preg_replace("/%E5|%E4/","a",$productfilenames[$prodfilenumber]);
							$productfilenames[$prodfilenumber] = preg_replace("/%F6/","o",$productfilenames[$prodfilenumber]);
							$productfilenames[$prodfilenumber] = preg_replace("/%C5|%C4/","A",$productfilenames[$prodfilenumber]);
							$productfilenames[$prodfilenumber] = preg_replace("/%D6/","O",$productfilenames[$prodfilenumber]);
							$productfilenames[$prodfilenumber] = preg_replace("/\+\+\+|\+\+/","+",$productfilenames[$prodfilenumber]);
							$madezip = FALSE;
							if (!empty($zip) && strtoupper($zip) != "FALSE") {
								$zipfile = new ZipArchive();
								$prodfileinfo = pathinfo($productfilenames[$prodfilenumber]);
								$zipfilename = str_replace(".".$prodfileinfo["extension"],"",$productfilenames[$prodfilenumber]);
								$zipfilename .= ".zip";
								if ($zipfile->open("$ashoppath/products/$zipfilename", ZIPARCHIVE::CREATE)) {
									$zipfile->addFile("$ashoppath/products/$prodfile","$prodfile");
									$zipfile->close();
									$productfilenames[$prodfilenumber] = $zipfilename;
									$prodfile = $zipfilename;
									$madezip = TRUE;
								}
							}
							$result = @mysqli_query($db, "SELECT MAX(fileid) AS maxfileid FROM productfiles");
							$prodfileid = @mysqli_result($result,0,"maxfileid")+1;
							$prodfileids[$prodfilenumber] = $prodfileid;
							$result = @mysqli_query($db, "INSERT INTO productfiles (productid, filename, fileid) VALUES ('$product_id', '{$productfilenames[$prodfilenumber]}','$prodfileid')");
							if (file_exists("$ashopspath/products/$prodfileid")) unlink("$ashopspath/products/$prodfileid");
							copy("$ashoppath/products/$prodfile", "$ashopspath/products/$prodfileid");
							@chmod("$ashopspath/products/$prodfileid", 0666);
							if ($madezip) unlink("$ashoppath/products/$zipfilename");
						}
					}
				}

				// Handle product attributes...
				if (!empty($attribute)) {
					$sql="INSERT INTO parameters (productid,caption) VALUES ('$product_id','$attribute')";
					$result = @mysqli_query($db, $sql);
					$parameterid = @mysqli_insert_id($db);

					// Insert alternatives with product files...
					$buybuttons = 0;
					if (!empty($numberofdownloadalternatives)) {
						for ($alternativenumber = 0; $alternativenumber < $numberofdownloadalternatives; $alternativenumber++) {
							$thisalternativenumber = $alternativenumber+1;
							$prodfileid = $prodfileids[$alternativenumber];
							if (empty($prodfileid)) $prodfileid = "none";
							if (isset($fieldnr["downloadalternative$thisalternativenumber"])) $alternativevalue = $productinfo[$fieldnr["downloadalternative$thisalternativenumber"]];
							else $alternativevalue == "";
							if (isset($fieldnr["alternativeprice$thisalternativenumber"])) $alternativeprice = $productinfo[$fieldnr["alternativeprice$thisalternativenumber"]];
							else $alternativeprice = "";
							if (strstr($alternativeprice,".")) $alternativeprice = str_replace(",","",$alternativeprice);
							else $alternativeprice = str_replace(",",".",$alternativeprice);
							if (!empty($alternativeprice)) $buybuttons = 1;
							if (!empty($alternativevalue)) {
								$sql="INSERT INTO parametervalues (parameterid, download, value, noshipping, notax, nofulfilment, price) VALUES ('$parameterid', '$prodfileid', '$alternativevalue', '0', '0', '0', '$alternativeprice')";
								$result = @mysqli_query($db, $sql);
							}
						}
					}

					// Insert alternatives without product files...
					if (!empty($numberofalternatives)) {
						for ($alternativenumber = 0; $alternativenumber < $numberofalternatives; $alternativenumber++) {
							$thisalternativenumber = $alternativenumber+1;
							if (isset($fieldnr["alternative$thisalternativenumber"])) $alternativevalue = $productinfo[$fieldnr["alternative$thisalternativenumber"]];
							else $alternativevalue == "";
							if (isset($fieldnr["alternativeprice$thisalternativenumber"])) $alternativeprice = $productinfo[$fieldnr["alternativeprice$thisalternativenumber"]];
							else $alternativeprice = "";
							if (strstr($alternativeprice,".")) $alternativeprice = str_replace(",","",$alternativeprice);
							else $alternativeprice = str_replace(",",".",$alternativeprice);
							if (!empty($alternativeprice)) $buybuttons = 1;
							if (!empty($alternativevalue)) {
								$sql="INSERT INTO parametervalues (parameterid, download, value, noshipping, notax, nofulfilment, price) VALUES ('$parameterid', 'all', '$alternativevalue', '0', '0', '0', '$alternativeprice')";
								$result = @mysqli_query($db, $sql);
							}
						}
					}

					// Activate separate prices...
					if ($buybuttons == 1) @mysqli_query($db, "UPDATE parameters SET buybuttons='1' WHERE parameterid='$parameterid'");
				}
				$csvline++;
			}
			fclose($fp);
			unlink ("$ashopspath/products/import");
			@mysqli_query($db, "UPDATE notification SET message='Product import completed.' WHERE type='productimport'");
		} else $error = "import";
	} else $error = "import";
	echo "<html><head><script language=\"JavaScript\" type=\"text/javascript\">window.parent.document.location.href='editcatalogue.php?cat=$cat';</script><body></body></html>";
} else {
	if ($action == "generateform") {
		echo "<html><head><link rel=\"stylesheet\" href=\"admin.css\" /></head><body>
		<form action=\"importproducts.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td align=\"right\" class=\"formlabel\">".CSVFILE.":</td><td><input type=\"file\" name=\"importfile\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".FIELDDELIMITER.":</td><td><input type=\"text\" size=\"2\" name=\"delimiter\" value=\"$defaultdelimiter\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".FIELDENCLOSURE.":</td><td><input type=\"text\" size=\"2\" name=\"enclosure\" value=\"$defaultenclosure\"></td></tr>
        <tr><td>&nbsp;</td><td align=\"left\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"submit\" value=\"".UPLOAD."\" onClick=\"window.parent.startimport()\"></td></tr></form>
		</body>
		</html>";
		exit;
	}
	$headerparts = explode("</head>",$header);
	echo $headerparts[0];
	echo "
		<script language=\"JavaScript\" src=\"../includes/prototype.js\" type=\"text/javascript\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\">
			function reportprogress(ajaxRequest) {
				$('importprogress').update(ajaxRequest.responseText);
			}
			
			function checkprogress() {
				var myAjax = new Ajax.Request(
				'importproducts.php', 
				{
					method: 'get',
					parameters: 'action=getprogress&dummy='+ new Date().getTime(), 
					onSuccess: reportprogress
				}
				);
			}

			function startimport() {
				$('importprogress').update('Starting product import...');
				window.setInterval(\"checkprogress()\",2000);
			}
		</script>";
	echo $headerparts[1];
	echo "
        <div class=\"heading\">".IMPORTPRODUCTS."</div><table cellpadding=\"10\" align=\"center\"><tr><td>
		<iframe src=\"importproducts.php?action=generateform\" frameborder=\"0\" width=\"450\" height=\"140\"></iframe>
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr><td>
		<div id=\"importprogress\" class=\"confirm\" style=\"font-size: 12px;\"></div>
		</td></tr></table>
		</td></tr></table>$footer";
}
?>