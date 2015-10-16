<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

unset($shop);
include "admin/checklicense.inc.php";
if (!$membershops) {
	header("Location: index.php");
	exit;
}
include "admin/ashopconstants.inc.php";
include "counter.php";

// Apply selected theme...
$buttonpath = "";
$templatepath = "/templates";
if ($ashoptheme && $ashoptheme != "none" && file_exists("$ashoppath/themes/$ashoptheme/theme.cfg.php")) include "themes/$ashoptheme/theme.cfg.php";
if ($usethemebuttons == "true") $buttonpath = "themes/$ashoptheme/";
if ($usethemetemplates == "true") $templatepath = "/themes/$ashoptheme";
if ($lang && is_array($themelanguages)) {
	if (!in_array("$lang",$themelanguages)) unset($lang);
}

// Include language file...
if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
include "language/$lang/mall.inc.php";

// Show "Please wait" page while completing the search...
if($searchstring && !$showresult) {
	echo "<html><head><title>".SEARCHING."</title>\n".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><table width=\"100%\" border=\"0\" height=\"100%\"><tr><td align=\"center\" valign=\"middle\"><font face=\"$font\" size=\"4\"><span class=\"fontsize2\"><b>".SEARCHING."</b></span></font><br></center><meta http-equiv=\"Refresh\" content=\"0; URL=mall.php?{$_SERVER["QUERY_STRING"]}&showresult=true\"><br><br><br><br><br><br></td></tr></table></body></html>";
	exit;
} else if ($searchcategories && !$showresult) {
	echo "<html><head><title>".SEARCHING."</title>\n".CHARSET."<style type=\"text/css\"><!-- .fontsize1 { font-size: {$fontsize1}px}\n.fontsize2 { font-size: {$fontsize2}px}\n.fontsize3 { font-size: {$fontsize3}px}--></style></head><body onload=\"document.searchform.submit();\" bgcolor=\"$bgcolor\" text=\"$textcolor\" link=\"$linkcolor\"><table width=\"100%\" border=\"0\" height=\"100%\"><tr><td align=\"center\" valign=\"middle\"><font face=\"$font\" size=\"4\"><span class=\"fontsize2\"><b>".SEARCHING."</b></span></font><br></center>
	<form name=\"searchform\" action=\"mall.php\" method=\"post\">
		<input type=\"hidden\" name=\"showresult\" value=\"true\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\">";
	foreach($searchcategories as $key => $value) echo "<input type=\"hidden\" name=\"searchcategories[$key]\" value=\"$value\">";
	echo "</form><br><br><br><br><br><br></td></tr></table></body></html>";
	exit;
}
ob_start();

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

// Print header from template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/mall-$lang.html")) ashop_showtemplateheader("$ashoppath$templatepath/mall-$lang.html");
else ashop_showtemplateheader("$ashoppath$templatepath/mall.html");

// Print top of page...
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><tr>";

// List categories...
echo "<td class=\"ashopcategoriesbox\">
      <table class=\"ashopcategoriestable\" cellspacing=\"0\"><tr><td class=\"ashopcategoriesheader\">&nbsp;&nbsp;<img src=\"$ashopurl/images/caticon.gif\" align=\"absbottom\">&nbsp;&nbsp;&nbsp;".CATEGORIES."</td></tr>
	  <tr><td align=\"right\" class=\"ashopcategory\">
	  <form action=\"mall.php\" method=\"post\">
	  <select class=\"ashopmallcategories\" name=\"searchcategories[]\" size=\"5\" multiple><option value=\"all\">".ALLCATEGORIES."<option value=\"new\">".NEWSHOPS;
$categories = @mysqli_query($db,"SELECT * FROM shopcategory ORDER BY name");
while ($row = @mysqli_fetch_array($categories)) echo "<option value=\"{$row["categoryid"]}\">{$row["name"]}";
echo "</select><br><input type=\"image\" src=\"{$buttonpath}images/search-$lang.png\" class=\"ashopbutton\"></form></td></tr></table><br>
<table class=\"ashopcategoriestable\" cellspacing=\"0\"><tr><td class=\"ashopcategoriesheader\">&nbsp;&nbsp;<img src=\"$ashopurl/images/findicon.gif\" align=\"absbottom\">&nbsp;&nbsp;&nbsp;".TEXT."</td></tr>
<tr><td align=\"right\" class=\"ashopcategory\"><form action=\"mall.php\" method=\"post\"><input class=\"ashopmallsearch\" type=\"text\" size=\"19\" name=\"searchstring\" style=\"\"><br><input type=\"image\" src=\"{$buttonpath}images/search-$lang.png\" class=\"ashopbutton\"></form></font></td></tr>";
	
if($ashopaffiliateid) echo "<tr><td align=\"center\"><br><a href=\"http://www.ashopsoftware.com\" onClick=\"window.open('http://www.ashopsoftware.com/affiliate.php?id=$ashopaffiliateid' , 'PGM' , 'scrollbars=yes, toolbar=yes, status=yes, menubar=yes location=yes resizable=yes'); return false; \" target=\"_blank\"><img src=\"images/ashoplogo.gif\" border=\"0\" alt=\"".POWEREDBYASHOP."\"></a></td></tr>";
echo "</table></p></td>";

echo "<td valign=\"top\">";

// Show search page header...
echo "<table class=\"ashoppageheader\"><tr><td valign=\"top\"><span class=\"ashoppageheadertext1\">";
if ($searchcategories[0] == "new") $searchcategories = "";
if ($searchstring || $searchcategories) echo SEARCHRESULT;
else echo LATESTADDITIONS;
echo ":</span></td></tr></table>";

