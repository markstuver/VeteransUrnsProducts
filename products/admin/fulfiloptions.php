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

if ($userid != "1") {
	header("Location: editmember.php");
	exit;
}
include "template.inc.php";
// Get language module...
include "language/$adminlang/configure.inc.php";
include "ashopconstants.inc.php";

// Get context help for this page...
$contexthelppage = "fulfiloptions";
include "help.inc.php";

echo "$header
<script language=\"JavaScript\">
		function setStart(fulfilmentform) 
		{
		  w = window.open('setimagestart.php?image='+fulfilmentform.necardimage.value+'&fnum='+fulfilmentform.formnumber.value,'_blank','toolbar=no,location=no,width=450,height=100');
	    }
		function setEnd(fulfilmentform) 
		{
		  w = window.open('setimagestart.php?endpoint=true&image='+fulfilmentform.necardimage.value+'&fnum='+fulfilmentform.formnumber.value,'_blank','toolbar=no,location=no,width=450,height=100');
	    }
		function colorpicker(formname,fieldname) 
		{
		  w = window.open('colors.php?form='+formname+'&field='+fieldname,'_blank','toolbar=no,location=no,width=450,height=100');
	    }
		function fontselect(formname,fieldname) 
		{
		  w = window.open('fonts.php?form='+formname+'&field='+fieldname,'_blank','toolbar=no,location=no,width=350,height=200');
	    }
</script>
<div class=\"heading\">".FULFILMENTOPTIONS."</div>
        <table align=\"center\" width=\"600\" cellpadding=\"10\"><tr><td>

	<form action=\"fulfiloptions.php\" method=\"post\" name=\"fulfiloptionform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td width=\"50%\" colspan=\"2\"><a href=\"$help1\" class=\"helpnav\" target=\"_blank\">".ADDNEWFULFILMENTOPTION."</a></td></tr><tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".SELECTFULFILMENTMETHOD.":</td><td class=\"formlabel\"><select name=\"nff\">";
$findfile = opendir("$ashoppath/admin/fulfilment");
while (false !== ($foundfile = readdir($findfile))) {
	if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") {
		$splitname = explode(".", $foundfile);
		echo "<option value=\"$splitname[0]\"";
		$fp = fopen ("$ashoppath/admin/fulfilment/$foundfile","r");
		while (!feof ($fp)) {
			$fileline = fgets($fp, 4096);
			if (strstr($fileline,"\$fulfilmentname")) $fulfilmentnamestring = $fileline;
		}
		fclose($fp);
		eval ($fulfilmentnamestring);
		echo ">$fulfilmentname</option>";
	}
}
echo "</select></td></tr><tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updatefulfiloption\" value=\"new\"><input type=\"submit\" name=\"add\" value=\"".ADD."\"></td></tr></table></form><br>";

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Update selected fulfilment option...
if ($updatefulfiloption && !$delete) {
	$nparamnames = "$nff_productname|$nff_date|$nff_orderid|$nff_password|$nff_customerid|$nff_firstname|$nff_lastname|$nff_email|$nff_address|$nff_zip|$nff_city|$nff_state|$nff_country|$nff_phone|$nff_price|$nff_affiliate|$nff_shopname|$nff_shopemail|$nff_shopphone|$nff_productid";
	$nff_extrafields = str_replace(" ","",$nff_extrafields);
	$nff_extrafields = str_replace(",","&",$nff_extrafields);
	if ($updatefulfiloption == "new") $sql="INSERT INTO fulfiloptions (method) VALUES ('$nff')";
	else $sql="UPDATE fulfiloptions SET method='$nff', name='$nname', userid='$nuserid', password='$npassword', email='$nemail', message='$nmessage', url='$nurl', parameternames='$nparamnames', extrafields='$nff_extrafields', returnmessage='$nreturnmessage', perorder='$nperorder', discount='$ndiscount', discounttype='$ndiscounttype', ecardimage='$necardimage', ecardfont='$necardfont', ecardtextcolor='$necardtextcolor', ecardtexttop='$necardtexttop', ecardtextleft='$necardtextleft', ecardtextright='$necardtextright', level='$nlevel' WHERE fulfiloptionid=$updatefulfiloption";
	$result = @mysqli_query($db, "$sql");
	// Switch off per product fulfilment if the option should be per order...
	if ($nperorder == "1") $result = @mysqli_query($db, "UPDATE product SET fulfilment='' WHERE fulfilment='$updatefulfiloption'");
} else if ($updatefulfiloption && $delete) {
	$sql="DELETE FROM fulfiloptions WHERE fulfiloptionid=$updatefulfiloption";
	$result = @mysqli_query($db, "$sql");
}

