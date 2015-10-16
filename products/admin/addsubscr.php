<?php
// AShop
// Copyright 2011 - AShop Software - http://www.ashopsoftware.com
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
   include "ashopconstants.inc.php";
   include "checklogin.inc.php";
   include "template.inc.php";

// Get context help for this page...
		$contexthelppage = "editproduct";
		include "help.inc.php";

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
	$lmdb = @mysql_connect("$lmhost", "$lmuser", "$lmpass");
	@mysql_select_db("$lmname",$lmdb);
	if ($listmessengerversion == "pro") $sql = "SELECT * FROM {$lmprefix}groups";
	else $sql = "SELECT * FROM {$lmprefix}user_groups";
	$result = @mysql_query($sql,$lmdb);
	$lmselectstring = "";
	if (@mysql_num_rows($result)) {
		$lmselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href= \"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image1','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image1\" align=\"absmiddle\" onclick=\"return overlib('$tip1');\" onmouseout=\"return nd();\"></a> ListMessenger Group:</td><td class=\"formlabel\"><select name=\"lmgroup\"><option value=\"0\">none</option>";
		for ($i = 0; $i < @mysql_num_rows($result); $i++) {
			if ($listmessengerversion == "pro") $lmgroupid = @mysql_result($result, $i, "groups_id");
			else $lmgroupid = @mysql_result($result, $i, "group_id");
			$lmgroupname = @mysql_result($result, $i, "group_name");
			$lmselectstring .= "<option value=\"$lmgroupid\">$lmgroupname</option>";
		}
		$lmselectstring .= "</select></td></tr>";
	}
	@mysql_close($lmdb);
}

