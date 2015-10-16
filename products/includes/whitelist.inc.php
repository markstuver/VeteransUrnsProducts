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

// Check if the customer's IP is allowed...
$ipnumber = $_SERVER["REMOTE_ADDR"];
$allowedcheck = @mysqli_query($db, "SELECT * FROM customerwhitelist WHERE whitelistitem='$ipnumber'");
if (!@mysqli_num_rows($allowedcheck)) {
	header("Location: whitelist.html");
	exit;
}
?>