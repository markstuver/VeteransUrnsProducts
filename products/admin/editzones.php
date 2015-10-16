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
include "language/$adminlang/configure.inc.php";
// Get context help for this page...
$contexthelppage = "editzones";
include "help.inc.php";

// Open database connection...
$db = @mysqli_connect($databaseserver, $databaseuser, $databasepasswd, $databasename);

define( "FORM_ZONES", 21 );
define( "EDIT_ZONES", 31 );
define( "ADD_ZONE_TABLES", 51 );
define( "EDIT_ZONE_TABLES", 52 );
define( "DELETE_ZONE_TABLES", 53 );
define( "CONFIRM_DELETE_ZONE_TABLES", 54 );

// Delete a zone table...
if ($remove && $zname) {
	if ($yes) {
		$sql = "delete from zipzones where zonename like '$zname' ";
		@mysqli_query($db, $sql);
		$sql = "update products set shipping = NULL where '$zname' like shipping+'%' ";
		@mysqli_query($db, $sql);
		header("Location: editzones.php");
		exit;
    }
	elseif ($no) header("Location: editzones.php");
	else echo "$header
        <table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><div class=\"heading\">".REMOVEZIPZONETABLE."</div>
        <p>".AREYOUSUREREMOVEZIPZONETABLE.": $zname?</p>
		<form action=\"editzones.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"submit\" name=\"no\" value=\"".NO."\"></td>
		</tr></table><input type=\"hidden\" name=\"zname\" value=\"$zname\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form></td></tr></table>
        $footer";
		exit;
} 

// Edit zip zones...
if ($edit == "true") {
	if ($edited == "true" && !empty($zip)) {
		if ( $old_zip != "" ) {
			$sql = "update zipzones set zip = '$zip', zone=$zone where zonename like '$zname' AND zip = '$old_zip'";
			@mysqli_query($db,  $sql );
		} else {
			$sql = "insert into zipzones ( zip, zone, zonename ) values ('$zip', $zone, '$zname')"; 
			@mysqli_query($db,  $sql );
		}
	}

	echo "$header
	<div class=\"heading\">".ZIPZONETABLES." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\" class=\"formlabel\">";

	if (!$editzip) {
		$sql = "select * from zipzones WHERE zonename = '$zname' order by zip";
		$result = @mysqli_query($db,  $sql );

		echo EDITZIPZONETABLE.": <b>$zname</b><p><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> <a href=\"editzones.php?zname=$zname&editzip=true&edit=true\" class=\"nav2\"> ".ADDNEWZIP."</a></p><table align=\"center\" border=\"0\" cellpadding=\"2\" cellspacing=\"5\" bgcolor=\"#D0D0D0\"><tr bgcolor=\"#808080\"><td class=\"formtitlewh\" align=\"left\">".ZIPFIRST3DIGITS."</td><td class=\"formtitlewh\" align=\"left\">".ZONE."</td></tr>";

		while ( $row = @mysqli_fetch_assoc( $result ) ) {
			echo "<tr><td align=\"left\"><a href=\"editzones.php?zname=$zname&editzip=true&edit=true&zip=" . $row["zip"] . "\">" . $row["zip"] . "</a></td><td align=\"left\">".$row["zone"]."</td></tr>";
		}

		echo "</table>";

	} else {
		$old_zip  = "";
		$old_zone = "";
		if ( $zip != "" ) {
			$sql = "select * from zipzones where zip = '" . $zip . "'";
			$result = @mysqli_query($db,  $sql );
			$old_zone = @mysqli_result( $result, 0, "zone"); 
			$old_zip  = @mysqli_result( $result, 0, "zip"); 
		}
		echo "<form action=\"editzones.php\" method=\"post\"><table align=\"center\"><tr><td align=\"right\" class=\"formlabel\">".ZIPFIRST3DIGITS.":</td><td align=\"left\"><input type=\"hidden\" name=\"zname\" value=\"$zname\"><input type=\"hidden\" name=\"edit\" value=\"true\"><input type=\"hidden\" name=\"edited\" value=\"true\"><input type=\"text\" name=\"zip\" value=\"$old_zip\"></td></tr><tr><td align=\"right\" class=\"formlabel\">".ZONE.":</td><td align=\"left\"><select name=\"zone\">";
		
		for ( $i = 2; $i < 27; $i++ ) {
			echo "<option value='" . $i . "' " . ( $old_zone == $i ? "selected" : "" ) . ">   " . $i . "   </option>";
		}
		
		echo "</select></td></tr><tr><td align=\"center\"></td><td align=\"left\"><input type=\"submit\" value=\"".ADDZIPZONE."\"> <input type=\"button\" value=\"".CANCEL."\" onClick=\"javascript: history.back();\"></td></tr></table><input type=\"hidden\" name=\"old_zip\" value=\"$old_zip\"></form>";
	}
} 

// Manage zip zone tables...
else {
	echo "$header
<div class=\"heading\">".ZIPZONETABLES." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> <a href=\"$help1\" target=\"_blank\"><img src=\"images/icon_helpsm.gif\" width=\"15\" height=\"15\" border=\"0\"></a></div><table cellpadding=\"10\" align=\"center\" width=\"100%\"><tr><td align=\"center\" class=\"formlabel\"><form action=\"editzones.php?edit=true\" method = \"POST\">".ADDNEWZIPZONETABLE.": <input type=\"text\" name=\"zname\" value=\"\"> <input type=\"submit\" value = \"".SUBMIT."\"></form><br>";

	$sql = 'SELECT DISTINCT zonename FROM zipzones ';
	$result = @mysqli_query($db,  $sql );
	$numtables = @mysqli_num_rows($result);

	if ($numtables > 0) {
		echo "<table cellpadding=\"2\" cellspacing=\"5\" align=\"center\" bgcolor=\"#D0D0D0\" width=\"400\"><tr bgcolor=\"#808080\"><td align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" color=\"#FFFFFF\" size=\"2\"><b>".ZONETABLENAME."</b></font></td><td></td></tr>";

		for ($i = 0; $i < $numtables; $i++) {
			$zn = @mysqli_result($result, $i, "zonename");
			echo "<tr><td align=\"left\">$zn</td><td width=\"80\" align=\"left\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">[<a href=\"editzones.php?zname=$zn&edit=true\">".EDIT." </a>][<a href=\"editzones.php?zname=$zn&remove=true\">".THEWORDDELETE."</a>]</font></td></tr>";
		}
		echo "</table>";
	}
}

echo "</td></tr></table>$footer";

// Close database connection...
@mysqli_close($db);
?>