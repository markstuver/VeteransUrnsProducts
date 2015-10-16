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
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";
include "keycodes.inc.php";

// Validate variables...
if (!is_numeric($resultpage)) unset($resultpage);
if (!is_numeric($kadmindisplayitems)) unset($kadmindisplayitems);
else {
	$c_kadmindisplayitems = $kadmindisplayitems;
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("c_kadmindisplayitems","$kadmindisplayitems");
}
if (!is_numeric($c_kadmindisplayitems)) unset($c_kadmindisplayitems);

// Get information about the product from the database...
if ($clear) $productid = $clear;
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");

// Handle removal of all key codes...
if ($clear) {
	if ($yes) {
       $sql="DELETE FROM unlockkeys WHERE productid='$clear'";
       $result = @mysqli_query($db, $sql);
	   header("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage&msg=keycodescleared");
	   exit;
    }
	else if ($no) header("Location: listkeycodes.php?cat=$cat&productid=$clear&resultpage=$resultpage");
	else echo "$header
        <div class=\"heading\">".REMOVEALLKEYCODES.": $productname</div>
        <p class=\"warning\">".AREYOUSURECLEARALL."</p><table cellpadding=\"10\" align=\"center\"><tr><td>
		<form action=\"listkeycodes.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"clear\" value=\"$clear\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"keyid\" value=\"$keyid\">
		<input type=\"hidden\" name=\"remove\" value=\"true\"></form></td></tr></table>
        $footer";
	exit;
}

// Handle removal of a single key code...
if ($remove && $keyid) {
	if ($yes) {
       $sql="DELETE FROM unlockkeys WHERE keyid=$keyid";
       $result = @mysqli_query($db, $sql);
	   header("Location: listkeycodes.php?cat=$cat&productid=$productid&msg=deleted&resultpage=$resultpage");
	   exit;
    }
	else if ($no) header("Location: listkeycodes.php?cat=$cat&productid=$productid&resultpage=$resultpage");
	else echo "$header    
        <div class=\"heading\">".REMOVEAKEYCODE.": $productname</div><table cellpadding=\"10\" align=\"center\"><tr><td>
        <p class=\"warning\">".AREYOUSUREDELETEKEYCODE."</p>
		<form action=\"listkeycodes.php\" method=\"post\">
		<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" align=\"center\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"keyid\" value=\"$keyid\">
		<input type=\"hidden\" name=\"remove\" value=\"true\"></form></td></tr></table>
        $footer";
	exit;
} 

