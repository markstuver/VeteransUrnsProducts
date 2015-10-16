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
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($_GET["cat"] == "pages" || $_GET["cat"] == "pageslinks") $scriptname = "page.php";
else $scriptname = "index.php";

// Make sure the feed shows items from the right shop...
if ($_GET["shop"] && is_numeric($_GET["shop"])) {
	$shopsql = "WHERE userid='{$_GET["shop"]}'";
	$shopsql2 = "AND userid='{$_GET["shop"]}'";
}
else {
	$shopsql = "";
	$shopsql2 = "";
	$_GET["shop"] = "";
	$shop = "";
}

// Validate variables...
if (!empty($mode) && $mode != "prisjakt") {
	$mode = "";
	$_GET["mode"] = "";
}
if (!empty($_GET["cat"]) && (!is_numeric($_GET["cat"]) && $_GET["cat"] != "pages" && $_GET["cat"] != "pageslinks" && $_GET["cat"] != "links")) {
	$cat = "";
	$_GET["cat"] = "";
}
if (!empty($items) && !is_numeric($items)) {
	$_GET["items"] = "";
	$items = "";
}
if (!empty($affiliate) && !is_numeric($affiliate)) {
	$_GET["affiliate"] = "";
	$affiliate = "";
}
if (!empty($featured) && (!is_numeric($featured) && $featured != "all")) {
	$_GET["featured"] = "";
	$featured = "";
}
if (!empty($showprice) && $showprice != "true") {
	$_GET["showprice"] = "";
	$showprice = "";
}
$ashopsortorder = "ASC";

// Check if there is a cached feed available...
$now = date("YmdG", time()+$timezoneoffset);
$threehoursago = date("YmdG", time()+$timezoneoffset-10800);
$twohoursago = date("YmdG", time()+$timezoneoffset-7200);
$onehourago = date("YmdG", time()+$timezoneoffset-3600);
$feedfilename = "rss";
if (strstr($_SERVER["QUERY_STRING"],"categories")) $feedfilename .= "categories";
else $feedfilename .= $mode.$cat.$shop.$items.$affiliate.$featured.$showprice;
str_replace(".","",$feedfilename);
str_replace("/","",$feedfilename);
str_replace("\\","",$feedfilename);
if (file_exists("$ashoppath/updates/$feedfilename$threehoursago")) {
	if (!empty($_GET["mode"]) && $_GET["mode"] != "rss") header('Content-type: text/plain');
	else header('Content-type: text/xml');
	$fp = fopen ("$ashoppath/updates/$feedfilename$threehoursago","r");
	if ($fp) {
		while (!feof ($fp)) echo fgets($fp, 4096);
		fclose($fp);
	}
	exit;
}
if (file_exists("$ashoppath/updates/$feedfilename$twohoursago")) {
	if (!empty($_GET["mode"]) && $_GET["mode"] != "rss") header('Content-type: text/plain');
	else header('Content-type: text/xml');
	$fp = fopen ("$ashoppath/updates/$feedfilename$twohoursago","r");
	if ($fp) {
		while (!feof ($fp)) echo fgets($fp, 4096);
		fclose($fp);
	}
	exit;
}
if (file_exists("$ashoppath/updates/$feedfilename$onehourago")) {
	if (!empty($_GET["mode"]) && $_GET["mode"] != "rss") header('Content-type: text/plain');
	else header('Content-type: text/xml');
	$fp = fopen ("$ashoppath/updates/$feedfilename$onehourago","r");
	if ($fp) {
		while (!feof ($fp)) echo fgets($fp, 4096);
		fclose($fp);
	}
	exit;
}
if (file_exists("$ashoppath/updates/$feedfilename$now")) {
	if (!empty($_GET["mode"]) && $_GET["mode"] != "rss") header('Content-type: text/plain');
	else header('Content-type: text/xml');
	$fp = fopen ("$ashoppath/updates/$feedfilename$now","r");
	if ($fp) {
		while (!feof ($fp)) echo fgets($fp, 4096);
		fclose($fp);
	}
	exit;
}
if (is_dir("$ashoppath/updates") && is_writable("$ashoppath/updates")) {
	// Remove old cache files...
	$findfile = opendir("$ashoppath/updates");
	if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
		if (substr($foundfile,0,strlen($feedfilename)) == $feedfilename && !is_dir("$ashoppath/updates/$foundfile")) unlink("$ashoppath/updates/$foundfile");
	}
	// Create new cache file...
	$fp = @fopen("$ashoppath/updates/$feedfilename$now", "w");
}

