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
if ($userid != "1" && !$memberpayoptions) {
	header("Location: editmember.php");
	exit;
}
include "template.inc.php";
// Get language module...
include "language/$adminlang/configure.inc.php";
include "ashopconstants.inc.php";
// Get context help for this page...
$contexthelppage = "payoptions";
include "help.inc.php"; 

echo "$header
<div class=\"heading\">".PAYMENTOPTIONS."</div>
        <table align=\"center\" width=\"600\" cellpadding=\"10\"><tr><td>

	<form action=\"payoptions.php\" method=\"post\" name=\"payoptionform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td width=\"50%\"><a href=\"$help1\" class=\"helpnav\" target=\"_blank\">".ADDNEWPAYMENTOPTION."</a> </td><td></td></tr><tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".SELECTAGATEWAY.":</td><td align=\"left\" class=\"formlabel\"><select name=\"ngw\">";
if ($ashopcurrency != "usd") $pathprefix = $ashopcurrency; else $pathprefix = "";
$findfile = opendir("$ashoppath/admin/gateways$pathprefix");
while ($foundfile = readdir($findfile)) {
	if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") {
		$splitname = explode(".", $foundfile);
		$fp = fopen ("$ashoppath/admin/gateways$pathprefix/$foundfile","r");
		while (!feof ($fp)) {
			$fileline = fgets($fp, 4096);
			if (strstr($fileline,"\$gatewayname")) $gatewaynamestring = $fileline;
		}
		fclose($fp);
		eval ($gatewaynamestring);
		$payoptions[$splitname[0]] = $gatewayname;
	}
}
natcasesort($payoptions);
foreach ($payoptions as $payoptiongw=>$payoptionname) echo "<option value=\"$payoptiongw\">$payoptionname</option>";
echo "</select></td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updatepayoption\" value=\"new\"><input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form><br>";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Move payment option...
if ($moveup) {
	$sql="UPDATE payoptions SET ordernumber='$prevordno' WHERE payoptionid='$thisid'";
    $result = @mysqli_query($db, $sql);
	$sql="UPDATE payoptions SET ordernumber='$thisordno' WHERE payoptionid='$previd'";
    $result = @mysqli_query($db, $sql);
}

