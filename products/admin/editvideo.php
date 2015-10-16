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

@set_time_limit(0);
$noinactivitycheck = "true";
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
require_once "$ashoppath/includes/aws/aws-config.php";
require_once "$ashoppath/includes/aws/aws-autoloader.php";
use Aws\S3\S3Client;

// Connect to AWS...
$client = S3Client::factory(array(
	'key'    => $awskey,
	'secret' => $awssecret
));
$client->registerStreamWrapper();

// Set uploaded filename...
if (!empty($mainuploadedfilename) && file_exists("$ashoppath/products/$mainuploadedfilename")) $uploadedfilename = $mainuploadedfilename;

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get information about the product from the database...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productname = str_replace("\"", "&quot;", $productname);
$fileresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid' AND fileid='$fileid'");
$filerow = @mysqli_fetch_array($fileresult);

// Handle editing of the product video file...
if ($productid) {

  // Show edit form...
  if (!$edited) {

	  echo "$header
        <div class=\"heading\">".EDITPRODUCTVIDEOS."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>";

		if ($error) {
			echo "<p class=\"error\">".ERROR."<br>";
			if ($error=="extension") echo MUSTBEFLVORMP4."</p>";
			if ($error=="missingfile") echo NOSUCHFILEONAWS."</p>";
		}

		echo "<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> ".EDITPRODUCTVIDEOFILE.":</td><td class=\"formlabel\" align=\"left\">";
		$size = filesize("s3://$awsbucket/$awsdirectory/{$filerow["filename"]}");
		$filesize = floor($size/1048576);
		if ($filesize == 0) {
			$filesize = floor($size/1024);
			if ($filesize == 0) $filesize = $size." bytes";
			else $filesize .= " kB";
		} else $filesize .= " MB";
		echo "</td></tr>
		<form action=\"editvideo.php\" method=\"post\" name=\"productform\" id=\"productform\">
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".NAME.":</td><td><input type=\"text\" name=\"title\" size=\"35\" value=\"{$filerow["name"]}\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".DESCRIPTION.":</td><td><textarea name=\"description\" cols=\"30\" rows=\"5\">".htmlentities(stripslashes($filerow["description"]), ENT_QUOTES)."</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".VIDEOTAGS.":</td><td><input type=\"text\" name=\"tags\" size=\"35\" value=\"{$filerow["tags"]}\"> <span class=\"sm\">".SEPARATEWITHCOMMA."</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".FILENAME.":</td><td><input type=\"text\" name=\"filename\" size=\"35\" value=\"{$filerow["filename"]}\"><span class=\"formlabel\"> ($filesize)</span></td></tr>";
		echo "<input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"fileid\" id=\"fileid\" value=\"$fileid\">
		<tr><td>&nbsp;</td><td align=\"right\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"></td></tr></table></form>
		</td></tr></table>
		$footer";
  } else {

	if ($filename) {
	   $uploadfilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($filename)));
	   $uploadfilename = preg_replace("/%E5|%E4/","a",$uploadfilename);
	   $uploadfilename = preg_replace("/%F6/","o",$uploadfilename);
	   $uploadfilename = preg_replace("/%C5|%C4/","A",$uploadfilename);
	   $uploadfilename = preg_replace("/%D6/","O",$uploadfilename);
	   $uploadfilename = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename);
	   $fileinfo = pathinfo("$uploadfilename");
	   $extension = strtolower($fileinfo["extension"]);
	   if ($extension != "mp4" && $extension != "flv") $error = "extension";
	}

	if (!file_exists("s3://$awsbucket/$awsdirectory/$filename")) $error = "missingfile";

	if (empty($error)) $result = @mysqli_query($db, "UPDATE productfiles SET name='$title', description='$description', tags='$tags', filename='$filename' WHERE fileid='$fileid' AND productid='$productid'");

	if ($error) header ("Location: editvideo.php?productid=$productid&fileid=$fileid&cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
    else header("Location: editvideos.php?productid=$productid&cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>