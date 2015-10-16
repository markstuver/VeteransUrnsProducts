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
include "template.inc.php";
include "ashopconstants.inc.php";
// Get language module...
include "language/$adminlang/configure.inc.php";

// Initiate password hasher for changing the admin panel password...
include "$ashoppath/includes/PasswordHash.php";
$passhasher = new PasswordHash(8, FALSE);

// Handle uploaded logo file...
if (is_uploaded_file($imgfile)) {
	$fileinfo = pathinfo("$imgfile_name");
	$extension = strtolower($fileinfo["extension"]);
	if ($extension == "gif" && is_writeable("$ashoppath/images")) {
		move_uploaded_file($imgfile, "$ashoppath/images/logo.gif");
		@chmod("$ashoppath/images/logo.gif", 0777);
	}
}

// Handle uploaded mobile logo file...
if (is_uploaded_file($mobimgfile)) {
	$fileinfo = pathinfo("$mobimgfile_name");
	$extension = strtolower($fileinfo["extension"]);
	if ($extension == "gif" && is_writeable("$ashoppath/images")) {
		move_uploaded_file($mobimgfile, "$ashoppath/images/logomobile.gif");
		@chmod("$ashoppath/images/logomobile.gif", 0777);
	}
}

// Open database connection...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Handle multiple origin countries...
if (substr($shipfromcountry,0,1) == "-") $shipfromcountry = substr_replace($shipfromcountry, "", 0,1);
$shipfromcountries = explode("-", $shipfromcountry);
$countrynumber = 1;
if ($shipfromcountries) foreach ($shipfromcountries as $thisshipfromcountry) {
	eval ("\$updatethiscountry = \$nshipfromcountry$countrynumber;");
	if ($countrynumber == 1) {
		if ($updatethiscountry && $updatethiscountry != "none") $nshipfromcountry = $updatethiscountry;
		else if ($updatethiscountry != "none") $nshipfromcountry = $thisshipfromcountry;
	} else {
		if ($updatethiscountry && $updatethiscountry != "none") $nshipfromcountry .= "-$updatethiscountry";
		else if ($updatethiscountry != "none") $nshipfromcountry .= "-$thisshipfromcountry";
	}
	$countrynumber++;
}
eval ("\$addedcountry = \$nshipfromcountry$countrynumber;");
if ($addedcountry) $nshipfromcountry .= "-$addedcountry";
if (substr($nshipfromcountry,0,1) == "-") $nshipfromcountry = substr_replace($nshipfromcountry, "", 0,1);

// Keep changes when adding new origin country...
if ($addcountry) {
	$shippingtax = $nshippingtax;
	$requestvat = $nrequestvat;
	$requestabn = $nrequestabn;
	$taxstate = $ntaxstate;
	$taxpercentage = $ntaxpercentage;
	$shipfromcountries = explode ("-", $nshipfromcountry);
	$handlinglocal = $nhandlinglocal;
	$handlingint = $nhandlingint;
	@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshipfromcountry' WHERE prefname='shipfromcountry'");
}

// Handle weight shipping levels...
$weightshippinglevelstring = "";
$nweightshipping = "";
if (!empty($weightshippinglevel0)) {
	foreach ($_POST as $key=>$value) {
		if (strstr($key,"weightshippinglevel")) {
			$thislevel = str_replace("weightshippinglevel","",$key);
			$thisweightshippinglevel = $value;
			$weightshippingcostvariablename = "weightshippingcost$thislevel";
			$thisweightshippingcost = $$weightshippingcostvariablename;
			if (is_numeric($thisweightshippingcost) && is_numeric($thisweightshippinglevel)) {
				$thisweightshippinglevel = number_format($thisweightshippinglevel,2,'.','');
				$thisweightshippingcost = number_format($thisweightshippingcost,2,'.','');
				if (!empty($nweightshipping)) $nweightshipping .= "|";
				$nweightshipping .= "$thisweightshippinglevel:$thisweightshippingcost";
			}
		}
	}
} else {
	$weightshippinglevels = explode("|",$weightshipping);
	$previousweightshippinglevel = "0.00";
	foreach ($weightshippinglevels as $weightshippingnumber=>$thisweightshipping) {
		if (!empty($thisweightshipping) && strstr($thisweightshipping,":")) {
			$thisweightshippingarray = explode(":",$thisweightshipping);
			$thisweightshippinglevel = $thisweightshippingarray[0];
			$thisweightshippingcost = $thisweightshippingarray[1];
			$weightshippinglevelstring .= "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\" colspan=\"2\">".WEIGHTFROM." $previousweightshippinglevel ".POUNDSUPTO." <input type=\"text\" name=\"weightshippinglevel{$weightshippingnumber}\" size=\"6\" value=\"$thisweightshippinglevel\"> ".POUNDSEQUALSHIPPING.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"weightshippingcost{$weightshippingnumber}\" size=\"6\" value=\"$thisweightshippingcost\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			$previousweightshippinglevel = $thisweightshippinglevel;
		}
	}
	if (!empty($weightshipping)) $weightshippingnumber++;
	$weightshippinglevelstring .= "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\" colspan=\"2\">".WEIGHTFROM." $previousweightshippinglevel ".POUNDSUPTO." <input type=\"text\" name=\"weightshippinglevel{$weightshippingnumber}\" size=\"6\"> ".POUNDSEQUALSHIPPING.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"weightshippingcost{$weightshippingnumber}\" size=\"6\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
}

// Handle price shipping levels...
$priceshippinglevelstring = "";
$npriceshipping = "";
if (!empty($priceshippinglevel0)) {
	foreach ($_POST as $key=>$value) {
		if (strstr($key,"priceshippinglevel")) {
			$thislevel = str_replace("priceshippinglevel","",$key);
			$thispriceshippinglevel = $value;
			$priceshippingcostvariablename = "priceshippingcost$thislevel";
			$thispriceshippingcost = $$priceshippingcostvariablename;
			if (is_numeric($thispriceshippingcost) && is_numeric($thispriceshippinglevel)) {
				$thispriceshippinglevel = number_format($thispriceshippinglevel,2,'.','');
				$thispriceshippingcost = number_format($thispriceshippingcost,2,'.','');
				if (!empty($npriceshipping)) $npriceshipping .= "|";
				$npriceshipping .= "$thispriceshippinglevel:$thispriceshippingcost";
			}
		}
	}
} else {
	$priceshippinglevels = explode("|",$priceshipping);
	$previouspriceshippinglevel = "0.00";
	foreach ($priceshippinglevels as $priceshippingnumber=>$thispriceshipping) {
		if (!empty($thispriceshipping) && strstr($thispriceshipping,":")) {
			$thispriceshippingarray = explode(":",$thispriceshipping);
			$thispriceshippinglevel = $thispriceshippingarray[0];
			$thispriceshippingcost = $thispriceshippingarray[1];
			$priceshippinglevelstring .= "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\" colspan=\"2\">".PRICEFROM." ".$currencysymbols[$ashopcurrency]["pre"]."$previouspriceshippinglevel".$currencysymbols[$ashopcurrency]["post"]." ".UPTO." ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"priceshippinglevel{$priceshippingnumber}\" size=\"6\" value=\"$thispriceshippinglevel\">".$currencysymbols[$ashopcurrency]["post"]." = ".SHIPPING.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"priceshippingcost{$priceshippingnumber}\" size=\"6\" value=\"$thispriceshippingcost\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			$previouspriceshippinglevel = $thispriceshippinglevel;
		}
	}
	if (!empty($priceshipping)) $priceshippingnumber++;
	$priceshippinglevelstring .= "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\" colspan=\"2\">".PRICEFROM." ".$currencysymbols[$ashopcurrency]["pre"]."$previouspriceshippinglevel".$currencysymbols[$ashopcurrency]["post"]." ".UPTO." ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"priceshippinglevel{$priceshippingnumber}\" size=\"6\">".$currencysymbols[$ashopcurrency]["post"]." = ".SHIPPING.": ".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"priceshippingcost{$priceshippingnumber}\" size=\"6\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
}

// Handle Canada sales tax table...
if ($salestaxtype == "cancstpst") {
	$cantaxtable = "
	<tr><td align=\"right\" class=\"formlabel\">&nbsp;</td><td><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td class=\"formlabel\" width=\"33%\" align=\"left\">".GSTHST."</td><td class=\"formlabel\" width=\"33%\" align=\"left\">".PST."</td><td class=\"formlabel\" width=\"33%\" align=\"left\">".PSTCOMP."</td></tr></table></td></tr>
	";
	if (!empty($hstpercentage) && strstr($hstpercentage,"|")) {
		$cantaxarray = explode("|",$hstpercentage);
		if (is_array($cantaxarray)) foreach ($cantaxarray as $cantaxarraypart) {
			$thisprovincearray = explode(":",$cantaxarraypart);
			$thisprovince = $thisprovincearray[0];
			foreach ($canprovinces as $longprovince => $shortprovince) if ($thisprovince == $shortprovince) $thisprovincename = $longprovince;
			$thisprovincepstcomp = $thisprovincearray[3];
			$cantaxtable .= "<tr><td align=\"right\" class=\"formlabel\">$thisprovincename</td><td><table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td class=\"formlabel\" width=\"33%\" align=\"left\"><input type=\"text\" name=\"gst{$thisprovince}\" size=\"3\" value=\"{$thisprovincearray[1]}\">% </td><td class=\"formlabel\" width=\"33%\" align=\"left\"><input type=\"text\" name=\"pst{$thisprovince}\" size=\"3\" value=\"{$thisprovincearray[2]}\">% </td><td class=\"formlabel\" width=\"33%\" align=\"left\"><input type=\"checkbox\" name=\"pstcom{$thisprovince}\""; if ($thisprovincepstcomp == "1") $cantaxtable .= " checked"; $cantaxtable .= "></td></tr></table></td></tr>
			";
		}
	}
}

// Convert mass maill throttle to seconds...
$massmailthrottle = $massmailthrottle/1000000;

// Change administrators password...
$passworderrorstring = "";
if ($changeconfig && $oldpassword && $newpassword1 && $newpassword2) {
	$sql = "SELECT password FROM user WHERE username = 'ashopadmin'";
	$result = @mysqli_query($db, $sql);
	$correctoldpassword = @mysqli_result($result,0,"password");
	$passcheck = $passhasher->CheckPassword($oldpassword, $correctoldpassword);
	if (!$passcheck) $passworderrorstring = "?passworderror=old";
	if ($newpassword1 != $newpassword2) $passworderrorstring = "?passworderror=new";
	if (!$passworderrorstring) {
		$passhash = $passhasher->HashPassword($newpassword1);
		$sql = "UPDATE user SET password='$passhash' WHERE username='ashopadmin'";
		$result = @mysqli_query($db, $sql);
		$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		@ashop_mail("$ashopemail",un_html($ashopname)." - ".ADMINPASSWORDCHANGED,THEADMINPASSWORDAT." $ashopurl ".HASBEENCHANGED.": {$_SERVER["REMOTE_ADDR"]}!","$headers");
	}
}

if (strpos($header, "body") != 0) {
	$newheader = substr($header,1,strpos($header, "body")+3);
	$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "body")+4,strlen($header));
} else {
	$newheader = substr($header,1,strpos($header, "BODY")+3);
	$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "BODY")+4,strlen($header));
}

