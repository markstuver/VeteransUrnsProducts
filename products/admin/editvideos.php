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

// Change the order of product files...
if ($filemoveup) {
	$sql="UPDATE productfiles SET ordernumber='$prevordno' WHERE fileid='$thisfileid'";
    $result = @mysqli_query($db, $sql);
	$sql="UPDATE productfiles SET ordernumber='$thisordno' WHERE fileid='$prevfileid'";
    $result = @mysqli_query($db, $sql);
}

// Get information about the product from the database...
$sql="SELECT * FROM product WHERE productid = '$productid'";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productname = str_replace("\"", "&quot;", $productname);
$filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid' ORDER BY ordernumber");
while($filerow = @mysqli_fetch_array($filesresult)) {
	$filename[$filerow["fileid"]] = $filerow["filename"];
	$fileordernumber[$filerow["fileid"]] = $filerow["ordernumber"];
	$filetitle[$filerow["fileid"]] = $filerow["name"];
	$filedescription[$filerow["fileid"]] = $filerow["description"];
	$filetags[$filerow["fileid"]] = $filerow["tags"];
}
$filesresult = @mysqli_query($db, "SELECT * FROM productpreviewfiles WHERE productid='$productid'");
$numberofpreviewfiles = @mysqli_num_rows($filesresult);
if (!empty($numberofpreviewfiles)) $previewfilerow = @mysqli_fetch_array($filesresult);

