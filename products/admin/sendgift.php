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

@set_time_limit(0);
include "config.inc.php";
include "ashopconstants.inc.php";
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";

$thisdate = date("Y-m-d H:i:s", time()+$timezoneoffset);

function makePassword() {
	$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
	srand ((double) microtime() * 1000000);
	$pwLength = "7"; // this sets the limit on how long the password is.
	for($i = 1; $i <=$pwLength; $i++) {
		$newPass .= $alphaNum[(rand(0,31))];
	}
	return ($newPass);
}

// Get listmessenger groups if applicable...
function parselmconfigstring($lmconfigstring) {
	$returnstring = "";
	$returnstring = substr($lmconfigstring, strpos($lmconfigstring, "\"")+1);
	$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
	$returnstring = substr($returnstring, strpos($returnstring, "\"")+1);
	$returnstring = substr($returnstring, 0, strpos($returnstring, "\""));
	return $returnstring;
}
if ($listmessengerpath && !file_exists("$listmessengerpath/config.inc.php") && file_exists("$listmessengerpath/includes/config.inc.php")) {
	$listmessengerversion = "pro";
	$listmessengerpath .= "/includes";
}
if ($listmessengerpath && file_exists("$listmessengerpath/config.inc.php")) {
	$fp = fopen ("$listmessengerpath/config.inc.php", "r");
	while (!feof($fp)) {
		$buffer = fgets($fp,128);
		if (strpos($buffer, "DATABASE_HOST")) {
			$lmhost = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "DATABASE_NAME")) {
			$lmname = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "DATABASE_USER")) {
			$lmuser = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "DATABASE_PASS")) {
			$lmpass = parselmconfigstring($buffer);
		}
		if (strpos($buffer, "TABLES_PREFIX")) {
			$lmprefix = parselmconfigstring($buffer);
		}
	}
	fclose ($fp);
	$lmdb = @mysqli_connect("$lmhost", "$lmuser", "$lmpass", "$lmname");
	if ($listmessengerversion == "pro") $sql = "SELECT * FROM {$lmprefix}groups";
	else $sql = "SELECT * FROM {$lmprefix}user_groups";
	$result = @mysqli_query($lmdb, $sql);
	$lmselectstring = "";
	if (@mysqli_num_rows($result)) {
		$lmselectstring = "<tr><td align=\"right\" class=\"formlabel\" nowrap>".ANDORLISTMESSENGERGROUP.":</td><td class=\"formlabel\"><select name=\"lmgroup\"><option value=\"0\">".NONE."</option>";
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			if ($listmessengerversion == "pro") $lmgroupid = @mysqli_result($result, $i, "groups_id");
			else $lmgroupid = @mysqli_result($result, $i, "group_id");
			$lmgroupname = @mysqli_result($result, $i, "group_name");
			$lmselectstring .= "<option value=\"$lmgroupid\">$lmgroupname</option>";
		}
		$lmselectstring .= "</select></td></tr>";
	}
	if ($lmgroup && $message) {
		if ($listmessengerversion == "pro") $sql = "SELECT * FROM {$lmprefix}users WHERE group_id='$lmgroup'";
		else $sql = "SELECT * FROM {$lmprefix}user_list WHERE group_id='$lmgroup'";
		$lmresult = @mysqli_query($lmdb, $sql);
	}
	@mysqli_close($lmdb);
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (!$subject || !$message) {

	// Get product name from database...
	$sql="SELECT * FROM product WHERE productid='$productid'";
	if ($userid != "1") $sql .= " AND userid='$userid'";
	$result = @mysqli_query($db, "$sql");
	if (!@mysqli_num_rows($result)) {
		header("Location: editcatalogue.php");
		exit;
	}
	$productname = @mysqli_result($result, 0, "name");
	$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");

	// Check if there are parameters for the product and list them...
	$sql = "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid";
	$paramresult = @mysqli_query($db, "$sql");

	echo "$header
	<center><div class=\"heading\">".SENDAGIFT."</div><p>".SEND." <b>$productname</b> ".ASAFREEGIFTTO."</font></p><form action=\"sendgift.php\" method=\"post\"  enctype=\"multipart/form-data\" name=\"giftform\">
	<table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">";
	if (@mysqli_num_rows($paramresult)) {
		for ($i = 0; $i < @mysqli_num_rows($paramresult); $i++) {
			$parameterid = @mysqli_result($paramresult, $i, "parameterid");
			$caption = @mysqli_result($paramresult, $i, "caption");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid=$parameterid ORDER BY valueid");
			if (@mysqli_num_rows($subresult)) {
				echo "<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$caption:</font></td><td><select name=\"parameter$parameterid\">";
				for ($j = 0; $j < @mysqli_num_rows($subresult); $j++) {
					$valueid = @mysqli_result($subresult, $j, "valueid");
					$value = @mysqli_result($subresult, $j, "value");
					echo "<option value=\"$valueid\">$value";
				}
				echo "</select></td></tr>";
			} else echo "<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">$caption:</font></td><td><input type=\"text\" size=\"30\" name=\"parameter$parameterid\"></td></tr>";
		}
	}
	echo "<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".FIRSTNAME.":</font></td><td><input type=\"text\" name=\"firstname\" size=\"40\"></td></tr>
	<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".LASTNAME.":</font></td><td><input type=\"text\" name=\"lastname\" size=\"40\"></td></tr>
	<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".EMAIL.":</font></td><td><input type=\"text\" name=\"email\" size=\"40\"></td></tr>
	<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".ANDORCSVFILE.":</font></td><td><input type=\"file\" name=\"importfile\"></td></tr>
	<tr><td align=\"right\" class=\"formlabel\">".FIELDDELIMITER.":</td><td><input type=\"text\" size=\"2\" name=\"delimiter\" value=\"$defaultdelimiter\"></td></tr>
	<tr><td align=\"right\" class=\"formlabel\">".FIELDENCLOSURE.":</td><td><input type=\"text\" size=\"2\" name=\"enclosure\" value=\"$defaultenclosure\"></td></tr>";
	if ($lmselectstring) echo $lmselectstring;
	echo "<tr><td align=\"right\" nowrap><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".SUBJECT.":</font></td><td><input type=\"text\" name=\"subject\" size=\"40\"></td></tr>
	<tr><td align=\"right\" valign=\"top\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".MESSAGE.":</td><td><textarea name=\"message\" cols=\"45\" rows=\"10\">".DEFAULTGIFTMESSAGE1.$ashopurl.DEFAULTGIFTMESSAGE2.$ashopurl.DEFAULTGIFTMESSAGE3."</textarea><br><font size=\"1\">".GIFTCODES."</font></td></tr>
	<tr><td></td><td><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"radio\" name=\"mailformat\" value=\"html\" checked> ".HTMLFORMAT." <input type=\"radio\" name=\"mailformat\" value=\"text\"> ".PLAINTEXT."</p><p><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" value=\"".SENDGIFT."\"></font></td></tr></table></form></center>$footer";

	// Close database connection...
	@mysqli_close($db);

} else {

	// Convert double qoutes in message text...
	$message = stripslashes($message);
	$subject = stripslashes($subject);

	// Get any parameter values and store in basket cookiestring...
	$parameterstring = "";
	$downloadable = FALSE;
	$sql = "SELECT * FROM parameters WHERE productid='$productid' ORDER BY parameterid";
	$result = @mysqli_query($db, "$sql");
	if (@mysqli_num_rows($result)) {
		for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
			$parameterid = @mysqli_result($result, $i, "parameterid");
			eval ("\$thisparameter = \$parameter$parameterid;");
			$subresult = @mysqli_query($db, "SELECT * FROM parametervalues WHERE parameterid='$parameterid'");
			if (!@mysqli_num_rows($subresult)) {
				@mysqli_query($db, "INSERT INTO customparametervalues (parameterid, value) VALUES ('$parameterid', '$thisparameter')");
				if (@mysqli_affected_rows($db) == 1) $thisparameter = @mysqli_insert_id($db);
				$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
				if (@mysqli_num_rows($result)) $downloadable = TRUE;
			} else while ($row = @mysqli_fetch_array($subresult)) if ($row["download"] != "none") $downloadable = TRUE;
			$parameterstring .= $thisparameter."b";
		}
	} else {
		$result = @mysqli_query($db, "SELECT * FROM productfiles WHERE productid='$productid'");
		if (@mysqli_num_rows($result)) $downloadable = TRUE;
	}

	// Send to single recipient...
	if ($email) {
		if ($downloadable) {
			// Generate a unique password...
			$password = makePassword();
			$unique = 0;
			while (!$unique) {
				$sql="SELECT password FROM orders WHERE password='$password'";
				$result = mysqli_query($db, "$sql");
				if (@mysqli_num_rows($result) == 0) $unique = 1;
				if($unique != 1) $password = makePassword();
			}
		}
		// Add password and download link to message...
		unset($fullmessage);
		$fullmessage = str_replace("%email%","$email",$message);
		$fullmessage = str_replace("%password%","$password",$fullmessage);
		
		if ($mailformat == "html") $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		else $headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";

		// Store customer information...
		$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email'");
		if (@mysqli_num_rows($result)) $customerid = @mysqli_result($result, 0, "customerid");
		else {
			@mysqli_query($db, "INSERT INTO customer (firstname, lastname, email, address, zip, city, state, country, phone) VALUES ('$firstname', '$lastname', '$email', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown')");
			$customerid = @mysqli_insert_id($db);
		}

		$timestamp = time()+$timezoneoffset;

		// Store the gift in the database...
		$sql = "INSERT INTO orders (customerid, products, password, payoptionid, description, productprices, userid, date, paid) VALUES ('$customerid', '1b{$parameterstring}{$productid}a', '$password', '0', 'gift|$timestamp', '0', '$userid', '$thisdate', '$thisdate')";
		$result = @mysqli_query($db, $sql);

		if (@mysqli_affected_rows($db) == 1) {
			$msg = "giftsent";
			@ashop_mail("$email","$subject","$fullmessage","$headers");
		} else $msg = "gifterror";
	}

	// Send to ListMessenger Group...
    while ($lmrow = @mysqli_fetch_array($lmresult)) {
		if ($listmessengerversion == "pro") {
			$email = $lmrow["email_address"];
			$firstname = $lmrow["firstname"];
			$lastname = $lmrow["lastname"];
		} else {
			$email = $lmrow["user_address"];
			$names = $lmrow["user_name"];
			$names = explode(" ",$names);
			$firstname = $names[0];
			$lastname = $names[1];
		}
		if ($downloadable) {
			// Generate a unique password...
			$password = makePassword();
			$unique = 0;
			while (!$unique) {
				$sql="SELECT password FROM orders WHERE password='$password'";
				$result = mysqli_query($db, "$sql");
				if (@mysqli_num_rows($result) == 0) $unique = 1;
				if($unique != 1) $password = makePassword();
			}

			// Add password and download link to message...
			unset($fullmessage);
			$fullmessage = str_replace("%email%","$email",$message);
			$fullmessage = str_replace("%password%","$password",$fullmessage);
		} else $fullmessage = $message;
		
		if ($mailformat == "html") $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
		else $headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";

		// Store customer information...
		$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email'");
		if (@mysqli_num_rows($result)) $customerid = @mysqli_result($result, 0, "customerid");
		else {
			@mysqli_query($db, "INSERT INTO customer (firstname, lastname, email, address, zip, city, state, country, phone) VALUES ('$firstname', '$lastname', '$email', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown')");
			$customerid = @mysqli_insert_id($db);
		}

		$timestamp = time()+$timezoneoffset;

		// Store the gift in the database...
		$sql = "INSERT INTO orders (customerid, products, password, payoptionid, description, productprices, userid) VALUES ('$customerid', '1b{$parameterstring}{$productid}a', '$password', '0', 'gift|$timestamp', '0', '$userid')";
		$result = @mysqli_query($db, $sql);

		if (@mysqli_affected_rows($db) == 1) {
			$msg = "giftsent";
			@ashop_mail("$email","$subject","$fullmessage","$headers");
		} else $msg = "gifterror";
	}

	// Import customer data from file...
	$fieldnr = array();
	$importfile = str_replace("\t","\\t",$importfile);
	if (!empty($importfile) && is_uploaded_file($importfile)) {
		$importfilesize = filesize($importfile);
		$enclosure = stripslashes($enclosure);
		$delimiter = stripslashes($delimiter);
		if (@move_uploaded_file($importfile, "$ashopspath/products/importgift")) {
			$fp = fopen ("$ashopspath/products/importgift","r");
			if ($fp) {
				$csvline = 0;
				while (!feof ($fp)) {
					unset($customerinfo);
					$customerinfo = fgetcsv($fp, 4096, $delimiter, $enclosure);
					// Get field order from first line of CSV file...
					if ($csvline == 0) {
						unset($fieldnr["firstname"]);
						unset($fieldnr["lastname"]);
						unset($fieldnr["email"]);
						unset($fieldnr["address"]);
						unset($fieldnr["zip"]);
						unset($fieldnr["city"]);
						unset($fieldnr["state"]);
						unset($fieldnr["country"]);
						unset($fieldnr["phone"]);
						foreach ($customerinfo as $cifieldnr=>$cifieldvalue) {
							$pifieldvalue = strtoupper($pifieldvalue);
							switch ($pifieldvalue) {
								case "FIRSTNAME":
									$fieldnr["firstname"] = $cifieldnr;
								    break;
								case "FIRST NAME":
									$fieldnr["firstname"] = $cifieldnr;
									break;
								case "LASTNAME":
									$fieldnr["lastname"] = $cifieldnr;
									break;
								case "LAST NAME":
									$fieldnr["lastname"] = $cifieldnr;
									break;
								case "SURNAME":
									$fieldnr["lastname"] = $cifieldnr;
									break;
								case "EMAIL":
									$fieldnr["email"] = $cifieldnr;
									break;
								case "EMAIL ADDRESS":
									$fieldnr["email"] = $cifieldnr;
									break;
								case "STREET":
									$fieldnr["address"] = $cifieldnr;
									break;
								case "ADDRESS":
									$fieldnr["address"] = $cifieldnr;
									break;
								case "STREET ADDRESS":
									$fieldnr["address"] = $cifieldnr;
									break;
								case "ZIP":
									$fieldnr["zip"] = $cifieldnr;
									break;
								case "ZIP CODE":
									$fieldnr["zip"] = $cifieldnr;
									break;
								case "POSTAL CODE":
									$fieldnr["zip"] = $cifieldnr;
									break;
								case "CITY":
									$fieldnr["city"] = $cifieldnr;
									break;
								case "TOWN":
									$fieldnr["city"] = $cifieldnr;
									break;
								case "COUNTRY":
									$fieldnr["country"] = $cifieldnr;
									break;
								case "PHONE":
									$fieldnr["phone"] = $cifieldnr;
									break;
								case "PHONE NUMBER":
									$fieldnr["phone"] = $cifieldnr;
									break;
							}
						}
						$csvline++;
						continue;
					}

					// Skip empty lines...
					if (empty($customerinfo)) continue;

					// Make sure the information can be stored in the database...
					foreach ($customerinfo as $cifieldnr=>$cifieldvalue) $customerinfo[$cifieldnr] = str_replace("'","\'",$cifieldvalue);

					// Get the firstname, if any...
					if (isset($fieldnr["firstname"])) $firstname = $customerinfo[$fieldnr["firstname"]];
					else $firstname = "Unknown";

					// Get the lastname, if any...
					if (isset($fieldnr["lastname"])) $lastname = $customerinfo[$fieldnr["lastname"]];
					else $lastname = "Unknown";

					// Get the email, if any...
					if (isset($fieldnr["email"])) $email = $customerinfo[$fieldnr["email"]];
					else $email = "Unknown";

					// Get the address, if any...
					if (isset($fieldnr["address"])) $address = $customerinfo[$fieldnr["address"]];
					else $address = "Unknown";

					// Get the zip, if any...
					if (isset($fieldnr["zip"])) $zip = $customerinfo[$fieldnr["zip"]];
					else $zip = "Unknown";

					// Get the city, if any...
					if (isset($fieldnr["city"])) $city = $customerinfo[$fieldnr["city"]];
					else $city = "Unknown";

					// Get the country, if any...
					if (isset($fieldnr["country"])) $country = $customerinfo[$fieldnr["country"]];
					else $country = "Unknown";

					// Get the phone number, if any...
					if (isset($fieldnr["phone"])) $phone = $customerinfo[$fieldnr["phone"]];
					else $phone = "Unknown";

					if ($downloadable) {
						// Generate a unique password...
						$password = makePassword();
						$unique = 0;
						while (!$unique) {
							$sql="SELECT password FROM orders WHERE password='$password'";
							$result = mysqli_query($db, "$sql");
							if (@mysqli_num_rows($result) == 0) $unique = 1;
							if($unique != 1) $password = makePassword();
						}

						// Add password and download link to message...
						unset($fullmessage);
						$fullmessage = str_replace("%email%","$email",$message);
						$fullmessage = str_replace("%password%","$password",$fullmessage);
					} else $fullmessage = $message;

					if ($mailformat == "html") $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
					else $headers = "From: ".un_html($ashopname)."<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";

					// Store customer information...
					$result = @mysqli_query($db, "SELECT * FROM customer WHERE email='$email'");
					if (@mysqli_num_rows($result)) $customerid = @mysqli_result($result, 0, "customerid");
					else {
						@mysqli_query($db, "INSERT INTO customer (firstname, lastname, email, address, zip, city, state, country, phone) VALUES ('$firstname', '$lastname', '$email', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown', 'Unknown')");
						$customerid = @mysqli_insert_id($db);
					}

					$timestamp = time()+$timezoneoffset;

					// Store the gift in the database...
					$sql = "INSERT INTO orders (customerid, products, password, payoptionid, description, productprices, userid) VALUES ('$customerid', '1b{$parameterstring}{$productid}a', '$password', '0', 'gift|$timestamp', '0', '$userid')";
					$result = @mysqli_query($db, $sql);

					if (@mysqli_affected_rows($db) == 1) {
						$msg = "giftsent";
						@ashop_mail("$email","$subject","$fullmessage","$headers");
					} else $msg = "gifterror";
				}
			}
		}
	}
		
	@mysqli_close($db);
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=editcatalogue.php?cat=$cat&resultpage=$resultpage&pid=$pid&search=$search&msg=$msg\"></head></html>";
		exit;
	} else header("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage&pid=$pid&search=$search&msg=$msg");
}
?>