// List found shops...
if ($searchstring) {
    $searchwords = explode(" ", $searchstring);
    $sql="SELECT * from user WHERE password > '' AND";
	foreach($searchwords as $wordnumber => $thisword) {
		if ($wordnumber == 0) $sql.=" (UPPER(shopdescription) LIKE '%".strtoupper($thisword)."%' OR UPPER(shopname) LIKE '%".strtoupper($thisword)."%')";
		else $sql.=" AND (UPPER(shopdescription) LIKE '%".strtoupper($thisword)."%' OR UPPER(shopname) LIKE '%".strtoupper($thisword)."%')";
	}
	$sql.=" ORDER BY userid";
} else if ($searchcategories[0] == "all") {
	$sql = "SELECT * FROM user WHERE userid!='1' AND password > '' ORDER BY userid ASC";
} else if ($searchcategories) {
	$sql = "SELECT DISTINCT user.* FROM user, membercategory WHERE user.password > '' AND user.userid=membercategory.userid AND (";
	$categorynumber = 0;
	foreach ($searchcategories as $key => $value) {
		if ($categorynumber == 0) $sql .= "membercategory.categoryid='$value'";
		else $sql .= " OR membercategory.categoryid='$value'";
		$categorynumber++;
	}
	$sql .= ")";
} else $sql = "SELECT * FROM user WHERE userid!='1' AND password > '' ORDER BY userid DESC LIMIT 10";
$displayitems = 10;
$result = @mysqli_query($db,$sql);
$numberofrows = intval(@mysqli_num_rows($result));
$numberofpages = ceil($numberofrows/$displayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $displayitems;
else {
	$resultpage = 1;
	$startrow = 0;
}
$startpage = $resultpage - 9;
if ($numberofpages - $resultpage < 10) {
	$pagesleft = $numberofpages - $resultpage;
	$startpage = $startpage - (10 - $pagesleft);
}
if ($startpage < 1) $startpage = 1;
$stoprow = $startrow + $displayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$thisrow++;
	$userid = $row["userid"];
	$shopname = $row["shopname"];
	$shopdescription = $row["shopdescription"];
	$url = $row["url"];

	if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) {
		echo "<table class=\"ashopmallbox\" cellpadding=\"5\">
		<tr>
		<td valign=\"top\">
		<span class=\"ashopmallname\">$thisrow) <a href=\"$url\" target=\"_blank\">$shopname</a> - </span>
		<span class=\"ashopmalltext\">$shopdescription<br>
		[ <a href=\"$url\" target=\"_blank\">".WEBSITE."</a> ] [ <a href=\"$url/affiliate/signupform.php\">".PROMOTESHOP."</a> ]";
		echo "</span>
		</td></tr></table>";
	} else {
		if (!$url) $url = $ashopurl."/index.php?shop=$userid";

		echo "<table class=\"ashopmallbox\" cellpadding=\"5\">
		<tr>
		<td valign=\"top\">
		<span class=\"ashopmallname\">$thisrow) <a href=\"index.php?shop=$userid\" target=\"_blank\">$shopname</a> - </span>
		<span class=\"ashopmalltext\">$shopdescription<br>
		[ <a href=\"$url\">".WEBSITE."</a> ]";
		if (file_exists("$ashoppath/affiliate/shoplink.php")) echo " [ <a href=\"affiliate/shoplink.php?promoteshop=$userid\">".PROMOTESHOP."</a> ]";
		echo "</span>
		</td></tr></table>";
	}
}

$searchcategoriesurl = "";
if (is_array($searchcategories)) foreach ($searchcategories as $key=>$value) $searchcategoriesurl .= "&searchcategories[$key]=$value";

if ($numberofpages > 1) {
	echo "
		<table class=\"ashoppagestable\"><tr><td align=\"center\"><span class=\"ashoppageslist\">".PAGE.": ";
	if ($resultpage > 1) {
		$previouspage = $resultpage-1;
		echo "<<<a href=\"mall.php?searchstring=$searchstring$searchcategoriesurl&resultpage=$previouspage\">".PREVIOUS."</a>&nbsp;&nbsp;";
	}
	$page = 1;
	for ($i = $startpage; $i <= $numberofpages; $i++) {
		if ($page > 20) break;
		if ($i != $resultpage) echo "<a href=\"mall.php?searchstring=$searchstring$searchcategoriesurl&resultpage=$i\">$i</a>";
		else echo "<span style=\"font-size: larger;\">$i</span>";
		if ($i != $resultpage) echo "</a>";
		echo "&nbsp;&nbsp;";
		$page++;
	}
	if ($resultpage < $numberofpages) {
		$nextpage = $resultpage+1;
		echo "<a href=\"mall.php?searchstring=$searchstring$searchcategoriesurl&resultpage=$nextpage\">".NEXTPAGE."</a>>></span>";
	}
	echo "
		</td></tr></table>";
}

// Close database...

@mysqli_close($db);

// Print error messages...

if ($error) {
	echo "<span class=\"ashopalert\">".ERROR1."<br>
         ".ERROR2." ";
	if ($error==1) echo ERROR3."</span>";
	else if ($error==2) echo ERROR4."</span>";
}
echo "</td></tr></table>";


// Print footer using template...
if ($lang != $defaultlanguage && file_exists("$ashoppath$templatepath/mall-$lang.html")) ashop_showtemplatefooter("$ashoppath$templatepath/mall-$lang.html");
else ashop_showtemplatefooter("$ashoppath$templatepath/mall.html");
ob_end_flush();
?>