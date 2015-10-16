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
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/members.inc.php";
include "ashopconstants.inc.php";

if ($userid != "1" && $memberid != $userid) $memberid = $userid;

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Initiate password hashing...
include "$ashoppath/includes/PasswordHash.php";
$passhasher = new PasswordHash(8, FALSE);

if ($remove && $memberid) {
	if ($yes) {
		if ($userid != "1") {
			header("Location: index.php");
			exit;
		}
		$sql="DELETE FROM user WHERE userid='$memberid'";
		$result = @mysqli_query($db, $sql);
		$sql="DELETE FROM membercategory WHERE userid='$memberid'";
		$result = @mysqli_query($db, $sql);
		$sql="DELETE FROM memberorders WHERE userid='$memberid'";
		$result = @mysqli_query($db, $sql);
		$sql="DELETE FROM category WHERE userid='$memberid'";
		$result = @mysqli_query($db, $sql);
		$productresult = @mysqli_query($db, "SELECT * FROM product WHERE userid='$memberid'");
		while ($row = @mysqli_fetch_array($productresult)) {
			$productid = $row["productid"];
			@mysqli_query($db, "DELETE FROM productcategory WHERE productid='$productid'");
			$findfile = opendir("$ashoppath/prodimg/$productid");
			while ($foundfile = readdir($findfile)) {
				if (!is_dir("$ashoppath/prodimg/$productid/$foundfile")) unlink("$ashoppath/prodimg/$productid/$foundfile");
				else if ($foundfile != "." && $foundfile != "..") {
					$subfindfile = opendir("$ashoppath/prodimg/$productid/$foundfile");
					while ($subfoundfile = readdir($subfindfile)) {
						if (!is_dir("$ashoppath/prodimg/$productid/$foundfile/$subfoundfile")) unlink("$ashoppath/prodimg/$productid/$foundfile/$subfoundfile");
						unset($subfoundfile);
					}
					//if (!empty($productid) && !empty($foundfile)) rmdir("$ashoppath/prodimg/$productid/$foundfile");
				}
				unset($foundfile);
			}
			if (!empty($productid)) rmdir("$ashoppath/prodimg/$productid");
			unset($findfile);
			if (is_dir("$ashoppath/previews/$productid")) {
				$findfile = opendir("$ashoppath/previews/$productid");
				while (false !== ($foundfile = readdir($findfile))) { 
					if($foundfile && $foundfile != "." && $foundfile != "..") unlink("$ashoppath/previews/$productid/$foundfile");
					unset($foundfile);
				}
				closedir($findfile);
				rmdir("$ashoppath/previews/$productid");
				unset($findfile);
			}
			if (file_exists("$ashopspath/updates/$productid")) unlink ("$ashopspath/updates/$productid");
			$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
			while ($filerow = @mysqli_fetch_array($result)) if (file_exists("$ashopspath/products/{$filerow["fileid"]}")) unlink("$ashopspath/products/{$filerow["fileid"]}");
			$result = @mysqli_query($db, "SELECT * FROM parameters WHERE productid='$productid'");
			while ($paramrow = @mysqli_fetch_array($result)) {
				$parameterid = $paramrow["parameterid"];
				@mysqli_query($db, "DELETE FROM parametervalues WHERE parameterid='$parameterid'");
				@mysqli_query($db, "DELETE FROM customparametervalues WHERE parameterid='$parameterid'");
			}
			@mysqli_query($db, "DELETE FROM parameters WHERE productid='$productid'");				
			@mysqli_query($db, "DELETE FROM productfiles WHERE productid='$productid'");
			@mysqli_query($db, "DELETE FROM flagvalues WHERE productid='$productid'");
			@mysqli_query($db, "DELETE FROM qtypricelevels WHERE productid='$productid'");
			@mysqli_query($db, "DELETE FROM unlockkeys WHERE productid='$productid'");
			@mysqli_query($db, "DELETE FROM updates WHERE productid='$productid'");
			$result = @mysqli_query($db, "SELECT * FROM discount WHERE productid='$productid'");
			while ($discountrow = @mysqli_fetch_array($result)) {
				$discountid = $discountrow["discountid"];
				@mysqli_query($db, "DELETE FROM onetimediscounts WHERE discountid='$discountid'");
			}
			@mysqli_query($db, "DELETE FROM discount WHERE productid='$productid'");
		}
		@mysqli_query($db, "DELETE FROM product WHERE userid='$memberid'");
		if (!empty($cpanelapiuser) && !empty($cpanelapipass) && !empty($cpanelapiurl)) {
			header("Location: makeshop.php?remove=$memberid");
			exit;
		}
		header("Location: memberadmin.php");
    }
	elseif ($no) {
		if ($userid == "1") header("Location: memberadmin.php");
		else header("Location: index.php");
	}
	else {
		if ($userid != "1") {
			header("Location: index.php");
			exit;
		}
		$sql="SELECT shopname FROM user WHERE userid='$memberid'";
		$result = @mysqli_query($db, $sql);
		$shopname = @mysqli_result($result,0,"shopname");
		echo "$header
<div class=\"heading\">".REMOVEAMEMBER."</div><center>
        <p>".AREYOUSURE.": $memberid, $shopname?</font></p>
		<form action=\"editmember.php\" method=\"post\">
		<table width=\"440\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\">
		<tr>
        <td width=\"100%\" align=\"center\" valign=\"top\"><input type=\"submit\" name=\"yes\" value=\"".YES."\">
		<input type=\"button\" name=\"no\" value=\"".NO."\" onClick=\"javascript:history.back()\"></td>
		</tr></table><input type=\"hidden\" name=\"memberid\" value=\"$memberid\">
		<input type=\"hidden\" name=\"remove\" value=\"True\"></form>
		</center>
        $footer";
		exit;
	}
}

