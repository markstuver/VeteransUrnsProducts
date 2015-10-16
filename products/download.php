<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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

unset($shop);
unset($userid);
include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";

// Apply selected theme...
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/download.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check if a mobile device is being used...
$iosdevice = FALSE;
$device = ashop_mobile();

// Set date for logging...
$date = date("Y-m-d", time()+$timezoneoffset);

// Get customer's IP for logging...
$ipnumber = $_SERVER["REMOTE_ADDR"];
if (!ashop_is_ip($ipnumber)) $ipnumber = "Unknown";

// If a mobile device is being used, activate temporary URL workaround...
if ($device == "mobile" && $seourls == "1") {
	// Convert URI and make it safe...
	if (!isset($_SERVER['REQUEST_URI']) and isset($_SERVER['SCRIPT_NAME'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
		if (isset($_SERVER['QUERY_STRING']) and !empty($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
	if ($_SERVER['HTTPS'] == "on") $url = "https://";
	else $url = "http://";
	$url .= $HTTP_HOST.$REQUEST_URI;
	if (strpos($url,"/download/")) {
		$downloadcode = str_replace("$ashopurl/download/","",$url);
		$downloadcode = stripslashes($downloadcode);
		$downloadcode = @mysqli_real_escape_string($db, $downloadcode);
		$downloadcode = str_replace("\'","",$downloadcode);
		$downloadcode = str_replace("\"","",$downloadcode);
		$downloadcode = str_replace("\n","",$downloadcode);
		$downloadcode = str_replace(";","",$downloadcode);
		$downloadcode = str_replace(".mp4","",$downloadcode);
		$downloadcode = str_replace(".mp3","",$downloadcode);
		if ($iosdevice && (substr($url,-4) == ".mp4" || substr($url,-4) == ".mp3")) $twentyfivesecondsago = time()-7200;
		else $twentyfivesecondsago = time()-25;
		$twohoursago = time()-7200;
		$dlcoderesult = @mysqli_query($db, "SELECT * FROM tempdllinks WHERE dlcode='$downloadcode' AND timestamp>'$twentyfivesecondsago'");
		$fileid = @mysqli_result($dlcoderesult,0,"fileid");
		$email = @mysqli_result($dlcoderesult,0,"email");
		$password = @mysqli_result($dlcoderesult,0,"password");
		// Check if this is an update...
		$checkupdateresult = @mysqli_query($db, "SELECT orders.*, customer.firstname, customer.lastname FROM customer, orders WHERE orders.password='$password' AND (customer.email='$email' OR customer.alternativeemails LIKE '%$email%') AND orders.customerid=customer.customerid");
		// Get filename...
		if (!@mysqli_num_rows($checkupdateresult)) $filenameresult = @mysqli_query($db, "SELECT filename FROM updates WHERE password='$password' AND productid='$fileid'");
		else $filenameresult = @mysqli_query($db, "SELECT filename FROM productfiles WHERE id='$fileid'");
		$_SERVER["QUERY_STRING"] = @mysqli_result($filenameresult,0,"filename");
		@mysqli_query($db, "DELETE FROM tempdllinks WHERE timestamp<'$twohoursago'");
		$downloadsavedialogue = "off";
	} else if (!empty($fileid) && !empty($email) && !empty($password)) {
		function makeDownloadcode() {
			$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
			srand ((double) microtime() * 1000000);
			$pwLength = "5";
			for($i = 1; $i <=$pwLength; $i++) {
				$newPass .= $alphaNum[(rand(0,31))];
			}
			return ($newPass);
		}
		$downloadcode = makeDownloadcode();
		$timestamp = time();
		@mysqli_query($db, "INSERT INTO tempdllinks (dlcode,fileid,email,password,timestamp) VALUES ('$downloadcode','$fileid','$email','$password','$timestamp')");
		// Check if this is an update...
		$checkupdateresult = @mysqli_query($db, "SELECT orders.*, customer.firstname, customer.lastname FROM customer, orders WHERE orders.password='$password' AND (customer.email='$email' OR customer.alternativeemails LIKE '%$email%') AND orders.customerid=customer.customerid");
		$orderid = @mysqli_result($checkupdateresult,0,"orderid");
		$firstname = @mysqli_result($checkupdateresult,0,"firstname");
		$lastname = @mysqli_result($checkupdateresult,0,"lastname");

		// Get filename...
		if (!@mysqli_num_rows($checkupdateresult)) $filenameresult = @mysqli_query($db, "SELECT filename FROM updates WHERE password='$password' AND productid='$fileid'");
		else $filenameresult = @mysqli_query($db, "SELECT filename FROM productfiles WHERE id='$fileid'");
		$filename = @mysqli_result($filenameresult,0,"filename");
		$fileinfo = pathinfo($filename);
	    $extension = strtolower($fileinfo["extension"]);

		// Log this download...
		@mysqli_query($db, "INSERT INTO downloadslog (fileid, orderid, date, ip) VALUES ('$fileid', '$orderid', '$date', '$ipnumber')");

		// Keep track of number of downloads for this file...
		$downloadsresult = @mysqli_query($db, "SELECT * FROM orderdownloads WHERE fileid='$fileid' AND orderid='$orderid'");
		$downloads = @mysqli_result($downloadsresult, 0, "downloads")+1;
		if (@mysqli_num_rows($downloadsresult)) @mysqli_query($db, "UPDATE orderdownloads SET downloads='$downloads' WHERE orderid='$orderid' AND fileid='$fileid'");
		else @mysqli_query($db, "INSERT INTO orderdownloads (downloads, orderid, fileid) VALUES ('$downloads','$orderid','$fileid')");

		// Send delivery notice to admin...
		$membershopemail = "";
		$fileresult = @mysqli_query($db, "SELECT product.userid FROM product, productfiles WHERE productfiles.fileid='$fileid' AND productfiles.productid=product.productid");
		$userid = @mysqli_result($fileresult, 0, "userid");
		if (!empty($userid) && $userid > 1) {
			$shopresult = @mysqli_query($db, "SELECT * FROM user WHERE userid='$userid'");
			$shoprow = @mysqli_fetch_array($result);
			$membershopemail = $row["email"];
			$membershopname = $row["firstname"]." ".$row["lastname"];
		}
		$message = "<html><head><title>$ashopname- Delivery</title>\n<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body><font face=\"$font\"><span class=\"fontsize2\"><p>$firstname $lastname downloaded $filename $date from: $ipnumber</p></span></font></body></html>";
		$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname)." - delivery","$message","$headers");
		if ($membershopemail) @ashop_mail("$membershopemail",un_html($membershopname)." - delivery","$message","$headers");

		// Redirect to download...
		if (!$iosdevice || ($extension != "mp4" && $extension != "mp3")) header("Location: $ashopurl/download/$downloadcode");
		else header("Location: $ashopurl/download/$downloadcode".".$extension");
		exit;
	}
}

if ($dorefresh == "true") {
	echo "<html><head><meta http-equiv=\"refresh\" content=\"0;URL=$ashopurl/download.php?$filename\"><style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" alink=\"$linkcolor\" vlink=\"$linkcolor\"><center>
		<p><img src=\"images/logo.gif\"></p>
        <font face=\"$font\"><span class=\"fontsize2\"><p><b>".WAIT."</b></p>
		<p><form action=\"deliver.php\" name=\"downloadform\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"email\" value=\"$nemail\"><input type=\"hidden\" name=\"password\" value=\"$npassword\"><a href=\"javascript:document.downloadform.submit();\">".BACK."</a></form></p>
        </span></font></center></body></html>";
	exit;
}
$nfile = $fileid;
$updatefile = $fileid;
$npassword = $password;
$nemail = $email;
if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$p3psent = TRUE;
setcookie("fileid","");
setcookie("password","");
setcookie("email","");

// Check login information...
if ((!$npassword || !$nfile || !$nemail) && !$notallowed) {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
	echo "<table class=\"ashopmessagetable\" align=\"center\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".NOACCESS."</span></p>
	<p><span class=\"ashopmessage\">".DLLOGIN."</span></p>
	<p><span class=\"ashopmessage\"><a href=\"deliver.php\">".CLICK."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
	exit;
}

// Get the productid and fileid for the file...
$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE id='$nfile'");
$nproduct = @mysqli_result($result, 0, "productid");
$nfile = @mysqli_result($result, 0, "fileid");
$nurl = @mysqli_result($result, 0, "url");

$isupdate == "false";
$sql="SELECT orders.*, customer.firstname, customer.lastname FROM customer, orders WHERE orders.password='$npassword' AND (customer.email='$nemail' OR customer.alternativeemails LIKE '%$nemail%') AND orders.customerid=customer.customerid";
$result = @mysqli_query($db, "$sql");
if (!$notallowed && @mysqli_num_rows($result) == 0) {
	$nfile = $updatefile;
	$sql = "SELECT * FROM updates WHERE password='$npassword' AND productid='$nfile'";
	$result2 = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result2) == 0 || !$nemail) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<table class=\"ashopmessagetable\" align=\"center\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".WRONGPASSWORD."</span></p>
		<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		exit;
	} else {
		$isupdate = "true";
		$alloweddownloaddays = @mysqli_result($result2, 0, "alloweddays");
	}
} else {
	$allowedproduct = "FALSE";
	$allowedproducts = ashop_parseproductstring($db, @mysqli_result($result, 0, "products"));
	if ($allowedproducts) foreach($allowedproducts as $productnumber => $thisproduct) if ($thisproduct["productid"] == $nproduct) $allowedproduct = "TRUE";
	if ($allowedproduct != "TRUE") {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<table class=\"ashopmessagetable\" align=\"center\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\"><b>".NOTALLOW."</b><br>".AUTOREPORT."</span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname)." - suspected delivery manipulation","Suspected delivery manipulation: the customer with this email address: $nemail has tried to access the product with ID: $nproduct with an unauthorized password from IP: {$_SERVER["REMOTE_ADDR"]}!","$headers");
		exit;
	}
}

