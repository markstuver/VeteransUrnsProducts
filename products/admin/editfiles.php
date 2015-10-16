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
$noinactivitycheck = "true";

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

include "config.inc.php";
include "ashopconstants.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";
// Get context help for this page...
$contexthelppage = "editproduct";
include "help.inc.php";
include "keycodes.inc.php";

// Get the productid...
if ($add) $productid = $add;

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Handle downloads...
if ($download) {
	if (file_exists("$ashopspath/products/$download")) {
		$result = @mysqli_query($db, "SELECT filename FROM productfiles WHERE fileid='$download'");
		$filename = @mysqli_result($result, 0, "filename");
		header ("Content-Type: application/octet-stream"); 
		header ("Content-Length: ".filesize("$ashopspath/products/$download"));
		header ("Content-Disposition: attachment; filename=$filename");
		readfile_chunked("$ashopspath/products/$download",false);
		exit;
	} else exit;
}

// Change the order of product files...
if ($filemoveup) {
	$sql="UPDATE productfiles SET ordernumber=$prevordno WHERE fileid=$thisfileid";
    $result = @mysqli_query($db, $sql);
	$sql="UPDATE productfiles SET ordernumber=$thisordno WHERE fileid=$prevfileid";
    $result = @mysqli_query($db, $sql);
}

