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
$noinactivitycheck = "true";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/filemanager.inc.php";

// Get context help for this page...
$contexthelppage = "memberfiles";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($userid != "1") {
	if (!$membershops || !isset($memberuploadsize) || $memberuploadsize == 0) {
		header("Location: editmember.php");
		exit;
	}
	header("Location: memberfiles.php");
	exit;
}

// Keep the directory variable safe...
if (!empty($directory)) {
	$directory = str_ireplace("../","",$directory);
	$directory = str_ireplace("./","",$directory);
	if ($directory == "/") $directory = "";
	if ($directory == "//") $directory = "";
	$directory = str_ireplace("..\\","",$directory);
	$directory = str_ireplace(".\\","",$directory);
	if ($directory == "\\\\") $directory = "";
	if ($directory == "\\") $directory = "";
	if (substr($directory,0,1) == "/" || substr($directory,0,1) == "\\" || substr($directory,1,1) == ":") $directory = "";
	$directory = str_ireplace("admin","",$directory);
	$directory = str_ireplace("affiliate","",$directory);
	$directory = str_ireplace("emerchant","",$directory);
	$directory = str_ireplace("wholesale","",$directory);
	$directory = str_ireplace("automation","",$directory);
	$directory = str_ireplace("members","",$directory);
	$directory = str_ireplace("twitter","",$directory);
	$directory = str_ireplace("ioncube","",$directory);
	$directory = str_ireplace("includes","",$directory);
}

// Show page header...
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
		function viewlinkurl(directory,file) 
		{
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=500, height=200\");
			w.document.write('<html><head><title>".FILEURL."</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\"><center><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".ABSOLUTEURL.":<br><textarea name=\"description\" cols=\"45\" rows=\"2\">$ashopurl/'+directory+'/'+file+'</textarea><br><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".RELATIVEURL.": <input type=\"text\" size=\"45\" value=\"'+directory+'/'+file+'\"><br><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSEWINDOW."</a></font></font><br></center></body></html>');
			return false;
	    }
        </script>
        <div class=\"heading\">".FILEMANAGER."</div><table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\">";
	
	if ($error) {
		echo "<p class=\"error\">".ERROR."<br>";
		if ($error=="filetype") echo FILETYPENOTALLOWED."</p>";
	}

	echo "<p><table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">";

	// Define excluded directories...
	$excludedirs = array("admin","affiliate","automation","emerchant","includes","ioncube","members","twitter","wholesale");

	// Show directories...
	if (!empty($directory) && is_dir("$ashoppath/$directory") && is_writeable("$ashoppath/$directory")) {
		$uponelevel = substr($directory,0,strrpos($directory,"/"));
		$viewpath = "$ashoppath/$directory";
		$startdir = "$directory/";
		echo "<tr><td align=\"left\" colspan=\"2\" class=\"formlabel\"><a href=\"filemanager.php?directory=$uponelevel\"><img src=\"images/icon_uplevel.gif\" alt=\"".UPONELEVEL."\" title=\"".UPONELEVEL."\" border=\"0\" style=\"vertical-align: text-bottom;\"></a> [ <a href=\"filemanager.php?directory=$uponelevel\">".UPONELEVEL."</a> ] <b><font size=\"3\">$directory ...</font></b></td></tr>";
	} else {
		$viewpath = $ashoppath;
		$startdir = "";
	}
	$numberofdirectories = 0;
	$findfile = opendir("$viewpath");
	if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
		if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && substr($foundfile, 0, 1) != "_" && is_dir("$ashoppath/$startdir$foundfile") && is_writeable("$ashoppath/$startdir$foundfile") && !in_array($foundfile,$excludedirs)) {
			if ($numberofdirectories == 0) 	echo "<tr><td width=\"14\">&nbsp;</td><td class=\"formlabel\" align=\"left\">";
			$numberofdirectories++;
			echo "<img src=\"images/icon_directory.gif\" alt=\"".DIRECTORY."\" title=\"".DIRECTORY."\"> <span class=\"formlabel\"><b><a href=\"filemanager.php?directory=$startdir$foundfile\">$foundfile</a><br>";
		}
	}
	if ($numberofdirectories > 0) echo "</td></tr>";
	echo "</table></p>";
}