if (!$changeconfig || $addcountry) {
        if ($param == "layout") echo "$newheader<script language=\"JavaScript\">
		function uploadmessage() 
		{
		  if (document.configurationform.imgfile.value != '' || document.configurationform.mobimgfile.value != '') w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
        </script>";
		else echo "$header";
		echo "
<div class=\"heading\">";
		switch ($param) {
			case "shop":
				echo SHOPPARAMETERS;
			    break;
			case "layout":
				echo LAYOUT;
				break;
			case "affiliate":
				echo AFFILIATEPROGRAM;
			    break;
			case "shipping":
				echo SHIPPING;
			    break;
			case "mall":
				echo SHOPPINGMALL;
			    break;
			case "taxes":
				echo TAXES;
			    break;
		}
		echo "</div><table align=\"center\" cellpadding=\"10\"><tr><td>
        <form action=\"configure.php?changeconfig=1\" method=\"post\" name=\"configurationform\" enctype=\"multipart/form-data\">
		<table width=\"600\" align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\" bgcolor=\"#F0F0F0\">";
}

$discountlvl1 = explode ("|", $discountlevel1);
$discountlvl2 = explode ("|", $discountlevel2);
$discountlvl3 = explode ("|", $discountlevel3);

if ($param == "shop") {
	if (!$changeconfig) {
		// Get list of languages...
		$langlist = "";
		$findfile = opendir("$ashoppath/language");
		while ($foundfile = readdir($findfile)) {
			if(is_dir("$ashoppath/language/$foundfile") && strlen($foundfile) == 2 && $foundfile != ".." && file_exists("$ashoppath/language/$foundfile/lang.cfg.php")) {
				$fp = fopen ("$ashoppath/language/$foundfile/lang.cfg.php","r");
				while (!feof ($fp)) {
					$fileline = fgets($fp, 4096);
					if (strstr($fileline,"\$langname")) $langnamestring = $fileline;
					if (strstr($fileline,"\$langredirect")) $langredirectstring = $fileline;
				}
				fclose($fp);
				eval ($langnamestring);
				if ($language == $foundfile) eval ($langredirectstring);
				$langlist .= "<option value=\"$foundfile\"";
				if ($defaultlanguage == $foundfile) $langlist .= " selected";
				$langlist .= ">$langname</option>";
			}
		}

		// Get context help for this page...
		$contexthelppage = "shopparameters";
		include "help.inc.php";
		echo "<input type=\"hidden\" name=\"param\" value=\"shop\">
		<tr><td width=\"45%\" class=\"formtitle\">".CHANGEPASSWORD." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td><td width=\"55%\"><span class=\"sm\">[".LEAVEBLANKTOKEEP."]</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><span >".OLDPASSWORD.": </td><td><input type=\"password\" name=\"oldpassword\" size=\"25\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".NEWPASSWORD.": </td><td><input type=\"password\" name=\"newpassword1\" size=\"25\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".CONFIRM.": </td><td><input type=\"password\" name=\"newpassword2\" size=\"25\"></td></tr></table>
<table width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">
		<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".CONTACTINFO." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></td></tr>
<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SHOPNAME.":</td><td><input type=\"text\" name=\"nashopname\" size=\"35\" value=\"$ashopname\"><script language=\"JavaScript\">document.configurationform.nashopname.focus();</script></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SHOPADDRESS.": </td><td><input type=\"text\" name=\"nashopaddress\" size=\"35\" value=\"$ashopaddress\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SHOPPHONE.": </td><td><input type=\"text\" name=\"nashopphone\" size=\"35\" value=\"$ashopphone\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".EMAIL.": </td><td><input type=\"text\" name=\"nashopemail\" size=\"35\" value=\"$ashopemail\"></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td colspan=\"2\" class=\"formtitle\">".CATALOGOPTIONS."</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".DEFAULTLANG.":</td><td><select name=\"ndefaultlanguage\">$langlist</select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> ".CURRENCY.":</td><td><select name=\"nashopcurrency\"><option value=\"usd\""; if ($ashopcurrency == "usd") echo " selected"; echo ">".USDOLLARS."</option><option value=\"cad\""; if ($ashopcurrency == "cad") echo " selected"; echo ">".CANDOLLARS."</option><option value=\"aud\""; if ($ashopcurrency == "aud") echo " selected"; echo ">".AUSDOLLARS."</option><option value=\"gbp\""; if ($ashopcurrency == "gbp") echo " selected"; echo ">Pounds Sterling</option><option value=\"eur\""; if ($ashopcurrency == "eur") echo " selected"; echo ">".EURO."</option><option value=\"nok\""; if ($ashopcurrency == "nok") echo " selected"; echo ">".NORKRONOR."</option><option value=\"sek\""; if ($ashopcurrency == "sek") echo " selected"; echo ">".SWEDKRONOR."</option><option value=\"zar\""; if ($ashopcurrency == "zar") echo " selected"; echo ">".SARAND."</option><option value=\"btc\""; if ($ashopcurrency == "btc") echo " selected"; echo ">".BITCOINS."</option><option value=\"jpy\""; if ($ashopcurrency == "jpy") echo " selected"; echo ">".JAPANYEN."</option><option value=\"nzd\""; if ($ashopcurrency == "nzd") echo " selected"; echo ">".NEWZDOLLARS."</option><option value=\"twd\""; if ($ashopcurrency == "twd") echo " selected"; echo ">".TAIWANDOLLARS."</option><option value=\"sgd\""; if ($ashopcurrency == "sgd") echo " selected"; echo ">".SINGADOLLARS."</option><option value=\"mxn\""; if ($ashopcurrency == "mxn") echo " selected"; echo ">Mexican Peso</option><option value=\"tec\""; if ($ashopcurrency == "tec") echo " selected"; echo ">".TECREDITS."</option></select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".THOUSANDSEPARATOR.":</td><td><select name=\"nthousandchar\"><option value=\"\""; if ($thousandchar == "") echo " selected"; echo ">".NONE."</option><option value=\",\""; if ($thousandchar == ",") echo " selected"; echo ">".COMMA."</option><option value=\".\""; if ($thousandchar == ".") echo " selected"; echo ">".DOT."</option><option value=\" \""; if ($thousandchar == " ") echo " selected"; echo ">".SPACE."</option></select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".DECIMALSEPARATOR.":</td><td><select name=\"ndecimalchar\"><option value=\",\""; if ($decimalchar == ",") echo " selected"; echo ">".COMMA."</option><option value=\".\""; if ($decimalchar == ".") echo " selected"; echo ">".DOT."</option></select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".NUMBEROFDECIMALS.":</td><td><select name=\"nshowdecimals\"><option value=\"0\""; if ($showdecimals == "0") echo " selected"; echo ">0</option><option value=\"1\""; if ($showdecimals == "1") echo " selected"; echo ">1</option><option value=\"2\""; if ($showdecimals == "2") echo " selected"; echo ">2</option><option value=\"3\""; if ($showdecimals == "3") echo " selected"; echo ">3</option><option value=\"4\""; if ($showdecimals == "4") echo " selected"; echo ">4</option></select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip3b');\" onmouseout=\"return nd();\"></a> <a href=\"$help3b\" class=\"helpnav2\" target=\"_blank\">".CATALOGMODE.":</a></td><td><select name=\"nshoppingcart\"><option value=\"0\""; if ($shoppingcart == "0") echo " selected"; echo ">".SINGLEPRODUCTORDER."
		<option value=\"1\""; if ($shoppingcart == "1") echo " selected"; echo ">".SHOPPINGCART."
		<option value=\"2\""; if ($shoppingcart == "2") echo " selected"; echo ">".SINGLEPRODUCTCART."
		<option value=\"3\""; if ($shoppingcart == "3") echo " selected"; echo ">".PRICELIST."
		</select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image16','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image16\" align=\"absmiddle\" onclick=\"return overlib('$tip3i');\" onmouseout=\"return nd();\"></a> ".SEOURLS.":</td><td><select name=\"nseourls\"><option value=\"1\""; if ($seourls == "1") echo " selected"; echo ">on</option><option value=\"0\""; if ($seourls == "0") echo " selected"; echo ">off</option>";
		echo "</select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip3c');\" onmouseout=\"return nd();\"></a> <a href=\"$help3c\" class=\"helpnav2\" target=\"_blank\">".ENABLEWISHLISTS.":</a></td><td><input type=\"checkbox\" name=\"nenablecustomerlogin\""; if ($enablecustomerlogin == "1") echo "checked"; echo "></td></tr>
<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip3e');\" onmouseout=\"return nd();\"></a> ".LINKFROMTHANKYOU.":</td><td><input type=\"text\" name=\"norderpagelink\" size=\"35\" value=\"$orderpagelink\"></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip3g');\" onmouseout=\"return nd();\"></a> ".ITEMSPERPAGE.":</td><td><input type=\"text\" name=\"ndisplayitems\" size=\"2\" value=\"$displayitems\"></td></tr>		
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image24','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image24\" align=\"absmiddle\" onclick=\"return overlib('$tip3f');\" onmouseout=\"return nd();\"></a> <a href=\"$help3f\" class=\"helpnav2\" target=\"_blank\">".ASHOPAFFILIATEID.":</a></td><td><input type=\"text\" name=\"nashopaffiliateid\" size=\"2\" value=\"$ashopaffiliateid\"></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".RECEIPTFORMAT.":</a></td><td><select name=\"nreceiptformat\"><option value=\"html\""; if ($receiptformat == "html") echo " selected"; echo ">HTML"; /* <option value=\"pdf\""; if ($receiptformat == "pdf") echo " selected"; echo ">PDF */ echo "<option value=\"txt\""; if ($receiptformat == "txt") echo " selected"; echo ">".PLAINTEXT."</select></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".COLLECT.":</a></td><td class=\"formlabel\"><select name=\"ncollectcustomerinfo\"><option value=\"1\""; if ($collectcustomerinfo == "1") echo " selected"; echo ">".FULLCONTACTINFO."<option value=\"0\""; if (!$collectcustomerinfo) echo " selected"; echo ">".ONLYNAMEEMAIL."</select> ".FORGIFTS."</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".UPSELL.":</a></td><td class=\"formlabel\"><select name=\"nupsellitems\"><option value=\"0\""; if ($upsellitems == "0") echo " selected"; echo ">0<option value=\"1\""; if ($upsellitems == "1") echo " selected"; echo ">1<option value=\"2\""; if ($upsellitems == "2") echo " selected"; echo ">2</select> ".ITEMSONCHECKOUT."</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".SHOWEUCOOKIEWARNING.":</a></td><td><input type=\"checkbox\" name=\"neucookiecheck\""; if ($eucookiecheck == "1") echo "checked";
		echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CUSTOMERREGISTRATIONREQUIRED.":</a></td><td><input type=\"checkbox\" name=\"ncustomermustregister\""; if ($customermustregister == "1") echo "checked";
		echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".CUSTOMEREMAILCONFIRMATION.":</a></td><td><input type=\"checkbox\" name=\"ncustomerconfirm\""; if ($customerconfirm == "1") echo "checked";
		echo "></td></tr>";
		if (!empty($autoresponderid) && is_numeric($autoresponderid)) {
			echo "<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".ACTIVATEAUTORESPONDER.":</a></td><td><input type=\"checkbox\" name=\"nactivateautoresponder\""; if ($activateautoresponder == "1") echo "checked"; echo "></td></tr>";
			$sql = "SELECT * FROM autoresponders ORDER BY name";
			$responderresult = @mysqli_query($db, $sql);
			if (@mysqli_num_rows($responderresult)) {
				echo "<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".NEWSLETTERAUTORESPONDER.":</td><td class=\"formlabel\"><select name=\"nnewsresponderid\"><option value=\"0\">none</option>";
				for ($i = 0; $i < @mysqli_num_rows($responderresult); $i++) {
					$responderid = @mysqli_result($responderresult, $i, "responderid");
					$respondername = @mysqli_result($responderresult, $i, "name");
					echo "<option value=\"$responderid\"";
					if ($newsresponderid == $responderid) echo " selected";
					echo ">$respondername</option>";
				}
				echo "</select></td></tr>";
			}
		}
		echo "<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image15','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image15\" align=\"absmiddle\" onclick=\"return overlib('$tip11');\" onmouseout=\"return nd();\"></a> ".SHOWDISCOUNTBOXONCHECKOUT.":</a></td><td><input type=\"checkbox\" name=\"ndiscountoncheckout\""; if ($discountoncheckout == "1") echo "checked";
		echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image23','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image23\" align=\"absmiddle\" onclick=\"return overlib('$tip12');\" onmouseout=\"return nd();\"></a> ".DISCOUNTMESSAGE.":</a></td><td><textarea name=\"ndiscountmessage\" cols=\"30\" rows=\"5\">$discountmessage</textarea></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image17','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image17\" align=\"absmiddle\" onclick=\"return overlib('$tip13');\" onmouseout=\"return nd();\"></a> ".LOYALTYREWARD.":</a></td><td class=\"formlabel\"><input type=\"text\" name=\"nvirtualcashpercent\" size=\"2\" value=\"$virtualcashpercent\">% ".VIRTUALCASHPERORDER."</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image19','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image19\" align=\"absmiddle\" onclick=\"return overlib('$tip15');\" onmouseout=\"return nd();\"></a> ".NEWSFEED.":</td><td><input type=\"text\" name=\"nashopnewsfeed\" size=\"35\" value=\"$ashopnewsfeed\"></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MASSMAILTHROTTLE.":</td><td><input type=\"text\" name=\"nmassmailthrottle\" size=\"2\" value=\"$massmailthrottle\"> ".SECONDS."</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".TELESIGNINTEGRATION."</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image22','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image22\" align=\"absmiddle\" onclick=\"return overlib('$tip3h');\" onmouseout=\"return nd();\"></a> <a href=\"$help3h\" class=\"helpnav2\" target=\"_blank\">".TELESIGNCUSTOMERID.":</a></td><td><input type=\"text\" name=\"ntelesignid\" size=\"35\" value=\"$telesignid\"></td></tr><tr bgcolor=\"#D0D0D0\"><td>&nbsp;</td><td align=\"top\"><span class=\"sm\">".LEAVEBLANKTODEACTIVATETELESIGN."</span></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".AUTHENTICATIONID.":</a></td><td><input type=\"text\" name=\"ntelesignauthid\" size=\"35\" value=\"$telesignauthid\"></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td colspan=\"2\" class=\"formtitle\">".MINFRAUDINTEGRATION."</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image20','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image20\" align=\"absmiddle\" onclick=\"return overlib('$tip18');\" onmouseout=\"return nd();\"></a> <a href=\"$help3h\" class=\"helpnav2\" target=\"_blank\">".MINFRAUDLICENSEKEY.":</a></td><td><input type=\"text\" name=\"nminfraudkey\" size=\"35\" value=\"$minfraudkey\"></td></tr><tr bgcolor=\"#F0F0F0\"><td>&nbsp;</td><td align=\"top\"><span class=\"sm\">".LEAVEBLANKTODEACTIVATEMINFRAUD."</span></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MINFRAUDTHRESHOLD.":</a></td><td><input type=\"text\" name=\"nminfraudthreshold\" size=\"3\" value=\"$minfraudthreshold\"><span class=\"sm\"> %</span></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image21','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image21\" align=\"absmiddle\" onclick=\"return overlib('$tip19');\" onmouseout=\"return nd();\"></a> <a href=\"$help3h\" class=\"helpnav2\" target=\"_blank\">".MINFRAUDGEOIPLICENSEKEY.":</a></td><td><input type=\"text\" name=\"nminfraudgeoipkey\" size=\"35\" value=\"$minfraudgeoipkey\"></td></tr><tr bgcolor=\"#F0F0F0\"><td>&nbsp;</td><td align=\"top\"><span class=\"sm\">".LEAVEBLANKTODEACTIVATEMINFRAUDGEOIP."</span></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".SAASUINTEGRATION."</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image14','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image14\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a> <a href=\"$help10\" class=\"helpnav2\" target=\"_blank\">".WSACCESSKEY.":</a></td><td><input type=\"text\" name=\"nsaasuwsaccesskey\" size=\"35\" value=\"$saasuwsaccesskey\"></td></tr><tr bgcolor=\"#D0D0D0\"><td>&nbsp;</td><td align=\"top\"><span class=\"sm\">".LEAVEBLANKTODEACTIVATESAASU."</span></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FILEID.":</a></td><td><input type=\"text\" name=\"nsaasufileid\" size=\"35\" value=\"$saasufileid\"></td></tr>";
		if ($saasuwsaccesskey && $saasufileid) {
			$saasutaxcodestring = ashop_saasu_gettaxcodes();
			if ($saasutaxcodestring) echo "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".TAXCODE.":</a></td><td><select name=\"nsaasutaxcode\">$saasutaxcodestring</select></td></tr>";
			$saasubankaccountstring = ashop_saasu_getbankaccounts();
			if ($saasubankaccountstring) echo "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".BANKACCOUNT.":</a></td><td>$saasubankaccountstring</td></tr>";
		} else {
			echo "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".TAXCODE.":</a></td><td class=\"formlabel\">".WSACCESSKEYMUSTBESET."</td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".BANKACCOUNT.":</a></td><td class=\"formlabel\">".ACTIVATESETTINGS."</td></tr>";
		}
		echo "<tr bgcolor=\"#F0F0F0\"><td><a href=\"$help4\" class=\"helpnav\" target=\"_blank\">".WHOLESALECATALOG."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a></td><td><input type=\"checkbox\" name=\"nwholesalecatalog\""; if ($wholesalecatalog == "1") echo "checked"; echo "><span class=\"sm\">".CHECKTOENABLEWHOLESALE."</span></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".PRICELEVELS.":</td><td  class=\"formlabel\"><input type=\"text\" name=\"npricelevels\" size=\"3\" value=\"$pricelevels\"></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".PRODUCTFILEDOWNLOAD." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".ALLOWDOWNLOADFOR.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nalloweddownloaddays\" size=\"5\" value=\"$alloweddownloaddays\"> ".DAYS." <span class=\"sm\">[0 = ".UNLIMITED."]</span></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".ALLOW.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nalloweddownloads\" size=\"5\" value=\"$alloweddownloads\"> ".DOWNLOADSPERPRODUCT." <span class=\"sm\">[0 = ".UNLIMITED."]</span></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".BROWSERSAVEDIALOGUE.":</td><td class=\"formlabel\"><select name=\"ndownloadsavedialogue\"><option value=\"on\""; if ($downloadsavedialogue == "on") echo " selected"; echo ">".ON."<option value=\"off\""; if ($downloadsavedialogue == "off") echo " selected"; echo ">".OFF."</select></td></tr>		
		<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\"><a href=\"$help6\" class=\"helpnav\" target=\"_blank\">".EMAILEDKEYCODES."</a></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a> ".RANDOMKEYCODEDELIVERY.":</td><td><input type=\"checkbox\" name=\"nrandomkeycodes\""; if ($randomkeycodes == "1") echo "checked"; echo "><span class=\"sm\"> ".UNCHECKTOUSEORIGINAL."</span></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td colspan=\"2\" class=\"formtitle\">".SEOSETTINGS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image18','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image18\" align=\"absmiddle\" onclick=\"return overlib('$tip14');\" onmouseout=\"return nd();\"></a></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".METAKEYWORDS.":</td><td class=\"formlabel\"><textarea name=\"nashopmetakeywords\" cols=\"30\" rows=\"5\">$ashopmetakeywords</textarea></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".METADESCRIPTION.":</td><td class=\"formlabel\"><textarea name=\"nashopmetadescription\" cols=\"30\" rows=\"5\">$ashopmetadescription</textarea></td></tr>
		";
		} else {
		$nashopname = htmlentities(stripslashes($nashopname), ENT_QUOTES);
		$nashopphone = htmlentities(stripslashes($nashopphone), ENT_QUOTES);
		$nashopaddress = htmlentities(stripslashes($nashopaddress), ENT_QUOTES);
		if ($nactivateautoresponder == "on") $nactivateautoresponder = "1";
		else $nactivateautoresponder = "0";
		if ($nenablecustomerlogin == "on") $nenablecustomerlogin = "1";
		else $nenablecustomerlogin = "0";
		if ($ncustomerconfirm == "on") $ncustomerconfirm = "1";
		else $ncustomerconfirm = "0";
		if ($ncustomermustregister == "on") $ncustomermustregister = "1";
		else $ncustomermustregister = "0";		
		if ($nwholesalecatalog == "on") $nwholesalecatalog = "1";
		else $nwholesalecatalog = "0";
		if ($npricelevels == "0") $nwholesalecatalog = "0";
		if ($nrandomkeycodes == "on") $nrandomkeycodes = "1";
		else $nrandomkeycodes = "0";
		if ($ndiscountoncheckout == "on") $ndiscountoncheckout = "1";
		else $ndiscountoncheckout = "0";
		if ($neucookiecheck == "on") $neucookiecheck = "1";
		else $neucookiecheck = "0";	
		//if ($nshoppingcart != "1" && $nshoppingcart != "2") $nupsellitems = "0";
		$nmassmailthrottle = $nmassmailthrottle*1000000;
		if (!empty($nvirtualcashpercent) && is_numeric($nvirtualcashpercent)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nvirtualcashpercent' WHERE prefname='virtualcashpercent'");
		else @mysqli_query($db, "UPDATE preferences SET prefvalue='' WHERE prefname='virtualcashpercent'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nseourls' WHERE prefname='seourls'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$neucookiecheck' WHERE prefname='eucookiecheck'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nactivateautoresponder' WHERE prefname='activateautoresponder'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nnewsresponderid' WHERE prefname='newsresponderid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopname' WHERE prefname='ashopname'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopphone' WHERE prefname='ashopphone'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopmetakeywords' WHERE prefname='ashopmetakeywords'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopmetadescription' WHERE prefname='ashopmetadescription'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopnewsfeed' WHERE prefname='ashopnewsfeed'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopnewsfeed' WHERE prefname='ashopnewsfeed'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmassmailthrottle' WHERE prefname='massmailthrottle'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nthousandchar' WHERE prefname='thousandchar'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshowdecimals' WHERE prefname='showdecimals'");
		if ($ashopemail != $nashopemail) {
			$headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			@ashop_mail("$ashopemail",un_html($ashopname)." - main shopping cart email changed","The main email address of your shopping cart at $ashopurl has been changed. If you have changed it yourself you can discard this message. In other case you may have had an unauthorized login to your administration panel by IP: {$_SERVER["REMOTE_ADDR"]}! The email is now set to: $nashopemail.","$headers");
		}
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopemail' WHERE prefname='ashopemail'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopaddress' WHERE prefname='ashopaddress'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopcurrency' WHERE prefname='ashopcurrency'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndefaultlanguage' WHERE prefname='defaultlanguage'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$timezoneoffset' WHERE prefname='timezoneoffset'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshoppingcart' WHERE prefname='shoppingcart'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nenablecustomerlogin' WHERE prefname='enablecustomerlogin'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncustomerconfirm' WHERE prefname='customerconfirm'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncustomermustregister' WHERE prefname='customermustregister'");	
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nwholesalecatalog' WHERE prefname='wholesalecatalog'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nalloweddownloaddays' WHERE prefname='alloweddownloaddays'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nalloweddownloads' WHERE prefname='alloweddownloads'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndownloadsavedialogue' WHERE prefname='downloadsavedialogue'");		
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$norderpagelink' WHERE prefname='orderpagelink'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nrandomkeycodes' WHERE prefname='randomkeycodes'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$npricelevels' WHERE prefname='pricelevels'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndiscountlevel1perc|$ndiscountlevel1amount' WHERE prefname='discountlevel1'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndiscountlevel2perc|$ndiscountlevel2amount' WHERE prefname='discountlevel2'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndiscountlevel3perc|$ndiscountlevel3amount' WHERE prefname='discountlevel3'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopaffiliateid' WHERE prefname='ashopaffiliateid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nreceiptformat' WHERE prefname='receiptformat'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntelesignid' WHERE prefname='telesignid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntelesignauthid' WHERE prefname='telesignauthid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nminfraudkey' WHERE prefname='minfraudkey'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nminfraudgeoipkey' WHERE prefname='minfraudgeoipkey'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nminfraudthreshold' WHERE prefname='minfraudthreshold'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsaasuwsaccesskey' WHERE prefname='saasuwsaccesskey'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsaasufileid' WHERE prefname='saasufileid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsaasubankaccountid' WHERE prefname='saasubankaccountid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsaasutaxcode' WHERE prefname='saasutaxcode'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndiscountoncheckout' WHERE prefname='discountoncheckout'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndiscountmessage' WHERE prefname='discountmessage'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndisplayitems' WHERE prefname='displayitems'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncollectcustomerinfo' WHERE prefname='collectcustomerinfo'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsellitems' WHERE prefname='upsellitems'");
		// Delete payment options that can not be used with the selected currency...
		if ($nashopcurrency != "usd") $pathprefix = $nashopcurrency; else $pathprefix = "";
		$result = @mysqli_query($db, "SELECT * FROM payoptions");
		while ($row = @mysqli_fetch_array($result)) {
			if (!file_exists("$ashoppath/admin/gateways$pathprefix/".$row["gateway"].".gw")) @mysqli_query($db, "DELETE FROM payoptions WHERE payoptionid='".$row["payoptionid"]."'");
		}
	}
}

if ($param == "mall") {
	if ($digitalmall != "ON") {
		header ("Location: index.php");
		exit;
	}
	// Get subscription fees...
	$hostingresult = @mysqli_query($db, "SELECT * FROM product WHERE prodtype='mallfee'");
	$hostingrow = @mysqli_fetch_array($hostingresult);
	$hostingid = $hostingrow["productid"];
	$feeslabel = $hostingrow["name"];
	if (!$changeconfig) {
		$setupfee = $hostingrow["price"];
		$monthlyfee = $hostingrow["recurringprice"];
		if ((!empty($setupfee) || !empty($monthlyfee)) && empty($feeslabel)) $feeslabel = SHOPPINGMALLFEE;
		// Get context help for this page...
		$contexthelppage = "shopparameters";
		include "help.inc.php";
		echo "<input type=\"hidden\" name=\"param\" value=\"mall\">
		<tr bgcolor=\"#F0F0F0\"><td colspan=\"2\" class=\"formtitle\">".SHOPPINGMALL." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a></td></tr>
		<tr><td valign=\"top\" align=\"right\" class=\"formlabel\" style=\"padding-top: 10px;\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image16\" align=\"absmiddle\" onclick=\"return overlib('$tip16');\" onmouseout=\"return nd();\"></a> ".SHOPPINGMALLTEXT.":</td><td class=\"formlabel\"><textarea name=\"nshoppingmallinfo\" cols=\"30\" rows=\"5\">$shoppingmallinfo</textarea><script language=\"JavaScript\">document.configurationform.nshoppingmallinfo.focus();</script></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".DEFAULTCOMMISSIONLEVEL.":</td><td  class=\"formlabel\"><input type=\"text\" name=\"nmemberpercent\" size=\"5\" value=\"$memberpercent\">%</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERCATALOGS.":</td><td  class=\"formlabel\"><input type=\"checkbox\" name=\"nmembershops\""; if ($membershops) echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERPAYOPTIONS.":</td><td  class=\"formlabel\"><input type=\"checkbox\" name=\"nmemberpayoptions\""; if ($memberpayoptions) echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERPRODUCTMANAGEMENT.":</td><td  class=\"formlabel\"><select name=\"nmemberprodmanage\"><option value=\"none\""; if (!$memberprodmanage && !$advancedmallmode) echo " selected"; echo ">".DEACTIVATED."</option><option value=\"simple\""; if ($memberprodmanage && !$advancedmallmode) echo " selected"; echo ">".SIMPLEMODE."</option><option value=\"advanced\""; if ($memberprodmanage && $advancedmallmode) echo " selected"; echo ">".ADVANCEDMODE."</option></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERHOSTINGLIMIT.":</td><td  class=\"formlabel\"><input type=\"text\" name=\"nmemberuploadsize\" size=\"5\" value=\"$memberuploadsize\"> MB <span class=\"sm\"> [0 = ".DISABLEHOSTING."]</span></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERACCOUNTSDEFAULTACTIVE.":</td><td  class=\"formlabel\"><input type=\"checkbox\" name=\"nautoapprovemembers\""; if ($autoapprovemembers) echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERPRODUCTSDEFAULTACTIVE.":</td><td  class=\"formlabel\"><input type=\"checkbox\" name=\"nmemberactivate\""; if ($memberactivate) echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".DISPLAYCUSTOMERINFO.":</td><td  class=\"formlabel\"><input type=\"checkbox\" name=\"ndmshowcustomers\""; if ($dmshowcustomers) echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MEMBERPRODUCTTEMPLATE.":</td><td  class=\"formlabel\"><textarea name=\"nmemberproducttemplate\" cols=\"30\" rows=\"5\">$memberproducttemplate</textarea></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".FEESLABEL.":</td><td  class=\"formlabel\"><input type=\"text\" name=\"nfeeslabel\" size=\"35\" value=\"$feeslabel\"> <span class=\"sm\"> ".OPTIONAL."</span></td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".SETUPFEE.":</td><td  class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nsetupfee\" size=\"5\" value=\"$setupfee\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>
		<tr bgcolor=\"#F0F0F0\"><td align=\"right\" class=\"formlabel\">".MONTHLYFEE.":</td><td  class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nmonthlyfee\" size=\"5\" value=\"$monthlyfee\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr>
		";
		} else {
		if ($nmembershops == "on") $nmembershops = "1";
		else $nmembershops = "0";
		if ($nmemberpayoptions == "on") $nmemberpayoptions = "1";
		else $nmemberpayoptions = "0";
		if ($nmemberprodmanage == "none") {
			$nmemberprodmanage = "0";
			$nadvancedmallmode = "0";
		} else if ($nmemberprodmanage == "simple") {
			$nmemberprodmanage = "1";
			$nadvancedmallmode = "0";
		} else if ($nmemberprodmanage == "advanced") {
			$nmemberprodmanage = "1";
			$nadvancedmallmode = "1";
		}
		if ($nautoapprovemembers == "on") $nautoapprovemembers = "1";
		else $nautoapprovemembers = "0";
		if ($nmemberactivate == "on") $nmemberactivate = "1";
		else $nmemberactivate = "0";
		if ($ndmshowcustomers == "on") $ndmshowcustomers = "1";
		else $ndmshowcustomers = "0";
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmemberproducttemplate' WHERE prefname='memberproducttemplate'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndmshowcustomers' WHERE prefname='dmshowcustomers'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmemberpercent' WHERE prefname='memberpercent'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmemberuploadsize' WHERE prefname='memberuploadsize'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmembershops' WHERE prefname='membershops'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmemberprodmanage' WHERE prefname='memberprodmanage'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmemberpayoptions' WHERE prefname='memberpayoptions'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nadvancedmallmode' WHERE prefname='advancedmallmode'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nautoapprovemembers' WHERE prefname='autoapprovemembers'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmemberactivate' WHERE prefname='memberactivate'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshoppingmallinfo' WHERE prefname='shoppingmallinfo'");

		// Add or update hosting fee product...
		if (@mysqli_num_rows($hostingresult)) {
			if (empty($nsetupfee) && empty($nmonthlyfee)) {
				@mysqli_query($db, "DELETE FROM product WHERE prodtype='mallfee'");
				@mysqli_query($db, "DELETE FROM parameters WHERE productid='$hostingid'");
			} else {
				if (empty($nsetupfee)) $nsetupfee = "0.00";
				if (empty($nmonthlyfee)) $nmonthlyfee = "0.00";
				@mysqli_query($db, "UPDATE product SET price='$nsetupfee', recurringprice='$nmonthlyfee', name='$nfeeslabel' WHERE prodtype='mallfee'");
				$idparamresult = @mysqli_query($db, "SELECT parameterid FROM parameters WHERE productid='$hostingid'");
				if (!@mysqli_num_rows($idparamresult)) @mysqli_query($db, "INSERT INTO parameters (productid,caption) VALUES ('$hostingid','ID')");
			}
		} else if (!empty($nsetupfee) || !empty($nmonthlyfee)) {
			if (empty($nsetupfee)) $nsetupfee = "0.00";
			if (empty($nmonthlyfee)) $nmonthlyfee = "0.00";
			@mysqli_query($db, "INSERT INTO product (userid,name,price,recurringprice,prodtype) VALUES ('1','$nfeeslabel','$nsetupfee','$nmonthlyfee','mallfee')");
			$hostingid = @mysqli_insert_id($db);
			@mysqli_query($db, "INSERT INTO parameters (productid,caption) VALUES ('$hostingid','ID')");
		}
	}
}

if ($param == "affiliate") {
	if (!$changeconfig) {
		// Get context help for this page...
		$contexthelppage = "affiliateconfiguration";
		include "help.inc.php";
		echo "<input type=\"hidden\" name=\"param\" value=\"affiliate\">
		<tr><td valign=\"top\" align=\"right\" class=\"formlabel\" style=\"padding-top: 10px;\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> <a href=\"$help1\" class=\"helpnav2\" target=\"_blank\">".AFFILIATETEXT.":</a></td><td class=\"formlabel\"><textarea name=\"naffiliateinfo\" cols=\"30\" rows=\"5\">$affiliateinfo</textarea><script language=\"JavaScript\">document.configurationform.naffiliateinfo.focus();</script></td></tr>
        <tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ".AFFILIATEEMAIL.":</td><td><input type=\"text\" name=\"naffiliaterecipient\" size=\"35\" value=\"$affiliaterecipient\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a> <a href=\"$help4\" class=\"helpnav2\" target=\"_blank\">".MULTITIER.":</td><td><input type=\"checkbox\" name=\"nsecondtieractivated\""; if ($secondtieractivated == "1") echo " checked"; echo "></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".MAXTIERS.":</td><td><input type=\"text\" name=\"nmaxaffiliatetiers\" size=\"3\" value=\"$maxaffiliatetiers\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".UPGRADEAFFILIATESAFTER.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nupgradeaffiliate\" size=\"3\" value=\"$upgradeaffiliate\"> ".ORDERS." <span class=\"sm\">[ 0 = ".DISABLE." ]</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".REFERRALLENGTH.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nreferrallength\" size=\"3\" value=\"$referrallength\"> ".DAYS." <span class=\"sm\">[ 0 = ".LIFETIME." ]</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".SHARELEADS.":</td><td><input type=\"checkbox\" name=\"nactivateleads\""; if ($activateleads == "1") echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".NORMAL."</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a> ".DEFAULTAFFILIATECOMMISSION.":</td><td><input type=\"text\" name=\"naffiliatepercent\" size=\"3\" value=\"$affiliatepercent\"> %</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".DEFAULTSECONDTIERCOMMISSION.":</td><td><input type=\"text\" name=\"nsecondtierpercent\" size=\"3\" value=\"$secondtierpercent\"> %</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".DEFAULTWHOLESALECOMMISSION.":</td><td><input type=\"text\" name=\"nwholesalepercent\" size=\"3\" value=\"$wholesalepercent\"> %</td></tr>
		<tr><td colspan=\"2\" class=\"formtitle\">".UPGRADED."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".DEFAULTAFFILIATECOMMISSION.":</td><td><input type=\"text\" name=\"naffiliatepercent2\" size=\"3\" value=\"$affiliatepercent2\"> %</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".DEFAULTSECONDTIERCOMMISSION.":</td><td><input type=\"text\" name=\"nsecondtierpercent2\" size=\"3\" value=\"$secondtierpercent2\"> %</td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".COMMISSIONONWHOLESALE.":</td><td><input type=\"checkbox\" name=\"nwholesaleaffiliate\""; if ($wholesaleaffiliate == "1") echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> ".EMAILCONFIRMATION.":</td><td><input type=\"checkbox\" name=\"naffiliateconfirm\""; if ($affiliateconfirm == "1") echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".REQUIREPAYPALID.":</td><td><input type=\"checkbox\" name=\"nrequirepaypalid\""; if ($requirepaypalid == "1") echo " checked"; echo "></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a> ".DEFAULTREDIRECTURL.":</td><td><input type=\"text\" name=\"naffiliateredirect\" size=\"35\" value=\"$affiliateredirect\"></td></tr>";
	} else {
		$naffiliateinfo = htmlentities(stripslashes($naffiliateinfo), ENT_QUOTES);
		if ($nsecondtieractivated == "on") $nsecondtieractivated = "1";
		else $nsecondtieractivated = "0";
		if ($nrequirepaypalid == "on") $nrequirepaypalid = "1";
		else $nrequirepaypalid = "0";
		if ($naffiliateconfirm == "on") $naffiliateconfirm = "1";
		else $naffiliateconfirm = "0";
		if ($nactivateleads == "on") $nactivateleads = "1";
		else $nactivateleads = "0";
		if ($nwholesaleaffiliate == "on") $nwholesaleaffiliate = "1";
		else $nwholesaleaffiliate = "0";
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$naffiliateinfo' WHERE prefname='affiliateinfo'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$naffiliaterecipient' WHERE prefname='affiliaterecipient'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$naffiliatepercent' WHERE prefname='affiliatepercent'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$naffiliatepercent2' WHERE prefname='affiliatepercent2'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsecondtieractivated' WHERE prefname='secondtieractivated'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsecondtierpercent' WHERE prefname='secondtierpercent'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsecondtierpercent2' WHERE prefname='secondtierpercent2'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nwholesalepercent' WHERE prefname='wholesalepercent'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nrequirepaypalid' WHERE prefname='requirepaypalid'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$naffiliateredirect' WHERE prefname='affiliateredirect'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$naffiliateconfirm' WHERE prefname='affiliateconfirm'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nmaxaffiliatetiers' WHERE prefname='maxaffiliatetiers'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupgradeaffiliate' WHERE prefname='upgradeaffiliate'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nactivateleads' WHERE prefname='activateleads'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nwholesaleaffiliate' WHERE prefname='wholesaleaffiliate'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nreferrallength' WHERE prefname='referrallength'");
	}
}

if ($param == "layout") {
	if (!$changeconfig) {
		// Get context help for this page...
		$contexthelppage = "layout";
		include "help.inc.php"; 
		echo "<script language=\"JavaScript\">
		function colorpicker(formname,fieldname) 
		{
		  w = window.open('colors.php?form='+formname+'&field='+fieldname,'_blank','toolbar=no,location=no,width=450,height=100');
	    }
		function fontselect(formname,fieldname) 
		{
		  w = window.open('fonts.php?form='+formname+'&field='+fieldname,'_blank','toolbar=no,location=no,width=350,height=200');
	    }
		</script>
		<input type=\"hidden\" name=\"param\" value=\"layout\">
	<tr><td colspan=\"2\" class=\"formtitle\">".DEFAULTLOGOIMAGE." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".UPLOADLOGOIMAGE.":</td><td><input type=\"file\" name=\"imgfile\" size=\"20\"></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".UPLOADMOBILELOGO.":</td><td><input type=\"file\" name=\"mobimgfile\" size=\"20\"></td></tr>
	<tr bgcolor=\"#D0D0D0\"><td class=\"formtitle\" colspan=\"2\">".THEMESELECTION." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".THEME.":</td><td><select name=\"nashoptheme\"><option value=\"none\"";
		if ($ashoptheme == "none") echo " selected";
		echo ">".NONE."</option>";
		$findfile = opendir("$ashoppath/themes");
		$starttime = time();
		while ($founddir = readdir($findfile)) {
			if  (time()-$starttime > 180) exit;
			if (is_dir("$ashoppath/themes/$founddir") && $founddir != "." && $founddir != ".." && $founddir != ".htaccess" && !strstr($founddir, "CVS") && substr($founddir, 0, 1) != "_") {
				echo "<option value=\"$founddir\"";
				$fp = fopen ("$ashoppath/themes/$founddir/theme.cfg.php","r");
				if ($fp) {
					while (!feof ($fp)) {
						$fileline = fgets($fp, 4096);
						if (strstr($fileline,"\$themename")) $themenamestring = $fileline;
					}
					fclose($fp);
					eval ($themenamestring);
				}
				if ($ashoptheme == $founddir) echo " selected";
				echo ">$themename</option>";
			}
		}
		echo "</select></td></tr>
	<tr><td class=\"formtitle\">".PAGEBODYCOLORS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a></td></tr>
<tr><td align=\"right\" class=\"formlabel\">".BACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"nbgcolor\" size=\"15\" value=\"$bgcolor\"><a href=\"javascript:colorpicker('configurationform','nbgcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a><script language=\"JavaScript\">document.configurationform.nbgcolor.focus();</script></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".TEXTCOLOR.":</td><td><input type=\"text\" name=\"ntextcolor\" size=\"15\" value=\"$textcolor\"><a href=\"javascript:colorpicker('configurationform','ntextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".LINKCOLOR.":</td><td><input type=\"text\" name=\"nlinkcolor\" size=\"15\" value=\"$linkcolor\"><a href=\"javascript:colorpicker('configurationform','nlinkcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ALERTCOLOR.":</td><td><input type=\"text\" name=\"nalertcolor\" size=\"15\" value=\"$alertcolor\"><a href=\"javascript:colorpicker('configurationform','nalertcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".HEADERBACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"ncatalogheader\" size=\"15\" value=\"$catalogheader\"><a href=\"javascript:colorpicker('configurationform','ncatalogheader')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".HEADERTEXTCOLOR.":</td><td><input type=\"text\" name=\"ncatalogheadertext\" size=\"15\" value=\"$catalogheadertext\"><a href=\"javascript:colorpicker('configurationform','ncatalogheadertext')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
	<tr bgcolor=\"#D0D0D0\"><td width=\"44%\" class=\"formtitle\" colspan=\"2\">".FORMSCOLORS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FORMSBACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"nformsbgcolor\" size=\"15\" value=\"$formsbgcolor\"><a href=\"javascript:colorpicker('configurationform','nformsbgcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FORMSTEXTCOLOR.":</td><td><input type=\"text\" name=\"nformstextcolor\" size=\"15\" value=\"$formstextcolor\"><a href=\"javascript:colorpicker('configurationform','nformstextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
		<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FORMSBORDERCOLOR.":</td><td><input type=\"text\" name=\"nformsbordercolor\" size=\"15\" value=\"$formsbordercolor\"><a href=\"javascript:colorpicker('configurationform','nformsbordercolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
	<tr><td class=\"formtitle\">".PRODUCTLAYOUT."
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMBORDERCOLOR.":</td><td><input type=\"text\" name=\"nitembordercolor\" size=\"15\" value=\"$itembordercolor\"><a href=\"javascript:colorpicker('configurationform','nitembordercolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMBORDERWIDTH.":</td><td><input type=\"text\" name=\"nitemborderwidth\" size=\"15\" value=\"$itemborderwidth\"></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMBACKGROUNDCOLOR.":</td><td><input type=\"text\" name=\"nitembgcolor\" size=\"15\" value=\"$itembgcolor\"><a href=\"javascript:colorpicker('configurationform','nitembgcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMTEXTCOLOR.":</td><td><input type=\"text\" name=\"nitemtextcolor\" size=\"15\" value=\"$itemtextcolor\"><a href=\"javascript:colorpicker('configurationform','nitemtextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".ITEMSPERROW.":</td><td><input type=\"text\" name=\"nitemsperrow\" size=\"15\" value=\"$itemsperrow\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".CONDENSEDLAYOUT.":</td><td><input type=\"checkbox\" name=\"nusecondensedlayout\" size=\"15\""; if ($usecondensedlayout == "true") echo "checked"; echo "></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".SHOWPRODUCTINFO.":</td><td><input type=\"checkbox\" name=\"nshowfileinfo\" size=\"15\""; if ($showfileinfo == "true") echo "checked"; echo "></td></tr>	
		<tr><td align=\"right\" class=\"formlabel\">".PRODUCTSORTORDER.":</td><td><select name=\"nashopsortorder\"><option value=\"ASC\""; if ($ashopsortorder == "ASC") echo " selected"; echo ">".NEWITEMSLAST."<option value=\"DESC\""; if ($ashopsortorder == "DESC") echo " selected"; echo ">".NEWITEMSFIRST."</select></td></tr>	
	<tr bgcolor=\"#D0D0D0\"><td class=\"formtitle\" colspan=\"2\">".CATEGORYCOLORS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CATEGORYCOLOR.":</td><td><input type=\"text\" name=\"ncategorycolor\" size=\"15\" value=\"$categorycolor\"><a href=\"javascript:colorpicker('configurationform','ncategorycolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CATEGORYTEXTCOLOR.":</td><td><input type=\"text\" name=\"ncategorytextcolor\" size=\"15\" value=\"$categorytextcolor\"><a href=\"javascript:colorpicker('configurationform','ncategorytextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SELECTEDCATEGORYCOLOR.":</td><td><input type=\"text\" name=\"nselectedcategory\" size=\"15\" value=\"$selectedcategory\"><a href=\"javascript:colorpicker('configurationform','nselectedcategory')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SELECTEDCATEGORYTEXTCOLOR.":</td><td><input type=\"text\" name=\"nselectedcategorytext\" size=\"15\" value=\"$selectedcategorytext\"><a href=\"javascript:colorpicker('configurationform','nselectedcategorytext')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".HIDEEMPTYCATEGORIES.":</td><td><input type=\"checkbox\" name=\"nhideemptycategories\" size=\"15\""; if ($hideemptycategories == "true") echo "checked"; echo "></td></tr>
        <tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SHOWPRODUCTCOUNT.":</td><td><input type=\"checkbox\" name=\"nenableproductcount\" size=\"15\""; if ($enableproductcount == "1") echo "checked"; echo "></td></tr>
	<tr><td class=\"formtitle\">".OTHERSETTINGS." 
</td></tr>
        <tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> ".FONT.":</td><td><input type=\"text\" name=\"nfont\" size=\"25\" value=\"$font\"><a href=\"javascript:fontselect('configurationform','nfont')\"><img src=\"images/fontselect.gif\" border=\"0\" align=\"absmiddle\" width=\"20\" height=\"20\"></a></td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".SMALLTEXTSIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nfontsize1\" size=\"5\" value=\"$fontsize1\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".REGULARTEXTSIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nfontsize2\" size=\"5\" value=\"$fontsize2\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".LARGETEXTSIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"nfontsize3\" size=\"5\" value=\"$fontsize3\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".REGULARTABLESIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ntablesize2\" size=\"5\" value=\"$tablesize2\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".LARGETABLESIZE.":</td><td class=\"formlabel\"><input type=\"text\" name=\"ntablesize1\" size=\"5\" value=\"$tablesize1\"> pixels</td></tr>
        <tr><td align=\"right\" class=\"formlabel\">".TOPFORMLAYOUT.":</td><td class=\"formlabel\"><select name=\"ntopformlayout\"><option value=\"1\""; if ($topformlayout == "1") echo " selected"; echo ">".ONEROW."<option value=\"2\""; if ($topformlayout == "2") echo " selected"; echo ">".TWOROWS."</select></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".NUMBEROFFEATURES.":</td><td align=\"left\"><input type=\"text\" name=\"nnumberoffeatures\" size=\"3\" value=\"$numberoffeatures\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip8');\" onmouseout=\"return nd();\"></a> ".THUMBNAILWIDTH.":</td><td align=\"left\"><input type=\"text\" name=\"nthumbnailwidth\" size=\"3\" value=\"$thumbnailwidth\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".PRODUCTIMAGEWIDTH.":</td><td align=\"left\"><input type=\"text\" name=\"nimagewidth\" size=\"3\" value=\"$imagewidth\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a></td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nshowimagesincart\" size=\"15\""; if ($showimagesincart == "true") echo "checked"; echo "> ".SHOWTHUMBNAILSINCART."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a></td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nkeeplargeprodimg\" size=\"15\""; if ($keeplargeprodimg == "true") echo "checked"; echo "> ".LINKTOLARGEIMAGE."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11\" align=\"absmiddle\" onclick=\"return overlib('$tip11');\" onmouseout=\"return nd();\"></a></td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nincludesubcategories\" size=\"15\""; if ($includesubcategories == "true") echo "checked"; echo "> ".INCLUDESUBCATEGORIES."</td></tr>
		<tr><td align=\"right\">&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"ncartlistoncheckout\" size=\"15\""; if ($cartlistoncheckout == "1") echo "checked"; echo "> ".DETAILEDCARTLISTONCHECKOUT."</td></tr>";
	} else {
		if ($nusecondensedlayout == "on") $nusecondensedlayout = "true";
		else $nusecondensedlayout = "0";
		if ($nshowfileinfo == "on") $nshowfileinfo = "true";
		else $nshowfileinfo = "0";		
		if ($nkeeplargeprodimg == "on") $nkeeplargeprodimg = "true";
		else $nkeeplargeprodimg = "0";
		if ($nshowimagesincart == "on") $nshowimagesincart = "true";
		else $nshowimagesincart = "0";
		if ($nhideemptycategories == "on") $nhideemptycategories = "true";
		else $nhideemptycategories = "0";
		if ($ncartlistoncheckout == "on") $ncartlistoncheckout = "1";
		else $ncartlistoncheckout = "0";
		if ($nenableproductcount == "on") $nenableproductcount = "1";
		else $nenableproductcount = "0";
		if ($nincludesubcategories == "on") $nincludesubcategories = "true";
		else $includesubcategories = "0";
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashoptheme' WHERE prefname='ashoptheme'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nbgcolor' WHERE prefname='bgcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntextcolor' WHERE prefname='textcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nlinkcolor' WHERE prefname='linkcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nalertcolor' WHERE prefname='alertcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncatalogheader' WHERE prefname='catalogheader'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncatalogheadertext' WHERE prefname='catalogheadertext'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nformsbgcolor' WHERE prefname='formsbgcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nformstextcolor' WHERE prefname='formstextcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nformsbordercolor' WHERE prefname='formsbordercolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nitembordercolor' WHERE prefname='itembordercolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nitemborderwidth' WHERE prefname='itemborderwidth'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nitembgcolor' WHERE prefname='itembgcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nitemtextcolor' WHERE prefname='itemtextcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nitemsperrow' WHERE prefname='itemsperrow'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nusecondensedlayout' WHERE prefname='usecondensedlayout'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nashopsortorder' WHERE prefname='ashopsortorder'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshowfileinfo' WHERE prefname='showfileinfo'");		
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncategorycolor' WHERE prefname='categorycolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncategorytextcolor' WHERE prefname='categorytextcolor'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nselectedcategory' WHERE prefname='selectedcategory'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nselectedcategorytext' WHERE prefname='selectedcategorytext'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfont' WHERE prefname='font'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfontsize1' WHERE prefname='fontsize1'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfontsize2' WHERE prefname='fontsize2'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfontsize3' WHERE prefname='fontsize3'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntablesize1' WHERE prefname='tablesize1'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntablesize2' WHERE prefname='tablesize2'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntopformlayout' WHERE prefname='topformlayout'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nnumberoffeatures' WHERE prefname='numberoffeatures'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nthumbnailwidth' WHERE prefname='thumbnailwidth'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nimagewidth' WHERE prefname='imagewidth'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nkeeplargeprodimg' WHERE prefname='keeplargeprodimg'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshowimagesincart' WHERE prefname='showimagesincart'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nhideemptycategories' WHERE prefname='hideemptycategories'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ncartlistoncheckout' WHERE prefname='cartlistoncheckout'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nenableproductcount' WHERE prefname='enableproductcount'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nincludesubcategories' WHERE prefname='includesubcategories'");
	}
}

if ($param == "shipping") {
	if (!$changeconfig || $addcountry) {
		// Get context help for this page...
		$contexthelppage = "shipping";
		include "help.inc.php";
		if ($storeshippingmaxweight) {
			$totalounces = $storeshippingmaxweight*16;
			$storeshippingmaxpounds = floor($storeshippingmaxweight);
			$storeshippingmaxounces = ($totalounces-($storeshippingmaxpounds*16));
			$storeshippingmaxounces = number_format($storeshippingmaxounces,1,'.','');
		} else {
			$storeshippingmaxpounds = 1.00;
			$storeshippingmaxounces = 0.00;
		}
		echo "<input type=\"hidden\" name=\"param\" value=\"shipping\">
	    <tr><td colspan=\"2\" class=\"formtitle\">".SHIPPINGCALCULATIONOPTIONS." 
        <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a></td></tr>
		 <tr><td class=\"formlabel\" align=\"right\">".SHIPPINGMETHOD.":</td><td><select name=\"nshippingmethod\" onChange=\"document.configurationform.submit()\"><option value=\"custom\""; if ($shippingmethod == "custom") echo " selected"; echo ">".CUSTOM."<option value=\"usps\""; if ($shippingmethod == "usps") echo " selected"; echo ">".USPS."<option value=\"ups\""; if ($shippingmethod == "ups") echo " selected"; echo ">".UPS."<option value=\"fedex\""; if ($shippingmethod == "fedex") echo " selected"; echo ">".FEDEX."</select></td></tr>";
		$countrynumber = 1;
		if ($shipfromcountries) foreach($shipfromcountries as $thisshipfromcountry) {
			echo "<tr><td class=\"formlabel\" align=\"right\">";
			if ($countrynumber == 1) echo LOCALHANDLINGCOUNTRIES.":";
			echo "</td><td><SELECT NAME=\"nshipfromcountry$countrynumber\"><option value=none>".CHOOSECOUNTRY."<option  value=none>-- ".REMOVETHISCOUNTRY;
			foreach ($countries as $shortcountry => $longcountry) {
				if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
				echo "<option  value=$shortcountry";
				if ($shortcountry == $thisshipfromcountry) echo " selected";
				echo ">$longcountry\n";
			}
			$countrynumber++;
			echo "</select></td></tr>";
		}
		if ($addcountry) {
			echo "<tr><td></td><td><SELECT NAME=\"nshipfromcountry$countrynumber\"><option  value=none>".CHOOSECOUNTRY."<option  value=none>-- ".REMOVETHISCOUNTRY;
			foreach ($countries as $shortcountry => $longcountry) {
				if (strlen($longcountry) > 30) $longcountry = substr($longcountry,0,27)."...";
				echo "<option  value=$shortcountry>$longcountry\n";
			}
			echo "</select></td></tr>";
		}

		echo "<tr><td></td><td><input type=\"submit\" name=\"addcountry\" value=\"".ADDCOUNTRY."\"></td></tr>		<tr><td align=\"right\" class=\"formlabel\">".SHIPONLYLOCALLY.":</td><td><input type=\"checkbox\" name=\"nlocalshipping\" ";
	    if ($localshipping) echo "checked ";
	    echo "></td></tr><tr><td align=\"right\" class=\"formlabel\">".ADDLOCALHANDLINGCHARGE.":</td><td>".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nhandlinglocal\" size=\"5\" value=\"$handlinglocal\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr><tr><td align=\"right\" class=\"formlabel\">".ADDINTERNATIONALHANDLINGCHARGE.":</td><td>".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nhandlingint\" size=\"5\" value=\"$handlingint\">".$currencysymbols[$ashopcurrency]["post"]."</td></tr><tr><td align=\"right\" class=\"formlabel\">".FREESHIPPINGONORDERSABOVE.":</td><td>".$currencysymbols[$ashopcurrency]["pre"]."<input type=\"text\" name=\"nfreeshippinglimit\" size=\"5\" value=\"$freeshippinglimit\">".$currencysymbols[$ashopcurrency]["post"]." <span class=\"sm\">[ 0 = ".DEACTIVATE." ]</span></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".FREESHIPPINGONLYLOCALLY.":</td><td><input type=\"checkbox\" name=\"nfreeshippingonlylocal\" ";
	    if ($freeshippingonlylocal) echo "checked ";
	    echo "></td></tr>";
		if ($shippingmethod == "usps") {
			echo "<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".USPSOPTIONS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".USPSUSERID.":</td><td><input type=\"text\" name=\"nuspsuserid\" size=\"20\" value=\"$uspsuserid\"></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CUSTOMERSELECTABLESERVICE.":</td><td><SELECT NAME=\"nuspscustomerselect\"><option value=\"no\""; if($shipoptionstype != "usps") echo " selected"; echo ">".NO."</option><option value=\"yes\""; if($shipoptionstype == "usps") echo " selected"; echo ">".YES."</option></select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".LOCALSERVICE.":</td><td><SELECT NAME=\"nuspsserviceusa\"><option value=\"0\""; if(!$upsserviceusa) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			foreach ($uspsservicesusa as $servicecode => $servicename) {
				echo "<option value=\"$servicecode\"";
				if ($servicecode == $uspsserviceusa) echo " selected";
				echo ">$servicename\n";
			}
			echo "</SELECT></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".INTERNATIONALSERVICE.":</td><td><SELECT NAME=\"nuspsserviceworld\"><option value=\"0\""; if(!$uspsserviceworld) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			foreach ($uspsservicesworld as $servicecode => $servicename) {
				echo "<option value=\"$servicecode\"";
				if ($servicecode == $uspsserviceworld) echo " selected";
				echo ">$servicename\n";
			}
			echo "</SELECT></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">
			".CONTAINER.":
			</td><td><SELECT NAME=\"nuspscontainer\"><option value=none>".CHOOSECONTAINER;
			foreach ($uspscontainers as $containercode => $containername) {
				echo "<option  value=\"$containercode\"";
				if ($containercode == $uspscontainer) echo " selected";
				echo ">$containername\n";
			}
			echo "</select></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">
			".MACHINABLE.":</td><td><SELECT NAME=\"nuspsmachinable\"><option value=\"True\""; if ($uspsmachinable == "True") echo " selected"; echo ">".YES."</option><option value=\"no\""; if ($uspsmachinable == "False") echo " selected"; echo ">".NO."</option></select></td></tr>";
			/*
			echo "
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SIZE.":</td><td><SELECT NAME=\"nuspssize\"><option  value=none>".CHOOSESIZE;
			foreach ($uspssizes as $sizecode => $sizename) {
				echo "<option  value=\"$sizecode\"";
				if ($sizecode == $uspssize) echo " selected";
				echo ">$sizename\n";
			}
			echo "</select></td></tr>";
			*/
			echo "
			<tr bgcolor=\"#D0D0D0\"><td></td><td></td></tr>";
		} else if ($shippingmethod == "ups") {
			echo "<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".UPSOPTIONS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".UPSACCESSKEY.":</td><td><input type=\"text\" name=\"nupsaccesskey\" size=\"20\" value=\"$upsaccesskey\"></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".ORIGINCOUNTRY.":</td><td><SELECT NAME=\"nupscountry\"><option value=\"0\""; if(!$upscountry) echo " selected"; echo ">".CHOOSECOUNTRY."</option><option value=\"US\"";
			if ($upscountry == "US") echo " selected";
			echo ">".UNITEDSTATES."\n<option value=\"CA\"";
			if ($upscountry == "CA") echo " selected";
			echo ">".CANADA."\n</SELECT></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CUSTOMERSELECTABLESERVICE.":</td><td><SELECT NAME=\"nupscustomerselect\"><option value=\"no\""; if($shipoptionstype != "ups") echo " selected"; echo ">".NO."</option><option value=\"yes\""; if($shipoptionstype == "ups") echo " selected"; echo ">".YES."</option></select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".LOCALSERVICE.":</td><td><SELECT NAME=\"nupsserviceusa\"><option value=\"0\""; if(!$upsserviceusa) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			if ($upscountry == "US") {
				foreach ($upsservicesusa as $servicecode => $servicename) {
					echo "<option value=\"$servicecode\"";
					if ($servicecode == $upsserviceusa) echo " selected";
					echo ">$servicename\n";
				}
			} else {
				foreach ($upsservicescan as $servicecode => $servicename) {
					echo "<option value=\"$servicecode\"";
					if ($servicecode == $upsserviceusa) echo " selected";
					echo ">$servicename\n";
				}
			}
			echo "</SELECT></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SERVICE." "; if ($upscountry == "US") echo TOCANADA; else echo TOUSA; echo ":</td><td><SELECT NAME=\"nupsservicecanusa\"><option value=\"0\""; if(!$upsserviceusa) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			if ($upscountry == "US") {
				foreach ($upsservicestocan as $servicecode => $servicename) {
					echo "<option value=\"$servicecode\"";
					if ($servicecode == $upsservicecanusa) echo " selected";
					echo ">$servicename\n";
				}
			} else {
				foreach ($upsservicestousa as $servicecode => $servicename) {
					echo "<option value=\"$servicecode\"";
					if ($servicecode == $upsservicecanusa) echo " selected";
					echo ">$servicename\n";
				}
			}
			echo "</SELECT></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".INTERNATIONALSERVICE.":</td><td><SELECT NAME=\"nupsserviceworld\"><option value=\"0\""; if(!$upsserviceworld) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			foreach ($upsservicesworld as $servicecode => $servicename) {
				echo "<option value=\"$servicecode\"";
				if ($servicecode == $upsserviceworld) echo " selected";
				echo ">$servicename\n";
			}
			echo "</SELECT></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">
			".DROPOFFPICKUP.":
			</td><td><SELECT NAME=\"nupsdropofftype\"><option value=none>".CHOOSEDROPOFFPICKUP;
			foreach ($upsdropofftypes as $number => $dropoffname) {
				echo "<option  value=\"$number\"";
				if ($number == $upsdropofftype) echo " selected";
				echo ">$dropoffname\n";
			}
			echo "</select></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">
			".DAILYPICKUP.":</td><td><SELECT NAME=\"nupsdailypickup\"><option value=\"yes\""; if ($upsdailypickup == "yes") echo " selected"; echo ">".YES."</option><option value=\"no\""; if ($upsdailypickup == "no") echo " selected"; echo ">".NO."</option></select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".PACKAGETYPELOCAL.":</td><td><SELECT NAME=\"nupspackagetype\"><option  value=none>".CHOOSEPACKAGETYPE;
			if ($upsserviceusa == "03" || $upsserviceusa == "12") echo "<option  value=\"02\" selected>".PACKAGE."\n";
			else {
				foreach ($upspackagetypes as $packagecode => $packagename) {
					if ($packagecode != "24" && $packagecode != "25") {
						echo "<option  value=\"$packagecode\"";
						if ($packagecode == $upspackagetype) echo " selected";
						echo ">$packagename\n";
					}
				}
			}
			echo "</select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".PACKAGETYPEINT.":</td><td><SELECT NAME=\"nupspackagetypeworld\"><option  value=none>".CHOOSEPACKAGETYPE;
			foreach ($upspackagetypes as $packagecode => $packagename) {
				if (($packagecode != "01" && $packagecode != "24" && $packagecode != "25") || $upsserviceworld == "07") {
					echo "<option  value=\"$packagecode\"";
					if ($packagecode == $upspackagetypeworld) echo " selected";
					echo ">$packagename\n";
				}
			}
			echo "</select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td></td><td></td></tr>";
		} else if ($shippingmethod == "fedex") {
			echo "<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\" class=\"formtitle\">".FEDEXOPTIONS." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip8');\" onmouseout=\"return nd();\"></a></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FEDEXACCOUNT.":</td><td><input type=\"text\" name=\"nfedexaccount\" size=\"10\" value=\"$fedexaccount\"></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FEDEXMETERNUMBER.":</td><td><input type=\"text\" name=\"nfedexmeternumber\" size=\"10\" value=\"$fedexmeternumber\"></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FEDEXKEY.":</td><td><input type=\"text\" name=\"nfedexkey\" size=\"20\" value=\"$fedexkey\"></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".FEDEXPASSWORD.":</td><td><input type=\"text\" name=\"nfedexpassword\" size=\"20\" value=\"$fedexpassword\"></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".CUSTOMERSELECTABLESERVICE.":</td><td><SELECT NAME=\"nfedexcustomerselect\"><option value=\"no\""; if($shipoptionstype != "fedex") echo " selected"; echo ">".NO."</option><option value=\"yes\""; if($shipoptionstype == "fedex") echo " selected"; echo ">".YES."</option></select></td></tr><tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".USSERVICE.":</td><td><SELECT NAME=\"nfedexserviceusa\"><option value=\"0\""; if(!$fedexserviceusa) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			foreach ($fedexservicesusa as $servicecode => $servicename) {
				echo "<option value=\"$servicecode\"";
				if ($servicecode == $fedexserviceusa) echo " selected";
				echo ">$servicename\n";
			}
			echo "</SELECT></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".INTERNATIONALSERVICE.":</td><td><SELECT NAME=\"nfedexserviceworld\"><option value=\"0\""; if(!$fedexserviceworld) echo " selected"; echo ">".CHOOSESERVICE."</option>";
			foreach ($fedexservicesworld as $servicecode => $servicename) {
				echo "<option value=\"$servicecode\"";
				if ($servicecode == $fedexserviceworld) echo " selected";
				echo ">$servicename\n";
			}
			echo "</SELECT></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".PACKAGETYPEUS.":</td><td><SELECT NAME=\"nfedexpackagetype\"><option  value=none>".CHOOSEPACKAGETYPE;
			foreach ($fedexpackagetypes as $packagecode => $packagename) {
				if ($packagecode != "15" && $packagecode != "25") {
					echo "<option  value=\"$packagecode\"";
					if ($packagecode == $fedexpackagetype) echo " selected";
					echo ">$packagename\n";
				}
			}
			echo "</select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".PACKAGETYPEINT.":</td><td><SELECT NAME=\"nfedexpackagetypeworld\"><option  value=none>".CHOOSEPACKAGETYPE;
			foreach ($fedexpackagetypes as $packagecode => $packagename) {
				echo "<option  value=\"$packagecode\"";
				if ($packagecode == $fedexpackagetypeworld) echo " selected";
				echo ">$packagename\n";
			}
			echo "</select></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td colspan=\"2\"></td></tr>";
		}
		if ($shippingmethod == "custom") echo "<tr bgcolor=\"#D0D0D0\">";
		else echo "<tr>";
		echo "<td colspan=\"2\" class=\"formtitle\">".STOREWIDESHIPPING." <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a></td></tr>";
		if ($shippingmethod == "custom") {
			echo "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".SHIPPINGCALCULATIONMETHOD.":</td><td><select name=\"nstoreshippingmethod\"  onChange=\"document.configurationform.submit()\">
			<option value=\"none\"";
			if ($storeshippingmethod == "none") echo " selected";
			echo ">".THEWORDNONE."</option>
			<option value=\"perpound\"";		
			if ($storeshippingmethod == "perpound") echo " selected";
			echo ">".PERPOUND."</option>
			<option value=\"byweight\"";		
			if ($storeshippingmethod == "byweight") echo " selected";
			echo ">".BYWEIGHTRANGE."</option>
			<option value=\"byprice\"";
			if ($storeshippingmethod == "byprice") echo " selected";
			echo ">".BYPRICE."</option>
			</select></td></tr>";
		}
		if ($shippingmethod != "custom") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".ORIGINSTATE.":</td><td align=\"left\"><SELECT NAME=\"nstoreshippingfromstate\"><option value=none>".CHOOSESTATE;
			 $storeshippingstates = $uscanstates;
			if (in_array("AU", $shipfromcountries) && !in_array("CA", $shipfromcountries) && !in_array("US", $shipfromcountries)) $storeshippingstates = $australianstates;
			else if (in_array("CA", $shipfromcountries) && !in_array("US", $shipfromcountries) && !in_array("AU", $shipfromcountries)) $storeshippingstates = $canprovinces;
			else if (in_array("US", $shipfromcountries) && !in_array("CA", $shipfromcountries) && !in_array("AU", $shipfromcountries)) $storeshippingstates = $americanstates;
			else $storeshippingstates = $uscanstates;
			foreach ($storeshippingstates as $longstate => $shortstate) {
				echo "<option  value=$shortstate";
				if ($shortstate == $storeshippingfromstate) echo " selected";
				echo ">$longstate\n";
			}
			echo "</SELECT></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".ORIGINZIP.": </td><td><input type=\"text\" name=\"nstoreshippingfromzip\" size=\"10\" value=\"$storeshippingfromzip\"></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".MAXWEIGHTPERPACKAGE.": </td><td align=\"left\" class=\"formlabel\"><input type=\"text\" name=\"nstoreshippingmaxpounds\" value=\"$storeshippingmaxpounds\" size=\"4\"> ".POUND." <input type=\"text\" name=\"nstoreshippingmaxounces\" value=\"$storeshippingmaxounces\" size=\"4\"> ".OUNCE."</td></tr>";
		} else if ($storeshippingmethod == "perpound") {
			echo "<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".BASECHARGE.": </td><td><input type=\"text\" name=\"nstoreshippingbasecharge\" size=\"6\" value=\"$storeshippingbasecharge\"></td></tr>
			<tr bgcolor=\"#D0D0D0\"><td align=\"right\" class=\"formlabel\">".RATEPERPOUND.": </td><td align=\"left\" class=\"formlabel\"><input type=\"text\" name=\"nstoreshippingperpound\" value=\"$storeshippingperpound\" size=\"6\"></td></tr>";			
		} else if ($storeshippingmethod == "byweight") echo $weightshippinglevelstring;
		else if ($storeshippingmethod == "byprice") echo $priceshippinglevelstring;
		echo "</td></tr>
		<tr><td colspan=\"2\"><table bgcolor=\"#3f71a2\" align=\"center\" width=\"100%\">
  	    <tr align=\"center\"><td class=\"nav\" nowrap><a href=\"editzones.php\" class=\"nav\">".ZIPZONETABLES."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a></td>";
		if ($shippingmethod == "custom") echo "<td class=\"nav\" nowrap><a href=\"editshipoptions.php\" class=\"nav\">".CUSTOMSHIPPINGOPTIONS."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a></td></tr>
        <tr align=\"center\"><td class=\"nav\" nowrap><a href=\"editshipdiscounts.php\" class=\"nav\">".SHIPPINGDISCOUNTS."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a></td>
        <td class=\"nav\" nowrap>&nbsp;</td></tr>";
		else echo "<td class=\"nav\" nowrap><a href=\"editshipdiscounts.php\" class=\"nav\">".SHIPPINGDISCOUNTS."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a></td>";
        echo "</table></td></tr>";
	} else {
		if ($nlocalshipping == "on") $nlocalshipping = "1";
		else $nlocalshipping = "0";
		if ($nshippingmethod == "fedex" && $nfedexcustomerselect == "yes") $nshipoptionstype = "fedex";
		else if ($nshippingmethod == "ups" && $nupscustomerselect == "yes") $nshipoptionstype = "ups";
		else if ($nshippingmethod == "usps" && $nuspscustomerselect == "yes") $nshipoptionstype = "usps";
		else $nshipoptionstype = "custom";
		if ($nfreeshippingonlylocal == "on") $nfreeshippingonlylocal = "1";
		else $nfreeshippingonlylocal = "0";
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshippingmethod' WHERE prefname='shippingmethod'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshipfromcountry' WHERE prefname='shipfromcountry'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nlocalshipping' WHERE prefname='localshipping'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nhandlinglocal' WHERE prefname='handlinglocal'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nhandlingint' WHERE prefname='handlingint'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfreeshippinglimit' WHERE prefname='freeshippinglimit'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfreeshippingonlylocal' WHERE prefname='freeshippingonlylocal'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshipoptionstype' WHERE prefname='shipoptionstype'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingmethod' WHERE prefname='storeshippingmethod'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$npriceshipping' WHERE prefname='priceshipping'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nweightshipping' WHERE prefname='weightshipping'");
		if (isset($nstoreshippingmaxpounds) || isset($nstoreshippingmaxounces)) {
			$totalounces = ($nstoreshippingmaxpounds*16)+$nstoreshippingmaxounces;
			$nstoreshippingmaxweight = $totalounces/16;
			@mysqli_query($db, "UPDATE preferences SET prefvalue='".number_format($nstoreshippingmaxweight,2,'.','')."' WHERE prefname='storeshippingmaxweight'");
		}
		if (isset($nstoreshippingfromzip)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingfromzip' WHERE prefname='storeshippingfromzip'");
		if (isset($nstoreshippingfromstate)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingfromstate' WHERE prefname='storeshippingfromstate'");
		if (isset($nstoreshippingbasecharge)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingbasecharge' WHERE prefname='storeshippingbasecharge'");
		if (isset($nstoreshippingperpound)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nstoreshippingperpound' WHERE prefname='storeshippingperpound'");
		if ($nshippingmethod != $shippingmethod) {
			if ($nshippingmethod == "custom") @mysqli_query($db, "UPDATE preferences SET prefvalue='none' WHERE prefname='storeshippingmethod'");
			else if ($nshippingmethod == "usps") @mysqli_query($db, "UPDATE preferences SET prefvalue='usps' WHERE prefname='storeshippingmethod'");
			else if ($nshippingmethod == "fedex") @mysqli_query($db, "UPDATE preferences SET prefvalue='fedex' WHERE prefname='storeshippingmethod'");
			else if ($nshippingmethod == "ups") @mysqli_query($db, "UPDATE preferences SET prefvalue='ups' WHERE prefname='storeshippingmethod'");
			header("Location: configure.php?param=shipping");
			exit;
		}
		if ($nstoreshippingmethod != $storeshippingmethod && $nshippingmethod == "custom") {
			header("Location: configure.php?param=shipping");
			exit;
		}
		if ($shippingmethod == "usps") {
			if ($shipoptionstype == "custom" || $nshipoptionstype == "custom") $sql = "SELECT productid FROM product WHERE shipping = 'ups' OR shipping = 'fedex'";
			else $sql = "SELECT productid FROM product WHERE shipping IS NOT NULL AND shipping != '' AND shipping != 'usps' AND shipping !='storewide'";
			$shippingresult = @mysqli_query($db, $sql);
			while ($shippingrow = @mysqli_fetch_array($shippingresult)) @mysqli_query($db, "UPDATE product SET shipping='usps' WHERE productid='{$shippingrow["productid"]}'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='usps' WHERE prefname='storeshippingmethod'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nuspsuserid' WHERE prefname='uspsuserid'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nuspsserviceusa' WHERE prefname='uspsserviceusa'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nuspsserviceworld' WHERE prefname='uspsserviceworld'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nuspsmachinable' WHERE prefname='uspsmachinable'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nuspssize' WHERE prefname='uspssize'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nuspscontainer' WHERE prefname='uspscontainer'");
		} else if ($shippingmethod == "fedex") {
			if ($shipoptionstype == "custom" || $nshipoptionstype == "custom") $sql = "SELECT productid FROM product WHERE shipping = 'ups' OR shipping = 'usps'";
			else $sql = "SELECT productid FROM product WHERE shipping IS NOT NULL AND shipping != '' AND shipping != 'fedex' AND shipping !='storewide'";
			$shippingresult = @mysqli_query($db, $sql);
			while ($shippingrow = @mysqli_fetch_array($shippingresult)) @mysqli_query($db, "UPDATE product SET shipping='fedex' WHERE productid='{$shippingrow["productid"]}'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='fedex' WHERE prefname='storeshippingmethod'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexaccount' WHERE prefname='fedexaccount'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexmeternumber' WHERE prefname='fedexmeternumber'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexkey' WHERE prefname='fedexkey'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexpassword' WHERE prefname='fedexpassword'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexserviceusa' WHERE prefname='fedexserviceusa'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexserviceworld' WHERE prefname='fedexserviceworld'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexpackagetype' WHERE prefname='fedexpackagetype'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexcarrier' WHERE prefname='fedexcarrier'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nfedexpackagetypeworld' WHERE prefname='fedexpackagetypeworld'");
		} else if ($shippingmethod == "ups") {
			if ($shipoptionstype == "custom" || $nshipoptionstype == "custom") $sql = "SELECT productid FROM product WHERE shipping = 'fedex' OR shipping = 'usps'";
			else $sql = "SELECT productid FROM product WHERE shipping IS NOT NULL AND shipping != '' AND shipping != 'ups' AND shipping !='storewide'";
			$shippingresult = @mysqli_query($db, $sql);
			$shippingresult = @mysqli_query($db, $sql);
			while ($shippingrow = @mysqli_fetch_array($shippingresult)) @mysqli_query($db, "UPDATE product SET shipping='ups' WHERE productid='{$shippingrow["productid"]}'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='ups' WHERE prefname='storeshippingmethod'");
			if ($nupsdailypickup == "no") {
				if ($nupsdropofftype == "1") $nupspickuptype = "03";
				if ($nupsdropofftype == "2") $nupspickuptype = "11";
				if ($nupsdropofftype == "3") $nupspickuptype = "06";
				if ($nupsdropofftype == "4") $nupspickuptype = "19";
			} else {
				if ($nupsdropofftype == "1") $nupspickuptype = "03";
				if ($nupsdropofftype == "2") $nupspickuptype = "01";
				if ($nupsdropofftype == "3") { $nupsdailypickup = "no"; $nupspickuptype = "06"; }
				if ($nupsdropofftype == "4") $nupspickuptype = "01";
			}

			if ($nupsserviceusa == "03" || $nupsserviceusa == "12") $nupspackagetype = "02";
			if ($nupsserviceworld == "08" && ($nupspackagetypeworld == "01" || $nupspackagetypeworld == "24" || $nupspackagetypeworld == "25")) $nupspackagetypeworld = "02";
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsaccesskey' WHERE prefname='upsaccesskey'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsserviceusa' WHERE prefname='upsserviceusa'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsservicecanusa' WHERE prefname='upsservicecanusa'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsserviceworld' WHERE prefname='upsserviceworld'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupspackagetype' WHERE prefname='upspackagetype'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupspickuptype' WHERE prefname='upspickuptype'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupspackagetypeworld' WHERE prefname='upspackagetypeworld'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupscountry' WHERE prefname='upscountry'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsdropofftype' WHERE prefname='upsdropofftype'");
			@mysqli_query($db, "UPDATE preferences SET prefvalue='$nupsdailypickup' WHERE prefname='upsdailypickup'");
		} else @mysqli_query($db, "UPDATE product SET shipping='' WHERE shipping='ups' OR shipping='fedex'");
	}
}

if ($param == "taxes") {
	if (!$changeconfig || $addcountry) {
		// Get context help for this page...
		$contexthelppage = "shipping";
		include "help.inc.php"; 
		echo "<input type=\"hidden\" name=\"param\" value=\"taxes\"><input type=\"hidden\" name=\"updatetaxes\" value=\"\">
		<tr><td class=\"formtitle\">".SALESTAXOPTIONS." 
<a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".SALESTAXTYPE.":</td><td><SELECT NAME=\"nsalestaxtype\" onChange=\"configurationform.updatetaxes.value=1; configurationform.submit();\"><option value=\"ussalestax\"";
		if($salestaxtype == "ussalestax") echo " selected";
		echo ">".USSALESTAX."</option><option value=\"cancstpst\"";
		if($salestaxtype == "cancstpst") echo " selected";
		echo ">".CANGSTPSTHST."</option><option value=\"euvat\"";
		if($salestaxtype == "euvat") echo " selected";
		echo ">".EUROPEANVAT."</option><option value=\"australiagst\"";
		if($salestaxtype == "australiagst") echo " selected";
		echo ">".AUSTRALIANGST."</option><option value=\"safricanvat\"";
		if($salestaxtype == "safricanvat") echo " selected";
		echo ">".SAFRICANVAT."</option></select></td></tr>";
		if ($salestaxtype != "cancstpst") {
			echo "
			<tr><td align=\"right\" class=\"formlabel\">".STATETOCHARGETAXTO.":</td><td><SELECT NAME=\"ntaxstate\"><option value=\"0\""; if(!$taxstate) echo " selected"; echo ">".THEWORDNONE."</option>";
			if ($salestaxtype == "ussalestax") $states = $americanstates;
			else if ($salestaxtype == "euvat") unset($states);
			else if ($salestaxtype == "australiagst") $states = $australianstates;
			foreach ($states as $longstate => $shortstate) {
				echo "<option value=\"$shortstate\"";
				if ($shortstate == $taxstate) echo " selected";
				echo ">$longstate\n";
			}
			echo "</SELECT></td></tr><tr><td align=\"right\" class=\"formlabel\">
			".EUVATORIGINCOUNTRY.":
			</td><td><SELECT NAME=\"nvatorigincountry\"><option  value=none>".CHOOSECOUNTRY;
			foreach ($ecmembers as $shortcountry) {
				echo "<option  value=$shortcountry";
				if ($shortcountry == $vatorigincountry) echo " selected";
				echo ">".$countries["$shortcountry"]."\n";
			}
			echo "</select></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".SALESTAXPERCENTAGE1.":</td><td><input type=\"text\" name=\"ntaxpercentage\" size=\"3\" value=\"$taxpercentage\"> %</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".SALESTAXPERCENTAGE2.":</td><td><input type=\"text\" name=\"ntaxpercentage2\" size=\"3\" value=\"$taxpercentage2\"> %</td></tr>";
		} else echo $cantaxtable;
		if ($salestaxtype == "euvat") {
			echo "
			<tr><td align=\"right\" class=\"formlabel\">".REQUESTVATNUMBER.":</td><td><input type=\"checkbox\" onChange=\"if(document.configurationform.nrequestvat.checked == true) document.configurationform.nrequestabn.checked = false;\" name=\"nrequestvat\" ";
			if ($requestvat) echo "checked ";
			echo "></td></tr>";
		}
		if ($salestaxtype == "australiagst") {
			echo "
			<tr><td align=\"right\" class=\"formlabel\">".REQUESTABNNUMBER.":</td><td><input type=\"checkbox\" onChange=\"if(document.configurationform.nrequestabn.checked == true) document.configurationform.nrequestvat.checked = false;\" name=\"nrequestabn\" ";
			if ($requestabn) echo "checked ";
			echo "></td></tr>";
		}
		echo "
		<tr><td align=\"right\" class=\"formlabel\">".SALESTAXONSHIPPING.":</td><td><input type=\"checkbox\" name=\"nshippingtax\" ";
	    if ($shippingtax) echo "checked ";
	    echo "></td></tr>";
		if ($salestaxtype != "cancstpst") {
			echo "
			<tr><td align=\"right\" class=\"formlabel\">".INCLUDETAXINDISPLAYEDPRICE.":</td><td><select name=\"ndisplaywithtax\"><option value=\"0\"";
			if ($displaywithtax == 0) echo " selected";
			echo ">".NO."</option><option value=\"1\"";
			if ($displaywithtax == 1) echo " selected";
			echo ">".ADDTOPRICE."</option><option value=\"2\"";
			if ($displaywithtax == 2) echo " selected";
			echo ">".INCLUDEDINLISTPRICE."</option></select>
			</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".INCLUDETAXINWHOLESALEPRICE.":</td><td><select name=\"ndisplaywswithtax\"><option value=\"0\"";
			if ($displaywswithtax == 0) echo " selected";
			echo ">".NO."</option><option value=\"1\"";
			if ($displaywswithtax) echo " selected";
			echo ">".ADDTOPRICE."</option><option value=\"2\"";
			if ($displaywswithtax == 2) echo " selected";
			echo ">".INCLUDEDINLISTPRICE."</option></select>
			</td></tr>";
		}
		echo "
		<tr><td colspan=\"2\"><table bgcolor=\"#3f71a2\" align=\"center\" width=\"200\">
  	    <tr align=\"center\"><td class=\"nav\" nowrap><a href=\"editlocaltax.php\" class=\"nav\">".EDITLOCALTAXRATES."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a></td></tr></table></td></tr>";
	} else {
		if ($nshippingtax == "on") $nshippingtax = "1";
		else $nshippingtax = "0";
		if ($nrequestvat == "on") $nrequestvat = "1";
		else $nrequestvat = "0";
		if ($nrequestabn == "on") {
			$nrequestabn = "1";
			$nrequestvat = "0";
		} else $nrequestabn = "0";
		// Calculate Canada tax table...
		$nhstpercentage = "";
		foreach ($canprovinces as $longprovince => $shortprovince) {
			$thisgst = $_POST["gst$shortprovince"];
			$thispst = $_POST["pst$shortprovince"];
			$thispstcom = $_POST["pstcom$shortprovince"];
			if ($thispstcom == "on") $thispstcom = "1";
			else $thispstcom = "0";
			$nhstpercentage .= "$shortprovince:$thisgst:$thispst:$thispstcom|";
		}
		$nhstpercentage = substr($nhstpercentage,0,-1);
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nsalestaxtype' WHERE prefname='salestaxtype'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntaxstate' WHERE prefname='taxstate'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntaxpercentage' WHERE prefname='taxpercentage'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ntaxpercentage2' WHERE prefname='taxpercentage2'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$npstpercentage' WHERE prefname='pstpercentage'");
		if (!empty($nhstpercentage)) @mysqli_query($db, "UPDATE preferences SET prefvalue='$nhstpercentage' WHERE prefname='hstpercentage'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nrequestvat' WHERE prefname='requestvat'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nrequestabn' WHERE prefname='requestabn'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nvatorigincountry' WHERE prefname='vatorigincountry'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$nshippingtax' WHERE prefname='shippingtax'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndisplaywithtax' WHERE prefname='displaywithtax'");
		@mysqli_query($db, "UPDATE preferences SET prefvalue='$ndisplaywswithtax' WHERE prefname='displaywswithtax'");
	}
}

if (!$changeconfig || $addcountry) {
	if ($param == "shop") {
		echo "<tr bgcolor=\"#F0F0F0\"><td colspan=\"2\"><table bgcolor=\"#3f71a2\" align=\"center\" width=\"100%\"><tr align=\"center\"><td class=\"nav\"><a href=\"advancedoptions.php\" class=\"nav\">".ADVANCEDOPTIONS."</a> <a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a></td></tr></table></td></tr>";
	} else if ($param == "mall" && $digitalmall == "ON") {
		echo "<tr bgcolor=\"#F0F0F0\"><td colspan=\"2\"><table bgcolor=\"#3f71a2\" align=\"center\" width=\"100%\"><tr align=\"center\"><td class=\"nav\" nowrap width=\"50%\"><a href=\"$ashopsurl/admin/cpanelconfigure.php\" class=\"nav\">".CPANELOPTIONS."</a></td><td class=\"nav\" nowrap width=\"50%\"><a href=\"shopcategories.php\" class=\"nav\">".EDITSHOPCATEGORIES."</a></td></tr></table></td></tr>";
	}
	echo "<tr bgcolor=\"#F0F0F0\"><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cancel\" value=\"\"><input type=\"button\" value=\"".CANCEL."\" onClick=\"document.configurationform.cancel.value='true';document.configurationform.submit();\"> <input type=\"submit\" value=\"".SUBMIT."\"";
	if ($param == "layout") echo "onClick=\"uploadmessage()\"";
	echo "></td></tr></table></form></table>$footer";
} else {
	@mysqli_close($db);
	if ($update) header("Location: configure.php?param=payment");
	else if ($updatetaxes) header("Location: configure.php?param=taxes");
	else header("Location: settings.php$passworderrorstring");
}
?>