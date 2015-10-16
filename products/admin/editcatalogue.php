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

error_reporting(E_ALL ^ E_NOTICE);
// Make sure the page isn't stored in the browsers cache...
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "config.inc.php";
if ($noinactivitycheck == "false") {
	if ($msg) $noinactivitycheck = "true";
	else $noinactivitycheck = "false";
}
include "ashopfunc.inc.php";
include "ashopconstants.inc.php";
include "checklogin.inc.php";
// Get context help for this page...
$contexthelppage = "editcatalogue";
include "help.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editcatalog.inc.php";

if (is_numeric($admindisplayitems)) {
	$c_admindisplayitems = $admindisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_admindisplayitems","$admindisplayitems");
}

if ($_POST["shopfilter"] == "main") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("shopfilter","main");
	$shopfilter = "main";
} else if ($_POST["shopfilter"] == "member") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("shopfilter","member");
	$shopfilter = "member";	
} else if ($_POST["shopfilter"] == "none") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("shopfilter","");
	$shopfilter = "";
}

// Validate variables...
if (!is_numeric($cat) && $cat != "pages") unset($cat);
$msg = str_replace("<","",$msg);
$msg = str_replace(">","",$msg);

if ($userid == "1" && $shopfilter == "main") {
	$user = "1";
	$catuser = "1";
} else if (!$memberprodmanage) {
	if ($userid > 1) {
		header("Location: index.php");
		exit;
	} else {
		$user = "%";
		$catuser = "%";
	}
} else {
	if ($userid == 1) {
		if ($_GET["memberid"]) {
			if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			$p3psent = TRUE;
			setcookie("catmemberid","{$_GET["memberid"]}");
			$catmemberid = $_GET["memberid"];
			setcookie("shopfilter","member");
			$shopfilter = "member";	
		} else if ($_COOKIE["catmemberid"]) $catmemberid = $_COOKIE["catmemberid"];
		if ($catmemberid && $shopfilter == "member") {
			$user = $catmemberid;
			$catuser = $catmemberid;
		} else {
			$user = "%";
			$catuser = "%";
		}
	} else {
		$user = $userid;
		if (!$membershops) $catuser = "1";
		else $catuser = $userid;
	}
}

// Set up sell selection cookie...
if (isset($_GET["relate"]) && is_numeric($_GET["relate"])) {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("relate",$_GET["relate"]);
	$relate = $_GET["relate"];
}

// Remove up sell selection cookie...
if (isset($cancelrelate) && $cancelrelate == "true") {
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("relate","");
	$_GET["relate"] = 0;
	$relate = 0;
}

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
if (!$db) $error = 1;

// Check if the current category is compatible with the selected shop filter...
if ($userid == "1" && $cat && $cat != "pages" && is_numeric($catmemberid)) {
	$catcheckresult = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$cat' AND (userid='$catmemberid' OR memberclone='1')");
	if (!@mysqli_num_rows($catcheckresult)) {
		if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		$p3psent = TRUE;
		setcookie("shopfilter","");
		$shopfilter = "";
		$user = "%";
		$catuser = "%";
	}
}

// Check for existing related products...
if ($relate) {
	$upsellresult = @mysqli_query($db, "SELECT * FROM relatedproducts WHERE productid='$relate' ORDER BY relationid ASC LIMIT 1");
	$upsellproduct = @mysqli_result($upsellresult, 0, "relatedproductid");
}

if ($rt) {
	$result = @mysqli_query($db, "SELECT active, copyof FROM product WHERE productid='$rt'");
	$active = @mysqli_result($result,0,"active");
	$copyof = @mysqli_result($result,0,"copyof");
	if ($active == "1") $active = "0";
	else {
		$active = "1";

		// Check if this is a standard or penny auction...
		$checkfloatingresult = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$rt'");
		$checkauctiontype = @mysqli_result($checkfloatingresult,0,"type");
		if ($checkauctiontype == "penny") {
			// Reset floating price to reactivate auction...
			if (empty($copyof)) @mysqli_query($db, "UPDATE floatingprice SET starttime=NULL, endprice=NULL, bids='0', bidderid='' WHERE productid='$rt'");
			else @mysqli_query($db, "UPDATE floatingprice SET starttime=NULL, endprice=NULL, bids='0', bidderid='' WHERE productid='$copyof'");
		} else if ($checkauctiontype == "standard") {
			$resetstarttime = time();
			// Reset floating price and start time to reactivate auction...
			if (empty($copyof)) @mysqli_query($db, "UPDATE floatingprice SET starttime='$resetstarttime', endprice=NULL, bids='0', bidderid='', startprice=originalstartprice WHERE productid='$rt'");
			else @mysqli_query($db, "UPDATE floatingprice SET starttime='$resetstarttime', endprice=NULL, bids='0', bidderid='', startprice=originalstartprice WHERE productid='$copyof'");
		}
	}
	@mysqli_query($db, "UPDATE product SET active='$active' WHERE productid='$rt'");
	header("Location: editcatalogue.php?cat=$cat&search=$search&resultpage=$resultpage&pid=$pid");
	exit;
}

if ($wt) {
	$result = @mysqli_query($db, "SELECT wholesaleactive FROM product WHERE productid='$wt'");
	$wholesaleactive = @mysqli_result($result,0,"wholesaleactive");
	if ($wholesaleactive == "1") $wholesaleactive = "0";
	else $wholesaleactive = "1";
	@mysqli_query($db, "UPDATE product SET wholesaleactive='$wholesaleactive' WHERE productid='$wt'");
	header("Location: editcatalogue.php?cat=$cat&search=$search&resultpage=$resultpage&pid=$pid");
	exit;
}

