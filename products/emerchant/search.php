<?php
@set_time_limit(0);
$emnoinactivitycheck = "true";
include "../admin/checklicense.inc.php";
include "checklogin.inc.php";
include "emfunc.inc.php";
include "template.inc.php";

if (!$searchstring || !$searchtype) {
	header("Location: messages.php?error=incompletesearch");
	exit;
}

// Open database...
$db = @mysqli_connect("$databaseserver", "$databaseuser", "$databasepasswd", "$databasename");

if (extension_loaded("imap")) {
	$result = @mysqli_query($db, "SELECT confvalue FROM emerchant_configuration WHERE confname='mailservertype'");
	$mailservertype = @mysqli_result($result,0,"confvalue");
} else $mailservertype = "pop3";

// Remove old searches...
$result = @mysqli_query($db, "SELECT * FROM emerchant_searches WHERE user='$emerchant_user'");
while ($row = @mysqli_fetch_array($result)) {
	$searchid = $row["id"];
	@mysqli_query($db, "DELETE FROM emerchant_searchresult WHERE searchid='$searchid'");
}
@mysqli_query($db, "DELETE FROM emerchant_searches WHERE user='$emerchant_user'");

// Validate search string...
$searchstring = stripslashes($searchstring);
$searchstring = @mysqli_real_escape_string($db, $searchstring);
$searchstring = strtolower($searchstring);
$searchstring = str_replace("\'","",$searchstring);
$searchstring = str_replace("\"","",$searchstring);
$searchstring = str_replace("/","",$searchstring);
$searchstring = str_replace("\n","",$searchstring);
$searchstring = str_replace(";","",$searchstring);
$searchstring = str_replace("concat","",$searchstring);

// Set dates for use in searches...
$pop3searchstring = "";
$imapsearchstring = "";
if ($datecriteria != "anytime") {
	$now = time()+$timezoneoffset;
	$year = date("Y", $now);
	$month = date("m", $now);
	$day = date("d", $now);
	$datearray = explode("-", $today);
	if ($datecriteria == "today") {
		$pop3fromdate = date("Y-m-d", $now);
		$imapfromdate = date("d-M-Y", $now);
		$pop3searchstring = " AND date = '$pop3fromdate'";
		$pop3searchstring2 = " WHERE date = '$pop3fromdate'";
		$imapsearchstring = " SINCE $imapfromdate";
	} else if ($datecriteria == "last7days") {
		$pop3fromdate = date("Y-m-d", mktime(0,0,0,$month,$day-7,$year));
		$imapfromdate = date("d-M-Y", mktime(0,0,0,$month,$day-8,$year));
		$pop3searchstring = " AND date >= '$pop3fromdate'";
		$pop3searchstring2 = " WHERE date >= '$pop3fromdate'";
		$imapsearchstring = " SINCE $imapfromdate";
	} else if ($datecriteria == "last30days") {
		$pop3fromdate = date("Y-m-d", mktime(0,0,0,$month,$day-30,$year));
		$imapfromdate = date("d-M-Y", mktime(0,0,0,$month,$day-31,$year));
		$pop3searchstring = " AND date >= '$pop3fromdate'";
		$pop3searchstring2 = " WHERE date >= '$pop3fromdate'";
		$imapsearchstring = " SINCE $imapfromdate";
	} else if ($datecriteria == "last3months") {
		$pop3fromdate = date("Y-m-d", mktime(0,0,0,$month-3,$day,$year));
		$imapfromdate = date("d-M-Y", mktime(0,0,0,$month-3,$day-1,$year));
		$pop3searchstring = " AND date >= '$pop3fromdate'";
		$pop3searchstring2 = " WHERE date >= '$pop3fromdate'";
		$imapsearchstring = " SINCE $imapfromdate";
	} else if ($datecriteria == "thisyear") {
		$pop3fromdate = "$year-01-01";
		$lastyear = $year-1;
		$imapfromdate = "31-Dec-$lastyear";
		$pop3searchstring = " AND date >= '$pop3fromdate'";
		$pop3searchstring2 = " WHERE date >= '$pop3fromdate'";
		$imapsearchstring = " SINCE $imapfromdate";
	} else if ($datecriteria == "lastyear") {
		$pop3fromdate = date("Y-m-d", mktime(0,0,0,01,01,$year-1));
		$pop3todate = date("Y-m-d", mktime(0,0,0,12,31,$year-1));
		$imaptodate = date("d-M-Y", mktime(0,0,0,12,31,$year-1));
		$imapfromdate = date("d-M-Y", mktime(0,0,0,12,31,$year-2));
		$pop3searchstring = " AND date >= '$pop3fromdate' AND date <= '$pop3todate'";
		$pop3searchstring2 = " WHERE date >= '$pop3fromdate' AND date <= '$pop3todate'";
		$imapsearchstring = " SINCE $imapfromdate BEFORE $imaptodate";
	}
}