// Handle editing of the product video files...
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
			  $fileinfo = pathinfo("$foundfile");
			  $extension = strtolower($fileinfo["extension"]);
			  if ($extension == "mp4" || $extensions == "flv") {
				  $uploadedfiles[$numberoffiles] = $foundfile;
				  $numberoffiles++;
			  }
		  }
	  }

	  // Check for uploaded preview files...
	  $numberofuploadedpreviewfiles = 0;
	  unset($findfile);
	  if (is_dir("$ashoppath/previews")) $findfile = opendir("$ashoppath/previews");
	  if ($findfile) while (false !== ($foundfile = readdir($findfile))) {
		  if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/index/", $foundfile) && !preg_match("/maillog/", $foundfile) && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashoppath/previews/$foundfile")) {
			  $uploadedpreviewfiles[$numberofuploadedpreviewfiles] = $foundfile;
			  $numberofuploadedpreviewfiles++;
		  }
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
        <div class=\"heading\">".EDITPRODUCTVIDEOS."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>";

		if ($error) {
			echo "<p class=\"error\">".ERROR."<br>";
			if ($error=="extension") echo MUSTBEFLVORMP4."</p>";
			elseif ($error=="previews") echo CHECKPERMISSIONS2."</p>";
		}

        echo "
        <form action=\"editvideos.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"previewform\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image16','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image16\" align=\"absmiddle\" onclick=\"return overlib('$tip17');\" onmouseout=\"return nd();\"></a> ".PREVIEWVIDEOFILE.":</td><td class=\"formlabel\" align=\"left\">";
		if (!empty($numberofpreviewfiles)) {
			$previewfilename = $previewfilerow["filename"];
			$previewfileid = $previewfilerow["fileid"];
			$size = filesize("s3://$awsbucket/$awsdirectory/$previewfilename");
			$filesize = floor($size/1048576);
			if ($filesize == 0) {
				$filesize = floor($size/1024);
				if ($filesize == 0) $filesize = $size." bytes";
				else $filesize .= " kB";
			} else $filesize .= " MB";

			if (!empty($previewfilename)) {
				echo "<span class=\"formlabel\"><b>$previewfilename</b> ($filesize) <input type=\"checkbox\" name=\"deletepreview[0]\" value=\"$previewfileid\"> ".THEWORDDELETE."</span><br>";
			}
		}
		echo "<input type=\"file\" name=\"demofile\"> <span class=\"sm\">".OPTIONAL."<br>".LEAVEBLANKTOKEEP."</span>";
		if ($numberofuploadedpreviewfiles) {
			echo "<br>".ORCHOOSEPREVIOUSLY."<br><select name=\"uploadedpreviewfilename\"><option value=\"\"></option>";
			for($i = 0; $i < $numberofuploadedpreviewfiles; $i++) {
				echo "<option value=\"$uploadedpreviewfiles[$i]\">$uploadedpreviewfiles[$i]</option>";
			}
			echo "</select>";
		}
		echo "</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"";
		if ($add) echo "add";
		else echo "productid";
		echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"upload\" value=\"".UPLOAD."\" onClick=\"uploadmessage()\"> <input type=\"submit\" name=\"upload\" value=\"".THEWORDDELETE."\"></td></tr></table></form><br />
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		<form action=\"editvideos.php\" method=\"post\" name=\"editform\">
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> ".PRODUCTVIDEOFILES.":</td><td class=\"formlabel\" align=\"left\">";
		if (count($filename) > 0) {
			foreach($filename as $thisfileid=>$thisfilename) {
				$size = filesize("s3://$awsbucket/$awsdirectory/$thisfilename");
				$filesize = floor($size/1048576);
				if ($filesize == 0) {
					$filesize = floor($size/1024);
					if ($filesize == 0) $filesize = $size." bytes";
					else $filesize .= " kB";
				} else $filesize .= " MB";
				echo "<span class=\"formlabel\"><b>{$filetitle["$thisfileid"]}</b><br><i>{$filedescription["$thisfileid"]}</i><br>Tags: {$filetags["$thisfileid"]}<br><b>$thisfilename</b> ($filesize) <a href=\"editvideo.php?fileid=$thisfileid&productid=$productid&cat=$cat&search=$search&pid=$pid&resultpage=$resultpage\"><img src=\"images/icon_edit.gif\" alt=\"".EDIT."\" title=\"".EDIT."\" border=\"0\" /></a> <input type=\"image\" src=\"images/icon_trash.gif\" name=\"deleteprodfile$thisfileid\" alt=\"".THEWORDDELETE."\" title=\"".THEWORDDELETE."\" border=\"0\" />";
				if ($filepreviousorderno) echo " <a href=\"editvideos.php?thisordno=$fileordernumber[$thisfileid]&prevordno=$filepreviousorderno&thisfileid=$thisfileid&prevfileid=$previousfileid&filemoveup=true&productid=$productid\"><img src=\"images/icon_up.gif\" alt=\"".MOVEUP."\" title=\"".MOVEUP."\" border=\"0\" /></a>";
				echo "</span><br>";
				$filepreviousorderno = $fileordernumber[$thisfileid];
				$previousfileid = $thisfileid;
				echo "<hr>";
			}
		}
		echo "</td></tr>
		<input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">
		</form>
		<form action=\"editvideos.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\" id=\"productform\" style=\"margin-bottom: 0px;\">
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".NAME.":</td><td><input type=\"text\" name=\"title\" size=\"35\" value=\"$title\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".DESCRIPTION.":</td><td><textarea name=\"description\" cols=\"30\" rows=\"5\">".htmlentities(stripslashes($description), ENT_QUOTES)."</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".VIDEOTAGS.":</td><td><input type=\"text\" name=\"tags\" size=\"35\" value=\"$tags\"> <span class=\"sm\">".SEPARATEWITHCOMMA."</span></td></tr>";
		if ($numberoffiles) {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">&nbsp;</td><td>".ORCHOOSEPREVIOUSLY."<br><select name=\"uploadedfilename\"><option value=\"\"></option>";
			for($i = 0; $i < $numberoffiles; $i++) {
				echo "<option value=\"$uploadedfiles[$i]\">$uploadedfiles[$i]</option>";
			}
			echo "</select></td></tr>";
		}
		echo "<input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"";
		if ($add) echo "add";
		else echo "productid";
		echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"mainuploadedfilename\" id=\"mainuploadedfilename\" value=\"\"></form>
		<tr><td>&nbsp;</td><td>
		<div id=\"uploadbox\">
			<span id=\"uploadcontainer\">
			<span class=\"fileinput-button\" id=\"fileinput-button\">
				<span>".SELECTFILE."</span>
				<input id=\"fileupload\" type=\"file\" name=\"files[]\" multiple>
			</span>
			</span>
			<div id=\"progress\" class=\"progress\">
				<div class=\"bar\" style=\"width: 0%;\"></div>
			</div>
		</div></td></tr></table>";
		echo "<form action=\"editvideos.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"finishform\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#FFFFFF\">
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"";
		if ($add) echo "add";
		else echo "productid";
		echo "\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" name=\"finish\" value=\"".FINISH."\"></td></tr></table></form>
		</td></tr></table>
		<script src=\"//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js\"></script>
		<script src=\"js/vendor/jquery.ui.widget.js\"></script>
		<script src=\"js/jquery.iframe-transport.js\"></script>
		<script src=\"js/jquery.fileupload.js\"></script>
		<script>
			function dump(obj) {
				var out = '';
				for (var i in obj) {
					out += i + \": \" + obj[i] + \"\\n\";
				}
				alert(out);
			}
			$(function () {
				$('#fileupload').fileupload({
					maxChunkSize: 5000000,
					dataType: 'json',
					url: 'upload.php',
					formData: [
					{
						name: 'type',
						value: 1
					}
					],
					add: function (e, data) {
						data.context = $('<input type=\"submit\" />').val('".UPLOAD."')
						.appendTo(\"#uploadbox\")
						.click(function () {
							data.submit();
							$('#progress').show();
							$('#uploadcontainer').html('<span class=\"formlabel\" style=\"font-weight: bold;\">".UPLOADINGPLEASEWAIT."</span><br><br>');
						});
						$('#uploadcontainer').html('<span class=\"formlabel\" style=\"font-weight: bold;\">'+data.files[0].name+' <a href=\"\"><img src=\"images/icon_delete.gif\" alt=\"".THEWORDDELETE."\" title=\"".THEWORDDELETE."\" /></a></span><br><br>');
					},
					done: function (e, data) {
						$.each(data.result.files, function (index, file) {
							setTimeout(function() {
								$('#uploadcontainer').html('<span class=\"formlabel\" style=\"font-weight: bold;\">".TRANSFERTOS3."</span><br><br>');
								$('#mainuploadedfilename').val(file.name);
								$('#productform').submit();
							}, 1000);
						});
					}
				});
			});
			$('#fileupload').fileupload({
				/* ... */
				progressall: function (e, data) {
					var progress = parseInt(data.loaded / data.total * 100, 10);
					$('#progress .bar').css(
					'width',
					progress + '%'
				);
			}
		});
		</script>
		$footer";
  } else {
	if (!empty($deletepreview) && is_array($deletepreview)) {
		$result = @mysqli_query($db, "SELECT * FROM productpreviewfiles WHERE fileid='{$deletepreview[0]}' AND productid='$productid'");
		$previewfilerow = @mysqli_fetch_array($result);
		$sql="DELETE FROM productpreviewfiles WHERE fileid='{$deletepreview[0]}' AND productid='$productid'";
		$result = @mysqli_query($db, $sql);
		$deletepreviewfilename = $previewfilerow["filename"];
		$deletepreviewfileinfo = pathinfo($deletepreviewfilename);
		$deletepreviewextension = strtolower($deletepreviewfileinfo["extension"]);
		if (($deletepreviewextension == "mp4" || $deletepreviewextension == "flv") && file_exists("$ashoppath/includes/aws/aws-config.php")) {
			// Delete the file from the S3 bucket...
			$result = $client->deleteObject(array(
				'Bucket'     => $awspreviewsbucket,
				'Key'        => $awsdirectory."/".$deletepreviewfilename
			));
		}
	}

	if (count($filename) > 0) {
		reset($filename);

		foreach($filename as $thisfileid=>$thisfilename) {
			$deleteprodfile = "off";
			if (!empty($_POST["deleteprodfile{$thisfileid}_x"])) $deleteprodfile = "on";
			if ($deleteprodfile == "on") {
				$sql="DELETE FROM productfiles WHERE fileid='$thisfileid' AND productid='$productid'";
				$result = @mysqli_query($db, $sql);
				$fileinfo = pathinfo("$thisfilename");
				$extension = strtolower($fileinfo["extension"]);
				if (($extension == "mp4" || $extensions == "flv") && file_exists("$ashoppath/includes/aws/aws-config.php")) {
					// Delete the file from the S3 bucket...
					$result = $client->deleteObject(array(
						'Bucket'     => $awsbucket,
						'Key'        => $awsdirectory."/".$thisfilename
					));
				}
			}
		}
	}

	if (!is_writeable("$ashopspath/products")) $error = "products";

	if ($uploadedfilename) {
	   $uploadfilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadedfilename)));
	   $uploadfilename = preg_replace("/%E5|%E4/","a",$uploadfilename);
	   $uploadfilename = preg_replace("/%F6/","o",$uploadfilename);
	   $uploadfilename = preg_replace("/%C5|%C4/","A",$uploadfilename);
	   $uploadfilename = preg_replace("/%D6/","O",$uploadfilename);
	   $uploadfilename = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename);
	   $fileinfo = pathinfo("$uploadfilename");
	   $extension = strtolower($fileinfo["extension"]);
	   $result = @mysqli_query($db, "SELECT MAX(fileid) AS maxfileid FROM productfiles");
	   $uploadfileid = @mysqli_result($result,0,"maxfileid")+1;
	   $result = @mysqli_query($db, "INSERT INTO productfiles (productid, filename, fileid, storage, name, description, tags) VALUES ('$productid', '$uploadfilename', '$uploadfileid', '1', '$title', '$description', '$tags')");
	   $id = @mysqli_insert_id($db);
	   @mysqli_query($db, "UPDATE productfiles SET ordernumber='$id' WHERE id='$id'");
	   if (file_exists("$ashopspath/products/$uploadfileid")) unlink("$ashopspath/products/$uploadfileid");
	   if ($userid == "1") rename("$ashopspath/products/$uploadedfilename", "$ashopspath/products/$uploadfileid");
	   else rename("$ashopspath/products/$username/$uploadedfilename", "$ashopspath/products/$uploadfileid");
	   if ($extension != "mp4" && $extension != "flv") $error = "extension";
	   else {

		   if ($extension == "mp4") $contenttype = "mp4";
		   else $contenttype = "x-flv";

		   // Upload the file to the S3 bucket...
		   $result = $client->putObject(array(
			   'Bucket'     => $awsbucket,
			   'Key'        => $awsdirectory."/".$uploadfilename,
			   'SourceFile' => "$ashopspath/products/$uploadfileid",
			   'ContentDisposition'   => 'inline',
			   'ContentType' => "video/$contenttype"
		   ));
		   $client->waitUntilObjectExists(array(
			   'Bucket' => $awsbucket,
			   'Key'    => $awsdirectory."/".$uploadfilename
		   ));
	   }
	   unlink("$ashopspath/products/$uploadfileid");
	}

	// Handle preview/demo files...
	if (!is_writeable("$ashoppath/previews")) $error = "previews";
	else {
		$demofile = str_replace("\t","\\t",$demofile);
		if (is_uploaded_file($demofile)) {
			$uploaddemofilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($demofile_name)));
			$uploaddemofilename = preg_replace("/%E5|%E4/","a",$uploaddemofilename);
			$uploaddemofilename = preg_replace("/%F6/","o",$uploaddemofilename);
			$uploaddemofilename = preg_replace("/%C5|%C4/","A",$uploaddemofilename);
			$uploaddemofilename = preg_replace("/%D6/","O",$uploaddemofilename);
			$uploaddemofilename = preg_replace("/\+\+\+|\+\+/","+",$uploaddemofilename);
			$demofileinfo = pathinfo("$uploaddemofilename");
			$demoextension = strtolower($demofileinfo["extension"]);
			$result = @mysqli_query($db, "INSERT INTO productpreviewfiles (productid, filename, storage) VALUES ('$productid', '$uploaddemofilename', '1')");
			move_uploaded_file($demofile, "$ashopspath/previews/$uploaddemofilename");
			if ($demoextension != "mp4" && $demoextension != "flv") $error = "extension";
			else {

				if ($demoextension == "mp4") $contenttype = "mp4";
				else $contenttype = "x-flv";

				// Upload the file to the S3 bucket...
				$result = $client->putObject(array(
					'Bucket'     => $awspreviewsbucket,
					'Key'        => $awsdirectory."/".$uploaddemofilename,
					'SourceFile' => "$ashopspath/previews/$uploaddemofilename",
					'ContentDisposition'   => 'inline',
					'ContentType' => "video/$contenttype"
				));
				$client->waitUntilObjectExists(array(
					'Bucket' => $awspreviewsbucket,
					'Key'    => $awsdirectory."/".$uploaddemofilename
				));
			}
			unlink("$ashopspath/previews/$uploaddemofilename");
		} else if ($uploadedpreviewfilename) {
			if (!is_dir("$ashoppath/previews/$productid")) {
				mkdir("$ashoppath/previews/$productid");
				@chmod("$ashoppath/previews/$productid", 0777);
			}
			copy("$ashoppath/previews/$uploadedpreviewfilename", "$ashoppath/previews/$productid/$uploadedpreviewfilename");
			@chmod("$ashoppath/previews/$productid/$uploadedpreviewfilenam", 0777);
		}
	}

	if (!$finish) {
		if ($add) $prodidstring = "&add=$add";
		else $prodidstring = "&productid=$productid";
		if ($error) $errorstring = "&error=$error";
		if ($thumbnailautosized) {
			if ($originalimagekept) $msgstring = "&msg=1";
			else $msgstring = "&msg=2";
		}
		header ("Location: editvideos.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage$prodidstring$errorstring$msgstring");
	} 
	else if ($error) header ("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&error=$error&resultpage=$resultpage");
    else header("Location: editcatalogue.php?cat=$cat&search=$search&pid=$pid&resultpage=$resultpage");
  }
}
?>