// Check if this is a gift...
$description = explode("|",@mysqli_result($result,0,"description"));
if ($description[0] == "gift") {
	$isgift = "true";
	$giftdate = date("Y-m-d H:i:s", $description[1]);
}

// Check allowed download time and downloads per product...
if ($isupdate != "true") {
	$orderid = @mysqli_result($result, 0, "orderid");
	$firstname = @mysqli_result($result, 0, "firstname");
	$lastname = @mysqli_result($result, 0, "lastname");
	$userid = @mysqli_result($result, 0, "userid");
	if ($userid && $userid != "|1|") {
		$fileresult = @mysqli_query($db, "SELECT product.userid FROM product, productfiles WHERE productfiles.fileid='$nfile' AND productfiles.productid=product.productid");
		$userid = @mysqli_result($fileresult, 0, "userid");
		$shopresult = @mysqli_query($db, "SELECT * FROM user WHERE userid='$userid'");
		$shoprow = @mysqli_fetch_array($result);
		$membershopemail = $row["email"];
		$membershopname = $row["firstname"]." ".$row["lastname"];
	}
	$downloadsresult = @mysqli_query($db, "SELECT * FROM orderdownloads WHERE fileid='$nfile' AND orderid='$orderid'");
	$downloads = @mysqli_result($downloadsresult, 0, "downloads");
	if (!strstr($_SERVER["HTTP_USER_AGENT"],"AppleCoreMedia") && !empty($_SERVER["HTTP_REFERER"]) && ($device != "mobile" || $seourls != "1")) $downloads++;

	// Log this download...
	if (!strstr($_SERVER["HTTP_USER_AGENT"],"AppleCoreMedia") && !empty($_SERVER["HTTP_REFERER"]) && ($device != "mobile" || $seourls != "1")) @mysqli_query($db, "INSERT INTO downloadslog (fileid, orderid, date, ip) VALUES ('$nfile', '$orderid', '$date', '$ipnumber')");

	if (($downloads > $alloweddownloads && $alloweddownloads) || $notallowed) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<table class=\"ashopmessagetable\" align=\"center\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ALREADY."</span></p>
		<p><span class=\"ashopmessage\"><form action=\"deliver.php\" name=\"downloadform\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"email\" value=\"$nemail\"><input type=\"hidden\" name=\"password\" value=\"$npassword\"><a href=\"javascript:document.downloadform.submit();\">".BACK."</a></form></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		exit;
	} else if (!strstr($_SERVER["HTTP_USER_AGENT"],"AppleCoreMedia") && !empty($_SERVER["HTTP_REFERER"]) && ($device != "mobile" || $seourls != "1")) {
		if (@mysqli_num_rows($downloadsresult)) @mysqli_query($db, "UPDATE orderdownloads SET downloads='$downloads' WHERE orderid='$orderid' AND fileid='$nfile'");
		else @mysqli_query($db, "INSERT INTO orderdownloads (downloads, orderid, fileid) VALUES ('$downloads','$orderid','$nfile')");
	}
}
if ($alloweddownloaddays) {
	$payoption = @mysqli_result($result, 0, "payoptionid");
	$result4 = @mysqli_query($db, "SELECT deliverpending FROM payoptions WHERE payoptionid='$payoption'");
	$deliverpending = @mysqli_result($result4, 0, "deliverpending");
	if ($isgift == "true") $orderdate = explode(" ",$giftdate);
	else if ($isupdate != "true" && $deliverpending) $orderdate = explode(" ",@mysqli_result($result, 0, "date"));
	else if ($isupdate != "true") $orderdate = explode(" ",@mysqli_result($result, 0, "paid"));
	else $orderdate = explode(" ",@mysqli_result($result2, 0, "date"));
	$orderdate = explode ("-",$orderdate[0]);
	$orderedtimestamp = mktime(0,0,0,$orderdate[1],$orderdate[2],$orderdate[0]);
	if ((floor((time()+$timezoneoffset)/86400))*86400 - $orderedtimestamp > 86400 * ($alloweddownloaddays+1)) {
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<table class=\"ashopmessagetable\" align=\"center\">
		<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".TIMEEXPIRED."</span></p>
		<p><span class=\"ashopmessage\"><form action=\"deliver.php\" name=\"downloadform\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"email\" value=\"$nemail\"><input type=\"hidden\" name=\"password\" value=\"$npassword\"><a href=\"javascript:document.downloadform.submit();\">".BACK."</a></form></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		exit;
	}
}

