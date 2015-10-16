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

unset($shop);
unset($userid);
include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";
include "admin/ashopconstants.inc.php";
require_once "includes/aws/aws-config.php";
require_once "includes/aws/aws-autoloader.php";
use Aws\S3\S3Client;

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
$device = ashop_mobile();

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

// Set date for logging...
$date = date("Y-m-d", time()+$timezoneoffset);

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
$nname = @mysqli_result($result, 0, "name");
$ndescription = @mysqli_result($result, 0, "description");

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

// Get product id of this video...
$productresult = @mysqli_query($db, "SELECT product.productid FROM product, productfiles WHERE productfiles.fileid='$nfile' AND productfiles.productid=product.productid");
$productid = @mysqli_result($productresult, 0, "productid");

// Log plays...
if ($playing == "true") {
	$orderid = @mysqli_result($result, 0, "orderid");
	$downloadsresult = @mysqli_query($db, "SELECT * FROM orderdownloads WHERE fileid='$nfile' AND orderid='$orderid'");
	$downloads = @mysqli_result($downloadsresult, 0, "downloads")+1;

	// Log this download...
	$ipnumber = $_SERVER["REMOTE_ADDR"];
	if (!ashop_is_ip($ipnumber)) $ipnumber = "Unknown";
	if (!empty($_SERVER["HTTP_REFERER"])) @mysqli_query($db, "INSERT INTO downloadslog (fileid, orderid, date, ip) VALUES ('$nfile', '$orderid', '$date', '$ipnumber')");

	if (@mysqli_num_rows($downloadsresult)) @mysqli_query($db, "UPDATE orderdownloads SET downloads='$downloads' WHERE orderid='$orderid' AND fileid='$nfile'");
	else @mysqli_query($db, "INSERT INTO orderdownloads (downloads, orderid, fileid) VALUES ('$downloads','$orderid','$nfile')");

	if (($downloads > $alloweddownloads && $alloweddownloads) || $notallowed) echo "1|".ALREADY;
	else echo "0|OK";
	exit;
}

