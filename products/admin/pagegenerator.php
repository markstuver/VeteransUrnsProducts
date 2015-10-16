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
include "ashopconstants.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";

// Get context help for this page...
$contexthelppage = "makehtml";
include "help.inc.php";
include "../language/$defaultlanguage/catalogue.inc.php";

// Check if this user should be allowed to access the page...
if ($userid != "1" && !$advancedmallmode) {
	header("Location: editcatalogue.php");
	exit;
}

// Get the productid...
if ($add) $productid = $add;

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if the page should be locked...
$lockcheck = @mysqli_query($db, "SELECT * FROM user WHERE userid='1' AND htmllock='1'");
if ($userid != "1" && @mysqli_num_rows($lockcheck)) {
	echo "$header
        <div class=\"heading\">".PRODUCTDETAILS."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><p><span class=\"formtitle\"><p align=\"center\" class=\"error\">".PAGEGENERATORINUSE."</p></span>
		</td></tr></table>$footer";
		exit;
}

// Only one product...
if ($productid) {
	// Get product details...
	$result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid'");
	$row = @mysqli_fetch_array($result);
	$productname = $row["name"];
	$productdetailsurl = $row["detailsurl"];
	if (!$productdetailsurl && $seourls != "1") $productdetailsurl = "$ashopurl/index.php?product=$productid";
	$productmanufacturer = $row["manufacturer"];
	$copyof = $row["copyof"];
	$productlongdescription = $row["longdescription"];
	//if (!$productlongdescription) $productlongdescription = $row["description"];
	$productmetakeywords = $row["metakeywords"];
	$productmetadescription = $row["metadescription"];
	$productactivatereviews = $row["activatereviews"];
	if (!isset($productactivatereviews)) $productactivatereviews = 1;
	$productactivatesocnet = $row["activatesocialnetworking"];
	if (!isset($productactivatesocnet)) $productactivatesocnet = 1;
	$checkreviews = @mysqli_query($db, "SELECT * FROM reviews WHERE productid='$productid'");
	$numberofreviews = @mysqli_num_rows($checkreviews);
	if (!$generate && !$skip) {
		echo "$header";
	  if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) {
		  echo "
<script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>
";
	  }
	  echo "
        <div class=\"heading\">".PRODUCTDETAILS."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>
        <form action=\"pagegenerator.php\" method=\"post\" name=\"productform\">";
		if ($msg) echo "<p align=\"center\" class=\"error\">".ERROR.": $msg</p>";
		if ($umsg) echo "<p align=\"center\" class=\"confirm\">$umsg</p>";
		echo "<table width=\"900\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">";
		if ($seourls == "1") echo "
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ".SEOFRIENDLYFILENAME.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"htmlfilename\" value=\"%productname%\" size=\"40\">.html</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".ORURL.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"detailsurl\" value=\"$productdetailsurl\" size=\"70\"></td></tr>";
		else {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".DETAILSURL.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"detailsurl\" value=\"$productdetailsurl\" size=\"70\">";
			if ($add) echo "<br><span class=\"sm\"> ".OPTIONALURL."</span>";
			echo "</td></tr>";
		}
		echo "
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".MANUFACTURER.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"manufacturer\" value=\"$productmanufacturer\" size=\"40\"> <span class=\"sm\"> ".OPTIONAL."</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a> ".EXTENDEDPRODUCTINFO.":</a></td><td align=\"left\"><textarea class=\"ckeditor\" name=\"longdescription\" cols=\"60\" rows=\"20\">$productlongdescription</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip11');\" onmouseout=\"return nd();\"></a> ".METAKEYWORDS.":</td><td align=\"left\"><textarea name=\"metakeywords\" cols=\"50\" rows=\"5\">$productmetakeywords</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> ".METADESCRIPTION.":</td><td align=\"left\"><textarea name=\"metadescription\" cols=\"50\" rows=\"5\">$productmetadescription</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">&nbsp;</td><td class=\"formlabel\" align=\"left\"><input type=\"checkbox\" name=\"activatereviews\""; if ($productactivatereviews != 0) echo " checked"; echo "> ".ACTIVATEREVIEWS; if ($numberofreviews) echo " [<a href=\"editreviews.php?productid=$productid&cat=$cat&resultpage=$resultpage&pid=$pid&search=$search\">".EDITREVIEWS."]"; echo "</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">&nbsp;</td><td class=\"formlabel\" align=\"left\"><input type=\"checkbox\" name=\"activatesocnet\""; if ($productactivatesocnet != 0) echo " checked"; echo "> ".ACTIVATESOCNET."</td></tr>
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"";
		if ($add) echo "add";
		else echo "productid";
		echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"generate\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"skip\" value=\"".SKIP."\"></td></tr></table></form></td></tr></table>";
		echo $footer;
	} else {
		if (!$skip) {
			// Lock page generator if the user is admin...
			if ($userid == "1") @mysqli_query($db, "UPDATE user SET htmllock='1' WHERE userid='$userid'");

			// Generate filename...
			$thisfilename = str_replace("%productid%","$productid",$htmlfilename.".html");
			$safeproductname = str_replace(" ","_",$productname);
			$safeproductname = str_replace("!","",$safeproductname);
			$safeproductname = str_replace("#","",$safeproductname);
			$safeproductname = str_replace("?","",$safeproductname);
			$safeproductname = str_replace("&","",$safeproductname);
			$safeproductname = str_replace(":","",$safeproductname);
			$safeproductname = str_replace(";","",$safeproductname);
			$safeproductname = str_replace(",","",$safeproductname);
			$safeproductname = str_replace(".","",$safeproductname);
			$safeproductname = str_replace("/","",$safeproductname);
			$safeproductname = str_replace("\\","",$safeproductname);
			$safeproductname = str_replace("\"","",$safeproductname);
			$safeproductname = str_replace("'","",$safeproductname);
			$safeproductname = str_replace("+","",$safeproductname);
			$safeproductname = str_replace("-","",$safeproductname);
			$safeproductname = str_replace("(","",$safeproductname);
			$safeproductname = str_replace(")","",$safeproductname);
			$safeproductname = str_replace("%","",$safeproductname);
			$safeproductname = str_replace("__","_",$safeproductname);
			$safeproductname = str_replace("__","_",$safeproductname);
			$safeproductname = str_replace("å","a",$safeproductname);
			$safeproductname = str_replace("ä","a",$safeproductname);
			$safeproductname = str_replace("ö","o",$safeproductname);
			$safeproductname = str_replace("Å","A",$safeproductname);
			$safeproductname = str_replace("Ä","A",$safeproductname);
			$safeproductname = str_replace("Ö","O",$safeproductname);
			$thisfilename = str_replace("%productname%","$safeproductname",$thisfilename);

			// Check that this filename has not already been used...
			if ($seourls == "1") {
				$newdetailsurl = "$ashopurl/catalog/$thisfilename";
				$newdetailsurl = str_replace("%","",$newdetailsurl);
				$newdetailsurl = str_replace(" ","",$newdetailsurl);
				$newdetailsurl = str_replace("&","",$newdetailsurl);
				$newdetailsurl = str_replace("?","",$newdetailsurl);
			}
			else $newdetailsurl = "$ashopurl/product.php?product=$productid";
			if (empty($detailsurl) || $detailsurl == "http://") {
				if (!empty($copyof) && is_numeric($copyof)) $productidcheckstring = "AND productid!='$copyof' AND (copyof!='$copyof' OR copyof IS NULL)";
				else $productidcheckstring = "AND productid!='$productid' AND (copyof!='$productid' OR copyof IS NULL)";
				$checkurlresult = @mysqli_query($db, "SELECT * FROM product WHERE detailsurl='$newdetailsurl' $productidcheckstring");
				$pagenumber = 1;
				while (@mysqli_num_rows($checkurlresult)) {
					$thisfilename = str_replace(".html","",$thisfilename);
					$newfilename = $thisfilename."_{$pagenumber}.html";
					$newdetailsurl = "$ashopurl/catalog/$newfilename";
					$newdetailsurl = str_replace("%","",$newdetailsurl);
					$newdetailsurl = str_replace(" ","",$newdetailsurl);
					$newdetailsurl = str_replace("&","",$newdetailsurl);
					$newdetailsurl = str_replace("?","",$newdetailsurl);
					$pagenumber++;
					$checkurlresult = @mysqli_query($db, "SELECT * FROM product WHERE detailsurl='$newdetailsurl' $productidcheckstring");
				}
			} else $newdetailsurl = $detailsurl;
			if (empty($add) && empty($detailsurl) && empty($htmlfilename)) $newdetailsurl = "";

			if ($activatereviews == "on") $activatereviews = 1;
			else $activatereviews = 0;
			if ($activatesocnet == "on") $activatesocnet = 1;
			else $activatesocnet = 0;

			@mysqli_query($db, "UPDATE product SET detailsurl='$newdetailsurl',manufacturer='$manufacturer',longdescription='$longdescription',metakeywords='$metakeywords',metadescription='$metadescription',activatereviews='$activatereviews',activatesocialnetworking='$activatesocnet' WHERE productid='$productid'");

			// Unlock page generator if the user is admin...
			if ($userid == "1") @mysqli_query($db, "UPDATE user SET htmllock='0' WHERE userid='$userid'");
			
			if ($add) header("Location: editfiles.php?add=$productid&cat=$cat&resultpage=$resultpage&search=$search");
			else header("Location: editcatalogue.php?msg=htmldone&cat=$cat&pid=$pid&resultpage=$resultpage&search=$search");
			exit;
		} else {
			if ($add) header("Location: editfiles.php?add=$productid&cat=$cat&resultpage=$resultpage&search=$search");
			else header("Location: editcatalogue.php?cat=$cat&pid=$pid&resultpage=$resultpage&search=$search");
			exit;
		}
	}