echo "$header<script language=\"JavaScript\">
		function vieworderformlink(query,formhtml) 
		{
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=600, height=250\");
			w.document.write('<html><head><title>".DIRECTLINK."</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\"><center><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".USETHISURLTOLINK.":<br><textarea name=\"description\" cols=\"65\" rows=\"2\">$ashopurl/buy.php?'+query+'"; if ($userid > 1) echo "&shop=$userid"; echo "&redirect=basket.php</textarea><br><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".ORUSETHISCODETOCREATEAFORM.":<br><textarea name=\"description\" cols=\"65\" rows=\"6\">'+formhtml+'</textarea><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSETHISWINDOW."</a></font></font><br></center></body></html>');
			return false;
	    }
		function viewtwitter(productid) 
		{
			w = window.open(\"sendtweet.php?productid=\"+productid+\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, "; if (!$twitteruser || !$twitterpass) echo " width=800, height=400"; else echo " width=400, height=150"; echo "\");
			return false;
	    }
		function viewqrgen(productid) 
		{
			w = window.open(\"qrgen.php?productid=\"+productid+\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=500, height=280\");
			return false;
	    }

    </script>";
if (!empty($cat) && $cat == "pages") echo "<div class=\"heading\">".MANAGEPAGES."</div>";
else echo "
	<div class=\"heading\">".EDITCATALOG."</div>";

if (!$cat) {
	$sql = "SELECT categoryid FROM category WHERE (userid LIKE '$catuser' OR memberclone='1') ORDER BY ordernumber";
	$result = @mysqli_query($db, $sql);
	$numberofcategories = @mysqli_num_rows($result);
	$cat = @mysqli_result($result, 0, "categoryid");
}

if ($unlock == "movetotop" && $userid == "1") @mysqli_query($db, "UPDATE user SET movelock='0'");

// Check that the page is not being reloaded...
if ($movetop) {
	$check = @mysqli_query($db, "SELECT * FROM product WHERE productid='$thisprodid' AND ordernumber='$thisordno'");
	if (!@mysqli_num_rows($check)) $movetop = "";
	else {
		$check = @mysqli_query($db, "SELECT * FROM product WHERE ordernumber='$topordno' AND productid!='$thisprodid'");
		if (!@mysqli_num_rows($check)) $movetop = "";
	}
	if ($thisordno == $topordno) $movetop = "";
}

if ($movetop) {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
	$movelock = @mysqli_num_rows($result);
	$starttime = time();
	while ($movelock && time()-$starttime < 180) {
		sleep(5);
		$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
		$movelock = @mysqli_num_rows($result);
	}

	if (!$movelock) {
		$starttime = time();
		$uptime = 0;
		if (!$uptimes) $uptimes = 1;
		@mysqli_query($db, "UPDATE user SET movelock='1' WHERE userid='$userid'");
		while ($thisordno != $topordno && $uptime < $uptimes) {
			if ($ashopsortorder == "DESC") {
				$movetopsort = "asc";
				$previous = ">";
			} else {
				$movetopsort = "desc";
				$previous = "<";
			}
			if ($cat == "pages") $result = @mysqli_query($db, "select * from product where ordernumber{$previous}'$thisordno' and userid like '$user' and not exists (select * from productcategory where productcategory.productid=product.productid) order by ordernumber $movetopsort limit 1");
			else $result = @mysqli_query($db, "select product.* from product, productcategory where product.ordernumber{$previous}'$thisordno' and product.productid=productcategory.productid and productcategory.categoryid='$cat' and userid like '$user' order by ordernumber $movetopsort limit 1");
			$prevordno = @mysqli_result($result, 0, "ordernumber");
			$prevprodid = @mysqli_result($result, 0, "productid");
			$sql="UPDATE product SET ordernumber=$prevordno WHERE productid=$thisprodid";
			$result = @mysqli_query($db, $sql);
			$sql="UPDATE product SET ordernumber=$thisordno WHERE productid=$prevprodid";
			$result = @mysqli_query($db, $sql);
			$thisordno = $prevordno;
			$uptime++;
			if (time()-$starttime >= 180) {
				$error = "movecrash";
				break;
			}
		}
		@mysqli_query($db, "UPDATE user SET movelock='0' WHERE userid='$userid'");
	} else $error = "movelock";
	unset($thisordno);
	unset($prevordno);
	unset($thisprodid);
	unset($prevprodid);
	unset($topordno);
}

// Make a top level category a subcategory...
if ($catmoveunder && !empty($thiscatid) && !empty($parentcatid)) {
	@mysqli_query($db, "UPDATE category SET grandparentcategoryid='$parentcatid' WHERE categoryid='$thiscatid'");
	@mysqli_query($db, "UPDATE category SET grandparentcategoryid='$parentcatid', parentcategoryid='$thiscatid' WHERE grandparentcategoryid='$thiscatid'");
}

// Move category up one step...
if ($catmoveup) {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
	$movelock = @mysqli_num_rows($result);
	$starttime = time();
	while ($movelock && time()-$starttime < 180) {
		sleep(5);
		$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
		$movelock = @mysqli_num_rows($result);
	}

	if (!$movelock) {
		$sql="UPDATE category SET ordernumber='$prevordno' WHERE categoryid='$thiscatid'";
		$result = @mysqli_query($db, $sql);
		$sql="UPDATE category SET ordernumber='$thisordno' WHERE categoryid='$prevcatid'";
		$result = @mysqli_query($db, $sql);
	} else $error = "movelock2";
}

// Check that the page is not being reloaded...
if ($catmovetop) {
	$check = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$thiscatid' AND ordernumber='$thisordno' AND userid like '$catuser'");
	if (!@mysqli_num_rows($check)) $catmovetop = "";
	else {
		$check = @mysqli_query($db, "SELECT * FROM category WHERE ordernumber='$topordno' AND categoryid!='$thiscatid' AND userid like '$catuser'");
		if (!@mysqli_num_rows($check)) $catmovetop = "";
	}
	if ($thisordno == $topordno) $catmovetop = "";
}

if ($catmovetop) {
	$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
	$movelock = @mysqli_num_rows($result);
	$starttime = time();
	while ($movelock && time()-$starttime < 180) {
		sleep(5);
		$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
		$movelock = @mysqli_num_rows($result);
	}

	if (!$movelock) {
		$starttime = time();
		@mysqli_query($db, "UPDATE user SET movelock='1' WHERE userid='$userid'");
		while ($thisordno != $topordno) {
			$result = @mysqli_query($db, "select * from category where ordernumber<'$thisordno' and userid like '$catuser' order by ordernumber desc limit 1");
			$prevordno = @mysqli_result($result, 0, "ordernumber");
			$prevcatid = @mysqli_result($result, 0, "categoryid");
			$sql="UPDATE category SET ordernumber=$prevordno WHERE categoryid='$thiscatid'";
			$result = @mysqli_query($db, $sql);
			$sql="UPDATE category SET ordernumber=$thisordno WHERE categoryid='$prevcatid'";
			$result = @mysqli_query($db, $sql);
			$thisordno = $prevordno;
			if (time()-$starttime >= 180) {
				$error = "movecrash";
				break;
			}
		}
		@mysqli_query($db, "UPDATE user SET movelock='0' WHERE userid='$userid'");
	} else $error = "movelock";
	unset($thisordno);
	unset($prevordno);
	unset($thiscatid);
	unset($prevcatid);
	unset($topordno);
}

if ($msg == "sent") echo "<p align=\"center\" class=\"confirm\">".UPDATENOTICESENT."</p>";
else if ($msg == "notsent") echo "<p align=\"center\" class=\"notconfirm\">".UPDATENOTICENOTSENT."</p>";
else if ($msg == "giftsent") echo "<p align=\"center\" class=\"confirm\">".FREEGIFTSENT."</p>";
else if ($msg == "gifterror") echo "<p align=\"center\" class=\"notconfirm\">".GIFTCOULDNOTBESENT."</p>";
else if ($msg == "htmldone") echo "<p align=\"center\" class=\"confirm\">".PRODUCTDETAILSUPDATED."</p>";
else if ($msg == "keycodescleared") echo "<p align=\"center\" class=\"confirm\">".KEYCODESCLEARED."</p>";

if ($error) {
	echo "<p class=\"error\">".ERROR."<br>";
	if ($error==1) echo USERNAMEORPASSINCORRECT;
	elseif ($error==2) echo DATABASENAMEINCORRECT;
	elseif ($error=="extension") echo PICTUREMUSTBEGIFORJPG;
	elseif ($error=="keycodes") echo COULDNOTWRITETOPRODUCTSDIR;
	elseif ($error=="import") echo UPLOADDOESNOTAPPEARTOBEPRODUCTLIST;
	elseif ($error=="movelock") {
		echo COULDNOTMOVELOCKED;
		if ($userid == "1") echo " <a href=\"editcatalogue.php?unlock=movetotop\">".CLICKHERE."</a> ".TOUNLOCKIT;
	}
	elseif ($error=="moveup") echo COULDNOTMOVESORTORDERINCORRECT;
	elseif ($error=="movelock2") {
		echo COULDNOTMOVELOCKED2;
		if ($userid == "1") echo " <a href=\"editcatalogue.php?unlock=movetotop\">".CLICKHERE."</a> ".TOUNLOCKIT;
	}
	elseif ($error=="movecrash") echo COULDNOTMOVETIMEOUT;
	elseif ($error=="sortcrash") echo COULDNOTREORDERTIMEOUT;
	elseif ($error=="sortlock") {
		echo COULDNOTREORDERLOCKED;
		if ($userid == "1") echo " <a href=\"editcatalogue.php?unlock=movetotop\">".CLICKHERE."</a> ".TOUNLOCKIT;
	}
	echo "</p>";
}

echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td width=\"180\" valign=\"top\" style=\"padding-right: 10px;\">
      ";
if ($userid == "1" && ($membershops || $memberprodmanage)) {
	echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\"><tr><td class=\"formtitle\" nowrap><form action=\"editcatalogue.php\" method=\"post\" style=\"margin-bottom: 0px;\" name=\"shopfilterform\">".SHOW.": <input type=\"hidden\" name=\"cat\" value=\"$cat\"><select name=\"shopfilter\" onChange=\"document.shopfilterform.submit()\" style=\"width: 109px\"><option value=\"none\""; if (!$shopfilter || $shopfilter == "none") echo " selected"; echo ">".ALLPRODUCTS."</option><option value=\"main\""; if ($shopfilter == "main") echo " selected"; echo ">".MAINSHOP."</option>";
	if ($catmemberid) {
		echo "<option value=\"member\""; if ($shopfilter == "member") echo " selected"; echo ">".MEMBERSHOP."</option>";
	}
	echo "
	</select></form></td></tr></table><br>";
}

// Manage pages...
if ($cat == "pages") $cellcolor = "#F0F0F0";
else $cellcolor = "#D0D0D0";
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\" class=\"categorytable\"><tr><td bgcolor=\"$cellcolor\" valign=\"top\"><span class=\"category\"><a href=\"editcatalogue.php?cat=pages\">".MANAGEPAGES."</a></span><br><span class=\"smaller\"><a href=\"addcontent.php?page=true&cat=pages&resultpage=$resultpage\" class=\"smaller\">[".NEWPAGE."]</a></span></td></tr></table>";

echo "<p class=\"category\">";
if ($userid == "1") echo "<a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\" alt=\"$alt1\"  title=\"$alt1\"></a> ";
echo SELECTCATEGORY."<br>
	  <table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\" class=\"categorytable\">";

// Select the product category if just one product should be displayed...
if ($pid) {
	$result = @mysqli_query($db, "SELECT categoryid FROM productcategory WHERE productid='$pid'");
	if (@mysqli_num_rows($result)) $cat = @mysqli_result($result,0,"categoryid");
}

// Make sure the correct top category ID is used...
if ($userid == "1") $result = @mysqli_query($db, "SELECT * FROM category WHERE grandparentcategoryid = categoryid ORDER BY ordernumber");
else $result = @mysqli_query($db, "SELECT * FROM category WHERE grandparentcategoryid = categoryid AND userid='$user' ORDER BY ordernumber");
$topcategoryid = @mysqli_result($result, $i, "categoryid");
$topcatordernumber = @mysqli_result($result, $i, "ordernumber");

// List categories...
	if (($membershops && $userid > 1) || $shopfilter == "member") $condition = " OR memberclone='1'";
	else $condition = "";
    if ($cat) {
       $sql="SELECT grandparentcategoryid, parentcategoryid from category WHERE categoryid = $cat AND (userid LIKE '$catuser'$condition) ORDER BY ordernumber";
       $result = @mysqli_query($db, $sql);
       $grandparent = @mysqli_result($result, 0, "grandparentcategoryid");
       $parent = @mysqli_result($result, 0, "parentcategoryid");
    }
    $sql="SELECT * FROM category WHERE grandparentcategoryid = categoryid AND (userid LIKE '$catuser'$condition) ORDER BY ordernumber";
    $result = @mysqli_query($db, $sql);
    for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
      $categoryname = @mysqli_result($result, $i, "name");
      $categoryid = @mysqli_result($result, $i, "categoryid");
	  $catordernumber = @mysqli_result($result, $i, "ordernumber");
	  $catowner = @mysqli_result($result, $i, "userid");
	  if ($catowner != $userid && $userid != "1") $catordernumber = "";
	  $categoryicon = "";
	  if ($userid == "1" && $shopfilter == "member" && $catowner != $catmemberid) {
		  // Check if this category or its subcategories contain any products belonging to this member...
		  $subcatresult = @mysqli_query($db, "SELECT * FROM category WHERE (grandparentcategoryid='$categoryid' OR parentcategoryid='$categoryid') AND categoryid!='$categoryid'");
		  if (@mysqli_num_rows($subcatresult)) $memberprodexistsresult = @mysqli_query($db, "SELECT product.name FROM product,productcategory,category WHERE product.userid LIKE '$user' AND product.productid=productcategory.productid AND productcategory.categoryid=category.categoryid AND (category.categoryid = '$categoryid' OR category.parentcategoryid = '$categoryid' OR category.grandparentcategoryid = '$categoryid') LIMIT 1");
		  else $memberprodexistsresult = @mysqli_query($db, "SELECT product.name FROM product,productcategory,category WHERE product.userid LIKE '$user' AND product.productid=productcategory.productid AND productcategory.categoryid=category.categoryid AND category.categoryid = '$categoryid' LIMIT 1");
		  if (!@mysqli_num_rows($memberprodexistsresult)) continue;
	  }
	  if ($catowner > "1") {
		  $ownerresult = @mysqli_query($db, "SELECT shopname FROM user WHERE userid='$catowner'");
		  $catownername = @mysqli_result($ownerresult,0,"shopname");
		  $categoryicon = "<a href=\"editmember.php?memberid=$catowner\"><img src=\"images/icon_owner.gif\" alt=\"".OWNEDBY.": $catownername\" title=\"".OWNEDBY.": $catownername\" border=\"0\"></a>";
	  }
	  if ($categoryid == $cat) $cellcolor = "#F0F0F0";
	  else $cellcolor = "#D0D0D0";
	  // Get product count...
	  $subcatresult = @mysqli_query($db, "SELECT * FROM category WHERE (grandparentcategoryid='$categoryid' OR parentcategoryid='$categoryid') AND categoryid!='$categoryid'");
	  if (@mysqli_num_rows($subcatresult)) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND (category.categoryid='$categoryid' OR category.grandparentcategoryid='$categoryid' OR category.parentcategoryid='$categoryid') AND product.productid=productcategory.productid AND product.userid LIKE '$user'");
	  else $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM productcategory,product WHERE productcategory.categoryid='$categoryid' AND product.userid LIKE '$user' AND product.productid=productcategory.productid");
	  $productcount = @mysqli_num_rows($prodcountresult);
	  if (empty($productcount)) $productcount = 0;
      echo "<tr><td bgcolor=\"$cellcolor\" valign=\"top\">$categoryicon <a href=\"editcatalogue.php?cat=$categoryid\" class=\"category\">$categoryname</a><span class=\"category\" style=\"font-weight: normal;\"> ($productcount)</span>";
	  if ($userid == "1" || ($membershops && $userid == $catowner)) {
		  echo "<br><span class=\"smaller\"><a href=\"editcategory.php?cat=$categoryid\" class=\"smaller\">[".EDIT."]</a> <a href=\"editcategory.php?cat=$categoryid&remove=True\" class=\"smaller\">[".REMOVE."]</a>";
		  if ($userid == "1") echo " <a href=\"pagegenerator.php?categoryid=$categoryid\" class=\"smaller\">[".SITEMAP."]</a> <a href=\"editdiscount.php?cat=$categoryid\" class=\"smaller\">[".DISCOUNT."]</a>";
		  if ($catpreviousorderno || $catordernumber != $topcatordernumber) echo "<br>";
		  if ($catpreviousorderno) echo "<a href=\"editcatalogue.php?thisordno=$catordernumber&prevordno=$catpreviousorderno&thiscatid=$categoryid&prevcatid=$previouscategoryid&catmoveup=true&cat=$cat\" class=\"smaller\">[".MOVEUP."]</a> <a href=\"editcatalogue.php?thiscatid=$categoryid&parentcatid=$previouscategoryid&catmoveunder=true&cat=$cat\" class=\"smaller\">[".MOVEUNDER."]</a>";
		  if ($catordernumber != $topcatordernumber) echo " <a href=\"editcatalogue.php?thisordno=$catordernumber&topordno=$topcatordernumber&thiscatid=$categoryid&catmovetop=true&cat=$cat\" class=\"smaller\">[".MOVETOTOP."]</a>";
	  }
	  echo "</span></td></tr>";
      if (($categoryid == $cat) || ($categoryid == $grandparent)) {
         $subsql="SELECT categoryid, name, ordernumber FROM category WHERE grandparentcategoryid = $categoryid AND categoryid != grandparentcategoryid AND parentcategoryid = categoryid AND (userid LIKE '$catuser'$condition) ORDER BY ordernumber";
         $subresult = @mysqli_query($db, $subsql);
         for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
            $subcategoryname = @mysqli_result($subresult, $j, "name");
            $subcategoryid = @mysqli_result($subresult, $j, "categoryid");
			$suborderno = @mysqli_result($subresult, $j, "ordernumber");
			if ($subcategoryid == $cat) $cellcolor = "#F0F0F0";
 		    else $cellcolor = "#D0D0D0";
			if ($userid == "1" && $shopfilter == "member" && $catowner != $catmemberid) {
				// Check if this category or its subcategories contain any products belonging to this member...
				$memberprodexistsresult = @mysqli_query($db, "SELECT product.name FROM product,productcategory,category WHERE product.userid='$user' AND product.productid=productcategory.productid AND productcategory.categoryid=category.categoryid AND (category.categoryid = '$subcategoryid' OR category.parentcategoryid = '$subcategoryid' OR category.grandparentcategoryid = '$subcategoryid') LIMIT 1");
				if (!@mysqli_num_rows($memberprodexistsresult)) continue;
			}
			// Get product count...
			$subcatresult = @mysqli_query($db, "SELECT * FROM category WHERE parentcategoryid='$subcategoryid' AND categoryid!='$subcategoryid'");
			if (@mysqli_num_rows($subcatresult)) $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND (category.categoryid='$subcategoryid' OR category.parentcategoryid='$subcategoryid') AND product.productid=productcategory.productid AND product.userid LIKE '$user'");
			else $prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM productcategory,product WHERE productcategory.categoryid='$subcategoryid' AND product.userid LIKE '$user' AND product.productid=productcategory.productid");
			$productcount = @mysqli_num_rows($prodcountresult);
			if (empty($productcount)) $productcount = 0;
            echo "<tr><td bgcolor=\"$cellcolor\"><img src=\"images/icon_subcategory.gif\" alt=\"".SUBCATEGORYOF." $categoryname\" title=\"".SUBCATEGORYOF." $categoryname\"><a href=\"editcatalogue.php?cat=$subcategoryid\" class=\"category\">$subcategoryname</a><span class=\"category\" style=\"font-weight: normal;\"> ($productcount)</span>";
			if ($userid == "1" || ($membershops && $userid == $catowner)) {
				echo "<br><img src=\"images/10pxl.gif\"><a href=\"editcategory.php?cat=$subcategoryid\" class=\"smaller\">[".EDIT."]</a> <a href=\"editcategory.php?cat=$subcategoryid&remove=True\" class=\"smaller\">[".REMOVE."]";
				if ($userid == "1") echo " <a href=\"pagegenerator.php?categoryid=$subcategoryid\" class=\"smaller\">[".SITEMAP."]</a> <a href=\"editdiscount.php?cat=$subcategoryid\" class=\"smaller\">[".DISCOUNT."]</a>";
				if ($previoussuborderno) echo "<br><img src=\"images/10pxl.gif\"><a href=\"editcatalogue.php?thisordno=$suborderno&prevordno=$previoussuborderno&thiscatid=$subcategoryid&prevcatid=$previoussubcategoryid&catmoveup=true&cat=$cat\" class=\"smaller\">[".MOVEUP."]</a>";
			}
			echo "</span></td></tr>";
			$previoussuborderno = $suborderno;
			$previoussubcategoryid = $subcategoryid;
			if ($subcategoryid == $parent || $subcategoryid == $cat) {
				$subsubsql="SELECT categoryid, name, ordernumber FROM category WHERE parentcategoryid = $subcategoryid AND parentcategoryid != categoryid AND (userid LIKE '$catuser'$condition) ORDER BY ordernumber";
				$subsubresult = @mysqli_query($db, $subsubsql);
				for ($k = 0; $k < @mysqli_num_rows($subsubresult); $k++) {
					$subsubcategoryname = @mysqli_result($subsubresult, $k, "name");
					$subsubcategoryid = @mysqli_result($subsubresult, $k, "categoryid");
					$subsuborderno = @mysqli_result($subsubresult, $k, "ordernumber");
					if ($subsubcategoryid == $cat) $cellcolor = "#F0F0F0";
					else $cellcolor = "#D0D0D0";
					// Get product count...
					$prodcountresult = @mysqli_query($db, "SELECT DISTINCT productcategory.productid FROM product, category, productcategory WHERE productcategory.categoryid=category.categoryid AND category.categoryid='$subsubcategoryid' AND product.productid=productcategory.productid AND product.userid LIKE '$user'");
					$productcount = @mysqli_num_rows($prodcountresult);
					if (empty($productcount)) $productcount = 0;
					echo "<tr><td bgcolor=\"$cellcolor\">&nbsp;&nbsp;<img src=\"images/icon_subcategory.gif\" alt=\"".SUBCATEGORYOF." $subcategoryname\" title=\"".SUBCATEGORYOF." $subcategoryname\"><a href=\"editcatalogue.php?cat=$subsubcategoryid\" class=\"category\">$subsubcategoryname</a><span class=\"category\" style=\"font-weight: normal;\"> ($productcount)</span>";
					if ($userid == "1" || $membershops) {
						echo "<br><img src=\"images/10pxl.gif\"><a href=\"editcategory.php?cat=$subsubcategoryid\" class=\"smaller\">[".EDIT."]</a> <a href=\"editcategory.php?cat=$subsubcategoryid&remove=True\" class=\"smaller\">[".REMOVE."]";
						if ($userid == "1") echo " <a href=\"pagegenerator.php?categoryid=$subsubcategoryid\" class=\"smaller\">[".SITEMAP."]</a> <a href=\"editdiscount.php?cat=$subsubcategoryid\" class=\"smaller\">[".DISCOUNT."]</a>";
						if ($previoussubsuborderno) echo "<br><img src=\"images/10pxl.gif\"><a href=\"editcatalogue.php?thisordno=$subsuborderno&prevordno=$previoussubsuborderno&thiscatid=$subsubcategoryid&prevcatid=$previoussubsubcategoryid&catmoveup=true&cat=$cat\" class=\"smaller\">[".MOVEUP."]</a>";
					}
					echo "</span></td></tr>";
					$previoussubsuborderno = $subsuborderno;
					$previoussubsubcategoryid = $subsubcategoryid;
				}
			}
         }
      }
	  $catpreviousorderno = $catordernumber;
	  $previouscategoryid = $categoryid;
    }
