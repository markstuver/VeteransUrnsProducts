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
// Get context help for this page...
		$contexthelppage = "editproduct";
		include "help.inc.php";

// Get information about the product from the database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
$sql="SELECT * FROM product WHERE productid = $productid";
$result = @mysqli_query($db, $sql);
$productname = @mysqli_result($result, 0, "name");
$productname = str_replace("\"", "&quot;", $productname);


// Handle editing of the product...
if ($flags && $productid) {

  // Check product flags...
  $flagnumber = 0;
  $flagvalues = "";
  $flagresult = @mysqli_query($db, "SELECT * FROM productflags ORDER BY flagid");
  while ($row = @mysqli_fetch_array($flagresult)) {
	  if ($flagnumber == 0) $flagvalues .= "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image16','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip16');\" onmouseout=\"return nd();\"></a> Flags:</td><td class=\"formlabel\">";
	  else $flagvalues .= "<td>&nbsp;</td><td class=\"formlabel\">";
	  $flagvalues .= "{$row["name"]} <input type=\"checkbox\" name=\"flag{$row["flagid"]}\"";
	  $flagresult2 = @mysqli_query($db, "SELECT * FROM flagvalues WHERE flagid='{$row["flagid"]}' AND productid='$productid'");
	  if (@mysqli_num_rows($flagresult2)) $flagvalues .= " checked";
	  $flagvalues .= "></td></tr>";
	  $flagnumber++;
  }

  // Show edit form...
  if (!$edited) {
	  echo "$header
        <table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td class=\"heading1\">Edit Catalogue</td></tr></table>
        <table cellpadding=\"3\" align=\"center\"><tr><td align=\"center\"><p><span class=\"heading\">Product Flags</span><br><span class=\"formtitle\">$productname</span></p>
        <form action=\"productflags.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">";
		echo "<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		$flagvalues
		<tr><td>&nbsp;</td><input type=\"hidden\" name=\"flags\" value=\"True\"><input type=\"hidden\" name=\"edited\" value=\"True\"><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><td align=\"right\"><input type=\"submit\" value=\"Submit\"></td></tr></table></form>$footer";
  }
  else {
	// Handle product flags...
	@mysqli_data_seek($flagresult, 0);
	while ($row = @mysqli_fetch_array($flagresult)) {
		$thisflagactivated = 0;
		eval ("if (\$flag{$row["flagid"]} == \"on\") \$thisflagactivated = \"on\";");
		if ($thisflagactivated) @mysqli_query($db, "INSERT INTO flagvalues (flagid, productid) VALUES ('{$row["flagid"]}', '$productid')");
		else @mysqli_query($db, "DELETE FROM flagvalues WHERE flagid='{$row["flagid"]}' AND productid='$productid'");
	}
	header("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage");
  }
}
?>