// Get member information from database...
$sql="SELECT * FROM user WHERE userid='$memberid'";
$result = @mysqli_query($db, "$sql");
$username = @mysqli_result($result, 0, "username");

// Store updated data...
if ($update) {
	$passhash = "";
	if ($userid > 1 && $oldpassword && $newpassword1 && $newpassword2) {
		$sql = "SELECT password FROM user WHERE userid = '$userid'";
		$passresult = @mysqli_query($db, $sql);
		$correctoldpassword = @mysqli_result($passresult,0,"password");
		$passcheck = $passhasher->CheckPassword($oldpassword, $correctoldpassword);
		if ($passcheck && $newpassword1 == $newpassword2) $passhash = $passhasher->HashPassword($newpassword1);
		else if (!$passcheck) $passworderror="old";
		else if ($newpassword1 != $newpassword2) $passworderror="new";
	} else if ($userid == "1" && !empty($password)) $passhash = $passhasher->HashPassword($password);

	$sql="UPDATE user SET username='$username'";
	if (!empty($passhash)) $sql .= ", password='$passhash'";
	$sql .= ", shopname='$shopname', shopdescription='$description', url='$url', businesstype='$businesstype', firstname='$firstname', lastname='$lastname', email='$email', address='$address', state='$state', zip='$zip', city='$city', country='$country', phone='$phone', paymentdetails='$paymentdetails'";
	if ($userid == "1") $sql .= ", commissionlevel='$commission', mallmode='$custommallmode'";
	$sql .= " WHERE userid='$memberid'";
    @mysqli_query($db, "$sql");

	// Store selected categories...
	if ($shopcategories) {
		@mysqli_query($db, "DELETE FROM membercategory WHERE userid='$memberid'");
		foreach ($shopcategories as $key => $value) @mysqli_query($db, "INSERT INTO membercategory (userid, categoryid) VALUES ('$memberid', '$value')");
	}

	if (!$passworderror) {
		if ($userid == "1") header("Location: memberadmin.php"); 
		else header("Location: index.php");
		exit;
	}
}

