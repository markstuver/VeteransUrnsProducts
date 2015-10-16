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

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

// Check mailing status...
$result = @mysqli_query($db, "SELECT * FROM mailing WHERE type='$mailingtype'");
$mailingid = @mysqli_result($result,0,"mailingid");
if (is_numeric($mailingid)) {
	$sentresult = @mysqli_query($db, "SELECT * FROM maillog WHERE mailingid='$mailingid'");
	if ($mailingtype == "customer") $totalresult = @mysqli_query($db, "SELECT customerid FROM customer WHERE customer.firstname != '' AND customer.email != '' AND customer.allowemail='1' AND ((customer.password != '' AND customer.password IS NOT NULL) OR EXISTS (SELECT customerid FROM orders WHERE orders.customerid=customer.customerid AND date IS NOT NULL))");
	else if ($mailingtype == "affiliate") $totalresult =  @mysqli_query($db, "SELECT DISTINCT email FROM affiliate");
	else if ($mailingtype == "member") $totalresult =  @mysqli_query($db, "SELECT DISTINCT email FROM user WHERE userid!='1' ORDER BY userid");
	else if ($mailingtype == "wholesale") $totalresult =  @mysqli_query($db, "SELECT DISTINCT email FROM customer WHERE firstname IS NOT NULL AND email IS NOT NULL AND level>'0' AND level IS NOT NULL");
	$sent = @mysqli_num_rows($sentresult);
	$total = @mysqli_num_rows($totalresult);
	header('Content-type: text/plain');
	echo "$sent|$total";
	exit;
} else {
	echo "-1|-1";
}

@mysqli_close($db);
?>