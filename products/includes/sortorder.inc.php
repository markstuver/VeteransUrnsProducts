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

// Remember sort order...
if (isset($_POST["sortby"])) {
	if ($_POST["sortby"] == "name" || $_POST["sortby"] == "lowprice" || $_POST["sortby"] == "highprice") {
		if (!$p3psent) {
			header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			$p3psent = TRUE;
		}
		setcookie("sortby", $_POST["sortby"]);
		$sortby = $_POST["sortby"];
	} else {
		if (!$p3psent) {
			header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
			$p3psent = TRUE;
		}
		setcookie("sortby", "");
		$sortby = "";
	}
}
?>