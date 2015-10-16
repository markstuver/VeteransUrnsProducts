<?php
include "../admin/config.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (is_numeric($productid)) {
	unset($filetypes);
	unset($filesize);
	unset($totalfilesize);
	unset($previousfiletypes);
	$filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
	$files = @mysqli_num_rows($filesresult);
	while($filerow = @mysqli_fetch_array($filesresult)) {
		$filename = $filerow["filename"];
		$fileid = $filerow["fileid"];
		$filetype = explode(".",$filename);
		$filetype = strtolower($filetype[1]);
		$firstletter = substr($filetype,0,1);
		$firstletter = strtoupper($firstletter);
		if(!is_array($previousfiletypes) || !in_array($filetype, $previousfiletypes)) $filetypes .= substr_replace($filetype,$firstletter,0,1).", ";
		$previousfiletypes[] = $filetype;
		if (file_exists("$ashopspath/products/$fileid")) $totalfilesize += filesize("$ashopspath/products/$fileid");
	}
	$filetypes = substr($filetypes, 0, -2);
	$filesize = floor($totalfilesize/1048576);
	if ($filesize == 0) {
		$filesize = floor($totalfilesize/1024);
		if ($filesize == 0) $filesize = $totalfilesize." bytes";
		else $filesize .= " kB";
	} else $filesize .= " MB";
	if ($showtype == "true") echo $filetypes;
	if ($showsize == "true") echo $filesize;
	unset($showtype);
	unset($showsize);
}
?>