// Show files...
if (!empty($directory) && is_dir("$ashoppath/$directory") && is_writeable("$ashoppath/$directory")) {

  // Show edit form...
  if (!$edited) {
        echo "
        <form action=\"filemanager.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"filesform\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\" width=\"110\">&nbsp;</a></td><td class=\"formlabel\" align=\"left\">".FILENAME."</td><td class=\"formlabel\" align=\"left\">".THEFILESIZE."</td><td>&nbsp;</td>";
		// List existing files...
		$numberoffiles = 0;
		$findfile = opendir("$ashoppath/$directory");
		if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
			if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && substr($foundfile, 0, 7) != "maillog" && !strstr($foundfile,".spc") && !is_dir("$ashoppath/$directory/$foundfile")) {
				$filesize = filesize("$ashoppath/$directory/$foundfile");
				$filesize = $filesize/1024;
				$filesize = number_format($filesize,2,'.','');
				echo "<tr><td align=\"right\"><a href=\"\" onClick=\"viewlinkurl('$directory','$foundfile'); return false;\" target=\"_blank\"><img src=\"images/icon_link.gif\" border=\"0\" alt=\"".VIEWLINKURL."\" title=\"".VIEWLINKURL."\"></a></td><td class=\"formlabel\" align=\"left\"><b><a href=\"$ashopurl/$directory/$foundfile\" target=\"_blank\">$foundfile</a></b></td><td class=\"formlabel\" align=\"left\">$filesize ".KILOBYTES."</td>";
				$foundfile = str_replace(".","__",$foundfile);
				echo "<td class=\"formlabel\" align=\"left\"><input type=\"checkbox\" name=\"deletefile$foundfile\"> ".THEWORDDELETE."</td></tr>";
				$numberoffiles++;
			}
		}
		if ($numberoffiles == 0) echo "<span class=\"formlabel\">".NOFILES."</span>";
		echo "</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 1:</a></td><td class=\"formlabel\" colspan=\"3\" align=\"left\"><input type=\"file\" name=\"uploadfile1\" size=\"40\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 2:</a></td><td class=\"formlabel\" colspan=\"3\" align=\"left\"><input type=\"file\" name=\"uploadfile2\" size=\"40\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 3:</a></td><td class=\"formlabel\" colspan=\"3\" align=\"left\"><input type=\"file\" name=\"uploadfile3\" size=\"40\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 4:</a></td><td class=\"formlabel\" colspan=\"3\" align=\"left\"><input type=\"file\" name=\"uploadfile4\" size=\"40\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".UPLOADFILE." 5:</a></td><td class=\"formlabel\" colspan=\"3\" align=\"left\"><input type=\"file\" name=\"uploadfile5\" size=\"40\"></td></tr>
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"directory\" value=\"$directory\"><input type=\"hidden\" name=\"edit\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\">
		<td align=\"right\" colspan=\"3\" align=\"left\"><input type=\"submit\" name=\"upload\" value=\"".UPLOAD."\" onClick=\"uploadmessage()\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form></td></tr></table>$footer";
  } else {
	  if ($delete) {
		  $findfile = opendir("$ashoppath/$directory");
		  if (isset($findfile)) while (false !== ($foundfile = readdir($findfile))) {
			  if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashoppath/$directory/$foundfile")) {
				  $foundfile = str_replace(".","__",$foundfile);
				  $deletefilestring = "deletefile$foundfile";
				  $foundfile = str_replace("__",".",$foundfile);
				  if ($_POST["$deletefilestring"] == "on") unlink("$ashoppath/$directory/$foundfile");
			  }
		  }
	  } else {

	  $uploadfile1 = str_replace("\t","\\t",$uploadfile1);
	  $uploadfile2 = str_replace("\t","\\t",$uploadfile2);
	  $uploadfile3 = str_replace("\t","\\t",$uploadfile3);
	  $uploadfile4 = str_replace("\t","\\t",$uploadfile4);
	  $uploadfile5 = str_replace("\t","\\t",$uploadfile5);
	  $acceptedextensions = array("jpg","jpeg","gif","png","swf","css","js","htm","html","pdf","doc","docx","mp3","m3u","xml","avi","wmv");
	  if (is_uploaded_file($uploadfile1)) {
		  $fileinfo = pathinfo("$uploadfile1_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else {
			  $uploadfile1_name = str_replace(",","",$uploadfile1_name);
			  $uploadfilename1 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile1_name)));
			  $uploadfilename1 = preg_replace("/%E5|%E4/","a",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/%F6/","o",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/%C5|%C4/","A",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/%D6/","O",$uploadfilename1);
			  $uploadfilename1 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename1);
			  $uploadfilename1 = strtolower($uploadfilename1);
			  if (file_exists("$ashoppath/$directory/$uploadfilename1")) unlink("$ashoppath/$directory/$uploadfilename1");
			  move_uploaded_file($uploadfile1, "$ashoppath/$directory/$uploadfilename1");
			  @chmod("$ashoppath/$directory/$uploadfilename1", 0666);
		  }
	  }
	  if (is_uploaded_file($uploadfile2)) {
		  $fileinfo = pathinfo("$uploadfile2_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else {
			  $uploadfile2_name = str_replace(",","",$uploadfile2_name);
			  $uploadfilename2 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile2_name)));
			  $uploadfilename2 = preg_replace("/%E5|%E4/","a",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/%F6/","o",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/%C5|%C4/","A",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/%D6/","O",$uploadfilename2);
			  $uploadfilename2 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename2);
			  $uploadfilename2 = strtolower($uploadfilename2);
			  if (file_exists("$ashoppath/$directory/$uploadfilename2")) unlink("$ashoppath/$directory/$uploadfilename2");
			  move_uploaded_file($uploadfile2, "$ashoppath/$directory/$uploadfilename2");
			  @chmod("$ashoppath/$directory/$uploadfilename2", 0666);
		  }
	  }
	  if (is_uploaded_file($uploadfile3)) {
		  $fileinfo = pathinfo("$uploadfile3_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else {
			  $uploadfile3_name = str_replace(",","",$uploadfile3_name);
			  $uploadfilename3 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile3_name)));
			  $uploadfilename3 = preg_replace("/%E5|%E4/","a",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/%F6/","o",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/%C5|%C4/","A",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/%D6/","O",$uploadfilename3);
			  $uploadfilename3 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename3);
			  $uploadfilename3 = strtolower($uploadfilename3);
			  if (file_exists("$ashoppath/$directory/$uploadfilename3")) unlink("$ashoppath/$directory/$uploadfilename3");
			  move_uploaded_file($uploadfile3, "$ashoppath/$directory/$uploadfilename3");
			  @chmod("$ashoppath/$directory/$uploadfilename3", 0666);
		  }
	  } 
	  if (is_uploaded_file($uploadfile4)) {
		  $fileinfo = pathinfo("$uploadfile4_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else {
			  $uploadfile4_name = str_replace(",","",$uploadfile4_name);
			  $uploadfilename4 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile4_name)));
			  $uploadfilename4 = preg_replace("/%E5|%E4/","a",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/%F6/","o",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/%C5|%C4/","A",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/%D6/","O",$uploadfilename4);
			  $uploadfilename4 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename4);
			  $uploadfilename4 = strtolower($uploadfilename4);
			  if (file_exists("$ashoppath/$directory/$uploadfilename4")) unlink("$ashoppath/$directory/$uploadfilename4");
			  move_uploaded_file($uploadfile4, "$ashoppath/$directory/$uploadfilename4");
			  @chmod("$ashoppath/$directory/$uploadfilename4", 0666);
		  }
	  } 
	  if (is_uploaded_file($uploadfile5)) {
		  $fileinfo = pathinfo("$uploadfile5_name");
		  $extension = $fileinfo["extension"];
		  $extension = strtolower($extension);
		  if (!in_array($extension,$acceptedextensions)) $error = "filetype";
		  else {
			  $uploadfile5_name = str_replace(",","",$uploadfile5_name);
			  $uploadfilename5 = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile5_name)));
			  $uploadfilename5 = preg_replace("/%E5|%E4/","a",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/%F6/","o",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/%C5|%C4/","A",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/%D6/","O",$uploadfilename5);
			  $uploadfilename5 = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename5);
			  $uploadfilename5 = strtolower($uploadfilename5);
			  if (file_exists("$ashoppath/$directory/$uploadfilename5")) unlink("$ashoppath/$directory/$uploadfilename5");
			  move_uploaded_file($uploadfile5, "$ashoppath/$directory/$uploadfilename5");
			  @chmod("$ashoppath/$directory/$uploadfilename5", 0666);
		  }
	  }
	  }

	  if ($error) header ("Location: filemanager.php?error=$error&directory=$directory");
	  else header("Location: filemanager.php?directory=$directory");
  }
} else echo "</td></tr></table>$footer";
?>