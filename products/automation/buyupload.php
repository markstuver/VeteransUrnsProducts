<?php
@set_time_limit(0);
####################################################################################
##                                                                                ##
##          Upload After Purchase - Automation Example for AShop V	              ##
##                                                                                ##
##                            Installation instructions:                          ##
##                                                                                ##
##              1. Change the $savepath variable to the path where uploaded       ##
##                 files should be stored on the server.                          ##
##              2. Change the $password variable to a secret password that will   ##
##                 be used to securely add new information to the database.       ##
##              3. Run this script in a browser to install the database tables.   ##
##                                                                                ##
####################################################################################

$ashoppath = "c:\apache\htdocs\ashopdeluxe";
$savepath = 'C:\Apache\htdocs\ashopdeluxe\updates';
$password = "mypassword";
$allowedfiletypes = "gif,jpg,jpeg";
$notifyemail = "andreas@ashopsoftware.com";

####################################################################################
##                                                                                ##
##                           Do not edit below this.                              ##
##                                                                                ##
####################################################################################

include "$ashoppath/admin/config.inc.php";
include "$ashoppath/admin/ashopfunc.inc.php";

// Messages...
function msg_success($filename) {
	global $notifyemail;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Upload successful! </TITLE>
</HEAD>

<BODY>
<CENTER>
<H1>Upload successful!</H1>
<p>The uploaded file was successfully stored</p>
</CENTER>
</BODY>
</HTML>
<?php
	@mail($notifyemail,"New file uploaded: $filename","A new file has been uploaded. Filename: $filename");
}

function msg_failed() {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Upload failed! </TITLE>
</HEAD>

<BODY>
<CENTER>
<H1>Upload failed!</H1>
<p>The uploaded file could not be stored</p>
</CENTER>
</BODY>
</HTML>
<?php
}

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Convert allowed filetypes to an array...
$allowedfiletypes = str_replace(" ","",$allowedfiletypes);
$allowedfiletypes = explode(",",$allowedfiletypes);

if (is_uploaded_file($uploadfile) && $orderid) {
	$result = @mysqli_query($db, "SELECT * FROM uploads WHERE orderid='$orderid'");
	$uploaded = @mysqli_result($result,0,"uploaded");
	$alloweduploads = @mysqli_result($result,0,"alloweduploads");
	if ($uploaded != $alloweduploads) {
		$uploadfilename = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadfile_name)));
		$uploadfilename = preg_replace("/%E5|%E4/","a",$uploadfilename);
		$uploadfilename = preg_replace("/%F6/","o",$uploadfilename);
		$uploadfilename = preg_replace("/%C5|%C4/","A",$uploadfilename);
		$uploadfilename = preg_replace("/%D6/","O",$uploadfilename);
		$uploadfilename = preg_replace("/\+\+\+|\+\+/","+",$uploadfilename);
		$checkextension = explode(".",$uploadfilename);
		if (!is_array($allowedfiletypes) || in_array($checkextension[1],$allowedfiletypes)) {
			$date = date("Y-m-d", time());
			$uploaded++;
			@mysqli_query($db, "UPDATE uploads SET uploaded='$uploaded', date='$date', filename='$uploadfilename' WHERE orderid='$orderid'");
			if (file_exists("$savepath/$uploadfilename")) unlink("$savepath/$uploadfilename");
			move_uploaded_file($uploadfile, "$savepath/$uploadfilename");
			@chmod("$savepat/$uploadfilename", 0666);
			msg_success($uploadfilename);
		} else {
			move_uploaded_file($uploadfile, "$savepath/trash_$uploadfilename");
			unlink("$savepath/trash_$uploadfilename");
			msg_failed();
		}
	} else {
		move_uploaded_file($uploadfile, "$savepath/trash_$uploadfilename");
		unlink("$savepath/trash_$uploadfilename");
		msg_failed();
	}
} else if ($orderid && $pass == $password) {
	if (!$alloweduploads) $alloweduploads = 1;
	@mysqli_query($db, "INSERT INTO uploads (orderid, uploaded, alloweduploads, date, filename) VALUES ('$orderid','','$alloweduploads','','')");
} else {
	@mysqli_query($db, "CREATE TABLE uploads (orderid int not null, uploaded int, alloweduploads int, date varchar(30), filename varchar(30))");
	echo "Database tables have been created!";
}
?>