// Delete a product image...
if (is_numeric($deleteimage) && is_numeric($productid)) {
	// Get number of images...
	$productimage = ashop_productimages($productid);
	// Get product images to delete...
	$deleteproductimage = ashop_productimages($productid,$deleteimage);
	if ($deleteproductimage["thumbnail"]) unlink("$ashoppath/prodimg/$productid/$deleteimage/{$deleteproductimage["thumbnail"]}");
	if ($deleteproductimage["main"]) unlink("$ashoppath/prodimg/$productid/$deleteimage/{$deleteproductimage["main"]}");
	if ($deleteproductimage["product"]) unlink("$ashoppath/prodimg/$productid/$deleteimage/{$deleteproductimage["product"]}");
	if ($deleteproductimage["mini"]) unlink("$ashoppath/prodimg/$productid/$deleteimage/{$deleteproductimage["mini"]}");
	if ($productimage["additionalimages"] > $deleteimage) {
		for ($imgnumber = $deleteimage+1; $imgnumber <= $productimage["additionalimages"]; $imgnumber++) {
			$newproductimage = ashop_productimages($productid,$imgnumber);
			$newimgnumber = $imgnumber-1;
			if ($newproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["thumbnail"]}");
			if ($newproductimage["main"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["main"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["main"]}");
			if ($newproductimage["product"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["product"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["product"]}");
			if ($newproductimage["mini"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["mini"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["mini"]}");
		}
		$newimgnumber = $imgnumber-1;
		if ($newimgnumber > 0) rmdir("$ashoppath/prodimg/$productid/$newimgnumber");
	} else rmdir("$ashoppath/prodimg/$productid/$deleteimage");

}

// Move a product image up one step...
if (is_numeric($moveupimage) && is_numeric($productid)) {
	if (!file_exists("$ashoppath/prodimg/$productid/temp")) mkdir("$ashoppath/prodimg/$productid/temp");

	if ($moveupimage == 1) {
		// Move top picture to temp...
		$downproductimage = ashop_productimages($productid);
		if ($downproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/{$downproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["thumbnail"]}");
		if ($downproductimage["main"]) rename("$ashoppath/prodimg/$productid/{$downproductimage["main"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["main"]}");
		if ($downproductimage["product"]) rename("$ashoppath/prodimg/$productid/{$downproductimage["product"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["product"]}");
		if ($downproductimage["mini"]) rename("$ashoppath/prodimg/$productid/{$downproductimage["mini"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["mini"]}");

		// Move selected picture up...
		$upproductimage = ashop_productimages($productid,$moveupimage);
		if ($upproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/{$upproductimage["thumbnail"]}");
		if ($upproductimage["main"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["main"]}","$ashoppath/prodimg/$productid/{$upproductimage["main"]}");
		if ($upproductimage["product"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["product"]}","$ashoppath/prodimg/$productid/{$upproductimage["product"]}");
		if ($upproductimage["mini"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["mini"]}","$ashoppath/prodimg/$productid/{$upproductimage["mini"]}");

		// Move previous top image to $moveupimage directory...
		if ($downproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["thumbnail"]}");
		if ($downproductimage["main"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["main"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["main"]}");
		if ($downproductimage["product"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["product"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["product"]}");
		if ($downproductimage["mini"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["mini"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["mini"]}");

		// Remove the temp directory...
		rmdir("$ashoppath/prodimg/$productid/temp");

	} else {
		$previousimage = $moveupimage-1;

		// Move previous picture to temp...
		$downproductimage = ashop_productimages($productid,$previousimage);
		if ($downproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/$previousimage/{$downproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["thumbnail"]}");
		if ($downproductimage["main"]) rename("$ashoppath/prodimg/$productid/$previousimage/{$downproductimage["main"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["main"]}");
		if ($downproductimage["product"]) rename("$ashoppath/prodimg/$productid/$previousimage/{$downproductimage["product"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["product"]}");
		if ($downproductimage["mini"]) rename("$ashoppath/prodimg/$productid/$previousimage/{$downproductimage["mini"]}","$ashoppath/prodimg/$productid/temp/{$downproductimage["mini"]}");

		// Move selected picture up...
		$upproductimage = ashop_productimages($productid,$moveupimage);
		if ($upproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/$previousimage/{$upproductimage["thumbnail"]}");
		if ($upproductimage["main"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["main"]}","$ashoppath/prodimg/$productid/$previousimage/{$upproductimage["main"]}");
		if ($upproductimage["product"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["product"]}","$ashoppath/prodimg/$productid/$previousimage/{$upproductimage["product"]}");
		if ($upproductimage["mini"]) rename("$ashoppath/prodimg/$productid/$moveupimage/{$upproductimage["mini"]}","$ashoppath/prodimg/$productid/$previousimage/{$upproductimage["mini"]}");

		// Move previous pircture to $moveupimage directory...
		if ($downproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["thumbnail"]}");
		if ($downproductimage["main"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["main"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["main"]}");
		if ($downproductimage["product"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["product"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["product"]}");
		if ($downproductimage["mini"]) rename("$ashoppath/prodimg/$productid/temp/{$downproductimage["mini"]}","$ashoppath/prodimg/$productid/$moveupimage/{$downproductimage["mini"]}");

		// Remove the temp directory...
		rmdir("$ashoppath/prodimg/$productid/temp");

	}
}

// Get information about the product from the database...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productname = str_replace("\"", "&quot;", $productname);
$filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid' AND (storage='' OR storage IS NULL) ORDER BY ordernumber");
while($filerow = @mysqli_fetch_array($filesresult)) {
	$filename[$filerow["fileid"]] = $filerow["filename"];
	$fileordernumber[$filerow["fileid"]] = $filerow["ordernumber"];
	$fileurl[$filerow["fileid"]] = $filerow["url"];
}

if ($copyfrom) unset($edited);

// Handle editing of the product...
if ($productid) {

  // Show edit form...
  if (!$edited) {

	  // Check for uploaded product files...
	  $numberoffiles = 0;
	  unset($findfile);
	  if ($userid == "1" && is_dir("$ashopspath/products")) $findfile = opendir("$ashopspath/products");
	  else if (is_dir("$ashopspath/products/$username")) $findfile = opendir("$ashopspath/products/$username");
	  if ($findfile) while (false !== ($foundfile = readdir($findfile))) {
		  if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/index/", $foundfile) && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashopspath/products/$foundfile")) {
			  $uploadedfiles[$numberoffiles] = $foundfile;
			  $numberoffiles++;
		  }
	  }

	  // Check for uploaded preview files...
	  $numberofpreviewfiles = 0;
	  unset($findfile);
	  if (is_dir("$ashoppath/previews")) $findfile = opendir("$ashoppath/previews");
	  if ($findfile) while (false !== ($foundfile = readdir($findfile))) {
		  if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/index/", $foundfile) && !preg_match("/maillog/", $foundfile) && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashoppath/previews/$foundfile")) {
			  $uploadedpreviewfiles[$numberofpreviewfiles] = $foundfile;
			  $numberofpreviewfiles++;
		  }
	  }

	  // Check for product files to copy...
	  if ($copyfrom) $result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$copyfrom' AND (storage='' OR storage IS NULL) ORDER BY filename");
	  while ($row = @mysqli_fetch_array($result)) $copyfiles[$row["fileid"]] = $row["filename"];
	  if (is_array($copyfiles)) {
		  $result = @mysqli_query($db, "SELECT * FROM product WHERE productid='$copyfrom'");
		  $copyfromname = @mysqli_result($result,0,"name");
	  }

	  if (strpos($header, "body") != 0) {
		  $newheader = substr($header,1,strpos($header, "body")+3);
		  $newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "body")+4,strlen($header));
	  } else {
		  $newheader = substr($header,1,strpos($header, "BODY")+3);
		  $newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "BODY")+4,strlen($header));
	  }
	  echo "$newheader
	  	<script language=\"JavaScript\">
		function uploadmessage() 
		{
		  if ((document.productform.prodfile.value != '') || (document.productform.imgfile.value != '') || (document.productform.demofile.value != '')) {
			  w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
		  }
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
        </script>
        <div class=\"heading\">".EDITPRODUCTFILES."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>";

		if ($error) {
			echo "<p class=\"error\">".ERROR."<br>";
			if ($error=="extension") echo MUSTBEGIFORJPG."</p>";
			elseif ($error=="keycodes" || $error=="products") echo CHECKPERMISSIONS."</p>";
			elseif ($error=="previews") echo CHECKPERMISSIONS2."</p>";
			elseif ($error=="prodimg") echo CHECKPERMISSIONS3."</p>";
		}

		if ($msg) {
			echo "<p class=\"confirm\">";
			if ($msg=="1") echo AUTORESIZED."<br>".ORIGINALKEPT."</p>";
			elseif ($msg=="2") echo AUTORESIZED."</p>";
			elseif ($msg=="3") echo UNLOCKKEYSADDED."</p>";
		}

        echo "
        <form action=\"editfiles.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productimageform\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">";
		if (!$copyfromname && !$copyfiles) {
			// Get product image info...
			$productimage = ashop_productimages($productid);
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11\" align=\"absmiddle\" onclick=\"return overlib('$tip11');\" onmouseout=\"return nd();\"></a> <a href=\"$help11\" class=\"helpnav2\" target=\"_blank\">".PRODUCTIMAGES.":</a><br><span class=\"sm\">".GIFORJPG."</span></td><td align=\"left\">";
			if ($productimage["thumbnail"]) echo "<img src=\"../prodimg/$productid/{$productimage["thumbnail"]}?x=".time()."\" alt=\"$productname\" width=\"$thumbnailwidth\" align=\"texttop\"> <input type=\"checkbox\" name=\"deletethumbnail\"><span class=\"formlabel\"> ".THEWORDDELETE."</span><br>";
			if ($productimage["additionalimages"]) {
				echo "<table cellpadding=\"3\" border=\"1\" cellspacing=\"0\"><tr>";
				$cellcount = 0;
				for ($imagenumber = 1; $imagenumber <= $productimage["additionalimages"]; $imagenumber++) {
					$thisproductimage = ashop_productimages($productid,$imagenumber);
					if ($thisproductimage["thumbnail"]) echo "<td class=\"sm\"><img src=\"$ashopurl/prodimg/$productid/$imagenumber/{$thisproductimage["thumbnail"]}?x=".time()."\" width=\"45\" height=\"45\"><br>$imagenumber) <a href=\"editfiles.php?productid=$productid&moveupimage=$imagenumber&cat=$cat&resultpage=$resultpage&add=$add\"><img src=\"images/icon_moveup.gif\" border=\"0\" align=\"absbottom\"></a> <a href=\"editfiles.php?productid=$productid&deleteimage=$imagenumber&cat=$cat&resultpage=$resultpage&add=$add\"><img src=\"images/icon_delete.gif\" border=\"0\" align=\"absbottom\"></a></td>";
					$cellcount++;
					if ($cellcount == 6) {
						echo "</tr><tr>";
						$cellcount = 0;
					}
				}
				echo "</tr></table>";
			}
			echo "<input type=\"file\" name=\"imgfile\"> <span class=\"sm\">".OPTIONAL."</span><br><span class=\"formlabel\">".USETHEUPLOADBUTTON."</span></td></tr>
			<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"";
			if ($add) echo "add";
			else echo "productid";
			echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"upload\" value=\"".UPLOAD."\" onClick=\"uploadmessage()\"> <input type=\"submit\" name=\"upload\" value=\"".THEWORDDELETE."\"></td></tr></table></form><br />

			<form action=\"editfiles.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productimageform\">
			<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
			  <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image16','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image16\" align=\"absmiddle\" onclick=\"return overlib('$tip17');\" onmouseout=\"return nd();\"></a> ".PREVIEWFILE.":</td><td class=\"formlabel\" align=\"left\">";
			if (is_dir("$ashoppath/previews/$productid")) {
				$findfile = opendir("$ashoppath/previews/$productid");
				$i = 0;
				while (false !== ($foundfile = readdir($findfile))) {
					if($foundfile && $foundfile != "." && $foundfile != "..") $previewfilename = $foundfile;
					else $previewfilename = "";
					if (!empty($previewfilename)) {
						echo "<span class=\"formlabel\"><b>$previewfilename</b> <input type=\"checkbox\" name=\"deletepreview[$i]\" value=\"$foundfile\"> ".THEWORDDELETE."</span><br>";
						$i++;
					}
					unset($foundfile);
				}
				unset($findfile);
			}
			echo "<input type=\"file\" name=\"demofile\"> <span class=\"sm\">".OPTIONAL."<br>".LEAVEBLANKTOKEEP."</span>";
			if ($numberofpreviewfiles) {
				echo "<br>".ORCHOOSEPREVIOUSLY."<br><select name=\"uploadedpreviewfilename\"><option value=\"\"></option>";
				for($i = 0; $i < $numberofpreviewfiles; $i++) {
					echo "<option value=\"$uploadedpreviewfiles[$i]\">$uploadedpreviewfiles[$i]</option>";
				}
				echo "</select>";
			}
			echo "</td></tr>
			  <tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> <a href=\"$help12\" class=\"helpnav2\" target=\"_blank\">".PRODUCTFILES.":</a></td><td class=\"formlabel\" align=\"left\">";
			if (count($filename) > 0) foreach($filename as $thisfileid=>$thisfilename) {
				if ($fileurl["$thisfileid"]) echo "<span class=\"formlabel\"><b><a href=\"{$fileurl["$thisfileid"]}\">$thisfilename</a></b> <input type=\"checkbox\" name=\"deleteprodfile$thisfileid\"> ".THEWORDDELETE;
				else echo "<span class=\"formlabel\"><b><a href=\"editfiles.php?download=$thisfileid\">$thisfilename</a></b> <input type=\"checkbox\" name=\"deleteprodfile$thisfileid\"> ".THEWORDDELETE;
				if ($filepreviousorderno) echo " <a href=\"editfiles.php?thisordno=$fileordernumber[$thisfileid]&prevordno=$filepreviousorderno&thisfileid=$thisfileid&prevfileid=$previousfileid&filemoveup=true&productid=$productid\">".MOVEUP."</a>";
				echo "</span><br>";
				$filepreviousorderno = $fileordernumber[$thisfileid];
				$previousfileid = $thisfileid;
			}
			echo "<input type=\"file\" name=\"prodfile\"> <span class=\"sm\">".OPTIONAL."</span>";
			if ($numberoffiles) {
				echo "<br>".ORCHOOSEPREVIOUSLY."<br><select name=\"uploadedfilename\"><option value=\"\"></option>";
				for($i = 0; $i < $numberoffiles; $i++) {
					echo "<option value=\"$uploadedfiles[$i]\">$uploadedfiles[$i]</option>";
				}
				echo "</select>";
			}
			echo "<br>".ORCOPYAFILE.": <input type=\"text\" name=\"copyfrom\" size=\"3\"><br>".ORENTERAURL.":<br><input type=\"text\" name=\"uploadurl\" size=\"45\" value=\"http://\"><br></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image13','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image13\" align=\"absmiddle\" onclick=\"return overlib('$tip13');\" onmouseout=\"return nd();\"></a> <a href=\"$help13\" class=\"helpnav2\" target=\"_blank\">".UPLOADKEYCODES.":</a></td><td align=\"left\"><input type=\"file\" name=\"unlockkeys\"> <span class=\"sm\">".OPTIONAL."</span></td></tr>";
			echo "<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"";
			if ($add) echo "add";
			else echo "productid";
			echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"upload\" value=\"".UPLOAD."\" onClick=\"uploadmessage()\"> <input type=\"submit\" name=\"upload\" value=\"".THEWORDDELETE."\"> <input type=\"submit\" name=\"finish\" value=\"".FINISH."\" onClick=\"uploadmessage()\"></td></tr></table></form></td></tr></table>$footer";
		} else {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> <a href=\"$help12\" class=\"helpnav2\" target=\"_blank\">".COPYPRODUCTFILES."</a> ".FROM." <b>$copyfromname</b>:</td><td class=\"formlabel\" align=\"left\"><select name=\"copyfile\">";
			if (is_array($copyfiles)) foreach ($copyfiles as $fileid=>$filename) echo "<option value=\"$fileid\">$filename</option>";
			echo "</td></tr><tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"";
			if ($add) echo "add";
			else echo "productid";
			echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"upload\" value=\"".THEWORDCOPY."\"> <input type=\"button\" value=\"".CANCEL."\" onClick=\"history.back();\"></td></tr></table></form></td></tr></table>$footer";
		}
  } else {
	if ($copyfile) {
		$result = @mysqli_query($db, "SELECT filename FROM productfiles WHERE fileid='$copyfile'");
		$copiedfilename = @mysqli_result($result,0,"filename");
		$copiedfileurl = @mysqli_result($result,0,"url");
		@mysqli_query($db, "INSERT INTO productfiles (fileid, filename, url, productid) VALUES ('$copyfile','$copiedfilename','$copiedfileurl', '$productid')");
		$id = @mysqli_insert_id($db);
		@mysqli_query($db, "UPDATE productfiles SET ordernumber='$id' WHERE id='$id'");
	}
	if ($deletethumbnail == "on") {
		// Get product image info...
		$productimage = ashop_productimages($productid);
		if ($productimage["thumbnail"]) unlink("$ashoppath/prodimg/$productid/{$productimage["thumbnail"]}");
		if ($productimage["main"]) unlink("$ashoppath/prodimg/$productid/{$productimage["main"]}");
		if ($productimage["product"]) unlink("$ashoppath/prodimg/$productid/{$productimage["product"]}");
		if ($productimage["mini"]) unlink("$ashoppath/prodimg/$productid/{$productimage["mini"]}");
		if ($productimage["additionalimages"]) {
			for ($imgnumber = 1; $imgnumber <= $productimage["additionalimages"]; $imgnumber++) {
				$newproductimage = ashop_productimages($productid,$imgnumber);
				if ($imgnumber == 1) {
					if ($newproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/{$newproductimage["thumbnail"]}");
					if ($newproductimage["main"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["main"]}","$ashoppath/prodimg/$productid/{$newproductimage["main"]}");
					if ($newproductimage["product"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["product"]}","$ashoppath/prodimg/$productid/{$newproductimage["product"]}");
					if ($newproductimage["mini"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["mini"]}","$ashoppath/prodimg/$productid/{$newproductimage["mini"]}");
				} else {
					$newimgnumber = $imgnumber-1;
					if ($newproductimage["thumbnail"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["thumbnail"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["thumbnail"]}");
					if ($newproductimage["main"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["main"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["main"]}");
					if ($newproductimage["product"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["product"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["product"]}");
					if ($newproductimage["mini"]) rename("$ashoppath/prodimg/$productid/$imgnumber/{$newproductimage["mini"]}","$ashoppath/prodimg/$productid/$newimgnumber/{$newproductimage["mini"]}");
				}
			}
			$newimgnumber = $imgnumber-1;
			if ($newimgnumber > 0) rmdir("$ashoppath/prodimg/$productid/$newimgnumber");
		} else rmdir("$ashoppath/prodimg/$productid");
	}
	if (is_array($deletepreview)) {
		//if (file_exists("$ashoppath/previews/$productid/$deletepreview")) unlink("$ashoppath/previews/$productid/$deletepreview");
		if (is_dir("$ashoppath/previews/$productid")) {
			$findfile = opendir("$ashoppath/previews/$productid");
			$previewfilenumber = 0;
			while (false !== ($foundfile = readdir($findfile))) { 
				if($foundfile && $foundfile != "." && $foundfile != "..") {
					if ($deletepreview[$previewfilenumber]) unlink("$ashoppath/previews/$productid/$foundfile");
					$previewfilenumber++;
				}
				unset($foundfile);
			}
			closedir($findfile);
			//rmdir("$ashoppath/previews/$productid");
			unset($findfile);
		}
	}
	if (count($filename) > 0) {
		reset($filename);
		foreach($filename as $thisfileid=>$thisfilename) {
			$deleteprodfile = "off";
			eval ("if (\$deleteprodfile$thisfileid == \"on\") \$deleteprodfile = \"on\";");
			if ($deleteprodfile == "on") {
				$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE fileid='$thisfileid' AND productid!='$productid'");
				if (!@mysqli_num_rows($result) && file_exists("$ashopspath/products/$thisfileid")) unlink("$ashopspath/products/$thisfileid");
				$sql="DELETE FROM productfiles WHERE fileid='$thisfileid' AND productid='$productid'";
				$result = @mysqli_query($db, $sql);
			}
		}
	}

	if (!is_writeable("$ashopspath/products")) $error = "products";

	$prodfile = str_replace("\t","\\t",$prodfile);
    if (is_uploaded_file($prodfile) && $error != "products") {
	   $uploadfilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($prodfile_name)));
       $uploadfilename = preg_replace("/%E5|%E4/","a",$uploadfilename);
       $uploadfilename = preg_replace("/%F6/","o",$uploadfilename);
       $uploadfilename = preg_replace("/%C5|%C4/","A",$uploadfilename);
       $uploadfilename = preg_replace("/%D6/","O",$uploadfilename);
       $uploadfilename = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename);
	   $result = @mysqli_query($db, "SELECT MAX(fileid) AS maxfileid FROM productfiles");
	   $uploadfileid = @mysqli_result($result,0,"maxfileid")+1;
	   $result = @mysqli_query($db, "INSERT INTO productfiles (productid, filename, fileid) VALUES ('$productid', '$uploadfilename','$uploadfileid')");
	   $id = @mysqli_insert_id($db);
	   @mysqli_query($db, "UPDATE productfiles SET ordernumber='$id' WHERE id='$id'");
       if (file_exists("$ashopspath/products/$uploadfileid")) unlink("$ashopspath/products/$uploadfileid");
	   move_uploaded_file($prodfile, "$ashopspath/products/$uploadfileid");
	   @chmod("$ashopspath/products/$uploadfileid", 0666);
	} else if ($uploadedfilename) {
   	  $uploadfilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadedfilename)));
      $uploadfilename = preg_replace("/%E5|%E4/","a",$uploadfilename);
      $uploadfilename = preg_replace("/%F6/","o",$uploadfilename);
      $uploadfilename = preg_replace("/%C5|%C4/","A",$uploadfilename);
      $uploadfilename = preg_replace("/%D6/","O",$uploadfilename);
      $uploadfilename = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename);
	  $result = @mysqli_query($db, "SELECT MAX(fileid) AS maxfileid FROM productfiles");
	  $uploadfileid = @mysqli_result($result,0,"maxfileid")+1;
	  $result = @mysqli_query($db, "INSERT INTO productfiles (productid, filename, fileid) VALUES ('$productid', '$uploadfilename','$uploadfileid')");
	  $id = @mysqli_insert_id($db);
	  @mysqli_query($db, "UPDATE productfiles SET ordernumber='$id' WHERE id='$id'");
      if (file_exists("$ashopspath/products/$uploadfileid")) unlink("$ashopspath/products/$uploadfileid");
      if ($userid == "1") rename("$ashopspath/products/$uploadedfilename", "$ashopspath/products/$uploadfileid");
	  else rename("$ashopspath/products/$username/$uploadedfilename", "$ashopspath/products/$uploadfileid");
	} else if (isset($uploadurl) && $uploadurl != "http://") {
	   $uploadfilename = substr($uploadurl, strrpos($uploadurl, "/")+1);
	   $result = @mysqli_query($db, "SELECT MAX(fileid) AS maxfileid FROM productfiles");
	   $uploadfileid = @mysqli_result($result,0,"maxfileid")+1;
	   $result = @mysqli_query($db, "INSERT INTO productfiles (productid, filename, fileid, url) VALUES ('$productid', '$uploadfilename','$uploadfileid','$uploadurl')");
	   $id = @mysqli_insert_id($db);
	   @mysqli_query($db, "UPDATE productfiles SET ordernumber='$id' WHERE id='$id'");
	}

	// Handle unlock code files...
	$unlockkeys = str_replace("\t","\\t",$unlockkeys);
	if (is_uploaded_file($unlockkeys)) {
		if (@move_uploaded_file($unlockkeys, "$ashopspath/products/unlockkeys$productid")) {
			$fp = fopen ("$ashopspath/products/unlockkeys$productid","r");
			if ($fp) {
				while (!feof ($fp)) {
					$keytext = fgets($fp, 4096);
					$keytext = trim($keytext);
					if ($keytext) {
						if (!empty($keycodeencryptionkey)) $keytext = ashop_encrypt($keytext, $keycodeencryptionkey);
						$sql = "INSERT INTO unlockkeys (productid, keytext) VALUES ($productid, '$keytext')";
						$result = @mysqli_query($db, $sql);
					}
				}
				fclose($fp);
				unlink ("$ashopspath/products/unlockkeys$productid");
				$msgstring = "&msg=3";
			} else $error = "keycodes";
		} else $error = "keycodes";
	}

	// Handle preview/demo files...
	if (!is_writeable("$ashoppath/previews")) $error = "previews";
	else {
		$demofile = str_replace("\t","\\t",$demofile);
		if (is_uploaded_file($demofile)) {
			//if (is_dir("$ashoppath/previews/$productid")) {
				//$findfile = opendir("$ashoppath/previews/$productid");
				//while (false !== ($foundfile = readdir($findfile))) { 
					//	if($foundfile && $foundfile != "." && $foundfile != "..") unlink("$ashoppath/previews/$productid/$foundfile");
					//	unset($foundfile);
				//}
				//closedir($findfile);
				//rmdir("$ashoppath/previews/$productid");
				//unset($findfile);
			//}
			if (!is_dir("$ashoppath/previews/$productid")) {
				mkdir("$ashoppath/previews/$productid");
				@chmod("$ashoppath/previews/$productid", 0777);
			}
			if (substr($demofile_name,0,2) == "..") $demofile_name = substr($demofile_name,2);
			if (substr($demofile_name,0,1) == ".") $demofile_name = substr($demofile_name,1);
			$demofile_name = str_replace("/","",$demofile_name);
			$demofile_name = str_replace("\\","",$demofile_name);
			$demofile_name = str_replace(".php",".phpfile",$demofile_name);
			$demofile_name = str_replace(".asp",".aspfile",$demofile_name);
			$demofile_name = str_replace(".cgi",".cgifile",$demofile_name);
			$demofile_name = str_replace(".pl",".plfile",$demofile_name);
			@move_uploaded_file($demofile, "$ashoppath/previews/$productid/$demofile_name");
			@chmod("$ashoppath/previews/$productid/$demofile_name", 0777);
		} else if ($uploadedpreviewfilename) {
			//if (is_dir("$ashoppath/previews/$productid")) {
				//	$findfile = opendir("$ashoppath/previews/$productid");
				//	while (false !== ($foundfile = readdir($findfile))) { 
					//		if($foundfile && $foundfile != "." && $foundfile != "..") unlink("$ashoppath/previews/$productid/$foundfile");
					//		unset($foundfile);
				//	}
				//	closedir($findfile);
				//	rmdir("$ashoppath/previews/$productid");
				//	unset($findfile);
			//}
			if (!is_dir("$ashoppath/previews/$productid")) {
				mkdir("$ashoppath/previews/$productid");
				@chmod("$ashoppath/previews/$productid", 0777);
			}
			copy("$ashoppath/previews/$uploadedpreviewfilename", "$ashoppath/previews/$productid/$uploadedpreviewfilename");
			@chmod("$ashoppath/previews/$productid/$uploadedpreviewfilenam", 0777);
		}
	}


   // Handle image files...
   if (!is_writeable("$ashoppath/prodimg")) $error = "prodimg";
   $imgfile = str_replace("\t","\\t",$imgfile);
	if (is_uploaded_file($imgfile) && $error != "prodimg") {
	   $originalimagekept = FALSE;
	   $thumbnailautosized = FALSE;
	   $imagefilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($imgfile_name)));
       $imagefilename = preg_replace("/%E5|%E4/","a",$imagefilename);
       $imagefilename = preg_replace("/%F6/","o",$imagefilename);
       $imagefilename = preg_replace("/%C5|%C4/","A",$imagefilename);
       $imagefilename = preg_replace("/%D6/","O",$imagefilename);
       $imagefilename = preg_replace("/\+\+\+|\+\+/","+",$imagefilename);
	   $fileinfo = pathinfo("$imgfile_name");
	   $extension = $fileinfo["extension"];
	   $imagefilename = str_replace(".$extension","",$imagefilename);
	   $extension = strtolower($extension);
	   if ($extension == "jpeg") $extension = "jpg";
	   if ($extension != "gif" && $extension != "jpg") $error = "extension";
	   else {
		   // Make sure the product has a subdirectory for its images...
		   if (!file_exists("$ashoppath/prodimg/$productid")) {
			   @mkdir("$ashoppath/prodimg/$productid");
			   @chmod("$ashoppath/prodimg/$productid", 0755);
		   }

		   // Check for existing files...
		   $productimage = ashop_productimages($productid);
		   if ($productimage["thumbnail"]) {
			   $imagenumber = $productimage["additionalimages"]+1;
			   $imagenumberpath = "$imagenumber/";
			   if (!file_exists("$ashoppath/prodimg/$productid/$imagenumber")) {
				   @mkdir("$ashoppath/prodimg/$productid/$imagenumber");
				   @chmod("$ashoppath/prodimg/$productid/$imagenumber", 0755);
			   }
		   } else $imagenumberpath = "";
		   move_uploaded_file($imgfile, "$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension");
		   @chmod("$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension", 0666);
		   copy ("$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension",
			   "$ashoppath/prodimg/$productid/{$imagenumberpath}p-$imagefilename.$extension");
		   @chmod("$ashoppath/prodimg/$productid/{$imagenumberpath}p-$imagefilename.$extension", 0666);
		   copy ("$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension",
			   "$ashoppath/prodimg/$productid/{$imagenumberpath}m-$imagefilename.$extension");
		   @chmod("$ashoppath/prodimg/$productid/{$imagenumberpath}m-$imagefilename.$extension", 0666);
		   copy ("$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension",
			   "$ashoppath/prodimg/$productid/{$imagenumberpath}t-$imagefilename.$extension");
		   @chmod("$ashoppath/prodimg/$productid/{$imagenumberpath}t-$imagefilename.$extension", 0666);

		   // Determine the new sizes...
		   $imagesize = getimagesize("$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension");
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

		   // If GD is available resample the image to fit the size set in layout config...
		   if (function_exists('imagecreatefromjpeg') && function_exists('imagecreatefromgif') && function_exists('imagecreatetruecolor') && $gdversion == 2) {
			   // Give the server some time to copy the uploaded file to the right location...
			   $resampleimage = "$ashoppath/prodimg/$productid/{$imagenumberpath}t-$imagefilename.$extension";
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
			   if ($extension == "jpg") imagejpeg($dest_img, $resampleimage, $quality);
			   else if ($extension == "gif") {
				   if (function_exists("imagegif")) {
					   imagetruecolortopalette($dest_img, TRUE, 256);
					   imagegif($dest_img, $resampleimage);
				   } else {
					   imagejpeg($dest_img, $resampleimage, $quality);
					   rename($resampleimage, "$ashoppath/prodimg/$productid/{$imagenumberpath}t-$imagefilename.jpg");
				   }
			   }
			   imagedestroy($src_img);
			   imagedestroy($dest_img);
			   $thumbnailautosized = TRUE;

			   // Resize the main product image...
			   // Give the server some time to copy the uploaded file to the right location...
			   $resampleimage = "$ashoppath/prodimg/$productid/{$imagenumberpath}p-$imagefilename.$extension";
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
					   rename($resampleimage, "$ashoppath/prodimg/$productid/{$imagenumberpath}p-$imagefilename.jpg");
				   }
			   }
			   imagedestroy($src_img);
			   imagedestroy($dest_img);

			   // Resize the mini thumbnail image...
			   // Give the server some time to copy the uploaded file to the right location...
			   $resampleimage = "$ashoppath/prodimg/$productid/{$imagenumberpath}m-$imagefilename.$extension";
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
					   rename($resampleimage, "$ashoppath/prodimg/$productid/{$imagenumberpath}m-$imagefilename.jpg");
				   }
			   }
			   imagedestroy($src_img);
			   imagedestroy($dest_img);

			   // Resize the large image...
			   if ($resizeoriginal == TRUE) {
				   // Give the server some time to copy the uploaded file to the right location...
				   $resampleimage = "$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.$extension";
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
						   rename($resampleimage, "$ashoppath/prodimg/$productid/{$imagenumberpath}$imagefilename.jpg");
					   }
				   }
				   imagedestroy($src_img);
				   imagedestroy($dest_img);
			   }
		   }
	   }
    }
	if ($upload) {
		if ($add) $prodidstring = "&add=$add";
		else $prodidstring = "&productid=$productid";
		if ($error) $errorstring = "&error=$error";
		if ($thumbnailautosized) {
			if ($originalimagekept) $msgstring = "&msg=1";
			else $msgstring = "&msg=2";
		}
		header ("Location: editfiles.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage$prodidstring$errorstring$msgstring");
	} 
	else if ($error) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
    else if ($add && $userid == 1) header("Location: editshipping.php?productid=$productid&cat=$cat&resultpage=$resultpage&search=$search");
    else header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>