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

// Include configuration file and functions...
if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Initialize variables...
if (empty($shop)) {
	$shop = 1;
	$resetshop = TRUE;
} else $resetshop = FALSE;

// Get visitor information and update stats...
$ip = $_SERVER["REMOTE_ADDR"];
$time = time()+$timezoneoffset;
$date = date("d.m.Y", $time);
$firstoday = "1";
$isactive = "0";
$shop = 1;
$result = @mysqli_query($db, "SELECT * FROM visitcounter WHERE id = '$shop'");
$row = @mysqli_fetch_array($result);
$oldtoday = $row["today"];
$oldtotal = $row["total"];
$currenttoday = $row["currenttoday"];
$keepcurrent = $row["keepcurrent"];
$olddate = $row["date"];
$midnight = strtotime("$date 00:00:00");
$yesterday = $midnight-1;

// Delete yesterdays visitors from the today table...
@mysqli_query($db, "DELETE FROM visitcounter_today WHERE time < '$yesterday'");

// Check if this is the first visit today...
$result = @mysqli_query($db, "SELECT * FROM visitcounter_today WHERE ip='$ip'");
if (@mysqli_num_rows($result)) $firstoday = "0";

// Check if the visitor is already active...
$result = @mysqli_query($db, "SELECT * FROM visitcounter_online WHERE ip='$ip'");
if (@mysqli_num_rows($result)) $isactive = "1";

// This is the first visit today...
if ($firstoday) {
	$newtotal = $oldtotal+1;
	$newtoday = $oldtoday+1;
	@mysqli_query($db, "UPDATE visitcounter SET total = '$newtotal', today = '$newtoday' WHERE id = '$shop'");
	if (!$isactive) @mysqli_query($db, "INSERT INTO visitcounter_online (ip, time) VALUES ('$ip', '$time')");
	@mysqli_query($db, "INSERT INTO visitcounter_today (ip, time) VALUES ('$ip', '$time')");

// The visitor has already been here today...
} else {
	@mysqli_query($db, "UPDATE visitcounter_today SET time = '$time' WHERE ip = '$ip'");
	if (!$isactive) @mysqli_query($db, "INSERT INTO visitcounter_online (ip, time) VALUES ('$ip', '$time')");
}

// Check if this is a new day...
if ($olddate != $date) @mysqli_query($db, "UPDATE visitcounter SET date = '$date', today = '1' WHERE id = '1'");

// Remove inactive visitors...
$lastactivetime = $time-$keepcurrent;
@mysqli_query($db, "DELETE FROM visitcounter_online WHERE time < '$lastactivetime'");

if ($resetshop) unset($shop);
?>