<?php
// AShop
// Copyright 2015 - AShop Software - http://www.ashopsoftware.com
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
include "ashopconstants.inc.php";
include "checklogin.inc.php";
// Get context help for this page...
$contexthelppage = "editproduct";
include "help.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";

// Get listmessenger groups if applicable...
function parselmconfigstring($lmconfigstring) {
	$returnstring = "";
	$returnstring = substr($lmconfigstring, strpos($lmconfigstring, "\"")+1);
	$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
	$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
	$returnstring = substr($returnstring, 0, strpos($returnstring, "\""));
	return $returnstring;
}
if ($listmessengerpath && !file_exists("$listmessengerpath/config.inc.php") && file_exists("$listmessengerpath/includes/config.inc.php")) {
	$listmessengerversion = "pro";
	$listmessengerpath .= "/includes";
}
if ($listmessengerpath && file_exists("$listmessengerpath/config.inc.php")) {
	$fp = fopen ("$listmessengerpath/config.inc.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "DATABASE_HOST")) {
			$lmhost = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "DATABASE_NAME")) {
			$lmname = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "DATABASE_USER")) {
			$lmuser = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "DATABASE_PASS")) {
			$lmpass = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "TABLES_PREFIX")) {
			$lmprefix = parselmconfigstring($buffer);
		}
	}
	fclose ($fp);
	$lmdb = @mysqli_connect("$lmhost", "$lmuser", "$lmpass", "$lmname");
	if ($listmessengerversion == "pro") $sql = "SELECT * FROM {$lmprefix}groups";
	else $sql = "SELECT * FROM {$lmprefix}user_groups";
	$result = @mysqli_query($lmdb,$sql);
	$lmselectstring = "";
	if (@mysqli_num_rows($result)) {
		$lmselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".LISTMESSENGERGROUP.":</td><td class=\"formlabel\"><select name=\"lmgroup\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			if ($listmessengerversion == "pro") $lmgroupid = @mysqli_result($result, $i, "groups_id");
			else $lmgroupid = @mysqli_result($result, $i, "group_id");
			$lmgroupname = @mysqli_result($result, $i, "group_name");
			$lmselectstring .= "<option value=\"$lmgroupid\">$lmgroupname</option>";
		}
		$lmselectstring .= "</select></td></tr>";
	}
	@mysqli_close($lmdb);
}

// Get MailChimp lists if applicable...
if ($mailchimpapikey) {
	require_once "../includes/MCAPI.class.php";
	$api = new MCAPI($mailchimpapikey);
	$retval = $api->lists();
	if (!$api->errorCode){
		$mcselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ".MAILCHIMPLIST.":</td><td class=\"formlabel\"><select name=\"mclist\"><option value=\"0\">".NONE."</option>";
		foreach ($retval['data'] as $list){
			$mcselectstring .= "<option value=\"".$list['id']."\">".$list['name']."</option>";
		}
		$mcselectstring .= "</select></td></tr>";
	}
}

// Get phpbb groups if applicable...
if ($phpbbpath && file_exists("$phpbbpath/config.php")) {
	$fp = fopen ("$phpbbpath/config.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$db_host") == 0 && is_integer(strpos($buffer, "\$db_host"))) {
			$phpbbhost = substr($buffer, strpos($buffer, "'")+1);
			$phpbbhost = substr($phpbbhost, 0, strpos($phpbbhost, "'"));
		}
		if (strpos($buffer, "\$db_name") == 0 && is_integer(strpos($buffer, "\$db_name"))) {
			$phpbbname = substr($buffer, strpos($buffer, "'")+1);
			$phpbbname = substr($phpbbname, 0, strpos($phpbbname, "'"));
		}
		if (strpos($buffer, "\$db_username") == 0 && is_integer(strpos($buffer, "\$db_username"))) {
			$phpbbuser = substr($buffer, strpos($buffer, "'")+1);
			$phpbbuser = substr($phpbbuser, 0, strpos($phpbbuser, "'"));
		}
		if (strpos($buffer, "\$db_password") == 0 && is_integer(strpos($buffer, "\$db_password"))) {
			$phpbbpass = substr($buffer, strpos($buffer, "'")+1);
			$phpbbpass = substr($phpbbpass, 0, strpos($phpbbpass, "'"));
		}
		if (strpos($buffer, "\$db_prefix") == 0 && is_integer(strpos($buffer, "\$db_prefix"))) {
			$phpbbtablepref = substr($buffer, strpos($buffer, "'")+1);
			$phpbbtablepref = substr($phpbbtablepref, 0, strpos($phpbbtablepref, "'"));
		}
		if (strpos($buffer, "\$db_type") == 0 && is_integer(strpos($buffer, "\$db_type"))) {
			$phpbbdbms = substr($buffer, strpos($buffer, "'")+1);
			$phpbbdbms = substr($phpbbdbms, 0, strpos($phpbbdbms, "'"));
		}
	}
	fclose ($fp);
	if (stristr($phpbbdbms, "mysql")) {
		$phpbbdb = @mysqli_connect("$phpbbhost", "$phpbbuser", "$phpbbpass", "$phpbbname");
		$sql = "SELECT * FROM $phpbbtablepref"."groups";
		$result = @mysqli_query($phpbbdb,$sql);
		$phpbbselectstring = "";
		if (@mysqli_num_rows($result)) {
			$phpbbselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a> ".PHPBBUSERGROUP.":</td><td class=\"formlabel\"><select name=\"phpbbgroup\"><option value=\"0\">".NONE."</option>";
			for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
				$phpbbgroupid = @mysqli_result($result, $i, "g_id");
				$phpbbgroupname = @mysqli_result($result, $i, "g_title");
				$phpbbselectstring .= "<option value=\"$phpbbgroupid\">$phpbbgroupname</option>";
			}
			$phpbbselectstring .= "</select></td></tr>";
		}
		@mysqli_close($phpbbdb);
	}
}