// Show list of key codes...
if ($productid) {
	echo "$header
        <div class=\"heading\">".UNLOCKKEYCODESFOR."</div><table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>";
	if ($msg == "deleted") echo "<p align=\"center\" class=\"confirm\">".KEYCODESDELETED."</p>";
	echo "</td></tr></table><form action=\"listkeycodes.php\" method=\"post\" name=\"keycodesform\" style=\"margin-bottom: 0px;\">
		<table width=\"80%\" border=\"0\" cellspacing=\"1\" cellpadding=\"2\" align=\"center\" bgcolor=\"#D0D0D0\">
		<tr class=\"reporthead\"><td align=\"center\">".KEYCODE."</td><td align=\"center\">".ASSIGNEDTO."</td><td width=\"60\">&nbsp;</td></tr>";

	  $sql="SELECT * FROM unlockkeys WHERE productid='$productid' ORDER BY keyid";
	  $result = @mysqli_query($db, $sql);

	  $numberofrows = intval(@mysqli_num_rows($result));
	  if (!$kadmindisplayitems) {
		  if ($c_kadmindisplayitems) $kadmindisplayitems = $c_kadmindisplayitems;
		  else $kadmindisplayitems = 10;
	  }
	  $numberofpages = ceil($numberofrows/$kadmindisplayitems);
	  if ($resultpage > 1) $startrow = (intval($resultpage)-1) * $kadmindisplayitems;
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
	  $stoprow = $startrow + $kadmindisplayitems;
	  @mysqli_data_seek($result, $startrow);
	  $thisrow = $startrow;
	  while (($row = @mysqli_fetch_array($result)) && ($thisrow < $stoprow)) {
		  $thiskeyid = $row["keyid"];
		  $thiskeytext = trim($row["keytext"]);
		  if (!empty($keycodeencryptionkey)) {
			  $thiskeytext = ashop_decrypt($thiskeytext, $keycodeencryptionkey);
			  $thiskeytext = trim($thiskeytext);
			  $thiskeytextlength = strlen($thiskeytext);
			  $showchars = floor($thiskeytextlength/8);
			  $hidechars = $thiskeytextlength-$showchars-$showchars;
			  $hidedots = "";
			  for ($dots = 0; $dots < $hidechars; $dots++) $hidedots .= "-";
			  $thiskeytext = substr($thiskeytext,0,$showchars).$hidedots.substr($thiskeytext,-$showchars);
		  }
		  $thisorderid = $row["orderid"];
		  $thisrow++;
		  if (substr($thisorderid, 0, 2) == "ws") {
			  $wholesaleorder = TRUE;
			  $thisorderid = substr($thisorderid, 2);
		  } else $wholesaleorder = FALSE;
		  echo "<tr class=\"reportline\"><td align=\"left\">$thiskeytext</td>";
		  if ($thisorderid) {
			  if (!$wholesaleorder) $sql2="SELECT customer.* FROM customer, orders WHERE orders.orderid=$thisorderid AND orders.customerid = customer.customerid";
			  else $sql2="SELECT customer.* FROM customer, orders WHERE orders.orderid=$thisorderid AND orders.customerid = customer.customerid";
			  $result2 = @mysqli_query($db, $sql2);
			  $firstname = @mysqli_result($result2, 0, "firstname");
			  $lastname = @mysqli_result($result2, 0, "lastname");
			  $email = @mysqli_result($result2, 0, "email");
			  if (!$wholesaleorder) $customerid = @mysqli_result($result2, 0, "customerid");
			  else $customerid = @mysqli_result($result2, 0, "userid");
			  echo "<td align=\"left\">";
			  if (!$wholesaleorder) echo "<a href=\"editcustomer.php?customerid=$customerid\">";
			  else echo "<a href=\"edituser.php?customerid=$customerid\">";
			  echo "$firstname $lastname</a> (".ORDERID.": <a href=\"getreceipt.php?orderid=$thisorderid\">$thisorderid</a>)</td><td align=\"center\">";
			  if (!$wholesaleorder) echo "<a href=\"editcustomer.php?customerid=$customerid\">";
			  else echo "<a href=\"edituser.php?customerid=$customerid\">";
			  echo "<img src=\"images/icon_profile.gif\" alt=\"".PROFILEFOR." $customerid\" title=\"".PROFILEFOR." $customerid\" border=\"0\"></a>&nbsp;";
			  if (!$wholesaleorder) echo "<a href=\"salesreport.php?customerid=$customerid&generate=true\">";
			  else echo "<a href=\"salesreport.php?customerid=$customerid&generate=true&reporttype=wholesale\">";
			  echo "<img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $customerid\" title=\"".SALESHISTORYFOR." $customerid\" border=\"0\"></a>&nbsp;<a href=\"listkeycodes.php?remove=true&keyid=$thiskeyid&productid=$productid&cat=$cat&resultpage=$resultpage\"><img src=\"images/icon_trash.gif\" border=\"0\"></a></td>";
		  } else echo "<td>".UNASSIGNED."</td><td align=\"center\"><img src=\"images/invisible.gif\" width=\"15\">&nbsp;<img src=\"images/invisible.gif\" width=\"15\">&nbsp;<a href=\"listkeycodes.php?remove=true&keyid=$thiskeyid&productid=$productid&cat=$cat&resultpage=$resultpage\"><img src=\"images/icon_trash.gif\" border=\"0\"></a></td>";
		  echo "</tr>";
	  }
  
	  echo "</table>";
	  if ($numberofrows > 5) {
		  echo "<table width=\"100%\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">";
		  if ($numberofpages > 1) {
			  echo "<b>".PAGE.": </b>";
			  if ($resultpage > 1) {
				  if ($resultpage > 10) echo "<a href=\"listkeycodes.php?resultpage=1&kadmindisplayitems=$kadmindisplayitems&productid=$productid&cat=$cat\"><b>".FIRSTPAGE."</b></a> ";
				  $previouspage = $resultpage-1;
				  echo "<<<a href=\"listkeycodes.php?resultpage=$previouspage&kadmindisplayitems=$kadmindisplayitems&productid=$productid&cat=$cat\"><b>".PREVIOUS."</b></a>&nbsp;&nbsp;";
			  }
			  $page = 1;
			  for ($i = $startpage; $i <= $numberofpages; $i++) {
				  if ($page > 20) break;
				  if ($i != $resultpage) echo "<a href=\"listkeycodes.php?resultpage=$i&kadmindisplayitems=$kadmindisplayitems&productid=$productid&cat=$cat\">";
				  else echo "<span style=\"font-size: 18px;\">";
				  echo "$i";
				  if ($i != $resultpage) echo "</a>";
				  else echo "</span>";
				  echo "&nbsp;&nbsp;";
				  $page++;
			  }
			  if ($resultpage < $numberofpages) {
				  $nextpage = $resultpage+1;
				  echo "<a href=\"listkeycodes.php?resultpage=$nextpage&kadmindisplayitems=$kadmindisplayitems&productid=$productid&cat=$cat\"><b>".NEXTPAGE."</b></a>>>";
			  }
			  if ($resultpage < ($numberofpages - 10)) echo " <a href=\"listkeycodes.php?resultpage=$numberofpages&kadmindisplayitems=$kadmindisplayitems&productid=$productid&cat=$cat\"><b>".LASTPAGE."</b></a> &nbsp;&nbsp;";
		  }
		  echo " ".DISPLAY.": <select name=\"kadmindisplayitems\" onChange=\"document.location.href='listkeycodes.php?resultpage=$resultpage&productid=$productid&cat=$cat&kadmindisplayitems='+keycodesform.kadmindisplayitems.value;\"><option value=\"$numberofrows\">".SELECT."</option><option value=\"5\">5</option><option value=\"10\">10</option><option value=\"20\">20</option><option value=\"40\">40</option><option value=\"$numberofrows\">".ALL."</option></select> ".CODES."</td></tr></table></form>
		  ";
	  }
	  
	  echo "<br><center><form method=\"post\" action=\"listkeycodes.php\"><input type=\"submit\" class=\"widebutton\" value=\"".CLEARALLCODES."\"><input type=\"hidden\" name=\"clear\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"></form></center></td></tr></table><br>$footer";
  }
   else header ("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage");
?>