// Display current fulfilment options...
$sql="SELECT * FROM fulfiloptions ORDER BY fulfiloptionid DESC";
$result = @mysqli_query($db, "$sql");
$ff = "";
for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
	$ff = @mysqli_result($result, $i, "method");
	if (file_exists("$ashoppath/admin/fulfilment/$ff.ff")) {
		$fulfiloptionid = @mysqli_result($result, $i, "fulfiloptionid");
		$fulfiloptionname = @mysqli_result($result, $i, "name");
		$fulfiloptionemail = @mysqli_result($result, $i, "email");
		$fulfiloptionmessage = @mysqli_result($result, $i, "message");
		$fulfiloptionuserid = @mysqli_result($result, $i, "userid");
		$fulfiloptionpassword = @mysqli_result($result, $i, "password");
		$fulfiloptionurl = @mysqli_result($result, $i, "url");
		$fulfiloptionparamnames = @mysqli_result($result, $i, "parameternames");
		$fulfiloptionreturnmessage = @mysqli_result($result, $i, "returnmessage");
		$fulfiloptionperorder = @mysqli_result($result, $i, "perorder");
		$fulfiloptionextrafields = str_replace("&",",",@mysqli_result($result, $i, "extrafields"));
		$fulfiloptiondiscount = @mysqli_result($result, $i, "discount");
		$fulfiloptiondiscounttype = @mysqli_result($result, $i, "discounttype");
		$fulfiloptionecardimage = @mysqli_result($result, $i, "ecardimage");
		$fulfiloptionecardfont = @mysqli_result($result, $i, "ecardfont");
		$fulfiloptionecardtextcolor = @mysqli_result($result, $i, "ecardtextcolor");
		$fulfiloptiontop = @mysqli_result($result, $i, "ecardtexttop");
		$fulfiloptionleft = @mysqli_result($result, $i, "ecardtextleft");
		$fulfiloptionbottom = @mysqli_result($result, $i, "ecardtexttop");
		$fulfiloptionright = @mysqli_result($result, $i, "ecardtextright");
		$fulfiloptionlevel = @mysqli_result($result, $i, "level");

		$dofulfilment = 0;
		$ff_parameters = array();
		include "fulfilment/$ff.ff";

		if ($ff_parameters['parameternames'] == "true" && !$fulfiloptionparamnames) $fulfiloptionparamnames = "productname|date|orderid|password|customerid|firstname|lastname|email|address|zip|city|state|country|phone|price|affiliate|shopname|shopemail|shopphone|productid";
		$ffparamnames = explode("|",$fulfiloptionparamnames);

		echo "<form action=\"fulfiloptions.php\" method=\"post\" name=\"fulfiloptionform$i\">
		<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#E0E0E0\">
		<tr><td align=\"right\" class=\"formlabel\">".OPTIONNAME.":</td><td><input type=\"text\" name=\"nname\" size=\"35\" value=\"";
		
		if ($fulfiloptionname) echo $fulfiloptionname;
		else echo "$fulfilmentname";

		echo "\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".METHOD.":</td><td class=\"formlabel\"><select name=\"nff\" onChange=\"document.fulfiloptionform$i.submit()\">";
		$findfile = opendir("$ashoppath/admin/fulfilment");
		while (false !== ($foundfile = readdir($findfile))) {
			if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") {
				$splitname = explode(".", $foundfile);
				echo "<option value=\"$splitname[0]\"";
				if ($splitname[0] == $ff) echo " selected";
				$fp = fopen ("$ashoppath/admin/fulfilment/$foundfile","r");
				while (!feof ($fp)) {
					$fileline = fgets($fp, 4096);
					if (strstr($fileline,"\$fulfilmentname")) $fulfilmentnamestring = $fileline;
				}
				fclose($fp);
				eval ($fulfilmentnamestring);
				echo ">$fulfilmentname</option>";
			}
		}
		echo "</select></td></tr>";

		if ($ff_parameters['ecardimage'] == "true") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".IMAGE.":</td><td class=\"formlabel\"><select name=\"necardimage\">";
			$findfile = opendir("$ashoppath/admin/ecards");
			while (false !== ($foundfile = readdir($findfile))) {
				if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") {
					$splitname = explode(".", $foundfile);
					if (strtolower($splitname[1]) == "jpg" || strtolower($splitname[1]) == "jpeg") {
						echo "<option value=\"$foundfile\"";
						if ($foundfile == $fulfiloptionecardimage) echo " selected";
						echo ">$foundfile</option>";
					}
				}
			}
			echo "</select></td></tr>";
		}
		if ($ff_parameters['ecardfont'] == "true") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".TEXTFONT.":</td><td><select name=\"necardfont\">";
			$findfile = opendir("$ashoppath/admin/ecards");
			while (false !== ($foundfile = readdir($findfile))) {
				if($foundfile && $foundfile != "." && $foundfile != ".." && $foundfile != ".htaccess" && !strstr($foundfile, "CVS") && substr($foundfile, 0, 1) != "_") {
					$splitname = explode(".", $foundfile);
					if (strtolower($splitname[1]) == "ttf") {
						$name = strtoupper(substr($splitname[0], 0, 1)).substr($splitname[0], 1);
						$name = str_replace("_"," ",$name);
						echo "<option value=\"$foundfile\"";
						if ($foundfile == $fulfiloptionecardfont) echo " selected";
						echo ">$name</option>";
					}
				}
			}
			echo "</select></td></tr>";
		}
		if ($ff_parameters['ecardtextcolor'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".TEXTCOLOR.":</td><td><input type=\"text\" name=\"necardtextcolor\" size=\"10\" value=\"$fulfiloptionecardtextcolor\"><a href=\"javascript:colorpicker('fulfiloptionform$i','necardtextcolor')\"><img src=\"images/colorpicker.gif\" border=\"0\" align=\"texttop\" width=\"20\" height=\"20\"></a></td></tr>";
		if ($ff_parameters['ecardtexttop'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".TEXTTOP.":</td><td><input type=\"hidden\" name=\"formnumber\" value=\"$i\"><input type=\"text\" name=\"necardtexttop\" size=\"5\" value=\"$fulfiloptiontop\"> <a href=\"javascript:setStart(document.fulfiloptionform$i)\"><img src=\"images/icon_image.gif\" border=\"0\" align=\"absbottom\"></a></td></tr>";
		if ($ff_parameters['ecardtextleft'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".TEXTLEFT.":</td><td><input type=\"text\" name=\"necardtextleft\" size=\"5\" value=\"$fulfiloptionleft\"> <img src=\"images/addarrow.gif\" align=\"top\"></td></tr>";
		if ($ff_parameters['ecardtextright'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".TEXTBOTTOMRIGHT.":</td><td><input type=\"text\" name=\"necardtextright\" size=\"5\" value=\"$fulfiloptionright\"> <a href=\"javascript:setEnd(document.fulfiloptionform$i)\"><img src=\"images/icon_image.gif\" border=\"0\" align=\"texttop\"></a></td></tr>";

		if ($ff == "automation" || $ff == "kunaki-automation" || $ff == "affiliate") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".RUNONCE.": </td><td class=\"formlabel\"><select name=\"nperorder\"><option value=\"0\"";
			if (!$fulfiloptionperorder) echo " selected";
			echo "> ".PERPRODUCT."<option value=\"1\"";
			if ($fulfiloptionperorder == "1") echo " selected";
			echo ">".PERORDER."</select></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nperorder\" value=\"\">";

		if ($ff_parameters['discount'] == "true") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".DISCOUNT.": </td><td class=\"formlabel\"><input type=\"text\" name=\"ndiscount\" value=\"$fulfiloptiondiscount\" size=\"7\"><input type=\"radio\" name=\"ndiscounttype\" value=\"%\"";
			  if ($fulfiloptiondiscounttype=="%" || !$fulfiloptiondiscounttype) echo "checked";
			  echo ">% <input type=\"radio\" name=\"ndiscounttype\" value=\"$\"";
			  if ($fulfiloptiondiscounttype=="$") echo "checked";
			  echo ">";
			  if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			  else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			  echo "</td></tr>";
		} else echo "<input type=\"hidden\" name=\"ndiscount\" value=\"\"><input type=\"hidden\" name=\"ndiscounttype\" value=\"\">";

		if ($ff_parameters['returnresult'] == "true") {
			if ($ff == "s2member" || $ff == "affiliate") echo "<input type=\"hidden\" name=\"nreturnmessage\" value=\"1\">";
			else {
				echo "<tr><td align=\"right\" class=\"formlabel\">&nbsp;</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"nreturnmessage\" value=\"1\"";
				if ($fulfiloptionreturnmessage == "1") echo " checked";
				echo "> ".INCLUDERETURNEDDATA."</td></tr>";
			}
		} else echo "<input type=\"hidden\" name=\"nreturnmessage\" value=\"\">";

		if ($ff_parameters['level'] == "true") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".COMMISSIONLEVEL.":</td><td class=\"formlabel\"><select name=\"nlevel\"><option value=\"1\"";
			if ($fulfiloptionlevel == "1") echo " selected";
			echo ">".NORMAL."</option><option value=\"2\"";
			if ($fulfiloptionlevel == "2") echo " selected";
			echo ">".UPGRADED."</option></select>
			</td></tr>";
		} else echo "<input type=\"hidden\" name=\"nlevel\" value=\"\">";

		if ($ff_parameters['userid'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".USERID.":</td><td><input type=\"text\" name=\"nuserid\" size=\"35\" value=\"$fulfiloptionuserid\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nuserid\" value=\"\">";

		if ($ff_parameters['password'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".PASSWORD.":</td><td><input type=\"text\" name=\"npassword\" size=\"35\" value=\"$fulfiloptionpassword\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"npassword\" value=\"\">";

		if ($ff_parameters['fulfilemail'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".EMAILADDRESS.":</td><td><input type=\"text\" name=\"nemail\" size=\"35\" value=\"$fulfiloptionemail\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nemail\" value=\"\">";

		if ($ff_parameters['fulfilmessage'] == "true") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".ADDITIONALMESSAGETEXT.":</td><td class=\"formlabel\"><textarea name=\"nmessage\" cols=\"30\" rows=\"5\">$fulfiloptionmessage</textarea></td></tr>";
		} else echo "<input type=\"hidden\" name=\"nmessage\" value=\"\">";

		if ($ff_parameters['url'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".URL.":</td><td><input type=\"text\" name=\"nurl\" size=\"40\" value=\"$fulfiloptionurl\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nurl\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".PRODUCT.":</td><td><input type=\"text\" name=\"nff_productname\" size=\"30\" value=\"{$ffparamnames[0]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_productname\" value=\"\">";
		
		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".PRODUCTID.":</td><td><input type=\"text\" name=\"nff_productid\" size=\"30\" value=\"{$ffparamnames[19]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_productid\" value=\"\">";		

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".THEWORDDATE.":</td><td><input type=\"text\" name=\"nff_date\" size=\"30\" value=\"{$ffparamnames[1]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_date\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".ORDERID.":</td><td><input type=\"text\" name=\"nff_orderid\" size=\"30\" value=\"{$ffparamnames[2]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_orderid\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".PASSWORD2.":</td><td><input type=\"text\" name=\"nff_password\" size=\"30\" value=\"{$ffparamnames[3]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_password\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".CUSTOMERID.":</td><td><input type=\"text\" name=\"nff_customerid\" size=\"30\" value=\"{$ffparamnames[4]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_customerid\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".FIRSTNAME.":</td><td><input type=\"text\" name=\"nff_firstname\" size=\"30\" value=\"{$ffparamnames[5]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_firstname\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".LASTNAME.":</td><td><input type=\"text\" name=\"nff_lastname\" size=\"30\" value=\"{$ffparamnames[6]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_lastname\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".EMAIL2.":</td><td><input type=\"text\" name=\"nff_email\" size=\"30\" value=\"{$ffparamnames[7]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_email\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".ADDRESS.":</td><td><input type=\"text\" name=\"nff_address\" size=\"30\" value=\"{$ffparamnames[8]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_address\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".ZIP.":</td><td><input type=\"text\" name=\"nff_zip\" size=\"30\" value=\"{$ffparamnames[9]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_zip\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".CITY.":</td><td><input type=\"text\" name=\"nff_city\" size=\"30\" value=\"{$ffparamnames[10]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_city\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".STATE.":</td><td><input type=\"text\" name=\"nff_state\" size=\"30\" value=\"{$ffparamnames[11]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_state\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".COUNTRY.":</td><td><input type=\"text\" name=\"nff_country\" size=\"30\" value=\"{$ffparamnames[12]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_country\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".PHONE.":</td><td><input type=\"text\" name=\"nff_phone\" size=\"30\" value=\"{$ffparamnames[13]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_phone\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".PRICE.":</td><td><input type=\"text\" name=\"nff_price\" size=\"30\" value=\"{$ffparamnames[14]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_price\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".AFFILIATEID.":</td><td><input type=\"text\" name=\"nff_affiliate\" size=\"30\" value=\"{$ffparamnames[15]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_affiliate\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".SHOPNAME2.":</td><td><input type=\"text\" name=\"nff_shopname\" size=\"30\" value=\"{$ffparamnames[16]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_shopname\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".SHOPEMAIL.":</td><td><input type=\"text\" name=\"nff_shopemail\" size=\"30\" value=\"{$ffparamnames[17]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_shopemail\" value=\"\">";

		if ($ff_parameters['parameternames'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".FIELDNAME2." - ".SHOPPHONE2.":</td><td><input type=\"text\" name=\"nff_shopphone\" size=\"30\" value=\"{$ffparamnames[18]}\"></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_shopphone\" value=\"\">";

		if ($ff_parameters['extrafields'] == "true") echo "<tr><td align=\"right\" class=\"formlabel\">".OTHERFIELDSVALUES.":</td><td><textarea name=\"nff_extrafields\" cols=\"35\" rows=\"2\">$fulfiloptionextrafields</textarea></td></tr>";
		else echo "<input type=\"hidden\" name=\"nff_extrafields\" value=\"\">";

		echo "<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"updatefulfiloption\" value=\"$fulfiloptionid\"><input type=\"submit\" name=\"update\" value=\"".UPDATE."\"> <input type=\"submit\" name=\"delete\" value=\"".THEWORDDELETE."\"></td></tr></table></form><br>";
	}
}

// Close database...
@mysqli_close($db);

echo "</table>$footer";
?>