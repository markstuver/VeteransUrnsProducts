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

include "admin/config.inc.php";
include "admin/ashopfunc.inc.php";

$fullquerystring = $_SERVER["QUERY_STRING"];
if (strpos($fullquerystring,"&")) {
	$queryarray = explode("&",$fullquerystring);
	$affiliate = $queryarray[0];
	$page = $queryarray[1];
	$pagepath = substr($page,0,strrpos($page,"/")+1);
} else $page = $fullquerystring;

if (substr($page,0,1) == "/") $page = substr($page,1);

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!empty($affiliate)) {
	$affiliate = @mysqli_real_escape_string($db, $affiliate);
	$result = @mysqli_query($db, "SELECT affiliateid FROM affiliate WHERE user='$affiliate'");
	$affiliate = @mysqli_result($result,0,"affiliateid");
}

if (!empty($affiliate) && is_numeric($affiliate) && !isset($_COOKIE["affiliate"])) {

    // Set current date and time...
	$date = date("Y-m-d H:i:s", time()+$timezoneoffset);
	
	$sql="SELECT clicks FROM affiliate WHERE affiliateid='$affiliate'";
	$result = @mysqli_query($db, "$sql");
	$clicks = @mysqli_result($result, 0, "clicks");
	$sql = "UPDATE affiliate SET clicks=$clicks+1, lastdate='$date' WHERE affiliateid='$affiliate'";
	$result = @mysqli_query($db, "$sql");
	$httpreferer = $_SERVER["HTTP_REFERER"];
	if(substr($httpreferer,0,strlen($ashopurl)) == $ashopurl) $httpreferer = "";
	if(substr($httpreferer,0,strlen($ashopsurl)) == $ashopsurl) $httpreferer = "";
	$httpreferer = @mysqli_real_escape_string($db, $httpreferer);
	if(!empty($httpreferer)) {
		$result = @mysqli_query($db, "SELECT clicks FROM affiliatereferer WHERE affiliateid='$affiliate' AND referer='$httpreferer'");
		if (@mysqli_num_rows($result)) {
			$refererclicks = @mysqli_result($result,0,"clicks");
			$refererclicks++;
			@mysqli_query($db, "UPDATE affiliatereferer SET clicks='$refererclicks' WHERE affiliateid='$affiliate' AND referer='$httpreferer'");
		} else @mysqli_query($db, "INSERT INTO affiliatereferer (affiliateid,referer,clicks) VALUES ('$affiliate','$httpreferer','1')");
	}

	/* Set referral discount cookies...
	$discountcookiestring = "";
	$result = @mysqli_query($db, "SELECT code FROM referraldiscount WHERE affiliateid='$affiliate'");
	while ($row = @mysqli_fetch_array($result)) $discountcookiestring .= $row["code"]."|";
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("referraldiscount", $discountcookiestring, time()+86400, "/");
	*/
	
	// Set tracking cookie...
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("affiliate","$affiliate", mktime(0,0,0,12,1,2020), "/");
}

if (file_exists("$ashoppath/$page")) {
	$fp = fopen ("$page","r");
	if ($fp) {
		while (!feof ($fp)) $pagecontents .= fgets($fp, 4096);
		fclose($fp);
	}
}
if (!empty($pagecontents)) {
	if (!empty($affiliate) && is_numeric($affiliate)) {
		// Open database...
		$pagecontents = ashop_parseaffiliatetags($pagecontents);
		$offset = 0;
		/*while(strpos($pagecontents,"href=\"",$offset)) {
			$newoffset = strpos($pagecontents,"href=\"",$offset)+6;
			$replacestring = substr($pagecontents,$newoffset,strpos($pagecontents,"\"",$newoffset)-$newoffset);
			if (!substr_count($replacestring,"css") && !substr_count($replacestring,"CSS") && !substr_count($replacestring,".php") && !substr_count($replacestring,".PHP") && substr($replacestring,0,7) != "http://" && substr($replacestring,0,7) != "HTTP://" && substr($replacestring,0,8) != "https://" && substr($replacestring,0,8) != "HTTPS://" && substr($replacestring,0,3) != "../") {
				$replacement = "view.php?$affiliate&$pagepath$replacestring";
				$pagecontents = substr_replace($pagecontents,$replacement,$newoffset,strpos($pagecontents,"\"",$newoffset)-$newoffset);
			}
			$offset = $newoffset;
		}*/
		$pagecontents = str_replace("src=\"../","src=\"../../",$pagecontents);
		$pagecontents = str_replace("SRC=\"../","SRC=\"../../",$pagecontents);
		$pagecontents = str_replace("href=\"../","href=\"../../",$pagecontents);
		$pagecontents = str_replace("HREF=\"../","HREF=\"../../",$pagecontents);
	}
	echo $pagecontents;
}
?>