// A whole category....
} else if ($categoryid) {
	// Check for Digital Mall...
	$result = @mysqli_query($db, "SELECT * FROM user");
	if (file_exists("$ashoppath/members/index.php") && @mysqli_num_rows($result)>1) $digitalmall = 1;
	else $digitalmall = 0;
	// Get category details...
	$result = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$categoryid'");
	$row = @mysqli_fetch_array($result);
	$categoryname = $row["name"];
	if (!$generate) {
		echo "$header
        <div class=\"heading\">".GENERATESITEMAP."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><p><span class=\"formtitle\">$categoryname</p>
        <form action=\"pagegenerator.php\" method=\"post\" name=\"productform\">
		<table width=\"580\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">";
		if ($digitalmall) echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a> ".ONLYPRODUCTSFORMEMBER.":</td><td><input type=\"text\" name=\"mallmember\" value=\"0\" size=\"3\"> <span class=\"sm\"> ".LEAVEBLANKTOINCLUDEALLMEMBERS."</span></td></tr>";
		if ($seourls == "1") echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip8');\" onmouseout=\"return nd();\"></a> ".FILENAME.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"htmlfilename\" value=\"%productname%\" size=\"40\">.html</td></tr>";
		echo "
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"categoryid\" value=\"$categoryid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"generate\" value=\"".MAKEPAGES."\"></td></tr></table></form></td></tr></table>$footer";
	} else {
		// Lock page generator if the user is admin...
		if ($userid == "1") @mysqli_query($db, "UPDATE user SET htmllock='1' WHERE userid='$userid'");

		// Create directory and XML sitemap file...
		$safecategoryname = str_replace(" ","_",$categoryname);
		$safecategoryname = str_replace("!","",$safecategoryname);
		$safecategoryname = str_replace("#","",$safecategoryname);
		$safecategoryname = str_replace("?","",$safecategoryname);
		$safecategoryname = str_replace("&","",$safecategoryname);
		$safecategoryname = str_replace(":","",$safecategoryname);
		$safecategoryname = str_replace(";","",$safecategoryname);
		$safecategoryname = str_replace("/","",$safecategoryname);
		$safecategoryname = str_replace("\\","",$safecategoryname);
		$safecategoryname = str_replace("\"","",$safecategoryname);
		$safecategoryname = str_replace("'","",$safecategoryname);
		$safecategoryname = str_replace("(","",$safecategoryname);
		$safecategoryname = str_replace(")","",$safecategoryname);
		$safecategoryname = str_replace("%","",$safecategoryname);
		$safecategoryname = str_replace("__","_",$safecategoryname);
		$safecategoryname = str_replace("å","a",$safecategoryname);
		$safecategoryname = str_replace("ä","a",$safecategoryname);
		$safecategoryname = str_replace("ö","o",$safecategoryname);
		$safecategoryname = str_replace("Å","A",$safecategoryname);
		$safecategoryname = str_replace("Ä","A",$safecategoryname);
		$safecategoryname = str_replace("Ö","O",$safecategoryname);
		@mkdir("$ashoppath/catalog/$safecategoryname");
		@chmod("$ashoppath/catalog/$safecategoryname", 0777);
		$sitemapfp = @fopen("$ashoppath/catalog/$safecategoryname/sitemap.xml","w");
		if ($sitemapfp) {
			fwrite($sitemapfp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
			fwrite($sitemapfp, "\n<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">");
		}
		$result = @mysqli_query($db, "SELECT productid FROM productcategory WHERE categoryid='$categoryid'");
		while ($row = @mysqli_fetch_array($result)) {
			$productid = $row["productid"];
			$copyof = $row["copyof"];
			// Get product details...
			$prodresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$productid' AND (prodtype!='content' OR prodtype IS NULL)");
			$prodrow = @mysqli_fetch_array($prodresult);
			$productowner = $prodrow["userid"];
			$productname = $prodrow["name"];

			if ($productname && (!$mallmember || $productowner == $mallmember)) {
				
				// Generate filename...
				$thisfilename = str_replace("%productid%","$productid",$htmlfilename.".html");
				$safeproductname = str_replace(" ","_",$productname);
				$safeproductname = str_replace(" ","_",$safeproductname);
				$safeproductname = str_replace("!","",$safeproductname);
				$safeproductname = str_replace("#","",$safeproductname);
				$safeproductname = str_replace("?","",$safeproductname);
				$safeproductname = str_replace("&","",$safeproductname);
				$safeproductname = str_replace(":","",$safeproductname);
				$safeproductname = str_replace(";","",$safeproductname);
				$safeproductname = str_replace(",","",$safeproductname);
				$safeproductname = str_replace(".","",$safeproductname);
				$safeproductname = str_replace("/","",$safeproductname);
				$safeproductname = str_replace("\\","",$safeproductname);
				$safeproductname = str_replace("\"","",$safeproductname);
				$safeproductname = str_replace("'","",$safeproductname);
				$safeproductname = str_replace("+","",$safeproductname);
				$safeproductname = str_replace("-","",$safeproductname);
				$safeproductname = str_replace("(","",$safeproductname);
				$safeproductname = str_replace("%","",$safeproductname);
				$safeproductname = str_replace(")","",$safeproductname);
				$safeproductname = str_replace("__","_",$safeproductname);
				$safeproductname = str_replace("__","_",$safeproductname);
				$safeproductname = str_replace("å","a",$safeproductname);
				$safeproductname = str_replace("ä","a",$safeproductname);
				$safeproductname = str_replace("ö","o",$safeproductname);
				$safeproductname = str_replace("Å","A",$safeproductname);
				$safeproductname = str_replace("Ä","A",$safeproductname);
				$safeproductname = str_replace("Ö","O",$safeproductname);
				$thisfilename = str_replace("%productname%","$safeproductname",$thisfilename);

				// Check that this filename has not already been used...
				if ($seourls == "1") {
					$newdetailsurl = "$ashopurl/catalog/$thisfilename";
					$newdetailsurl = str_replace("%","",$newdetailsurl);
					$newdetailsurl = str_replace(" ","",$newdetailsurl);
					$newdetailsurl = str_replace("&","",$newdetailsurl);
					$newdetailsurl = str_replace("?","",$newdetailsurl);
				} else $newdetailsurl = "$ashopurl/product.php?product=$productid";
				if (!empty($copyof) && is_numeric($copyof)) $productidcheckstring = "AND productid!='$copyof' AND (copyof!='$copyof' OR copyof IS NULL)";
				else $productidcheckstring = "AND productid!='$productid' AND (copyof!='$productid' OR copyof IS NULL)";
				$checkurlresult = @mysqli_query($db, "SELECT * FROM product WHERE detailsurl='$newdetailsurl' $productidcheckstring");
				$pagenumber = 1;
				while (@mysqli_num_rows($checkurlresult)) {
					$thisfilename = str_replace(".html","",$thisfilename);
					$newfilename = $thisfilename."_{$pagenumber}.html";
					$newdetailsurl = "$ashopurl/catalog/$newfilename";
					$newdetailsurl = str_replace("%","",$newdetailsurl);
					$newdetailsurl = str_replace(" ","",$newdetailsurl);
					$newdetailsurl = str_replace("&","",$newdetailsurl);
					$newdetailsurl = str_replace("?","",$newdetailsurl);
					$pagenumber++;
					$checkurlresult = @mysqli_query($db, "SELECT * FROM product WHERE detailsurl='$newdetailsurl' $productidcheckstring");
				}		

				@mysqli_query($db, "UPDATE product SET detailsurl='$newdetailsurl', activatereviews='1',activatesocialnetworking='1' WHERE productid='$productid'");

				// Add to sitemap file...
				if ($sitemapfp) {
					fwrite($sitemapfp, "\n\t<url>");
					fwrite($sitemapfp, "\n\t\t<loc>$newdetailsurl</loc>");
					fwrite($sitemapfp, "\n\t</url>");
				}
			}
		}
		// Write end of sitemap file...
		if ($sitemapfp) {
			fwrite($sitemapfp, "\n</urlset>");
			fclose($sitemapfp);
			@chmod("$ashoppath/catalog/$safecategoryname/sitemap.xml", 0777);
		}

		// Index all category sitemaps...
		$sitemapindexfp = @fopen("$ashoppath/catalog/sitemap_index.xml","w");
		if ($sitemapindexfp) {
			fwrite($sitemapindexfp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
			fwrite($sitemapindexfp, "\n<sitemapindex xmlns=\"http://www.google.com/schemas/sitemap/0.84\">");
			$findfile = opendir("$ashoppath/catalog");
			while ($foundfile = readdir($findfile)) {
				if($foundfile && is_dir("$ashoppath/catalog/$foundfile") && $foundfile != "." && $foundfile != ".." && !strstr($foundfile, "CVS") && substr($foundfile1, 0, 1) != "_") {
					fwrite($sitemapindexfp, "\n\t<sitemap>");
					fwrite($sitemapindexfp, "\n\t\t<loc>$ashopurl/catalog/$foundfile/sitemap.xml</loc>");
					fwrite($sitemapindexfp, "\n\t</sitemap>");
				}
			}
			fwrite($sitemapindexfp, "\n</sitemapindex>");
			fclose($sitemapindexfp);
			@chmod("$ashoppath/catalog/$safecategoryname/sitemap_index.xml", 0777);
		}

		// Unlock page generator if the user is admin...
		if ($userid == "1") @mysqli_query($db, "UPDATE user SET htmllock='0' WHERE userid='$userid'");

		header("Location: editcatalogue.php?msg=htmldone&cat=$categoryid");
		exit;
	}
}
?>