// Get ARP3 autoresponders if applicable...
if ($arpluspath && file_exists("$arpluspath/arp3-config.pl")) {
	$fp = fopen ("$arpluspath/arp3-config.pl", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$db_host") == 0 && is_integer(strpos($buffer, "\$db_host"))) {
			$arphost = substr($buffer, strpos($buffer, "\"")+1);
			$arphost = substr($arphost, 0, strpos($arphost, "\""));
		}
		if (strpos($buffer, "\$db_name") == 0 && is_integer(strpos($buffer, "\$db_name"))) {
			$arpname = substr($buffer, strpos($buffer, "\"")+1);
			$arpname = substr($arpname, 0, strpos($arpname, "\""));
		}
		if (strpos($buffer, "\$db_login") == 0 && is_integer(strpos($buffer, "\$db_login"))) {
			$arpuser = substr($buffer, strpos($buffer, "\"")+1);
			$arpuser = substr($arpuser, 0, strpos($arpuser, "\""));
		}
		if (strpos($buffer, "\$db_password") == 0 && is_integer(strpos($buffer, "\$db_password"))) {
			$arppass = substr($buffer, strpos($buffer, "\"")+1);
			$arppass = substr($arppass, 0, strpos($arppass, "\""));
		}
		if (strpos($buffer, "\$db_table_AUT") == 0 && is_integer(strpos($buffer, "\$db_table_AUT"))) {
			$arptable = substr($buffer, strpos($buffer, "'")+1);
			$arptable = substr($arptable, 0, strpos($arptable, "'"));
		}
		if (strpos($buffer, "\$_your_domain_name") == 0 && is_integer(strpos($buffer, "\$_your_domain_name"))) {
			$arpdomain = substr($buffer, strpos($buffer, "\"")+1);
			$arpdomain = substr($arpdomain, 0, strpos($arpdomain, "\""));
		}
	}
	fclose ($fp);
	$arpdb = @mysqli_connect("$arphost", "$arpuser", "$arppass", "$arpname");
	$sql = "SELECT * FROM $arptable";
	$result = @mysqli_query($arpdb,$sql);
	$arpselectstring = "";
	if (@mysqli_num_rows($result)) {
		$arpselectstring = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3a\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> ".ARP3AUTORESPONDER.":</td><td class=\"formlabel\"><select name=\"arpresponder\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$arprespid = @mysqli_result($result, $i, "id");
			$arprespname = @mysqli_result($result, $i, "listens_on")."@$arpdomain";
			$arpselectstring .= "<option value=\"$arprespid\">$arprespname</option>";
		}
		$arpselectstring .= "</select></td></tr>";
	}
	@mysqli_close($arpdb);
}

// Get ARP Reach autoresponders if applicable...
if ($arpreachpath && file_exists("$arpreachpath/config.php")) {
	$fp = fopen ("$arpreachpath/config.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$config['database_host']") == 0 && is_integer(strpos($buffer, "\$config['database_host']"))) {
			$arpreachhost = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachhost = substr($arpreachhost, 0, strpos($arpreachhost, "'"));
		}
		if (strpos($buffer, "\$config['database_name']") == 0 && is_integer(strpos($buffer, "\$config['database_name']"))) {
			$arpreachname = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachname = substr($arpreachname, 0, strpos($arpreachname, "'"));
		}
		if (strpos($buffer, "\$config['database_username']") == 0 && is_integer(strpos($buffer, "\$config['database_username']"))) {
			$arpreachuser = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachuser = substr($arpreachuser, 0, strpos($arpreachuser, "'"));
		}
		if (strpos($buffer, "\$config['database_password']") == 0 && is_integer(strpos($buffer, "\$config['database_password']"))) {
			$arpreachpass = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachpass = substr($arpreachpass, 0, strpos($arpreachpass, "'"));
		}
		if (strpos($buffer, "\$config['database_table_prefix']") == 0 && is_integer(strpos($buffer, "\$config['database_table_prefix']"))) {
			$arpreachtable = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachtable = substr($arpreachtable, 0, strpos($arpreachtable, "'"));
		}
		if (strpos($buffer, "\$config['application_url']") == 0 && is_integer(strpos($buffer, "\$config['application_url']"))) {
			$arpreachurl = substr($buffer, strpos($buffer, " = '")+4);
			$arpreachurl = substr($arpreachurl, 0, strpos($arpreachurl, "'"));
		}
	}
	fclose ($fp);
	$arpreachdb = @mysqli_connect("$arpreachhost", "$arpreachuser", "$arpreachpass", "$arpreachname");
	$sql = "SELECT name FROM {$arpreachtable}actions WHERE event_type='6' AND enabled='1'";
	$result = @mysqli_query($arpreachdb,$sql);
	$arpreachselectstring = "";
	if (@mysqli_num_rows($result)) {
		$arpreachselectstring = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3a\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> ARP Reach Action:</td><td class=\"formlabel\"><select name=\"arpreachresponder\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$arpreachrespid = @mysqli_result($result, $i, "id");
			$arpreachrespname = @mysqli_result($result, $i, "name");
			$arpreachselectstring .= "<option value=\"$arpreachrespid\">$arpreachrespname</option>";
		}
		$arpreachselectstring .= "</select></td></tr>";
	}
	@mysqli_close($arpreachdb);
}

