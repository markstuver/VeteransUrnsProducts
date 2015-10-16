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
if (!function_exists(ashop_mailsafe)) include "ashopfunc.inc.php";

// Validate product ID...
$productid = $_GET["productid"];
if (!$productid) {
	$productid = $_GET["placebid"];
	$bidder = $_GET["bidder"];
	if ($productid && $bidder && is_numeric($productid) && is_numeric($bidder)) $placebid = TRUE;
	else $placebid = FALSE;
} else $placebid = FALSE;
if ($productid && !is_numeric($productid)) exit;

// Open database...
$errorcheck = ashop_opendatabase();
if ($errorcheck) $error = $errorcheck;

// Get product information...
$result = @mysqli_query($db, "SELECT * FROM floatingprice WHERE productid='$productid'");
$row = @mysqli_fetch_array($result);
$starttime = $row["starttime"];
$activatetime = $row["activatetime"];
$endprice = $row["endprice"];
$startprice = $row["startprice"];
$priceincrement = $row["priceincrement"];
$length = $row["length"];
$bids = $row["bids"];
$bidderid = $row["bidderid"];
$auctiontype = $row["type"];
if ($starttime && (time() >= ($starttime+$length))) $placebid = FALSE;

// Send countdown timer information...
if ($placebid) {
	// Check if there are any bids left for this bidder...
	$bidderresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$bidder'");
	$bidderrow = @mysqli_fetch_array($bidderresult);
	$numberofbids = $bidderrow["numberofbids"];
	if ($numberofbids > 0) {
		$numberofbids = $numberofbids - 1;
		$screenname = $bidderrow["screenname"];
		@mysqli_query($db, "UPDATE pricebidder SET numberofbids='$numberofbids' WHERE bidderid='$bidder'");

		// Increase number of bids for this product and reset timer...
		$bids++;
		$starttime = time()+1;
		$bidderid = $bidder;
		@mysqli_query($db, "UPDATE floatingprice SET starttime='$starttime', bids='$bids', bidderid='$bidder' WHERE productid='$productid'");
	}
}

// Get screen name of current bidder...
if (!$screenname) {
	$bidderresult = @mysqli_query($db, "SELECT * FROM pricebidder WHERE bidderid='$bidderid'");
	$bidderrow = @mysqli_fetch_array($bidderresult);
	$screenname = $bidderrow["screenname"];
	if ($endprice) {
		// Include language file...
		if (!isset($lang) || !preg_match("/[a-z]+/", $lang) || strlen($lang) > 2) $lang = $defaultlanguage;
		include "../language/$lang/bidregister.inc.php";
		$screenname = "won-$screenname";
	}
	$numberofbids = $bidderror["numberofbids"];
}

// Calculate the current bidding price...
if ($endprice) $currentprice = $endprice;
else $currentprice = number_format($startprice + ($priceincrement*$bids),2,'.','');

@mysqli_close($db);

header('Content-type: text/plain');
echo "$productid|$starttime|$length|$currentprice|$bidderid|$screenname|$activatetime";

/* Output XML document...
header('Content-type: text/xml');
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?> 
	<ajax-response>
		<response type=\"object\" id=\"productTimer\">
			<timerProductID>$productid</timerProductID>
			<timerStarttime>$starttime</timerStarttime>
			<timerLength>$length</timerLength>
			<Price>$currentprice</Price>
			<bidderID>$bidderid</bidderID>
			<bidderScreenname>$screenname</bidderScreenname>
		</response>
	</ajax-response>";*/
?>