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
// Get context help for this page...
$contexthelppage = "editparameters";
include "help.inc.php";

// Connect to database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Get product information...
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");

// Print shipping option form...
if (!$update && !$delete) {
  echo "$header
      <script language=\"JavaScript\">
	  <!--
	    function vieworderformlink(query) 
		{
			w = window.open(\"\",\"_blank\",\"toolbar=no, location=no, scrollbars=no, width=600, height=100\");
			w.document.write('<html><head><title>".DIRECTLINK."</title></head><body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#000000\"><center><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".DIRECTLINKURL.":<br><textarea name=\"description\" cols=\"65\" rows=\"2\">$ashopurl/buy.php?'+query+'&redirect=basket.php</textarea><br><font size=\"2\"><a href=\"javascript:this.close()\">".CLOSEWINDOW."</a></font></font><br></center></body></html>');
			return false;
	    }
		-->
	    </script>
        <div class=\"heading\">".PRODUCTATTRIBUTES." <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><span class=\"subheader\"><a href=\"editcatalogue.php?pid=$productid&cat=$cat\">$productname</a></span><br><br>
        <form action=\"editalternatives.php\" method=\"post\" name=\"newparamform\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"productid\" value=\"$productid\">
        <table width=\"540\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"fofofo\">
        <tr><td colspan=\"4\" class=\"formtitle\">".CREATEATTRIBUTE."</td></tr><tr><td class=\"formlabel\">".CAPTION.":</td><td class=\"formlabel\"><input type=\"text\" name=\"caption\" size=\"20\"></td>
		<td class=\"formlabel\">".NUMBEROFALTERNATIVES.":</td><td><input type=\"text\" name=\"alternatives\" size=\"3\" value=\"0\"></td>
		<td class=\"formlabel\"><input type=\"submit\" name=\"add\" value=\"".ADDATTRIBUTE."\"></td></tr><tr><td></td><td></td><td colspan=\"3\" class=\"sm\">".ENTERZEROFORCUSTOMERINPUT." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></td></tr></table></form><br>";

    $sql = "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid";
	$result = @mysqli_query($db, $sql);
	if (@mysqli_num_rows($result)) {
		echo "<table width=\"540\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\"><tr><td bgcolor=\"#000000\"><img src=\"images/invisible.gif\" height=\"2\" width=\"2\"></td></tr></table><br><div class=\"formtitle\">".EXISTINGATTRIBUTES.":</div><br>";
		$currentbuybuttons = 0;
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$caption = @mysqli_result($result, $i, "caption");
			$inputrows = @mysqli_result($result, $i, "inputrows");
			if (!$inputrows) $inputrows = 1;
			$parameterid = @mysqli_result($result, $i, "parameterid");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid=$parameterid");
			$parameternumber = $i + 1;
			echo "<table width=\"540\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\"><tr><td width=\"75%\"><form action=\"editparameters.php\" method=\"post\" style=\"margin-bottom: 0px;\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\"><input type=\"hidden\" name=\"paramid\" value=\"$parameterid\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><tr><td class=\"formlabel\">$parameternumber:</td><td width=\"70%\" class=\"formlabel\" nowrap><input type=\"text\" name=\"caption\" size=\"20\" value=\"$caption\">";
			if (!@mysqli_num_rows($subresult)) echo "<a href=\"\" onClick=\"vieworderformlink('attribute{$parameterid}=value&item=$productid&quantity=1'); return false;\" target=\"_blank\"><img src=\"images/icon_link.gif\" border=\"0\"></a> &nbsp;<input type=\"text\" size=\"2\" name=\"inputrows\" value=\"$inputrows\"> ".ROWS;
			echo "</td>
			<td class=\"formtitle\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"></td><td class=\"formtitle\"><input type=\"submit\" name=\"delete\" value=\"".REMOVE."\"></td></tr></table></form></td>";
			if (@mysqli_num_rows($subresult)) echo "<td><form action=\"editalternatives.php\" method=\"post\" style=\"margin-bottom: 0px;\"><input type=\"hidden\" name=\"paramid\" value=\"$parameterid\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"submit\" name=\"edit\" value=\"".ALTERNATIVES."\"></form></td>";
			else echo "<td>&nbsp;</td>";
			echo "</tr></table><table width=\"540\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\"><tr><td bgcolor=\"#FFFFFF\"><img src=\"images/invisible.gif\" height=\"4\" width=\"2\"></td></tr></table>";
		}

		echo "<table width=\"540\" cellpadding=\"0\" border=\"0\" cellspacing=\"0\"><tr><td bgcolor=\"#000000\"><img src=\"images/invisible.gif\" height=\"2\" width=\"2\"></td></tr><tr><td align=\"right\"><br><input type=\"button\" value=\"".FINISH."\" onClick=\"document.location.href='editcatalogue.php?cat=$cat&pid=$pid&resultpage=$resultpage&search=$search'\"></td></tr></table>";
	}
	echo "</td></tr></table>$footer";


// Store data in database...
} else if ($update) {
	$sql = "UPDATE parameters SET caption='$caption', inputrows='$inputrows' WHERE parameterid=$paramid";
	$result = @mysqli_query($db, $sql);
	header ("Location: editcatalogue.php?cat=$cat&pid=$pid&search=$search&resultpage=$resultpage");
} else if ($delete) {
	$sql = "DELETE FROM parameters WHERE parameterid=$paramid";
	$result = @mysqli_query($db, $sql);
	$sql = "DELETE FROM parametervalues WHERE parameterid=$paramid";
	$result = @mysqli_query($db, $sql);
	header ("Location: editparameters.php?cat=$cat&pid=$pid&search=$search&productid=$productid&resultpage=$resultpage");
}
?>