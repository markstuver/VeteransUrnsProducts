<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/carts.inc.php";

// Get the path to the logo image for error messages...
if ($ashopuser && file_exists("$ashoppath/members/files/$ashopuser/logo.gif")) $ashoplogopath = "$ashoppath/members/files/$ashopuser";
else $ashoplogopath = "images";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Save current cart...
if ($save_x) {
	if (!$cartname) {
		echo "<html><head><title>".ERROR."</title>\n".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head>
		<body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\" vlink=\"$linkcolor\" alink=\"$linkcolor\"><table width=\"75%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" align=\"center\">
		<tr bordercolor=\"$formsbordercolor\" align=\"center\"><td><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr align=\"center\"><td> <img src=\"$ashoplogopath/logo.gif\" width=\"208\" height=\"67\"><br><hr size=\"0\" noshade>
		</td></tr></table><p><font face=\"$font\"><p><font size=\"5\"><span class=\"fontsize3\">".ERR."</span></font>
		<p><font size=\"4\"><span class=\"fontsize2\">".FORGOT2."</span></font><p><font size=\"4\"><span class=\"fontsize2\">
		<a href=\"javascript:history.back()\">".TRYAGAIN."</a></span></font></font></td></tr></table></body></html>";
		exit;
	} else {
		$result = @mysqli_query($db, "INSERT INTO savedcarts (sessionid, cartname, productstring) VALUES ('{$_COOKIE["customersessionid"]}', '$cartname', '$basket')");
		header("Location: basket.php?returnurl=$returnurl&cat=$cat");
	}
} else if ($cart) {
	$result = @mysqli_query($db, "SELECT * FROM savedcarts WHERE sessionid='{$_COOKIE["customersessionid"]}' AND cartid='$cart'");
	$row = @mysqli_fetch_array($result);
	if ($row["productstring"]) {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("basket", $row["productstring"]);
	}
	header("Location: basket.php?returnurl=$returnurl&cat=$cat");
} else if ($deletecart) {
	$result = @mysqli_query($db, "DELETE FROM savedcarts WHERE sessionid='{$_COOKIE["customersessionid"]}' AND cartid='$deletecart'");
	header("Location: basket.php?returnurl=$returnurl&cat=$cat");
}

// Close database...
@mysqli_close($db);
?>