// Save the search and get a search ID...
@mysqli_query($db, "INSERT INTO emerchant_searches (searchstring, searchtype, datecriteria, user) VALUES ('$searchstring', '$searchtype', '$datecriteria', '$emerchant_user')");
$searchid = @mysqli_insert_id($db);

// Search in subject lines...
if ($searchtype == "subject") {
	$searchresult = @mysqli_query($db, "SELECT * FROM emerchant_messages WHERE UPPER(subject) LIKE '%".strtoupper($searchstring)."%'$pop3searchstring");
	while ($row = @mysqli_fetch_array($searchresult)) {
		$messageid = $row["id"];
		@mysqli_query($db, "INSERT INTO emerchant_searchresult (searchid, messageid) VALUES ('$searchid','$messageid')");
	}
}

// Search in message body and/or subject of IMAP messages...
if ($mailservertype == "imap") {
	// Open connection to mail server...
	$paramresult = @mysqli_query($db, "SELECT * FROM emerchant_configuration");
	while ($paramrow = @mysqli_fetch_array($paramresult)) {
		if ($paramrow["confname"] == "pophost") $hostname = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popuser") $popuser = $paramrow["confvalue"];
		if ($paramrow["confname"] == "poppass") $poppass = $paramrow["confvalue"];
		if ($paramrow["confname"] == "popport") $port = $paramrow["confvalue"];
	}
	if ($hostname == "mail.yourdomain.com" || !$hostname) $mailnotconfigured = 1;
	else $mailnotconfigured = 0;
	$authenticated = false;
	if (!$mailnotconfigured) {
		$popfp = @imap_open ("{"."$hostname:$port/notls"."}INBOX", "$popuser", "$poppass");
		if($popfp) $authenticated = true;
	}
	if ($authenticated) {
		if ($searchtype == "subject" || $searchtype == "both") {
			$criteria = "SUBJECT \"$searchstring\"$imapsearchstring";
			$searchresults = @imap_search($popfp, $criteria, SE_UID);
			if ($searchresults && is_array($searchresults)) {
				foreach($searchresults as $messageuid) {
					$result = @mysqli_query($db, "SELECT id FROM emerchant_messages WHERE uid='$messageuid'");
					$messageid = @mysqli_result($result, 0, "id");
					$result = @mysqli_query($db, "SELECT * FROM emerchant_searchresult WHERE searchid='$searchid' AND messageid='$messageid'");
					if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO emerchant_searchresult (searchid, messageid) VALUES ('$searchid', '$messageid')", $db);
				}
			}
		} else if ($searchtype == "message" || $searchtype == "both") {
			$criteria = "BODY \"$searchstring\"$imapsearchstring";
			$searchresults = @imap_search($popfp, $criteria, SE_UID);
			if ($searchresults && is_array($searchresults)) {
				foreach($searchresults as $messageuid) {
					$result = @mysqli_query($db, "SELECT id FROM emerchant_messages WHERE uid='$messageuid'");
					$messageid = @mysqli_result($result, 0, "id");
					$result = @mysqli_query($db, "SELECT * FROM emerchant_searchresult WHERE searchid='$searchid' AND messageid='$messageid'");
					if (!@mysqli_num_rows($result)) @mysqli_query($db, "INSERT INTO emerchant_searchresult (searchid, messageid) VALUES ('$searchid', '$messageid')", $db);
				}
			}
		}
	}
}

// Search in message body and/or subject of POP3 messages...
if ($searchtype == "message" || $searchtype == "both") {
	$searchresult = @mysqli_query($db, "SELECT * FROM emerchant_messages$pop3searchstring2");
	while ($row = @mysqli_fetch_array($searchresult)) {
		if ($mailservertype != "imap" || !$row["uid"]) {
			$messageid = $row["id"];
			$searchsaved = FALSE;
			if ($searchtype == "both") {
				$subject = $row["subject"];
				if (strstr(strtoupper($subject), strtoupper($searchstring))) {
					@mysqli_query($db, "INSERT INTO emerchant_searchresult (searchid, messageid) VALUES ('$searchid','$messageid')");
					$searchsaved = TRUE;
				}
			}
			if (!$searchsaved) {
				$messageline = "";
				unset($fp);
				if (file_exists("$ashoppath/emerchant/mail/cust-$messageid")) $fp = fopen("$ashoppath/emerchant/mail/cust-$messageid","r");
				if ($fp) {
					$searchsaved = FALSE;
					while (!feof ($fp) && !$searchsaved) {
						$messageline = fgets($fp, 4096);
						if (strstr(strtoupper($messageline), strtoupper($searchstring))) {
							@mysqli_query($db, "INSERT INTO emerchant_searchresult (searchid, messageid) VALUES ('$searchid','$messageid')");
							$searchsaved = TRUE;
						}
					}
					fclose($fp);
				}
			}
		}
	}
}

header("Location: messages.php?showsearch=$searchid");
?>