// Get Infinity Responder autoresponders if applicable...
if ($infinitypath && file_exists("$infinitypath/config.php")) {
	$fp = fopen ("$infinitypath/config.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$MySQL_server") == 0 && is_integer(strpos($buffer, "\$MySQL_server"))) {
			$infinityhost = substr($buffer, strpos($buffer, "'")+1);
			$infinityhost = substr($infinityhost, 0, strpos($infinityhost, "\""));
		}
		if (strpos($buffer, "\$MySQL_database") == 0 && is_integer(strpos($buffer, "\$MySQL_database"))) {
			$infinityname = substr($buffer, strpos($buffer, "'")+1);
			$infinityname = substr($infinityname, 0, strpos($infinityname, "'"));
		}
		if (strpos($buffer, "\$MySQL_user") == 0 && is_integer(strpos($buffer, "\$MySQL_user"))) {
			$infinityuser = substr($buffer, strpos($buffer, "'")+1);
			$infinityuser = substr($infinityuser, 0, strpos($infinityuser, "'"));
		}
		if (strpos($buffer, "\$MySQL_password") == 0 && is_integer(strpos($buffer, "\$MySQL_password"))) {
			$infinitypass = substr($buffer, strpos($buffer, "'")+1);
			$infinitypass = substr($infinitypass, 0, strpos($infinitypass, "'"));
		}
	}
	fclose ($fp);
	$infinitydb = @mysqli_connect("$infinityhost", "$infinityuser", "$infinitypass", "$infinityname");
	$sql = "SELECT * FROM InfResp_responders ORDER BY Name";
	$result = @mysqli_query($infinitydb,$sql);
	$infinityselectstring = "";
	if (@mysqli_num_rows($result)) {
		$infinityselectstring1 = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3b','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3b\" align=\"absmiddle\" onclick=\"return overlib('$tip3b');\" onmouseout=\"return nd();\"></a> ".ADDTOINFINITYRESPONDER.":</td><td class=\"formlabel\"><select name=\"infinityresponder\"><option value=\"0\">".NONE."</option>";
		$infinityselectstring2 = "<tr><td class=\"formlabel\" align=\"right\">".REMOVEFROMINFINITYRESPONDER.":</td><td class=\"formlabel\"><select name=\"infinityresponderoff\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$infinityrespid = @mysqli_result($result, $i, "ResponderID");
			$infinityrespname = @mysqli_result($result, $i, "Name");
			$infinityselectstring1 .= "<option value=\"$infinityrespid\">$infinityrespname</option>";
			$infinityselectstring2 .= "<option value=\"$infinityrespid\">$infinityrespname</option>";
		}
		$infinityselectstring1 .= "</select></td></tr>";
		$infinityselectstring2 .= "</select></td></tr>";
	}
	@mysqli_close($infinitydb);
}

// Get Interspire Email Marketer lists if applicable...
if ($iemurl && $iemuser && $iemtoken) {
	$iemselectstring = "";
	$iemxml = "<xmlrequest><username>$iemuser</username><usertoken>$iemtoken</usertoken><requesttype>lists</requesttype><requestmethod>GetLists</requestmethod><details> </details></xmlrequest>";
	$iemch = @curl_init($iemurl);
	curl_setopt($iemch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($iemch, CURLOPT_POST, 1);
	curl_setopt($iemch, CURLOPT_POSTFIELDS, $iemxml);
	$iemresult = @curl_exec($iemch);
	if($iemresult === false) {}
	else {
		$iemselectstring .= "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3d','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3d\" align=\"absmiddle\" onclick=\"return overlib('$tip3d');\" onmouseout=\"return nd();\"></a> ".EMAILMARKETERLIST.":</td><td class=\"formlabel\"><select name=\"iemlist\"><option value=\"0\">".NONE."</option>";
		if (strpos($iemresult,"<item>")) {
			$iemresultarray = explode("<item>",$iemresult);
			foreach($iemresultarray as $iempartnumber=>$iemxmlpart) {
				$iemlistid = 0;
				$iemlistname = "";
				if (strpos($iemxmlpart,"<listid>")) {
					$iemsubresultarray = explode("<listid>",$iemxmlpart);
					$iemsubresultarray = explode("</listid>",$iemsubresultarray[1]);
					$iemlistid = $iemsubresultarray[0];
				}
				if (strpos($iemxmlpart,"<name>")) {
					$iemsubresultarray = explode("<name>",$iemxmlpart);
					$iemsubresultarray = explode("</name>",$iemsubresultarray[1]);
					$iemlistname = $iemsubresultarray[0];
				}
				if ($iemlistid && $iemlistname) $iemselectstring .= "<option value=\"$iemlistid\">$iemlistname</option>";
			}
		}
		$iemselectstring .= "</select></td></tr>";
	}
}

   $db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Generate Digital Mall member list if needed...
if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
	// Check if we are in a member owned category...
	$result = @mysqli_query($db,"SELECT userid FROM category WHERE categoryid='$cat'");
	$catuser = @mysqli_result($result, 0, "userid");
	$memberlist = "<select name=\"memberid\"><option value=\"1\">".ADMINISTRATOR;
	$result = @mysqli_query($db,"SELECT * FROM user WHERE userid>1 ORDER BY shopname");
	while ($row = @mysqli_fetch_array($result)) {
		$memberlist .= "<option value=\"{$row["userid"]}\"";
		if ($catuser == $row["userid"]) $memberlist .= " selected";
		$memberlist .= ">{$row["shopname"]}";
	}
	$memberlist .= "</select>";
}