// Get additional member information...
$commission = @mysqli_result($result, 0, "commissionlevel");
if (!$commission) $commission = $memberpercent;
$shopname = @mysqli_result($result, 0, "shopname");
$shopdescription = @mysqli_result($result, 0, "shopdescription");
$url = @mysqli_result($result, 0, "url");
if (!$url) $url = $ashopurl."/index.php?shop=$memberid";
$businesstype = @mysqli_result($result, 0, "businesstype");
$firstname = @mysqli_result($result, 0, "firstname");
$lastname = @mysqli_result($result, 0, "lastname");
$email = @mysqli_result($result, 0, "email");
$address = @mysqli_result($result, 0, "address");
$state = @mysqli_result($result, 0, "state");
$zip = @mysqli_result($result, 0, "zip");
$city = @mysqli_result($result, 0, "city");
$country = @mysqli_result($result, 0, "country");
$phone = @mysqli_result($result, 0, "phone");
$paymentdetails = @mysqli_result($result, 0, "paymentdetails");
$ashoptheme = @mysqli_result($result, 0, "theme");
$custommallmode = @mysqli_result($result, 0, "mallmode");

$categoriesstring = "";
$categories = @mysqli_query($db, "SELECT * FROM shopcategory ORDER BY name");
while ($row = @mysqli_fetch_array($categories)) {
	$categoriesstring .= "<option value=\"{$row["categoryid"]}\"";
	$result2 = @mysqli_query($db, "SELECT * FROM membercategory WHERE categoryid='{$row["categoryid"]}' AND userid='$memberid'");
	if (@mysqli_num_rows($result2)) $categoriesstring .= " selected";
	$categoriesstring .= ">{$row["name"]}";
}

// Close database...
@mysqli_close($db);

// Show member profile page in browser...
echo "$header
<div class=\"heading\">".PROFILEOF." $shopname, ".MEMBERID." $memberid&nbsp;<a href=\"";
if ($membershops) echo "editcatalogue";
else echo "editmembercat";
echo ".php?memberid=$memberid\"><img src=\"images/icon_catalog.gif\" alt=\"".PRODUCTCATALOGFOR." $memberid\" title=\"".PRODUCTCATALOGFOR." $memberid\" border=\"0\"></a>&nbsp;<a href=\"salesreport.php?memberid=$memberid&generate=true&reporttype=paid\"><img src=\"images/icon_history.gif\" alt=\"".SALESHISTORYFOR." $memberid\" title=\"".SALESHISTORYFOR." $memberid\" border=\"0\"></a>";
if ($userid == "1") echo "&nbsp;<a href=\"editmember.php?memberid=$memberid&remove=True\"><img src=\"images/icon_trash.gif\" alt=\"".DELETEMEMBER." $memberid ".FROMDB."\" title=\"".DELETEMEMBER." $memberid ".FROMDB."\" border=\"0\"></a>";
echo "</div><center>";
if ($passworderror == "old") echo "<CENTER><P><font size=\"3\" color=\"#FF0000\"><b>".OLDPASSWORDINCORRECT."</b></font></P></CENTER>";
if ($passworderror == "new") echo "<CENTER><P><font size=\"3\" color=\"#FF0000\"><b>".PASSWORDSDIDNOTMATCH."</b></font></P></CENTER>";
echo "
    <form action=\"editmember.php\" method=\"post\" enctype=\"multipart/form-data\"><input type=\"hidden\" name=\"memberid\" value=\"$memberid\">
    <table width=\"540\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".USERNAME.":</font></td>
    <td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">$username</font></td></tr>";
if ($userid > 1) echo "
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".OLDPASSWORD.":</font></td>
	<td align=\"left\"><input type=\"password\" name=\"oldpassword\" size=\"25\"></td></tr>
	<tr><td align=\"right\">&nbsp;</td><td align=\"left\"><span class=\"sm\">[".LEAVEBLANKTOKEEP."]</span></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".NEWPASSWORD.":</font></td>
	<td align=\"left\"><input type=\"password\" name=\"newpassword1\" size=\"25\"></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CONFIRM.":</font></td>
	<td><input type=\"password\" name=\"newpassword2\" size=\"25\"></td></tr>";
else echo "
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PASSWORD.":</font></td>
    <td align=\"left\"><input type=text name=\"password\" value=\"$password\" size=15></td></tr>";
