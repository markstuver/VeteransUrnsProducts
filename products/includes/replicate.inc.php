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
// --------------------------------------------------------------------
// Module: replicate.inc.php
// Description: generates affiliate replicated content
// Input variables: replicate = content to parse

if (!$databaseserver || !$databaseuser) include "admin/config.inc.php";
if (!function_exists('ashop_mailsafe')) include "admin/ashopfunc.inc.php";

// Open database...
if (!is_resource($db) || get_resource_type($db) !== 'mysql link') {
	$errorcheck = ashop_opendatabase();
	if ($errorcheck) $error = $errorcheck;
}

// Validate variables...
if ($_GET["replicate"] || $_POST["replicate"] || $_COOKIE["replicate"]) $replicate = "";
if (!empty($replicate)) {
	if (substr($replicate,0,11) != "<!-- AShop_") $replicate = "";
	if (substr($replicate,-4) != " -->") $replicate = "";
}

// Generate replicated content...
if (!empty($replicate)) echo ashop_parseaffiliatetags($replicate);
?>