// Generate floating price activation time selectors...
$fp_activate = time();
$fp_activatemonth = date("m",$fp_activate);
$fp_activateday = date("d",$fp_activate);
$fp_activatehour = date("H",$fp_activate);
$fp_activateminute = date("i",$fp_activate);
$fp_activatestring = " <select name=\"activatemonth\"><option value=\"01\"";
if ($fp_activatemonth == "01") $fp_activatestring .= " selected";
$fp_activatestring .= ">".JAN."</option><option value=\"02\"";
if ($fp_activatemonth == "02") $fp_activatestring .= " selected";
$fp_activatestring .= ">".FEB."</option><option value=\"03\"";
if ($fp_activatemonth == "03") $fp_activatestring .= " selected";
$fp_activatestring .= ">".MAR."</option><option value=\"04\"";
if ($fp_activatemonth == "04") $fp_activatestring .= " selected";
$fp_activatestring .= ">".APR."</option><option value=\"05\"";
if ($fp_activatemonth == "05") $fp_activatestring .= " selected";
$fp_activatestring .= ">".MAY."</option><option value=\"06\"";
if ($fp_activatemonth == "06") $fp_activatestring .= " selected";
$fp_activatestring .= ">".JUN."</option><option value=\"07\"";
if ($fp_activatemonth == "07") $fp_activatestring .= " selected";
$fp_activatestring .= ">".JUL."</option><option value=\"08\"";
if ($fp_activatemonth == "08") $fp_activatestring .= " selected";
$fp_activatestring .= ">".AUG."</option><option value=\"09\"";
if ($fp_activatemonth == "09") $fp_activatestring .= " selected";
$fp_activatestring .= ">".SEP."</option><option value=\"10\"";
if ($fp_activatemonth == "10") $fp_activatestring .= " selected";
$fp_activatestring .= ">".OCT."</option><option value=\"11\"";
if ($fp_activatemonth == "11") $fp_activatestring .= " selected";
$fp_activatestring .= ">".NOV."</option><option value=\"12\"";
if ($fp_activatemonth == "12") $fp_activatestring .= " selected";
$fp_activatestring .= ">".DEC."</option></select>";
$fp_activatestring .= " <input type=\"text\" size=\"2\" name=\"activateday\" value=\"$fp_activateday\">";
$fp_activatestring .= " ".HOUR.": <input type=\"text\" size=\"2\" name=\"activatehour\" value=\"$fp_activatehour\">";
$fp_activatestring .= " ".MINUTE.": <input type=\"text\" size=\"2\" name=\"activateminute\" value=\"$fp_activateminute\">";

   // Make sure special characters are handled properly...
   $name = str_replace("\"", "&quot;", $name);
   //$name = str_replace("'", "&#039;", $name);
   //$description = str_replace("'", "&#039;", $description);

