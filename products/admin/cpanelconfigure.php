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

include "checklicense.inc.php";
include "checklogin.inc.php";

if ($cancel) {
	header("Location: settings.php?cancel=true");
	exit;
}

if ($userid != "1") {
	header("Location: editmember.php");
	exit;
}

if ($digitalmall != "ON") {
	header ("Location: index.php");
	exit;
}

include "template.inc.php";
include "ashopconstants.inc.php";

// Get language module...
include "language/$adminlang/configure.inc.php";

// Get context help for this page...
$contexthelppage = "shopparameters";
include "help.inc.php";

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$changeconfig) {
	echo "$header
	<div class=\"heading\">".CPANELOPTIONS."</div><table align=\"center\" cellpadding=\"10\"><tr><td>
	<form action=\"cpanelconfigure.php?changeconfig=1\" method=\"post\" name=\"configurationform\">
	<table width=\"600\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELAPIURL.":</td><td  class=\"formlabel\"><input name=\"ncpanelapiurl\" size=\"42\" value=\"$cpanelapiurl\" /></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELAPIUSER.":</td><td  class=\"formlabel\"><input name=\"ncpanelapiuser\" size=\"20\" value=\"$cpanelapiuser\" /></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELAPIPASS.":</td><td  class=\"formlabel\"><input name=\"ncpanelapipass\" size=\"20\" value=\"\" type=\"password\" /></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELAPIPORT.":</td><td  class=\"formlabel\"><input name=\"ncpanelapiport\" size=\"5\" value=\"$cpanelapiport\" /><span class=\"sm\"> [ ".USUALLY.": 2083 ]</span></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELDOMAIN.":</td><td  class=\"formlabel\"><input name=\"ncpaneldomain\" size=\"20\" value=\"$cpaneldomain\" /></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELDBHOST.":</td><td  class=\"formlabel\"><input name=\"ncpaneldbhost\" size=\"20\" value=\"$cpaneldbhost\" /><span class=\"sm\"> [ ".USUALLY.": localhost ]</span></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CPANELZIPFILE.":</td><td  class=\"formlabel\"><input name=\"ncpanelzip\" size=\"20\" value=\"$cpanelzip\" /></td></tr>
	<tr bgcolor=\"#F0F0F0\"><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cancel\" value=\"\"><input type=\"button\" value=\"".CANCEL."\" onClick=\"document.configurationform.cancel.value='true';document.configurationform.submit();\"> <input type=\"submit\" value=\"".SUBMIT."\"></td></tr></table></form></table>$footer";
} else {
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpanelapiuser' WHERE prefname='cpanelapiuser'");
	if (!empty($ncpanelapipass) || empty($ncpanelapiurl)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpanelapipass' WHERE prefname='cpanelapipass'");
	$ncpanelapiurl = str_ireplace("http://","",$ncpanelapiurl);
	$ncpanelapiurl = str_ireplace("https://","",$ncpanelapiurl);
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpanelapiurl' WHERE prefname='cpanelapiurl'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpanelapiport' WHERE prefname='cpanelapiport'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpaneldomain' WHERE prefname='cpaneldomain'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpaneldbhost' WHERE prefname='cpaneldbhost'");
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncpanelzip' WHERE prefname='cpanelzip'");
	@mysqli_close($db);
	header("Location: settings.php$errorstring");
}
?>