echo "
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".SHOPNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"shopname\" value=\"$shopname\" size=40></td></tr>";
if ($userid == "1") {
	echo "<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COMMISSIONLEVEL.":</font></td><td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><input type=text name=\"commission\" value=\"$commission\" size=5>%</font></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".PRODUCTMANAGEMENT.":</font></td>
    <td align=\"left\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\"><select name=\"custommallmode\"><option value=\"deactivated\"";
	if ((empty($custommallmode) && !$memberprodmanage && !$advancedmallmode) || $custommallmode == "deactivated") echo " selected";
	echo ">".DEACTIVATED."</option><option value=\"simple\"";
	if ((empty($custommallmode) && $memberprodmanage && !$advancedmallmode) || $custommallmode == "simple") echo " selected";
	echo ">".SIMPLEMODE."</option><option value=\"advanced\"";
	if ((empty($custommallmode) && $memberprodmanage && $advancedmallmode) || $custommallmode == "advanced") echo " selected";
	echo ">".ADVANCEDMODE."</option></select></font></td></tr>";
}
echo "<tr><td align=\"right\" valign=\"top\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CATEGORIES.":</font></td>
	<td align=\"left\"><select name=\"shopcategories[]\" size=\"5\" multiple>$categoriesstring</select><br><font size=\"1\" face=\"Arial, Helvetica, sans-serif\"> ".CTRLCLICKFORMULTIPLE."</font></td></tr>
    <tr><td align=\"right\" valign=\"top\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".SHOPDESCRIPTION.":</font></td>
	<td align=\"left\"><textarea name=\"description\" cols=\"35\" rows=\"4\">$shopdescription</textarea></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".BUSINESSTYPE.":</font></td>
    <td align=\"left\"><select name=\"businesstype\"><option value=\"none\""; 
	if ($businesstype == "none") echo " selected";
	echo ">".SELECTONE."</option><option value=\"soleproprietor\"";
	if ($businesstype == "soleproprietor") echo " selected";
	echo ">".SOLEPROPRIETOR."</option><option value=\"corporation\"";
	if ($businesstype == "corporation") echo " selected";
	echo ">".CORPORATION."</option><option value=\"nonprofit\"";
	if ($businesstype == "nonprofit") echo " selected";
	echo ">".NONPROFIT."</option></select></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".WEBSITEURL.":</font></td>
    <td align=\"left\"><input type=text name=\"url\" value=\"$url\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".FIRSTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"firstname\" value=\"$firstname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".LASTNAME.":</font></td>
    <td align=\"left\"><input type=text name=\"lastname\" value=\"$lastname\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".EMAIL.":</font></td>
    <td align=\"left\"><input type=text name=\"email\" value=\"$email\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ADDRESS.":</font></td>
    <td align=\"left\"><input type=text name=\"address\" value=\"$address\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".STATEPROVINCE.":</font></td>
    <td align=\"left\"><input type=text name=\"state\" value=\"$state\" size=40></td></tr>
	<tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".ZIP.":</font></td>
    <td align=\"left\"><input type=text name=\"zip\" value=\"$zip\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".CITY.":</font></td>
    <td align=\"left\"><input type=text name=\"city\" value=\"$city\" size=40></td></tr>
    <tr><td align=\"right\"><font size=\"2\" face=\"Arial, Helvetica, sans-serif\">".COUNTRY.":</font></td>
    <td align=\"left\"><select name=\"country\"><option  value=none>".CHOOSECOUNTRY;

	foreach ($countries as $shortcountry => $longcountry) {
		if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
		echo "<option  value=$shortcountry";
		if ($country == $shortcountry) echo " selected";
		echo ">$longcountry\n"; 
	}

	echo "</select></td></tr>
	<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PHONE.":</font></td>
    <td align=\"left\"><input type=text name=\"phone\" value=\"$phone\" size=40></td></tr>
	<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".PAYMENTDETAILS.":</font></td>
    <td align=\"left\"><textarea name=\"paymentdetails\" cols=\"40\" rows=\"5\">$paymentdetails</textarea></td></tr>
    <tr><td></td><td align=\"right\"><input type=\"submit\" value=\"".UPDATE."\" name=\"update\"></td></tr>
    </table></form>
	</font></center>
	$footer";
?>