// Generate price comparison export...
if ($_GET["mode"] == "prisjakt") {
	$pricefile = "Produktnamn;Art.nr.;Kategori;Pris inkl.moms;Produkt-URL;Tillverkare;Tillverkar-SKU;Frakt;Bild-URL;Lagerstatus\n";
}

// Generate a feed with a list of categories...
if (strstr($_SERVER["QUERY_STRING"],"categories")) {
	if ($_GET["shop"] && is_numeric($_GET["shop"]) && $_GET["shop"] > 1) {
		$result = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='{$_GET["shop"]}'");
		$shopname = @mysqli_result($result, 0, "shopname");
	} else $shopname = $ashopname;
	$rssfeed = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
<rss version=\"2.0\">
<channel>
  <title>$ashopname Categories</title>
  <link>$ashopurl</link>
  <description>Full list of categories in $shopname.</description>";
	 $sql = "SELECT * FROM category $shopsql";
	 $result = @mysqli_query($db, $sql);
	 while ($row = @mysqli_fetch_array($result)) {
		 $categorydescription = strip_tags($row["description"]);
		 $categorydescription = html_entity_decode($categorydescription);
		 $categorydescription = str_replace("& ","&amp; ",$categorydescription);
		 $categoryname = strip_tags($row["name"]);
		 $categoryname = htmlspecialchars($categoryname);
		 $categorylinkurl = str_replace("&amp;","&",$categorylinkurl);
		 $categorylinkurl = str_replace("&","&amp;",$categorylinkurl);
		 if ($_GET["affiliate"] && is_numeric($_GET["affiliate"])) $categorylinkurl = "$ashopurl/affiliate.php?id={$_GET["affiliate"]}|redirect=$scriptname?cat={$row["categoryid"]}|shop={$_GET["shop"]}";
		 else $categorylinkurl = "$ashopurl/$scriptname?cat={$row["categoryid"]}";
		 $rssfeed .= "
		 <item>
		 <title>$categoryname</title>
		 <link>$categorylinkurl</link>
		 <description>$categorydescription</description>
		 </item>";
	 }
	 $rssfeed .= "
	 </channel>
	 </rss>";

	 @mysqli_close($db);
	 header('Content-type: text/xml');
	 echo $rssfeed;
	 if ($fp) {
		 @fwrite($fp, $rssfeed);
		 fclose($fp);
	 }
	 exit;
}

if (!empty($_GET["cat"]) && (is_numeric($_GET["cat"]) || $_GET["cat"] == "pages" || $_GET["cat"] == "pageslinks" || $_GET["cat"] == "links")) {
	if ($_GET["cat"] == "pages") {
		$categoryname = "Pages";
		$categorydescription = "";
	} else if ($_GET["cat"] == "pageslinks") {
		$categoryname = "Pages &amp; Links";
		$categorydescription = "";
	} else if ($_GET["cat"] == "links") {
		$categoryname = "Links";
		$categorydescription = "";
	} else {
		$result = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='{$_GET["cat"]}' $shopsql2");
		$categoryname = @mysqli_result($result,0,"name");
		$categorydescription = @mysqli_result($result,0,"description");
		$categorydescription = strip_tags($categorydescription);
		$categorydescription = html_entity_decode($categorydescription);
		$categorydescription = str_replace("& ","&amp; ",$categorydescription);
	}
	$rssfeed = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
<rss version=\"2.0\">
<channel>
  <title>$categoryname</title>
  <link>$ashopurl/$scriptname?cat={$_GET["cat"]}</link>
  <description>$categorydescription</description>";
} else if (!empty($_GET["featured"]) && (is_numeric($_GET["featured"]) || $featured == "all")) {
	$rssfeed = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
<rss version=\"2.0\">
<channel>
  <title>$ashopname Recommended Products</title>
  <link>$ashopurl</link>
  <description>Recommended products at $ashopname.</description>";
} else {
	$rssfeed = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
<rss version=\"2.0\">
<channel>
  <title>$ashopname New Items</title>
  <link>$ashopurl</link>
  <description>The lastest items added to our catalog.</description>";
}