if (!$name || $price == '' || $addlicense || $addfloatingprice || $removefloatingprice) {
	if (!empty($description)) $description = str_replace("\\\"","\"",$description);

  $sql="SELECT name FROM category WHERE categoryid = $cat";
  $result = @mysqli_query($db,$sql);
  $categoryname = @mysqli_result($result, $j, "name");
  echo "$header";
	  if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) {
		  echo "
<script type=\"text/javascript\" src=\"ckeditor/ckeditor.js\"></script>
";
	  }
  echo "
        <div class=\"heading\">".ADDPRODUCTTOCATEGORY.": $categoryname</div>
        <table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><form action=\"addproduct.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">
       <table width=\"670\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		<tr><td align=\"right\" class=\"formlabel\" width=\"200\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a> ".NAME.":</td><td align=\"left\"><input type=\"text\" name=\"name\" size=\"35\" value=\"$name\"><script language=\"JavaScript\">document.productform.name.focus();</script></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") echo "<tr><td align=\"right\" class=\"formlabel\">".OWNERMEMBER.":</td><td align=\"left\">$memberlist</td></tr>";
		if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> ".CATALOGSTATUS.":</td><td class=\"formlabel\" align=\"left\"><input type=\"checkbox\" name=\"active\" checked> ".ACTIVE; if($wholesalecatalog && (!$addfloatingprice && (!$fp_length || $removefloatingprice))) echo " ".RETAIL." <input type=\"checkbox\" name=\"wholesale\" checked> ".WHOLESALE;
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".FEATUREDSPOT.":</td><td align=\"left\"><select name=\"featured\"><option value=\"0\">".NO."</option>";
			for ($featuredspot = 1; $featuredspot <= $numberoffeatures; $featuredspot++) {
				echo "<option value=\"$featuredspot\">$featuredspot</option>";
			}
			echo "</select></td></tr>
			<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image6','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image6\" align=\"absmiddle\" onclick=\"return overlib('$tip6');\" onmouseout=\"return nd();\"></a> <a href=\"$help6\" class=\"helpnav2\" target=\"_blank\">".EBAYID."</a>:</td><td align=\"left\"><input type=\"text\" name=\"ebayid\" size=\"20\" value=\"$ebayid\"><span class=\"sm\"> ".OPTIONAL."</span></td></tr>";
		}

		// Show price parameters...        
		if ($addfloatingprice || ($fp_length && !$removefloatingprice)) echo "<tr><td align=\"right\" class=\"formlabel\">".STARTPRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"price\" size=\"10\" value=\"$price\"> ".$currencysymbols[$ashopcurrency]["post"]."<input type=\"hidden\" name=\"removefloatingprice\" value=\"\"><input type=\"button\" value=\"".FIXEDPRICE."\" onClick=\"document.productform.removefloatingprice.value='true';document.productform.submit();\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".BIDINCREMENT.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"priceincrement\" size=\"10\" value=\"$priceincrement\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".LENGTH.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"bidlengthdays\" size=\"2\" value=\"$bidlengthdays\"> ".DAYS.", <input type=\"text\" name=\"bidlengthhours\" size=\"2\" value=\"$bidlengthhours\"> ".HOURS.", <input type=\"text\" name=\"bidlengthminutes\" size=\"2\" value=\"$bidlengthminutes\"> ".MINUTES.", <input type=\"text\" name=\"bidlengthseconds\" size=\"2\" value=\"$bidlengthseconds\"> ".SECONDS."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".ACTIVATE.":</td><td class=\"formlabel\" align=\"left\">$fp_activatestring</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">".AUCTIONTYPE.":</td><td class=\"formlabel\" align=\"left\"><select name=\"auctiontype\"><option value=\"standard\">".STANDARD."</option><option value=\"penny\">".PENNY."</option></td></tr>";

		else {
			echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> ".PRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"price\" size=\"10\" value=\"$price\"> ".$currencysymbols[$ashopcurrency]["post"].
			" <input type=\"hidden\" name=\"addfloatingprice\" value=\"\"><input type=\"button\" value=\"".AUCTION."\" onClick=\"document.productform.addfloatingprice.value='true';document.productform.submit();\">
			</td></tr>";
		}

		if ($wholesalecatalog && ($advancedmallmode == "1" || $userid == "1")) {
			echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image8','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image8\" align=\"absmiddle\" onclick=\"return overlib('$tip8');\" onmouseout=\"return nd();\"></a> ".WHOLESALEPRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"wholesaleprice\" size=\"10\" value=\"$wholesaleprice\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			if ($pricelevels > 1) {
				for ($thislevel = 2; $thislevel <= $pricelevels; $thislevel++) {
					echo "<tr><td align=\"right\" class=\"formlabel\">".WHOLESALEPRICELEVEL." $thislevel:</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"wholesaleprice$thislevel\" size=\"10\" value=\"{$_POST["wholesaleprice$thislevel"]}\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
				}
			}
		}
		if ($userid == "1" && file_exists("$ashoppath/emerchant/quote.php")) $billtemplateresult = @mysqli_query($db,"SELECT * FROM emerchant_billtemplates ORDER BY name");
		$recurringresult = @mysqli_query($db,"SELECT recurringperiod FROM payoptions WHERE (gateway='ccbill' OR gateway='paypal' OR gateway='payza' OR gateway='netbillingrecurring') AND recurringperiod IS NOT NULL AND recurringperiod!=''");
		if (@mysqli_num_rows($billtemplateresult) || @mysqli_num_rows($recurringresult)) {
			if (empty($nrecurringperiod)) {
				$nrecurringperiod = @mysqli_result($recurringresult, 0, "recurringperiod");
				$nrecurringperiod = explode("|",$nrecurringperiod);
				$nrecurringperiodunits = $nrecurringperiod[1];
				$nrecurringperiod = $nrecurringperiod[0];
			}
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".RECURRINGPRICE.":</td><td class=\"formlabel\" align=\"left\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"recurringprice\" size=\"10\" value=\"$recurringprice\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
			echo "<tr><td align=\"right\" class=\"formlabel\">".RECURRINGPERIOD.":</td><td><input type=\"text\" name=\"nrecurringperiod\" size=\"5\" value=\"$nrecurringperiod\"> <select name=\"nrecurringperiodunits\"><option value=\"D\""; if ($nrecurringperiodunits == "D") echo " selected"; echo ">".DAYS."</option><option value=\"W\""; if ($nrecurringperiodunits == "W") echo " selected"; echo ">".WEEKS."</option><option value=\"M\""; if ($nrecurringperiodunits == "M") echo " selected"; echo ">".MONTHS."</option><option value=\"Y\""; if ($nrecurringperiodunits == "Y") echo " selected"; echo ">".YEARS."</option></select></td></tr>";
		}
		if ($userid == "1" && @mysqli_num_rows($billtemplateresult)) {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".BILLINGTEMPLATE."</a>:</td><td class=\"formlabel\" align=\"left\"><select name=\"billtemplate\"><option value=\"none\" selected>".NONE;
			while ($billtemplaterow = @mysqli_fetch_array($billtemplateresult)) {
				$billtemplatename = $billtemplaterow["name"];
				$billtemplateid = $billtemplaterow["billtemplateid"];
				echo "<option value=\"$billtemplateid\">$billtemplatename";
			}
			echo "</select></td></tr>";
		}
        echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">".RECEIPTTEXT.":</td><td align=\"left\"><textarea name=\"receipttext\" cols=\"30\" rows=\"2\">$receipttext</textarea></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> ".DESCRIPTION.":</td><td align=\"left\"><textarea name=\"description\" id=\"id_description\" class=\"ckeditor\" cols=\"30\" rows=\"15\">";
		if ($userid != "1" && !$description) echo "$memberproducttemplate";
		else echo "$description";
		echo "</textarea>";
		if (is_dir("$ashoppath/admin/ckeditor") && file_exists("$ashoppath/admin/ckeditor/ckeditor.js")) echo "<script type=\"text/javascript\">
		CKEDITOR.replace( 'id_description', {
			// Define the toolbar groups as it is a more accessible solution.
			toolbarGroups: [
				{\"name\":\"basicstyles\",\"groups\":[\"basicstyles\"]},
				{\"name\":\"links\",\"groups\":[\"links\"]},
				{\"name\":\"paragraph\",\"groups\":[\"list\",\"blocks\"]},
				{\"name\":\"document\",\"groups\":[\"mode\"]},
				{\"name\":\"insert\",\"groups\":[\"insert\"]},
				{\"name\":\"styles\",\"groups\":[\"styles\"]},
				{\"name\":\"colors\",\"groups\":[\"colors\"]}
			],
			// Remove the redundant buttons from toolbar groups defined above.
			removeButtons: 'Underline,Strike,Subscript,Superscript,Anchor,Styles,Specialchar'
		} );
		</script>";
		echo "</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a> <a href=\"$help10\" class=\"helpnav2\" target=\"_blank\">".LICENSEAGREEMENT."</a>:</td><td align=\"left\">";
		if ($addlicense) echo "<textarea name=\"licensetext\" cols=\"30\" rows=\"5\"></textarea>";
		else echo "<input type=\"hidden\" name=\"addlicense\" value=\"\"><input type=\"button\" class=\"widebutton\" value=\"".ADDLICENSEAGREEMENT."\" onClick=\"document.productform.addlicense.value='true';document.productform.submit();\">";
		echo "</td></tr>
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image14','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image14\" align=\"absmiddle\" onclick=\"return overlib('$tip14');\" onmouseout=\"return nd();\"></a> <a href=\"$help14\" class=\"helpnav2\" target=\"_blank\">".AFFILIATECOMMISSION."</a>:</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatecom\" size=\"7\" value=\"$affiliatepercent\"><input type=\"radio\" name=\"affcomtype\" value=\"percent\" checked>% <input type=\"radio\" name=\"affcomtype\" value=\"money\">";
		if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
		else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
		echo "</td></tr>\n";
		if ($wholesaleaffiliate == "1") {
			echo "
			<tr><td align=\"right\" class=\"formlabel\">".WSAFFILIATECOMMISSION."</a>:</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatewscom\" size=\"7\" value=\"$wholesalepercent\"><input type=\"radio\" name=\"affwscomtype\" value=\"percent\" checked>% <input type=\"radio\" name=\"affwscomtype\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>\n";
		}
		if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\">".UPGRADEDAFFILIATECOMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatecom2\" size=\"7\" value=\"$affiliatepercent2\"><input type=\"radio\" name=\"affcomtype2\" value=\"percent\" checked>% <input type=\"radio\" name=\"affcomtype2\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".TIER2COMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatetier2com\" size=\"7\" value=\"$secondtierpercent\"><input type=\"radio\" name=\"afftier2comtype\" value=\"percent\" checked>% <input type=\"radio\" name=\"afftier2comtype\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".UPGRADEDTIER2COMMISSION.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatetier2com2\" size=\"7\" value=\"$secondtierpercent2\"><input type=\"radio\" name=\"afftier2comtype2\" value=\"percent\" checked>% <input type=\"radio\" name=\"afftier2comtype2\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".LOWERBY.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" name=\"affiliatetierlowerby\" size=\"7\" value=\"0\"> ".ONEACHTIER." <span class=\"sm\"> ".DISABLEMULTITIER."</span></td></tr>
			<tr><td align=\"right\" class=\"formlabel\">".ONREPEATORDERS.":</td><td class=\"formlabel\" align=\"left\"><select name=\"affiliaterepeatcommission\"><option value=\"1\">Yes</option><option value=\"0\">No</option></select></td></tr>
			<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image15','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image15\" align=\"absmiddle\" onclick=\"return overlib('$tip15');\" onmouseout=\"return nd();\"></a> ".SALESTAX.":</td><td align=\"left\"><select name=\"taxable\"><option value=\"0\""; if ($taxable == "0") echo " selected"; echo ">".NO."<option value=\"1\""; if ($taxable == "1") echo " selected"; echo ">".YES."<option value=\"2\""; if ($taxable == "2") echo " selected"; echo ">".LEVEL2."</select></td></tr>";
			if ($activateautoresponder == "1" && !empty($autoresponderid) && is_numeric($autoresponderid)) {
				$sql = "SELECT * FROM autoresponders ORDER BY name";
				$result = @mysqli_query($db,$sql);
				if (@mysqli_num_rows($result)) {
					echo "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3c','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3c\" align=\"absmiddle\" onclick=\"return overlib('$tip3c');\" onmouseout=\"return nd();\"></a> ".ADDTOAUTORESPONDER.":</td><td class=\"formlabel\" align=\"left\"><select name=\"autoresponder\"><option value=\"0\">".NONE."</option>";
					for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
						$responderid = @mysqli_result($result, $i, "responderid");
						$respondername = @mysqli_result($result, $i, "name");
						echo "<option value=\"$responderid\">$respondername</option>";
					}
					echo "</select></td></tr>
					<tr><td class=\"formlabel\" align=\"right\">".REMOVEFROMAUTORESPONDER.":</td><td class=\"formlabel\" align=\"left\"><select name=\"autoresponderoff\"><option value=\"0\">".NONE."</option>";
					for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
						$responderid = @mysqli_result($result, $i, "responderid");
						$respondername = @mysqli_result($result, $i, "name");
						echo "<option value=\"$responderid\">$respondername</option>";
					}
					echo "</select></td></tr>";
				}
			} else if (!empty($aweberauthcode)) {
				$sql = "SELECT * FROM autoresponders ORDER BY name";
				$result = @mysqli_query($db,$sql);
				if (@mysqli_num_rows($result)) {
					echo "<tr><td class=\"formlabel\" align=\"right\">AWeber List:</td><td class=\"formlabel\"><select name=\"autoresponder\"><option value=\"0\">".NONE."</option>";
					for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
						$responderid = @mysqli_result($result, $i, "responderid");
						$respondername = @mysqli_result($result, $i, "name");
						echo "<option value=\"$responderid\">$respondername</option>";
					}
					echo "</select></td></tr>";
				}
			}

			if ($infinityselectstring1) echo $infinityselectstring1;
			if ($infinityselectstring2) echo $infinityselectstring2;
			if ($lmselectstring) echo $lmselectstring;
			if ($mcselectstring) echo $mcselectstring;
			if ($iemselectstring) echo $iemselectstring;
			if ($listmailurl) echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ".LISTMAILPROID.":</td><td class=\"formlabel\" align=\"left\"><input type=\"text\" size=\"5\" name=\"lmpgroup\" value=\"$lmpgroup\"></td></tr>";
			if ($phpbbselectstring) echo $phpbbselectstring;
			if ($arpselectstring) echo $arpselectstring;
			if ($arpreachselectstring) echo $arpreachselectstring;
		}
		echo "<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".SUBMIT."\" name=\"submitbutton\"></td></tr></table></form></td></tr></table>";
		echo $footer;
} else {
	// Convert money format...
	$price = str_replace($thousandchar,"",$price);
	$price = str_replace($decimalchar,".",$price);
	$wholesaleprice = str_replace($thousandchar,"",$wholesaleprice);
	$wholesaleprice = str_replace($decimalchar,".",$wholesaleprice);
	$wspricelevels = "";
	if ($pricelevels > 1) {
		for ($thislevel = 2; $thislevel <= $pricelevels; $thislevel++) {
			$thislevelprice = $_POST["wholesaleprice$thislevel"];
			$thislevelprice = str_replace($thousandchar,"",$thislevelprice);
			$thislevelprice = str_replace($decimalchar,".",$thislevelprice);
			$wspricelevels .= $thislevelprice."|";
		}
		$wspricelevels = substr($wspricelevels,0,-1);
	}
	$priceincrement = str_replace($thousandchar,"",$priceincrement);
	$priceincrement = str_replace($decimalchar,".",$priceincrement);
	$recurringprice = str_replace($thousandchar,"",$recurringprice);
	$recurringprice = str_replace($decimalchar,".",$recurringprice);
	$affiliatecom = str_replace($thousandchar,"",$affiliatecom);
	$affiliatecom = str_replace($decimalchar,".",$affiliatecom);
	$affiliatewscom = str_replace($thousandchar,"",$affiliatewscom);
	$affiliatewscom = str_replace($decimalchar,".",$affiliatewscom);
	$affiliatecom2 = str_replace($thousandchar,"",$affiliatecom2);
	$affiliatecom2 = str_replace($decimalchar,".",$affiliatecom2);
	$affiliatetier2com = str_replace($thousandchar,"",$affiliatetier2com);
	$affiliatetier2com = str_replace($decimalchar,".",$affiliatetier2com);
	$affiliatetier2com2 = str_replace($thousandchar,"",$affiliatetier2com2);
	$affiliatetier2com2 = str_replace($decimalchar,".",$affiliatetier2com2);
	$affiliatetierlowerby = str_replace($thousandchar,"",$affiliatetierlowerby);
	$affiliatetierlowerby = str_replace($decimalchar,".",$affiliatetierlowerby);

   if (!$memberid) $memberid = $userid;
   if ($userid == "1" && $featured > 0) @mysqli_query($db,"UPDATE product SET featured='0' WHERE featured='$featured'");
   // Check max affiliate commission for member products...
   if ($userid != "1") {
	   if ($affiliatecom && $affcomtype == "money") {
		   $memberresult = @mysqli_query($db,"SELECT commissionlevel FROM user WHERE userid='$userid'");
		   $commissionlevel = @mysqli_result($memberresult, 0, "commissionlevel");
		   if ($commissionlevel > 75) $commissionlevel = 75;
		   $commissionlevel = $commissionlevel/100;
		   $maxcommission = number_format($price*$commissionlevel,2,'.','');
		   if ($affiliatecom > $maxcommission) $affiliatecom = $maxcommission;
	   } else if ($affiliatecom > 75) $affiliatecom = 75;
	   if ($affiliatewscom && $affwscomtype == "money") {
		   $memberresult = @mysqli_query($db,"SELECT commissionlevel FROM user WHERE userid='$userid'");
		   $commissionlevel = @mysqli_result($memberresult, 0, "commissionlevel");
		   if ($commissionlevel > 75) $commissionlevel = 75;
		   $commissionlevel = $commissionlevel/100;
		   $maxcommission = number_format($price*$commissionlevel,2,'.','');
		   if ($affiliatewscom > $maxcommission) $affiliatewscom = $maxcommission;
	   } else if ($affiliatewscom > 75) $affiliatewscom = 75;
   }
   $sql="INSERT INTO product (name, price, description, receipttext, userid, featured) VALUES ('$name','$price','$description','$receipttext','$memberid','$featured')";
   $result = @mysqli_query($db,$sql);
   $product_id = @mysqli_insert_id($db);
   $sql="UPDATE product SET ordernumber='$product_id'";
   // Check if the product is in a main shop category...
   if ($memberid > "1" && !empty($cat) && is_numeric($cat)) {
	   $catresult = @mysqli_query($db,"SELECT * FROM category WHERE categoryid='$cat'");
	   $catowner = @mysqli_result($catresult,0,"userid");
	   $memberclone = @mysqli_result($catresult,0,"memberclone");
	   if ($memberclone == "1" && $catowner == "1") $sql .= ", inmainshop='1'";
   }
   if ($userid != "1" && $memberactivate) $active = "on";
   if ($active == "on") $sql.=", active='1'";
   else $sql.=", active='0'";
   if ($userid != "1" && $memberactivate) $wholesale = "on";
   if ($wholesale == "on" && !$priceincrement) $sql.=", wholesaleactive='1'";
   else $sql.=", wholesaleactive='0'";
   if ($taxable == "1") $sql.=", taxable='1'";
   else if ($taxable == "2") $sql.=", taxable='2'";
   if ($billtemplate) $sql.=", billtemplate='$billtemplate'";
   if ($licensetext) $sql.=", licensetext='$licensetext'";
   if ($lmgroup) $sql.=", listmessengergroup='$lmgroup'";
   if ($lmpgroup) $sql.=", listmaillist='$lmpgroup'";
   if ($iemlist) $sql.=", iemlist='$iemlist'";
   if ($mclist) $sql.=", mailchimplist='$mclist'";
   if ($phpbbgroup) $sql.=", phpbbgroup='$phpbbgroup'";
   if ($arpresponder) $sql.=", arpresponder='$arpresponder'";
   if ($arpreachresponder) $sql.=", arpreachresponder='$arpreachresponder'";
   if (isset($autoresponder)) $sql .= ", autoresponder='$autoresponder'";
   if (isset($autoresponderoff)) $sql .= ", autoresponderoff='$autoresponderoff'";
   if ($infinityresponder) $sql.=", infresponder='$infinityresponder'";
   if ($infinityresponderoff && $infinityresponderoff != $infinityresponder) $sql.=", infresponderoff='$infinityresponderoff'";
   if ($ebayid) $sql.=", ebayid='$ebayid'";
   if ($wholesaleprice) $sql.=", wholesaleprice='$wholesaleprice'";
   if ($wspricelevels) $sql.=", wspricelevels='$wspricelevels'";
   if (!$affiliatecom) $affiliatecom = "0";
   $affiliatecomstring = $affiliatecom."a$affcomtype";
   $sql.=", affiliatecom='$affiliatecomstring'";
   if (!$affiliatewscom) $affiliatewscom = "0";
   $affiliatewscomstring = $affiliatewscom."a$affwscomtype";
   $sql.=", affiliatewscom='$affiliatewscomstring'";
   if (!$affiliatecom2) $affiliatecom2 = "0";
   $affiliatecom2string = $affiliatecom2."a$affcomtype2";
   $sql.=", affiliatecom2='$affiliatecom2string'";
   if (empty($affiliatetier2com)) $affiliatetier2com = 0;
   if (empty($affiliatetier2com2)) $affiliatetier2com2 = 0;
   if (empty($affiliatetierlowerby)) $affiliatetierlowerby = 0;
   if ($afftier2comtype != "percent" && $afftier2comtype != "money") $afftier2comtype = "percent";
   if ($afftier2comtype2 != "percent" && $afftier2comtype2 != "money") $afftier2comtype2 = "percent";
   $affiliatetiercom = $affiliatetier2com."a".$afftier2comtype."|".$affiliatetier2com2."a".$afftier2comtype2."|".$affiliatetierlowerby;
   $sql.=", affiliatetiercom='$affiliatetiercom'";
   if (empty($affiliaterepeatcommission) || $affiliaterepeatcommission != "1") $affiliaterepeatcommission = 0;
   $sql.=", affiliaterepeatcommission='$affiliaterepeatcommission'";

   if ($recurringprice) $sql .= ", recurringprice='$recurringprice'";

   if ($recurringprice && $nrecurringperiod) $sql .= ", recurringperiod='$nrecurringperiod|$nrecurringperiodunits'";
   else $sql .= ", recurringperiod=NULL";

   $sql.=" WHERE productid='$product_id'";
   $result = @mysqli_query($db,$sql);

   $sql="INSERT INTO productcategory (productid,categoryid) VALUES ($product_id,$cat)";
   $result = @mysqli_query($db,$sql);

   // Add floating price...
   $bidlength = $bidlengthdays*86400;
   $bidlength += $bidlengthhours*3600;
   $bidlength += $bidlengthminutes*60;
   $bidlength += $bidlengthseconds;
   if ($priceincrement && $bidlength) {
	   $activatetimestamp = mktime($activatehour, $activateminute, 0, $activatemonth, $activateday, date("Y",time()));
	   if ($auctiontype == "standard") @mysqli_query($db,"INSERT INTO floatingprice (productid, startprice, originalstartprice, length, priceincrement, activatetime, starttime, type) VALUES ('$product_id', '$price', '$price', '$bidlength', '$priceincrement', '$activatetimestamp', '$activatetimestamp', '$auctiontype')");
	   else @mysqli_query($db,"INSERT INTO floatingprice (productid, startprice, originalstartprice, length, priceincrement, activatetime, type) VALUES ('$product_id', '$price', '$price', '$bidlength', '$priceincrement', '$activatetimestamp', '$auctiontype')");
   }

   if ($userid != "1" && !$memberactivate) {
	   $result = @mysqli_query($db,"SELECT prefvalue FROM preferences WHERE prefname='ashopname'");
	   $ashopname = @mysqli_result($result, 0, "prefvalue");
	   $result = @mysqli_query($db,"SELECT * FROM user WHERE userid='$userid'");
	   $membershopname = @mysqli_result($result, 0, "shopname");
	   $membershopemail = @mysqli_result($result, 0, "email");
	   $message="<html><head><title>$ashopname - ".MEMBERPRODUCTACTIVATION."</title></head><body><font face=\"$font\"><p>".MEMBER." <b>$userid: $membershopname</b> ".HASADDEDAPRODUCT." <a href=\"$ashopurl/admin/login.php?prodactivate=$product_id\">".VERIFYPRODUCT."</a></p></font></body></html>";
	   $headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";

	   @ashop_mail("$ashopemail",un_html($ashopname)." - ".MEMBERPRODUCTACTIVATION,"$message","$headers");
   }

   if ($error) header ("Location: editcatalogue.php?cat=$cat&error=$error&resultpage=$resultpage&search=$search");
   else {
	   if ($userid != "1" && !$advancedmallmode) header("Location: editfiles.php?add=$product_id&cat=$cat&resultpage=$resultpage&search=$search");
	   else header ("Location: pagegenerator.php?add=$product_id&cat=$cat&resultpage=$resultpage&search=$search");
   }
}
?>