echo "</table></p></td><td valign=\"top\">";

// Show category name and description...
  if ($cat == "pages") {
	  echo "<table width=\"600\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" style=\"border: 1px solid #000000;\"><tr><td><form action=\"addcontent.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".NEWPAGE."\" class=\"widebutton\"></form></td></tr></table>
	  <table width=\"100%\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\"><tr><td colspan=\"4\"><br><span class=\"heading3\">".MANAGEPAGES.":</span></td></tr>
	  </table>";
  } else if ($cat) {
	  if ($search) {
		  $categoryname = SEARCHRESULTS;
		  $categorydescr = "";
	  } else {
		  $sql="SELECT name, description, memberclone FROM category WHERE categoryid = $cat AND (userid LIKE '$catuser' OR memberclone='1')";
		  $result = @mysqli_query($db, $sql);
		  $categoryname = @mysqli_result($result, 0, "name");
		  $categorydescr = @mysqli_result($result, 0, "description");
		  $categoryclone = @mysqli_result($result, 0, "memberclone");
	  }
	  echo "<table width=\"600\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" style=\"border: 1px solid #000000;\"><tr>";
	  if ($userid == "1" || $membershops) echo "<td><form action=\"addcategory.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"submit\" value=\"".NEWCATEGORY."\" class=\"widebutton\"></form></td>";
	  else echo "<td>&nbsp;</td>";
	  echo "<td><form action=\"addproduct.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".NEWPRODUCT."\" class=\"widebutton\"></form></td>
	  <td><form action=\"addcontent.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".NEWCONTENT."\" class=\"widebutton\"></form></td>";
	  if ($userid == "1") echo "<td align=\"right\"><form action=\"editstorediscounts.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" value=\"$cat\" name=\"cat\"><input type=\"submit\" value=\"".DISCOUNTS."\" class=\"widebutton\"></form></td></tr>";
	  else echo "<td>&nbsp;</td></tr>";
	  echo "<tr><td colspan=\"3\" class=\"formtitle\"><form action=\"editcatalogue.php?cat=$cat\" method=\"post\" style=\"margin-bottom: 0px;\">".SEARCHFOR.": <input type=\"text\" name=\"search\" value=\"{$search}\" size=\"28\" style=\"width: 211px; margin-right: 26px;\">
	  <input type=\"submit\" value=\"".SEARCH."\" class=\"widebutton\"></form></td>
	  <td align=\"right\"><form action=\"sortcatalogue.php\" method=\"post\" name=\"sortform\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><select name=\"sortby\" onChange=\"document.sortform.submit()\" style=\"width: 130px\"><option value=\"none\">".REORDERPRODUCTS."</option><option value=\"name\">".ALPHABETICALLY."</option><option value=\"productidasc\">".BYPRODUCTID."</option><option value=\"productiddesc\">".REVERSEBYID."</option><option value=\"ordernumber\">".REVERSE."</option></select></form></td></tr>
	  <tr><td class=\"formtitle\" colspan=\"2\"><form action=\"editcatalogue.php?cat=$cat\" method=\"post\" style=\"margin-bottom: 0px;\">".PRODUCTID.": <input type=\"text\" name=\"pid\" value=\"{$pid}\" size=\"10\" style=\"width: 54px; margin-right: 22px\">
	  <input type=\"submit\" value=\"".LOOKUP."\" class=\"widebutton\"></form></td><td>";
	  if ($userid == "1" || (isset($memberuploadsize) && $memberuploadsize > 0)) echo "<form action=\"filemanager.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".FILEMANAGER."\" class=\"widebutton\"></form>";
	  else echo "&nbsp;";
	  echo "</td><td align=\"right\"><form action=\"importproducts.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".IMPORTPRODUCTS."\" class=\"widebutton\"></form></td></tr></table>
	  <table width=\"100%\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\"><tr><td colspan=\"4\"><br><span class=\"heading3\">$categoryname:</span> <span class=\"text\">$categorydescr</span></td></tr>
	  </table>";
  } else {
	  echo "<table width=\"600\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" style=\"border: 1px solid #000000;\"><tr>";
	  if ($userid == "1" || $membershops) echo "<td><form action=\"addcategory.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"submit\" value=\"".NEWCATEGORY."\" class=\"widebutton\"></form></td>";
	  else echo "<td>&nbsp;</td>";
	  echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></table>";
  }

  
