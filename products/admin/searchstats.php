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
include "ashopconstants.inc.php";
include "checklogin.inc.php";
include "template.inc.php";

if ($userid != "1") header("Location: index.php");

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

echo $header."<div class=\"heading\">Search Statistics</div><table cellpadding=\"0\" align=\"center\"><tr><td>";

// Get total searches....
$result = @mysqli_query($db, "SELECT * FROM searchstatistics ORDER BY date DESC LIMIT 400");
$totalsearches = @mysqli_num_rows($result);
$lastrow = $totalsearches-1;
$firstdate = @mysqli_result($result,$lastrow,"date");

// Get stats for all unique searches...
$searchlist = array();
$result = @mysqli_query($db, "SELECT DISTINCT keyword FROM searchstatistics WHERE date>'$firstdate'");
while ($row = @mysqli_fetch_array($result)) {
	$keyword = trim($row["keyword"]);
	if (!empty($keyword)) {
		$result2 = @mysqli_query($db, "SELECT * FROM searchstatistics WHERE keyword='$keyword' AND date>'$firstdate'");
		$searches = @mysqli_num_rows($result2);
		$searchlist["$keyword"] = $searches;
	}
}

arsort($searchlist);
echo "<table cellpadding=\"5\" cellspacing=\"0\" border=\"0\">";
foreach($searchlist as $keyword=>$searches) {
	$searchpercent = ($searches/$totalsearches)*100;
	if ($searchpercent > 1) echo "<tr><td align=\"right\" class=\"text\">$keyword:</td><td align=\"right\" class=\"text\">".number_format($searchpercent,1,'.','')."%</td></tr>";
}

echo "</td></tr></table></td></tr></table></center>$footer";
?>