if (!$items || !is_numeric($items)) $items = 10;

if (!empty($_GET["mode"]) && $_GET["mode"] == "prisjakt") $result = @mysqli_query($db, "SELECT * from product WHERE active='1' AND ((prodtype!='content' AND prodtype!='mallfee' AND prodtype!='auctionbids') OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) $shopsql2 ORDER BY ordernumber $ashopsortorder");
else if (!empty($_GET["cat"]) && is_numeric($_GET["cat"])) $result = @mysqli_query($db, "SELECT product.* from productcategory, product WHERE productcategory.categoryid = '{$_GET["cat"]}' AND product.productid = productcategory.productid AND product.active='1' AND ((prodtype!='content' AND prodtype!='mallfee' AND prodtype!='auctionbids') OR prodtype IS NULL) $shopsql2 ORDER BY product.ordernumber $ashopsortorder LIMIT $items");
else if (!empty($_GET["cat"]) && $_GET["cat"] == "pageslinks") $result = @mysqli_query($db, "SELECT * from product WHERE prodtype='content' AND name!='AShopFirstPage' AND name!='AShopFirstPageMobile' AND name!='AShopAboutPage' AND name!='AShopTermsPage' AND name!='AShopPrivacyPage' AND name!='AShopContent' $shopsql2 ORDER BY ordernumber $ashopsortorder");
else if (!empty($_GET["cat"]) && $_GET["cat"] == "pages") $result = @mysqli_query($db, "SELECT * from product WHERE prodtype='content' AND name!='AShopFirstPage' AND name!='AShopFirstPageMobile' AND name!='AShopAboutPage' AND name!='AShopTermsPage' AND name!='AShopPrivacyPage' AND name!='AShopContent' AND description != '' AND description IS NOT NULL AND (detailsurl='' OR detailsurl IS NULL) $shopsql2 ORDER BY ordernumber $ashopsortorder");
else if (!empty($_GET["cat"]) && $_GET["cat"] == "links") $result = @mysqli_query($db, "SELECT * from product WHERE prodtype='content' AND name!='AShopFirstPage' AND name!='AShopFirstPageMobile' AND name!='AShopAboutPage' AND name!='AShopTermsPage' AND name!='AShopPrivacyPage' AND name!='AShopContent' AND (description='' OR description IS NULL) AND detailsurl IS NOT NULL AND detailsurl!='' $shopsql2 ORDER BY ordernumber $ashopsortorder");
else if (!empty($featured) && is_numeric($featured)) $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND ((prodtype!='content' AND prodtype!='mallfee' AND prodtype!='auctionbids') OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) AND featured='$featured' $shopsql2");
else if ($featured == "all") $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND ((prodtype!='content' AND prodtype!='mallfee' AND prodtype!='auctionbids') OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) AND featured>'0' $shopsql2 ORDER BY featured ASC LIMIT $items");
else $result = @mysqli_query($db, "SELECT * FROM product WHERE active='1' AND ((prodtype!='content' AND prodtype!='mallfee' AND prodtype!='auctionbids') OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) $shopsql2 ORDER BY productid DESC LIMIT $items");
while ($row = @mysqli_fetch_array($result)) {
	$productdescription = strip_tags($row["description"]);
	$productdescription = html_entity_decode($productdescription);
	$productdescription = str_replace("&amp;","&",$productdescription);
	$productdescription = str_replace("&","&amp;",$productdescription);
	$productdescription = str_replace("&mdash;","--",$productdescription);
	$productdescription = str_replace("&ndash;","-",$productdescription);
	$productdescription = str_replace("&ldquo;","\"",$productdescription);
	$productdescription = str_replace("&rdquo;","\"",$productdescription);
	$productdescription = str_replace("&rsquo;","'",$productdescription);
	$productname = strip_tags($row["name"]);
	$productname = htmlspecialchars($productname);
	$producturl = $row["detailsurl"];
	$productprice = $row["price"];
	$productprice = $currencysymbols[$ashopcurrency]["pre"].number_format($productprice,2,'.','').$currencysymbols[$ashopcurrency]["post"];
	if ($_GET["showprice"] == "true") $productname .= "|$productprice";
	if ($_GET["affiliate"] && is_numeric($_GET["affiliate"])) {
		if (empty($producturl)) $productlinkurl = "$ashopurl/affiliate.php?id={$_GET["affiliate"]}|redirect=$scriptname?product={$row["productid"]}";
		else $productlinkurl = "$ashopurl/affiliate.php?id={$_GET["affiliate"]}|redirect=$producturl";
	} else if (!empty($producturl)) $productlinkurl = $producturl;
	else {
		if ($_GET["cat"] == "pageslinks") $productlinkurl = "$ashopurl/$scriptname?id={$row["productid"]}";
		else $productlinkurl = "$ashopurl/$scriptname?product={$row["productid"]}";
	}
	// Get product image info...
	$productimage = ashop_productimages($row["productid"]);
	if (!empty($productimage["product"])) $productimageurl = $ashopurl."/prodimg/".$row["productid"]."/".$productimage["product"];
	else $productimageurl = "";
	if (!empty($productimage["thumbnail"])) $productthumburl = $ashopurl."/prodimg/".$row["productid"]."/".$productimage["thumbnail"];
	else $productthumburl = "";
	if (empty($_GET["mode"]) || $_GET["mode"] == "rss") {
		$productlinkurl = str_replace("&amp;","&",$productlinkurl);
		$productlinkurl = str_replace("&","&amp;",$productlinkurl);
		$rssfeed .= "
		<item>
		<title>$productname</title>
		<link>$productlinkurl</link>
		<description>$productdescription</description>";
		if (!empty($productthumburl)) $rssfeed .= "
		<image>
		<title>$productname</title>
		<url>$productthumburl</url>
		<link>$productlinkurl</link>
		</image>";
		$rssfeed .= "
		</item>";
	} else {
		if ($_GET["mode"] == "prisjakt") {
			if ($row["inventory"] > 0 || !$row["useinventory"]) $inventory = "Ja";
			if ($row["skucode"]) $skucode = $row["skucode"];
			else $skucode = $row["productid"];
			$categoryresult = @mysqli_query($db, "SELECT categoryid FROM productcategory WHERE productid='{$row["productid"]}' LIMIT 1");
			$categoryid = @mysqli_result($categoryresult,0,"categoryid");
			$categoryresult = @mysqli_query($db, "SELECT parentcategoryid,grandparentcategoryid,name FROM category WHERE categoryid='$categoryid'");
			$categoryname = @mysqli_result($categoryresult,0,"name");
			$parentcategoryid = @mysqli_result($categoryresult,0,"parentcategoryid");
			$grandparentcategoryid = @mysqli_result($categoryresult,0,"grandparentcategoryid");
			if ($parentcategoryid != $categoryid) {
				$pcategoryresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$parentcategoryid'");
				$pname = @mysqli_result($pcategoryresult,0,"name");
				$categoryname = $pname." / ".$categoryname;
			}
			if ($grandparentcategoryid != $categoryid) {
				$gpcategoryresult = @mysqli_query($db, "SELECT name FROM category WHERE categoryid='$grandparentcategoryid'");
				$gpname = @mysqli_result($gpcategoryresult,0,"name");
				$categoryname = $gpname." / ".$categoryname;
			}
			$taxmultiplier = 1+($taxpercentage/100);
			if ($row["taxable"] && $displaywithtax == 1) $productprice = $productprice*$taxmultiplier;
			if (!is_numeric($row["shipping"])) $row["shipping"] = 0;
			$pricefile .= "$productname;{$row["productid"]};$categoryname;$productprice;$productlinkurl;{$row["manufacturer"]};$skucode;{$row["shipping"]};$productimageurl;$inventory\n";
		}
	}
}

$rssfeed .= "
</channel>
</rss>";

@mysqli_close($db);
if (!empty($_GET["mode"]) && $_GET["mode"] != "rss") {
	header('Content-type: text/plain');
	if ($_GET["mode"] == "prisjakt") echo $pricefile;
	if ($fp) @fwrite($fp, $pricefile);
} else {
	header('Content-type: text/xml');
	echo $rssfeed;
	if ($fp) @fwrite($fp, $rssfeed);
}
if ($fp) fclose($fp);
?>