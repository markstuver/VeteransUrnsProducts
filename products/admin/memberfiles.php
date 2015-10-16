<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

@set_time_limit(0);
include "checklicense.inc.php";
$noinactivitycheck = "true";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/filemanager.inc.php";

// Get context help for this page...
$contexthelppage = "memberfiles";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($userid == "1") {
	header("Location: editmember.php");
	exit;
}

if (!$membershops || !isset($memberuploadsize) || $memberuploadsize == 0) {
	header("Location: editmember.php");
	exit;
}

// Get member info...
$result = @mysqli_query($db, "SELECT * FROM user WHERE userid='$userid'");
$username = @mysqli_result($result,0,"username");

// Get current storage usage...
$currentsize = ashop_getdirsize("$ashoppath/members/files/$username");

// Show member files page...
if (!empty($username) && is_dir("$ashoppath/members/files/$username")) {

  // Show edit form...
  if (!$edited) {

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
		  if ((document.filesform.uploadfile1.value != '') || (document.filesform.uploadfile2.value != '') || (document.filesform.uploadfile3.value != '') || (document.filesform.uploadfile4.value != '') || (document.filesform.uploadfile5.value != '')) {
			  w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
		  }
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
        </script>
        <div class=\"heading\">".FILES."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><span class=\"formtitle\"><p>".YOUAREUSING." $currentsize ".MEGABYTES." ".OF." $memberuploadsize ".MEGABYTES.".</p>";

		if ($error) {
			echo "<p class=\"error\">".ERROR."<br>";
			if ($error=="filesize") echo EXCEEDEDSPACE."</p>";
			else if ($error=="filetype") echo FILETYPENOTALLOWED."</p>";
		}

        echo "
        <form action=\"memberfiles.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"filesform\">
		<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\" width=\"110\">".EXISTINGFILES.":</a></td><td class=\"formlabel\">";
		// List existing files...
		$numberoffiles = 0;
		$findfile = opendir("$ashoppath/members/files/$username");
		if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
			if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashoppath/members/files/$username/$foundfile")) {
				$filesize = filesize("$ashoppath/members/files/$username/$foundfile");
				$filesize = $filesize/1024;
				$filesize = $filesize/1024;
				$filesize = number_format($filesize,2,'.','');
				echo "<span class=\"formlabel\"><b><a href=\"$ashopurl/members/$username/$foundfile\" target=\"_blank\">$foundfile</a></b> $filesize ".MEGABYTES;
				$foundfile = str_replace(".","__",$foundfile);
				echo " <input type=\"checkbox\" name=\"deletefile$foundfile\"> ".THEWORDDELETE."</span><br>";
				$numberoffiles++;
			}
		}
		if ($numberoffiles == 0) echo "<span class=\"formlabel\">".NOFILES."</span>";
		echo "</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 1:</a></td><td class=\"formlabel\"><input type=\"file\" name=\"uploadfile1\" size=\"50\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 2:</a></td><td class=\"formlabel\"><input type=\"file\" name=\"uploadfile2\" size=\"50\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 3:</a></td><td class=\"formlabel\"><input type=\"file\" name=\"uploadfile3\" size=\"50\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 4:</a></td><td class=\"formlabel\"><input type=\"file\" name=\"uploadfile4\" size=\"50\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 5:</a></td><td class=\"formlabel\"><input type=\"file\" name=\"uploadfile5\" size=\"50\"></td></tr>
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\">
		<td align=\"right\"><input type=\"submit\" name=\"upload\" value=\"".UPLOAD."\" onClick=\"uploadmessage()\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form></td></tr></table>$footer";
  } else {
	  if ($delete) {
		  $findfile = opendir("$ashoppath/members/files/$username");
		  if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
			  if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashoppath/members/files/$username/$foundfile")) {
				  $foundfile = str_replace(".","__",$foundfile);
				  $deletefilestring = "deletefile$foundfile";
				  $foundfile = str_replace("__",".",$foundfile);
				  if ($_POST["$deletefilestring"] == "on") unlink("$ashoppath/members/files/$username/$foundfile");
			  }
		  }
	  } else {

	  $uploadfile1 = str_replace("\t","\\t",$uploadfile1);
	  $uploadfile2 = str_replace("\t","\\t",$uploadfile2);
	  $uploadfile3 = str_replace("\t","\\t",$uploadfile3);
	  $uploadfile4 = str_replace("\t","\\t",$uploadfile4);
	  $uploadfile5 = str_replace("\t","\\t",$uploadfile5);
	  $totalsize = $currentsize;
	  $acceptedextensions = array("jpg","jpeg","gif","png","swf","css","js","htm","html","pdf");
	  if (is_uploaded_file($uploadfile1)) {
		  $thissize = $uploadfile1_size;
		  $thissize = $thissize/1024;
		  $thissize = $thissize/1024;
		  $thissize = number_format($thissize,2,'.','');
		  $totalsize += $thissize;
		  $fileinfo = pathinfo("$uploadfile1_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else if ($totalsize <= $memberuploadsize) {
			  $uploadfile1_name = str_replace(",","",$uploadfile1_name);
			  $uploadfilename1 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile1_name)));
			  $uploadfilename1 = preg_replace("/%E5|%E4/","a",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/%F6/","o",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/%C5|%C4/","A",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/%D6/","O",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename1);
			  $uploadfilename1 = strtolower($uploadfilename1);
			  if (file_exists("$ashoppath/members/files/$username/$uploadfilename1")) unlink("$ashoppath/members/files/$username/$uploadfilename1");
			  move_uploaded_file($uploadfile1, "$ashoppath/members/files/$username/$uploadfilename1");
			  @chmod("$ashoppath/members/files/$username/$uploadfilename1", 0666);
		  } else $error = "filesize";
	  }
	  if (is_uploaded_file($uploadfile2)) {
		  $thissize = $uploadfile2_size;
		  $thissize = $thissize/1024;
		  $thissize = $thissize/1024;
		  $thissize = number_format($thissize,2,'.','');
		  $totalsize += $thissize;
		  $fileinfo = pathinfo("$uploadfile2_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else if ($totalsize <= $memberuploadsize) {
			  $uploadfile2_name = str_replace(",","",$uploadfile2_name);
			  $uploadfilename2 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile2_name)));
			  $uploadfilename2 = preg_replace("/%E5|%E4/","a",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/%F6/","o",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/%C5|%C4/","A",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/%D6/","O",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename2);
			  $uploadfilename2 = strtolower($uploadfilename2);
			  if (file_exists("$ashoppath/members/files/$username/$uploadfilename2")) unlink("$ashoppath/members/files/$username/$uploadfilename2");
			  move_uploaded_file($uploadfile2, "$ashoppath/members/files/$username/$uploadfilename2");
			  @chmod("$ashoppath/members/files/$username/$uploadfilename2", 0666);
		  } else $error = "filesize";
	  }
	  if (is_uploaded_file($uploadfile3)) {
		  $thissize = $uploadfile3_size;
		  $thissize = $thissize/1024;
		  $thissize = $thissize/1024;
		  $thissize = number_format($thissize,2,'.','');
		  $totalsize += $thissize;
		  $fileinfo = pathinfo("$uploadfile3_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else if ($totalsize <= $memberuploadsize) {
			  $uploadfile3_name = str_replace(",","",$uploadfile3_name);
			  $uploadfilename3 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile3_name)));
			  $uploadfilename3 = preg_replace("/%E5|%E4/","a",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/%F6/","o",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/%C5|%C4/","A",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/%D6/","O",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename3);
			  $uploadfilename3 = strtolower($uploadfilename3);
			  if (file_exists("$ashoppath/members/files/$username/$uploadfilename3")) unlink("$ashoppath/members/files/$username/$uploadfilename3");
			  move_uploaded_file($uploadfile3, "$ashoppath/members/files/$username/$uploadfilename3");
			  @chmod("$ashoppath/members/files/$username/$uploadfilename3", 0666);
		  } else $error = "filesize";
	  } 
	  if (is_uploaded_file($uploadfile4)) {
		  $thissize = $uploadfile4_size;
		  $thissize = $thissize/1024;
		  $thissize = $thissize/1024;
		  $thissize = number_format($thissize,2,'.','');
		  $totalsize += $thissize;
		  $fileinfo = pathinfo("$uploadfile4_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else if ($totalsize <= $memberuploadsize) {
			  $uploadfile4_name = str_replace(",","",$uploadfile4_name);
			  $uploadfilename4 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile4_name)));
			  $uploadfilename4 = preg_replace("/%E5|%E4/","a",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/%F6/","o",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/%C5|%C4/","A",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/%D6/","O",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename4);
			  $uploadfilename4 = strtolower($uploadfilename4);
			  if (file_exists("$ashoppath/members/files/$username/$uploadfilename4")) unlink("$ashoppath/members/files/$username/$uploadfilename4");
			  move_uploaded_file($uploadfile4, "$ashoppath/members/files/$username/$uploadfilename4");
			  @chmod("$ashoppath/members/files/$username/$uploadfilename4", 0666);
		  } else $error = "filesize";
	  } 
	  if (is_uploaded_file($uploadfile5)) {
		  $thissize = $uploadfile5_size;
		  $thissize = $thissize/1024;
		  $thissize = $thissize/1024;
		  $thissize = number_format($thissize,2,'.','');
		  $totalsize += $thissize;
		  $fileinfo = pathinfo("$uploadfile5_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else if ($totalsize <= $memberuploadsize) {
			  $uploadfile5_name = str_replace(",","",$uploadfile5_name);
			  $uploadfilename5 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile5_name)));
			  $uploadfilename5 = preg_replace("/%E5|%E4/","a",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/%F6/","o",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/%C5|%C4/","A",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/%D6/","O",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename5);
			  $uploadfilename5 = strtolower($uploadfilename5);
			  if (file_exists("$ashoppath/members/files/$username/$uploadfilename5")) unlink("$ashoppath/members/files/$username/$uploadfilename5");
			  move_uploaded_file($uploadfile5, "$ashoppath/members/files/$username/$uploadfilename5");
			  @chmod("$ashoppath/members/files/$username/$uploadfilename5", 0666);
		  } else $error = "filesize";
	  }
	  }

	  if ($error) header ("Location: memberfiles.php?error=$error");
	  else header("Location: memberfiles.php");
  }
}
?>