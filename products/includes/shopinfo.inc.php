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

// Include configuration file...

if ($_GET["shop"]) $shop = $_GET["shop"];
if (!$databaseserver || !$databaseuser) include "../admin/config.inc.php";
echo "var ashopname = '$ashopname';\n";
echo "var ashopphone = '$ashopphone';\n";
echo "var ashopaddress = '$ashopaddress';\n";
echo "var ashopemail = '$ashopemail';\n";
?>