<?php
// AShop
// Copyright 2002-2014 - All Rights Reserved Worldwide
// http://www.ashopsoftware.com
// This software is licensed per individual site.
// By installing or using this software, you agree to the licensing terms,
// which are located at http://www.ashopsoftware.com/license.htm
// Unauthorized use or distribution of this software 
// is a violation U.S. and international copyright laws.

include "checklicense.inc.php";
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/members.inc.php";
// Get context help for this page...
$contexthelppage = "memberstats";
include "help.inc.php";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get member information from database...
$sql="SELECT * FROM user WHERE userid='$memberid'";
$result = @mysqli_query($db, "$sql");
$username = @mysqli_result($result, 0, "username");
$shopname = @mysqli_result($result, 0, "shopname");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");
$address = @mysqli_result($result, 0, "address");
$state = @mysqli_result($result, 0, "state");
$zip = @mysqli_result($result, 0, "zip");
$city = @mysqli_result($result, 0, "city");
$country = @mysqli_result($result, 0, "country");
$phone = @mysqli_result($result, 0, "phone");
$paypalid = @mysqli_result($result, 0, "paypalid");
$ashoptheme = @mysqli_result($result, 0, "theme");

echo "$header";

echo  "<center>
<div class=\"heading\" align=\"center\">".PRODUCTCATALOGOF." $shopname, ".MEMBERID." $memberid <a href=\"editmember.php?memberid=$memberid\"><img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $memberid\" title=\"".PROFILEFOR." $memberid\" border=\"0\"></a> <a href=\"salesreport.php?memberid=$memberid&generate=true&reporttype=paid\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $memberid\" title=\"".SALESHISTORYFOR." $memberid\" border=\"0\"></a></div>
      <table width=\"500\" border=\"0\" cellspacing=\"1\" cellpadding=\"0\" align=\"center\" bgcolor=\"#D0D0D0\">
      <tr class=\"reporthead\"><td align=\"left\">".IDPRODUCT."</td><td align=\"center\" width=\"80\">".ACTION."</td></tr>";

// Get member information from database...
$sql="SELECT * FROM product WHERE userid = '$memberid' AND (prodtype != 'content' OR prodtype IS NULL) AND (copyof='' OR copyof='0' OR copyof IS NULL) ORDER BY name";
$result = @mysqli_query($db, "$sql");
$numberofrows = intval(@mysqli_num_rows($result));
if (!$admindisplayitems) {
	if ($c_admindisplayitems) $admindisplayitems = $c_admindisplayitems;
	else $admindisplayitems = 10;
}
$numberofpages = ceil($numberofrows/$admindisplayitems);
if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $admindisplayitems;
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
$stoprow = $startrow + $admindisplayitems;
@mysqli_data_seek($result, $startrow);
$thisrow = $startrow;
while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
	$thisrow++;
	$productid = $row["productid"];
	$productname = $row["name"];
	$productactive = $row["active"];
	echo "<tr class=\"reportline\"><td>$productid, <a href=\"editcatalogue.php?pid=$productid\">$productname</a></td>
	<td align=\"center\">";
	if (!$productactive) echo "<a href=\"editcatalogue.php?rt=$productid&pid=$productid\"><img src=\"images/icon_activate.gif\" border=\"0\"></a>&nbsp;";
	else echo "<img src=\"images/invisible.gif\" width=\"15\">";
	echo "<a href=\"salesreport.php?memberid=$memberid&productid=$productid&orderby=productid&generate=true&reporttype=paid\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFORPRODUCT."\" title=\"".SALESHISTORYFORPRODUCT."\" border=\"0\"></a>&nbsp;<a href=\"editproduct.php?productid=$productid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEPRODUCT."\" title=\"".DELETEPRODUCT."\" border=\"0\"></a></td></tr>";
}

echo "</table>";
if ($numberofrows > 5) {
	echo "<form name=\"memberitemsform\"><table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
	if ($numberofpages > 1) {
		echo "<b>".PAGE.": </b>";
		if ($resultpage > 1) {
			$previouspage = $resultpage-1;
			echo "<<<a href=\"editmembercat.php?memberid=$memberid&resultpage=$previouspage&admindisplayitems=$admindisplayitems&namefilter=$namefilter\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
		}
		$page = 1;
		for ($i = $startpage; $i <= $numberofpages; $i++) {
			if ($page > 20) break;
			if ($i != $resultpage) echo "<a href=\"editmembercat.php?memberid=$memberid&resultpage=$i&admindisplayitems=$admindisplayitems\">";
			echo "$i";
			if ($i != $resultpage) echo "</a>";
			echo "&nbsp;&nbsp;";
			$page++;
		}
		if ($resultpage < $numberofpages) {
			$nextpage = $resultpage+1;
			echo "<a href=\"editmembercat.php?memberid=$memberid&resultpage=$nextpage&admindisplayitems=$admindisplayitems\"><b>".NEXTPAGE."</b></a>>>";
		}
	}
	echo " ".DISPLAY.": <select name=\"admindisplayitems\" onChange=\"document.location.href='editmembercat.php?memberid=$memberid&resultpage=$resultpage&admindisplayitems='+memberitemsform.admindisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"5\""; if ($admindisplayitems == "5") echo " selected"; echo ">5</option><option value=\"10\""; if ($admindisplayitems == "10") echo " selected"; echo ">10</option><option value=\"20\""; if ($admindisplayitems == "20") echo " selected"; echo ">20</option><option value=\"40\""; if ($admindisplayitems == "40") echo " selected"; echo ">40</option><option value=\"$numberofrows\""; if ($admindisplayitems == "$numberofrows") echo " selected"; echo ">".ALL."</option></select> ".ITEMS."</td></tr></table></form>
	";
}
echo "</center>$footer";
?>