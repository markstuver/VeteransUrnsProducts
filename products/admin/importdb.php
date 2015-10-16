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

// Get the correct path to the scripts...
$path = (substr(PHP_OS, 0, 3) == 'WIN') ? strtolower(getcwd()) : getcwd();
$path = str_replace("\\","\\\\",$path);
$ashoppath = $path;
$ashoppath = substr(getcwd(),0,-6);

// REQUEST_URI fix for Windows+IIS...
if (!isset($REQUEST_URI) and isset($_SERVER['SCRIPT_NAME'])) {
    $REQUEST_URI = $_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $REQUEST_URI .= '?' . $_SERVER['QUERY_STRING'];
}

// Get the correct url to the scripts...
$url = "http://";
$url .= $HTTP_HOST.$REQUEST_URI;
$url = str_replace("/admin/importdb.php","",$url);

// Create default secure URL...
$secureurl = str_replace("http://", "https://", $url);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename") or die("Error! Could not connect to database server!");

if ($_POST["sqldumpfile"]) $sqldumpfile = $_POST["sqldumpfile"];
if (!is_uploaded_file($sqldumpfile)) {
	echo "<html><head><title>Restore from Backup</title>\n</head><body bgcolor=\"#FFFFFF\" textcoloe=\"#000000\"><center><br><br><br><br><p><img src=\"images/logo.gif\"></p><p><font face=\"Arial, Helvetica, sans-serif\"><b>Restore AShop Database from Backup</b></p><p><font color=\"#FF0000\"><b>Warning! This will overwrite everything currently stored in the database!</b></font></p><p><form action=\"importdb.php\" method=\"post\" enctype=\"multipart/form-data\"><br>Database Password: <input type=\"password\" name=\"dbpass\"><br><br>Backupfile: <input type=\"file\" name=\"sqldumpfile\"><br><br><input type=\"submit\" value=\"Upload and Restore\"></form></p></center></body></html>";
	exit;
} else {
	$updating = TRUE;
	if ($dbpass != $databasepasswd) {
		echo "<html><head><title>Wrong Password</title>\n</head>
		<body bgcolor=\"#FFFFFF\" text=\"#000000\"><center>
			<p><img src=\"../images/logo.gif\"></p>
			<p><font face=\"Arial, Helvetica, sans-serif\"><b>Incorrect Database Password!</b></p>
			</font></center></body></html>";
		unlink("$sqldumpfile");
		exit;
	} else move_uploaded_file($sqldumpfile, "$ashopspath/updates/ashop.sql");
}
@set_time_limit(10000);

$backupfile = "$ashopspath/updates/ashop.sql";
if(file_exists($backupfile)) {
	$fp = fopen($backupfile, "r");
	$sqlquery = addslashes(fread($fp, filesize($backupfile)));
}
fclose($fp);

$sqlquery = trim($sqlquery);
$buffer = array();
$pieces = array();
$in_string = false;

for($i=0; $i<strlen($sqlquery)-1; $i++) {
	if($sqlquery[$i] == ";" && !$in_string) {
		$pieces[] = substr($sqlquery, 0, $i);
		$sqlquery = substr($sqlquery, $i + 1);
		$i = 0;
	}
	if($in_string && ($sqlquery[$i] == $in_string) && $buffer[0] != "\\") $in_string = false;
	elseif(!$in_string && ($sqlquery[$i] == "\"" || $sqlquery[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) $in_string = $sqlquery[$i];

	if(isset($buffer[1])) $buffer[0] = $buffer[1];
	$buffer[1] = $sqlquery[$i];
}
if(!empty($sqlquery)) $pieces[] = $sqlquery;

for ($i=0; $i<count($pieces); $i++) {
	$pieces[$i] = stripslashes(trim($pieces[$i]));
	if(!empty($pieces[$i]) && $pieces[$i] != "#") $result = @mysqli_query($db, $pieces[$i]) or die("<b>Error!</b> ".mysqli_error());
}

// Convert path and URL if needed...
$result = @mysqli_query($db, "SELECT prefvalue FROM preferences WHERE prefname='ashoppath'");
$oldashoppath = @mysqli_result($result,0,"prefvalue");
$result = @mysqli_query($db, "SELECT prefvalue FROM preferences WHERE prefname='ashopurl'");
$oldashopurl = @mysqli_result($result,0,"prefvalue");
if ($oldashoppath != $ashoppath) {
	// The AShop has been moved to a different location in the filesystem...
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ashoppath' WHERE prefname='ashoppath'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ashoppath' WHERE prefname='ashopspath'");
}
if ($oldashopurl != $url) {
	// The AShop has been moved to a different URL...
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$url' WHERE prefname='ashopurl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$secureurl' WHERE prefname='ashopsurl'");
}

echo "<html><head><title>Success!</title>\n</head>
<body bgcolor=\"#FFFFFF\" text=\"#000000\"><center>
<p><img src=\"../images/logo.gif\"></p>
<p><font face=\"Arial, Helvetica, sans-serif\"><b>Database successfully restored!</b></p>
</font></center></body></html>";
unlink("$backupfile");
?>