// Update selected payment option...
if ($updatepayoption && !$delete) {
	if (!$nfee) $nfee = 0.00;
	if (!$nthankyoutext) $nthankyoutext = DEFAULTTHANKYOUTEXT.$ashopphone.DEFAULTTHANKYOUTEXT2.$ashopemail.DEFAULTTHANKYOUTEXT3.$ashopaddress.DEFAULTTHANKYOUTEXT4;
	$nthankyoutext = str_replace("</body>","",$nthankyoutext);
	$nthankyoutext = str_replace("</BODY>","",$nthankyoutext);
	$nthankyoutext = str_replace("<body>","",$nthankyoutext);
	$nthankyoutext = str_replace("<BODY>","",$nthankyoutext);
	$npayoptiondescr = str_replace("</body>","",$npayoptiondescr);
	$npayoptiondescr = str_replace("</BODY>","",$npayoptiondescr);
	$npayoptiondescr = str_replace("<body>","",$npayoptiondescr);
	$npayoptiondescr = str_replace("<BODY>","",$npayoptiondescr);
    if ($ntestmode == "on") $ntestmode = 1;
	else $ntestmode = 0;
    if ($nautodelivery == "on") $nautodelivery = 1;
	else $nautodelivery = 0;
    if ($ndeliverpending == "on") $ndeliverpending = 1;
	else $ndeliverpending = 0;
    if ($nsmspayment == "on") $nsmspayment = 1;
	else $nsmspayment = 0;
	if ($nvisiblein == "emerchantonly") $nemerchantonly = 1;
	else if ($nvisiblein == "wholesaleonly") $nwholesaleonly = 1;
	else if ($nvisiblein == "retailonly") $nretailonly = 1;
	else if ($nvisiblein == "all") {
		$nemerchantonly = 0;
		$nwholesaleonly = 0;
		$nretailonly = 0;
	}
    if ($ntelesign == "on") $ntelesign = 1;
	else $ntelesign = 0;
	if (!$npageid) $npageid = 0;
	if ($updatepayoption == "new") {
		@mysqli_query($db, "INSERT INTO payoptions (gateway, thankyoutext, userid) VALUES ('$ngw', '$nthankyoutext', '$userid')");
		$payoptionid = @mysqli_insert_id($db);
		@mysqli_query($db, "UPDATE payoptions SET ordernumber='$payoptionid' WHERE payoptionid='$payoptionid'");
	} else {
		if ($ninitialperiod) $ninitialperiod .= "|$initialperiodunits";
		if ($nrecurringperiod) $nrecurringperiod .= "|$recurringperiodunits";
		$securityresult = @mysqli_query($db, "SELECT merchantid FROM payoptions WHERE payoptionid='$updatepayoption'");
		$oldmerchantid = @mysqli_result($securityresult, 0, "merchantid");
		if ($oldmerchantid != $nmerchantid) {
			$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail",un_html($ashopname)." - ".PAYMENTCONFIGCHANGED,THEPAYMENTCONFIGAT." $ashopurl ".HASBEENCHANGED." {$_SERVER["REMOTE_ADDR"]}!","$headers");
		}
		@mysqli_query($db, "UPDATE payoptions SET gateway='$ngw', name='$nname', merchantid='$nmerchantid', secret='$nsecret', transactionkey='$ntranskey', logourl='$nlogourl', vspartner='$nvspartner', pageid='$npageid', testmode='$ntestmode', autodelivery=$nautodelivery, smspayment='$nsmspayment', deliverpending='$ndeliverpending', bgcolor='$ngwbgcolor', bgurl='$ngwbgurl', description='$npayoptiondescr', orderpagetext='$norderpagetext', thankyoutext='$nthankyoutext', fee='$nfee', emerchantonly='$nemerchantonly', wholesaleonly='$nwholesaleonly', retailonly='$nretailonly', telesign='$ntelesign',initialperiod='$ninitialperiod',recurringperiod='$nrecurringperiod',rebills='$nrebills',paypalid='$npaypalid' WHERE payoptionid='$updatepayoption'");
	}
} else if ($updatepayoption && $delete) {
	$sql="DELETE FROM payoptions WHERE payoptionid='$updatepayoption'";
	$result = @mysqli_query($db, "$sql");
}