// List products belonging to this category...
    if ($search) {
		$searchwords = explode(" ", $search);
		if ($categoryclone == "1" && $userid == "1" && !$shopfilter) $sql = "SELECT * from product WHERE";
		else $sql="SELECT * from product WHERE userid LIKE '$user' AND";
		foreach($searchwords as $wordnumber => $thisword) {
			if ($wordnumber == 0) $sql.=" (UPPER(description) LIKE '%".strtoupper($thisword)."%' OR UPPER(name) LIKE '%".strtoupper($thisword)."%' OR UPPER(skucode) LIKE '%".strtoupper($thisword)."%')";
			else $sql.=" AND (UPPER(description) LIKE '%".strtoupper($thisword)."%' OR UPPER(name) LIKE '%".strtoupper($thisword)."%' OR UPPER(skucode) LIKE '%".strtoupper($thisword)."%')";
		}
		$sql.=" AND name!='AShopFirstPage' AND name!='AShopAboutPage' AND name!='AShopTermsPage' AND name!='AShopPrivacyPage' ORDER BY name";
	} else if ($pid) {
		if ($categoryclone == "1" && $userid == "1" && !$shopfilter) $sql = "SELECT * FROM product WHERE productid='$pid' AND name!='AShopFirstPage' AND name!='AShopAboutPage' AND name!='AShopTermsPage' AND name!='AShopPrivacyPage'";
		else $sql = "SELECT * FROM product WHERE userid LIKE '$user' AND productid='$pid' AND name!='AShopFirstPage' AND name!='AShopAboutPage' AND name!='AShopTermsPage' AND name!='AShopPrivacyPage'";
	} else if ($cat == "pages") {
		$sql = "SELECT * from product WHERE userid LIKE '$user' AND prodtype='content' AND NOT EXISTS (SELECT * FROM productcategory WHERE productcategory.productid=product.productid) ORDER BY ordernumber $ashopsortorder";
	} else {
		if ($categoryclone == "1" && $userid == "1" && !$shopfilter) $sql = "SELECT product.* from productcategory, product WHERE productcategory.categoryid = '$cat' AND product.productid = productcategory.productid ORDER BY product.ordernumber $ashopsortorder";
		else $sql="SELECT product.* from productcategory, product WHERE productcategory.categoryid = '$cat' AND product.productid = productcategory.productid AND product.userid LIKE '$user' ORDER BY product.ordernumber $ashopsortorder";
	}
    $result = @mysqli_query($db, $sql);
	$topprodid = @mysqli_result($result, 0, "productid");
	$toporderno = @mysqli_result($result, 0, "ordernumber");
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
	  $ordernumber = $row["ordernumber"];
	  $productstatus = $row["active"];
	  $productwsstatus = $row["wholesaleactive"];
	  if (!empty($row["copyof"]) && is_numeric($row["copyof"])) {
		  $realproductid = $row["copyof"];
		  $copyresult = @mysqli_query($db, "SELECT * FROM product WHERE productid='$realproductid'");
		  $row = @mysqli_fetch_array($copyresult);
	  } else $realproductid = $productid;
      $productname = $row["name"];
	  $productsku = $row["skucode"];
	  $producttype = $row["prodtype"];
      $description = $row["description"];
	  $ownerid = $row["userid"];
	  if ($ownerid > "1") {
		  $ownerresult = @mysqli_query($db, "SELECT * FROM user WHERE userid='$ownerid'");
		  $owner = @mysqli_result($ownerresult,0,"shopname");
	  }
	  $productuseinventory = $row["useinventory"];
	  $productinventory = $row["inventory"];
	  $productlowlimit = $row["lowlimit"];
	  if ($productuseinventory) {
		  if (!$productinventory) $inventorystatus = " <a href=\"editinventory.php?productid=$realproductid&cat=$cat&search=$search&resultpage=$resultpage&rt=$realproductid&pid=$pid\"><img src=\"images/icon_inv3.gif\" alt=\"".OUTOFSTOCK."\" title=\"".OUTOFSTOCK."\" border=\"0\"></a>";
		  else if ($productinventory < $productlowlimit) $inventorystatus = " <a href=\"editinventory.php?productid=$realproductid&cat=$cat&search=$search&resultpage=$resultpage&rt=$realproductid&pid=$pid\"><img src=\"images/icon_inv2.gif\" alt=\"".LOWSTOCK." ($productinventory)\" title=\"".LOWSTOCK." ($productinventory)\" border=\"0\"></a>";
		  else $inventorystatus = " <a href=\"editinventory.php?productid=$realproductid&cat=$cat&search=$search&resultpage=$resultpage&rt=$realproductid&pid=$pid\"><img src=\"images/icon_inv1.gif\" alt=\"".INSTOCK." ($productinventory)\" title=\"".INSTOCK." ($productinventory)\" border=\"0\"></a>";
	  } else $inventorystatus = "";
	  $detailsurl = $row["detailsurl"];
	  unset($retailstatus);
	  unset($wholesalestatus);
	  unset($fpprice);
	  $fppriceresult = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$realproductid'");
	  $fpprice = @mysqli_num_rows($fppriceresult);
	  $fptype = @mysqli_result($fppriceresult,0,"type");
	  $fpended = @mysqli_result($fppriceresult,0,"endprice");
	  $fpwinner = @mysqli_result($fppriceresult,0,"bidderid");
	  if ($productstatus == 1) {
		  if ($userid == "1") $retailstatus = " <a href=\"editcatalogue.php?cat=$cat&search=$search&resultpage=$resultpage&rt=$productid&pid=$pid\"><img src=\"images/icon_on.gif\" alt=\"".VISIBLEINRETAILCATALOG."\" title=\"".VISIBLEINRETAILCATALOG."\" border=\"0\"></a>";
		  else $retailstatus = " <img src=\"images/icon_on.gif\" alt=\"".VISIBLEINRETAILCATALOG."\" title=\"".VISIBLEINRETAILCATALOG."\" border=\"0\">";
	  }
	  else {
		  if ($userid == "1") $retailstatus = " <a href=\"editcatalogue.php?cat=$cat&search=$search&resultpage=$resultpage&rt=$productid&pid=$pid\"><img src=\"images/icon_off.gif\" alt=\"".NOTVISIBLEINRETAILCATALOG."\" title=\"".NOTVISIBLEINRETAILCATALOG."\" border=\"0\"></a>";
		  else $retailstatus = " <img src=\"images/icon_off.gif\" alt=\"".NOTVISIBLEINRETAILCATALOG."\" title=\"".NOTVISIBLEINRETAILCATALOG."\" border=\"0\">";
	  }
	  if ($productwsstatus == 1 && $wholesalecatalog && $producttype != "subscription" && !$fpprice) {
		  if ($userid == "1") $wholesalestatus = " <a href=\"editcatalogue.php?cat=$cat&pid=$pid&search=$search&resultpage=$resultpage&wt=$productid\"><img src=\"images/icon_on.gif\" alt=\"".VISIBLEINWHOLESALECATALOG."\" title=\"".VISIBLEINWHOLESALECATALOG."\" border=\"0\"></a>";
		  else if ($advancedmallmode == "1") $wholesalestatus = " <img src=\"images/icon_on.gif\" alt=\"".VISIBLEINWHOLESALECATALOG."\" title=\"".VISIBLEINWHOLESALECATALOG."\" border=\"0\">";
	  } else if ($wholesalecatalog && $producttype != "subscription" && !$fpprice) {
		  if ($userid == "1") $wholesalestatus = " <a href=\"editcatalogue.php?cat=$cat&pid=$pid&search=$search&resultpage=$resultpage&wt=$productid\"><img src=\"images/icon_off.gif\" alt=\"".NOTVISIBLEINWHOLESALECATALOG."\" title=\"".NOTVISIBLEINWHOLESALECATALOG."\" border=\"0\"></a>";
		  else if ($advancedmallmode == "1") $wholesalestatus = " <img src=\"images/icon_off.gif\" alt=\"".NOTVISIBLEINWHOLESALECATALOG."\" title=\"".NOTVISIBLEINWHOLESALECATALOG."\" border=\"0\">";
	  }
	  if ($producttype == "content") {
		  echo "<table cellspacing=\"0\" cellpadding=\"5\" width=\"100%\" id=\"productbox$productid\" style=\"border: 1px solid #000000; margin-bottom: 5px;\">
		  <script language=\"javascript\">
			var NS = (navigator.appName==\"Netscape\")?true:false;
			iWidth = (NS)?window.innerWidth:document.body.clientWidth;
			if (iWidth > 1300) document.getElementById(\"productbox$productid\").width = 800;
		  </script>
		  <tr><td class=\"text\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\"><tr><td width=\"90%\" class=\"productname\">";
		  if ($cat == "pages") {
			  $productlanguage = $row["language"];
			  if (!empty($productlanguage) && $productlanguage != "any") echo " <img src=\"../language/$productlanguage/flag.gif\" alt=\"$productlanguage\"> ";
			  if ($productname == "AShopFirstPage") {
				  if (file_exists("$ashoppath/index.php")) echo "<a href=\"$ashopurl/index.php";
				  else echo "<a href=\"$ashopurl/catalogue.php";
				  if ($ownerid > 1) {
					  echo "?shop=$ownerid";
					  if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "&lang=$productlanguage";
				  } else if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "?lang=$productlanguage";
				  echo "\" target=\"_blank\">".WELCOMEMESSAGE."</a>";
			  }
			  else if ($productname == "AShopFirstPageMobile") {
				  if (file_exists("$ashoppath/index.php")) echo "<a href=\"$ashopurl/index.php";
				  else echo "<a href=\"$ashopurl/catalogue.php";
				  if ($ownerid > 1) {
					  echo "?shop=$ownerid";
					  if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "&lang=$productlanguage";
				  } else if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "?lang=$productlanguage";
				  echo "\" target=\"_blank\">".WELCOMEMESSAGEMOBILE."</a>";
			  }
			  else if ($productname == "AShopAboutPage") {
				  echo "<a href=\"$ashopurl/aboutus.php";
				  if ($ownerid > 1) {
					  echo "?shop=$ownerid";
					  if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "&lang=$productlanguage";
				  } else if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "?lang=$productlanguage";
				  echo "\" target=\"_blank\">".ABOUTPAGE."</a>";
			  }
			  else if ($productname == "AShopTermsPage") {
				  echo "<a href=\"$ashopurl/terms.php";
				  if ($ownerid > 1) {
					  echo "?shop=$ownerid";
					  if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "&lang=$productlanguage";
				  } else if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "?lang=$productlanguage";
				  echo "\" target=\"_blank\">".TERMSPAGE."</a>";
			  }
			  else if ($productname == "AShopPrivacyPage") {
				  echo "<a href=\"$ashopurl/privacy.php";
				  if ($ownerid > 1) {
					  echo "?shop=$ownerid";
					  if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "&lang=$productlanguage";
				  } else if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) echo "?lang=$productlanguage";
				  echo "\" target=\"_blank\">".PRIVACYPAGE."</a>";
			  } else {
				  if (empty($detailsurl)) {
					  $detailsurl = "$ashopurl/page.php?id=$productid";
					  if ($ownerid > 1) $detailsurl .= "&shop=$ownerid";
					  if (!empty($productlanguage) && $productlanguage != "any" && $productlanguage != $defaultlanguage) $detailsurl .= "&lang=$productlanguage";
				  }
				  echo "<a href=\"$detailsurl\" target=\"_blank\">$productname</a>";
			  }
			  if ($userid == "1" && $ownerid > "1") echo "<p><span class=\"formtitle\">".OWNEDBY.": <a href=\"editmember.php?memberid=$ownerid\">$owner</a></span></p>";
		  } else echo "$retailstatus$wholesalestatus";
		  echo "</td></tr></table><br>$description<br><br></td></tr>
		  <tr bgcolor=\"#DDDDDD\"><td><table width=\"264\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr>
		  <td width=\"85\"><form action=\"editcontent.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"edit\" value=\"".EDIT."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>
		  <td width=\"85\"><form action=\"editcontent.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"remove\" value=\"".REMOVE."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
		  if ($previousorderno && !$search) echo "<td valign=\"top\" width=\"85\">
		  <form action=\"editcatalogue.php\" method=\"POST\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"thisordno\" value=\"$ordernumber\"><input type=\"hidden\" name=\"topordno\" value=\"$toporderno\"><input type=\"hidden\" name=\"thisprodid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"text\" name=\"uptimes\" size=\"2\" value=\"1\" style=\"text-align: center;\"> <input type=\"submit\" name=\"movetop\" value=\"".UP."\" class=\"smallbutton\"></form></td>";
		  else echo "<td valign=\"top\" width=\"85\">&nbsp;</td>";
		  echo "</tr></table>
		  </td></tr></table>";
		  $previousorderno = $ordernumber;
		  $previousprodid = $productid;
		  continue;
	  }
      $price = $row["price"];
	  $cost = $row["cost"];
	  $checkvendor = @mysqli_query($db, "SELECT * FROM emerchant_vendor");
	  $checkvendor = @mysqli_num_rows($checkvendor);
	  $qtypriceresult = @mysqli_query($db, "SELECT * FROM qtypricelevels WHERE productid='$realproductid'");
	  $qtypricing = @mysqli_num_rows($qtypriceresult);
	  $wholesaleprice = $row["wholesaleprice"];
      $shipping = $row["shipping"];
	  $intshipping = $row["intshipping"];
	  $subscriptiondir = $row["subscriptiondir"];
	  $taxable = $row["taxable"];
	  $filesresult = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$realproductid'");
	  $files = @mysqli_num_rows($filesresult);
	  $sql2="SELECT * FROM productflags";
	  $result2 = @mysqli_query($db, $sql2);
	  if (@mysqli_num_rows($result2)) $flagsavailable = TRUE;
	  else $flagsavailable = FALSE;
	  $numberofavailkeys = ashop_checkfreecodes($db,$realproductid);
	  if (strstr($numberofavailkeys,"|")) {
		  $numberofavailkeys = explode("|",$numberofavailkeys);
		  $unlockkeys = $numberofavailkeys[0];
		  $unlockkeysproduct = $numberofavailkeys[2];
		  $numberofavailkeys = $numberofavailkeys[1];
		  if (!$numberofavailkeys) $numberofavailkeys = "0";
	  } else {
		  $numberofavailkeys = 0;
		  $unlockkeys = 0;
	  }
	  $sql2="SELECT * FROM discount WHERE productid='$realproductid'";
	  $result2 = @mysqli_query($db, $sql2);
	  $discounted = @mysqli_num_rows($result2);

	  // Check for related products...
	  $upsellresult = @mysqli_query($db, "SELECT * FROM relatedproducts WHERE productid='$realproductid' ORDER BY relationid ASC");
	  $upsellcount = 1;
	  $upsell1 = 0;
	  $upsell2 = 0;
	  while($upsellrow = @mysqli_fetch_array($upsellresult)) {
		  if ($upsellcount == 1) $upsell1 = $upsellrow["relationid"];
		  if ($upsellcount == 2) $upsell2 = $upsellrow["relationid"];
		  $upsellcount++;
	  }

	  // Generate html code for orderform...
	  $orderhtml = "<form action=&quot;$ashopurl/buy.php&quot; method=&quot;post&quot;><input type=&quot;hidden&quot; name=&quot;item&quot; value=&quot;$realproductid&quot;><input type=&quot;hidden&quot; name=&quot;redirect&quot; value=&quot;basket.php&quot;>".QUANTITY.": <input type=&quot;text&quot; name=&quot;quantity&quot; size=&quot;5&quot; value=&quot;1&quot;><br><br>";
	  $result2 = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$realproductid' ORDER BY parameterid");
	  while($row = @mysqli_fetch_array($result2)) {
		  $result3 = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid = '{$row["parameterid"]}' ORDER BY valueid");
		  if (!@mysqli_num_rows($result3)) $orderhtml .= "{$row["caption"]}: <input type=&quot;text&quot name=&quot;attribute{$row["parameterid"]}&quot;><br><br>";
		  else {
			  $orderhtml .= "{$row["caption"]}: <select name=&quot;parameter{$row["parameterid"]}&quot;>";
			  while($row2 = @mysqli_fetch_array($result3)) $orderhtml .= "<option value=&quot;{$row2["valueid"]}&quot;>{$row2["value"]}";
			  $orderhtml .= "</select><br><br>";
		  }
	  }
	  if ($userid > 1) $orderhtml .= "<input type=&quot;hidden&quot; name=&quot;shop&quot; value=&quot;$userid&quot;>";
	  $orderhtml .= "<input type=&quot;submit&quot; value=&quot;".BUYNOW."&quot;></form>";


      echo "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" id=\"productbox$productid\" style=\"border: 1px solid #000000; margin-bottom: 5px;\"><tr>
	  	  <script language=\"javascript\">
		var NS = (navigator.appName==\"Netscape\")?true:false;
		iWidth = (NS)?window.innerWidth:document.body.clientWidth;
		if (iWidth > 1300) document.getElementById(\"productbox$productid\").width = 800;
	  </script>";
	  $thumbnailcellwidth = $thumbnailwidth+6;
	  // Get product image info...
	  $productimage = ashop_productimages($realproductid);
	  if ($productimage["thumbnail"]) echo "<td width=\"$thumbnailcellwidth\" align=\"center\" valign=\"top\" style=\"padding: 3px;\"><img src=\"../prodimg/$realproductid/{$productimage["thumbnail"]}\" width=\"$thumbnailwidth\"></td>";
	  echo	"<td><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" align=\"center\" width=\"100%\"><tr>
	        <td><table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\"><tr><td width=\"90%\" class=\"productname\">";
	  if ($realproductid != $productid) echo "<font size=\"2\">($productid)</font> <a href=\"editcatalogue.php?pid=$realproductid\">$realproductid</a>";
	  else echo $productid;
	  echo ": ";
	  if ($productsku) echo " <i>$productsku</i> - ";
	  if ($detailsurl) echo "<a href=\"$detailsurl\" target=\"_blank\">$productname</a>";
	  else echo "$productname";
	  echo "$retailstatus$wholesalestatus$inventorystatus";
	  if ($unlockkeys) echo " <a href=\"listkeycodes.php?productid=$unlockkeysproduct&cat=$cat&resultpage=$resultpage\"><img src=\"images/icon_keycodes.gif\" alt=\"".THEREARE." $numberofavailkeys ".UNREGISTEREDKEYSREMAINING."\" title=\"".THEREARE." $numberofavailkeys ".UNREGISTEREDKEYSREMAINING."\" border=\"0\"></a>$numberofavailkeys";
	  if ($files) echo " <img src=\"images/icon_downloadable.gif\" alt=\"".FILEDOWNLOAD."\" title=\"".FILEDOWNLOAD."\">";
	  if ($subscriptiondir && $producttype == "subscription") echo " <a href=\"listsubscr.php?productid=$realproductid&cat=$cat&resultpage=$resultpage\"><img src=\"images/icon_subscription.gif\" alt=\"".SUBSCRIPTIONTOPROTECTEDDIR."\" title=\"".SUBSCRIPTIONTOPROTECTEDDIR."\"></a>";
	  else if ($producttype == "subscription") echo " <img src=\"images/icon_subscription.gif\" alt=\"".SUBSCRIPTION."\" title=\"".SUBSCRIPTION."\">";
	  echo  "</td>";
	  if (!$relate) {
		  if ($upsell1) {
			  echo "<td align=\"right\" width=\"10%\"><a href=\"editupsell.php?editrelation=$upsell1&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_upsell1.gif\" border=\"0\" alt=\"".SELECTFIRSTRELATED."\" title=\"".SELECTFIRSTRELATED."\"></a></td>";
			  if ($upsell2) {
				  echo "<td align=\"right\" width=\"10%\"><a href=\"editupsell.php?editrelation=$upsell2&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_upsell2.gif\" border=\"0\" alt=\"".SELECTSECONDRELATED."\" title=\"".SELECTSECONDRELATED."\"></a></td>";
			  } else {
				  echo "<td align=\"right\" width=\"10%\"><a href=\"editcatalogue.php?relate=$realproductid&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_addupsell2.gif\" border=\"0\" alt=\"".SELECTSECONDRELATED."\" title=\"".SELECTSECONDRELATED."\"></a></td>";
			  }
		  } else echo "<td align=\"right\" width=\"10%\"><a href=\"editcatalogue.php?relate=$realproductid&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_addupsell1.gif\" border=\"0\" alt=\"".SELECTFIRSTRELATED."\" title=\"".SELECTFIRSTRELATED."\"></a></td>";
	  } else if ($relate != $realproductid && $upsellproduct != $realproductid) echo "<td align=\"right\" width=\"10%\"><a href=\"editupsell.php?productid=$relate&relatewith=$realproductid&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_accept.gif\" border=\"0\" alt=\"".ACCEPT."\" title=\"".ACCEPT."\"></a></td><td align=\"right\" width=\"10%\"><a href=\"editcatalogue.php?cancelrelate=true&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_cancel.gif\" border=\"0\" alt=\"".CANCEL."\" title=\"".CANCEL."\"></a></td>";
	  else echo "<td align=\"right\" width=\"10%\"><a href=\"editcatalogue.php?cancelrelate=true&cat=$cat&resultpage=$resultpage&search=$search\" target=\"_self\"><img src=\"images/icon_cancel.gif\" border=\"0\" alt=\"".CANCEL."\" title=\"".CANCEL."\"></a></td>";
	  echo "<td align=\"right\" width=\"10%\"><a href=\"salesreport.php?productid=$realproductid&memberid=$ownerid&generate=true&orderby=productid&reporttype=paid\"><img src=\"images/icon_history.gif\" alt=\"".SALESREPORT."\" title=\"".SALESREPORT."\" border=\"0\"></a></td>";
	  if (($twitteruser && $twitterpass && ($advancedmallmode == "1" || $userid == "1")) || $userid == "1") echo "<td align=\"right\" width=\"10%\"><a href=\"\" onClick=\"viewtwitter('$realproductid'); return false;\" target=\"_blank\"><img src=\"images/icon_twitter.gif\" alt=\"".POSTTOTWITTER."\" title=\"".POSTTOTWITTER."\" border=\"0\"></a></td>";
	  if (file_exists("$ashoppath/includes/qrgen/phpqrcode.php") && is_dir("$ashoppath/prodqrimg") && is_writeable("$ashoppath/prodqrimg") && ($advancedmallmode == "1" || $userid == "1")) echo "<td align=\"right\" width=\"10%\"><a href=\"\" onClick=\"viewqrgen('$realproductid'); return false;\" target=\"_blank\"><img src=\"images/icon_qr.gif\" alt=\"".GENERATEQRCODE."\" title=\"".GENERATEQRCODE."\" border=\"0\"></a></td>";
	  echo "<td align=\"right\" width=\"10%\"><a href=\"\" onClick=\"vieworderformlink('item=$realproductid&quantity=1','$orderhtml'); return false;\" target=\"_blank\"><img src=\"images/icon_link.gif\" border=\"0\" alt=\"".DIRECTLINKTOBUY."\" title=\"".DIRECTLINKTOBUY."\"></a></td></tr></table></td></tr>
            <tr><td class=\"text\">$description<br><br></td></tr>
            <tr><td><span class=\"formtitle\">".PRICE.": </span>
            <span class=\"text\">";
	  if (!$qtypricing && !$fpprice) echo $currencysymbols[$ashopcurrency]["pre"].number_format($price,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"];
	  else if ($fpprice) {
		  if ($fpended) echo ENDED." ";
		  if ($fptype == "penny") echo PENNYAUCTION;
		  else if ($fptype == "standard") echo AUCTION;
		  if ($fpended && $fpwinner) {
			  $fpwinnerresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$fpwinner'");
			  $fpwinnercustomerid = @mysqli_result($fpwinnerresult,0,"customerid");
			  $fpwinnername = @mysqli_result($fpwinnerresult,0,"screenname");
			  if ($fpwinnercustomerid) {
				  $fpwinnerresult = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE customerid='$fpwinnercustomerid'");
				  $fpwinnername = @mysqli_result($fpwinnerresult,0,"firstname");
				  if ($fpwinnername) $fpwinnername .= " ".@mysqli_result($fpwinnerresult,0,"lastname");
				  $fpwinnername = "<a href=\"editcustomer.php?customerid=$fpwinnercustomerid\">$fpwinnername</a>";
			  }
			  echo ", ".WONBY.": $fpwinnername";
		  }
	  }
	  else if ($qtypricing) echo QTYBASED;
	  if ($wholesaleprice && $wholesalecatalog && !$qtypricing) echo " (".$currencysymbols[$ashopcurrency]["pre"].$wholesaleprice.$currencysymbols[$ashopcurrency]["post"].")";
	  echo "</span>";
	  if (empty($cost)) $cost = 0.00;
	  if (file_exists("$ashoppath/emerchant/quote.php") && $checkvendor) echo ", <span class=\"formtitle\">".COST.": </span><span class=\"text\">".$currencysymbols[$ashopcurrency]["pre"].number_format($cost,$showdecimals,$decimalchar,$thousandchar).$currencysymbols[$ashopcurrency]["post"]."</span>";
      if ($discounted) echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".DISCOUNTS."</span>";
      if ($shipping == "usps") echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"text\">USPS</span>";
	  else if ($shipping == "ups") echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"text\">UPS</span>";
      else if ($shipping == "storewide") {
		  echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"text\">".STOREWIDE." ";
		  if ($storeshippingmethod == "usps") echo "USPS";
		  else if ($storeshippingmethod == "ups") echo "UPS";
		  else if ($storeshippingmethod == "fedex") echo "FedEx";
		  else if ($storeshippingmethod == "perpound") echo PERPOUND;
		  else if ($storeshippingmethod == "byprice") echo BYPRICE;
		  else if ($storeshippingmethod == "byweight") echo BYWEIGHTRANGE;
		  else echo NONE;
		  echo "</span>";
      } else if ($shipping == "fedex") echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"text\">FedEx</span>";
      else if ($shipping == "wml") echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"text\">Watkins ML</span>";
      else if (strstr($shipping,"zone")) echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"text\">".substr($shipping,4)."</span>";
      else if ($shipping == "quantity") echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span>
            <span class=\"text\">".QUANTITY."</span>";
      else if ($shipping) {
		  echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SHIPPING.": </span><span class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]."$shipping ".$currencysymbols[$ashopcurrency]["post"];
		  if ($intshipping != $shipping) echo "<span class=\"formlabel\">/ ".$currencysymbols[$ashopcurrency]["pre"]."$intshipping ".$currencysymbols[$ashopcurrency]["post"]."</span>";
		  echo "</span>";
	  }
      if ($taxable == 1) echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".SALESTAX."</span>";
	  if ($taxable == 2) echo "<span class=\"formlabel\">, </span><span class=\"formtitle\">".TAXLEVEL2."</span>";
	  if ($userid == "1" && $ownerid > "1") echo "<br><span class=\"formtitle\">".OWNEDBY.": <a href=\"editmember.php?memberid=$ownerid\">$owner</a></span>";
	  echo	"</td></tr><tr bgcolor=\"#DDDDDD\"><td>
	        <table width=\"100%\" cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr>
	        <td width=\"85\"><form action=\"editproduct.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"edit\" value=\"".EDIT."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  if ($userid == "1" || $advancedmallmode) echo "<td align=\"left\" width=\"80\" valign=\"top\" align=\"left\"><form action=\"pagegenerator.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"makehtml\" value=\"".DETAILS."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  echo "<td width=\"85\"><form action=\"editproduct.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"remove\" value=\"".REMOVE."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>
	  <td width=\"85\"><form action=\"editinventory.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"inventory\" value=\"".INVENTORY."\" style=\"width: 85px\"></form></td>
	  <td width=\"85\"><form action=\"editdiscount.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"discount\" value=\"".DISCOUNT."\" style=\"width: 85px\"></form></td>";
	  if ($userid == "1" || $advancedmallmode == "1") echo "<td valign=\"top\" width=\"80\"><form action=\"editshipping.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"shipping\" value=\"".SHIPPING."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  echo "<td></td></tr></table><table cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr>
  	  <td width=\"80\" valign=\"top\"><form action=\"editfiles.php\" method=\"POST\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"editfiles\" value=\"".FILES."\" style=\"width: 85px\"></form></td>";
	  if (file_exists("$ashoppath/includes/aws/aws-config.php")) {
		  echo "<td width=\"80\" valign=\"top\"><form action=\"editvideos.php\" method=\"POST\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"editfiles\" value=\"".VIDEOS."\" style=\"width: 85px\"></form></td>";
	  }
	  echo "
	  <td valign=\"top\" width=\"70\"><form action=\"editparameters.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"parameters\" value=\"".ATTRIBUTES."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  if ($userid == "1") echo "<td valign=\"top\"><form action=\"editfulfilment.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"fulfilment\" value=\"".FULFILMENT."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  if ($userid == "1") echo "<td align=\"left\" width=\"80\" valign=\"top\"><form action=\"editsubscr.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"edit\" value=\"".MEMBERSHIP."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  if ($producttype != "subscription") echo "<td valign=\"top\" width=\"80\"><form action=\"editqtypricing.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"qtypricing\" value=\"".QTYPRICING."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  echo "</tr></table><table cellpadding=\"2\" cellspacing=\"0\" border=\"0\"><tr><td valign=\"top\"><form action=\"sendupdate.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  if ($files) echo "<td valign=\"top\"><form action=\"sendgift.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"gift\" value=\"".GIFT."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$realproductid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></td>";
	  echo "<td valign=\"top\"><form action=\"moveproduct.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"submit\" name=\"move\" value=\"".MOVECOPY."\" style=\"width: 85px\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"></form></td>";
  	  if ($previousorderno && !$search) echo "<td valign=\"top\"><form action=\"editcatalogue.php\" method=\"POST\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"thisordno\" value=\"$ordernumber\"><input type=\"hidden\" name=\"topordno\" value=\"$toporderno\"><input type=\"hidden\" name=\"thisprodid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"text\" name=\"uptimes\" size=\"2\" value=\"1\" style=\"text-align: center;\"> <input type=\"submit\" name=\"movetop\" value=\"".UP."\" class=\"smallbutton\"></form></td>";
	  /*if ($toporderno != $ordernumber) echo "<td valign=\"top\"><form action=\"editcatalogue.php\" method=\"POST\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"thisordno\" value=\"$ordernumber\"><input type=\"hidden\" name=\"topordno\" value=\"$toporderno\"><input type=\"hidden\" name=\"thisprodid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"submit\" name=\"movetop\" value=\"".TOP."\" class=\"smallbutton\"></form></td>";*/
	  echo "</tr></table></td></tr></table></td></tr></table>";
	  $previousorderno = $ordernumber;
	  $previousprodid = $productid;
    }
	if ($pid) {
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\"><tr><td class=\"formlabel\" colspan=\"2\"><b>".THISPRODUCTCANBEFOUNDIN.":</b></td></tr>";
		$checkpidcategories = @mysqli_query($db, "SELECT productcategory.categoryid FROM product,productcategory WHERE (product.productid='$realproductid' OR product.copyof='$realproductid') AND product.productid=productcategory.productid");
		while ($pidcategoriesrow = @mysqli_fetch_array($checkpidcategories)) {
			$pidcategoryid = $pidcategoriesrow["categoryid"];
			$pidcategoryresult = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$pidcategoryid'");
			$pidcategoryrow = @mysqli_fetch_array($pidcategoryresult);
			$pidcategoryname = $pidcategoryrow["name"];
			$pidcategoryparent = $pidcategoryrow["parentcategoryid"];
			$pidcategorygrandparent = $pidcategoryrow["grandparentcategoryid"];
			echo "<td width=\"100\">&nbsp;</td><td class=\"formlabel\">";
			if ($pidcategorygrandparent != $pidcategoryid) {
				$pidcatgrandparentresult = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$pidcategorygrandparent'");
				$pidcatgrandparentrow = @mysqli_fetch_array($pidcatgrandparentresult);
				$pidcatgrandparentname = $pidcatgrandparentrow["name"];
				echo "$pidcatgrandparentname >> ";
			}
			if ($pidcategoryparent != $pidcategoryid) {
				$pidcatparentresult = @mysqli_query($db, "SELECT * FROM category WHERE categoryid='$pidcategoryparent'");
				$pidcatparentrow = @mysqli_fetch_array($pidcatparentresult);
				$pidcatparentname = $pidcatparentrow["name"];
				echo "$pidcatparentname >> ";
			}
			echo "<a href=\"editcatalogue.php?cat=$pidcategoryid\">$pidcategoryname</a></td></tr>";
		}
		echo"
		</table>";
	}
	if ($numberofrows > 5) {
		echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" id=\"bottomtable\">
		<script language=\"javascript\">
			var NS = (navigator.appName==\"Netscape\")?true:false;
			iWidth = (NS)?window.innerWidth:document.body.clientWidth;
			if (iWidth > 1300) document.getElementById(\"bottomtable\").width = 800;
		</script>
		<tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
		if ($numberofpages > 1) {
			echo "<b>".PAGE.": </b>";
			if ($resultpage > 1) {
				$previouspage = $resultpage-1;
				echo "<<<a href=\"editcatalogue.php?cat=$cat&search=$search&resultpage=$previouspage\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
			}
			$page = 1;
			for ($i = $startpage; $i <= $numberofpages; $i++) {
				if ($page > 20) break;
				if ($i != $resultpage) echo "<a href=\"editcatalogue.php?cat=$cat&search=$search&resultpage=$i\">";
				echo "$i";
				if ($i != $resultpage) echo "</a>";
				echo "&nbsp;&nbsp;";
				$page++;
			}
			if ($resultpage < $numberofpages) {
				$nextpage = $resultpage+1;
				echo "<a href=\"editcatalogue.php?cat=$cat&search=$search&resultpage=$nextpage\"><b>".NEXTPAGE."</b></a>>>";
			}
		}
		echo " <form action=\"editcatalogue.php\" method=\"POST\" name=\"displayform\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"cat\" value=\"$cat\">".DISPLAY.": <select name=\"admindisplayitems\" onChange=\"displayform.submit();\"><option value=\"$numberofrows\">".SELECT."</option>
		<option value=\"5\"";
		if ($c_admindisplayitems == "5") echo " selected";
		echo ">5</option><option value=\"10\"";
		if ($c_admindisplayitems == "10") echo " selected";
		echo ">10</option><option value=\"20\"";
		if ($c_admindisplayitems == "20") echo " selected";
		echo ">20</option><option value=\"40\"";
		if ($c_admindisplayitems == "40") echo " selected";
		echo ">40</option><option value=\"$numberofrows\"";
		if ($c_admindisplayitems == "$numberofrows") echo " selected";
		echo ">".ALL."</option></select> ".ITEMS."</form></td></tr></table>";
	}

@mysqli_close($db);
echo "</td>
</tr>
</table><br><br>
$footer";
?>