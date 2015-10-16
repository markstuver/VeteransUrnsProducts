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
include "ashopfunc.inc.php";
include "checklogin.inc.php";
include "template.inc.php";
// Get language module...
include "language/$adminlang/editproduct.inc.php";

// Validate variables...
if (!isset($productid) || !is_numeric($productid)) {
	header("Location: editcatalogue.php");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

$thisdate = date("Y-m-d H:i:s", time()+$timezoneoffset);

// Check for uploaded product files...
$numberoffiles = 0;
$findfile = opendir("$ashopspath/updates");
while (false !== ($foundfile = readdir($findfile))) {
	if ($foundfile != "." && $foundfile != ".." && $foundfile != "CVS" && $foundfile != ".htaccess" && $foundfile != ".dmall" && !preg_match("/\.spc/", $foundfile) && substr($foundfile,0,3) != "top" && !preg_match("/index/", $foundfile) && !preg_match("/^[0-9]*$/", $foundfile) && substr($foundfile, 0, 1) != "_" && !is_dir("$ashopspath/updates/$foundfile")) {
		$uploadedfiles[$numberoffiles] = $foundfile;
		$numberoffiles++;
	}
}

if (!$subject || !$message) {

	// Get the oldest and newest order...
	if ($userid == "1") $sql = "SELECT MAX(date) FROM orders WHERE (wholesale IS NULL OR wholesale != '1')";
	else $sql = "SELECT MAX(date) FROM orders WHERE (wholesale IS NULL OR wholesale != '1') AND userid LIKE '%|$userid|%'";
	$result = @mysqli_query($db, "$sql");
	$maxdate = @mysqli_result($result, 0, "MAX(date)");
	if($wholesalecatalog && $userid == "1") {
		$sql = "SELECT MAX(date) FROM orders WHERE wholesale='1'";
		$result = @mysqli_query($db, "$sql");
		$wsmaxdate = @mysqli_result($result, 0, "MAX(date)");
		if($wsmaxdate > $maxdate) $maxdate = $wsmaxdate;
	}
	if ($userid == "1") $sql = "SELECT date FROM orders WHERE (wholesale IS NULL OR wholesale != '1') AND date != '' ORDER BY date LIMIT 1";
	else $sql = "SELECT date FROM orders WHERE (wholesale IS NULL OR wholesale != '1') AND userid LIKE '%|$userid|%' AND date != '' ORDER BY date LIMIT 1";
	$result = @mysqli_query($db, "$sql");
	$mindate = @mysqli_result($result, 0, "date");
	if($wholesalecatalog && $userid == "1") {
		$sql = "SELECT date FROM orders WHERE date != '' AND wholesale='1' ORDER BY date LIMIT 1";
		$result = @mysqli_query($db, "$sql");
		$wsmindate = @mysqli_result($result, 0, "date");
		if($wsmindate && $wsmindate < $mindate) $mindate = $wsmindate;
	}
	$oldestarray = explode("-", $mindate);
	$oldest = $oldestarray[0];
	$newestarray = explode("-", $maxdate);
	$newest = $newestarray[0];
	$fromyears = "";
	$toyears = "";
	for ($thisyear = $oldest; $thisyear<=$newest; $thisyear++) {
		$toyears .= "<option value=\"$thisyear\"";
		if ($thisyear==$newest) $toyears.= " selected";
		$toyears .= ">$thisyear</option>";
		$fromyears .= "<option value=\"$thisyear\">$thisyear</option>";
	}

	// Get the current month and day...
	$currentmonth = date("m", time()+$timezoneoffset);
	$currentday = date("d", time()+$timezoneoffset);


	// Get product name from database...
	$sql="SELECT * FROM product WHERE productid='$productid'";
	if ($userid != "1") $sql .= " AND userid='$userid'";
	$result = @mysqli_query($db, "$sql");
	if (!@mysqli_num_rows($result)) {
		header("Location: editcatalogue.php");
		exit;
	}
	$productname = @mysqli_result($result, $i, "name");

	// Make sure the upload message disappears...
	if (strpos($header, "body") != 0) {
		$newheader = substr($header,1,strpos($header, "body")+3);
		$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "body")+4,strlen($header));
	} else {
		$newheader = substr($header,1,strpos($header, "BODY")+3);
		$newheader .= " onUnload=\"closemessage()\" ".substr($header,strpos($header, "BODY")+4,strlen($header));
	}

	echo "$newheader
	<script language=\"JavaScript\">
		function uploadmessage() 
		{
		  if (document.updateform.updatefile.value != '') {
			  w = window.open('uploadmessage.html','_blank','toolbar=no,location=no,width=350,height=150');
		  }
	    }
        function closemessage()
        {
       	  if (typeof w != 'undefined') w.close();
        }
    </script>
	<div class=\"heading\">".SENDANUPDATE."</div><center><p>".SENDANUPDATEFOR." <b>$productname</b> ".TOCURRENTUSERS."</font></p><form action=\"sendupdate.php\" method=\"post\" enctype=\"multipart/form-data\" name=\"updateform\"><table width=\"500\" border=\"0\" cellspacing=\"0\" cellpadding=\"3\">
	<tr><td align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".UPDATECAPTION.":</font></td><td align=\"left\"><input type=\"text\" name=\"title\" size=\"40\"><script language=\"JavaScript\">document.updateform.title.focus();</script> <font face=\"Arial, Helvetica, sans-serif\" size=\"1\">".OPTIONAL."</font></td></tr>
	<tr><td valign=\"top\" align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".UPDATEFILE.":</font></td><td align=\"left\"><input type=\"file\" name=\"updatefile\"> <font face=\"Arial, Helvetica, sans-serif\" size=\"1\">".OPTIONAL."</font><span class=\"formlabel\">";
	if ($numberoffiles) {
		echo "<br>".ORCHOOSEPREVIOUSLY."<br><select name=\"uploadedfilename\"><option value=\"\"></option>";
		for($i = 0; $i < $numberoffiles; $i++) {
			echo "<option value=\"$uploadedfiles[$i]\">$uploadedfiles[$i]</option>";
		}
		echo "</select>";
	}	
	echo "</span></td></tr><tr><td align=\"center\" colspan=\"2\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".SENDTOCUSTOMERSWHOBOUGHT.":<br><br>	

	<select name=\"startyear\">$fromyears</select>	

	<select name=\"startmonth\"><option value=\"01\" selected>".JAN."</option><option value=\"02\">".FEB."</option><option value=\"03\">".MAR."</option><option value=\"04\">".APR."</option><option value=\"05\">".MAY."</option><option value=\"06\">".JUN."</option><option value=\"07\">".JUL."</option><option value=\"08\">".AUG."</option><option value=\"09\">".SEP."</option><option value=\"10\">".OCT."</option><option value=\"11\">".NOV."</option><option value=\"12\">".DEC."</option></select>

	<select name=\"startday\"><option value=\"01\" selected>1</option>";
	
	for ($i = 2; $i < 32; $i++) {
		echo "<option value=\"";
		if ($i < 10) echo "0";
		echo "$i\">$i</option>";
	}
    echo "</select>

	&nbsp;".THEWORDAND.":   
	
	<select name=\"toyear\">$toyears</select>
	
	<select name=\"tomonth\">";

	echo "<option value=\"01\""; if ($currentmonth == 1) echo "selected"; echo">".JAN."</option>";
	echo "<option value=\"02\""; if ($currentmonth == 2) echo "selected"; echo">".FEB."</option>";
	echo "<option value=\"03\""; if ($currentmonth == 3) echo "selected"; echo">".MAR."</option>";
	echo "<option value=\"04\""; if ($currentmonth == 4) echo "selected"; echo">".APR."</option>";
	echo "<option value=\"05\""; if ($currentmonth == 5) echo "selected"; echo">".MAY."</option>";
	echo "<option value=\"06\""; if ($currentmonth == 6) echo "selected"; echo">".JUN."</option>";
	echo "<option value=\"07\""; if ($currentmonth == 7) echo "selected"; echo">".JUL."</option>";
	echo "<option value=\"08\""; if ($currentmonth == 8) echo "selected"; echo">".AUG."</option>";
	echo "<option value=\"09\""; if ($currentmonth == 9) echo "selected"; echo">".SEP."</option>";
	echo "<option value=\"10\""; if ($currentmonth == 10) echo "selected"; echo">".OCT."</option>";
	echo "<option value=\"11\""; if ($currentmonth == 11) echo "selected"; echo">".NOV."</option>";
	echo "<option value=\"12\""; if ($currentmonth == 12) echo "selected"; echo">".DEC."</option>";

    echo "</select><select name=\"today\">";
	
	for ($i = 1; $i < 32; $i++) {
		echo "<option value=\"";
		if ($i < 10) echo "0";
		echo "$i\"";
		if ($i == $currentday) echo " selected";
		echo ">$i</option>";
	}
    echo "</select></font></td></tr>
	<tr><td align=\"center\" colspan=\"2\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".ALLOWDOWNLOADFOR.": <input type=\"text\" name=\"allowdowndays\" size=\"3\"> ".DAYS.". <font size=\"1\">".OPTIONAL."</font></font></td></tr>
	<tr><td nowrap align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".EMAILSUBJECT.":</td><td align=\"left\"><input type=\"text\" name=\"subject\" size=\"40\"></td></tr>
	<tr><td valign=\"top\" align=\"right\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\">".MESSAGE.":</td><td align=\"left\"><textarea name=\"message\" cols=\"45\" rows=\"10\"></textarea><br><span class=\"sm\">".UPDATECODES."</td></tr>
	<tr><td>&nbsp;</td><td align=\"center\"><font face=\"Arial, Helvetica, sans-serif\" size=\"2\"><input type=\"radio\" name=\"mailformat\" value=\"html\"";
	if ($prefupdmailformat == "html") echo " checked";
	echo "> ".HTMLFORMAT." <input type=\"radio\" name=\"mailformat\" value=\"text\"";
	if ($prefupdmailformat == "text" || !$prefupdmailformat) echo "checked";
	echo "> ".PLAINTEXT."</p><p><input type=\"hidden\" name=\"productid\" value=\"$productid\"><input type=\"hidden\" name=\"cat\" value=\"$cat\"><input type=\"hidden\" name=\"pid\" value=\"$pid\"><input type=\"hidden\" name=\"search\" value=\"$search\"><input type=\"submit\" class=\"widebutton\" value=\"".UPLOADANDORSEND."\" onClick=\"uploadmessage()\"></font></td></tr></table></form></center>$footer";

	// Close database connection...
	@mysqli_close($db);

} else {

	// Store prefered mail format in a cookie...
	if (!$p3psent) header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
	$p3psent = TRUE;
	setcookie("prefupdmailformat","$mailformat", mktime(0,0,0,12,1,2020));

	// Set the earliest and latest purchase date for customers that should receive the update...
	$firstdate = "$startyear-$startmonth-$startday 00:00:00";
	$lastdate = "$toyear-$tomonth-$today 23:59:59";

	// Convert double qoutes in message text...
	$message = stripslashes($message);
	$subject = stripslashes($subject);

	$sendupdatefile = 0;
    if (is_uploaded_file($updatefile) || ($uploadedfilename && file_exists("$ashopspath/updates/$uploadedfilename"))) {
		$sendupdatefile = 1;
		if (!$allowdowndays) $allowdowndays = "0";
		// Generate a unique password...
		function makePassword() {
			$alphaNum = array(2, 3, 4, 5, 6, 7, 8, 9, a, b, c, d, e, f, g, h, i, j, k, m, n, p, q, r, s, t, u, v, w, x, y, z);
			srand ((double) microtime() * 1000000);
			$pwLength = "7"; // this sets the limit on how long the password is.
			for($i = 1; $i <=$pwLength; $i++) {
				$newPass .= $alphaNum[(rand(0,31))];
			}
			return ($newPass);
		}
		$password = makePassword();
		$unique = 0;
		while (!$unique) {
			$sql="SELECT password FROM updates WHERE password='$password'";
			$result = mysqli_query($db, "$sql");
			if (@mysqli_num_rows($result) == 0) $unique = 1;
			$sql="SELECT password FROM orders WHERE password='$password'";
			$result = mysqli_query($db, "$sql");
			if (@mysqli_num_rows($result) == 0) $unique = 1;
			if($unique != 1) $password = makePassword();
		}
	}
		
	$updaterecievers = array();

	// Send the update to all customers that should get it...
	$sql = "SELECT customer.*, orders.products, orders.orderid FROM customer, orders WHERE (orders.products LIKE '%b$productid"."a%' OR orders.products LIKE '%b$productid"."d%') AND orders.date > '$firstdate' AND orders.date < '$lastdate' AND orders.customerid = customer.customerid AND orders.paid != '' AND (orders.reference = '' OR orders.reference IS NULL) AND customer.allowemail='1' AND (orders.wholesale IS NULL or orders.wholesale != '1')";
	$result = @mysqli_query($db, "$sql");
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$orderid = @mysqli_result($result, $i, "orderid");
		$firstname = @mysqli_result($result, $i, "firstname");
		$lastname = @mysqli_result($result, $i, "lastname");
		$email = @mysqli_result($result, $i, "email");
		$products = @mysqli_result($result, $i, "products");
		$preflanguage = @mysqli_result($result, $i, "preflanguage");
		if (!$preflanguage) $preflanguage = "en";
		$customerid = @mysqli_result($result, $i, "customerid");
		$address = @mysqli_result($result, $i, "address");
		$state = @mysqli_result($result, $i, "state");
		$city = @mysqli_result($result, $i, "city");
		$country = @mysqli_result($result, $i, "country");
		$phone = @mysqli_result($result, $i, "phone");

		// Check that this order has not been returned...
		$returnresult = @mysqli_query($db, "SELECT * FROM orders WHERE reference='$orderid' AND price < 0");
		$returned = @mysqli_num_rows($returnresult);

		// Add language specific password and download link to message...
		unset($fullmessage);
		if (!isset($language["$preflanguage"]["DOWNLOADUPDATE"]) && !isset($language["$preflanguage"]["LOGINWITH"])) {
			if (file_exists("$ashoppath/language/$preflanguage/ad_sendupdate.inc.php")) {
				$fp = fopen ("$ashoppath/language/$preflanguage/ad_sendupdate.inc.php", "r");
				while (!feof($fp)) {
					$buffer = fgets($fp);
					if (strpos($buffer, "DOWNLOADUPDATE")) {
						$langstring = substr($buffer, strpos($buffer, ",")+1);
						$langstring = substr($langstring, strpos($langstring, "'")+1);
						$language["$preflanguage"]["DOWNLOADUPDATE"] = substr($langstring, 0, strpos($langstring, "'"));
					}
					if (strpos($buffer, "LOGINWITH")) {
						$langstring = substr($buffer, strpos($buffer, ",")+1);
						$langstring = substr($langstring, strpos($langstring, "'")+1);
						$language["$preflanguage"]["LOGINWITH"] = substr($langstring, 0, strpos($langstring, "'"));
					}
				}
				fclose ($fp);
			} else {
				$language["$preflanguage"]["DOWNLOADUPDATE"] = DEFAULTDOWNLOADUPDATE;
				$language["$preflanguage"]["LOGINWITH"] = DEFAULTLOGINWITH;
			}
		}
		if ($sendupdatefile) {
			if ($mailformat == "html") $fullmessage = "$message<br><br>{$language["$preflanguage"]["DOWNLOADUPDATE"]}: <a href=\"$ashopurl/deliver.php\">$ashopurl/deliver.php</a>. ";
			else $fullmessage .= "$message\r\n\r\n{$language["$preflanguage"]["DOWNLOADUPDATE"]}: $ashopurl/deliver.php ";
			$fullmessage .= $language["$preflanguage"]["LOGINWITH"].": $password";
		} else $fullmessage = $message;

		if (!$returned && $email && !in_array($email, $updaterecievers) && ashop_checkproduct($productid,$products)) {
			$updaterecievers[] = $email;
			if ($mailformat == "html") $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";

			// Personalize the message...
			$sendmessage = str_replace("%firstname%",$firstname,$fullmessage);
			$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
			$sendmessage = str_replace("%email%",$email,$sendmessage);
			$sendmessage = str_replace("%customerid%",$customerid,$sendmessage);
			$sendmessage = str_replace("%address%",$address,$sendmessage);
			$sendmessage = str_replace("%state%",$state,$sendmessage);
			$sendmessage = str_replace("%zip%",$zip,$sendmessage);
			$sendmessage = str_replace("%city%",$city,$sendmessage);
			$sendmessage = str_replace("%country%",$country,$sendmessage);
			$sendmessage = str_replace("%phone%",$phone,$sendmessage);

			// Send the message...
			@ashop_mail("$email","$subject","$sendmessage","$headers");
		}
	}

	// Send the update to all wholesale customers that should get it...
	$sql = "SELECT customer.*, orders.products FROM customer, orders WHERE (orders.products LIKE '%b$productid"."a%' OR orders.products LIKE '%b$productid"."d%') AND orders.date > '$firstdate' AND orders.date < '$lastdate' AND orders.customerid = customer.customerid AND orders.paid != '' AND orders.wholesale='1'";
	$result = mysqli_query($db, "$sql");
	for ($i = 0; $i < @mysqli_num_rows($result); $i++) {
		$firstname = @mysqli_result($result, $i, "firstname");
		$lastname = @mysqli_result($result, $i, "lastname");
		$email = @mysqli_result($result, $i, "email");
		$products = @mysqli_result($result, $i, "products");
		$customerid = @mysqli_result($result, $i, "customerid");
		$address = @mysqli_result($result, $i, "address");
		$state = @mysqli_result($result, $i, "state");
		$city = @mysqli_result($result, $i, "city");
		$country = @mysqli_result($result, $i, "country");
		$phone = @mysqli_result($result, $i, "phone");
		unset($fullmessage);

		if ($sendupdatefile) {
			if ($mailformat == "html") $fullmessage = "$message<br><br>".DEFAULTDOWNLOADUPDATE.": <a href=\"$ashopurl/deliver.php\">$ashopurl/deliver.php</a>. ";
			else $fullmessage .= "$message\r\n\r\n".DEFAULTDOWNLOADUPDATE.": $ashopurl/deliver.php ";
			$fullmessage .= DEFAULTLOGINWITH.": $password";
		} else $fullmessage = $message;

		if ($email && !in_array($email, $updaterecievers) && ashop_checkproduct($productid,$products)) {
			$updaterecievers[] = $email;
			if ($mailformat == "html") $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\nMIME-Version: 1.0\nContent-Type: text/html; charset=iso-8859-1\n";
			else $headers = "From: $ashopname<$ashopemail>\nX-Sender: <$ashopemail>\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <$ashopemail>\n";

			// Personalize the message...
			$sendmessage = str_replace("%firstname%",$firstname,$fullmessage);
			$sendmessage = str_replace("%lastname%",$lastname,$sendmessage);
			$sendmessage = str_replace("%email%",$email,$sendmessage);
			$sendmessage = str_replace("%customerid%",$customerid,$sendmessage);
			$sendmessage = str_replace("%address%",$address,$sendmessage);
			$sendmessage = str_replace("%state%",$state,$sendmessage);
			$sendmessage = str_replace("%zip%",$zip,$sendmessage);
			$sendmessage = str_replace("%city%",$city,$sendmessage);
			$sendmessage = str_replace("%country%",$country,$sendmessage);
			$sendmessage = str_replace("%phone%",$phone,$sendmessage);

			// Send the message...
			@ashop_mail("$email","$subject","$sendmessage","$headers");
		}
	}


	if (count($updaterecievers)) {
		if ($sendupdatefile) {
			if (is_uploaded_file($updatefile)) {
				// Handle the uploaded update file...
				$filename = preg_replace("/%28|%29|%2B/","",urlencode(basename($updatefile_name)));
				move_uploaded_file($updatefile, "$ashopspath/updates/$productid");
			} else if ($uploadedfilename) {
				$filename = preg_replace("/%28|%29|%2B/","",urlencode(basename($uploadedfilename)));
				if (file_exists("$ashopspath/updates/$productid")) unlink("$ashopspath/updates/$productid");
				rename("$ashopspath/updates/$uploadedfilename", "$ashopspath/updates/$productid");
			}
			
			$filename = preg_replace("/%E5|%E4/","a",$filename);
			$filename = preg_replace("/%F6/","o",$filename);
			$filename = preg_replace("/%C5|%C4/","A",$filename);
			$filename = preg_replace("/%D6/","O",$filename);
			$filename = preg_replace("/\+\+\+|\+\+/","+",$filename);

			@chmod("$ashopspath/updates/$productid", 0666);
				
			// Store the update in the database...
			$sql = "SELECT * FROM updates WHERE productid=$productid";
			$result = @mysqli_query($db, $sql);
			if (@mysqli_num_rows($result)) $sql = "UPDATE updates SET title='$title', password='$password', date='$thisdate', alloweddays='$allowdowndays', filename='$filename' WHERE productid='$productid'";
			else $sql = "INSERT INTO updates (productid, title, password, date, alloweddays, filename) VALUES ('$productid', '$title', '$password', '$thisdate', '$allowdowndays', '$filename')";
			$result = @mysqli_query($db, $sql);
		}
		$msg = "sent";
	} else $msg = "notsent";

	@mysqli_close($db);
	if (strstr($SERVER_SOFTWARE, "IIS")) {
		echo "<html><head><meta http-equiv=\"Refresh\" content=\"0; URL=editcatalogue.php?cat=$cat&resultpage=$resultpage&pid=$pid&search=$search&msg=$msg\"></head></html>";
		exit;
	} else header("Location: editcatalogue.php?cat=$cat&resultpage=$resultpage&pid=$pid&search=$search&msg=$msg");
}

?>