// Check allowed download time and downloads per product...
if ($isupdate != "true") {
	$orderid = @mysqli_result($result, 0, "orderid");
	$firstname = @mysqli_result($result, 0, "firstname");
	$lastname = @mysqli_result($result, 0, "lastname");
	$userid = @mysqli_result($result, 0, "userid");
	if ($userid && $userid != "|1|") {
		$fileresult = @mysqli_query($db, "SELECT userid FROM product WHERE productid='$productid'");
		$userid = @mysqli_result($fileresult, 0, "userid");
		$shopresult = @mysqli_query($db, "SELECT * FROM user WHERE userid='$userid'");
		$shoprow = @mysqli_fetch_array($result);
		$membershopemail = $row["email"];
		$membershopname = $row["firstname"]." ".$row["lastname"];
	}
	$downloadsresult = @mysqli_query($db, "SELECT * FROM orderdownloads WHERE fileid='$nfile' AND orderid='$orderid'");
	$downloads = @mysqli_result($downloadsresult, 0, "downloads");
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

// Generate list of additional videos...
$playlist = "<div class=\"ashopvideostreamplaylist\"><ul>";
$playlistresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid' AND storage='1'");
while ($playlistrow = @mysqli_fetch_array($playlistresult)) {
	$playlist .= "<form action=\"deliver.php\" method=\"post\" name=\"playlistform{$playlistrow["fileid"]}\"><input type=\"hidden\" name=\"email\" value=\"$nemail\" /><input type=\"hidden\" name=\"file\" value=\"{$playlistrow["id"]}\" /><input type=\"hidden\" name=\"filename\" value=\"{$playlistrow["filename"]}\" /><input type=\"hidden\" name=\"password\" value=\"$npassword\" /></form><li><a href=\"javascript: document.playlistform{$playlistrow["fileid"]}.submit();\"><b>{$playlistrow["name"]}</b> - {$playlistrow["description"]}</a></li>";
}
$playlist .= "</ul></div>";

// Start download...
if ($nfile && $isupdate != "true") {
	$message = "<html><head><title>$ashopname- Delivery</title>\n<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body><font face=\"$font\"><span class=\"fontsize2\"><p>$firstname $lastname downloaded {$_SERVER["QUERY_STRING"]} $date from: {$_SERVER["REMOTE_ADDR"]}</p></span></font></body></html>";
	$headers = "From: $firstname $lastname<$email>\nX-Sender: <$email>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$email>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	if (!strstr($_SERVER["HTTP_USER_AGENT"],"AppleCoreMedia") && !empty($_SERVER["HTTP_REFERER"])) {
		//@ashop_mail("$ashopemail",un_html($ashopname)." - delivery","$message","$headers");
		//if ($membershopemail) @ashop_mail("$membershopemail",un_html($membershopname)." - delivery","$message","$headers");
	}
	$fileinfo = pathinfo("{$_SERVER["QUERY_STRING"]}");
	$extension = strtolower($fileinfo["extension"]);
	if (($extension == "mp4" || $extensions == "flv") && file_exists("$ashoppath/includes/aws/aws-config.php")) {
		$signedurl = ashop_getsignedawsurl("$awsdirectory/".$_SERVER["QUERY_STRING"], 3600, $cloudfrontkeypairid);
		$signedhttpurl = ashop_getsignedawsurl("{$cloudfronthttpurl}$awsdirectory/".$_SERVER["QUERY_STRING"], 3600, $cloudfrontkeypairid);
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		$currentdevice = "";
		foreach ($devices as $thisuseragent=>$thisdevice) if (stripos($useragent,$thisuseragent)) $currentdevice = $thisuseragent;
		if ($currentdevice == "iPad" || $currentdevice == "iPod" || $currentdevice == "iPhone") $streamurl = $signedhttpurl;
		else $streamurl = "{$cloudfronturl}$extension:$signedurl";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplateheader("$ashoppath$templatepath/delivery.html");
		echo "<script type=\"text/javascript\" src=\"includes/jwplayer/jwplayer.js\"></script>
		<script type=\"text/javascript\" src=\"includes/jwplayer/jwtrack.js\"></script>
		<script type=\"text/javascript\">jwplayer.key=\"$jwplayerkey\";</script>
		<table class=\"ashopmessagetable\" align=\"center\">
		<tr align=\"center\"><td><br><br><div id=\"videoplayerdiv\"></div>
		<script>
		jwplayer(\"videoplayerdiv\").setup({
			sources: [
			   {file: \"{$streamurl}\"}
	        ],
			width: \"720\",
			height: \"480\"
		});
		jwplayer(\"videoplayerdiv\").onPlay( function(event){
			jwplaying('$fileid', '$nemail', '$npassword');
		});
		jwplayer(\"videoplayerdiv\").onComplete( function(event){
			jwplaying('$fileid', '$nemail', '$npassword');
		});
		jwplayer(\"videoplayerdiv\").onPause( function(event){
			jwstopped('$fileid', '$nemail', '$npassword');
		});
		</script>
		<div id=\"message\"></div>
		<h1>$nname</h1>
		<p><i>$ndescription</i></p>
		$playlist
		<p><span class=\"ashopmessage\"><form action=\"deliver.php\" name=\"downloadform\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"email\" value=\"$nemail\"><input type=\"hidden\" name=\"password\" value=\"$npassword\"><a href=\"javascript:document.downloadform.submit();\">".BACK."</a></form></span></p></td></tr></table>";
		if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/delivery-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/delivery-$lang.html");
		else ashop_showtemplatefooter("$ashoppath$templatepath/delivery.html");
		exit;
	}
} else if ($nfile && $isupdate == "true") {
	$message = "<html><head><title>$ashopname - Update delivery</title>\n<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body><font face=\"$font\"><span class=\"fontsize2\"><p>A customer with the email address $email downloaded the update {$_SERVER["QUERY_STRING"]} $date from: {$_SERVER["REMOTE_ADDR"]}</p></span></font></body></html>";
	$headers = "From: $firstname $lastname<$email>\nX-Sender: <$email>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$email>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
	if (!empty($_SERVER["HTTP_REFERER"])) @ashop_mail("$ashopemail",un_html($ashopname)." - delivery","$message","$headers");
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