// Display current payment options...
$sql="SELECT * FROM payoptions WHERE userid='$userid' ORDER BY ordernumber";
$result = @mysqli_query($db, "$sql");
$gw = "";
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$gw = @mysqli_result($result, $i, "gateway");
	if (file_exists("$ashoppath/admin/gateways$pathprefix/$gw.gw")) {
		$emerchantonly = @mysqli_result($result, $i, "emerchantonly");
		$retailonly = @mysqli_result($result, $i, "retailonly");
		$wholesaleonly = @mysqli_result($result, $i, "wholesaleonly");
		$payoptionid = @mysqli_result($result, $i, "payoptionid");
		$ordernumber = @mysqli_result($result, $i, "ordernumber");
		$payoptionname = @mysqli_result($result, $i, "name");
		$payoptiondescr = @mysqli_result($result, $i, "description");
		$orderpagetext = @mysqli_result($result, $i, "orderpagetext");
		$payoptionthankyou = @mysqli_result($result, $i, "thankyoutext");
		$payoptionfee = @mysqli_result($result, $i, "fee");
		$merchantid = @mysqli_result($result, $i, "merchantid");
		$logourl = @mysqli_result($result, $i, "logourl");
		$vspartner = @mysqli_result($result, $i, "vspartner");
		$pageid = @mysqli_result($result, $i, "pageid");
		$gwbgcolor = @mysqli_result($result, $i, "bgcolor");
		$gwbgurl = @mysqli_result($result, $i, "bgurl");
		$testmode = @mysqli_result($result, $i, "testmode");
		$autodelivery = @mysqli_result($result, $i, "autodelivery");
		$deliverpending = @mysqli_result($result, $i, "deliverpending");
		$smspayment = @mysqli_result($result, $i, "smspayment");
		$securitysecret = @mysqli_result($result, $i, "secret");
		$transkey = @mysqli_result($result, $i, "transactionkey");
		$paypalid = @mysqli_result($result, $i, "paypalid");
		$telesign = @mysqli_result($result, $i, "telesign");
		$initialperiod = @mysqli_result($result, $i, "initialperiod");
		if (!empty($initialperiod) && strstr($initialperiod,"|")) {
			$initialperiodarray = explode("|",$initialperiod);
			$initialperiod = $initialperiodarray[0];
			$initialperiodunits = $initialperiodarray[1];
		} else {
			$initialperiod = "";
			$initialperiodunits = "";
		}
		$recurringperiod = @mysqli_result($result, $i, "recurringperiod");
		if (!empty($recurringperiod) && strstr($recurringperiod,"|")) {
			$recurringperiodarray = explode("|",$recurringperiod);
			$recurringperiod = $recurringperiodarray[0];
			$recurringperiodunits = $recurringperiodarray[1];
		} else {
			$recurringperiod = "";
			$recurringperiodunits = "";
		}
		$rebills = @mysqli_result($result, $i, "rebills");
		unset($gw_parameters);

		include "gateways$pathprefix/$gw.gw";

		echo "<form action=\"payoptions.php\" method=\"post\" name=\"payoptionform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td align=\"right\" class=\"formlabel\">".OPTIONNAME.":</td><td><input type=\"text\" name=\"nname\" size=\"35\" value=\"";
		
		if ($payoptionname) echo $payoptionname;
		else echo "$gatewayname";

		echo "\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PAYMENTGATEWAY.":</td><td class=\"formlabel\"><select name=\"ngw\" onChange=\"document.payoptionform$i.submit()\">";
		reset($payoptions);
		if (is_array($payoptions)) foreach ($payoptions as $payoptiongw=>$payoptionname) {
			echo "<option value=\"$payoptiongw\"";
			if ($payoptiongw == $gw) echo " selected";
			echo ">$payoptionname</option>";
		}
		echo "</select></td></tr><tr><td align=\"right\" class=\"formlabel\">".PAYMENTFEE." <span=\"sm\">".OPTIONAL."</span>:</td><td class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"nfee\" size=\"5\" value=\"$payoptionfee\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";

		if (file_exists("$ashoppath/emerchant/quote.php") && $userid == "1") { 
			echo "<tr><td align=\"right\" class=\"formlabel\">".VISIBLEIN.":</td><td><select name=\"nvisiblein\"><option value=\"all\""; if (!$retailonly && !$wholesaleonly && !$emerchantonly) echo " selected"; echo ">".ENTIRESITE."</option><option value=\"retailonly\""; if ($retailonly == "1") echo " selected"; echo ">".RETAILCATALOGONLY."</option><option value=\"wholesaleonly\""; if ($wholesaleonly == "1") echo " selected"; echo ">".WHOLESALEONLY."</option><option value=\"emerchantonly\""; if ($emerchantonly == "1") echo " selected"; echo ">".SALESOFFICEONLY."</option></select></td></tr>";
		} else if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".VISIBLEIN.":</td><td><select name=\"nvisiblein\"><option value=\"all\">".ENTIRESITE."</option><option value=\"retailonly\""; if ($retailonly == "1") echo " selected"; echo ">".RETAILCATALOGONLY."</option><option value=\"wholesaleonly\""; if ($wholesaleonly == "1") echo " selected"; echo ">".WHOLESALEONLY."</option></select></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nvisbilein\" value=\"all\">";

		if ($telesignid && $gw_parameters['telesign'] == "true") { 
			echo "<tr><td align=\"right\" class=\"formlabel\">".TELESIGNVERIFICATION.":</td><td><input type=\"checkbox\" name=\"ntelesign\""; if ($telesign == "1") echo "checked"; echo "></td></tr>";
		} else echo "<input type=\"hidden\" name=\"ntelesign\" value=\"\">";

		if ($gw_parameters['merchantid'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".MERCHANTID.":</td><td><input type=\"text\" name=\"nmerchantid\" size=\"35\" value=\"$merchantid\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nmerchantid\" value=\"\">";

		if ($gw_parameters['secret'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".SECURITYSECRET.":</td><td><input type=\"text\" name=\"nsecret\" size=\"35\" value=\"$securitysecret\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nsecret\" value=\"\">";

		if ($gw_parameters['pageid'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".PAGEID.":</td><td><input type=\"text\" name=\"npageid\" size=\"5\" value=\"$pageid\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"npageid\" value=\"\">";

		if ($gw_parameters['transactionkey'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".TRANSACTIONKEY.":</td><td><input type=\"text\" name=\"ntranskey\" size=\"35\" value=\"$transkey\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"ntranskey\" value=\"\">";

		if (file_exists("$ashoppath/members/index.php") && $gw == "paypalec") echo "<tr><td align=\"right\" class=\"formlabel\">".PAYPALID.":</td><td><input type=\"text\" name=\"npaypalid\" size=\"35\" value=\"$paypalid\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"npaypalid\" value=\"\">";

		if ($gw_parameters['logourl'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".SSLURLLOGO.":</td><td><input type=\"text\" name=\"nlogourl\" size=\"35\" value=\"$logourl\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nlogourl\" value=\"\">";

		if ($gw_parameters['vspartner'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".VERISIGNPARTNER.":</td><td><input type=\"text\" name=\"nvspartner\" size=\"35\" value=\"$vspartner\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nvspartner\" value=\"\">";

		if ($gw_parameters['formfields'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".EXTRAFORMFIELDS.":</td><td class=\"formlabel\"><a href=\"editformfields.php?payoption=$payoptionid\">".EDITLIST."</a></td></tr>";

		if ($gw_parameters['testmode'] == "true") { 
			echo "<tr><td align=\"right\" class=\"formlabel\">".TESTMODE.":</td><td><input type=\"checkbox\" name=\"ntestmode\""; if ($testmode == "1") echo "checked"; echo "></td></tr>";
		} else echo "<input type=\"hidden\" name=\"ntestmode\" value=\"\">";

		if ($gw_parameters['autodelivery'] == "true") { 
			echo "<tr><td align=\"right\" class=\"formlabel\">".AUTODELIVERY.":</td><td><input type=\"checkbox\" name=\"nautodelivery\""; if ($autodelivery == "1") echo "checked"; echo "></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nautodelivery\" value=\"\">";

		if ($gw_parameters['deliverpending'] == "true") { 
			echo "<tr><td align=\"right\" class=\"formlabel\">".DELIVERBEFOREPAY.":</td><td><input type=\"checkbox\" name=\"ndeliverpending\""; if ($deliverpending == "1") echo "checked"; echo "><span class=\"sm\">".ALLOWFILEDOWNLOAD."</span></td></tr>";
		} else echo "<input type=\"hidden\" name=\"ndeliverpending\" value=\"\">";
		
		if ($gw_parameters['gwbgcolor'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".BACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"ngwbgcolor\" size=\"35\" value=\"$gwbgcolor\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"ngwbgcolor\" value=\"\">";

		if ($gw_parameters['gwbgurl'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".BACKGROUNDURL.":<br><span class=\"sm\">".LEAVEBLANKTOUSECOLOR."</span></td><td><input type=\"text\" name=\"ngwbgurl\" size=\"35\" value=\"$gwbgurl\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"ngwbgurl\" value=\"\">";

		if ($gw_parameters['smspayment'] == "true") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".SMSPAYMENT.":</td><td><input type=\"checkbox\" name=\"nsmspayment\""; if ($smspayment == "1") echo "checked"; echo "></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nsmspayment\" value=\"0\">";

		if ($gw_parameters['initialperiod'] == "true" || $gw_parameters['recurringperiod'] == "true" || $gw_parameters['rebills'] == "true") echo "<tr bgcolor=\"#C0C0C0\"><td align=\"left\" class=\"formlabel\" colspan=\"2\"><b>".RECURRINGBILLINGCONFIG.":</b><br>".ONLYUSEDWHENACTIVATED."</td></tr>";

		if ($gw_parameters['initialperiod'] == "true") {
			echo "<tr bgcolor=\"#C0C0C0\"><td align=\"right\" class=\"formlabel\">".INITIALPERIOD.":</td><td><input type=\"text\" name=\"ninitialperiod\" size=\"5\" value=\"$initialperiod\"> <select name=\"initialperiodunits\"><option value=\"D\""; if ($initialperiodunits == "D") echo " selected"; echo ">".DAYS."</option><option value=\"W\""; if ($initialperiodunits == "W") echo " selected"; echo ">".WEEKS."</option><option value=\"M\""; if ($initialperiodunits == "M") echo " selected"; echo ">".MONTHS."</option><option value=\"Y\""; if ($initialperiodunits == "Y") echo " selected"; echo ">".YEARS."</option></select></td></tr>";
		} else echo "<input type=\"hidden\" name=\"ninitialperiod\" value=\"\">";

		if ($gw_parameters['recurringperiod'] == "true") {
			echo "<tr bgcolor=\"#C0C0C0\"><td align=\"right\" class=\"formlabel\">".RECURRINGPERIOD.":</td><td><input type=\"text\" name=\"nrecurringperiod\" size=\"5\" value=\"$recurringperiod\"> <select name=\"recurringperiodunits\"><option value=\"D\""; if ($recurringperiodunits == "D") echo " selected"; echo ">".DAYS."</option><option value=\"W\""; if ($recurringperiodunits == "W") echo " selected"; echo ">".WEEKS."</option><option value=\"M\""; if ($recurringperiodunits == "M") echo " selected"; echo ">".MONTHS."</option><option value=\"Y\""; if ($recurringperiodunits == "Y") echo " selected"; echo ">".YEARS."</option></select></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nrecurringperiod\" value=\"\">";

		if ($gw_parameters['rebills'] == "true") {
			echo "<tr bgcolor=\"#C0C0C0\"><td align=\"right\" class=\"formlabel\">".NUMBEROFREBILLS.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nrebills\" size=\"5\" value=\"$rebills\"> ".ZEROREBILLINDEFINITELY."</td></tr>";
		} else echo "<input type=\"hidden\" name=\"nrebills\" value=\"0\">";

		echo "<tr><td valign=\"top\" align=\"right\" class=\"formlabel\">".DESCRIPTION.":</td><td class=\"formlabel\" valign=\"top\"><textarea name=\"npayoptiondescr\" cols=\"30\" rows=\"5\">$payoptiondescr</textarea></td></tr>";

		if ($gw_parameters['paymentinfo'] == "true") {
			echo "<tr><td valign=\"top\" align=\"right\" class=\"formlabel\">".TEXTONORDERPAGE.":</td><td class=\"formlabel\"><textarea name=\"norderpagetext\" cols=\"30\" rows=\"5\">$orderpagetext</textarea></td></tr>";
		} else echo "<input type=\"hidden\" name=\"norderpagetext\" value=\"\">";

		echo "<tr><td valign=\"top\" align=\"right\" class=\"formlabel\">".THANKYOUMESSAGE.":</td><td valign=\"top\" class=\"formlabel\"><textarea name=\"nthankyoutext\" cols=\"30\" rows=\"5\">$payoptionthankyou</textarea>";
		if ($gw == "inetsecure" || $gw == "moneybookers" || $gw == "nochex" || $gw == "sfipay" || $gw == "usight") echo "<br><font size=\"1\">".NOVARIABLESAVAILABLE."</font></td></tr>";
		else echo "<br><font size=\"1\">".THANKYOUCODES."</font></td></tr>";
		echo "<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updatepayoption\" value=\"$payoptionid\">";

		if ($previousorderno) echo "<input type=\"hidden\" name=\"thisordno\" value=\"$ordernumber\"><input type=\"hidden\" name=\"prevordno\" value=\"$previousorderno\"><input type=\"hidden\" name=\"thisid\" value=\"$payoptionid\"><input type=\"hidden\" name=\"previd\" value=\"$previousid\"><input type=\"submit\" name=\"moveup\" value=\"".MOVEUP."\"> ";
		$previousorderno = $ordernumber;
		$previousid = $payoptionid;
		
		echo "<input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form><br>";
	}
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>