// Get phpbb groups if applicable...
if ($phpbbpath && file_exists("$phpbbpath/config.php")) {
	$fp = fopen ("$phpbbpath/config.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "\$dbhost") == 0 && is_integer(strpos($buffer, "\$dbhost"))) {
			$phpbbhost = substr($buffer, strpos($buffer, "'")+1);
			$phpbbhost = substr($phpbbhost, 0, strpos($phpbbhost, "'"));
		}
		if (strpos($buffer, "\$dbname") == 0 && is_integer(strpos($buffer, "\$dbname"))) {
			$phpbbname = substr($buffer, strpos($buffer, "'")+1);
			$phpbbname = substr($phpbbname, 0, strpos($phpbbname, "'"));
		}
		if (strpos($buffer, "\$dbuser") == 0 && is_integer(strpos($buffer, "\$dbuser"))) {
			$phpbbuser = substr($buffer, strpos($buffer, "'")+1);
			$phpbbuser = substr($phpbbuser, 0, strpos($phpbbuser, "'"));
		}
		if (strpos($buffer, "\$dbpasswd") == 0 && is_integer(strpos($buffer, "\$dbpasswd"))) {
			$phpbbpass = substr($buffer, strpos($buffer, "'")+1);
			$phpbbpass = substr($phpbbpass, 0, strpos($phpbbpass, "'"));
		}
		if (strpos($buffer, "\$table_prefix") == 0 && is_integer(strpos($buffer, "\$table_prefix"))) {
			$phpbbtablepref = substr($buffer, strpos($buffer, "'")+1);
			$phpbbtablepref = substr($phpbbtablepref, 0, strpos($phpbbtablepref, "'"));
		}
		if (strpos($buffer, "\$dbms") == 0 && is_integer(strpos($buffer, "\$dbms"))) {
			$phpbbdbms = substr($buffer, strpos($buffer, "'")+1);
			$phpbbdbms = substr($phpbbdbms, 0, strpos($phpbbdbms, "'"));
		}
	}
	fclose ($fp);
	if (stristr($phpbbdbms, "mysql")) {
		$phpbbdb = @mysql_connect("$phpbbhost", "$phpbbuser", "$phpbbpass");
		@mysql_select_db("$phpbbname",$phpbbdb);
		$sql = "SELECT * FROM $phpbbtablepref"."groups WHERE group_single_user = 0";
		$result = @mysql_query($sql,$phpbbdb);
		$phpbbselectstring = "";
		if (@mysql_num_rows($result)) {
			$phpbbselectstring = "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3\" align=\"absmiddle\" onclick=\"return overlib('$tip3');\" onmouseout=\"return nd();\"></a> phpbb User Group:</td><td class=\"formlabel\"><select name=\"phpbbgroup\"><option value=\"0\">none</option>";
			for ($i = 0; $i < @mysql_num_rows($result); $i++) {
				$phpbbgroupid = @mysql_result($result, $i, "group_id");
				$phpbbgroupname = @mysql_result($result, $i, "group_name");
				$phpbbselectstring .= "<option value=\"$phpbbgroupid\">$phpbbgroupname</option>";
			}
			$phpbbselectstring .= "</select></td></tr>";
		}
		@mysql_close($phpbbdb);
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
	$arpdb = @mysql_connect("$arphost", "$arpuser", "$arppass");
	@mysql_select_db("$arpname",$arpdb);
	$sql = "SELECT * FROM $arptable";
	$result = @mysql_query($sql,$arpdb);
	$arpselectstring = "";
	if (@mysql_num_rows($result)) {
		$arpselectstring = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3a\" align=\"absmiddle\" onclick=\"return overlib('$tip3a');\" onmouseout=\"return nd();\"></a> ARP3 AutoResponder:</td><td class=\"formlabel\"><select name=\"arpresponder\"><option value=\"0\">none</option>";
		for ($i = 0; $i < @mysql_num_rows($result); $i++) {
			$arprespid = @mysql_result($result, $i, "id");
			$arprespname = @mysql_result($result, $i, "listens_on")."@$arpdomain";
			$arpselectstring .= "<option value=\"$arprespid\">$arprespname</option>";
		}
		$arpselectstring .= "</select></td></tr>";
	}
	@mysql_close($arpdb);
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
	$infinitydb = @mysql_connect("$infinityhost", "$infinityuser", "$infinitypass");
	@mysql_select_db("$infinityname",$infinitydb);
	$sql = "SELECT * FROM InfResp_responders ORDER BY Name";
	$result = @mysql_query($sql,$infinitydb);
	$infinityselectstring = "";
	if (@mysql_num_rows($result)) {
		$infinityselectstring1 = "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3b','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3b\" align=\"absmiddle\" onclick=\"return overlib('$tip3b');\" onmouseout=\"return nd();\"></a> Add to Infinity Responder:</td><td class=\"formlabel\"><select name=\"infinityresponder\"><option value=\"0\">none</option>";
		$infinityselectstring2 = "<tr><td class=\"formlabel\" align=\"right\">Remove from Infinity Responder:</td><td class=\"formlabel\"><select name=\"infinityresponderoff\"><option value=\"0\">none</option>";
		for ($i = 0; $i < @mysql_num_rows($result); $i++) {
			$infinityrespid = @mysql_result($result, $i, "ResponderID");
			$infinityrespname = @mysql_result($result, $i, "Name");
			$infinityselectstring1 .= "<option value=\"$infinityrespid\">$infinityrespname</option>";
			$infinityselectstring2 .= "<option value=\"$infinityrespid\">$infinityrespname</option>";
		}
		$infinityselectstring1 .= "</select></td></tr>";
		$infinityselectstring2 .= "</select></td></tr>";
	}
	@mysql_close($infinitydb);
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
		$iemselectstring .= "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3d','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3d\" align=\"absmiddle\" onclick=\"return overlib('$tip3d');\" onmouseout=\"return nd();\"></a> Email Marketer list:</td><td class=\"formlabel\"><select name=\"iemlist\"><option value=\"0\">none</option>";
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

   $db = @mysql_connect("$databaseserver", "$databaseuser", "$databasepasswd");
   @mysql_select_db("$databasename",$db);

// Generate Digital Mall member list if needed...
if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") {
	// Check if we are in a member owned category...
	$result = @mysql_query("SELECT userid FROM category WHERE categoryid='$cat'",$db);
	$catuser = @mysql_result($result, 0, "userid");
	$memberlist = "<select name=\"memberid\"><option value=\"1\">Administrator";
	$result = @mysql_query("SELECT * FROM user WHERE userid>1 ORDER BY shopname",$db);
	while ($row = @mysql_fetch_array($result)) {
		$memberlist .= "<option value=\"{$row["userid"]}\"";
		if ($catuser == $row["userid"]) $memberlist .= " selected";
		$memberlist .= ">{$row["shopname"]}";
	}
	$memberlist .= "</select>";
}

   // Make sure special characters are handled properly...
   $name = str_replace("\"", "&quot;", $name);
   //$name = str_replace("'", "&#039;", $name);
   //$description = str_replace("'", "&#039;", $description);

