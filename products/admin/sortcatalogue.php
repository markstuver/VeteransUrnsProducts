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

if (!$memberprodmanage) {
	if ($userid > 1) {
		header("Location: index.php");
		exit;
	} else $catuser = "%";
} else {
	if ($userid == 1) $catuser = "%";
	else {
		if (!$membershops) $catuser = "1";
		else $catuser = $userid;
	}
}

if ($cat && $sortby) {
	$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");
	$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
	$movelock = @mysqli_num_rows($result);
	$starttime = time();
	while ($movelock && time()-$starttime < 180) {
		sleep(5);
		$result = @mysqli_query($db, "SELECT * FROM user WHERE movelock='1' AND userid!='$userid'");
		$movelock = @mysqli_num_rows($result);
	}

	if (!$movelock) {
		$starttime = time();
		$keepsortby = $sortby;
		if ($sortby == "productidasc" || $sortby == "productiddesc") $sortby = "productid";
		@mysqli_query($db, "UPDATE user SET movelock='1' WHERE userid='$userid'");
		$sql = "SELECT product.ordernumber, product.productid FROM product, productcategory WHERE product.productid=productcategory.productid AND productcategory.categoryid='$cat' AND product.userid LIKE '$catuser' ORDER BY product.$sortby";
		if ($ashopsortorder == "DESC" && $sortby == "ordernumber") $sql .= " ASC";
		else if ($ashopsortorder == "ASC" && $sortby == "ordernumber") $sql .= " DESC";
		else if ($keepsortby == "productiddesc") $sql .= " DESC";
		$result = @mysqli_query($db, $sql);
		while ($row = @mysqli_fetch_array($result)) {
			$ordernumbers[] = $row["ordernumber"];
			if (time()-$starttime >= 180) {
				$error = "sortcrash";
				break;
			}
		}
		@mysqli_data_seek($result, 0);
		if (is_array($ordernumbers)) {
			if ($ashopsortorder == "ASC") sort ($ordernumbers, SORT_NUMERIC);
			else rsort ($ordernumbers, SORT_NUMERIC);
		}
		$counter = 0;
		while ($row = @mysqli_fetch_array($result)) {
			@mysqli_query($db, "UPDATE product SET ordernumber='".$ordernumbers[$counter]."' WHERE productid='".$row["productid"]."'");
			$counter++;
			if (time()-$starttime >= 180) {
				$error = "sortcrash";
				break;
			}
		}
		@mysqli_query($db, "UPDATE user SET movelock='0' WHERE userid='$userid'");
	} else $error = "sortlock";
	@mysqli_close($db);
}
if ($error) header("Location: editcatalogue.php?cat=$cat&error=$error");
header("Location: editcatalogue.php?cat=$cat");
?>