// Start download...
if ($nfile && $isupdate != "true") {
	$message = "<html><head><title>$ashopname- Delivery</title>\n<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body><font face=\"$font\"><span class=\"fontsize2\"><p>$firstname $lastname downloaded {$_SERVER["QUERY_STRING"]} $date from: {$_SERVER["REMOTE_ADDR"]}</p></span></font></body></html>";
	$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	if (!strstr($_SERVER["HTTP_USER_AGENT"],"AppleCoreMedia") && !empty($_SERVER["HTTP_REFERER"]) && ($device != "mobile" || $seourls != "1")) {
		@ashop_mail("$ashopemail",un_html($ashopname)." - delivery","$message","$headers");
		if ($membershopemail) @ashop_mail("$membershopemail",un_html($membershopname)." - delivery","$message","$headers");
	}
	if ($device == "mobile" && $iosdevice) {
		$file = "$ashopspath/products/$nfile";
		$fp = @fopen($file, 'rb');
		$size   = filesize($file); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		$fileinfo = pathinfo("{$_SERVER["QUERY_STRING"]}");
	    $extension = strtolower($fileinfo["extension"]);
		$mimetype = $mimetypes["$extension"];
		if (!$mimetype) $mimetype = "application/octet-stream";
		header('Content-type: $mimetype');
		header("Accept-Ranges: 0-$length");
		if (isset($_SERVER['HTTP_RANGE'])) {
			$c_start = $start;
			$c_end   = $end;
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			if (strpos($range, ',') !== false) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				exit;
			}
			if ($range == '-') {
				$c_start = $size - substr($range, 1);
			} else {
				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			$c_end = ($c_end > $end) ? $end : $c_end;
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $start-$end/$size");
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1;
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		header("Content-Range: bytes $start-$end/$size");
		header("Content-Length: ".$length);
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {
			if ($p + $buffer > $end) {
				$buffer = $end - $p + 1;
			}
			set_time_limit(0);
			echo fread($fp, $buffer);
			flush();
		}
		fclose($fp);
		exit;
	} else if ($downloadsavedialogue == "on") {
		header ("Content-Type: application/octet-stream"); 
		if (!$nurl) header ("Content-Length: ".filesize("$ashopspath/products/$nfile"));
		header ("Content-Disposition: attachment; filename={$_SERVER["QUERY_STRING"]}");
	} else {
		$fileinfo = pathinfo("{$_SERVER["QUERY_STRING"]}");
	    $extension = strtolower($fileinfo["extension"]);
		$mimetype = $mimetypes["$extension"];
		if (!$mimetype) $mimetype = "application/octet-stream";
		header ("Content-Type: $mimetype");
		if (!$nurl) header ("Content-Length: ".filesize("$ashopspath/products/$nfile"));
	}
	if ($nurl) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$nurl);
		curl_setopt($ch, CURLOPT_POST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
		curl_exec ($ch);
	} else readfile_chunked("$ashopspath/products/$nfile",false);
} else if ($nfile && $isupdate == "true") {
	$message = "<html><head><title>$ashopname - Update delivery</title>\n<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body><font face=\"$font\"><span class=\"fontsize2\"><p>A customer with the email address $email downloaded the update {$_SERVER["QUERY_STRING"]} $date from: {$_SERVER["REMOTE_ADDR"]}</p></span></font></body></html>";
	$headers = "From: $firstname $lastname<$email>\nX-Sender: <$email>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$email>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	if (!strstr($_SERVER["HTTP_USER_AGENT"],"AppleCoreMedia") && !empty($_SERVER["HTTP_REFERER"]) && ($device != "mobile" || $seourls != "1")) @ashop_mail("$ashopemail",un_html($ashopname)." - delivery","$message","$headers");
	header ("Content-Type: application/octet-stream"); 
	header ("Content-Length: ".filesize("$ashopspath/updates/$nfile"));
	header ("Content-Disposition: attachment; filename={$_SERVER["QUERY_STRING"]}");
	readfile_chunked("$ashopspath/updates/$nfile",false);
} else {
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
	echo "<table class=\"ashopmessagetable\" align=\"center\">
	<tr align=\"center\"><td><br><br><p><span class=\"ashopmessageheader\">".ERROR."</span></p>
	<p><span class=\"ashopmessage\">".COULDNOTDOWNLOAD."</span></p>
	<p><span class=\"ashopmessage\"><a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></p></td></tr></table>";
	if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
	else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
	exit;
}
?>