if (!$name || $price == '' || $addlicense) {

  $sql="SELECT name FROM category WHERE categoryid = $cat";
  $result = @mysql_query($sql,$db);
  $categoryname = @mysql_result($result, $j, "name");
  echo "$header
        <table bgcolor=\"#$adminpanelcolor\" height=\"50\" width=\"100%\"><tr valign=\"middle\" align=\"center\"><td class=\"heading1\">Edit Catalogue</td></tr></table>
        <table cellpadding=\"10\" align=\"center\"><tr><td align=\"center\"><p class=\"heading\">Add subscription to category: $categoryname</p>
        <form action=\"addsubscr.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"productform\">
        <table width=\"550\" border=\"0\" cellspacing=\"0\" cellpadding=\"5\" bgcolor=\"#F0F0F0\">
		<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image4','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image4\" align=\"absmiddle\" onclick=\"return overlib('$tip4');\" onmouseout=\"return nd();\"></a> Name:</td><td><input type=\"text\" name=\"name\" size=\"35\" value=\"$name\"><script language=\"JavaScript\">document.productform.name.focus();</script></td></tr>";
		if ($userid == "1" && file_exists("$ashoppath/members/index.php") && $digitalmall != "OFF") echo "<tr><td align=\"right\" class=\"formlabel\">Owner Member:</td><td>$memberlist</td></tr>";
		if ($userid == "1") echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image5','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image5\" align=\"absmiddle\" onclick=\"return overlib('$tip5');\" onmouseout=\"return nd();\"></a> Catalog Status:</td><td class=\"formlabel\"><input type=\"checkbox\" name=\"active\" checked> Active</td></tr>
		<tr><td align=\"right\" class=\"formlabel\">Featured Spot:</td><td><select name=\"featured\"><option value=\"0\">No</option><option value=\"1\">1</option><option value=\"2\">2</option><option value=\"3\">3</option><option value=\"4\">4</option><option value=\"5\">5</option><option value=\"6\">6</option><option value=\"7\">7</option><option value=\"8\">8</option><option value=\"9\">9</option><option value=\"10\">10</option></select></td></tr>";
        echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image7','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image7\" align=\"absmiddle\" onclick=\"return overlib('$tip7');\" onmouseout=\"return nd();\"></a> Price:</td><td class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"price\" size=\"10\" value=\"$price\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">Receipt text:</td><td><textarea name=\"receipttext\" cols=\"30\" rows=\"2\">$receipttext</textarea></td></tr>
        <tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image9','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image9\" align=\"absmiddle\" onclick=\"return overlib('$tip9');\" onmouseout=\"return nd();\"></a> Description:</td><td><textarea name=\"description\" cols=\"30\" rows=\"5\">";
		if ($userid != "1" && !$description) echo "$memberproducttemplate";
		else echo "$description";
		echo "</textarea></td></tr>";
		if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image10','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image10\" align=\"absmiddle\" onclick=\"return overlib('$tip10');\" onmouseout=\"return nd();\"></a> <a href=\"$help10\" class=\"helpnav2\" target=\"_blank\">License Agreement:</a></td><td>";
			if ($addlicense) echo "<textarea name=\"licensetext\" cols=\"30\" rows=\"5\"></textarea>";
			else echo "<input type=\"hidden\" name=\"addlicense\" value=\"\"><input type=\"button\" value=\"Add license agreement\" onClick=\"document.productform.addlicense.value='true';document.productform.submit();\">";
			echo "</td></tr>";
		}
		echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11a\" align=\"absmiddle\" onclick=\"return overlib('$tip11a');\" onmouseout=\"return nd();\"></a> <a href=\"$help11a\" class=\"helpnav2\" target=\"_blank\">Protected directory:</td><td><input type=\"text\" name=\"subscriptiondir\" value=\"$subscriptiondir\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image11b','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image11b\" align=\"absmiddle\" onclick=\"return overlib('$tip11b');\" onmouseout=\"return nd();\"></a> <a href=\"$help11b\" class=\"helpnav2\" target=\"_blank\">Protected URL:</a></td><td><input type=\"text\" name=\"protectedurl\"></td></tr>
		<tr><td align=\"right\" class=\"formlabel\" valign=\"top\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image12a','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image12a\" align=\"absmiddle\" onclick=\"return overlib('$tip12a');\" onmouseout=\"return nd();\"></a> <a href=\"$help12a\" class=\"helpnav2\" target=\"_blank\">Subscription length:</a></td><td class=\"formlabel\"><input type=\"text\" name=\"length\" size=\"10\"> days <span class=\"sm\">[0 = unlimited]</span></td></tr>";
		if ($userid == "1") {
			if (file_exists("$ashoppath/emerchant/quote.php")) {
				$billtemplateresult = @mysql_query("SELECT * FROM emerchant_billtemplates ORDER BY name",$db);
				if (@mysql_num_rows($billtemplateresult)) {
					echo "<tr><td align=\"right\" class=\"formlabel\" valign=\"top\">Billing template:</a></td><td class=\"formlabel\"><select name=\"billtemplate\"><option value=\"none\" selected>None";
					while ($billtemplaterow = @mysql_fetch_array($billtemplateresult)) {
						$billtemplatename = $billtemplaterow["name"];
						$billtemplateid = $billtemplaterow["billtemplateid"];
						echo "<option value=\"$billtemplateid\">$billtemplatename";
					}
				}
				$ccbillresult = @mysql_query("SELECT * FROM payoptions WHERE gateway='ccbill' OR gateway='paypal'",$db);
				if (@mysql_num_rows($billtemplateresult) || @mysql_num_rows($ccbillresult)) {
					echo "</select></td></tr><tr><td align=\"right\" class=\"formlabel\" valign=\"top\">Recurring price:</td><td class=\"formlabel\">".$currencysymbols[$ashopcurrency]["pre"]." <input type=\"text\" name=\"recurringprice\" size=\"10\" value=\"$recurringprice\"> ".$currencysymbols[$ashopcurrency]["post"]."</td></tr>";
				}
			}
		}
		echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image14','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image14\" align=\"absmiddle\" onclick=\"return overlib('$tip14');\" onmouseout=\"return nd();\"></a> <a href=\"$help14\" class=\"helpnav2\" target=\"_blank\">Affiliate Commission:</a></td><td><input type=\"text\" name=\"affiliatecom\" size=\"7\" value=\"$affiliatepercent\"><input type=\"radio\" name=\"affcomtype\" value=\"percent\" checked>% <input type=\"radio\" name=\"affcomtype\" value=\"money\">";
		if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
		else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
		echo "</td></tr>\n";
		if ($userid == "1") {
			echo "<tr><td align=\"right\" class=\"formlabel\">Upgraded Affiliate Commission:</td><td><input type=\"text\" name=\"affiliatecom2\" size=\"7\" value=\"$affiliatepercent2\"><input type=\"radio\" name=\"affcomtype2\" value=\"percent\" checked>% <input type=\"radio\" name=\"affcomtype2\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">Tier 2 Commission:</td><td class=\"formlabel\"><input type=\"text\" name=\"affiliatetier2com\" size=\"7\" value=\"$secondtierpercent\"><input type=\"radio\" name=\"afftier2comtype\" value=\"percent\" checked>% <input type=\"radio\" name=\"afftier2comtype\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">Upgraded Tier 2 Commission:</td><td class=\"formlabel\"><input type=\"text\" name=\"affiliatetier2com2\" size=\"7\" value=\"$secondtierpercent2\"><input type=\"radio\" name=\"afftier2comtype2\" value=\"percent\" checked>% <input type=\"radio\" name=\"afftier2comtype2\" value=\"money\">";
			if ($currencysymbols[$ashopcurrency]["pre"]) echo $currencysymbols[$ashopcurrency]["pre"];
			else if ($currencysymbols[$ashopcurrency]["post"]) echo $currencysymbols[$ashopcurrency]["post"];
			echo "</td></tr>
			<tr><td align=\"right\" class=\"formlabel\">Lower by:</td><td class=\"formlabel\"><input type=\"text\" name=\"affiliatetierlowerby\" size=\"7\" value=\"0\"> on each additional tier. <span class=\"sm\"> [ 0 = disable multi tier ]</span></td></tr>
			<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image15','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image15\" align=\"absmiddle\" onclick=\"return overlib('$tip15');\" onmouseout=\"return nd();\"></a> Sales Tax:</td><td><select name=\"taxable\"><option value=\"0\""; if ($taxable == "0") echo " selected"; echo ">No<option value=\"1\""; if ($taxable == "1") echo " selected"; echo ">Yes<option value=\"2\""; if ($taxable == "2") echo " selected"; echo ">Level 2</select></td></tr>";
			if ($activateautoresponder == "1" && !empty($autoresponderid) && is_numeric($autoresponderid)) {
				$sql = "SELECT * FROM autoresponders ORDER BY name";
				$result = @mysql_query($sql,$db);
				if (@mysql_num_rows($result)) {
					echo "<tr><td class=\"formlabel\" align=\"right\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image3c','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image3c\" align=\"absmiddle\" onclick=\"return overlib('$tip3c');\" onmouseout=\"return nd();\"></a> Add to autoresponder:</td><td class=\"formlabel\"><select name=\"autoresponder\"><option value=\"0\">none</option>";
					for ($i = 0; $i < @mysql_num_rows($result); $i++) {
						$responderid = @mysql_result($result, $i, "responderid");
						$respondername = @mysql_result($result, $i, "name");
						echo "<option value=\"$responderid\">$respondername</option>";
					}
					echo "</select></td></tr>
					<tr><td class=\"formlabel\" align=\"right\">Remove from autoresponder:</td><td class=\"formlabel\"><select name=\"autoresponderoff\"><option value=\"0\">none</option>";
					for ($i = 0; $i < @mysql_num_rows($result); $i++) {
						$responderid = @mysql_result($result, $i, "responderid");
						$respondername = @mysql_result($result, $i, "name");
						echo "<option value=\"$responderid\">$respondername</option>";
					}
					echo "</select></td></tr>";
				}
			}
			if ($infinityselectstring1) echo $infinityselectstring1;
			if ($infinityselectstring2) echo $infinityselectstring2;
			if ($lmselectstring) echo $lmselectstring;
			if ($iemselectstring) echo $iemselectstring;
			if ($listmailurl) echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image2','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image2\" align=\"absmiddle\" onclick=\"return overlib('$tip2');\" onmouseout=\"return nd();\"></a> ListMail Pro List ID:</td><td class=\"formlabel\"><input type=\"text\" size=\"5\" name=\"lmpgroup\" value=\"$lmpgroup\"></td></tr>";
			if ($fitlisturl) echo "<tr><td align=\"right\" class=\"formlabel\"><a href=\"javascript:;\" onMouseOut=\"MM_swapImgRestore()\" onMouseOver=\"MM_swapImage('Image16','','images/contexthelpicon_over.gif',1)\"><img src=\"images/contexthelpicon.gif\" width=\"14\" height=\"15\" border=\"0\" name=\"Image16\" align=\"absmiddle\" onclick=\"return overlib('$tip16');\" onmouseout=\"return nd();\"></a> Fitlist Pro Responder ID:</td><td class=\"formlabel\"><input type=\"text\" size=\"5\" name=\"fitlistresponder\" value=\"$fitlistresponder\"></td></tr>";
			if ($phpbbselectstring) echo $phpbbselectstring;
			if ($arpselectstring) echo $arpselectstring;
		}
		echo "<tr><td>&nbsp;</td><td align=\"right\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"resultpage\" value=\"$resultpage\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"Submit\" onClick=\"uploadmessage()\" name=\"submitbutton\"></td></tr></table></form>$footer";
} else {
   if (!$memberid) $memberid = $userid;
   if ($userid == "1" && $featured > 0) @mysql_query("UPDATE product SET featured='0' WHERE featured='$featured'",$db);
   // Check max affiliate commission for member products...
   if ($userid != "1") {
	   if ($affiliatecom && $affcomtype == "money") {
		   $memberresult = @mysql_query("SELECT commissionlevel FROM user WHERE userid='$userid'",$db);
		   $commissionlevel = @mysql_result($memberresult, 0, "commissionlevel");
		   if ($commissionlevel > 75) $commissionlevel = 75;
		   $commissionlevel = $commissionlevel/100;
		   $maxcommission = number_format($price*$commissionlevel,2,'.','');
		   if ($affiliatecom > $maxcommission) $affiliatecom = $maxcommission;
	   } else if ($affiliatecom > 75) $affiliatecom = 75;
   }
   $sql="INSERT INTO product (name,price,description, prodtype,userid, featured) VALUES ('$name','$price','$description','subscription','$memberid','$featured')";
   $result = @mysql_query($sql,$db);
   $product_id = @mysql_insert_id();
   $sql="UPDATE product SET ordernumber='$product_id' WHERE productid=$product_id";
   $result = @mysql_query($sql,$db);

   if ($active == "on") $sql="UPDATE product SET active=1 WHERE productid=$product_id";
   else $sql="UPDATE product SET active=0 WHERE productid=$product_id";
   $result = @mysql_query($sql,$db);

   // Check if the product is in a main shop category...
   if ($userid > "1" && !empty($cat) && is_numeric($cat)) {
	   $catresult = @mysql_query("SELECT * FROM category WHERE categoryid='$cat'",$db);
	   $catowner = @mysql_result($catresult,0,"userid");
	   $memberclone = @mysql_result($catresult,0,"memberclone");
	   if ($memberclone == "1" && $catowner == "1") {
		   $sql="UPDATE product SET inmainshop='1' WHERE productid=$product_id";
		   $result = @mysql_query($sql,$db);
	   }
   }

   if ($taxable == "1") {
	   $sql="UPDATE product SET taxable='1' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   } else if ($taxable == "2") {
	   $sql="UPDATE product SET taxable='2' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($length) {
	   $sql="UPDATE product SET length=$length WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($billtemplate) {
	   $sql="UPDATE product SET billtemplate=$billtemplate WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($recurringprice) {
	   $sql="UPDATE product SET recurringprice='$recurringprice' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($protectedurl) {
	   $sql="UPDATE product SET protectedurl='$protectedurl' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($licensetext) {
	   $sql="UPDATE product SET licensetext='$licensetext' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($lmgroup) {
	   $sql="UPDATE product SET listmessengergroup=$lmgroup WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($lmpgroup) {
	   $sql="UPDATE product SET listmaillist=$lmpgroup WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($fitlistresponder) {
	   $sql="UPDATE product SET fitlistresponder=$fitlistresponder WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($iemlist) {
	   $sql="UPDATE product SET iemlist=$iemlist WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($phpbbgroup) {
	   $sql="UPDATE product SET phpbbgroup=$phpbbgroup WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($arpresponder) {
	   $sql="UPDATE product SET arpresponder=$arpresponder WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if (isset($autoresponder)) {
	   $sql="UPDATE product SET autoresponder='$autoresponder' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if (isset($autoresponderoff)) {
	   $sql="UPDATE product SET autoresponderoff='$autoresponderoff' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($infinityresponder) {
	   $sql="UPDATE product SET infresponder=$infinityresponder WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($infinityresponderoff) {
	   $sql="UPDATE product SET infresponderoff=$infinityresponderoff WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if (!$affiliatecom) $affiliatecom = "0";
   $affiliatecomstring = $affiliatecom."a$affcomtype";
   $sql="UPDATE product SET affiliatecom='$affiliatecomstring' WHERE productid=$product_id";
   $result = @mysql_query($sql,$db);

   if (!$affiliatecom2) $affiliatecom2 = "0";
   $affiliatecom2string = $affiliatecom2."a$affcomtype2";
   $sql="UPDATE product SET affiliatecom2='$affiliatecom2string' WHERE productid=$product_id";
   $result = @mysql_query($sql,$db);

   if (!empty($affiliatetier2com) && is_numeric($affiliatetier2com)) {
	   if (empty($affiliatetier2com2)) $affiliatetier2com2 = 0;
	   if (empty($affiliatetierlowerby)) $affiliatetierlowerby = 0;
	   if ($afftier2comtype != "percent" && $afftier2comtype != "money") $afftier2comtype = "percent";
	   if ($afftier2comtype2 != "percent" && $afftier2comtype2 != "money") $afftier2comtype2 = "percent";
	   $affiliatetiercom = $affiliatetier2com."a".$afftier2comtype."|".$affiliatetier2com2."a".$afftier2comtype2."|".$affiliatetierlowerby;
   } else $affiliatetiercom = "";
   $sql="UPDATE product SET affiliatetiercom='$affiliatetiercom' WHERE productid=$productid";
   $result = @mysql_query($sql,$db);

   $sql="INSERT INTO productcategory (productid,categoryid) VALUES ($product_id,$cat)";
   $result = @mysql_query($sql,$db);

   if ($subscriptiondir) {
	   $sql="UPDATE product SET subscriptiondir='$subscriptiondir' WHERE productid=$product_id";
	   $result = @mysql_query($sql,$db);
   }
   if ($error) header ("Location: editcatalogue.php?cat=$cat&error=$error&resultpage=$resultpage&search=$search");
   else header ("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage&search=$search");
}
?>