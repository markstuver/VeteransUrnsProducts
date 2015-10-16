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
include "language/$adminlang/partyplanner.inc.php";

if ($userid != 1) {
	header("Location: index.php");
	exit;
}

if (empty($partyid) || !is_numeric($partyid)) {
	header("Location: index.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if ($remove && $partyid) {
	if ($yes) {
       $sql="DELETE FROM party WHERE partyid='$partyid'";
       $result = @mysqli_query($db, $sql);
	   if (!empty($affiliateid)) header("Location: viewparties.php?affiliateid=$affiliateid");
	   else if (!empty($customerid)) header("Location: viewparties.php?customerid=$customerid");
	   else header("Location: index.php");
	   exit;
    } elseif ($no) {
	   if (!empty($affiliateid)) header("Location: viewparties.php?affiliateid=$affiliateid");
	   else if (!empty($customerid)) header("Location: viewparties.php?customerid=$customerid");
	   else header("Location: index.php");
	   exit;
	} else {
		$sql="SELECT date FROM party WHERE partyid='$partyid'";
		$result = @mysqli_query($db, $sql);
		$partydate = @mysqli_result($result,0,"date");
		echo "$header
<div class=\"heading\">".REMOVEAPARTY."</div><center>
        <p>".AREYOUSUREREMOVE." $partyid, ".PARTYDATED." $partydate?</font></p>
		<form action=\"editparty.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"partyid\" value=\"$partyid\"><input type=\"hidden\" name=\"affiliateid\" value=\"$affiliateid\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\">
		<input type=\"hidden\" name=\"remove\" value=\"True\">
		</form></center>$footer";
		exit;
	}
}

// Store updated data...
if ($update) {

	// Generate date string...
	$date = "";
	if (isset($year) && is_numeric($year) && isset($month) && is_numeric($month) && isset($day) && is_numeric($day) && isset($hour) && is_numeric($hour) && isset($minute) && is_numeric($minute) && !empty($ampm) && ($ampm == "AM" || $ampm == "PM")) {
		$date = "$year-$month-$day $hour:$minute $ampm";
	}

	$sql="UPDATE party SET description='$description', location='$location', date='$date', customerid='$partycustomer', affiliateid='$partyaffiliate' WHERE partyid='$partyid'";
    $result = @mysqli_query($db, "$sql");
	if (!empty($affiliateid)) header("Location: viewparties.php?affiliateid=$affiliateid");
	else if (!empty($customerid)) header("Location: viewparties.php?customerid=$customerid");
	else header("Location: index.php");
	exit;
}

// Get party information from database...
$sql="SELECT * FROM party WHERE partyid='$partyid'";
$result = @mysqli_query($db, "$sql");
$description = @mysqli_result($result, 0, "description");
$location = @mysqli_result($result, 0, "location");
$partyaffiliate = @mysqli_result($result, 0, "affiliateid");
$partycustomer = @mysqli_result($result, 0, "customerid");
$partydate = @mysqli_result($result, 0, "date");
$partydatearray = explode(" ",$partydate);
$partydate = $partydatearray[0];
$partytime = $partydatearray[1];
$partyampm = $partydatearray[2];
$partydatearray = explode("-",$partydate);
$partyyear = $partydatearray[0];
$partymonth = $partydatearray[1];
$partyday = $partydatearray[2];
$partytimearray = explode(":",$partytime);
$partyhour = $partytimearray[0];
$partyminute = $partytimearray[1];

$result = @mysqli_query($db, "SELECT invitationid FROM partyinvitations WHERE partyid='$partyid'");
$invitations = @mysqli_num_rows($result);
$result = @mysqli_query($db, "SELECT invitationid FROM partyinvitations WHERE partyid='$partyid' AND response='yes'");
$invitationsaccepted = @mysqli_num_rows($result);

// Get info on hosting customer...
$result = @mysqli_query($db, "SELECT firstname, lastname FROM customer WHERE customerid='$partycustomer'");
$customerfirstname = @mysqli_result($result, 0, "firstname");
$customerlastname = @mysqli_result($result, 0, "lastname");

// Get info on referring affiliate...
$result = @mysqli_query($db, "SELECT firstname, lastname FROM affiliate WHERE affiliateid='$partyaffiliate'");
$sponsorfirstname = @mysqli_result($result, 0, "firstname");
$sponsorlastname = @mysqli_result($result, 0, "lastname");

// Close database...
@mysqli_close($db);

$thisyear = date("Y",time());
$tenyearsfromnow = $thisyear+10;

// Show party page in browser...
	if (strpos($header, "title") != 0) {
	    $newheader = substr($header,1,strpos($header, "title")+5);
	    $newheader .= PARTYDETAILS.": $date - ".substr($header,strpos($header, "title")+6,strlen($header));
    } else {
		$newheader = substr($header,1,strpos($header, "TITLE")+5);
		$newheader .= PARTYDETAILS.": $date - ".substr($header,strpos($header, "TITLE")+6,strlen($header));
	}

echo "$newheader
<div class=\"heading\">".PARTYDETAILS." $date at $location&nbsp;<a href=\"editparty.php?partyid=$partyid&remove=True&affiliateid=$affiliateid&customerid=$customerid\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEPARTY." $partyid ".FROMTHEDATABASE."\" title=\"".DELETEPARTY." $partyid ".FROMTHEDATABASE."\" border=\"0\"></a></div><center>
<font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".INVITATIONSSENT.": $invitations, ".ACCEPTED.": $invitationsaccepted</font>
</p>";
if ($msg) echo $msg;
echo "
    <form action=\"editparty.php\" method=\"post\"><input type=\"hidden\" name=\"partyid\" value=\"$partyid\"><input type=\"hidden\" name=\"affiliateid\" value=\"$affiliateid\"><input type=\"hidden\" name=\"customerid\" value=\"$customerid\">
    <table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">

	<tr><td align=\"right\" width=\"150\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".HOSTEDBYCUSTOMER.":</font></td>
    <td align=\"left\"><input type=text name=\"partycustomer\" value=\"$partycustomer\" size=4><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"> <a href=\"editcustomer.php?customerid=$partycustomer\">$customerfirstname $customerlastname</a></font></td></tr>

	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".REFERREDBYAFFILIATE.":</font></td>
    <td align=\"left\"><input type=text name=\"partyaffiliate\" value=\"$partyaffiliate\" size=4><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"> <a href=\"editaffiliate.php?affiliateid=$partyaffiliate\">$sponsorfirstname $sponsorlastname</a></font></td></tr>

	<td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".DATEANDTIME."</font></td>
	<td align=\"left\"><select name=\"year\">";
	for ($y = $thisyear; $y < $tenyearsfromnow; $y++) {
		echo "<option value=\"$y\"";
		if ($partyyear == $y) echo " selected";
		echo ">$y</option>\n";
	}
	echo "
	</select>
	<select name=\"month\"><option value=\"01\""; if ($partymonth == "01") echo " selected"; echo ">".JAN."</option><option value=\"02\""; if ($partymonth == "02") echo " selected"; echo ">".FEB."</option><option value=\"03\""; if ($partymonth == "03") echo " selected"; echo ">".MAR."</option><option value=\"04\""; if ($partymonth == "04") echo " selected"; echo ">".APR."</option><option value=\"05\""; if ($partymonth == "05") echo " selected"; echo ">".MAY."</option><option value=\"06\""; if ($partymonth == "06") echo " selected"; echo ">".JUN."</option><option value=\"07\""; if ($partymonth == "07") echo " selected"; echo ">".JUL."</option><option value=\"08\""; if ($partymonth == "08") echo " selected"; echo ">".AUG."</option><option value=\"09\""; if ($partymonth == "09") echo " selected"; echo ">".SEP."</option><option value=\"10\""; if ($partymonth == "10") echo " selected"; echo ">".OCT."</option><option value=\"11\""; if ($partymonth == "11") echo " selected"; echo ">".NOV."</option><option value=\"12\""; if ($partymonth == "12") echo " selected"; echo ">".DEC."</option></select>
	<select name=\"day\">";
	for ($i = 1; $i < 32; $i++) {
		if ($day < 10) $day = "0".$i;
		else $day = $i;
		echo "<option value=\"$day\"";
		if ($day == $partyday) echo " selected";
		echo ">$i</option>";
	}
	echo "</select> :
	<select name=\"hour\">";
	for ($h = 1; $h <= 12; $h++) {
		if ($h < 10) $thishour = "0".$h;
		else $thishour = $h;
		echo "<option value=\"$thishour\"";
		if ($thishour == $partyhour) echo " selected";
		echo ">$thishour</option>\n";
	}
	echo "</select>
	<select name=\"minute\">";
	for ($m = 0; $m <= 59; $m++) {
		if ($m < 10) $thisminute = "0".$m;
		else $thisminute = $m;
		echo "<option value=\"$thisminute\"";
		if ($thisminute == $partyminute) echo " selected";
		echo ">$thisminute</option>\n";
	}
	echo "</select>
	<select name=\"ampm\"><option value=\"AM\">AM</option><option value=\"PM\">PM</option></select></td></tr>

    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LOCATION.":</font></td>
    <td align=\"left\"><textarea name=\"location\" cols=\"40\" rows=\"5\">$location</textarea></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COMMENTS.":</font></td>
    <td align=\"left\"><textarea name=\"description\" cols=\"40\" rows=\"5\">$description</textarea></td></tr>
    ";
	echo "<tr><td></td><td align=\"right\">";
	echo "<input type=\"submit\" value=\"".UPDATE."\" name=\"update\"></td></tr>
    </table></form>
	</font></center>
	$footer";
?>